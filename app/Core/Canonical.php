<?php

declare(strict_types=1);

namespace App\Core;

/**
 * The one URL each page should be indexed under.
 *
 * Most pages are their own canonical. The exceptions are pages the export
 * left reachable at more than one address:
 *
 *  - the beginners' guide, published in three categories at once - the
 *    copy WordPress itself declared canonical keeps that role;
 *  - archive roots that also exist as a flat ".html" file
 *    (/category/badminton-news.html beside /category/badminton-news);
 *  - "page/1" of a paginated archive, which is the archive itself.
 *
 * The sitemap leaves the duplicates out and the header points their
 * canonical tag at the URL returned here, so ranking signals collect on one
 * address instead of being split.
 */
final class Canonical
{
    /** @var array<string, string> duplicate English path => canonical English path */
    private const ALIASES = [
        '/badminton-basics.html' => '/category/badminton-articles/badminton-basics.html',
        '/category/badminton-videos/badminton-basics.html' => '/category/badminton-articles/badminton-basics.html',
    ];

    public static function path(string $path, SiteLinks $links): string
    {
        $path = '/' . trim($path, '/');
        $locale = Locale::of($path);
        $neutral = Locale::neutral($path);

        if (isset(self::ALIASES[$neutral])) {
            $target = Locale::to(self::ALIASES[$neutral], $locale);

            if ($links->exists($target)) {
                return $target;
            }
        }

        $unpaged = (string) preg_replace('~/page/1(\.html)?$~', '', $path);

        if ($unpaged !== $path && $unpaged !== '' && $links->exists($unpaged)) {
            return $unpaged;
        }

        if (str_ends_with($path, '.html') && str_contains(trim($path, '/'), '/')) {
            $extensionless = substr($path, 0, -5);

            if ($links->exists($extensionless)) {
                return $extensionless;
            }
        }

        return $path;
    }

    /** Whether $path is a duplicate that should stay out of the sitemap. */
    public static function isDuplicate(string $path, SiteLinks $links): bool
    {
        return self::path($path, $links) !== '/' . trim($path, '/');
    }
}
