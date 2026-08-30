<?php

declare(strict_types=1);

namespace App\Content;

use App\Core\PageContent;
use App\Core\Router;

/**
 * Builds the standalone category directory page served at /categories (and
 * /zh/categories on the Chinese mirror), the page the "Category" item in
 * the navigation bar points at.
 *
 * There is no exported HTML file for this URL: the legacy site never had a
 * category directory of its own, it only had the "All Categories" block
 * embedded in the homepage. Rather than duplicating that block into a new
 * static file - which would go stale the moment the homepage export is
 * refreshed - this class reads the homepage export and re-presents the very
 * same data as a full page.
 *
 * The page follows the shape of the live /techniques listing: a sidebar of
 * sections beside a grid of picture cards, one card per guide. Each card's
 * artwork is that guide's own og:image, read from the guide's exported
 * page; guides that never had a featured image fall back to a plain tile
 * carrying their category's icon, which is what the live listing does too.
 *
 * Nothing here writes category copy. Category names and guide titles are
 * the text of the homepage's own nodes, and every link target is the
 * homepage's own href - only the *form* of a link is rewritten (see
 * normaliseHref), so links written relative to the homepage still resolve
 * from a page that lives at a different URL. The page's own furniture -
 * title, hero, search placeholder, counts - is the one thing authored
 * here, in COPY.
 */
final class CategoryDirectory
{
    /**
     * Virtual URL => the exported homepage that supplies its categories.
     *
     * The Chinese mirror is a page-for-page copy of the English tree, so it
     * gets its own directory built from its own homepage, in its own
     * language, instead of falling back to the English one.
     */
    private const SOURCES = [
        '/categories' => 'index.html',
        '/zh/categories' => 'zh/index.html',
    ];

    /** Per-language page furniture. Keyed by the virtual URL above. */
    private const COPY = [
        '/categories' => [
            'title' => 'All Badminton Categories - Master Badminton',
            'description' => 'Browse every badminton topic on Master Badminton - rules, basics, strokes, techniques, net play, smashing, advanced skills and equipment - in one place.',
            'search' => 'Search categories or guides…',
            'guides_one' => '%d guide',
            'guides_many' => '%d guides',
            'empty' => 'No guides match that search.',
            'hero_eyebrow' => 'Browse the site',
            'hero_lead' => 'All',
            'hero_key' => 'Categories',
            'hero_blurb' => 'Every badminton topic on Master Badminton in one place - pick a category and go straight to the guide you need.',
            'sidebar' => 'Categories',
            'view_all' => 'View section',
        ],
        '/zh/categories' => [
            'title' => '所有羽毛球分类 - Master Badminton',
            'description' => '在一个页面浏览 Master Badminton 的所有羽毛球主题，选择分类即可直接前往你需要的教程。',
            'search' => '搜索分类或教程…',
            'guides_one' => '%d 篇教程',
            'guides_many' => '%d 篇教程',
            'empty' => '没有符合的教程。',
            'hero_eyebrow' => '浏览网站',
            'hero_lead' => '所有',
            'hero_key' => '分类',
            'hero_blurb' => 'Master Badminton 的所有羽毛球主题都在这里 - 选择一个分类，直接前往你需要的教程。',
            'sidebar' => '分类',
            'view_all' => '查看该分类',
        ],
    ];

    private ?Router $router = null;

    /**
     * Featured image per guide path, for the life of one build. A handful
     * of guides are listed under two categories (the jump smash is both a
     * Technique and a Smash), and their page should be opened once.
     *
     * @var array<string, string|null>
     */
    private array $images = [];

    public function __construct(private readonly string $baseDir)
    {
    }

    /** Whether this URL is one of the virtual directory pages. */
    public static function handles(string $path): bool
    {
        return isset(self::SOURCES[self::normalisePath($path)]);
    }

    /**
     * Hero copy for the directory page, in the language of the tree it
     * belongs to. It lives here with the rest of the page's copy even
     * though templates/header.php is what paints it: the hero band is a
     * child of .site-header, which is the header template's to render.
     *
     * @return array{eyebrow: string, lead: string, key: string, blurb: string}|null
     */
    public static function hero(string $path): ?array
    {
        $copy = self::COPY[self::normalisePath($path)] ?? null;

        if ($copy === null) {
            return null;
        }

        return [
            'eyebrow' => $copy['hero_eyebrow'],
            'lead' => $copy['hero_lead'],
            'key' => $copy['hero_key'],
            'blurb' => $copy['hero_blurb'],
        ];
    }

