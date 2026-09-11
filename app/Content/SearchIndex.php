<?php

declare(strict_types=1);

namespace App\Content;

use App\Core\Locale;
use App\Core\MirrorTitle;
use App\Core\Router;
use App\Core\SiteLinks;

/**
 * The site's search index, and the scoring that queries it.
 *
 * There is no database here and no external search service: the site is a
 * static export served by a front controller, so the index is built from
 * the export itself and cached as JSON beside the page cache. It is built
 * from Sitemap::pages() rather than from a second walk of the tree,
 * because that method already answers "which URLs does this site actually
 * serve": it drops the feed documents the exporter wrote beside every
 * page, the query-string URLs it saved as files of their own, the second
 * spelling of a page that exists as both a directory and a flat file, and
 * the wp-* directories that hold no pages at all. A search result that
 * 404s is worse than no result, so the two are never allowed to disagree
 * about which pages exist.
 *
 * One index per language. The English tree and the Chinese mirror are
 * page-for-page, so searching one would otherwise return the other's
 * copy of every hit - twice the results, none of them in the language the
 * reader is reading.
 *
 * Building one index reads a few hundred exported files and takes a few
 * seconds, which is why it is cached rather than done per request: the
 * first search after a deploy pays for it, every one after that is served
 * out of the JSON in a few milliseconds.
 *
 * Nothing here needs mbstring: case folding is ASCII-only, which is all a
 * mixed English/Chinese index needs (Chinese has no case), and everything
 * that has to count characters rather than bytes goes through PCRE's /u
 * mode - so the index cannot be the thing that breaks on a host where the
 * extension is missing.
 */
final class SearchIndex
{
    /** Body text kept per page, in characters. Enough to match on, small enough to load every request. */
    private const TEXT_LIMIT = 2400;

    /** How long a built index is served before it is rebuilt from the tree. */
    private const CACHE_TTL = 86400;

    /** Hard ceiling on indexed pages per language, so a runaway tree cannot exhaust memory. */
    private const MAX_PAGES = 5000;

    /** Longest query honoured, in characters; the rest is ignored rather than scored. */
    private const MAX_QUERY = 120;

    /** Most terms one query is split into. */
    private const MAX_TERMS = 8;

    /**
     * URLs that exist but are not answers to a search.
     *
     * comment-form pages are the export's copy of the reply form under a
     * question, carrying the question's own text - so they compete with the
     * page they belong to for the same query. "page/N" is one archive under
     * N names. The rest are legal boilerplate nobody searches for.
     */
    private const SKIP_PATTERN = '~-comment-form(\.html)?$|/page/\d+(\.html)?$|^/(zh/)?(disclaimer|privacy-policy)(\.html)?$~';

    private ?Router $router = null;

    public function __construct(
        private readonly string $baseDir,
        private readonly SiteLinks $links,
    ) {
    }

    /**
     * The pages matching $query in $locale, best first.
     *
     * Matching is AND across terms: every term has to appear somewhere in
     * a page's title, description or indexed body text for that page to be
     * a hit. A reader who types three words means all three - an OR search
     * over a site this size returns most of it.
     *
     * @param string $category when non-empty, only pages filed under this
     *        category (as the homepage's own grid files them) are returned
     * @return list<array{path: string, title: string, category: string, score: float, snippet: string}>
     */
    public function search(string $query, string $locale, string $category = ''): array
    {
        $terms = self::terms($query);

        if ($terms === []) {
            return [];
        }

        $phrase = self::fold($query);
        $hits = [];

        foreach ($this->entries($locale) as $entry) {
            if ($category !== '' && $entry['category'] !== $category) {
                continue;
            }

            $score = $this->score($entry, $terms, $phrase);

            if ($score === null) {
                continue;
            }

            $hits[] = [
                'path' => $entry['path'],
                'title' => $entry['title'],
                'category' => $entry['category'],
                'score' => $score,
                'snippet' => $this->snippet($entry, $terms),
            ];
        }

        // Score first, then title, so a rebuild of the index cannot reorder
        // two equally good hits from one request to the next.
        usort($hits, static function (array $a, array $b): int {
            return $b['score'] <=> $a['score'] ?: strcmp($a['title'], $b['title']);
        });

        return $hits;
    }

