<?php
/**
 * Plugin Name: Simple SEO
 * Plugin URI: https://wordpress.org/plugins/simple-seo/
 * Description: Change the page title and menu label output for any page or post, which can be useful for SEO (Search Engine Optimization) reasons. It may also increase the usability of your website, making it more friendly and understandable for your visitors.
 * Version: 0.3.5
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

define( 'SIMPLE_SEO_VERSION', '0.3.5' );

add_action( 'init', 'simple_seo_register_meta' );
add_action( 'added_post_meta', 'simple_seo_forget_legacy_title', 10, 3 );
add_action( 'updated_post_meta', 'simple_seo_forget_legacy_title', 10, 3 );
add_action( 'deleted_post_meta', 'simple_seo_forget_legacy_title', 10, 3 );
add_action( 'admin_init', 'simple_seo_admin_init' );
add_action( 'admin_enqueue_scripts', 'simple_seo_admin_enqueue_scripts' );
add_action( 'save_post', 'simple_seo_save_post' );
add_filter( 'single_post_title', 'simple_seo_single_post_title', 10, 2 );
add_filter( 'get_pages', 'simple_seo_get_pages' );
add_filter( 'wp_title', 'simple_seo_wp_title', 10, 2 );

/**
 * Change the post_title for get_pages().
 * Both wp_list_pages() and get_pages() use it, so it should work fine.
 * Some other plugins and templates may use it too. They get the customization for free! :)
 *
 * @param WP_Post[] $pages Pages found by get_pages().
 * @return WP_Post[]
 */
function simple_seo_get_pages( $pages ) {
	if ( empty( $pages ) ) {
		return $pages;
	}

	// Load the meta for all pages in one query, so get_post_meta() below is free.
	update_postmeta_cache( wp_list_pluck( $pages, 'ID' ) );

	foreach ( $pages as $page ) {
		if ( ! get_post_meta( $page->ID, '_simple_seo_use_custom_menu_label', true ) ) {
			continue;
		}

		$label = (string) get_post_meta( $page->ID, '_simple_seo_custom_menu_label_value', true );

		// Checked but left empty: keep the page title instead of an empty link.
		if ( trim( $label ) === '' ) {
			continue;
		}

		$page->post_title = $label;
	}

	return $pages;
}

/**
 * Register the fields as post meta, so the REST API, WP-CLI and AI tools can read and write them.
 *
 * Meta is only in the REST API for post types that support 'custom-fields'.
 */
function simple_seo_register_meta() {
	// Added in WordPress 4.9.8. Older sites keep working, just without REST access.
	if ( ! function_exists( 'register_post_meta' ) ) {
		return;
	}

	$auth_callback = 'simple_seo_meta_auth_callback';

	register_post_meta(
		'',
		'_simple_seo_title',
		array(
			'type'              => 'string',
			'description'       => __( 'SEO title. Replaces the post title in the <title> tag. Empty uses the post title.', 'simple-seo' ),
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => $auth_callback,
		)
	);

	register_post_meta(
		'',
		'_simple_seo_description',
		array(
			'type'              => 'string',
			'description'       => __( 'Meta description. Empty outputs none, and search engines pick their own snippet.', 'simple-seo' ),
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => $auth_callback,
		)
	);

	register_post_meta(
		'',
		'_simple_seo_noindex',
		array(
			'type'          => 'boolean',
			'description'   => __( 'Hide from search engines (noindex).', 'simple-seo' ),
			'single'        => true,
			'default'       => false,
			'show_in_rest'  => true,
			'auth_callback' => $auth_callback,
		)
	);
}

/**
 * Who may write our meta over the REST API: anyone who can edit the post.
 *
 * @param bool   $allowed  Whether the user can add the meta. Default false for protected keys.
 * @param string $meta_key The meta key.
 * @param int    $post_id  Post ID.
 * @return bool
 */
function simple_seo_meta_auth_callback( $allowed, $meta_key, $post_id ) {
	return current_user_can( 'edit_post', $post_id );
}

