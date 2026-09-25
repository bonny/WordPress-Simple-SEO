<?php
/**
 * Classic Editor: the fields below the title, and saving them.
 *
 * @package SimpleSEO
 */

namespace SimpleSEO;

use WP_Post;

defined( 'ABSPATH' ) || exit;

add_action( 'dbx_post_sidebar', __NAMESPACE__ . '\\fields' );
add_action( 'save_post', __NAMESPACE__ . '\\save_post' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_styles' );

/**
 * When saving a post: update custom page title and menu label.
 *
 * @param int $post_id Post ID.
 */
function save_post( $post_id ): void {
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
	update_title( $post_id, $title_value );

	update_post_meta( $post_id, '_simple_seo_use_custom_menu_label', isset( $_POST['simple_seo_custom_menu_label'] ) ? 1 : 0 );
	$label_value = isset( $_POST['simple_seo_custom_menu_label_value'] ) ? sanitize_text_field( wp_unslash( $_POST['simple_seo_custom_menu_label_value'] ) ) : '';
	update_post_meta( $post_id, '_simple_seo_custom_menu_label_value', $label_value );
}

/**
 * Load our CSS on the edit post screens only.
 *
 * @param string $hook_suffix The current admin page.
 */
function enqueue_styles( $hook_suffix ): void {
	if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
		return;
	}

	wp_enqueue_style( 'simple_seo_styles', plugins_url( 'styles.css', \SIMPLE_SEO_PLUGIN_FILE ), [], SIMPLE_SEO_VERSION );
}

/**
 * Output HTML for our stuff, on edit post screen.
 * We're outputting it at the wrong place, but then we move it
 * into place with JS.
 *
 * @param WP_Post $post The post being edited.
 */
function fields( WP_Post $post ): void {
	$post_id = (int) $post->ID;

	echo '<input type="hidden" name="simple_seo_save" value="' . esc_attr( wp_create_nonce( 'simple_seo_save' ) ) . '" />';

	$simple_seo_custom_page_title_value = get_title( $post_id );
	$simple_seo_use_custom_page_title   = '' !== $simple_seo_custom_page_title_value;

	$simple_seo_use_custom_menu_label   = (bool) get_post_meta( $post_id, '_simple_seo_use_custom_menu_label', true );
	$simple_seo_custom_menu_label_value = (string) get_post_meta( $post_id, '_simple_seo_custom_menu_label_value', true );

	echo '<div id="simple_seo_edit_wrapper">';

	$other_plugin = active_seo_plugin();

	if ( $other_plugin ) {
		printf(
			'<p class="description">%s</p>',
			esc_html(
				sprintf(
					/* translators: %s: name of another SEO plugin, like Yoast SEO. */
					__( '%s is active, so it handles the page title and Simple SEO\'s title isn\'t used. The menu label still works.', 'simple-seo' ),
					$other_plugin
				)
			)
		);
	}

	?>
		<div class="simple_seo_row">
			<div class="simle_seo_row_checkbox_and_label">
				<input type="checkbox" name="simple_seo_custom_page_title" id="simple_seo_custom_page_title" value="1" <?php checked( $simple_seo_use_custom_page_title ); ?> />
				<label for="simple_seo_custom_page_title"><?php esc_html_e( 'Custom Page Title', 'simple-seo' ); ?></label>
			</div>
			<div class="simple_seo_row_edit <?php echo ( $simple_seo_use_custom_page_title ) ? '' : 'hidden'; ?>">
				<input class="text" type="text" name="simple_seo_custom_page_title_value" value="<?php echo esc_attr( $simple_seo_custom_page_title_value ); ?>" />
				<div class="hidden simple_seo_row_edit_help"><?php esc_html_e( 'The Page Title is shown in search engines and in the title bar of web browsers', 'simple-seo' ); ?></div>
			</div>
		</div>
		<div class="simple_seo_row">
			<div class="simle_seo_row_checkbox_and_label">
				<input type="checkbox" name="simple_seo_custom_menu_label" id="simple_seo_custom_menu_label" value="1" <?php checked( $simple_seo_use_custom_menu_label ); ?> />
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
