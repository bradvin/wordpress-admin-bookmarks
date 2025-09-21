<?php
/**
 * Helper functions for Admin Bookmarks.
 *
 * @package Admin Bookmarks
 */

/**
 * Retrieve the list of post types supported by Admin Bookmarks.
 *
 * @since 1.0.1
 *
 * @param string $output Optional. Accepts 'names' or 'objects'. Default 'names'.
 *
 * @return array
 */
function admin_bookmarks_get_supported_post_types( $output = 'names' ) {
    $valid_output = in_array( $output, array( 'names', 'objects' ), true ) ? $output : 'names';
    $post_types   = get_post_types( array(), $valid_output );

    /**
     * Filters the post types that support Admin Bookmarks features.
     *
     * @since 1.0.1
     *
     * @param array  $post_types   List of post type names or objects.
     * @param string $valid_output Requested output format ('names' or 'objects').
     */
    return apply_filters( 'admin_bookmarks_post_types', $post_types, $valid_output );
}

/**
 * Retrieve the current user's bookmark list.
 *
 * @param WP_User|false $user Optional. User object to inspect. Defaults to current user.
 *
 * @return array
 */
function admin_bookmarks_get_bookmarks( $user = false ) {
    $user = ( false === $user ) ? wp_get_current_user() : $user;

    $bookmarks = get_user_meta( $user->ID, ADMIN_BOOKMARKS_SLUG, true );

    return is_array( $bookmarks ) ? $bookmarks : array();
}

/**
 * Retrieve the current user's bookmarks keyed by post type and title.
 *
 * @param WP_User|false $user Optional. User object to inspect. Defaults to current user.
 *
 * @return array
 */
function admin_bookmarks_get_bookmarks_as_posts( $user = false ) {
    $bookmarks = admin_bookmarks_get_bookmarks( $user );

    if ( 0 === count( $bookmarks ) ) {
        return $bookmarks;
    }

    $post_types = admin_bookmarks_get_supported_post_types( 'objects' );

    if ( empty( $post_types ) || ! is_array( $post_types ) ) {
        return array();
    }

    $post_types_lookup = array();
    foreach ( $post_types as $post_type ) {
        $post_types_lookup[ $post_type->name ] = $post_type;
    }

    $args = array(
        'post_type'           => array_keys( $post_types_lookup ),
        'orderby'             => 'post_type',
        'post_status'         => 'any',
        'numberposts'         => -1,
        'post__in'            => wp_parse_id_list( array_keys( $bookmarks ) ),
        'ignore_sticky_posts' => true,
    );

    $posts = get_posts( $args );

    if ( count( $posts ) > 0 ) {
        $arr = array();

        foreach ( $posts as $post ) {
            $post_type = $post_types_lookup[ $post->post_type ];
            $sort_key  = $post_type->name . '|' . $post->post_title . '|' . $post->ID;
            $arr[ $sort_key ] = array(
                'post'      => $post,
                'post_type' => $post_type,
            );
        }

        return $arr;
    }

    return array();
}

/**
 * Determine whether a post is bookmarked for a user.
 *
 * @param int           $post_id Post ID to check.
 * @param WP_User|false $user    Optional. User object to inspect. Defaults to current user.
 *
 * @return bool
 */
function admin_bookmarks_is_post_bookmarked( $post_id = 0, $user = false ) {
    $bookmarks = admin_bookmarks_get_bookmarks( $user );

    return is_array( $bookmarks ) ? array_key_exists( $post_id, $bookmarks ) : false;
}

/**
 * Add a bookmark for a given post.
 *
 * @param int           $post_id Post ID to add.
 * @param WP_User|false $user    Optional. User object to update. Defaults to current user.
 *
 * @return void
 */
function admin_bookmarks_add_bookmark( $post_id = 0, $user = false ) {
    $post_id = ( 0 === $post_id ) ? get_the_ID() : $post_id;
    $user    = ( false === $user ) ? wp_get_current_user() : $user;

    $bookmarks = admin_bookmarks_get_bookmarks( $user );

    if ( ! array_key_exists( $post_id, $bookmarks ) ) {
        $bookmarks[ $post_id ] = $post_id;
    }

    update_user_meta( $user->ID, ADMIN_BOOKMARKS_SLUG, $bookmarks );
}

/**
 * Remove a bookmark for a given post.
 *
 * @param int           $post_id Post ID to remove.
 * @param WP_User|false $user    Optional. User object to update. Defaults to current user.
 *
 * @return void
 */
function admin_bookmarks_remove_bookmark( $post_id = 0, $user = false ) {
    $post_id = ( 0 === $post_id ) ? get_the_ID() : $post_id;
    $user    = ( false === $user ) ? wp_get_current_user() : $user;

    $bookmarks = admin_bookmarks_get_bookmarks( $user );

    if ( array_key_exists( $post_id, $bookmarks ) ) {
        unset( $bookmarks[ $post_id ] );
        update_user_meta( $user->ID, ADMIN_BOOKMARKS_SLUG, $bookmarks );
    }
}

/**
 * Toggle a bookmark for a post, adding or removing it as needed.
 *
 * @param int           $post_id Post ID to toggle.
 * @param WP_User|false $user    Optional. User object to update. Defaults to current user.
 *
 * @return bool True when the post is bookmarked after the toggle, false otherwise.
 */
function admin_bookmarks_toggle_bookmark( $post_id = 0, $user = false ) {
    if ( admin_bookmarks_is_post_bookmarked( $post_id, $user ) ) {
        admin_bookmarks_remove_bookmark( $post_id, $user );

        return false;
    }

    admin_bookmarks_add_bookmark( $post_id, $user );

    return true;
}

