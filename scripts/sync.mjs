import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { readFile, lstat } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import dotenv from 'dotenv';
import SftpClient from 'ssh2-sftp-client';
import chokidar from 'chokidar';
import { build } from 'vite';
import { isRuntimeFile, manifestFile, sftpSettings, runtimeFiles, validateBuild, uploadOrder, uploadFiles } from './sync-files.mjs';
import { browserSettings, startBrowser, notifyBrowser, buildReload } from './browser-reload.mjs';

const root = fileURLToPath(new URL('../', import.meta.url));
process.chdir(root);
dotenv.config({ path: path.join(root, '.env'), quiet: true });
const flags = new Set(process.argv.slice(2));
if ([...flags].some((flag) => !['--watch', '--dry-run', '--reload', '--local'].includes(flag))) {
	throw new Error('Usage: npm run sync -- [--watch] [--dry-run] [--reload] [--local]');
}
const watch = flags.has('--watch');
const dryRun = flags.has('--dry-run');
const liveReload = flags.has('--reload');
const local = flags.has('--local');
if ((liveReload && !watch) || (local && (!watch || !liveReload))) {
	throw new Error('--reload requires --watch; --local is only for the development proxy.');
}
let client;
let browser;
let fileWatcher;
let buildWatcher;
let stopped = false;
let queue = Promise.resolve();
let runtimeSnapshot = new Map();
let previousBuild;
let building = false;
let pendingFullReload = false;

async function stop(code = 0) {
	if (stopped) return;
	stopped = true;
	browser?.exit();
	await fileWatcher?.close();
	await buildWatcher?.close();
	await queue.catch(() => {});
	await client?.end().catch(() => {});
	process.exitCode = code;
}

function fail(error) {
	if (stopped) return;
	console.error(error.message);
	void stop(1);
}

function enqueue(task) {
	const next = queue.then(task);
	queue = next.catch(fail);
	return next;
}

async function snapshot(files) {
	const result = new Map();
	for (const file of files) {
		const contents = await readFile(path.join(root, file));
		result.set(file, createHash('sha256').update(contents).digest('hex'));
	}
	return result;
}

function isBuildInput(file) {
	return file.endsWith('.php') || file === 'theme.json';
}

function reload(change) {
	if (!liveReload || !change || stopped) return;
	if (dryRun) console.log(change === 'css' ? 'Would inject CSS after synchronization' : 'Would reload browser after synchronization');
	else notifyBrowser(browser, change);
}

