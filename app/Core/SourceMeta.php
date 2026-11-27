<?php

declare(strict_types=1);

namespace App\Core;

/**
 * The publishing facts WordPress (via Yoast) wrote into the <head> of every
 * exported page: when it was published, when it was last changed, and the
 * image it was shared with.
 *
 * The shared header replaces the exported <head> wholesale, so without this
 * those facts were thrown away - which is how the site lost its dates, its
 * share images and every article's "last updated" signal in the move. They
 * are read straight out of the file with patterns rather than a DOM parse,
 * because the sitemap asks for them for every page on the site.
 */
final class SourceMeta
{
    /** Only the head is read; Yoast always writes these near the top. */
    private const HEAD_BYTES = 24000;

    /**
     * @return array{published: string, modified: string, image: string, type: string}
     *         ISO-8601 dates and a site-relative or absolute image URL;
     *         '' for anything the page does not declare.
     */
    public static function read(string $file): array
    {
        $head = (string) @file_get_contents($file, false, null, 0, self::HEAD_BYTES);
        $end = stripos($head, '</head>');

        if ($end !== false) {
            $head = substr($head, 0, $end);
        }

        return [
            'published' => self::date(self::property($head, 'article:published_time')),
            'modified' => self::date(self::property($head, 'article:modified_time')),
            'image' => html_entity_decode(self::property($head, 'og:image'), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'type' => self::property($head, 'og:type'),
        ];
    }

    private static function property(string $head, string $property): string
    {
        $quoted = preg_quote($property, '~');

        if (preg_match('~<meta\s+property=["\']' . $quoted . '["\']\s+content=["\']([^"\']*)["\']~i', $head, $m) === 1
            || preg_match('~<meta\s+content=["\']([^"\']*)["\']\s+property=["\']' . $quoted . '["\']~i', $head, $m) === 1) {
            return trim($m[1]);
        }

        return '';
    }

    private static function date(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $time = strtotime($value);

        return $time === false ? '' : gmdate('c', $time);
    }
}
