<?php

declare(strict_types=1);

namespace App\Content;

use App\Core\Locale;

/**
 * Article-page layout surgery, applied to the legacy WordPress markup after
 * ContentExtractor has pulled #main out of an exported file.
 *
 * This is the article-page counterpart of HomepageLayout, and follows the
 * same two rules:
 *
 *  - Every transform is keyed off the markup it needs, not off the request
 *    path, so the English tree and the Chinese mirror get identical
 *    treatment from the same code.
 *  - Nothing here writes or edits article copy. Every transform moves,
 *    re-parents or re-tags nodes that the export already contained; the
 *    words, their order, their emphasis and their links are carried across
 *    as the original DOM nodes. The only strings this class authors are
 *    page furniture that never existed in the source at all - the
 *    "In this article" label and the reading estimate - and those are held
 *    in COPY below, in both languages.
 *
 * Two page shapes are recognised:
 *
 *  - "post": a single article (article.single-post). Its <h1> and its
 *    breadcrumb category are handed back to the caller so the shared header
 *    can paint them into the same charcoal hero band the homepage and the
 *    category directory wear, and the body is reflowed into a contents
 *    rail beside the prose.
 *  - "archive": a listing of posts (div.list-posts) - the Blog page and the
 *    category archives. Its heading moves into the same hero band and its
 *    items are tagged so they can be dressed as cards.
 */
final class PostLayout
{
    /** Page furniture authored here, in the language of the tree. */
    private const COPY = [
        Locale::ENGLISH => [
            'contents' => 'In this article',
            'share' => 'Share',
            'read_time' => '%d min read',
            'breadcrumb' => 'Breadcrumb',
            'eyebrow' => 'Article',
            'archive_eyebrow' => 'Browse',
        ],
        Locale::CHINESE => [
            'contents' => '本文目录',
            'share' => '分享',
            'read_time' => '约 %d 分钟阅读',
            'breadcrumb' => '面包屑导航',
            'eyebrow' => '文章',
            'archive_eyebrow' => '浏览',
        ],
    ];

    /** Below this many headings a contents rail is noise rather than help. */
    private const MIN_TOC_ENTRIES = 3;

    /** Reading pace, in words and in Han characters per minute. */
    private const WORDS_PER_MINUTE = 220;
    private const HAN_PER_MINUTE = 400;

    /**
     * Reshape the page and hand back what the header needs to paint it.
     *
     * @param \DOMNode|null $root the extracted content region (#main). Every
     *        query below is asked inside it rather than of the whole
     *        document, because the legacy <body> wears the theme's own
     *        "single single-post" classes: a document-wide search for the
     *        article matched the body element, and the reflow then rebuilt
     *        a region that is never rendered while quietly emptying the one
     *        that is.
     *
     * @return array{layout: string, hero: array<string, string>}|null
     *         null when the page is neither an article nor a listing, in
     *         which case nothing was touched.
     */
    public function apply(\DOMDocument $dom, \DOMXPath $xpath, ?\DOMNode $root, string $locale): ?array
    {
        if (!$root instanceof \DOMElement) {
            return null;
        }

        $copy = self::COPY[$locale] ?? self::COPY[Locale::ENGLISH];

        $article = $this->firstByClass($xpath, 'single-post', $root);

        if ($article !== null) {
            return ['layout' => 'post', 'hero' => $this->buildPost($dom, $xpath, $root, $article, $copy)];
        }

        $list = $this->firstByClass($xpath, 'list-posts', $root);

        if ($list !== null) {
            return ['layout' => 'archive', 'hero' => $this->buildArchive($dom, $xpath, $root, $list, $copy)];
        }

        // Standalone pages - Contact, Disclaimer, Privacy Policy - are not
        // articles, but they are nav and footer destinations, and leaving
        // them on the old theme made three of the site's links open what
        // looks like a different site. They get the same shell and the same
        // prose treatment, without the contents rail an article needs.
        $page = $this->firstByClass($xpath, 'type-page', $root);

        if ($page !== null) {
            return ['layout' => 'post', 'hero' => $this->buildPage($dom, $xpath, $root, $page, $copy)];
        }

        return null;
    }

