<?php

declare(strict_types=1);

/**
 * Карточка материала.
 *
 * Что изменилось против оригинала:
 *   • появились хлебные крошки, лид, теги, блок источника и «следующий/предыдущий»;
 *   • дата — полная, а не только «18:14»;
 *   • обложка и картинки в тексте идут через /convert-image, а не оригиналом;
 *   • добавлены JSON-LD NewsArticle, кнопки «поделиться» и прогресс чтения.
 */

$id = (int) ($routeParams['id'] ?? 0);
$article = article_find($id) ?? fail('article_not_found', ['id' => $id]);

$related = articles_related($article, 3);
$neighbours = article_neighbours($article);
$absolute = app_absolute_url((string) $article['url']);
$cover = (string) ($article['cover'] ?? '');
$tags = $article['tags'] ?? [];
$source = $article['source'] ?? null;

$description = (string) $article['lead'];
if ($description === '') {
	$description = mb_substr(trim(strip_tags((string) $article['body'])), 0, 200) . '…';
}

$schema = [
	'@context' => 'https://schema.org',
	'@type' => 'NewsArticle',
	'headline' => (string) $article['title'],
	'description' => $description,
	'datePublished' => (string) $article['iso'],
	'dateModified' => (string) $article['iso'],
	'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $absolute],
	'url' => $absolute,
	'inLanguage' => 'ru',
	'articleSection' => (string) $article['category_title'],
	'isAccessibleForFree' => true,
	'publisher' => [
		'@type' => 'NewsMediaOrganization',
		'name' => 'Соловей',
		'url' => app_absolute_url('/o-nas'),
	],
	'author' => [
		'@type' => 'Organization',
		'name' => $source['title'] ?? 'Редакция «Соловья»',
	],
];

if ($cover !== '') {
	$schema['image'] = [app_absolute_url(image_url($cover, 'og'))];
}

if ($tags !== []) {
	$schema['keywords'] = implode(', ', array_map(static fn (array $t): string => (string) $t['title'], $tags));
}

$this->layout('_app://components/layout/main', [
	'title' => (string) $article['title'],
	'description' => $description,
	'url' => (string) $article['url'],
	'image' => $cover !== '' ? image_url($cover, 'og') : '',
	'type' => 'article',
	'schema' => $schema,
]);

$this->start('page');
?>

<?php // Полоса прогресса чтения: единственная анимация, привязанная к прокрутке. ?>
<div
	class="fixed inset-x-0 top-0 z-50 h-0.5 origin-left bg-brand transition-transform duration-75"
	x-data="readingProgress"
	:style="`transform: scaleX(${progress})`"
	aria-hidden="true"
></div>

