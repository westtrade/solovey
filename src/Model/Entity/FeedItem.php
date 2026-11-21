<?php

declare(strict_types=1);

namespace Solovey\Model\Entity;

use Cake\ORM\Entity;

/**
 * Строка оперативной ленты.
 *
 * @property int         $id
 * @property string      $kind
 * @property string      $title
 * @property string      $body
 * @property string      $published_at
 * @property bool        $pinned
 * @property string      $severity
 * @property object|null $source
 * @property object|null $article
 */
final class FeedItem extends Entity
{
	protected array $_virtual = ['time_label', 'url', 'is_alert'];

	protected function _getTimeLabel(): string
	{
		$date = date_create((string) $this->get('published_at'));

		return $date === false ? '' : $date->format('H:i');
	}

	protected function _getUrl(): string
	{
		return $this->article !== null ? (string) $this->article->url : '';
	}

	protected function _getIsAlert(): bool
	{
		return (string) $this->get('kind') === 'alert';
	}
}