    /**
     * How many of these hits fall in each category - the counts behind the
     * facet chips. Busiest category first, ties broken by name so the row
     * cannot reshuffle between two requests for the same query. Only
     * categories that actually have a hit are listed: a chip reading zero
     * is a dead end.
     *
     * @param list<array{path: string, title: string, category: string, score: float, snippet: string}> $hits
     * @return array<string, int>
     */
    public function facets(array $hits): array
    {
        $counts = [];

        foreach ($hits as $hit) {
            if ($hit['category'] !== '') {
                $counts[$hit['category']] = ($counts[$hit['category']] ?? 0) + 1;
            }
        }

        uksort($counts, static fn (string $a, string $b): int => $counts[$b] <=> $counts[$a] ?: strcmp($a, $b));

        return $counts;
    }

    /**
     * Split a query into the terms it is matched on.
     *
     * Latin text splits on anything that is not a letter or digit. CJK
     * does not put spaces between words, so a Chinese query is split into
     * overlapping two-character terms instead - the standard bigram
     * approximation, and the reason "羽毛球" finds pages that write
     * "羽毛球拍" without needing a dictionary to tell where words end.
     *
     * @return list<string>
     */
    public static function terms(string $query): array
    {
        $query = self::fold($query);
        $terms = [];

        // Runs of CJK, and runs of everything else, are tokenised
        // differently, so they are pulled apart first.
        preg_match_all('/[\x{3000}-\x{9FFF}\x{F900}-\x{FAFF}]+|[^\x{3000}-\x{9FFF}\x{F900}-\x{FAFF}]+/u', $query, $runs);

        foreach ($runs[0] ?? [] as $run) {
            if (preg_match('/^[\x{3000}-\x{9FFF}\x{F900}-\x{FAFF}]/u', $run) === 1) {
                $chars = preg_split('//u', $run, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $count = count($chars);

                if ($count === 1) {
                    $terms[] = $chars[0];
                    continue;
                }

                for ($i = 0; $i < $count - 1; $i++) {
                    $terms[] = $chars[$i] . $chars[$i + 1];
                }

                continue;
            }

            foreach (preg_split('/[^\p{L}\p{N}]+/u', $run, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
                // Single letters match half the site; single digits are
                // usually a stray from a phrase like "2 v 2".
                if (preg_match('/^\p{L}$|^\p{N}$/u', $word) !== 1) {
                    $terms[] = $word;
                }
            }
        }

        return array_slice(array_values(array_unique($terms)), 0, self::MAX_TERMS);
    }

    /**
     * The index for one language, from cache when it is fresh.
     *
     * @return list<array{path: string, title: string, description: string, text: string, category: string}>
     */
    public function entries(string $locale): array
    {
        $cacheFile = $this->cacheFile($locale);

        if (is_file($cacheFile) && time() - (int) filemtime($cacheFile) < self::CACHE_TTL) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);

            if (is_array($cached) && isset($cached['entries']) && is_array($cached['entries'])) {
                return $cached['entries'];
            }
        }

        $built = $this->build($locale);

        $this->writeCache($cacheFile, [
            'built' => gmdate('c'),
            'locale' => $locale,
            'entries' => $built,
        ]);

        return $built;
    }

    /**
     * Fingerprint of the indexing logic, folded into the cache filename
     * for the same reason ContentExtractor carries one: the exported files
     * never change, so without it a deploy that changes how pages are
     * indexed keeps answering out of an index built by the old code.
     */
    public static function fingerprint(): string
    {
        $sources = [__FILE__, __DIR__ . '/Sitemap.php', dirname(__DIR__) . '/Core/MirrorTitle.php'];
        $parts = [];

        foreach ($sources as $source) {
            $parts[] = is_file($source) ? (string) filemtime($source) : '0';
        }

        return substr(sha1(implode('|', $parts)), 0, 12);
    }

