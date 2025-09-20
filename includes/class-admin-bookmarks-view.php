<?php
/**
 * List table view integration for Admin Bookmarks.
 *
 * @package Admin Bookmarks
 */

class Admin_Bookmarks_View {

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
		add_action( 'current_screen', array( $this, 'maybe_register_bookmark_view' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_bookmarked_posts' ) );
	}

	/**
	 * Conditionally register the Bookmarks tab alongside post status views.
	 *
	 * @param WP_Screen $screen Current screen.
	 *
	 * @return void
	 */
	public function maybe_register_bookmark_view( $screen ) {
		if ( ! $screen instanceof WP_Screen || 'edit' !== $screen->base ) {
			return;
		}

		$post_type = $screen->post_type ? $screen->post_type : 'post';
		$supported = admin_bookmarks_get_supported_post_types( 'names' );

		if ( empty( $supported ) || ! in_array( $post_type, $supported, true ) ) {
			return;
		}

		add_filter( 'views_' . $screen->id, array( $this, 'add_bookmark_view' ) );
	}

	/**
	 * Inject the Bookmarks view link into the list table status links.
	 *
	 * @param array $views Existing views.
	 *
	 * @return array
	 */
	public function add_bookmark_view( $views ) {
		$screen = get_current_screen();
		if ( ! $screen instanceof WP_Screen ) {
			return $views;
		}

		$post_type      = $screen->post_type ? $screen->post_type : 'post';
		$bookmarked_ids = $this->core->get_bookmarked_post_ids( $post_type );
		$count          = count( $bookmarked_ids );
		$is_current     = isset( $_GET['admin_bookmarks'] ) && '1' === $_GET['admin_bookmarks'];

		if ( 0 === $count && ! $is_current ) {
			return $views;
		}

		if ( $is_current ) {
			foreach ( $views as $key => $link ) {
				if ( false !== strpos( $link, 'class="current"' ) ) {
					$views[ $key ] = str_replace( 'class="current"', '', $link );
				}
			}
		}

		$url = add_query_arg(
			array( 'admin_bookmarks' => 1 ),
			$this->core->edit_list_url( $post_type )
		);

		$views['admin-bookmarks'] = sprintf(
			'<a href="%1$s"%2$s>%3$s <span class="count">(%4$s)</span></a>',
			esc_url( $url ),
			$is_current ? ' class="current"' : '',
			esc_html__( 'Bookmarks', 'admin-bookmarks' ),
			number_format_i18n( $count )
		);

		return $views;
	}

	/**
	 * Filter the edit screen query to show only bookmarked posts when requested.
	 *
	 * @param WP_Query $query Query instance.
	 *
	 * @return void
	 */
	public function filter_bookmarked_posts( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		global $pagenow;

		if ( 'edit.php' !== $pagenow || empty( $_GET['admin_bookmarks'] ) ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		$post_type = $post_type ? $post_type : 'post';

		$supported = admin_bookmarks_get_supported_post_types( 'names' );
		if ( empty( $supported ) || ! in_array( $post_type, $supported, true ) ) {
			return;
		}

		$bookmarked_ids = $this->core->get_bookmarked_post_ids( $post_type );

		if ( empty( $bookmarked_ids ) ) {
			$bookmarked_ids = array( 0 );
		}

		$query->set( 'post__in', $bookmarked_ids );
		$query->set( 'orderby', 'post__in' );
		$query->set( 'ignore_sticky_posts', true );
	}
}

