<?php
/**
 * Plugin Name: Simple SEO
 * Plugin URI: https://wordpress.org/plugins/simple-seo/
 * Description: Change the page title and menu label output for any page or post, which can be useful for SEO (Search Engine Optimization) reasons. It may also increase the usability of your website, making it more friendly and understandable for your visitors.
 * Version: 1.2.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Text Domain: simple-seo
 * Author: Pär Thernström
 * Author URI: https://eskapism.se/
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/*
	Copyright 2010  Pär Thernström (email: par.thernstrom@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SIMPLE_SEO_VERSION', '1.2.0' );

// This file must stay parseable by PHP 5.6: WordPress before 5.2 ignores "Requires PHP"
// and installs updates anyway. Too old a site gets a notice instead of a fatal error.
if ( version_compare( (string) phpversion(), '7.4', '<' ) || version_compare( $GLOBALS['wp_version'], '6.6', '<' ) ) {
	add_action( 'admin_notices', 'simple_seo_too_old_notice' );
	return;
}

const SIMPLE_SEO_PLUGIN_FILE = __FILE__;

require __DIR__ . '/src/meta.php';
require __DIR__ . '/src/admin.php';
require __DIR__ . '/src/frontend.php';
require __DIR__ . '/src/link-previews.php';
require __DIR__ . '/src/website-schema.php';
require __DIR__ . '/src/settings.php';
require __DIR__ . '/src/simple-history.php';
require __DIR__ . '/src/cms-tree-page-view.php';
require __DIR__ . '/src/classic-editor.php';
require __DIR__ . '/src/block-editor.php';
require __DIR__ . '/src/list-table.php';

/**
 * Tell admins that this version doesn't run here, and how to get back to one that does.
 */
function simple_seo_too_old_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error error"><p><strong>%1$s</strong> %2$s</p><p>%3$s</p></div>',
		esc_html__( 'Simple SEO is paused: this site is too old for it.', 'simple-seo' ),
		esc_html(
			sprintf(
				/* translators: 1: WordPress version, 2: PHP version. */
				__( 'This version of Simple SEO needs WordPress 6.6 and PHP 7.4 or newer. This site runs WordPress %1$s and PHP %2$s, so your custom page titles and menu labels are not used right now.', 'simple-seo' ),
				$GLOBALS['wp_version'],
				PHP_VERSION
			)
		),
		sprintf(
			/* translators: %s: link to download Simple SEO 0.3.5. */
			esc_html__( 'The fix: deactivate and delete Simple SEO (your titles and labels are kept), then install %s, which works on older sites. After that, don\'t update Simple SEO until WordPress and PHP are updated.', 'simple-seo' ),
			'<a href="https://downloads.wordpress.org/plugin/simple-seo.0.3.5.zip">Simple SEO 0.3.5</a>'
		)
	);
}
