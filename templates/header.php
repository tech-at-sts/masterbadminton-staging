<?php
/**
 * Single shared header for every page on the site.
 *
 * Rendered by App\Layout\Layout::render(). Variables in scope:
 * $title, $description, $currentPath (all set by Layout before this file
 * is included).
 */

declare(strict_types=1);
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
	<style>
		.page-container{padding-top:2px;padding-left:0 !important;padding-right:0 !important;}
		.vertical-menu-wrapper{display:none;}
		li{color:#454545;}
		.has-vertical-menu .ts-menu > .pc-menu{margin-left:0 !important;}
		@media only screen and (max-width: 767px){
			header.ts-header.has-sticky .header-container .cat-head-custom{display:block !important;}
			.side-home-tw.wpb_column.vc_column_container.vc_col-sm-3{display:none;}
		}
	</style>

	<script src="https://masterbadminto.wpenginepowered.com/wp-includes/js/jquery/jquery.min.js"></script>
</head>
<body class="wp-theme-gon wp-child-theme-gon-child header-v2 wide layout-fullwidth ts_desktop">
<div id="page" class="hfeed site">

	<header class="ts-header has-sticky">
		<div class="header-container">
			<div class="header-template header-v2 has-vertical-menu hidden-cart show-search">

				<div class="header-top">
					<div class="container">
						<div class="header-top-left">
							<div class="info-desc"><span class='info-begin'><i class='fa-wpbeginner'></i><a href="/category/badminton-videos/badminton-basics.html" style="font-weight:bold;"><span><img src="/wp-content/uploads/2016/09/icon-badminton-1.png" width="25px"/></span> Are you a beginner? Click Here</a></span></div>
						</div>
						<div class="header-top-right">
							<span class="ts-mobile-menu-icon-toggle visible-phone"><i class="fa fa-bars"></i></span>
						</div>
					</div>
				</div>

				<div class="mobile-menu-wrapper">
					<div class="menu-categories-container"><ul id="menu-categories" class="menu">
						<li class="menu-item menu-item-has-children"><a href="#">Playing the Game</a>
							<ul class="sub-menu">
								<li><a href="/badminton-rules.html">Rules</a></li>
								<li><a href="/category/badminton-videos/badminton-basics.html">Badminton Basics</a></li>
								<li><a href="/badminton-strokes.html">Badminton Strokes</a></li>
								<li><a href="/badminton-techniques.html">Techniques and Shots</a></li>
								<li><a href="/badminton-net-play.html">Net Play</a></li>
								<li><a href="/badminton-smash-technique.html">Smashing</a></li>
								<li><a href="/advanced-badminton-techniques.html">Advanced Techniques</a></li>
							</ul>
						</li>
						<li class="menu-item menu-item-has-children"><a href="#">Equipments</a>
							<ul class="sub-menu">
								<li><a href="/badminton-racket.html">Rackets</a></li>
								<li><a href="/badminton-equipment.html">Equipments</a></li>
							</ul>
						</li>
						<li class="menu-item menu-item-has-children"><a href="#">Resources</a>
							<ul class="sub-menu">
								<li><a href="/badminton-articles.html">Badminton Articles</a></li>
								<li><a href="/badminton-tips.html">Badminton Tips and Advice</a></li>
								<li><a href="/professional-badminton-interview.html">Professional Players</a></li>
								<li><a href="/uk-badminton-places-to-play.html">Places to Play in UK</a></li>
							</ul>
						</li>
						<li class="menu-item menu-item-has-children"><a href="#">Just for Fun</a>
							<ul class="sub-menu">
								<li><a href="/badminton-players.html">Top Players</a></li>
								<li><a href="/badminton-videos.html">Videos</a></li>
								<li><a href="/badminton-news.html">News</a></li>
							</ul>
						</li>
					</ul></div>
					<nav class="main-menu mobile-menu"><ul id="menu-main" class="menu">
						<li><a href="/">Home</a></li>
						<li><a href="/how-to-play-badminton-blog.html">Blog</a></li>
						<li><a href="/contact.html">Contact</a></li>
						<li><a href="https://masterbadminton.com/badminton-questions.html">Ask a Question</a></li>
						<li><a target="_blank" href="https://masterbadmintonshop.com/">Store</a></li>
					</ul></nav>
					<div class="cat-head-custom">
						<span class="cat-sp-cus" id="catcl">CATEGORIES</span>
					</div>
					<a class="mob-csa" href="/category/badminton-videos/badminton-basics.html" style="font-weight:bold;"><span><img src="/wp-content/uploads/2016/09/icon-badminton-1.png" width="25px"/></span> Are you a beginner? Click Here</a>
				</div>

				<div class="header-middle">
					<div class="container">
						<div class="search-wrapper hidden-phone">
							<div class="ts-search-by-category"><form method="get" action="/" id="searchform-447">
								<div class="search-table">
									<div class="search-field search-content">
										<input type="text" value="" name="s" id="s-447" placeholder="Search" autocomplete="off" />
									</div>
									<div class="search-button">
										<input type="submit" id="searchsubmit-447" value="Search" />
									</div>
								</div>
							</form></div>
						</div>

						<div class="logo-wrapper">
							<div class="logo">
								<a href="/">
									<img src="https://masterbadminto.wpenginepowered.com/wp-content/uploads/2016/05/masw.png" alt="Master Badminton" title="Master Badminton" class="normal-logo" />
									<img src="https://masterbadminto.wpenginepowered.com/wp-content/uploads/2016/05/masw.png" alt="Master Badminton" title="Master Badminton" class="normal-logo mobile-logo" />
									<img src="https://masterbadminto.wpenginepowered.com/wp-content/uploads/2016/05/masw.png" alt="Master Badminton" title="Master Badminton" class="normal-logo sticky-logo" />
								</a>
							</div>
						</div>

						<div class="search-wrapper visible-phone">
							<div class="ts-search-by-category"><form method="get" action="/" id="searchform-430">
								<div class="search-table">
									<div class="search-field search-content">
										<input type="text" value="" name="s" id="s-430" placeholder="Search" autocomplete="off" />
									</div>
									<div class="search-button">
										<input type="submit" id="searchsubmit-430" value="Search" />
									</div>
								</div>
							</form></div>
						</div>
					</div>
				</div>

				<div class="header-bottom header-sticky">
					<div class="container">
						<div class="menu-wrapper hidden-phone">
							<div class="ts-menu">
								<div class="vertical-menu-wrapper">
									<div class="vertical-menu-heading">CATEGORIES</div>
									<nav class="vertical-menu pc-menu ts-mega-menu-wrapper"><ul id="menu-categories-2" class="menu">
										<li class="menu-item menu-item-has-children ts-normal-menu parent">
											<a href="#"><span class="menu-label">Playing the Game</span></a><span class="ts-menu-drop-icon"></span>
											<ul class="sub-menu">
												<li><a href="/badminton-rules.html"><span class="menu-label">Rules</span></a></li>
												<li><a href="/category/badminton-videos/badminton-basics.html"><span class="menu-label">Badminton Basics</span></a></li>
												<li><a href="/badminton-strokes.html"><span class="menu-label">Badminton Strokes</span></a></li>
												<li><a href="/badminton-techniques.html"><span class="menu-label">Techniques and Shots</span></a></li>
												<li><a href="/badminton-net-play.html"><span class="menu-label">Net Play</span></a></li>
												<li><a href="/badminton-smash-technique.html"><span class="menu-label">Smashing</span></a></li>
												<li><a href="/advanced-badminton-techniques.html"><span class="menu-label">Advanced Techniques</span></a></li>
											</ul>
										</li>
										<li class="menu-item menu-item-has-children ts-normal-menu parent">
											<a href="#"><span class="menu-label">Equipments</span></a><span class="ts-menu-drop-icon"></span>
											<ul class="sub-menu">
												<li><a href="/badminton-racket.html"><span class="menu-label">Rackets</span></a></li>
												<li><a href="/badminton-equipment.html"><span class="menu-label">Equipments</span></a></li>
											</ul>
										</li>
										<li class="menu-item menu-item-has-children ts-normal-menu parent">
											<a href="#"><span class="menu-label">Resources</span></a><span class="ts-menu-drop-icon"></span>
											<ul class="sub-menu">
												<li><a href="/badminton-articles.html"><span class="menu-label">Badminton Articles</span></a></li>
												<li><a href="/badminton-tips.html"><span class="menu-label">Badminton Tips and Advice</span></a></li>
												<li><a href="/professional-badminton-interview.html"><span class="menu-label">Professional Players</span></a></li>
												<li><a href="/uk-badminton-places-to-play.html"><span class="menu-label">Places to Play in UK</span></a></li>
											</ul>
										</li>
										<li class="menu-item menu-item-has-children ts-normal-menu parent">
											<a href="#"><span class="menu-label">Just for Fun</span></a><span class="ts-menu-drop-icon"></span>
											<ul class="sub-menu">
												<li><a href="/badminton-players.html"><span class="menu-label">Top Players</span></a></li>
												<li><a href="/badminton-videos.html"><span class="menu-label">Videos</span></a></li>
												<li><a href="/badminton-news.html"><span class="menu-label">News</span></a></li>
											</ul>
										</li>
									</ul></nav>
								</div>
								<nav class="main-menu pc-menu ts-mega-menu-wrapper"><ul id="menu-main-1" class="menu">
									<li class="ts-normal-menu"><a href="/"><span class="menu-label">Home</span></a></li>
									<li class="ts-normal-menu"><a href="/how-to-play-badminton-blog.html"><span class="menu-label">Blog</span></a></li>
									<li class="ts-normal-menu"><a href="/contact.html"><span class="menu-label">Contact</span></a></li>
									<li class="ts-normal-menu"><a href="https://masterbadminton.com/badminton-questions.html"><span class="menu-label">Ask a Question</span></a></li>
									<li class="ts-normal-menu"><a target="_blank" href="https://masterbadmintonshop.com/"><span class="menu-label">Store</span></a></li>
								</ul></nav>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="mobile-menu hidden-phone">
			<div class="ic-menu-phone"></div>
			<div class="mobile-menu-content"></div>
		</div>
	</header>

	<div id="main" class="wrapper">