/**
 * Build the relative edit list path for a post type.
 *
 * @param string $post_type Post type slug.
 *
 * @return string
 */
function admin_bookmarks_get_edit_list_path( $post_type ) {
    return ( 'post' === $post_type ) ? 'edit.php' : 'edit.php?post_type=' . sanitize_key( $post_type );
}

/**
 * Build the absolute edit list URL for a post type.
 *
 * @param string $post_type Post type slug.
 *
 * @return string
 */
function admin_bookmarks_get_edit_list_url( $post_type ) {
    return admin_url( admin_bookmarks_get_edit_list_path( $post_type ) );
}

/**
 * Determine the admin menu handle for a post type list table.
 *
 * @param string $post_type Post type slug.
 *
 * @return string
 */
function admin_bookmarks_get_menu_handle( $post_type ) {
    return ( 'post' === $post_type ) ? 'edit.php' : 'edit.php?post_type=' . sanitize_key( $post_type );
}

/**
 * Retrieve the admin-facing label for a post type.
 *
 * @param string $post_type Post type slug.
 *
 * @return string
 */
function admin_bookmarks_get_post_type_label( $post_type ) {
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
 * Build the relative edit path for a specific post.
 *
 * @param int|WP_Post $post Post object or ID.
 *
 * @return string
 */
function admin_bookmarks_get_edit_post_path( $post ) {
    $post   = get_post( $post );
    $post_id = $post ? (int) $post->ID : (int) $post;

    if ( $post_id <= 0 ) {
        $post_id = 0;
    }

    return 'post.php?post=' . $post_id . '&action=edit';
}

/**
 * Build the absolute edit URL for a specific post.
 *
 * @param int|WP_Post $post Post object or ID.
 *
 * @return string
 */
function admin_bookmarks_get_edit_post_url( $post ) {
    return admin_url( admin_bookmarks_get_edit_post_path( $post ) );
}

/**
 * Resolve the label used for a bookmark.
 *
 * @param int    $post_id    Post ID.
 * @param string $post_title Original post title.
 *
 * @return string
 */
function admin_bookmarks_resolve_title( $post_id, $post_title = '' ) {
    $post_id      = absint( $post_id );
    $custom_title = get_post_meta( $post_id, '_bookmark_title', true );
    $title        = '' !== trim( (string) $custom_title ) ? $custom_title : trim( (string) $post_title );

    if ( '' === $title ) {
        $title = sprintf( __( 'ID : %d', 'admin-bookmarks' ), $post_id );
    }

    return $title;
}

/**
 * Retrieve the bookmark title for a post object.
 *
 * @param WP_Post $post Post object.
 *
 * @return string
 */
function admin_bookmarks_get_bookmark_title_text( $post ) {
    if ( ! $post instanceof WP_Post ) {
        return '';
    }

    return admin_bookmarks_resolve_title( $post->ID, $post->post_title );
}

/**
 * Build the menu item markup for a bookmarked post.
 *
 * @param int    $post_id    Post ID.
 * @param string $post_title Post title.
 *
 * @return string
 */
function admin_bookmarks_build_menu_item_content( $post_id, $post_title = '' ) {
    $post_id = absint( $post_id );
    $title   = admin_bookmarks_resolve_title( $post_id, $post_title );

    return sprintf(
        '<span id="%1$s" data-admin-bookmark="%2$s" class="admin-bookmarks-icon bookmarked admin-bookmarks-menu-item"></span>%3$s',
        esc_attr( 'admin-bookmark-' . $post_id ),
        esc_attr( (string) $post_id ),
        esc_html( $title )
    );
}

/**
 * Retrieve bookmarked post IDs for the current user filtered by type.
 *
 * @param string $post_type Post type slug.
 *
 * @return array
 */
function admin_bookmarks_get_bookmarked_post_ids( $post_type ) {
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
 * Retrieve grouped bookmarks keyed by post type.
 *
 * @param bool $force_refresh Whether to rebuild the cache.
 *
 * @return array
 */
function admin_bookmarks_get_bookmark_groups( $force_refresh = false ) {
    static $cache = null;

    if ( $force_refresh ) {
        $cache = null;
    }

    if ( null !== $cache ) {
        return $cache;
    }

    $bookmarks = admin_bookmarks_get_bookmarks();

    if ( empty( $bookmarks ) || ! is_array( $bookmarks ) ) {
        $cache = array();
        return $cache;
    }

    $post_types = admin_bookmarks_get_supported_post_types( 'names' );

    if ( empty( $post_types ) || ! is_array( $post_types ) ) {
        $cache = array();
        return $cache;
    }

    $bookmark_ids = wp_parse_id_list( array_keys( $bookmarks ) );
    if ( empty( $bookmark_ids ) ) {
        $cache = array();
        return $cache;
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
        $cache = array();
        return $cache;
    }

    $groups = array();

    foreach ( $posts as $post ) {
        if ( ! $post instanceof WP_Post ) {
            continue;
        }

        if ( ! isset( $groups[ $post->post_type ] ) ) {
            $groups[ $post->post_type ] = array(
                'post_type' => $post->post_type,
                'handle'    => admin_bookmarks_get_menu_handle( $post->post_type ),
                'href'      => add_query_arg( 'admin_bookmarks', 1, admin_bookmarks_get_edit_list_path( $post->post_type ) ),
                'label'     => admin_bookmarks_get_post_type_label( $post->post_type ),
                'posts'     => array(),
            );
        }

        $groups[ $post->post_type ]['posts'][] = $post;
    }

    $cache = $groups;

    return $cache;
}

/**
 * Reset the cached bookmark groups.
 */
function admin_bookmarks_reset_bookmark_groups() {
    admin_bookmarks_get_bookmark_groups( true );
}
