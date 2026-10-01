<?php
/**
 * Settings → General: a "Simple SEO" section with the default share image.
 *
 * Uses the Settings API on core's General screen, so there is no settings page of our own.
 * The image's URL and size are stored with it, so link previews need no queries.
 *
 * @package SimpleSEO
 */

namespace SimpleSEO;

defined( 'ABSPATH' ) || exit;

const SHARE_IMAGE_OPTION = 'simple_seo_share_image';

add_action( 'admin_init', __NAMESPACE__ . '\\register_settings' );
add_action( 'admin_init', __NAMESPACE__ . '\\add_share_image_option' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_settings_script' );
add_action( 'updated_post_meta', __NAMESPACE__ . '\\refresh_share_image', 10, 3 );
add_action( 'added_post_meta', __NAMESPACE__ . '\\refresh_share_image', 10, 3 );
add_action( 'delete_attachment', __NAMESPACE__ . '\\forget_share_image' );

/**
 * Make sure the option exists, autoloaded, even with no image. A missing option costs a
 * query on every front-end request (get_option() only remembers it's missing for the
 * request, without a persistent object cache); an autoloaded one costs none.
 * In wp-admin, so sites updating from an older version get it too.
 */
function add_share_image_option(): void {
	if ( false === get_option( SHARE_IMAGE_OPTION ) ) {
		add_option( SHARE_IMAGE_OPTION, [], '', true );
	}
}

/**
 * Add the section and field to Settings → General.
 */
function register_settings(): void {
	register_setting(
		'general',
		SHARE_IMAGE_OPTION,
		[
			'type'              => 'object',
			'description'       => __( 'Default image for link previews, used when a post has no featured image.', 'simple-seo' ),
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_share_image',
			'show_in_rest'      => false,
			'default'           => [],
		]
	);

	add_settings_section( 'simple-seo', __( 'Simple SEO', 'simple-seo' ), __NAMESPACE__ . '\\settings_section', 'general' );
	add_settings_field( SHARE_IMAGE_OPTION, __( 'Default share image', 'simple-seo' ), __NAMESPACE__ . '\\share_image_field', 'general', 'simple-seo' );
}

/**
 * One line under the section heading, so it's clear where the setting comes from.
 */
function settings_section(): void {
	$other_plugin = active_seo_plugin();

	$text = $other_plugin
		/* translators: %s: name of another SEO plugin, like "Yoast SEO" or "The SEO Framework". */
		? sprintf( __( 'Settings from the Simple SEO plugin. %s plugin is active and handles SEO, so this isn\'t used.', 'simple-seo' ), $other_plugin )
		: __( 'Settings from the Simple SEO plugin. The SEO title, description and "Discourage search engines" are on each post and page.', 'simple-seo' );

	printf( '<p>%s</p>', esc_html( $text ) );
}

/**
 * The field: a preview, the media library buttons and a hidden attachment ID, like core's Site Icon.
 */
function share_image_field(): void {
	$image = share_image();

	image_field( SHARE_IMAGE_OPTION, (int) ( $image['id'] ?? 0 ), (string) ( $image['url'] ?? '' ) );

	printf(
		'<p class="description">%s</p>',
		esc_html__( 'Used in link previews when a post has no featured image. A wide picture, 1200 × 630.', 'simple-seo' )
	);
}

/**
 * Load the media library and our small script on Settings → General only.
 *
 * @param string $hook_suffix The current admin page.
 */
function enqueue_settings_script( string $hook_suffix ): void {
	if ( 'options-general.php' !== $hook_suffix ) {
		return;
	}

	enqueue_image_field_script();
}

/**
 * The form posts an attachment ID. Store the image's details with it, so the front end needs no queries.
 *
 * @param mixed $value Attachment ID from the form, or the stored array.
 * @return array{id?: int, url?: string, width?: int, height?: int, alt?: string}
 */
function sanitize_share_image( $value ): array {
	if ( is_array( $value ) ) {
		$value = $value['id'] ?? 0;
	}

	return share_image_data( absint( $value ) );
}

/**
 * An image attachment's URL, size and alt text, or [] if it isn't an image.
 *
 * @param int $attachment_id Attachment ID.
 * @return array{id?: int, url?: string, width?: int, height?: int, alt?: string}
 */
function share_image_data( int $attachment_id ): array {
	$src = $attachment_id && wp_attachment_is_image( $attachment_id ) ? wp_get_attachment_image_src( $attachment_id, 'full' ) : false;

	if ( ! $src ) {
		return [];
	}

	return [
		'id'     => $attachment_id,
		'url'    => $src[0],
		'width'  => (int) $src[1],
		'height' => (int) $src[2],
		'alt'    => trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ),
	];
}

/**
 * The stored default share image, or [] for none.
 *
 * @return array{id?: int, url?: string, width?: int, height?: int, alt?: string}
 */
function share_image(): array {
	$image = get_option( SHARE_IMAGE_OPTION );

	return is_array( $image ) && ! empty( $image['url'] ) ? $image : [];
}

/**
 * When the chosen image is edited (cropped, new alt text), store its new details.
 *
 * @param int|int[] $meta_id   Meta ID(s), unused.
 * @param int       $object_id Post ID.
 * @param string    $meta_key  Meta key.
 */
function refresh_share_image( $meta_id, $object_id, $meta_key ): void {
	if ( '_wp_attachment_metadata' !== $meta_key && '_wp_attachment_image_alt' !== $meta_key ) {
		return;
	}

	if ( (int) ( share_image()['id'] ?? 0 ) !== (int) $object_id ) {
		return;
	}

	update_option( SHARE_IMAGE_OPTION, share_image_data( (int) $object_id ) );
}

/**
 * When the chosen image is deleted, stop using it.
 *
 * @param int $attachment_id Attachment ID.
 */
function forget_share_image( $attachment_id ): void {
	if ( (int) ( share_image()['id'] ?? 0 ) === (int) $attachment_id ) {
		update_option( SHARE_IMAGE_OPTION, [] ); // Not delete_option(): see add_share_image_option().
	}
}
