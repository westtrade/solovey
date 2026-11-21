<?php

declare(strict_types=1);

/**
 * Рубрика: «Общество», «Происшествия» и остальные разделы.
 */

$slug = (string) ($route['slug'] ?? '');
$category = category_find($slug) ?? fail('category_not_found', ['slug' => $slug]);

$total = (int) $category['total'];
$title = $category['title'] . ' — новости Курска и области';
$description = $category['lead'] !== ''
	? $category['lead']
	: $category['title'] . ': ' . ru_count($total, 'материал', 'материала', 'материалов') . ' из Курска и области.';

$this->layout('_app://components/layout/main', [
	'title' => $title,
	'description' => $description,
	'url' => $category['url'],
]);

$this->start('page');
?>

<?= $this->insert('_app://components/news/listing', [
	'title' => $category['title'],
	'lead' => $description,
	'loader' => feed_loader(null, (int) $category['id']),
	'name' => 'category-' . $slug,
	'total' => $total,
	'breadcrumbs' => [
		['title' => 'Главная', 'url' => '/'],
		['title' => $category['title'], 'url' => null],
	],
]) ?>

<?php $this->stop() ?>
