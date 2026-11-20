=== PLUGIN_TITLE ===
Contributors: iworks
Donate link: https://ko-fi.com/iworks?utm_source=simple-seo-improvements&utm_medium=readme-donate
Tags: PLUGIN_TAGS
Requires at least: PLUGIN_REQUIRES_WORDPRESS
Tested up to: PLUGIN_TESTED_WORDPRESS
Stable tag: PLUGIN_VERSION
Requires PHP: PLUGIN_REQUIRES_PHP
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

PLUGIN_TAGLINE

== Description ==

Simple SEO Improvements offers a lightweight solution to enhance your website's search engine optimization (SEO) effortlessly.

Easily modify HTML titles and META robots fields across all post types and taxonomies.

Content editing allows you to set custom meta values for page titles, descriptions, posts, and other content types.

With flexible configuration options, you can define default values for the "robots" meta field for pages, posts, attachments, and custom content types.

You can prevent the indexing of unwanted pages, such as attachment pages, while keeping the link-follow attribute intact. Additionally, choose whether global settings should apply to new content or be enforced for each content type individually.


= Key Features =

* Add a Facebook application ID.
* Add a Twitter (X) site tag.
* Add webmaster tools verification codes (Google Search Console, Bing, Yandex, Pinterest).
* Add structured data (JSON-LD) for articles and LocalBusiness.
* Add rel="nofollow" to external links.
* Add target="_blank" to external links.
* Add own CSS classes to external links.
* Apply meta robots to all post types (posts, pages, and media).
* Block AI crawlers in robots.txt (ChatGPT, Claude, CCBot, Bytespider, and more).
* Configure "Max Image Preview" settings.
* Control meta robots for author archives.
* Customize meta robots for individual entries.
* Include meta descriptions for each entry.
* Add meta descriptions for the homepage when displaying posts.
* Insert custom HTML code after the &lt;BODY&gt; tag.
* Insert custom HTML code before the &lt;/BODY&gt; tag.
* Insert custom HTML code in the &lt;HEAD&gt; section.
* Integrate IndexNow for Bing.
* Integrate OpenSearch for the site.
* Manage meta robots for date archives.
* Redirect attachment pages to their parent (or the main page).
* Remove category prefixes from URLs.
* Remove tag prefixes from URLs.
* Set a default og:image for the site.
* Set "Max Snippet" options.
* Set "Max Video Preview" options.
* Set a unique HTML title for individual entries.

