<?php

declare(strict_types=1);

namespace App\Content;

use App\Core\Locale;
use App\Core\PageContent;
use App\Core\SiteLinks;

/**
 * The search results page served at /search (and /zh/search on the Chinese
 * mirror) - the page the magnifier in the header has always pointed at in
 * spirit and, until this class existed, not in fact: both header forms
 * submitted "s" to "/", which no route ever read, so the button reloaded
 * the homepage and the site had no search at all.
 *
 * Like the category directory this is a page with no file of its own in the
 * exported tree, so it is built here and answered before the legacy lookup
 * (see index.php). Ranking and the index behind it are App\Content\
 * SearchIndex's; this class is only the page: the field, the facet chips,
 * the results, the pager and the empty state, in the language of the tree
 * the request arrived in.
 */
final class SearchResults
{
    /** The virtual URLs this page answers, one per language tree. */
    private const PATHS = ['/search' => Locale::ENGLISH, '/zh/search' => Locale::CHINESE];

    /** Results per page. */
    private const PER_PAGE = 12;

    /** Highest page number honoured, so ?page= cannot be walked forever. */
    private const MAX_PAGE = 200;

    /** Per-language page furniture. Nothing here is read from the legacy tree. */
    private const COPY = [
        Locale::ENGLISH => [
            'title_query' => 'Search results for “%s” - Master Badminton',
            'title_blank' => 'Search - Master Badminton',
            'description' => 'Search every badminton guide on Master Badminton - rules, strokes, techniques, net play, smashing, equipment and player interviews.',
            'hero_eyebrow' => 'Search the site',
            'hero_lead' => 'Search',
            'hero_key' => 'Results',
            'hero_blurb_blank' => 'Look through every guide on Master Badminton - rules, strokes, techniques, equipment and the players.',
            'hero_blurb_hits' => 'Showing what we found for “%s”.',
            'hero_blurb_none' => 'Nothing on the site matches “%s”.',
            'label' => 'Search',
            'placeholder' => 'Search guides, rules, players…',
            'submit' => 'Search',
            'results_one' => '%d result',
            'results_many' => '%d results',
            'all' => 'All results',
            'prompt' => 'Type a word or two - a shot, a rule, a player - and the guides that cover it come back here.',
            'empty' => 'No page matches “%s”.',
            'empty_help' => 'Try a shorter phrase, a single word, or browse the topics instead.',
            'browse' => 'Browse all categories',
            'page_of' => 'Page %d of %d',
            'prev' => 'Previous',
            'next' => 'Next',
            'in' => 'in',
        ],
        Locale::CHINESE => [
            'title_query' => '“%s” 的搜索结果 - Master Badminton',
            'title_blank' => '搜索 - Master Badminton',
            'description' => '搜索 Master Badminton 上的所有羽毛球教程 - 规则、击球动作、技术、网前球、扣杀、装备与球员访谈。',
            'hero_eyebrow' => '站内搜索',
            'hero_lead' => '搜索',
            'hero_key' => '结果',
            'hero_blurb_blank' => '在 Master Badminton 的所有教程中查找 - 规则、击球动作、技术、装备与球员。',
            'hero_blurb_hits' => '以下是“%s”的搜索结果。',
            'hero_blurb_none' => '站内没有与“%s”相符的内容。',
            'label' => '搜索',
            'placeholder' => '搜索教程、规则、球员…',
            'submit' => '搜索',
            'results_one' => '%d 条结果',
            'results_many' => '%d 条结果',
            'all' => '全部结果',
            'prompt' => '输入一两个关键词 - 一种球路、一条规则、一位球员 - 相关教程就会显示在这里。',
            'empty' => '没有找到与“%s”相符的页面。',
            'empty_help' => '试试更短的词，或者直接浏览分类。',
            'browse' => '浏览所有分类',
            'page_of' => '第 %d 页，共 %d 页',
            'prev' => '上一页',
            'next' => '下一页',
            'in' => '分类',
        ],
    ];

    public function __construct(
        private readonly string $baseDir,
        private readonly SiteLinks $links,
    ) {
    }

    /** Whether this URL is the search page of one of the two trees. */
    public static function handles(string $path): bool
    {
        return isset(self::PATHS[self::normalisePath($path)]);
    }

    /**
     * Build the page for one request.
     *
     * @param array<string, mixed> $query the request's own query string -
     *        "s" for the search terms (the name the legacy WordPress forms
     *        have always used, and the one old inbound links still carry),
     *        "cat" for a facet, "page" for the pager.
     */
    public function build(string $path, array $query): PageContent
    {
        $path = self::normalisePath($path);
        $locale = self::PATHS[$path] ?? Locale::ENGLISH;
        $copy = self::COPY[$locale];

        $terms = SearchIndex::clean(is_string($query['s'] ?? null) ? $query['s'] : '');
        $category = SearchIndex::clean(is_string($query['cat'] ?? null) ? $query['cat'] : '');
        $page = max(1, min(self::MAX_PAGE, (int) ($query['page'] ?? 1)));

        $index = new SearchIndex($this->baseDir, $this->links);

        // The facet counts have to be taken before the facet is applied,
        // or choosing one would leave every other chip reading zero.
        $all = $terms === '' ? [] : $index->search($terms, $locale);
        $facets = $index->facets($all);

        if ($category !== '') {
            $all = array_values(array_filter(
                $all,
                static fn (array $hit): bool => $hit['category'] === $category,
            ));
        }

        $total = count($all);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);
        $shown = array_slice($all, ($page - 1) * self::PER_PAGE, self::PER_PAGE);

