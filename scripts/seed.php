<?php

declare(strict_types=1);

/**
 * Наполнение базы из снимка kursk-news.net.
 *
 *   ./scripts/seed            — пересобрать данные
 *
 * Снимок лежит в `database/snapshot.json` (его делает scripts/fetch_snapshot.py),
 * тексты страниц — в `database/pages.php`. Скрипт идемпотентен: перед вставкой
 * таблицы очищаются, поэтому запускать можно сколько угодно раз.
 */

use Solovey\Model\Table\ArticlesTable;
use Solovey\Model\Table\CategoriesTable;
use Solovey\Model\Table\FeedItemsTable;
use Solovey\Model\Table\PagesTable;
use Solovey\Model\Table\SourcesTable;
use Solovey\Model\Table\TagsTable;
use Solovey\Support\AppError;
use Solovey\Support\Database;
use Solovey\Support\Slug;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
$snapshotFile = $root . '/database/snapshot.json';

if (!is_file($snapshotFile)) {
	$error = AppError::make('seed_source_missing', ['file' => 'database/snapshot.json']);
	fwrite(STDERR, $error->getMessage() . "\n");
	fwrite(STDERR, "Сначала запустите: ./scripts/fetch_snapshot.py 200\n");
	exit(1);
}

$snapshot = json_decode((string) file_get_contents($snapshotFile), true, 512, JSON_THROW_ON_ERROR);

if (!is_array($snapshot) || !isset($snapshot['articles'])) {
	fwrite(STDERR, "Снимок повреждён: нет ключа articles\n");
	exit(1);
}

// ─── Рубрики ────────────────────────────────────────────────────────────
// Порядок задаёт и меню, и сетку на главной. `accent` — имя палитры Tailwind,
// из него собираются бейджи и подложки карточек.
$categories = [
	['society', 'Общество', 'Город, люди, образование, медицина и всё, что происходит вокруг.', 'sky', 10],
	['incident', 'Происшествия', 'Пожары, ДТП, аварии и оперативные сводки.', 'rose', 20],
	['politics', 'Политика', 'Власть, выборы, решения и их последствия.', 'violet', 30],
	['economy', 'Экономика', 'Бюджет, бизнес, цены и рабочие места.', 'emerald', 40],
	['sport', 'Спорт', 'Матчи, турниры и курские спортсмены.', 'amber', 50],
	['culture', 'Культура', 'Театр, музыка, выставки и городские события.', 'fuchsia', 60],
	['other', 'Разное', 'Всё остальное: погода, транспорт, полезное.', 'slate', 70],
];

$categoriesTable = Database::table('Categories');
/** @var CategoriesTable $categoriesTable */
$categoriesTable->deleteAll([]);

$categoryIds = [];

foreach ($categories as [$slug, $title, $lead, $accent, $sort]) {
	$entity = $categoriesTable->newEntity([
		'slug' => $slug,
		'title' => $title,
		'lead' => $lead,
		'accent' => $accent,
		'sort' => $sort,
	]);

	if ($entity->hasErrors()) {
		fwrite(STDERR, 'Рубрика ' . $slug . ': ' . json_encode($entity->getErrors(), JSON_UNESCAPED_UNICODE) . "\n");
		exit(1);
	}

	$categoriesTable->saveOrFail($entity);
	$categoryIds[$slug] = (int) $entity->id;
}

// ─── Источники ──────────────────────────────────────────────────────────
// Названия в снимке грязные: «Канал в МАКС "РИА «Курск»"», «Telegram-канал
// "Курская Telega"». Чистим префикс, вид источника определяем по префиксу.
$sourceIds = [];
$sourceKinds = [];

/**
 * @return array{title: string, kind: string}
 */
