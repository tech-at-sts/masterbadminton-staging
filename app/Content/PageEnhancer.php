<?php

declare(strict_types=1);

namespace App\Content;

use App\Core\AssetUrl;
use App\Core\Canonical;
use App\Core\Locale;
use App\Core\SiteConfig;
use App\Core\SiteLinks;

/**
 * The SEO, performance and E-E-A-T pass every extracted page goes through
 * after the layout transforms (HomepageLayout / PostLayout) have run.
 *
 *  - Images: asset URLs repointed at this site (AssetUrl), missing alt text
 *    filled in, below-the-fold images and iframes lazy-loaded.
 *  - Guides: the answer-first summary, fact tables and FAQ from
 *    GuideEnhancements.
 *  - Articles: a byline with the last-updated date in the hero, an author
 *    box, a "next lesson" link and related guides at the end.
 *  - Pages with a job the export cannot do any more: the contact page
 *    (its WordPress forms posted to a server that no longer exists) and the
 *    clubs directory (titled "Archives").
 *  - The facts the shared header turns into tags: title, description,
 *    dates, image, breadcrumbs, FAQ - returned as PageContent::$meta.
 */
final class PageEnhancer
{
    /**
     * A beginner's path through the core guides. Each page in it ends with
     * a link to the next one, so a reader who finishes a lesson has an
     * obvious next step instead of a dead end.
     */
    private const LEARNING_PATH = [
        '/how-to-play-badminton.html',
        '/category/badminton-articles/badminton-basics.html',
        '/badminton-grip.html',
        '/badminton-stance.html',
        '/badminton-footwork.html',
        '/badminton-serve.html',
        '/badminton-strokes.html',
        '/badminton-clear.html',
        '/badminton-drop.html',
        '/badminton-smash.html',
        '/badminton-net-play.html',
        '/badminton-drive.html',
        '/badminton-defense.html',
        '/badminton-rules.html',
        '/badminton-scoring.html',
        '/badminton-court-size.html',
        '/badminton-singles.html',
        '/badminton-doubles.html',
        '/advanced-badminton-techniques.html',
    ];

    private const COPY = [
        Locale::ENGLISH => [
            'by' => 'By %s',
            'updated' => 'Updated %s',
            'quick' => 'Quick answer',
            'faq' => 'Frequently asked questions',
            'next' => 'Next lesson',
            'related' => 'Related guides',
            'author_heading' => 'About the author',
            'author_bio' => 'David Tee has run Master Badminton since 2010, writing free guides on badminton technique, rules, tactics and equipment for players at every level, and answering readers\' questions about the game.',
            'image' => '%s - image %d',
            'lang' => 'en',
            'video' => 'Video',
            'map' => 'Map',
            'star' => 'star',
        ],
        Locale::CHINESE => [
            'by' => '作者：%s',
            'updated' => '更新于 %s',
            'quick' => '快速解答',
            'faq' => '常见问题',
            'next' => '下一课',
            'related' => '相关指南',
            'author_heading' => '关于作者',
            'author_bio' => 'David Tee 自 2010 年起运营 Master Badminton，为各个水平的球员撰写免费的羽毛球技术、规则、战术和装备指南，并解答读者的提问。',
            'image' => '%s - 图 %d',
            'lang' => 'zh',
            'video' => '视频',
            'map' => '地图',
            'star' => '星级',
        ],
    ];

    /** English page => its Chinese counterpart, where the slugs differ. */
    private const ZH_SLUGS = [
        '/category/badminton-articles/badminton-basics.html' => '/zh/badminton-basics.html',
    ];

    /** Attributes an <img> legitimately carries (data-* and aria-* are kept too). */
    private const IMG_ATTRIBUTES = [
        'src', 'srcset', 'sizes', 'alt', 'title', 'width', 'height', 'loading', 'decoding', 'fetchpriority',
        'class', 'id', 'style', 'align', 'border', 'hspace', 'vspace', 'usemap', 'ismap', 'crossorigin',
        'referrerpolicy', 'role', 'longdesc', 'itemprop', 'lang', 'dir',
    ];

    /** Filename words that describe nothing. */
    private const JUNK_WORDS = [
        'img', 'image', 'images', 'pic', 'picture', 'photo', 'dsc', 'dscn', 'fotolia', 'shutterstock', 'istock',
        'screenshot', 'copy', 'final', 'new', 'ic', 'ce', 'bg', 'main', 'scaled', 'thumb', 'thumbnail', 'jpg',
        'jpeg', 'png', 'gif', 'webp', 'pagespeed', 'wp', 'xnx', 'html', 'irt', 'amp', 'lur', 'ampo',
    ];

    public function __construct(
        private readonly SiteLinks $links,
        private readonly PostIndex $posts,
    ) {
    }

