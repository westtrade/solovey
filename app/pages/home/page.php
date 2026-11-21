<?php

declare(strict_types=1);

/**
 * Главная: главное за сутки, лента, рубрики, важное.
 *
 * Отличия от оригинала: вместо одного героя и «бесконечной» ленты без
 * заголовка — три осмысленных блока с разной ролью. Первый экран отвечает
 * «что случилось», второй — «что ещё», третий — «куда смотреть дальше».
 */

use Cake\ORM\Query\SelectQuery;

$hero = article_hero();
$latest = articles_page(9);
$important = articles_page(3, 0, 'hot')['items'];
$categories = categories_all();
$stats = site_stats();

$title = 'Новости Курска и Курской области';
$description = 'Что случилось в Курске и области: главное за сутки, лента городских новостей, '
	. 'официальные сообщения и материалы районных изданий. '
	. ru_count($stats['articles'], 'материал', 'материала', 'материалов') . ', '
	. ru_count($stats['sources'], 'источник', 'источника', 'источников') . '.';
$url = '/';
$image = $hero !== null ? (string) $hero['cover'] : '';

$schema = [
	'@context' => 'https://schema.org',
	'@type' => 'WebSite',
	'name' => 'Соловей',
	'url' => app_absolute_url('/'),
	'description' => $description,
	'publisher' => [
		'@type' => 'NewsMediaOrganization',
		'name' => 'Соловей',
		'url' => app_absolute_url('/o-nas'),
	],
	'potentialAction' => [
		'@type' => 'SearchAction',
		'target' => app_absolute_url('/poisk') . '?q={search_term_string}',
		'query-input' => 'required name=search_term_string',
	],
];

$this->layout('_app://components/layout/main', [
	'title' => $title,
	'description' => $description,
	'url' => $url,
	'image' => $image,
	'schema' => $schema,
]);
?>

<?php $this->start('page') ?>
<div class="mx-auto max-w-7xl px-4 py-6 sm:py-8">

	<h1 class="sr-only">Новости Курска и Курской области</h1>

	<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-10">

		<div class="min-w-0">

			<?php // ─── Главное за сутки ─────────────────────────────── ?>
			<?php if ($hero !== null): ?>
				<section aria-labelledby="hero-title">
					<div class="mb-4 flex items-center gap-2">
						<span class="inline-flex items-center gap-1.5 rounded-full bg-ink px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-paper">
							<?= $this->insert('bolt://ui-kit/icon', ['name' => 'flame', 'size' => 12]) ?>
							Главное за сутки
						</span>
						<span class="h-px flex-1 bg-line" aria-hidden="true"></span>
					</div>

					<h2 id="hero-title" class="sr-only">Главное за сутки</h2>

					<?= article_card($hero, 'hero') ?>
				</section>
			<?php endif; ?>

			<?php // ─── Рубрики ───────────────────────────────────────── ?>
			<section class="mt-10" aria-labelledby="categories-title">
				<h2 id="categories-title" class="sr-only">Рубрики</h2>

				<div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
					<?php foreach ($categories as $category): ?>
						<?php $accent = accent_classes($category['accent']); ?>
						<a
							href="<?= e($category['url']) ?>"
							class="group flex flex-col gap-1 rounded-xl border border-line bg-white/60 px-3 py-2.5 transition-all duration-200 hover:-translate-y-0.5 hover:border-line-strong hover:shadow-[var(--shadow-card)]"
						>
							<span class="<?= e(clsx('h-1.5 w-6 rounded-full transition-all duration-300 group-hover:w-9', $accent['dot'])) ?>"></span>
							<span class="mt-0.5 text-[13px] font-medium leading-tight text-ink"><?= e($category['title']) ?></span>
							<span class="text-[11px] text-muted"><?= e(compact_number($category['total'])) ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>

			<?php // ─── Лента ─────────────────────────────────────────── ?>
			<section class="mt-10" aria-labelledby="latest-title">
				<div class="mb-5 flex items-end justify-between gap-4 border-b border-line pb-3">
					<div>
						<h2 id="latest-title" class="font-display text-xl text-ink sm:text-2xl">Последние новости</h2>
						<p class="mt-1.5 text-xs text-muted">Обновляется по мере выхода материалов</p>
					</div>
					<a href="/lenta" class="group inline-flex shrink-0 items-center gap-1.5 text-xs font-medium text-muted transition-colors hover:text-brand">
						Вся лента
						<span class="transition-transform duration-200 group-hover:translate-x-0.5">
							<?= $this->insert('bolt://ui-kit/icon', ['name' => 'arrow-right', 'size' => 13]) ?>
						</span>
					</a>
				</div>

				<?= $this->insert('bolt://ui-kit/pagination', [
					'name' => 'home-latest',
					'loader' => feed_loader(null),
					'limit' => 9,
					'class' => 'grid gap-4 sm:grid-cols-2',
					'idField' => 'id',
					'item' => static function (array $attrs, array $meta): void {
						echo article_card($attrs, 'default', ['id' => $attrs['id'], 'class' => $attrs['class']]);
					},
					'trigger' => pagination_trigger(),
				]) ?>
			</section>

			<?php // ─── Важное ────────────────────────────────────────── ?>
			<?php if ($important !== []): ?>
				<section class="mt-10" aria-labelledby="important-title">
					<?= $this->insert('_app://components/ui/section-head', [
						'title' => 'Важное',
						'lead' => 'Материалы, на которые стоит обратить внимание',
						'href' => '/hot',
						'linkLabel' => 'Все важное',
						'icon' => 'flame',
					]) ?>

					<h2 id="important-title" class="sr-only">Важное</h2>

					<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
						<?php foreach ($important as $article): ?>
							<?= article_card($article) ?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>
		</div>

		<?php // ─── Правая колонка ───────────────────────────────────── ?>
		<div class="min-w-0">
			<?= $this->insert('_app://components/layout/live', ['limit' => 14]) ?>
		</div>
	</div>
</div>
<?php $this->stop() ?>
