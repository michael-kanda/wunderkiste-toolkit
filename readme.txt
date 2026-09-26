=== Wunderkiste Toolkit ===
Contributors: michaelkanda
Tags: seo, schema, svg, image resize, lightbox
Requires at least: 6.3
Tested up to: 7.1
Stable tag: 2.12
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modular toolkit: SEO meta and schema, image resizing, SVG uploads, login protection, lightbox and more. Enable only what you need.

== Description ==

Wunderkiste Toolkit bundles **18 small modules** in one plugin. Every module is disabled by default and only loads when you switch it on under "Settings → Wunderkiste Toolkit". No bloat, no overhead for features you do not use.

= SEO & Content =

**SEO Meta Settings**
Per post and page:

* Custom SEO title and meta description
* Robots directives, merged into the robots tag WordPress already prints
* Open Graph tags (og:title, og:description, og:image) with the featured image as fallback
* Twitter Card tags

**SEO Schema (JSON-LD)**
A meta box for custom structured data (Schema.org). Enter plain JSON without script tags. Invalid JSON is flagged in the editor and never printed on the frontend.

**Bulk NoIndex Manager**

* Bulk actions "Set NoIndex" and "Remove NoIndex" for posts and pages
* Status column in the post list
* NoIndex always takes priority over other robots settings

**Attachment Redirects ("SEO Zombie Killer")**
Redirects the empty attachment pages WordPress creates for every upload: 301 to the parent post, or 302 to the homepage for unattached files.

**Conversion Tracker**
Fires GA4 events and Google Ads conversions on selected pages, e.g. thank-you pages. Event name, conversion ID, label, value and currency are configurable. The module only calls an existing `gtag()` on your site; it does not load any Google script itself.

= Images & Media =

**Image Resizer (800px / 1200px)**
Scales an image to 800 or 1200 px (longest side, 92% quality) with one click, in the attachment details, the media list, or as a bulk action. The original file is overwritten.

**Upload Cleaner**
Cleans file names on upload: umlauts are transliterated (ä → ae, ß → ss), spaces become hyphens, everything is lowercased.

**Zero-Click Image SEO**
Generates the image title and, if empty, the alt text from the file name on upload.

**Media Inspector**
Adds file size and pixel dimensions columns to the media library.

**SVG Upload Support**
Allows SVG and SVGZ uploads for users with the `unfiltered_html` capability (filterable via `seowk_svg_upload_capability`). Every file is sanitized with the bundled enshrined/svg-sanitize library.

**Decent Lightbox**
Lightweight lightbox that is enabled per image in the media library. Vanilla JavaScript, keyboard accessible (Esc, focus trap), respects `prefers-reduced-motion`.

= Performance =

**Emoji Bloat Remover**
Removes the WordPress emoji detection script and styles.

= Security & Admin =

**XML-RPC Blocker**
Disables the XML-RPC interface. Note: apps that rely on XML-RPC stop working.

**Login Guard ("Login Türsteher")**
Hides the login form behind a secret key: `wp-login.php?YOURKEY`. There is no default key; the module does nothing until you set one. Logout, password reset, protected posts and recovery mode keep working.

**Comment Blocker**
Disables comments site-wide: post type support, admin menu, dashboard widget, meta boxes, feeds and list columns. WooCommerce product reviews are left alone (filterable via `seowk_comment_blocker_exempt_post_types`).

**ID Column Display**
Shows a sortable ID column for posts, pages, media and custom post types. Click an ID to copy it.

= Content Tools =

**Date Shortcode**
Prints the current date: `[seowk_date]`, plus the aliases `[datum]`, `[jahr]` (year) and `[monat]` (month).

Attributes: `format` (presets such as `numeric`, `full`, `full_day`, `month_year`, `iso`, `us`, `datetime`, or any PHP date format), `timezone`, `prefix`, `suffix`, `wrapper` (span, time, div, p, strong, em), `class`, `lang` (month and day names follow the site language by default; `de` or `en` forces a language).

Example: `[seowk_date format="full"]`

**Semantic Blocks**
HTML5 wrapper blocks for the block editor: article, section, aside, header, footer, main, figure, address, details/summary and mark, each with optional CSS class and ID.

= Clean uninstall =

Deleting the plugin via "Plugins → Delete" removes its settings, post meta, user meta and transients, on every site of a multisite network. Deactivating alone keeps all data.

== Installation ==

1. Upload the `wunderkiste-toolkit` folder to `/wp-content/plugins/`, or install the plugin from the WordPress plugin directory.
2. Activate the plugin through the "Plugins" screen.
3. Go to "Settings → Wunderkiste Toolkit".
4. Enable the modules you need and save.

