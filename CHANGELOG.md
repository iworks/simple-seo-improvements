# Changelog — Simple SEO Improvements

Older releases (below 2.2.0) have been moved here from `readme.txt`.
The current changelog is maintained in `readme.txt` (WordPress.org format).

The project is maintained on GitHub at [iworks/simple-seo-improvements](https://github.com/iworks/simple-seo-improvements).

## 2.1.0 (2025-01-26)

- Added multiple `LocalBusiness` types.
- Removed the `Article` structured data from the LocalBusiness page.
- Improved the country selector.
- Improved the LocalBusiness `Type` list.
- Fixed the `og:image` output.
- Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 2.9.4.
- Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.2.1.

## 2.0.9 (2024-02-14)

- Improved JSON-LD output for products.
- Improved compatibility with the [OG plugin](https://wordpress.org/plugins/og/).
- Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 2.9.1.

## 2.0.8 (2024-02-08)

- Added the `offers`/`Offer` field.

## 2.0.7 (2024-02-06)

- Improved the `offerCount` field.

## 2.0.6 (2024-02-06)

- Added the `offerCount` field.
- Improved string sanitization.

## 2.0.5 (2024-01-31)

- Added support for WooCommerce products.
- Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.1.7.

## 2.0.4 (2023-12-29)

- Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 2.9.0.
- Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.1.6.

## 2.0.3 (2023-11-22)

- Added a check for the LocalBusiness page option to prevent the form from appearing on all new pages.

## 2.0.2 (2023-11-15)

- Added blocking of AI bots in the `robots.txt` file.
- Fixed a dynamic property issue.

## 2.0.1 (2023-11-07)

- Fixed a too-early call of the `get_pages()` function. Thanks to [schulz](https://wordpress.org/support/users/schulz/).
- Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 2.8.8.

## 2.0.0 (2023-11-01)

- Added structured data.

## 1.5.8 (2023-11-01)

- Fixed a small typo in the structured data.

## 1.5.7 (2023-11-01)

- Added post structured data (JSON-LD).
- Added sitelinks search box structured data (JSON-LD).
- Added logo structured data (JSON-LD).
- Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 2.8.7.
- Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.1.3.

## 1.5.6 (2023-10-06)

- Added the `simple-seo-improvements/is_active` filter, which allows checking whether the plugin is active.

## 1.5.5 (2023-08-03)

- Moved the `robots.txt` options to the configuration panel.

## 1.5.4 (2023-06-30)

- Replaced the deprecated `FILTER_SANITIZE_STRING` flag with `FILTER_DEFAULT`.
- Removed unnecessary trailing slashes.
- Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 2.8.5.
- Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.1.2.

## 1.5.3 (2023-06-09)

- Set the correct post type for the front page.
- Set the correct post type for all singular views.
- Set the schema attribute `name` to `og:title`.
- Set the schema attribute `description` to `og:description`.

## 1.5.2 (2023-05-09)

- Added the ability to set `noindex` for Google News.
- Added the ability to set `noindex` for Google.
- Added the ability to configure the "Max Image Preview" value.
- Added the ability to configure the "Max Snippet" value.
- Added the ability to configure the "Max Video Preview" value.

## 1.5.1 (2023-01-18)

- Added an `is_array` check to avoid warnings in certain situations.

## 1.5.0 (2023-01-17)

- Added meta robots for the author, day, month, and year archives.
- Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 2.8.4.

## 1.4.5 (2022-12-16)

- Improved the `robots.txt` output.

## 1.4.4 (2022-12-02)

- Improved PHP syntax compatibility.
- Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.1.1.

## 1.4.3 (2022-09-19)

- Fixed a typo in an `sprintf()` pattern.

## 1.4.2 (2022-09-19)

- Added the ability to use the default image as the site icon.
- Moved the [iWorks Rate](https://github.com/iworks/iworks-rate) module repository to GitHub.

## 1.4.1 (2022-06-29)

- Fixed saving attachment data.
- Fixed the translation domain.
- Fixed an issue with an empty description.

## 1.4.0 (2022-03-02)

- Improved the `robots.txt` output.

## 1.3.0 (2022-03-02)

- Added IndexNow support for Bing.

## 1.2.4 (2022-02-25)

- Fixed the display of `og:image` when the [OG — Better Share on Social Media](https://wordpress.org/plugins/og/) plugin is installed and activated.
- Fixed a missing function when editing a taxonomy. Thanks to [Michał Ruszczyk](https://profiles.wordpress.org/mruszczyk/).
- Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 2.8.1.

## 1.2.3 (2022-02-23)

- Fixed the filter call order — singular settings were ignored.

## 1.2.2 (2022-02-22)

- Fixed a typo in the meta description field.

## 1.2.1 (2022-02-22)

- Fixed the display of the meta description when the [OG — Better Share on Social Media](https://wordpress.org/plugins/og/) plugin is installed and activated.

## 1.2.0 (2022-02-17)

- Added the meta description for the homepage displaying a list of posts.
- Added the default `og:image`.
- Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.1.0.

## 1.1.0 (2022-02-15)

- Added the ability to insert custom code into the HTML `<head>`.
- Added the ability to insert custom code at the beginning of the `<body>` tag.
- Added the ability to insert custom code at the end of the `<body>` tag.
- Added the ability to remove the category URL prefix.
- Added the ability to remove the tag URL prefix.
- Added the `fb:app_id` option to the configuration.
- Added integration with the [OG — Better Share on Social Media](https://wordpress.org/plugins/og/) plugin.
- Added the `twitter:site` option to the configuration.
- Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 2.8.1.

## 1.0.8 (2022-01-21)

- Updated the [iWorks Options](https://github.com/iworks/wordpress-options-class) module to version 2.8.0.
- Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.0.6.

## 1.0.7 (2022-01-17)

- Fixed the link from the plugins page to the configuration page. Thanks to [Sebastian Miśniakiewicz](https://profiles.wordpress.org/sebastianm/).

## 1.0.6 (2022-01-14)

- Added configuration for single entry defaults.

## 1.0.5 (2021-09-28)

- Changed the attachment URL to the direct file URL.
- Restricted the sitemap XML attachments to images only.

## 1.0.4 (2021-09-28)

- Added the ability to publish images in the sitemap XML.

## 1.0.3 (2021-09-04)

- Fixed missing data on the blog posts page.
- Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.0.4.

## 1.0.2 (2021-06-29)

- Stripped tags from the title and description fields.
- Updated the [iWorks Rate](https://github.com/iworks/iworks-rate) module to version 2.0.2.

## 1.0.1 (2021-06-08)

- Added integration with the [OG plugin](https://wordpress.org/plugins/og/) to use the same custom title for `og:title`.

## 1.0.0 (2021-05-21)

- Initial release.
- Added a custom HTML title for entries.
- Added a custom HTML title for taxonomies.
- Added the HTML meta `description` for entries.
- Added the HTML meta `description` for taxonomies.
- Added the HTML meta `robots` for entries.
- Added the HTML meta `robots` for taxonomies.
