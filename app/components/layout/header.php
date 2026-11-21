<?php

declare(strict_types=1);

/**
 * Шапка: бренд, поиск, действия, меню лент и рубрик.
 *
 * Две строки вместо трёх: служебная строка оригинала (дата, город, счётчик)
 * растворена в брендовой — она не стоит отдельного ряда пикселей. При
 * прокрутке на десктопе брендовая строка сворачивается, остаётся полоса
 * навигации с компактным знаком и кнопкой поиска: липкая шапка занимает
 * 88 px в начале страницы и 36 px при чтении.
 */
 
$feeds = site_feeds();
$categories = categories_all();
$stats = site_stats();
$today = (new DateTimeImmutable('now'))->format('c');
?>

<header x-data="siteHeader" class="sticky top-0 z-40" @keydown.escape.window="searchOpen = false">

	<div :class="scrolled ? 'bg-paper/95 shadow-[0_1px_0_0_var(--color-line)] backdrop-blur' : 'bg-paper'" class="transition-colors duration-300">

		<?php // ─── Служебная полоса ─────────────────────────────────────── ?>
		<?php // Тонкая чернильная полоса: дата, город, счётчик за сутки, RSS.
		// Остаётся на месте при прокрутке — это единственный «пульс» издания. ?>
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

			<?php // ─── Строка 1: бренд, поиск, действия ─────────────────────── ?>
			<?php // Сворачивается только на md+: на мобильном навигация живёт в
			// бургер-меню, и прятать единственную строку некуда. ?>
			<div
				class="flex items-center gap-3 py-2 md:overflow-hidden md:transition-[max-height,opacity,padding] md:duration-300"
				:class="compact ? 'md:max-h-0 md:py-0 md:opacity-0' : 'md:max-h-16 md:opacity-100'"
				:inert="compact ? true : null"
				:aria-hidden="compact ? 'true' : null"
			>

				<a href="/" class="group flex shrink-0 items-center gap-2.5">
					<span class="flex h-9 w-9 items-center justify-center rounded-lg bg-ink text-paper transition-transform duration-300 group-hover:-rotate-6">
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'bird', 'size' => 20]) ?>
					</span>
					<span class="leading-none">
						<span class="block font-display text-xl leading-tight tracking-tight text-ink">Соловей</span>
						<span class="mt-1 block text-xs leading-none text-muted">Курск и область</span>
					</span>
				</a>

				<?php // Поиск: на десктопе поле, на мобильном — внутри бургер-меню. ?>
				<form action="/poisk" method="get" role="search" aria-label="Поиск по сайту" x-data="searchShortcut" class="ml-auto hidden w-full max-w-xs items-center gap-2 md:flex">
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
							class="w-full rounded-lg border border-line bg-white/70 py-1.5 pl-9 pr-10 text-sm text-ink placeholder:text-muted/80 transition-colors focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/15"
						>
						<kbd class="pointer-events-none absolute right-2.5 top-1/2 hidden -translate-y-1/2 rounded border border-line bg-paper-2 px-1.5 py-0.5 font-mono text-[10px] text-muted lg:block">/</kbd>
					</div>
					<button type="submit" class="sr-only">Искать</button>
				</form>

				<div class="ml-auto flex items-center gap-1.5 md:ml-0">
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

			<?php // ─── Строка 2: навигация ───────────────────────────────────── ?>
			<?php // Без overflow: прокрутка делала из nav scroll-контейнер и обрезала
			// выпадающий список рубрик. Не влезающие ленты скрываются по брейкпоинтам. ?>
			<nav class="hidden items-center gap-1 pb-1.5 md:flex" aria-label="Ленты и рубрики">

				<?php // Компактный знак: появляется, когда брендовая строка свернулась. ?>
				<a
					href="/"
					x-show="compact"
					x-cloak
					class="mr-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-ink text-paper"
					aria-label="Соловей — на главную"
				>
					<?= $this->insert('bolt://ui-kit/icon', ['name' => 'bird', 'size' => 16]) ?>
				</a>

				<a
					href="/"
					class="<?= e(clsx(
						'rounded-lg px-2.5 py-1 text-sm font-medium transition-colors',
						current_path() === '/' ? 'bg-ink text-paper' : 'text-ink-soft hover:bg-paper-2 hover:text-ink',
					)) ?>"
				>Главная</a>

				<?php foreach ($feeds as $feed): ?>
					<a
						href="<?= e($feed['url']) ?>"
						class="<?= e(clsx(
							'items-center gap-1.5 whitespace-nowrap rounded-lg px-2.5 py-1 text-sm transition-colors',
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
						class="flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2.5 py-1 text-sm text-ink-soft transition-colors hover:bg-paper-2 hover:text-ink"
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

				<?php // В свёрнутой шапке поиск и RSS переезжают сюда. ?>
				<span class="ml-auto flex items-center gap-0.5">
					<button
						type="button"
						x-show="compact"
						x-cloak
						class="flex h-8 w-8 items-center justify-center rounded-lg text-muted transition-colors hover:bg-paper-2 hover:text-ink"
						@click="openSearch()"
						:aria-expanded="searchOpen"
						aria-controls="compact-search"
						aria-label="Поиск по сайту"
					>
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'search', 'size' => 16]) ?>
					</button>
					<a
						href="/rss.xml"
						x-show="compact"
						x-cloak
						class="flex h-8 w-8 items-center justify-center rounded-lg text-muted transition-colors hover:bg-paper-2 hover:text-ink"
						aria-label="RSS-лента"
					>
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'rss', 'size' => 16]) ?>
					</a>
				</span>
			</nav>

			<?php // ─── Мобильное меню ────────────────────────────────────── ?>
			<div id="mobile-menu" x-show="menuOpen" x-cloak x-transition.opacity.duration.200ms class="border-t border-line pb-4 pt-3 md:hidden">
				<form action="/poisk" method="get" role="search" aria-label="Поиск по сайту (мобильное меню)" class="mb-3 flex items-center gap-2">
					<label for="q-mobile" class="sr-only">Поиск по сайту</label>
					<div class="relative flex-1">
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'search', 'size' => 15, 'class' => 'pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted']) ?>
						<input
							id="q-mobile"
							type="search"
							name="q"
							value="<?= e((string) ($_GET['q'] ?? '')) ?>"
							placeholder="Найти новость"
							class="w-full rounded-lg border border-line bg-white py-2 pl-9 pr-3 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/15"
						>
					</div>
					<button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm text-paper">Искать</button>
				</form>

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

	<?php // ─── Поиск в свёрнутой шапке ─────────────────────────────────── ?>
	<div
		id="compact-search"
		x-show="searchOpen"
		x-cloak
		x-transition.opacity.duration.150ms
		class="absolute inset-x-0 top-full border-t border-line bg-paper/95 backdrop-blur"
	>
		<div class="mx-auto max-w-7xl px-4 py-3">
			<form action="/poisk" method="get" role="search" aria-label="Поиск по сайту (свёрнутая шапка)" class="flex items-center gap-2">
				<label for="q-compact" class="sr-only">Поиск по сайту</label>
				<div class="relative flex-1">
					<?= $this->insert('bolt://ui-kit/icon', ['name' => 'search', 'size' => 15, 'class' => 'pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted']) ?>
					<input
						id="q-compact"
						x-ref="compactField"
						type="search"
						name="q"
						value="<?= e((string) ($_GET['q'] ?? '')) ?>"
						placeholder="Найти новость, тему, источник"
						autocomplete="off"
						class="w-full rounded-lg border border-line bg-white py-2 pl-9 pr-3 text-sm text-ink placeholder:text-muted/80 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/15"
					>
				</div>
				<button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm text-paper">Искать</button>
			</form>
		</div>
	</div>
</header>
