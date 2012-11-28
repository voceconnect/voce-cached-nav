<?php
/*
  Plugin Name: Voce Cached Nav
  Plugin URI: http://voceconnect.com
  Description: Serve cached WordPress Navigation Objects.
  Version: 1.0
  Author: Mark Parolisi
  License: GPL2
*/

/**
 * Caching class for wp_nav_menu.
 * @class Voce_Cached_Nav
 */
if ( !class_exists( 'Voce_Cached_Nav' ) ) {

	class Voce_Cached_Nav {

		const MENUPREFIX = 'wp_nav_menu-';
		const ITEMSPREFIX = 'wp_nav_items-';
		const MENUIDS = 'wp_nav_menus';
		
		/**
		 * Set the action hooks to update the cache
		 * @method init
		 * @constructor
		 */
		public static function init() {
			add_action( 'wp_create_nav_menu', array( __CLASS__, 'action_wp_update_nav_menu' ), 100 );
			add_action( 'wp_update_nav_menu', array( __CLASS__, 'action_wp_update_nav_menu' ) );
			add_action( 'wp_delete_nav_menu', array( __CLASS__, 'action_wp_delete_nav_menu' ), 100 );
			add_action( 'save_post', array( __CLASS__, 'action_save_post' ) );
		}

		/**
		 * @method action_wp_update_nav_menu
		 * @param Integer $menu_id 
		 */
		public static function action_wp_update_nav_menu( $menu_id ) {
			self::delete_menu_objects_cache( $menu_id );
		}

		/**
		 * @method action_wp_delete_nav_menu
		 * @param Integer $menu_id 
		 */
		public static function action_wp_delete_nav_menu( $menu_id ) {
			self::update_menu_ids_cache( $menu_id );
			self::delete_menu_objects_cache( $menu_id );
		}

		/**
		 * Clear the menu caches because the post title/permalink/etc could change.
		 * @method action_save_post
		 */
		public static function action_save_post( ) {
			// Passing 0 will ensure that all caches are deleted.
			self::delete_menu_objects_cache( 0 );
		}

		/**
		 * @method delete_menu_objects_cache
		 * @param Integer $menu_id
		 * @return type 
		 */
		public static function delete_menu_objects_cache( $menu_id ) {
			//if given an existing menu_id delete just that menu
			if ( term_exists( $menu_id, 'nav_menu' ) ) {
				return delete_transient( self::ITEMSPREFIX . $menu_id );
			} else { //delete all cached menus recursively
				$all_cached_menus = get_transient( self::MENUIDS );
				if ( is_array( $all_cached_menus ) ) {
					foreach ($all_cached_menus as $menu_id) {
						self::delete_menu_objects_cache( $menu_id );
					}
				}
			}
		}

		/**
		 * @method update_menu_ids_cache
		 * @param Integer $menu_id 
		 */
		public static function update_menu_ids_cache( $menu_id ) {
			$cache = get_transient( self::MENUIDS );
			// If there is already a cached array
			if ( is_array( $cache ) ) {
				// If the menu ID is not already in cache and is a valid menu
				if ( !in_array( $menu_id, $cache ) && term_exists( $menu_id, 'nav_menu' ) ) {
					$cache = array_merge( $cache, array( $menu_id ) );
				}
				foreach ($cache as $key => $cached_id) {
					// Remove the menu ID if it's invalid
					if ( !term_exists( $cached_id, 'nav_menu' ) ) {
						unset( $cache[$key] );
					}
				}
				$data = $cache;
				// If this is executing for the first time
			} else {
				if ( term_exists( $menu_id, 'nav_menu' ) ) {
					$data = array( $menu_id );
				}
			}
			set_transient( self::MENUIDS, $data );
		}

		/**
		 * @method parse_args
		 * @param Array $args
		 * @return Object Filtered args 
		 */
		public static function parse_args( $args ) {
			$defaults = array( 'menu' => '', 'container' => 'div', 'container_class' => '', 'container_id' => '', 'menu_class' => 'menu', 'menu_id' => '',
				'echo' => true, 'fallback_cb' => 'wp_page_menu', 'before' => '', 'after' => '', 'link_before' => '', 'link_after' => '', 'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
				'depth' => 0, 'walker' => '', 'theme_location' => '' );

			$args = wp_parse_args( $args, $defaults );
			$args = apply_filters( 'wp_nav_menu_args', $args );
			return (object) $args;
		}

		/**
		 * @method get_nav_menu_object
		 * @param Object $args
		 * @return type 
		 */
		public static function get_nav_menu_object( $args ) {
			if ( empty( $args->menu ) ) {
				$locations = get_nav_menu_locations();
				if ( empty( $locations ) ) {
					return false;
				}
				$menu_lookup = $locations[$args->theme_location];
			} else {
				$menu_lookup = $args->menu;
			}
			if ( $cache = get_transient( self::MENUPREFIX . $menu_lookup ) ) {
				$menu = $cache;
			} else {
				$menu = wp_get_nav_menu_object( $menu_lookup );
				set_transient( self::MENUPREFIX . $menu_lookup, $menu );
			}

			return $menu;
		}

		/**
		 * @method get_nav_menu_items
		 * @param Integer $term_id
		 * @return type 
		 */
		public static function get_nav_menu_items( $term_id ) {
			if ( $cache = get_transient( self::ITEMSPREFIX . $term_id ) ) {
				$items = $cache;
			} else {
				$items = wp_get_nav_menu_items( $term_id );
				set_transient( self::ITEMSPREFIX . $term_id, $items );
			}
			return $items;
		}

		/**
		 * @method menu
		 * @staticvar array $menu_id_slugs
		 * @param {Array} $args
		 * @return boolean 
		 */
		public static function menu( $args = array( ) ) {

			$args = self::parse_args( $args );

			$menu = self::get_nav_menu_object( $args );

			// If the menu exists, get its items.
			if ( $menu && !is_wp_error( $menu ) && !isset( $menu_items ) && property_exists( $menu, 'term_id' ) ) {
				$menu_items = self::get_nav_menu_items( $menu->term_id );
			}

			// If no menu was found or if the menu has no items and no location was requested, call the fallback_cb if it exists
			if ( (!$menu || is_wp_error( $menu ) || ( isset( $menu_items )
					&& empty( $menu_items ) && !$args->theme_location ) )
					&& $args->fallback_cb && is_callable( $args->fallback_cb ) ) {
				return call_user_func( $args->fallback_cb, (array) $args );
			}

			// If no fallback function was specified and the menu doesn't exists, bail.
			if ( !$menu || is_wp_error( $menu ) ) {
				return false;
			}

			static $menu_id_slugs = array( );

			$nav_menu = $items = '';

			// Set up the $menu_item variables
			_wp_menu_item_classes_by_context( $menu_items );

			$sorted_menu_items = array( );
			foreach ((array) $menu_items as $key => $menu_item) {
				$sorted_menu_items[$menu_item->menu_order] = $menu_item;
			}
			unset( $menu_items );

			$sorted_menu_items = apply_filters( 'wp_nav_menu_objects', $sorted_menu_items, $args );

			$show_container = false;

			if ( $args->container ) {
				$allowed_tags = apply_filters( 'wp_nav_menu_container_allowedtags', array( 'div', 'nav' ) );
				if ( in_array( $args->container, $allowed_tags ) ) {
					$show_container = true;
					$class = $args->container_class ? ' class="' . esc_attr( $args->container_class ) . '"' : ' class="menu-' . $menu->slug . '-container"';
					$id = $args->container_id ? ' id="' . esc_attr( $args->container_id ) . '"' : '';
					$nav_menu .= '<' . $args->container . $id . $class . '>';
				}
			}
			$items .= walk_nav_menu_tree( $sorted_menu_items, $args->depth, $args );
			unset( $sorted_menu_items );

			// Attributes
			if ( !empty( $args->menu_id ) ) {
				$wrap_id = $args->menu_id;
			} else {
				$wrap_id = 'menu-' . $menu->slug;
				while ( in_array( $wrap_id, $menu_id_slugs ) ) {
					if ( preg_match( '#-(\d+)$#', $wrap_id, $matches ) ) {
						$wrap_id = preg_replace( '#-(\d+)$#', '-' . ++$matches[1], $wrap_id );
					} else {
						$wrap_id = $wrap_id . '-1';
					}
				}
			}
			$menu_id_slugs[] = $wrap_id;

			$wrap_class = $args->menu_class ? $args->menu_class : '';

			// Allow plugins to hook into the menu to add their own <li>'s
			$items = apply_filters( 'wp_nav_menu_items', $items, $args );
			$items = apply_filters( "wp_nav_menu_{$menu->slug}_items", $items, $args );

			$nav_menu .= sprintf( $args->items_wrap, esc_attr( $wrap_id ), esc_attr( $wrap_class ), $items );
			unset( $items );

			if ( $show_container ) {
				$nav_menu .= '</' . $args->container . '>';
			}

			$nav_menu = apply_filters( 'wp_nav_menu', $nav_menu, $args );

			if ( $args->echo ) {
				echo $nav_menu;
			} else {
				return $nav_menu;
			}
		}

	}

	Voce_Cached_Nav::init();

	/**
	 * Just a template tag
	 * @method wp_cached_nav_menu
	 * @param Array $args 
	 */
	function wp_cached_nav_menu( $args ) {
		Voce_Cached_Nav::menu( $args );
	}

}