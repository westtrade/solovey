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
					<div class="mb-3 flex items-center gap-3">
						<h2 id="hero-title" class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink">
							<span class="text-alert"><?= $this->insert('bolt://ui-kit/icon', ['name' => 'flame', 'size' => 13]) ?></span>
							Главное за сутки
						</h2>
						<span class="h-0.5 flex-1 bg-ink/85" aria-hidden="true"></span>
						<span class="hidden font-mono text-xs text-muted sm:block"><?= e(ru_date(date('c'))) ?></span>
					</div>

					<?= article_card($hero, 'hero') ?>
				</section>
			<?php endif; ?>

			<?php // На телефоне колонка «Сейчас» стоит сразу после главного: иначе до неё шесть экранов. ?>
			<div class="mt-6 lg:hidden">
				<?= $this->insert('_app://components/layout/live', ['limit' => 8, 'sticky' => false]) ?>
			</div>

			<?php // ─── Рубрики ───────────────────────────────────────── ?>
			<section class="mt-10" aria-labelledby="categories-title">
				<h2 id="categories-title" class="sr-only">Рубрики</h2>

				<?php // Чипы по ширине содержимого: в жёсткой сетке «Происшествия 17» не влезали. ?>
				<div class="flex flex-wrap gap-2">
					<?php foreach ($categories as $category): ?>
						<?php $accent = accent_classes($category['accent']); ?>
						<a
							href="<?= e($category['url']) ?>"
							class="group inline-flex items-baseline gap-2 rounded-lg border border-line bg-white/60 px-3 py-2 transition-colors duration-200 hover:border-ink hover:bg-ink"
						>
							<span class="text-[13px] font-medium leading-tight text-ink transition-colors group-hover:text-paper"><?= e($category['title']) ?></span>
							<span class="font-mono text-xs tabular-nums text-muted transition-colors group-hover:text-paper/60"><?= e(compact_number($category['total'])) ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>

			<?php // ─── Лента ─────────────────────────────────────────── ?>
			<section class="mt-10" aria-labelledby="latest-title">
				<div class="mb-5 flex items-end justify-between gap-4 border-b-2 border-ink/85 pb-2.5">
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
				<?php // Чернильная плашка: вторая контрастная точка страницы после шапки. ?>
				<section class="mt-10 rounded-xl bg-ink p-5 sm:p-6" aria-labelledby="important-title">
					<?= $this->insert('_app://components/ui/section-head', [
						'title' => 'Важное',
						'lead' => 'Материалы, на которые стоит обратить внимание',
						'href' => '/hot',
						'linkLabel' => 'Все важное',
						'icon' => 'flame',
						'tone' => 'dark',
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
		<div class="hidden min-w-0 lg:block">
			<?= $this->insert('_app://components/layout/live', ['limit' => 14]) ?>
		</div>
	</div>
</div>
<?php $this->stop() ?>
