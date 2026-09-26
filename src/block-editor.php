<?php
/**
 * Block editor: the SEO panel in the document sidebar (js/editor-panel.js, built to build/).
 *
 * @package SimpleSEO
 */

namespace SimpleSEO;

defined( 'ABSPATH' ) || exit;

add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor_panel' );

/**
 * Load the panel when editing a post of a type with pages on the front end.
 * Not in the site editor or the widgets editor.
 */
function enqueue_editor_panel(): void {
	$screen = get_current_screen();

	if ( ! $screen || 'post' !== $screen->base || ! has_seo_fields( $screen->post_type ) ) {
		return;
	}

	$asset = require dirname( __DIR__ ) . '/build/editor-panel.asset.php';

	wp_enqueue_script(
		'simple-seo-editor-panel',
		plugins_url( 'build/editor-panel.js', \SIMPLE_SEO_PLUGIN_FILE ),
		$asset['dependencies'],
		$asset['version'],
		true
	);

	wp_add_inline_script(
		'simple-seo-editor-panel',
		'window.simpleSeoEditor = ' . wp_json_encode(
			[
				'otherPlugin'      => active_seo_plugin(),
				'simpleHistoryUrl' => simple_history_tip_url(),
				'frontPageId'      => 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0,
			]
		) . ';',
		'before'
	);

	wp_set_script_translations( 'simple-seo-editor-panel', 'simple-seo' );
}
