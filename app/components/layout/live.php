<?php

declare(strict_types=1);

/**
 * Колонка «Сейчас»: оперативные сообщения и свежие материалы одним потоком.
 *
 * В оригинале это был виджет с девятью вкладками и AJAX-подгрузкой: пока
 * вкладку не открыть, её содержимое не существовало. Здесь поток один и
 * читается сверху вниз, а предупреждения закреплены над ним.
 *
 * @param int    $limit
 * @param bool   $sticky
 * @param string $title
 * @param string $lead
 */

$limit ??= 12;
$sticky ??= true;
$title ??= 'Сейчас';
$lead ??= 'Оперативные сообщения и свежие материалы';

$rows = live_stream($limit);
?>

<aside class="<?= e(clsx('rounded-2xl border border-line bg-white/70', $sticky ? 'lg:sticky lg:top-28' : null)) ?>" aria-labelledby="live-title">
	<div class="flex items-center gap-2 border-b border-line px-4 py-3">
		<span class="relative inline-flex h-2 w-2">
			<span class="absolute inset-0 rounded-full bg-alert animate-ping [animation-duration:1.9s]"></span>
			<span class="relative inline-flex h-2 w-2 rounded-full bg-alert"></span>
		</span>
		<h2 id="live-title" class="text-sm font-semibold uppercase tracking-[0.12em] text-ink"><?= e($title) ?></h2>
		<a href="/lenta" class="ml-auto inline-flex items-center gap-1 text-[11px] font-medium text-muted transition-colors hover:text-brand">
			Вся лента
			<?= $this->insert('bolt://ui-kit/icon', ['name' => 'arrow-right', 'size' => 12]) ?>
		</a>
	</div>

	<p class="sr-only"><?= e($lead) ?></p>

	<ol class="divide-y divide-line/70">
		<?php foreach ($rows as $row): ?>
			<?php if ($row['is_alert']): ?>
				<li class="relative bg-alert-soft/50 px-4 py-3">
					<div class="flex items-start gap-2.5">
						<span class="mt-0.5 text-alert">
							<?= $this->insert('bolt://ui-kit/icon', ['name' => 'alert', 'size' => 15]) ?>
						</span>
						<div class="min-w-0">
							<p class="text-[13px] font-medium leading-snug text-alert"><?= e($row['title']) ?></p>
							<p class="mt-1 flex items-center gap-1.5 text-[11px] text-alert/70">
								<time datetime="<?= e($row['iso']) ?>"><?= e($row['time_label']) ?></time>
								<?php if (($row['source'] ?? '') !== ''): ?>
									<span aria-hidden="true">·</span>
									<span class="truncate"><?= e($row['source']) ?></span>
								<?php endif; ?>
							</p>
						</div>
					</div>
				</li>
			<?php else: ?>
				<li>
					<a href="<?= e($row['url']) ?>" class="group flex items-start gap-3 px-4 py-3 transition-colors hover:bg-paper-2/60">
						<time datetime="<?= e($row['iso']) ?>" class="w-10 shrink-0 pt-0.5 font-mono text-[11px] text-muted"><?= e($row['time_label']) ?></time>

						<?php if (($row['thumb'] ?? '') !== ''): ?>
							<span class="h-11 w-16 shrink-0 overflow-hidden rounded-md bg-paper-2">
								<img src="<?= e(image_url($row['thumb'], 'row')) ?>" alt="" width="200" height="130" loading="lazy" class="h-full w-full object-cover">
							</span>
						<?php endif; ?>

						<span class="min-w-0 flex-1">
							<span class="line-clamp-2 block text-[13px] leading-snug text-ink">
								<span class="<?= link_underline() ?>"><?= e($row['title']) ?></span>
							</span>
							<?php if (($row['source'] ?? '') !== ''): ?>
								<span class="mt-1 block truncate text-[11px] text-muted"><?= e($row['source']) ?></span>
							<?php endif; ?>
						</span>
					</a>
				</li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ol>

	<a href="/lenta" class="flex items-center justify-center gap-1.5 border-t border-line px-4 py-2.5 text-xs font-medium text-muted transition-colors hover:bg-paper-2/60 hover:text-ink">
		<?= $this->insert('bolt://ui-kit/icon', ['name' => 'refresh', 'size' => 13]) ?>
		Смотреть всю ленту
	</a>
</aside>
