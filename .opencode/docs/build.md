# Build — Bun bundler

> Toolchain, tasks, pipeline, placeholders, release ZIPs, translations, tests.
> Read only when working on the build.

## Toolchain

- [Bun](https://bun.sh) + the `bundler/` scripts (ported from
  `../dev-iworks-aquarium-log/bundler/`, CSS pipeline removed — this plugin
  ships no CSS build). Entry point `bundler/index.js`,
  config in `bundler/config.mjs`, pipeline in `bundler/pipelines.mjs`,
  tool wrappers (terser, prettier) in `bundler/tools.mjs`,
  ZIP staging in `makeZip.mjs`, i18n via wp-cli in `makePot.mjs`, Plugin
  Check in `pluginCheck.mjs`.
- Dependencies (in `package.json` `devDependencies`): `archiver`,
  `prettier`, `terser`. Install with `bun install` (its `postinstall`
  downloads `wp-cli.phar` for `make-pot`; a `wp` binary phar copy also works).
- PHP ≥ 8.0; wp-cli for i18n. There is **no Gruntfile** anymore (removed;
  grunt deps dropped from `package.json`).
- Prettier reads `.editorconfig` (tabs for JS) — `bundler/tools.mjs` resolves
  the config explicitly (`prettier.resolveConfig(..., { editorconfig: true })`)
  and passes it to `prettier.format()` (the bare `editorconfig: true` format
  option is not honored by prettier's API).

## Tasks (`bun run ...`)

| Command                | Description                                                                  |
| ---------------------- | ---------------------------------------------------------------------------- |
| `bun run dev`          | watcher: prettier + recompile JS on change (no ZIP, no i18n)                 |
| `bun run compile`      | one-shot JS compile (no prettier pass)                                       |
| `bun run build`        | build:github, then build:wporg, then Plugin Check on the wp.org ZIP          |
| `bun run build:wporg`  | production build → `release/wporg/simple-seo-improvements.zip`               |
| `bun run build:github` | production build + `.mo` → `release/github/simple-seo-improvements.zip`      |
| `bun run make-pot`     | translations only (`.pot` + `.po` update via wp-cli i18n)                    |
| `bun run update-libs`  | git pull on the vendored iWorks libraries (`includes/iworks/{options,rate}`) |
| `bun run check:pcp`    | Plugin Check on the built wp.org ZIP (`--skip-build`, `--ignore-warnings`)   |
| `bun run test`         | PHPUnit (`tests/php/phpunit.xml`)                                            |
| `bun run notes`        | release notes checklist (grunt parity)                                       |

## Asset pipeline

This plugin's asset surface is a single admin JS bundle. **There is no CSS
build** — the plugin ships no SCSS sources and nothing compiled into
`assets/styles/`; the bundler's CSS/sass stages were removed when porting.

1. **prettier** (build modes only) — formats `assets/scripts/src/**/*.js`
   and the bundler sources.
2. **JS** — `assets/scripts/src/admin/*.js` →
   `assets/scripts/admin/simple-seo-improvements.js` (sorted, banner
   prepended) → terser minify (mangle reserves `jQuery`) to `.min.js`.
   The output path matches what the main class enqueues
   (`assets/scripts/admin/simple-seo-improvements{dev}.js` — see
   `classes.md`).
3. **i18n** — wp-cli `i18n make-pot` with `--ignore-domain` and
   `--slug=simple-seo-improvements` → `languages/simple-seo-improvements.pot`,
   `update-po` for every `.po`; `build:github` also runs `make-mo`.
   A regenerated `.pot` that only changed volatile metadata is restored
   from backup (keeps diffs clean). Unlike newer iWorks plugins, the
   textdomain is the **literal** `'simple-seo-improvements'`, so a
   domain-filtered extraction would work too — `--ignore-domain` is kept
   for bundler parity.

Compiled outputs (`assets/scripts/admin/simple-seo-improvements*js`, `languages/*.mo`) are gitignored — never edit them.

## Release ZIPs

- **GitHub** (`bun run build:github`) → `release/github/{version}/{name}/`.
  Built **first** in `bun run build`. Base excludes plus `readme.txt`;
  `.po` **are** included and `.mo` are compiled. Pairs with the GitHub
  self-updater class.
- **wp.org** (`bun run build:wporg`) → `release/wporg/{version}/{name}/` →
  `release/wporg/simple-seo-improvements.zip`. Built **second** in
  `bun run build`. Copy rules live in
  `bundler/config.mjs` `copyRules(flavor)` — ordered, **first match wins**
  (grunt parity), the catch-all `{ glob: "**/*", include: true }` must stay
  last. Base excludes: repo meta (`.git*`, `.github/`, `.editorconfig`,
  `AGENTS.md`, `Gruntfile.js` (legacy), `bundler/`, `bun.lock*`,
  `wp-cli.phar`, `package.json`, lock files, `README.md`, `LICENSE`,
  `contributing.md`, tests, `release/`, `.opencode/`,
  `.playwright-mcp/`, source maps, `o/`, legacy `**/img/src`,
  `**/js/src`), asset sources (`assets/scripts/src`), the GitHub
  self-updater class
  (`includes/iworks/simple-seo-improvements/class-iworks-simple-seo-improvements-github.php`),
  `languages/*.po|*.mo`. `readme.txt` **is** included (required by wp.org).
  After staging, every directory without one gets an `index.php` silence
  file (`// Silence is golden.`) — wp.org review requirement to prevent
  directory listing; existing files are kept.
- **Plugin Check (PCP)** — runs **inside `bun run build`** (third step,
  against the fresh wp.org ZIP) and standalone via `bun run check:pcp`
  (builds the wp.org ZIP first, skip with `--skip-build`). Stages the ZIP
  into `wp-content/plugins/{name}-pcp/`, runs
  `wp plugin check <dir> --slug={name} --format=csv` (`--slug` prevents
  the i18n "expected <staging-slug>" noise), prints errors/warnings grouped
  and removes the staging copy. Exits 1 on any ERROR (standalone mode and
  at the end of `bun run build` — the ZIPs are still produced). Inside
  `build` the check is **optional**: it is skipped with a warning when the
  environment lacks `wp` CLI or the active `plugin-check` plugin. Needs
  `unzip`; WP root is auto-detected as the repo's `../../../` (override
  with `WP_ROOT`).
