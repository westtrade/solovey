<?php

declare(strict_types=1);

/**
 * Шапка: дата, логотип, поиск, меню лент и рубрик.
 *
 * Три уровня вместо одного длинного ряда, как в оригинале: служебная строка
 * (дата, город, поиск), брендовая (логотип, поиск, RSS), навигация. На
 * мобильном служебная строка скрыта, а навигация прокручивается вбок.
 */

$feeds = site_feeds();
$categories = categories_all();
$stats = site_stats();
$today = (new DateTimeImmutable('now'))->format('c');
?>

<header x-data="siteHeader" class="sticky top-0 z-40">

	<?php // ─── Служебная строка ─────────────────────────────────────── ?>
	<div class="hidden border-b border-line/70 bg-paper/85 backdrop-blur sm:block">
		<div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-1.5 text-[11px] font-medium uppercase tracking-[0.14em] text-muted">
			<time datetime="<?= e($today) ?>" class="text-ink-soft"><?= e(ru_date($today)) ?></time>
			<span aria-hidden="true" class="h-1 w-1 rounded-full bg-line-strong"></span>
			<span class="flex items-center gap-1.5">
				<?= $this->insert('bolt://ui-kit/icon', ['name' => 'pin', 'size' => 12, 'class' => 'text-muted']) ?>
				Курск
			</span>

			<span class="ml-auto flex items-center gap-4">
				<span class="flex items-center gap-1.5 text-brand">
					<span class="relative inline-flex h-1.5 w-1.5">
						<span class="absolute inset-0 rounded-full bg-brand animate-ping [animation-duration:1.9s]"></span>
						<span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-brand"></span>
					</span>
					<?= e(ru_count($stats['today'], 'новость', 'новости', 'новостей')) ?> за сутки
				</span>
				<a href="/rss.xml" class="transition-colors hover:text-ink">RSS</a>
				<a href="/o-nas" class="transition-colors hover:text-ink">О проекте</a>
			</span>
		</div>
	</div>

	<div :class="scrolled ? 'bg-paper/95 shadow-[0_1px_0_0_var(--color-line)] backdrop-blur' : 'bg-paper'" class="transition-colors duration-300">
		<div class="mx-auto max-w-7xl px-4">

			<?php // ─── Брендовая строка ─────────────────────────────────── ?>
			<div class="flex items-center gap-4 py-3">

				<a href="/" class="group flex shrink-0 items-center gap-2.5">
					<span class="flex h-10 w-10 items-center justify-center rounded-xl bg-ink text-paper transition-transform duration-300 group-hover:-rotate-6">
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'bird', 'size' => 22]) ?>
					</span>
					<span class="leading-none">
						<span class="block font-display text-2xl tracking-tight text-ink">Соловей</span>
						<span class="mt-1 block text-[10px] font-medium uppercase tracking-[0.18em] text-muted">Курск и область</span>
					</span>
				</a>

				<?php // Поиск: на десктопе поле, на мобильном — кнопка-панель. ?>
				<form action="/poisk" method="get" role="search" aria-label="Поиск по сайту" x-data="searchShortcut" class="ml-auto hidden w-full max-w-sm items-center gap-2 md:flex">
					<label for="q-desktop" class="sr-only">Поиск по сайту</label>
					<div class="relative flex-1">
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'search', 'size' => 15, 'class' => 'pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted']) ?>
						<input
							id="q-desktop"
							x-ref="field"
							type="search"
							name="q"
							value="<?= e((string) ($_GET['q'] ?? '')) ?>"
							placeholder="Найти новость, тему, источник"
							autocomplete="off"
							class="w-full rounded-full border border-line bg-white/70 py-2 pl-9 pr-16 text-sm text-ink placeholder:text-muted/80 transition-colors focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/15"
						>
						<kbd class="pointer-events-none absolute right-2.5 top-1/2 hidden -translate-y-1/2 rounded border border-line bg-paper-2 px-1.5 py-0.5 font-mono text-[10px] text-muted lg:block">/</kbd>
					</div>
					<button type="submit" class="sr-only">Искать</button>
				</form>

				<div class="ml-auto flex items-center gap-1 md:ml-0">
					<a
						href="/rss.xml"
						class="hidden h-9 w-9 items-center justify-center rounded-full text-muted transition-colors hover:bg-paper-2 hover:text-ink sm:flex"
						title="RSS-лента"
						aria-label="RSS-лента"
					>
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'rss', 'size' => 17]) ?>
					</a>

					<button
						type="button"
						class="flex h-9 w-9 items-center justify-center rounded-full text-ink transition-colors hover:bg-paper-2 md:hidden"
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

			<?php // ─── Навигация ─────────────────────────────────────────── ?>
			<?php // Без overflow: прокрутка делала из nav scroll-контейнер и обрезала
			// выпадающий список рубрик. Не влезающие ленты скрываются по брейкпоинтам. ?>
			<nav class="hidden items-center gap-1 pb-2 md:flex" aria-label="Ленты и рубрики">
				<a
					href="/"
					class="<?= e(clsx(
						'rounded-full px-3 py-1.5 text-sm font-medium transition-colors',
						current_path() === '/' ? 'bg-ink text-paper' : 'text-ink-soft hover:bg-paper-2 hover:text-ink',
					)) ?>"
				>Главная</a>

				<?php foreach ($feeds as $feed): ?>
					<a
						href="<?= e($feed['url']) ?>"
						class="<?= e(clsx(
							'items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1.5 text-sm transition-colors',
							feed_nav_class($feed['nav']),
							nav_active($feed['url']) ? 'bg-ink text-paper' : 'text-ink-soft hover:bg-paper-2 hover:text-ink',
						)) ?>"
					>
						<?= $this->insert('bolt://ui-kit/icon', ['name' => $feed['icon'], 'size' => 14, 'class' => 'opacity-70']) ?>
						<?= e($feed['title']) ?>
					</a>
				<?php endforeach; ?>

				<span aria-hidden="true" class="mx-1 h-4 w-px bg-line"></span>

				<?php // Рубрики: раскрывающаяся панель, а не пункт меню. ?>
				<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
					<button
						type="button"
						class="flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1.5 text-sm text-ink-soft transition-colors hover:bg-paper-2 hover:text-ink"
						@click="open = !open"
						:aria-expanded="open"
						aria-controls="categories-panel"
					>
						Рубрики
						<span class="transition-transform duration-200" :class="open && 'rotate-180'">
							<?= $this->insert('bolt://ui-kit/icon', ['name' => 'chevron-down', 'size' => 14]) ?>
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

			<?php // ─── Мобильное меню ────────────────────────────────────── ?>
			<div id="mobile-menu" x-show="menuOpen" x-cloak x-transition.opacity.duration.200ms class="border-t border-line pb-4 pt-3 md:hidden">
				<form action="/poisk" method="get" role="search" class="mb-3 flex items-center gap-2">
					<label for="q-mobile" class="sr-only">Поиск по сайту</label>
					<div class="relative flex-1">
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'search', 'size' => 15, 'class' => 'pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted']) ?>
						<input
							id="q-mobile"
							type="search"
							name="q"
							value="<?= e((string) ($_GET['q'] ?? '')) ?>"
							placeholder="Найти новость"
							class="w-full rounded-full border border-line bg-white py-2 pl-9 pr-3 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/15"
						>
					</div>
					<button type="submit" class="rounded-full bg-ink px-4 py-2 text-sm text-paper">Искать</button>
				</form>

				<div class="grid grid-cols-2 gap-1">
					<a href="/" class="rounded-xl px-3 py-2 text-sm font-medium text-ink hover:bg-paper-2">Главная</a>
					<?php foreach ($feeds as $feed): ?>
						<a href="<?= e($feed['url']) ?>" class="rounded-xl px-3 py-2 text-sm text-ink-soft hover:bg-paper-2 hover:text-ink">
							<?= e($feed['title']) ?>
						</a>
					<?php endforeach; ?>
				</div>

				<p class="mt-4 px-3 text-[11px] font-medium uppercase tracking-[0.14em] text-muted">Рубрики</p>
				<div class="mt-1 grid grid-cols-2 gap-1">
					<?php foreach ($categories as $category): ?>
						<a href="<?= e($category['url']) ?>" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-ink-soft hover:bg-paper-2 hover:text-ink">
							<span class="<?= e(clsx('h-1.5 w-1.5 rounded-full', accent_classes($category['accent'])['dot'])) ?>"></span>
							<?= e($category['title']) ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>

		</div>
	</div>
</header>
