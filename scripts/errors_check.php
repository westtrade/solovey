#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Проверка реестра ошибок.
 *
 * Правила:
 *  1. код из `fail()` и `AppError::make()` объявлен в `ErrorCodes`;
 *  2. у каждого кода есть текст и HTTP-статус;
 *  3. объявленный код где-то используется (иначе он мёртвый).
 *
 * Код возврата 1 — есть нарушение.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Solovey\Support\ErrorCodes;

$root = dirname(__DIR__);

$files = [];

foreach (['app', 'src', 'scripts', 'database'] as $dir) {
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir, FilesystemIterator::SKIP_DOTS)) as $file) {
		if ($file instanceof SplFileInfo && $file->isFile() && str_ends_with($file->getFilename(), '.php')) {
			$files[] = $file->getPathname();
		}
	}
}

sort($files);

$used = [];

foreach ($files as $path) {
	$source = (string) file_get_contents($path);

	if (preg_match_all('/(?:fail|AppError::make)\(\s*\'([a-z0-9_]+)\'/', $source, $matches) === false) {
		continue;
	}

	foreach ($matches[1] as $code) {
		$used[$code] = ($used[$code] ?? 0) + 1;
	}
}

$problems = [];
$declared = ErrorCodes::codes();

foreach (array_keys($used) as $code) {
	if (ErrorCodes::get($code) === null) {
		$problems[] = sprintf('Код «%s» используется, но не объявлен в ErrorCodes', $code);
	}
}

foreach ($declared as $code) {
	$entry = ErrorCodes::get($code);

	if ($entry === null || trim((string) ($entry['message'] ?? '')) === '') {
		$problems[] = sprintf('У кода «%s» нет текста', $code);
	}

	if (($entry['status'] ?? 0) < 400 || ($entry['status'] ?? 0) > 599) {
		$problems[] = sprintf('У кода «%s» некорректный HTTP-статус', $code);
	}

	if (!isset($used[$code])) {
		$problems[] = sprintf('Код «%s» объявлен, но нигде не используется', $code);
	}
}

foreach ($problems as $problem) {
	echo $problem, "\n";
}

printf("Кодов: %d, используется: %d\n", count($declared), count($used));

exit($problems === [] ? 0 : 1);
