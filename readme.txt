=== Plugin Name ===
Contributors: markparolisi, voceplatforms
Tags: nav menus
Requires at least: 3.3
Tested up to: 3.4
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Serve cached copies of WordPress navigation menus

== Description ==

Replace your template calls to `wp_nav_menu` with `wp_cached_nav_menu` to retreive cached copies of menu objects.

== Installation ==

1. Upload `voce-cached-nav` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Replace calls to `wp_nav_menu` with `wp_cached_nav_menu` in your templates

== Frequently Asked Questions ==

= Can I pass the same arguments to wp_cached_nav_menu? =

Yes. The caching class is essentially just a refactor of that large core function with caching at all possible levels.

== Screenshots ==

== Changelog ==

= 1.0 =
* Initial release.
