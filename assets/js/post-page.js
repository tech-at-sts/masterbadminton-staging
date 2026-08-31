/*
 * Article page behaviour: keep the contents rail in step with the reader.
 *
 * Progressive enhancement only. Without this file the rail is still a list
 * of working anchor links - the script adds the "you are here" marker and
 * smooth scrolling, nothing else. It touches no article content.
 */
(function () {
	'use strict';

	var rail = document.querySelector('.post-toc');

	if (!rail) {
		return;
	}

	var items = Array.prototype.slice.call(rail.querySelectorAll('.post-toc-item'));

	var targets = items
		.map(function (item) {
			var link = item.querySelector('a');
			var id = link && link.getAttribute('href');
			var heading = id && id.charAt(0) === '#' ? document.getElementById(id.slice(1)) : null;

			return heading ? { item: item, heading: heading } : null;
		})
		.filter(Boolean);

	if (targets.length === 0) {
		return;
	}

	function mark(active) {
		targets.forEach(function (entry) {
			entry.item.classList.toggle('is-current', entry === active);
		});
	}

	// The heading nearest above the reading line wins, so the marker tracks
	// the section actually on screen rather than only the last one clicked.
	function sync() {
		var line = window.scrollY + Math.min(180, window.innerHeight * 0.28);
		var current = targets[0];

		targets.forEach(function (entry) {
			if (entry.heading.getBoundingClientRect().top + window.scrollY <= line) {
				current = entry;
			}
		});

		mark(current);
	}

	var scheduled = false;

	window.addEventListener('scroll', function () {
		if (scheduled) {
			return;
		}

		scheduled = true;
		window.requestAnimationFrame(function () {
			scheduled = false;
			sync();
		});
	}, { passive: true });

	window.addEventListener('resize', sync, { passive: true });

	targets.forEach(function (entry) {
		var link = entry.item.querySelector('a');

		link.addEventListener('click', function (event) {
			if (event.metaKey || event.ctrlKey || event.shiftKey) {
				return;
			}

			event.preventDefault();
			entry.heading.scrollIntoView({ behavior: 'smooth', block: 'start' });

			if (window.history && window.history.replaceState) {
				window.history.replaceState(null, '', link.getAttribute('href'));
			}

			mark(entry);
		});
	});

	sync();
})();
