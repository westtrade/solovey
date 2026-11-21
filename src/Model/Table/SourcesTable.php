<?php

declare(strict_types=1);

namespace Solovey\Model\Table;

use Cake\ORM\Table;

/**
 * Источники: СМИ, Telegram-каналы, ведомства.
 *
 * `kind` — тип источника: `media`, `tg`, `gov`. Он же определяет, в какую
 * ленту попадает статья («СМИ», «Паблики», «Официально»).
 */
final class SourcesTable extends Table
{
	public function initialize(array $config): void
	{
		parent::initialize($config);

		$this->setTable('sources');
		$this->setPrimaryKey('id');

		$this->hasMany('Articles', [
			'className' => ArticlesTable::class,
			'foreignKey' => 'source_id',
		]);
	}
}
