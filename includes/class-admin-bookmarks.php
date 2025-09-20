<?php
/**
 * Admin Bookmarks
 *
 * @package   Admin Bookmarks
 * @author    Brad Vincent <bradvin@gmail.com>
 * @license   GPL-2.0+
 * @link      http://fooplugins.com/plugins/admin-bookmarks
 * @copyright 2025 Brad Vincent
 */

/**
 * Admin Bookmarks main class.
 */
class AdminBookmarks {

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var AdminBookmarks|null
     */
    protected static $instance = null;

    /**
     * Cached bookmark menu data used for client-side rendering.
     *
     * @var array
     */
    private $menu_data = array();

    /**
     * Grouped bookmarks keyed by post type.
     *
     * @var array
     */
    private $bookmark_groups = array();

    /**
     * Feature components managed by the core class.
     *
     * @var array
     */
    private $components = array();

    /**
     * Initialize the plugin by setting localization and loading admin assets.
     *
     * @since 1.0.0
     */
    private function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'admin_menu', array( $this, 'alter_admin_menu' ) );
        add_action( 'init', array( $this, 'create_columns_for_all_post_types' ), 999 );
        add_action( 'wp_ajax_toggle_admin_bookmark', array( $this, 'ajax_toggle_bookmark_callback' ) );

        $this->components[] = new Admin_Bookmarks_Admin_Bar( $this );
        $this->components[] = new Admin_Bookmarks_Dashboard_Widget( $this );
        $this->components[] = new Admin_Bookmarks_Quick_Edit( $this );
        $this->components[] = new Admin_Bookmarks_View( $this );
    }

    /**
     * Return an instance of this class.
     *
     * @since 1.0.0
     *
     * @return AdminBookmarks
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Enqueue styles and scripts used by the plugin for the current admin screen.
     *
     * @return void
     */
    public function enqueue_admin_assets() {
        wp_enqueue_script(
            'admin-bookmarks-menu',
            $this->asset_url( 'js/admin_bookmarks_menu.js' ),
            array(),
            ADMIN_BOOKMARKS_VERSION,
            true
        );

        wp_localize_script(
            'admin-bookmarks-menu',
            'adminBookmarksMenuData',
            array(
                'label' => esc_html__( 'Bookmarks', 'admin-bookmarks' ),
                'menus' => array_values( $this->menu_data ),
            )
        );

        $this->enqueue_global_styles();

        if ( $this->is_screen_base_edit() ) {
            $this->enqueue_post_listing_assets();
        }

        if ( $this->is_screen_base_post() ) {
            $this->enqueue_post_edit_assets();
        }
    }

    /**
     * Register the global stylesheet used by the plugin inside the admin area.
     *
     * @return void
     */
    private function enqueue_global_styles() {
        wp_enqueue_style(
            'admin-bookmarks',
            $this->asset_url( 'css/admin_bookmarks_post_listing.css' ),
            array( 'dashicons' ),
            ADMIN_BOOKMARKS_VERSION
        );
    }

    /**
     * Enqueue scripts required on post listing screens.
     *
     * @return void
     */
    private function enqueue_post_listing_assets() {
        wp_enqueue_script(
            'admin-bookmarks-post-listing',
            $this->asset_url( 'js/admin_bookmarks_post_listing.js' ),
            array( 'admin-bookmarks-menu' ),
            ADMIN_BOOKMARKS_VERSION,
            true
        );

        $untitled_label = apply_filters( 'admin_bookmarks_untitled_label', __( 'ID : %s', 'admin-bookmarks' ) );
        $screen         = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $screen_type    = ( $screen && $screen->post_type ) ? $screen->post_type : 'post';
        $menu_handle    = $this->get_menu_handle_for_post_type( $screen_type );

        wp_localize_script(
            'admin-bookmarks-post-listing',
            'admin_bookmarks_data',
            array(
                'nonce'         => wp_create_nonce( ADMIN_BOOKMARKS_SLUG ),
                'untitledLabel' => $untitled_label,
                'handle'        => $menu_handle,
            )
        );
    }

    /**
     * Enqueue scripts required on the post edit screen.
     *
     * @return void
     */
    private function enqueue_post_edit_assets() {
        wp_enqueue_script(
            'admin-bookmarks-post-edit',
            $this->asset_url( 'js/admin_bookmarks_post_edit.js' ),
            array(),
            ADMIN_BOOKMARKS_VERSION,
            false
        );
    }

    /**
     * Build the absolute URL to an asset relative to the plugin directory.
     *
     * @param string $relative_path Relative path to the asset within the plugin.
     *
     * @return string
     */
    private function asset_url( $relative_path ) {
        return plugins_url( $relative_path, ADMIN_BOOKMARKS_FILE );
    }

    /**
     * Provide the edit screen URL for a given post type.
     *
     * @param string $post_type Post type slug.
     *
     * @return string
     */
    public function edit_list_url( $post_type ) {
        return admin_url( $this->edit_list_path( $post_type ) );
    }

    /**
     * Provide the relative edit screen path for a post type.
     *
     * @param string $post_type Post type slug.
     *
     * @return string
     */
    public function edit_list_path( $post_type ) {
        if ( 'post' === $post_type ) {
            return 'edit.php';
        }

        return 'edit.php?post_type=' . sanitize_key( $post_type );
    }

    /**
     * Determine the admin menu handle associated with a post type list table.
     *
     * @param string $post_type Post type slug.
     *
     * @return string
     */
    public function get_menu_handle_for_post_type( $post_type ) {
        return ( 'post' === $post_type ) ? 'edit.php' : 'edit.php?post_type=' . sanitize_key( $post_type );
    }

    /**
     * Return a human-readable label for the given post type.
     *
     * @param string $post_type Post type slug.
     *
     * @return string
     */
    private function get_post_type_label( $post_type ) {
        $object = get_post_type_object( $post_type );

        if ( $object ) {
            if ( ! empty( $object->labels->menu_name ) ) {
                return $object->labels->menu_name;
            }

            if ( ! empty( $object->label ) ) {
                return $object->label;
            }
        }

        $post_type = sanitize_key( $post_type );

        return ucwords( str_replace( array( '-', '_' ), ' ', $post_type ) );
    }

    /**
     * Resolve the bookmark title for a post, falling back to the ID label.
     *
     * @param int    $post_id    Post ID.
     * @param string $post_title Original post title.
     *
     * @return string
     */
    public function resolve_bookmark_title( $post_id, $post_title = '' ) {
        $post_id      = absint( $post_id );
        $custom_title = get_post_meta( $post_id, '_bookmark_title', true );
        $title        = '' !== trim( (string) $custom_title ) ? $custom_title : trim( (string) $post_title );

        if ( '' === $title ) {
            $title = sprintf( __( 'ID : %d', 'admin-bookmarks' ), $post_id );
        }

        return $title;
    }

    /**
     * Return the user-facing bookmark title for a post object.
     *
     * @param WP_Post $post Post object.
     *
     * @return string
     */
    public function get_bookmark_title_text( $post ) {
        if ( ! $post instanceof WP_Post ) {
            return '';
        }

        return $this->resolve_bookmark_title( $post->ID, $post->post_title );
    }

    /**
     * Compute bookmark groups keyed by post type.
     *
     * @return array
     */
    private function compute_bookmark_groups() {
        $bookmarks = admin_bookmarks_get_bookmarks();

        if ( empty( $bookmarks ) || ! is_array( $bookmarks ) ) {
            return array();
        }

        $post_types = admin_bookmarks_get_supported_post_types( 'names' );

        if ( empty( $post_types ) || ! is_array( $post_types ) ) {
            return array();
        }

        $bookmark_ids = wp_parse_id_list( array_keys( $bookmarks ) );
        if ( empty( $bookmark_ids ) ) {
            return array();
        }

        $posts = get_posts(
            array(
                'post_type'           => $post_types,
                'post_status'         => 'any',
                'numberposts'         => -1,
                'post__in'            => $bookmark_ids,
                'orderby'             => 'post__in',
                'ignore_sticky_posts' => true,
            )
        );

        if ( empty( $posts ) ) {
            return array();
        }

        $groups = array();

        foreach ( $posts as $post ) {
            if ( ! $post instanceof WP_Post ) {
                continue;
            }

            if ( ! isset( $groups[ $post->post_type ] ) ) {
                $groups[ $post->post_type ] = array(
                    'post_type' => $post->post_type,
                    'handle'    => $this->get_menu_handle_for_post_type( $post->post_type ),
                    'href'      => add_query_arg( 'admin_bookmarks', 1, $this->edit_list_path( $post->post_type ) ),
                    'label'     => $this->get_post_type_label( $post->post_type ),
                    'posts'     => array(),
                );
            }

            $groups[ $post->post_type ]['posts'][] = $post;
        }

        return $groups;
    }

    /**
     * Ensure bookmark groups are computed and cached.
     *
     * @return array
     */
    private function ensure_bookmark_groups() {
        if ( empty( $this->bookmark_groups ) ) {
            $this->bookmark_groups = $this->compute_bookmark_groups();
        }

        return $this->bookmark_groups;
    }

    /**
     * Retrieve grouped bookmarks keyed by post type.
     *
     * @return array
     */
    public function get_bookmark_groups() {
        return $this->ensure_bookmark_groups();
    }

    /**
     * Retrieve bookmarked post IDs for the current user filtered by type.
     *
     * @param string $post_type Post type slug.
     *
     * @return array
     */
    public function get_bookmarked_post_ids( $post_type ) {
        $bookmarks = admin_bookmarks_get_bookmarks();

        if ( empty( $bookmarks ) || ! is_array( $bookmarks ) ) {
            return array();
        }

        $post_ids = array_keys( $bookmarks );
        if ( empty( $post_ids ) ) {
            return array();
        }

        $posts = get_posts(
            array(
                'post_type'       => $post_type,
                'post_status'     => 'any',
                'numberposts'     => -1,
                'fields'          => 'ids',
                'post__in'        => $post_ids,
                'orderby'         => 'post__in',
                'no_found_rows'   => true,
                'suppress_filters'=> true,
            )
        );

        return is_array( $posts ) ? array_map( 'absint', $posts ) : array();
    }

    /**
     * Register the custom bookmark column for all post types.
     *
     * @return void
     */
    public function create_columns_for_all_post_types() {
        $post_types = admin_bookmarks_get_supported_post_types( 'names' );
        if ( empty( $post_types ) || ! is_array( $post_types ) ) {
            return;
        }

        foreach ( $post_types as $post_type ) {
            add_filter( "manage_edit-{$post_type}_columns", array( $this, 'add_bookmark_column' ) );
            add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'add_bookmark_column_value' ), 10, 2 );
        }
    }

    /**
     * Inject bookmark links for bookmarked posts into the admin menu.
     *
     * @return void
     */
    public function alter_admin_menu() {
        $this->menu_data = array();
        $this->bookmark_groups = $this->compute_bookmark_groups();

        if ( empty( $this->bookmark_groups ) ) {
            return;
        }

        global $submenu;

        foreach ( $this->bookmark_groups as $post_type => $data ) {
            if ( empty( $data['posts'] ) ) {
                continue;
            }

            $handle = $data['handle'];

            if ( ! isset( $submenu[ $handle ] ) ) {
                continue;
            }

            $post_type_object = get_post_type_object( $post_type );
            $capability       = ( $post_type_object && ! empty( $post_type_object->cap->edit_posts ) ) ? $post_type_object->cap->edit_posts : 'edit_posts';

            if ( ! current_user_can( $capability ) ) {
                continue;
            }

            $submenu[ $handle ][] = array(
                esc_html__( 'Bookmarks', 'admin-bookmarks' ),
                $capability,
                esc_url_raw( $data['href'] ),
                esc_html__( 'Bookmarks', 'admin-bookmarks' ),
            );

            $items = array();

            foreach ( $data['posts'] as $post ) {
                if ( ! $post instanceof WP_Post ) {
                    continue;
                }

                if ( ! current_user_can( 'edit_post', $post->ID ) ) {
                    continue;
                }

                $items[] = array(
                    'post_id' => (int) $post->ID,
                    'url'     => esc_url_raw( admin_url( $this->build_edit_url( $post ) ) ),
                    'label'   => $this->build_menu_item_content( $post->ID, $post->post_title ),
                    'post_type' => $post->post_type,
                );
            }

            if ( empty( $items ) ) {
                continue;
            }

            $this->menu_data[] = array(
                'handle'    => $data['handle'],
                'href'      => esc_url_raw( $data['href'] ),
                'post_type' => $post_type,
                'items'     => $items,
            );
        }
    }

    /**
     * Build the edit URL for a given post.
     *
     * @param WP_Post $post Post object to edit.
     *
     * @return string
     */
    public function build_edit_url( $post ) {
        $post_id = $post instanceof WP_Post ? absint( $post->ID ) : 0;

        return 'post.php?post=' . $post_id . '&action=edit';
    }

    /**
     * Generate the menu item markup for a bookmarked post.
     *
     * @param int    $post_id    Post ID.
     * @param string $post_title Post title.
     *
     * @return string
     */
    public function build_menu_item_content( $post_id, $post_title ) {
        $post_id = absint( $post_id );
        $title   = $this->resolve_bookmark_title( $post_id, $post_title );

        return sprintf(
            '<span id="%1$s" data-admin-bookmark="%2$s" class="admin-bookmarks-icon bookmarked admin-bookmarks-menu-item"></span>%3$s',
            esc_attr( 'admin-bookmark-' . $post_id ),
            esc_attr( (string) $post_id ),
            esc_html( $title )
        );
    }

    /**
     * AJAX callback handler for toggling a bookmark.
     *
     * Sends a JSON response containing bookmark status and menu markup.
     *
     * @return void
     */
    public function ajax_toggle_bookmark_callback() {
        $this->bookmark_groups = array();
        $this->menu_data       = array();

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $nonce   = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, ADMIN_BOOKMARKS_SLUG ) ) {
            wp_die( __( 'Invalid Admin Bookmark request!', 'admin-bookmarks' ) );
        }

        $post   = get_post( $post_id );
        $handle = $post instanceof WP_Post ? $this->get_menu_handle_for_post_type( $post->post_type ) : '';
        $href   = $post instanceof WP_Post ? esc_url_raw( add_query_arg( 'admin_bookmarks', 1, $this->edit_list_path( $post->post_type ) ) ) : '';

        $bookmarked = admin_bookmarks_toggle_bookmark( $post_id );

        if ( true === $bookmarked && $post instanceof WP_Post ) {
            wp_send_json(
                array(
                    'post_id' => $post_id,
                    'removed' => false,
                    'item'    => array(
                        'post_id' => (int) $post_id,
                        'url'     => esc_url_raw( admin_url( $this->build_edit_url( $post ) ) ),
                        'label'   => $this->build_menu_item_content( $post_id, $post->post_title ),
                        'handle'  => $handle,
                        'href'    => $href,
                        'post_type' => $post->post_type,
                    ),
                )
            );
        }

        wp_send_json( array(
            'post_id' => $post_id,
            'removed' => true,
            'handle'  => $handle,
            'post_type' => $post instanceof WP_Post ? $post->post_type : '',
        ) );
    }

    /**
     * Determine whether the current screen base is a post list.
     *
     * @return bool
     */
    public function is_screen_base_edit() {
        return 'edit' === $this->get_current_screen_base();
    }

    /**
     * Determine whether the current screen base is a post editor.
     *
     * @return bool
     */
    public function is_screen_base_post() {
        return 'post' === $this->get_current_screen_base();
    }

    /**
     * Retrieve the base value for the current screen.
     *
     * @return string
     */
    private function get_current_screen_base() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

        return ( null !== $screen && isset( $screen->base ) ) ? $screen->base : '';
    }

    /**
     * Add the bookmark column to the provided columns array.
     *
     * @param array $cols Existing column headers.
     *
     * @return array
     */
    public function add_bookmark_column( $cols ) {
        $newcols['bookmark'] = sprintf(
            '<div title="%1$s" class="admin-bookmarks-icon bookmarked"><span>%2$s</span></div>',
            esc_attr__( 'Bookmark', 'admin-bookmarks' ),
            esc_html__( 'Bookmark', 'admin-bookmarks' )
        );

        return array_slice( $cols, 0, 1 ) + $newcols + array_slice( $cols, 1 );
    }

    /**
     * Output the bookmark column value for a post row.
     *
     * @param string $column_name Column identifier.
     * @param int    $post_id     Post ID.
     *
     * @return void
     */
    public function add_bookmark_column_value( $column_name, $post_id ) {
        if ( 'bookmark' === $column_name ) {
            $bookmark_title = get_post_meta( $post_id, '_bookmark_title', true );
            $bookmark_attr  = esc_attr( $bookmark_title );
            $post_id_attr   = esc_attr( absint( $post_id ) );

            if ( admin_bookmarks_is_post_bookmarked( $post_id ) ) {
                printf(
                    '<a title="%1$s" href="#" data-post_id="%2$s" data-bookmark-title="%3$s" class="admin-bookmarks-icon bookmarked"></a>',
                    esc_attr__( 'Bookmark!', 'admin-bookmarks' ),
                    $post_id_attr,
                    $bookmark_attr
                );
            } else {
                printf(
                    '<a title="%1$s" href="#" data-post_id="%2$s" data-bookmark-title="%3$s" class="admin-bookmarks-icon"></a>',
                    esc_attr__( 'Remove bookmark', 'admin-bookmarks' ),
                    $post_id_attr,
                    $bookmark_attr
                );
            }

            printf(
                '<span class="admin-bookmarks-quickdata" data-bookmark-title="%1$s" hidden></span>',
                $bookmark_attr
            );
        }
    }

}

require_once __DIR__ . '/class-admin-bookmarks-admin-bar.php';
require_once __DIR__ . '/class-admin-bookmarks-dashboard-widget.php';
require_once __DIR__ . '/class-admin-bookmarks-quick-edit.php';
require_once __DIR__ . '/class-admin-bookmarks-view.php';
