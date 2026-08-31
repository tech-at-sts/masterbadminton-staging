<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Whether this deployment should invite search engines in.
 *
 * This repository is the staging site: same code, same content, as the
 * production one will eventually run, reachable at whatever public URL the
 * host gives it. Nothing about a sitemap or a page's own markup can tell a
 * crawler "this copy doesn't count yet" - that has to be said explicitly,
 * on every page and in robots.txt, or a search engine that stumbles onto
 * the staging URL indexes it as if it were the real site: duplicate
 * content fighting the production domain, and a page that outranks nothing
 * because two copies split whatever signal either would have earned alone.
 *
 * The default is closed on purpose: a new environment that forgets to set
 * SITE_INDEXABLE stays hidden rather than accidentally going public. Flip
 * it to true only on the environment that is meant to be found - normally
 * that means setting it once on the production deployment, never on
 * staging.
 */
final class SiteVisibility
{
    private const ENV_VAR = 'SITE_INDEXABLE';

    public static function indexable(): bool
    {
        $value = getenv(self::ENV_VAR);

        return $value !== false && filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
