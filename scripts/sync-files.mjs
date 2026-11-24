import path from 'node:path';
import { readdir, readFile, lstat } from 'node:fs/promises';

export const manifestFile = 'assets/dist/manifest.json';

// Explicit runtime allowlist: newly added tooling or credentials cannot leak by default.
export function isRuntimeFile(file) {
	if (file.includes('\\') || file.split('/').some((part) => !part || part.startsWith('.'))) return false;
	if (/^wp-config(?:-sample)?\.php$/i.test(file)) return false;
	if (/^[^/]+\.php$/.test(file)) return true;
	if (['style.css', 'theme.json', 'screenshot.png'].includes(file)) return true;
	if (/^(inc|templates|components)\/.+\.php$/.test(file)) return true;
	if (/^scf-json\/.+\.json$/.test(file)) return true;
	if (/^lang\/.+\.(mo|po|pot|json)$/.test(file)) return true;
	if (/^assets\/(images|fonts)\/.+\.(png|jpe?g|gif|svg|webp|avif|ico|woff2?|ttf|otf|eot)$/i.test(file)) return true;
	return /^assets\/dist\/.+\.(js|css|map|json|png|jpe?g|gif|svg|webp|avif|ico|woff2?|ttf|otf|eot|wasm)$/i.test(file);
}

export function destinationPath(value) {
	if (!value || value.includes('\\') || value.split('/').includes('..') || value.includes('\0')) {
		throw new Error('SFTP_DEST must be an absolute POSIX path to one WordPress theme directory.');
	}
	const destination = path.posix.normalize(value).replace(/\/$/, '');
	if (!/^\/.*\/themes\/[a-zA-Z0-9_-]+$/.test(destination)) {
		throw new Error('SFTP_DEST must end in /themes/<theme-slug>, not the themes parent directory.');
	}
	return destination;
}

export function sftpSettings(env) {
	for (const key of ['SFTP_HOST', 'SFTP_USERNAME', 'SFTP_HOST_KEY_SHA256']) {
		if (!env[key]) throw new Error(`Missing ${key}. Copy .env.example to .env and configure SFTP.`);
	}
	if (!env.SFTP_PASSWORD && !env.SFTP_PRIVATE_KEY) throw new Error('Set SFTP_PASSWORD or SFTP_PRIVATE_KEY.');
	if (!/^[\da-f]{64}$/i.test(env.SFTP_HOST_KEY_SHA256)) throw new Error('SFTP_HOST_KEY_SHA256 must contain 64 hexadecimal characters.');
	if (!/^\d+$/.test(env.SFTP_PORT || '22')) throw new Error('SFTP_PORT must be an integer from 1 to 65535.');
	const port = Number(env.SFTP_PORT || 22);
	if (port < 1 || port > 65535) throw new Error('SFTP_PORT must be an integer from 1 to 65535.');
	if (/[:/\s]/.test(env.SFTP_HOST) && !/^[\da-f:]+$/i.test(env.SFTP_HOST)) {
		throw new Error('SFTP_HOST is a hostname or IP, without a URL scheme or port.');
	}
	return {
		destination: destinationPath(env.SFTP_DEST),
		connection: {
			host: env.SFTP_HOST,
			port,
			username: env.SFTP_USERNAME,
			password: env.SFTP_PASSWORD || undefined,
			passphrase: env.SFTP_PASSPHRASE || undefined,
			hostHash: 'sha256',
			hostVerifier: (hash) => hash.toLowerCase() === env.SFTP_HOST_KEY_SHA256.toLowerCase(),
		},
	};
}

export async function runtimeFiles(root, { includeBuild = true } = {}) {
	const files = [];
	async function walk(directory) {
		for (const item of await readdir(path.join(root, directory), { withFileTypes: true })) {
			if (item.name.startsWith('.') || item.isSymbolicLink()) continue;
			const relative = directory ? `${directory}/${item.name}` : item.name;
			if (item.isDirectory()) {
				if (!directory && !['inc', 'templates', 'components', 'scf-json', 'lang', 'assets'].includes(item.name)) continue;
				if (directory === 'assets' && !['dist', 'images', 'fonts'].includes(item.name)) continue;
				if (relative === 'assets/dist' && !includeBuild) continue;
				await walk(relative);
			} else if (item.isFile() && isRuntimeFile(relative)) files.push(relative);
		}
	}
	await walk('');
	return files.sort();
}

export async function safeLocalFile(root, relative) {
	if (!isRuntimeFile(relative)) throw new Error(`Refusing non-runtime path: ${relative}`);
	const local = path.resolve(root, relative);
	// Check every path component: a symlinked directory must not expose outside files.
	let current = root;
	for (const part of relative.split('/')) {
		current = path.join(current, part);
		if ((await lstat(current)).isSymbolicLink()) throw new Error(`Refusing symlink: ${relative}`);
	}
	if (!(await lstat(local)).isFile()) throw new Error(`Refusing non-file: ${relative}`);
	return local;
}

export async function validateBuild(root) {
	const manifest = JSON.parse(await readFile(await safeLocalFile(root, manifestFile), 'utf8'));
	if (!manifest?.['assets/src/js/app.js']?.isEntry) throw new Error('Missing Vite entry. Run npm run build.');
	const files = new Set();
	for (const chunk of Object.values(manifest)) {
		for (const file of [chunk.file, ...(chunk.css || []), ...(chunk.assets || [])]) {
			files.add(`assets/dist/${file}`);
		}
		for (const key of [...(chunk.imports || []), ...(chunk.dynamicImports || [])]) {
			if (!manifest[key]) throw new Error(`Missing manifest chunk: ${key}`);
		}
	}
	for (const file of files) await safeLocalFile(root, file);
	return files;
}

// Install referenced assets before publishing the manifest. Keep old hashes remotely
// so previously rendered/cached pages continue to work during a deployment.
export function uploadOrder(files) {
	return [...new Set(files)].sort((a, b) => {
		if (a === b) return 0;
		if (a === manifestFile) return 1;
		if (b === manifestFile) return -1;
		return a.localeCompare(b);
	});
}

export async function uploadFiles(client, root, destination, files, log = console.log) {
	for (const relative of uploadOrder(files)) {
		const local = await safeLocalFile(root, relative);
		const remote = `${destination}/${relative}`;
		await client.mkdir(path.posix.dirname(remote), true);
		if (relative === manifestFile) {
			// OpenSSH's POSIX rename replaces the manifest atomically.
			await client.put(local, `${remote}.upload`);
			await client.posixRename(`${remote}.upload`, remote);
		} else await client.put(local, remote);
		log(`Uploaded ${relative}`);
	}
}
