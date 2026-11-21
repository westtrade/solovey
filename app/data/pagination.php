<?php

declare(strict_types=1);

/**
 * Триггер «Показать ещё» для `bolt://ui-kit/pagination`.
 *
 * Зачем свой: `hx-select-oob` в htmx 2 принимает **id** элемента, а не
 * CSS-селектор (`fragment.querySelector('#' + id)`). Компонент Bolt кладёт
 * туда `[data-pagination-more="…"]`, и htmx падает с
 * `SyntaxError: '#[data-pagination-more=…]' is not a valid selector`.
 * Здесь атрибут заменяется на id контейнера, который компонент и так
 * печатает: `pagination-<name>-container`.
 *
 * Заодно задаём вид кнопки: компонент рисует её сам, но с классами `btn`.
 */

/**
 * @param string $label Текст кнопки.
 * @return callable(array<int, array<string, mixed>>): void
 */
function pagination_trigger(string $label = 'Показать ещё'): callable
{
	return static function (array $items) use ($label): void {
		foreach ($items as $item) {
			$attrs = $item['attrs'] ?? [];

			if (!is_array($attrs)) {
				continue;
			}

			$name = (string) ($attrs['data-pagination-button'] ?? '');

			if ($name !== '') {
				$attrs['hx-select-oob'] = 'pagination-' . $name . '-container';
			}

			$attrs['class'] = clsx(
				$attrs['class'] ?? null,
				'group mx-auto flex w-full max-w-xs items-center justify-center gap-2 rounded-full',
				'border border-line bg-white/70 px-5 py-2.5 text-sm font-medium text-ink-soft',
				'transition-colors hover:border-brand/40 hover:text-brand',
			);
			?>
			<a <?= attrs($attrs) ?>>
				<span><?= e($label) ?></span>
				<span class="transition-transform duration-200 group-hover:translate-y-0.5"><?= icon_svg('chevron-down', 16) ?></span>
			</a>
			<?php
		}
	};
}
