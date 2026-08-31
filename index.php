<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Content\CategoryDirectory;
use App\Core\ContentCache;
use App\Core\ContentExtractor;
use App\Core\LinkRewriter;
use App\Core\PageContent;
use App\Core\Request;
use App\Core\Router;
use App\Core\SiteLinks;
use App\Layout\Layout;

$request = new Request($_SERVER);
$router = new Router(__DIR__);
$siteLinks = new SiteLinks(__DIR__);
$layout = new Layout(__DIR__ . '/templates/header.php', __DIR__ . '/templates/footer.php');

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
