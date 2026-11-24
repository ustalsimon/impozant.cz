import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath } from 'node:url';

export default defineConfig(({ mode }) => ({
	base: './',
	publicDir: false,
	plugins: [tailwindcss(), {
		name: 'theme-template-watch',
		// Tailwind registers existing files, but Rolldown does not expand its glob
		// dependencies for newly created PHP templates. Associate directories with
		// the CSS module so additions/deletions invalidate and rescan that module.
		transform(_code, id) {
			if (id.endsWith('/assets/src/css/app.css')) {
				for (const directory of ['./', './inc', './templates', './components']) {
					this.addWatchFile(fileURLToPath(new URL(directory, import.meta.url)));
				}
				this.addWatchFile(fileURLToPath(new URL('./theme.json', import.meta.url)));
			}
		},
	}],
	build: {
		outDir: 'assets/dist',
		// Keep the previous manifest/assets available during development rebuilds.
		emptyOutDir: mode !== 'development',
		manifest: 'manifest.json',
		sourcemap: mode === 'development',
		minify: mode === 'development' ? false : 'oxc',
		cssMinify: mode !== 'development',
		watch: mode === 'development' ? { exclude: ['**/node_modules/**', '**/assets/dist/**', '**/.git/**'] } : null,
		rolldownOptions: {
			input: 'assets/src/js/app.js',
			output: {
				// Stable development CSS URLs let BrowserSync inject the new stylesheet.
				assetFileNames: (asset) => mode === 'development' && asset.names.some((name) => name.endsWith('.css'))
					? 'assets/[name][extname]' : 'assets/[name]-[hash][extname]',
			},
		},
	},
}));
