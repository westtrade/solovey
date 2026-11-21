<?php

declare(strict_types=1);

use Bolt\Core\Runtime;
use Solovey\Support\AppError;
use Solovey\Support\ErrorCodes;

/**
 * Хелперы проекта. Подключается composer'ом (autoload.files), поэтому доступны
 * и в шаблонах, и в CLI-скриптах.
 *
 * Функции выборок лежат в `app/data/`: они обращаются к CakePHP ORM и
 * возвращают готовые к рендеру массивы, чтобы шаблоны не знали про сущности.
 */

foreach (glob(__DIR__ . '/../data/*.php') ?: [] as $file) {
	require_once $file;
}

/**
 * Склонение существительного после числа: 1 новость, 2 новости, 5 новостей.
 *
 * @param list<string> $forms [одна, две, много]
 */
function ru_plural(int $count, array $forms): string
{
	$abs = abs($count) % 100;
	$last = $abs % 10;

	if ($abs > 10 && $abs < 20) {
		return $forms[2];
	}

	return match ($last) {
		1 => $forms[0],
		2, 3, 4 => $forms[1],
		default => $forms[2],
	};
}

/**
 * «5 новостей», «21 новость».
 */
function ru_count(int $count, string $one, string $few, string $many): string
{
	return $count . ' ' . ru_plural($count, [$one, $few, $many]);
}

/**
 * Время чтения по тексту: 180 слов в минуту, минимум одна минута.
 */
function reading_minutes(string $text): int
{
	$words = preg_split('/\s+/u', trim(strip_tags($text)), -1, PREG_SPLIT_NO_EMPTY);

	return max(1, (int) ceil(count($words ?: []) / 180));
}

/**
 * Абзацы текста статьи: пустые строки не рендерим.
 *
 * @return list<string>
 */
function text_paragraphs(string $text): array
{
	$parts = preg_split('/\n{2,}/u', trim($text)) ?: [];

	return array_values(array_filter(array_map('trim', $parts), static fn (string $p): bool => $p !== ''));
}

/**
 * Первая буква строки — для заглушки карточки без картинки.
 */
function initial_letter(string $text): string
{
	$text = trim($text);

	return $text === '' ? 'С' : mb_strtoupper(mb_substr($text, 0, 1));
}

/**
 * Цвет рубрики → классы бейджа.
 *
 * Tailwind собирает только те классы, которые видит в исходниках, поэтому
 * здесь полные строки, а не `bg-{$accent}-50`: конкатенация дала бы пустой CSS.
 *
 * @return array{badge: string, soft: string, text: string, dot: string, ring: string}
 */
function accent_classes(string $accent): array
{
	$map = [
		'sky' => [
			'badge' => 'bg-sky-50 text-sky-900 ring-sky-200',
			'soft' => 'bg-sky-50/70',
			'text' => 'text-sky-800',
			'dot' => 'bg-sky-500',
			'ring' => 'hover:border-sky-300',
		],
		'rose' => [
			'badge' => 'bg-rose-50 text-rose-900 ring-rose-200',
			'soft' => 'bg-rose-50/70',
			'text' => 'text-rose-800',
			'dot' => 'bg-rose-500',
			'ring' => 'hover:border-rose-300',
		],
		'violet' => [
			'badge' => 'bg-violet-50 text-violet-900 ring-violet-200',
			'soft' => 'bg-violet-50/70',
			'text' => 'text-violet-800',
			'dot' => 'bg-violet-500',
			'ring' => 'hover:border-violet-300',
		],
		'emerald' => [
			'badge' => 'bg-emerald-50 text-emerald-900 ring-emerald-200',
			'soft' => 'bg-emerald-50/70',
			'text' => 'text-emerald-800',
			'dot' => 'bg-emerald-500',
			'ring' => 'hover:border-emerald-300',
		],
		'amber' => [
			'badge' => 'bg-amber-50 text-amber-900 ring-amber-200',
			'soft' => 'bg-amber-50/70',
			'text' => 'text-amber-800',
			'dot' => 'bg-amber-500',
			'ring' => 'hover:border-amber-300',
		],
		'fuchsia' => [
			'badge' => 'bg-fuchsia-50 text-fuchsia-900 ring-fuchsia-200',
			'soft' => 'bg-fuchsia-50/70',
			'text' => 'text-fuchsia-800',
			'dot' => 'bg-fuchsia-500',
			'ring' => 'hover:border-fuchsia-300',
		],
		'slate' => [
			'badge' => 'bg-slate-100 text-slate-800 ring-slate-200',
			'soft' => 'bg-slate-50/70',
			'text' => 'text-slate-700',
			'dot' => 'bg-slate-500',
			'ring' => 'hover:border-slate-300',
		],
	];

	return $map[$accent] ?? $map['slate'];
}

/**
 * «сегодня», «вчера» — для строки даты в шапке.
 */
function human_day(string $iso): string
{
	$date = date_create($iso);

	if ($date === false) {
		return '';
	}

	$today = new DateTimeImmutable('today');
	$day = $date->setTime(0, 0);

	return match ($day->format('Y-m-d')) {
		$today->format('Y-m-d') => 'сегодня',
		$today->modify('-1 day')->format('Y-m-d') => 'вчера',
		default => ru_date($day),
	};
}

/**
 * Число просмотров в коротком виде: 4 981 → «4,9 тыс.».
 */
function compact_number(int $value): string
{
	if ($value < 1000) {
		return (string) $value;
	}

	// Округляем вниз: «4,9 тыс.» честнее, чем «5,0 тыс.» у четырёх тысяч.
	$thousands = floor($value / 100) / 10;

	return str_replace('.', ',', (string) $thousands) . ' тыс.';
}

