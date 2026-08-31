<?php

declare(strict_types=1);

namespace App\Core;

use App\Content\CategoryDirectory;

/**
 * Answers two questions about a public URL path: does this site actually
 * serve it, and what is it called in a given language.
 *
 * "Serves it" is asked of the Router (plus the one virtual page that has no
 * exported file, the category directory) rather than re-implemented, so
 * aliases and the document-root-through-a-symlink case are handled in one
 * place. Answers are memoised for the life of a request because the header
 * asks about every navigation target on every page render.
 */
final class SiteLinks
{
    private readonly Router $router;

    /** @var array<string, bool> */
    private array $exists = [];

    public function __construct(string $baseDir)
    {
        $this->router = new Router($baseDir);
    }

    public function exists(string $publicPath): bool
    {
        return $this->exists[$publicPath] ??= $this->router->resolve($publicPath) !== null
            || CategoryDirectory::handles($publicPath);
    }

    /**
     * The given path spelled in $locale when that spelling is actually
     * served, and the path itself otherwise.
     *
     * The fallback is the point: the Chinese mirror is page-for-page with
     * the English tree but not every page made it across, and sending a
     * reader to a 404 in their own language is worse than handing them the
     * English page they can at least read.
     */
    public function localized(string $publicPath, string $locale): string
    {
        if (!str_starts_with($publicPath, '/')) {
            return $publicPath;
        }

        $translated = Locale::to($publicPath, $locale);

        return $translated !== $publicPath && $this->exists($translated) ? $translated : $publicPath;
    }
}