<div class="mx-auto max-w-7xl px-4 py-6 sm:py-8">
	<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-10">
		<div class="min-w-0">

			<?= $this->insert('_app://components/ui/breadcrumbs', ['items' => [
				['title' => 'Главная', 'url' => '/'],
				['title' => (string) $article['category_title'], 'url' => '/' . $article['category_slug']],
				['title' => (string) $article['title'], 'url' => null],
			]]) ?>

			<article>
				<header>
					<div class="flex flex-wrap items-center gap-2">
						<a
							href="/<?= e((string) $article['category_slug']) ?>"
							class="<?= e(clsx('rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 transition-opacity hover:opacity-80', accent_classes((string) $article['category_accent'])['badge'])) ?>"
						><?= e((string) $article['category_title']) ?></a>

						<?php if ((bool) $article['is_important']): ?>
							<span class="inline-flex items-center gap-1 rounded-full bg-alert-soft px-2.5 py-1 text-[11px] font-semibold text-alert">
								<?= $this->insert('bolt://ui-kit/icon', ['name' => 'flame', 'size' => 12]) ?>
								Важно
							</span>
						<?php endif; ?>

						<?php if ((bool) $article['is_longread']): ?>
							<span class="inline-flex items-center gap-1 rounded-full bg-paper-2 px-2.5 py-1 text-[11px] font-semibold text-ink-soft">
								<?= $this->insert('bolt://ui-kit/icon', ['name' => 'book', 'size' => 12]) ?>
								Лонгрид
							</span>
						<?php endif; ?>
					</div>

					<h1 class="mt-3 font-display text-2xl leading-[1.15] text-ink sm:text-3xl lg:text-4xl">
						<?= e((string) $article['title']) ?>
					</h1>

					<?php if ((string) $article['lead'] !== ''): ?>
						<p class="mt-3 max-w-3xl text-base leading-relaxed text-ink-soft sm:text-lg">
							<?= e((string) $article['lead']) ?>
						</p>
					<?php endif; ?>

					<div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 border-y border-line py-3 text-xs text-muted">
						<time datetime="<?= e((string) $article['iso']) ?>" class="inline-flex items-center gap-1.5 text-ink-soft">
							<?= $this->insert('bolt://ui-kit/icon', ['name' => 'calendar', 'size' => 13]) ?>
							<?= e((string) $article['human_date']) ?>
						</time>

						<span class="inline-flex items-center gap-1.5">
							<?= $this->insert('bolt://ui-kit/icon', ['name' => 'clock', 'size' => 13]) ?>
							<?= e((string) $article['reading_time']) ?> мин чтения
						</span>

						<span class="inline-flex items-center gap-1.5">
							<?= $this->insert('bolt://ui-kit/icon', ['name' => 'eye', 'size' => 13]) ?>
							<?= e(compact_number((int) $article['views'])) ?> просмотров
						</span>

						<?php // «Поделиться» — системный диалог на мобильном, копирование на десктопе. ?>
						<div class="ml-auto flex items-center gap-1" x-data="shareButton('<?= e($absolute) ?>', '<?= e((string) $article['title']) ?>')">
							<button
								type="button"
								class="inline-flex items-center gap-1.5 rounded-full border border-line px-3 py-1.5 text-xs text-ink-soft transition-colors hover:border-brand/40 hover:text-brand"
								@click="share()"
							>
								<?= $this->insert('bolt://ui-kit/icon', ['name' => 'share', 'size' => 13]) ?>
								<span x-show="!copied">Поделиться</span>
								<span x-show="copied" x-cloak class="text-brand">Ссылка скопирована</span>
							</button>
						</div>
					</div>
				</header>

				<?php if ($cover !== ''): ?>
					<figure class="mt-6 overflow-hidden rounded-2xl border border-line">
						<img
							src="<?= e(image_url($cover, 'cover')) ?>"
							alt="<?= e((string) $article['title']) ?>"
							width="1200" height="750"
							class="h-auto w-full object-cover"
							fetchpriority="high"
						>
					</figure>
				<?php endif; ?>

				<div class="prose-news mt-6">
					<?= article_body((string) $article['body']) ?>
				</div>

				<?php if ($source !== null && (string) $source['title'] !== ''): ?>
					<div class="mt-8 flex flex-wrap items-center gap-3 rounded-xl border border-line bg-paper-2/50 px-4 py-3 text-sm">
						<span class="text-xs uppercase tracking-[0.12em] text-muted">Источник</span>
						<a href="<?= e((string) $source['url']) ?>" class="group inline-flex items-center gap-1.5 font-medium text-ink transition-colors hover:text-brand">
							<?= e((string) $source['title']) ?>
							<span class="transition-transform duration-200 group-hover:translate-x-0.5">
								<?= $this->insert('bolt://ui-kit/icon', ['name' => 'external', 'size' => 13]) ?>
							</span>
						</a>
						<span class="rounded-full bg-white px-2 py-0.5 text-[11px] text-muted"><?= e((string) $source['kind_label']) ?></span>
					</div>
				<?php endif; ?>

				<?php if ($tags !== []): ?>
					<div class="mt-6 flex flex-wrap items-center gap-2">
						<span class="text-xs uppercase tracking-[0.12em] text-muted">Темы</span>
						<?php foreach ($tags as $tag): ?>
							<a href="<?= e((string) $tag['url']) ?>" class="rounded-full border border-line bg-white/60 px-3 py-1 text-xs text-ink-soft transition-colors hover:border-brand/40 hover:text-brand">
								<?= e((string) $tag['title']) ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php // Кнопки соцсетей: прямые ссылки, без трекеров. ?>
				<div class="mt-8 rounded-2xl border border-line bg-white/60 p-4">
					<p class="text-xs font-semibold uppercase tracking-[0.12em] text-muted">Рассказать</p>
					<div class="mt-3 flex flex-wrap gap-2">
						<?php
						$shareUrl = rawurlencode($absolute);
						$shareTitle = rawurlencode((string) $article['title']);
						$networks = [
							['title' => 'Telegram', 'icon' => 'telegram', 'href' => 'https://t.me/share/url?url=' . $shareUrl . '&text=' . $shareTitle],
							['title' => 'ВКонтакте', 'icon' => 'vk', 'href' => 'https://vk.com/share.php?url=' . $shareUrl],
							['title' => 'Одноклассники', 'icon' => 'ok', 'href' => 'https://connect.ok.ru/offer?url=' . $shareUrl],
						];
						?>
						<?php foreach ($networks as $network): ?>
							<a
								href="<?= e($network['href']) ?>"
								target="_blank"
								rel="noopener noreferrer"
								class="inline-flex items-center gap-2 rounded-full border border-line px-3.5 py-2 text-xs font-medium text-ink-soft transition-colors hover:border-brand/40 hover:text-brand"
							>
								<?= $this->insert('bolt://ui-kit/icon', ['name' => $network['icon'], 'size' => 14]) ?>
								<?= e($network['title']) ?>
							</a>
						<?php endforeach; ?>

						<button
							type="button"
							x-data="shareButton('<?= e($absolute) ?>', '<?= e((string) $article['title']) ?>')"
							@click="share()"
							class="inline-flex items-center gap-2 rounded-full border border-line px-3.5 py-2 text-xs font-medium text-ink-soft transition-colors hover:border-brand/40 hover:text-brand"
						>
							<?= $this->insert('bolt://ui-kit/icon', ['name' => 'link', 'size' => 14]) ?>
							<span x-show="!copied">Скопировать ссылку</span>
							<span x-show="copied" x-cloak class="text-brand">Готово</span>
						</button>
					</div>
				</div>

				<?php // Соседние материалы: раньше их не было вообще, читатель упирался в тупик. ?>
				<?php if ($neighbours['prev'] !== null || $neighbours['next'] !== null): ?>
					<nav class="mt-8 grid gap-3 sm:grid-cols-2" aria-label="Соседние материалы">
						<?php if ($neighbours['prev'] !== null): ?>
							<a href="<?= e((string) $neighbours['prev']['url']) ?>" class="group rounded-xl border border-line bg-white/60 p-4 transition-colors hover:border-line-strong">
								<span class="flex items-center gap-1.5 text-[11px] uppercase tracking-[0.12em] text-muted">
									<?= $this->insert('bolt://ui-kit/icon', ['name' => 'arrow-left', 'size' => 12]) ?>
									Предыдущий материал
								</span>
								<span class="mt-1.5 block line-clamp-2 text-sm font-medium leading-snug text-ink group-hover:text-brand">
									<?= e((string) $neighbours['prev']['title']) ?>
								</span>
							</a>
						<?php endif; ?>

						<?php if ($neighbours['next'] !== null): ?>
							<a href="<?= e((string) $neighbours['next']['url']) ?>" class="group rounded-xl border border-line bg-white/60 p-4 text-right transition-colors hover:border-line-strong sm:col-start-2">
								<span class="flex items-center justify-end gap-1.5 text-[11px] uppercase tracking-[0.12em] text-muted">
									Следующий материал
									<?= $this->insert('bolt://ui-kit/icon', ['name' => 'arrow-right', 'size' => 12]) ?>
								</span>
								<span class="mt-1.5 block line-clamp-2 text-sm font-medium leading-snug text-ink group-hover:text-brand">
									<?= e((string) $neighbours['next']['title']) ?>
								</span>
							</a>
						<?php endif; ?>
					</nav>
				<?php endif; ?>
			</article>

			<?php if ($related !== []): ?>
				<section class="mt-10" aria-labelledby="related-title">
					<h2 id="related-title" class="mb-4 border-b border-line pb-3 font-display text-xl text-ink">По теме</h2>
					<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
						<?php foreach ($related as $item): ?>
							<?= article_card($item) ?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>
		</div>

		<div class="min-w-0">
			<?= $this->insert('_app://components/layout/live', ['limit' => 12, 'title' => 'Лента']) ?>
		</div>
	</div>
</div>
<?php $this->stop() ?>
