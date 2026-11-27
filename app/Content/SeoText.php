<?php

declare(strict_types=1);

namespace App\Content;

use App\Core\Locale;
use App\Core\SiteConfig;

/**
 * Title and meta-description hygiene for pages without hand-written copy
 * (see SeoOverrides for the ones that have it).
 *
 * Titles: the export's titles range from 15 characters ("Badminton Serve")
 * to almost 80 ("Badminton Singles | Badminton Strategy | Win Rallies |
 * Basic Skills and Shots"). Short ones get the site name so they read as a
 * brand result; long ones lose the site name and trailing "|" segments so
 * the main keyword is not cut off in results.
 *
 * Descriptions: a page with none - or, on the Chinese mirror, one still in
 * English - gets one built from its own opening paragraph.
 */
final class SeoText
{
    private const MAX_TITLE = 62;
    private const SHORT_TITLE = 32;
    private const MAX_DESCRIPTION = 158;

    public static function stripSiteName(string $title): string
    {
        $title = self::squash($title);

        return trim((string) preg_replace('~\s*[-–|]\s*' . preg_quote(SiteConfig::SITE_NAME, '~') . '\s*$~iu', '', $title));
    }

    public static function title(string $title, string $headline = ''): string
    {
        $title = self::squash($title);

        if ($title === '' || $title === SiteConfig::SITE_NAME) {
            $title = $headline !== '' ? self::squash($headline) . ' - ' . SiteConfig::SITE_NAME : SiteConfig::SITE_NAME;
        }

        // Chinese titles are judged by their own conventions; leave them be.
        if (preg_match('~\p{Han}~u', $title) === 1) {
            return $title;
        }

        if (mb_strlen($title) > self::MAX_TITLE) {
            $title = self::stripSiteName($title);
        }

        if (mb_strlen($title) > self::MAX_TITLE && str_contains($title, ' | ')) {
            $kept = '';

            foreach (explode(' | ', $title) as $segment) {
                $candidate = $kept === '' ? $segment : $kept . ' | ' . $segment;

                if ($kept !== '' && mb_strlen($candidate) > self::MAX_TITLE) {
                    break;
                }

                $kept = $candidate;
            }

            $title = $kept;
        }

        if (mb_strlen($title) < self::SHORT_TITLE && stripos($title, SiteConfig::SITE_NAME) === false) {
            $title .= ' - ' . SiteConfig::SITE_NAME;
        }

        return $title;
    }

    /** Whether a description is worth keeping as it is. */
    public static function usableDescription(string $description, string $locale): bool
    {
        $description = self::clean($description);

        if (mb_strlen($description) < 50) {
            return $locale === Locale::CHINESE
                ? preg_match('~\p{Han}~u', $description) === 1 && mb_strlen($description) >= 20
                : false;
        }

        if ($locale === Locale::CHINESE && preg_match('~\p{Han}~u', $description) !== 1) {
            return false;
        }

        return true;
    }

    /** A description built from the page's own opening paragraphs. */
    public static function describe(\DOMXPath $xpath, \DOMElement $main, string $locale): string
    {
        $query = './/p[not(ancestor::nav) and not(ancestor::aside) and not(ancestor::form) and not(ancestor::*[contains(@class, "share")])'
            . ' and not(ancestor::*[contains(@class, "post-breadcrumb")]) and not(ancestor::*[contains(@class, "guide-answer")])]';
        $text = '';
        $target = $locale === Locale::CHINESE ? 60 : 120;

        foreach ($xpath->query($query, $main) as $paragraph) {
            $piece = '';

            foreach ($xpath->query('.//text()[not(ancestor::script or ancestor::style)]', $paragraph) as $node) {
                $piece .= $node->nodeValue;
            }

            $piece = self::squash($piece);

            if (mb_strlen($piece) < ($locale === Locale::CHINESE ? 12 : 40)) {
                continue;
            }

            if ($locale === Locale::CHINESE && preg_match('~\p{Han}~u', $piece) !== 1) {
                continue;
            }

            $text = $text === '' ? $piece : $text . ' ' . $piece;

            if (mb_strlen($text) >= $target) {
                break;
            }
        }

        return self::clean($text, $locale === Locale::CHINESE ? 90 : self::MAX_DESCRIPTION);
    }

    public static function clean(string $text, int $max = self::MAX_DESCRIPTION): string
    {
        $text = self::squash(strip_tags($text));

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $max - 1);
        $space = mb_strrpos($cut, ' ');

        if ($space !== false && $space > $max * 0.6) {
            $cut = mb_substr($cut, 0, $space);
        }

        return rtrim($cut, " \t\n\r\0\x0B,;:-–") . '…';
    }

    private static function squash(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('~[\s\x{00A0}]+~u', ' ', $text));
    }
}
