# Classes — Simple SEO Improvements

> Reference for work on PHP classes: base class, main class, feature classes,
> JSON-LD, post types, taxonomies, and the vendored iWorks frameworks.
> Read only when the task involves these areas.

## Class hierarchy

```
iworks_simple_seo_improvements_base                  (foundation, includes/iworks/)
└── iworks_simple_seo_improvements                   (main class, instantiated in bootstrap)

iworks_simple_seo_improvements_base_abstract         (abstract, simple-seo-improvements/)
├── iworks_simple_seo_improvements_posttypes         (loader)
├── iworks_simple_seo_improvements_taxonomies        (loader)
├── iworks_simple_seo_improvements_sitemap           (+ class-sitemaps-attachements.php)
├── iworks_simple_seo_improvements_archives          (author/date robots)
├── iworks_simple_seo_improvements_opensearch
├── iworks_simple_seo_improvements_prefixes          (category/tag URL prefix removal)
├── iworks_simple_seo_improvements_links             (external links: nofollow/target/class)
└── iworks_simple_seo_improvements_json_ld           (large, standalone-ish)

iworks_index_now / iworks_index_now_{bing,seznam}    (index-now/, own base)
iworks_robots_txt                                    (robots.txt builder)
iworks_simple_seo_improvements_github                (standalone self-updater)
```

Note the naming split: most feature classes extend
`..._base_abstract` (in `simple-seo-improvements/`), while the main class
extends `..._base` (in `includes/iworks/`). Two files use legacy short names:
`class-sitemap.php` (`iworks_simple_seo_improvements_sitemap`) and
`class-taxonomies.php` (`iworks_simple_seo_improvements_taxonomies`).

## Base class (class-iworks-simple-seo-improvements-base.php)

`iworks_simple_seo_improvements_base` — shared properties and helpers; the
main class extends it (feature classes use the separate `base_abstract`).

Key properties:

- `$dev` — `'.min'` normally, `''` when `IWORKS_DEV_MODE` is set (asset suffix).
- `$version` — literal `'PLUGIN_VERSION'` (replaced at build; the main class
  sets it again in its constructor).
- `$base` (includes/iworks dir), `$dir` (plugin directory name),
  `$plugin_file` (plugin basename of `simple-seo-improvements.php`).

Key methods: `get_version( $file )` (with `IWORKS_DEV_MODE` returns an md5 of
the file for cache busting), `get_meta_name()`, `get_post_meta()`,
`get_post_type()`, `get_this_capability()`, `print_table_body( $post_id,
$fields )` (admin meta-field rows), `get_module_file()`, `html_title( $text )`.

## Main class (class-iworks-simple-seo-improvements.php, ~1,234 lines)

`iworks_simple_seo_improvements extends iworks_simple_seo_improvements_base`
— instantiated once in `simple-seo-improvements.php`.

- `__construct()` (line 70): sets `$base`, `$dir`, `$version`, `$plugin_file`,
  registers the filter `simple-seo-improvements/is_active` → `__return_true`,
  then hooks:
  - `init` → `action_init_set_options()` (conditional class loading, incl.
    JSON-LD), `maybe_load_index_now()` (line 738, key gen `get_indexnow_key()`
    line 724), `maybe_load_robots_txt()` (line 751 → `iworks_robots_txt`),
    `action_init_register_iworks_rate()` (priority `PHP_INT_MAX`);
  - `admin_init` → `admin_init()` (enqueues
    `assets/scripts/admin/simple-seo-improvements{dev}.js`, line 202);
  - `wp_head` → `add_robots()` (line 402: builds `<meta name="robots">` from
    per-post-type/archive options) and `filter_wp_head_add_html_head()`
    (line 472, priority 0);
  - `wp_body_open` / `wp_footer` → custom body-start/body-end code
    (lines 613 / 626);
  - `iworks_plugin_get_options` filters → dynamic settings UI
    (`filter_add_post_types_options()`, line 243: per-post-type `mode`
    allow/force + robots checkboxes + `*_archive_*` options; skips the
    meta-description group when the front page is static; and
    `filter_maybe_add_advertising()`, line 661).
- Loads feature classes directly (`require_once` + `new`, passing `$this`):
  `posttypes` (103), `taxonomies` (108), `sitemap` (113), then a loop over
  `archives`, `opensearch`, `prefixes`, `links` (118–128) storing instances
  in `$this->objects[ $classname ]`.
- OG plugin coexistence: `check_og_plugin()` (line 450) detects an active OG
  plugin and suppresses duplicate head output; integration filters
  `filter_og_twitter_site` (572), `filter_og_array_add_fb_app_id` (588),
  `filter_og_image_init` (604). Head output is wrapped in HTML comments
  (`wrap_code_in_comments()`, line 639).
