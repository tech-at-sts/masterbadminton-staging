/*
 * Behaviour for the category directory (/categories): the search field
 * that narrows the grid, and the sidebar that tracks the section you are
 * reading.
 *
 * Both hooks are optional and the script no-ops on any page without the
 * markup, so it is safe to load site-wide from templates/footer.php.
 */
(function () {
	'use strict';

	var sections = Array.prototype.slice.call(document.querySelectorAll('[data-category-section]'));

	if (sections.length === 0) {
		return;
	}

	filter();
	spy();

	/* ---------------------------------------------------------------
	 * Search: hides cards that do not match, then any section left with
	 * no visible card, and mirrors both in the sidebar and the tally.
	 * ------------------------------------------------------------ */
	function filter() {
		var input = document.querySelector('[data-category-search]');

		if (!input) {
			return;
		}

		var empty = document.querySelector('[data-category-empty]');
		var tally = document.querySelector('[data-category-tally]');
		// "37 guides" / "1 guide" - the phrasing (and language) is the
		// server's, so the count is swapped inside it rather than rebuilt.
		var tallyTemplate = tally ? tally.textContent.replace(/\d+/, '{n}') : '';
		var tallySingular = tally ? tally.getAttribute('data-tally-one') : null;

		var groups = sections.map(function (section) {
			var cards = Array.prototype.slice.call(section.querySelectorAll('[data-category-card]'));

			return {
				section: section,
				name: (section.getAttribute('data-category-name') || '').toLowerCase(),
				cards: cards,
				titles: cards.map(function (card) {
					return (card.getAttribute('data-category-title') || '').toLowerCase();
				}),
				tab: document.querySelector('[data-sidebar-item="' + section.id + '"]')
			};
		});

		input.addEventListener('input', apply);

		// Escape clears the field, the way a search box is expected to behave.
		input.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && input.value !== '') {
				event.preventDefault();
				input.value = '';
				apply();
			}
		});

		apply();

		function apply() {
			var query = input.value.trim().toLowerCase();
			var visible = 0;

			groups.forEach(function (group) {
				// A category whose own name matches keeps all its guides,
				// so searching "smashing" shows the section, not nothing.
				var wholeSection = query !== '' && group.name.indexOf(query) !== -1;
				var shown = 0;

				group.cards.forEach(function (card, index) {
					var matches = query === '' || wholeSection || group.titles[index].indexOf(query) !== -1;

					card.hidden = !matches;

					if (matches) {
						shown += 1;
					}
				});

				group.section.hidden = shown === 0;

				if (group.tab) {
					group.tab.hidden = shown === 0;
				}

				visible += shown;
			});

			if (empty) {
				empty.hidden = visible !== 0;
			}

			if (tally && tallyTemplate) {
				tally.textContent = visible === 1 && tallySingular
					? tallySingular
					: tallyTemplate.replace('{n}', String(visible));
			}
		}
	}

	/* ---------------------------------------------------------------
	 * Sidebar: marks the section currently under the top of the viewport,
	 * and takes over its jump links so the landing position accounts for
	 * the rail that sits over the content on narrow screens.
	 * ------------------------------------------------------------ */
	function spy() {
		var items = Array.prototype.slice.call(document.querySelectorAll('[data-sidebar-item]'));

		if (items.length === 0) {
			return;
		}

		var byId = {};

		items.forEach(function (item) {
			byId[item.getAttribute('data-sidebar-item')] = item;

			var link = item.querySelector('a');

			if (link) {
				link.addEventListener('click', function (event) {
					var target = document.getElementById(item.getAttribute('data-sidebar-item'));

					if (!target) {
						return;
					}

					event.preventDefault();
					setActive(item);

					var top = window.pageYOffset + target.getBoundingClientRect().top - offset();

					window.scrollTo({
						top: Math.max(top, 0),
						behavior: reducedMotion() ? 'auto' : 'smooth'
					});

					if (history.replaceState) {
						history.replaceState(null, '', '#' + target.id);
					}
				});
			}
		});

		var ticking = false;

		window.addEventListener('scroll', function () {
			if (ticking) {
				return;
			}

			ticking = true;
			window.requestAnimationFrame(function () {
				ticking = false;
				sync();
			});
		}, { passive: true });

		window.addEventListener('resize', sync);
		sync();

		function sync() {
			var line = offset() + 24;
			var shown = sections.filter(function (section) { return !section.hidden; });
			var current = null;

			shown.forEach(function (section) {
				if (section.getBoundingClientRect().top <= line) {
					current = section;
				}
			});

			// Before the first heading passes the line, the first section
			// is the one being read. At the very bottom the last section is:
			// the page runs out of scroll before a short final section can
			// reach the line, which would otherwise leave it unreachable.
			if (atBottom()) {
				current = shown[shown.length - 1] || null;
			} else if (current === null) {
				current = shown[0] || null;
			}

			setActive(current === null ? null : byId[current.id] || null);
		}

		function atBottom() {
			var doc = document.documentElement;

			return window.pageYOffset + window.innerHeight >= doc.scrollHeight - 2;
		}

		function setActive(active) {
			items.forEach(function (item) {
				item.classList.toggle('is-active', item === active);
			});
		}

		// On narrow screens the sidebar becomes a sticky rail above the
		// grid, so a section has to clear its height to be readable.
		function offset() {
			var rail = document.querySelector('.catdir-sidebar-inner');

			if (!rail || window.getComputedStyle(rail).position !== 'sticky') {
				return 16;
			}

			var rect = rail.getBoundingClientRect();

			return rect.width >= document.documentElement.clientWidth - 80 ? rect.height + 20 : 16;
		}

		function reducedMotion() {
			return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		}
	}
}());
