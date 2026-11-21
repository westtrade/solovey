<?php

declare(strict_types=1);

namespace Solovey\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\Query\SelectQuery;

/**
 * Новости.
 *
 * Все выборки живут здесь, а не в шаблонах: страница получает готовый массив
 * из `app/data`, а правила «что считается лентой» описаны в одном файле.
 */
final class ArticlesTable extends Table
{
	public function initialize(array $config): void
	{
		parent::initialize($config);

		$this->setTable('articles');
		$this->setPrimaryKey('id');
		$this->belongsTo('Categories', [
			'className' => CategoriesTable::class,
			'foreignKey' => 'category_id',
			'joinType' => 'INNER',
		]);

		$this->belongsTo('Sources', [
			'className' => SourcesTable::class,
			'foreignKey' => 'source_id',
			'joinType' => 'LEFT',
		]);

		// Флаги объявлены в SQLite как INTEGER, но в PHP это bool: без явного
		// типа marshaller превращает false в NULL (IntegerType::marshal
		// отвергает нечисловые значения), и NOT NULL падает.
		foreach (['is_top', 'is_important', 'is_video', 'is_longread', 'is_opinion'] as $flag) {
			$this->getSchema()->setColumnType($flag, 'boolean');
		}

		$this->belongsToMany('Tags', [
			'className' => TagsTable::class,
			'foreignKey' => 'article_id',
			'targetForeignKey' => 'tag_id',
			'joinTable' => 'article_tags',
		]);
	}

	/**
	 * Свежее сверху. Явный порядок нужен везде: в SQLite у выборки без
	 * ORDER BY порядок строк не определён, и пагинация «прыгала» бы.
	 */
	public function findLatest(SelectQuery $query): SelectQuery
	{
		return $query->orderBy(['Articles.published_at' => 'DESC', 'Articles.id' => 'DESC']);
	}

	/**
	 * Лента: `all` — всё подряд, остальные ключи — флаг статьи.
	 */
	public function findFeed(SelectQuery $query, string $key): SelectQuery
	{
		$flags = [
			'top' => 'is_top',
			'hot' => 'is_important',
			'video' => 'is_video',
			'longread' => 'is_longread',
			'opinions' => 'is_opinion',
			'official' => 'feed_key',
			'smi' => 'feed_key',
			'tg' => 'feed_key',
		];

		if ($key === 'all') {
			return $query;
		}

		$flag = $flags[$key] ?? null;

		if ($flag === null) {
			return $query->where(['1 = 0']);
		}

		// Ленты «СМИ», «Официально», «Паблики» — это ключ ленты, а не флаг:
		// статья попадает ровно в одну такую ленту, флагов у неё может быть
		// несколько одновременно (топ + видео + лонгрид).
		if ($flag === 'feed_key') {
			return $query->where(['Articles.feed_key' => $key]);
		}

		return $query->where(['Articles.' . $flag => true]);
	}

	public function findSearch(SelectQuery $query, string $term): SelectQuery
	{
		$like = '%' . $term . '%';

		return $query->where([
			'OR' => [
				'Articles.title LIKE' => $like,
				'Articles.lead LIKE' => $like,
				'Articles.body LIKE' => $like,
			],
		]);
	}
}
