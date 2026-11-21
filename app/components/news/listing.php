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
 * @param int|null          $total    всего материалов в выборке
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
$total ??= null;

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

			<?php if ($total === 0): ?>
				<?php // Пустая подборка: вместо сетки и кнопки — объяснение и выход. ?>
				<div class="rounded-2xl border border-dashed border-line bg-white/50 px-6 py-14 text-center">
					<span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-paper-2 text-muted">
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'grid', 'size' => 18]) ?>
					</span>
					<p class="mt-4 font-display text-lg text-ink">Здесь пока пусто</p>
					<p class="mx-auto mt-1.5 max-w-sm text-sm leading-relaxed text-muted"><?= e($empty) ?></p>
					<div class="mt-5 flex flex-wrap items-center justify-center gap-2">
						<a href="/lenta" class="inline-flex items-center gap-1.5 rounded-full bg-ink px-4 py-2 text-xs font-medium text-paper transition-colors hover:bg-ink-soft">
							<?= $this->insert('bolt://ui-kit/icon', ['name' => 'list', 'size' => 13]) ?>
							Вся лента
						</a>
						<a href="/" class="inline-flex items-center gap-1.5 rounded-full border border-line bg-white/70 px-4 py-2 text-xs font-medium text-ink-soft transition-colors hover:border-line-strong hover:text-ink">
							На главную
						</a>
					</div>
				</div>
			<?php else: ?>
				<h2 class="sr-only">Материалы: <?= e($title) ?></h2>

				<?= $this->insert('bolt://ui-kit/pagination', [
					'name' => $name,
					'loader' => $loader,
					'limit' => $limit,
					'idField' => 'id',
					'class' => 'grid gap-4 sm:grid-cols-2',
					'item' => static function (array $attrs, array $meta): void {
						echo article_card($attrs, 'default', ['id' => $attrs['id'], 'class' => $attrs['class']]);
					},
					'trigger' => pagination_trigger(),
				]) ?>
			<?php endif; ?>
		</div>

		<div class="min-w-0">
			<?= $this->insert('_app://components/layout/live', ['limit' => 14]) ?>
		</div>
	</div>
</div>
