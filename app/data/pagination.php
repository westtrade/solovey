<?php

declare(strict_types=1);

/**
 * Триггер «Показать ещё» для `bolt://ui-kit/pagination`.
 *
 * Своя реализация нужна только ради вида: компонент рисует голую ссылку с
 * классами `btn`, а нам нужна кнопка с иконкой и состоянием загрузки.
 * Атрибуты htmx (`hx-get`, `hx-target`, `hx-select`, `hx-swap`,
 * `hx-select-oob`, `hx-indicator`) берём из компонента как есть — они
 * рассчитаны на htmx 4, где `hx-select-oob` принимает CSS-селектор
 * (`fragment.querySelectorAll(selector)`).
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

			// Отступ от сетки: контейнер .pagination__more у Bolt идёт без
			// своих полей, поэтому обёртка с mt-7 живёт здесь, а не в CSS.
			$attrs['class'] = clsx(
				$attrs['class'] ?? null,
				'group flex w-full max-w-xs items-center justify-center gap-2 rounded-lg',
				'border border-line bg-white/70 px-5 py-2.5 text-sm font-medium text-ink-soft',
				'transition duration-200 hover:border-brand/40 hover:text-brand',
				// Состояние загрузки htmx вешает на саму кнопку — красим вариантом.
				'[&.htmx-request]:pointer-events-none [&.htmx-request]:opacity-60',
			);
			?>
			<div class="mt-7">
				<a <?= attrs($attrs) ?>>
					<span><?= e($label) ?></span>
					<span class="transition-transform duration-200 group-hover:translate-y-0.5"><?= icon_svg('chevron-down', 16) ?></span>
				</a>
			</div>
			<?php
		}
	};
}
