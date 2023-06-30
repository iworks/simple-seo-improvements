=== PLUGIN_TITLE ===
Contributors: iworks
Donate link: https://ko-fi.com/iworks?utm_source=simple-seo-improvements&utm_medium=readme-donate
Tags: seo, robots, meta robots, meta description, google search console. facebook, twitter, category, tag
Requires at least: PLUGIN_REQUIRES_WORDPRESS
Tested up to: 6.2
Stable tag: PLUGIN_VERSION
Requires PHP: PLUGIN_REQUIRES_PHP
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

PLUGIN_TAGLINE

== Description ==

PLUGIN_DESCRIPTION

Change the HTML title and META robots fields for all post types and taxonomies.

Content editing allows you to set a meta value for the page title and description, posts, and content types.

The configuration allows you to set the default values of the "robots" meta field for pages, entries, attachments, and your content types.

You can block the indexing of unwanted pages, such as attachment pages, but leave the link-follow parameter.

You can also choose whether the global settings will set values for new content or be enforced for each content type.

= List of features =

* Add Facebook application ID.
* Add IndexNow for Bing.
* Add the Twitter site.
* Insert custom HTML code after the `<BODY>` tag.
* Insert custom HTML code before the `</BODY>` tag.
* Insert custom HTML code in the `<HEAD>` section.
* Add default site og:image.
* Add a different HTML title for a single entry.
* Add meta description for the homepage when it displays posts.
* Include a meta description for each entry.
* Include meta robots in all build post types (post, page, and media).
* Include meta robots in all custom post types.
* Include meta robots in a single entry.
* Include meta robots in an author archive.
* Include meta robots in a date archive.
* Allows the category prefix to be removed from the URL.
* Allows the tag prefix to be removed from the URL.
* Allows to set the "Max Image Preview".
* Allows to set the "Max Snippet".
* Allows to set the "Max Video Preview".

== Installation ==

There are 3 ways to install this plugin:

= 1. The super-easy way =
1. Navigate to the Plugins > Add menu in your Admin
1. Search for `Simple SEO Improvements`
1. Click to install
1. Activate the plugin
1. A new box `Simple SEO Improvements` will appear in the entry and taxonomy screen
1. A new menu `SEO Improvements` in `Appearance` will appear in your Admin Settings Menu

= 2. The easy way =
1. Download the plugin (.zip file) on the right column of this page
1. In your Admin, go to the menu Plugins > Add
1. Select the button `Upload Plugin`
1. Upload the .zip file you just downloaded
1. Activate the plugin
1. A new box `Simple SEO Improvements` will appear in the entry and taxonomy screen
1. A new menu `SEO Improvements` in `Appearance` will appear in your Admin Settings Menu

= 3. The old and reliable way (FTP) =
1. Upload the `Simple SEO Improvements` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. A new box `Simple SEO Improvements` will appear in the entry and taxonomy screen
1. A new menu `SEO Improvements` in `Appearance` will appear in your Admin Settings Menu

== Frequently Asked Questions ==

= Why should I change the title? =

I'm not sure... but we have to do it sometimes.

== Screenshots ==

1. The entry editing screen.
1. The taxonomy editing screen.
1. Plugin configuration screen - posts.

== Changelog ==

= 1.5.4 (2023-06-30) =

* The `FILTER_SANITIZE_STRING` flag has been replaced by `FILTER_DEFAULT`.
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.8.5.
* The [iWorks Rate](https://github.com/iworks/iworks-rate) module has been updated to 2.1.2.
* Unnecessary trailing slashes have been removed.

= 1.5.3 (2023-06-09) =

* The front page post type has been set.
* Every singular post type has been set.
* The schema attribute "name" has been set to `og:title`.
* The schema attribute "description" has been set to `og:description`.

= 1.5.2 (2023-05-09) =

* An ability to Google News noindex has been added.
* An ability to Google noindex has been added.
* An ability to set the "Max Image Preview" value has been added.
* An ability to set the "Max Snippet" value has been added.
* An ability to set the "Max Video Preview" value has been added.

= 1.5.1 (2023-01-18) =

* The check for `is_array` has been added to avoid warnings in some situations.

= 1.5.0 (2023-01-17) =

* iWorks Options has been updated to version 2.8.4.
* The meta robots for the author, day, month, and year archives have been added.

= 1.4.5 (2022-12-16) =

* Improved robots.txt

= 1.4.4 (2022-12-02) =

* Improved PHP syntax compatibility.
* iWorks Rate has been updated to 2.1.1.

= 1.4.3 (2022-09-19) =

* Fixed a typo in sprintf pattern.

= 1.4.2 (2022-09-19) =

* Added ability to use default image as site icon.
* Changed [iWorks Rate Module](https://github.com/iworks/iworks-rate) repository to GitHub.

= 1.4.1 (2022-06-29) =

* Fixed saving attachment data.
* Fixed translation domain.
* Fixed issue with empty description.

= 1.4.0 (2022-03-02) =

* Added improvements to the robots.txt file.

= 1.3.0 (2022-03-02) =

* Added IndexNow for Bing.

= 1.2.4 (2022-02-25) =

* Fixed display `og:image` when [OG — Better Share on Social Media](https://wordpress.org/plugins/og/) plugin is installed and activated.
* Fixed missing function when editing taxonomy. Props for [Michał Ruszczyk](https://profiles.wordpress.org/mruszczyk/).
* Updated iWorks Options to 2.8.1.

= 1.2.3 (2022-02-23) =

* Fixed filter call order - singular settings were ignored.

= 1.2.2 (2022-02-22) =

* Fixed typo in the meta description field.

= 1.2.1 (2022-02-22) =

* Fixed display meta description when [OG — Better Share on Social Media](https://wordpress.org/plugins/og/) plugin is installed and activated.

= 1.2.0 (2022-02-17) =

* Added meta description for the homepage with posts blog list.
* Added default og:image.
* Updated iWorks Rate to 2.1.0.

= 1.1.0 (2022-02-15) =

* Added the ability to add custom code for HTML HEAD.
* Added the ability to add custom code for the beginning of the BODY tag.
* Added the ability to add custom code for the end of the BODY tag.
* Added ability to remove category URL prefix.
* Added ability to remove tag URL prefix.
* Added `fb:app_id` into configuration.
* Added integration with [OG — Better Share on Social Media](https://wordpress.org/plugins/og/).
* Added `twitter:site` into the configuration,
* Updated iWorks Options to 2.8.1.

= 1.0.8 (2022-01-21) =

* Updated iWorks Options to 2.8.0.
* Updated iWorks Rate to 2.0.6.

= 1.0.7 (2022-01-17) =

* Fixed link from the plugins page to the configuration page. Props for [Sebastian Miśniakiewicz](https://profiles.wordpress.org/sebastianm/)

= 1.0.6 (2022-01-14) =

* Added configuration for single entries defaults.

= 1.0.5 (2021-09-28) =

* Changed attachment URL to direct URL to file.
* Filter attachments on sitemap XML only to images.

= 1.0.4 (2021-09-28) =

* Added ability to publish images on Sitemap XML.

= 1.0.3 (2021-09-04) =

* Fixed missing data on the blog posts page.
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

