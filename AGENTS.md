# Instructions for coding agents

## Purpose and scope

- This is a generic GitHub template for independent custom WordPress themes.
  Preserve its reusable nature; do not add client branding, domains, IDs, server
  paths, credentials, or assumptions about one hosting provider.
- Prefer small, understandable changes. Preserve useful WordPress functionality
  and optional SCF integration; avoid introducing a framework or needless layers.
- Read the relevant source and README before changing architecture or commands.
  Preserve unrelated user changes.

## Theme and assets

- This is a classic PHP theme, not a block theme. Root templates follow the
  WordPress hierarchy. `functions.php` loads `inc/`; `base.php` and
  `inc/template-wrapper.php` provide the shared layout. Keep the wrapper intact.
- Layout/content parts live in `templates/`; reusable PHP components belong in
  `components/` and receive data through `$args`. `searchform.php` stays at root
  for WordPress discovery. Metadata lives in `style.css`.
- Use the `base-wp-theme` text domain and existing `theme_` / `Theme_` naming for
  generic additions. Do not rename WordPress APIs when customizing own prefixes.
  Escape output appropriately and use WordPress APIs for URLs and paths.
- Keep PHP compatible with PHP 8.0 and WordPress 6.4. SCF is optional: use the
  safe helpers; do not add unguarded plugin dependencies. `scf-json/` contains
  field definitions only, not database content.
- Node tooling uses ESM and requires Node >=22.12. Match surrounding formatting;
  keep functions focused and error handling explicit. Use project-local npm tools.
- Vite's entry is `assets/src/js/app.js`, which imports `assets/src/css/app.css`.
  Keep Tailwind directives in CSS, design tokens in `theme.css`, and optional
  Sass in separate imports. Use complete literal Tailwind class strings.
- Add Tailwind source paths AND watch dependencies when introducing template
  directories. Preserve detection of added/deleted PHP files in `vite.config.js`.
- WordPress loads `assets/dist/manifest.json` through `inc/assets.php`, including
  static imported CSS and module JS. Never hardcode generated hashes in PHP.
  Production cleans/builds hashed assets; development retains previous outputs
  and uses stable CSS filenames for injection. JS/non-CSS assets remain hashed.

## Development, synchronization, and security

- `npm run dev` runs Vite build watching + SFTP + a BrowserSync WordPress proxy.
  `dev:local` runs the same build/proxy against the active local theme without
  SFTP. `sync:watch` is upload-only watching; it does not start BrowserSync.
- Keep local settings in ignored `.env` or shell environment variables. Document
  new settings in blank `.env.example` and README. `WP_DEV_URL` configures the
  proxy; `BROWSERSYNC_PORT` defaults to 3000. PHP/browser code must not read secrets.
- Serialize runtime uploads and build batches in `scripts/sync.mjs`. Compare
  PHP and `theme.json` snapshots before and after compilation so changes during
  a build wait for the next build. Upload changed inputs and current outputs
  together, including when Vite finishes before the runtime watcher reports an edit.
- Upload new or changed assets before atomically publishing a changed manifest.
  Notify BrowserSync after successful synchronization. Inject CSS when only
  content at existing CSS paths changes. Reload for PHP, JS, runtime assets,
  or CSS path changes. Stop on a failed build or upload.
- BrowserSync's independent file watching stays disabled; bind the proxy to
  loopback. Do not serve the repository/source tree or expose credentials.
- Preserve the runtime allowlist, symlink rejection, pinned SSH host-key check,
  and destination validation requiring one `/themes/<slug>` directory. Exclude
  WordPress configuration, environment files, keys, tooling, and documentation
  from uploads. Add runtime allowlist rules deliberately when adding file types.
- Keep private keys outside the repository. Never log, commit, or upload secrets.
  Do not edit/commit `.env`, `node_modules/`, generated `assets/dist/`, logs,
  coverage, editor/OS files, or private keys. Edit sources and rebuild instead.
  Regenerate `package-lock.json` using npm; do not edit it manually.
- SFTP is one-way. Watch deletions remove matching runtime files; one-time sync
  does not prune arbitrary remote files. Retain old remote asset hashes for cached
  pages. Do not restore project-specific destructive cleanup lists.
- Use dry-runs for validation. Perform real remote writes only when authorized
  by the user's task; never infer permission merely from available credentials.

## Validation and documentation

- Stop other build watchers before building. Run `npm ci` when checking
  dependency changes.
- For relevant changes run `npm run build` and `npm run sync -- --dry-run`.
- `npm run dev -- --dry-run` checks development/reload decisions without a server
  (valid `WP_DEV_URL` required). `sync:watch -- --dry-run` needs no URL/credentials.
- Use the dry-run commands for validation. `npm run deploy` uploads to production.
- If PHP is available, lint touched files with `php -l <file>` and verify relevant
  behavior in WordPress. Report unavailable runtime/live-server checks honestly.
- Review `npm audit` for dependency changes. BrowserSync currently has an unpatched
  transitive `braces` advisory; do not blindly force a downgrade to suppress it.
- Update README, `.env.example`, and these instructions when developer-facing
  configuration, commands, deployment boundaries, or architecture change. Ensure
  every documented command and feature actually exists.
