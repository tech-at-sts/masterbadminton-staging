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
				<div class="container no-padding">
					<div class="ts-col-24">
						<div id="nav_menu-15" class="widget-container widget_nav_menu">
							<div class="menu-footer-container"><ul id="menu-footer" class="menu">
								<?php foreach ($footerItems as $item): ?>
								<li><a href="<?= htmlspecialchars($localize($item['href']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($navLabel($item), ENT_QUOTES, 'UTF-8') ?></a></li>
								<?php endforeach; ?>
							</ul></div>
						</div>
						<div id="text-2" class="widget-container widget_text">
							<div class="textwidget">
								<div style="font-size: 12px;color: white;">
									<strong>By David Tee,</strong> Copyright &copy; 2010-<?= date('Y') ?> <a href="<?= htmlspecialchars($localize('/'), ENT_QUOTES, 'UTF-8') ?>">masterbadminton.com</a>
								</div>
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
<script src="/assets/js/home-ui.js" defer></script>
<script src="/assets/js/category-page.js" defer></script>
<?php if ($isPost): ?>
<script src="/assets/js/post-page.js" defer></script>
<?php endif; ?>
</body>
</html>
