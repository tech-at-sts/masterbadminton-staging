<?php

declare(strict_types=1);

namespace App\Content;

use App\Core\SiteConfig;
use App\Core\SiteLinks;

/**
 * The site's RSS 2.0 feed at /feed/, rebuilt from the post list.
 *
 * WordPress served one at /feed for years, and feed readers and a few
 * aggregators still poll it; it 404ed after the move. This lists the most
 * recently updated posts that this site actually serves.
 */
final class Feed
{
    private const LIMIT = 30;

    public function __construct(
        private readonly PostIndex $posts,
        private readonly SiteLinks $links,
    ) {
    }

    public function build(): string
    {
        $base = SiteConfig::siteUrl();
        $posts = array_filter(
            $this->posts->posts(),
            fn (array $post): bool => $post['path'] !== '/' && $this->links->exists($post['path']),
        );

        usort($posts, static fn (array $a, array $b): int => strcmp($b['modified'] ?: $b['published'], $a['modified'] ?: $a['published']));
        $posts = array_slice($posts, 0, self::LIMIT);

        $latest = $posts[0]['modified'] ?? gmdate('c');

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n"
            . "<channel>\n"
            . '  <title>' . self::e(SiteConfig::SITE_NAME) . "</title>\n"
            . '  <link>' . self::e($base . '/') . "</link>\n"
            . '  <atom:link href="' . self::e($base . '/feed/') . "\" rel=\"self\" type=\"application/rss+xml\" />\n"
            . "  <description>Badminton rules, techniques, equipment advice and news from Master Badminton.</description>\n"
            . "  <language>en</language>\n"
            . '  <lastBuildDate>' . self::rfc822($latest) . "</lastBuildDate>\n";

        foreach ($posts as $post) {
            $url = $base . $post['path'];

            $xml .= "  <item>\n"
                . '    <title>' . self::e($post['title']) . "</title>\n"
                . '    <link>' . self::e($url) . "</link>\n"
                . '    <guid isPermaLink="true">' . self::e($url) . "</guid>\n"
                . '    <dc:creator>' . self::e(SiteConfig::AUTHOR_NAME) . "</dc:creator>\n"
                . '    <pubDate>' . self::rfc822($post['published'] ?: $post['modified']) . "</pubDate>\n";

            foreach ($post['categories'] as $id) {
                $category = $this->posts->category($id);

                if ($category !== null && !in_array($id, [1, 13, 55], true)) {
                    $xml .= '    <category>' . self::e($category['name']) . "</category>\n";
                }
            }

            if ($post['excerpt'] !== '') {
                $xml .= '    <description>' . self::e(self::trim($post['excerpt'])) . "</description>\n";
            }

            $xml .= "  </item>\n";
        }

        return $xml . "</channel>\n</rss>\n";
    }

    private static function trim(string $text): string
    {
        $text = (string) preg_replace('~\s*(\[(&hellip;|…|\.\.\.)\]|Continue reading.*)$~u', '…', $text);

        return mb_strlen($text) > 300 ? rtrim(mb_substr($text, 0, 297)) . '…' : $text;
    }

    private static function rfc822(string $iso): string
    {
        $time = strtotime($iso);

        return gmdate('D, d M Y H:i:s', $time === false ? time() : $time) . ' +0000';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
