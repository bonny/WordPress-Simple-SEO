<?php
/**
 * Posts and pages lists: an "SEO" column, and the fields in Quick Edit.
 *
 * The list query has already loaded the posts' meta, so the column adds no queries.
 * Quick Edit posts the same field names as the Classic box, so save_post() saves both.
 *
 * @package SimpleSEO
 */

namespace SimpleSEO;

defined( 'ABSPATH' ) || exit;

const COLUMN = 'simple_seo';

add_filter( 'manage_posts_columns', __NAMESPACE__ . '\\add_column', 10, 2 );
add_filter( 'manage_pages_columns', __NAMESPACE__ . '\\add_page_column' );
add_action( 'manage_posts_custom_column', __NAMESPACE__ . '\\column_content', 10, 2 );
add_action( 'manage_pages_custom_column', __NAMESPACE__ . '\\column_content', 10, 2 );
add_action( 'quick_edit_custom_box', __NAMESPACE__ . '\\quick_edit_fields', 10, 2 );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_quick_edit_script' );

/**
 * Add the column right after the title, since the SEO title is an alternative to it.
 *
 * @param array<string, string> $columns   Columns.
 * @param string                $post_type Post type.
 * @return array<string, string>
 */
function add_column( $columns, $post_type = 'page' ): array {
	$columns = (array) $columns;

	if ( ! has_seo_fields( (string) $post_type ) ) {
		return $columns;
	}

	$added = [];

	foreach ( $columns as $key => $label ) {
		$added[ $key ] = $label;

		if ( 'title' === $key ) {
			$added[ COLUMN ] = __( 'SEO', 'simple-seo' );
		}
	}

	// No title column (another plugin removed it): add it at the end.
	if ( ! isset( $added[ COLUMN ] ) ) {
		$added[ COLUMN ] = __( 'SEO', 'simple-seo' );
	}

	return $added;
}

/**
 * The pages list passes no post type.
 *
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function add_page_column( $columns ): array {
	return add_column( $columns, 'page' );
}

/**
 * What's used, and the stored values for Quick Edit in a hidden element.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 */
function column_content( $column, $post_id ): void {
	if ( COLUMN !== $column ) {
		return;
	}

	$post_id                               = (int) $post_id;
	$title                                 = get_title( $post_id );
	$description                           = get_description( $post_id );
	$noindex                               = is_noindex( $post_id );
	[ $title_on, $title_text ]             = title_field( $post_id );
	[ $description_on, $description_text ] = description_field( $post_id );

	$other_plugin = active_seo_plugin();

	if ( $other_plugin && ( '' !== $title || '' !== $description || $noindex ) ) {
		printf(
			'<div class="simple-seo-not-used"><span class="screen-reader-text">%s</span>',
			/* translators: %s: name of another SEO plugin, like Yoast SEO. */
			esc_html( sprintf( __( 'Not used, %s handles SEO:', 'simple-seo' ), $other_plugin ) )
		);
	}

	if ( $noindex ) {
		printf( '<span class="dashicons dashicons-hidden" aria-hidden="true"></span> %s<br />', esc_html__( 'Search engines discouraged', 'simple-seo' ) );
	}

	if ( '' !== $title ) {
		printf( '<strong>%s</strong><br />', esc_html( $title ) );
	}

	if ( '' !== $description ) {
		printf( '<span class="description">%s</span>', esc_html( wp_trim_words( $description, 12 ) ) );
	}

	if ( $other_plugin && ( '' !== $title || '' !== $description || $noindex ) ) {
		echo '</div>';
	}

	if ( '' === $title && '' === $description && ! $noindex ) {
		printf( '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">%s</span>', esc_html__( 'Default', 'simple-seo' ) );
	}

	// esc_textarea() re-encodes entities already in the text, like core's Quick Edit data does.
	// esc_attr() wouldn't, and "&amp;" stored in a field would come back from Quick Edit as "&".
	printf(
		'<div class="hidden simple-seo-data" data-values="%s"></div>',
		esc_textarea(
			(string) wp_json_encode(
				[
					'title_on'       => $title_on,
					'title'          => $title_text,
					'description_on' => $description_on,
					'description'    => $description_text,
					'noindex_on'     => $noindex,
					'menu_label_on'  => (bool) get_post_meta( $post_id, USE_MENU_LABEL_KEY, true ),
					'menu_label'     => (string) get_post_meta( $post_id, MENU_LABEL_KEY, true ),
				]
			)
		)
	);
}

/**
 * The fields in Quick Edit, filled in by build/quick-edit.js: a checkbox, then a full-width
 * text field under it, like core's Tags field.
 *
 * @param string $column    Column name.
 * @param string $post_type Post type.
 */
function quick_edit_fields( $column, $post_type ): void {
	if ( COLUMN !== $column ) {
		return;
	}

	echo '<fieldset class="inline-edit-col-left simple-seo-quick-edit"><div class="inline-edit-col">';
	printf( '<span class="title">%s</span>', esc_html__( 'SEO', 'simple-seo' ) );
	wp_nonce_field( 'simple_seo_save', 'simple_seo_nonce', false );

	$other_plugin = active_seo_plugin();

	if ( $other_plugin ) {
		printf(
			'<p class="description simple-seo-note">%s</p>',
			/* translators: %s: name of another SEO plugin, like Yoast SEO. */
			esc_html( sprintf( __( '%s is active and handles SEO, so these fields aren\'t used.', 'simple-seo' ), $other_plugin ) )
		);
	}

	quick_edit_field( 'title', __( 'Use a custom SEO title', 'simple-seo' ), __( 'SEO title', 'simple-seo' ) );
	quick_edit_field( 'description', __( 'Use a custom meta description', 'simple-seo' ), __( 'Meta description', 'simple-seo' ) );

	if ( 'page' === $post_type ) {
		quick_edit_field( 'menu_label', __( 'Use a custom menu label', 'simple-seo' ), __( 'Menu label', 'simple-seo' ) );
	}

	printf(
		'<label class="simple-seo-check"><input type="checkbox" name="simple_seo[noindex_on]" value="1" /> %s</label>',
		esc_html__( 'Discourage search engines from indexing this page', 'simple-seo' )
	);

	echo '</div></fieldset>';
}

/**
 * One checkbox + text field in Quick Edit.
 *
 * @param string $name        Field name.
 * @param string $label       Checkbox label.
 * @param string $input_label Label of the text field, for screen readers.
 */
function quick_edit_field( string $name, string $label, string $input_label ): void {
	printf(
		'<label class="simple-seo-check"><input type="checkbox" name="simple_seo[%1$s_on]" value="1" /> %2$s</label>',
		esc_attr( $name ),
		esc_html( $label )
	);

	printf(
		'<input type="text" name="simple_seo[%1$s]" aria-label="%2$s" />',
		esc_attr( $name ),
		esc_attr( $input_label )
	);
}

/**
 * Load the Quick Edit script on the posts lists that have the column.
 *
 * @param string $hook_suffix The current admin page.
 */
function enqueue_quick_edit_script( string $hook_suffix ): void {
	$screen = get_current_screen();

	if ( 'edit.php' !== $hook_suffix || ! $screen || ! has_seo_fields( $screen->post_type ) ) {
		return;
	}

	$asset = require dirname( __DIR__ ) . '/build/quick-edit.asset.php';

	wp_enqueue_script(
		'simple-seo-quick-edit',
		plugins_url( 'build/quick-edit.js', \SIMPLE_SEO_PLUGIN_FILE ),
		array_merge( $asset['dependencies'], [ 'inline-edit-post' ] ),
		$asset['version'],
		true
	);
}
