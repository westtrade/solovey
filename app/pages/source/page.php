<?php

declare(strict_types=1);

/**
 * Источник: всё, что пришло из одного издания или канала.
 */

$slug = (string) ($routeParams['slug'] ?? $slug ?? '');
$source = source_find($slug) ?? fail('source_not_found', ['slug' => $slug]);

$total = (int) $source['total'];
$title = $source['title'] . ' — материалы';
$description = 'Материалы источника «' . $source['title'] . '» (' . $source['kind_label'] . '): '
	. ru_count($total, 'материал', 'материала', 'материалов') . '.';

$this->layout('_app://components/layout/main', [
	'title' => $title,
	'description' => $description,
	'url' => $source['url'],
]);

$this->start('page');
?>

<?= $this->insert('_app://components/news/listing', [
	'title' => $source['title'],
	'lead' => $source['kind_label'] . ' · ' . ru_count($total, 'материал', 'материала', 'материалов'),
	'icon' => 'building',
	'loader' => feed_loader(null, null, null, null, (int) $source['id']),
	'name' => 'source-' . $slug,
	'showFeedsTabs' => false,
	'breadcrumbs' => [
		['title' => 'Главная', 'url' => '/'],
		['title' => 'Источники', 'url' => null],
		['title' => $source['title'], 'url' => null],
	],
]) ?>

<?php $this->stop() ?>
