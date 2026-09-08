<?php
/**
 * Single shared footer for every page on the site. See templates/header.php
 * for the variables available in scope - Layout includes both templates
 * from one method, so the header's $navItems, $localize, $navLabel and
 * $chrome are still live here and the footer links stay in the reader's
 * language just like the navigation above them.
 *
 * Every column below is built from $navItems - the same data the header
 * nav renders from - rather than a second, separately maintained list, so
 * the footer can never drift out of step with what the site actually
 * links to.
 */

declare(strict_types=1);

$navByLabel = [];
foreach ($navItems as $item) {
	$navByLabel[$item['label']] = $item;
}

$footerColumns = [
	[
		'label' => 'Company',
		'label_zh' => '关于本站',
		'items' => [
			$navByLabel['Home'],
			$navByLabel['Blog'],
			$navByLabel['Category'],
			$navByLabel['Where to Play'],
			$navByLabel['Contact'],
			$navByLabel['Ask a Question'],
			$navByLabel['Store'],
			['label' => 'Disclaimer', 'label_zh' => '免责声明', 'href' => '/disclaimer'],
			['label' => 'Privacy Policy', 'label_zh' => '隐私政策', 'href' => '/privacy-policy.html'],
		],
	],
	[
		'label' => $navByLabel['Playing the Game']['label'],
		'label_zh' => $navByLabel['Playing the Game']['label_zh'],
		'items' => $navByLabel['Playing the Game']['children'],
	],
	[
		'label' => 'Equipment & Resources',
		'label_zh' => '装备与资源',
		'items' => array_merge($navByLabel['Equipment']['children'], $navByLabel['Resources']['children']),
	],
	[
		'label' => $navByLabel['Just for Fun']['label'],
		'label_zh' => $navByLabel['Just for Fun']['label_zh'],
		'items' => $navByLabel['Just for Fun']['children'],
	],
];
?>
	</div><!-- #main .wrapper -->
	<div class="clear"></div>

	<footer id="colophon" class="site-footer">
		<div class="site-footer-inner">
			<div class="site-footer-columns">
				<?php foreach ($footerColumns as $column): ?>
				<div class="site-footer-col">
					<p class="site-footer-col-title"><?= htmlspecialchars($navLabel($column), ENT_QUOTES, 'UTF-8') ?></p>
					<ul>
						<?php foreach ($column['items'] as $item): ?>
						<li><a href="<?= htmlspecialchars($localize($item['href']), ENT_QUOTES, 'UTF-8') ?>"<?= isset($item['target']) ? ' target="' . htmlspecialchars($item['target'], ENT_QUOTES, 'UTF-8') . '" rel="noopener"' : '' ?>><?= htmlspecialchars($navLabel($item), ENT_QUOTES, 'UTF-8') ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endforeach; ?>
			</div>

			<div class="site-footer-bottom">
				<strong>By David Tee,</strong> Copyright &copy; 2010-<?= date('Y') ?> <a href="<?= htmlspecialchars($localize('/'), ENT_QUOTES, 'UTF-8') ?>">masterbadminton.com</a>
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
