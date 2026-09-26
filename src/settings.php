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
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_settings_script' );
add_action( 'updated_post_meta', __NAMESPACE__ . '\\refresh_share_image', 10, 3 );
add_action( 'added_post_meta', __NAMESPACE__ . '\\refresh_share_image', 10, 3 );
add_action( 'delete_attachment', __NAMESPACE__ . '\\forget_share_image' );

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
		/* translators: %s: name of another SEO plugin, like Yoast SEO. */
		? sprintf( __( 'Settings from the Simple SEO plugin. %s is active and handles SEO, so this isn\'t used.', 'simple-seo' ), $other_plugin )
		: __( 'Settings from the Simple SEO plugin. The SEO title, description and "Hide from search engines" are on each post and page.', 'simple-seo' );

	printf( '<p>%s</p>', esc_html( $text ) );
}

/**
 * The field: a preview, the media library buttons and a hidden attachment ID, like core's Site Icon.
 */
function share_image_field(): void {
	$image = share_image();

	printf(
		'<div class="simple-seo-share-image"><img src="%1$s" alt="" style="max-width:300px;height:auto;display:block;margin-bottom:8px" %2$s />',
		esc_url( $image['url'] ?? '' ),
		$image ? '' : 'hidden'
	);

	printf(
		'<input type="hidden" name="%1$s" value="%2$s" />',
		esc_attr( SHARE_IMAGE_OPTION ),
		esc_attr( (string) ( $image['id'] ?? '' ) )
	);

	printf(
		'<button type="button" class="button simple-seo-share-image-choose" data-choose="%1$s" data-change="%2$s">%3$s</button> ',
		esc_attr__( 'Choose image', 'simple-seo' ),
		esc_attr__( 'Change image', 'simple-seo' ),
		$image ? esc_html__( 'Change image', 'simple-seo' ) : esc_html__( 'Choose image', 'simple-seo' )
	);

	printf(
		'<button type="button" class="button button-secondary simple-seo-share-image-remove" %1$s>%2$s</button></div>',
		$image ? '' : 'hidden',
		esc_html__( 'Remove image', 'simple-seo' )
	);

	printf(
		'<p class="description">%s</p>',
		esc_html__( 'Shown in link previews (Slack, LinkedIn, X and others) when a post has no featured image. Best at 1200 × 630 pixels.', 'simple-seo' )
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

	wp_enqueue_media();

	$asset = require dirname( __DIR__ ) . '/build/share-image-field.asset.php';

	wp_enqueue_script(
		'simple-seo-share-image-field',
		plugins_url( 'build/share-image-field.js', \SIMPLE_SEO_PLUGIN_FILE ),
		$asset['dependencies'],
		$asset['version'],
		true
	);
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
		delete_option( SHARE_IMAGE_OPTION );
	}
}
