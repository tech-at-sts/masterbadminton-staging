<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Whether this response should invite search engines in.
 *
 * The same code runs on the production site and on staging copies, and a
 * staging copy that gets indexed competes with the real site as duplicate
 * content. So the question is answered per request:
 *
 *  1. SITE_INDEXABLE, when it is set, wins outright - "true" opens every
 *     host, "false" closes every host (useful to keep a whole environment
 *     dark, or to force a check on a preview URL).
 *  2. Otherwise a request is indexable exactly when it arrived on the
 *     canonical host (SiteConfig::siteUrl(), www.masterbadminton.com by
 *     default). The production domain is open without anyone having to
 *     remember a variable - which is how the site went live as
 *     "noindex, nofollow" after the move to Railway - while the
 *     *.up.railway.app address of the very same service, and any other
 *     staging hostname, stay closed.
 */
final class SiteVisibility
{
    private const ENV_VAR = 'SITE_INDEXABLE';

    public static function indexable(?string $host = null): bool
    {
        $value = getenv(self::ENV_VAR);

        if ($value !== false && trim($value) !== '') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        $host ??= (string) ($_SERVER['HTTP_HOST'] ?? '');
        $host = strtolower((string) preg_replace('~:\d+$~', '', trim($host)));

        return $host !== '' && $host === SiteConfig::canonicalHost();
    }
}
