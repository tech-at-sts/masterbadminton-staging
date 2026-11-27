/*
 * Engagement events for GA4 / Google Tag Manager.
 *
 * Every event is pushed to window.dataLayer, where a GTM container can turn
 * it into a GA4 event, a Google Ads conversion or a Meta Pixel event. When
 * GA4 is installed directly (gtag.js, no GTM) the same event is also sent
 * with gtag('event', ...). With neither installed the pushes are inert.
 *
 * Events:
 *   scroll_depth     {percent: 25|50|75|90}       once each per page
 *   guide_complete   {page_path}                  reader reached the end of an article
 *   click_outbound   {link_url, link_domain}      any link to another site
 *   click_affiliate  {link_url, link_domain}      outbound link marked rel="sponsored" or data-affiliate
 *   select_content   {content_type, link_url}     next-lesson / related-guide / contact-email clicks
 *   generate_lead    {form}                       newsletter signup or contact form submitted
 */
(function () {
	'use strict';

	var dataLayer = window.dataLayer = window.dataLayer || [];
	var usesGtm = function () {
		return dataLayer.some(function (entry) { return entry && entry.event === 'gtm.js'; });
	};

	function track(name, params) {
		params = params || {};
		var payload = { event: name };
		for (var key in params) {
			if (Object.prototype.hasOwnProperty.call(params, key)) { payload[key] = params[key]; }
		}
		dataLayer.push(payload);

		if (typeof window.gtag === 'function' && !usesGtm()) {
			window.gtag('event', name, params);
		}
	}

	window.mbTrack = track;

	// Scroll depth.
	var marks = [25, 50, 75, 90];
	var sent = {};
	var ticking = false;

	function onScroll() {
		ticking = false;
		var doc = document.documentElement;
		var scrollable = doc.scrollHeight - window.innerHeight;
		if (scrollable <= 0) { return; }
		var percent = (window.scrollY || doc.scrollTop) / scrollable * 100;

		marks.forEach(function (mark) {
			if (percent >= mark && !sent[mark]) {
				sent[mark] = true;
				track('scroll_depth', { percent: mark });
			}
		});
	}

	window.addEventListener('scroll', function () {
		if (!ticking) { ticking = true; window.requestAnimationFrame(onScroll); }
	}, { passive: true });

	// Guide completion: the end-of-article block scrolled into view.
	var end = document.querySelector('.post-after, .guide-faq, .meta-bottom-wrapper');
	if (end && 'IntersectionObserver' in window && document.body.classList.contains('post-v2')) {
		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					track('guide_complete', { page_path: window.location.pathname });
					observer.disconnect();
				}
			});
		});
		observer.observe(end);
	}

	// Clicks.
	document.addEventListener('click', function (event) {
		var link = event.target && event.target.closest ? event.target.closest('a[href]') : null;
		if (!link) { return; }

		var marked = link.getAttribute('data-track');
		if (marked === 'next-lesson' || marked === 'related-guide' || marked === 'contact-email') {
			track('select_content', { content_type: marked, link_url: link.href });
			return;
		}

		var url;
		try { url = new URL(link.href, window.location.href); } catch (e) { return; }
		if (url.protocol !== 'http:' && url.protocol !== 'https:') { return; }
		if (url.hostname === window.location.hostname) { return; }

		var rel = (link.getAttribute('rel') || '').toLowerCase();
		var affiliate = rel.indexOf('sponsored') !== -1 || link.hasAttribute('data-affiliate');

		track(affiliate ? 'click_affiliate' : 'click_outbound', { link_url: url.href, link_domain: url.hostname });
	}, true);

	// Lead forms.
	document.addEventListener('submit', function (event) {
		var form = event.target;
		if (!form || !form.getAttribute) { return; }
		var marked = form.getAttribute('data-track');
		if (marked === 'newsletter-signup' || marked === 'contact-form') {
			track('generate_lead', { form: marked });
		}
	}, true);
})();