        return new PageContent(
            $terms === '' ? $copy['title_blank'] : sprintf($copy['title_query'], $terms),
            $copy['description'],
            $this->render($path, $copy, $terms, $category, $facets, $shown, $total, $page, $pages),
            '',
            $this->hero($copy, $terms, $total),
        );
    }

    /**
     * Hero copy for the band the shared header paints above the page. It
     * is handed up through PageContent rather than read from a static
     * table the way the category directory's is, because half of it is the
     * query the reader just typed.
     *
     * @param array<string, string> $copy
     * @return array{eyebrow: string, lead: string, key: string, blurb: string}
     */
    private function hero(array $copy, string $terms, int $total): array
    {
        if ($terms === '') {
            $blurb = $copy['hero_blurb_blank'];
        } else {
            $blurb = sprintf($copy[$total > 0 ? 'hero_blurb_hits' : 'hero_blurb_none'], $terms);
        }

        return [
            'eyebrow' => $copy['hero_eyebrow'],
            'lead' => $copy['hero_lead'],
            'key' => $copy['hero_key'],
            'blurb' => $blurb,
        ];
    }

    /**
     * The page: one white panel carrying the field and the tally, the facet
     * chips, the results, and the pager - the category directory's shape,
     * so the two pages read as one family.
     *
     * @param array<string, string> $copy
     * @param array<string, int> $facets
     * @param list<array{path: string, title: string, category: string, score: float, snippet: string}> $hits
     */
    private function render(
        string $path,
        array $copy,
        string $terms,
        string $category,
        array $facets,
        array $hits,
        int $total,
        int $page,
        int $pages,
    ): string {
        $body = $terms === ''
            ? '<p class="search-prompt">' . $this->text($copy['prompt']) . '</p>'
            : ($total === 0
                ? $this->renderEmpty($path, $copy, $terms)
                : $this->renderHits($copy, $hits, $page) . $this->renderPager($path, $copy, $terms, $category, $page, $pages));

        return '<div class="page-container search-page">'
            . '<div class="search-shell">'
            . $this->renderHead($path, $copy, $terms, $total)
            . ($terms === '' ? '' : $this->renderFacets($path, $copy, $terms, $category, $facets))
            . '<main class="search-main">' . $body . '</main>'
            . '</div>'
            . '</div>';
    }

    /**
     * The panel's top row: what this page is, the field itself, and the
     * count of what the query found.
     *
     * The field is a form of its own rather than a link back to the header
     * one, so a reader who mistyped can fix it where they are looking - on
     * a phone the header field is behind a toggle and scrolled off.
     *
     * @param array<string, string> $copy
     */
    private function renderHead(string $path, array $copy, string $terms, int $total): string
    {
        return '<div class="search-head">'
            . '<span class="search-head-label">' . $this->text($copy['label']) . '</span>'
            . '<div class="search-head-tools">'
            . '<form class="search-field" method="get" action="' . $this->attr($path) . '" role="search">'
            . '<span class="search-field-icon" aria-hidden="true">&#9906;</span>'
            . '<input type="search" class="search-field-input" name="s"'
            . ' value="' . $this->attr($terms) . '"'
            . ' aria-label="' . $this->attr($copy['label']) . '"'
            . ' placeholder="' . $this->attr($copy['placeholder']) . '" autocomplete="off" />'
            . '<button type="submit" class="search-field-submit">' . $this->text($copy['submit']) . '</button>'
            . '</form>'
            . ($terms === '' ? '' : '<p class="search-tally" aria-live="polite">'
                . $this->text($this->plural($copy, 'results', $total)) . '</p>')
            . '</div>'
            . '</div>';
    }

    /**
     * The facet chips: every category that has a hit for this query, with
     * its count, plus the "all results" chip that clears the filter. A
     * query whose hits are all uncategorised gets no chips at all rather
     * than a row holding one.
     *
     * @param array<string, string> $copy
     * @param array<string, int> $facets
     */
    private function renderFacets(string $path, array $copy, string $terms, string $category, array $facets): string
    {
        if ($facets === []) {
            return '';
        }

        // The "all results" chip carries no count: while a facet is chosen
        // $total is that facet's total, and a chip that read the same
        // number as the one beside it would only mislead.
        $chips = '<li class="search-facet' . ($category === '' ? ' is-active' : '') . '">'
            . '<a href="' . $this->attr($this->url($path, $terms, '', 1)) . '">'
            . '<span class="search-facet-label">' . $this->text($copy['all']) . '</span>'
            . '</a></li>';

        foreach ($facets as $name => $count) {
            $chips .= '<li class="search-facet' . ($category === $name ? ' is-active' : '') . '">'
                . '<a href="' . $this->attr($this->url($path, $terms, $name, 1)) . '">'
                . '<span class="search-facet-label">' . $this->text($name) . '</span>'
                . '<span class="search-facet-count">' . $count . '</span>'
                . '</a></li>';
        }

        return '<nav class="search-facets" aria-label="' . $this->attr($copy['label']) . '">'
            . '<ul class="search-facet-list">' . $chips . '</ul></nav>';
    }

    /**
     * The results themselves: a numbered list, each row the page's own
     * title, the passage the terms were found in, and the category it is
     * filed under.
     *
     * Numbering continues across pages - the second page starts at 13 -
     * which is what tells a reader how far down the ranking they are.
     *
     * @param array<string, string> $copy
     * @param list<array{path: string, title: string, category: string, score: float, snippet: string}> $hits
     */
    private function renderHits(array $copy, array $hits, int $page): string
    {
        $rows = '';
        $number = ($page - 1) * self::PER_PAGE;

        foreach ($hits as $hit) {
            $number++;

            $meta = $hit['category'] === ''
                ? ''
                : '<span class="search-hit-cat">' . $this->text($copy['in']) . ' '
                    . '<strong>' . $this->text($hit['category']) . '</strong></span>';

            $rows .= '<li class="search-hit">'
                . '<span class="search-hit-num">' . $this->text(str_pad((string) $number, 2, '0', STR_PAD_LEFT)) . '</span>'
                . '<div class="search-hit-body">'
                . '<h2 class="search-hit-title"><a href="' . $this->attr($hit['path']) . '">'
                . $this->text($hit['title']) . '</a></h2>'
                // Already escaped by SearchIndex, which marks the terms
                // inside it; the only markup in here is <mark>.
                . ($hit['snippet'] === '' ? '' : '<p class="search-hit-snippet">' . $hit['snippet'] . '</p>')
                . '<p class="search-hit-meta">' . $meta
                . '<span class="search-hit-path">' . $this->text($hit['path']) . '</span></p>'
                . '</div>'
                . '</li>';
        }

        return '<ol class="search-results">' . $rows . '</ol>';
    }

    /**
     * Nothing matched. The way out of here is a different query or the
     * topic index, so both are offered rather than a bare apology.
     *
     * @param array<string, string> $copy
     */
    private function renderEmpty(string $path, array $copy, string $terms): string
    {
        $categories = $this->links->localized('/categories', Locale::of($path));

        return '<div class="search-empty">'
            . '<p class="search-empty-lead">' . $this->text(sprintf($copy['empty'], $terms)) . '</p>'
            . '<p class="search-empty-help">' . $this->text($copy['empty_help']) . '</p>'
            . '<a class="search-empty-link" href="' . $this->attr($categories) . '">'
            . $this->text($copy['browse']) . '<span aria-hidden="true">&#8594;</span></a>'
            . '</div>';
    }

    /**
     * Previous / next and where you are. Only rendered when there is more
     * than one page, so a short result set does not carry dead furniture.
     *
     * @param array<string, string> $copy
     */
    private function renderPager(string $path, array $copy, string $terms, string $category, int $page, int $pages): string
    {
        if ($pages < 2) {
            return '';
        }

        $prev = $page > 1
            ? '<a class="search-pager-link" rel="prev" href="' . $this->attr($this->url($path, $terms, $category, $page - 1)) . '">'
                . '<span aria-hidden="true">&#8592;</span> ' . $this->text($copy['prev']) . '</a>'
            : '<span class="search-pager-link is-disabled" aria-hidden="true">&#8592; ' . $this->text($copy['prev']) . '</span>';

        $next = $page < $pages
            ? '<a class="search-pager-link" rel="next" href="' . $this->attr($this->url($path, $terms, $category, $page + 1)) . '">'
                . $this->text($copy['next']) . ' <span aria-hidden="true">&#8594;</span></a>'
            : '<span class="search-pager-link is-disabled" aria-hidden="true">' . $this->text($copy['next']) . ' &#8594;</span>';

        return '<nav class="search-pager" aria-label="' . $this->attr($copy['label']) . '">'
            . $prev
            . '<span class="search-pager-where">' . $this->text(sprintf($copy['page_of'], $page, $pages)) . '</span>'
            . $next
            . '</nav>';
    }

    /** This page's own URL with a different query, facet or page number. */
    private function url(string $path, string $terms, string $category, int $page): string
    {
        $params = ['s' => $terms];

        if ($category !== '') {
            $params['cat'] = $category;
        }

        if ($page > 1) {
            $params['page'] = (string) $page;
        }

        return $path . '?' . http_build_query($params);
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
