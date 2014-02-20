<?php
/**
 * Admin Bookmarks
 *
 * Add bookmarks to your favorite posts, pages or custom post types within the WordPress admin
 *
 * @package   Admin Bookmarks
 * @author    Brad Vincent <bradvin@gmail.com>
 * @license   GPL-2.0+
 * @link      http://fooplugins.com/plugins/admin-bookmarks
 * @copyright 2014 Brad Vincent
 *
 * @wordpress-plugin
 * Plugin Name:       Admin Bookmarks
 * Plugin URI:        http://fooplugins.com/plugins/admin-bookmarks
 * Description:       Allows users to bookmark their favorite posts and pages within the WordPress admin area
 * Version:           1.0.0
 * Author:            Brad Vincent
 * Author URI:        http://fooplugins.com
 * Text Domain:       admin-bookmarks
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/fooplugins/admin-bookmarks
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

//we only need to do things in the admin
if (is_admin()) {

	define( 'ADMIN_BOOKMARKS_SLUG', 'admin-bookmarks' );
	define( 'ADMIN_BOOKMARKS_FILE', __FILE__ );
	define( 'ADMIN_BOOKMARKS_VERSION', '1.0.0' );

	require_once( 'includes/class-admin-bookmarks.php' );
	require_once( 'includes/functions.php' );
	add_action( 'plugins_loaded', array( 'AdminBookmarks', 'get_instance' ) );

}