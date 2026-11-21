<?php

declare(strict_types=1);

/**
 * Карточка материала строкой.
 *
 * Сделана функцией, а не шаблоном, потому что используется в замыкании
 * пагинации: там нет `$this`, а значит нет и `insert()`.
 *
 * @param array<string, mixed> $article   материал из present_article()
 * @param string               $variant   hero | default | row | plain
 * @param array<string, mixed> $attributes атрибуты обёртки (id/class от пагинации)
 */
function article_card(array $article, string $variant = 'default', array $attributes = [], bool $showLead = true): string
{
	if ($article === []) {
		return '';
	}

	$accent = accent_classes((string) ($article['category_accent'] ?? 'slate'));
	$cover = (string) ($article['cover'] ?? '');
	$source = (string) ($article['source']['title'] ?? '');
	$url = (string) ($article['url'] ?? '#');
	$title = (string) ($article['title'] ?? '');
	$lead = (string) ($article['lead'] ?? '');
	$iso = (string) ($article['iso'] ?? '');
	$time = (string) ($article['time_label'] ?? '');
	$minutes = (int) ($article['reading_time'] ?? 1);
	$views = compact_number((int) ($article['views'] ?? 0));

	$flagLabel = match (true) {
		(bool) ($article['is_important'] ?? false) => 'Важно',
		(bool) ($article['is_longread'] ?? false) => 'Лонгрид',
		(bool) ($article['is_opinion'] ?? false) => 'Мнение',
		(bool) ($article['is_video'] ?? false) => 'Видео',
		default => null,
	};
	$flagIcon = match ($flagLabel) {
		'Важно' => 'flame',
		'Лонгрид' => 'book',
		'Мнение' => 'tag',
		'Видео' => 'video',
		default => null,
	};

	$wrapper = '<article ' . attrs($attributes) . ' class="' . e(clsx('group relative', $attributes['class'] ?? null)) . '">';
	$wrapperEnd = '</article>';

	$media = static function (string $preset, string $ratio, string $width, string $height, bool $eager = false) use ($cover, $title): string {
		if ($cover === '') {
			return '<div class="flex h-full w-full items-center justify-center bg-paper-2 text-line-strong">'
				. icon_svg('bird', 40) . '</div>';
		}

		return '<img src="' . e(image_url($cover, $preset)) . '" alt="' . e($title) . '"'
			. ' width="' . $width . '" height="' . $height . '"'
			. ($eager ? ' fetchpriority="high"' : ' loading="lazy"')
			. ' class="h-full w-full object-cover">';
	};

	if ($variant === 'hero') {
		return $wrapper
			. '<a href="' . e($url) . '" class="block">'
			. '<div class="grid gap-0 overflow-hidden rounded-2xl border border-line bg-white/80 card-lift md:grid-cols-[1.15fr_1fr]">'
			. '<div class="card-media relative aspect-[16/10] md:aspect-auto md:min-h-[22rem]">'
			. $media('cover', '16/10', '1200', '750', true)
			. '<span class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide ring-1 ' . e($accent['badge']) . '">'
			. e((string) ($article['category_title'] ?? '')) . '</span>'
			. '</div>'
			. '<div class="flex flex-col justify-between p-5 sm:p-6">'
			. '<div>'
			. '<div class="flex flex-wrap items-center gap-2 text-xs text-muted">'
			. '<span class="font-medium text-ink-soft">' . e((string) ($article['time_group'] ?? '')) . '</span>'
			. '<span aria-hidden="true">·</span>'
			. '<time datetime="' . e($iso) . '">' . e($time) . '</time>'
			. '<span aria-hidden="true">·</span>'
			. '<span class="inline-flex items-center gap-1">' . icon_svg('clock', 12) . e((string) $minutes) . ' мин</span>'
			. '</div>'
			. '<h2 class="mt-3 font-display text-2xl leading-tight text-ink sm:text-3xl">'
			. '<span class="link-underline">' . e($title) . '</span></h2>'
			. ($showLead && $lead !== '' ? '<p class="mt-3 line-clamp-3 text-sm leading-relaxed text-ink-soft">' . e($lead) . '</p>' : '')
			. '</div>'
			. '<div class="mt-5 flex items-center gap-2 text-xs text-muted">'
			. ($source !== '' ? '<span class="truncate">' . e($source) . '</span><span aria-hidden="true">·</span>' : '')
			. '<span class="inline-flex items-center gap-1">' . icon_svg('eye', 12) . e($views) . '</span>'
			. '</div>'
			. '</div></div></a>'
			. $wrapperEnd;
	}

	if ($variant === 'row') {
		return $wrapper
			. '<a href="' . e($url) . '" data-rise class="flex gap-3 rounded-xl p-1 transition-colors hover:bg-paper-2/70">'
			. ($cover !== ''
				? '<div class="card-media relative h-[4.5rem] w-[6rem] shrink-0 overflow-hidden rounded-lg">'
					. '<img src="' . e(image_url($cover, 'row')) . '" alt="" width="200" height="130" loading="lazy" class="h-full w-full object-cover">'
					. '</div>'
				: '')
			. '<div class="min-w-0 flex-1">'
			. '<div class="flex items-center gap-1.5 text-[11px] text-muted">'
			. '<time datetime="' . e($iso) . '">' . e($time) . '</time>'
			. '<span aria-hidden="true" class="h-1 w-1 rounded-full ' . e($accent['dot']) . '"></span>'
			. '<span class="truncate">' . e((string) ($article['category_title'] ?? '')) . '</span>'
			. '</div>'
			. '<h3 class="mt-1 line-clamp-2 text-sm font-medium leading-snug text-ink">'
			. '<span class="link-underline">' . e($title) . '</span></h3>'
			. '</div></a>'
			. $wrapperEnd;
	}

	if ($variant === 'plain') {
		return $wrapper
			. '<a href="' . e($url) . '" class="flex items-baseline gap-3 py-2.5">'
			. '<time datetime="' . e($iso) . '" class="w-10 shrink-0 font-mono text-xs text-muted">' . e($time) . '</time>'
			. '<h3 class="text-sm leading-snug text-ink"><span class="link-underline">' . e($title) . '</span></h3>'
			. '</a>'
			. $wrapperEnd;
	}

	return $wrapper
		. '<a href="' . e($url) . '" data-rise class="flex h-full flex-col overflow-hidden rounded-2xl border border-line bg-white/80 card-lift">'
		. '<div class="card-media relative aspect-[16/10]">'
		. $media('card', '16/10', '640', '400')
		. '<span class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ' . e($accent['badge']) . '">'
		. e((string) ($article['category_title'] ?? '')) . '</span>'
		. ($flagLabel !== null
			? '<span class="flag absolute right-3 top-3 inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold text-ink ring-1 ring-ink/10">'
				. icon_svg((string) $flagIcon, 12, 'text-alert') . e($flagLabel) . '</span>'
			: '')
		. '</div>'
		. '<div class="flex flex-1 flex-col p-4">'
		. '<div class="flex items-center gap-1.5 text-[11px] text-muted">'
		. '<time datetime="' . e($iso) . '">' . e($time) . '</time>'
		. '<span aria-hidden="true">·</span>'
		. '<span class="inline-flex items-center gap-1">' . icon_svg('clock', 11) . e((string) $minutes) . ' мин</span>'
		. '</div>'
		. '<h3 class="mt-1.5 font-display text-lg leading-snug text-ink"><span class="link-underline">' . e($title) . '</span></h3>'
		. ($showLead && $lead !== '' ? '<p class="mt-2 line-clamp-2 text-[13px] leading-relaxed text-ink-soft/90">' . e($lead) . '</p>' : '')
		. '<div class="mt-auto flex items-center justify-between gap-2 pt-3 text-[11px] text-muted">'
		. '<span class="truncate">' . e($source) . '</span>'
		. '<span class="inline-flex shrink-0 items-center gap-1">' . icon_svg('eye', 11) . e($views) . '</span>'
		. '</div></div></a>'
		. $wrapperEnd;
}
