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
	['label' => 'Store', 'label_zh' => '商店', 'href' => 'https://masterbadmintonshop.com/', 'target' => '_blank', 'group' => 'utility', 'variant' => 'store'],
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
	]
	: [
		'beginner' => 'Are you a beginner? Click Here',
		'search' => 'Search',
		'menu' => 'Toggle navigation menu',
		'logo' => 'Master Badminton',
		'top' => 'Back to Top',
		'switch' => '中文',
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

$usesHomeVisual = $isHome || $isCategoryIndex || $isPost || $isArchive;

// One hero band serves all of them: the category directory authors its own
// copy, article and listing pages hand theirs up from the extractor.
$hero = $isCategoryIndex
	? \App\Content\CategoryDirectory::hero($normalizedPath)
	: ($pageHero ?? null);

$beginnerHref = $localize('/category/badminton-videos/badminton-basics.html');

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
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
	<link rel="shortcut icon" href="/wp-content/uploads/2016/09/favi.png" />
	<meta name="robots" content="index, follow" />

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
	<link href="https://fonts.googleapis.com/css2?family=Anton&family=Lato:wght@400;700&family=Outfit:wght@300;400;500;600;700&family=Raleway:wght@700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet" />
	<style>
		.page-container{padding-top:2px;padding-left:0 !important;padding-right:0 !important;}
		li{color:#454545;}
		#main-content.ts-col-18{width:100% !important;}
		@media only screen and (max-width: 767px){
			.side-home-tw.wpb_column.vc_column_container.vc_col-sm-3{display:none;}
		}

		/* Site header / navigation */
		.site-header{font-family:'Lato',Arial,sans-serif;}
		.site-header a{text-decoration:none;}
		.topbar{background:#3a3a3a;}
		.topbar-inner{max-width:1140px;margin:0 auto;padding:9px 20px;display:flex;align-items:center;gap:8px;}
		.topbar-inner img{width:18px;height:18px;opacity:.85;}
		.beginner-link{color:#b8b8b8;font-size:13px;font-weight:700;letter-spacing:.2px;}
		.beginner-link:hover{color:#fff;}

		.header-middle{background:#fff;box-shadow:0 1px 0 rgba(0,0,0,.05);}
		.header-middle-inner{max-width:1140px;margin:0 auto;padding:22px 20px;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;}
		.site-logo img{width:190px;display:block;}
		.site-search{position:relative;width:300px;max-width:100%;display:flex;}
		.site-search input[type="text"]{background:#f4f4f5;border:1px solid transparent;border-radius:24px;padding:12px 44px 12px 18px;width:100%;font-size:14px;color:#444;font-family:'Lato',sans-serif;outline:none;}
		.site-search input[type="text"]:focus{border-color:#eb7d2e;background:#fff;}
		.site-search button{position:absolute;right:4px;top:4px;bottom:4px;width:38px;background:transparent;border:0;color:#b0b0b0;font-size:15px;cursor:pointer;}

		.main-nav{background:#1f1f1f;}
		.nav-toggle-checkbox{display:none;}
		.nav-toggle-btn{display:none;}
		.nav-list,
		.nav-list .nav-item,
		.nav-dropdown,
		.nav-dropdown li{list-style:none !important;list-style-type:none !important;margin:0;padding:0;}
		.nav-list{max-width:1140px;margin:0 auto;padding:0 20px;display:flex;align-items:center;justify-content:center;flex-wrap:wrap;}
		.nav-item{position:relative;}
		.nav-item > a,
		.nav-item > .nav-parent{color:#fff;font-family:'Raleway',Arial,sans-serif;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:16px 9px;white-space:nowrap;display:flex;align-items:center;gap:4px;border-bottom:2px solid transparent;cursor:pointer;}
		.nav-item > a:hover,
		.nav-item > .nav-parent:hover,
		.nav-item:hover > .nav-parent,
		.nav-item:focus-within > .nav-parent,
		.nav-item.is-active > a{border-bottom-color:#eb7d2e;}
		.has-children > .nav-parent::after{content:'▾';font-size:9px;opacity:.7;}

		.nav-dropdown{display:none;position:absolute;top:100%;left:0;z-index:30;background:#fff;border-radius:10px;box-shadow:0 12px 28px rgba(0,0,0,.16);padding:8px;margin-top:4px !important;min-width:230px;}
		/* The panel is offset 4px below its parent, and that gap used to be
		   dead: moving the pointer down into the menu left the <li> for long
		   enough that :hover went false and the panel closed before it could
		   be clicked. This invisible strip bridges the gap. It is a child of
		   the panel, so the pointer never leaves the <li> that :hover is
		   tested on. It is a little taller than the gap so a fast pointer
		   cannot skip past it between frames. */
		.nav-dropdown::before{content:'';position:absolute;left:0;right:0;top:-8px;height:8px;}
		.nav-item:hover > .nav-dropdown,
		.nav-item:focus-within > .nav-dropdown{display:block;}
		.nav-dropdown li a{display:block;padding:10px 14px;border-radius:6px;color:#3f3f3f;font-size:14px;font-family:'Lato',sans-serif;font-weight:400;}
		.nav-dropdown li a:hover{background:#fbeee2;color:#eb7d2e;}

		@media (max-width: 767px){
			.header-middle-inner{padding:14px 20px;}
			.site-search{width:auto;flex:1;}
			.nav-list{flex-direction:column;align-items:stretch;padding:0;max-height:0;overflow:hidden;transition:max-height .2s ease;}
			.nav-toggle-checkbox:checked ~ .nav-list{max-height:2000px;}
			.nav-toggle-btn{display:flex;flex-direction:column;justify-content:center;gap:4px;max-width:1140px;margin:0 auto;padding:14px 20px;cursor:pointer;}
			.nav-toggle-btn span{display:block;width:22px;height:2px;background:#fff;}
			.nav-item > a,.nav-item > .nav-parent{padding:12px 20px;border-bottom:1px solid #2c2c2c;}
			.nav-dropdown,
			.nav-item:hover > .nav-dropdown,
			.nav-item:focus-within > .nav-dropdown{display:block;position:static;background:#181818;box-shadow:none;border-radius:0;margin:0 !important;padding:0;}
			/* No hover on the stacked mobile menu, so no gap to bridge. */
			.nav-dropdown::before{display:none;}
			.nav-dropdown li a{color:#ccc;padding:10px 20px 10px 34px;}
			.nav-dropdown li a:hover{background:#2c2c2c;color:#eb7d2e;}
		}

		.lang-switch{position:fixed;bottom:16px;right:16px;background:#2a2a2a;color:#fff;font-size:12px;font-family:'Lato',sans-serif;padding:9px 16px;border-radius:24px;display:flex;align-items:center;gap:6px;box-shadow:0 6px 18px rgba(0,0,0,.25);z-index:40;text-decoration:none;}
		.lang-switch:hover{background:#1a1a1a;color:#fff;}

		/* "All Categories" section */
		.cat-section{background:#faf5ea;border-radius:20px;padding:44px 40px;margin:36px 0;box-sizing:border-box;}
		.cat-section-title{text-align:center;font-family:'Playfair Display',serif;font-size:34px;font-weight:800;color:#1c2541;margin:0 0 6px;}
		.cat-section-subtitle{text-align:center;font-size:14px;color:#9a9186;margin:0 0 26px;font-family:'Lato',sans-serif;}
		.cat-section-controls{display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;}
		.cat-search-wrap{position:relative;flex:1;min-width:200px;}
		.cat-search-icon{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#b0a894;font-size:14px;}
		.cat-search-input{width:100%;box-sizing:border-box;background:#fff;border:1px solid #e9e1d1;border-radius:10px;padding:13px 16px 13px 40px;font-size:14px;color:#4a3a26;font-family:'Lato',sans-serif;outline:none;}
		.cat-search-input:focus{border-color:#eb7d2e;}
		.cat-expand-all{background:#fff;border:1px solid #e9e1d1;border-radius:10px;padding:13px 20px;font-size:14px;font-weight:700;color:#1c2541;font-family:'Lato',sans-serif;cursor:pointer;white-space:nowrap;}
		.cat-expand-all:hover{border-color:#eb7d2e;color:#eb7d2e;}
		.cat-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
		.cat-accordion{background:#fff;border-radius:14px;box-shadow:0 2px 6px rgba(28,37,65,.05);overflow:hidden;align-self:start;}
		.cat-accordion summary{list-style:none;cursor:pointer;padding:18px 22px;display:flex;align-items:center;justify-content:space-between;gap:12px;font-family:'Playfair Display',serif;font-weight:700;font-size:16px;color:#1c2541;}
		.cat-accordion summary::-webkit-details-marker{display:none;}
		.cat-accordion-meta{display:flex;align-items:center;gap:12px;flex-shrink:0;}
		.cat-accordion-count{background:#f6dfc4;color:#c1602a;font-size:12px;font-weight:700;border-radius:999px;padding:3px 11px;font-family:'Lato',sans-serif;}
		.cat-accordion-chevron{color:#b0a894;font-size:15px;transition:transform .15s;display:inline-block;}
		.cat-accordion[open] .cat-accordion-chevron{transform:rotate(90deg);}
		.cat-accordion-body{padding:0 22px 20px;border-top:1px solid #f3ede1;padding-top:14px;}
		.cat-accordion-body ul{list-style:none !important;margin:0;padding:0;display:flex;flex-direction:column;gap:10px;}
		.cat-accordion-body li{padding:0;}
		.cat-accordion-body a{color:#8a5a3b;text-decoration:none;font-size:13px;font-weight:600;font-family:'Lato',sans-serif;}
		.cat-accordion-body a:hover{color:#c1602a;text-decoration:underline;}

		@media (max-width: 640px){
			.cat-section{padding:28px 20px;}
			.cat-section-title{font-size:26px;}
			.cat-grid{grid-template-columns:1fr;}
		}
	</style>

	<!-- Homepage UI layer. Loaded last so it wins ties against the rules above. -->
	<link rel="stylesheet" href="/assets/css/home-ui.css" media="all" />
	<?php if ($usesHomeVisual): ?>
	<!-- Homepage visual language. Scoped to body.home-v2; see the file header. -->
	<link rel="stylesheet" href="/assets/css/home-v2.css" media="all" />
	<?php endif; ?>
	<?php if ($isCategoryIndex): ?>
	<!-- Category directory layer. Scoped to body.cat-directory-page; builds on home-v2. -->
	<link rel="stylesheet" href="/assets/css/category-page.css" media="all" />
	<?php endif; ?>
	<?php if ($isPost || $isArchive): ?>
	<!-- Article and listing layer. Scoped to body.post-v2 / body.archive-v2; builds on home-v2. -->
	<link rel="stylesheet" href="/assets/css/post-page.css" media="all" />
	<?php endif; ?>

	<script src="https://masterbadminto.wpenginepowered.com/wp-includes/js/jquery/jquery.min.js"></script>
</head>
<body class="wp-theme-gon wp-child-theme-gon-child header-v2 wide layout-fullwidth ts_desktop<?= $usesHomeVisual ? ' home-v2' : '' ?><?= $isCategoryIndex ? ' cat-directory-page' : '' ?><?= $isPost ? ' post-v2' : '' ?><?= $isArchive ? ' archive-v2' : '' ?>">
<div id="page" class="hfeed site">

	<header class="site-header">
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
				<a class="site-logo" href="<?= htmlspecialchars($localize('/'), ENT_QUOTES, 'UTF-8') ?>">
					<img src="https://masterbadminto.wpenginepowered.com/wp-content/uploads/2016/05/masw.png" alt="<?= htmlspecialchars($chrome['logo'], ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($chrome['logo'], ENT_QUOTES, 'UTF-8') ?>" />
				</a>
				<form class="site-search" method="get" action="<?= htmlspecialchars($localize('/'), ENT_QUOTES, 'UTF-8') ?>">
					<input type="text" value="" name="s" placeholder="<?= htmlspecialchars($chrome['search'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" />
					<button type="submit" aria-label="<?= htmlspecialchars($chrome['search'], ENT_QUOTES, 'UTF-8') ?>">&#128269;</button>
				</form>
			</div>
		</div>

		<nav class="main-nav">
			<input type="checkbox" id="nav-toggle" class="nav-toggle-checkbox" />
			<label for="nav-toggle" class="nav-toggle-btn" aria-label="<?= htmlspecialchars($chrome['menu'], ENT_QUOTES, 'UTF-8') ?>"><span></span><span></span><span></span></label>
			<div class="nav-groups">
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
			// Two shapes of title: the directory pages set lead/key for the
			// two-tone display treatment, article and listing pages hand up
			// their own heading as one string.
			$heroTwoTone = isset($hero['lead'], $hero['key']);
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
			</div>
		</section>
		<?php endif; ?>

		<?php if ($isHome): ?>
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
