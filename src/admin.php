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
 * Help text for the SEO title field.
 *
 * @param int $post_id Post ID.
 */
function title_help( int $post_id ): string {
	return is_front_page_post( $post_id )
		? __( 'Used as the whole title of the front page.', 'simple-seo' )
		: __( 'The site name is added after it.', 'simple-seo' );
}

/**
 * Help text for "Discourage search engines from indexing this page". noindex is a request, not a block.
 */
function noindex_help(): string {
	return __( 'It\'s up to search engines to honor this request. Anyone with the link can still open the page.', 'simple-seo' );
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
		|| (bool) get_post_meta( $post_id, USE_MENU_LABEL_KEY, true );
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
		'#simple-seo .description { display: block; margin-top: 4px; }
		.simple-seo-quick-edit .title { display: block; }
		.simple-seo-quick-edit .simple-seo-check { display: block; margin-top: 6px; }
		.simple-seo-quick-edit input[type="text"] { width: 100%; }
		.simple-seo-quick-edit .simple-seo-note { margin: 4px 0; }
		.column-simple_seo .simple-seo-not-used, .column-simple_seo .simple-seo-off { opacity: .6; }
		.column-simple_seo .dashicons-hidden { font-size: 16px; width: 16px; height: 16px; vertical-align: text-bottom; }
		' . autogrow_css( '#simple-seo textarea' )
	);
}
