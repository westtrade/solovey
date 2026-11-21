<?php

declare(strict_types=1);

namespace Solovey\Model\Entity;

use Cake\ORM\Entity;

/**
 * Источник публикации: СМИ, Telegram-канал, ведомство.
 *
 * @property string $slug
 * @property string $title
 * @property string $kind
 */
final class Source extends Entity
{
	protected array $_virtual = ['url', 'kind_label'];

	protected function _getUrl(): string
	{
		return '/istochnik/' . (string) $this->get('slug');
	}

	protected function _getKindLabel(): string
	{
		return match ((string) $this->get('kind')) {
			'tg' => 'Telegram',
			'gov' => 'Ведомство',
			default => 'СМИ',
		};
	}
}