function normalize_source(string $raw): array
{
	$title = trim($raw);
	$kind = 'media';

	if (preg_match('/^Канал в МАКС\s+"?(.+?)"?$/u', $title, $m) === 1) {
		$title = $m[1];
		$kind = 'max';
	} elseif (preg_match('/^Telegram-канал\s+"?(.+?)"?$/u', $title, $m) === 1) {
		$title = $m[1];
		$kind = 'tg';
	}

	$title = trim($title, " \t\n\r\0\x0B\"");

	if (preg_match('/МЧС|Оперштаб|УФСИН|Администрац|Правительств|Минобороны|Росгварди|Прокуратур|Следственн|ГИБДД|ГУ МВД/u', $title) === 1) {
		$kind = 'gov';
	}

	return ['title' => $title, 'kind' => $kind];
}

$sourcesTable = Database::table('Sources');
/** @var SourcesTable $sourcesTable */
$sourcesTable->deleteAll([]);

$rawSources = [];

foreach ($snapshot['articles'] as $article) {
	if (($article['source'] ?? '') !== '') {
		$rawSources[] = (string) $article['source'];
	}
}

foreach ($snapshot['feed_rows'] as $row) {
	if (($row['source'] ?? '') !== '') {
		$rawSources[] = (string) $row['source'];
	}
}

foreach (array_unique($rawSources) as $raw) {
	$normalized = normalize_source($raw);
	$slug = Slug::make($normalized['title']);

	if (isset($sourceIds[$slug])) {
		continue;
	}

	$entity = $sourcesTable->newEntity([
		'slug' => $slug,
		'title' => $normalized['title'],
		'kind' => $normalized['kind'],
	]);

	$sourcesTable->saveOrFail($entity);
	$sourceIds[$slug] = (int) $entity->id;
	$sourceKinds[$slug] = $normalized['kind'];
}

// Сырое имя → id: так статья находит свой источник без повторной нормализации.
$sourceByRaw = [];
foreach ($rawSources as $raw) {
	$sourceByRaw[$raw] = $sourceIds[Slug::make(normalize_source($raw)['title'])];
}

// ─── Темы ───────────────────────────────────────────────────────────────
// В оригинале тем нет: контент достаётся только через ленту и семь рубрик.
// Здесь тема — это устойчивый сюжет, по которому материал можно найти позже.
$tagVocabulary = [
	'kursk' => ['Курск', '/Курск/u'],
	'oblast' => ['Курская область', '/Курская область|Курской области/u'],
	'aviadanger' => ['Авиационная опасность', '/авиационн|БПЛА|беспилотник|дрон/iu'],
	'rocketdanger' => ['Ракетная опасность', '/ракетная опасность/iu'],
	'mchs' => ['МЧС', '/МЧС/u'],
	'gubernator' => ['Губернатор', '/губернатор|Хинштейн/iu'],
	'zhkh' => ['ЖКХ', '/ЖКХ|отоплен|теплосет|водоснабж|коммунальн/iu'],
	'dorogi' => ['Дороги', '/дорог|трасс|развязк/iu'],
	'transport' => ['Транспорт', '/автобус|троллейбус|трамвай|транспорт|маршрут/iu'],
	'obrazovanie' => ['Образование', '/школ|гимнази|университет|вуз|образован|студент/iu'],
	'medicina' => ['Медицина', '/больниц|поликлиник|медиц|врач|здравоохран/iu'],
	'sport' => ['Спорт', '/спорт|матч|турнир|футбол|хокке|чемпионат/iu'],
	'kultura' => ['Культура', '/концерт|выставк|театр|музей|фестивал|библиотек/iu'],
	'proisshestvie' => ['Происшествия', '/пожар|ДТП|авари|погиб|пострада|столкновен/iu'],
	'pogoda' => ['Погода', '/погод|дожд|снег|градус|ветер|гололед|заморозк/iu'],
	'ekonomika' => ['Экономика', '/экономик|бизнес|цен[аы]|субсиди|бюджет|налог/iu'],
	'politika' => ['Политика', '/политик|выбор|депутат|администрац|парламент/iu'],
	'stroyka' => ['Строительство', '/строитель|реконструкц|капремонт|объект|школу на/iu'],
	'pomoshch' => ['Помощь', '/гумпомощ|волонтер|помощь|сбор|пожертвован/iu'],
];

