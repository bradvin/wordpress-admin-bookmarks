<?php
/**
 * Created by brad.
 * Date: 2014/02/16
 */

function admin_bookmarks_get_bookmarks($user = false) {
	//if no user is passed in, get currently logged in user
	$user = (false === $user) ? wp_get_current_user() : $user;

	$bookamrks = get_user_meta( $user->ID, ADMIN_BOOKMARKS_SLUG, true );

	return is_array($bookamrks) ? $bookamrks : array();
}

function admin_bookmarks_get_bookmarks_as_posts($user = false) {
	$bookmarks = admin_bookmarks_get_bookmarks($user);

	if (count($bookmarks) == 0) {
		return $bookmarks;
	}

	$post_types = get_post_types( array() , 'objects' );

	//create a lookup array
	$post_types_lookup = array();
	foreach ( $post_types as $post_type ) {
		$post_types_lookup[$post_type->name] = $post_type;
	}

	$args = array(
		'post_type' => array_keys( $post_types_lookup ),
		'orderby' => 'post_type',
		'post_status' => 'any',
		'numberposts' => -1,
		'post__in' => wp_parse_id_list( array_keys($bookmarks) ),
		'ignore_sticky_posts' => true
	);

	//get me all bookmarked posts
	$posts = get_posts($args);

	if (count($posts) > 0) {

		$arr = array();

		foreach( $posts as $post ) {
			$post_type = $post_types_lookup[$post->post_type];
			$sort_key = $post_type->name . '|' . $post->post_title . '|' . $post->ID;
			$arr[$sort_key] = array(
				'post' => $post,
				'post_type' => $post_type
			);
		}

		return $arr;

	}

	//no results - return empty array
	return array();
}

function admin_bookmarks_is_post_bookmarked($post_id = 0, $user = false) {
	$bookmarks = admin_bookmarks_get_bookmarks( $user );
	return (is_array( $bookmarks )) ? array_key_exists( $post_id, $bookmarks ) : false;
}

function admin_bookmarks_add_bookmark($post_id = 0, $user = false) {

	//if no post is passed in, get current post in loop
	$post_id = (0 === $post_id) ? get_the_ID() : $post_id;

	//if no user is passed in, get currently logged in user
	$user = (false === $user) ? wp_get_current_user() : $user;

	$bookmarks = admin_bookmarks_get_bookmarks( $user );

	if ( !array_key_exists( $post_id, $bookmarks ) ) {
		$bookmarks[$post_id] = $post_id;
	}

	update_user_meta( $user->ID, ADMIN_BOOKMARKS_SLUG, $bookmarks );
}

function admin_bookmarks_remove_bookmark($post_id = 0, $user = false) {

	//if no post is passed in, get current post in loop
	$post_id = (0 === $post_id) ? get_the_ID() : $post_id;

	//if no user is passed in, get currently logged in user
	$user = (false === $user) ? wp_get_current_user() : $user;

	$bookmarks = admin_bookmarks_get_bookmarks( $user );

	if ( array_key_exists( $post_id, $bookmarks ) ) {
		unset( $bookmarks[$post_id] );
		update_user_meta( $user->ID, ADMIN_BOOKMARKS_SLUG, $bookmarks );
	}
}

function admin_bookmarks_toggle_bookmark($post_id = 0, $user = false) {
	if ( admin_bookmarks_is_post_bookmarked( $post_id, $user) ) {
		admin_bookmarks_remove_bookmark( $post_id, $user );
		return false;
	} else {
		admin_bookmarks_add_bookmark( $post_id, $user );
		return true;
	}
}