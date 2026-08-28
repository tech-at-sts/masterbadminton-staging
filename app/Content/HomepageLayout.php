<?php

declare(strict_types=1);

namespace App\Content;

/**
 * Homepage-specific layout surgery, applied to the legacy WPBakery markup
 * after ContentExtractor has pulled #main out of the exported HTML.
 *
 * Every transform here is keyed off the markup it needs rather than off the
 * request path, matching how the rest of the extractor works: the homepage
 * layout is also reused verbatim by a couple of other exported pages and by
 * the Chinese mirror, and they should all get the same treatment.
 *
 * These transforms only ever move, wrap or re-parent existing nodes. No
 * transform rewrites copy: headings, paragraphs, category names, counts and
 * link targets are carried across as the original DOM nodes.
 */
final class HomepageLayout
{
    /**
     * Literal Revolution Slider shortcodes left in the exported HTML. The
     * source page stores the alias in curly quotes ([rev_slider alias=”…”]),
     * which WordPress' shortcode regex never matched, so do_shortcode() left
     * the raw text in place and the export captured it verbatim. There is no
     * WordPress, no plugin and no slider definition in this tree, so the
     * token is stripped rather than expanded.
     */
    private const SHORTCODE_PATTERN = '/\[rev_slider\b[^\]]*\]/u';

    public function apply(\DOMDocument $dom, \DOMXPath $xpath): void
    {
        // Safe everywhere: the token is literal text wherever it appears.
        $this->removeSliderShortcode($xpath);

        if (!$this->isHomepageLayout($xpath)) {
            return;
        }

        $this->frameCategoryDirectory($dom, $xpath);

        $sections = $this->identifyCategorySections($xpath);

        $this->buildQuickNav($dom, $xpath, $sections);
        $this->reflowIntro($dom, $xpath);
        $this->relocateSocialModule($dom, $xpath);
    }

    /**
     * The eight-icon strip (ul#thct) only exists on pages built from the
     * homepage layout, which makes it a reliable marker for the rest of the
     * transforms. It matters: the Facebook block and the WPBakery text
     * column that the reflow targets also appear on well over a thousand
     * article pages, and those pages are out of scope here.
     */
    private function isHomepageLayout(\DOMXPath $xpath): bool
    {
        return $xpath->query('//ul[@id="thct"]')->length > 0;
    }

    /**
     * Drop the unprocessed [rev_slider …] token so it stops rendering as
     * literal text. Only the shortcode itself is removed; any surrounding
     * text in the same node is preserved.
     */
    private function removeSliderShortcode(\DOMXPath $xpath): void
    {
        foreach ($xpath->query('//text()[contains(., "[rev_slider")]') as $text) {
            $replaced = preg_replace(self::SHORTCODE_PATTERN, '', $text->nodeValue ?? '');

            if ($replaced !== null) {
                $text->nodeValue = $replaced;
            }
        }
    }

    /**
     * Wrap the "All Categories" heading, subtitle and controls in their own
     * header band so the block reads as a directory/browse panel rather than
     * a second copy of the icon strip. The cards themselves are untouched.
     */
    private function frameCategoryDirectory(\DOMDocument $dom, \DOMXPath $xpath): void
    {
        $section = $this->firstByClass($xpath, 'cat-section');

        if ($section === null || $this->hasClass($section, 'cat-directory')) {
            return;
        }

        $section->setAttribute('class', $section->getAttribute('class') . ' cat-directory');

        $head = $dom->createElement('div');
        $head->setAttribute('class', 'cat-directory-head');
        $section->insertBefore($head, $section->firstChild);

        foreach (['cat-section-title', 'cat-section-subtitle', 'cat-section-controls'] as $class) {
            $node = $this->firstByClass($xpath, $class, $section);

            if ($node !== null) {
                $head->appendChild($node);
            }
        }
    }

