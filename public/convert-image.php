<?php

declare(strict_types=1);

/**
 * Ресайз и конвертация изображений по запросу: `/convert-image/<путь>?preset=…`.
 *
 * Точка входа отдельная, а не маршрут ядра: картинки кэшируются как
 * `immutable`, и поднимать ради них сессию и роутер незачем. Правила
 * веб-сервера — в `docs/DEPLOY.md`.
 *
 * Путь берётся из `REQUEST_URI`, а не из query-строки: веб-сервер декодирует
 * захват rewrite-правила до подстановки, и имя файла с пробелом ломает
 * правило. В `REQUEST_URI` исходное кодирование сохраняется.
 */

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

define('BOLT_ROOT', $root);

// Ядро поднимается только ради конфигурации: пресеты проекта (`images.presets`)
// живут в config.php, без него останутся встроенные.
\Bolt\Core\Application::boot($root);

$image = \Bolt\Images\Image::instance();

$uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
$uri = (string) preg_replace('~[?#].*$~', '', $uri);
$uri = (string) preg_replace('~^/convert-image/~', '', $uri);

$path = ltrim(rawurldecode($uri), '/');

$source = $path !== '' ? $image->resolveSource($path) : null;

if ($source === null) {
	http_response_code(404);
	header('Content-Type: text/plain; charset=utf-8');

	exit('Not found');
}

$resolved = $image->params($_GET);

if ($resolved === []) {
	$image->serveOriginal($source);

	return;
}

$file = $image->cacheFile($source, $resolved['params'], $resolved['preset']);

if ($file === null) {
	http_response_code(500);
	header('Content-Type: text/plain; charset=utf-8');

	exit('Conversion failed');
}

$image->serve($file, $image->cachePath($source, $resolved['params'], $resolved['preset']));
