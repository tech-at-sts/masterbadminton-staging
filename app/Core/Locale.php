<?php

declare(strict_types=1);

namespace App\Core;

/**
 * The site ships two page-for-page trees: the English one at the document
 * root and the Chinese mirror under /zh/. Which of the two a URL belongs to
 * is decided purely by that prefix, so the whole notion of "what language
 * is this page" is this class - pure path arithmetic, no filesystem, no
 * cookies, no session.
 *
 * It exists because the answer was previously nobody's: templates/header.php
 * built every navigation link as a literal English path, so a reader who
 * switched to 中文 on the homepage was thrown back into the English tree by
 * the very next click. Anything that builds a link now asks here which tree
 * it should be pointing at.
 */
final class Locale
{
    public const ENGLISH = 'en';
    public const CHINESE = 'zh';

    /** URL prefix of the Chinese mirror, without a trailing slash. */
    private const CHINESE_PREFIX = '/zh';

    /** The language a request path belongs to. */
    public static function of(string $path): string
    {
        $path = self::normalise($path);

        return $path === self::CHINESE_PREFIX || str_starts_with($path, self::CHINESE_PREFIX . '/')
            ? self::CHINESE
            : self::ENGLISH;
    }

    public static function isChinese(string $path): bool
    {
        return self::of($path) === self::CHINESE;
    }

    /**
     * The same path with any language prefix removed, i.e. the path as the
     * English tree spells it. "/zh/badminton-rules.html" =>
     * "/badminton-rules.html", "/zh" => "/".
     */
    public static function neutral(string $path): string
    {
        $path = self::normalise($path);

        if ($path === self::CHINESE_PREFIX) {
            return '/';
        }

        if (str_starts_with($path, self::CHINESE_PREFIX . '/')) {
            return substr($path, strlen(self::CHINESE_PREFIX));
        }

        return $path;
    }

    /**
     * The same path spelled in the given language. Idempotent: a path that
     * already belongs to that tree comes back unchanged.
     */
    public static function to(string $path, string $locale): string
    {
        $neutral = self::neutral($path);

        if ($locale !== self::CHINESE) {
            return $neutral;
        }

        return $neutral === '/' ? self::CHINESE_PREFIX : self::CHINESE_PREFIX . $neutral;
    }

    /** The other language - the one the switcher offers. */
    public static function other(string $locale): string
    {
        return $locale === self::CHINESE ? self::ENGLISH : self::CHINESE;
    }

    /** IETF tag for the <html lang> attribute. */
    public static function htmlLang(string $locale): string
    {
        return $locale === self::CHINESE ? 'zh-CN' : 'en-US';
    }

    private static function normalise(string $path): string
    {
        $path = (string) (parse_url($path, PHP_URL_PATH) ?? $path);

        return '/' . trim($path, '/');
    }
}
