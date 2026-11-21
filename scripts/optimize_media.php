#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Уменьшает исходники в public/media: максимум 1000 px по длинной стороне.
 *
 * Конвейер Bolt всё равно отдаёт карточки через /convert-image, но исходники
 * лежат в репозитории — полтора мегабайта на фотографию в git ни к чему.
 * Скрипт идемпотентен: повторный запуск ничего не меняет.
 */

$root = dirname(__DIR__);
$dir = $root . '/public/media';
$maxSide = 1000;
$quality = 78;

if (!is_dir($dir)) {
	fwrite(STDERR, "Нет каталога public/media\n");
	exit(1);
}

$files = [];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) {
	if ($file instanceof SplFileInfo && $file->isFile() && preg_match('/\.(jpe?g|png)$/i', $file->getFilename()) === 1) {
		$files[] = $file->getPathname();
	}
}

sort($files);

$resized = 0;
$skipped = 0;
$before = 0;
$after = 0;

foreach ($files as $path) {
	$before += (int) filesize($path);

	$info = @getimagesize($path);

	if ($info === false) {
		$skipped++;
		continue;
	}

	[$width, $height] = $info;
	$isPng = $info[2] === IMAGETYPE_PNG;

	$image = $isPng ? @imagecreatefrompng($path) : @imagecreatefromjpeg($path);

	if ($image === false) {
		$skipped++;
		continue;
	}

	$longest = max($width, $height);

	if ($longest > $maxSide) {
		$scale = $maxSide / $longest;
		$newWidth = max(1, (int) round($width * $scale));
		$newHeight = max(1, (int) round($height * $scale));

		$target = imagecreatetruecolor($newWidth, $newHeight);

		if ($isPng) {
			imagealphablending($target, false);
			imagesavealpha($target, true);
		}

		imagecopyresampled($target, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		imagedestroy($image);
		$image = $target;
		$resized++;
	}

	if ($isPng) {
		imagepng($image, $path, 7);
	} else {
		imagejpeg($image, $path, $quality);
	}

	imagedestroy($image);
	$after += (int) filesize($path);
}

printf(
	"Готово: %d файлов, уменьшено %d, пропущено %d. Было %.1f МБ, стало %.1f МБ.\n",
	count($files),
	$resized,
	$skipped,
	$before / 1048576,
	$after / 1048576,
);
