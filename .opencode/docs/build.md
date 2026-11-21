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
| `bun run build`        | PHPUnit first, then build:github, then build:wporg (no PCP)                  |
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
- **Plugin Check (PCP)** — **not part of `bun run build`** (removed from
  the build flow; run it separately via `bun run check:pcp`, which builds
  the wp.org ZIP first, skip with `--skip-build`). Stages the ZIP
  into `wp-content/plugins/{name}-pcp/`, runs
  `wp plugin check <dir> --slug={name} --format=csv` (`--slug` prevents
  the i18n "expected <staging-slug>" noise), prints errors/warnings grouped
  (full messages) followed by a summary table (file, code label,
  assessment), and removes the staging copy. Exits 1 on any ERROR. It is skipped with
  a warning when the environment lacks `wp` CLI or the active
  `plugin-check` plugin. Needs
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
    - `WmtSaveTest.php` — webmaster tools key saving for all 4 services
      (google/bing/yandex/pinterest): option config declares the
      `iworks_ssi_sanitize_wmt_code` sanitize_callback, simulated save
      (sanitize → store → `get_option` roundtrip), injection stripping,
      empty-value handling.
    - `XssTextareaFieldsTest.php` — exhaustive XSS on textarea fields:
      29 payloads (multi-line script, tag-splitting via newline/tab, null
      bytes, unicode brackets, entity tricks, srcdoc/animate/object/form/
      base/meta-refresh vectors, URI schemes, comment/unclosed-tag tricks)
      run through every textarea's real sanitize_callback; stored values
      tag-free + null-byte-free; robots/meta/JSON-LD sinks stay structurally
      intact; benign multi-line values pass untouched. Custom Code fields
      (html_head/body) excluded by design.
    - `XssNonTextFieldsTest.php` — XSS coverage for every non-text field
      type: all checkbox/number/image/select-numeric fields (absint/intval)
      reduce hostile payloads to integers and declare their exact callback;
      `json_org_pa_c` (esc_html select) stores entity-encoded, tag-free
      values; radio + default-select fields (comparison-only sinks) store
      tag-free values that can never match a sink literal.
    - `SaveDataTest.php` — per-post save flows (post/page/attachment:
      save, update, delete, revision/autosave/nonce guards) plus per-post
      field XSS tests (relevant when `{post_type}_mode` = allow): hostile
      title/description payloads stored tag-free; hostile `robots[]`
      sub-array keys ignored by the meta robots sink (known-keys
      iteration only); robots values never echoed; description sink
      survives legacy quote-bearing data via esc_attr.
    - `ExternalLinksTest.php` — external-links feature (exli:* options)
      through the real `WP_HTML_Tag_Processor`: rel=nofollow added to
      external links only (internal + www variants untouched), existing
      rel/target preserved, custom class(es) added, relative hrefs skipped,
      content without anchors untouched. XSS: hostile `exli:class` values
      (tag breakout, attribute smuggling) stay entity-encoded inside the
      class attribute — no raw markup can reach the anchor tag.
    - `TaxonomySaveTest.php` — term SEO-data save flows for tags/categories
      (create + update via the `create_`/`edited_{taxonomy}` hooks): meta
      written with sanitized title/description + robots flags, update
      replaces the whole meta (old robots flags dropped), save without form
      data deletes meta, invalid nonce skips the save, delete-term is core
      semantics (no plugin delete hook — meta dies with the term), hostile
      robots keys sink-safe, hostile title/description stored tag-free.
    - `PostStatusChangeTest.php` — post/page/attachment status transitions
      (publish↔draft, private, future): status changes re-fire `save_post`
      so the same save path re-runs — meta survives unchanged; clearing
      fields during a transition deletes stale meta; autosave during a
      transition is skipped. - `SecurityBridgeTest.php` — cross-sink security bridge: a value saved
      once via a textarea is traced into every consumer. robots.txt sink:
      each input line maps to exactly one directive, no CR residue (multi-line
      input is trusted admin intent — only `manage_options` users can save).
      JSON-LD sink: XSS payloads in `json_other` can never break the JSON
      document — `</script>` cannot appear raw, output re-decodes as valid
      JSON. Head `<meta>` sink: `esc_attr()` encodes all quotes so attribute-
      break/event-handler payloads stay inert text. - `RobotsTxtLineEndingsTest.php` — line endings in the robots.txt
      textareas: LF / CRLF / CR / mixed endings and blank or trailing lines
      all split into one directive per line (no empty `Allow:`/`Disallow:`
      directives, no `\r` corrupting values), duplicates deduplicated,
      `robots_txt_disallow_all` short-circuits and leaves robots.txt untouched. - `PrefixesTest.php` — category/tag URL prefix remover (`category_no_slug`,
      `tag_no_slug`): options off → permastructs untouched; options on → bare
      `%category%` / `%post_tag%` structs; rewrite rules per term (bare slug,
      feed, pagination), old-base redirect rule honoring custom
      `category_base`/`tag_base`, nested categories build full slug path
      (`europe/poland`), redirect query vars registered, passthrough without
      redirect var, redirect target computation to the bare URL. - `SiteIconTest.php` — site icon / default image handling: config
      sanitizers pinned (`default_image` → `intval`, `use_as_favicon` →
      `absint`), favicon `<link rel="icon">` + `<link rel="shortcut icon">`
      printed only when image set AND toggle on AND URL resolvable, URL
      forced relative (host never leaks), broken attachment prints nothing,
      XSS in the favicon option / attachment URL / mime type neutralized
      (intval sanitizer + esc_attr attribute encoding), og:image fallback
      data (url/width/height/mime) skipped for non-image mime and empty id. - `XssConfigFieldsTest.php` — XSS injection against every free-text
      config field: 9 classic payloads (script/img/svg/iframe/
      attribute-break/javascript:-URI/encoded/style) run through each
      field's real `sanitize_callback`; WMT fields pinned to the
      `[A-Za-z0-9_-]` allowlist; default fields pinned to
      `sanitize_text_field` (no raw `<` may survive); benign values must
      pass unmangled; metabox title/description strip tags; output layer
      (`esc_attr`) neutralizes quotes so stored payloads cannot break the
      meta attribute context. Custom Code fields (html_head/body) are
      intentionally excluded — raw admin HTML by design. - `OptionsConfigTest.php` — robots.txt allow defaults (expected paths,
      newline separation), deprecated robots aliases return identical values,
      `iworks_seo_improvements_no_sanitization` passthrough.
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