- Helper option lists via filters: `filter_get_pages()` (780),
  `filter_get_user_list_options()` (763), `filter_get_lb_types()` (795–953,
  full schema.org LocalBusiness list), `filter_get_countries()` (961+).

## JSON-LD (class-iworks-simple-seo-improvements-json-ld.php, ~1,727 lines)

Loaded conditionally in `action_init_set_options()` (main class lines
166–178) when the `use_json_ld` option is on. Schema parts: BreadcrumbList,
WebSite + SearchAction, Article, Organization/Person (driven by the
`json_type` option), LocalBusiness (multiple types from the meta box),
PostalAddress, ImageObject. Hooks `simple_seo_improvements_wp_head`,
`add_meta_boxes`, `save_post`; renders the LocalBusiness meta box from
`assets/templates/meta-boxes/localbusiness.php` (nonce
`simple-seo-improvements-local-business`, repeatable `type[]` rows via
`wp.template('local-business-type')`).

## Other feature classes (includes/iworks/simple-seo-improvements/)

Every file: ABSPATH guard + own `require_once` of
`class-iworks-simple-seo-improvements-base-abstract.php`.

- **`class-iworks-simple-seo-improvements-posttypes.php`** — registers
  per-post-type meta boxes / settings bridges (robots mode per entry).
- **`class-taxonomies.php`** — taxonomy term meta: HTML title, HTML
  description, robots checkboxes per term.
- **`class-iworks-simple-seo-improvements-archives.php`** — robots for
  author/date archives.
- **`class-iworks-simple-seo-improvements-prefixes.php`** — removes the
  `/category/` and `/tag/` URL prefixes (options `category_no_slug`,
  `tag_no_slug`).
- **`class-iworks-simple-seo-improvements-links.php`** — rewrites external
  links in content using **Simple HTML DOM** (`vendor/simple_html_dom.php`):
  adds `rel="nofollow"`, `target="_blank"`, CSS class (options
  `exli:rel:nofollow`, `exli:target:blank`, `exli:class`).
- **`class-iworks-simple-seo-improvements-opensearch.php`** — OpenSearch
  description document (option `opensearch_on`).
- **`class-sitemap.php`** + **`class-sitemaps-attachements.php`** —
  attachments-only (images) sitemap for WP native sitemaps.
- **`class-iworks-robots-txt.php`** — builds robots.txt from options:
  custom rules (`robots_txt`), `disallow all` mode, extra allow/disallow
  lists, AI-crawler blocking (GPTBot, Claude, Bytespider, Perplexity, CCBot,
  …). Header comment `'# PLUGIN_NAME PLUGIN_VERSION'` (placeholder).
- **`index-now/`** — `iworks_index_now` base + Bing and Seznam
  implementations; pings URL changes on `save_post` when enabled
  (`indexnow_bing` option).
- **`class-iworks-simple-seo-improvements-github.php`** — GitHub self-updater
  (repo `iworks/simple-seo-improvements`, releases API, update transient).
  Excluded from the wp.org release ZIP.

## Post types & taxonomies

There are **no custom post types or taxonomies registered by this plugin**.
"posttypes"/"taxonomies" classes only attach SEO settings to existing
content types; the settings are generated dynamically in the main class
(`filter_add_post_types_options()`) via the `iworks_plugin_get_options`
filter.

## Vendored frameworks (includes/iworks/, git submodules)

- `options/` — `iworks_options` v3.1.1: settings pages, option storage and
  upgrade routines. Configured from `etc/options.php`; option names get the
  `iworks_ssi_` prefix. Separate git repo — treat as vendor, do not refactor.
- `rate/` — iWorks Rate v2.3.2: rate-it / admin notices, anniversary
  templates, dashboard widgets. Separate git repo — vendor.
- `vendor/simple_html_dom.php` — Simple HTML DOM parser (used by `links`).

## Adding a new class

- **Feature class**: create
  `includes/iworks/simple-seo-improvements/class-iworks-simple-seo-improvements-{name}.php`
  with the ABSPATH guard, `require_once` of `...-base-abstract.php`, and the
  class extending `iworks_simple_seo_improvements_base_abstract` (constructor
  receives the main class instance). Instantiate it in the main constructor
  (`require_once` + `new`, add the short name to the `$classess` loop array
  for simple ones).
- **Options field**: add the field array in `etc/options.php` (`name`, `type`,
  `th`, `sanitize_callback`, `since`) or extend via the
  `iworks_plugin_get_options` filter.
