<?php
/*
Plugin Name: Simple SEO
Description: Change the page title and menu label output for any page or post, which can be useful for SEO (Search Engine Optimization) reasons. It may also increase the usability of your website, making it more friendly and understandable for your visitors.
*/
/*
simple_seo
vid post save: spara våra värden
*/
add_action('admin_init', 'simple_seo_admin_init');
add_action("admin_head", "simple_seo_admin_head");
add_action("save_post", "simple_seo_save_post");
add_action("single_post_title", "simple_seo_single_post_title");
add_action('get_pages', 'simple_seo_get_pages', 10, 2);
// add_action('the_title', 'simple_seo_the_title', 10, 2);


/**
 * change the post_title for get_pages().
 * both wp_list_pages and wp_get_pages use it, so it should work fine
 * some other plugins and templates may use it too. they get the customize for free! :)
 */
function simple_seo_get_pages($pages, $r) {
	foreach ($pages as $loop_id => $page) {
		$use_custom_page_title = (bool) get_post_meta($page->ID, "_simple_seo_use_custom_menu_label", true);
		if ($use_custom_page_title) {
			$custom_menu_label = get_post_meta($page->ID, "_simple_seo_custom_menu_label_value", true);
			$pages[$loop_id]->post_title = $custom_menu_label;
		}
	}
	
	return $pages;
}


/*
function simple_seo_the_title($post_title, $post_id) {
	return $post_title;
}
*/
/**
 * change the page title. called by aciton single_post_title
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
function simple_seo_admin_head() {
	?>
	<style type="text/css">
		#simple_seo_edit_wrapper {
			font-size: 11px;
			margin: 10px 0 0 6px;
		}
		#simple_seo_edit_wrapper input.text {
			font-size: 11px;
			width: 450px;
		}
		#simple_seo_edit_wrapper label {
			cursor: pointer;
		}
		.simple_seo_row {
			
		}
		.simple_seo_row_edit {
			margin-left: 18px;
		}
		.simple_seo_row_edit_help {
			font-style: italic;
		}
	</style>
	<script type="text/javascript">
/*
		jQuery(function($) {
			// append our html to the title/permalink-area, where it look so much better
			$("#simple_seo_edit_wrapper").appendTo("#titlediv");
			$("#simple_seo_custom_page_title,#simple_seo_custom_menu_label").click(function() {
				$(this).closest(".simple_seo_row").find(".simple_seo_row_edit").toggle().find("input[type=text]").focus();
			});
		});
*/
	</script>
	<?php
}

function simple_seo_admin_init() {

	add_filter("dbx_post_sidebar", "simple_seo_dbs_post_sidebar", 10, 1);

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
			<input type="checkbox" name="simple_seo_custom_page_title" id="simple_seo_custom_page_title" value="1" <?php echo ($simple_seo_use_custom_page_title) ? " checked='checked' " : "" ?> />
			<label for="simple_seo_custom_page_title">Custom Page Title</label>
			<div class="simple_seo_row_edit <?php echo ($simple_seo_use_custom_page_title) ? "" : "hidden" ?>">
				<input class="text" type="text" name="simple_seo_custom_page_title_value" value="<?php echo $simple_seo_custom_page_title_value ?>" />
				<div class="simple_seo_row_edit_help">The Page Title is shown in the title bar in the web browser window.</div>
			</div>
		</div>
		<div class="simple_seo_row">
			<input type="checkbox" name="simple_seo_custom_menu_label" id="simple_seo_custom_menu_label" value="1" <?php echo ($simple_seo_use_custom_menu_label) ? " checked='checked '" : "" ?> />
			<label for="simple_seo_custom_menu_label">Custom Menu Label</label>
			<div class="simple_seo_row_edit <?php echo ($simple_seo_use_custom_menu_label) ? "" : "hidden" ?>">
				<input class="text" type="text" name="simple_seo_custom_menu_label_value" value="<?php echo $simple_seo_custom_menu_label_value ?>" />
				<div class="simple_seo_row_edit_help">The Menu Label is the text shown for a page in for example menus.</div>
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


