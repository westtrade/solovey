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
 * @param string      $tone  light | dark (чернильная плашка)
 */

$title ??= '';
$lead ??= null;
$href ??= null;
$linkLabel ??= 'Все материалы';
$icon ??= null;
$tone ??= 'light';
$dark = $tone === 'dark';
?>
<div class="<?= e(clsx('mb-5 flex items-end justify-between gap-4 border-b-2 pb-2.5', $dark ? 'border-paper/25' : 'border-ink/85')) ?>">
	<div class="flex items-center gap-2.5">
		<?php if ($icon !== null): ?>
			<span class="<?= e(clsx('flex h-7 w-7 items-center justify-center rounded-lg', $dark ? 'bg-paper text-ink' : 'bg-ink text-paper')) ?>">
				<?= $this->insert('bolt://ui-kit/icon', ['name' => $icon, 'size' => 15]) ?>
			</span>
		<?php endif; ?>

		<div>
			<h2 class="<?= e(clsx('font-display text-xl leading-none sm:text-2xl', $dark ? 'text-paper' : 'text-ink')) ?>"><?= e($title) ?></h2>
			<?php if ($lead !== null): ?>
				<p class="<?= e(clsx('mt-1.5 text-xs', $dark ? 'text-paper/60' : 'text-muted')) ?>"><?= e($lead) ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php if ($href !== null): ?>
		<a href="<?= e($href) ?>" class="<?= e(clsx('group inline-flex shrink-0 items-center gap-1.5 text-xs font-medium transition-colors', $dark ? 'text-paper/60 hover:text-paper' : 'text-muted hover:text-brand')) ?>">
			<?= e($linkLabel) ?>
			<span class="transition-transform duration-200 group-hover:translate-x-0.5">
				<?= $this->insert('bolt://ui-kit/icon', ['name' => 'arrow-right', 'size' => 13]) ?>
			</span>
		</a>
	<?php endif; ?>
</div>
