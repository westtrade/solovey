<?php

declare(strict_types=1);

namespace Solovey\Model\Entity;

use Cake\ORM\Entity;

/**
 * Тема. В оригинале тем не было вовсе: контент доставался только через ленту
 * и семь рубрик.
 *
 * @property string $slug
 * @property string $title
 */
final class Tag extends Entity
{
	protected array $_virtual = ['url'];

	protected function _getUrl(): string
	{
		return '/tema/' . (string) $this->get('slug');
	}
}
