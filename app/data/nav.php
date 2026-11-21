<?php

declare(strict_types=1);

/**
 * Навигация: ленты и служебные ссылки.
 *
 * Ленты — не рубрики: «Важно», «Топ» и «Видео» собираются по признаку
 * материала, а не по разделу. В оригинале они лежали в одном ряду с
 * рубриками, и было непонятно, чем «Общество» отличается от «Важно».
 *
 * @return array<string, array{title: string, url: string, lead: string, icon: string}>
 */
function site_feeds(): array
{
	return [
		'all' => ['title' => 'Лента', 'url' => '/lenta', 'lead' => 'Всё, что вышло, по времени', 'icon' => 'list'],
		'hot' => ['title' => 'Важно', 'url' => '/hot', 'lead' => 'Материалы, которые стоит прочитать', 'icon' => 'flame'],
		'official' => ['title' => 'Официально', 'url' => '/official', 'lead' => 'Сообщения ведомств и администрации', 'icon' => 'building'],
		'top' => ['title' => 'Топ', 'url' => '/top', 'lead' => 'Самое читаемое за неделю', 'icon' => 'grid'],
		'video' => ['title' => 'Видео', 'url' => '/video', 'lead' => 'Сюжеты и записи', 'icon' => 'video'],
		'smi' => ['title' => 'СМИ', 'url' => '/smi', 'lead' => 'Материалы районных изданий', 'icon' => 'book'],
		'tg' => ['title' => 'Паблики', 'url' => '/tg', 'lead' => 'Городские каналы и чаты', 'icon' => 'users'],
		'opinions' => ['title' => 'Мнения', 'url' => '/opinions', 'lead' => 'Колонки и комментарии', 'icon' => 'tag'],
		'longread' => ['title' => 'Лонгриды', 'url' => '/longread', 'lead' => 'Разборы и истории', 'icon' => 'book'],
	];
}

/**
 * Текущий путь запроса без хвостового слэша.
 */
function current_path(): string
{
	$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
	$path = is_string($path) ? $path : '/';
	$path = '/' . trim($path, '/');

	return $path === '/' ? '/' : rtrim($path, '/');
}

/**
 * Активна ли ссылка навигации.
 */
function nav_active(string $url): bool
{
	$current = current_path();

	if ($url === '/') {
		return $current === '/';
	}

	return $current === $url || str_starts_with($current, $url . '/');
}
