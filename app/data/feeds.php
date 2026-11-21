<?php

declare(strict_types=1);

/**
 * Машиночитаемые выходы: RSS, sitemap, robots.
 *
 * Собираются функциями, а не шаблонами: отдавать XML через Plates
 * неудобно (пробелы, экранирование), а разметку робот читает целиком.
 */

/**
 * RSS 2.0 с полным текстом материалов.
 */
function rss_feed(): string
{
	$articles = articles_page(50)['items'];
	$site = app_absolute_url('/');
	$now = (new DateTimeImmutable('now'))->format(DATE_RSS);

	$items = '';

	foreach ($articles as $article) {
		$link = app_absolute_url((string) $article['url']);
		$published = date_create((string) $article['iso']);
		$pubDate = $published !== false ? $published->format(DATE_RSS) : $now;
		$body = article_body((string) $article['body']);

		$items .= "\t\t<item>\n"
			. "\t\t\t<title>" . e((string) $article['title']) . "</title>\n"
			. "\t\t\t<link>" . e($link) . "</link>\n"
			. "\t\t\t<guid isPermaLink=\"true\">" . e($link) . "</guid>\n"
			. "\t\t\t<pubDate>" . e($pubDate) . "</pubDate>\n"
			. "\t\t\t<category>" . e((string) $article['category_title']) . "</category>\n"
			. "\t\t\t<description>" . e((string) $article['lead']) . "</description>\n"
			. "\t\t\t<content:encoded><![CDATA[" . str_replace(']]>', ']]]]><![CDATA[>', $body) . "]]></content:encoded>\n"
			. "\t\t</item>\n";
	}

	return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
		. '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n"
		. "\t<channel>\n"
		. "\t\t<title>Соловей — новости Курска и области</title>\n"
		. "\t\t<link>" . e($site) . "</link>\n"
		. "\t\t<description>Что случилось в Курске и области: главное за сутки, лента новостей, официальные сообщения.</description>\n"
		. "\t\t<language>ru</language>\n"
		. "\t\t<lastBuildDate>" . e($now) . "</lastBuildDate>\n"
		. "\t\t<atom:link href=\"" . e(app_absolute_url('/rss.xml')) . "\" rel=\"self\" type=\"application/rss+xml\"/>\n"
		. $items
		. "\t</channel>\n"
		. "</rss>\n";
}

/**
 * Карта сайта: все страницы, которые нужно обойти.
 */
function sitemap_xml(): string
{
	$urls = [];

	$add = static function (string $path, ?string $lastmod = null, string $changefreq = 'daily', string $priority = '0.7') use (&$urls): void {
		$urls[] = ['loc' => app_absolute_url($path), 'lastmod' => $lastmod, 'changefreq' => $changefreq, 'priority' => $priority];
	};

	$add('/', (new DateTimeImmutable('now'))->format('c'), 'hourly', '1.0');

	foreach (site_feeds() as $feed) {
		$add($feed['url'], null, 'hourly', '0.8');
	}

	foreach (categories_all() as $category) {
		$add((string) $category['url'], null, 'daily', '0.7');
	}

	foreach (articles_page(1000)['items'] as $article) {
		$add((string) $article['url'], (string) $article['iso'], 'weekly', '0.6');
	}

	foreach (['o-nas', 'kontakty', 'redakciya', 'privacy', 'pravila'] as $slug) {
		$add('/' . $slug, null, 'monthly', '0.3');
	}

	$body = '';

	foreach ($urls as $entry) {
		$body .= "\t<url>\n\t\t<loc>" . e($entry['loc']) . "</loc>\n";

		if ($entry['lastmod'] !== null && $entry['lastmod'] !== '') {
			$body .= "\t\t<lastmod>" . e($entry['lastmod']) . "</lastmod>\n";
		}

		$body .= "\t\t<changefreq>" . e($entry['changefreq']) . "</changefreq>\n"
			. "\t\t<priority>" . e($entry['priority']) . "</priority>\n"
			. "\t</url>\n";
	}

	return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
		. '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
		. $body
		. "</urlset>\n";
}

/**
 * robots.txt: служебные пути закрыты, карта сайта и RSS указаны.
 */
function robots_txt(): string
{
	$lines = [
		'User-agent: *',
		'Allow: /',
		'Disallow: /poisk',
		'Disallow: /convert-image',
		'Disallow: /build',
		'',
		'User-agent: Yandex',
		'Allow: /',
		'Disallow: /poisk',
		'Clean-param: q /poisk',
		'',
		'Sitemap: ' . app_absolute_url('/sitemap.xml'),
		'',
	];

	return implode("\n", $lines);
}
