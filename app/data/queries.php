<?php

declare(strict_types=1);

/**
 * Выборки через CakePHP ORM.
 *
 * Каждая функция возвращает массивы, готовые к рендеру: шаблон не знает ни про
 * сущности, ни про то, из какой таблицы пришла строка. Преобразование одно —
 * `present_article()`.
 */

use Cake\ORM\Query\SelectQuery;
use Solovey\Model\Entity\Article;
use Solovey\Model\Table\ArticlesTable;
use Solovey\Support\Database;

/**
 * Базовый запрос статей: рубрика и источник подгружаются всегда — они нужны
 * каждой карточке (ссылка на рубрику и подпись источника).
 */
function articles_query(): SelectQuery
{
	/** @var ArticlesTable $table */
	$table = Database::table('Articles');

	return $table->find()
		->contain(['Categories', 'Sources'])
		->find('latest');
}

/**
 * Страница ленты.
 *
 * @return array{items: list<array<string, mixed>>, total: int}
 */
function articles_page(
	int $limit = 12,
	int $offset = 0,
	?string $feed = null,
	?int $categoryId = null,
	?string $term = null,
	?int $tagId = null,
	?int $sourceId = null,
): array {
	$query = articles_query();

	if ($feed !== null && $feed !== '' && $feed !== 'all') {
		$query = $query->find('feed', $feed);
	}

	if ($categoryId !== null) {
		$query = $query->where(['Articles.category_id' => $categoryId]);
	}

	if ($sourceId !== null) {
		$query = $query->where(['Articles.source_id' => $sourceId]);
	}

	if ($term !== null && $term !== '') {
		$query = $query->find('search', $term);
	}

	if ($tagId !== null) {
		$query->matching('Tags', static fn (SelectQuery $q): SelectQuery => $q->where(['Tags.id' => $tagId]));
	}

	$total = (int) (clone $query)->count();

	$rows = $query
		->limit(max(1, $limit))
		->offset(max(0, $offset))
		->all()
		->toList();

	return [
		'items' => array_map('present_article', $rows),
		'total' => $total,
	];
}

/**
 * Загрузчик для `bolt://ui-kit/pagination`.
 *
 * Контракт Bolt: функция получает сумку с limit/offset и возвращает
 * `['items' => …, 'pagination' => ['total' => int, 'hasMore' => bool]]`.
 * Элементы — массивы: компонент дописывает каждому `id` и класс
 * `pagination__item`, чтобы htmx мог дозагружать их в конец списка.
 */
function feed_loader(
	?string $feed = null,
	?int $categoryId = null,
	?string $term = null,
	?int $tagId = null,
	?int $sourceId = null,
): callable {
	return static function (array $bag) use ($feed, $categoryId, $term, $tagId, $sourceId): array {
		$limit = (int) ($bag['limit'] ?? 12);
		$offset = (int) ($bag['offset'] ?? 0);

		$page = articles_page($limit, $offset, $feed, $categoryId, $term, $tagId, $sourceId);

		return [
			'items' => $page['items'],
			'pagination' => [
				'total' => $page['total'],
				'hasMore' => $offset + count($page['items']) < $page['total'],
			],
		];
	};
}

/**
 * Одна статья по идентификатору из URL.
 *
 * @return array<string, mixed>|null
 */
function article_find(int $id): ?array
{
	$row = articles_query()
		->where(['Articles.id' => $id])
		->contain(['Categories', 'Sources', 'Tags'])
		->first();

	return $row instanceof Article ? present_article($row) : null;
}

/**
 * Соседние статьи той же рубрики — «предыдущая / следующая».
 *
 * @return array{prev: array<string, mixed>|null, next: array<string, mixed>|null}
 */
function article_neighbours(array $article): array
{
	$base = articles_query()
		->where(['Articles.category_id' => $article['category_id']])
		->where(['Articles.id !=' => $article['id']]);

	$prev = (clone $base)
		->where(['Articles.published_at <' => $article['iso']])
		->orderBy(['Articles.published_at' => 'DESC'])
		->first();

	$next = (clone $base)
		->where(['Articles.published_at >' => $article['iso']])
		->orderBy(['Articles.published_at' => 'ASC'])
		->first();

	return [
		'prev' => $prev instanceof Article ? present_article($prev) : null,
		'next' => $next instanceof Article ? present_article($next) : null,
	];
}

/**
 * Связанные материалы: сначала по общей теме, затем по рубрике.
 *
 * @return list<array<string, mixed>>
 */
