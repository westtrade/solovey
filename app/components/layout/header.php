<?php

declare(strict_types=1);

/**
 * Шапка: служебная полоса и одна строка навигации.
 *
 * Полоса (дата, город, счётчик за сутки, RSS) — 24 px чернил, она не
 * двигается. Под ней ровно одна строка: знак, ленты, рубрики, поле поиска.
 * Ничего не сворачивается и не прячется: поиск виден всегда, на всех
 * ширинах, без кнопок и выпадающих панелей. Меняется только фон — на
 * прокрутке он становится полупрозрачным.
 *
 * Всё девять лент в строку не влезают на планшете, поэтому второстепенные
 * пункты появляются по мере ширины (см. `nav` в site_feeds()), а полный
 * список всегда есть в бургер-меню и подвале. Иконки в пунктах меню убраны:
 * на 1248 px они стоили почти 200 px ширины, а различать ленты по подписи
 * проще, чем по пиктограмме.
 */

$feeds = site_feeds();
$categories = categories_all();
$stats = site_stats();
$today = (new DateTimeImmutable('now'))->format('c');
?>

<header x-data="siteHeader" class="sticky top-0 z-40">

	<div :class="scrolled ? 'bg-paper/95 shadow-[0_1px_0_0_var(--color-line)] backdrop-blur' : 'bg-paper'" class="transition-colors duration-300">

		<?php // ─── Служебная полоса ─────────────────────────────────────── ?>
		<div class="hidden bg-ink sm:block">
			<div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-1 text-xs text-paper/60">
				<time datetime="<?= e($today) ?>" class="text-paper/85"><?= e(ru_date($today)) ?></time>
				<span aria-hidden="true" class="h-1 w-1 rounded-full bg-paper/25"></span>
				<span class="flex items-center gap-1.5">
					<?= $this->insert('bolt://ui-kit/icon', ['name' => 'pin', 'size' => 12, 'class' => 'text-paper/45']) ?>
					Курск
				</span>

				<span class="ml-auto flex items-center gap-4">
					<a href="/lenta" class="flex items-center gap-1.5 text-brand-light transition-colors hover:text-paper">
						<span class="relative inline-flex h-1.5 w-1.5">
							<span class="absolute inset-0 rounded-full bg-brand-light animate-ping [animation-duration:1.9s]"></span>
							<span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-brand-light"></span>
						</span>
						<span class="font-mono tabular-nums"><?= e((string) $stats['today']) ?></span>
						<?= e(ru_count($stats['today'], 'новость', 'новости', 'новостей')) ?> за сутки
					</a>
					<a href="/rss.xml" class="transition-colors hover:text-paper">RSS</a>
					<a href="/o-nas" class="transition-colors hover:text-paper">О проекте</a>
				</span>
			</div>
		</div>

		<div class="mx-auto max-w-7xl px-4">
			<div class="flex h-14 items-center gap-2 sm:gap-3">

				<?php // ─── Знак ──────────────────────────────────────────────── ?>
				<a href="/" class="group flex shrink-0 items-center gap-2">
					<span class="flex h-8 w-8 items-center justify-center rounded-lg bg-ink text-paper transition-transform duration-300 group-hover:-rotate-6">
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'bird', 'size' => 18]) ?>
					</span>
					<span class="leading-none">
						<span class="block font-display text-lg leading-none tracking-tight text-ink">Соловей</span>
						<span class="mt-1 hidden text-[11px] leading-none text-muted sm:block">Курск и область</span>
					</span>
				</a>

				<?php // ─── Навигация: одна строка ───────────────────────────── ?>
				<?php // Без overflow: прокрутка делала из nav scroll-контейнер и обрезала
				// выпадающий список рубрик. Не влезающие ленты скрываются по брейкпоинтам. ?>
				<nav class="hidden min-w-0 items-center gap-0.5 md:flex" aria-label="Ленты и рубрики">
					<a
						href="/"
						class="<?= e(clsx(
							'whitespace-nowrap rounded-lg px-2.5 py-1.5 text-sm font-medium transition-colors',
							current_path() === '/' ? 'bg-ink text-paper' : 'text-ink-soft hover:bg-paper-2 hover:text-ink',
						)) ?>"
					>Главная</a>

					<?php foreach ($feeds as $feed): ?>
						<a
							href="<?= e($feed['url']) ?>"
							class="<?= e(clsx(
								'whitespace-nowrap rounded-lg px-2.5 py-1.5 text-sm transition-colors',
								feed_nav_class($feed['nav']),
								nav_active($feed['url']) ? 'bg-ink text-paper' : 'text-ink-soft hover:bg-paper-2 hover:text-ink',
							)) ?>"
						><?= e($feed['title']) ?></a>
					<?php endforeach; ?>

					<?php // Рубрики: раскрывающаяся панель, а не пункт меню. ?>
					<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
						<button
							type="button"
							class="flex items-center gap-1 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-sm text-ink-soft transition-colors hover:bg-paper-2 hover:text-ink"
							@click="open = !open"
							:aria-expanded="open"
							aria-controls="categories-panel"
						>
							Рубрики
							<span class="transition-transform duration-200" :class="open && 'rotate-180'">
								<?= $this->insert('bolt://ui-kit/icon', ['name' => 'chevron-down', 'size' => 13]) ?>
							</span>
						</button>

						<div
							id="categories-panel"
							x-show="open"
							x-cloak
							x-transition.opacity.duration.150ms
							@click.outside="open = false"
							class="absolute right-0 top-full z-50 mt-2 w-[min(20rem,calc(100vw-2rem))] rounded-2xl border border-line bg-white p-3 shadow-[var(--shadow-lift)] lg:w-[min(34rem,calc(100vw-2rem))]"
						>
							<div class="grid grid-cols-1 gap-1 lg:grid-cols-2">
								<?php foreach ($categories as $category): ?>
									<?php $accent = accent_classes($category['accent']); ?>
									<a
										href="<?= e($category['url']) ?>"
										class="<?= e(clsx('group flex items-start gap-2.5 rounded-xl px-3 py-2 transition-colors hover:bg-paper-2', $accent['ring'])) ?>"
									>
										<span class="<?= e(clsx('mt-1.5 h-2 w-2 shrink-0 rounded-full', $accent['dot'])) ?>"></span>
										<span>
											<span class="block text-sm font-medium text-ink"><?= e($category['title']) ?></span>
											<span class="mt-0.5 block text-xs text-muted"><?= e(ru_count($category['total'], 'материал', 'материала', 'материалов')) ?></span>
										</span>
									</a>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</nav>

				<?php // ─── Поиск: всегда открыт, без кнопок и панелей ─────────── ?>
				<form
					action="/poisk"
					method="get"
					role="search"
					aria-label="Поиск по сайту"
					x-data="searchShortcut"
					class="relative ml-auto w-full min-w-0 max-w-[22rem] flex-1 md:ml-3 md:w-auto md:flex-none md:basis-56 lg:basis-64"
				>
					<label for="q" class="sr-only">Поиск по сайту</label>
					<?= $this->insert('bolt://ui-kit/icon', ['name' => 'search', 'size' => 15, 'class' => 'pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted']) ?>
					<input
						id="q"
						x-ref="field"
						type="search"
						name="q"
						value="<?= e((string) ($_GET['q'] ?? '')) ?>"
						placeholder="Найти новость"
						autocomplete="off"
						class="w-full min-w-0 rounded-lg border border-line bg-white/70 py-1.5 pl-9 pr-8 text-sm text-ink placeholder:text-muted/80 transition-colors focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/15"
					>
					<kbd class="pointer-events-none absolute right-2 top-1/2 hidden -translate-y-1/2 rounded border border-line bg-paper-2 px-1 py-0.5 font-mono text-[10px] leading-none text-muted lg:block">/</kbd>
					<button type="submit" class="sr-only">Искать</button>
				</form>

				<?php // ─── Действия ──────────────────────────────────────────── ?>
				<div class="flex shrink-0 items-center gap-1">
					<a
						href="/rss.xml"
						class="hidden h-9 w-9 items-center justify-center rounded-lg text-muted transition-colors hover:bg-paper-2 hover:text-ink md:flex"
						title="RSS-лента"
						aria-label="RSS-лента"
					>
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'rss', 'size' => 16]) ?>
					</a>

					<button
						type="button"
						class="flex h-9 w-9 items-center justify-center rounded-lg text-ink transition-colors hover:bg-paper-2 md:hidden"
						@click="menuOpen = !menuOpen"
						:aria-expanded="menuOpen"
						aria-controls="mobile-menu"
						aria-label="Меню"
					>
						<span x-show="!menuOpen"><?= $this->insert('bolt://ui-kit/icon', ['name' => 'menu', 'size' => 20]) ?></span>
						<span x-show="menuOpen" x-cloak><?= $this->insert('bolt://ui-kit/icon', ['name' => 'close', 'size' => 20]) ?></span>
					</button>
				</div>
			</div>

			<?php // ─── Мобильное меню ────────────────────────────────────── ?>
			<div id="mobile-menu" x-show="menuOpen" x-cloak x-transition.opacity.duration.200ms class="border-t border-line pb-4 pt-3 md:hidden">
				<div class="grid grid-cols-2 gap-1">
					<a href="/" class="rounded-lg px-3 py-2 text-sm font-medium text-ink hover:bg-paper-2">Главная</a>
					<?php foreach ($feeds as $feed): ?>
						<a href="<?= e($feed['url']) ?>" class="rounded-lg px-3 py-2 text-sm text-ink-soft transition-colors hover:bg-paper-2 hover:text-ink">
							<?= e($feed['title']) ?>
						</a>
					<?php endforeach; ?>
				</div>

				<div class="mt-3 flex items-center gap-1 border-t border-line pt-3">
					<a href="/rss.xml" class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm text-ink-soft transition-colors hover:bg-paper-2 hover:text-ink">
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'rss', 'size' => 15]) ?>
						RSS
					</a>
					<a href="/o-nas" class="rounded-lg px-3 py-2 text-sm text-ink-soft transition-colors hover:bg-paper-2 hover:text-ink">О проекте</a>
				</div>

				<p class="mt-4 px-3 text-xs font-medium text-muted">Рубрики</p>
				<div class="mt-1 grid grid-cols-2 gap-1">
					<?php foreach ($categories as $category): ?>
						<a href="<?= e($category['url']) ?>" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-ink-soft transition-colors hover:bg-paper-2 hover:text-ink">
							<span class="<?= e(clsx('h-1.5 w-1.5 rounded-full', accent_classes($category['accent'])['dot'])) ?>"></span>
							<?= e($category['title']) ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>

		</div>
	</div>

</header>
