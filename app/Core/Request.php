<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private readonly string $path;

    /** @var array<string, mixed> */
    private readonly array $server;

    public function __construct(array $server)
    {
        $uri = $server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = rawurldecode($path);

        $this->path = '/' . trim($path, '/');
        $this->server = $server;
    }

    /**
     * Scheme and host this request arrived on, without a trailing slash -
     * the prefix absolute URLs in the sitemap are built from.
     *
     * X-Forwarded-Proto is read because the app runs behind a TLS-
     * terminating proxy on Railway: the connection Apache sees is plain
     * HTTP, so $_SERVER['HTTPS'] alone would advertise every URL as http://
     * on a site served over https.
     */
    public function baseUrl(): string
    {
        $forwarded = strtolower(trim(explode(',', (string) ($this->server['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));

        if ($forwarded === 'https' || $forwarded === 'http') {
            $scheme = $forwarded;
        } else {
            $https = (string) ($this->server['HTTPS'] ?? '');
            $scheme = $https !== '' && strtolower($https) !== 'off' ? 'https' : 'http';
        }

        $host = (string) ($this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? 'localhost');

        // Host comes from the client; keep it to what a hostname can be.
        if (preg_match('~^[A-Za-z0-9.\-]+(:\d{1,5})?$~', $host) !== 1) {
            $host = 'localhost';
        }

        return $scheme . '://' . $host;
    }

    /**
     * Normalized request path, e.g. "/", "/badminton-basics.html", "/category/badminton-news".
     */
    public function path(): string
    {
        return $this->path;
    }
}
