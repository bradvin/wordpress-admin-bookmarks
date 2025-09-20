<?php
/**
 * Dashboard widget feature for Admin Bookmarks.
 *
 * @package Admin Bookmarks
 */

class Admin_Bookmarks_Dashboard_Widget {

	/**
	 * @var AdminBookmarks
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @param AdminBookmarks $core Core plugin instance.
	 */
	public function __construct( AdminBookmarks $core ) {
		$this->core = $core;
		add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
	}

	/**
	 * Register the dashboard widget.
	 */
	public function register_widget() {
		wp_add_dashboard_widget( 'dashboard_my_bookmarks', __( 'My Bookmarks', 'admin-bookmarks' ), array( $this, 'render_widget' ) );
	}

	/**
	 * Render the dashboard widget contents.
	 */
	public function render_widget() {
		$bookmarks = admin_bookmarks_get_bookmarks_as_posts();

		if ( count( $bookmarks ) > 0 ) {
			ksort( $bookmarks );

			$current_post_type = false;

			echo '<table width="100%">';

			foreach ( $bookmarks as $bookmark ) {
				$post      = $bookmark['post'];
				$post_type = $bookmark['post_type'];
				$edit_link = admin_url( $this->core->build_edit_url( $post ) );
				if ( $current_post_type !== $post_type ) {
					if ( false !== $current_post_type ) {
						echo '<tr><td><br /></td></tr>';
					}
					$current_post_type = $post_type;
					echo '<tr><td colspan="3"><h4>' . esc_html( $current_post_type->label ) . '</h4></td></tr>';
				}
				echo '<tr><td>' . $this->core->build_menu_item_content( $post->ID, $post->post_title ) . '</span></td>';
				echo '<td><a href="' . esc_url( $edit_link ) . '">' . esc_html__( 'Edit', 'admin-bookmarks' ) . '</a></td>';
				echo '<td><a href="' . esc_url( get_permalink( $post->ID ) ) . '">' . esc_html__( 'View', 'admin-bookmarks' ) . '</a></td></tr>';
			}

			echo '</table>';

			return;
		}

		echo '<p>' . esc_html__( 'You have no saved bookmarks', 'admin-bookmarks' ) . '</p>';
	}
}

