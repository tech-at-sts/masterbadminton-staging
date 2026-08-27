<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Caches the (title, description, html) extracted from a legacy source file
 * so repeated requests for the same page don't re-parse the DOM every time.
 * Invalidated automatically whenever the source file's mtime changes.
 */
final class ContentCache
{
    public function __construct(private readonly string $cacheDir)
    {
    }

    public function remember(string $sourceFile, callable $producer): PageContent
    {
        $cacheFile = $this->cacheFileFor($sourceFile);
        $sourceMTime = filemtime($sourceFile);

        if ($sourceMTime !== false && is_file($cacheFile) && filemtime($cacheFile) >= $sourceMTime) {
            $data = include $cacheFile;

            if (is_array($data)) {
                return PageContent::fromArray($data);
            }
        }

        /** @var PageContent $page */
        $page = $producer();

        $this->write($cacheFile, $page);

        return $page;
    }

    private function cacheFileFor(string $sourceFile): string
    {
        return $this->cacheDir . '/' . sha1($sourceFile) . '.php';
    }

    private function write(string $cacheFile, PageContent $page): void
    {
        if (!is_dir($this->cacheDir) && !@mkdir($this->cacheDir, 0775, true) && !is_dir($this->cacheDir)) {
            return;
        }

        if (!is_writable($this->cacheDir)) {
            return;
        }

        $export = var_export($page->toArray(), true);
        @file_put_contents($cacheFile, "<?php\nreturn {$export};\n", LOCK_EX);
    }
}
