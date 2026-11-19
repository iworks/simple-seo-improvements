---
name: verify
description: post-work verification — lint, asset build and a check that everything works
---
When a task that changed code (PHP, JS, SCSS) is finished, **before reporting success** run:

## 0. SEO context

This plugin is focused on SEO (and related) improvements of a WordPress site —
meta robots, JSON-LD structured data, robots.txt, IndexNow, OpenSearch,
external-link rewriting. Keep that scope in mind while verifying:

- **SEO output changes** (head/body injection, robots meta, JSON-LD, sitemap,
  robots.txt, links rewriting) must not emit duplicate or conflicting tags —
  especially when an OG plugin is active (`check_og_plugin()` suppression).
- Verify generated markup stays valid: correct escaping at output
  (`esc_html`/`esc_attr`/`esc_url`), correct `<meta>`/JSON-LD syntax, and no
  SEO-relevant option silently defaulting to a blocking value
  (`noindex`, robots.txt `Disallow: /`, AI-crawler blocking).
- Behavioral risk is higher than average: a broken robots.txt builder or a
  wrong `noindex` default can de-index a production site — treat these paths
  as high-risk and re-test with representative options combinations.

## 1. Sanity-check diffs

- `git diff --stat` — compare the changes against the list of files you actually edited. **Any changed file you did not edit is an alarm signal** (a stray build/formatter step overwriting sources).
- Compiled files (`assets/styles/`, `assets/scripts/admin/simple-seo-improvements*js`, `languages/*.mo`, `.sass-cache/`, `release/`) are gitignored — they should never appear in a diff at all. (Exception: a change to `assets/scripts/src/admin/` requires rebuilding the admin JS — if the build outputs are tracked in your checkout, mention it explicitly.)
- Vendored submodules (`includes/iworks/options/`, `includes/iworks/rate/`, `vendor/`) must never be modified by a task.

## 2. Verification with a build

Do not declare success based on a single quick command — run the appropriate pipeline for the changed asset type (each must pass end-to-end):

- **JS (`assets/scripts/src/admin/**`)**: `grunt` (or `grunt js`) — must pass (concat + uglify). For a release-affecting change run the full `grunt build` (wp.org ZIP) or `grunt build:github` when the GitHub release path is affected.
- **SCSS (`assets/scss/**`)**: `grunt css` — must pass (sass → concat → cssmin). If the Ruby-Sass-era Grunt tasks fail locally, compile the changed SCSS with `sass` directly and say so.
- **PHP (`includes/**`, `etc/`, main file)**: `php -l` on every changed file (new files included) — there is no phpcs setup in this repo, so do not claim WPCS compliance, just match the surrounding style (tabs, docblocks, ABSPATH guards).
- **Placeholders**: verify no `PLUGIN_*` / `BUILDTIME` placeholder was introduced into any runtime code path that would break on the dev site (textdomain must stay the literal `'simple-seo-improvements'`).

## 3. Report

Summarize the build result and the list of verified files. If the build does not pass — fix it before reporting task completion (or clearly list the blockers for the user to decide). Never write "done" without a successful completion of step 2.