== Frequently Asked Questions ==

= Are all modules active by default? =

No. Every module is disabled until you enable it.

= Does it work alongside other SEO plugins? =

Yes, but disable overlapping modules. For example, do not use SEO Meta Settings together with Yoast SEO or Rank Math.

= Are SVG uploads safe? =

Every SVG is sanitized on upload (scripts, event handlers, external references are removed), and only users who may post unfiltered HTML can upload SVGs.

= I enabled the Login Guard and forgot the key. What now? =

Rename the plugin folder via FTP to deactivate it, or look up the key in the `seowk_settings` option in the `wp_options` table.

= Can I change the conversion currency? =

Yes, under "Settings → Wunderkiste Toolkit → Additional settings", or with the `seowk_conversion_currency` filter.

= Is the plugin available in German? =

Yes. A German translation (de_DE and de_AT) is included, and the interface switches automatically with the site or user language. Language packs from translate.wordpress.org take priority once they exist. German documentation is available at https://designare.at/wunderkiste-toolkit.

== Screenshots ==

1. Settings page with all modules
2. SEO Meta Settings meta box
3. Media library with resizer buttons and inspector columns
4. NoIndex status column in the post list
5. Conversion Tracker meta box

== Changelog ==

= 2.12 =
* Renamed from "SEO Wunderkiste" to "Wunderkiste Toolkit" (slug and text domain `wunderkiste-toolkit`). Settings and post data are kept.
* Security: the image resizer bulk log no longer inserts attachment titles as HTML (DOM XSS). All resizer output is escaped.
* Fix: JSON-LD containing escaped quotes (\") was corrupted on save.
* Fix: SEO Meta Settings and Bulk NoIndex no longer print a second robots tag and a second canonical link; they now use the core `wp_robots` filter.
* Fix: resize status messages now also show in the media list view.
* All functions, classes and constants use the `seowk` prefix (lightbox and resizer included).
* Translations are loaded by WordPress; the plugin no longer creates a `languages` folder inside its own directory.
* Uninstall uses the metadata API instead of a direct database query.
* The interface is now English, with a bundled German translation (de_DE, de_AT) for PHP and JavaScript strings.
* Date shortcode: month and day names follow the site language by default; `lang="de"` or `lang="en"` still forces a language.
* The lightbox no longer loads wp-i18n on the frontend.
* readme rewritten in English.

= 2.11 =
* Security: SVG sanitizing now uses the bundled enshrined/svg-sanitize 0.22.0 library. The previous filter missed elements in the SVG namespace.
* Security: SVG uploads require `unfiltered_html` (filter `seowk_svg_upload_capability`).
* Security: closed a stored XSS in the JSON-LD output.
* Security: the image resizer checks `edit_post` on the attachment and no longer lets the request choose which nonce to verify.
* Security: Bulk NoIndex checks edit rights per post; the date shortcode output is escaped.
* SVG: `@import` and external `url()` references are removed from style blocks; SVGZ uploads are supported; dimensions are read from width/height or viewBox.
* Login Guard rewritten: only GET requests to the login form are filtered, access is remembered in a short-lived HttpOnly cookie, and the published default key was removed.
* Comment Blocker leaves WooCommerce product reviews alone and only removes the comment feed link.
* `og:locale` follows the site language.
* Semantic Blocks use block API v3.
* Debug console output only with WP_DEBUG.
* Multisite uninstall cleans post meta on all sites.

= 2.10 =
* New: Decent Lightbox integrated as a module (previously a standalone plugin).

= 2.7 =
* New: Semantic Blocks module with 10 HTML5 wrapper blocks.
* New: block for the date shortcode.
* Image Resizer offers 800px and 1200px.

= 2.6 =
* New: Date Shortcode module.

= 2.5 =
* New: SEO Meta Settings with Open Graph and Twitter Cards.

= 2.4 =
* New: Conversion Tracker for GA4 and Google Ads.
* New: ID Column Display.

= 2.3 =
* New: Bulk NoIndex Manager and Comment Blocker.

= 2.2 =
* New: SVG Upload Support and Media Inspector.

= 2.1 =
* New: Login Guard and attachment redirects.

= 2.0 =
* Rebuilt as a modular plugin; all modules disabled by default.

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 2.12 =
The plugin is now called "Wunderkiste Toolkit" and lives in the folder `wunderkiste-toolkit`. When updating manually from "SEO Wunderkiste", deactivate the old plugin first; your settings are kept.

== Third-party libraries ==

* enshrined/svg-sanitize 0.22.0 (GPL-2.0-or-later), bundled in `includes/vendor/svg-sanitize/`.
