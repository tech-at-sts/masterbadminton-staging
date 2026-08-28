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
