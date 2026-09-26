<?php
/**
 * The SEO fields, stored as post meta. A text field is used when it isn't empty.
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

// Menu label, pages only, Classic Editor only. Same keys as before 1.0: the label is used when the flag is 1.
const USE_MENU_LABEL_KEY = '_simple_seo_use_custom_menu_label';
const MENU_LABEL_KEY     = '_simple_seo_custom_menu_label_value';

// The custom page title from before 1.0. A fallback until the SEO title is written.
const LEGACY_USE_TITLE_KEY = '_simple_seo_use_custom_page_title';
const LEGACY_TITLE_KEY     = '_simple_seo_custom_page_title_value';

add_action( 'init', __NAMESPACE__ . '\\register_meta' );
add_action( 'added_post_meta', __NAMESPACE__ . '\\forget_legacy_title', 10, 3 );
add_action( 'updated_post_meta', __NAMESPACE__ . '\\forget_legacy_title', 10, 3 );
add_action( 'deleted_post_meta', __NAMESPACE__ . '\\forget_legacy_title', 10, 3 );
add_filter( 'default_post_metadata', __NAMESPACE__ . '\\legacy_title_default', 20, 4 );

/**
 * Register the fields for all post types. A text field is used when it isn't empty.
 *
 * Meta is only in the REST API for post types that support 'custom-fields'.
 */
function register_meta(): void {
	$fields = [
		TITLE_KEY       => [ 'string', __( 'SEO title. Replaces the post title in the <title> tag. Empty uses the post title.', 'simple-seo' ) ],
		DESCRIPTION_KEY => [ 'string', __( 'Meta description. Empty outputs none, and search engines pick their own snippet.', 'simple-seo' ) ],
		NOINDEX_KEY     => [ 'boolean', __( 'Discourage search engines from indexing this post (noindex).', 'simple-seo' ) ],
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
				// Edit context only: logged-in editors (block editor, WP-CLI, application passwords) see the
				// fields, anonymous requests don't.
				'show_in_rest'      => [ 'schema' => [ 'context' => [ 'edit' ] ] ],
				'sanitize_callback' => 'boolean' === $type ? 'rest_sanitize_boolean' : 'sanitize_text_field',
				'auth_callback'     => fn( $allowed, $meta_key, $post_id ) => current_user_can( 'edit_post', $post_id ),
			]
		);
	}
}

/**
 * Whether a post type gets the fields: it has pages on the front end, and isn't attachments
 * (attachment pages are off by default since WordPress 6.4).
 * Used by the Classic box, the block editor panel and the posts lists.
 *
 * @param string $post_type Post type.
 */
function has_seo_fields( string $post_type ): bool {
	return 'attachment' !== $post_type && is_post_type_viewable( $post_type );
}

/**
 * The SEO title to use, or '' to use the normal title.
 *
 * Before 1.0 the title was a "use" flag plus a value. Posts whose title hasn't been written since read from that.
 *
 * @param int $post_id Post ID.
 */
function get_title( int $post_id ): string {
	if ( metadata_exists( 'post', $post_id, TITLE_KEY ) ) {
		return trim( (string) get_post_meta( $post_id, TITLE_KEY, true ) );
	}

	return legacy_title( $post_id );
}

/**
 * The pre-1.0 title if its box was ticked, or ''.
 *
 * @param int $post_id Post ID.
 */
function legacy_title( int $post_id ): string {
	return get_post_meta( $post_id, LEGACY_USE_TITLE_KEY, true )
		? trim( (string) get_post_meta( $post_id, LEGACY_TITLE_KEY, true ) )
		: '';
}

/**
 * The meta description to use, or '' for none.
 *
 * @param int $post_id Post ID.
 */
function get_description( int $post_id ): string {
	return trim( (string) get_post_meta( $post_id, DESCRIPTION_KEY, true ) );
}

/**
 * The menu label of a page, or '' to use the page title. Stored as before 1.0: a flag and the text.
 *
 * @param int $post_id Post ID.
 */
function get_menu_label( int $post_id ): string {
	return get_post_meta( $post_id, USE_MENU_LABEL_KEY, true )
		? trim( (string) get_post_meta( $post_id, MENU_LABEL_KEY, true ) )
		: '';
}

/**
 * Whether search engines are asked not to index a post (noindex).
 *
 * @param int $post_id Post ID.
 */
function is_noindex( int $post_id ): bool {
	// Values written with WP-CLI are strings, so "false" and "0" must mean false.
	return rest_sanitize_boolean( get_post_meta( $post_id, NOINDEX_KEY, true ) );
}

/**
 * Save a text field: the text when there is some, nothing when it's empty.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @param string $text    The text.
 */
function save_text( int $post_id, string $key, string $text ): void {
	$text = sanitize_text_field( $text );

	if ( '' !== $text ) {
		update_post_meta( $post_id, $key, $text );
		return;
	}

	delete_post_meta( $post_id, $key );
	// Nothing to delete fires no hook, but a pre-1.0 title may still be there.
	forget_legacy_title( 0, $post_id, $key );
}

/**
 * Save the menu label, in its pre-1.0 keys.
 *
 * @param int    $post_id Post ID.
 * @param string $text    The label.
 */
function save_menu_label( int $post_id, string $text ): void {
	$text = sanitize_text_field( $text );

	if ( '' === $text ) {
		delete_post_meta( $post_id, USE_MENU_LABEL_KEY );
		delete_post_meta( $post_id, MENU_LABEL_KEY );
		return;
	}

	update_post_meta( $post_id, USE_MENU_LABEL_KEY, 1 );
	update_post_meta( $post_id, MENU_LABEL_KEY, $text );
}

/**
 * For posts whose title hasn't been written since before 1.0, serve the old title as the default
 * of the new key, so the REST API and the block editor panel show it.
 *
 * @param mixed  $value     Default value.
 * @param int    $object_id Post ID.
 * @param string $meta_key  Meta key.
 * @param bool   $single    Whether a single value is asked for.
 * @return mixed
 */
function legacy_title_default( $value, $object_id, $meta_key, $single ) {
	if ( TITLE_KEY !== $meta_key || ! metadata_exists( 'post', $object_id, LEGACY_TITLE_KEY ) ) {
		return $value;
	}

	$legacy = legacy_title( (int) $object_id );

	return $single ? $legacy : [ $legacy ];
}

/**
 * Once the SEO title is written in any way (editor, REST API, WP-CLI), the pre-1.0 title goes.
 *
 * @param int|int[] $meta_id   Meta ID(s), unused.
 * @param int       $object_id Post ID.
 * @param string    $meta_key  Meta key.
 */
function forget_legacy_title( $meta_id, $object_id, $meta_key ): void {
	if ( TITLE_KEY === $meta_key ) {
		delete_post_meta( $object_id, LEGACY_USE_TITLE_KEY );
		delete_post_meta( $object_id, LEGACY_TITLE_KEY );
	}
}