    /**
     * @param array<string, mixed>|null $hero the layout's hero, updated in place
     * @param array{published: string, modified: string, image: string, type: string} $source
     * @return array{title: string, description: string, meta: array<string, mixed>}
     */
    public function apply(
        \DOMDocument $dom,
        \DOMXPath $xpath,
        ?\DOMElement $main,
        string $path,
        string $layout,
        ?array &$hero,
        string $title,
        string $description,
        array $source,
    ): array {
        $locale = Locale::of($path);
        $copy = self::COPY[$locale];
        $isArticle = $layout === 'post' && (($hero['variant'] ?? '') === 'post');
        $headline = (string) ($hero['title'] ?? '');

        if ($headline === '' && isset($hero['key'])) {
            $headline = trim(($hero['lead'] ?? '') . ' ' . $hero['key']);
        }

        if ($headline === '') {
            $headline = SeoText::stripSiteName($title);
        }

        $guide = $locale === Locale::ENGLISH ? GuideEnhancements::for($path) : [];
        $faq = $guide['faq'] ?? [];

        if ($main instanceof \DOMElement) {
            if ($path === '/contact.html' || $path === '/zh/contact.html') {
                $this->rebuildContact($dom, $xpath, $main, $locale);
            }

            if ($path === '/directory' || $path === '/zh/directory') {
                $this->introduceDirectory($dom, $xpath, $main, $hero, $locale);
                $headline = (string) ($hero['title'] ?? $headline);
            }

            $body = $this->firstByClass($xpath, 'post-body', $main);

            $notice = GuideEnhancements::notice($path);

            if ($body !== null && $notice !== '') {
                $noticeNode = $this->fragment($dom, $notice);

                if ($noticeNode !== null) {
                    $body->insertBefore($noticeNode, $body->firstChild);
                }
            }

            if ($body !== null && $guide !== []) {
                $this->addGuide($dom, $body, $guide, $copy);

                // The layout estimated reading time from the original text;
                // the added reference material changes it.
                if (is_array($hero) && isset($hero['meta'])) {
                    $words = str_word_count(strip_tags((string) $body->textContent));
                    $hero['meta'] = sprintf($locale === Locale::CHINESE ? '约 %d 分钟阅读' : '%d min read', max(1, (int) round($words / 220)));
                }
            }

            if ($isArticle && $body !== null) {
                $this->addArticleFooter($dom, $body, $path, $locale, $copy);
            }

            $this->cleanLegacyComments($xpath, $main);
            $this->removeDeadForms($xpath, $main);
            $this->removeArchiveJunk($xpath, $main);
            $this->rootAssetPaths($xpath, $main);
            $this->fixImages($xpath, $main, $headline, $copy);
            $this->fixLinksToFiles($xpath, $main);
            $this->upgradeInsecureUrls($xpath, $main);
        }

        // Dates: a reviewed guide carries the date it was reviewed; every
        // other page keeps the dates WordPress recorded.
        $published = $source['published'];
        $modified = $source['modified'];

        if (isset($guide['updated'])) {
            $modified = gmdate('c', (int) strtotime($guide['updated'] . ' 00:00:00 UTC'));
        }

        if ($isArticle && is_array($hero)) {
            $hero['meta'] = $this->byline($copy, $locale, $modified ?: $published, (string) ($hero['meta'] ?? ''));
        }

        $override = SeoOverrides::for($path);
        $title = $override['title'] ?? SeoText::title($title, $headline);

        if (isset($override['description'])) {
            $description = $override['description'];
        } elseif (!SeoText::usableDescription($description, $locale) && $main instanceof \DOMElement) {
            $description = SeoText::describe($xpath, $main, $locale);
        }

        return [
            'title' => $title,
            'description' => SeoText::clean($description),
            'meta' => array_filter([
                'headline' => $headline,
                'published' => $published,
                'modified' => $modified,
                'image' => $this->shareImage($source['image'], $xpath, $main),
                'type' => $isArticle ? 'article' : 'website',
                'article' => $isArticle,
                'breadcrumbs' => $main instanceof \DOMElement ? $this->breadcrumbs($xpath, $main, $path, $headline) : [],
                'faq' => $faq,
            ], static fn ($value): bool => $value !== '' && $value !== [] && $value !== null),
        ];
    }

    /* -----------------------------------------------------------------
     * Images
     * -------------------------------------------------------------- */