$tagsTable = Database::table('Tags');
/** @var TagsTable $tagsTable */
$tagsTable->deleteAll([]);

$tagIds = [];

foreach ($tagVocabulary as $slug => [$title, $pattern]) {
	$entity = $tagsTable->newEntity(['slug' => $slug, 'title' => $title]);
	$tagsTable->saveOrFail($entity);
	$tagIds[$slug] = (int) $entity->id;
}

// ─── Статьи ─────────────────────────────────────────────────────────────
$articlesTable = Database::table('Articles');
/** @var ArticlesTable $articlesTable */
$articlesTable->deleteAll([]);

$articleTagsTable = Database::table('ArticleTags');
$articleTagsTable->deleteAll([]);

/** @var array<string, array<string, true>> $feedUrls */
$feedUrls = ['top' => [], 'hot' => [], 'video' => [], 'longread' => [], 'opinions' => []];

foreach ($snapshot['feed_rows'] as $row) {
	$feed = (string) ($row['feed'] ?? '');
	$url = (string) ($row['url'] ?? '');

	if ($url !== '' && isset($feedUrls[$feed])) {
		$feedUrls[$feed][$url] = true;
	}
}

$inserted = 0;
$skipped = 0;

foreach ($snapshot['articles'] as $article) {
	$category = (string) ($article['category'] ?? 'other');

	if (!isset($categoryIds[$category])) {
		$skipped++;
		continue;
	}

	$title = trim((string) ($article['title'] ?? ''));

	if ($title === '') {
		$skipped++;
		continue;
	}

	$body = (string) ($article['body'] ?? '');
	$lead = trim((string) ($article['lead'] ?? ''));
	$url = (string) ($article['url'] ?? '');
	$feed = (string) ($article['feed'] ?? 'all');
	$rawSource = (string) ($article['source'] ?? '');
	$sourceId = $rawSource !== '' ? ($sourceByRaw[$rawSource] ?? null) : null;
	$sourceKind = $sourceId !== null ? ($sourceKinds[Slug::make(normalize_source($rawSource)['title'])] ?? 'media') : 'media';

	$feedKey = match (true) {
		$feed === 'official' => 'official',
		$feed === 'tg' => 'tg',
		$feed === 'smi' => $sourceKind === 'tg' ? 'tg' : ($sourceKind === 'gov' ? 'official' : 'smi'),
		$sourceKind === 'tg' => 'tg',
		$sourceKind === 'gov' => 'official',
		default => 'smi',
	};

	$id = (int) ($article['id'] ?? 0);
	$publishedAt = (string) ($article['published_at'] ?? '');

	$entity = $articlesTable->newEntity([
		'id' => $id,
		'category_id' => $categoryIds[$category],
		'source_id' => $sourceId,
		'title' => $title,
		'lead' => $lead,
		'body' => $body,
		'cover' => (string) ($article['cover'] ?? ''),
		'published_at' => $publishedAt,
		'views' => 200 + (crc32($id . 'views') % 9000),
		'reading_time' => reading_minutes((string) ($article['text'] ?? $body)),
		'is_top' => isset($feedUrls['top'][$url]) || $feed === 'top',
		'is_important' => isset($feedUrls['hot'][$url]) || $feed === 'hot',
		'is_video' => isset($feedUrls['video'][$url]) || in_array($feed, ['video', 'photo'], true),
		'is_longread' => isset($feedUrls['longread'][$url]) || $feed === 'longread',
		'is_opinion' => isset($feedUrls['opinions'][$url]) || $feed === 'opinions',
		'feed_key' => $feedKey,
	]);

	if ($entity->hasErrors()) {
		fwrite(STDERR, 'Статья ' . $id . ': ' . json_encode($entity->getErrors(), JSON_UNESCAPED_UNICODE) . "\n");
		continue;
	}

	$articlesTable->saveOrFail($entity);
	$inserted++;

	// Темы ищем по заголовку и лиду: тело слишком шумное, там цитаты.
	$haystack = $title . "\n" . $lead;
	$matched = 0;

	foreach ($tagVocabulary as $slug => [$tagTitle, $pattern]) {
		if ($matched >= 3) {
			break;
		}

		if (preg_match($pattern, $haystack) === 1) {
			$join = $articleTagsTable->newEntity([
				'article_id' => $id,
				'tag_id' => $tagIds[$slug],
			]);
			$articleTagsTable->saveOrFail($join);
			$matched++;
		}
	}
}

