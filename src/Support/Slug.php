<?php

declare(strict_types=1);

namespace Solovey\Support;

/**
 * Транслитерация в slug: «Курская область» → `kurskaya-oblast`.
 *
 * Нужна сидеру и любым будущим импортам: URL сайта латинские, а заголовки
 * источников и тем — русские.
 */
final class Slug
{
	private const MAP = [
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
		'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k',
		'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
		'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
		'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
		'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
	];

	public static function make(string $text, int $maxLength = 60): string
	{
		$text = mb_strtolower(trim($text));
		$text = strtr($text, self::MAP);
		$text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
		$text = trim($text, '-');

		if (mb_strlen($text) > $maxLength) {
			$text = rtrim(mb_substr($text, 0, $maxLength), '-');
		}

		return $text === '' ? 'item' : $text;
	}
}
