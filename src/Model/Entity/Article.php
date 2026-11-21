<?php

declare(strict_types=1);

namespace Solovey\Model\Entity;

use Cake\ORM\Entity;

/**
 * Новость.
 *
 * Производные поля считаются здесь, а не в шаблонах: адрес, человеческая дата
 * и группа «Сегодня / Вчера / 20 ноября» нужны в пяти местах, и одна копия
 * правил избавляет от расхождений.
 *
 * @property int         $id
 * @property int         $category_id
 * @property int|null    $source_id
 * @property string      $title
 * @property string      $lead
 * @property string      $body
 * @property string      $cover
 * @property string      $published_at
 * @property int         $views
 * @property int         $reading_time
 * @property bool        $is_top
 * @property bool        $is_important
 * @property bool        $is_video
 * @property bool        $is_longread
 * @property bool        $is_opinion
 * @property string      $feed_key
 * @property object|null $category
 * @property object|null $source
 * @property array|null  $tags
 */
final class Article extends Entity
{
	protected array $_virtual = ['url', 'time_label', 'time_group', 'human_date', 'iso'];

	protected array $_hidden = ['body'];

	/**
	 * Адрес карточки: `/society/2026/11/20/886667.html`.
	 *
	 * Путь повторяет оригинал, поэтому внешние ссылки и закладки продолжают
	 * работать — это условие переезда без потери трафика.
	 */
	protected function _getUrl(): string
	{
		$date = date_create((string) $this->get('published_at'));

		if ($date === false) {
			return '/';
		}

		$slug = (string) ($this->category->slug ?? 'other');

		return sprintf(
			'/%s/%s/%s/%s/%d.html',
			$slug,
			$date->format('Y'),
			$date->format('m'),
			$date->format('d'),
			(int) $this->get('id'),
		);
	}

	protected function _getTimeLabel(): string
	{
		$date = date_create((string) $this->get('published_at'));

		return $date === false ? '' : $date->format('H:i');
	}

	/**
	 * Заголовок группы в ленте: «Сегодня», «Вчера», «20 ноября».
	 *
	 * Оригинал показывал только `ЧЧ:ММ`, поэтому у записи недельной давности
	 * нельзя было понять дату.
	 */
	protected function _getTimeGroup(): string
	{
		$date = date_create((string) $this->get('published_at'));

		if ($date === false) {
			return '';
		}

		$today = new \DateTimeImmutable('today');
		$day = new \DateTimeImmutable($date->format('Y-m-d'));
		$diff = (int) $today->diff($day)->format('%r%a');

		if ($diff === 0) {
			return 'Сегодня';
		}

		if ($diff === -1) {
			return 'Вчера';
		}

		$months = [
			1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
			5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
			9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
		];

		return (int) $date->format('j') . ' ' . $months[(int) $date->format('n')];
	}

	protected function _getHumanDate(): string
	{
		$date = date_create((string) $this->get('published_at'));

		return $date === false ? '' : ru_date($date->format('c')) . ', ' . $date->format('H:i');
	}

	protected function _getIso(): string
	{
		return (string) $this->get('published_at');
	}
}
