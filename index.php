<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Content\CategoryDirectory;
use App\Content\Sitemap;
use App\Core\ContentCache;
use App\Core\ContentExtractor;
use App\Core\LinkRewriter;
use App\Core\PageContent;
use App\Core\Request;
use App\Core\Router;
use App\Core\SiteLinks;
use App\Core\SiteVisibility;
use App\Layout\Layout;

$request = new Request($_SERVER);
$router = new Router(__DIR__);
$siteLinks = new SiteLinks(__DIR__);
$layout = new Layout(__DIR__ . '/templates/header.php', __DIR__ . '/templates/footer.php');

// robots.txt carries the same on/off switch as the <meta name="robots">
// tag every page sends (see templates/header.php): while this deployment
// isn't meant to be found yet, it disallows everything and says nothing
// about the sitemap, rather than pointing crawlers at 1,000+ URLs it is
// simultaneously asking them not to index.
if ($request->path() === '/robots.txt') {
    $body = SiteVisibility::indexable()
        ? "User-agent: *\nAllow: /\n\nSitemap: {$request->baseUrl()}/sitemap.xml\n"
        : "User-agent: *\nDisallow: /\n";

    header('Content-Type: text/plain; charset=UTF-8');
    echo $body;
    exit;
}

// /sitemap.xml is generated from the exported tree rather than kept as a
// file, so it cannot drift out of step with the pages that actually exist.
// Answered before anything else because it is not a page and takes none of
// the page pipeline.
if ($request->path() === '/sitemap.xml') {
    $baseUrl = $request->baseUrl();

    // Keyed on the host, not just on the path: <loc> values are absolute,
    // so a copy built for the staging hostname must never be handed to a
    // request that arrived on the production one.
    $cacheFile = __DIR__ . '/storage/cache/sitemap-' . substr(sha1($baseUrl), 0, 12) . '.xml';
    $maxAge = 3600;

    if (is_file($cacheFile) && time() - (int) filemtime($cacheFile) < $maxAge) {
        $xml = (string) file_get_contents($cacheFile);
    } else {
        $xml = (new Sitemap(__DIR__, $siteLinks))->build($baseUrl);

        if (is_dir(dirname($cacheFile)) && is_writable(dirname($cacheFile))) {
            @file_put_contents($cacheFile, $xml, LOCK_EX);
        }
    }

    header('Content-Type: application/xml; charset=UTF-8');
    header('Cache-Control: public, max-age=3600');
    echo $xml;
    exit;
}

// The category directory has no exported file of its own: it is built
// from the homepage's own category grid, so it is answered before the
// legacy tree is consulted. A real /categories export dropped in later
// would still lose to this - which is the intent, since the directory is
// the page the "Category" nav item points at.
if (CategoryDirectory::handles($request->path())) {
    $directory = new CategoryDirectory(__DIR__);
    $directorySource = $directory->sourceFile($request->path());

    if ($directorySource !== null) {
        $page = (new ContentCache(__DIR__ . '/storage/cache', CategoryDirectory::fingerprint()))
            ->remember($directorySource, static fn (): PageContent => $directory->build($request->path()));

        // Empty html means the homepage carries no category grid to build
        // from; fall through to the normal lookup (and its 404) instead.
        if ($page->html !== '') {
            $layout->render($page, $request->path());
            exit;
        }
    }
}

$sourceFile = $router->resolve($request->path());

if ($sourceFile === null) {
    $layout->render(
        new PageContent(
            'Page Not Found - Master Badminton',
            '',
            '<div class="breadcrumb-title-wrapper breadcrumb-v3"><div class="breadcrumb-content"><div class="breadcrumb-title">'
                . '<h1 class="heading-title page-title entry-title">Page Not Found</h1></div></div></div>'
                . '<div class="page-container"><div class="content-front" style="padding:3em 1em;text-align:center;">'
                . '<p>Sorry, the page you were looking for could not be found.</p>'
                . '<p><a href="/">Return to the homepage</a></p></div></div>',
        ),
        $request->path(),
        404,
    );
    exit;
}

$cache = new ContentCache(__DIR__ . '/storage/cache', ContentExtractor::fingerprint());
$extractor = new ContentExtractor(__DIR__, new LinkRewriter(__DIR__, $siteLinks));

$page = $cache->remember($sourceFile, static fn (): PageContent => $extractor->extract($sourceFile));

$layout->render($page, $request->path());