function articles_related(array $article, int $limit = 3): array
{
	$query = articles_query()->where(['Articles.id !=' => $article['id']]);

	if ($article['tags'] !== []) {
		$tagIds = array_column($article['tags'], 'id');

		$query->matching('Tags', static fn (SelectQuery $q): SelectQuery => $q->where(['Tags.id IN' => $tagIds]));

		$rows = $query->limit($limit)->all()->toList();

		if ($rows !== []) {
			return array_map('present_article', $rows);
		}
	}

	$rows = articles_query()
		->where(['Articles.id !=' => $article['id'], 'Articles.category_id' => $article['category_id']])
		->limit($limit)
		->all()
		->toList();

	return array_map('present_article', $rows);
}

/**
 * Главная новость суток: самая важная из свежих, иначе просто свежая.
 *
 * @return array<string, mixed>|null
 */
function article_hero(): ?array
{
	$row = articles_query()
		->where(['Articles.is_important' => true])
		->limit(1)
		->first();

	if (!$row instanceof Article) {
		$row = articles_query()->limit(1)->first();
	}

	return $row instanceof Article ? present_article($row) : null;
}

/**
 * Все рубрики с числом статей — для меню и сетки на главной.
 *
 * @return list<array<string, mixed>>
 */
function categories_all(): array
{
	$table = Database::table('Categories');

	$rows = $table->find()
		->orderBy(['sort' => 'ASC'])
		->all()
		->toList();

	$counts = [];
	$stmt = Database::table('Articles')->find()
		->select(['category_id', 'total' => 'COUNT(*)'])
		->groupBy(['category_id']);

	foreach ($stmt->all() as $row) {
		$counts[(int) $row->category_id] = (int) $row->total;
	}

	return array_map(
		static fn ($category): array => [
			'id' => (int) $category->id,
			'slug' => (string) $category->slug,
			'title' => (string) $category->title,
			'lead' => (string) $category->lead,
			'accent' => (string) $category->accent,
			'url' => (string) $category->url,
			'total' => $counts[(int) $category->id] ?? 0,
		],
		$rows,
	);
}

/**
 * @return array<string, mixed>|null
 */
function category_find(string $slug): ?array
{
	foreach (categories_all() as $category) {
		if ($category['slug'] === $slug) {
			return $category;
		}
	}

	return null;
}

/**
 * Источники с числом публикаций.
 *
 * @return list<array<string, mixed>>
 */
function sources_all(): array
{
	$rows = Database::table('Sources')->find()->orderBy(['title' => 'ASC'])->all()->toList();

	$counts = [];
	foreach (Database::table('Articles')->find()
		->select(['source_id', 'total' => 'COUNT(*)'])
		->where(['source_id IS NOT' => null])
		->groupBy(['source_id'])
		->all() as $row) {
		$counts[(int) $row->source_id] = (int) $row->total;
	}

	return array_map(
		static fn ($source): array => [
			'id' => (int) $source->id,
			'slug' => (string) $source->slug,
			'title' => (string) $source->title,
			'kind' => (string) $source->kind,
			'kind_label' => (string) $source->kind_label,
			'url' => (string) $source->url,
			'total' => $counts[(int) $source->id] ?? 0,
		],
		$rows,
	);
}

/**
 * @return array<string, mixed>|null
 */
function source_find(string $slug): ?array
{
	foreach (sources_all() as $source) {
		if ($source['slug'] === $slug) {
			return $source;
		}
	}

	return null;
}

/**
 * Темы, отсортированные по числу статей.
 *
 * @return list<array<string, mixed>>
 */
function tags_popular(int $limit = 12): array
{
	$rows = Database::table('Tags')->find()
		->select(['Tags.id', 'Tags.slug', 'Tags.title', 'total' => 'COUNT(article_tags.article_id)'])
		->join([
			'table' => 'article_tags',
			'alias' => 'article_tags',
			'type' => 'LEFT',
			'conditions' => 'article_tags.tag_id = Tags.id',
		])
		->groupBy(['Tags.id'])
		->orderBy(['total' => 'DESC', 'Tags.title' => 'ASC'])
		->limit($limit)
		->all()
		->toList();

	return array_map(
		static fn ($tag): array => [
			'id' => (int) $tag->id,
			'slug' => (string) $tag->slug,
			'title' => (string) $tag->title,
			'url' => (string) $tag->url,
			'total' => (int) $tag->total,
		],
		$rows,
	);
}

/**
 * @return array<string, mixed>|null
 */