    /** @param array<string, string> $copy */
    private function fixImages(\DOMXPath $xpath, \DOMElement $main, string $headline, array $copy): void
    {
        $index = 0;

        foreach (iterator_to_array($xpath->query('.//img', $main)) as $img) {
            if (!$img instanceof \DOMElement) {
                continue;
            }

            foreach (['src', 'data-src', 'data-lazy-src', 'data-orig-file', 'data-large-file', 'data-medium-file'] as $attr) {
                if ($img->hasAttribute($attr)) {
                    $img->setAttribute($attr, AssetUrl::resolve($img->getAttribute($attr)));
                }
            }

            // Pinterest's "pin it" image must be an absolute URL.
            if ($img->hasAttribute('data-pin-media')) {
                $pin = AssetUrl::resolve($img->getAttribute('data-pin-media'));
                $img->setAttribute('data-pin-media', str_starts_with($pin, '/') ? SiteConfig::siteUrl() . $pin : $pin);
            }

            foreach (['srcset', 'data-srcset', 'data-lazy-srcset'] as $attr) {
                if ($img->hasAttribute($attr)) {
                    $img->setAttribute($attr, AssetUrl::resolveSrcset($img->getAttribute($attr)));
                }
            }

            $index++;

            // ...and the same cut-off markup can spill stray attributes onto
            // the image ("button-readmore", href="...") - drop anything an
            // <img> does not take.
            foreach (iterator_to_array($img->attributes) as $attribute) {
                $name = strtolower($attribute->nodeName);

                if (!str_starts_with($name, 'data-') && !str_starts_with($name, 'aria-')
                    && !in_array($name, self::IMG_ATTRIBUTES, true)) {
                    $img->removeAttribute($attribute->nodeName);
                }
            }

            // Some excerpts were cut off mid-markup by the export, leaving an
            // image's alt text holding the rest of the card's HTML ("Lee
            // Chong </div> <!-- Blog Read More Button --> <a class=...").
            // Keep the words before the markup, tidy the whitespace.
            $alt = $img->getAttribute('alt');

            if (str_contains($alt, '<') || preg_match('~\s{3,}~u', $alt) === 1) {
                $clean = trim((string) preg_replace('~\s+~u', ' ', explode('<', $alt)[0]));
                $img->setAttribute('alt', mb_strlen($clean) > 125 ? rtrim(mb_substr($clean, 0, 122)) . '...' : $clean);
            }

            // On the Chinese mirror the export kept the English alt text
            // TranslatePress never translated ("star", "badminton drive"),
            // and English filenames are no description for a Chinese
            // reader. Alt text without a single Chinese character is
            // replaced with Chinese drawn from the page itself.
            if ($copy['lang'] === 'zh' && trim($img->getAttribute('alt')) !== '' && !self::hasHan($img->getAttribute('alt'))) {
                $img->setAttribute('alt', strtolower(trim($img->getAttribute('alt'))) === 'star'
                    ? $copy['star']
                    : $this->altFor($xpath, $img, $headline, $index, $copy));
            }

            if (trim($img->getAttribute('alt')) === '' && !$this->isDecorative($img)) {
                $img->setAttribute('alt', $this->altFor($xpath, $img, $headline, $index, $copy));
            } elseif (!$img->hasAttribute('alt')) {
                $img->setAttribute('alt', '');
            }

            // The first image is likely above the fold - leave it eager so it
            // does not delay the largest contentful paint. Everything after
            // it waits until the reader scrolls near it.
            if ($index === 1) {
                $img->removeAttribute('loading');
            } else {
                $img->setAttribute('loading', 'lazy');
                $img->removeAttribute('fetchpriority');
            }

            $img->setAttribute('decoding', 'async');
        }

        // Share widgets carry the page's address for the share itself;
        // point them at the canonical https://www address rather than the
        // old http:// domain, which only reaches the page via a redirect.
        foreach ($xpath->query('.//*[@data-link or @data-url or @data-href or @data-permalink]', $main) as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            foreach (['data-link', 'data-url', 'data-href', 'data-permalink'] as $attr) {
                $value = $node->getAttribute($attr);

                if ($value !== '' && preg_match('~^(https?:)?//(www\.)?masterbadminton\.com(/.*)?$~i', $value, $m) === 1) {
                    $sharePath = (string) parse_url(($m[3] ?? '') !== '' ? $m[3] : '/', PHP_URL_PATH);
                    $node->setAttribute($attr, SiteConfig::siteUrl() . Canonical::path($sharePath, $this->links));
                }
            }
        }

        foreach ($xpath->query('.//source[@srcset]', $main) as $source) {
            if ($source instanceof \DOMElement) {
                $source->setAttribute('srcset', AssetUrl::resolveSrcset($source->getAttribute('srcset')));
            }
        }

        foreach ($xpath->query('.//iframe', $main) as $iframe) {
            if ($iframe instanceof \DOMElement) {
                $iframe->setAttribute('loading', 'lazy');

                $current = trim($iframe->getAttribute('title'));

                if ($current === '' || ($copy['lang'] === 'zh' && !self::hasHan($current))) {
                    $src = $iframe->getAttribute('src');
                    $iframe->setAttribute('title', str_contains($src, 'google.com/maps') ? $copy['map'] : (str_contains($src, 'youtube') ? $copy['video'] : $headline));
                }
            }
        }
    }

    /** Tracking pixels, spacers and the like: tiny, and meaningless to a screen reader. */
    private function isDecorative(\DOMElement $img): bool
    {
        $width = (int) $img->getAttribute('width');
        $height = (int) $img->getAttribute('height');

        return ($width > 0 && $width <= 24) || ($height > 0 && $height <= 24)
            || preg_match('~(spacer|pixel|blank|transparent)\.(gif|png)~i', $img->getAttribute('src')) === 1;
    }

    /**
     * Alt text for an image the export left without one: the image's own
     * filename when it names the subject ("Service-Boundaries-Doubles-v4.png"
     * => "Service boundaries doubles"), otherwise the section heading the
     * image sits under, otherwise the page's own headline.
     *
     * @param array<string, string> $copy
     */
    private function altFor(\DOMXPath $xpath, \DOMElement $img, string $headline, int $index, array $copy): string
    {
        $zh = $copy['lang'] === 'zh';

        // A filename is English, so it describes nothing to a Chinese reader.
        $fromName = $zh ? '' : $this->altFromFilename($img->getAttribute('src'));

        if ($fromName !== '') {
            return $fromName;
        }

        $title = trim($img->getAttribute('title'));

        if ($title !== '' && mb_strlen($title) <= 120 && (!$zh || self::hasHan($title))) {
            return $title;
        }

        $heading = $xpath->query('preceding::*[self::h2 or self::h3][1]', $img)->item(0);
        $headingText = $heading instanceof \DOMElement ? trim((string) preg_replace('~\s+~u', ' ', $heading->textContent)) : '';

        if ($zh && !self::hasHan($headingText)) {
            $headingText = '';
        }

        if ($zh && $headingText !== '' && mb_strlen($headingText) <= 60) {
            return $headingText . ($headline !== '' && $headline !== $headingText ? ' - ' . $headline : '');
        }

        if (!$zh && $headingText !== '' && mb_strlen($headingText) <= 100) {
            return $headline !== '' && !str_contains(mb_strtolower($headingText), 'badminton')
                ? $headingText . ' - ' . $headline
                : $headingText;
        }

        return $index > 1 ? sprintf($copy['image'], $headline, $index) : $headline;
    }