    /**
     * @param array<string, string> $copy
     * @return array<string, string>
     */
    private function buildPage(
        \DOMDocument $dom,
        \DOMXPath $xpath,
        \DOMElement $root,
        \DOMElement $page,
        array $copy,
    ): array {
        $page->setAttribute('class', trim($page->getAttribute('class') . ' post-article post-body'));

        $trail = $this->harvestBreadcrumb($dom, $xpath, $root);
        $title = $this->liftTitle($xpath, $root);

        $this->normaliseHeadings($dom, $xpath, $page);
        $this->tagFurniture($xpath, $root);
        $this->splitColumns($dom, $page, null);
        $this->sinkAdSlots($xpath, $page);
        $this->reattachPager($xpath, $root, $page);

        if ($trail !== null) {
            $trail->setAttribute('aria-label', $copy['breadcrumb']);
            $page->insertBefore($trail, $page->firstChild);
        }

        return ['title' => $title, 'variant' => 'page'];
    }

    /* -----------------------------------------------------------------
     * Single article
     * -------------------------------------------------------------- */

    /**
     * @param array<string, string> $copy
     * @return array<string, string>
     */
    private function buildPost(
        \DOMDocument $dom,
        \DOMXPath $xpath,
        \DOMElement $root,
        \DOMElement $article,
        array $copy,
    ): array {
        $article->setAttribute('class', trim($article->getAttribute('class') . ' post-article'));

        $trail = $this->harvestBreadcrumb($dom, $xpath, $root);
        $title = $this->liftTitle($xpath, $root);

        $body = $this->firstByClass($xpath, 'full-content', $root);
        $body?->setAttribute('class', trim($body->getAttribute('class') . ' post-body'));

        $headings = $body !== null ? $this->normaliseHeadings($dom, $xpath, $body) : [];
        $readMinutes = $body !== null ? $this->readingMinutes($body->textContent) : 0;

        // Scoped to the whole content region, not to the <article>: on a
        // fifth of the exported posts the markup closes the article early
        // and the prev/next block ends up as its sibling, so an
        // article-scoped search silently missed it.
        $this->tagFurniture($xpath, $root);

        $rail = $this->buildRail($dom, $xpath, $root, $headings, $copy);
        $this->splitColumns($dom, $article, $rail);
        $this->sinkAdSlots($xpath, $article);
        $this->reattachPager($xpath, $root, $article);

        if ($trail !== null) {
            $trail->setAttribute('aria-label', $copy['breadcrumb']);
            $article->insertBefore($trail, $article->firstChild);
        }

        // Where the trail's section step is just the article's own name
        // again - which is how the exporter wrote the breadcrumb for posts
        // that are their own category - the eyebrow would repeat the
        // headline directly below it, so the generic label is used instead.
        $section = $this->trailCategory($xpath, $trail);

        if ($section !== null && $this->sameText($section, $title)) {
            $section = null;
        }

        $hero = [
            'eyebrow' => $section ?? $copy['eyebrow'],
            'title' => $title,
            'variant' => 'post',
        ];

        if ($readMinutes > 0) {
            $hero['meta'] = sprintf($copy['read_time'], $readMinutes);
        }

        return $hero;
    }

