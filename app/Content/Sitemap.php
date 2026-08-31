<?php

declare(strict_types=1);

namespace App\Content;

use App\Core\Locale;
use App\Core\SiteLinks;

/**
 * Builds /sitemap.xml from the exported tree itself.
 *
 * The tree is the site's only source of truth about which pages exist, so
 * the sitemap is generated from it rather than written by hand: a hand-kept
 * file drifts the moment an export is refreshed, and a sitemap that lists
 * pages which 404 is worse than no sitemap at all. Every candidate URL is
 * put through the same Router the front controller uses before it is
 * emitted, so every <loc> in the file is a URL this site actually serves.
 *
 * The English tree and the Chinese mirror are page-for-page, so each pair
 * is cross-declared with xhtml:link alternates. That is what tells a search
 * engine the two are translations of one page rather than duplicates
 * competing with each other - the other half of the language work, for
 * readers who arrive from a search result rather than from the switcher.
 */
final class Sitemap
{
    /** Directories that hold code, assets or theme files rather than pages. */
    private const SKIP_DIRS = [
        '.git', 'app', 'templates', 'storage', 'docker', 'assets', 'node_modules',
        'wp-content', 'wp-includes', 'wp-json', 'wp-admin', 'cgi-bin',
    ];

    /**
     * Files that are not pages a reader would land on. The exporter wrote a
     * feed.html/feed.xml beside almost every page, and saved a handful of
     * query-string URLs (index.html?p=332) as files of their own, which are
     * duplicates of the posts they pointed at.
     */
    private const SKIP_FILE_PATTERN = '~(^|/)(feed|comments-feed)\.html$|(^|/)index\.htmlp=|(^|/)xmlrpc|(^|/)wp-login~i';

    /** Sitemaps must stay under 50,000 URLs; this tree is far short of that, but the cap is enforced anyway. */
    private const MAX_URLS = 50000;

    public function __construct(
        private readonly string $baseDir,
        private readonly SiteLinks $links,
    ) {
    }

    /**
     * @param string $baseUrl scheme and host, without a trailing slash
     */
    public function build(string $baseUrl): string
    {
        $pages = $this->pages();

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n"
            . "        xmlns:xhtml=\"http://www.w3.org/1999/xhtml\">\n";

        foreach ($pages as $path => $lastModified) {
            $xml .= "  <url>\n    <loc>" . $this->escape($baseUrl . $path) . "</loc>\n";

            if ($lastModified !== null) {
                $xml .= '    <lastmod>' . gmdate('Y-m-d', $lastModified) . "</lastmod>\n";
            }

            foreach ($this->alternates($path, $pages) as $hreflang => $href) {
                $xml .= '    <xhtml:link rel="alternate" hreflang="' . $hreflang
                    . '" href="' . $this->escape($baseUrl . $href) . "\" />\n";
            }

            $xml .= "  </url>\n";
        }

        return $xml . "</urlset>\n";
    }

    /**
     * Every public page URL, mapped to the modification time of the file
     * behind it, in a stable order (shallowest first, then alphabetical) so
     * the file does not churn between builds.
     *
     * @return array<string, int|null>
     */
    public function pages(): array
    {
        $found = [];

        foreach ($this->htmlFiles() as $file) {
            $relative = substr($file, strlen($this->baseDir) + 1);
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);

            if (preg_match(self::SKIP_FILE_PATTERN, $relative) === 1) {
                continue;
            }

            $path = $this->publicPath($relative);

            // "foo/index.html" and a flat "foo.html" can both back the same
            // URL; whichever is seen first wins, and the Router agrees
            // because it tries the directory form first too.
            if (isset($found[$path]) || !$this->links->exists($path)) {
                continue;
            }

            $mtime = filemtime($file);
            $found[$path] = $mtime === false ? null : $mtime;
        }

        $found = $this->dropDuplicates($found);

        // The category directory has no exported file of its own - it is
        // built from the homepage - so it is added by hand.
        foreach (['/categories', '/zh/categories'] as $virtual) {
            if (!isset($found[$virtual]) && $this->links->exists($virtual)) {
                $found[$virtual] = null;
            }
        }

        uksort($found, static function (string $a, string $b): int {
            $depth = substr_count($a, '/') <=> substr_count($b, '/');

            return $depth !== 0 ? $depth : strcmp($a, $b);
        });

        return array_slice($found, 0, self::MAX_URLS, true);
    }

    /**
     * Drop the URLs that are a second address for a page already listed.
     *
     * The export left two of each behind:
     *
     *  - "page/1" is the archive's own first page under a second name, so
     *    it duplicates the archive root it paginates.
     *  - Wherever the exporter wrote both a directory and a flat file for
     *    one page (category/badminton-news/index.html beside
     *    category/badminton-news.html), the extensionless form is the one
     *    the site's own links and its pagination point at, so the ".html"
     *    twin goes. This only fires when both forms exist, which leaves
     *    ordinary pages whose slug simply ends in ".html" - most of the
     *    site - untouched.
     *
     * @param array<string, int|null> $found
     * @return array<string, int|null>
     */
    private function dropDuplicates(array $found): array
    {
        foreach (array_keys($found) as $path) {
            if (preg_match('~/page/1(\.html)?$~', $path) === 1) {
                unset($found[$path]);
            }
        }

        foreach (array_keys($found) as $path) {
            if (str_ends_with($path, '.html') && isset($found[substr($path, 0, -5)])) {
                unset($found[$path]);
            }
        }

        return $found;
    }

    /**
     * The hreflang alternates for one page: itself, its counterpart in the
     * other tree when that page exists, and x-default pointing at the
     * English one. A page with no counterpart declares nothing - a lone
     * self-referencing alternate says nothing a canonical does not.
     *
     * @param array<string, int|null> $pages
     * @return array<string, string>
     */
    private function alternates(string $path, array $pages): array
    {
        $english = Locale::to($path, Locale::ENGLISH);
        $chinese = Locale::to($path, Locale::CHINESE);

        if (!isset($pages[$english], $pages[$chinese])) {
            return [];
        }

        return [
            'en' => $english,
            'zh' => $chinese,
            'x-default' => $english,
        ];
    }

    /**
     * @return iterable<string> absolute paths of every .html file in the tree
     */
    private function htmlFiles(): iterable
    {
        $root = realpath($this->baseDir);

        if ($root === false) {
            return;
        }

        $directories = new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS);

        $filtered = new \RecursiveCallbackFilterIterator(
            $directories,
            static function (\SplFileInfo $item): bool {
                if (!$item->isDir()) {
                    return strtolower($item->getExtension()) === 'html';
                }

                // "feed" directories hold nothing but feed documents.
                return !in_array($item->getFilename(), self::SKIP_DIRS, true)
                    && $item->getFilename() !== 'feed';
            },
        );

        /** @var \SplFileInfo $file */
        foreach (new \RecursiveIteratorIterator($filtered) as $file) {
            if ($file->isFile()) {
                yield $file->getPathname();
            }
        }
    }

    /** The URL a file in the tree is served at - the Router's mapping, in reverse. */
    private function publicPath(string $relative): string
    {
        if ($relative === 'index.html') {
            return '/';
        }

        if (str_ends_with($relative, '/index.html')) {
            $relative = substr($relative, 0, -strlen('/index.html'));
        }

        return $relative === '' ? '/' : '/' . $relative;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
