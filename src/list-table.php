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

	$post_id     = (int) $post_id;
	$noindex     = is_noindex( $post_id );
	$title       = get_title( $post_id );
	$description = get_description( $post_id );
	$has_values  = $noindex || '' !== $title || '' !== $description;

	$other_plugin = active_seo_plugin();

	if ( $other_plugin && $has_values ) {
		printf(
			'<div class="simple-seo-not-used"><span class="screen-reader-text">%s</span>',
			/* translators: %s: name of another SEO plugin, like Yoast SEO. */
			esc_html( sprintf( __( 'Not used, %s handles SEO:', 'simple-seo' ), $other_plugin ) )
		);
	}

	if ( $noindex ) {
		printf( '<span class="dashicons dashicons-hidden" aria-hidden="true"></span> %s<br />', esc_html__( 'Search engines discouraged', 'simple-seo' ) );
	}

	// A short label on each line, so a row reads on its own.
	if ( '' !== $title ) {
		column_line( __( 'Title:', 'simple-seo' ), $title, 'simple-seo-title' );
	}

	if ( '' !== $description ) {
		column_line( __( 'Description:', 'simple-seo' ), wp_trim_words( $description, 12 ), 'simple-seo-description' );
	}

	if ( $other_plugin && $has_values ) {
		echo '</div>';
	}

	if ( ! $has_values ) {
		printf( '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">%s</span>', esc_html__( 'Default', 'simple-seo' ) );
	}

	// esc_textarea() re-encodes entities already in the text, like core's Quick Edit data does.
	// esc_attr() wouldn't, and "&amp;" stored in a field would come back from Quick Edit as "&".
	printf(
		'<div class="hidden simple-seo-data" data-values="%s"></div>',
		esc_textarea(
			(string) wp_json_encode(
				[
					'title'       => $title,
					'description' => $description,
					'noindex_on'  => $noindex,
					'menu_label'  => get_menu_label( $post_id ),
				]
			)
		)
	);
}

/**
 * One labeled line in the column.
 *
 * @param string $label      Label, like "Title:".
 * @param string $text       The field's text.
 * @param string $text_class Class of the text.
 */
function column_line( string $label, string $text, string $text_class ): void {
	printf(
		'<div><span class="simple-seo-label">%s</span> <span class="%s">%s</span></div>',
		esc_html( $label ),
		esc_attr( $text_class ),
		esc_html( $text )
	);
}

/**
 * The fields in Quick Edit, filled in by build/quick-edit.js: a label, then a full-width
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
	wp_nonce_field( 'simple_seo_save', 'simple_seo_nonce', false );

	$other_plugin = active_seo_plugin();

	if ( $other_plugin ) {
		printf(
			'<p class="description simple-seo-note">%s</p>',
			/* translators: %s: name of another SEO plugin, like Yoast SEO. */
			esc_html( sprintf( __( '%s is active and handles SEO, so these fields aren\'t used.', 'simple-seo' ), $other_plugin ) )
		);
	}

	quick_edit_field( 'title', __( 'SEO title', 'simple-seo' ) );
	quick_edit_field( 'description', __( 'Meta description', 'simple-seo' ) );

	if ( 'page' === $post_type ) {
		quick_edit_field( 'menu_label', __( 'Menu label', 'simple-seo' ) );
	}

	printf(
		'<label class="simple-seo-check"><input type="checkbox" name="simple_seo[noindex_on]" value="1" /> %s</label>',
		esc_html__( 'Discourage search engines from indexing this page', 'simple-seo' )
	);

	echo '</div></fieldset>';
}

/**
 * One labeled text field in Quick Edit.
 *
 * @param string $name  Field name.
 * @param string $label Label.
 */
function quick_edit_field( string $name, string $label ): void {
	printf(
		'<label class="simple-seo-field"><span class="simple-seo-label">%2$s</span><input type="text" name="simple_seo[%1$s]" /></label>',
		esc_attr( $name ),
		esc_html( $label )
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
