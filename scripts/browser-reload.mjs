import browserSync from 'browser-sync';
import { manifestFile } from './sync-files.mjs';

export function browserSettings(env) {
	let url;
	try { url = new URL(env.WP_DEV_URL); } catch { throw new Error('Set WP_DEV_URL to the full WordPress development URL in .env.'); }
	if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password || url.search || url.hash) {
		throw new Error('WP_DEV_URL must be an HTTP(S) URL without credentials, query or fragment.');
	}
	const port = Number(env.BROWSERSYNC_PORT || 3000);
	if (!/^\d+$/.test(env.BROWSERSYNC_PORT || '3000') || port < 1 || port > 65535) {
		throw new Error('BROWSERSYNC_PORT must be an integer from 1 to 65535.');
	}
	return {
		proxy: {
			target: url.href,
			proxyRes: [(response) => {
				// Node de-chunks the upstream body. Let BrowserSync frame the
				// rewritten response with its own Content-Length.
				if (response.headers['transfer-encoding'] === 'chunked') {
					delete response.headers['transfer-encoding'];
				}
			}],
		},
		port,
		listen: '127.0.0.1',
		open: false,
		ui: false,
		online: false,
		ghostMode: false,
		notify: false,
		files: false,
		watch: false,
		reloadOnRestart: false,
	};
}

export async function startBrowser(settings) {
	const browser = browserSync.create('theme-development');
	try {
		await new Promise((resolve, reject) => browser.init(settings, (error) => error ? reject(error) : resolve()));
		return browser;
	} catch (error) { browser.exit(); throw error; }
}

// Called only from the serialized sync queue, after ALL files in the change were
// uploaded (including the manifest). Failed uploads must never reach this point.
export function notifyBrowser(browser, change) {
	if (change === 'css') browser.reload('*.css');
	else if (change === 'full') browser.reload();
}

export function buildReload(previous, current, runtimeChanged) {
	if (!previous || runtimeChanged) return 'full';
	const affectsPage = (file) => file !== manifestFile && !file.endsWith('.map');
	for (const file of previous.keys()) {
		if (affectsPage(file) && !current.has(file)) return 'full';
	}
	let cssChanged = false;
	for (const [file, digest] of current) {
		if (!affectsPage(file) || previous.get(file) === digest) continue;
		if (!previous.has(file) || !file.endsWith('.css')) return 'full';
		cssChanged = true;
	}
	return cssChanged ? 'css' : null;
}
