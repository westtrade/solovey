<?php

declare(strict_types=1);

/**
 * Роутер для встроенного сервера PHP.
 *
 * Существующие файлы (ассеты, картинки, favicon) отдаются как есть, всё
 * остальное уходит в `public/index.php` — так же, как на nginx.
 */

$path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

if (str_starts_with($path, '/convert-image/')) {
	require __DIR__ . '/../public/convert-image.php';

	return true;
}

$file = __DIR__ . '/../public' . $path;

if (is_string($path) && $path !== '/' && is_file($file)) {
	return false;
}

require __DIR__ . '/../public/index.php';
