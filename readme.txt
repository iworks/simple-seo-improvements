=== PLUGIN_TITLE ===
Contributors: iworks
Donate link: https://ko-fi.com/iworks?utm_source=simple-seo-improvements&utm_medium=readme-donate
Tags: seo, robots, meta robots, meta description, google search console. facebook, twitter, category, tag
Requires at least: PLUGIN_REQUIRES_WORDPRESS
Tested up to: 6.0
Stable tag: PLUGIN_VERSION
Requires PHP: PLUGIN_REQUIRES_PHP
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

PLUGIN_TAGLINE

== Description ==

PLUGIN_DESCRIPTION

Change HTML title and META robots fields for all post types and taxonomies.

Content editing allows you to set a meta value for the page title and description, post, and your own content types.

The configuration allows you to set the default values of the "robots" meta field for pages, entries, attachments and your own content types.

You can block the indexing of unwanted pages, such as attachment pages, but leave the link-follow parameter.

You can also choose whether the global settings will be set values for new content or be enforced for each content type.

= Features list =

* Add custom html code after <code>&lt;BODY&gt;</code> tag.
* Add custom html code before <code>&lt;/BODY&gt;</code> tag.
* Add custom html code in <code>&lt;HEAD&gt;</code> section.
* Add default site og:image.
* Add different html title for single entry.
* Add Facebook application ID.
* Add meta description for homepage when it display posts.
* Add meta description for single entry.
* Add meta robots for all build in post types (post, page, media).
* Add meta robots for all custom post types
* Add meta robots for single entry.
* Add Twitter site.
* Remove category URL prefix.
* Remove tag URL prefix.

== Installation ==

There are 3 ways to install this plugin:

= 1. The super easy way =
1. In your Admin, go to menu Plugins > Add
1. Search for `Simple SEO Improvements`
1. Click to install
1. Activate the plugin
1. A new box `Simple SEO Improvements` will appear in entry and taxonomy screen
1. A new menu `SEO Improvements` in `Appearance` will appear in your Admin Settings Menu

= 2. The easy way =
1. Download the plugin (.zip file) on the right column of this page
1. In your Admin, go to menu Plugins > Add
1. Select button `Upload Plugin`
1. Upload the .zip file you just downloaded
1. Activate the plugin
1. A new box `Simple SEO Improvements` will appear in entry and taxonomy screen
1. A new menu `SEO Improvements` in `Appearance` will appear in your Admin Settings Menu

= 3. The old and reliable way (FTP) =
1. Upload `Simple SEO Improvements` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. A new box `Simple SEO Improvements` will appear in entry and taxonomy screen
1. A new menu `SEO Improvements` in `Appearance` will appear in your Admin Settings Menu

== Frequently Asked Questions ==

= Why should I change title? =

I do not know... but some times we have to.

== Screenshots ==

1. Entry edit screen.
1. Taxonomy edit screen.
1. Plugin configuration screen - posts.

== Changelog ==

= 1.2.3 (2022-02-23) =
* Fixed filter call order - singular settings was ignored.

= 1.2.2 (2022-02-22) =
* Fixed typo in meta description field.

= 1.2.1 (2022-02-22) =
* Fixed display meta description when [OG — Better Share on Social Media](https://wordpress.org/plugins/og/) plugin is installed and activated.

= 1.2.0 (2022-02-17) =
* Added meta description for homepage with posts blog list.
* Added default og:image.
* Updated iWorks Rate to 2.1.0.

= 1.1.0 (2022-02-15) =
* Added ability to add custom code for HTML HEAD.
* Added ability to add custom code for the begin of the BODY tag.
* Added ability to add custom code for the end of the BODY tag.
* Added ability to remove category URL prefix.
* Added ability to remove tag URL prefix.
* Added `fb:app_id` into configuration.
* Added integration with [OG — Better Share on Social Media](https://wordpress.org/plugins/og/).
* Added `twitter:site` into configuration,
* Updated iWorks Options to 2.8.1.

= 1.0.8 (2022-01-21) =
* Updated iWorks Options to 2.8.0.
* Updated iWorks Rate to 2.0.6.

= 1.0.7 (2022-01-17) =
* Fixed link from plugins page to configuration page. Props for [Sebastian Miśniakiewicz](https://profiles.wordpress.org/sebastianm/)

= 1.0.6 (2022-01-14) =
* Added configuration for single entries defaults.

= 1.0.5 (2021-09-28) =
* Changed attachment URL to direct URL to file.
* Filter attachments on sitemap XML only to images.

= 1.0.4 (2021-09-28) =
* Added ability to publish images on Sitemap XML.

= 1.0.3 (2021-09-04) =
* Fixed missing data on blog posts page.
* Updated iWorks Rate to 2.0.4.

= 1.0.2 (2021-06-29) =
* Strip tags from title and description fields.
* Updated iWorks Rate to 2.0.2.

= 1.0.1 (2021-06-08) =
* Added integration with [OG Plugin](https://wordpress.org/plugins/og/) to use the same custom title for `og:title`.

= 1.0.0 (2021-05-21) =
* First release.
* Added HTML custom title for entries.
* Added HTML custom title for taxonomies.
* Added HTML meta `description` for entries.
* Added HTML meta `description` for taxonomies.
* Added HTML meta `robots` for entries.
* Added HTML meta `robots` for taxonomies.

== Upgrade Notice ==

