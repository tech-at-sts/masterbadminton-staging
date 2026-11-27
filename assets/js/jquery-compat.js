/*
 * Compatibility for the old WordPress theme's scripts, loaded (deferred)
 * right after jQuery.
 *
 * - The theme's main.js was written for jQuery 1.x and calls .live(), which
 *   jQuery removed in 1.9 (jQuery Migrate 3 does not restore it). The call
 *   threw "$(...).live is not a function" and stopped main.js part-way. The
 *   elements it targets (WooCommerce cart and wishlist controls) are not on
 *   this site, so binding directly to whatever matched is equivalent.
 * - jQuery Migrate's console warnings are silenced.
 */
(function ($) {
	if (!$) { return; }

	$.migrateMute = true;

	if (typeof $.fn.live !== 'function') {
		$.fn.live = function (types, data, fn) {
			return this.on(types, data, fn);
		};
	}

	if (typeof $.fn.die !== 'function') {
		$.fn.die = function (types, fn) {
			return this.off(types, fn);
		};
	}
})(window.jQuery);
