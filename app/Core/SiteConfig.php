<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Deployment settings, read from the environment in one place.
 *
 * Everything that differs between the production site and a staging copy -
 * which hostname is the real one, which analytics and verification IDs to
 * print, where the contact form posts - is an environment variable read
 * here, so a Railway service is configured from its Variables tab and the
 * templates never hard-code an ID that belongs to one environment only.
 *
 * Every getter returns '' when its variable is unset, and every consumer
 * treats '' as "leave this feature out" - so a new environment with no
 * variables set renders a clean page with no half-configured tags in it.
 */
final class SiteConfig
{
    /** The one address the site is meant to be found at. */
    private const DEFAULT_SITE_URL = 'https://www.masterbadminton.com';

    /** The AdSense publisher already running on the guide pages. */
    private const DEFAULT_ADSENSE_PUB = 'pub-3695804435293422';

    public const SITE_NAME = 'Master Badminton';

    /** The byline the footer has always carried. */
    public const AUTHOR_NAME = 'David Tee';

    /**
     * Canonical scheme + host, no trailing slash. Canonical tags, hreflang,
     * Open Graph URLs, structured data, the sitemap and the feed are all
     * built on this rather than on whatever Host header a request arrived
     * with, so a page reached through the Railway hostname or the bare
     * domain still names the www address as the real one.
     */
    public static function siteUrl(): string
    {
        $url = rtrim(self::env('SITE_URL'), '/');

        return preg_match('~^https?://[A-Za-z0-9.\-]+(:\d+)?$~', $url) === 1 ? $url : self::DEFAULT_SITE_URL;
    }

    /** Hostname part of siteUrl(), lower-cased: "www.masterbadminton.com". */
    public static function canonicalHost(): string
    {
        return strtolower((string) parse_url(self::siteUrl(), PHP_URL_HOST));
    }

    /**
     * Hosts that should 301 to the canonical one. By default that is the
     * bare domain, so masterbadminton.com/foo lands on www even if the
     * Cloudflare redirect rule is missing or the request bypasses it.
     *
     * @return list<string>
     */
    public static function redirectHosts(): array
    {
        $canonical = self::canonicalHost();
        $hosts = [];

        if (str_starts_with($canonical, 'www.')) {
            $hosts[] = substr($canonical, 4);
        }

        foreach (explode(',', self::env('REDIRECT_HOSTS')) as $host) {
            $host = strtolower(trim($host));

            if ($host !== '' && $host !== $canonical) {
                $hosts[] = $host;
            }
        }

        return array_values(array_unique($hosts));
    }

    /** The site's Google Tag Manager container. */
    private const DEFAULT_GTM_ID = 'GTM-T3GSTNV';

    /**
     * Google Tag Manager container. Defaults to the site's own container;
     * GTM_ID overrides it, and GTM_ID=off removes the tag entirely.
     */
    public static function gtmId(): string
    {
        if (strtolower(self::env('GTM_ID')) === 'off') {
            return '';
        }

        return self::id('GTM_ID', '~^GTM-[A-Z0-9]+$~') ?: self::DEFAULT_GTM_ID;
    }

    /**
     * GA4 measurement ID, e.g. "G-XXXXXXXXXX". Only used when there is no
     * GTM container - with GTM, GA4 is configured inside the container.
     */
    public static function ga4Id(): string
    {
        return self::id('GA4_MEASUREMENT_ID', '~^G-[A-Z0-9]+$~');
    }

    /** Search Console HTML-tag verification token (the content="" value). */
    public static function googleSiteVerification(): string
    {
        return self::id('GOOGLE_SITE_VERIFICATION', '~^[A-Za-z0-9_\-]+$~');
    }

    /** Bing Webmaster Tools verification token. */
    public static function bingSiteVerification(): string
    {
        return self::id('BING_SITE_VERIFICATION', '~^[A-Za-z0-9_\-]+$~');
    }

    /** AdSense publisher ID as ads.txt spells it: "pub-1234...". */
    public static function adsensePublisher(): string
    {
        $value = self::env('ADSENSE_PUBLISHER_ID');
        $value = preg_replace('~^ca-~', '', $value) ?? '';

        return preg_match('~^pub-\d+$~', $value) === 1 ? $value : self::DEFAULT_ADSENSE_PUB;
    }

    /** Public contact address. Shown on /contact.html when set. */
    public static function contactEmail(): string
    {
        $value = self::env('CONTACT_EMAIL');

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? $value : '';
    }

    /**
     * Where the contact form posts - a form-backend URL (Formspree, Basin,
     * Getform, a Google Apps Script web app...). The form is only rendered
     * when this is set, because a form that goes nowhere is worse than none.
     */
    public static function contactFormEndpoint(): string
    {
        return self::httpsUrl('CONTACT_FORM_ENDPOINT');
    }

    /**
     * Where the newsletter / lead-magnet signup posts (Mailchimp, MailerLite,
     * ConvertKit, Brevo... embedded-form action URL). The signup block is
     * only rendered when this is set.
     */
    public static function newsletterEndpoint(): string
    {
        return self::httpsUrl('NEWSLETTER_FORM_ENDPOINT');
    }

    /**
     * Name of the email field the newsletter provider expects: "EMAIL" for
     * Mailchimp, "fields[email]" for MailerLite, "email_address" for Kit...
     */
    public static function newsletterEmailField(): string
    {
        $value = self::env('NEWSLETTER_EMAIL_FIELD');

        return preg_match('~^[A-Za-z0-9_\-\[\]]{1,64}$~', $value) === 1 ? $value : 'email';
    }

    private static function httpsUrl(string $name): string
    {
        $value = self::env($name);

        return str_starts_with($value, 'https://') && filter_var($value, FILTER_VALIDATE_URL) !== false ? $value : '';
    }

    private static function id(string $name, string $pattern): string
    {
        $value = self::env($name);

        return preg_match($pattern, $value) === 1 ? $value : '';
    }

    private static function env(string $name): string
    {
        $value = getenv($name);

        return $value === false ? '' : trim($value);
    }
}
