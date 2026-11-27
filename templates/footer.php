<?php
/**
 * Single shared footer for every page on the site. See templates/header.php
 * for the variables available in scope - Layout includes both templates
 * from one method, so the header's $localize, $navLabel and $chrome are
 * still live here and the footer links stay in the reader's language just
 * like the navigation above them.
 */

declare(strict_types=1);

use App\Core\AssetUrl;
use App\Core\SiteConfig;

$footerItems = [
	['label' => 'Home', 'label_zh' => '首页', 'href' => '/'],
	['label' => 'Blog', 'label_zh' => '博客', 'href' => '/how-to-play-badminton-blog.html'],
	['label' => 'Contact', 'label_zh' => '联系我们', 'href' => '/contact.html'],
	['label' => 'Disclaimer', 'label_zh' => '免责声明', 'href' => '/disclaimer'],
	['label' => 'Privacy Policy', 'label_zh' => '隐私政策', 'href' => '/privacy-policy.html'],
];

// The lead magnet: shown on the homepages and at the end of every article
// once a newsletter provider is configured (NEWSLETTER_FORM_ENDPOINT).
$newsletterEndpoint = SiteConfig::newsletterEndpoint();
$showNewsletter = $newsletterEndpoint !== '' && ($isHome || $isPost);
?>
	</main>
	<?php if ($showNewsletter): ?>
	<section class="newsletter-cta" aria-labelledby="newsletter-cta-heading">
		<div class="newsletter-cta-inner">
			<h2 id="newsletter-cta-heading"><?= $isChinese ? '免费领取 7 天羽毛球入门计划' : 'Get the free 7-day beginner badminton plan' ?></h2>
			<p><?= $isChinese
				? '每天一节简短课程：握拍、站位、步法、发球和基本击球，帮助你一周内打好基础。'
				: 'One short lesson a day - grip, stance, footwork, serve and the basic shots - to build a solid foundation in a week. Plus new guides as they are published.' ?></p>
			<form class="newsletter-form" method="post" action="<?= htmlspecialchars($newsletterEndpoint, ENT_QUOTES, 'UTF-8') ?>" data-track="newsletter-signup">
				<label class="screen-reader-text" for="newsletter-email"><?= $isChinese ? '电子邮件地址' : 'Email address' ?></label>
				<input id="newsletter-email" type="email" name="<?= htmlspecialchars(SiteConfig::newsletterEmailField(), ENT_QUOTES, 'UTF-8') ?>" required autocomplete="email" placeholder="<?= $isChinese ? '你的电子邮件' : 'Your email address' ?>" />
				<button type="submit"><?= $isChinese ? '免费获取' : 'Send me the plan' ?></button>
			</form>
			<p class="newsletter-note"><?= $isChinese ? '不发垃圾邮件，可随时退订。' : 'No spam. Unsubscribe at any time.' ?></p>
		</div>
	</section>
	<?php endif; ?>
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

<?php /* Theme scripts: local once mirrored, WP Engine until then - see App\Core\AssetUrl. */ ?>
<script src="<?= htmlspecialchars(AssetUrl::resolve('/wp-content/themes/gon/js/include_scripts.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
<script src="<?= htmlspecialchars(AssetUrl::resolve('/wp-content/themes/gon/js/main.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
<script src="<?= htmlspecialchars(AssetUrl::resolve('/wp-content/themes/gon/js/select2.min.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
<?php /* Stamped like the stylesheets - see $asset in templates/header.php. */ ?>
<script src="<?= htmlspecialchars($asset('/assets/js/home-ui.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
<script src="<?= htmlspecialchars($asset('/assets/js/category-page.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
<?php if ($isPost): ?>
<script src="<?= htmlspecialchars($asset('/assets/js/post-page.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
<?php endif; ?>
<?php /* Engagement events for GA4 / GTM: scroll depth, guide completion, outbound and affiliate clicks, form submissions. */ ?>
<script src="<?= htmlspecialchars($asset('/assets/js/analytics-events.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
</body>
</html>