    /**
     * Absolute path of the exported homepage this URL's directory is built
     * from, or null when the URL is not a directory page or that homepage
     * is missing - in which case the caller falls through to its normal 404
     * handling. Doubles as the cache key for the built page.
     */
    public function sourceFile(string $path): ?string
    {
        $source = self::SOURCES[self::normalisePath($path)] ?? null;

        if ($source === null) {
            return null;
        }

        $file = $this->baseDir . '/' . $source;

        return is_file($file) ? $file : null;
    }

    /**
     * Fingerprint of this builder, folded into the cache key by the front
     * controller. The exported homepage never changes, only this code does,
     * so without it a persisted storage/cache would keep serving a
     * directory page built by an older version after a deploy - the same
     * reason ContentExtractor carries one.
     *
     * The "categories-" prefix matters as much: the directory and the
     * homepage are cached from the very same source file, and the cache
     * keys on (file, version), so only a version string that can never
     * collide with ContentExtractor's keeps the two entries apart.
     */
    public static function fingerprint(): string
    {
        return 'categories-' . substr(sha1((string) (is_file(__FILE__) ? filemtime(__FILE__) : 0)), 0, 12);
    }

    /**
     * Render the directory page.
     *
     * A PageContent with empty html means "there is nothing to serve here"
     * - the URL is not a directory page, or the homepage it would be built
     * from is missing or carries no category grid. It is returned rather
     * than null so the result can go straight through ContentCache, which
     * deals in PageContent; the caller checks the html before rendering.
     */
    public function build(string $path): PageContent
    {
        $empty = new PageContent('', '', '');
        $path = self::normalisePath($path);
        $file = $this->sourceFile($path);

        if ($file === null) {
            return $empty;
        }

        $source = self::SOURCES[$path];
        $html = file_get_contents($file);

        if ($html === false) {
            return $empty;
        }

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $categories = $this->collectCategories(new \DOMXPath($dom), $source);

        if ($categories === []) {
            return $empty;
        }

        $copy = self::COPY[$path];

        return new PageContent($copy['title'], $copy['description'], $this->render($categories, $copy));
    }