    private function altFromFilename(string $src): string
    {
        $name = rawurldecode(basename((string) parse_url($src, PHP_URL_PATH)));
        $name = (string) preg_replace('~\.pagespeed\..*$~i', '', $name);
        $name = (string) preg_replace('~\.(jpe?g|png|gif|webp|svg|avif)$~i', '', $name);
        $name = (string) preg_replace('~^\d+x(\d+|N)x~i', '', $name);
        $name = (string) preg_replace('~-\d+x\d+$~', '', $name);
        $name = (string) preg_replace('~\.(jpe?g|png|gif)$~i', '', $name);
        $name = (string) preg_replace('~([a-z])([A-Z])~', '$1 $2', $name);

        $words = [];

        foreach (preg_split('~[^A-Za-z]+~', $name) ?: [] as $word) {
            $lower = strtolower($word);

            if (strlen($lower) < 2 || in_array($lower, self::JUNK_WORDS, true) || preg_match('~^v\d*$~', $lower) === 1) {
                continue;
            }

            $words[] = $lower;
        }

        // One word ("Underarm", "defense") or a run of consonant soup is
        // not a description; two real words usually are.
        $real = array_filter($words, static fn (string $w): bool => preg_match('~[aeiouy]~', $w) === 1 && strlen($w) >= 3);

        if (count($real) < 2) {
            return '';
        }

        $text = implode(' ', $words);
        $text = str_replace(['inout', 'msia'], ['in and out', 'Malaysia'], $text);

        return ucfirst($text);
    }

    /** Links to image and document files in the content go to this site's copy. */
    private function fixLinksToFiles(\DOMXPath $xpath, \DOMElement $main): void
    {
        foreach ($xpath->query('.//a[contains(@href, "/wp-content/") or contains(@href, "/wp-includes/")]', $main) as $anchor) {
            if ($anchor instanceof \DOMElement) {
                $anchor->setAttribute('href', AssetUrl::resolve($anchor->getAttribute('href')));
            }
        }
    }

    /** Absolute URL of the image a share card should use. */
    private function shareImage(string $declared, \DOMXPath $xpath, ?\DOMElement $main): string
    {
        $url = $declared !== '' ? AssetUrl::resolve($declared) : '';

        if ($url === '' && $main instanceof \DOMElement) {
            foreach ($xpath->query('.//img[@src]', $main) as $img) {
                if (!$img instanceof \DOMElement) {
                    continue;
                }

                $width = (int) $img->getAttribute('width');

                if ($width !== 0 && $width < 200) {
                    continue;
                }

                $url = $img->getAttribute('src');
                break;
            }
        }

        if ($url === '' || str_starts_with($url, 'data:')) {
            return '';
        }

        return str_starts_with($url, '/') ? SiteConfig::siteUrl() . $url : $url;
    }

    /**
     * The reader comments carried over from the site's pre-WordPress Q&A
     * pages. Most of them are spam - essay mills, supplements, escort and
     * greeting-card sites - and a page that links out to that is a page
     * search engines trust less. Comments that link to another site or
     * read as advertising are removed; links in the comments that remain
     * are marked rel="ugc nofollow". The "add your own comments" links go
     * too: the comment system behind them no longer exists.
     */
    private function cleanLegacyComments(\DOMXPath $xpath, \DOMElement $main): void
    {
        $spam = '~\b(essays?|assignments?|homework|dissertation|research paper|term paper|escorts?|call girls?|casino|viagra|cialis|'
            . 'crazy ?bulk|steroids?|weight loss|payday|loans?|seo services?|buy (cheap|online)|replica|happy new year|'
            . 'merry christmas|greetings? message|wishes images|snoring|leather jackets?)\b~iu';

        foreach (iterator_to_array($xpath->query('.//div[starts-with(@id, "comment_")]', $main)) as $comment) {
            if (!$comment instanceof \DOMElement) {
                continue;
            }

            $external = false;

            foreach ($xpath->query('.//a[@href]', $comment) as $link) {
                $host = strtolower((string) parse_url($link->getAttribute('href'), PHP_URL_HOST));

                if ($host !== '' && !str_ends_with($host, 'masterbadminton.com')) {
                    $external = true;
                    break;
                }
            }

            if ($external || preg_match($spam, $comment->textContent) === 1) {
                // The comment sits in a wrapper <div id="<number>"> of its own.
                $wrapper = $comment->parentNode;
                $target = $wrapper instanceof \DOMElement && preg_match('~^\d+$~', $wrapper->getAttribute('id')) === 1 ? $wrapper : $comment;
                $target->parentNode?->removeChild($target);
                continue;
            }

            foreach ($xpath->query('.//a[@href]', $comment) as $link) {
                if ($link instanceof \DOMElement) {
                    $link->setAttribute('rel', 'ugc nofollow');
                }
            }
        }

        foreach (iterator_to_array($xpath->query('.//a[contains(@class, "old-comments") or contains(@href, "-comment-form.html")]', $main)) as $link) {
            $paragraph = $link->parentNode;
            $target = $paragraph instanceof \DOMElement && strtolower($paragraph->nodeName) === 'p' && trim($paragraph->textContent) === trim($link->textContent)
                ? $paragraph
                : $link;
            $target->parentNode?->removeChild($target);
        }

        // A comments table with no comments left is just a heading and a
        // star rating; drop it.
        foreach (iterator_to_array($xpath->query('.//table[@id="comments"]', $main)) as $table) {
            if ($table instanceof \DOMElement && $xpath->query('.//div[starts-with(@id, "comment_")]', $table)->length === 0) {
                $table->parentNode?->removeChild($table);
            }
        }
    }

