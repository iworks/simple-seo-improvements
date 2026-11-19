# Project mechanics — Simple SEO Improvements

> Loading flow, assets, translations, options framework, dependencies, general conventions.
> Read only when the task involves these areas.

## Overview

An iWorks-style WordPress SEO plugin: meta robots per post type/taxonomy/
archive, robots.txt (incl. AI-crawler blocking), JSON-LD structured data,
IndexNow, external-link rewriting, OpenSearch. Main file
`simple-seo-improvements.php`; textdomain `simple-seo-improvements`
(literal, not a placeholder); slug/package name `simple-seo-improvements`
(single source of truth: `package.json` `"name"`).

Source files contain **literal placeholders** (`PLUGIN_VERSION`,
`PLUGIN_TILL_YEAR`, `PLUGIN_TITLE`, …) — replaced by the Bun bundler at build time
(see `build.md`), never in the source.

The version lives only in `package.json` (`"version"`); the main file header
and the version property use the `PLUGIN_VERSION` placeholder.

## Directory structure

```
/
├── simple-seo-improvements.php     # Plugin bootstrap (header, options, main class)
├── etc/
│   └── options.php                 # Options pages configuration (iworks_options format)
├── includes/
│   └── iworks/
│       ├── class-simple-seo-improvements-base.php   # Base class
│       ├── class-simple-seo-improvements.php        # Main plugin class
│       ├── simple-seo-improvements/                 # Feature classes
│       │   ├── class-iworks-simple-seo-improvements-base-abstract.php
│       │   ├── class-iworks-simple-seo-improvements-{archives,github,
│       │   │   json-ld,links,opensearch,prefixes,posttypes}.php
│       │   ├── class-sitemap.php / class-sitemaps-attachements.php  # legacy names
│       │   ├── class-taxonomies.php                                 # legacy name
│       │   ├── class-iworks-robots-txt.php
│       │   └── index-now/           # IndexNow base + Bing + Seznam
│       ├── options/                 # vendored iWorks Options (submodule)
│       └── rate/                    # vendored iWorks Rate (submodule)
├── vendor/
│   └── simple_html_dom.php         # Simple HTML DOM parser (links rewriting)
├── assets/
│   ├── scripts/src/admin/          # JS sources (options.js)
│   ├── scripts/admin/              # Built JS (gitignored)
│   └── templates/meta-boxes/       # localbusiness.php meta box
├── languages/                      # .pot + .po (+ .mo built, gitignored)
├── bundler/                        # Bun build system — see build.md
├── package.json                    # Version + release metadata (single source of truth)
└── readme.txt                      # WordPress.org readme (placeholder-based)
```

## Loading flow

1. WordPress loads `simple-seo-improvements.php` (ABSPATH guard):
    - requires `etc/options.php`, the vendored `iworks_options` class
      (guarded by `class_exists`), and the main class (guarded);
    - instantiates `iworks_simple_seo_improvements`;
    - defines `get_iworks_simple_seo_improvements_options()` (global
      `$iworks_simple_seo_improvements_options`, prefix `iworks_ssi_`,
      option function name `iworks_simple_seo_improvements_options`) and
      registers the activation hook (`$options->activate()`) and an empty
      deactivation hook.
2. `iworks_simple_seo_improvements::__construct()`:
    - sets `$base`/`$dir`/`$version`/`$plugin_file`, filter
      `simple-seo-improvements/is_active` → `__return_true`;
    - hooks `init` (options + conditional loads: JSON-LD, IndexNow,
      robots.txt, iWorks Rate), `admin_init`, `wp_head` (robots meta + custom
      head), `wp_body_open`, `wp_footer`;
    - loads feature classes directly (`require_once` + `new`): posttypes,
      taxonomies, sitemap, then archives/opensearch/prefixes/links in a loop;
    - checks for an active OG plugin and registers its integration filters.
3. Classes access options via the global options object
   (`get_iworks_simple_seo_improvements_options()` / `check_option_object()`).

## Constants

- No version constant is defined; the version lives in the main class
  `$version` (`'PLUGIN_VERSION'` placeholder).
- `IWORKS_DEV_MODE` (optional, `wp-config.php`) — dev mode: base class uses
  unminified assets (`$this->dev = ''`) and md5-based cache busting in
  `get_version()`.

## Options layer (etc/options.php + iworks_options)

- `etc/options.php` returns the config array (963 lines). Main page entry
  `$options['index']`: page `simple-seo-improvements-admin`, menu title
  "SEO Improvements" (under Settings), tabs (General, Social Media, …).
