<?php
/**
 * Classic Editor: a plain meta box with the fields, and saving them.
 *
 * Hidden in the block editor, which gets its own panel.
 *
 * @package SimpleSEO
 */

namespace SimpleSEO;

use WP_Post;

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', __NAMESPACE__ . '\\register_meta_box' );
add_action( 'save_post', __NAMESPACE__ . '\\save_post', 10, 2 );

/**
 * Add the box to every post type that has pages on the front end, except attachments.
 *
 * @param string $post_type Post type of the post being edited.
 */
function register_meta_box( string $post_type ): void {
	if ( ! has_seo_fields( $post_type ) ) {
		return;
	}

	\add_meta_box(
		'simple-seo',
		__( 'Simple SEO', 'simple-seo' ),
		__NAMESPACE__ . '\\meta_box',
		null,
		'normal',
		'high',
		[ '__back_compat_meta_box' => true ]
	);
}

/**
 * The box: a text field per value, used when it isn't empty, and the noindex checkbox.
 *
 * @param WP_Post $post The post being edited.
 */
function meta_box( WP_Post $post ): void {
	wp_nonce_field( 'simple_seo_save', 'simple_seo_nonce' );

	$other_plugin = active_seo_plugin();

	if ( $other_plugin ) {
		printf(
			'<p class="description">%s</p>',
			esc_html(
				sprintf(
					/* translators: %s: name of another SEO plugin, like Yoast SEO. */
					__( '%s is active and handles SEO, so these fields aren\'t used. The menu label still works.', 'simple-seo' ),
					$other_plugin
				)
			)
		);
	}

	text_field( 'title', __( 'SEO title', 'simple-seo' ), get_title( $post->ID ), title_help( $post->ID ) );
	text_field( 'description', __( 'Meta description', 'simple-seo' ), get_description( $post->ID ), __( 'Often shown under the title in search results.', 'simple-seo' ), true );

	if ( 'page' === $post->post_type ) {
		text_field( 'menu_label', __( 'Menu label', 'simple-seo' ), get_menu_label( $post->ID ), __( 'Used in automatic page lists, not in hand-made menus.', 'simple-seo' ) );
	}

	printf(
		'<p><label><input type="checkbox" name="simple_seo[noindex_on]" value="1" aria-describedby="simple-seo-noindex-help" %1$s /> %2$s</label><span class="description" id="simple-seo-noindex-help">%3$s</span></p>',
		checked( is_noindex( $post->ID ), true, false ),
		esc_html__( 'Discourage search engines from indexing this page', 'simple-seo' ),
		esc_html( noindex_help() )
	);

	$tip_url = simple_history_tip_url();

	if ( $tip_url && uses_seo_fields( $post->ID ) ) {
		printf(
			'<p class="description simple-seo-tip">%s</p>',
			sprintf(
				/* translators: %s: link to the Simple History plugin. */
				esc_html__( 'Tip: %s logs every change to these fields.', 'simple-seo' ),
				'<a href="' . esc_url( $tip_url ) . '">Simple History</a>'
			)
		);
	}

	printf(
		'<p><a href="%1$s" target="_blank" rel="noopener">%2$s<span class="screen-reader-text"> %3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>',
		esc_url( FAQ_URL ),
		esc_html__( 'Learn more about these fields', 'simple-seo' ),
		/* translators: Accessibility text. */
		esc_html__( '(opens in a new tab)', 'simple-seo' )
	);
}

/**
 * One labeled text field, with help text under it.
 *
 * @param string $name     Field name.
 * @param string $label    Label.
 * @param string $value    The text.
 * @param string $help     Help text below the field.
 * @param bool   $textarea A textarea instead of a one-line input.
 */
function text_field( string $name, string $label, string $value, string $help, bool $textarea = false ): void {
	printf( '<p><label for="simple-seo-%1$s">%2$s</label>', esc_attr( $name ), esc_html( $label ) );

	if ( $textarea ) {
		printf(
			'<textarea class="widefat" rows="2" id="simple-seo-%1$s" name="simple_seo[%1$s]" aria-describedby="simple-seo-%1$s-help">%2$s</textarea>',
			esc_attr( $name ),
			esc_textarea( $value )
		);
	} else {
		printf(
			'<input type="text" class="widefat" id="simple-seo-%1$s" name="simple_seo[%1$s]" aria-describedby="simple-seo-%1$s-help" value="%2$s" />',
			esc_attr( $name ),
			esc_attr( $value )
		);
	}

	printf( '<span class="description" id="simple-seo-%1$s-help">%2$s</span></p>', esc_attr( $name ), esc_html( $help ) );
}

/**
 * Save the box.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    The post being saved.
 */
function save_post( int $post_id, WP_Post $post ): void {
	if ( ! isset( $_POST['simple_seo_nonce'], $_POST['simple_seo'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['simple_seo_nonce'] ) ), 'simple_seo_save' ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Only save to the post the form belongs to, not to other posts saved in the same request.
	if ( ! isset( $_POST['post_ID'] ) || absint( $_POST['post_ID'] ) !== $post_id ) {
		return;
	}

	$fields = map_deep( wp_unslash( (array) $_POST['simple_seo'] ), 'sanitize_text_field' );
	$text   = fn( string $name ): string => (string) ( $fields[ $name ] ?? '' );

	save_text( $post_id, TITLE_KEY, $text( 'title' ) );
	save_text( $post_id, DESCRIPTION_KEY, $text( 'description' ) );
	update_post_meta( $post_id, NOINDEX_KEY, ! empty( $fields['noindex_on'] ) );

	if ( 'page' === $post->post_type ) {
		save_menu_label( $post_id, $text( 'menu_label' ) );
	}
}
