/*
 * Filter behaviour for the category directory (/categories).
 *
 * The jump links and active tab in the quick-nav rail are already handled
 * by assets/js/home-ui.js, whose markup this page reuses; the only thing
 * left is the search field, which narrows the grid to the categories and
 * guides matching what you type.
 *
 * The script no-ops on any page without the markup, so it is safe to load
 * site-wide from templates/footer.php.
 */
(function () {
	'use strict';

	var grid = document.querySelector('[data-category-grid]');
	var input = document.querySelector('[data-category-search]');

	if (!grid || !input) {
		return;
	}

	var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-category-card]'));

	if (cards.length === 0) {
		return;
	}

	var empty = document.querySelector('[data-category-empty]');
	var tally = document.querySelector('[data-category-tally]');
	// "8 categories" / "1 category" - the phrasing (and language) is the
	// server's, so the count is swapped inside it rather than rebuilt here.
	var tallyTemplate = tally ? tally.textContent.replace(/\d+/, '{n}') : '';
	var tallySingular = tally ? tally.getAttribute('data-tally-one') : null;

	var haystacks = cards.map(function (card) {
		return (card.getAttribute('data-category-name') + ' ' + card.textContent).toLowerCase();
	});

	function apply() {
		var query = input.value.trim().toLowerCase();
		var visible = 0;

		cards.forEach(function (card, index) {
			var matches = query === '' || haystacks[index].indexOf(query) !== -1;

			card.hidden = !matches;

			if (matches) {
				visible += 1;
			}
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
}());
