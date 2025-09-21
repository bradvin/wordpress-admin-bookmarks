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
 * @copyright 2025 Brad Vincent
 *
 * @wordpress-plugin
 * Plugin Name:       Admin Bookmarks
 * Plugin URI:        http://fooplugins.com/plugins/admin-bookmarks
 * Description:       Allows users to bookmark their favorite posts and pages within the WordPress admin area
 * Version:           2.1.0
 * Author:            Brad Vincent
 * Author URI:        http://fooplugins.com
 * Text Domain:       admin-bookmarks
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/fooplugins/admin-bookmarks
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'ADMIN_BOOKMARKS_SLUG', 'admin-bookmarks' );
define( 'ADMIN_BOOKMARKS_FILE', __FILE__ );
define( 'ADMIN_BOOKMARKS_VERSION', '2.1.0' );

require_once( 'includes/functions.php' );
require_once( 'includes/class-admin-bookmarks.php' );

function admin_bookmarks_init() {
	if ( is_admin() ) {
		// Always run the main class in admin.
		new Admin_Bookmarks_Main();

		if ( apply_filters( 'admin_bookmark_feature-dashboard_widget', true ) ) {
			require_once( 'includes/class-admin-bookmarks-dashboard-widget.php' );
			new Admin_Bookmarks_Dashboard_Widget();
		}

		if ( apply_filters( 'admin_bookmark_feature-quick-edit', true ) ) {
			require_once( 'includes/class-admin-bookmarks-quick-edit.php' );
			new Admin_Bookmarks_Quick_Edit();
		}

		if ( apply_filters( 'admin_bookmark_feature-view', true ) ) {
			require_once( 'includes/class-admin-bookmarks-view.php' );
			new Admin_Bookmarks_View();
		}
	}

	// Run the admin bar class only if the user is logged in.
	if ( is_user_logged_in() && apply_filters( 'admin_bookmark_feature-admin-bar', true ) ) {
		require_once( 'includes/class-admin-bookmarks-admin-bar.php' );
		new Admin_Bookmarks_Admin_Bar();
	}
}
add_action( 'plugins_loaded', 'admin_bookmarks_init' );