- Placeholder **replace** runs on every staged text file (binary extensions
  and `.mo` skipped, see `BINARY_EXTENSIONS`), then `archiver` zips the
  staging directory (level 9, `{name}/` wrapper folder).

## Placeholders (release copies only)

`AUTHOR_NAME/URI/URL`, `BUILDTIMESTAMP`, `BUILDTIME`, `IWORKS_RATE_TEXTDOMAIN`,
`IWORKS_OPTIONS_TEXTDOMAIN`, `PLUGIN_DESCRIPTION`, `PLUGIN_GITHUB_WEBSITE`,
`PLUGIN_NAME`, `PLUGIN_REQUIRES_PHP`, `PLUGIN_REQUIRES_WORDPRESS`,
`PLUGIN_TESTED_WORDPRESS`, `PLUGIN_TAGLINE`, `PLUGIN_TAGS`,
`PLUGIN_TILL_YEAR`, `PLUGIN_TITLE`, `PLUGIN_URI`/`PLUGIN_URL` (from
`pkg.homepage`), `PLUGIN_VERSION`, and the `^Version: .*$` header line —
all sourced from `package.json` + build time (`bundler/config.mjs`
`replacePatterns()`).

Notes (grunt parity / differences from other iWorks plugins):

- The **textdomain is the literal `'simple-seo-improvements'`** everywhere —
  it is NOT a placeholder. `PLUGIN_NAME` appears only in vendored/legacy
  strings (e.g. the robots.txt header comment in `class-iworks-robots-txt.php`).
- Copyright years are baked in manually (`2021-PLUGIN_TILL_YEAR`);
  `CURRENT_YEAR` is not replaced at build.

## Tests

- `bun run test` → `vendor/bin/phpunit --configuration tests/php/phpunit.xml`
  (phpunit ^11 via Composer, dev dependency). Runs **without** a WordPress
  install: `tests/php/bootstrap.php` loads the plugin's pure PHP classes with a
  minimal set of WP function stubs.
- Covered (unit tests in `tests/php/`):
    - `BaseAbstractTest.php` — base-abstract helpers: `compress_all_whitespaces`
      (whitespace collapsing), `clear_string` (HTML stripping + whitespace
      compression, non-string passthrough), `get_post_data` (title/description
      sanitized via `wp_strip_all_tags` — script/style content removed entirely,
      other fields untouched, empty `$_POST` → empty array), field name value.
    - `BaseClassTest.php` — `get_meta_name` (prefix + `sanitize_title`
      slugification, including the underscore-stripping quirk), `get_version`
      behavior in normal and `IWORKS_DEV_MODE` modes.
    - `WmtSanitizerTest.php` — `iworks_ssi_sanitize_wmt_code` allowlist
      `[A-Za-z0-9_-]`, null/int coercion, injection attempts (it does not parse
      HTML; output safety relies on `esc_attr` at print time).
    - `OptionsConfigTest.php` — robots.txt allow defaults (expected paths,
      newline separation), deprecated robots aliases return identical values,
      `iworks_seo_improvements_no_sanitization` passthrough.
    - `SaveDataTest.php` — `save_data()` for post, page and attachment IDs:
      meta written with sanitized title/description, empty-field behavior
      (meta updated with empty strings, not deleted), and guards skipping the
      save (revision, autosave, invalid nonce). Uses an in-memory meta store;
      `check_nonce()` is stubbed because `filter_input()` sees no CLI input.
- Not covered (needs a WP integration environment): JSON-LD
  output, robots.txt hooking, admin pages. jshint was dropped with Grunt; the
  bundler runs prettier instead and `node --check` on the bundle works as a
  smoke test.

## Runtime quirks / pitfalls (do not fix without approval)

- Copy rules in `bundler/config.mjs` also exclude composer artifacts
  (`composer.json`, `composer.lock`, `vendor/**`) — dev-only, never shipped.
- `bun run build:wporg` is the **wp.org** build; `bun run build` does both
  flavors (there is no plain `build` that only compiles assets — use
  `compile` for that).
- The bundler concatenates full source files (prettier-formatted), so the
  built bundles keep file docblocks; grunt's `stripBanners` removed them.
- `build` runs the **prettier pass over the JS sources in place** — commit
  any formatting churn it produces (or `git checkout --` the sources and
  run `compile` instead when you do not want formatting churn).
- wp-cli.phar is expected in the project root (`postinstall` downloads it;
  `makePot.mjs` retries the download when missing).
- The wp.org copy rules also keep the vendored submodules' `.scss` /
  `common.js` sources under `includes/iworks/{options,rate}` (grunt copied
  them too) — do not "clean them up" without checking a grunt-era ZIP first.