/**
 * The SEO title of a post, or '' when there is none.
 *
 * Falls back to the custom page title from before 1.0 until the post is saved again.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function simple_seo_get_title( $post_id ) {
	$title = trim( (string) get_post_meta( $post_id, '_simple_seo_title', true ) );
	if ( $title !== '' ) {
		return $title;
	}

	if ( ! get_post_meta( $post_id, '_simple_seo_use_custom_page_title', true ) ) {
		return '';
	}

	return trim( (string) get_post_meta( $post_id, '_simple_seo_custom_page_title_value', true ) );
}

/**
 * The meta description of a post, or '' when there is none.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function simple_seo_get_description( $post_id ) {
	return trim( (string) get_post_meta( $post_id, '_simple_seo_description', true ) );
}

/**
 * Whether a post is hidden from search engines.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function simple_seo_is_noindex( $post_id ) {
	// Values written with WP-CLI are strings, so "false" and "0" must mean false.
	return rest_sanitize_boolean( get_post_meta( $post_id, '_simple_seo_noindex', true ) );
}

/**
 * Once the SEO title is written in any way (editor, REST API, WP-CLI), drop the pre-1.0 title,
 * so clearing the new title doesn't bring the old one back.
 *
 * @param int|int[] $meta_id   Meta ID(s), unused.
 * @param int       $object_id Post ID.
 * @param string    $meta_key  Meta key.
 */
function simple_seo_forget_legacy_title( $meta_id, $object_id, $meta_key ) {
	if ( $meta_key !== '_simple_seo_title' ) {
		return;
	}

	delete_post_meta( $object_id, '_simple_seo_use_custom_page_title' );
	delete_post_meta( $object_id, '_simple_seo_custom_page_title_value' );
}

/**
 * Save the SEO title and drop the pre-1.0 title keys, so the old value can't come back.
 *
 * @param int    $post_id Post ID.
 * @param string $title   SEO title, '' to remove it.
 */
function simple_seo_update_title( $post_id, $title ) {
	$title = sanitize_text_field( $title );

	if ( $title === '' ) {
		delete_post_meta( $post_id, '_simple_seo_title' );
	} else {
		update_post_meta( $post_id, '_simple_seo_title', $title );
	}

	delete_post_meta( $post_id, '_simple_seo_use_custom_page_title' );
	delete_post_meta( $post_id, '_simple_seo_custom_page_title_value' );
}

/**
 * If a static front page has a custom page title then prepend our title.
 * Only used by old themes that still call wp_title().
 *
 * @param string $post_title Page title.
 * @param string $sep        Title separator.
 * @return string
 */
function simple_seo_wp_title( $post_title, $sep ) {
	// With "Your latest posts" on the front page there is no page to take a title from.
	if ( ! is_front_page() || get_option( 'show_on_front' ) !== 'page' ) {
		return $post_title;
	}

	$custom_page_title = simple_seo_get_title( get_queried_object_id() );
	if ( $custom_page_title === '' ) {
		return $post_title;
	}

	return "$custom_page_title $sep ";
}

/**
 * Change the page title. Called by filter single_post_title.
 *
 * @param string       $title Page title.
 * @param WP_Post|null $_post The post the title is for. On the blog page this is the
 *                            page, while global $post is the first post in the list.
 * @return string
 */
function simple_seo_single_post_title( $title, $_post = null ) {
	if ( ! $_post instanceof WP_Post ) {
		return $title;
	}

	$custom_page_title = simple_seo_get_title( $_post->ID );

	return $custom_page_title !== '' ? $custom_page_title : $title;
}

/**
 * When saving a post: update custom page title and menu label.
 *
 * @param int $post_id Post ID.
 */
function simple_seo_save_post( $post_id ) {
	if ( ! isset( $_POST['simple_seo_save'] ) ) {
		// No nonce. That can't be right, right?
		return;
	}

	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['simple_seo_save'] ) ), 'simple_seo_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Only save to the post the form belongs to, not to other posts saved in the same request.
	if ( ! isset( $_POST['post_ID'] ) || absint( $_POST['post_ID'] ) !== $post_id ) {
		return;
	}

	// Unchecking the box removes the title.
	$title_value = isset( $_POST['simple_seo_custom_page_title'], $_POST['simple_seo_custom_page_title_value'] ) ? sanitize_text_field( wp_unslash( $_POST['simple_seo_custom_page_title_value'] ) ) : '';
	simple_seo_update_title( $post_id, $title_value );

	update_post_meta( $post_id, '_simple_seo_use_custom_menu_label', isset( $_POST['simple_seo_custom_menu_label'] ) ? 1 : 0 );
	$label_value = isset( $_POST['simple_seo_custom_menu_label_value'] ) ? sanitize_text_field( wp_unslash( $_POST['simple_seo_custom_menu_label_value'] ) ) : '';
	update_post_meta( $post_id, '_simple_seo_custom_menu_label_value', $label_value );
}

