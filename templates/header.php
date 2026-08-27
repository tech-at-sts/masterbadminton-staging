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
	['label' => 'Contact', 'href' => '/contact.html'],
	['label' => 'Ask a Question', 'href' => 'https://masterbadminton.com/badminton-questions.html'],
	['label' => 'Store', 'href' => 'https://masterbadmintonshop.com/', 'target' => '_blank'],
];

$normalizedPath = '/' . trim((string) parse_url($currentPath, PHP_URL_PATH), '/');

$isLinkActive = static function (string $href) use ($normalizedPath): bool {
	if ($href === '/') {
		return $normalizedPath === '/';
	}

	return str_starts_with($href, '/') && rtrim($href, '/') === rtrim($normalizedPath, '/');
};
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
	<link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Raleway:wght@700;800&display=swap" rel="stylesheet" />
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
		.nav-list{max-width:1140px;margin:0 auto;padding:0 20px;display:flex;align-items:center;justify-content:center;flex-wrap:wrap;list-style:none;}
		.nav-item{position:relative;}
		.nav-item > a,
		.nav-item summary{color:#fff;font-family:'Raleway',Arial,sans-serif;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:16px 9px;white-space:nowrap;display:flex;align-items:center;gap:4px;border-bottom:2px solid transparent;cursor:pointer;list-style:none;}
		.nav-item summary::-webkit-details-marker{display:none;}
		.nav-item > a:hover,
		.nav-item summary:hover,
		.nav-item.is-active > a{color:#ff9d5c;border-bottom-color:#eb7d2e;}
		.nav-item.is-active > a{color:#eb7d2e;}
		.nav-item summary::after{content:'▾';font-size:9px;opacity:.7;}
		.nav-item details[open] > summary{color:#ff9d5c;border-bottom-color:#eb7d2e;}

		.nav-dropdown{list-style:none;background:#fff;border-radius:10px;box-shadow:0 12px 28px rgba(0,0,0,.16);padding:8px;margin:4px 0 0;min-width:230px;}
		.nav-item a{color:inherit;}
		.nav-dropdown li a{display:block;padding:10px 14px;border-radius:6px;color:#3f3f3f;font-size:14px;font-family:'Lato',sans-serif;font-weight:400;}
		.nav-dropdown li a:hover{background:#fbeee2;color:#eb7d2e;}

		@media (min-width: 768px){
			.nav-item details{position:static;}
			.nav-item summary{list-style:none;}
			.nav-dropdown{position:absolute;top:100%;left:0;z-index:30;}
		}

		@media (max-width: 767px){
			.header-middle-inner{padding:14px 20px;}
			.site-search{width:auto;flex:1;}
			.nav-list{flex-direction:column;align-items:stretch;padding:0;max-height:0;overflow:hidden;transition:max-height .2s ease;}
			.nav-toggle-checkbox:checked ~ .nav-list{max-height:2000px;}
			.nav-toggle-btn{display:flex;flex-direction:column;justify-content:center;gap:4px;max-width:1140px;margin:0 auto;padding:14px 20px;cursor:pointer;}
			.nav-toggle-btn span{display:block;width:22px;height:2px;background:#fff;}
			.nav-item > a,.nav-item summary{padding:12px 20px;border-bottom:1px solid #2c2c2c;}
			.nav-dropdown{background:#181818;box-shadow:none;border-radius:0;margin:0;padding:0;}
			.nav-dropdown li a{color:#ccc;padding:10px 20px 10px 34px;}
			.nav-dropdown li a:hover{background:#2c2c2c;color:#eb7d2e;}
		}
	</style>

	<script src="https://masterbadminto.wpenginepowered.com/wp-includes/js/jquery/jquery.min.js"></script>
</head>
<body class="wp-theme-gon wp-child-theme-gon-child header-v2 wide layout-fullwidth ts_desktop">
<div id="page" class="hfeed site">

	<header class="site-header">
		<div class="topbar">
			<div class="topbar-inner">
				<img src="/wp-content/uploads/2016/09/icon-badminton-1.png" alt="" />
				<a class="beginner-link" href="/category/badminton-videos/badminton-basics.html">Are you a beginner? Click Here</a>
			</div>
		</div>

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

		<input type="checkbox" id="nav-toggle" class="nav-toggle-checkbox" />
		<nav class="main-nav">
			<label for="nav-toggle" class="nav-toggle-btn" aria-label="Toggle navigation menu"><span></span><span></span><span></span></label>
			<ul class="nav-list">
				<?php foreach ($navItems as $item): ?>
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
					<li class="nav-item<?= $active ? ' is-active' : '' ?><?= $hasChildren ? ' has-children' : '' ?>">
						<?php if ($hasChildren): ?>
							<details>
								<summary><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></summary>
								<ul class="nav-dropdown">
									<?php foreach ($item['children'] as $child): ?>
										<li><a href="<?= htmlspecialchars($child['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($child['label'], ENT_QUOTES, 'UTF-8') ?></a></li>
									<?php endforeach; ?>
								</ul>
							</details>
						<?php else: ?>
							<a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"<?= isset($item['target']) ? ' target="' . htmlspecialchars($item['target'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
	</header>

	<div id="main" class="wrapper">
