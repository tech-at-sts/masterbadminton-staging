<?php
/**
 * Single shared header for every page on the site.
 *
 * Rendered by App\Layout\Layout::render(). Variables in scope:
 * $title, $description, $currentPath (all set by Layout before this file
 * is included).
 */

declare(strict_types=1);

$navItems = [
	['label' => 'Home', 'href' => '/'],
	['label' => 'Blog', 'href' => '/how-to-play-badminton-blog.html'],
	[
		'label' => 'Playing the Game',
		'children' => [
			['label' => 'Rules', 'href' => '/badminton-rules.html'],
			['label' => 'Badminton Basics', 'href' => '/category/badminton-videos/badminton-basics.html'],
			['label' => 'Badminton Strokes', 'href' => '/badminton-strokes.html'],
			['label' => 'Techniques and Shots', 'href' => '/badminton-techniques.html'],
			['label' => 'Net Play', 'href' => '/badminton-net-play.html'],
			['label' => 'Smashing', 'href' => '/badminton-smash-technique.html'],
			['label' => 'Advanced Skills', 'href' => '/advanced-badminton-techniques.html'],
		],
	],
	[
		'label' => 'Equipment',
		'children' => [
			['label' => 'Rackets', 'href' => '/badminton-racket.html'],
			['label' => 'Equipments', 'href' => '/badminton-equipment.html'],
		],
	],
	[
		'label' => 'Resources',
		'children' => [
			['label' => 'Badminton Articles', 'href' => '/badminton-articles.html'],
			['label' => 'Badminton Tips', 'href' => '/badminton-tips.html'],
			['label' => 'Professional Players', 'href' => '/professional-badminton-interview.html'],
			['label' => 'Places to Play in UK', 'href' => '/uk-badminton-places-to-play.html'],
		],
	],
	[
		'label' => 'Just for Fun',
		'children' => [
			['label' => 'Top Players', 'href' => '/badminton-players.html'],
			['label' => 'Videos', 'href' => '/badminton-videos.html'],
			['label' => 'News', 'href' => '/badminton-news.html'],
		],
	],
	['label' => 'Contact', 'href' => '/contact.html', 'group' => 'utility'],
	['label' => 'Ask a Question', 'href' => 'https://masterbadminton.com/badminton-questions.html', 'group' => 'utility'],
	['label' => 'Store', 'href' => 'https://masterbadmintonshop.com/', 'target' => '_blank', 'group' => 'utility', 'variant' => 'store'],
];

// Same items, same order, same labels - only split into two visual groups
// so the content sections read apart from the utility links.
$navGroups = [
	'primary' => array_values(array_filter($navItems, static fn (array $item): bool => ($item['group'] ?? 'primary') === 'primary')),
	'utility' => array_values(array_filter($navItems, static fn (array $item): bool => ($item['group'] ?? 'primary') === 'utility')),
];

$normalizedPath = '/' . trim((string) parse_url($currentPath, PHP_URL_PATH), '/');

// The homepage - English or the Chinese mirror - is the only page with a
// hero region, so it gets the prominent beginner call-to-action band; every
// other page keeps the same link in the slim top strip.
$isHome = in_array($normalizedPath, ['/', '/zh'], true);

$isLinkActive = static function (string $href) use ($normalizedPath): bool {
	if ($href === '/') {
		return $normalizedPath === '/';
	}

	return str_starts_with($href, '/') && rtrim($href, '/') === rtrim($normalizedPath, '/');
};

// The legacy tree keeps a full Chinese mirror under /zh/, page-for-page
// with the English tree. Point the language switcher at the mirror of the
// page actually being viewed when it exists, falling back to that
// language's homepage otherwise, rather than always sending readers to /.
// realpath() the root too, not just each candidate: if the document root
// is itself reached through a symlink (common with release-based deploys),
// comparing a resolved candidate path against an unresolved root would fail
// the prefix check below for every single path, silently forcing every
// language-switch link back to the homepage. Router::safeFile() resolves
// both sides the same way for the same reason.
$siteRoot = realpath(dirname(__DIR__)) ?: dirname(__DIR__);
$pathResolves = static function (string $path) use ($siteRoot): bool {
	$trimmed = trim($path, '/');
	$candidates = $trimmed === '' ? ['index.html'] : [$trimmed . '/index.html', $trimmed];

	foreach ($candidates as $candidate) {
		$full = realpath($siteRoot . '/' . $candidate);

		if (
			$full !== false
			&& is_file($full)
			&& str_starts_with($full, $siteRoot . DIRECTORY_SEPARATOR)
			&& strtolower(pathinfo($full, PATHINFO_EXTENSION)) === 'html'
		) {
			return true;
		}
	}

	return false;
};

