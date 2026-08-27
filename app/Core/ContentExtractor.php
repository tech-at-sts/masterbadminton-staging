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

        $content = $main instanceof \DOMNode ? $this->innerHtml($dom, $main) : '';

        return new PageContent(
            $title !== null && $title !== '' ? $title : self::DEFAULT_TITLE,
            $description ?? '',
            $content,
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
}
