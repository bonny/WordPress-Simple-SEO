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
 * The box: a checkbox and a text field per value, both always visible. Ticked means used.
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

	[ $title_on, $title ] = title_field( $post->ID );
	text_field( 'title', __( 'Use a custom SEO title', 'simple-seo' ), __( 'SEO title', 'simple-seo' ), $title_on, $title, __( 'The site name is added after it.', 'simple-seo' ) );

	[ $description_on, $description ] = description_field( $post->ID );
	text_field( 'description', __( 'Use a custom meta description', 'simple-seo' ), __( 'Meta description', 'simple-seo' ), $description_on, $description, __( 'Shown under the title in search results.', 'simple-seo' ), true );

	if ( 'page' === $post->post_type ) {
		text_field(
			'menu_label',
			__( 'Use a custom menu label', 'simple-seo' ),
			__( 'Menu label', 'simple-seo' ),
			(bool) get_post_meta( $post->ID, USE_MENU_LABEL_KEY, true ),
			(string) get_post_meta( $post->ID, MENU_LABEL_KEY, true ),
			__( 'Used in automatic page lists, not in hand-made menus.', 'simple-seo' )
		);
	}

	printf(
		'<p><label><input type="checkbox" name="simple_seo[noindex_on]" value="1" %1$s /> %2$s</label><br /><span class="description">%3$s</span></p>',
		checked( is_noindex( $post->ID ), true, false ),
		esc_html__( 'Hide from search engines', 'simple-seo' ),
		esc_html__( 'Anyone with the link can still open it.', 'simple-seo' )
	);

	$tip_url = simple_history_tip_url();

	if ( $tip_url ) {
		printf(
			'<p class="description">%s</p>',
			sprintf(
				/* translators: %s: link to the Simple History plugin. */
				esc_html__( 'Tip: %s logs every change to these fields.', 'simple-seo' ),
				'<a href="' . esc_url( $tip_url ) . '">Simple History</a>'
			)
		);
	}
}

/**
 * One checkbox + text field row.
 *
 * @param string $name        Field name.
 * @param string $label       Checkbox label.
 * @param string $input_label Label of the text field, for screen readers.
 * @param bool   $on          Whether the box is ticked.
 * @param string $value       The text.
 * @param string $help        Help text below the field.
 * @param bool   $textarea    A textarea instead of a one-line input.
 */
function text_field( string $name, string $label, string $input_label, bool $on, string $value, string $help, bool $textarea = false ): void {
	printf(
		'<p><label><input type="checkbox" name="simple_seo[%1$s_on]" value="1" %2$s /> %3$s</label><br />',
		esc_attr( $name ),
		checked( $on, true, false ),
		esc_html( $label )
	);

	if ( $textarea ) {
		printf(
			'<textarea class="widefat" rows="2" name="simple_seo[%1$s]" aria-label="%2$s">%3$s</textarea>',
			esc_attr( $name ),
			esc_attr( $input_label ),
			esc_textarea( $value )
		);
	} else {
		printf(
			'<input type="text" class="widefat" name="simple_seo[%1$s]" aria-label="%2$s" value="%3$s" />',
			esc_attr( $name ),
			esc_attr( $input_label ),
			esc_attr( $value )
		);
	}

	printf( '<span class="description">%s</span></p>', esc_html( $help ) );
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
	$on     = fn( string $name ): bool => ! empty( $fields[ "{$name}_on" ] );
	$text   = fn( string $name ): string => (string) ( $fields[ $name ] ?? '' );

	save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, $on( 'title' ), $text( 'title' ) );
	save_field( $post_id, DESCRIPTION_KEY, DESCRIPTION_DISABLED_KEY, $on( 'description' ), $text( 'description' ) );
	update_post_meta( $post_id, NOINDEX_KEY, $on( 'noindex' ) );

	if ( 'page' === $post->post_type ) {
		update_post_meta( $post_id, USE_MENU_LABEL_KEY, $on( 'menu_label' ) ? 1 : 0 );
		update_post_meta( $post_id, MENU_LABEL_KEY, $text( 'menu_label' ) );
	}
}
