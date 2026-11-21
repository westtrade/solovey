<?php

declare(strict_types=1);

namespace Solovey\Model\Table;

use Cake\ORM\Table;

/**
 * Информационные страницы: о проекте, контакты, политика, правила.
 */
final class PagesTable extends Table
{
	public function initialize(array $config): void
	{
		parent::initialize($config);

		$this->setTable('pages');
		$this->setPrimaryKey('slug');
	}
}
