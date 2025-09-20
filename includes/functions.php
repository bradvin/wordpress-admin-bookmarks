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
