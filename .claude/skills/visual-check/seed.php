<?php
/**
 * Seed the demo content for the WordPress.org screenshots.
 *
 * Run with wp eval-file on the wordpress_php74 site (see SKILL.md). Idempotent:
 * deletes the pages it made last time, then recreates them. Trashes "Sample Page"
 * and sets the site title, so only point it at a throwaway test site.
 *
 * Prints the ID of the "About us" page.
 */

update_option( 'blogname', 'Acme Coffee Roasters' );

// Pretty permalinks, so the Edit screen doesn't show ?page_id= and a "Change Permalink Structure" button.
update_option( 'permalink_structure', '/%postname%/' );
flush_rewrite_rules();

$sample = get_page_by_path( 'sample-page' );
if ( $sample ) {
	wp_trash_post( $sample->ID );
}

foreach ( get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => '_simple_seo_demo' ) ) as $old ) {
	wp_delete_post( $old->ID, true );
}

$pages = array(
	array( 'About us', 'We have been roasting coffee in a small shed in Stockholm since 1998. These days the shed is a bit bigger.', 'Our story – small batch coffee from Stockholm', 'About' ),
	array( 'Our coffees', 'Light, medium and dark roasts, all roasted to order.', '', '' ),
	array( 'Contact us', 'Drop by the roastery or send us an email.', '', 'Contact' ),
);

$about_id = 0;
foreach ( $pages as $i => $p ) {
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_title'   => $p[0],
			'post_content' => $p[1],
			'post_status'  => 'publish',
			'menu_order'   => $i + 1,
		)
	);
	update_post_meta( $id, '_simple_seo_demo', 1 );
	update_post_meta( $id, '_simple_seo_use_custom_page_title', '' !== $p[2] ? 1 : 0 );
	update_post_meta( $id, '_simple_seo_custom_page_title_value', $p[2] );
	update_post_meta( $id, '_simple_seo_use_custom_menu_label', '' !== $p[3] ? 1 : 0 );
	update_post_meta( $id, '_simple_seo_custom_menu_label_value', $p[3] );
	if ( 0 === $i ) {
		$about_id = $id;
	}
}

echo (int) $about_id;
