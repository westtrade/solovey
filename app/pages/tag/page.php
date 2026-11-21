<?php

declare(strict_types=1);

/**
 * Тема: сквозная подборка по ключевому слову.
 *
 * В оригинале тем не было вовсе: связать материалы про авиационную
 * опасность или отключения воды можно было только поиском.
 */

$slug = (string) ($routeParams['slug'] ?? $slug ?? '');
$tag = tag_find($slug) ?? fail('tag_not_found', ['slug' => $slug]);

$total = (int) $tag['total'];
$title = 'Тема «' . $tag['title'] . '» — Соловей';
$description = 'Все материалы по теме «' . $tag['title'] . '»: '
	. ru_count($total, 'материал', 'материала', 'материалов') . ' из Курска и области.';

$this->layout('_app://components/layout/main', [
	'title' => $title,
	'description' => $description,
	'url' => $tag['url'],
]);

$this->start('page');
?>

<?= $this->insert('_app://components/news/listing', [
	'title' => 'Тема: ' . $tag['title'],
	'lead' => ru_count($total, 'материал', 'материала', 'материалов') . ' по этой теме',
	'icon' => 'tag',
	'loader' => feed_loader(null, null, null, (int) $tag['id']),
	'name' => 'tag-' . $slug,
	'showFeedsTabs' => false,
	'breadcrumbs' => [
		['title' => 'Главная', 'url' => '/'],
		['title' => 'Темы', 'url' => null],
		['title' => $tag['title'], 'url' => null],
	],
]) ?>

<?php $this->stop() ?>