try {
	if (!watch) await validateBuild(root);
	// Validate the proxy before making any connection or remote mutation.
	const browserConfig = liveReload ? browserSettings(process.env) : null;
	const settings = dryRun || local ? null : sftpSettings(process.env);
	if (settings) {
		if (process.env.SFTP_PRIVATE_KEY) settings.connection.privateKey = await readFile(process.env.SFTP_PRIVATE_KEY);
		client = new SftpClient('theme-sync');
		await client.connect(settings.connection);
		console.log(`Connected to ${settings.connection.host}; destination ${settings.destination}`);
	}
	const upload = async (files) => {
		if (stopped) return;
		if (dryRun) {
			for (const file of uploadOrder(files)) console.log(`Would upload ${file}`);
		} else if (!local) await uploadFiles(client, root, settings.destination, files);
	};
	const remove = async (file) => {
		if (dryRun) console.log(`Would delete ${file}`);
		else if (!local && await client.exists(`${settings.destination}/${file}`)) {
			await client.delete(`${settings.destination}/${file}`);
			console.log(`Deleted ${file}`);
		}
	};
	if (settings) {
		for (const directory of ['scf-json', 'assets/images', 'assets/fonts', 'components', 'lang']) {
			await client.mkdir(`${settings.destination}/${directory}`, true);
		}
	}
	if (!watch) {
		await upload(await runtimeFiles(root));
		await stop();
	} else {
		let initial = true;
		let buildInputs;
		buildWatcher = await build({
			mode: 'development',
			build: { watch: {} },
			plugins: [{
				name: 'theme-sync-after-build',
				async buildStart() {
					building = true;
					const files = await runtimeFiles(root, { includeBuild: false });
					buildInputs = await snapshot(files.filter(isBuildInput));
				},
				async writeBundle(_options, bundle) {
					await enqueue(async () => {
						if (stopped) return;
						const currentRuntime = await snapshot(await runtimeFiles(root, { includeBuild: false }));
						const inputsChanged = [...currentRuntime].some(([file, digest]) => isBuildInput(file) && buildInputs.get(file) !== digest)
							|| [...buildInputs.keys()].some((file) => !currentRuntime.has(file));
						// A queued rebuild will process inputs that changed while this build was running.
						if (inputsChanged) return;

						const builtFiles = await validateBuild(root);
						// Include emitted files such as source maps that aren't in the manifest.
						for (const name of Object.keys(bundle)) {
							const file = `assets/dist/${name}`;
							if (isRuntimeFile(file)) builtFiles.add(file);
						}
						builtFiles.add(manifestFile);
						const currentBuild = await snapshot(builtFiles);
						const changedBuild = [...currentBuild.keys()].filter((file) => currentBuild.get(file) !== previousBuild?.get(file));
						const changed = [...currentRuntime.keys()].filter((file) => currentRuntime.get(file) !== runtimeSnapshot.get(file));
						const deleted = [...runtimeSnapshot.keys()].filter((file) => !currentRuntime.has(file));
						// A PHP-triggered build can finish before Chokidar's settled-write
						// event. Upload changed runtime files IN THIS batch before reloading.
						for (const file of deleted) await remove(file);
						await upload([...changed, ...changedBuild]);
						runtimeSnapshot = currentRuntime;
						if (liveReload && initial && !dryRun) browser = await startBrowser(browserConfig);
						reload(buildReload(previousBuild, currentBuild, pendingFullReload || changed.length > 0 || deleted.length > 0));
						pendingFullReload = false;
						building = false;
						previousBuild = currentBuild;
						initial = false;
					});
				},
			}],
		});
		buildWatcher.on('event', (event) => {
			if (event.code === 'ERROR') fail(event.error);
		});
		fileWatcher = chokidar.watch(root, {
			ignoreInitial: true,
			followSymlinks: false,
			ignored(absolute, stats) {
				const relative = path.relative(root, absolute).split(path.sep).join('/');
				if (!relative) return false;
				if (relative.split('/').some((part) => part.startsWith('.'))) return true;
				if (stats?.isFile()) return !isRuntimeFile(relative) || relative.startsWith('assets/dist/');
				const directory = /^(inc|templates|components|scf-json|lang)(\/|$)/.test(relative)
					|| relative === 'assets' || /^assets\/(images|fonts)(\/|$)/.test(relative);
				return !directory && !isRuntimeFile(relative);
			},
			awaitWriteFinish: { stabilityThreshold: 300, pollInterval: 100 },
		});
		fileWatcher.on('all', (event, absolute) => {
			const relative = path.relative(root, absolute).split(path.sep).join('/');
			if (!isRuntimeFile(relative) || relative.startsWith('assets/dist/') || !['add', 'change', 'unlink'].includes(event)) return;
			// PHP/settings participate in the build batch: don't publish/reload them
			// independently while their new Tailwind utilities are still compiling.
			if (isBuildInput(relative)) return;
			enqueue(async () => {
				if (stopped) return;
				if (event === 'unlink') {
					if (!runtimeSnapshot.has(relative)) return;
					await remove(relative);
					runtimeSnapshot.delete(relative);
				} else {
					try { await lstat(absolute); } catch (error) { if (error.code === 'ENOENT') return; throw error; }
					const digest = (await snapshot([relative])).get(relative);
					if (runtimeSnapshot.get(relative) === digest) return;
					await upload([relative]);
					runtimeSnapshot.set(relative, digest);
				}
				pendingFullReload = true;
				if (!building) { reload('full'); pendingFullReload = false; }
			});
		});
		fileWatcher.on('error', fail);
		fileWatcher.once('ready', () => console.log('Watching theme files and Vite builds. Ctrl+C stops.'));
		process.once('SIGINT', () => stop());
		process.once('SIGTERM', () => stop());
	}
} catch (error) {
	console.error(error.message);
	await stop(1);
}