// ─── Оперативная лента ──────────────────────────────────────────────────
// Строки без ссылки на статью — это и есть «живая лента»: предупреждения,
// посты пабликов, короткие сообщения. В оригинале они рендерились в правую
// колонку без URL и без возможности открыть материал.
$feedItemsTable = Database::table('FeedItems');
/** @var FeedItemsTable $feedItemsTable */
$feedItemsTable->deleteAll([]);

$seen = [];
$feedInserted = 0;

foreach ($snapshot['feed_rows'] as $row) {
	if (($row['inline'] ?? false) !== true) {
		continue;
	}

	$title = trim((string) ($row['title'] ?? ''));

	if ($title === '') {
		continue;
	}

	// В ленте оригинала источник дописан в тот же текст через «//»:
	// «Курская область: авиационная опасность.// Сейм: новости Курской области».
	// Разделяем: заголовок — слева, источник — справа.
	$parts = array_map('trim', explode('//', $title, 2));
	$title = $parts[0];
	$inlineSource = $parts[1] ?? '';

	$key = md5($title . (string) ($row['published_at'] ?? ''));

	if (isset($seen[$key])) {
		continue;
	}

	$seen[$key] = true;

	$rawSource = (string) ($row['source'] ?? '');
	$rawSource = $rawSource !== '' ? $rawSource : $inlineSource;
	$isAlert = preg_match('/ОПАСНОСТЬ|опасность|ракетн|авиационн|БПЛА|обстрел/iu', $title) === 1;

	$entity = $feedItemsTable->newEntity([
		'kind' => $isAlert ? 'alert' : 'news',
		'title' => $title,
		'body' => '',
		'source_id' => $rawSource !== '' ? ($sourceByRaw[$rawSource] ?? null) : null,
		'article_id' => null,
		'published_at' => (string) ($row['published_at'] ?? ''),
		'pinned' => $isAlert,
		'severity' => $isAlert ? 'critical' : 'normal',
	]);

	$feedItemsTable->saveOrFail($entity);
	$feedInserted++;
}

// ─── Информационные страницы ────────────────────────────────────────────
$pagesTable = Database::table('Pages');
/** @var PagesTable $pagesTable */
$pagesTable->deleteAll([]);

foreach (require $root . '/database/pages.php' as $slug => $page) {
	$entity = $pagesTable->newEntity([
		'slug' => $slug,
		'title' => $page['title'],
		'lead' => $page['lead'],
		'body' => $page['body'],
		'updated_at' => $page['updated_at'],
	]);

	$pagesTable->saveOrFail($entity);
}

// ─── Итог ───────────────────────────────────────────────────────────────
$stats = site_stats();

printf(
	"Готово: %d статей (пропущено %d), %d источников, %d тем, %d строк ленты, %d страниц.\n",
	$inserted,
	$skipped,
	count($sourceIds),
	count($tagIds),
	$feedInserted,
	count(require $root . '/database/pages.php'),
);

printf("Всего в базе: %d статей, сегодня %d.\n", $stats['articles'], $stats['today']);
