<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Points a WordPress-era asset URL at this deployment.
 *
 * The export was taken from WP Engine, so its theme CSS and JS, jQuery and
 * a large share of its images still load from masterbadminto.wpenginepowered.com
 * - or from http://masterbadminton.com, the bare domain, over plain HTTP.
 * Cancel the WP Engine plan and the site loses its styling and its images.
 *
 * Every such URL is mapped to the same path on this site (/wp-content/...,
 * /wp-includes/...) when the file is present in the repository. When it is
 * not present yet, the URL is pointed at WP Engine over HTTPS as a
 * fallback, so the page keeps working while the files are being copied
 * across - bin/mirror-wp-assets.sh pulls every file still served from
 * there. Once a file is committed, the next deploy serves it locally with no
 * code change.
 */
final class AssetUrl
{
    /** Legacy hosts whose /wp-content and /wp-includes are this site's own files. */
    private const LEGACY_HOSTS = [
        'masterbadminton.com',
        'www.masterbadminton.com',
        'masterbadminto.wpenginepowered.com',
        'masterbadminto.wpengine.com',
        'how-to-play-badminton.com',
        'www.how-to-play-badminton.com',
    ];

    /** Where files not yet copied across are still served from. */
    public const FALLBACK_ORIGIN = 'https://masterbadminto.wpenginepowered.com';

    private static ?string $root = null;

    /** @var array<string, bool> */
    private static array $exists = [];

    public static function setRoot(string $root): void
    {
        self::$root = rtrim($root, '/');
    }

    /**
     * The URL to use for $url. Off-site URLs and anything that is not a
     * WordPress asset path come back unchanged.
     */
    public static function resolve(string $url): string
    {
        $trimmed = trim($url);

        if ($trimmed === '') {
            return $url;
        }

        $path = self::legacyPath($trimmed);

        if ($path === null) {
            return $url;
        }

        $query = (string) parse_url($trimmed, PHP_URL_QUERY);
        $bare = (string) parse_url($path, PHP_URL_PATH);

        if (self::existsLocally($bare)) {
            return $bare;
        }

        return self::FALLBACK_ORIGIN . $bare . ($query !== '' ? '?' . $query : '');
    }

    /** Rewrite every candidate in a srcset list. */
    public static function resolveSrcset(string $srcset): string
    {
        $parts = array_map('trim', explode(',', $srcset));

        foreach ($parts as $i => $part) {
            if ($part === '') {
                continue;
            }

            $pieces = preg_split('~\s+~', $part, 2) ?: [$part];
            $pieces[0] = self::resolve($pieces[0]);
            $parts[$i] = implode(' ', $pieces);
        }

        return implode(', ', $parts);
    }

    /**
     * The site-relative path a URL names when it is one of this site's
     * WordPress asset URLs (absolute on a legacy host, protocol-relative,
     * or already site-relative), and null otherwise.
     */
    private static function legacyPath(string $url): ?string
    {
        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }

        if (preg_match('~^https?://~i', $url) === 1) {
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));

            if (!in_array($host, self::LEGACY_HOSTS, true)) {
                return null;
            }

            $path = (string) parse_url($url, PHP_URL_PATH);
        } elseif (str_starts_with($url, '/')) {
            $path = (string) parse_url($url, PHP_URL_PATH);
        } else {
            return null;
        }

        // The Chinese mirror's export prefixed asset paths with its own
        // "/zh" ("/zh/wp-content/uploads/..."); there is only one copy of
        // each file, at the site root.
        // Some archive pages wrote them relative to their own address
        // ("/zh/category/badminton-videos/wp-content/..."), so anything in
        // front of the asset directory is dropped.
        $path = (string) preg_replace('~^/(?:[^?#]*?/)?(?=wp-(content|includes)/)~', '/', $path);

        return preg_match('~^/(wp-content|wp-includes)/~', $path) === 1 ? $path : null;
    }

    private static function existsLocally(string $path): bool
    {
        $root = self::$root ?? dirname(__DIR__, 2);

        if (str_contains($path, '..')) {
            return false;
        }

        return self::$exists[$path] ??= is_file($root . rawurldecode($path));
    }
}
