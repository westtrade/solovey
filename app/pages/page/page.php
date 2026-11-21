<?php

declare(strict_types=1);

/**
 * Информационная страница: о проекте, контакты, редакционная политика,
 * конфиденциальность, правила.
 *
 * Пять отдельных HTML-файлов оригинала стали одной страницей и данными в
 * базе: текст правится без вёрстки, а крошки и подвал общие.
 */

$slug = (string) ($route['slug'] ?? '');
$page = page_find($slug) ?? fail('page_not_found', ['slug' => $slug]);

$this->layout('_app://components/layout/main', [
	'title' => (string) $page['title'],
	'description' => (string) $page['lead'],
	'url' => '/' . $slug,
]);

$this->start('page');
?>
<div class="mx-auto max-w-3xl px-4 py-8 sm:py-12">

	<?= $this->insert('_app://components/ui/breadcrumbs', ['items' => [
		['title' => 'Главная', 'url' => '/'],
		['title' => (string) $page['title'], 'url' => null],
	]]) ?>

	<article>
		<h1 class="font-display text-2xl leading-tight text-ink sm:text-3xl"><?= e((string) $page['title']) ?></h1>

		<?php if ((string) $page['lead'] !== ''): ?>
			<p class="mt-3 text-base leading-relaxed text-ink-soft"><?= e((string) $page['lead']) ?></p>
		<?php endif; ?>

		<?php if ((string) ($page['updated_label'] ?? '') !== ''): ?>
			<p class="mt-3 text-xs text-muted">Обновлено <?= e((string) $page['updated_label']) ?></p>
		<?php endif; ?>

		<div class="prose-news mt-6">
			<?= text_to_html((string) $page['body']) ?>
		</div>
	</article>

	<div class="mt-10 rounded-2xl border border-line bg-paper-2/50 p-5">
		<p class="text-sm text-ink-soft">
			Не нашли ответ? Напишите в редакцию —
			<a href="/kontakty" class="<?= link_underline() ?> font-medium text-brand">контакты и почта</a>.
		</p>
	</div>
</div>
<?php $this->stop() ?>
