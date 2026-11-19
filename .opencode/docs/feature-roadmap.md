# Feature roadmap — candidates from the WordPress.org "seo" tag survey

> Comparison basis: feature sets of SEOPress, The SEO Framework, SiteSEO,
> SureRank, and SEO SIMPLE PACK (surveyed 2026-11), filtered against features
> this plugin already has (meta title/description/robots, OG/Twitter,
> JSON-LD LocalBusiness/Article, XML sitemap + images, robots.txt editor +
> AI-crawler blocking, IndexNow, link rewriting, category/tag prefix removal,
> custom code injection, archives meta).
>
> Tiers:
>
> - **must** — expected of any SEO plugin today; users notice the gap immediately
> - **ok** — standard in this weight class; strong differentiators for a "simple" plugin
> - **nice** — differentiators that stay lightweight; pick selectively
> - **wish** — showcase/future ideas; heavy or out of character for "Simple"

## Must

| Feature                                                                                             | Who has it                            | Notes                                                                                                                                                                                                                         |
| --------------------------------------------------------------------------------------------------- | ------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ~~Custom canonical URL per post/term~~ — **declined**                                               | SEOPress, TSF, SureRank, SiteSEO, SSP | Decided against: WP core already prints a canonical for singular pages; per-term canonical is a niche case for a "simple" plugin                                                                                              |
| Webmaster tools verification codes (GSC, Bing, Yandex, Pinterest) — **DONE in 2.4.0**               | all 5                                 | Shipped: options `wmt_google`/`wmt_bing`/`wmt_yandex`/`wmt_pinterest`, meta tags in `filter_wp_head_add_html_head()`                                                                                                          |
| Attachment handling (redirect attachment pages to parent, noindex attachments) — **DONE in 2.4.0**  | SEOPress, SureRank, TSF               | Shipped: class `attachments`, options `atta_redirect` + `atta_redirect_unattached` (nothing/home), injected via `iworks/simple-seo-improvements/add_post_type_options/opts`; noindex covered by existing `attachment_noindex` |
| Per-page-type default noindex (search, paged, empty categories, date archives) — **partially done** | TSF, SSP                              | Remaining gaps only: `is_search()` and empty term archives; singular/author/date/term coverage already exists                                                                                                                 |
| Import/export settings + migration from Yoast/AIOSEO/Rank Math                                      | SEOPress, SiteSEO, SureRank           | Barrier to entry for switchers; good ops anyway                                                                                                                                                                               |

## OK

| Feature                                                     | Who has it                       | Notes                                                        |
| ----------------------------------------------------------- | -------------------------------- | ------------------------------------------------------------ |
| Breadcrumbs (output + BreadcrumbList JSON-LD)               | SEOPress, TSF, SiteSEO, SureRank | Fits existing JSON-LD infrastructure                         |
| Primary term/category selection                             | TSF, SEOPress PRO, SiteSEO       | Small UX feature, big sitemap/schema payoff                  |
| Disable/redirect author archives (301 or disable)           | TSF, SSP                         | Complements existing archive meta robots                     |
| Image SEO: auto `alt`/`title` from filename                 | SEOPress, SureRank, SiteSEO      | Cheap, popular; fits sitemap-images work                     |
| `llms.txt` generation (AI-readiness)                        | SEOPress, SureRank, SiteSEO      | Sibling to existing AI-crawler blocking — thematic coherence |
| Google Analytics / GA4 / GTM snippet field                  | SEOPress, SSP, SiteSEO           | Merges with existing custom-code injection; just a preset    |
| SERP / social preview in the metabox (Google snippet, FB/X) | SEOPress, SiteSEO, SureRank      | UX polish for existing fields, no new SEO surface            |
| WP-CLI commands (flush sitemap, export settings)            | SEOPress                         | Project already ships WP-CLI-friendly tooling                |

## Nice

| Feature                                             | Who has it                       | Notes                                                    |
| --------------------------------------------------- | -------------------------------- | -------------------------------------------------------- |
| Google News sitemap / video sitemap                 | SEOPress PRO, SiteSEO PRO        | Extends existing sitemap class; medium effort            |
| Custom JSON-LD injection + FAQ/How-to schema        | SEOPress, SureRank, SiteSEO      | Extends existing JSON-LD work                            |
| IndexNow history/log + all engines (Yandex, Seznam) | SEOPress, SiteSEO                | Seznam class already exists; log UI is the missing layer |
| RSS feed footer / before-content options            | SEOPress, SureRank               | Tiny feature, cheap to ship                              |
| Per-author SEO fields on profile screen             | TSF, SureRank                    | Complements author-archive meta                          |
| Color-coded SEO status column in post lists         | TSF, SureRank                    | Awareness without full content analysis                  |
| Bulk edit / quick edit of SEO fields                | TSF, SEOPress                    |                                                          |
| Multisite (network) support                         | SEOPress, TSF, SiteSEO           | Growing ask                                              |
| Multilingual (WPML/Polylang) sitemap + hreflang     | SEOPress, TSF, SiteSEO, SureRank | Medium-high effort                                       |

## Wish

| Feature                                                    | Who has it                              | Notes                                                                       |
| ---------------------------------------------------------- | --------------------------------------- | --------------------------------------------------------------------------- |
| Redirect manager (301/302, 404 monitor)                    | SEOPress PRO, SureRank PRO, SiteSEO PRO | Most-requested in the category, but big scope (DB tables, cron, logging)    |
| Content analysis / readability + keyword scoring           | SEOPress, SiteSEO, SureRank             | The Yoast-defining feature; heavy, opinionated                              |
| AI generation of titles/descriptions                       | SEOPress PRO, SureRank, SiteSEO PRO     | Requires provider API + cost governance (ai-llm-integration-security skill) |
| Search Console integration / statistics dashboard          | SEOPress PRO, SureRank, SiteSEO PRO     | External OAuth, data storage — separate plugin's worth of scope             |
| Broken link checker                                        | SEOPress PRO, SureRank PRO              | CPU/crawl-heavy; poor fit for a lightweight plugin                          |
| Site audit / SEO checks dashboard                          | SureRank, SEOPress PRO, SiteSEO PRO     |                                                                             |
| Internal linking suggestions                               | SEOPress PRO, SureRank                  |                                                                             |
| Schema builder for many types (Event, Recipe, JobPosting…) | SEOPress PRO, SiteSEO PRO               |                                                                             |
| MCP server / Abilities API exposure                        | SEOPress, SureRank                      | Forward-looking; see wp-abilities-api skill                                 |
| White label                                                | SEOPress, TSF                           |                                                                             |

## Suggested reading

The plugin's identity is "simple, no bloat." The **must** tier alone closes
every credibility gap against the free tiers of SureRank/SEOPress. Items in
**ok** — breadcrumbs, `llms.txt`, primary term — fit the existing architecture
(JSON-LD class, sitemap class, options framework) without adding admin weight.
**Redirect manager** is the single most-requested feature in the category, but
only take it on as a deliberate scope decision.
