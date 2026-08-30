<?php

declare(strict_types=1);

namespace App\Content;

use App\Core\PageContent;

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
 * Nothing here writes category copy. Category names, link labels and link
 * targets are the homepage's own DOM nodes, moved across as-is; the only
 * thing rewritten is the *form* of an href or an icon src (see
 * normaliseHref), so that links written relative to the homepage still
 * resolve from a page that lives at a different URL. The page's own
 * furniture - title, hero, search placeholder, counts - is the one thing
 * authored here, in COPY.
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
            'summary_one' => '%d category',
            'summary_many' => '%d categories',
            'empty' => 'No categories match that search.',
            'hero_eyebrow' => 'Browse the site',
            'hero_lead' => 'All',
            'hero_key' => 'Categories',
            'hero_blurb' => 'Every badminton topic on Master Badminton in one place - pick a category and go straight to the guide you need.',
            'view_all' => 'View all',
        ],
        '/zh/categories' => [
            'title' => '所有羽毛球分类 - Master Badminton',
            'description' => '在一个页面浏览 Master Badminton 的所有羽毛球主题，选择分类即可直接前往你需要的教程。',
            'search' => '搜索分类或教程…',
            'guides_one' => '%d 篇教程',
            'guides_many' => '%d 篇教程',
            'summary_one' => '%d 个分类',
            'summary_many' => '%d 个分类',
            'empty' => '没有符合的分类。',
            'hero_eyebrow' => '浏览网站',
            'hero_lead' => '所有',
            'hero_key' => '分类',
            'hero_blurb' => 'Master Badminton 的所有羽毛球主题都在这里 - 选择一个分类，直接前往你需要的教程。',
            'view_all' => '查看全部',
        ],
    ];

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

        $xpath = new \DOMXPath($dom);

        $categories = $this->collectCategories($xpath, $source);

        if ($categories === []) {
            return $empty;
        }

        $copy = self::COPY[$path];

        return new PageContent(
            $copy['title'],
            $copy['description'],
            $this->render($dom, $categories, $copy),
        );
    }

    /**
     * Pull the category cards out of the homepage's WPBakery markup.
     *
     * The grid is split across one or more rows carrying the "catfrbn"
     * class, each column holding a heading and a link list - the same shape
     * ContentExtractor turns into the homepage accordion. The eight-icon
     * strip (ul#thct) sits above the grid in the same order, so its images
     * pair up with the cards positionally.
     *
     * @return list<array{id: string, title: string, label: string, href: string, icon: ?\DOMElement, items: list<\DOMElement>}>
     */
    private function collectCategories(\DOMXPath $xpath, string $source): array
    {
        // The strip carries three things the grid below it does not: a short
        // tab label ("Basics" where the card heading reads "Badminton
        // Basics"), the artwork, and a link to the section's own archive
        // page. All three are the page's own, and all three pair up with
        // the cards positionally.
        $tabs = [];

        foreach ($xpath->query('//ul[@id="thct"]/li') as $tab) {
            if (!$tab instanceof \DOMElement) {
                continue;
            }

            // Each <li> holds two links to the same target: one around the
            // label, one around the icon. The label is the one without an
            // image inside it.
            $anchor = $xpath->query('.//a[not(.//img)]', $tab)->item(0);
            $image = $xpath->query('.//img', $tab)->item(0);
            $link = $anchor instanceof \DOMElement ? $anchor : $xpath->query('.//a[@href]', $tab)->item(0);

            $tabs[] = [
                'label' => $anchor instanceof \DOMElement ? trim($anchor->textContent) : '',
                'href' => $link instanceof \DOMElement ? $link->getAttribute('href') : '',
                'icon' => $image instanceof \DOMElement ? $image : null,
            ];
        }

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

                $items = [];

                foreach ($xpath->query('./li', $list) as $item) {
                    if ($item instanceof \DOMElement) {
                        $items[] = $item;
                    }
                }

                if ($items === []) {
                    continue;
                }

                $title = trim($heading->textContent);
                $index = count($categories);

                $tab = $tabs[$index] ?? ['label' => '', 'href' => '', 'icon' => null];

                $categories[] = [
                    'id' => $this->uniqueId($title, $index, $used),
                    'title' => $title,
                    'label' => $tab['label'] !== '' ? $tab['label'] : $title,
                    'href' => $tab['href'] !== '' ? $this->normaliseHref($tab['href'], $source) : '',
                    'icon' => $tab['icon'],
                    'items' => $items,
                ];
            }
        }

        foreach ($categories as $category) {
            foreach ($category['items'] as $item) {
                $this->rewriteLinks($xpath, $item, $source);
            }

            if ($category['icon'] instanceof \DOMElement) {
                $this->rewriteIcon($category['icon'], $source);
            }
        }

        return $categories;
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

    private function rewriteLinks(\DOMXPath $xpath, \DOMElement $item, string $source): void
    {
        foreach ($xpath->query('.//a[@href]', $item) as $anchor) {
            if ($anchor instanceof \DOMElement) {
                $anchor->setAttribute('href', $this->normaliseHref($anchor->getAttribute('href'), $source));
            }
        }
    }

    private function rewriteIcon(\DOMElement $image, string $source): void
    {
        $image->setAttribute('src', $this->normaliseHref($image->getAttribute('src'), $source));
        $image->setAttribute('loading', 'lazy');
        $image->removeAttribute('width');
        $image->removeAttribute('height');
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
     * @param list<array{id: string, title: string, label: string, href: string, icon: ?\DOMElement, items: list<\DOMElement>}> $categories
     * @param array<string, string> $copy
     */
    private function render(\DOMDocument $dom, array $categories, array $copy): string
    {
        $total = 0;

        foreach ($categories as $category) {
            $total += count($category['items']);
        }

        $html = '<div class="page-container cat-page">';
        $html .= $this->renderQuickNav($dom, $categories);

        $html .= '<div class="cat-page-toolbar">';
        $html .= '<div class="cat-page-search"><span class="cat-page-search-icon" aria-hidden="true">🔍</span>'
            . '<input type="search" class="cat-page-search-input" data-category-search'
            . ' aria-label="' . $this->attr($copy['search']) . '"'
            . ' placeholder="' . $this->attr($copy['search']) . '" /></div>';
        // The singular form travels with the element: the filter script
        // swaps the number inside this string as you type, and has no way
        // to know how the language it is written in forms a plural.
        $html .= '<p class="cat-page-tally" data-category-tally aria-live="polite"'
            . ' data-tally-one="' . $this->attr($this->plural($copy, 'summary', 1)) . '">'
            . $this->text($this->plural($copy, 'summary', count($categories)))
            . '</p>';
        $html .= '</div>';

        $html .= '<div class="cat-page-grid" data-category-grid>';

        foreach ($categories as $category) {
            $html .= $this->renderCard($dom, $category, $copy);
        }

        $html .= '</div>';

        $html .= '<p class="cat-page-empty" data-category-empty hidden>' . $this->text($copy['empty']) . '</p>';
        $html .= '<p class="cat-page-total">' . $this->text($this->plural($copy, 'guides', $total)) . '</p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * The icon strip, rebuilt as the same sticky pill rail the homepage
     * uses. The markup deliberately matches (.quick-nav + ul#thct), so
     * assets/js/home-ui.js drives the jump links and active state here too.
     *
     * @param list<array{id: string, title: string, label: string, href: string, icon: ?\DOMElement, items: list<\DOMElement>}> $categories
     */
    private function renderQuickNav(\DOMDocument $dom, array $categories): string
    {
        $html = '<nav class="quick-nav" aria-label="Category quick navigation"><div class="quick-nav-inner"><ul id="thct">';

        foreach ($categories as $category) {
            $html .= '<li><a class="quick-nav-link" href="#' . $this->attr($category['id']) . '">'
                . '<span class="quick-nav-icon">' . $this->icon($dom, $category['icon']) . '</span>'
                . '<span class="quick-nav-label">' . $this->text($category['label']) . '</span>'
                . '</a></li>';
        }

        return $html . '</ul></div></nav>';
    }

    /**
     * @param array{id: string, title: string, label: string, href: string, icon: ?\DOMElement, items: list<\DOMElement>} $category
     * @param array<string, string> $copy
     */
    private function renderCard(\DOMDocument $dom, array $category, array $copy): string
    {
        $items = '';

        foreach ($category['items'] as $item) {
            $items .= $dom->saveHTML($item);
        }

        return '<article class="cat-card" id="' . $this->attr($category['id']) . '" data-category-card'
            . ' data-category-name="' . $this->attr($category['title'] . ' ' . $category['label']) . '">'
            . '<div class="cat-card-head">'
            . '<span class="cat-card-icon">' . $this->icon($dom, $category['icon']) . '</span>'
            . '<h2 class="cat-card-title">' . $this->text($category['title']) . '</h2>'
            . '<span class="cat-card-count">' . $this->text($this->plural($copy, 'guides', count($category['items']))) . '</span>'
            . '</div>'
            . '<ul class="cat-card-list">' . $items . '</ul>'
            . ($category['href'] === '' ? '' :
                '<a class="cat-card-more" href="' . $this->attr($category['href']) . '">'
                . $this->text($copy['view_all'])
                . '<span class="cat-card-more-sr"> ' . $this->text($category['title']) . '</span></a>')
            . '</article>';
    }

    /**
     * The icon is decorative here: the card heading and the tab label sit
     * right beside it and say the same thing, so it is hidden from
     * assistive technology rather than given a duplicate alt.
     */
    private function icon(\DOMDocument $dom, ?\DOMElement $image): string
    {
        if (!$image instanceof \DOMElement) {
            return '';
        }

        // Each card and its quick-nav tab show the same icon, so the node is
        // cloned rather than moved.
        $clone = $image->cloneNode(true);
        $clone->setAttribute('alt', '');
        $clone->setAttribute('aria-hidden', 'true');

        return (string) $dom->saveHTML($clone);
    }

    /** @param array<string, string> $copy */
    private function plural(array $copy, string $key, int $count): string
    {
        return sprintf($copy[$count === 1 ? $key . '_one' : $key . '_many'], $count);
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