/**
 * Иконка спрайта строкой.
 *
 * Нужна там, где HTML собирается в обычной функции, а не в шаблоне Plates:
 * карточки материалов рендерятся и внутри замыканий пагинации, где `$this`
 * недоступен. Спрайт в режиме `inline` уже вставлен в документ, поэтому
 * достаточно ссылки на символ.
 */
function icon_svg(string $name, int $size = 18, string $class = ''): string
{
	$symbol = e($name) . '-icon';

	return sprintf(
		'<svg class="%s" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
		. 'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
		. '<use href="#%s"></use></svg>',
		e(trim($class)),
		$size,
		$size,
		$symbol,
	);
}

/**
 * Тело статьи: локальные картинки идут через конвейер Bolt.
 *
 * В базе лежит уже очищенный HTML с путями `/media/...` — исходными
 * файлами по несколько мегабайт. Отдавать их в текст статьи нельзя,
 * поэтому каждый `<img>` переписывается на `/convert-image/...?preset=body`
 * и получает ленивую загрузку.
 */
function article_body(string $html): string
{
	if ($html === '') {
		return '';
	}

	$result = preg_replace_callback(
		'~<img\b([^>]*?)src="(/media/[^"]+)"([^>]*)>~i',
		static function (array $m): string {
			$before = $m[1];
			$after = $m[3];
			$attrs = $before . ' ' . $after;

			// Уже с lazy — не дублируем атрибут.
			$lazy = stripos($attrs, 'loading=') === false ? ' loading="lazy"' : '';
			$async = stripos($attrs, 'decoding=') === false ? ' decoding="async"' : '';

			return '<img' . $before . 'src="' . e(image_url($m[2], 'body')) . '"' . $after . $lazy . $async . '>';
		},
		$html,
	);

	return $result ?? $html;
}

/**
 * Простой текст информационной страницы → HTML.
 *
 * Поддерживает то, что реально встречается в текстах: абзацы, списки через
 * «- » и «1. », подзаголовки «## » и **выделение**. Полноценный Markdown
 * здесь не нужен: тексты пишет редакция, а не разработчик.
 */
function text_to_html(string $text): string
{
	$text = trim(str_replace(["\r\n", "\r"], "\n", $text));

	if ($text === '') {
		return '';
	}

	$blocks = preg_split('/\n{2,}/', $text) ?: [];
	$html = '';

	foreach ($blocks as $block) {
		$block = trim($block);

		if ($block === '') {
			continue;
		}

		$lines = explode("\n", $block);

		if (str_starts_with($lines[0], '## ')) {
			$html .= '<h2>' . e(mb_substr($lines[0], 3)) . '</h2>';
			continue;
		}

		if (preg_match('/^-\s+/u', $lines[0]) === 1) {
			$html .= '<ul>';
			foreach ($lines as $line) {
				$line = trim(preg_replace('/^-\s+/u', '', trim($line)) ?? '');
				if ($line !== '') {
					$html .= '<li>' . text_inline($line) . '</li>';
				}
			}
			$html .= '</ul>';
			continue;
		}

		if (preg_match('/^\d+\.\s+/u', $lines[0]) === 1) {
			$html .= '<ol>';
			foreach ($lines as $line) {
				$line = trim(preg_replace('/^\d+\.\s+/u', '', trim($line)) ?? '');
				if ($line !== '') {
					$html .= '<li>' . text_inline($line) . '</li>';
				}
			}
			$html .= '</ol>';
			continue;
		}

		$html .= '<p>' . text_inline(implode(' ', array_map('trim', $lines))) . '</p>';
	}

	return $html;
}

/**
 * Строчное форматирование: **жирный** и `код`.
 */
function text_inline(string $text): string
{
	$safe = e($text);
	$safe = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $safe) ?? $safe;

	return preg_replace('/`(.+?)`/u', '<code>$1</code>', $safe) ?? $safe;
}

/**
 * Остановить рендер ожидаемой ошибкой: «нет такой статьи», «нет рубрики».
 *
 * Исключение здесь не работает: ядро ловит любое `Throwable` и отдаёт 500,
 * теряя и код, и статус. Поэтому страница сама выставляет статус, рендерит
 * страницу ошибки и завершает запрос.
 *
 * @param array<string, scalar|null> $details
 */
function fail(string $code, array $details = []): never
{
	$error = AppError::make($code, $details);
	$status = ErrorCodes::get($code)['status'] ?? 500;

	if (!headers_sent()) {
		http_response_code($status);
	}

	echo Runtime::app()->renderError($status, [
		'code' => $status,
		'error' => $error->toArray(),
		'exception' => null,
	]);

	exit;
}

/**
 * Теги ассетов текущего приложения.
 *
 * `vite_assets()` без аргументов ничего не выводит: точки входа он ждёт
 * параметром. Список лежит в `config.php` → `vite.entries`, поэтому шаблон
 * не должен знать имена файлов.
 */
function app_assets(): string
{
	if (!Runtime::has()) {
		return '';
	}

	$entries = Runtime::app()->config()->get('vite.entries', []);

	return vite_assets(is_array($entries) ? $entries : []);
}

/**
 * Подчёркивание заголовка-ссылки: растёт слева при наведении на карточку.
 *
 * Живёт функцией, а не классом в CSS, потому что это чистые утилиты
 * Tailwind: градиент-подложка высотой 1px и переход по `background-size`.
 * Повторять такую строку в каждом шаблоне — хуже, чем одна функция.
 */
function link_underline(): string
{
	return 'bg-[linear-gradient(currentColor,currentColor)] bg-[length:0%_1px] bg-left-bottom bg-no-repeat '
		. 'transition-[background-size] duration-200 '
		. 'group-hover:bg-[length:100%_1px] hover:bg-[length:100%_1px]';
}