Project maintained on GitHub at [iworks/simple-seo-improvements](https://github.com/iworks/simple-seo-improvements).

= Room for Improvement? =

We'd love your help to make Simple SEO Improvements even better!

1. **Report Bugs**: Found an issue? Report it by [creating a new topic](https://wordpress.org/support/plugin/simple-seo-improvements/) in the plugin forum. Once verified, the issue will be tracked in GitHub for resolution.
1. **Suggest New Features**: Have an idea for a new feature? Share it by creating a topic in the plugin forum to discuss its potential.
1. **Submit Pull Requests**: If you're a developer, contribute by addressing [existing issues on GitHub](https://github.com/iworks/simple-seo-improvements/issues). Be sure to check our contributing [guide for developers](https://github.com/iworks/simple-seo-improvements/blob/master/contributing.md).

Thank you for helping improve Simple SEO Improvements for everyone!

== Installation ==

There are 3 ways to install this plugin:

= 1. The super-easy way =
1. Navigate to the Plugins > Add menu in your Admin
1. Search for `Simple SEO Improvements`
1. Click to install
1. Activate the plugin
1. A new box `Simple SEO Improvements` will appear in the entry and taxonomy screen
1. A new menu `SEO Improvements` in `Settings` will appear in your Admin menu

= 2. The easy way =
1. Download the plugin (.zip file) on the right column of this page
1. In your Admin, go to the menu Plugins > Add
1. Select the button `Upload Plugin`
1. Upload the .zip file you just downloaded
1. Activate the plugin
1. A new box `Simple SEO Improvements` will appear in the entry and taxonomy screen
1. A new menu `SEO Improvements` in `Settings` will appear in your Admin menu

= 3. The old and reliable way (FTP) =
1. Upload the `Simple SEO Improvements` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. A new box `Simple SEO Improvements` will appear in the entry and taxonomy screen
1. A new menu `SEO Improvements` in `Settings` will appear in your Admin menu

== Frequently Asked Questions ==

= Why should I change the title? =

I'm not sure... but we have to do it sometimes.= I have a problem with the plugin, or I want to suggest a feature. Where can I do this? =

You can do it on [Support Threads](https://wordpress.org/support/plugin/simple-seo-improvements/#new-topic-0), but please add your ticket to [Github Issues](https://github.com/iworks/simple-seo-improvements/issues/new).

== Screenshots ==

1. The entry editing screen.
1. The taxonomy editing screen.
1. Plugin configuration screen - posts.

== Changelog ==

Project maintained on GitHub at [iworks/simple-seo-improvements](https://github.com/iworks/simple-seo-improvements).

= 2.3.8 =
* **Bugfix**: Fixed empty `<link rel="icon">` tags being printed when the default site icon attachment was missing or broken.
* **Bugfix**: Fixed robots.txt Allow/Disallow options losing line breaks when saved (`sanitize_text_field` was applied instead of `sanitize_textarea_field`), which merged all entries into one invalid line.
* **Improvement**: Added a unit test suite (139 tests) covering sanitization, save flows, and XSS resistance of config fields.

= 2.3.7 (2026-11-20) =
* **Bugfix**: Fixed a fatal error when saving posts (`wp_is_autosave()` did not exist).

= 2.3.6 (2026-11-20) =
* **Bugfix**: Fixed missing `<script>` wrapper for the JSON-LD output caused by over-eager sanitization.
* **Bugfix**: Fixed Custom Code body snippets being sanitized, which stripped `<script>` tags.

= 2.3.5 (2026-11-19) =
* **Improvement**: Replaced the bundled Simple HTML DOM parser with the WordPress native `WP_HTML_Tag_Processor` (HTML API) for external link rewriting. The `vendor/` directory has been removed.
* **Improvement**: Added webmaster tools verification codes (Google Search Console, Bing, Yandex, Pinterest).
* **Improvement**: Added attachment page handling: 301 redirect to the parent page, with an option for unattached files (leave as-is or redirect to the main page) and the `noindex` robots meta tag.
* **Improvement**: Added `noai` and `noimageai` for HTML meta tags for content.
* **Improvement**: Added Polish translations for new options.
* **Improvement**: Tested up to WordPress 7.1.
* **Security**: Added capability checks to post, term, and LocalBusiness meta saving.
* **Security**: Added revision and autosave guards when saving post meta.
* **Security**: Added output escaping for the IndexNow key and an error guard for the GitHub update check.
* **Security**: Escaped meta box, template, and OpenSearch XML output; sanitized the sitemap setting registration.
* **Bugfix**: Fixed missing `str_get_html()` function.
* **Maintenance**: Unified license declaration to GPL v3 or later in the plugin header.
* **Maintenance**: Renamed the `simple_seo_improvements_robots_txt_allow` / `simple_seo_improvements_robots_txt_disallow` functions and hooks to the `iworks_` prefix; old names keep working as deprecated aliases.

= 2.3.4 (2026-07-15) =
* **Dependencies**: Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 3.1.1.
* **Improvement**: Handled `LocalBusiness type` as a string if it is a single-element array.

= 2.3.3 (2026-06-29) =
* **Bugfix**: Fixed an issue where the local business type was not being saved correctly. Props for [anoriell - Katarzyna Janoska](https://profiles.wordpress.org/anoriell/). [#20](https://github.com/iworks/simple-seo-improvements/issues/20).
* **Improvement**: Extended support for blocking AI crawlers in robots.txt. Added the following AI bots to block: Anthropic AI, Bytespider, CCBot, ChatGPT-User, Claude Bot, Claude Web, Cohere AI, GPT Bot, OAI Search Bot, Perplexity Bot.

= 2.3.2 (2026-05-17) =
* **Dependencies**: Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 3.1.0 and the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.3.2.

= 2.3.1 (2025-09-18) =
* **Improvement**: The option to disallow all in robots.txt has been added.

= 2.3.0 (2025-09-05) =
* **Bugfix**: Fixed an issue where the `$options` object was not defined.
* **Dependencies**: Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 3.0.7.
* **Dependencies**: Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.3.1.

= 2.2.9 (2025-05-09) =
* **Bugfix**: Fixed an issue where the $content string was not parseable as HTML. Props for [anoriell - Katarzyna Janoska](https://profiles.wordpress.org/anoriell/). [#17](https://github.com/iworks/simple-seo-improvements/issues/17).

= 2.2.8 (2025-05-07) =
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 3.0.0.
* **Bugfix**: Fixed an issue with saving multiline text. [#16](https://github.com/iworks/simple-seo-improvements/issues/16).
* **Improvement**: Added the `www` prefix to site url during checking externals. [#14](https://github.com/iworks/simple-seo-improvements/issues/14).

= 2.2.7 (2025-03-14) =
* Removed the external class from internal links for improved consistency. Props for [Rafał Jendrzejewski](https://www.linkedin.com/in/rafaljendrzejewski/).

= 2.2.6 (2025-03-13) =
* Removed the external class from internal links for improved consistency. Props for [Michał Rogalski](https://dribbble.com/Rogalski_777).

= 2.2.5 (2025-03-11) =
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.9.8.
* Too aggressive sanitation has been fixed. Props for [Gabi](https://infoblogerka.pl/). [#12](https://github.com/iworks/simple-seo-improvements/issues/12).

= 2.2.4 (2025-03-09) =
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.9.7.
* Too aggressive sanitation has been fixed. Props for [Gabi](https://infoblogerka.pl/). [#12](https://github.com/iworks/simple-seo-improvements/issues/12).
* Updating plugin from GitHub releases has been improved.

= 2.2.3 (2025-02-22) =
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.9.6.

= 2.2.2 (2025-02-22) =
* An error with replacement on older PHP has been fixed.

= 2.2.1 (2025-02-21) =
* A typo has been fixed.

= 2.2.0 (2025-02-21) =
* The build process has been improved.
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.9.5.
* The [iWorks Rate](https://github.com/iworks/iworks-rate) module has been updated to 2.2.3.
* The `_load_textdomain_just_in_time()` notice has been fixed. [#6](https://github.com/iworks/simple-seo-improvements/issues/6).
* The rel="nofollow" attribute can be added now. [#4](https://github.com/iworks/simple-seo-improvements/issues/4). Props for [sylwiastein](https://github.com/sylwiastein).
* The target="blank" attribute can be added now. [#4](https://github.com/iworks/simple-seo-improvements/issues/4). Props for [sylwiastein](https://github.com/sylwiastein).
* The translation domain for few strings where been fixed.
* Translation placeholders has been added.

For older releases (below 2.2.0), see the [changelog](https://github.com/iworks/simple-seo-improvements/blob/master/CHANGELOG.md) on GitHub.

== Upgrade Notice ==

= 2.1.0 =
After the upgrade please visit the settings page and LocalBusiness Page to select a country.

