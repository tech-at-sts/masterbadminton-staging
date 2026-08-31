<?php

declare(strict_types=1);

namespace App\Core;

use App\Content\HomepageLayout;
use App\Content\PostLayout;

/**
 * Pulls the page <title>, meta description, and main content region out of
 * a legacy exported HTML file. Every page in the legacy tree shares the
 * same WordPress theme wrapper (<div id="main" class="wrapper"> ... </div>),
 * which is exactly the region the shared header.php / footer.php templates
 * are built to bracket, so this is the only thing that needs to change
 * between pages.
 */
final class ContentExtractor
{
    private const DEFAULT_TITLE = 'Master Badminton';

    /** Powers the search filter and "Expand all" toggle on .cat-section blocks. */
    private const CATEGORY_SECTION_SCRIPT = <<<'HTML'
        <script>
        (function () {
            document.querySelectorAll('.cat-section').forEach(function (section) {
                var searchInput = section.querySelector('[data-cat-search]');
                var expandBtn = section.querySelector('[data-cat-expand-all]');
                var cards = Array.prototype.slice.call(section.querySelectorAll('.cat-accordion'));

                if (searchInput) {
                    searchInput.addEventListener('input', function () {
                        var query = searchInput.value.trim().toLowerCase();
                        cards.forEach(function (card) {
                            var matches = query === '' || card.textContent.toLowerCase().indexOf(query) !== -1;
                            card.style.display = matches ? '' : 'none';
                            if (query !== '' && matches) {
                                card.open = true;
                            }
                        });
                    });
                }

                if (expandBtn) {
                    expandBtn.addEventListener('click', function () {
                        var allOpen = cards.every(function (card) { return card.open; });
                        cards.forEach(function (card) { card.open = !allOpen; });
                        expandBtn.textContent = allOpen ? 'Expand all' : 'Collapse all';
                    });
                }
            });
        })();
        </script>
        HTML;

    /**
     * Fingerprint of the extraction logic itself.
     *
     * ContentCache keys entries on the source file and compares mtimes
     * against it, but the exported *.html files never change - only this
     * code does. Without folding a code fingerprint into the cache key, a
     * deployment with a persisted storage/cache keeps serving HTML that was
     * extracted by an older version of this class (which is how the retired
     * TranslatePress floater survived alongside the header's own language
     * switcher, rendering two stacked bubbles).
     */
    public static function fingerprint(): string
    {
        $sources = [
            __FILE__,
            __DIR__ . '/LinkRewriter.php',
            __DIR__ . '/Locale.php',
            __DIR__ . '/SiteLinks.php',
            dirname(__DIR__) . '/Content/HomepageLayout.php',
            dirname(__DIR__) . '/Content/PostLayout.php',
        ];
        $parts = [];

        foreach ($sources as $source) {
            $parts[] = is_file($source) ? (string) filemtime($source) : '0';
        }

        return substr(sha1(implode('|', $parts)), 0, 12);
    }

    /**
     * @param string $baseDir the document root the exported tree sits in;
     *        the source file's position under it is what says which
     *        language the page is written in
     * @param LinkRewriter|null $links repoints in-content links at this
     *        site and at the language of the page being extracted. Optional
     *        so the extractor stays usable on its own, but index.php always
     *        supplies one - without it a reader on the Chinese mirror falls
     *        back into the English tree on the first link they follow.
     */
    public function __construct(
        private readonly string $baseDir,
        private readonly ?LinkRewriter $links = null,
    ) {
    }

    public function extract(string $filePath): PageContent
    {
        $html = file_get_contents($filePath);

        if ($html === false) {
            return new PageContent(self::DEFAULT_TITLE, '', '');
        }

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);