function tag_find(string $slug): ?array
{
	$table = Database::table('Tags');
	$tag = $table->find()->where(['slug' => $slug])->first();

	if ($tag === null) {
		return null;
	}

	// Число материалов по теме: нужно для подписи и для пустого состояния.
	$total = (int) $table->getConnection()
		->execute('SELECT COUNT(*) FROM article_tags WHERE tag_id = ?', [(int) $tag->id])
		->fetchColumn(0);

	return [
		'id' => (int) $tag->id,
		'slug' => (string) $tag->slug,
		'title' => (string) $tag->title,
		'url' => (string) $tag->url,
		'total' => $total,
	];
}

/**
 * Оперативная лента: закреплённые сверху.
 *
 * @return list<array<string, mixed>>
 */
function feed_items_live(int $limit = 12): array
{
	$rows = Database::table('FeedItems')->find()
		->contain(['Sources', 'Articles'])
		->find('live')
		->limit($limit)
		->all()
		->toList();

	return array_map(
		static fn ($item): array => [
			'id' => (int) $item->id,
			'kind' => (string) $item->kind,
			'title' => (string) $item->title,
			'body' => (string) $item->body,
			'url' => (string) $item->url,
			'time_label' => (string) $item->time_label,
			'published_at' => (string) $item->published_at,
			'pinned' => (bool) $item->pinned,
			'severity' => (string) $item->severity,
			'is_alert' => (bool) $item->is_alert,
			'source' => $item->source !== null ? (string) $item->source->title : '',
			'source_url' => $item->source !== null ? (string) $item->source->url : '',
		],
		$rows,
	);
}

/**
 * Действующий алерт: последнее предупреждение за последние три часа.
 *
 * @return array<string, mixed>|null
 */
function alert_active(): ?array
{
	$since = (new DateTimeImmutable('-3 hours'))->format('c');

	$row = Database::table('FeedItems')->find()
		->contain(['Sources'])
		->where(['FeedItems.kind' => 'alert'])
		->where(['FeedItems.published_at >=' => $since])
		->orderBy(['published_at' => 'DESC'])
		->first();

	if ($row === null) {
		return null;
	}

	return [
		'title' => (string) $row->title,
		'time_label' => (string) $row->time_label,
		'severity' => (string) $row->severity,
		'source' => $row->source !== null ? (string) $row->source->title : '',
	];
}

/**
 * Страница из базы.
 *
 * @return array<string, mixed>|null
 */
function page_find(string $slug): ?array
{
	$page = Database::table('Pages')->find()->where(['slug' => $slug])->first();

	if ($page === null) {
		return null;
	}

	return [
		'slug' => (string) $page->slug,
		'title' => (string) $page->title,
		'lead' => (string) $page->lead,
		'body' => (string) $page->body,
		'updated_label' => (string) $page->updated_label,
	];
}

/**
 * Счётчики для шапки и подвала.
 *
 * @return array{articles: int, sources: int, tags: int, today: int}
 */
function site_stats(): array
{
	$articles = Database::table('Articles');
	$today = (new DateTimeImmutable('today'))->format('c');

	return [
		'articles' => (int) $articles->find()->count(),
		'sources' => (int) Database::table('Sources')->find()->count(),
		'tags' => (int) Database::table('Tags')->find()->count(),
		'today' => (int) $articles->find()->where(['published_at >=' => $today])->count(),
	];
}

/**
 * Живая лента для правой колонки: предупреждения из `feed_items` плюс
 * свежие материалы. В оригинале колонка была отдельным AJAX-виджетом с
 * девятью вкладками, и половина строк не вела никуда.
 *
 * @return list<array<string, mixed>>
 */
function live_stream(int $limit = 14): array
{
	$rows = [];

	foreach (feed_items_live($limit) as $item) {
		$rows[] = [
			'id' => 'f' . $item['id'],
			'kind' => $item['kind'],
			'title' => $item['title'],
			'url' => $item['url'],
			'time_label' => $item['time_label'],
			'iso' => $item['published_at'],
			'source' => $item['source'],
			'thumb' => '',
			'is_alert' => $item['is_alert'],
			'pinned' => $item['pinned'],
		];
	}

	foreach (articles_page($limit, 0)['items'] as $article) {
		$rows[] = [
			'id' => 'a' . $article['id'],
			'kind' => 'news',
			'title' => $article['title'],
			'url' => $article['url'],
			'time_label' => $article['time_label'],
			'iso' => $article['iso'],
			'source' => $article['source']['title'] ?? '',
			'thumb' => $article['cover'],
			'is_alert' => false,
			'pinned' => false,
		];
	}

	usort($rows, static function (array $a, array $b): int {
		// Закреплённые предупреждения — всегда сверху, дальше по времени.
		if ($a['pinned'] !== $b['pinned']) {
			return $a['pinned'] ? -1 : 1;
		}

		return strcmp($b['iso'], $a['iso']);
	});

	return array_slice($rows, 0, $limit);
}