    /**
     * Read every page of one language out of the tree.
     *
     * @return list<array{path: string, title: string, description: string, text: string, category: string}>
     */
    private function build(string $locale): array
    {
        $entries = [];

        foreach ((new Sitemap($this->baseDir, $this->links))->pages() as $path => $_mtime) {
            if (Locale::of($path) !== $locale || preg_match(self::SKIP_PATTERN, $path) === 1) {
                continue;
            }

            $file = $this->router()->resolve($path);

            if ($file === null) {
                continue;
            }

            $entry = $this->read($file, $path);

            if ($entry === null) {
                continue;
            }

            $entries[] = $entry;

            if (count($entries) >= self::MAX_PAGES) {
                break;
            }
        }

        return $entries;
    }

    /**
     * Title, description, category and body text of one exported page.
     *
     * The content region is the same one ContentExtractor serves from
     * (#main, falling back to #primary), and the theme's widget sidebar
     * comes out of it for the same reason the extractor drops it: that
     * <aside> holds the full site menu, word for word, on all 1,300 pages.
     * Left in, every page would contain every category name, and a search
     * for "net play" would return the whole site.
     *
     * @return array{path: string, title: string, description: string, text: string, category: string}|null
     */
    private function read(string $file, string $path): ?array
    {
        $html = file_get_contents($file);

        if ($html === false) {
            return null;
        }

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);

        $title = $this->flatten($this->first($xpath, '//title'));
        $description = $this->flatten($this->attribute($xpath, '//meta[@name="description"]', 'content'));

        // A mirrored page's exported title is English even though the page
        // is not; the same substitution the extractor makes when it serves
        // the page is made here, so a result is listed under the headline
        // the reader will actually land on. Asked before the strips below,
        // which take the headings' surroundings apart.
        $title = MirrorTitle::resolve($title, $path, $xpath);

        // Read before the breadcrumb itself is dropped below.
        $category = $this->breadcrumbCategory($xpath);

        // Script and style text is not prose; the widget sidebar and the
        // exported footer are the site menu repeated on every page; and
        // both breadcrumb blocks only restate the title and the category,
        // which the entry already carries in fields of their own - left in,
        // they would open every snippet with "Home / ...".
        $strip = '//script | //style | //noscript'
            . ' | //aside[@id="left-sidebar"]'
            . ' | //footer[@id="colophon"] | //div[@id="to-top"]'
            . ' | //*[contains(concat(" ", normalize-space(@class), " "), " breadcrumb-title-wrapper ")]'
            . ' | //*[contains(concat(" ", normalize-space(@class), " "), " breadcr ")]';

        foreach (iterator_to_array($xpath->query($strip)) as $node) {
            $node->parentNode?->removeChild($node);
        }

        $main = $xpath->query('//div[@id="main"]')->item(0) ?? $xpath->query('//div[@id="primary"]')->item(0);
        $text = $main instanceof \DOMNode ? $this->plainText($dom, $main) : '';

        if ($title === '' && $text === '') {
            return null;
        }