- Field shape: `name`, `type` (heading, text, textarea, checkbox, select,
  media, custom), `th` (label), `description`, `sanitize_callback`, `since`.
- Option names get the `iworks_ssi_` prefix. Major groups: default image /
  favicon, home meta description, category/tag prefix removal, `post_types`
  (per_type + common robots), `other_archives`, external links (`exli:*`),
  robots.txt (`robots_txt`, allow/disallow, `ai_block_*` for AI crawlers),
  IndexNow (`indexnow_bing`), robots directives (`robots_googlebot`,
  `robots_max_snippet`, `robots_max_image_preview`, `robots_max_video_preview`),
  JSON-LD (`use_json_ld`, `json_type`, `json_org_*`, `json_org_pa_*`,
  `json_person_*`, `json_other`), custom code (`html_head`, `html_body_start`,
  `html_body_end`), OpenSearch.
- Dynamic option groups are appended at runtime via the
  `iworks_plugin_get_options` filter in the main class
  (`filter_add_post_types_options()` — per-post-type robots settings).

## Assets layer

- **JS**: `assets/scripts/src/admin/*.js` → concat + terser →
  `assets/scripts/admin/simple-seo-improvements{,.min}.js`; enqueued on
  admin pages via `admin_init()` (handle with `$this->dev` suffix switch).
  Only settings UX today (toggles org vs. person JSON-LD fields).
- **No CSS build** — the plugin ships no SCSS sources; the bundler has no
  CSS/sass stage (the old `assets/scss/` / `assets/styles/` pipeline was
  removed with the grunt→bundler port).
- Built via the Bun bundler (`bundler/`) — see `build.md`.
- Compiled files are **gitignored** — never edit them; edit sources and
  rebuild (`bun run compile` / `bun run build`).
- Templates: `assets/templates/meta-boxes/localbusiness.php` loaded for the
  LocalBusiness meta box (JSON-LD class); each directory also carries an
  `index.php` silence file.

## Translations (i18n)

- Textdomain: `simple-seo-improvements` — **literal** in sources, not a
  placeholder. `languages/simple-seo-improvements.pot` + `.po` (pl_PL,
  sv_SE); `.mo` compiled only in `bun run build:github`.
- `bun run make-pot` runs wp-cli `i18n make-pot` (creates the `.pot` and
  updates every existing `.po`), with a metadata-only-change backup restore
  to keep diffs clean.

## GitHub self-updater

`class-iworks-simple-seo-improvements-github.php` — standalone class,
updates the plugin from GitHub (`iworks/simple-seo-improvements`) via
releases API / update transients. **Excluded from the WordPress.org ZIP**.

## PHP dependencies

- No Composer. Requires (from `package.json`, replaced into headers at
  build): WordPress ≥ 6.0, PHP ≥ 8.0, tested up to 7.0.
- Vendored code: `includes/iworks/options/` and `includes/iworks/rate/`
  (both **git submodules** pointing at iWorks GitHub repos) and
  `vendor/simple_html_dom.php`. Treat all as vendor — do not refactor their
  internals; update via `git submodule update --remote` when asked.

## General conventions

- Formatting: tabs for PHP/JS/SCSS, LF, final newline (`.editorconfig`).
- Every PHP file: `defined( 'WPINC' )` die-guard in the bootstrap or
  ABSPATH-style guards in classes; `class_exists` guards around includes.
- File naming: kebab-case, `class-iworks-simple-seo-improvements-{name}.php`;
  class names: `iworks_simple_seo_improvements_{name}` (snake_case,
  lowercase). Legacy exceptions: `class-sitemap.php`, `class-taxonomies.php`.
- Options: `iworks_ssi_` prefix; plugin filter namespace:
  `simple-seo-improvements/...`.
- Never read the vendored frameworks' internals from feature classes — use
  the options object helpers.

## Known pitfalls (do not fix without approval)

- Source placeholders (`PLUGIN_VERSION`, `PLUGIN_TILL_YEAR`, …) are
  intentional — do not replace them with real values in the repo.
- Typo in the main class: `$classess` (line 118) — intentional legacy, keep
  as-is when editing nearby code.
- Typo in `etc/options.php` description ("avaialble") — user-facing string;
  do not fix without approval (it would churn translations).
- The main class `filter_add_post_types_options()` mutates the
  `$options[0]` placeholder entry ("--- select ---") when building post-type
  option lists — options array index 0 is load-bearing.
- Feature classes live in two naming generations (short vs. long filenames);
  match the file you are editing, do not rename.
