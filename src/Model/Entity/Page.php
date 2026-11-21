<?php

declare(strict_types=1);

namespace Solovey\Model\Entity;

use Cake\ORM\Entity;

/**
 * Информационная страница.
 *
 * @property string $slug
 * @property string $title
 * @property string $lead
 * @property string $body
 * @property string $updated_at
 */
final class Page extends Entity
{
	protected array $_virtual = ['updated_label'];

	protected function _getUpdatedLabel(): string
	{
		$raw = (string) $this->get('updated_at');

		return $raw === '' ? '' : ru_date($raw);
	}
}
