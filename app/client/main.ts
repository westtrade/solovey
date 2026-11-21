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
 * Шапка: сворачивается после прокрутки, мобильное меню закрывается по Esc,
 * высота уходит в `--header-h`, чтобы колонка «Сейчас» вставала под шапку.
 *
 * `scrolled` — только фон. `compact` — свёрнутая шапка: на мобильном она не
 * сворачивается никогда, иначе от шапки не осталось бы ни одной строки.
 */
Alpine.data('siteHeader', () => ({
	scrolled: false,
	compact: false,
	menuOpen: false,
	searchOpen: false,

	init(): void {
		const wide = window.matchMedia('(min-width: 768px)');

		const sync = (): void => {
			this.scrolled = window.scrollY > 8;
			this.compact = this.scrolled && wide.matches;

			if (!this.scrolled) {
				this.searchOpen = false;
			}
		};

		sync();

		window.addEventListener('scroll', sync, { passive: true });
		wide.addEventListener('change', sync);

		// Шапка меняет высоту при сворачивании — отступы зависят от неё.
		new ResizeObserver(() => this.syncHeight()).observe((this as unknown as AlpineMagic).$el);

		window.addEventListener('keydown', (event: KeyboardEvent) => {
			if (event.key === 'Escape') {
				this.menuOpen = false;
			}
		});

		// «/» в свёрнутой шапке: поле брендовой строки скрыто, открываем панель.
		window.addEventListener('solovey:search', () => {
			if (this.compact) {
				this.openSearch();
			}
		});
	},

	syncHeight(): void {
		const height = Math.round((this as unknown as AlpineMagic).$el.getBoundingClientRect().height);
		document.documentElement.style.setProperty('--header-h', `${height}px`);
	},

	openSearch(): void {
		this.searchOpen = !this.searchOpen;

		if (!this.searchOpen) {
			return;
		}

		const magic = this as unknown as AlpineMagic;
		magic.$nextTick(() => {
			magic.$refs.compactField?.focus();
		});
	},

	toggleMenu(): void {
		this.menuOpen = !this.menuOpen;
	},
}));

/**
 * Поиск: «/» ставит фокус в поле, как в почте и трекерах.
 *
 * Компонент висит на форме, поэтому `$refs.field` — своё поле, а не первое
 * найденное в документе. В полях ввода и в модификаторах сочетание не
 * перехватываем: там «/» — обычный символ.
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

			event.preventDefault();

			const refs = (this as unknown as { $refs?: Record<string, HTMLInputElement> }).$refs;
			const field = refs?.field;

			// Поле есть, но шапка свёрнута — им нельзя пользоваться, просим панель.
			if (field === undefined || field.closest('[inert]') !== null) {
				window.dispatchEvent(new CustomEvent('solovey:search'));

				return;
			}

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
