<?php
/**
 * Plugin Name: Simple SEO
 * Plugin URI: http://eskapism.se/code-playground/simple-seo/
 * Description: Change the page title and menu label output for any page or post, which can be useful for SEO (Search Engine Optimization) reasons. It may also increase the usability of your website, making it more friendly and understandable for your visitors.
 * Version: 0.3.5
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Text Domain: simple-seo
 * Domain Path: /languages
 * Author: Pär Thernström
 * Author URI: http://eskapism.se/
 * License: GPL2
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

add_action( 'admin_init', 'simple_seo_admin_init' );
add_action( 'admin_enqueue_scripts', 'simple_seo_admin_enqueue_scripts' );
add_action( 'save_post', 'simple_seo_save_post' );
add_filter( 'single_post_title', 'simple_seo_single_post_title' );
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

		$page->post_title = (string) get_post_meta( $page->ID, '_simple_seo_custom_menu_label_value', true );
	}

	return $pages;
}

/**
 * If on front page and post has custom page title then prepend our title.
 * Only used by old themes that still call wp_title().
 *
 * @param string $post_title Page title.
 * @param string $sep        Title separator.
 * @return string
 */
function simple_seo_wp_title( $post_title, $sep ) {
	global $post;
	if ( isset( $post ) && isset( $post->ID ) ) {
		$post_id               = $post->ID;
		$use_custom_page_title = (bool) get_post_meta( $post_id, '_simple_seo_use_custom_page_title', true );

		if ( is_front_page() && $use_custom_page_title ) {
			$custom_page_title_value = (string) get_post_meta( $post_id, '_simple_seo_custom_page_title_value', true );
			$post_title              = $custom_page_title_value;
			if ( ! empty( $post_title ) ) {
				$post_title .= " $sep ";
			}
		}
	}
	return $post_title;
}

/**
 * Change the page title. Called by filter single_post_title.
 *
 * @param string $title Page title.
 * @return string
 */
function simple_seo_single_post_title( $title ) {
	global $post;
	if ( isset( $post ) && isset( $post->ID ) ) {
		$post_id                 = $post->ID;
		$use_custom_page_title   = (bool) get_post_meta( $post_id, '_simple_seo_use_custom_page_title', true );
		$custom_page_title_value = (string) get_post_meta( $post_id, '_simple_seo_custom_page_title_value', true );
		// Checked but left empty: keep the normal title instead of blanking it.
		if ( $use_custom_page_title && trim( $custom_page_title_value ) !== '' ) {
			$title = $custom_page_title_value;
		}
	}
	return $title;
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

	update_post_meta( $post_id, '_simple_seo_use_custom_page_title', isset( $_POST['simple_seo_custom_page_title'] ) ? 1 : 0 );
	update_post_meta( $post_id, '_simple_seo_use_custom_menu_label', isset( $_POST['simple_seo_custom_menu_label'] ) ? 1 : 0 );

	$title_value = isset( $_POST['simple_seo_custom_page_title_value'] ) ? sanitize_text_field( wp_unslash( $_POST['simple_seo_custom_page_title_value'] ) ) : '';
	$label_value = isset( $_POST['simple_seo_custom_menu_label_value'] ) ? sanitize_text_field( wp_unslash( $_POST['simple_seo_custom_menu_label_value'] ) ) : '';
	update_post_meta( $post_id, '_simple_seo_custom_page_title_value', $title_value );
	update_post_meta( $post_id, '_simple_seo_custom_menu_label_value', $label_value );
}

/**
 * Load translations and hook the fields into the edit post screen.
 */
function simple_seo_admin_init() {
	load_plugin_textdomain( 'simple-seo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

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

	wp_enqueue_style( 'simple_seo_styles', plugins_url( 'styles.css', __FILE__ ), array(), '0.3.5' );
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

	$simple_seo_use_custom_page_title   = (bool) get_post_meta( $post_id, '_simple_seo_use_custom_page_title', true );
	$simple_seo_custom_page_title_value = (string) get_post_meta( $post_id, '_simple_seo_custom_page_title_value', true );

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
