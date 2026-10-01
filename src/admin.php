<?php
/**
 * Admin bits shared by the Classic box, the block editor panel and the posts lists:
 * help texts, and a few lines of CSS.
 *
 * @package SimpleSEO
 */

namespace SimpleSEO;

defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_css' );

/**
 * Whether a post is the static front page, where the SEO title is the whole title.
 *
 * @param int $post_id Post ID.
 */
function is_front_page_post( int $post_id ): bool {
	return 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) === $post_id;
}

/**
 * The readme's FAQ on WordPress.org, linked as "Learn more" at the bottom of the fields.
 */
const FAQ_URL = 'https://wordpress.org/plugins/simple-seo/#faq';

/**
 * Help text for the SEO title field.
 *
 * @param int $post_id Post ID.
 */
function title_help( int $post_id ): string {
	return is_front_page_post( $post_id )
		? __( 'The whole title of the front page.', 'simple-seo' )
		: __( 'About 50 characters. The site name is added after it.', 'simple-seo' );
}

/**
 * Help text for "Discourage search engines from indexing this page". noindex is a request, not a block.
 */
function noindex_help(): string {
	return __( 'It\'s up to search engines to honor this request.', 'simple-seo' );
}

/**
 * Help text for a post's share image.
 */
function share_image_help(): string {
	return __( 'Used in link previews instead of the featured image.', 'simple-seo' );
}

/**
 * Whether a post uses any of the fields. The Simple History tip only shows then.
 *
 * @param int $post_id Post ID.
 */
function uses_seo_fields( int $post_id ): bool {
	return '' !== get_title( $post_id )
		|| '' !== get_description( $post_id )
		|| is_noindex( $post_id )
		|| get_share_image_id( $post_id )
		|| '' !== get_menu_label( $post_id );
}

/**
 * An image picker: a preview, the media library buttons and a hidden attachment ID, like core's
 * Site Icon. Used by the default share image in Settings and the share image in the Classic box.
 * Needs enqueue_image_field_script().
 *
 * @param string $name          Form field name.
 * @param int    $attachment_id The image, or 0.
 * @param string $preview_url   URL of the image to show, or ''.
 */
function image_field( string $name, int $attachment_id, string $preview_url ): void {
	$has_image = $attachment_id && '' !== $preview_url;

	printf(
		'<div class="simple-seo-share-image"><img src="%1$s" alt="" style="max-width:300px;width:100%%;height:auto;display:block;margin-bottom:8px" %2$s />',
		esc_url( $preview_url ),
		$has_image ? '' : 'hidden'
	);

	printf(
		'<input type="hidden" name="%1$s" value="%2$s" />',
		esc_attr( $name ),
		esc_attr( $has_image ? (string) $attachment_id : '' )
	);

	printf(
		'<button type="button" class="button simple-seo-share-image-choose" data-choose="%1$s" data-change="%2$s">%3$s</button> ',
		esc_attr__( 'Choose image', 'simple-seo' ),
		esc_attr__( 'Change image', 'simple-seo' ),
		$has_image ? esc_html__( 'Change image', 'simple-seo' ) : esc_html__( 'Choose image', 'simple-seo' )
	);

	printf(
		'<button type="button" class="button button-secondary simple-seo-share-image-remove" %1$s>%2$s</button></div>',
		$has_image ? '' : 'hidden',
		esc_html__( 'Remove image', 'simple-seo' )
	);
}

/**
 * The media library and the script behind image_field(). Can be called while the page renders:
 * both print in the footer.
 */
function enqueue_image_field_script(): void {
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
 * CSS that lets a textarea grow with its text, between two and about ten lines.
 * field-sizing: content where the browser supports it; elsewhere the textarea keeps its rows and scrolls.
 *
 * @param string $selector The textarea selector.
 */
function autogrow_css( string $selector ): string {
	return "{$selector} { field-sizing: content; min-height: 3.5em; max-height: 14em; }";
}

/**
 * CSS for the Simple History tip: small and grey like help text, with a grey link, so it
 * doesn't compete with the fields.
 *
 * @param string $selector The tip's selector.
 */
function tip_css( string $selector ): string {
	return "{$selector} { margin: 0; font-size: 12px; color: #757575; } {$selector} a { color: inherit; }";
}

/**
 * A few lines of CSS for the Classic box and Quick Edit, on the screens that have them.
 *
 * @param string $hook_suffix The current admin page.
 */
function enqueue_admin_css( string $hook_suffix ): void {
	if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php', 'edit.php' ], true ) ) {
		return;
	}

	wp_register_style( 'simple-seo-admin', false, [], SIMPLE_SEO_VERSION );
	wp_enqueue_style( 'simple-seo-admin' );
	wp_add_inline_style(
		'simple-seo-admin',
		'#simple-seo label, #simple-seo .simple-seo-field-label { display: block; margin-bottom: 4px; font-weight: 600; }
		#simple-seo .description { display: block; margin-top: 4px; }
		#simple-seo .simple-seo-field { margin: 1em 0; }
		.simple-seo-quick-edit .simple-seo-field, .simple-seo-quick-edit .simple-seo-check { display: block; margin-top: 6px; }
		.simple-seo-quick-edit input[type="text"] { display: block; width: 100%; }
		.simple-seo-quick-edit .simple-seo-note { margin: 4px 0; }
		.column-simple_seo .simple-seo-not-used { opacity: .6; }
		.column-simple_seo .simple-seo-label { color: #646970; }
		.column-simple_seo .simple-seo-title { font-weight: 600; }
		.column-simple_seo .dashicons-hidden { font-size: 16px; width: 16px; height: 16px; vertical-align: text-bottom; }
		' . autogrow_css( '#simple-seo textarea' ) . ' ' . tip_css( '#simple-seo .simple-seo-tip' )
	);
}