    /**
     * Move the article's <h1> text into the hero band.
     *
     * The heading is removed from the body because the hero now carries it:
     * the title is rendered once, in exactly the words the export wrote,
     * just at the top of the page rather than inside the white panel.
     */
    private function liftTitle(\DOMXPath $xpath, \DOMElement $root): string
    {
        $heading = $xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " entry-content ")]/h1', $root)->item(0);

        if (!$heading instanceof \DOMElement) {
            $heading = $xpath->query('.//h1[contains(concat(" ", normalize-space(@class), " "), " page-title ")]', $root)->item(0);
        }

        if (!$heading instanceof \DOMElement) {
            return '';
        }

        $text = trim($heading->textContent);
        $heading->parentNode?->removeChild($heading);

        return $text;
    }

    /**
     * Rebuild the legacy breadcrumb as a single rail.
     *
     * Exported pages carry the trail twice - once in the theme's
     * .breadcrumb-title-wrapper (which the page's own inline CSS then hides)
     * and once in the plugin's .breadcr block. The anchors of whichever copy
     * is richer are moved into a new <nav>, and both legacy blocks are then
     * dropped, so the trail appears once and is styled with the rest of the
     * page rather than by leftover theme rules.
     */
    private function harvestBreadcrumb(\DOMDocument $dom, \DOMXPath $xpath, \DOMElement $root): ?\DOMElement
    {
        $sources = [
            $this->firstByClass($xpath, 'inner-br', $root),
            $this->firstByClass($xpath, 'breadcrumbs-container', $root),
        ];

        $best = null;
        $bestCount = 0;

        foreach ($sources as $source) {
            if ($source === null) {
                continue;
            }

            $count = $xpath->query('.//a', $source)->length;

            if ($count > $bestCount) {
                $best = $source;
                $bestCount = $count;
            }
        }

        $nav = null;

        if ($best !== null && $bestCount > 0) {
            $nav = $dom->createElement('nav');
            $nav->setAttribute('class', 'post-breadcrumb');

            foreach (iterator_to_array($xpath->query('.//a', $best)) as $anchor) {
                if (!$anchor instanceof \DOMElement) {
                    continue;
                }

                $label = trim($anchor->textContent);

                if ($label === '') {
                    continue;
                }

                // Drop the theme's own classes so the rail is styled once,
                // here; href and text are the export's own.
                $anchor->removeAttribute('class');
                $anchor->removeAttribute('style');
                $anchor->setAttribute('class', 'post-breadcrumb-link');
                $nav->appendChild($anchor);
            }
        }

        foreach (['breadcrumb-title-wrapper', 'breadcr'] as $class) {
            $legacy = $this->firstByClass($xpath, $class, $root);
            $legacy?->parentNode?->removeChild($legacy);
        }

        return $nav !== null && $nav->childNodes->length > 0 ? $nav : null;
    }

    /** The section this article sits in: the last breadcrumb step before the article itself. */
    private function trailCategory(\DOMXPath $xpath, ?\DOMElement $trail): ?string
    {
        if ($trail === null) {
            return null;
        }

        $labels = [];

        foreach ($xpath->query('./a', $trail) as $anchor) {
            $label = trim($anchor->textContent);

            if ($label !== '') {
                $labels[] = $label;
            }
        }

        // [Home, Section, ...] - the first step after Home names the section.
        return $labels[1] ?? null;
    }

    /**
     * Give every section heading in the article body a real heading tag and
     * a stable id, and hand back the ones worth listing.
     *
     * Most posts mark their sections not with <h2> but with a paragraph
     * holding a single coloured, enlarged <b> - a visual heading with no
     * semantics, which no screen reader, no outline and no contents list
     * can see. Those paragraphs are re-tagged as <h2>; the words inside
     * them, and any markup around those words, are moved across untouched.
     *
     * @return list<array{id: string, level: int, label: string}>
     */
    private function normaliseHeadings(\DOMDocument $dom, \DOMXPath $xpath, \DOMElement $body): array
    {
        foreach (iterator_to_array($xpath->query('.//p', $body)) as $paragraph) {
            if (!$paragraph instanceof \DOMElement) {
                continue;
            }

            $emphasis = $this->soleVisualHeading($paragraph);

            if ($emphasis === null) {
                continue;
            }

            $heading = $dom->createElement('h2');
            $heading->setAttribute('class', 'post-heading');

            while ($emphasis->firstChild !== null) {
                $heading->appendChild($emphasis->firstChild);
            }

            $paragraph->parentNode?->replaceChild($heading, $paragraph);
        }

        $entries = [];
        $index = 0;

        foreach ($xpath->query('.//h2 | .//h3', $body) as $heading) {
            if (!$heading instanceof \DOMElement) {
                continue;
            }

            $label = trim(preg_replace('/\s+/u', ' ', $heading->textContent) ?? '');

            if ($label === '') {
                continue;
            }

            // Headings inside a layout table belong to a cell, and the one
            // inside the comment block belongs to the page furniture -
            // neither is part of the article's outline, so both are styled
            // but never listed.
            $skip = $xpath->query('ancestor::table', $heading)->length > 0
                || $xpath->query('ancestor::*[@id="COMMENTING"]', $heading)->length > 0
                || $xpath->query('ancestor::*[contains(concat(" ", normalize-space(@class), " "), " fb-comment ")]', $heading)->length > 0;

            if (!$this->hasClass($heading, 'post-heading')) {
                $heading->setAttribute('class', trim($heading->getAttribute('class') . ' post-heading'));
            }

            if ($skip) {
                continue;
            }

            $id = $heading->getAttribute('id');

            if ($id === '') {
                $id = 'section-' . (++$index);
                $heading->setAttribute('id', $id);
            }

            $entries[] = [
                'id' => $id,
                'level' => $heading->nodeName === 'h3' ? 3 : 2,
                'label' => $label,
            ];
        }

        return $entries;
    }

    /**
     * The <b>/<strong> of a paragraph that is nothing but one styled,
     * emphasised run - the legacy pages' stand-in for a section heading -
     * or null when the paragraph is ordinary prose.
     *
     * Deliberately strict: a paragraph that merely *opens* with a bold
     * lead-in ("General rule of thumb: ...") still has text after it and is
     * left as a paragraph.
     */
    private function soleVisualHeading(\DOMElement $paragraph): ?\DOMElement
    {
        $node = $this->soleElementChild($paragraph);

        if ($node === null || strtolower($node->nodeName) !== 'span') {
            return null;
        }

        // The size bump is what made it read as a heading in the export.
        if (!preg_match('/font-size\s*:/i', $node->getAttribute('style'))) {
            return null;
        }

        $emphasis = $this->soleElementChild($node);

        if ($emphasis === null || !in_array(strtolower($emphasis->nodeName), ['b', 'strong'], true)) {
            return null;
        }

        return trim($emphasis->textContent) === '' ? null : $emphasis;
    }

    /** Whether two headings say the same thing, ignoring case and spacing. */
    private function sameText(string $a, string $b): bool
    {
        $normalise = static fn (string $text): string => mb_strtolower(
            trim(preg_replace('/\s+/u', ' ', $text) ?? ''),
            'UTF-8',
        );

        return $normalise($a) !== '' && $normalise($a) === $normalise($b);
    }

    /** The one element child of $node, when the node holds nothing else but blank text. */
    private function soleElementChild(\DOMElement $node): ?\DOMElement
    {
        $found = null;

        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMText) {
                if (trim($child->nodeValue ?? '') !== '') {
                    return null;
                }

                continue;
            }

            if ($child instanceof \DOMComment) {
                continue;
            }

            if (!$child instanceof \DOMElement || $found !== null) {
                return null;
            }

            $found = $child;
        }

        return $found;
    }

    /**
     * Tag the leftovers the theme scattered through the article - share
     * widgets, ad slots, the Facebook comment block, the prev/next pair -
     * so the stylesheet can place them instead of letting them float.
     */
    private function tagFurniture(\DOMXPath $xpath, \DOMElement $article): void
    {
        // The ad's own wrapper, and only that: the export nests each <ins>
        // in a one-purpose <div> of its own. Walking any further up reaches
        // .content-wrapper, which holds the whole article - tagging that as
        // an ad slot centred every paragraph on the page.
        foreach ($xpath->query('.//ins[contains(concat(" ", normalize-space(@class), " "), " adsbygoogle ")]', $article) as $ad) {
            $slot = $ad->parentNode;

            if ($slot instanceof \DOMElement && !$this->hasClass($slot, 'post-slot-ad')) {
                $slot->setAttribute('class', trim($slot->getAttribute('class') . ' post-slot post-slot-ad'));
            }
        }

        foreach ($xpath->query('.//div[@id="COMMENTING"]', $article) as $comments) {
            if ($comments instanceof \DOMElement) {
                $comments->setAttribute('class', trim($comments->getAttribute('class') . ' post-comments'));
            }
        }

        $navigation = $this->firstByClass($xpath, 'single-navigation', $article);
        $navigation?->setAttribute(
            'class',
            trim($navigation->getAttribute('class') . ' post-pager'),
        );

        foreach ($xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " single-navigation ")]/a', $article) as $link) {
            if ($link instanceof \DOMElement) {
                $link->setAttribute(
                    'class',
                    trim($link->getAttribute('class') . ' post-pager-link post-pager-' . $link->getAttribute('rel')),
                );
            }
        }
    }

    /**
     * Build the sticky rail that sits beside the prose: the contents list,
     * then the export's own share widgets moved out of the headline area
     * where they used to float against the title.
     *
     * @param list<array{id: string, level: int, label: string}> $headings
     * @param array<string, string> $copy
     */
    private function buildRail(
        \DOMDocument $dom,
        \DOMXPath $xpath,
        \DOMElement $article,
        array $headings,
        array $copy,
    ): ?\DOMElement {
        $inner = $dom->createElement('div');
        $inner->setAttribute('class', 'post-rail-inner');

        if (count($headings) >= self::MIN_TOC_ENTRIES) {
            $label = $dom->createElement('p');
            $label->setAttribute('class', 'post-rail-title');
            $label->textContent = $copy['contents'];
            $inner->appendChild($label);

            $list = $dom->createElement('ul');
            $list->setAttribute('class', 'post-toc');

            foreach ($headings as $heading) {
                $item = $dom->createElement('li');
                $item->setAttribute('class', 'post-toc-item post-toc-level-' . $heading['level']);

                $link = $dom->createElement('a');
                $link->setAttribute('href', '#' . $heading['id']);
                $link->textContent = $heading['label'];

                $item->appendChild($link);
                $list->appendChild($item);
            }

            $inner->appendChild($list);
        }

        $share = $dom->createElement('div');
        $share->setAttribute('class', 'post-share');

        foreach (['fb-pg-lk', 'sharing-bs'] as $class) {
            $widget = $this->firstByClass($xpath, $class, $article);

            if ($widget === null) {
                continue;
            }

            $widget->removeAttribute('style');
            $widget->setAttribute('class', trim($widget->getAttribute('class') . ' post-share-widget'));
            $share->appendChild($widget);
        }

        if ($share->childNodes->length > 0) {
            $shareLabel = $dom->createElement('p');
            $shareLabel->setAttribute('class', 'post-rail-title');
            $shareLabel->textContent = $copy['share'];
            $share->insertBefore($shareLabel, $share->firstChild);
            $inner->appendChild($share);
        }

        if ($inner->childNodes->length === 0) {
            return null;
        }

        $rail = $dom->createElement('aside');
        $rail->setAttribute('class', 'post-rail');
        $rail->appendChild($inner);

        return $rail;
    }

    /**
     * Put the article body and the rail side by side.
     *
     * Everything the article already held is moved, in order, into the
     * prose column - nothing is dropped and nothing is reordered - and the
     * rail is added as its sibling. With no rail to show, the wrapper is
     * still added so the prose keeps its measure, just in one column.
     */
    private function splitColumns(\DOMDocument $dom, \DOMElement $article, ?\DOMElement $rail): void
    {
        $layout = $dom->createElement('div');
        $layout->setAttribute('class', 'post-layout' . ($rail === null ? ' post-layout-single' : ''));

        $main = $dom->createElement('div');
        $main->setAttribute('class', 'post-main');

        while ($article->firstChild !== null) {
            $main->appendChild($article->firstChild);
        }

        if ($rail !== null) {
            $layout->appendChild($rail);
        }

        $layout->appendChild($main);
        $article->appendChild($layout);
    }

    /**
     * Put the prev/next pair back at the foot of the prose column.
     *
     * Where the export closed the <article> early, the pair is a sibling of
     * the article rather than a child of it, and would otherwise render
     * outside the reflowed columns.
     */
    private function reattachPager(\DOMXPath $xpath, \DOMElement $root, \DOMElement $article): void
    {
        $pager = $this->firstByClass($xpath, 'post-pager', $root);
        $main = $this->firstByClass($xpath, 'post-main', $article);

        if ($pager !== null && $main !== null && $pager->parentNode !== $main) {
            $main->appendChild($pager);
        }
    }

    /**
     * Send the theme's ad slots to the foot of the article.
     *
     * The export opens the page with one, immediately under the title, so
     * the first thing a reader met was a 300x250 hole. They keep their
     * markup and their slot ids - they are only moved, in the order they
     * appeared, to after the prose.
     */
    private function sinkAdSlots(\DOMXPath $xpath, \DOMElement $article): void
    {
        $main = $this->firstByClass($xpath, 'post-main', $article);

        if ($main === null) {
            return;
        }

        foreach (iterator_to_array($xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " post-slot-ad ")]', $main)) as $slot) {
            if ($slot instanceof \DOMElement) {
                $main->appendChild($slot);
            }
        }
    }

    /**
     * Minutes of reading, from the article's own text. Latin words and Han
     * characters are counted separately because they are read at very
     * different rates, and the Chinese mirror is a full translation rather
     * than a transliteration.
     */
    private function readingMinutes(string $text): int
    {
        $han = preg_match_all('/\p{Han}/u', $text) ?: 0;
        $latin = preg_match_all('/[\p{Latin}\p{Nd}][\p{Latin}\p{Nd}\'’\-]*/u', $text) ?: 0;

        $minutes = (int) ceil($latin / self::WORDS_PER_MINUTE + $han / self::HAN_PER_MINUTE);

        return max(0, min($minutes, 120));
    }

    /* -----------------------------------------------------------------
     * Listing pages (the Blog page and the category archives)
     * -------------------------------------------------------------- */

    /**
     * @param array<string, string> $copy
     * @return array<string, string>
     */
    private function buildArchive(
        \DOMDocument $dom,
        \DOMXPath $xpath,
        \DOMElement $root,
        \DOMElement $list,
        array $copy,
    ): array {
        $list->setAttribute('class', trim($list->getAttribute('class') . ' post-cards'));

        // Put every post back on the same level before dressing them.
        //
        // WordPress cut some excerpts in the middle of their markup, so the
        // export contains posts with unclosed <div>s; the parser then nests
        // every following post inside the broken one, and a grid built from
        // the direct children shows two cards instead of twenty. Re-appending
        // each post to the list in document order un-nests them without
        // touching a word of their contents.
        foreach (iterator_to_array($xpath->query('.//article[contains(concat(" ", normalize-space(@class), " "), " post-item ")]', $root)) as $item) {
            if (!$item instanceof \DOMElement) {
                continue;
            }

            $list->appendChild($item);
            $item->setAttribute('class', trim($item->getAttribute('class') . ' post-card'));

            // A truncated excerpt can also carry a stray <style> block from
            // the page it was cut out of, which would otherwise apply to the
            // whole listing.
            foreach (iterator_to_array($xpath->query('.//style | .//script', $item)) as $stray) {
                $stray->parentNode?->removeChild($stray);
            }
        }

        // The pager rides along inside the broken post; it belongs after the
        // grid.
        $pagerNav = $xpath->query('.//nav[contains(concat(" ", normalize-space(@class), " "), " ts-pagination ")]', $root)->item(0);

        if ($pagerNav instanceof \DOMElement && $list->parentNode !== null) {
            $list->parentNode->insertBefore($pagerNav, $list->nextSibling);
        }

        // The read-more button of a truncated post ends up inside the
        // excerpt it was meant to follow, where the card's clipped excerpt
        // box hides half of it. Each card's button is moved back out to the
        // foot of its own card.
        foreach ($xpath->query('.//article[contains(concat(" ", normalize-space(@class), " "), " post-card ")]', $list) as $card) {
            if (!$card instanceof \DOMElement) {
                continue;
            }

            $more = $xpath->query('.//a[contains(concat(" ", normalize-space(@class), " "), " button-readmore ")]', $card)->item(0);

            if ($more instanceof \DOMElement) {
                $more->setAttribute('class', trim($more->getAttribute('class') . ' post-card-more'));
                $card->appendChild($more);
            }
        }

        $title = $this->liftTitle($xpath, $root);
        $trail = $this->harvestBreadcrumb($dom, $xpath, $root);

        if ($trail !== null && $list->parentNode !== null) {
            $trail->setAttribute('aria-label', $copy['breadcrumb']);
            $list->parentNode->insertBefore($trail, $list);
        }

        $pagerQuery = './/nav[contains(concat(" ", normalize-space(@class), " "), " ts-pagination ")]'
            . ' | .//div[contains(concat(" ", normalize-space(@class), " "), " wp-pagenavi ")]'
            . ' | .//div[contains(concat(" ", normalize-space(@class), " "), " pagination ")]';

        foreach ($xpath->query($pagerQuery, $root) as $pager) {
            if ($pager instanceof \DOMElement) {
                $pager->setAttribute('class', trim($pager->getAttribute('class') . ' post-archive-pager'));
            }
        }

        return [
            'eyebrow' => $copy['archive_eyebrow'],
            'title' => $title,
            'variant' => 'archive',
        ];
    }

    /* -----------------------------------------------------------------
     * Small DOM helpers, mirroring HomepageLayout's
     * -------------------------------------------------------------- */

    private function firstByClass(\DOMXPath $xpath, string $class, \DOMElement $context): ?\DOMElement
    {
        $query = './/*[contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")]';
        $node = $xpath->query($query, $context)->item(0);

        return $node instanceof \DOMElement ? $node : null;
    }

    private function hasClass(\DOMElement $element, string $class): bool
    {
        return str_contains(' ' . $element->getAttribute('class') . ' ', ' ' . $class . ' ');
    }
}
