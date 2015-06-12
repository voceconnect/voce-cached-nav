<?php
/*
  Plugin Name: Voce Cached Nav
  Plugin URI: http://voceconnect.com
  Description: Serve cached WordPress Navigation Objects.
  Version: 1.3.4
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

			// Handle term splitting
			add_action( 'split_shared_term', array( __CLASS__, 'handle_term_splitting'), 10, 4 );
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
		public static function action_save_post() {
			// Passing 0 will ensure that all caches are deleted.
			self::get_nav_menus();
			self::delete_menu_objects_cache( 0 );
		}

		public static function get_nav_menus() {
			$menus = get_transient( self::MENUIDS );
			if ( !is_array( $menus ) ) {
				$menus = wp_get_nav_menus();
				foreach ( $menus as $menu ) {
					self::update_menu_ids_cache( $menu->term_id );
				}
			}
			return $menus;
		}

		/**
		 * @method delete_menu_objects_cache
		 * @param Integer $menu_id
		 *
		 * @return type
		 */
		public static function delete_menu_objects_cache( $menu_id ) {
			//if given an existing menu_id delete just that menu
			if ( term_exists( (int) $menu_id, 'nav_menu' ) ) {
				return delete_transient( self::ITEMSPREFIX . $menu_id );
			} else { //delete all cached menus recursively
				$all_cached_menus = get_transient( self::MENUIDS );
				if ( is_array( $all_cached_menus ) ) {
					foreach ( $all_cached_menus as $menu_id ) {
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
				if ( !in_array( $menu_id, $cache ) && term_exists( (int) $menu_id, 'nav_menu' ) ) {
					$cache = array_merge( $cache, array( $menu_id ) );
				}
				foreach ( $cache as $key => $cached_id ) {
					// Remove the menu ID if it's invalid
					if ( !term_exists( (int) $cached_id, 'nav_menu' ) ) {
						unset( $cache[ $key ] );
					}
				}
				$data = $cache;
				// If this is executing for the first time
			} else {
				$data = ( term_exists( (int) $menu_id, 'nav_menu' ) ) ? array( $menu_id ) : false;
			}

			if( $data ){
				set_transient( self::MENUIDS, $data );
			} else {
				delete_transient( self::MENUIDS );
			}
		}

		/**
		 * @method get_nav_menu_object
		 * @param Object $args
		 *
		 * @return type
		 */
		public static function get_nav_menu_object( $args ) {
			$menu_lookup = $args->menu;
			$menu = get_transient( self::MENUPREFIX . $menu_lookup );
			if ( empty( $menu ) ) {
				$menu = wp_get_nav_menu_object( $args->menu );
				set_transient( self::MENUPREFIX . $menu_lookup, $menu );
			}

			// Get the nav menu based on the theme_location
			if ( !$menu && $args->theme_location && ( $locations = get_nav_menu_locations() ) && isset( $locations[ $args->theme_location ] ) ) {
				$menu_lookup = $locations[ $args->theme_location ];
				$menu = get_transient( self::MENUPREFIX . $menu_lookup );
				if ( empty( $menu ) ) {
					$menu = wp_get_nav_menu_object( $locations[ $args->theme_location ] );
					set_transient( self::MENUPREFIX . $menu_lookup, $menu );
				}
			}

			// get the first menu that has items if we still can't find a menu
			if ( !$menu && !$args->theme_location ) {
				$menus = self::get_nav_menus();
				foreach ( $menus as $menu_maybe ) {
					if ( $menu_items = self::get_nav_menu_items( $menu_maybe->term_id, array( 'update_post_term_cache' => false ) ) ) {
						$menu = $menu_maybe;
						break;
					}
				}
			}

			return $menu;
		}

		/**
		 * @method get_nav_menu_items
		 * @param Integer $term_id
		 *
		 * @return type
		 */
		public static function get_nav_menu_items( $term_id, $args ) {
			if ( $cache = get_transient( self::ITEMSPREFIX . $term_id ) ) {
				$items = $cache;
			} else {
				$items = wp_get_nav_menu_items( $term_id, $args );
				set_transient( self::ITEMSPREFIX . $term_id, $items );
			}
			return $items;
		}

		/**
		 * @method menu
		 * @staticvar array $menu_id_slugs
		 *
		 * @param     {Array} $args
		 *
		 * @return boolean
		 */
		public static function menu( $args = array() ) {
			static $menu_id_slugs = array();

			$defaults = array(
				'menu'            => '',
				'container'       => 'div',
				'container_class' => '',
				'container_id'    => '',
				'menu_class'      => 'menu',
				'menu_id'         => '',
				'echo'            => true,
				'fallback_cb'     => 'wp_page_menu',
				'before'          => '',
				'after'           => '',
				'link_before'     => '',
				'link_after'      => '',
				'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
				'depth'           => 0,
				'walker'          => '',
				'theme_location'  => ''
			);

			$args = wp_parse_args( $args, $defaults );
			$args = apply_filters( 'wp_nav_menu_args', $args );
			$args = (object) $args;

			// Get the nav menu based on the requested menu
			// move get menu part to self::get_nav_menu_object function
			// to manage cache
			$menu = self::get_nav_menu_object( $args );

			// If the menu exists, get its items.
			if ( $menu && !is_wp_error( $menu ) && !isset( $menu_items ) ) //replace wp_get_nav_menu_items with self::get_nav_menu_items to manage cache
			{
				$menu_items = self::get_nav_menu_items( $menu->term_id, array( 'update_post_term_cache' => false ) );
			}

			/*
			   * If no menu was found:
			   *  - Fall back (if one was specified), or bail.
			   *
			   * If no menu items were found:
			   *  - Fall back, but only if no theme location was specified.
			   *  - Otherwise, bail.
			   */
			if ( ( !$menu || is_wp_error( $menu ) || ( isset( $menu_items ) && empty( $menu_items ) && !$args->theme_location ) ) && $args->fallback_cb && is_callable( $args->fallback_cb ) ) return call_user_func( $args->fallback_cb, (array) $args );

			if ( !$menu || is_wp_error( $menu ) ) return false;

			$nav_menu = $items = '';

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

			// Set up the $menu_item variables
			_wp_menu_item_classes_by_context( $menu_items );

			$sorted_menu_items = $menu_items_with_children = array();
			foreach ( (array) $menu_items as $key => $menu_item ) {
				$sorted_menu_items[ $menu_item->menu_order ] = $menu_item;
				if ( $menu_item->menu_item_parent )
					$menu_items_with_children[ $menu_item->menu_item_parent ] = true;
			}

			// Add the menu-item-has-children class where applicable
			if ( !empty( $menu_items_with_children ) ) {
				foreach ( $sorted_menu_items as &$menu_item ) {
					if ( isset( $menu_items_with_children[ $menu_item->ID ] ) )
						$menu_item->classes[] = 'menu-item-has-children';
				}
			}

			unset( $menu_items );

			$sorted_menu_items = apply_filters( 'wp_nav_menu_objects', $sorted_menu_items, $args );

			$items .= walk_nav_menu_tree( $sorted_menu_items, $args->depth, $args );
			unset( $sorted_menu_items );

			// Attributes
			if ( !empty( $args->menu_id ) ) {
				$wrap_id = $args->menu_id;
			} else {
				$wrap_id = 'menu-' . $menu->slug;
				while ( in_array( $wrap_id, $menu_id_slugs ) ) {
					if ( preg_match( '#-(\d+)$#', $wrap_id, $matches ) ) $wrap_id = preg_replace( '#-(\d+)$#', '-' . ++$matches[ 1 ], $wrap_id ); else
						$wrap_id = $wrap_id . '-1';
				}
			}
			$menu_id_slugs[ ] = $wrap_id;

			$wrap_class = $args->menu_class ? $args->menu_class : '';

			// Allow plugins to hook into the menu to add their own <li>'s
			$items = apply_filters( 'wp_nav_menu_items', $items, $args );
			$items = apply_filters( "wp_nav_menu_{$menu->slug}_items", $items, $args );

			// Don't print any markup if there are no items at this point.
			if ( empty( $items ) ) return false;

			$nav_menu .= sprintf( $args->items_wrap, esc_attr( $wrap_id ), esc_attr( $wrap_class ), $items );
			unset( $items );

			if ( $show_container ) $nav_menu .= '</' . $args->container . '>';

			$nav_menu = apply_filters( 'wp_nav_menu', $nav_menu, $args );

			if ( $args->echo ) echo $nav_menu; else
				return $nav_menu;
		}

		public static function handle_term_splitting( $old_term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {
			$transient = get_transient( self::ITEMSPREFIX . $old_term_id );

			if( false !== $transient) {
				delete_transient( self::ITEMSPREFIX . $old_term_id );
				set_transient( self::ITEMSPREFIX . $new_term_id , $transient );
			}
		}

	}

	Voce_Cached_Nav::init();

	if ( !function_exists( 'wp_cached_nav_menu' ) ) {
		function wp_cached_nav_menu( $args ) {
			voce_cached_nav_menu( $args );
		}
	}

	function voce_cached_nav_menu( $args ) {
		return Voce_Cached_Nav::menu( $args );

	}

}
