<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\ContentCache;
use App\Core\ContentExtractor;
use App\Core\PageContent;
use App\Core\Request;
use App\Core\Router;
use App\Layout\Layout;

$request = new Request($_SERVER);
$router = new Router(__DIR__);
$layout = new Layout(__DIR__ . '/templates/header.php', __DIR__ . '/templates/footer.php');

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
$extractor = new ContentExtractor();

$page = $cache->remember($sourceFile, static fn (): PageContent => $extractor->extract($sourceFile));

$layout->render($page, $request->path());
