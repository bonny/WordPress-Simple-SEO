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

const TITLE_KEY                = '_simple_seo_title';
const TITLE_DISABLED_KEY       = '_simple_seo_title_disabled';
const DESCRIPTION_KEY          = '_simple_seo_description';
const DESCRIPTION_DISABLED_KEY = '_simple_seo_description_disabled';
const NOINDEX_KEY              = '_simple_seo_noindex';

// Menu label, pages only, Classic Editor only. Same keys as before 1.0.
const USE_MENU_LABEL_KEY = '_simple_seo_use_custom_menu_label';
const MENU_LABEL_KEY     = '_simple_seo_custom_menu_label_value';

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
		TITLE_KEY                => [ 'string', __( 'SEO title. Replaces the post title in the <title> tag. Empty uses the post title.', 'simple-seo' ) ],
		DESCRIPTION_KEY          => [ 'string', __( 'Meta description. Empty outputs none, and search engines pick their own snippet.', 'simple-seo' ) ],
		NOINDEX_KEY              => [ 'boolean', __( 'Hide from search engines (noindex).', 'simple-seo' ) ],
		// "Disabled" rather than "enabled", so a missing flag means on and setting just the text works.
		TITLE_DISABLED_KEY       => [ 'boolean', __( 'Keep the SEO title but don\'t use it.', 'simple-seo' ) ],
		DESCRIPTION_DISABLED_KEY => [ 'boolean', __( 'Keep the meta description but don\'t use it.', 'simple-seo' ) ],
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
 * The SEO title field of a post: whether it's on, and its text (also when off).
 *
 * Before 1.0 the title was a "use" flag plus a value. Posts not saved since read from that.
 *
 * @param int $post_id Post ID.
 * @return array{bool, string} On, text.
 */
function title_field( int $post_id ): array {
	if ( metadata_exists( 'post', $post_id, TITLE_KEY ) || metadata_exists( 'post', $post_id, TITLE_DISABLED_KEY ) ) {
		return field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY );
	}

	return [
		(bool) get_post_meta( $post_id, LEGACY_USE_TITLE_KEY, true ),
		trim( (string) get_post_meta( $post_id, LEGACY_TITLE_KEY, true ) ),
	];
}

/**
 * The meta description field of a post: whether it's on, and its text (also when off).
 *
 * @param int $post_id Post ID.
 * @return array{bool, string} On, text.
 */
function description_field( int $post_id ): array {
	return field( $post_id, DESCRIPTION_KEY, DESCRIPTION_DISABLED_KEY );
}

/**
 * A text field and its "disabled" flag. On when there is text or the box was ticked, and not disabled.
 *
 * @param int    $post_id      Post ID.
 * @param string $key          Text meta key.
 * @param string $disabled_key Disabled flag meta key.
 * @return array{bool, string} On, text.
 */
function field( int $post_id, string $key, string $disabled_key ): array {
	$text = trim( (string) get_post_meta( $post_id, $key, true ) );

	if ( metadata_exists( 'post', $post_id, $disabled_key ) ) {
		return [ ! rest_sanitize_boolean( get_post_meta( $post_id, $disabled_key, true ) ), $text ];
	}

	return [ '' !== $text, $text ];
}

/**
 * The SEO title to use, or '' to use the normal title.
 *
 * @param int $post_id Post ID.
 */
function get_title( int $post_id ): string {
	[ $on, $title ] = title_field( $post_id );

	return $on ? $title : '';
}

/**
 * The meta description to use, or '' for none.
 *
 * @param int $post_id Post ID.
 */
function get_description( int $post_id ): string {
	[ $on, $description ] = description_field( $post_id );

	return $on ? $description : '';
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
 * Save a text field and its checkbox. Unticking keeps the text.
 *
 * @param int    $post_id      Post ID.
 * @param string $key          Text meta key.
 * @param string $disabled_key Disabled flag meta key.
 * @param bool   $on           Whether the box is ticked.
 * @param string $text         The text.
 */
function save_field( int $post_id, string $key, string $disabled_key, bool $on, string $text ): void {
	update_post_meta( $post_id, $key, sanitize_text_field( $text ) );
	update_post_meta( $post_id, $disabled_key, ! $on );
}

/**
 * Once the SEO title or its flag is written in any way (editor, REST API, WP-CLI), drop the pre-1.0 title,
 * so clearing the new title doesn't bring the old one back.
 *
 * @param int|int[] $meta_id   Meta ID(s), unused.
 * @param int       $object_id Post ID.
 * @param string    $meta_key  Meta key.
 */
function forget_legacy_title( $meta_id, $object_id, $meta_key ): void {
	if ( TITLE_KEY !== $meta_key && TITLE_DISABLED_KEY !== $meta_key ) {
		return;
	}

	delete_post_meta( $object_id, LEGACY_USE_TITLE_KEY );
	delete_post_meta( $object_id, LEGACY_TITLE_KEY );
}
