<?php

declare(strict_types=1);

/**
 * Подвал: карта сайта, соцсети, правовые ссылки.
 *
 * В оригинале подвал был одной строкой копирайта и пятью ссылками — при
 * 23 типах страниц это единственная навигация «вниз».
 */

$feeds = site_feeds();
$categories = categories_all();
$stats = site_stats();
$year = (int) date('Y');
?>

<footer class="mt-16 border-t border-line bg-paper-2/60">
	<div class="mx-auto max-w-7xl px-4 py-12">

		<div class="grid gap-10 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">

			<div>
				<a href="/" class="flex items-center gap-2.5">
					<span class="flex h-9 w-9 items-center justify-center rounded-xl bg-ink text-paper">
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'bird', 'size' => 20]) ?>
					</span>
					<span class="font-display text-xl text-ink">Соловей</span>
				</a>

				<p class="mt-4 max-w-sm text-sm leading-relaxed text-ink-soft">
					Городское издание о Курске и области. Собираем сообщения редакции,
					районных газет, официальных каналов и городских пабликов — и приводим
					их к одному виду: заголовок, суть, источник, время.
				</p>

				<div class="mt-5 flex items-center gap-1.5">
					<a href="https://t.me/" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full border border-line bg-white/60 text-muted transition-colors hover:border-brand/40 hover:text-brand" aria-label="Telegram">
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'telegram', 'size' => 16]) ?>
					</a>
					<a href="https://vk.com/" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full border border-line bg-white/60 text-muted transition-colors hover:border-brand/40 hover:text-brand" aria-label="ВКонтакте">
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'vk', 'size' => 16]) ?>
					</a>
					<a href="https://ok.ru/" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full border border-line bg-white/60 text-muted transition-colors hover:border-brand/40 hover:text-brand" aria-label="Одноклассники">
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'ok', 'size' => 16]) ?>
					</a>
					<a href="/rss.xml" class="flex h-9 w-9 items-center justify-center rounded-full border border-line bg-white/60 text-muted transition-colors hover:border-brand/40 hover:text-brand" aria-label="RSS">
						<?= $this->insert('bolt://ui-kit/icon', ['name' => 'rss', 'size' => 16]) ?>
					</a>
				</div>
			</div>

			<nav aria-labelledby="footer-categories">
				<h2 id="footer-categories" class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">Рубрики</h2>
				<ul class="mt-3 space-y-1.5">
					<?php foreach ($categories as $category): ?>
						<li>
							<a href="<?= e($category['url']) ?>" class="group -mx-1 inline-flex items-center gap-2 rounded-lg px-1 py-1 text-sm text-ink-soft transition-colors hover:bg-paper-3/60 hover:text-ink">
								<span class="<?= e(clsx('h-1.5 w-1.5 rounded-full transition-transform group-hover:scale-125', accent_classes($category['accent'])['dot'])) ?>"></span>
								<?= e($category['title']) ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<nav aria-labelledby="footer-feeds">
				<h2 id="footer-feeds" class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">Ленты</h2>
				<ul class="mt-3 space-y-1.5">
					<?php foreach ($feeds as $feed): ?>
						<li>
							<a href="<?= e($feed['url']) ?>" class="text-sm text-ink-soft transition-colors hover:text-ink"><?= e($feed['title']) ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<nav aria-labelledby="footer-about">
				<h2 id="footer-about" class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">Издание</h2>
				<ul class="mt-3 space-y-1.5">
					<li><a href="/o-nas" class="text-sm text-ink-soft transition-colors hover:text-ink">О проекте</a></li>
					<li><a href="/kontakty" class="text-sm text-ink-soft transition-colors hover:text-ink">Контакты</a></li>
					<li><a href="/redakciya" class="text-sm text-ink-soft transition-colors hover:text-ink">Редакция и политика</a></li>
					<li><a href="/poisk" class="text-sm text-ink-soft transition-colors hover:text-ink">Поиск</a></li>
					<li><a href="/rss.xml" class="text-sm text-ink-soft transition-colors hover:text-ink">RSS-лента</a></li>
				</ul>

				<p class="mt-5 text-xs leading-relaxed text-muted">
					<?= e($stats['articles']) ?> материалов,
					<?= e($stats['sources']) ?> источников,
					<?= e($stats['tags']) ?> тем.
				</p>
			</nav>
		</div>

		<div class="mt-10 flex flex-col gap-3 border-t border-line pt-6 text-xs text-muted sm:flex-row sm:items-center sm:justify-between">
			<p>© <?= $year ?> «Соловей». Все права на материалы принадлежат их источникам.</p>
			<div class="flex flex-wrap items-center gap-4">
				<a href="/privacy" class="transition-colors hover:text-ink">Политика конфиденциальности</a>
				<a href="/pravila" class="transition-colors hover:text-ink">Правила комментирования</a>
				<a href="/redakciya" class="transition-colors hover:text-ink">Редакционная политика</a>
			</div>
		</div>
	</div>
</footer>
