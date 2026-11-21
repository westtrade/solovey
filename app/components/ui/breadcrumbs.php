<?php

declare(strict_types=1);

/**
 * Хлебные крошки: список `[['title' => …, 'url' => …], …]`, последний — текущий.
 *
 * @param list<array{title: string, url?: string|null}> $items
 * @param bool $schema печатать ли микроразметку BreadcrumbList
 */

$items ??= [];
$schema ??= true;

if ($items === []) {
	return;
}
?>
<nav aria-label="Хлебные крошки" class="mb-4">
	<ol class="flex flex-wrap items-center gap-1.5 text-xs text-muted" <?= $schema ? 'itemscope itemtype="https://schema.org/BreadcrumbList"' : '' ?>>
		<?php foreach ($items as $index => $item): ?>
			<?php $isLast = $index === count($items) - 1; ?>
			<li
				class="flex items-center gap-1.5"
				<?= $schema ? 'itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"' : '' ?>
			>
				<?php if (!$isLast && isset($item['url']) && $item['url'] !== null): ?>
					<a href="<?= e($item['url']) ?>" class="transition-colors hover:text-ink" <?= $schema ? 'itemprop="item"' : '' ?>>
						<span <?= $schema ? 'itemprop="name"' : '' ?>><?= e($item['title']) ?></span>
					</a>
				<?php else: ?>
					<span class="text-ink-soft" <?= $schema ? 'itemprop="name"' : '' ?>><?= e($item['title']) ?></span>
				<?php endif; ?>

				<?php if ($schema): ?>
					<meta itemprop="position" content="<?= $index + 1 ?>">
				<?php endif; ?>

				<?php if (!$isLast): ?>
					<span aria-hidden="true" class="text-line-strong">/</span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
