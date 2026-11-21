<?php

declare(strict_types=1);

/**
 * Пересоздание схемы. База демонстрационная, поэтому DROP + CREATE: данные
 * всегда восстанавливаются из снимка командой `make seed`.
 */

use Solovey\Support\Database;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
$schemaFile = $root . '/database/schema.sql';
$databaseFile = Database::path();

if (!is_file($schemaFile)) {
	fwrite(STDERR, "Нет схемы: database/schema.sql\n");
	exit(1);
}

if (!is_dir(dirname($databaseFile))) {
	mkdir(dirname($databaseFile), 0775, true);
}

$pdo = new PDO('sqlite:' . $databaseFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = (string) file_get_contents($schemaFile);
$pdo->exec($sql);

$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
	->fetchAll(PDO::FETCH_COLUMN);

printf("Схема применена: %s\nТаблицы: %s\n", $databaseFile, implode(', ', $tables));
