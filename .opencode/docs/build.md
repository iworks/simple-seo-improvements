# Build — Grunt

> Toolchain, tasks, pipeline, placeholders, release ZIPs, translations.
> Read only when working on the build.

## Toolchain

- **Grunt** (classic iWorks scaffold, `Gruntfile.js`) with
  `load-grunt-tasks`; deps in `package.json` `devDependencies` (grunt-contrib
  concat/uglify/copy/compress/cssmin/sass, grunt-replace, grunt-wp-i18n,
  grunt-potomo, grunt-checktextdomain, grunt-phpunit). Install with
  `npm install`.
- PHP ≥ 8.0; wp-i18n tooling for i18n; PHPUnit for tests (no test dir is
  wired in `phpunit.xml` by default — `grunt test` also runs jshint).

## Tasks (`grunt ...`)

| Command            | Description                                                        |
| ------------------ | ------------------------------------------------------------------ |
| `grunt` (default)  | `clean:temp` + `concat` + `uglify` (JS assets only)                |
| `grunt js`         | concat + uglify                                                    |
| `grunt css`        | sass + concat_css + cssmin                                         |
| `grunt i18n`       | checktextdomain + makepot                                          |
| `grunt build`      | default + i18n → wp.org flavor: clean, copy:wporg, replace, zip    |
| `grunt build:github` | default + i18n + potomo → GitHub flavor copy + replace + zip     |
| `grunt release`    | `build` (wporg) + `build:github` + `notes` (release notes)         |
| `grunt test`       | phpunit + jshint + notes                                           |
| `grunt notes`      | prints release notes                                               |

## Asset pipeline

- **JS**: `assets/scripts/src/admin/*.js` → concatenated to
  `assets/scripts/admin/simple-seo-improvements.js` → uglified to `.min.js`.
- **CSS**: `assets/scss/` → compass/sass → `assets/styles/` → concatenated +
  minified (`.min.css`).
- Compiled outputs are **gitignored** (`assets/styles/`,
  `assets/scripts/admin/simple-seo-improvements*js`, `languages/*.mo`,
  `.sass-cache/`, `release/`, `package-lock.json`) — never edit them.

## Release ZIPs

- **wp.org** (`grunt build`) → `release/wporg/{version}/{name}/` + ZIP.
  Copy excludes (Gruntfile `excludeCopyFilesWPorg`) strip sources
  (`assets/scss`, `assets/scripts/src`), `.github/`, `.git*` dotfiles,
  `package.json`/lock files, `README.md`, `contributing.md`, `Gruntfile.js`,
  `tests/`, and **the GitHub self-updater class** (wp.org releases must not
  self-update from GitHub). `readme.txt` **is** included (required by wp.org).
- **GitHub** (`grunt build:github`) → `release/github/{version}/{name}/`.
  Includes `.po` files and compiles `.mo` (grunt-potomo). Pairs with the
  GitHub self-updater class. Existing release dirs live under
  `release/{github,wporg}/2.3.{2,3,4}/` (gitignored).
- Placeholder **replace** (grunt-replace) runs on the staged copies only,
  then `grunt-contrib-compress` zips.

## Placeholders (release copies only)

Replaced by Grunt from `package.json` + build time (Gruntfile
`replace_patterns`): `PLUGIN_VERSION`, `PLUGIN_TITLE`, `PLUGIN_TAGS`,
`PLUGIN_TAGLINE`, `PLUGIN_DESCRIPTION`, `PLUGIN_URI`, `AUTHOR_NAME`,
`AUTHOR_URI`, `PLUGIN_REQUIRES_WORDPRESS`, `PLUGIN_REQUIRES_PHP`,
`PLUGIN_TESTED_WORDPRESS`, `PLUGIN_TILL_YEAR`, `BUILDTIME`,
`IWORKS_RATE_TEXTDOMAIN`, and the `^Version: .*$` header line (regex).

Notes:

- The **textdomain is the literal `'simple-seo-improvements'`** everywhere —
  it is NOT a placeholder (unlike newer iWorks plugins that use
  `'PLUGIN_NAME'`).
- `PLUGIN_NAME` appears only in vendored/legacy strings such as the
  robots.txt header comment (`# PLUGIN_NAME PLUGIN_VERSION` in
  `class-iworks-robots-txt.php`).
- Copyright years are baked in manually (`2021-PLUGIN_TILL_YEAR`).

## Tests

- `grunt test` → phpunit + jshint. There is no full WordPress test harness
  committed; `php -l` on changed files is the practical PHP check.

## Runtime quirks / pitfalls (do not fix without approval)

- `grunt build` is the **wp.org** build; `grunt build:github` is the GitHub
  one (with `.mo`); `grunt release` does both. `grunt` alone only rebuilds JS.
- Sass runs through `grunt-contrib-compass`/`grunt-contrib-sass` (Ruby
  Sass-era config) — if the CSS build fails locally, compile SCSS manually
  or use `sass` directly.
- `grunt-contrib-uglify` output and concatenated files are committed-
  ignored but expected by the runtime loader (`$this->dev` suffix switch);
  a fresh checkout needs `grunt` before the admin JS works.
