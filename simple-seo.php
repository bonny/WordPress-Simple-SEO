<?php
/*
Plugin Name: Simple SEO
Plugin URI: http://eskapism.se/code-playground/simple-seo/
Description: Change the page title and menu label output for any page or post, which can be useful for SEO (Search Engine Optimization) reasons. It may also increase the usability of your website, making it more friendly and understandable for your visitors.
Version: 0.3.4
Author: Pär Thernström
Author URI: http://eskapism.se/
License: GPL2
*/

/*  Copyright 2010  Pär Thernström (email: par.thernstrom@gmail.com)

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

add_action('admin_init', 'simple_seo_admin_init');
// add_action("admin_head", "simple_seo_admin_head");
add_action("save_post", "simple_seo_save_post");
add_action("single_post_title", "simple_seo_single_post_title");
add_action('get_pages', 'simple_seo_get_pages', 10, 2);
add_action('wp_title', 'simple_seo_wp_title', 10, 3);


/**
 * change the post_title for get_pages().
 * both wp_list_pages and wp_get_pages use it, so it should work fine
 * some other plugins and templates may use it too. they get the customize for free! :)
 */
function simple_seo_get_pages($pages, $r) {

	global $wpdb;

	// Get all pages that have a custom menu label
	$arr_pages_ids = array();
	foreach ($pages as $loop_id => $page) {
		$arr_pages_ids[] = $page->ID;
	}
	$str_ids = join(",", $arr_pages_ids);

	// Fetch the ids of all pages that have a custom menu label
	$rows = array();
	if ($str_ids) {
		$sql = "
			SELECT
				post_id
				#, meta_key, meta_value 
			FROM $wpdb->postmeta 
			WHERE 
				post_id IN ($str_ids) 
				AND meta_key = '_simple_seo_use_custom_menu_label' 
				AND meta_value = 1
			";
		$rows = $wpdb->get_results( $sql );
	}	
	
	// nu har vi alla som ska ha custom menu label
	// så hämta in alla igen fast den andra key'n då
	$arr_pages_ids = array();
	foreach ($rows as $row) {
		$arr_pages_ids[] = $row->post_id;
	}
	$str_ids = join(",", $arr_pages_ids);
	
	$rows = array();
	if ($str_ids) {
		$sql = "
			SELECT
				post_id,
				meta_value
			FROM $wpdb->postmeta 
			WHERE 
				post_id IN ($str_ids) 
				AND meta_key = '_simple_seo_custom_menu_label_value'
			";
		$rows = $wpdb->get_results( $sql );
	}
	
	// för varje hittad titel som ska ändras
	foreach ($rows as $row) {
		// ... leta upp rätt post och uppdatera titeln
		foreach ($pages as $page_index => & $one_page) {
			if ($one_page->ID == $row->post_id) {
				$one_page->post_title = $row->meta_value;
				break;
			}
		}
	}
		
	return $pages;
}


function simple_seo_wp_title($post_title, $sep, $seplocation) {

	// If on front page and post has custom page title then append our title
	global $post;
	if (isset($post) && isset($post->ID)) {
		$post_id = $post->ID;
		$use_custom_page_title = (bool) get_post_meta($post_id, "_simple_seo_use_custom_page_title", true);

		if (is_front_page() && $use_custom_page_title) {
			$custom_page_title_value = (string) get_post_meta($post_id, "_simple_seo_custom_page_title_value", true);
			$post_title = $custom_page_title_value;
			if ( !empty($post_title) ) {
				$post_title .= " $sep ";
			}

		}
	
	}
	return $post_title;
}

/**
 * change the page title. called by action single_post_title
 */
function simple_seo_single_post_title($title) {
	global $post;
	if (isset($post) && isset($post->ID)) {
		$post_id = $post->ID;
		$use_custom_page_title = (bool) get_post_meta($post_id, "_simple_seo_use_custom_page_title", true);
		if ($use_custom_page_title) {
			$custom_page_title_value = (string) get_post_meta($post_id, "_simple_seo_custom_page_title_value", true);
			$title = $custom_page_title_value;
		}
		return $title;
	}

}

/**
 * When saving a post: update custom page title and menu label
 */