        // Force UTF-8 interpretation regardless of what libxml would otherwise
        // guess, without corrupting the document (standard DOMDocument trick).
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);

        $title = $this->firstText($xpath, '//title');
        $description = $this->firstAttribute($xpath, '//meta[@name="description"]', 'content');

        $main = $xpath->query('//div[@id="main"]')->item(0)
            ?? $xpath->query('//div[@id="primary"]')->item(0);

        $this->stripSidebar($xpath);
        $this->stripLegacyFooter($xpath, $main);
        $this->stripLegacyLanguageSwitcher($xpath);
        $this->stripHomeCategoryColumn($dom, $xpath);
        $hasCategorySections = $this->convertCategoryGridToAccordion($dom, $xpath);

        // Before any layout surgery: the transforms below move anchors
        // around, and the rewriter needs to see them where the export put
        // them to know what they were written relative to.
        $this->links?->rewrite($xpath, $filePath);

        (new HomepageLayout())->apply($dom, $xpath);
        $shape = (new PostLayout())->apply($dom, $xpath, $main, Locale::of($this->publicPathOf($filePath)));

        $pageStyle = $this->extractPageStyle($xpath);

        $content = $main instanceof \DOMNode ? $this->innerHtml($dom, $main) : '';

        // A few listing pages carry no heading of their own in the body -
        // the theme only ever named them in <title>. Rather than leave the
        // hero blank, or invent a name for the page, it borrows the one the
        // page already has, minus the site suffix every title repeats.
        if ($shape !== null && ($shape['hero']['title'] ?? '') === '') {
            $shape['hero']['title'] = $this->headline($title ?? '');
        }

        return new PageContent(
            $title !== null && $title !== '' ? $title : self::DEFAULT_TITLE,
            $description ?? '',
            $pageStyle . $content . ($hasCategorySections ? self::CATEGORY_SECTION_SCRIPT : ''),
            $shape['layout'] ?? '',
            $shape['hero'] ?? null,
        );
    }

    /** The page's own <title>, without the " - Master Badminton" every one of them ends in. */
    private function headline(string $title): string
    {
        return trim((string) preg_replace('/\s*[-–|]\s*' . preg_quote(self::DEFAULT_TITLE, '/') . '\s*$/u', '', $title));
    }

    /**
     * Where in the exported tree the source file sits, as a site path. The
     * layout of the tree is what marks a page's language - the mirror is
     * everything under zh/ - so this is all the extractor needs to know to
     * hand the right language to the layout transforms.
     */
    private function publicPathOf(string $filePath): string
    {
        $base = realpath($this->baseDir);
        $file = realpath($filePath);

        if ($base === false || $file === false || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
            return '/';
        }

        return '/' . str_replace(DIRECTORY_SEPARATOR, '/', substr($file, strlen($base) + 1));
    }

    private function firstText(\DOMXPath $xpath, string $query): ?string
    {
        $node = $xpath->query($query)->item(0);

        return $node?->textContent;
    }

    private function firstAttribute(\DOMXPath $xpath, string $query, string $attribute): ?string
    {
        $node = $xpath->query($query)->item(0);

        if (!$node instanceof \DOMElement) {
            return null;
        }

        return $node->getAttribute($attribute);
    }

    private function innerHtml(\DOMDocument $dom, \DOMNode $node): string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }

        return $html;
    }

    /**
     * Every legacy page embeds a left-hand category sidebar (<aside
     * id="left-sidebar">) inside its own content. It's now replaced by the
     * unified navigation bar in templates/header.php, so it's stripped here
     * — the one place all legacy content passes through — rather than
     * editing every exported page.
     */
    private function stripSidebar(\DOMXPath $xpath): void
    {
        $sidebar = $xpath->query('//aside[@id="left-sidebar"]')->item(0);

        $sidebar?->parentNode?->removeChild($sidebar);
    }

    /**
     * Drop the exported page's own footer and back-to-top button when they
     * end up inside the extracted region.
     *
     * They normally sit outside #main and are simply not extracted. On the
     * listing pages they are pulled in: WordPress cut some post excerpts in
     * the middle of their markup, leaving <div>s unclosed, and the parser
     * then nests everything that follows - the remaining posts, the pager,
     * and the page's footer - inside the broken excerpt. templates/footer.php
     * renders the site's one footer, so the smuggled copy is removed here.
     */
    private function stripLegacyFooter(\DOMXPath $xpath, ?\DOMNode $main): void
    {
        if (!$main instanceof \DOMElement) {
            return;
        }

        // #fb-root is deliberately not in this list: the exported pages
        // carry the Facebook SDK loader inside the content region, and it
        // is the only copy on the page, so removing it would leave the
        // like button and comment embeds dead.
        $query = './/footer[@id="colophon"] | .//div[@id="to-top"]';

        foreach (iterator_to_array($xpath->query($query, $main)) as $node) {
            $node->parentNode?->removeChild($node);
        }
    }

    /**
     * The exported pages still embed the retired TranslatePress floating
     * language switcher (#trp-floater-ls, pinned bottom-right) inside their
     * own content. templates/header.php now renders the site's single
     * switcher, so leaving the legacy one in place stacks a second bubble in
     * the same corner. Strip it here, where all legacy content passes
     * through, rather than editing 1,400+ exported files.
     */
    private function stripLegacyLanguageSwitcher(\DOMXPath $xpath): void
    {
        $query = '//*[@id="trp-floater-ls"]'
            . ' | //*[contains(concat(" ", normalize-space(@class), " "), " trp-language-switcher-container ")]'
            . ' | //*[contains(concat(" ", normalize-space(@class), " "), " trp_language_switcher_shortcode ")]';

        foreach (iterator_to_array($xpath->query($query)) as $node) {
            $node->parentNode?->removeChild($node);
        }
    }

    /**
     * The homepage (and a couple of pages that reused its layout) carries a
     * "side-home-tw" column repeating the same Playing the Game / Equipment
     * / Resources / Just for Fun links now covered by the unified nav bar.
     * Drop it and let its sibling column take the freed-up width.
     */
    private function stripHomeCategoryColumn(\DOMDocument $dom, \DOMXPath $xpath): void
    {
        $column = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " side-home-tw ")]')->item(0);

        if (!$column instanceof \DOMElement) {
            return;
        }

        $row = $column->parentNode;
        $column->parentNode?->removeChild($column);

        if (!$row instanceof \DOMElement) {
            return;
        }

        foreach ($xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " vc_col-sm-9 ")]', $row) as $sibling) {
            if ($sibling instanceof \DOMElement) {
                $sibling->setAttribute(
                    'class',
                    trim(str_replace('vc_col-sm-9', 'vc_col-sm-12', ' ' . $sibling->getAttribute('class') . ' ')),
                );
            }
        }
    }

    /**
     * The "All Categories" grid is a set of fixed-height, clipped-overflow
     * boxes in the legacy markup — really just a preview, not an accordion.
     * WPBakery also splits it across more than one row carrying the
     * "catfrbn" class (four columns per inner row rather than one row of
     * eight), so every such row on the page is treated as one logical grid:
     * cards from all of them are collected and rendered as a single
     * section — one heading, one search box, one "expand all" toggle, and
     * a two-column grid of <details> cards that actually expand and
     * collapse. Returns whether a section was built, so the caller knows
     * whether to attach the small script that drives search/expand-all.
     */
    private function convertCategoryGridToAccordion(\DOMDocument $dom, \DOMXPath $xpath): bool
    {
        $rows = iterator_to_array($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " catfrbn ")]'));

        if ($rows === []) {
            return false;
        }

        $cards = [];

        foreach ($rows as $row) {
            if (!$row instanceof \DOMElement) {
                continue;
            }

            foreach ($xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " vc_column-inner ")]', $row) as $inner) {
                if (!$inner instanceof \DOMElement) {
                    continue;
                }

                $heading = $xpath->query('.//h2[contains(concat(" ", normalize-space(@class), " "), " vc_custom_heading ")]', $inner)->item(0);
                $list = $xpath->query('.//ul', $inner)->item(0);

                if (!$heading instanceof \DOMElement || !$list instanceof \DOMElement) {
                    continue;
                }

                $count = $xpath->query('./li', $list)->length;

                $details = $dom->createElement('details');
                $details->setAttribute('class', 'cat-accordion');

                $summary = $dom->createElement('summary');

                $cardTitle = $dom->createElement('span');
                $cardTitle->setAttribute('class', 'cat-accordion-title');
                $cardTitle->textContent = trim($heading->textContent);
                $summary->appendChild($cardTitle);

                $meta = $dom->createElement('span');
                $meta->setAttribute('class', 'cat-accordion-meta');

                $countBadge = $dom->createElement('span');
                $countBadge->setAttribute('class', 'cat-accordion-count');
                $countBadge->textContent = (string) $count;
                $meta->appendChild($countBadge);

                $chevron = $dom->createElement('span');
                $chevron->setAttribute('class', 'cat-accordion-chevron');
                $chevron->textContent = '›';
                $meta->appendChild($chevron);

                $summary->appendChild($meta);
                $details->appendChild($summary);

                $body = $dom->createElement('div');
                $body->setAttribute('class', 'cat-accordion-body');

                $list->parentNode?->removeChild($list);
                $body->appendChild($list);
                $details->appendChild($body);

                $cards[] = $details;
            }
        }

        if ($cards === []) {
            return false;
        }

        $firstRow = $rows[0];

        // Drop the legacy "ALL CATEGORIES" WPBakery heading right before the
        // first row — the new section renders its own heading/subtitle.
        $oldHeading = $xpath->query(
            'preceding-sibling::h2[contains(concat(" ", normalize-space(@class), " "), " vc_custom_heading ")][1]',
            $firstRow,
        )->item(0);
        $oldHeading?->parentNode?->removeChild($oldHeading);

        $section = $dom->createElement('div');
        $section->setAttribute('class', 'cat-section');

        $sectionTitle = $dom->createElement('h2');
        $sectionTitle->setAttribute('class', 'cat-section-title');
        $sectionTitle->textContent = 'All Categories';
        $section->appendChild($sectionTitle);

        $subtitle = $dom->createElement('p');
        $subtitle->setAttribute('class', 'cat-section-subtitle');
        $subtitle->textContent = count($cards) . ' categories · click to expand';
        $section->appendChild($subtitle);

        $controls = $dom->createElement('div');
        $controls->setAttribute('class', 'cat-section-controls');

        $searchWrap = $dom->createElement('div');
        $searchWrap->setAttribute('class', 'cat-search-wrap');

        $searchIcon = $dom->createElement('span');
        $searchIcon->setAttribute('class', 'cat-search-icon');
        $searchIcon->textContent = '🔍';
        $searchWrap->appendChild($searchIcon);

        $searchInput = $dom->createElement('input');
        $searchInput->setAttribute('type', 'text');
        $searchInput->setAttribute('class', 'cat-search-input');
        $searchInput->setAttribute('placeholder', 'Search categories or topics…');
        $searchInput->setAttribute('data-cat-search', '');
        $searchWrap->appendChild($searchInput);

        $controls->appendChild($searchWrap);

        $expandBtn = $dom->createElement('button');
        $expandBtn->setAttribute('type', 'button');
        $expandBtn->setAttribute('class', 'cat-expand-all');
        $expandBtn->setAttribute('data-cat-expand-all', '');
        $expandBtn->textContent = 'Expand all';
        $controls->appendChild($expandBtn);

        $section->appendChild($controls);

        $grid = $dom->createElement('div');
        $grid->setAttribute('class', 'cat-grid');

        foreach ($cards as $card) {
            $grid->appendChild($card);
        }

        $section->appendChild($grid);

        $firstRow->parentNode?->replaceChild($section, $firstRow);

        foreach (array_slice($rows, 1) as $extraRow) {
            $extraRow?->parentNode?->removeChild($extraRow);
        }

        return true;
    }

    /**
     * WPBakery renders a per-page <style id="js_composer_front-inline-css">
     * block in <head> for the shortcode layouts actually used on that page
     * (icon grids, category boxes, etc.). Extraction only keeps #main, so
     * without this the page-specific layout CSS is silently lost. Pull it
     * along with the content so each page keeps its own look.
     */
    private function extractPageStyle(\DOMXPath $xpath): string
    {
        $style = $xpath->query('//style[@id="js_composer_front-inline-css"]')->item(0);

        return $style instanceof \DOMNode ? $style->ownerDocument->saveHTML($style) : '';
    }
}
