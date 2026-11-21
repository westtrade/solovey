<?php

declare(strict_types=1);

/**
 * Конфигурация «Соловья» — аналог settings.py.
 *
 *   urlpatterns  → какие URL ведут в какие страницы
 *   icons        → откуда берётся спрайт иконок
 *   images       → пресеты /convert-image
 *   seo          → название сайта и разделитель заголовка
 *   vite         → точки входа ассетов
 *
 * Бандлов в конфиге нет намеренно: всё приложение живёт в каталоге `app/`,
 * который Bolt подключает сам как системный бандл `_app`.
 */
return [
	'debug' => env('BOLT_DEBUG', env('APP_ENV', 'production') !== 'production'),

	// ─── Адрес сайта ─────────────────────────────────────────────────
	// `url` нужен для абсолютных canonical, og:url и og:image: без него
	// Bolt оставляет их относительными. `allowed_hosts` — белый список
	// хостов, которым верим при сборке абсолютного адреса из запроса.
	'app' => [
		'url' => (string) env('APP_URL', ''),
		'allowed_hosts' => array_values(array_filter(array_map(
			'trim',
			explode(',', (string) env('APP_ALLOWED_HOSTS', 'localhost,localhost:8090,127.0.0.1,127.0.0.1:8090')),
		))),
	],

	// ─── Маршруты ────────────────────────────────────────────────────
	// Порядок важен: конкретные пути объявлены раньше рубрики-ловушки
	// `<slug>` и раньше карточки `<cat>/<y>/<m>/<d>/<id>.html`.
	'urlpatterns' => [
		['path' => '', 'dir' => 'home', 'name' => 'home'],

		// Ленты — один шаблон, разные выборки.
		['path' => 'lenta', 'dir' => 'feed', 'page' => 'feed/page.php', 'feed' => 'all', 'name' => 'feed-all'],
		['path' => 'hot', 'dir' => 'feed', 'page' => 'feed/page.php', 'feed' => 'hot', 'name' => 'feed-hot'],
		['path' => 'official', 'dir' => 'feed', 'page' => 'feed/page.php', 'feed' => 'official', 'name' => 'feed-official'],
		['path' => 'top', 'dir' => 'feed', 'page' => 'feed/page.php', 'feed' => 'top', 'name' => 'feed-top'],
		['path' => 'video', 'dir' => 'feed', 'page' => 'feed/page.php', 'feed' => 'video', 'name' => 'feed-video'],
		['path' => 'smi', 'dir' => 'feed', 'page' => 'feed/page.php', 'feed' => 'smi', 'name' => 'feed-smi'],
		['path' => 'tg', 'dir' => 'feed', 'page' => 'feed/page.php', 'feed' => 'tg', 'name' => 'feed-tg'],
		['path' => 'opinions', 'dir' => 'feed', 'page' => 'feed/page.php', 'feed' => 'opinions', 'name' => 'feed-opinions'],
		['path' => 'longread', 'dir' => 'feed', 'page' => 'feed/page.php', 'feed' => 'longread', 'name' => 'feed-longread'],

		// Рубрики.
		['path' => 'society', 'dir' => 'category', 'page' => 'category/page.php', 'slug' => 'society', 'name' => 'cat-society'],
		['path' => 'incident', 'dir' => 'category', 'page' => 'category/page.php', 'slug' => 'incident', 'name' => 'cat-incident'],
		['path' => 'politics', 'dir' => 'category', 'page' => 'category/page.php', 'slug' => 'politics', 'name' => 'cat-politics'],
		['path' => 'economy', 'dir' => 'category', 'page' => 'category/page.php', 'slug' => 'economy', 'name' => 'cat-economy'],
		['path' => 'sport', 'dir' => 'category', 'page' => 'category/page.php', 'slug' => 'sport', 'name' => 'cat-sport'],
		['path' => 'culture', 'dir' => 'category', 'page' => 'category/page.php', 'slug' => 'culture', 'name' => 'cat-culture'],
		['path' => 'other', 'dir' => 'category', 'page' => 'category/page.php', 'slug' => 'other', 'name' => 'cat-other'],

		// Тема, источник, поиск.
		['path' => 'tema/<slug:slug>', 'dir' => 'tag', 'name' => 'tag'],
		['path' => 'istochnik/<slug:slug>', 'dir' => 'source', 'name' => 'source'],
		['path' => 'poisk', 'dir' => 'search', 'name' => 'search'],

		// Информационные страницы.
		['path' => 'o-nas', 'dir' => 'page', 'page' => 'page/page.php', 'slug' => 'o-nas', 'name' => 'page-about'],
		['path' => 'kontakty', 'dir' => 'page', 'page' => 'page/page.php', 'slug' => 'kontakty', 'name' => 'page-contacts'],
		['path' => 'redakciya', 'dir' => 'page', 'page' => 'page/page.php', 'slug' => 'redakciya', 'name' => 'page-editorial'],
		['path' => 'privacy', 'dir' => 'page', 'page' => 'page/page.php', 'slug' => 'privacy', 'name' => 'page-privacy'],
		['path' => 'pravila', 'dir' => 'page', 'page' => 'page/page.php', 'slug' => 'pravila', 'name' => 'page-rules'],

		// Файлы для роботов: view-функции, а не шаблоны — отдают свой Content-Type.
		[
			'path' => 'rss.xml',
			'name' => 'rss',
			'view' => static fn (): \Bolt\Core\Http\Response => new \Bolt\Core\Http\Response(
				rss_feed(),
				200,
				['Content-Type' => 'application/rss+xml; charset=utf-8'],
			),
		],
		[
			'path' => 'sitemap.xml',
			'name' => 'sitemap',
			'view' => static fn (): \Bolt\Core\Http\Response => new \Bolt\Core\Http\Response(
				sitemap_xml(),
				200,
				['Content-Type' => 'application/xml; charset=utf-8'],
			),
		],
		[
			'path' => 'robots.txt',
			'name' => 'robots',
			'view' => static fn (): \Bolt\Core\Http\Response => \Bolt\Core\Http\Response::text(robots_txt()),
		],

		// Карточка: последний сегмент — идентификатор, как в оригинале.
		[
			'path' => '<cat>/<y>/<m>/<d>/<id>.html',
			'dir' => 'article',
			'name' => 'article',
		],
	],

	// ─── Иконки ──────────────────────────────────────────────────────
	// Свой набор лежит в секции icons/ бандла; встроенные иконки Bolt —
	// источник с низшим приоритетом (нужны для ui-kit).
	'icons' => [
		'sources' => ['_app://icons', 'bolt://icons'],
		'mode' => 'inline',
	],

	// ─── Картинки ────────────────────────────────────────────────────
	// Пресеты дополняют встроенные: карточка, миниатюра строки, обложка.
	'images' => [
		'presets' => [
			'card' => ['w' => 640, 'h' => 400, 'fit' => 'cover', 'q' => 80, 'fm' => 'webp'],
			'row' => ['w' => 200, 'h' => 130, 'fit' => 'cover', 'q' => 76, 'fm' => 'webp'],
			'cover' => ['w' => 1200, 'h' => 750, 'fit' => 'cover', 'q' => 82, 'fm' => 'webp'],
			'body' => ['w' => 1128, 'h' => 0, 'fit' => 'contain', 'q' => 82, 'fm' => 'webp'],
			'og' => ['w' => 1200, 'h' => 630, 'fit' => 'cover', 'q' => 85, 'fm' => 'jpeg'],
		],
	],

	// ─── SEO ─────────────────────────────────────────────────────────
	'seo' => [
		'site_name' => 'Соловей',
		'title_separator' => ' — ',
		'locale' => 'ru_RU',
		'twitter' => 'summary_large_image',
	],

	// ─── Vite ────────────────────────────────────────────────────────
	'vite' => [
		'entries' => ['app/client/main.ts'],
		'manifest' => 'public/build/.vite/manifest.json',
		'port' => env('VITE_PORT', 5183),
	],
];
