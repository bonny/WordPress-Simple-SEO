<?php
/**
 * Seed the demo content for the WordPress.org screenshots.
 *
 * Run with `wp eval-file` on a throwaway site (see SKILL.md): the wp-env dev site for the
 * block editor, settings and Simple History shots, the Classic Editor site for the Classic box.
 * Idempotent: deletes the pages it made last time, then recreates them. Trashes "Sample Page",
 * sets the site title and the default share image (acme-share-image.png), so never point it at a real site.
 *
 * Prints the IDs of the "About us" and "Our coffees" pages, space separated.
 */

update_option( 'blogname', 'Acme Coffee Roasters' );
update_option( 'blogdescription', 'Small batch coffee from Stockholm' );

// Pretty permalinks, so the edit screen doesn't show ?page_id=.
update_option( 'permalink_structure', '/%postname%/' );
flush_rewrite_rules();

$sample = get_page_by_path( 'sample-page' );
if ( $sample ) {
	wp_trash_post( $sample->ID );
}

foreach ( get_posts( [ 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => '_simple_seo_demo' ] ) as $old ) {
	wp_delete_post( $old->ID, true );
}

// Title, content, SEO title, meta description, menu label. The thank-you page is hidden from search engines.
$pages = [
	[ 'About us', 'We have been roasting coffee in a small shed in Stockholm since 1998. These days the shed is a bit bigger.', 'Our story – small batch coffee', 'Small batch coffee, roasted to order in Stockholm since 1998.', 'About' ],
	[ 'Our coffees', 'Light, medium and dark roasts, all roasted to order.', 'Coffee beans – light, medium and dark roasts', 'Freshly roasted coffee beans, shipped the day after roasting.', 'Coffees' ],
	[ 'Contact us', 'Drop by the roastery or send us an email.', '', '', 'Contact' ],
	[ 'Thanks for your order', 'Your coffee is on its way.', '', '', '' ],
];

$ids = [];
foreach ( $pages as $i => $p ) {
	$id = wp_insert_post(
		[
			'post_type'    => 'page',
			'post_title'   => $p[0],
			'post_content' => "<!-- wp:paragraph -->\n<p>{$p[1]}</p>\n<!-- /wp:paragraph -->",
			'post_status'  => 'publish',
			'post_author'  => 1,
			'menu_order'   => $i + 1,
		]
	);
	update_post_meta( $id, '_simple_seo_demo', 1 );
	SimpleSEO\save_field( $id, SimpleSEO\TITLE_KEY, SimpleSEO\TITLE_DISABLED_KEY, '' !== $p[2], $p[2] );
	SimpleSEO\save_field( $id, SimpleSEO\DESCRIPTION_KEY, SimpleSEO\DESCRIPTION_DISABLED_KEY, '' !== $p[3], $p[3] );
	update_post_meta( $id, SimpleSEO\USE_MENU_LABEL_KEY, '' !== $p[4] ? 1 : 0 );
	update_post_meta( $id, SimpleSEO\MENU_LABEL_KEY, $p[4] );
	update_post_meta( $id, SimpleSEO\NOINDEX_KEY, 'Thanks for your order' === $p[0] );
	$ids[] = $id;
}

// A demo share image (1200 × 630, made for the screenshots) as the default share image.
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
foreach ( get_posts( [ 'post_type' => 'attachment', 'numberposts' => -1, 'meta_key' => '_simple_seo_demo' ] ) as $old ) {
	wp_delete_attachment( $old->ID, true );
}
$tmp = wp_tempnam( 'acme-share-image.png' );
copy( WP_PLUGIN_DIR . '/simple-seo/.claude/skills/visual-check/acme-share-image.png', $tmp );
$image_id = media_handle_sideload( [ 'name' => 'acme-share-image.png', 'tmp_name' => $tmp ], 0, 'Acme Coffee Roasters' );
update_post_meta( $image_id, '_simple_seo_demo', 1 );
update_post_meta( $image_id, '_wp_attachment_image_alt', 'Acme Coffee Roasters, small batch coffee from Stockholm' );
update_option( SimpleSEO\SHARE_IMAGE_OPTION, SimpleSEO\sanitize_share_image( $image_id ) );

echo (int) $ids[0], ' ', (int) $ids[1];
