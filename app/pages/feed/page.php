<?php

declare(strict_types=1);

/**
 * Лента: «Важно», «Официально», «СМИ», «Видео» и остальные подборки.
 *
 * Один шаблон на девять лент. Подпись, описание и иконка берутся из
 * `site_feeds()`, выборка — из метаданных маршрута (`$route['feed']`).
 */

$feedKey = (string) ($route['feed'] ?? 'all');
$feeds = site_feeds();
$feed = $feeds[$feedKey] ?? fail('feed_unknown', ['feed' => $feedKey]);

$total = articles_page(1, 0, $feedKey)['total'];
$title = $feed['title'] . ' — новости Курска и области';
$description = $feed['lead'] . '. ' . ru_count($total, 'материал', 'материала', 'материалов') . ' в подборке.';
$url = $feed['url'];

$this->layout('_app://components/layout/main', [
	'title' => $feed['title'] . ' — новости Курска и области',
	'description' => $description,
	'url' => $url,
]);

$this->start('page');
?>

<?= $this->insert('_app://components/news/listing', [
	'title' => $feed['title'],
	'lead' => $feed['lead'] . '. ' . ru_count($total, 'материал', 'материала', 'материалов') . '.',
	'icon' => $feed['icon'],
	'loader' => feed_loader($feedKey),
	'name' => 'feed-' . $feedKey,
	'total' => $total,
	'breadcrumbs' => [
		['title' => 'Главная', 'url' => '/'],
		['title' => $feed['title'], 'url' => null],
	],
]) ?>

<?php $this->stop() ?>
