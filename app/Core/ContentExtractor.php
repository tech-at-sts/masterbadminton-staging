<?php

declare(strict_types=1);

namespace App\Core;

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
        $this->stripHomeCategoryColumn($dom, $xpath);
        $this->convertCategoryGridToAccordion($dom, $xpath);
        $pageStyle = $this->extractPageStyle($xpath);

        $content = $main instanceof \DOMNode ? $this->innerHtml($dom, $main) : '';

        return new PageContent(
            $title !== null && $title !== '' ? $title : self::DEFAULT_TITLE,
            $description ?? '',
            $pageStyle . $content,
        );
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
     * The "All Categories" grid (rows carrying the "catfrbn" class) is a set
     * of fixed-height, clipped-overflow boxes in the legacy markup — really
     * just a preview, not an accordion. Rebuild each box as a real <details>
     * disclosure so category lists actually expand and collapse.
     */
    private function convertCategoryGridToAccordion(\DOMDocument $dom, \DOMXPath $xpath): void
    {
        $columns = $xpath->query(
            '//*[contains(concat(" ", normalize-space(@class), " "), " catfrbn ")]'
            . '//*[contains(concat(" ", normalize-space(@class), " "), " vc_column-inner ")]',
        );

        foreach ($columns as $inner) {
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

            $title = $dom->createElement('span');
            $title->setAttribute('class', 'cat-accordion-title');
            $title->textContent = trim($heading->textContent);
            $summary->appendChild($title);

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

            while ($inner->firstChild !== null) {
                $inner->removeChild($inner->firstChild);
            }

            $inner->appendChild($details);
        }
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
