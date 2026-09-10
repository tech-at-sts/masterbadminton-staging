<?php
/**
 * Single shared header for every page on the site.
 *
 * Rendered by App\Layout\Layout::render(). Variables in scope:
 * $title, $description, $currentPath, $pageLayout, $pageHero (all set by
 * Layout before this file is included). Anything defined here is still in
 * scope for templates/footer.php, which Layout includes from the same
 * method - the footer uses that to reach $localize and $lang below.
 */

declare(strict_types=1);

use App\Core\Locale;
use App\Core\SiteLinks;
use App\Core\SiteVisibility;

// Every label carries its Chinese counterpart, so the mirror's chrome
// speaks the mirror's language. A missing translation simply falls back to
// the English label rather than blanking the item.
$navItems = [
	['label' => 'Home', 'label_zh' => '首页', 'href' => '/'],
	['label' => 'Blog', 'label_zh' => '博客', 'href' => '/how-to-play-badminton-blog.html'],
	// The category directory, built by App\Content\CategoryDirectory from the
	// homepage's own "All Categories" grid. Top level rather than inside
	// "Playing the Game", so the full topic index is one click from every
	// page. (This slot used to hold a "Techniques" link to /techniques; that
	// URL still resolves through the Router alias, it just no longer takes a
	// top-level nav slot - Techniques is one of the eight cards here, and
	// "Techniques and Shots" remains under Playing the Game.)
	['label' => 'Category', 'label_zh' => '分类', 'href' => '/categories'],
	[
		'label' => 'Where to Play',
		'label_zh' => '哪里可以打球',
		'children' => [
			['label' => 'Australia', 'label_zh' => '澳大利亚', 'href' => '/where-to-play-in-australia.html'],
			['label' => 'New York', 'label_zh' => '纽约', 'href' => '/directory'],
		],
	],
	[
		'label' => 'Playing the Game',
		'label_zh' => '打球技巧',
		'children' => [
			['label' => 'Rules', 'label_zh' => '比赛规则', 'href' => '/badminton-rules.html'],
			['label' => 'Badminton Basics', 'label_zh' => '羽毛球基础', 'href' => '/category/badminton-videos/badminton-basics.html'],
			['label' => 'Badminton Strokes', 'label_zh' => '击球动作', 'href' => '/badminton-strokes.html'],
			['label' => 'Techniques and Shots', 'label_zh' => '技术与球路', 'href' => '/badminton-techniques.html'],
			['label' => 'Net Play', 'label_zh' => '网前球', 'href' => '/badminton-net-play.html'],
			['label' => 'Smashing', 'label_zh' => '扣杀', 'href' => '/badminton-smash-technique.html'],
			['label' => 'Advanced Skills', 'label_zh' => '进阶技巧', 'href' => '/advanced-badminton-techniques.html'],
		],
	],
	[
		'label' => 'Equipment',
		'label_zh' => '装备',
		'children' => [
			['label' => 'Rackets', 'label_zh' => '球拍', 'href' => '/badminton-racket.html'],
			['label' => 'Equipments', 'label_zh' => '器材', 'href' => '/badminton-equipment.html'],
		],
	],
	[
		'label' => 'Resources',
		'label_zh' => '资源',
		'children' => [
			['label' => 'Badminton Articles', 'label_zh' => '羽毛球文章', 'href' => '/badminton-articles.html'],
			['label' => 'Badminton Tips', 'label_zh' => '羽毛球窍门', 'href' => '/badminton-tips.html'],
			['label' => 'Professional Players', 'label_zh' => '职业球员访谈', 'href' => '/professional-badminton-interview.html'],
			['label' => 'Places to Play in UK', 'label_zh' => '英国球场', 'href' => '/uk-badminton-places-to-play.html'],
		],
	],
	[
		'label' => 'Just for Fun',
		'label_zh' => '趣味专区',
		'children' => [
			['label' => 'Top Players', 'label_zh' => '顶尖球员', 'href' => '/badminton-players.html'],
			['label' => 'Videos', 'label_zh' => '视频', 'href' => '/badminton-videos.html'],
			['label' => 'News', 'label_zh' => '新闻', 'href' => '/badminton-news.html'],
		],
	],
	['label' => 'Contact', 'label_zh' => '联系我们', 'href' => '/contact.html', 'group' => 'utility'],
	['label' => 'Ask a Question', 'label_zh' => '提问', 'href' => 'https://masterbadminton.com/badminton-questions.html', 'group' => 'utility'],
	['label' => 'Store', 'label_zh' => '商店', 'href' => 'https://badmintonclick.com.au/', 'target' => '_blank', 'group' => 'utility', 'variant' => 'store'],
];

// Same items, same order, same labels - only split into two visual groups
// so the content sections read apart from the utility links.
$navGroups = [
	'primary' => array_values(array_filter($navItems, static fn (array $item): bool => ($item['group'] ?? 'primary') === 'primary')),
	'utility' => array_values(array_filter($navItems, static fn (array $item): bool => ($item['group'] ?? 'primary') === 'utility')),
];

$normalizedPath = '/' . trim((string) parse_url($currentPath, PHP_URL_PATH), '/');

// Which of the two page-for-page trees this page belongs to - English at
// the root, the Chinese mirror under /zh/.
$lang = Locale::of($normalizedPath);
$isChinese = $lang === Locale::CHINESE;

