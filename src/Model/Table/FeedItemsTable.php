<?php

declare(strict_types=1);

namespace Solovey\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\Query\SelectQuery;

/**
 * Строки оперативной ленты: новость, предупреждение, пост паблика.
 *
 * В оригинале это был `<div>` без сущности — предупреждение МЧС лежало в общем
 * потоке наравне с погодной новостью. Здесь у записи есть `kind` и `severity`,
 * поэтому алерт можно закрепить и показать отдельной полосой.
 */
final class FeedItemsTable extends Table
{
	public function initialize(array $config): void
	{
		parent::initialize($config);

		$this->setTable('feed_items');
		$this->setPrimaryKey('id');

		$this->belongsTo('Sources', [
			'className' => SourcesTable::class,
			'foreignKey' => 'source_id',
			'joinType' => 'LEFT',
		]);

		$this->getSchema()->setColumnType('pinned', 'boolean');

		$this->belongsTo('Articles', [
			'className' => ArticlesTable::class,
			'foreignKey' => 'article_id',
			'joinType' => 'LEFT',
		]);
	}

	/**
	 * Сначала закреплённые, затем по времени.
	 */
	public function findLive(SelectQuery $query): SelectQuery
	{
		return $query->orderBy(['FeedItems.pinned' => 'DESC', 'FeedItems.published_at' => 'DESC']);
	}
}
