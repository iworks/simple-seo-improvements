# AGENTS.md — Simple SEO Improvements (simple-seo-improvements)

> Working rules only. Technical knowledge lives in topic docs — read on demand, never preload.

## What the project is

A lightweight WordPress SEO plugin (meta robots, JSON-LD, robots.txt, IndexNow,
AI-crawler blocking): main file (`simple-seo-improvements.php`) + PHP classes
(`/includes/iworks`) + options config (`/etc/options.php`) + assets (`/assets`),
built with Bun (bundler/) → `release/` ZIPs (wp.org + GitHub flavors).
Built from the classic iWorks plugin scaffold; sources contain build-time
placeholders (`PLUGIN_VERSION`, `PLUGIN_TITLE`, `PLUGIN_TILL_YEAR`, …).

## Docs map — read only what the task needs

| Task                                                                    | Read first                                          |
| ----------------------------------------------------------------------- | --------------------------------------------------- |
| Main class, feature classes, JSON-LD, post types, taxonomies, new class | `.opencode/docs/classes.md`                         |
| Bun build, release ZIPs, placeholders, translations (.pot/.po)          | `.opencode/docs/build.md`                           |
| Loading flow, assets, i18n, options framework, project conventions      | `.opencode/docs/project.md`                         |
| After any task that changed code                                        | skill `verify` (`.opencode/skills/verify/SKILL.md`) |

- Read ONLY the docs relevant to the current task — do not load everything upfront.
- When a change alters documented behavior, update the corresponding doc in the same task.

## Evergreen rules (every task)

- All documentation and README in English.
- `readme.txt` changelog section is edited only by the user — never touch it
  unless the user explicitly asks.
- Never commit, push, amend or create PRs without an explicit request.
- Naming: kebab-case files (`class-iworks-simple-seo-improvements-{name}.php`);
  PHP classes `iworks_simple_seo_improvements_` prefix, snake_case, lowercase.
  Legacy shorter names exist (`class-sitemap.php`, `class-taxonomies.php`) —
  match the neighborhood when editing.
- Version lives only in `package.json` — placeholders (`PLUGIN_VERSION`,
  `BUILDTIME`, `PLUGIN_TILL_YEAR`, …) are replaced by the Bun bundler during build;
  never hardcode real values in sources. The textdomain is the **literal**
  `'simple-seo-improvements'` (NOT a placeholder — unlike newer iWorks plugins).
- Do not fix known typos/pitfalls without approval (see topic docs).

## Commands

`bun run compile` (JS bundle) · `bun run dev` (watcher) ·
`bun run build:github` (GitHub ZIP) · `bun run build:wporg` (wp.org ZIP) ·
`bun run build` (both ZIPs + Plugin Check) · `bun run make-pot` (i18n) ·
`bun run check:pcp` (Plugin Check on the built wp.org ZIP) ·
`bun run update-libs` · `bun run test` (phpunit) · `bun run notes`