    /**
     * WordPress's comment form posted to wp-comments-post.php on a server
     * that no longer exists, so anything typed into it was lost. The form
     * goes, together with the "Leave a comment" block that holds it.
     */
    private function removeDeadForms(\DOMXPath $xpath, \DOMElement $main): void
    {
        $query = './/form[contains(@action, "wp-comments-post") or contains(@data-trp-original-action, "wp-comments-post")]';

        foreach (iterator_to_array($xpath->query($query, $main)) as $form) {
            $block = $xpath->query('ancestor::*[@id="respond" or @id="comments" or @id="comment-wrapper"][last()]', $form)->item(0);
            $target = $block instanceof \DOMElement ? $block : $form;
            $target->parentNode?->removeChild($target);
        }
    }

    /**
     * Asset paths the export wrote relative to the page - "wp-content/...",
     * "../wp-content/..." - or under the Chinese mirror ("/zh/wp-content/...").
     * A browser resolves those against the page's own address
     * (/zh/badminton-drive.html/wp-content/..., /category/blog/wp-content/...),
     * where nothing exists. Every such file lives once, at /wp-content/ or
     * /wp-includes/ at the site root, so the path is rooted there.
     */
    private function rootAssetPaths(\DOMXPath $xpath, \DOMElement $main): void
    {
        $root = static function (string $value): string {
            $value = trim($value);

            if ($value === '' || preg_match('~^(https?:)?//|^(data|mailto|javascript):~i', $value) === 1) {
                return $value;
            }

            if (preg_match('~(?:^|/)(wp-(?:content|includes)/.*)$~', $value, $m) === 1) {
                return AssetUrl::resolve('/' . $m[1]);
            }

            return $value;
        };

        $query = './/img | .//source | .//script[@src] | .//link[@href] | .//input[@src] | .//embed | .//video | .//audio'
            . ' | .//a[contains(@href, "wp-content/") or contains(@href, "wp-includes/")]';

        foreach ($xpath->query($query, $main) as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            foreach (['src', 'href', 'data-src', 'data-lazy-src', 'poster'] as $attr) {
                if ($node->hasAttribute($attr)) {
                    $node->setAttribute($attr, $root($node->getAttribute($attr)));
                }
            }

            foreach (['srcset', 'data-srcset'] as $attr) {
                if (!$node->hasAttribute($attr)) {
                    continue;
                }

                $parts = [];

                foreach (explode(',', $node->getAttribute($attr)) as $part) {
                    $pieces = preg_split('~\s+~', trim($part), 2) ?: [];

                    if (($pieces[0] ?? '') !== '') {
                        $pieces[0] = $root($pieces[0]);
                        $parts[] = implode(' ', $pieces);
                    }
                }

                $node->setAttribute($attr, implode(', ', $parts));
            }
        }
    }

    /**
     * Leftovers from the archived copies some pages were restored from: an
     * archive.org tracking pixel, a third-party badge, Google's old custom
     * search stylesheet, PageSpeed "ssjs" bundles from the previous host and
     * Cloudflare's email-obfuscation script. The exporter saved their URLs
     * as local paths ("../trafeze.com/img/surf.gif", "web/2016...im_/...")
     * that 404 here, and none of them does anything on this site.
     */
    private function removeArchiveJunk(\DOMXPath $xpath, \DOMElement $main): void
    {
        $junk = static function (string $url): bool {
            $url = trim($url);

            if ($url === '' || preg_match('~^(https?:)?//~i', $url) === 1) {
                // Absolute: only the archive's own tracking URLs are junk.
                return preg_match('~web\.archive\.org/web/|assoc-amazon\.com/e/ir|/cdn-cgi/~i', $url) === 1;
            }

            $path = (string) preg_replace('~^(\.\./|\./)+~', '', $url);

            return preg_match('~^/?web/\d{8,}~', $path) === 1                         // archive.org snapshot path
                || preg_match('~(^|/)ssjs/~', $path) === 1                             // old host's PageSpeed bundles
                || preg_match('~(^|/)cdn-cgi/~', $path) === 1                          // Cloudflare email decode
                // A mirrored copy of another host: "trafeze.com/img/...",
                // "cse.google.com/...", "pagead2.googlesyndication.com/...".
                || preg_match('~^/?[a-z0-9-]+(\.[a-z0-9-]+)*\.(com|net|org|info|biz|io|co|uk|au|my|sg|in)(/|$)~i', $path) === 1;
        };

        $query = './/img[@src] | .//script[@src] | .//link[@href] | .//iframe[@src] | .//embed[@src] | .//input[@type="image"]';

        foreach (iterator_to_array($xpath->query($query, $main)) as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $url = $node->getAttribute($node->hasAttribute('src') ? 'src' : 'href');

            if (!$junk($url)) {
                continue;
            }

            // A badge or pixel is usually the only thing inside its link.
            $parent = $node->parentNode;
            $node->parentNode?->removeChild($node);

            if ($parent instanceof \DOMElement && strtolower($parent->nodeName) === 'a' && trim($parent->textContent) === ''
                && $xpath->query('.//img', $parent)->length === 0) {
                $parent->parentNode?->removeChild($parent);
            }
        }
    }

