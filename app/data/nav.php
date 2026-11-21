<?php

declare(strict_types=1);

/**
 * Навигация: ленты и служебные ссылки.
 *
 * Ленты — не рубрики: «Важно», «Топ» и «Видео» собираются по признаку
 * материала, а не по разделу. В оригинале они лежали в одном ряду с
 * рубриками, и было непонятно, чем «Общество» отличается от «Важно».
 *
 * `nav` — на каком брейкпоинте лента попадает в верхнее меню. Всё девять
 * пунктов не влезают в строку на планшете, а горизонтальная прокрутка
 * обрезала выпадающий список рубрик (overflow у родителя режет absolute).
 * Поэтому второстепенные ленты появляются по мере ширины экрана, а полный
 * список всегда есть в мобильном меню и подвале.
 *
 * @return array<string, array{title: string, url: string, lead: string, icon: string, nav: string}>
 */
function site_feeds(): array
{
	return [
		'all' => ['title' => 'Лента', 'url' => '/lenta', 'lead' => 'Всё, что вышло, по времени', 'icon' => 'list', 'nav' => 'base'],
		'hot' => ['title' => 'Важно', 'url' => '/hot', 'lead' => 'Материалы, которые стоит прочитать', 'icon' => 'flame', 'nav' => 'base'],
		'top' => ['title' => 'Топ', 'url' => '/top', 'lead' => 'Самое читаемое за неделю', 'icon' => 'grid', 'nav' => 'base'],
		'official' => ['title' => 'Официально', 'url' => '/official', 'lead' => 'Сообщения ведомств и администрации', 'icon' => 'building', 'nav' => 'lg'],
		'video' => ['title' => 'Видео', 'url' => '/video', 'lead' => 'Сюжеты и записи', 'icon' => 'video', 'nav' => 'lg'],
		'smi' => ['title' => 'СМИ', 'url' => '/smi', 'lead' => 'Материалы районных изданий', 'icon' => 'book', 'nav' => 'lg'],
		'tg' => ['title' => 'Паблики', 'url' => '/tg', 'lead' => 'Городские каналы и чаты', 'icon' => 'users', 'nav' => 'xl'],
		'opinions' => ['title' => 'Мнения', 'url' => '/opinions', 'lead' => 'Колонки и комментарии', 'icon' => 'tag', 'nav' => 'xl'],
		'longread' => ['title' => 'Лонгриды', 'url' => '/longread', 'lead' => 'Разборы и истории', 'icon' => 'book', 'nav' => 'xl'],
	];
}

/**
 * Классы видимости пункта меню для его уровня: базовые видны всегда,
 * остальные — с lg или xl.
 */
function feed_nav_class(string $nav): string
{
	return match ($nav) {
		'lg' => 'hidden lg:flex',
		'xl' => 'hidden xl:flex',
		default => 'flex',
	};
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