        return [
            'path' => $path,
            'title' => $this->headline($title),
            'description' => $description,
            'text' => $this->truncate($text, self::TEXT_LIMIT),
            'category' => $category,
        ];
    }

    /**
     * The category a page is filed under, read from its own breadcrumb -
     * "Home / Badminton Videos / Yonex All England FINAL 2009" means
     * Badminton Videos.
     *
     * This is the site's own taxonomy rather than a second one invented
     * here: every value is a real category archive with a page of its own.
     * The breadcrumb is used in preference to the homepage's category grid
     * because the grid names eight curated topics and lists a few dozen
     * guides, while the breadcrumb covers nearly every page in the tree -
     * and a facet row is only useful if most results can be filed in it.
     *
     * Pages with a one-step breadcrumb (the homepage, the standalone
     * pages) have no category, and appear only under "all results".
     */
    private function breadcrumbCategory(\DOMXPath $xpath): string
    {
        $links = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " breadcrumbs-container ")]//a');
        $label = '';

        foreach ($links as $link) {
            if (!$link instanceof \DOMElement) {
                continue;
            }

            // Only a crumb that points into the category archive is a
            // category. The first crumb is always "Home", and the pages
            // this app adds on top of the export (the directory, the
            // venue listings) have crumbs of their own that name a folder
            // rather than a topic - those are not facets anyone wants.
            $href = $link->getAttribute('href');

            if (!str_contains($href, '/category/')) {
                continue;
            }

            // /category/parent is a placeholder the source WordPress left
            // in its taxonomy: three pages are filed under a category
            // whose name is literally "parent". It is a real archive, so
            // the rule above cannot see anything wrong with it - but a
            // facet chip reading "parent" only looks broken.
            // The export spells the archive both ways - as a directory and
            // as a flat file - so both are matched.
            if (preg_match('~/category/parent(/|\.html|$)~', $href) === 1) {
                continue;
            }

            $text = $this->flatten($link->textContent);

            if ($text !== '') {
                // The last one wins: a deeper breadcrumb ends at the
                // narrowest category the page belongs to.
                $label = $text;
            }
        }

        return $label;
    }

    /**
     * The readable text of a region, with block boundaries honoured.
     *
     * textContent would do this in one call but concatenates adjoining
     * elements without a separator, which welds the last word of a heading
     * to the first word of the paragraph under it ("...FINAL 2009Lin Dan
     * vs..."). Tags become spaces instead, so every word in the index is a
     * word that appears on the page.
     */
    private function plainText(\DOMDocument $dom, \DOMNode $node): string
    {
        $html = $dom->saveHTML($node);

        if ($html === false) {
            return $this->flatten($node->textContent);
        }

        $text = (string) preg_replace('/<[^>]*+>/', ' ', $html);

        return $this->flatten(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * A page's score for one query, or null when it is not a hit.
     *
     * The weights say where a match means the most: a term in the title is
     * what the page is about, a term in the description is what it says it
     * is about, and a term in the body may be one passing mention. Repeats
     * in the body are worth something but are capped, so a long page cannot
     * outrank a short page that is actually on the subject.
     *
     * @param array{path: string, title: string, description: string, text: string, category: string} $entry
     * @param list<string> $terms
     */
    private function score(array $entry, array $terms, string $phrase): ?float
    {
        $title = self::fold($entry['title']);
        $description = self::fold($entry['description']);
        $text = self::fold($entry['text']);

        $score = 0.0;

        foreach ($terms as $term) {
            $inTitle = substr_count($title, $term);
            $inDescription = substr_count($description, $term);
            $inText = substr_count($text, $term);

            if ($inTitle + $inDescription + $inText === 0) {
                return null;
            }

            $score += min($inTitle, 3) * 14.0
                + min($inDescription, 2) * 5.0
                + min($inText, 4) * 1.5;
        }

        // The whole query, in order, in the title is as good as a match
        // gets on a site of guides named after their subject.
        if ($phrase !== '' && str_contains($title, $phrase)) {
            $score += 30.0;

            if (str_starts_with($title, $phrase)) {
                $score += 10.0;
            }
        } elseif ($phrase !== '' && str_contains($text, $phrase)) {
            $score += 6.0;
        }

        // Two pages can say the same thing, one of them a top-level guide
        // and the other a question filed three levels down; the guide is
        // the better answer.
        $score += max(0, 3 - substr_count($entry['path'], '/')) * 0.75;

        // A category archive matches everything its posts match, and its
        // own text is their excerpts - so for any query about a topic it
        // scores like the best page on that topic without being it. It
        // stays in the results, just under the guide it is listing.
        if (str_starts_with(Locale::neutral($entry['path']), '/category/')) {
            $score -= 10.0;
        }

        return $score;
    }

    /**
     * The passage a result is shown with: the first stretch of the page
     * that actually contains a search term, with the terms marked.
     *
     * The window is cut on character boundaries with PCRE rather than with
     * substr, so a snippet can never end halfway through a multi-byte
     * character - which would put a replacement glyph in the middle of
     * every Chinese result.
     *
     * @param array{path: string, title: string, description: string, text: string, category: string} $entry
     * @param list<string> $terms
     */
    private function snippet(array $entry, array $terms): string
    {
        $source = $entry['text'] !== '' ? $entry['text'] : $entry['description'];

        if ($source === '') {
            return '';
        }

        $chars = preg_split('//u', $source, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $folded = self::fold($source);
        $window = 34;
        $length = 200;

        // Where the earliest term lands, as a character offset - which is
        // what the window is measured in.
        $start = 0;

        foreach ($terms as $term) {
            $at = strpos($folded, $term);

            if ($at === false) {
                continue;
            }

            $before = substr($source, 0, $at);
            $offset = (int) preg_match_all('/./us', $before);
            $start = max(0, $offset - $window);
            break;
        }

        $slice = implode('', array_slice($chars, $start, $length));
        $slice = trim($slice);

        if ($start > 0) {
            $slice = '…' . $slice;
        }

        if ($start + $length < count($chars)) {
            $slice .= '…';
        }

        return $this->highlight($slice, $terms);
    }

    /**
     * Mark the query's terms inside an already-escaped passage.
     *
     * The passage is escaped first and matched second, so the only markup
     * this can ever introduce is the <mark> tags themselves - the terms
     * come from the query string, and are escaped before they are used as
     * a pattern.
     *
     * @param list<string> $terms
     */
    private function highlight(string $slice, array $terms): string
    {
        $escaped = htmlspecialchars($slice, ENT_QUOTES, 'UTF-8');
        $patterns = [];

        foreach ($terms as $term) {
            $patterns[] = preg_quote(htmlspecialchars($term, ENT_QUOTES, 'UTF-8'), '/');
        }

        if ($patterns === []) {
            return $escaped;
        }

        return (string) preg_replace(
            '/(' . implode('|', $patterns) . ')/iu',
            '<mark>$1</mark>',
            $escaped,
        );
    }

    /** ASCII-only case folding - all the folding a mixed en/zh index needs. */
    private static function fold(string $value): string
    {
        return strtolower(trim($value));
    }

    /** The page's own title, without the " - Master Badminton" every one of them ends in. */
    private function headline(string $title): string
    {
        $trimmed = trim((string) preg_replace('/\s*[-–|]\s*Master Badminton\s*$/ui', '', $title));

        return $trimmed === '' ? $title : $trimmed;
    }

    /** Collapse the export's line breaks and indentation into single spaces. */
    private function flatten(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function truncate(string $text, int $characters): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return count($chars) <= $characters ? $text : implode('', array_slice($chars, 0, $characters));
    }

    private function first(\DOMXPath $xpath, string $query): string
    {
        $node = $xpath->query($query)->item(0);

        return $node instanceof \DOMNode ? $node->textContent : '';
    }

    private function attribute(\DOMXPath $xpath, string $query, string $name): string
    {
        $node = $xpath->query($query)->item(0);

        return $node instanceof \DOMElement ? $node->getAttribute($name) : '';
    }

    private function router(): Router
    {
        return $this->router ??= new Router($this->baseDir);
    }

    private function cacheFile(string $locale): string
    {
        return $this->baseDir . '/storage/cache/search-index-' . $locale . '-' . self::fingerprint() . '.json';
    }

    /** @param array<string, mixed> $payload */
    private function writeCache(string $file, array $payload): void
    {
        $dir = dirname($file);

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return;
        }

        if (!is_writable($dir)) {
            return;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json !== false) {
            @file_put_contents($file, $json, LOCK_EX);
        }
    }

    /**
     * A raw "s" parameter, cleaned into the query the page works with:
     * whitespace collapsed, control characters gone, and capped in length
     * so a pathological query cannot cost anything to score, or to echo
     * back in a heading.
     */
    public static function clean(string $raw): string
    {
        $query = trim((string) preg_replace('/[\p{C}\s]+/u', ' ', $raw));
        $chars = preg_split('//u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return count($chars) <= self::MAX_QUERY ? $query : implode('', array_slice($chars, 0, self::MAX_QUERY));
    }
}
