/*
 * Behaviour for the sticky quick-nav tab bar.
 *
 * Every hook is optional: the script no-ops on pages that don't carry the
 * markup, so it is safe to load site-wide from templates/footer.php.
 */
(function () {
	'use strict';

	var nav = document.querySelector('.quick-nav');

	if (!nav) {
		return;
	}

	arrows(nav);

	/* ---------------------------------------------------------------
	 * The hero strip is a scrolling row once its eight columns stop
	 * fitting. The two arrows say so - and step it along - but only
	 * while there is something to scroll to, which is measured here
	 * rather than assumed from a breakpoint.
	 * ------------------------------------------------------------ */
	function arrows(strip) {
		var rail = strip.querySelector('#thct');
		var prev = strip.querySelector('.hero-strip-prev');
		var next = strip.querySelector('.hero-strip-next');

		if (!rail || !prev || !next) {
			return;
		}

		// How far the row can actually be scrolled. Measured rather than
		// derived: a flex row of sub-pixel columns reports a scrollWidth
		// some 25px longer than the browser will ever scroll it to, so
		// "scrollLeft + clientWidth >= scrollWidth" never came true and the
		// forward arrow never reached its end state.
		var limit = 0;

		prev.addEventListener('click', function () { step(-1); });
		next.addEventListener('click', function () { step(1); });

		rail.addEventListener('scroll', function () {
			window.requestAnimationFrame(sync);
		}, { passive: true });

		window.addEventListener('resize', measure);

		// Web fonts land after first paint and change the columns' width,
		// so the row is measured again once they have.
		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(measure);
		}

		measure();

		function step(direction) {
			var by = Math.max(rail.clientWidth * 0.8, 120) * direction;

			if (rail.scrollBy) {
				rail.scrollBy({ left: by, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
			} else {
				rail.scrollLeft += by;
			}
		}

		/**
		 * Ask the browser where its own scroll limit is, by scrolling past
		 * it and reading back what it clamped to. Both the write and the
		 * restore happen in one frame, so nothing is painted in between -
		 * and smooth scrolling is switched off for it, or the probe would
		 * animate and cancel whatever the reader had going.
		 */
		function measure() {
			var from = rail.scrollLeft;
			var behavior = rail.style.scrollBehavior;

			rail.style.scrollBehavior = 'auto';
			rail.scrollLeft = rail.scrollWidth;
			limit = rail.scrollLeft;
			rail.scrollLeft = from;
			rail.style.scrollBehavior = behavior;

			sync();
		}

		function sync() {
			var atStart = rail.scrollLeft <= 2;
			var atEnd = limit <= 2 || rail.scrollLeft >= limit - 2;

			strip.classList.toggle('is-scrollable', limit > 2);
			strip.classList.toggle('at-start', atStart);
			strip.classList.toggle('at-end', atEnd);

			prev.disabled = atStart;
			next.disabled = atEnd;
		}
	}

	var items = Array.prototype.slice.call(nav.querySelectorAll('#thct > li'));

	if (items.length === 0) {
		return;
	}

	/** @type {Array<{item: HTMLElement, target: HTMLElement}>} */
	var links = [];

	items.forEach(function (item) {
		var link = item.querySelector('.quick-nav-link');
		var hash = link && link.getAttribute('href');

		if (!hash || hash.charAt(0) !== '#') {
			return;
		}

		var target = document.getElementById(hash.slice(1));

		if (!target) {
			return;
		}

		links.push({ item: item, target: target });

		link.addEventListener('click', function (event) {
			event.preventDefault();
			jumpTo(item, target);
		});
	});

	if (links.length === 0) {
		return;
	}

	function setActive(item) {
		links.forEach(function (entry) {
			entry.item.classList.toggle('is-active', entry.item === item);
		});
	}

	function jumpTo(item, target) {
		setActive(item);

		// The target is a <details> card: open it before measuring, so the
		// scroll lands on the expanded card rather than its collapsed height.
		if (target.tagName === 'DETAILS') {
			target.open = true;
		}

		var offset = nav.getBoundingClientRect().height + 16;
		var top = window.pageYOffset + target.getBoundingClientRect().top - offset;

		window.scrollTo({ top: Math.max(top, 0), behavior: prefersReducedMotion() ? 'auto' : 'smooth' });

		target.classList.add('is-targeted');
		window.setTimeout(function () {
			target.classList.remove('is-targeted');
		}, 1600);

		if (history.replaceState) {
			history.replaceState(null, '', '#' + target.id);
		}
	}

	function prefersReducedMotion() {
		return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	}

	// The cards sit in a two-column grid, so "the section you are scrolled
	// past" is ambiguous - Rules and Badminton Basics are side by side. The
	// active tab therefore tracks the jump you made (or the hash you arrived
	// on) rather than scroll position.
	function syncFromHash() {
		var id = window.location.hash.slice(1);

		if (!id) {
			return;
		}

		links.forEach(function (entry) {
			if (entry.target.id === id) {
				setActive(entry.item);

				if (entry.target.tagName === 'DETAILS') {
					entry.target.open = true;
				}
			}
		});
	}

	window.addEventListener('hashchange', syncFromHash);
	syncFromHash();
}());

/*
 * The category cards (.cat-accordion) are native <details>, which snap
 * open and shut. Clicking a card's summary instead slides its guide list
 * open or closed. The list's own height is what animates, not the card's,
 * so the tile above it is never clipped mid-slide. Opening a card from
 * script ("Expand all", search, a quick-nav jump) still snaps, as do
 * visitors who asked for reduced motion.
 */
(function () {
	'use strict';

	var cards = document.querySelectorAll('.cat-accordion');

	if (cards.length === 0 || !Element.prototype.animate) {
		return;
	}

	Array.prototype.forEach.call(cards, function (card) {
		var summary = card.querySelector('summary');
		var body = card.querySelector('.cat-accordion-body');
		var running = null;

		if (!summary || !body) {
			return;
		}

		summary.addEventListener('click', function (event) {
			if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
				return;
			}

			event.preventDefault();

			// Opening mid-close (or the reverse) turns around from wherever
			// the list currently is rather than jumping to an end.
			var closing = card.open && !(running && running.closing);
			var from = null;

			if (running) {
				var now = window.getComputedStyle(body);
				from = {
					height: body.getBoundingClientRect().height + 'px',
					paddingTop: now.paddingTop,
					paddingBottom: now.paddingBottom,
					opacity: now.opacity
				};

				running.animation.cancel();
			}

			card.open = true;

			var style = window.getComputedStyle(body);
			var full = {
				height: body.offsetHeight + 'px',
				paddingTop: style.paddingTop,
				paddingBottom: style.paddingBottom,
				opacity: 1
			};
			var shut = { height: '0px', paddingTop: '0px', paddingBottom: '0px', opacity: 0 };
			var start = from || (closing ? full : shut);

			body.style.overflow = 'hidden';
			body.style.boxSizing = 'border-box';

			var animation = body.animate([start, closing ? shut : full], {
				duration: 320,
				easing: 'cubic-bezier(.2, .7, .2, 1)'
			});

			running = { animation: animation, closing: closing };

			animation.onfinish = function () {
				running = null;
				body.style.overflow = '';
				body.style.boxSizing = '';

				if (closing) {
					card.open = false;
				}
			};
		});
	});
}());