// Whether a URL is actually served, and what it is called in a given
// language, are both asked of SiteLinks rather than re-implemented here.
// This file used to carry its own copy of the candidate lookup, and the two
// drifted: the Router also resolves aliases for live URLs with no exported
// file of their own (/techniques), which the private copy could not see, so
// the switcher fell back to the language's homepage for exactly those
// pages. The Router is also the side that handles a document root reached
// through a symlink, by realpath()ing the root as well as each candidate.
$siteLinks = new SiteLinks(realpath(dirname(__DIR__)) ?: dirname(__DIR__));

/**
 * Every internal link in the site chrome goes through here.
 *
 * Navigation used to be a list of literal English paths, so a reader who
 * switched to 中文 was thrown straight back into the English tree by the
 * next thing they clicked - and had to switch language again on every page.
 * Now each target is asked for in the language of the page it is being
 * rendered on, and only falls back to English when the mirror genuinely has
 * no counterpart for it.
 */
$localize = static function (string $href) use ($siteLinks, $lang, $isChinese): string {
	if (!str_starts_with($href, '/')) {
		return $href;
	}

	// A few pages sit at a different slug in the mirror than in the English
	// tree, so prefixing "/zh" cannot find them. Those are named rather than
	// guessed; everything else follows the page-for-page rule.
	$aliases = [
		'/category/badminton-videos/badminton-basics.html' => '/zh/badminton-basics.html',
	];

	if ($isChinese && isset($aliases[$href]) && $siteLinks->exists($aliases[$href])) {
		return $aliases[$href];
	}

	return $siteLinks->localized($href, $lang);
};

/** The label to show for a nav entry, in this page's language. */
$navLabel = static function (array $item) use ($isChinese): string {
	return $isChinese && ($item['label_zh'] ?? '') !== '' ? $item['label_zh'] : $item['label'];
};

// Chrome copy that is not a nav label. Same rule: the mirror gets Chinese,
// everything else gets English.
$chrome = $isChinese
	? [
		'beginner' => '新手入门？点这里',
		'search' => '搜索',
		'menu' => '打开导航菜单',
		'logo' => 'Master Badminton',
		'top' => '回到顶部',
		'switch' => 'English',
		'learn' => '立即学习',
		'store' => '商店',
		'strip_prev' => '查看上一组分类',
		'strip_next' => '查看更多分类',
	]
	: [
		'beginner' => 'Are you a beginner? Click Here',
		'search' => 'Search',
		'menu' => 'Toggle navigation menu',
		'logo' => 'Master Badminton',
		'top' => 'Back to Top',
		'switch' => '中文',
		'learn' => 'Learn now',
		'store' => 'Store',
		'strip_prev' => 'Show previous categories',
		'strip_next' => 'Show more categories',
	];

// The homepage - English or the Chinese mirror - gets the prominent
// beginner call-to-action band; every page that keeps the plain white
// header carries the same link in the slim top strip instead.
$isHome = in_array($normalizedPath, ['/', '/zh'], true);

// The category directory (/categories, /zh/categories) is built by
// App\Content\CategoryDirectory rather than pulled from the legacy tree,
// and is designed in the homepage's visual language - so it opts into the
// same body class and stylesheet, with its own hero band in place of the
// beginner call-to-action.
$isCategoryIndex = \App\Content\CategoryDirectory::handles($normalizedPath);

// Article pages and post listings, recognised by App\Content\PostLayout
// from the markup rather than from the URL, wear the same visual language
// with a stylesheet of their own on top.
$pageLayout = $pageLayout ?? '';
$isPost = $pageLayout === 'post';
$isArchive = $pageLayout === 'archive';

// The homepage layout, recognised by App\Content\HomepageLayout from the
// markup rather than from the URL - the export is reused by a couple of
// other pages and by the Chinese mirror, and they all wear the same hero.
$isHomeLayout = $pageLayout === 'home';

$usesHomeVisual = $isHome || $isHomeLayout || $isCategoryIndex || $isPost || $isArchive;

// One hero band serves all of them: the category directory authors its own
// copy, article and listing pages hand theirs up from the extractor.
$hero = $isCategoryIndex
	? \App\Content\CategoryDirectory::hero($normalizedPath)
	: ($pageHero ?? null);

$beginnerHref = $localize('/category/badminton-videos/badminton-basics.html');

// The search control used to be the 🔍 emoji, which every platform paints
// in its own colour and its own shape - a blue disc on some, a grey outline
// on others - so it never matched the type around it. This is drawn in
// currentColor instead, and so inherits whatever the header is wearing.
$searchIcon = '<svg class="icon-search" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
	. '<circle cx="11" cy="11" r="7" /><line x1="16.2" y1="16.2" x2="21" y2="21" />'
	. '</svg>';

/**
 * A site asset's URL, stamped with the file's own last-modified time.
 *
 * Without it a returning reader keeps whatever copy of the stylesheet
 * their browser already had, which after a redesign means new markup
 * dressed in old rules. The stamp changes only when the file does, so the
 * asset stays cacheable in between.
 */
$asset = static function (string $path): string {
	$file = dirname(__DIR__) . $path;
	$stamp = is_file($file) ? (string) filemtime($file) : '';

	return $stamp === '' ? $path : $path . '?v=' . $stamp;
};

$isLinkActive = static function (string $href) use ($normalizedPath): bool {
	if ($href === '/' || $href === '/zh') {
		return $normalizedPath === $href;
	}

	return str_starts_with($href, '/') && rtrim($href, '/') === rtrim($normalizedPath, '/');
};

