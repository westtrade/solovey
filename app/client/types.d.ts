/**
 * Alpine поставляется без деклараций типов: у пакета нет `types` в
 * package.json. Объявляем минимум, который использует проект, чтобы
 * `tsc --noEmit` проверял наш код, а не чужой.
 *
 * `ThisType<T>` — то, ради чего здесь обобщение: без него `this` внутри
 * методов компонента выводится как `unknown`.
 */
declare module 'alpinejs' {
	interface Alpine {
		data<T extends object>(name: string, factory: (...args: any[]) => T & ThisType<T>): void;
		start(): void;
	}

	const Alpine: Alpine;

	export default Alpine;
}