    /**
     * Embedded resources (YouTube players, the AdSense loader, images) that
     * the export references over plain http:// are blocked by browsers on
     * an https page, so they are requested over https. Links to a few
     * well-known sites that serve https are upgraded too; other outbound
     * links are left as written, since not every old site supports https.
     */
    private function upgradeInsecureUrls(\DOMXPath $xpath, \DOMElement $main): void
    {
        foreach ($xpath->query('.//iframe[@src] | .//script[@src] | .//img[@src] | .//embed[@src] | .//source[@src] | .//video[@src] | .//audio[@src]', $main) as $node) {
            if ($node instanceof \DOMElement && str_starts_with(strtolower($node->getAttribute('src')), 'http://')) {
                $node->setAttribute('src', 'https://' . substr($node->getAttribute('src'), 7));
            }
        }

        foreach ($xpath->query('.//object[@data] | .//param[@name="movie" and @value]', $main) as $node) {
            if ($node instanceof \DOMElement) {
                $attr = $node->hasAttribute('data') ? 'data' : 'value';

                if (str_starts_with(strtolower($node->getAttribute($attr)), 'http://')) {
                    $node->setAttribute($attr, 'https://' . substr($node->getAttribute($attr), 7));
                }
            }
        }

        $secureHosts = '~^(www\.)?(youtube\.com|youtu\.be|google\.[a-z.]+|facebook\.com|twitter\.com|pinterest\.com|instagram\.com|'
            . 'wikipedia\.org|en\.wikipedia\.org|amazon\.[a-z.]+|bwfbadminton\.com|badmintonengland\.co\.uk|badminton\.org\.au)$~i';

        foreach ($xpath->query('.//a[starts-with(@href, "http://")]', $main) as $link) {
            if (!$link instanceof \DOMElement) {
                continue;
            }

            $host = (string) parse_url($link->getAttribute('href'), PHP_URL_HOST);

            if (preg_match($secureHosts, $host) === 1) {
                $link->setAttribute('href', 'https://' . substr($link->getAttribute('href'), 7));
            }
        }
    }

    /* -----------------------------------------------------------------
     * Guides and articles
     * -------------------------------------------------------------- */

    /**
     * @param array{summary?: string, facts?: string, faq?: list<array{0: string, 1: string}>} $guide
     * @param array<string, string> $copy
     */
    private function addGuide(\DOMDocument $dom, \DOMElement $body, array $guide, array $copy): void
    {
        if (isset($guide['summary'])) {
            $summary = $this->fragment($dom, '<div class="guide-answer" role="note"><p class="guide-answer-label">'
                . self::e($copy['quick']) . '</p><p>' . $guide['summary'] . '</p></div>');

            if ($summary !== null) {
                $body->insertBefore($summary, $body->firstChild);
            }
        }

        $tail = '';

        if (isset($guide['facts'])) {
            $tail .= '<section class="guide-facts">' . $guide['facts'] . '</section>';
        }

        if (($guide['faq'] ?? []) !== []) {
            $tail .= '<section class="guide-faq" id="faq"><h2 id="faq-heading">' . self::e($copy['faq']) . '</h2>';

            foreach ($guide['faq'] as [$question, $answer]) {
                $tail .= '<div class="guide-faq-item"><h3>' . self::e($question) . '</h3><p>' . self::e($answer) . '</p></div>';
            }

            $tail .= '</section>';
        }

        $fragment = $tail !== '' ? $this->fragment($dom, $tail) : null;

        if ($fragment !== null) {
            $body->appendChild($fragment);
        }
    }

    /** @param array<string, string> $copy */
    private function addArticleFooter(\DOMDocument $dom, \DOMElement $body, string $path, string $locale, array $copy): void
    {
        $html = '<aside class="author-box" aria-label="' . self::e($copy['author_heading']) . '">'
            . '<p class="author-box-label">' . self::e($copy['author_heading']) . '</p>'
            . '<p class="author-box-name">' . self::e(SiteConfig::AUTHOR_NAME) . '</p>'
            . '<p class="author-box-bio">' . self::e($copy['author_bio']) . '</p>'
            . '</aside>';

        $neutral = Locale::neutral($path);
        $position = array_search($neutral, self::LEARNING_PATH, true);

        if ($position !== false && isset(self::LEARNING_PATH[$position + 1])) {
            $next = $this->localizedPage(self::LEARNING_PATH[$position + 1], $locale);

            if ($this->links->exists($next)) {
                $html .= '<nav class="next-lesson" aria-label="' . self::e($copy['next']) . '">'
                    . '<a href="' . self::e($next) . '" data-track="next-lesson">'
                    . '<span class="next-lesson-label">' . self::e($copy['next']) . ' &#8594;</span>'
                    . '<span class="next-lesson-title">' . self::e($this->pageTitle($next)) . '</span>'
                    . '</a></nav>';
            }
        }

        $related = [];

        foreach ($this->posts->related($neutral, 6) as $post) {
            $target = Canonical::path($this->localizedPage($post['path'], $locale), $this->links);

            if ($target === $path || $target === Canonical::path($path, $this->links) || !$this->links->exists($target) || count($related) >= 4) {
                continue;
            }

            $related[$target] = $locale === Locale::ENGLISH ? $post['title'] : $this->pageTitle($target, $post['title']);
        }

        if ($related !== []) {
            $html .= '<nav class="related-guides" aria-labelledby="related-guides-heading">'
                . '<h2 id="related-guides-heading">' . self::e($copy['related']) . '</h2><ul>';

            foreach ($related as $href => $label) {
                $html .= '<li><a href="' . self::e($href) . '" data-track="related-guide">' . self::e($label) . '</a></li>';
            }

            $html .= '</ul></nav>';
        }

        $fragment = $this->fragment($dom, '<div class="post-after">' . $html . '</div>');

        if ($fragment !== null) {
            $body->appendChild($fragment);
        }
    }