function simple_seo_save_post($post_id) {

	if (!isset($_POST['simple_seo_save'])) {
		// no nonce. that can't be right, right?
		return $post_id;
	}
	
	if (!wp_verify_nonce( $_POST['simple_seo_save'], "simple_seo_save")) {
		// nonce failed, do nothing
		return $post_id;
	}

	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
		// nah. autosave.
		return $post_id;
	}

	// okej, go on and save
	if (isset($_POST["simple_seo_custom_page_title"])) {
		update_post_meta($post_id, "_simple_seo_use_custom_page_title", 1);
	} else {
		update_post_meta($post_id, "_simple_seo_use_custom_page_title", 0);
	}

	if (isset($_POST["simple_seo_custom_menu_label"])) {
		update_post_meta($post_id, "_simple_seo_use_custom_menu_label", 1);
	} else {
		update_post_meta($post_id, "_simple_seo_use_custom_menu_label", 0);
	}

	$title_value = (isset($_POST["simple_seo_custom_page_title_value"])) ? $_POST["simple_seo_custom_page_title_value"] : "";
	$label_value = (isset($_POST["simple_seo_custom_menu_label_value"])) ? $_POST["simple_seo_custom_menu_label_value"] : "";
	update_post_meta($post_id, "_simple_seo_custom_page_title_value", $title_value);
	update_post_meta($post_id, "_simple_seo_custom_menu_label_value", $label_value);
	
}

/**
 * Output CSS and JS in head of admin
 * @todo: perhaps move to external files
 */
/*
function simple_seo_admin_head() {
}
*/

function simple_seo_admin_init() {

	load_plugin_textdomain('cms-tree-page-view', false, "/simple-seo/languages");

	add_filter("dbx_post_sidebar", "simple_seo_dbs_post_sidebar", 10, 1);

	define("SIMPLE_SEO_URL", WP_PLUGIN_URL . '/simple-seo/');
	wp_enqueue_style( "simple_seo_styles", SIMPLE_SEO_URL . "styles.css", false );

}

/**
 * Output HTML for our stuff, on edit post screen
 * We're outputing it at the wrong place, but then we move it
 * into place with JS.
 */
function simple_seo_dbs_post_sidebar($arg) {

	global $post;
	$post_id = (int) $post->ID;

	echo '<input type="hidden" name="simple_seo_save" value="' . wp_create_nonce("simple_seo_save") . '" />';

	$simple_seo_use_custom_page_title = (bool) get_post_meta($post_id, "_simple_seo_use_custom_page_title", true);
	$simple_seo_custom_page_title_value = (string) get_post_meta($post_id, "_simple_seo_custom_page_title_value", true);
	$simple_seo_custom_page_title_value = esc_html($simple_seo_custom_page_title_value);
	
	$simple_seo_use_custom_menu_label = (bool) get_post_meta($post_id, "_simple_seo_use_custom_menu_label", true);
	$simple_seo_custom_menu_label_value = (string) get_post_meta($post_id, "_simple_seo_custom_menu_label_value", true);
	$simple_seo_custom_menu_label_value = esc_html($simple_seo_custom_menu_label_value);
	
	?>
	<div id="simple_seo_edit_wrapper">
		<div class="simple_seo_row">
			<div class="simle_seo_row_checkbox_and_label">
				<input type="checkbox" name="simple_seo_custom_page_title" id="simple_seo_custom_page_title" value="1" <?php echo ($simple_seo_use_custom_page_title) ? " checked='checked' " : "" ?> />
				<label for="simple_seo_custom_page_title"><?php _e("Custom Page Title", 'simple-seo') ?></label>
			</div>
			<div class="simple_seo_row_edit <?php echo ($simple_seo_use_custom_page_title) ? "" : "hidden" ?>">
				<input class="text" type="text" name="simple_seo_custom_page_title_value" value="<?php echo $simple_seo_custom_page_title_value ?>" />
				<div class="hidden simple_seo_row_edit_help">The Page Title is shown in search engines and in the title bar of web browsers</div>
			</div>
		</div>
		<div class="simple_seo_row">
			<div class="simle_seo_row_checkbox_and_label">
				<input type="checkbox" name="simple_seo_custom_menu_label" id="simple_seo_custom_menu_label" value="1" <?php echo ($simple_seo_use_custom_menu_label) ? " checked='checked '" : "" ?> />
				<label for="simple_seo_custom_menu_label"><?php _e("Custom Menu Label", 'simple-seo') ?></label>
			</div>
			<div class="simple_seo_row_edit <?php echo ($simple_seo_use_custom_menu_label) ? "" : "hidden" ?>">
				<input class="text" type="text" name="simple_seo_custom_menu_label_value" value="<?php echo $simple_seo_custom_menu_label_value ?>" />
				<div class="hidden simple_seo_row_edit_help">The Menu Label is the text shown for a page in for example menus.</div>
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