// The language switcher points at the mirror of the page actually being
// viewed when it exists, falling back to that language's homepage
// otherwise, rather than always sending readers to /.
$otherLang = Locale::other($lang);
$counterpart = Locale::to($normalizedPath, $otherLang);
$langHref = $siteLinks->exists($counterpart) ? $counterpart : Locale::to('/', $otherLang);
$langLabel = $chrome['switch'];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(Locale::htmlLang($lang), ENT_QUOTES, 'UTF-8') ?>">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="shortcut icon" href="/wp-content/uploads/2016/09/favi.png" />
	<?php /*
		SiteVisibility::indexable() is off by default, which is what keeps
		this deployment out of search results until it is turned on
		explicitly (normally: only on production, never here). robots.txt
		carries the same on/off switch - see index.php - so the two never
		disagree about whether a crawler is welcome.
	*/ ?>
	<meta name="robots" content="<?= SiteVisibility::indexable() ? 'index, follow' : 'noindex, nofollow' ?>" />

	<title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
	<?php if ($description !== ''): ?>
	<meta name="description" content="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>" />
	<?php endif; ?>
	<link rel="canonical" href="<?= htmlspecialchars($currentPath, ENT_QUOTES, 'UTF-8') ?>" />

	<link rel='stylesheet' href='https://masterbadminto.wpenginepowered.com/wp-content/themes/gon/css/reset.css' media='all' />
	<link rel='stylesheet' href='https://masterbadminto.wpenginepowered.com/wp-content/themes/gon/style.css' media='all' />
	<link rel='stylesheet' href='https://masterbadminto.wpenginepowered.com/wp-content/themes/gon-child/style.css' media='all' />
	<link rel='stylesheet' href='https://masterbadminto.wpenginepowered.com/wp-content/themes/gon/css/font-awesome.css' media='all' />
	<link rel='stylesheet' href='https://masterbadminto.wpenginepowered.com/wp-content/themes/gon/css/responsive.css' media='all' />
	<link rel='stylesheet' href='https://masterbadminto.wpenginepowered.com/wp-content/uploads/gonchild.css' media='all' />
	<link rel="preconnect" href="https://fonts.googleapis.com" />
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
	<link href="https://fonts.googleapis.com/css2?family=Saira+Condensed:ital,wght@0,600;0,700;0,800;0,900;1,600;1,700;1,800;1,900&family=Hanken+Grotesk:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet" />
	<!-- BadmintonClick design tokens - colors, type and spacing shared by every stylesheet below. -->
	<link rel="stylesheet" href="<?= htmlspecialchars($asset('/assets/css/design-tokens.css'), ENT_QUOTES, 'UTF-8') ?>" media="all" />
	<style>
		.page-container{padding-top:2px;padding-left:0 !important;padding-right:0 !important;}
		li{color:#454545;}
		#main-content.ts-col-18{width:100% !important;}
		@media only screen and (max-width: 767px){
			.side-home-tw.wpb_column.vc_column_container.vc_col-sm-3{display:none;}
		}

		/* ----------------------------------------------------------------
		 * Site header / navigation - the same warm near-black band on every
		 * page (the big hero title below it is added per-page, see .page-hero
		 * and .hero-cta further down and in home-v2.css).
		 * ------------------------------------------------------------- */
		.site-header{font-family:var(--font-sans);background:var(--hero-2);}
		.site-header a{text-decoration:none;}
		.topbar{background:rgba(0,0,0,.28);border-bottom:1px solid rgba(255,255,255,.1);}
		.topbar-inner{max-width:1200px;margin:0 auto;padding:9px 20px;display:flex;align-items:center;gap:8px;}
		.topbar-inner img{width:18px;height:18px;opacity:.85;}
		.beginner-link{color:rgba(255,255,255,.72);font-size:13px;font-weight:700;letter-spacing:.2px;}
		.beginner-link:hover{color:#fff;}

		.header-middle{background:transparent;box-shadow:none;}
		.header-middle-inner{max-width:1200px;margin:0 auto;padding:20px 32px;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;}

		/* Text wordmark - replaces the old raster logo so it reads cleanly on
		   the dark header at every breakpoint. See design-tokens.css for the
		   shared .bc-wordmark rules (also used in the mobile menu and footer). */
		.site-logo{display:flex;}
		.site-logo:hover .bc-wordmark-master{color:#fff;}

		.header-actions{display:flex;align-items:center;gap:14px;flex-wrap:wrap;justify-content:flex-end;}
		.site-search{position:relative;width:220px;max-width:100%;display:flex;align-items:center;gap:9px;border-bottom:1px solid rgba(255,255,255,.3);padding:6px 2px;}
		.site-search input[type="text"]{background:transparent;border:0;padding:0;width:100%;font-size:13px;letter-spacing:.02em;color:#fff;font-family:var(--font-sans);outline:none;}
		.site-search input[type="text"]::placeholder{color:rgba(255,255,255,.65);}
		.site-search button{flex:0 0 auto;display:flex;align-items:center;justify-content:center;background:transparent;border:0;color:rgba(255,255,255,.75);cursor:pointer;padding:0;}
		.site-search button:hover{color:var(--accent-energy);}

		/* One magnifier, drawn in currentColor, so the control matches the
		   type beside it instead of whatever the platform paints an emoji. */
		.icon-search{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;display:block;}

		.main-nav{background:transparent;}
		/* Kept in the layout but invisible, rather than display:none, so the
		   toggles stay reachable from the keyboard. */
		.nav-toggle-checkbox,
		.search-toggle-checkbox{position:absolute;width:1px;height:1px;margin:-1px;padding:0;border:0;overflow:hidden;clip:rect(0 0 0 0);clip-path:inset(50%);}
		.icon-btn{display:none;align-items:center;justify-content:center;width:44px;height:44px;border-radius:50%;cursor:pointer;flex:0 0 auto;color:#fff;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);}
		.nav-toggle-btn{flex-direction:column;gap:4px;}
		.nav-toggle-btn span{display:block;width:18px;height:1.5px;background:currentColor;border-radius:1px;}
		.icon-btn:hover{background:rgba(255,255,255,.18);}
		.search-toggle-checkbox:focus-visible ~ .header-middle .search-toggle-btn,
		.nav-toggle-checkbox:focus-visible ~ .header-middle .nav-toggle-btn{outline:2px solid var(--accent-energy);outline-offset:2px;}
		.nav-list,
		.nav-list .nav-item,
		.nav-dropdown,
		.nav-dropdown li{list-style:none !important;list-style-type:none !important;margin:0;padding:0;}
		.nav-list{max-width:1200px;margin:0 auto;padding:6px 32px 16px;display:flex;align-items:center;justify-content:flex-start;gap:2px;flex-wrap:wrap;}
		/* Panel furniture: only ever shown inside the mobile takeover menu
		   (see the 1100px breakpoint below), never on the desktop rail. */
		.nav-panel-head,
		.nav-panel-search{display:none;}
		.nav-item{position:relative;}
		.nav-item > a,
		.nav-item > .nav-parent{color:rgba(255,255,255,.82);font-family:var(--font-sans);font-size:12px;font-weight:600;text-transform:none;letter-spacing:.02em;padding:8px 13px;white-space:nowrap;display:flex;align-items:center;gap:5px;border-bottom:0;border-radius:999px;cursor:pointer;transition:background var(--dur-fast) var(--ease-out),color var(--dur-fast) var(--ease-out);}
		.nav-item > a:hover,
		.nav-item > .nav-parent:hover,
		.nav-item:hover > .nav-parent,
		.nav-item:focus-within > .nav-parent{background:rgba(255,255,255,.14);color:#fff;}
		.nav-item.is-active > a{background:rgba(255,255,255,.14);color:#fff;}
		.has-children > .nav-parent::after{content:'▾';font-size:7px;opacity:.65;}

		.nav-dropdown{display:none;position:absolute;top:100%;left:0;z-index:30;background:#fff;border-radius:14px;box-shadow:0 22px 48px rgba(0,0,0,.3);padding:10px;margin-top:8px !important;min-width:232px;}
		/* The panel is offset below its parent, and that gap used to be
		   dead: moving the pointer down into the menu left the <li> for long
		   enough that :hover went false and the panel closed before it could
		   be clicked. This invisible strip bridges the gap. It is a child of
		   the panel, so the pointer never leaves the <li> that :hover is
		   tested on. It is a little taller than the gap so a fast pointer
		   cannot skip past it between frames. */
		.nav-dropdown::before{content:'';position:absolute;left:0;right:0;top:-8px;height:8px;}
		.nav-item:hover > .nav-dropdown,
		.nav-item:focus-within > .nav-dropdown{display:block;}
		.nav-dropdown li a{display:block;padding:9px 13px;border-radius:9px;color:var(--text-secondary);font-size:12.5px;font-family:var(--font-sans);font-weight:600;white-space:nowrap;}
		.nav-dropdown li a:hover{background:var(--surface-sunken);color:var(--bc-black);}

		<?php /*
			The nav rail collapses well before phone width: eleven items and
			five dropdowns need about 1100px to sit on one line, and wrapping
			them onto three lines instead is worse than the takeover panel.
		*/ ?>
		@media (max-width: 1100px){
			.header-middle-inner{padding:18px 20px;gap:12px;flex-wrap:nowrap;}
			/* The bar is one row: logo, then the two icon buttons. The search
			   field is not in that row at all until it is asked for, and then
			   it opens as a row of its own underneath. */
			.header-actions{flex:0 0 auto;gap:6px;}
			.site-search{display:none;}
			.icon-btn{display:flex;}
			/* An explicit ground, not inherit: the row is positioned outside
			   the flow, so it would otherwise be transparent over whatever
			   sits beneath the bar. */
			.search-toggle-checkbox:checked ~ .header-middle .site-search{display:flex;position:absolute;left:20px;right:20px;top:100%;width:auto;padding:12px 16px;background:var(--hero-overlay);border:1px solid rgba(255,255,255,.2);border-radius:14px;box-shadow:0 14px 30px rgba(0,0,0,.35);z-index:30;margin-top:8px;}
			.header-middle{position:relative;}
			/* The nav rail collapses; opening it fills the screen edge-to-edge
			   as a dark takeover panel, echoing the reference's full-screen
			   mobile menu, without needing any script beyond the existing
			   checkbox toggle. */
			.main-nav{position:relative;}
			/* .nav-groups (not .nav-list) is the fullscreen panel: it already
			   wraps both the primary and utility <ul>s, so the two stack as
			   one column instead of each becoming its own overlay. */
			.main-nav .nav-groups{
				position:fixed;inset:0;z-index:150;
				/* nowrap matters: this is a column as tall as the viewport, and
				   the menu is taller than that - left wrapping, the overflow
				   breaks into a second and third column beside the first
				   instead of scrolling. */
				flex-direction:column;flex-wrap:nowrap;align-items:stretch;justify-content:flex-start;
				max-width:none;margin:0;padding:22px 20px 48px;gap:0;
				background:var(--hero-overlay);
				overflow-y:auto;-webkit-overflow-scrolling:touch;
				visibility:hidden;opacity:0;transition:opacity var(--dur-base) var(--ease-out);
			}
			.nav-toggle-checkbox:checked ~ .main-nav .nav-groups{visibility:visible;opacity:1;}

			/* The panel's own wordmark, close button and search field. They
			   are in the markup for every width and only shown in here. */
			.main-nav .nav-panel-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex:0 0 auto;}
			.main-nav .nav-panel-close{
				display:flex;align-items:center;justify-content:center;
				width:46px;height:46px;flex-shrink:0;
				background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.22);border-radius:12px;
				color:#fff;font-family:var(--font-mono);font-size:17px;line-height:1;cursor:pointer;
			}
			.main-nav .nav-panel-close:hover{background:rgba(255,255,255,.18);}
			.main-nav .nav-panel-search{
				display:flex;align-items:center;gap:10px;flex:0 0 auto;
				margin:26px 0;padding:13px 18px;
				border:1px solid rgba(255,255,255,.24);border-radius:999px;
				color:rgba(255,255,255,.7);
			}
			.main-nav .nav-panel-search input[type="text"]{border:0;outline:0;background:transparent;width:100%;color:#fff;font-family:var(--font-sans);font-size:14px;}
			.main-nav .nav-panel-search input[type="text"]::placeholder{color:rgba(255,255,255,.62);}
			.main-nav .nav-panel-search button{flex:0 0 auto;display:flex;align-items:center;justify-content:center;background:transparent;border:0;padding:0;color:inherit;cursor:pointer;}

			.main-nav .nav-list{max-width:none;margin:0;padding:0;flex-direction:column;align-items:stretch;gap:0;}

			/* The three standalone links at the top are the display type; the
			   group headings above the chip sets are small mono labels; the
			   utility links at the foot are plain. */
			.main-nav .nav-list-primary .nav-item:not(.has-children) > a{
				padding:15px 2px;border-bottom:1px solid rgba(255,255,255,.1);border-radius:0;
				font-family:var(--font-display);font-weight:900;font-style:italic;text-transform:uppercase;
				font-size:24px;line-height:1;letter-spacing:-.01em;
			}
			.main-nav .nav-item.has-children > .nav-parent{
				padding:24px 2px 10px;border-bottom:0;border-radius:0;
				font-family:var(--font-mono);font-size:10px;font-weight:700;
				letter-spacing:.2em;text-transform:uppercase;color:var(--accent-energy);
			}
			.main-nav .has-children > .nav-parent::after{display:none;}
			.main-nav .nav-list-utility .nav-item > a{
				padding:14px 2px;border-bottom:0;border-radius:0;min-height:44px;
				font-family:var(--font-sans);font-size:14px;font-weight:600;
				text-transform:none;letter-spacing:0;color:rgba(255,255,255,.86);
			}
			.main-nav .nav-list-primary .nav-item:not(.has-children) > a:hover,
			.main-nav .nav-list-utility .nav-item > a:hover,
			.main-nav .nav-list-primary .nav-item.is-active > a{background:transparent;color:var(--accent-energy);}
			.main-nav .nav-item.has-children > .nav-parent:hover,
			.main-nav .nav-item:hover > .nav-parent,
			.main-nav .nav-item:focus-within > .nav-parent{background:transparent;color:var(--accent-energy);}

			/* Every group is open in here - the panel is a map of the site,
			   not a set of things to open one at a time. */
			.main-nav .nav-dropdown,
			.main-nav .nav-item:hover > .nav-dropdown,
			.main-nav .nav-item:focus-within > .nav-dropdown{display:block;position:static;background:transparent;box-shadow:none;border-radius:0;margin:0 !important;padding:0;}
			/* No hover on the stacked mobile menu, so no gap to bridge. */
			.main-nav .nav-dropdown::before{display:none;}
			.main-nav .nav-dropdown li{display:inline-block;}
			.main-nav .nav-dropdown li a{display:inline-flex;align-items:center;min-height:44px;color:rgba(255,255,255,.88);padding:10px 15px;border-radius:999px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);font-family:var(--font-sans);font-size:13px;font-weight:600;margin:0 8px 8px 0;}
			.main-nav .nav-dropdown li a:hover{background:#fff;color:var(--bc-black);}

			/* The store closes the panel as a full-width button. */
			.main-nav .nav-item-store{margin-top:8px;}
			.main-nav .nav-item-store > a{
				display:flex;align-items:center;justify-content:center;
				background:#fff;color:var(--bc-black) !important;
				padding:16px 22px !important;border-radius:999px;text-align:center;
				font-size:12.5px !important;font-weight:700 !important;letter-spacing:.12em !important;text-transform:uppercase;
			}
			.main-nav .nav-item-store > a:hover{background:var(--accent-energy);color:var(--bc-black) !important;}
		}

		.lang-switch{position:fixed;bottom:16px;right:16px;background:var(--bc-black);color:#fff;font-size:12px;font-family:var(--font-sans);font-weight:600;padding:9px 16px;border-radius:999px;display:flex;align-items:center;gap:6px;box-shadow:0 6px 18px rgba(0,0,0,.35);z-index:40;text-decoration:none;}
		.lang-switch:hover{background:var(--neutral-800);color:#fff;}

		/* "All Categories" section (base styling; body.home-v2 in home-ui.css
		   and home-v2.css layer the full BadmintonClick treatment on top). */
		.cat-section{background:var(--surface-sunken);border-radius:var(--radius-xl);padding:44px 40px;margin:36px 0;box-sizing:border-box;}
		.cat-section-title{text-align:center;font-family:var(--font-display);font-style:italic;font-weight:800;text-transform:uppercase;font-size:34px;color:var(--bc-black);margin:0 0 6px;}
		.cat-section-subtitle{text-align:center;font-size:14px;color:var(--text-muted);margin:0 0 26px;font-family:var(--font-sans);}
		.cat-section-controls{display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;}
		.cat-search-wrap{position:relative;flex:1;min-width:200px;}
		.cat-search-icon{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:14px;}
		.cat-search-input{width:100%;box-sizing:border-box;background:#fff;border:1px solid var(--border);border-radius:var(--radius-pill);padding:13px 16px 13px 40px;font-size:14px;color:var(--text-primary);font-family:var(--font-sans);outline:none;}
		.cat-search-input:focus{border-color:var(--accent-sale);}
		.cat-expand-all{background:var(--bc-black);border:1px solid var(--bc-black);border-radius:var(--radius-pill);padding:13px 22px;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#fff;font-family:var(--font-sans);cursor:pointer;white-space:nowrap;}
		.cat-expand-all:hover{filter:brightness(1.4);}
		.cat-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
		.cat-accordion{background:#fff;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden;align-self:start;}
		.cat-accordion summary{list-style:none;cursor:pointer;padding:18px 22px;display:flex;align-items:center;justify-content:space-between;gap:12px;font-family:var(--font-display);font-style:italic;font-weight:800;text-transform:uppercase;font-size:17px;color:var(--bc-black);}
		.cat-accordion summary::-webkit-details-marker{display:none;}
		.cat-accordion-meta{display:flex;align-items:center;gap:12px;flex-shrink:0;}
		.cat-accordion-count{background:var(--surface-sunken);color:var(--text-secondary);font-size:11px;font-weight:700;border-radius:999px;padding:3px 11px;font-family:var(--font-mono);}
		.cat-accordion-chevron{color:var(--text-muted);font-size:15px;transition:transform .15s;display:inline-block;}
		.cat-accordion[open] .cat-accordion-chevron{transform:rotate(90deg);}
		.cat-accordion-body{padding:0 22px 20px;border-top:1px solid var(--border);padding-top:14px;}
		.cat-accordion-body ul{list-style:none !important;margin:0;padding:0;display:flex;flex-direction:column;gap:10px;}
		.cat-accordion-body li{padding:0;}
		.cat-accordion-body a{color:var(--text-secondary);text-decoration:none;font-size:13px;font-weight:600;font-family:var(--font-sans);}
		.cat-accordion-body a:hover{color:var(--bc-black);text-decoration:underline;}

		@media (max-width: 640px){
			.cat-section{padding:28px 20px;}
			.cat-section-title{font-size:26px;}
			.cat-grid{grid-template-columns:1fr;}
		}
	</style>

	<!-- Homepage UI layer. Loaded last so it wins ties against the rules above. -->
	<link rel="stylesheet" href="<?= htmlspecialchars($asset('/assets/css/home-ui.css'), ENT_QUOTES, 'UTF-8') ?>" media="all" />
	<?php if ($usesHomeVisual): ?>
	<!-- Homepage visual language. Scoped to body.home-v2; see the file header. -->
	<link rel="stylesheet" href="<?= htmlspecialchars($asset('/assets/css/home-v2.css'), ENT_QUOTES, 'UTF-8') ?>" media="all" />
	<?php endif; ?>
	<?php if ($isCategoryIndex): ?>
	<!-- Category directory layer. Scoped to body.cat-directory-page; builds on home-v2. -->
	<link rel="stylesheet" href="<?= htmlspecialchars($asset('/assets/css/category-page.css'), ENT_QUOTES, 'UTF-8') ?>" media="all" />
	<?php endif; ?>
	<?php if ($isPost || $isArchive): ?>
	<!-- Article and listing layer. Scoped to body.post-v2 / body.archive-v2; builds on home-v2. -->
	<link rel="stylesheet" href="<?= htmlspecialchars($asset('/assets/css/post-page.css'), ENT_QUOTES, 'UTF-8') ?>" media="all" />
	<?php endif; ?>

	<script src="https://masterbadminto.wpenginepowered.com/wp-includes/js/jquery/jquery.min.js"></script>
</head>
<body class="wp-theme-gon wp-child-theme-gon-child header-v2 wide layout-fullwidth ts_desktop<?= $usesHomeVisual ? ' home-v2' : '' ?><?= $isHome || $isHomeLayout ? ' home-page' : '' ?><?= $isCategoryIndex ? ' cat-directory-page' : '' ?><?= $isPost ? ' post-v2' : '' ?><?= $isArchive ? ' archive-v2' : '' ?>">
<div id="page" class="hfeed site">

	<header class="site-header">
		<?php /*
			Both toggles are checkboxes at the top of the header rather than
			next to the controls they open: everything they show - the nav
			panel inside .main-nav, the search field inside .header-middle -
			is a following sibling from here, which is what lets a label
			placed anywhere in the bar drive them without script.
		*/ ?>
		<input type="checkbox" id="nav-toggle" class="nav-toggle-checkbox" />
		<input type="checkbox" id="search-toggle" class="search-toggle-checkbox" />

		<?php if (!$usesHomeVisual): ?>
		<div class="topbar">
			<div class="topbar-inner">
				<img src="/wp-content/uploads/2016/09/icon-badminton-1.png" alt="" />
				<a class="beginner-link" href="<?= htmlspecialchars($beginnerHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($chrome['beginner'], ENT_QUOTES, 'UTF-8') ?></a>
			</div>
		</div>
		<?php endif; ?>

		<div class="header-middle">
			<div class="header-middle-inner">
				<a class="site-logo bc-wordmark" href="<?= htmlspecialchars($localize('/'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($chrome['logo'], ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($chrome['logo'], ENT_QUOTES, 'UTF-8') ?>">
					<span class="bc-wordmark-master">MASTER</span>
					<span class="bc-wordmark-sub"><span class="bc-wordmark-rule"></span><span class="bc-wordmark-badminton">BADMINTON</span><span class="bc-wordmark-rule"></span></span>
				</a>
				<div class="header-actions">
					<form class="site-search" method="get" action="<?= htmlspecialchars($localize('/'), ENT_QUOTES, 'UTF-8') ?>">
						<input type="text" value="" name="s" placeholder="<?= htmlspecialchars($chrome['search'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" />
						<button type="submit" aria-label="<?= htmlspecialchars($chrome['search'], ENT_QUOTES, 'UTF-8') ?>"><?= $searchIcon ?></button>
					</form>
					<?php /*
						Phone only: the field collapses to this button so the
						bar stays one row, and opens as a row of its own
						underneath. Both labels sit here, side by side, so the
						hamburger no longer takes a second row to itself.
					*/ ?>
					<label for="search-toggle" class="icon-btn search-toggle-btn" aria-label="<?= htmlspecialchars($chrome['search'], ENT_QUOTES, 'UTF-8') ?>"><?= $searchIcon ?></label>
					<label for="nav-toggle" class="icon-btn nav-toggle-btn" aria-label="<?= htmlspecialchars($chrome['menu'], ENT_QUOTES, 'UTF-8') ?>"><span></span><span></span><span></span></label>
				</div>
			</div>
		</div>

		<nav class="main-nav">
			<div class="nav-groups">
			<?php /*
				The takeover menu's own head and search field. Both are
				hidden until the panel opens (see the 1100px breakpoint in
				the stylesheet above): the bar keeps its own wordmark and
				search, and the panel covers them.
			*/ ?>
				<div class="nav-panel-head">
					<div class="bc-wordmark bc-wordmark-sm" aria-hidden="true">
						<span class="bc-wordmark-master">MASTER</span>
						<span class="bc-wordmark-sub"><span class="bc-wordmark-rule"></span><span class="bc-wordmark-badminton">BADMINTON</span><span class="bc-wordmark-rule"></span></span>
					</div>
					<label for="nav-toggle" class="nav-panel-close" aria-label="<?= htmlspecialchars($chrome['menu'], ENT_QUOTES, 'UTF-8') ?>">&#215;</label>
				</div>
				<form class="nav-panel-search" method="get" action="<?= htmlspecialchars($localize('/'), ENT_QUOTES, 'UTF-8') ?>">
					<button type="submit" aria-label="<?= htmlspecialchars($chrome['search'], ENT_QUOTES, 'UTF-8') ?>"><?= $searchIcon ?></button>
					<input type="text" value="" name="s" placeholder="<?= htmlspecialchars($chrome['search'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" />
				</form>
			<?php foreach ($navGroups as $groupName => $groupItems): ?>
				<?php if ($groupItems === []) { continue; } ?>
				<ul class="nav-list nav-list-<?= htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') ?>">
				<?php foreach ($groupItems as $item): ?>
					<?php $hasChildren = !empty($item['children']); ?>
					<?php
						// Both the active test and the rendered href use the
						// localized target, so "you are here" still lights up
						// on the Chinese mirror.
						$itemHref = $hasChildren ? null : $localize($item['href']);
						$active = $hasChildren
							? array_reduce(
								$item['children'],
								fn (bool $carry, array $child): bool => $carry || $isLinkActive($localize($child['href'])),
								false,
							)
							: $isLinkActive($itemHref);
					?>
					<?php $isMega = $hasChildren && count($item['children']) > 4; ?>
					<li class="nav-item<?= $active ? ' is-active' : '' ?><?= $hasChildren ? ' has-children' : '' ?><?= isset($item['variant']) ? ' nav-item-' . htmlspecialchars($item['variant'], ENT_QUOTES, 'UTF-8') : '' ?>">
						<?php if ($hasChildren): ?>
							<a href="#" class="nav-parent" aria-haspopup="true"><?= htmlspecialchars($navLabel($item), ENT_QUOTES, 'UTF-8') ?></a>
							<ul class="nav-dropdown<?= $isMega ? ' nav-dropdown-mega' : '' ?>">
								<?php foreach ($item['children'] as $child): ?>
									<li><a href="<?= htmlspecialchars($localize($child['href']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($navLabel($child), ENT_QUOTES, 'UTF-8') ?></a></li>
								<?php endforeach; ?>
							</ul>
						<?php else: ?>
							<a href="<?= htmlspecialchars($itemHref, ENT_QUOTES, 'UTF-8') ?>"<?= isset($item['target']) ? ' target="' . htmlspecialchars($item['target'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>><?= htmlspecialchars($navLabel($item), ENT_QUOTES, 'UTF-8') ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
			</div>
		</nav>

		<?php if ($hero !== null): ?>
		<?php
			// Two shapes of title: the homepage and the directory pages set
			// lead/key for the two-tone display treatment, article and
			// listing pages hand up their own heading as one string.
			$heroTwoTone = isset($hero['lead'], $hero['key']);

			// The homepage's hero carries the page's own call to action and
			// the eight-category strip along the bottom of the band.
			$heroStrip = is_array($hero['strip'] ?? null) ? $hero['strip'] : [];
			$heroJump = (string) ($hero['jump'] ?? '');
		?>
		<section class="page-hero<?= isset($hero['variant']) ? ' page-hero-' . htmlspecialchars($hero['variant'], ENT_QUOTES, 'UTF-8') : '' ?>">
			<div class="page-hero-inner">
				<?php if (($hero['eyebrow'] ?? '') !== ''): ?>
				<p class="page-hero-eyebrow"><?= htmlspecialchars($hero['eyebrow'], ENT_QUOTES, 'UTF-8') ?></p>
				<?php endif; ?>
				<?php if ($heroTwoTone): ?>
				<h1 class="page-hero-title"><span class="v2-head-lead"><?= htmlspecialchars($hero['lead'], ENT_QUOTES, 'UTF-8') ?></span> <span class="v2-head-key"><?= htmlspecialchars($hero['key'], ENT_QUOTES, 'UTF-8') ?></span></h1>
				<?php elseif (($hero['title'] ?? '') !== ''): ?>
				<h1 class="page-hero-title"><?= htmlspecialchars($hero['title'], ENT_QUOTES, 'UTF-8') ?></h1>
				<?php endif; ?>
				<?php if (($hero['blurb'] ?? '') !== ''): ?>
				<p class="page-hero-blurb"><?= htmlspecialchars($hero['blurb'], ENT_QUOTES, 'UTF-8') ?></p>
				<?php endif; ?>
				<?php if (($hero['meta'] ?? '') !== ''): ?>
				<p class="page-hero-meta"><?= htmlspecialchars($hero['meta'], ENT_QUOTES, 'UTF-8') ?></p>
				<?php endif; ?>

				<?php if ($heroJump !== ''): ?>
				<div class="hero-actions">
					<a class="hero-btn hero-btn-primary" href="<?= htmlspecialchars($heroJump, ENT_QUOTES, 'UTF-8') ?>">
						<span class="hero-btn-label"><?= htmlspecialchars($chrome['learn'], ENT_QUOTES, 'UTF-8') ?></span>
						<span class="hero-btn-icon" aria-hidden="true">&#8595;</span>
					</a>
					<a class="hero-btn hero-btn-ghost" href="<?= htmlspecialchars($beginnerHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($chrome['beginner'], ENT_QUOTES, 'UTF-8') ?></a>
				</div>
				<?php endif; ?>

				<?php if ($heroStrip !== []): ?>
				<?php /*
					The eight-category strip, handed up by HomepageLayout
					from the page's own icon strip. It keeps the .quick-nav /
					#thct / .quick-nav-link contract assets/js/home-ui.js
					reads, so clicking a column still opens the matching
					category card further down the page.
				*/ ?>
				<nav class="quick-nav hero-strip" aria-label="<?= htmlspecialchars((string) ($hero['strip_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
					<?php /*
						The strip is a scrolling row once the eight columns
						stop fitting. These say so, and step it along; they
						stay hidden until assets/js/home-ui.js measures that
						there is actually something to scroll to.
					*/ ?>
					<button type="button" class="hero-strip-arrow hero-strip-prev" aria-label="<?= htmlspecialchars($chrome['strip_prev'], ENT_QUOTES, 'UTF-8') ?>">&#8249;</button>
					<button type="button" class="hero-strip-arrow hero-strip-next" aria-label="<?= htmlspecialchars($chrome['strip_next'], ENT_QUOTES, 'UTF-8') ?>">&#8250;</button>
					<div class="quick-nav-inner">
						<ul id="thct">
							<?php foreach ($heroStrip as $column): ?>
							<li>
								<a class="quick-nav-link" href="<?= htmlspecialchars((string) $column['href'], ENT_QUOTES, 'UTF-8') ?>">
									<span class="quick-nav-label"><?= htmlspecialchars((string) $column['label'], ENT_QUOTES, 'UTF-8') ?></span>
									<span class="quick-nav-count"><?= htmlspecialchars((string) $column['count'], ENT_QUOTES, 'UTF-8') ?></span>
								</a>
							</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</nav>
				<?php endif; ?>
			</div>
		</section>
		<?php endif; ?>

		<?php /*
			The beginner call-to-action band, for a homepage whose export did
			not give HomepageLayout the shape it needed to build the hero
			above (which carries the same link as one of its two buttons).
		*/ ?>
		<?php if ($isHome && $hero === null): ?>
		<section class="hero-cta">
			<div class="hero-cta-inner">
				<img class="hero-cta-icon" src="/wp-content/uploads/2016/09/icon-badminton-1.png" alt="" />
				<a class="hero-cta-btn" href="<?= htmlspecialchars($beginnerHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($chrome['beginner'], ENT_QUOTES, 'UTF-8') ?></a>
			</div>
		</section>
		<?php endif; ?>
	</header>

	<a class="lang-switch" href="<?= htmlspecialchars($langHref, ENT_QUOTES, 'UTF-8') ?>">🌐 <?= htmlspecialchars($langLabel, ENT_QUOTES, 'UTF-8') ?></a>

	<div id="main" class="wrapper">
