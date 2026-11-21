import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

/**
 * Сборка клиента «Соловья».
 *
 * Точка входа одна — `app/client/main.ts`; она же объявлена в config.php
 * (`vite.entries`), поэтому PHP подключает ассеты по манифесту, а не по
 * угаданному имени файла с хешем.
 */
export default defineConfig(({ command }) => ({
	base: command === 'build' ? '/build/' : '/',

	plugins: [tailwindcss()],

	build: {
		manifest: true,
		copyPublicDir: false,
		outDir: 'public/build',
		emptyOutDir: true,
		rollupOptions: {
			input: {
				main: 'app/client/main.ts',
			},
		},
	},

	server: {
		host: '127.0.0.1',
		// Порт совпадает с `vite.port` в config.php: PHP строит адрес dev-сервера
		// из конфига, и второй копии числа быть не должно.
		port: Number(process.env.VITE_PORT ?? 5183),
		strictPort: true,
		watch: {
			ignored: ['**/vendor/**', '**/public/build/**', '**/var/**', '**/database/**'],
		},
	},
}));
