<?php
/**
 * Single shared footer for every page on the site. See templates/header.php
 * for the variables available in scope - Layout includes both templates
 * from one method, so the header's $localize, $navLabel and $chrome are
 * still live here and the footer links stay in the reader's language just
 * like the navigation above them.
 */

declare(strict_types=1);

$footerItems = [
	['label' => 'Home', 'label_zh' => '首页', 'href' => '/'],
	['label' => 'Blog', 'label_zh' => '博客', 'href' => '/how-to-play-badminton-blog.html'],
	['label' => 'Contact', 'label_zh' => '联系我们', 'href' => '/contact.html'],
	['label' => 'Disclaimer', 'label_zh' => '免责声明', 'href' => '/disclaimer'],
	['label' => 'Privacy Policy', 'label_zh' => '隐私政策', 'href' => '/privacy-policy.html'],
];
?>
	</div><!-- #main .wrapper -->
	<div class="clear"></div>

	<footer id="colophon">
		<div class="footer-container">
			<div class="end-footer footer-area">
				<div class="site-footer-inner">
					<?php /*
						One row on the homepage - the wordmark against the
						links - and a centred stack everywhere else; both are
						the same two parts, see assets/css/home-v2.css.
					*/ ?>
					<div class="site-footer-top-row">
						<div class="bc-wordmark bc-wordmark-sm site-footer-wordmark" aria-hidden="true">
							<span class="bc-wordmark-master">MASTER</span>
							<span class="bc-wordmark-sub"><span class="bc-wordmark-rule"></span><span class="bc-wordmark-badminton">BADMINTON</span><span class="bc-wordmark-rule"></span></span>
						</div>
						<div id="nav_menu-15" class="widget-container widget_nav_menu">
							<div class="menu-footer-container"><ul id="menu-footer" class="menu">
								<?php foreach ($footerItems as $item): ?>
								<li><a href="<?= htmlspecialchars($localize($item['href']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($navLabel($item), ENT_QUOTES, 'UTF-8') ?></a></li>
								<?php endforeach; ?>
							</ul></div>
						</div>
					</div>
					<div class="site-footer-divider" aria-hidden="true"></div>
					<div id="text-2" class="widget-container widget_text">
						<div class="textwidget">
							<div class="site-footer-meta">
								<span><strong>By David Tee,</strong> Copyright &copy; 2010-<?= date('Y') ?> <a href="<?= htmlspecialchars($localize('/'), ENT_QUOTES, 'UTF-8') ?>">masterbadminton.com</a></span>
								<a class="site-footer-top" href="javascript:void(0)" onclick="window.scrollTo({top:0,behavior:'smooth'});"><?= htmlspecialchars($chrome['top'], ENT_QUOTES, 'UTF-8') ?></a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</footer>
	</div><!-- #page -->

<div id="to-top" class="scroll-button">
	<a class="scroll-button" href="javascript:void(0)" title="<?= htmlspecialchars($chrome['top'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($chrome['top'], ENT_QUOTES, 'UTF-8') ?></a>
</div>

<script src="https://masterbadminto.wpenginepowered.com/wp-content/themes/gon/js/include_scripts.js"></script>
<script src="https://masterbadminto.wpenginepowered.com/wp-content/themes/gon/js/main.js"></script>
<script src="https://masterbadminto.wpenginepowered.com/wp-content/themes/gon/js/select2.min.js"></script>
<?php /* Stamped like the stylesheets - see $asset in templates/header.php. */ ?>
<script src="<?= htmlspecialchars($asset('/assets/js/home-ui.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
<script src="<?= htmlspecialchars($asset('/assets/js/category-page.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
<?php if ($isPost): ?>
<script src="<?= htmlspecialchars($asset('/assets/js/post-page.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
<?php endif; ?>
</body>
</html>