    /**
     * Pull the categories out of the homepage's WPBakery markup.
     *
     * The grid is split across one or more rows carrying the "catfrbn"
     * class, each column holding a heading and a link list - the same shape
     * ContentExtractor turns into the homepage accordion. The eight-icon
     * strip (ul#thct) sits above the grid in the same order, so its short
     * tab labels, artwork and section links pair up with the categories
     * positionally.
     *
     * @return list<array{
     *     id: string, title: string, label: string, href: string, icon: ?string,
     *     guides: list<array{href: string, title: string, image: ?string}>
     * }>
     */
    private function collectCategories(\DOMXPath $xpath, string $source): array
    {
        $tabs = $this->collectTabs($xpath, $source);

        $categories = [];
        $used = [];

        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " catfrbn ")]') as $row) {
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

                $guides = $this->collectGuides($xpath, $list, $source);

                if ($guides === []) {
                    continue;
                }

                $title = $this->flatten($heading->textContent);
                $index = count($categories);
                $tab = $tabs[$index] ?? ['label' => '', 'href' => '', 'icon' => null];

                $categories[] = [
                    'id' => $this->uniqueId($title, $index, $used),
                    'title' => $title,
                    'label' => $tab['label'] !== '' ? $tab['label'] : $title,
                    'href' => $tab['href'],
                    'icon' => $tab['icon'],
                    'guides' => $guides,
                ];
            }
        }

        return $categories;
    }

    /**
     * The icon strip carries three things the grid below it does not: a
     * short tab label ("Basics" where the card heading reads "Badminton
     * Basics"), the artwork, and a link to the section's own archive page.
     *
     * @return list<array{label: string, href: string, icon: ?string}>
     */
    private function collectTabs(\DOMXPath $xpath, string $source): array
    {
        $tabs = [];

        foreach ($xpath->query('//ul[@id="thct"]/li') as $tab) {
            if (!$tab instanceof \DOMElement) {
                continue;
            }

            // Each <li> holds two links to the same target: one around the
            // label, one around the icon. The label is the one without an
            // image inside it.
            $anchor = $xpath->query('.//a[not(.//img)]', $tab)->item(0);
            $link = $anchor instanceof \DOMElement ? $anchor : $xpath->query('.//a[@href]', $tab)->item(0);
            $image = $xpath->query('.//img[@src]', $tab)->item(0);

            $tabs[] = [
                'label' => $anchor instanceof \DOMElement ? $this->flatten($anchor->textContent) : '',
                'href' => $link instanceof \DOMElement ? $this->normaliseHref($link->getAttribute('href'), $source) : '',
                'icon' => $image instanceof \DOMElement
                    ? $this->normaliseHref($image->getAttribute('src'), $source)
                    : null,
            ];
        }

        return $tabs;
    }

    /**
     * One entry per <li> in a category's list.
     *
     * The title is the list item's own text rather than the anchor's: the
     * homepage has one row whose label straddles the link ("Defensive High
     * Clear/lo</a>b"), and reading the row keeps that word whole.
     *
     * @return list<array{href: string, title: string, image: ?string}>
     */
    private function collectGuides(\DOMXPath $xpath, \DOMElement $list, string $source): array
    {
        $guides = [];

        foreach ($xpath->query('./li', $list) as $item) {
            if (!$item instanceof \DOMElement) {
                continue;
            }

            $anchor = $xpath->query('.//a[@href]', $item)->item(0);
            $title = $this->flatten($item->textContent);

            if (!$anchor instanceof \DOMElement || $title === '') {
                continue;
            }

            $href = $this->normaliseHref($anchor->getAttribute('href'), $source);

            $guides[] = [
                'href' => $href,
                'title' => $title,
                'image' => $this->featuredImage($href),
            ];
        }

        return $guides;
    }

    /**
     * A guide's own featured image, as the exported page declares it.
     *
     * The Router resolves the link to the file that serves it, so this
     * follows the same candidates a request would (page.html, its
     * directory's index.html, the aliases). Only the first 64KB is read,
     * cut at </head>, and only for the og:image tag - the alternative, a
     * DOMDocument per guide, would parse forty full articles to pull forty
     * strings out of their <head>s.
     *
     * Null means the guide never had a featured image, and its card falls
     * back to the category-icon tile.
     */
    private function featuredImage(string $href): ?string
    {
        // Only pages in this tree have an export to read. Anything that
        // still points off-site after normaliseHref (the shop, say) is not
        // looked up: its path could collide with a local file's.
        if (!str_starts_with($href, '/')) {
            return null;
        }

        $path = (string) parse_url($href, PHP_URL_PATH);

        if (array_key_exists($path, $this->images)) {
            return $this->images[$path];
        }

        $this->images[$path] = null;
        $this->router ??= new Router($this->baseDir);
        $file = $this->router->resolve($path);

        if ($file === null) {
            return null;
        }

        $handle = @fopen($file, 'rb');

        if ($handle === false) {
            return null;
        }

        $head = (string) fread($handle, 65536);
        fclose($handle);

        $end = stripos($head, '</head>');

        if ($end !== false) {
            $head = substr($head, 0, $end);
        }

        if (preg_match('/<meta[^>]+og:image[^>]+content=["\']([^"\']+)["\']/i', $head, $match) !== 1
            && preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+og:image/i', $head, $match) !== 1) {
            return null;
        }

        $url = trim(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'));

        return $this->images[$path] = ($url === '' ? null : $url);
    }

    /**
     * @param array<string, true> $used
     */
    private function uniqueId(string $title, int $index, array &$used): string
    {
        $slug = strtolower(trim($title));
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $slug), '-');

        if ($slug === '') {
            $slug = 'section-' . ($index + 1);
        }

        $id = 'category-' . $slug;
        $suffix = 2;

        while (isset($used[$id])) {
            $id = 'category-' . $slug . '-' . $suffix++;
        }

        $used[$id] = true;

        return $id;
    }

    /**
     * Turn a link written for the homepage into one that resolves from
     * /categories.
     *
     * Three shapes need handling and none of them changes where the link
     * points, only how it is written:
     *
     * - absolute URLs back to masterbadminton.com become site-relative, so
     *   browsing the directory keeps you on the host you are already on
     *   (staging, local, or production);
     * - links relative to the homepage ("category/badminton-drop.html",
     *   "../images/icons/net.png" on the mirror) are resolved against the
     *   source page's own directory and emitted root-relative, because a
     *   relative link would otherwise resolve against /categories;
     * - the "page.html/index.html" form the export uses for directory
     *   pages is collapsed to "page.html", which the Router resolves to the
     *   same file.
     *
     * Anything else - mailto:, other hosts, in-page anchors - is left alone.
     */
    private function normaliseHref(string $href, string $source): string
    {
        $href = trim($href);

        if ($href === '' || str_starts_with($href, '#')) {
            return $href;
        }

        $scheme = parse_url($href, PHP_URL_SCHEME);
        $host = parse_url($href, PHP_URL_HOST);

        if ($host !== null) {
            $bare = preg_replace('/^www\./', '', strtolower($host));

            if ($bare !== 'masterbadminton.com') {
                return $href;
            }
        } elseif ($scheme !== null) {
            // mailto:, tel:, javascript: - not a document link.
            return $href;
        }

        // The query string and fragment ride along untouched: one of the
        // homepage's links points at a named section of its target
        // (…/badminton-underarm-clear.html#forehand), which rewriting only
        // the path would silently drop.
        $path = (string) parse_url($href, PHP_URL_PATH);
        $query = parse_url($href, PHP_URL_QUERY);
        $fragment = parse_url($href, PHP_URL_FRAGMENT);
        $tail = ($query !== null && $query !== '' ? '?' . $query : '')
            . ($fragment !== null && $fragment !== '' ? '#' . $fragment : '');

        if ($path === '') {
            return $host !== null ? '/' . $tail : $href;
        }

        if (!str_starts_with($path, '/')) {
            $path = $this->resolveRelative($path, $source);
        }

        return $this->collapseIndex($path) . $tail;
    }

    /** Resolve a homepage-relative link against the homepage's directory. */
    private function resolveRelative(string $href, string $source): string
    {
        $base = trim(str_replace('\\', '/', dirname($source)), '/.');
        $segments = $base === '' ? [] : explode('/', $base);

        foreach (explode('/', $href) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        return '/' . implode('/', $segments);
    }

    /** "/badminton-strokes.html/index.html" and "/foo/" both name a page. */
    private function collapseIndex(string $href): string
    {
        $href = rtrim((string) preg_replace('#/index\.html$#', '', $href), '/');

        return $href === '' ? '/' : $href;
    }

    /**
     * The page: a sticky sidebar of sections beside the sections
     * themselves, each one a grid of picture cards.
     *
     * @param list<array{id: string, title: string, label: string, href: string, icon: ?string, guides: list<array{href: string, title: string, image: ?string}>}> $categories
     * @param array<string, string> $copy
     */
    private function render(array $categories, array $copy): string
    {
        $total = 0;

        foreach ($categories as $category) {
            $total += count($category['guides']);
        }

        $sections = '';

        foreach ($categories as $category) {
            $sections .= $this->renderSection($category, $copy);
        }

        return '<div class="page-container catdir-page">'
            . '<div class="catdir-layout">'
            . $this->renderSidebar($categories, $copy)
            . '<div class="catdir-main">'
            . $this->renderToolbar($copy, $total)
            . $sections
            . '<p class="catdir-empty" data-category-empty hidden>' . $this->text($copy['empty']) . '</p>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    /**
     * @param list<array{id: string, title: string, label: string, href: string, icon: ?string, guides: list<array{href: string, title: string, image: ?string}>}> $categories
     * @param array<string, string> $copy
     */
    private function renderSidebar(array $categories, array $copy): string
    {
        $html = '<aside class="catdir-sidebar"><nav class="catdir-sidebar-inner" aria-label="' . $this->attr($copy['sidebar']) . '">'
            . '<h2 class="catdir-sidebar-title">' . $this->text($copy['sidebar']) . '</h2>'
            . '<ul class="catdir-sidebar-list">';

        foreach ($categories as $category) {
            $html .= '<li class="catdir-sidebar-item" data-sidebar-item="' . $this->attr($category['id']) . '">'
                . '<a href="#' . $this->attr($category['id']) . '">'
                . $this->iconTag($category['icon'], 'catdir-sidebar-icon')
                . '<span class="catdir-sidebar-label">' . $this->text($category['label']) . '</span>'
                . '<span class="catdir-sidebar-count">' . count($category['guides']) . '</span>'
                . '</a></li>';
        }

        return $html . '</ul></nav></aside>';
    }

    /** @param array<string, string> $copy */
    private function renderToolbar(array $copy, int $total): string
    {
        // The singular form travels with the element: the filter script
        // swaps the number inside this string as you type, and has no way
        // to know how the language it is written in forms a plural.
        return '<div class="catdir-toolbar">'
            . '<div class="catdir-search"><span class="catdir-search-icon" aria-hidden="true">🔍</span>'
            . '<input type="search" class="catdir-search-input" data-category-search'
            . ' aria-label="' . $this->attr($copy['search']) . '"'
            . ' placeholder="' . $this->attr($copy['search']) . '" /></div>'
            . '<p class="catdir-tally" data-category-tally aria-live="polite"'
            . ' data-tally-one="' . $this->attr($this->plural($copy, 'guides', 1)) . '">'
            . $this->text($this->plural($copy, 'guides', $total))
            . '</p>'
            . '</div>';
    }

    /**
     * @param array{id: string, title: string, label: string, href: string, icon: ?string, guides: list<array{href: string, title: string, image: ?string}>} $category
     * @param array<string, string> $copy
     */
    private function renderSection(array $category, array $copy): string
    {
        $cards = '';

        foreach ($category['guides'] as $guide) {
            $cards .= $this->renderCard($guide, $category);
        }

        $more = $category['href'] === '' ? '' :
            '<a class="catdir-section-more" href="' . $this->attr($category['href']) . '">'
            . $this->text($copy['view_all'])
            . '<span class="catdir-sr"> ' . $this->text($category['title']) . '</span></a>';

        return '<section class="catdir-section" id="' . $this->attr($category['id']) . '" data-category-section'
            . ' data-category-name="' . $this->attr($this->haystack($category)) . '">'
            . '<div class="catdir-section-head">'
            . '<h2 class="catdir-section-title">' . $this->text($category['title']) . '</h2>'
            . '<span class="catdir-section-count">' . $this->text($this->plural($copy, 'guides', count($category['guides']))) . '</span>'
            . $more
            . '</div>'
            . '<div class="catdir-grid">' . $cards . '</div>'
            . '</section>';
    }

    /**
     * @param array{href: string, title: string, image: ?string} $guide
     * @param array{id: string, title: string, label: string, href: string, icon: ?string, guides: list<array{href: string, title: string, image: ?string}>} $category
     */
    private function renderCard(array $guide, array $category): string
    {
        // No featured image: the tile carries the category's icon instead,
        // the way the live listing falls back to a plain placeholder.
        $thumb = $guide['image'] !== null
            ? '<img class="catdir-card-img" src="' . $this->attr($guide['image']) . '" alt="" loading="lazy" decoding="async" />'
            : '<span class="catdir-card-fallback">' . $this->iconTag($category['icon'], 'catdir-card-fallback-icon') . '</span>';

        return '<article class="catdir-card' . ($guide['image'] === null ? ' catdir-card-no-image' : '') . '"'
            . ' data-category-card data-category-title="' . $this->attr($guide['title']) . '">'
            . '<a class="catdir-card-link" href="' . $this->attr($guide['href']) . '">'
            . '<span class="catdir-card-thumb">' . $thumb . '</span>'
            . '<span class="catdir-card-title">' . $this->text($guide['title']) . '</span>'
            . '</a></article>';
    }

    /**
     * What a section is matched against when filtering: its heading plus
     * the strip's short label, which are often but not always the same
     * word ("Basics" for "Badminton Basics").
     *
     * @param array{title: string, label: string} $category
     */
    private function haystack(array $category): string
    {
        return $category['label'] === $category['title']
            ? $category['title']
            : $category['title'] . ' ' . $category['label'];
    }

    /**
     * Category icons are decorative wherever they appear here: the label
     * beside them in the sidebar, and the card title under them in a
     * fallback tile, already say the same thing.
     */
    private function iconTag(?string $src, string $class): string
    {
        if ($src === null || $src === '') {
            return '';
        }

        return '<img class="' . $this->attr($class) . '" src="' . $this->attr($src)
            . '" alt="" aria-hidden="true" loading="lazy" decoding="async" />';
    }

    /** @param array<string, string> $copy */
    private function plural(array $copy, string $key, int $count): string
    {
        return sprintf($copy[$count === 1 ? $key . '_one' : $key . '_many'], $count);
    }

    /** Collapse the export's line breaks inside a label to single spaces. */
    private function flatten(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function text(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private static function normalisePath(string $path): string
    {
        $path = '/' . trim((string) parse_url($path, PHP_URL_PATH), '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
