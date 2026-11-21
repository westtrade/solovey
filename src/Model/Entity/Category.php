<?php

declare(strict_types=1);

namespace Solovey\Model\Entity;

use Cake\ORM\Entity;

/**
 * Рубрика. `url` и `count` добавляет выборка в `app/data`, потому что число
 * статей — агрегат, а не свойство строки.
 *
 * @property string $slug
 * @property string $title
 * @property string $lead
 * @property string $accent
 */
final class Category extends Entity
{
	protected array $_virtual = ['url'];

	protected function _getUrl(): string
	{
		return '/' . (string) $this->get('slug');
	}
}
