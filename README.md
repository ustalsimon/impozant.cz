# Base WP Theme

A small classic WordPress starter theme for custom projects. It includes PHP
page templates, a shared layout wrapper, menus and widgets, optional Secure
Custom Fields (SCF) helpers, Tailwind CSS 4, project-local Vite tooling, and
BrowserSync for automatic browser updates after successful synchronization.
It is intended to be a **GitHub Template Repository**: every project gets an
independent repository, rather than sharing a production theme or its settings.

## Prerequisites

- Node.js **22.12 or newer**; Node.js 24 LTS is recommended. npm ships with Node.
- WordPress **6.4 or newer** running on PHP **8.0 or newer**. Use versions
  supported by your hosting provider for a new installation.
- Git, an editor, and an existing local or remote WordPress installation.
- Optional: Secure Custom Fields for custom fields and Local JSON. The theme
  works without it; missing fields return the helper's default value.
- For remote upload: an SFTP account with write access to one theme directory,
  and a server supporting OpenSSH's POSIX rename extension (atomic overwrite).

Node builds assets; it does not install or serve WordPress. No global Grunt,
Vite, Tailwind, Sass, PHP package manager, or npm package is required. Check:

```bash
node --version
npm --version
```

On Windows, use `npm.cmd` if PowerShell blocks the `npm.ps1` launcher.

## Create a project from the template

The repository owner should enable **Settings > Template repository** in
GitHub. Then choose **Use this template > Create a new repository**, name your
new repository (for example `acme-theme`), and clone it:

```bash
git clone https://github.com/YOUR-ORGANIZATION/acme-theme.git
cd acme-theme
npm ci
```

`npm ci` installs the exact versions in `package-lock.json`. `npm install` also
works for initial setup; use it when deliberately changing dependencies, and
commit the updated lockfile. Do not copy `node_modules` from another project.

Before your first project commit, customize these values:

| Value | Where to change it |
| --- | --- |
| Theme display name, description, version, optional author | Header in `style.css` |
| Theme slug | Folder under `wp-content/themes/`, such as `acme-theme` |
| Text domain | Replace the literal `base-wp-theme` in PHP translation calls, `inc/setup.php`, and `style.css` with the slug |
| npm package name/description | `package.json`; run `npm install` to update the lockfile |
| Own PHP prefixes | Rename symbols/callbacks beginning `theme_` and the `Theme_Template_Wrapper` class to a unique project prefix |
| Colors, fonts, design | `assets/src/css/theme.css` and `app.css` |
| Editor settings | `theme.json` |
| WordPress development URL and proxy port | `WP_DEV_URL` and `BROWSERSYNC_PORT` in ignored `.env` |
| Upload destination and credentials | Ignored `.env`, for SFTP |

Rename only your own PHP symbols and their references. A blanket substring
replacement of `theme_` would also break WordPress functions such as
`get_theme_file_path()`. Include the wrapper's custom `theme_wrap_...` filter
names if you rename the prefix. Asset handles such as `theme-main` may also be
renamed, provided the matching script-tag filter is updated.

