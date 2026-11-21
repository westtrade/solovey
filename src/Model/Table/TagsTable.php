<?php

declare(strict_types=1);

namespace Solovey\Model\Table;

use Cake\ORM\Table;

/**
 * Темы. Привязаны к статьям через `article_tags`.
 */
final class TagsTable extends Table
{
	public function initialize(array $config): void
	{
		parent::initialize($config);

		$this->setTable('tags');
		$this->setPrimaryKey('id');

		$this->belongsToMany('Articles', [
			'className' => ArticlesTable::class,
			'foreignKey' => 'tag_id',
			'targetForeignKey' => 'article_id',
			'joinTable' => 'article_tags',
		]);
	}
}
