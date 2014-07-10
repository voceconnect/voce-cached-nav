=== Voce Cached Nav ===
Contributors: markparolisi, voceplatforms, nattyait, kevinlangleyjr
Tags: nav, menus, cache, caching, performance
Requires at least: 3.3
Tested up to: 3.9.1
Stable tag: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Serve cached copies of WordPress navigation menus

== Description ==

Replace your template calls to `wp_nav_menu` with `voce_cached_nav_menu` to retreive cached copies of menu objects.

== Installation ==

1. Upload `voce-cached-nav` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Replace calls to `wp_nav_menu` with `voce_cached_nav_menu` in your templates

== Frequently Asked Questions ==

= Can I pass the same arguments to voce_cached_nav_menu? =

Yes. The caching class is essentially just a refactor of that large core function with caching at all possible levels.

== Screenshots ==

== Changelog ==
= 1.3 =
* Fixing undefined $data variable bug when deleting menus

= 1.2 =
* Adding Capistrano deploy files

= 1.1.2 =
* Fix for declaration that defines if adding 'menu-item-has-children' class to parent items

= 1.1.1 =
* Add 'menu-item-has-children' class to parent items

= 1.1 =
* 3.6 compatibility

= 1.0 =
* Initial release.
