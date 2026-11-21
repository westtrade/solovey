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

/**
 * Шапка: фон появляется после прокрутки, мобильное меню закрывается по Esc.
 */
Alpine.data('siteHeader', () => ({
	scrolled: false,
	menuOpen: false,

	init(): void {
		this.scrolled = window.scrollY > 8;

		const onScroll = (): void => {
			this.scrolled = window.scrollY > 8;
		};

		window.addEventListener('scroll', onScroll, { passive: true });

		window.addEventListener('keydown', (event: KeyboardEvent) => {
			if (event.key === 'Escape') {
				this.menuOpen = false;
			}
		});
	},

	toggleMenu(): void {
		this.menuOpen = !this.menuOpen;
	},
}));

/**
 * Поиск: панель открывается по кнопке и по «/», фокус ставится в поле.
 */
Alpine.data('searchPanel', () => ({
	open: false,

	init(): void {
		window.addEventListener('keydown', (event: KeyboardEvent) => {
			const target = event.target as HTMLElement | null;
			const typing = target !== null && /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName);

			if (event.key === '/' && !typing) {
				event.preventDefault();
				this.show();
			}

			if (event.key === 'Escape') {
				this.hide();
			}
		});
	},

	show(): void {
		this.open = true;

		requestAnimationFrame(() => {
			const refs = (this as unknown as { $refs?: Record<string, HTMLElement> }).$refs;
			const field = refs?.field as HTMLInputElement | undefined;
			field?.focus();
			field?.select();
		});
	},

	hide(): void {
		this.open = false;
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
htmx.onLoad((root: Node) => {
	if (!(root instanceof Element)) {
		return;
	}

	root.querySelectorAll<HTMLElement>('[data-rise]').forEach((node, index) => {
		node.classList.add('rise');
		node.style.animationDelay = `${Math.min(index * 40, 240)}ms`;
	});
});

Alpine.start();
