<?php

declare(strict_types=1);

namespace App\Layout;

use App\Core\PageContent;

/**
 * The single place that controls the page header and footer. Every page on
 * the site — old blog posts, category archives, the Chinese mirror, the
 * homepage — is rendered by calling render() here, which always includes
 * the same two template files. Change templates/header.php or
 * templates/footer.php once and every page picks it up.
 */
final class Layout
{
    public function __construct(
        private readonly string $headerTemplate,
        private readonly string $footerTemplate,
    ) {
    }

    public function render(PageContent $page, string $requestPath, int $statusCode = 200): void
    {
        http_response_code($statusCode);

        // Exposed to the header/footer templates via include() scope. The
        // two templates are included from this one method, so anything the
        // header defines is still in scope for the footer - which is how
        // the footer reaches the header's language helpers.
        $title = $page->title;
        $description = $page->description;
        $currentPath = $requestPath;
        $pageLayout = $page->layout;
        $pageHero = $page->hero;

        include $this->headerTemplate;
        echo $page->html;
        include $this->footerTemplate;
    }
}
