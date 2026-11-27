<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Repoints the links inside legacy exported content at this site.
 *
 * Two things are wrong with those hrefs as exported, and both of them land
 * a Chinese reader back in the English tree:
 *
 * 1. Relative hrefs are written relative to the *file's* place in the
 *    export ("../badminton-smash.html/index.html" inside
 *    zh/badminton-smash-technique.html/index.html), but the browser
 *    resolves them against the *URL* the page is served at
 *    (/zh/badminton-smash-technique.html), which is one directory level
 *    shallower. Every such link therefore lost its "/zh" and dropped the
 *    reader into the English page of the same name. The homepage of the
 *    mirror had the same problem the other way round: its category links
 *    are relative ("badminton-rules.html/index.html") and resolved from
 *    /zh straight to the English /badminton-rules.html.
 *
 * 2. Absolute hrefs point at the live domain, so following one leaves this
 *    deployment entirely - and lands on the English page whenever the
 *    export happened to record the English URL.
 *
 * Both are fixed by resolving each href the way the *export* meant it -
 * against the source file's own directory - and re-emitting it as a
 * site-absolute URL, preferring the current page's language when that
 * translation exists. A rewrite only happens when the target actually
 * resolves to a page on this site; anything else (off-site links, feeds,
 * anchors, mailto:) is left exactly as the export wrote it.
 *
 * No link text is touched. This class only ever changes where an existing
 * anchor points, never what it says.
 */
final class LinkRewriter
{
    /**
     * Hosts whose absolute URLs name a page of this same site. The two
     * masterbadminton domains are the site itself, past and present;
     * how-to-play-badminton.com is the domain it was published under
     * before the rename and is still baked into older posts.
     */
    private const SITE_HOSTS = [
        'masterbadminton.com',
        'www.masterbadminton.com',
        'how-to-play-badminton.com',
        'www.how-to-play-badminton.com',
        'masterbadminto.wpengine.com',
        'masterbadminto.wpenginepowered.com',
    ];

    /** target() result for a link to a page of this site that does not exist. */
    private const DEAD = "\0dead";

    /** Old pages that were never exported, and the page that replaces them. */
    private const MOVED = [
        '/badminton-questions.html' => '/category/questions.html',
        '/category/questions' => '/category/questions.html',
    ];

    /** @var array<string, string>|null */
    private ?array $caseIndex = null;

    public function __construct(
        private readonly string $baseDir,
        private readonly SiteLinks $links,
    ) {
    }

    /**
     * @param string $sourceFile absolute path of the exported file the
     *                           content was pulled from - the base every
     *                           relative href in it was written against
     */
    public function rewrite(\DOMXPath $xpath, string $sourceFile): void
    {
        $baseTreeDir = $this->treeDirectoryOf($sourceFile);

        if ($baseTreeDir === null) {
            return;
        }

        $locale = Locale::of('/' . $baseTreeDir);
        $dead = [];

        foreach (iterator_to_array($xpath->query('//a[@href]')) as $anchor) {
            if (!$anchor instanceof \DOMElement) {
                continue;
            }

            $target = $this->target($anchor->getAttribute('href'), $baseTreeDir, $locale);

            if ($target === self::DEAD) {
                $dead[] = $anchor;
            } elseif ($target !== null) {
                $anchor->setAttribute('href', $target);
            }
        }

        // Links to pages of this site that no longer exist keep their text
        // and lose the link.
        foreach ($dead as $anchor) {
            $parent = $anchor->parentNode;

            if ($parent === null) {
                continue;
            }

            while ($anchor->firstChild !== null) {
                $parent->insertBefore($anchor->firstChild, $anchor);
            }

            $parent->removeChild($anchor);
        }
    }

    /** Directory of the source file relative to the document root, or null if it sits outside. */
    private function treeDirectoryOf(string $sourceFile): ?string
    {
        $base = realpath($this->baseDir);
        $file = realpath($sourceFile);

        if ($base === false || $file === false || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
            return null;
        }

        $relative = substr($file, strlen($base) + 1);

        return trim(str_replace(DIRECTORY_SEPARATOR, '/', dirname($relative)), '/.');
    }

