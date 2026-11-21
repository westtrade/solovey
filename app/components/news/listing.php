<?php

declare(strict_types=1);

/**
 * Шаблон списка материалов: заголовок, чипы лент, сетка карточек, колонка «Сейчас».
 *
 * Один шаблон на 18 URL вместо восемнадцати почти одинаковых страниц
 * оригинала: различаются только подпись, источник данных и активный чип.
 *
 * @param string            $title
 * @param string|null       $lead
 * @param callable          $loader   загрузчик для пагинации
 * @param string            $name     имя ленты пагинации (уникально на странице)
 * @param list<array>       $breadcrumbs
 * @param string|null       $empty
 * @param string|null       $icon
 * @param bool              $showFeedsTabs
 * @param int               $limit
 */

$title ??= '';
$lead ??= null;
$loader ??= null;
$name ??= 'listing';
$breadcrumbs ??= [];
$empty ??= 'Здесь пока нет материалов.';
$icon ??= null;
$showFeedsTabs ??= true;
$limit ??= 12;

if (!is_callable($loader)) {
	return;
}
?>
<div class="mx-auto max-w-7xl px-4 py-6 sm:py-8">

	<?php if ($breadcrumbs !== []): ?>
		<?= $this->insert('_app://components/ui/breadcrumbs', ['items' => $breadcrumbs]) ?>
	<?php endif; ?>

	<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-10">
		<div class="min-w-0">

			<header class="mb-5">
				<div class="flex items-center gap-2.5">
					<?php if ($icon !== null): ?>
						<span class="flex h-9 w-9 items-center justify-center rounded-xl bg-ink text-paper">
							<?= $this->insert('bolt://ui-kit/icon', ['name' => $icon, 'size' => 18]) ?>
						</span>
					<?php endif; ?>

					<h1 class="font-display text-2xl leading-tight text-ink sm:text-3xl"><?= e($title) ?></h1>
				</div>

				<?php if ($lead !== null): ?>
					<p class="mt-2 max-w-2xl text-sm leading-relaxed text-ink-soft"><?= e($lead) ?></p>
				<?php endif; ?>
			</header>

			<?php if ($showFeedsTabs): ?>
				<?php // Переключатель лент: в оригинале это было выпадающее меню в шапке. ?>
				<nav aria-label="Другие ленты" class="mb-6 -mx-1 flex gap-1.5 overflow-x-auto px-1 pb-1 no-scrollbar">
					<?php foreach (site_feeds() as $feed): ?>
						<a
							href="<?= e($feed['url']) ?>"
							class="<?= e(clsx(
								'flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1.5 text-xs font-medium transition-colors',
								nav_active($feed['url'])
									? 'border-ink bg-ink text-paper'
									: 'border-line bg-white/60 text-ink-soft hover:border-line-strong hover:text-ink',
							)) ?>"
						>
							<?= $this->insert('bolt://ui-kit/icon', ['name' => $feed['icon'], 'size' => 13, 'class' => 'opacity-70']) ?>
							<?= e($feed['title']) ?>
						</a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

			<?= $this->insert('bolt://ui-kit/pagination', [
				'name' => $name,
				'loader' => $loader,
				'limit' => $limit,
				'idField' => 'id',
				'class' => 'grid gap-4 sm:grid-cols-2',
				'item' => static function (array $attrs, array $meta): void {
					echo article_card($attrs, 'default', ['id' => $attrs['id'], 'class' => $attrs['class']]);
				},
				'trigger' => static function (array $items): void {
					foreach ($items as $item) {
						$attrs = $item['attrs'] ?? [];
						$attrs['class'] = clsx(
							$attrs['class'] ?? null,
							'group mx-auto flex w-full max-w-xs items-center justify-center gap-2 rounded-full border border-line bg-white/70 px-5 py-2.5 text-sm font-medium text-ink-soft transition-colors hover:border-brand/40 hover:text-brand',
						);
						echo '<div class="col-span-full mt-2"><a ' . attrs($attrs) . '><span>Показать ещё</span></a></div>';
					}
				},
			]) ?>

			<p class="mt-6 text-xs text-muted">
				<?= e($empty) ?>
			</p>
		</div>

		<div class="min-w-0">
			<?= $this->insert('_app://components/layout/live', ['limit' => 14]) ?>
		</div>
	</div>
</div>
