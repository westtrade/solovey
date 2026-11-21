<?php

declare(strict_types=1);

/**
 * Полоса оперативного предупреждения.
 *
 * В оригинале предупреждение МЧС было обычной строкой в правой колонке:
 * его можно было не заметить, прокрутив страницу. Здесь оно закреплено
 * над контентом, пока активно (последние три часа).
 */

$alert = alert_active();

if ($alert === null) {
	return;
}
?>
<div class="border-b border-alert/20 bg-alert-soft/80">
	<div class="mx-auto flex max-w-7xl items-start gap-3 px-4 py-2.5">
		<span class="relative mt-1 inline-flex h-2 w-2 shrink-0">
			<span class="absolute inset-0 rounded-full bg-alert animate-ping [animation-duration:1.9s]"></span>
			<span class="relative inline-flex h-2 w-2 rounded-full bg-alert"></span>
		</span>

		<p class="flex-1 text-sm leading-snug text-alert">
			<span class="font-semibold">Срочно.</span>
			<?= e($alert['title']) ?>
		</p>

		<span class="hidden shrink-0 items-center gap-2 text-xs text-alert/80 sm:flex">
			<?= $this->insert('bolt://ui-kit/icon', ['name' => 'clock', 'size' => 13]) ?>
			<?= e($alert['time_label']) ?>
			<?php if (($alert['source'] ?? '') !== ''): ?>
				<span aria-hidden="true">·</span>
				<?= e($alert['source']) ?>
			<?php endif; ?>
		</span>
	</div>
</div>
