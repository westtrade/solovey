<?php

declare(strict_types=1);

/**
 * Страница ошибки: 404, 400, 503.
 *
 * Сознательно не использует общий layout: шапка и подвал читают базу, а
 * ошибка 503 возникает как раз тогда, когда база недоступна. Никаких
 * запросов — только статичная разметка и ссылки.
 */

$code = (int) ($code ?? 404);
$error = is_array($error ?? null) ? $error : null;

$message = is_array($error) && isset($error['message'])
	? (string) $error['message']
	: 'Страница не найдена или была удалена.';

$titles = [
	400 => 'Некорректный запрос',
	404 => 'Страница не найдена',
	500 => 'Внутренняя ошибка',
	503 => 'Сервис недоступен',
];

$title = $titles[$code] ?? 'Ошибка';
$isServer = $code >= 500;

$hints = [
	404 => 'Возможно, материал переехал или ссылка устарела. Попробуйте поиск или ленту.',
	503 => 'База данных временно недоступна. Мы уже знаем и чиним — попробуйте через минуту.',
	400 => 'Проверьте параметры запроса и попробуйте ещё раз.',
];
$hint = $hints[$code] ?? 'Попробуйте вернуться на главную.';
?>
<!doctype html>
<html lang="ru">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#f7f4ef">
	<title><?= e($title) ?> — Соловей</title>
	<meta name="robots" content="noindex,follow">
	<?= app_assets() ?>
</head>

<body class="flex min-h-screen flex-col items-center justify-center bg-paper px-4 text-ink antialiased">
	<main class="w-full max-w-lg">
		<a href="/" class="inline-flex items-center gap-2.5">
			<span class="flex h-10 w-10 items-center justify-center rounded-lg bg-ink text-paper">
				<?= icon_svg('bird', 22) ?>
			</span>
			<span class="font-display text-xl">Соловей</span>
		</a>

		<p class="mt-10 font-display text-7xl leading-none tracking-tight text-ink/12"><?= e((string) $code) ?></p>
		<h1 class="mt-3 font-display text-2xl leading-tight text-ink"><?= e($title) ?></h1>
		<p class="mt-3 text-sm leading-relaxed text-ink-soft"><?= e($message) ?></p>
		<p class="mt-2 text-sm leading-relaxed text-muted"><?= e($hint) ?></p>

		<?php if ($isServer && is_array($exception ?? null)): ?>
			<pre class="mt-5 max-h-56 overflow-auto rounded-xl border border-line bg-white p-4 text-xs text-alert"><?= e((string) $exception) ?></pre>
		<?php endif; ?>

		<div class="mt-8 flex flex-wrap gap-2">
			<a href="/" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-paper transition-colors hover:bg-brand-dark">
				На главную
			</a>
			<a href="/lenta" class="rounded-lg border border-line bg-white px-4 py-2 text-sm font-medium text-ink-soft transition-colors hover:border-line-strong hover:text-ink">
				Лента новостей
			</a>
			<a href="/poisk" class="rounded-lg border border-line bg-white px-4 py-2 text-sm font-medium text-ink-soft transition-colors hover:border-line-strong hover:text-ink">
				Поиск
			</a>
		</div>
	</main>
</body>

</html>
