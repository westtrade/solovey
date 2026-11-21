import './styles.css';

import Alpine from 'alpinejs';
import htmx from 'htmx.org';

/**
 * Клиент «Соловья»: три маленьких поведения, а не SPA.
 *
 *   Alpine — шапка, поиск, кнопка «поделиться», прогресс чтения;
 *   htmx   — догрузка ленты и переходы по «Показать ещё»;
 *   CSS    — все микроанимации (наведение карточек, пульс алерта).
 */

declare global {
	interface Window {
		Alpine: typeof Alpine;
		htmx: typeof htmx;
	}
}

window.Alpine = Alpine;
window.htmx = htmx;

/** Магия Alpine, которой нет в типе компонента. */
type AlpineMagic = {
	$el: HTMLElement;
	$refs: Record<string, HTMLElement | undefined>;
	$nextTick: (callback: () => void) => void;
};

/**
 * Шапка: фон после прокрутки и мобильное меню по Esc.
 *
 * Высота шапки уходит в `--header-h` — от неё считаются отступ липкой
 * колонки «Сейчас» и `scroll-padding-top` у якорей.
 */
Alpine.data('siteHeader', () => ({
	scrolled: false,
	menuOpen: false,

	init(): void {
		const sync = (): void => {
			this.scrolled = window.scrollY > 8;
		};

		sync();
		window.addEventListener('scroll', sync, { passive: true });

		new ResizeObserver(() => this.syncHeight()).observe((this as unknown as AlpineMagic).$el);

		window.addEventListener('keydown', (event: KeyboardEvent) => {
			if (event.key === 'Escape') {
				this.menuOpen = false;
			}
		});
	},

	syncHeight(): void {
		const height = Math.round((this as unknown as AlpineMagic).$el.getBoundingClientRect().height);
		document.documentElement.style.setProperty('--header-h', `${height}px`);
	},

	toggleMenu(): void {
		this.menuOpen = !this.menuOpen;
	},
}));

/**
 * Поиск: «/» ставит фокус в поле — оно в шапке и видно всегда.
 *
 * Компонент висит на форме, поэтому `$refs.field` — своё поле, а не первое
 * найденное в документе. В полях ввода и с модификаторами «/» не
 * перехватываем: там это обычный символ.
 */
Alpine.data('searchShortcut', () => ({
	init(): void {
		window.addEventListener('keydown', (event: KeyboardEvent) => {
			if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) {
				return;
			}

			const target = event.target as HTMLElement | null;

			if (target !== null && /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName)) {
				return;
			}

			const field = (this as unknown as AlpineMagic).$refs.field as HTMLInputElement | undefined;

			if (field === undefined) {
				return;
			}

			event.preventDefault();
			field.focus();
			field.select();
		});
	},
}));

/**
 * «Поделиться»: системный диалог, если он есть, иначе копирование ссылки.
 * Состояние `copied` живёт две секунды — этого хватает, чтобы увидеть ответ.
 */
Alpine.data('shareButton', (url: string, title: string) => ({
	copied: false,

	async share(): Promise<void> {
		const data = { title, url };

		if (typeof navigator.share === 'function') {
			try {
				await navigator.share(data);

				return;
			} catch {
				// Пользователь отменил диалог — падать некуда, идём к копированию.
			}
		}

		try {
			await navigator.clipboard.writeText(url);
			this.copied = true;
			window.setTimeout(() => {
				this.copied = false;
			}, 2000);
		} catch {
			window.prompt('Скопируйте ссылку', url);
		}
	},
}));

/**
 * Прогресс чтения статьи: масштаб полосы считается от прокрутки документа.
 */
Alpine.data('readingProgress', () => ({
	progress: 0,

	init(): void {
		const update = (): void => {
			const doc = document.documentElement;
			const max = doc.scrollHeight - doc.clientHeight;
			this.progress = max > 0 ? Math.min(1, doc.scrollTop / max) : 0;
		};

		update();
		window.addEventListener('scroll', update, { passive: true });
		window.addEventListener('resize', update);
	},
}));

/**
 * htmx: сохраняем позицию прокрутки и не даём кнопке «Показать ещё» прыгать.
 * Плюс плавное появление догруженных карточек.
 */
htmx.onLoad((root: Element) => {
	root.querySelectorAll<HTMLElement>('[data-rise]').forEach((node, index) => {
		node.classList.add('animate-rise');
		node.style.animationDelay = `${Math.min(index * 40, 240)}ms`;
	});
});

Alpine.start();