    /**
     * A page's display name, from its own source file: the first <h1>, or
     * its <title> without the site name.
     */
    private function pageTitle(string $path, string $fallback = ''): string
    {
        $neutral = Locale::neutral($path);
        $override = Locale::of($path) === Locale::ENGLISH ? (SeoOverrides::for($neutral)['title'] ?? '') : '';

        $file = $this->links->sourceFile($path);

        if ($file !== null) {
            $html = (string) @file_get_contents($file);

            // The heading follows the content wrapper, sometimes after a
            // template comment ("<!-- Blog Date -->") - which the mirror
            // needs, because its <title> was left in English.
            if (preg_match('~<div[^>]*class="[^"]*entry-content[^"]*"[^>]*>(?:\s|<!--.*?-->)*<h1[^>]*>(.*?)</h1>~is', $html, $m) === 1
                || preg_match('~<h1[^>]*class="[^"]*page-title[^"]*"[^>]*>(.*?)</h1>~is', $html, $m) === 1) {
                $text = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if ($text !== '' && mb_strlen($text) <= 120) {
                    return $text;
                }
            }

            if ($override === '' && preg_match('~<title>(.*?)</title>~is', $html, $m) === 1) {
                $text = SeoText::stripSiteName(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if ($text !== '') {
                    return $text;
                }
            }
        }

        return $override !== '' ? SeoText::stripSiteName($override) : ($fallback !== '' ? $fallback : $path);
    }

    /** @param array<string, string> $copy */
    private function byline(array $copy, string $locale, string $date, string $readTime): string
    {
        $parts = [sprintf($copy['by'], SiteConfig::AUTHOR_NAME)];
        $time = $date !== '' ? strtotime($date) : false;

        if ($time !== false) {
            $parts[] = sprintf($copy['updated'], $locale === Locale::CHINESE ? gmdate('Y年n月j日', $time) : gmdate('j M Y', $time));
        }

        if ($readTime !== '') {
            $parts[] = $readTime;
        }

        return implode(' · ', $parts);
    }

    /* -----------------------------------------------------------------
     * Pages that need rebuilding
     * -------------------------------------------------------------- */

    /**
     * The exported contact page carried a Contact Form 7 form and a
     * WordPress comment form, both posting to a WordPress install that no
     * longer exists - so every message sent through them was lost. They are
     * replaced by a working form (when CONTACT_FORM_ENDPOINT is set), the
     * contact email (when CONTACT_EMAIL is set), and a pointer to the
     * answered-questions archive.
     */
    private function rebuildContact(\DOMDocument $dom, \DOMXPath $xpath, \DOMElement $main, string $locale): void
    {
        $container = $this->firstByClass($xpath, 'post-main', $main) ?? $this->firstByClass($xpath, 'type-page', $main);

        if ($container === null) {
            return;
        }

        foreach (iterator_to_array($xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " wpcf7 ")] | .//div[@id="comments"] | .//form', $container)) as $node) {
            $node->parentNode?->removeChild($node);
        }

        // What remains of the original copy is a "Please note that all
        // fields followed by an asterisk..." line for a form that is gone.
        foreach (iterator_to_array($xpath->query('.//p', $container)) as $paragraph) {
            if (str_contains($paragraph->textContent, 'asterisk') || str_contains($paragraph->textContent, '星号')) {
                $paragraph->parentNode?->removeChild($paragraph);
            }
        }

        $zh = $locale === Locale::CHINESE;
        $email = SiteConfig::contactEmail();
        $endpoint = SiteConfig::contactFormEndpoint();
        $questions = $this->links->localized('/category/questions.html', $locale);

        $html = '<div class="contact-panel">'
            . '<p class="contact-lead">' . ($zh
                ? '对羽毛球规则、技术或装备有疑问，或想对本站提出建议？欢迎联系我们，我们会尽快回复。'
                : 'Have a question about badminton rules, technique or equipment, or feedback about the site? Get in touch and we will reply as soon as we can.') . '</p>';

        if ($email !== '') {
            $html .= '<p class="contact-email">' . ($zh ? '电子邮件：' : 'Email: ')
                . '<a href="mailto:' . self::e($email) . '" data-track="contact-email">' . self::e($email) . '</a></p>';
        }

        if ($endpoint !== '') {
            $html .= '<form class="contact-form" method="post" action="' . self::e($endpoint) . '" data-track="contact-form">'
                . '<p><label for="contact-name">' . ($zh ? '姓名' : 'Your name') . '</label>'
                . '<input id="contact-name" name="name" type="text" autocomplete="name" required maxlength="100" /></p>'
                . '<p><label for="contact-email">' . ($zh ? '电子邮件' : 'Email address') . '</label>'
                . '<input id="contact-email" name="email" type="email" autocomplete="email" required maxlength="200" /></p>'
                . '<p><label for="contact-message">' . ($zh ? '留言' : 'Your message') . '</label>'
                . '<textarea id="contact-message" name="message" rows="6" required maxlength="5000"></textarea></p>'
                // Honeypot: hidden from people, filled in by bots.
                . '<p class="contact-hp" aria-hidden="true"><label for="contact-website">Website</label>'
                . '<input id="contact-website" name="_gotcha" type="text" tabindex="-1" autocomplete="off" /></p>'
                . '<p><button type="submit">' . ($zh ? '发送' : 'Send message') . '</button></p>'
                . '</form>';
        }

        $html .= '<p class="contact-questions">' . ($zh
                ? '想先看看别人问过的问题？浏览<a href="' . self::e($questions) . '">羽毛球问答</a>。'
                : 'Looking for a quick answer? Browse <a href="' . self::e($questions) . '">badminton questions and answers</a> from other readers.') . '</p>'
            . '</div>';

        $fragment = $this->fragment($dom, $html);

        if ($fragment !== null) {
            $container->appendChild($fragment);
        }
    }