    /** The site-absolute URL this href means, or null to leave it alone. */
    private function target(string $href, string $baseTreeDir, string $locale): ?string
    {
        $href = trim($href);

        if ($href === '' || str_starts_with($href, '#')) {
            return null;
        }

        // Anything with a scheme that is not plain web traffic - javascript:,
        // mailto:, tel: - is not a page of this site.
        if (preg_match('~^([a-z][a-z0-9+.\-]*):~i', $href, $scheme) === 1
            && !in_array(strtolower($scheme[1]), ['http', 'https'], true)) {
            return null;
        }

        $parts = parse_url($href);

        if ($parts === false) {
            return null;
        }

        $host = isset($parts['host']) ? strtolower($parts['host']) : null;

        if ($host !== null && !in_array($host, self::SITE_HOSTS, true)) {
            return null;
        }

        $path = $parts['path'] ?? '';

        if ($path === '') {
            return null;
        }

        $suffix = (isset($parts['query']) ? '?' . $parts['query'] : '')
            . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');

        $treePath = $host !== null || str_starts_with($path, '/')
            ? ltrim(rawurldecode($path), '/')
            : trim($baseTreeDir . '/' . rawurldecode($path), '/');

        $treePath = $this->flatten($treePath);

        if ($treePath === null) {
            return null;
        }

        foreach ($this->alternatives($this->publicPath($treePath)) as $publicPath) {
            foreach ($this->candidates($publicPath, $locale) as $candidate) {
                if ($this->links->exists($candidate)) {
                    return Canonical::path($candidate, $this->links) . $suffix;
                }
            }
        }

        // An absolute link to this site's own domain that resolves to
        // nothing points at a page the export never captured. It is
        // reported as dead so rewrite() can turn it into plain text instead
        // of sending readers (and crawlers) to a 404.
        // The same for a relative link to a page (".html") that does not
        // exist on this site.
        if ($host === null && str_ends_with(strtolower(rtrim($path, '/')), '.html')) {
            return self::DEAD;
        }

        if ($host !== null && !preg_match('~^/?(wp-content|wp-includes|wp-json|wp-admin|feed|comments|xmlrpc)~', ltrim($path, '/'))
            && !str_contains($path, '/feed')) {
            return self::DEAD;
        }

        return null;
    }

    /**
     * Other spellings of a path that the old site answered to: without the
     * ".html" WordPress sometimes added, with a different letter case, or
     * a page that has since moved.
     *
     * @return list<string>
     */
    private function alternatives(string $publicPath): array
    {
        $paths = [$publicPath];

        $neutral = Locale::neutral($publicPath);

        if (isset(self::MOVED[$neutral])) {
            $paths[] = self::MOVED[$neutral];
        }

        if ($publicPath !== '/' && !str_ends_with($publicPath, '.html')) {
            $paths[] = $publicPath . '.html';
        }

        // Single-segment paths whose case differs from the file on disk
        // ("yonex-all-england-final-2010.html" for
        // "Yonex-All-England-Final-2010.html").
        if (substr_count($publicPath, '/') === 1) {
            $actual = $this->caseIndex()[strtolower(ltrim($publicPath, '/'))] ?? null;

            if ($actual !== null) {
                $paths[] = '/' . $actual;
            }
        }

        return array_values(array_unique($paths));
    }

    /** @return array<string, string> lower-cased root entry => its real name */
    private function caseIndex(): array
    {
        if ($this->caseIndex !== null) {
            return $this->caseIndex;
        }

        $this->caseIndex = [];

        foreach (@scandir($this->baseDir) ?: [] as $entry) {
            if ($entry[0] !== '.') {
                $this->caseIndex[strtolower($entry)] ??= $entry;
            }
        }

        return $this->caseIndex;
    }

    /** Collapse "." and ".." segments; null if the path climbs out of the root. */
    private function flatten(string $treePath): ?string
    {
        $segments = [];

        foreach (explode('/', $treePath) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment !== '..') {
                $segments[] = $segment;
                continue;
            }

            if ($segments === []) {
                return null;
            }

            array_pop($segments);
        }

        return implode('/', $segments);
    }

    /**
     * URL a file in the export tree is served at. The exporter wrote every
     * page as <slug>/index.html, which the Router serves at "/<slug>", so
     * the trailing index.html is dropped rather than left in the address
     * bar.
     */
    private function publicPath(string $treePath): string
    {
        if ($treePath === 'index.html') {
            return '/';
        }

        if (str_ends_with($treePath, '/index.html')) {
            $treePath = substr($treePath, 0, -strlen('/index.html'));
        }

        return $treePath === '' ? '/' : '/' . $treePath;
    }

    /**
     * The URLs to try, best first: on a Chinese page the Chinese spelling
     * of the target comes first, and the English one is the fallback for
     * the pages the mirror never received.
     *
     * @return list<string>
     */
    private function candidates(string $publicPath, string $locale): array
    {
        $translated = Locale::to($publicPath, $locale);

        return $translated === $publicPath ? [$publicPath] : [$translated, $publicPath];
    }
}
