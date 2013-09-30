=== Widon’t Part Deux ===
Contributors: morganestes
Donate link: http://www.morganestes.me/donate
Tags: typography, widows, orphans, title
Requires at least: 3.5
Tested up to: 3.6.1
Stable tag: 1.1.
License: GPLv3

Widon’t Part Deux eliminates typographic widows in the titles and content your posts and pages.

== Description ==

Building on <a href="http://www.shauninman.com/archive/2008/08/25/widont_2_1_1" target="_blank">Shaun Inman’s plugin</a>, Widon’t Part Deux eliminates <a href="http://en.wikipedia.org/wiki/Widow_(typesetting)" target="_blank">widows</a> in the titles and content your posts and pages.

== Installation ==

The easy way:

1. Download from the WordPress plugins page and use the Administrator pages to upload and activate.

The other way:

1. Upload the `wp-widont` folder and contents to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Update the settings with any additional tags you want to remove widows and orphans from in a post.

== Frequently Asked Questions ==

= What is a widow and orphan? =

When the line length is short enough that a word wraps, but long enough that it only leaves one word on the next line.

`
It looks something like
this.

But will look like this.
`

= Why should I care? =

Because you want your site to be as usable for your visitors as possible. This will help.

= What tags can I add in the settings? =

The settings filter uses `wp_kses_post` to sanitize the tags you specify. By default, this is set in [`wp-includes/kses.php`](http://core.trac.wordpress.org/browser/tags/3.6.1/wp-includes/kses.php#L56).

== Changelog ==

= 1.1.0 =
* Enable validation and sanitization of the options input.
* Use the Settings API to get and store options in the database.

= 1.0.1 =
* Formatted to match WordPress coding standards.

= 1.0.0 =
* Forked from Shaun Inman's plugin at v2.1.1.