# Changelog — Simple SEO Improvements

Older releases (below 2.2.0) moved here from `readme.txt`.
The current changelog lives in `readme.txt` (WordPress.org format).

Project maintained on GitHub at [iworks/simple-seo-improvements](https://github.com/iworks/simple-seo-improvements).

## 2.1.0 (2025-01-26)

- Multiple `LocalBusiness` `Type` have been added.
- The `Article` has been removed from JSON-LD on LocalBusiness page.
- The country select has been improved.
- The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.9.4.
- The [iWorks Rate](https://github.com/iworks/iworks-rate) module has been updated to 2.2.1.
- The LocalBusiness `Type` list has been improved.
- The `og:image` has been fixed.

## 2.0.9 (2024-02-14)

- The JSON+LD has been improved for a product.
- Compatibility with the [OG plugin](https://wordpress.org/plugins/og/) has been improved.
- The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.9.1.

## 2.0.8 (2024-02-08)

- The "offers"/"Offer" field has been added.

## 2.0.7 (2024-02-06)

- The "offerCount" field has been improved.

## 2.0.6 (2024-02-06)

- The "offerCount" field has been added.
- Cleaning string has been improved.

## 2.0.5 (2024-01-31)

- The support for WooCommerce product has been added.
- The [iWorks Rate](https://github.com/iworks/iworks-rate) module has been updated to 2.1.7.

## 2.0.4 (2023-12-29)

- The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.9.0.
- The [iWorks Rate](https://github.com/iworks/iworks-rate) module has been updated to 2.1.6.

## 2.0.3 (2023-11-22)

- Check for the LocalBusiness page option has been added to avoid showing the form on all new pages.

## 2.0.2 (2023-11-15)

- The AI bots bloking has been added into the robots.txt file.
- The dynamic property has been fixed.

## 2.0.1 (2023-11-07)

- The too-early call of the function `get_pages()` has been fixed. Props for [schulz](https://wordpress.org/support/users/schulz/),
- The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.8.8.

## 2.0.0 (2023-11-01)

- The structured data has been added.

## 1.5.8 (2023-11-01)

- A small typo in structured data has been fixed.

## 1.5.7 (2023-11-01)

- The post data has been added (json-ld).
- The sitelinks search box structured data has been added (json-ld).
- The logo structured data has been added (json-ld).
- The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.8.7.
- The [iWorks Rate](https://github.com/iworks/iworks-rate) module has been updated to 2.1.3.

## 1.5.6 (2023-10-06)

- The filter `simple-seo-improvements/is_active` has been added. It allows you to check if the plugin is active.

## 1.5.5 (2023-08-03)

- Robots.txt options have been moved to the configuration panel.

## 1.5.4 (2023-06-30)

- The `FILTER_SANITIZE_STRING` flag has been replaced by `FILTER_DEFAULT`.
- The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.8.5.
- The [iWorks Rate](https://github.com/iworks/iworks-rate) module has been updated to 2.1.2.
- Unnecessary trailing slashes have been removed.

## 1.5.3 (2023-06-09)

- The front page post type has been set.
- Every singular post type has been set.
- The schema attribute "name" has been set to `og:title`.
- The schema attribute "description" has been set to `og:description`.

## 1.5.2 (2023-05-09)

- An ability to Google News noindex has been added.
- An ability to Google noindex has been added.
- An ability to set the "Max Image Preview" value has been added.
- An ability to set the "Max Snippet" value has been added.
- An ability to set the "Max Video Preview" value has been added.

## 1.5.1 (2023-01-18)

- The check for `is_array` has been added to avoid warnings in some situations.

## 1.5.0 (2023-01-17)

- iWorks Options has been updated to version 2.8.4.
- The meta robots for the author, day, month, and year archives have been added.

## 1.4.5 (2022-12-16)

- Improved robots.txt

## 1.4.4 (2022-12-02)

- Improved PHP syntax compatibility.
- iWorks Rate has been updated to 2.1.1.

## 1.4.3 (2022-09-19)

- Fixed a typo in sprintf pattern.

## 1.4.2 (2022-09-19)

- Added ability to use default image as site icon.
- Changed [iWorks Rate Module](https://github.com/iworks/iworks-rate) repository to GitHub.

## 1.4.1 (2022-06-29)

- Fixed saving attachment data.
- Fixed translation domain.
- Fixed issue with empty description.

## 1.4.0 (2022-03-02)

- Added improvements to the robots.txt file.

## 1.3.0 (2022-03-02)

- Added IndexNow for Bing.

## 1.2.4 (2022-02-25)

- Fixed display `og:image` when [OG — Better Share on Social Media](https://wordpress.org/plugins/og/) plugin is installed and activated.
- Fixed missing function when editing taxonomy. Props for [Michał Ruszczyk](https://profiles.wordpress.org/mruszczyk/).
- Updated iWorks Options to 2.8.1.

## 1.2.3 (2022-02-23)

- Fixed filter call order - singular settings were ignored.

## 1.2.2 (2022-02-22)

- Fixed typo in the meta description field.

## 1.2.1 (2022-02-22)

- Fixed display meta description when [OG — Better Share on Social Media](https://wordpress.org/plugins/og/) plugin is installed and activated.

## 1.2.0 (2022-02-17)

- Added meta description for the homepage with posts blog list.
- Added default og:image.
- Updated iWorks Rate to 2.1.0.

## 1.1.0 (2022-02-15)

- Added the ability to add custom code for HTML HEAD.
- Added the ability to add custom code for the beginning of the BODY tag.
- Added the ability to add custom code for the end of the BODY tag.
- Added ability to remove category URL prefix.
- Added ability to remove tag URL prefix.
- Added `fb:app_id` into configuration.
- Added integration with [OG — Better Share on Social Media](https://wordpress.org/plugins/og/).
- Added `twitter:site` into the configuration,
- Updated iWorks Options to 2.8.1.

## 1.0.8 (2022-01-21)

- Updated iWorks Options to 2.8.0.
- Updated iWorks Rate to 2.0.6.

## 1.0.7 (2022-01-17)

- Fixed link from the plugins page to the configuration page. Props for [Sebastian Miśniakiewicz](https://profiles.wordpress.org/sebastianm/)

## 1.0.6 (2022-01-14)

- Added configuration for single entries defaults.

## 1.0.5 (2021-09-28)

- Changed attachment URL to direct URL to file.
- Filter attachments on sitemap XML only to images.

## 1.0.4 (2021-09-28)

- Added ability to publish images on Sitemap XML.

## 1.0.3 (2021-09-04)

- Fixed missing data on the blog posts page.
- Updated iWorks Rate to 2.0.4.

## 1.0.2 (2021-06-29)

- Strip tags from title and description fields.
- Updated iWorks Rate to 2.0.2.

## 1.0.1 (2021-06-08)

- Added integration with [OG Plugin](https://wordpress.org/plugins/og/) to use the same custom title for `og:title`.

## 1.0.0 (2021-05-21)

- First release.
- Added HTML custom title for entries.
- Added HTML custom title for taxonomies.
- Added HTML meta `description` for entries.
- Added HTML meta `description` for taxonomies.
- Added HTML meta `robots` for entries.
- Added HTML meta `robots` for taxonomies.