    /**
     * The clubs directory exported as a bare "Archives" listing of New York
     * clubs with no heading of its own. It gets a real name and a short
     * introduction saying what it covers.
     *
     * @param array<string, mixed>|null $hero
     */
    private function introduceDirectory(\DOMDocument $dom, \DOMXPath $xpath, \DOMElement $main, ?array &$hero, string $locale): void
    {
        $zh = $locale === Locale::CHINESE;

        if (is_array($hero)) {
            $hero['title'] = $zh ? '纽约羽毛球俱乐部名录' : 'New York Badminton Clubs Directory';
            $hero['eyebrow'] = $zh ? '哪里可以打球' : 'Where to play';
        }

        $australia = $this->links->localized('/where-to-play-in-australia.html', $locale);
        $uk = $this->links->localized('/uk-badminton-places-to-play.html', $locale);

        $intro = '<div class="directory-intro"><p>' . ($zh
                ? '以下是纽约市（皇后区、布鲁克林和法拉盛）的羽毛球俱乐部和球馆，包括地址、电话、网站和开放时间。出发前请先向俱乐部确认最新的开放时间。'
                : 'Badminton clubs and courts in New York City - Queens, Brooklyn and Flushing - with addresses, phone numbers, websites and opening hours. Opening times change, so check with the club before you go.')
            . '</p><p>' . ($zh
                ? '在其他地区打球？查看<a href="' . self::e($australia) . '">澳大利亚</a>和<a href="' . self::e($uk) . '">英国</a>的打球场所。'
                : 'Playing somewhere else? See where to play in <a href="' . self::e($australia) . '">Australia</a> and the <a href="' . self::e($uk) . '">UK</a>.')
            . '</p></div>';

        $list = $this->firstByClass($xpath, 'list-posts', $main);
        $fragment = $this->fragment($dom, $intro);

        if ($list !== null && $fragment !== null) {
            $list->parentNode?->insertBefore($fragment, $list);
        }
    }

    /* -----------------------------------------------------------------
     * Structured data inputs
     * -------------------------------------------------------------- */

    /**
     * The breadcrumb trail PostLayout rebuilt, as [name, url] pairs ending
     * with the page itself.
     *
     * @return list<array{0: string, 1: string}>
     */
    private function breadcrumbs(\DOMXPath $xpath, \DOMElement $main, string $path, string $headline): array
    {
        $trail = [];
        $seen = [];

        foreach ($xpath->query('.//nav[contains(concat(" ", normalize-space(@class), " "), " post-breadcrumb ")]/a', $main) as $anchor) {
            if (!$anchor instanceof \DOMElement) {
                continue;
            }

            $name = trim((string) preg_replace('~\s+~u', ' ', $anchor->textContent));
            $href = $anchor->getAttribute('href');

            if ($name === '' || !str_starts_with($href, '/') || isset($seen[$href]) || $href === $path) {
                continue;
            }

            // The export's "parent" pseudo-category is not a real section.
            if (strtolower($name) === 'parent') {
                continue;
            }

            $seen[$href] = true;
            $trail[] = [$name, $href];
        }

        if ($trail === []) {
            return [];
        }

        if ($headline !== '') {
            $trail[] = [$headline, $path];
        }

        return $trail;
    }

    /* -----------------------------------------------------------------
     * Helpers
     * -------------------------------------------------------------- */

    private function fragment(\DOMDocument $dom, string $html): ?\DOMNode
    {
        $scratch = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $scratch->loadHTML('<?xml encoding="UTF-8"><div id="__fragment">' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $wrapper = $scratch->getElementById('__fragment');

        if ($wrapper === null) {
            foreach ($scratch->getElementsByTagName('div') as $div) {
                if ($div->getAttribute('id') === '__fragment') {
                    $wrapper = $div;
                    break;
                }
            }
        }

        if (!$wrapper instanceof \DOMElement) {
            return null;
        }

        $fragment = $dom->createDocumentFragment();

        foreach (iterator_to_array($wrapper->childNodes) as $child) {
            $fragment->appendChild($dom->importNode($child, true));
        }

        return $fragment;
    }

    private function firstByClass(\DOMXPath $xpath, string $class, \DOMNode $context): ?\DOMElement
    {
        $node = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")]', $context)->item(0);

        return $node instanceof \DOMElement ? $node : null;
    }

    /**
     * A page's address in $locale. A few pages sit at a different slug in
     * the Chinese mirror than in the English tree (the same mapping the
     * header's navigation uses), so prefixing "/zh" cannot find them.
     */
    private function localizedPage(string $path, string $locale): string
    {
        if ($locale === Locale::CHINESE) {
            $canonical = Canonical::path($path, $this->links);

            if (isset(self::ZH_SLUGS[$canonical]) && $this->links->exists(self::ZH_SLUGS[$canonical])) {
                return self::ZH_SLUGS[$canonical];
            }
        }

        return $this->links->localized($path, $locale);
    }

    private static function hasHan(string $text): bool
    {
        return preg_match('~\p{Han}~u', $text) === 1;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
