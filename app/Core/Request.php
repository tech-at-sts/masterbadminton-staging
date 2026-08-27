<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private readonly string $path;

    public function __construct(array $server)
    {
        $uri = $server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = rawurldecode($path);

        $this->path = '/' . trim($path, '/');
    }

    /**
     * Normalized request path, e.g. "/", "/badminton-basics.html", "/category/badminton-news".
     */
    public function path(): string
    {
        return $this->path;
    }
}
