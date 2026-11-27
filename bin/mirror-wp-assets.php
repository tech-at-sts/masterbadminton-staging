<?php

declare(strict_types=1);

/**
 * Copies every WordPress asset the site still loads from WP Engine into
 * this repository, so the WP Engine plan can be cancelled without the site
 * losing its styling or its images.
 *
 * Run it from a machine that can reach masterbadminto.wpenginepowered.com,
 * BEFORE WP Engine is cancelled:
 *
 *     php bin/mirror-wp-assets.php            # download what is missing
 *     php bin/mirror-wp-assets.php --dry-run  # only list it
 *
 * then commit the new files under wp-content/ and wp-includes/ and deploy.
 * Nothing else needs changing: App\Core\AssetUrl serves a file locally as
 * soon as it exists in the repository, and only falls back to WP Engine for
 * files that are still missing.
 *
 * What it collects:
 *  - every /wp-content/... and /wp-includes/... URL on a legacy host
 *    (WP Engine, masterbadminton.com, how-to-play-badminton.com) or
 *    site-relative, in the exported HTML and in templates/;
 *  - the files those stylesheets pull in themselves through url() and
 *    @import - fonts, background images - followed recursively.
 *
 * It never overwrites a file that is already present.
 */

const ORIGIN = 'https://masterbadminto.wpenginepowered.com';
const LEGACY_HOSTS = '(?:https?:)?//(?:www\.)?(?:masterbadminton\.com|masterbadminto\.wpenginepowered\.com|masterbadminto\.wpengine\.com|how-to-play-badminton\.com)';

$root = dirname(__DIR__);
$dryRun = in_array('--dry-run', $argv, true);

$skipDirs = ['.git', 'storage', 'node_modules', 'docker', 'bin', 'wp-json'];

/** @return iterable<string> */
$scan = static function (string $dir) use (&$scan, $skipDirs): iterable {
    foreach (new DirectoryIterator($dir) as $item) {
        if ($item->isDot()) {
            continue;
        }

        if ($item->isDir()) {
            if (!in_array($item->getFilename(), $skipDirs, true)) {
                yield from $scan($item->getPathname());
            }

            continue;
        }

        if (preg_match('~\.(html|php|css)$~i', $item->getFilename()) === 1) {
            yield $item->getPathname();
        }
    }
};

$paths = [];
$pattern = '~(?:' . LEGACY_HOSTS . ')(/wp-(?:content|includes)/[^\s"\'()<>\\\\,]+)~i';

foreach ($scan($root) as $file) {
    $text = (string) file_get_contents($file);

    if (preg_match_all($pattern, $text, $matches) > 0) {
        foreach ($matches[1] as $path) {
            $paths[normalise($path)] = true;
        }
    }
}

// Stylesheets reference fonts and images relative to themselves, so each
// one fetched is read for more work until nothing new turns up.
$queue = array_keys($paths);
$seen = $paths;
$fetched = 0;
$present = 0;
$failed = [];

while ($queue !== []) {
    $path = array_shift($queue);

    if ($path === '' || str_contains($path, '..')) {
        continue;
    }

    $target = $root . rawurldecode($path);

    if (is_file($target)) {
        $present++;
    } elseif ($dryRun) {
        echo "missing  {$path}\n";
    } else {
        $body = download(ORIGIN . $path);

        if ($body === null) {
            $failed[] = $path;
            continue;
        }

        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0775, true);
        }

        file_put_contents($target, $body);
        $fetched++;
        echo "fetched  {$path}\n";
    }

    if (str_ends_with(strtolower($path), '.css') && is_file($target)) {
        foreach (cssReferences((string) file_get_contents($target), $path) as $ref) {
            if (!isset($seen[$ref])) {
                $seen[$ref] = true;
                $queue[] = $ref;
            }
        }
    }
}

printf("\n%d already present, %d downloaded, %d failed.\n", $present, $fetched, count($failed));

foreach ($failed as $path) {
    echo "FAILED   {$path}\n";
}

exit($failed === [] ? 0 : 1);

function normalise(string $path): string
{
    $path = html_entity_decode($path, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $path = (string) parse_url($path, PHP_URL_PATH);

    return rtrim($path, '.;');
}

/** @return list<string> site-relative paths a stylesheet at $cssPath pulls in */
function cssReferences(string $css, string $cssPath): array
{
    $refs = [];
    preg_match_all('~url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)|@import\s+[\'"]([^\'"]+)[\'"]~i', $css, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $url = trim($match[1] !== '' ? $match[1] : ($match[2] ?? ''));

        if ($url === '' || str_starts_with($url, 'data:') || str_starts_with($url, '#')) {
            continue;
        }

        if (preg_match('~^' . LEGACY_HOSTS . '(/.*)$~i', $url, $m) === 1) {
            $url = $m[1];
        } elseif (preg_match('~^(?:https?:)?//~i', $url) === 1) {
            continue;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        if (!str_starts_with($path, '/')) {
            $path = dirname($cssPath) . '/' . $path;
        }

        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        $resolved = '/' . implode('/', $segments);

        if (preg_match('~^/wp-(content|includes)/~', $resolved) === 1) {
            $refs[] = $resolved;
        }
    }

    return $refs;
}

function download(string $url): ?string
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'ignore_errors' => true,
            'header' => "User-Agent: masterbadminton-asset-mirror/1.0\r\n",
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    $status = 0;

    foreach ($http_response_header ?? [] as $line) {
        if (preg_match('~^HTTP/\S+\s+(\d{3})~', $line, $m) === 1) {
            $status = (int) $m[1];
        }
    }

    return $body !== false && $status === 200 ? $body : null;
}
