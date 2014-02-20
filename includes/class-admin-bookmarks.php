<?php
/**
 * Admin Bookmarks
 *
 * @package   Admin Bookmarks
 * @author    Brad Vincent <bradvin@gmail.com>
 * @license   GPL-2.0+
 * @link      http://fooplugins.com/plugins/admin-bookmarks
 * @copyright 2014 Brad Vincent
 */

/**
 * Admin Bookmarks main class.
 */

require_once 'foopluginbase/bootstrapper.php';

class AdminBookmarks extends Foo_Plugin_Base_v2_0 {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		//init Foo Plugin Base framework
		$this->init( ADMIN_BOOKMARKS_FILE, ADMIN_BOOKMARKS_SLUG, ADMIN_BOOKMARKS_VERSION, __('Admin Bookmarks', 'admin-bookmarks') );

		add_filter( ADMIN_BOOKMARKS_SLUG . '-has_settings_page', '__return_false' );

		//add CSS
		add_action( ADMIN_BOOKMARKS_SLUG . '-admin_print_styles', array($this, 'add_css') );

		//add JS
		add_action( ADMIN_BOOKMARKS_SLUG . '-admin_print_scripts',  array($this, 'add_scripts') );

		//create bookmark items in the admin menu
		add_action( 'admin_menu', array($this, 'alter_admin_menu') );

		//register columns for all post types
		add_action( 'init', array($this, 'create_columns_for_all_post_types'), 999);

		//setup ajax callbacks
		add_action('wp_ajax_toggle_admin_bookmark', array($this, 'ajax_toggle_bookmark_callback') );

		//add dashboard widget
		add_action("wp_dashboard_setup", array($this, "setup_dashboard_widget") );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    FooAuth    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	function create_columns_for_all_post_types() {
		$post_types = get_post_types( array() , 'names' );
		foreach ($post_types as $post_type ) {
			add_filter( "manage_edit-{$post_type}_columns", array($this, 'add_bookmark_column') );
			add_action( "manage_{$post_type}_posts_custom_column" , array($this, 'add_bookmark_column_value'), 10, 2 );
		}
	}

	function alter_admin_menu() {
		$bookmarks = admin_bookmarks_get_bookmarks();

		if (count($bookmarks) == 0) {
			return;
		}

		$post_types = get_post_types( array() , 'names' );
		$args = array(
			'post_type' => $post_types,
			'post_status' => 'any',
			'numberposts' => -1,
			'post__in' => wp_parse_id_list( array_keys($bookmarks) ),
			'ignore_sticky_posts' => true
		);

		//get me all bookmarked posts
		$posts = get_posts($args);

		//add each post to the admin menu
		foreach( $posts as $post ) {
			$url = $this->build_edit_url($post);
			$post_type = $post->post_type;
			$handle = 'edit.php';
			if ($post_type != 'post') $handle = "edit.php?post_type=$post_type";
			add_submenu_page($handle, $post->post_title, $this->build_menu_item_content($post->ID, $post->post_title), 'edit_posts', $url);
		}
	}

	function build_edit_url($post) {
		return 'post.php?post=' . $post->ID . '&action=edit';
	}

	function build_menu_item_content($post_id, $post_title) {
		return '<span id="admin-bookmark-' . $post_id . '" class="admin-bookmarks-icon bookmarked admin-bookmarks-menu-item"></span>'.$post_title;
	}

	function ajax_toggle_bookmark_callback() {
		$post_id = $_POST['post_id'];
		$nonce = $_POST['nonce'];

		if ( ! wp_verify_nonce( $nonce, ADMIN_BOOKMARKS_SLUG ) )
			wp_die ( __('Invalid Admin Bookmark request!', 'admin-bookmarks') );

		$bookmarked = admin_bookmarks_toggle_bookmark( $post_id );

		if ( true === $bookmarked ) {

			$post = get_post($post_id);

			//return post data so that UI updates can happen in 'realtime'
			echo json_encode(array(
				'post_id' => $post_id,
				'removed' => false,
				'menu' => '<li><a href="' . $this->build_edit_url( $post ) . '">' . $this->build_menu_item_content($post_id, $post->post_title) . '</a></li>',
				'post_type' => $post->post_type
			));

		} else {
			echo json_encode(array(
				'post_id' => $post_id,
				'removed' => true
			));
		}
		die();
	}

	function is_screen_base_edit() {
		return 'edit' === foo_current_screen_base();
	}

	function is_screen_base_post() {
		return 'post' === foo_current_screen_base();
	}

	function add_css() {
		$this->register_and_enqueue_css( 'admin_bookmarks_post_listing.css', array('dashicons') );
	}

	function add_scripts() {
		if ( $this->is_screen_base_edit() ) {
			$handle = $this->register_and_enqueue_js( 'admin_bookmarks_post_listing.js', array('jquery', 'wp-ajax-response') );
			wp_localize_script( $handle, 'admin_bookmarks_data', array(
				'nonce' => wp_create_nonce( ADMIN_BOOKMARKS_SLUG )
			));
		}

		if ($this->is_screen_base_post()) {
			$this->register_and_enqueue_js( 'admin_bookmarks_post_edit.js', array('jquery') );
		}
	}

	function add_bookmark_column($cols) {
		$newcols['bookmark'] = '<div title="' . __('Bookmark', 'admin-bookmarks') . '" class="admin-bookmarks-icon bookmarked"><span>' . __('Bookmark', 'admin-bookmarks') . '</span></div>';

		//insert the bookmark column after the checkbox
		return array_slice($cols, 0, 1) + $newcols + array_slice($cols, 1);
	}

	function add_bookmark_column_value($column_name, $post_id) {
		if ('bookmark' === $column_name) {
			if (admin_bookmarks_is_post_bookmarked( $post_id )) {
				echo '<a title="' . __('Bookmark!','') . '" href="#" data-post_id="' . $post_id . '" class="admin-bookmarks-icon bookmarked"></a>';
			} else {
				echo '<a title="' . __('Remove bookmark','') . '" href="#" data-post_id="' . $post_id . '" class="admin-bookmarks-icon"></a>';
			}
		}
	}

	function setup_dashboard_widget() {
		wp_add_dashboard_widget('dashboard_my_bookmarks', __('My Bookmarks', 'admin-bookmarks'), array($this, 'render_dashboard_widget'));
	}

	function render_dashboard_widget() {
		$bookmarks = admin_bookmarks_get_bookmarks_as_posts();

		if (count($bookmarks) > 0) {

			ksort($bookmarks);

			$current_post_type = false;

			echo '<table width="100%">';

			foreach( $bookmarks as $bookmark ) {
				$post = $bookmark['post'];
				$post_type = $bookmark['post_type'];
				$url = $this->build_edit_url($post);
				if ( $current_post_type !== $post_type ) {
					if (false !== $current_post_type) {
						echo '<tr><td><br /></td></tr>';
					}
					$current_post_type = $post_type;
					echo '<tr><td colspan="3"><h4>' . $current_post_type->label . '</h4></td></tr>';
				}
				echo '<tr><td>' . $this->build_menu_item_content($post->ID, $post->post_title) . '</span></td>';
				echo '<td><a href="' . $url . '">' . __('Edit') . '</a></td>';
				echo '<td><a href="' . get_permalink( $post->ID ) . '">' . __('View') . '</a></td></tr>';
			}

			echo '</table>';

		} else {
			echo '<p>' . __('You have no saved bookmarks', 'admin-bookmarks') . '</p>';
		}
	}
}