    /**
     * Give every category card a stable id so the quick-nav tabs have
     * something to jump to, and hand back the ids in document order.
     *
     * @return list<string>
     */
    private function identifyCategorySections(\DOMXPath $xpath): array
    {
        $ids = [];
        $used = [];

        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " cat-accordion ")]') as $index => $card) {
            if (!$card instanceof \DOMElement) {
                continue;
            }

            $title = $this->firstByClass($xpath, 'cat-accordion-title', $card);
            $slug = $this->slug($title?->textContent ?? '');

            if ($slug === '') {
                $slug = 'section-' . ($index + 1);
            }

            $id = 'category-' . $slug;
            $suffix = 2;

            while (isset($used[$id])) {
                $id = 'category-' . $slug . '-' . $suffix++;
            }

            $used[$id] = true;
            $card->setAttribute('id', $id);
            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * Rebuild the eight-icon strip (ul#thct) as a sticky tab bar.
     *
     * Each <li> currently holds two separate links to the same target - one
     * around the label, one around the icon. They are collapsed into a
     * single tab-shaped link, with the original label nodes and the original
     * <img> moved across rather than recreated, so text and artwork are
     * untouched. The strip is then lifted out of its short WPBakery row and
     * made a direct child of the article, which is what lets position:sticky
     * hold it in place for the rest of the page.
     *
     * @param list<string> $sectionIds jump targets, in strip order
     */
    private function buildQuickNav(\DOMDocument $dom, \DOMXPath $xpath, array $sectionIds): void
    {
        $list = $xpath->query('//ul[@id="thct"]')->item(0);

        if (!$list instanceof \DOMElement) {
            return;
        }

        foreach (iterator_to_array($xpath->query('./li', $list)) as $index => $item) {
            if (!$item instanceof \DOMElement) {
                continue;
            }

            $anchor = $xpath->query('.//a', $item)->item(0);

            if (!$anchor instanceof \DOMElement) {
                continue;
            }

            // Prefer the in-page section; fall back to the original link
            // target when this page has no matching category card.
            $href = isset($sectionIds[$index]) ? '#' . $sectionIds[$index] : $anchor->getAttribute('href');

            $link = $dom->createElement('a');
            $link->setAttribute('class', 'quick-nav-link');
            $link->setAttribute('href', $href);

            $icon = $dom->createElement('span');
            $icon->setAttribute('class', 'quick-nav-icon');

            $image = $xpath->query('.//img', $item)->item(0);

            if ($image instanceof \DOMElement) {
                $image->parentNode?->removeChild($image);
                $icon->appendChild($image);
            }

            $link->appendChild($icon);

            $label = $dom->createElement('span');
            $label->setAttribute('class', 'quick-nav-label');

            while ($anchor->firstChild !== null) {
                $label->appendChild($anchor->firstChild);
            }

            $link->appendChild($label);

            while ($item->firstChild !== null) {
                $item->removeChild($item->firstChild);
            }

            $item->appendChild($link);
        }

        $nav = $dom->createElement('nav');
        $nav->setAttribute('class', 'quick-nav');
        $nav->setAttribute('aria-label', 'Category quick navigation');

        $inner = $dom->createElement('div');
        $inner->setAttribute('class', 'quick-nav-inner');
        $nav->appendChild($inner);

        // Replace the whole WPBakery row: left in place, the row's own height
        // would bound the sticky element to a few pixels of scrolling.
        $row = $xpath->query(
            'ancestor::div[contains(concat(" ", normalize-space(@class), " "), " vc_row ")][1]',
            $list,
        )->item(0);

        $anchorNode = $row instanceof \DOMElement ? $row : $list;
        $anchorNode->parentNode?->replaceChild($nav, $anchorNode);

        $inner->appendChild($list);
    }

    /**
     * Reflow the "How To Play Badminton" intro: the prose paragraphs go into
     * a two-column block, the paragraph that introduces the list keeps its
     * place directly above it, and the list becomes a card grid.
     *
     * The split is purely positional - the last paragraph before the list is
     * the lead-in - so no sentence is read, cut or rewritten.
     */
    private function reflowIntro(\DOMDocument $dom, \DOMXPath $xpath): void
    {
        $wrapper = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " wpb_text_column ")]//div[contains(concat(" ", normalize-space(@class), " "), " wpb_wrapper ")][h2][ul]')->item(0);

        if (!$wrapper instanceof \DOMElement) {
            return;
        }

        $paragraphs = iterator_to_array($xpath->query('./p', $wrapper));
        $list = $xpath->query('./ul', $wrapper)->item(0);

        if (count($paragraphs) < 3 || !$list instanceof \DOMElement) {
            return;
        }

        $column = $xpath->query(
            'ancestor::div[contains(concat(" ", normalize-space(@class), " "), " wpb_text_column ")][1]',
            $wrapper,
        )->item(0);

        if ($column instanceof \DOMElement) {
            $column->setAttribute('class', $column->getAttribute('class') . ' home-intro');
        }

        $lead = array_pop($paragraphs);

        $prose = $dom->createElement('div');
        $prose->setAttribute('class', 'home-intro-prose');
        $wrapper->insertBefore($prose, $paragraphs[0]);

        foreach ($paragraphs as $paragraph) {
            $prose->appendChild($paragraph);
        }

        if ($lead instanceof \DOMElement) {
            $lead->setAttribute('class', trim($lead->getAttribute('class') . ' home-intro-lead'));
        }

        $list->setAttribute('class', trim($list->getAttribute('class') . ' home-intro-list'));
    }

    /**
     * Demote the Facebook band. The whole WPBakery row - "Like Us on
     * Facebook", the like iframe, "Facebook Comments", the comments embed
     * and the trailing widgets - is moved into a compact <aside> as a single
     * unit, so nothing inside it is edited or reordered.
     */
    private function relocateSocialModule(\DOMDocument $dom, \DOMXPath $xpath): void
    {
        $comments = $this->firstByClass($xpath, 'fb-comments');

        if ($comments === null) {
            return;
        }

        $row = $xpath->query(
            'ancestor::div[contains(concat(" ", normalize-space(@class), " "), " vc_row ")][1]',
            $comments,
        )->item(0);

        if (!$row instanceof \DOMElement || $row->parentNode === null) {
            return;
        }

        $aside = $dom->createElement('aside');
        $aside->setAttribute('class', 'home-social');

        $row->parentNode->replaceChild($aside, $row);
        $aside->appendChild($row);
    }

    private function firstByClass(\DOMXPath $xpath, string $class, ?\DOMElement $context = null): ?\DOMElement
    {
        $query = ($context === null ? '//' : './/')
            . '*[contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")]';

        $node = $context === null ? $xpath->query($query)->item(0) : $xpath->query($query, $context)->item(0);

        return $node instanceof \DOMElement ? $node : null;
    }

    private function hasClass(\DOMElement $element, string $class): bool
    {
        return str_contains(' ' . $element->getAttribute('class') . ' ', ' ' . $class . ' ');
    }

    private function slug(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';

        return trim($slug, '-');
    }
}
