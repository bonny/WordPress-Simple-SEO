<?php
/**
 * The SEO fields, stored as post meta.
 *
 * Registered with show_in_rest, so the REST API, WP-CLI and AI tools can read and write them.
 *
 * @package SimpleSEO
 */

namespace SimpleSEO;

defined( 'ABSPATH' ) || exit;

const TITLE_KEY       = '_simple_seo_title';
const DESCRIPTION_KEY = '_simple_seo_description';
const NOINDEX_KEY     = '_simple_seo_noindex';

// The custom page title from before 1.0. A fallback until the SEO title is written.
const LEGACY_USE_TITLE_KEY = '_simple_seo_use_custom_page_title';
const LEGACY_TITLE_KEY     = '_simple_seo_custom_page_title_value';

add_action( 'init', __NAMESPACE__ . '\\register_meta' );
add_action( 'added_post_meta', __NAMESPACE__ . '\\forget_legacy_title', 10, 3 );
add_action( 'updated_post_meta', __NAMESPACE__ . '\\forget_legacy_title', 10, 3 );
add_action( 'deleted_post_meta', __NAMESPACE__ . '\\forget_legacy_title', 10, 3 );

/**
 * Register the fields for all post types.
 *
 * Meta is only in the REST API for post types that support 'custom-fields'.
 */
function register_meta(): void {
	$fields = [
		TITLE_KEY       => [ 'string', __( 'SEO title. Replaces the post title in the <title> tag. Empty uses the post title.', 'simple-seo' ) ],
		DESCRIPTION_KEY => [ 'string', __( 'Meta description. Empty outputs none, and search engines pick their own snippet.', 'simple-seo' ) ],
		NOINDEX_KEY     => [ 'boolean', __( 'Hide from search engines (noindex).', 'simple-seo' ) ],
	];

	foreach ( $fields as $key => [ $type, $description ] ) {
		register_post_meta(
			'',
			$key,
			[
				'type'              => $type,
				'description'       => $description,
				'single'            => true,
				'default'           => 'boolean' === $type ? false : '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'boolean' === $type ? 'rest_sanitize_boolean' : 'sanitize_text_field',
				'auth_callback'     => fn( $allowed, $meta_key, $post_id ) => current_user_can( 'edit_post', $post_id ),
			]
		);
	}
}

/**
 * The SEO title of a post, or '' when there is none.
 *
 * @param int $post_id Post ID.
 */
function get_title( int $post_id ): string {
	$title = trim( (string) get_post_meta( $post_id, TITLE_KEY, true ) );

	if ( '' === $title && get_post_meta( $post_id, LEGACY_USE_TITLE_KEY, true ) ) {
		$title = trim( (string) get_post_meta( $post_id, LEGACY_TITLE_KEY, true ) );
	}

	return $title;
}

/**
 * The meta description of a post, or '' when there is none.
 *
 * @param int $post_id Post ID.
 */
function get_description( int $post_id ): string {
	return trim( (string) get_post_meta( $post_id, DESCRIPTION_KEY, true ) );
}

/**
 * Whether a post is hidden from search engines.
 *
 * @param int $post_id Post ID.
 */
function is_noindex( int $post_id ): bool {
	// Values written with WP-CLI are strings, so "false" and "0" must mean false.
	return rest_sanitize_boolean( get_post_meta( $post_id, NOINDEX_KEY, true ) );
}

/**
 * Save the SEO title, '' to remove it.
 *
 * @param int    $post_id Post ID.
 * @param string $title   SEO title.
 */
function update_title( int $post_id, string $title ): void {
	$title = sanitize_text_field( $title );

	if ( '' === $title ) {
		delete_post_meta( $post_id, TITLE_KEY );
	} else {
		update_post_meta( $post_id, TITLE_KEY, $title );
	}

	// Deleting a key that isn't there fires no hook, so drop the old title here too.
	forget_legacy_title( 0, $post_id, TITLE_KEY );
}

/**
 * Once the SEO title is written in any way (editor, REST API, WP-CLI), drop the pre-1.0 title,
 * so clearing the new title doesn't bring the old one back.
 *
 * @param int|int[] $meta_id   Meta ID(s), unused.
 * @param int       $object_id Post ID.
 * @param string    $meta_key  Meta key.
 */
function forget_legacy_title( $meta_id, $object_id, $meta_key ): void {
	if ( TITLE_KEY !== $meta_key ) {
		return;
	}

	delete_post_meta( $object_id, LEGACY_USE_TITLE_KEY );
	delete_post_meta( $object_id, LEGACY_TITLE_KEY );
}
