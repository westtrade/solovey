<?php

declare(strict_types=1);

/**
 * Заголовок секции: название, подпись, ссылка «все».
 *
 * @param string      $title
 * @param string|null $lead
 * @param string|null $href
 * @param string|null $linkLabel
 * @param string|null $icon
 */

$title ??= '';
$lead ??= null;
$href ??= null;
$linkLabel ??= 'Все материалы';
$icon ??= null;
?>
<div class="mb-5 flex items-end justify-between gap-4 border-b border-line pb-3">
	<div class="flex items-center gap-2.5">
		<?php if ($icon !== null): ?>
			<span class="flex h-7 w-7 items-center justify-center rounded-lg bg-ink text-paper">
				<?= $this->insert('bolt://ui-kit/icon', ['name' => $icon, 'size' => 15]) ?>
			</span>
		<?php endif; ?>

		<div>
			<h2 class="font-display text-xl leading-none text-ink sm:text-2xl"><?= e($title) ?></h2>
			<?php if ($lead !== null): ?>
				<p class="mt-1.5 text-xs text-muted"><?= e($lead) ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php if ($href !== null): ?>
		<a href="<?= e($href) ?>" class="group inline-flex shrink-0 items-center gap-1.5 text-xs font-medium text-muted transition-colors hover:text-brand">
			<?= e($linkLabel) ?>
			<span class="transition-transform duration-200 group-hover:translate-x-0.5">
				<?= $this->insert('bolt://ui-kit/icon', ['name' => 'arrow-right', 'size' => 13]) ?>
			</span>
		</a>
	<?php endif; ?>
</div>
