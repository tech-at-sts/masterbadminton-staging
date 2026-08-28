<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Resolves a public request path to a source HTML file inside the legacy
 * static content tree. The legacy tree (all the *.html directories, the
 * category/ and zh/ archives, and the flat root-level *.html pages) is left
 * exactly where it was exported to, so every relative asset link inside
 * those files keeps working: the router only decides which file supplies
 * the content for a given URL, it never moves or rewrites the files.
 */
final class Router
{
    /**
     * Live URLs that have no counterpart of their own in the exported tree,
     * mapped to the file that already holds the same content.
     *
     * These are only consulted after the normal candidates miss, so dropping
     * a real export in later silently takes precedence over the alias.
     *
     * techniques: the live site publishes a page at /techniques (WordPress
     * page id 1260) whose body is a WPBakery masonry post grid. The grid's
     * tiles are fetched at runtime from admin-ajax.php, so the static export
     * captured nothing but an empty grid container and the page never made
     * it into the tree. The category archive is that same listing - the same
     * ten Techniques posts, with the same excerpts and links - so it supplies
     * the content for this URL.
     */
    private const ALIASES = [
        'techniques' => 'category/badminton-techniques-shots-and-skills/index.html',
        'zh/techniques' => 'zh/category/badminton-techniques-shots-and-skills/index.html',
    ];

    public function __construct(private readonly string $baseDir)
    {
    }

    public function resolve(string $requestPath): ?string
    {
        $path = trim($requestPath, '/');

        $candidates = $path === ''
            ? ['index.html']
            : [$path . '/index.html', $path];

        if (isset(self::ALIASES[$path])) {
            $candidates[] = self::ALIASES[$path];
        }

        foreach ($candidates as $candidate) {
            $resolved = $this->safeFile($candidate);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    private function safeFile(string $relativePath): ?string
    {
        $base = realpath($this->baseDir);

        if ($base === false) {
            return null;
        }

        $full = realpath($base . '/' . $relativePath);

        if ($full === false || !is_file($full)) {
            return null;
        }

        // Guard against path traversal: the resolved file must stay inside baseDir.
        if (!str_starts_with($full, $base . DIRECTORY_SEPARATOR)) {
            return null;
        }

        if (strtolower(pathinfo($full, PATHINFO_EXTENSION)) !== 'html') {
            return null;
        }

        return $full;
    }
}
