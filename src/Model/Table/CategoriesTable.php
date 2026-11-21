<?php

declare(strict_types=1);

namespace Solovey\Model\Table;

use Cake\ORM\Table;

/**
 * Рубрики. Семь штук, порядок задаётся полем `sort`.
 */
final class CategoriesTable extends Table
{
	public function initialize(array $config): void
	{
		parent::initialize($config);

		$this->setTable('categories');
		$this->setPrimaryKey('id');

		$this->hasMany('Articles', [
			'className' => ArticlesTable::class,
			'foreignKey' => 'category_id',
		]);
	}
}
