<?php

declare(strict_types=1);

/**
 * Поиск по заголовкам, лиду и тексту.
 *
 * В оригинале стоял внешний виджет Яндекса, который отдавал 404 без
 * параметров и не индексировал собственные материалы. Здесь поиск свой,
 * по своей базе, с честным «ничего не найдено» и подсказками.
 */

$term = trim((string) ($_GET['q'] ?? ''));
$term = preg_replace('/\s+/u', ' ', $term) ?? '';
$tooShort = $term !== '' && mb_strlen($term) < 2;
$hasQuery = $term !== '' && !$tooShort;

$total = $hasQuery ? articles_page(1, 0, null, null, $term)['total'] : 0;

$title = $hasQuery
	? 'Поиск: ' . $term . ' — ' . ru_count($total, 'материал', 'материала', 'материалов')
	: 'Поиск по материалам — Соловей';
$description = $hasQuery
	? 'Результаты поиска по запросу «' . $term . '»: ' . ru_count($total, 'материал', 'материала', 'материалов') . '.'
	: 'Поиск по новостям Курска и области: заголовки, лиды и тексты материалов.';

$this->layout('_app://components/layout/main', [
	'title' => $title,
	'description' => $description,
	'url' => '/poisk',
	'robots' => 'noindex,follow',
]);

$this->start('page');
?>
<div class="mx-auto max-w-3xl px-4 py-8 sm:py-12">

	<?= $this->insert('_app://components/ui/breadcrumbs', ['items' => [
		['title' => 'Главная', 'url' => '/'],
		['title' => 'Поиск', 'url' => null],
	]]) ?>

	<h1 class="font-display text-2xl leading-tight text-ink sm:text-3xl">Поиск по материалам</h1>
	<p class="mt-2 text-sm text-ink-soft">
		Ищем по заголовкам, лидам и текстам. Можно ввести тему, район или название источника.
	</p>

	<form action="/poisk" method="get" role="search" class="mt-5">
		<div class="relative">
			<label for="q" class="sr-only">Поисковый запрос</label>
			<?= $this->insert('bolt://ui-kit/icon', ['name' => 'search', 'size' => 18, 'class' => 'pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-muted']) ?>
			<input
				id="q"
				type="search"
				name="q"
				value="<?= e($term) ?>"
				placeholder="Например: авиационная опасность"
				autocomplete="off"
				autofocus
				class="w-full rounded-2xl border border-line bg-white py-3.5 pl-11 pr-28 text-base text-ink placeholder:text-muted/80 focus:border-brand focus:outline-none focus:ring-4 focus:ring-brand/10"
			>
			<button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-xl bg-ink px-4 py-2 text-sm font-medium text-paper transition-colors hover:bg-brand-dark">
				Найти
			</button>
		</div>
	</form>

	<?php if ($tooShort): ?>
		<p class="mt-4 rounded-xl border border-alert/20 bg-alert-soft/60 px-4 py-3 text-sm text-alert">
			Запрос слишком короткий: нужно хотя бы два символа.
		</p>
	<?php endif; ?>

	<?php if ($hasQuery): ?>
		<p class="mt-6 text-sm text-muted">
			<?php if ($total > 0): ?>
				Найдено <?= e(ru_count($total, 'материал', 'материала', 'материалов')) ?> по запросу
				<span class="font-medium text-ink">«<?= e($term) ?>»</span>
			<?php else: ?>
				По запросу <span class="font-medium text-ink">«<?= e($term) ?>»</span> ничего не нашлось.
			<?php endif; ?>
		</p>

		<?php if ($total > 0): ?>
			<div class="mt-4 divide-y divide-line">
				<?= $this->insert('bolt://ui-kit/pagination', [
					'name' => 'search-results',
					'loader' => feed_loader(null, null, $term),
					'limit' => 10,
					'idField' => 'id',
					'class' => 'flex flex-col',
					'item' => static function (array $attrs, array $meta): void {
						echo article_card($attrs, 'row', ['id' => $attrs['id'], 'class' => $attrs['class']]);
					},
					'trigger' => static function (array $items): void {
						foreach ($items as $item) {
							$attrs = $item['attrs'] ?? [];
							$attrs['class'] = clsx(
								$attrs['class'] ?? null,
								'mx-auto mt-4 flex w-full max-w-xs items-center justify-center gap-2 rounded-full border border-line bg-white/70 px-5 py-2.5 text-sm font-medium text-ink-soft transition-colors hover:border-brand/40 hover:text-brand',
							);
							echo '<div class="pt-4"><a ' . attrs($attrs) . '><span>Показать ещё</span></a></div>';
						}
					},
				]) ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php // Подсказки: свежие темы и рубрики — чтобы поиск не был пустым полем. ?>
	<section class="mt-10">
		<h2 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">Частые темы</h2>
		<div class="mt-3 flex flex-wrap gap-2">
			<?php foreach (tags_popular(10) as $tag): ?>
				<a href="<?= e($tag['url']) ?>" class="rounded-full border border-line bg-white/60 px-3 py-1.5 text-xs text-ink-soft transition-colors hover:border-brand/40 hover:text-brand">
					<?= e($tag['title']) ?>
				</a>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="mt-8">
		<h2 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">Рубрики</h2>
		<div class="mt-3 flex flex-wrap gap-2">
			<?php foreach (categories_all() as $category): ?>
				<a href="<?= e($category['url']) ?>" class="flex items-center gap-2 rounded-full border border-line bg-white/60 px-3 py-1.5 text-xs text-ink-soft transition-colors hover:border-brand/40 hover:text-brand">
					<span class="<?= e(clsx('h-1.5 w-1.5 rounded-full', accent_classes($category['accent'])['dot'])) ?>"></span>
					<?= e($category['title']) ?>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
</div>
<?php $this->stop() ?>