/**
 * Hook the fields into the edit post screen.
 * Translations load on their own from translate.wordpress.org (WP 4.6+).
 */
function simple_seo_admin_init() {
	add_action( 'dbx_post_sidebar', 'simple_seo_dbs_post_sidebar' );
}

/**
 * Load our CSS on the edit post screens only.
 *
 * @param string $hook_suffix The current admin page.
 */
function simple_seo_admin_enqueue_scripts( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	wp_enqueue_style( 'simple_seo_styles', plugins_url( 'styles.css', __FILE__ ), array(), SIMPLE_SEO_VERSION );
}

/**
 * Output HTML for our stuff, on edit post screen.
 * We're outputting it at the wrong place, but then we move it
 * into place with JS.
 *
 * @param WP_Post $post The post being edited.
 */
function simple_seo_dbs_post_sidebar( $post ) {
	$post_id = (int) $post->ID;

	echo '<input type="hidden" name="simple_seo_save" value="' . esc_attr( wp_create_nonce( 'simple_seo_save' ) ) . '" />';

	$simple_seo_custom_page_title_value = simple_seo_get_title( $post_id );
	$simple_seo_use_custom_page_title   = $simple_seo_custom_page_title_value !== '';

	$simple_seo_use_custom_menu_label   = (bool) get_post_meta( $post_id, '_simple_seo_use_custom_menu_label', true );
	$simple_seo_custom_menu_label_value = (string) get_post_meta( $post_id, '_simple_seo_custom_menu_label_value', true );

	?>
	<div id="simple_seo_edit_wrapper">
		<div class="simple_seo_row">
			<div class="simle_seo_row_checkbox_and_label">
				<input type="checkbox" name="simple_seo_custom_page_title" id="simple_seo_custom_page_title" value="1" <?php echo ( $simple_seo_use_custom_page_title ) ? " checked='checked' " : ''; ?> />
				<label for="simple_seo_custom_page_title"><?php esc_html_e( 'Custom Page Title', 'simple-seo' ); ?></label>
			</div>
			<div class="simple_seo_row_edit <?php echo ( $simple_seo_use_custom_page_title ) ? '' : 'hidden'; ?>">
				<input class="text" type="text" name="simple_seo_custom_page_title_value" value="<?php echo esc_attr( $simple_seo_custom_page_title_value ); ?>" />
				<div class="hidden simple_seo_row_edit_help"><?php esc_html_e( 'The Page Title is shown in search engines and in the title bar of web browsers', 'simple-seo' ); ?></div>
			</div>
		</div>
		<div class="simple_seo_row">
			<div class="simle_seo_row_checkbox_and_label">
				<input type="checkbox" name="simple_seo_custom_menu_label" id="simple_seo_custom_menu_label" value="1" <?php echo ( $simple_seo_use_custom_menu_label ) ? " checked='checked' " : ''; ?> />
				<label for="simple_seo_custom_menu_label"><?php esc_html_e( 'Custom Menu Label', 'simple-seo' ); ?></label>
			</div>
			<div class="simple_seo_row_edit <?php echo ( $simple_seo_use_custom_menu_label ) ? '' : 'hidden'; ?>">
				<input class="text" type="text" name="simple_seo_custom_menu_label_value" value="<?php echo esc_attr( $simple_seo_custom_menu_label_value ); ?>" />
				<div class="hidden simple_seo_row_edit_help"><?php esc_html_e( 'The Menu Label is the text shown for a page in for example menus.', 'simple-seo' ); ?></div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		// append our html to the title/permalink-area, where it look so much better
		// it seems to work when doing it direct here, without waiting for DOMReady
		// (which sometimes make the fields "disappear" for a while, before the page is fully loaded.
		jQuery("#simple_seo_edit_wrapper").appendTo("#titlediv");
		jQuery("#simple_seo_custom_page_title,#simple_seo_custom_menu_label").click(function() {
			jQuery(this).closest(".simple_seo_row").find(".simple_seo_row_edit").toggle().find("input[type=text]").focus();
		});
	</script>

	<?php
}