if ($normalizedPath === '/zh' || str_starts_with($normalizedPath, '/zh/')) {
	$counterpart = $normalizedPath === '/zh' ? '/' : substr($normalizedPath, 3);
	$langHref = $pathResolves($counterpart) ? $counterpart : '/';
	$langLabel = 'English';
} else {
	$counterpart = $normalizedPath === '/' ? '/zh' : '/zh' . $normalizedPath;
	$langHref = $pathResolves($counterpart) ? $counterpart : '/zh';
	$langLabel = '中文';
}
?>
<!DOCTYPE html>
<html lang="en-US">
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
	<link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Raleway:wght@700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet" />
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

	<script src="https://masterbadminto.wpenginepowered.com/wp-includes/js/jquery/jquery.min.js"></script>
</head>
<body class="wp-theme-gon wp-child-theme-gon-child header-v2 wide layout-fullwidth ts_desktop">
<div id="page" class="hfeed site">

	<header class="site-header">
		<?php if (!$isHome): ?>
		<div class="topbar">
			<div class="topbar-inner">
				<img src="/wp-content/uploads/2016/09/icon-badminton-1.png" alt="" />
				<a class="beginner-link" href="/category/badminton-videos/badminton-basics.html">Are you a beginner? Click Here</a>
			</div>
		</div>
		<?php endif; ?>

		<div class="header-middle">
			<div class="header-middle-inner">
				<a class="site-logo" href="/">
					<img src="https://masterbadminto.wpenginepowered.com/wp-content/uploads/2016/05/masw.png" alt="Master Badminton" title="Master Badminton" />
				</a>
				<form class="site-search" method="get" action="/">
					<input type="text" value="" name="s" placeholder="Search" autocomplete="off" />
					<button type="submit" aria-label="Search">&#128269;</button>
				</form>
			</div>
		</div>

		<nav class="main-nav">
			<input type="checkbox" id="nav-toggle" class="nav-toggle-checkbox" />
			<label for="nav-toggle" class="nav-toggle-btn" aria-label="Toggle navigation menu"><span></span><span></span><span></span></label>
			<div class="nav-groups">
			<?php foreach ($navGroups as $groupName => $groupItems): ?>
				<?php if ($groupItems === []) { continue; } ?>
				<ul class="nav-list nav-list-<?= htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') ?>">
				<?php foreach ($groupItems as $item): ?>
					<?php $hasChildren = !empty($item['children']); ?>
					<?php
						$active = $hasChildren
							? array_reduce(
								$item['children'],
								static fn (bool $carry, array $child): bool => $carry || $isLinkActive($child['href']),
								false,
							)
							: $isLinkActive($item['href']);
					?>
					<?php $isMega = $hasChildren && count($item['children']) > 4; ?>
					<li class="nav-item<?= $active ? ' is-active' : '' ?><?= $hasChildren ? ' has-children' : '' ?><?= isset($item['variant']) ? ' nav-item-' . htmlspecialchars($item['variant'], ENT_QUOTES, 'UTF-8') : '' ?>">
						<?php if ($hasChildren): ?>
							<a href="#" class="nav-parent" aria-haspopup="true"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></a>
							<ul class="nav-dropdown<?= $isMega ? ' nav-dropdown-mega' : '' ?>">
								<?php foreach ($item['children'] as $child): ?>
									<li><a href="<?= htmlspecialchars($child['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($child['label'], ENT_QUOTES, 'UTF-8') ?></a></li>
								<?php endforeach; ?>
							</ul>
						<?php else: ?>
							<a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"<?= isset($item['target']) ? ' target="' . htmlspecialchars($item['target'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
			</div>
		</nav>

		<?php if ($isHome): ?>
		<section class="hero-cta">
			<div class="hero-cta-inner">
				<img class="hero-cta-icon" src="/wp-content/uploads/2016/09/icon-badminton-1.png" alt="" />
				<a class="hero-cta-btn" href="/category/badminton-videos/badminton-basics.html">Are you a beginner? Click Here</a>
			</div>
		</section>
		<?php endif; ?>
	</header>

	<a class="lang-switch" href="<?= htmlspecialchars($langHref, ENT_QUOTES, 'UTF-8') ?>">🌐 <?= htmlspecialchars($langLabel, ENT_QUOTES, 'UTF-8') ?></a>

	<div id="main" class="wrapper">
