<?php
/**
 * CMS Tree Page View (https://wordpress.org/plugins/cms-tree-page-view/), a plugin by the same
 * author: show a page's SEO fields in its page tree card.
 *
 * Only a filter of that plugin's. Without it the filter never runs, so nothing here costs anything.
 *
 * @package SimpleSEO
 */

namespace SimpleSEO;

use WP_Post;

defined( 'ABSPATH' ) || exit;

add_filter( 'cms_tree_page_view_detail_rows', __NAMESPACE__ . '\\tree_detail_rows', 10, 3 );

/**
 * The fields that are in use, as read-only rows in the card of the selected page. CMS Tree Page
 * View only asks for users who can edit the page, and strips tags. Nothing but the menu label
 * while another SEO plugin is in charge, as in the editor.
 *
 * @param array<int, array<string, string>> $rows    Rows so far.
 * @param int                               $page_id Post ID.
 * @param WP_Post|mixed                     $post    The post; checked, since anything can apply a filter.
 * @return array<int, array<string, string>>
 */
function tree_detail_rows( $rows, $page_id, $post ): array {
	$rows = (array) $rows;

	if ( ! $post instanceof WP_Post || ! has_seo_fields( $post->post_type ) ) {
		return $rows;
	}

	$added = [];

	if ( ! active_seo_plugin() ) {
		$added[] = [ 'simple-seo-title', __( 'SEO title', 'simple-seo' ), get_title( $post->ID ) ];
		$added[] = [ 'simple-seo-description', __( 'Meta description', 'simple-seo' ), get_description( $post->ID ) ];

		if ( is_noindex( $post->ID ) ) {
			$added[] = [ 'simple-seo-noindex', __( 'Search engines', 'simple-seo' ), __( 'Discouraged', 'simple-seo' ) ];
		}
	}

	if ( 'page' === $post->post_type ) {
		$added[] = [ 'simple-seo-menu-label', __( 'Menu label', 'simple-seo' ), get_menu_label( $post->ID ) ];
	}

	foreach ( $added as [ $id, $label, $value ] ) {
		if ( '' !== $value ) {
			$rows[] = [
				'id'    => $id,
				'label' => $label,
				'value' => $value,
			];
		}
	}

	return $rows;
}

/**
 * The page tree with a post selected, or '' when CMS Tree Page View isn't active, has no tree
 * for the post type, or the user can't open it. For the links under our Simple History events.
 *
 * Asks CMS Tree Page View for its URL rather than building one; if that method ever goes away,
 * the link just doesn't show.
 *
 * @param WP_Post $post The post.
 */
function page_tree_url( WP_Post $post ): string {
	$get_url = [ '\\CMS_Tree_Page_View\\Admin\\Menu', 'get_tree_view_url' ];
	$type    = get_post_type_object( $post->post_type );

	if ( ! is_callable( $get_url ) || ! $type || ! current_user_can( $type->cap->edit_posts ) ) {
		return '';
	}

	$url = (string) call_user_func( $get_url, $post->post_type );

	return '' === $url ? '' : add_query_arg( 'selected', $post->ID, $url );
}
