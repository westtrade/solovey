<?php

declare(strict_types=1);

/**
 * Каркас страницы.
 *
 * @param string      $title       заголовок без названия сайта
 * @param string      $description описание для поиска
 * @param string      $url         канонический путь
 * @param string      $image       картинка для соцсетей
 * @param string      $type        og:type
 * @param string      $robots      meta robots
 * @param array|null  $schema      JSON-LD
 * @param string      $bodyClass   дополнительные классы <body>
 */

$title ??= '';
$description ??= 'Новости Курска и Курской области: что случилось, почему это важно и что будет дальше.';
$url ??= current_path();
$image ??= '';
$type ??= 'website';
$robots ??= 'index,follow';
$schema ??= null;
$bodyClass ??= '';
?>
<!doctype html>
<html lang="ru">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#f7f4ef">

	<?= $this->insert('bolt://ui-kit/seo', [
		'title' => $title,
		'description' => $description,
		'url' => $url,
		'image' => $image,
		'type' => $type,
		'robots' => $robots,
		'schema' => $schema,
	]) ?>

	<link rel="alternate" type="application/rss+xml" title="Соловей — RSS" href="/rss.xml">
	<link rel="icon" href="/favicon.svg" type="image/svg+xml">

	<?= app_assets() ?>
</head>

<body class="<?= e(clsx('flex min-h-screen flex-col antialiased', $bodyClass)) ?>">
	<?= icons_sprite() ?>

	<a
		href="#main"
		class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-ink focus:px-4 focus:py-2 focus:text-sm focus:text-paper"
	>Перейти к содержанию</a>

	<?= $this->insert('_app://components/layout/header') ?>
	<?= $this->insert('_app://components/layout/alert') ?>

	<main id="main" class="flex-1">
		<?= $this->section('page') ?>
	</main>

	<?= $this->insert('_app://components/layout/footer') ?>
</body>

</html>
