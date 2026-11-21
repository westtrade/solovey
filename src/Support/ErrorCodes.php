<?php

declare(strict_types=1);

namespace Solovey\Support;

/**
 * Реестр символьных кодов ошибок.
 *
 * Один код — одно место. Код неизменяем: по нему ищут в логах и в тестах,
 * поэтому текст сообщения можно менять, а код — нет. Формат: snake_case,
 * `<область>_<что не так>`.
 */
final class ErrorCodes
{
	/**
	 * @var array<string, array{message: string, status: int}>
	 */
	private const REGISTRY = [
		// ─── Данные ──────────────────────────────────────────────────
		'article_not_found' => ['message' => 'Новость не найдена.', 'status' => 404],
		'category_not_found' => ['message' => 'Рубрика не найдена.', 'status' => 404],
		'source_not_found' => ['message' => 'Источник не найден.', 'status' => 404],
		'tag_not_found' => ['message' => 'Тема не найдена.', 'status' => 404],
		'page_not_found' => ['message' => 'Страница не найдена.', 'status' => 404],
		'feed_unknown' => ['message' => 'Неизвестная лента.', 'status' => 404],

		// ─── База ────────────────────────────────────────────────────
		'database_schema_missing' => ['message' => 'Схема базы не создана. Запустите make db-migrate.', 'status' => 503],
		'seed_source_missing' => ['message' => 'Нет файла снимка для наполнения базы.', 'status' => 500],
	];

	/**
	 * @return array{message: string, status: int}|null
	 */
	public static function get(string $code): ?array
	{
		return self::REGISTRY[$code] ?? null;
	}

	/**
	 * @return list<string>
	 */
	public static function codes(): array
	{
		return array_keys(self::REGISTRY);
	}
}
