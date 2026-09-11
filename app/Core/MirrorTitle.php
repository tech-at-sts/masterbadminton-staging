<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gives a page on the Chinese mirror a Chinese <title>.
 *
 * The mirror was produced by TranslatePress, which translated what it
 * could see in the document body but never touched the <title> element -
 * so 527 of the 574 mirrored pages were exported with a Chinese heading,
 * Chinese prose, and an English title. A reader on the mirror got a tab,
 * a bookmark and a browser-history entry in a language the page they were
 * looking at was not written in, and (once the site had one) a search
 * results page listing Chinese pages under English headlines.
 *
 * Nothing is translated here. The Chinese title of a page is the Chinese
 * heading the page already carries - the site's own translation, in the
 * site's own terminology - so this class only finds it and uses it. That
 * is the same rule the rest of the app follows: category names come from
 * the homepage's grid, a page's category comes from its own breadcrumb,
 * and copy is authored in one place only when the export genuinely has
 * none (see OVERRIDES, which covers the one page whose heading never made
 * it across).
 *
 * The English title is left exactly as it is: an English page is not the
 * mirror's problem, and a mirrored page that was already translated - 47
 * of them were - is not re-derived.
 */
final class MirrorTitle
{
    /** Appended so a tab and a bookmark say which site they came from. */
    private const SITE = 'Master Badminton';

    /**
     * Mirrored pages whose export carries no Chinese heading to take a
     * title from, and the title they are given instead.
     *
     * Keyed by the path as the English tree spells it, with any archive
     * pagination removed - "/foo.html/page/2.html" is the same page as
     * "/foo.html" as far as its name is concerned.
     *
     * There is one: the blog listing, whose heading the theme only ever
     * printed in <title> (see ContentExtractor, which borrows the title
     * into the hero for exactly this reason). The homepage looks like a
     * second case and is not - its "如何打羽毛球" is a heading in the body,
     * which the rule below finds on its own.
     */
    private const OVERRIDES = [
        '/how-to-play-badminton-blog.html' => '羽毛球博客',
    ];

    /**
     * A heading longer than this is not a title. The export has a handful
     * of question pages whose heading is a paragraph; those keep the title
     * the page was exported with rather than putting 300 characters in a
     * browser tab.
     */
    private const MAX_LENGTH = 150;

    /**
     * The title to serve a page with.
     *
     * @param string $title the page's own exported <title>
     * @param string $publicPath where the page is served, which is what
     *        says whether it belongs to the mirror
     * @param \DOMXPath $xpath the page's document, before any layout
     *        transform has had a chance to move its headings around
     */
    public static function resolve(string $title, string $publicPath, \DOMXPath $xpath): string
    {
        if (!Locale::isChinese($publicPath)) {
            return $title;
        }

        // Already translated - by TranslatePress for the few pages it did
        // reach, or by hand since.
        if (self::hasChinese($title)) {
            return $title;
        }

        $heading = self::OVERRIDES[self::nameKey($publicPath)] ?? self::heading($xpath);

        if ($heading === '' || self::length($heading) > self::MAX_LENGTH) {
            return $title;
        }

        return str_contains($heading, self::SITE) ? $heading : $heading . ' - ' . self::SITE;
    }

    /**
     * The page's own Chinese heading.
     *
     * h1 then h2, because the article pages name themselves in an h1 while
     * the homepage names itself in a centred h2. The theme's widget
     * sidebar is excluded: it holds the whole site menu, so its first
     * heading ("玩游戏") is the name of a menu section on every page in the
     * tree, and taking it would give 500 pages the same title.
     */
    private static function heading(\DOMXPath $xpath): string
    {
        $region = '//div[@id="main"]';
        $notSidebar = '[not(ancestor::aside[@id="left-sidebar"])]';

        foreach (['h1', 'h2'] as $level) {
            foreach ($xpath->query($region . '//' . $level . $notSidebar) as $node) {
                $text = trim((string) preg_replace('/\s+/u', ' ', $node->textContent));

                if ($text !== '' && self::hasChinese($text)) {
                    return $text;
                }
            }
        }

        return '';
    }

    /**
     * Whether a string carries any Chinese at all. The test is "has some"
     * rather than "is all": a translated title can still name a player or
     * a tournament in Latin script ("Lin Dan 的视频"), and it is translated
     * all the same.
     */
    private static function hasChinese(string $value): bool
    {
        return preg_match('/[\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F900}-\x{FAFF}]/u', $value) === 1;
    }

    /**
     * The path a page is named by, in the spelling OVERRIDES uses:
     * language prefix, pagination and index file removed.
     *
     * The index file has to come off because callers name a page two ways
     * - the front controller asks about the URL it is serving
     * ("/zh/foo.html"), the extractor about where the file it is reading
     * sits ("/zh/foo.html/index.html") - and both are the same page.
     */
    private static function nameKey(string $publicPath): string
    {
        $path = Locale::neutral($publicPath);

        foreach (['~/index\.html$~', '~/page/\d+(\.html)?$~', '~/index\.html$~'] as $pattern) {
            $path = (string) preg_replace($pattern, '', $path);
        }

        return $path === '' ? '/' : $path;
    }

    /** Characters, not bytes - and without requiring mbstring to count them. */
    private static function length(string $value): int
    {
        return (int) preg_match_all('/./us', $value);
    }
}