There is no fixed website domain or PHP runtime environment file. WordPress supplies
the site's URL; Vite uses relative asset URLs. Add your own `screenshot.png` when
you have a real design (the original project's screenshot was removed).

## Local WordPress setup and development

1. Install WordPress through your preferred local stack and complete its setup.
2. Put this repository inside that installation's
   `wp-content/themes/acme-theme/`. Clone directly there, or move the complete
   repository there. Building a separate checkout does not update another copy.
3. Install dependencies and build assets:

   ```bash
   npm ci
   npm run build
   ```

4. Activate the theme in **Appearance > Themes**. Assign a menu to **Primary
   Navigation** under **Appearance > Menus**. The footer menu and primary
   sidebar are registered for use in your own layouts.
5. Choose a static homepage in **Settings > Reading** if desired. Otherwise,
   the homepage displays posts with pagination.
6. Copy `.env.example` to `.env` and set `WP_DEV_URL` to your local WordPress
   URL (for example `http://acme.test`). Start the local development workflow:

   ```bash
   npm run dev:local
   ```

7. Open the **local BrowserSync proxy URL printed in the terminal**, usually
   `http://localhost:3000`. Edit a PHP template, CSS, or imported JavaScript.
   CSS is injected where practical; PHP/JS changes refresh the page automatically
   after the build completes. Stop the workflow with **Ctrl+C**.

`dev:local` needs the WordPress URL but no upload credentials. PHP changes are
already in the active theme directory; Vite rebuilds CSS when template classes
change, including newly added templates. `theme.json` changes also trigger a
build. Restart the watcher after changing Vite configuration or dependencies.

### How Vite is used

`npm run dev` starts **Vite build watching, SFTP synchronization, and a BrowserSync
proxy** through `scripts/sync.mjs`. Both development and production use the same
WordPress manifest loader; Vite's HTTP server and HMR are not used. BrowserSync
proxies `WP_DEV_URL`, rewrites upstream URLs, and injects its browser client.
It does not serve source assets or watch files independently. The sync queue
explicitly requests browser updates only after successful upload/build work.

- Entry point: `assets/src/js/app.js`, which imports `assets/src/css/app.css`.
- Tailwind's Vite plugin processes CSS, design tokens, and typography utilities.
  Explicit `@source` paths scan root PHP, `inc/`, `templates/`, `components/`,
  and source JS/TS. Add another source path and watch directory in
  `vite.config.js` if you add a new template directory outside those locations.
- Use complete class strings, such as `bg-red-500`. Tailwind cannot detect
  dynamically assembled strings like `bg-` plus a database value. Map dynamic
  choices to complete literal classes or explicitly safelist them with
  Tailwind's `@source inline(...)` directive.
- JavaScript imports are bundled. Imported TypeScript is transpiled, without
  type checking. Production CSS and JS are minified; development JS has source
  maps and development assets are unminified.
- Sass is installed for optional project styles. Import a separate file from
  `app.js`, for example `import '../css/custom.scss';`. Keep Tailwind directives
  in `app.css`; do not move Tailwind into Sass.
- Imported images and fonts, including CSS `url(...)` references, go through
  Vite's asset pipeline. Small assets may be inlined; larger assets receive
  hashed filenames. Put directly referenced PHP assets in `assets/images/`
  or `assets/fonts/` and use `get_theme_file_uri()` for their URLs. These retain
  their filenames and are uploaded separately; Vite does not optimize images.

`assets/dist/manifest.json` maps the source entry to built files in
`assets/dist/assets/`. `inc/assets.php` reads the manifest, enqueues its CSS
(including CSS from static imported chunks), and loads JS as an ES module.
Production files are hashed for cache busting. Development CSS filenames are
stable (for example `assets/dist/assets/app.css`) so BrowserSync can inject a
new stylesheet at the existing URL. CSS links also use file modification time
as their WordPress version; BrowserSync adds its own cache-busting query when
injecting. JS and imported non-CSS assets remain hashed. Development keeps the
previous outputs available during rebuilds; production builds clean them.
No PHP filename changes are needed after a build.
A missing/invalid manifest skips asset loading: build before activating or
shipping the theme. `style.css` provides WordPress metadata; frontend styles
belong in the source CSS.

## Remote development: build, upload, and automatic browser reload

Use a development or staging WordPress installation with a dedicated theme
folder. Obtain its SFTP hostname, port, username, password or private key,
absolute theme-directory path, and trusted server host-key fingerprint from
your hosting provider or administrator. The web URL and the SFTP host can differ.

Create local configuration:

```bash
# macOS / Linux
cp .env.example .env
```

```powershell
# Windows PowerShell
Copy-Item .env.example .env
```

Example settings (replace every placeholder):

```dotenv
WP_DEV_URL=https://wordpress.example.test
BROWSERSYNC_PORT=3000
SFTP_HOST=sftp.example.test
SFTP_PORT=22
SFTP_USERNAME=theme-developer
SFTP_PASSWORD="your-password"
SFTP_PRIVATE_KEY=
SFTP_PASSPHRASE=
SFTP_HOST_KEY_SHA256=YOUR_TRUSTED_64_CHARACTER_HEX_FINGERPRINT
SFTP_DEST=/webroot/wp-content/themes/acme-theme
```

| Variable | Meaning |
| --- | --- |
| `WP_DEV_URL` | Required for `dev`/`dev:local`: full HTTP(S) WordPress URL, optionally including a subdirectory; no credentials, query, or fragment |
| `BROWSERSYNC_PORT` | Local proxy port, defaults to 3000; integer 1-65535 |
| `SFTP_HOST` | Required hostname or IP only; no `sftp://` prefix or port |
| `SFTP_PORT` | Integer 1-65535; defaults to 22 |
| `SFTP_USERNAME` | Required SFTP username |
| `SFTP_PASSWORD` | Password; quote it when it contains `#` or spaces |
| `SFTP_PRIVATE_KEY` | Alternative to password: absolute path to a private key outside this repository; forward slashes work on Windows |
| `SFTP_PASSPHRASE` | Optional passphrase for an encrypted private key |
| `SFTP_HOST_KEY_SHA256` | Required trusted server SSH host-key SHA-256 hash: 64 hexadecimal characters |
| `SFTP_DEST` | Required absolute POSIX path ending in `/themes/<theme-slug>` |

For key authentication, leave the password blank and set, for example,
`SFTP_PRIVATE_KEY=C:/Users/your-name/.ssh/id_ed25519`. Shell environment
variables override values in `.env`. `.env` is read only by Node development/upload
tooling, never by WordPress or browser JavaScript. Legacy `FTP_*` variables are
no longer supported; transfer their values to the documented `SFTP_*` settings.

If your provider gives you a trusted `SHA256:...` Base64 fingerprint, convert it
to hexadecimal (the input must come from a trusted source):

```bash
node -e "console.log(Buffer.from(process.argv[1].replace(/^SHA256:/, ''), 'base64').toString('hex'))" "SHA256:YOUR_TRUSTED_BASE64_FINGERPRINT"
```

Copy the printed 64 characters into `.env`. The client rejects a different host
key. Never commit passwords, private keys, or a filled `.env`.

First inspect the files that would be uploaded, without connecting to a server:

```bash
npm run build
npm run sync -- --dry-run
```

For continuous remote development, run this **single command**:

```bash
npm run dev
```

It validates configuration, connects to SFTP, starts a development Vite build,
uploads the whole runtime theme, and starts BrowserSync after the initial upload
succeeds. It then watches for changes:

- PHP and `theme.json` changes are uploaded with their completed Vite build,
  including any new Tailwind utilities. `style.css`, supported images/fonts,
  translations, and SCF JSON are also watched and synchronized.
- Source CSS/JS and template classes cause Vite to rebuild. The first build
  uploads the runtime theme and current build outputs. Later builds upload
  new or changed files and publish a changed manifest last using atomic POSIX
  rename. Older hashed files stay available for cached pages.
- A single queue serializes uploads. Content snapshots include PHP changes
  even if Vite finishes before the separate runtime watcher reports them.
  When PHP or `theme.json` changes during a build, the watcher waits for the
  next build before uploading that batch.
- After synchronization, CSS-only content changes are injected without losing
  page state. PHP/templates, JS, static assets, and CSS file additions/removals
  trigger a full browser reload. A pending build delays runtime reload signals;
  failed builds/uploads never emit a success notification.
- Deleting a watched runtime file deletes its matching remote file. Empty
  remote directories may remain. Deleted generated hashes are retained remotely
  to keep already-rendered pages working.
- A connection, build, or upload error stops synchronization with a nonzero exit
  code. Correct the error and restart; startup performs a complete upload again.

Run only one development command in a checkout. Open the **BrowserSync proxy URL
printed in the terminal**, activate the uploaded theme on WordPress, and keep
viewing the site through the proxy. Editing a theme file then builds/uploads
and updates the browser automatically. Opening the upstream WordPress URL
directly will not receive reload signals. BrowserSync binds to loopback only,
does not open a browser automatically, and has its UI and cross-device gesture
synchronization disabled. An HTTPS upstream uses BrowserSync's local HTTPS
certificate; trust the local development certificate if the browser requests it.

`npm run dev -- --dry-run` exercises build/upload/reload decisions without SFTP
credentials or opening a proxy (a valid `WP_DEV_URL` is still required).
`npm run sync:watch` remains available for upload-only watching with no proxy;
its `--dry-run` does not require `WP_DEV_URL`. Use `npm run dev:local` when this
checkout is already the active local theme and SFTP is unnecessary.

To change the destination, stop the watcher, update `SFTP_DEST` (and host/account
if necessary) in `.env`, and restart. Use the path visible to the SFTP account,
not the website URL. No project-specific fallback destination exists.

### Upload boundaries

`scripts/sync-files.mjs` explicitly allows root PHP files, `style.css`,
`theme.json`, an optional `screenshot.png`, PHP under `inc/`, `templates/`, and
`components/`, SCF JSON, translation files, common image/font formats, and built
assets. It creates empty SCF, image/font, component, and language directories.
Add an allowlist rule if your project introduces another runtime directory or
file type. Symlinks, hidden files, tooling, documentation, source assets, Git
metadata, dependencies, and environment files are excluded.

SFTP is one-way upload, not a bidirectional mirror. A one-time upload does not
delete remote files that are absent locally. Remove obsolete PHP/templates
manually when updating an existing installation; especially remove the old
empty `single-post.php` and any old block-theme `templates/index.html` override.
The original project's hardcoded cleanup list is deliberately gone. Old hashed
assets can be cleaned up separately after caches expire. PHP deployment is not
transactional, so validate updates on staging.

## Production build and delivery

```bash
npm run build
```

This cleans `assets/dist/`, produces minified hashed assets and a manifest, and
omits development source maps. Do not run it while another build watcher is
writing the same directory.

For configured SFTP delivery:

```bash
npm run deploy
```

This runs the production build and then a one-time runtime upload, without
starting BrowserSync.
`npm run sync` uploads the existing build without rebuilding, so run `build`
first if you need production output. Review the dry-run file list before using
SFTP for a new server.

For manual delivery, upload/package the runtime files shown by
`npm run sync -- --dry-run`, preserving their paths, including the complete
`assets/dist/` directory and `scf-json/`. Keep developer tooling and secrets out
of the deployed theme. Node and npm are not needed on the WordPress server.

## Commands

| Command | Purpose |
| --- | --- |
| `npm ci` | Install locked dependencies |
| `npm install` | Install/update dependencies and the lockfile |
| `npm run dev` | Remote development: Vite build watcher + SFTP + BrowserSync |
| `npm run dev:local` | Local active theme: Vite build watcher + BrowserSync, no SFTP |
| `npm run dev -- --dry-run` | Build/upload/reload preview without SFTP or a proxy |
| `npm run build` | Clean production build |
| `npm run sync` | One-time SFTP upload of an existing build |
| `npm run sync -- --dry-run` | List runtime files without connecting |
| `npm run sync:watch` | Build/upload watching without BrowserSync |
| `npm run sync:watch -- --dry-run` | Verify build/upload watching without a connection |
| `npm run deploy` | Production build followed by SFTP upload |

## Repository structure and Git policy

- Root PHP files: WordPress template hierarchy; `functions.php` loads modules.
- `base.php`: shared layout. `inc/template-wrapper.php` wraps WordPress's chosen
  template; `base-<template>.php` can customize a particular layout.
- `inc/`: setup, manifest loader, helpers, optional SCF, template wrapper.
- `templates/`: reusable page/content/layout parts.
- `components/`: space for PHP components accepting `$args`.
- `assets/src/`: editable CSS/JS and optional Sass/imported assets.
- `assets/images/`, `assets/fonts/`: directly referenced static assets.
- `assets/dist/`: generated assets and manifest; ignored by Git.
- `lang/`: translations; `scf-json/`: field definitions.
- `scripts/`, `vite.config.js`: development tooling and validation.

Commit PHP, source assets, theme metadata, SCF definitions, translations,
`package.json`, `package-lock.json`, development tooling, documentation, and the
blank `.env.example`. Git ignores `.env` and other `.env.*` files (except the
example), generated builds, `node_modules/`, logs, coverage, editor settings,
common private-key formats, `.ssh/`, `wp-config.php`, and OS metadata. Builds are
recreated for delivery; a Git-only copy of the theme needs `npm ci` and `build`
before activation. Keep private keys outside the repository even if ignored.

SCF saves field-group configuration to `scf-json/`, which must be writable by
WordPress. Commit its JSON with the PHP that consumes it. Field content remains
in the WordPress database. On the target installation, synchronize available
field definitions in the SCF administration screen. Remote administration
changes are not downloaded automatically: retrieve the resulting JSON before
committing, or edit definitions locally and upload them.

## Troubleshooting

- **Theme renders without styling:** build first; confirm the manifest and its
  referenced files exist in the active theme's `assets/dist/`. Check that you
  edited/built the active checkout rather than another copy.
- **New utility has no CSS:** use a complete literal class in a configured source
  directory. Add sources/watch paths for new directories, then restart Vite.
- **Browser still shows an older version:** wait for upload completion, refresh,
  and clear WordPress/CDN page caches. Check that cached HTML references the
  current manifest's files. PHP opcode caching may also need a server refresh.
- **Browser does not update automatically:** open the BrowserSync proxy URL,
  rather than `WP_DEV_URL` directly. Check the browser console for client/socket
  connection errors. Disable page/CDN caches for development; a restrictive
  Content Security Policy may need to allow the local BrowserSync script/socket.
- **Proxy does not start:** verify `WP_DEV_URL` and `BROWSERSYNC_PORT`; check that
  WordPress is reachable and follow the actual proxy URL printed in the terminal.
  Restart after changing `.env`. For HTTPS, trust the local development certificate.
- **SFTP configuration rejected:** check all required values, the numeric port,
  and the destination's `/themes/<slug>` suffix. Passwords with `#` need quotes.
- **Host key mismatch:** verify the fingerprint with your administrator. Do not
  replace it with an unverified value just to bypass the failure.
- **Permission denied or POSIX rename unsupported:** ask your provider for theme
  directory permissions or a compatible SFTP endpoint; no manifest is published
  after a failed asset upload. Stop, fix the issue, and restart synchronization.
- **No remote changes:** check the upload log, allowlist, active theme folder,
  and SFTP-visible destination. Only successful build output is uploaded.
- **SCF fields missing:** activate the optional plugin, synchronize JSON in its
  administration screen, and check the save directory's permissions.
- **Windows/WSL watching misses changes:** run Node in the same environment that
  edits files. If needed, configure polling via Rolldown's watch options in
  `vite.config.js`; polling uses more CPU.
- **Installation/build problems:** confirm your Node version, run `npm ci`, and
  restart. On npm versions enforcing install-script approval, review package
  scripts with `npm approve-scripts` according to your team's policy; the SSH
  optional native accelerators are not required for basic SFTP.
- **npm audit flags BrowserSync's glob dependencies:** the current release has
  an unpatched `braces` advisory reported through four dependencies. Its file
  watching is disabled here and the proxy binds to loopback. Review upstream
  updates; do not blindly apply `npm audit fix --force`, which currently proposes
  a downgrade to an old BrowserSync release.

Tooling reference: [Vite backend integration](https://vite.dev/guide/backend-integration.html),
[Tailwind with Vite](https://tailwindcss.com/docs/installation/using-vite), and
[Rolldown watch dependencies](https://rolldown.rs/reference/Interface.PluginContext).
Browser reload API: [BrowserSync](https://browsersync.io/docs/api).
