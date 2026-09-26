<?php
/**
 * Simple History (https://simple-history.com/), Pär's other plugin: log changes to the SEO fields.
 *
 * Nothing here runs unless Simple History is active. Without it, the editor fields show one
 * discreet tip to users who can install plugins.
 *
 * @package SimpleSEO
 */

namespace SimpleSEO;

defined( 'ABSPATH' ) || exit;

add_action( 'simple_history/add_custom_logger', __NAMESPACE__ . '\\register_simple_history_logger' );
add_filter( 'simple_history/post_logger/meta_keys_to_ignore', __NAMESPACE__ . '\\simple_history_ignore_meta_keys' );

/**
 * Register our logger. The action fires since Simple History 2.1, but the namespaced
 * Logger base class exists since 4.0, so check for it first (as CMS Tree Page View does).
 *
 * @param object $simple_history The Simple_History instance.
 */
function register_simple_history_logger( $simple_history ): void {
	if ( ! class_exists( '\\Simple_History\\Loggers\\Logger' ) ) {
		return;
	}

	require_once __DIR__ . '/class-simple-history-logger.php';
	$simple_history->register_logger( Simple_History_Logger::class );
}

/**
 * Our logger shows the changes with old and new values, so leave our keys out of the
 * post logger's generic "custom fields changed" list.
 *
 * @param string[] $keys Meta keys, or "prefix*" wildcards, to ignore.
 * @return string[]
 */
function simple_history_ignore_meta_keys( $keys ): array {
	$keys   = (array) $keys;
	$keys[] = '_simple_seo_*';

	return $keys;
}

/**
 * The values Simple History logs for a post: each checkbox and its text separately, as the user
 * sees them in the editor, so ticking a box on kept text logs as "No → Yes", not as new text.
 *
 * @param int $post_id Post ID.
 * @return array<string, string>
 */
function logged_values( int $post_id ): array {
	$yes_no = fn( bool $on ): string => $on ? __( 'Yes', 'simple-seo' ) : __( 'No', 'simple-seo' );

	[ $title_on, $title ]             = title_field( $post_id );
	[ $description_on, $description ] = description_field( $post_id );

	return [
		'seo_title_on'        => $yes_no( $title_on ),
		'seo_title'           => $title,
		'meta_description_on' => $yes_no( $description_on ),
		'meta_description'    => $description,
		'noindex'             => $yes_no( is_noindex( $post_id ) ),
		'menu_label_on'       => $yes_no( (bool) get_post_meta( $post_id, USE_MENU_LABEL_KEY, true ) ),
		'menu_label'          => trim( (string) get_post_meta( $post_id, MENU_LABEL_KEY, true ) ),
	];
}

/**
 * Link to install Simple History, for the tip in the editor fields. '' when it's active
 * or the user can't install plugins.
 */
function simple_history_tip_url(): string {
	if ( defined( 'SIMPLE_HISTORY_VERSION' ) || ! current_user_can( 'install_plugins' ) ) {
		return '';
	}

	return admin_url( 'plugin-install.php?tab=plugin-information&plugin=simple-history' );
}
