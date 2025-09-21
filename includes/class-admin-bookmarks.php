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

        $this->components[] = new Admin_Bookmarks_Admin_Bar();
        $this->components[] = new Admin_Bookmarks_Dashboard_Widget();
        $this->components[] = new Admin_Bookmarks_Quick_Edit();
        $this->components[] = new Admin_Bookmarks_View();
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
        $menu_handle    = admin_bookmarks_get_menu_handle( $screen_type );

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
        $groups = admin_bookmarks_get_bookmark_groups();

        if ( empty( $groups ) ) {
            return;
        }

        global $submenu;

        foreach ( $groups as $post_type => $data ) {
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
                $data['href'],
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
                    'post_id'  => (int) $post->ID,
                    'url'      => esc_url_raw( admin_bookmarks_get_edit_post_url( $post ) ),
                    'label'    => admin_bookmarks_build_menu_item_content( $post->ID, $post->post_title ),
                    'post_type'=> $post->post_type,
                );
            }

            if ( empty( $items ) ) {
                continue;
            }

            $this->menu_data[] = array(
                'handle'    => $data['handle'],
                'href'      => $data['href'],
                'post_type' => $post_type,
                'items'     => $items,
            );
        }
    }

    /**
     * AJAX callback handler for toggling a bookmark.
     *
     * Sends a JSON response containing bookmark status and menu markup.
     *
     * @return void
     */
    public function ajax_toggle_bookmark_callback() {
        $this->menu_data = array();
        admin_bookmarks_reset_bookmark_groups();

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $nonce   = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, ADMIN_BOOKMARKS_SLUG ) ) {
            wp_die( __( 'Invalid Admin Bookmark request!', 'admin-bookmarks' ) );
        }

        $post   = get_post( $post_id );
        $handle = $post instanceof WP_Post ? admin_bookmarks_get_menu_handle( $post->post_type ) : '';
        $href   = $post instanceof WP_Post ? add_query_arg( 'admin_bookmarks', 1, admin_bookmarks_get_edit_list_path( $post->post_type ) ) : '';

        $bookmarked = admin_bookmarks_toggle_bookmark( $post_id );

        if ( true === $bookmarked && $post instanceof WP_Post ) {
            wp_send_json(
                array(
                    'post_id' => $post_id,
                    'removed' => false,
                    'item'    => array(
                        'post_id' => (int) $post_id,
                        'url'     => esc_url_raw( admin_bookmarks_get_edit_post_url( $post ) ),
                        'label'   => admin_bookmarks_build_menu_item_content( $post_id, $post->post_title ),
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
            'handle'    => $handle,
            'post_type' => $post instanceof WP_Post ? $post->post_type : '',
            'href'      => $href,
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
