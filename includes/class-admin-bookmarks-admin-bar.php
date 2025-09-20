<?php
/**
 * Admin bar integration for Admin Bookmarks.
 *
 * @package Admin Bookmarks
 */

class Admin_Bookmarks_Admin_Bar {

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
		add_action( 'admin_bar_menu', array( $this, 'add_bookmarks_to_admin_bar' ), 80 );
	}

	/**
	 * Add bookmarked posts to the WordPress admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 *
	 * @return void
	 */
	public function add_bookmarks_to_admin_bar( $wp_admin_bar ) {
		if ( ! is_user_logged_in() || ! $wp_admin_bar instanceof WP_Admin_Bar ) {
			return;
		}

		$groups = $this->core->get_bookmark_groups();

		if ( empty( $groups ) ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'admin-bookmarks',
				'title' => esc_html__( 'Bookmarks', 'admin-bookmarks' ),
				'href'  => false,
				'meta'  => array( 'class' => 'admin-bookmarks-adminbar' ),
			)
		);

		foreach ( $groups as $post_type => $group ) {
			if ( empty( $group['posts'] ) ) {
				continue;
			}

			$parent_id = 'admin-bookmarks-' . sanitize_key( $post_type );

			$wp_admin_bar->add_node(
				array(
					'id'     => $parent_id,
					'parent' => 'admin-bookmarks',
					'title'  => esc_html( $group['label'] ),
					'href'   => admin_url( $group['href'] ),
				)
			);

			foreach ( $group['posts'] as $post ) {
				if ( ! $post instanceof WP_Post ) {
					continue;
				}

				if ( ! current_user_can( 'edit_post', $post->ID ) ) {
					continue;
				}

				$wp_admin_bar->add_node(
					array(
						'id'     => $parent_id . '-' . $post->ID,
						'parent' => $parent_id,
						'title'  => esc_html( $this->core->get_bookmark_title_text( $post ) ),
						'href'   => admin_url( $this->core->build_edit_url( $post ) ),
					)
				);
			}
		}
	}
}

