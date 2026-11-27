<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Everything in <head> that search engines, AI answer engines and social
 * networks read: robots, canonical, hreflang, Open Graph, Twitter Card,
 * verification tags, the feed link and JSON-LD structured data - plus the
 * analytics loader.
 *
 * Rendered by templates/header.php from what the page pipeline handed up in
 * PageContent::$meta (see App\Content\PageEnhancer):
 *
 *   headline    the page's own heading
 *   published   ISO-8601, when the page was first published
 *   modified    ISO-8601, when it was last changed or reviewed
 *   image       absolute URL of the page's share image
 *   type        'article' or 'website'
 *   article     true for articles (adds Article schema and a byline)
 *   breadcrumbs list of [name, site path], ending with the page
 *   faq         list of [question, answer]
 */
final class SeoHead
{
    /** Used when a page has no image of its own. */
    private const DEFAULT_IMAGE = '/assets/images/og-default.jpg';
    private const DEFAULT_IMAGE_WIDTH = 1200;
    private const DEFAULT_IMAGE_HEIGHT = 630;

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        private readonly string $title,
        private readonly string $description,
        private readonly string $path,
        private readonly array $meta,
        private readonly bool $isSearch,
        private readonly SiteLinks $links,
    ) {
    }

    public function robots(): string
    {
        if ($this->isSearch) {
            return 'noindex, follow';
        }

        if (!SiteVisibility::indexable()) {
            return 'noindex, nofollow';
        }

        if (http_response_code() === 404) {
            return 'noindex, follow';
        }

        return 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    }

    public function canonicalUrl(): string
    {
        return SiteConfig::siteUrl() . $this->canonicalPath();
    }

    public function render(): string
    {
        $locale = Locale::of($this->path);
        $site = SiteConfig::siteUrl();
        $canonical = $this->canonicalUrl();
        $out = [];

        $out[] = $this->tag('meta', ['name' => 'robots', 'content' => $this->robots()]);

        if ($this->description !== '') {
            $out[] = $this->tag('meta', ['name' => 'description', 'content' => $this->description]);
        }

        if (http_response_code() !== 404) {
            $out[] = $this->tag('link', ['rel' => 'canonical', 'href' => $canonical]);

            foreach ($this->alternates() as $hreflang => $href) {
                $out[] = $this->tag('link', ['rel' => 'alternate', 'hreflang' => $hreflang, 'href' => $site . $href]);
            }
        }

        $out[] = $this->tag('link', ['rel' => 'alternate', 'type' => 'application/rss+xml', 'title' => SiteConfig::SITE_NAME . ' feed', 'href' => $site . '/feed/']);

        if (SiteConfig::googleSiteVerification() !== '') {
            $out[] = $this->tag('meta', ['name' => 'google-site-verification', 'content' => SiteConfig::googleSiteVerification()]);
        }

        if (SiteConfig::bingSiteVerification() !== '') {
            $out[] = $this->tag('meta', ['name' => 'msvalidate.01', 'content' => SiteConfig::bingSiteVerification()]);
        }

        // Open Graph and Twitter Card: what Facebook, WhatsApp, LinkedIn,
        // Slack, X and iMessage show when the page is shared.
        $image = $this->image();
        $ogTitle = $this->shareTitle();
        $isArticle = ($this->meta['type'] ?? '') === 'article';

        $og = [
            'og:locale' => $locale === Locale::CHINESE ? 'zh_CN' : 'en_US',
            'og:type' => $isArticle ? 'article' : 'website',
            'og:site_name' => SiteConfig::SITE_NAME,
            'og:title' => $ogTitle,
            'og:description' => $this->description,
            'og:url' => $canonical,
            'og:image' => $image['url'],
        ];

        foreach ($og as $property => $content) {
            if ($content !== '') {
                $out[] = $this->tag('meta', ['property' => $property, 'content' => $content]);
            }
        }

        if ($this->alternates() !== []) {
            $out[] = $this->tag('meta', ['property' => 'og:locale:alternate', 'content' => $locale === Locale::CHINESE ? 'en_US' : 'zh_CN']);
        }

        if ($image['width'] > 0) {
            $out[] = $this->tag('meta', ['property' => 'og:image:width', 'content' => (string) $image['width']]);
            $out[] = $this->tag('meta', ['property' => 'og:image:height', 'content' => (string) $image['height']]);
        }

        $out[] = $this->tag('meta', ['property' => 'og:image:alt', 'content' => $ogTitle]);

        if ($isArticle) {
            foreach (['published' => 'article:published_time', 'modified' => 'article:modified_time'] as $key => $property) {
                if (($this->meta[$key] ?? '') !== '') {
                    $out[] = $this->tag('meta', ['property' => $property, 'content' => (string) $this->meta[$key]]);
                }
            }

            $out[] = $this->tag('meta', ['name' => 'author', 'content' => SiteConfig::AUTHOR_NAME]);
        }

        $out[] = $this->tag('meta', ['name' => 'twitter:card', 'content' => 'summary_large_image']);
        $out[] = $this->tag('meta', ['name' => 'twitter:title', 'content' => $ogTitle]);

        if ($this->description !== '') {
            $out[] = $this->tag('meta', ['name' => 'twitter:description', 'content' => $this->description]);
        }

        $out[] = $this->tag('meta', ['name' => 'twitter:image', 'content' => $image['url']]);

        $out[] = '<script type="application/ld+json">' . $this->jsonLd($canonical, $image['url']) . '</script>';

        return implode("\n\t", $out) . "\n";
    }

    /**
     * Google Tag Manager (preferred - GA4, Ads and Meta tags are then
     * managed in the container) or, failing that, GA4's own gtag.js. Both
     * are only printed when their ID is configured, and never on a host
     * that is not meant to be indexed, so staging traffic does not pollute
     * production analytics.
     */
    public static function analyticsHead(): string
    {
        if (!SiteVisibility::indexable()) {
            return '';
        }

        $gtm = SiteConfig::gtmId();
        $consent = self::consentDefaults();

        if ($gtm !== '') {
            return $consent . "<script>window.dataLayer=window.dataLayer||[];(function(w,d,s,l,i){w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});"
                . "var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;"
                . "j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',"
                . json_encode($gtm) . ");</script>\n";
        }

        $ga4 = SiteConfig::ga4Id();

        if ($ga4 !== '') {
            return $consent . '<script async src="https://www.googletagmanager.com/gtag/js?id=' . htmlspecialchars($ga4, ENT_QUOTES, 'UTF-8') . '"></script>' . "\n"
                . "\t<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config'," . json_encode($ga4) . ");</script>\n";
        }

        return '';
    }

    /**
     * Google Consent Mode v2 defaults, set before any Google tag loads.
     *
     * Visitors in the EEA, the UK and Switzerland start with every storage
     * type denied until a consent banner (the AdSense / Google "Privacy &
     * messaging" CMP, or any Google-certified CMP added in Tag Manager)
     * grants it; everyone else starts granted. Google requires this for
     * ads personalisation and measurement of EEA/UK traffic. With storage
     * denied, Google tags still send cookieless pings, so GA4 can model the
     * missing data.
     */
    private static function consentDefaults(): string
    {
        $regions = [
            'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU',
            'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'IS', 'LI', 'NO', 'GB', 'CH',
        ];

        return "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}"
            . "gtag('consent','default',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied',wait_for_update:500,region:"
            . json_encode($regions) . "});"
            . "gtag('consent','default',{ad_storage:'granted',ad_user_data:'granted',ad_personalization:'granted',analytics_storage:'granted'});"
            . "gtag('set','ads_data_redaction',true);</script>\n\t";
    }

    /** GTM's <noscript> fallback, placed right after <body>. */
    public static function analyticsBody(): string
    {
        $gtm = SiteConfig::gtmId();

        if ($gtm === '' || !SiteVisibility::indexable()) {
            return '';
        }

        return '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . htmlspecialchars($gtm, ENT_QUOTES, 'UTF-8')
            . '" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>' . "\n";
    }

    private function canonicalPath(): string
    {
        $path = Canonical::path($this->path, $this->links);

        // The homepages are "/" and "/zh"; every other path is used as is.
        return $path === '' ? '/' : $path;
    }

    /**
     * hreflang alternates: this page and its translation, when both exist,
     * plus x-default pointing at the English one. The same pairs the
     * sitemap declares.
     *
     * @return array<string, string>
     */
    private function alternates(): array
    {
        if ($this->isSearch) {
            return [];
        }

        $canonical = $this->canonicalPath();
        $english = Locale::to($canonical, Locale::ENGLISH);
        $chinese = Locale::to($canonical, Locale::CHINESE);

        if (!$this->links->exists($english) || !$this->links->exists($chinese)) {
            return [];
        }

        return ['en' => $english, 'zh' => $chinese, 'x-default' => $english];
    }

    /** @return array{url: string, width: int, height: int} */
    private function image(): array
    {
        $image = (string) ($this->meta['image'] ?? '');

        if ($image !== '') {
            return ['url' => $image, 'width' => 0, 'height' => 0];
        }

        return [
            'url' => SiteConfig::siteUrl() . self::DEFAULT_IMAGE,
            'width' => self::DEFAULT_IMAGE_WIDTH,
            'height' => self::DEFAULT_IMAGE_HEIGHT,
        ];
    }

    /** The title without the site name - og:site_name already carries it. */
    private function shareTitle(): string
    {
        $title = trim((string) preg_replace('~\s*[-–|]\s*' . preg_quote(SiteConfig::SITE_NAME, '~') . '\s*$~u', '', $this->title));

        return $title !== '' ? $title : $this->title;
    }

    private function jsonLd(string $canonical, string $image): string
    {
        $site = SiteConfig::siteUrl();
        $locale = Locale::of($this->path);
        $language = Locale::htmlLang($locale);
        $orgId = $site . '/#organization';
        $websiteId = $site . '/#website';
        $pageId = $canonical . '#webpage';

        $graph = [
            [
                '@type' => 'Organization',
                '@id' => $orgId,
                'name' => SiteConfig::SITE_NAME,
                'url' => $site . '/',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $site . '/assets/images/favicon-192.png',
                    'width' => 192,
                    'height' => 192,
                ],
                'founder' => ['@type' => 'Person', 'name' => SiteConfig::AUTHOR_NAME],
            ],
            [
                '@type' => 'WebSite',
                '@id' => $websiteId,
                'url' => $site . '/',
                'name' => SiteConfig::SITE_NAME,
                'publisher' => ['@id' => $orgId],
                'inLanguage' => ['en-US', 'zh-CN'],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => [
                        '@type' => 'EntryPoint',
                        'urlTemplate' => $site . '/search?s={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];

        $webPage = [
            '@type' => $this->isSearch ? 'SearchResultsPage' : 'WebPage',
            '@id' => $pageId,
            'url' => $canonical,
            'name' => $this->title,
            'isPartOf' => ['@id' => $websiteId],
            'inLanguage' => $language,
        ];

        if ($this->description !== '') {
            $webPage['description'] = $this->description;
        }

        if ($image !== '') {
            $webPage['primaryImageOfPage'] = ['@type' => 'ImageObject', 'url' => $image];
        }

        foreach (['published' => 'datePublished', 'modified' => 'dateModified'] as $key => $property) {
            if (($this->meta[$key] ?? '') !== '') {
                $webPage[$property] = (string) $this->meta[$key];
            }
        }

        $crumbs = is_array($this->meta['breadcrumbs'] ?? null) ? $this->meta['breadcrumbs'] : [];

        if ($crumbs !== []) {
            $items = [];

            foreach (array_values($crumbs) as $i => [$name, $href]) {
                $items[] = [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => (string) $name,
                    'item' => $site . Canonical::path((string) $href, $this->links),
                ];
            }

            $graph[] = ['@type' => 'BreadcrumbList', '@id' => $canonical . '#breadcrumb', 'itemListElement' => $items];
            $webPage['breadcrumb'] = ['@id' => $canonical . '#breadcrumb'];
        }

        $graph[] = $webPage;

        if (($this->meta['article'] ?? false) === true) {
            $article = [
                '@type' => 'Article',
                '@id' => $canonical . '#article',
                'headline' => mb_substr((string) ($this->meta['headline'] ?? $this->shareTitle()), 0, 110),
                'mainEntityOfPage' => ['@id' => $pageId],
                'isPartOf' => ['@id' => $websiteId],
                'author' => ['@type' => 'Person', 'name' => SiteConfig::AUTHOR_NAME, 'url' => $site . '/'],
                'publisher' => ['@id' => $orgId],
                'inLanguage' => $language,
            ];

            if ($this->description !== '') {
                $article['description'] = $this->description;
            }

            if ($image !== '') {
                $article['image'] = [$image];
            }

            foreach (['published' => 'datePublished', 'modified' => 'dateModified'] as $key => $property) {
                if (($this->meta[$key] ?? '') !== '') {
                    $article[$property] = (string) $this->meta[$key];
                }
            }

            if (!isset($article['dateModified']) && isset($article['datePublished'])) {
                $article['dateModified'] = $article['datePublished'];
            }

            $graph[] = $article;
        }

        $faq = is_array($this->meta['faq'] ?? null) ? $this->meta['faq'] : [];

        if ($faq !== []) {
            $questions = [];

            foreach ($faq as [$question, $answer]) {
                $questions[] = [
                    '@type' => 'Question',
                    'name' => (string) $question,
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => (string) $answer],
                ];
            }

            $graph[] = ['@type' => 'FAQPage', '@id' => $canonical . '#faq', 'mainEntity' => $questions, 'isPartOf' => ['@id' => $pageId]];
        }

        $json = json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP,
        );

        return $json === false ? '{}' : $json;
    }

    /** @param array<string, string> $attributes */
    private function tag(string $name, array $attributes): string
    {
        $html = '<' . $name;

        foreach ($attributes as $key => $value) {
            $html .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return $html . ' />';
    }
}
