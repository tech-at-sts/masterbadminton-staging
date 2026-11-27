# Deployment and SEO setup

## Environment variables (Railway → service → Variables)

All are optional. Anything left unset is simply left out of the page.

| Variable | Example | What it does |
| --- | --- | --- |
| `SITE_URL` | `https://www.masterbadminton.com` | Canonical address used for canonical tags, hreflang, Open Graph, structured data, sitemap and feed. Defaults to the www domain. |
| `SITE_INDEXABLE` | `true` / `false` | Overrides the indexing switch. When unset, pages are indexable **only** on the `SITE_URL` host and `noindex` everywhere else (Railway preview URLs, staging). |
| `REDIRECT_HOSTS` | `masterbadminton.com` | Extra hosts to 301 to `SITE_URL`. The bare domain is always redirected. |
| `GTM_ID` | `GTM-XXXXXXX` | Google Tag Manager container (recommended: configure GA4, Google Ads and Meta Pixel inside it). |
| `GA4_MEASUREMENT_ID` | `G-XXXXXXXXXX` | GA4 via gtag.js, used only when `GTM_ID` is not set. |
| `GOOGLE_SITE_VERIFICATION` | token | Search Console HTML-tag verification. |
| `BING_SITE_VERIFICATION` | token | Bing Webmaster Tools verification. |
| `ADSENSE_PUBLISHER_ID` | `pub-3695804435293422` | Reserved for ads.txt tooling; `ads.txt` itself is a static file in the repo root. |
| `CONTACT_EMAIL` | `hello@example.com` | Shown on `/contact.html`. |
| `CONTACT_FORM_ENDPOINT` | `https://formspree.io/f/xxxx` | Contact form action (Formspree, Basin, Getform...). The form only appears when this is set. |
| `NEWSLETTER_FORM_ENDPOINT` | Mailchimp/MailerLite/Kit embed action URL | Turns on the "7-day beginner plan" signup on the homepage and at the end of articles. |
| `NEWSLETTER_EMAIL_FIELD` | `EMAIL` | Name of the email field your provider expects (default `email`). |

Analytics tags only load on the indexable host, so staging traffic never reaches production reports.

## Analytics events

`assets/js/analytics-events.js` pushes these to `dataLayer` (and to `gtag` when GA4 is installed without GTM): `scroll_depth`, `guide_complete`, `click_outbound`, `click_affiliate` (links with `rel="sponsored"` or `data-affiliate`), `select_content` (next lesson / related guide), `generate_lead` (newsletter or contact form). In GTM, create a Custom Event trigger per event name and a GA4 Event tag for each; mark `generate_lead` as a key event.

## Before cancelling WP Engine

The theme CSS/JS and ~230 images are still loaded from `masterbadminto.wpenginepowered.com` because they are not in this repository yet. The site serves any of these files locally as soon as it exists in the repo, so:

```bash
php bin/mirror-wp-assets.php --dry-run   # list what is missing
php bin/mirror-wp-assets.php             # download it into wp-content/ and wp-includes/
```

Commit the downloaded files and deploy. When `--dry-run` reports nothing missing, WP Engine can be cancelled.

## Outside the code

- **Cloudflare**: once Railway verifies `masterbadminton.com`, add a redirect rule from the bare domain to `https://www.masterbadminton.com` (301, keep query string). The app also does this itself as a fallback.
- **Cloudflare → AI Crawl Control**: make sure GPTBot, OAI-SearchBot, PerplexityBot, ClaudeBot and Google-Extended are not blocked, and turn off the managed robots.txt if it adds Disallow rules - the site's own `/robots.txt` already sends `Content-Signal: search=yes, ai-input=yes, ai-train=no`.
- **Search Console**: verify the www property, submit `https://www.masterbadminton.com/sitemap.xml`, and request indexing for the homepage and top guides.
- **AdSense**: check `ads.txt` under Sites, and enable a certified consent message (Privacy & messaging → European regulations) for EEA/UK/Swiss visitors.
