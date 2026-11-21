<?php

declare(strict_types=1);

/**
 * Преобразование сущности статьи в массив для шаблона.
 *
 * Одна точка: адрес, человеческая дата, подпись рубрики и источника. Шаблон
 * карточки, карточки статьи и RSS получают одинаковую форму.
 */

use Solovey\Model\Entity\Article;

/**
 * @return array<string, mixed>
 */
function present_article(Article $article): array
{
	$category = $article->category;
	$source = $article->source;

	$tags = [];

	foreach ($article->tags ?? [] as $tag) {
		$tags[] = [
			'id' => (int) $tag->id,
			'slug' => (string) $tag->slug,
			'title' => (string) $tag->title,
			'url' => (string) $tag->url,
		];
	}

	$body = (string) $article->body;

	return [
		'id' => (int) $article->id,
		'category_id' => (int) $article->category_id,
		'category_slug' => $category !== null ? (string) $category->slug : 'other',
		'category_title' => $category !== null ? (string) $category->title : 'Разное',
		'category_accent' => $category !== null ? (string) $category->accent : 'slate',
		'title' => (string) $article->title,
		'lead' => (string) $article->lead,
		'body' => $body,
		'cover' => (string) $article->cover,
		'url' => (string) $article->url,
		'iso' => (string) $article->iso,
		'human_date' => (string) $article->human_date,
		'time_label' => (string) $article->time_label,
		'time_group' => (string) $article->time_group,
		'reading_time' => (int) $article->reading_time,
		'views' => (int) $article->views,
		'is_top' => (bool) $article->is_top,
		'is_important' => (bool) $article->is_important,
		'is_video' => (bool) $article->is_video,
		'is_longread' => (bool) $article->is_longread,
		'is_opinion' => (bool) $article->is_opinion,
		'feed_key' => (string) $article->feed_key,
		'source' => $source !== null
			? [
				'id' => (int) $source->id,
				'slug' => (string) $source->slug,
				'title' => (string) $source->title,
				'kind' => (string) $source->kind,
				'kind_label' => (string) $source->kind_label,
				'url' => (string) $source->url,
			]
			: null,
		'tags' => $tags,
		// Поля, которые нужны компоненту пагинации: он дописывает id и класс
		// в тот же массив, поэтому id здесь уже есть.
		'class' => '',
	];
}
