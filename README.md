Voce Cached Nav
==================

Contributors: markparolisi, voceplatforms, nattyait, kevinlangleyjr  
Tags: nav, menus, cache, caching, performance  
Requires at least: 3.3  
Tested up to: 4.2.2  
Stable tag: 1.3.4  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html

## Description
Serve cached copies of WordPress navigation menus by replacing your template calls to `wp_nav_menu` with `voce_cached_nav_menu`.

## Installation

### As standard plugin:
> See [Installing Plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

### As theme or plugin dependency:
> After dropping the plugin into the containing theme or plugin, add the following:

```php
if( ! class_exists( 'Voce_Cached_Nav' ) ) {
	require_once( $path_to_voce_cached_nav . '/voce-cached-nav.php' );
}
```

## Usage
Replacing your template calls to `wp_nav_menu` with `voce_cached_nav_menu`

## Frequently Asked Questions

* **Can I pass the same arguments to voce_cached_nav_menu?**
	* *Yes. The caching class is essentially just a refactor of that large core function with caching at all possible levels.*

# Changelog
**1.3**  
* Fixing undefined $data variable bug when deleting menus

**1.2**  
* Adding Capistrano deploy files

**1.1.2**  
* Fix for declaration that defines if adding 'menu-item-has-children' class to parent items

**1.1.1**  
* Add 'menu-item-has-children' class to parent items

**1.1**  
* 3.6 compatibility

**1.0**  
* Initial release.
