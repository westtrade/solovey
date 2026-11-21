<?php

declare(strict_types=1);

/**
 * Front controller «Соловья».
 *
 * Сессии и кэш пишутся в var/: системный каталог PHP в этом окружении только
 * для чтения. Точка входа общая для веб-сервера и CLI-команд Bolt.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

$sessionDir = dirname(__DIR__) . '/var/sessions';

if (!is_dir($sessionDir)) {
	@mkdir($sessionDir, 0775, true);
}

if (is_dir($sessionDir) && is_writable($sessionDir)) {
	ini_set('session.save_path', $sessionDir);
}

$app = Bolt\Core\Application::boot(dirname(__DIR__));

(new Bolt\Core\Http\Kernel($app))->run();
