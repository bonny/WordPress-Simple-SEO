<?php
/**
 * Seed the demo content for the WordPress.org screenshots: a made-up Stockholm coffee roastery
 * (Tallvik Coffee Roasters), set up like a small company's real site: named authors, pages
 * published over several years, a child page, a draft, and a mix of SEO fields.
 *
 * Run with `wp eval-file` on a throwaway site (see SKILL.md): the wp-env dev site for the
 * block editor, list, settings and Simple History shots, the Classic Editor site for the Classic box.
 * Idempotent: deletes the pages it made last time, then recreates them. Sets the site title,
 * tagline, the admin's name, and the default share image (demo-share-image.png), so never
 * point it at a real site.
 *
 * With the argument `full` (wp-env only) it also sets the static front page and publishes the
 * privacy policy, which would change how the Classic Editor test site behaves.
 *
 * Prints the IDs of the "About us" and "Our coffees" pages, space separated.
 */

$full = in_array( 'full', $args ?? [], true );

update_option( 'blogname', 'Tallvik Coffee Roasters' );
update_option( 'blogdescription', 'Specialty coffee, roasted to order in Stockholm' );

// Pretty permalinks, so the edit screen doesn't show ?page_id=.
update_option( 'permalink_structure', '/%postname%/' );
flush_rewrite_rules();

// People instead of "admin": the admin bar, the Author column and Simple History show them.
wp_update_user(
	[
		'ID'           => 1,
		'first_name'   => 'Anna',
		'last_name'    => 'Lindberg',
		'display_name' => 'Anna Lindberg',
		'nickname'     => 'Anna',
	]
);
$erik = get_user_by( 'login', 'erik' );
$erik = $erik ? $erik->ID : wp_insert_user(
	[
		'user_login'   => 'erik',
		'user_pass'    => wp_generate_password(),
		'user_email'   => 'erik@tallvikcoffee.se',
		'first_name'   => 'Erik',
		'last_name'    => 'Sjöberg',
		'display_name' => 'Erik Sjöberg',
		'role'         => 'editor',
	]
);

$sample = get_page_by_path( 'sample-page' );
if ( $sample ) {
	wp_trash_post( $sample->ID );
}

foreach ( get_posts( [ 'post_type' => [ 'page', 'post' ], 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => '_simple_seo_demo' ] ) as $old ) {
	wp_delete_post( $old->ID, true );
}

$paragraphs = fn( string ...$texts ): string => implode(
	"\n\n",
	array_map( fn( $t ) => 0 === strpos( $t, '## ' ) ? "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . substr( $t, 3 ) . "</h2>\n<!-- /wp:heading -->" : "<!-- wp:paragraph -->\n<p>{$t}</p>\n<!-- /wp:paragraph -->", $texts )
);

// Title, content, SEO title (and whether it's used; unused ones are saved empty), meta description,
// menu label, date, author, and optional: parent title, status, noindex.
$pages = [
	[ 'Home', $paragraphs( 'Specialty coffee from small farms, roasted in small batches every Tuesday and Friday in Stockholm.', 'Free shipping in Sweden on orders over 400 kr.' ), 'Tallvik Coffee Roasters – specialty coffee from Stockholm', true, 'Specialty coffee roasted to order in Stockholm since 1998. Free shipping in Sweden on orders over 400 kr.', '', '2019-03-12 09:14', 1 ],
	[ 'Our coffees', $paragraphs( 'Single origins and blends, from light and fruity to dark and chocolatey.', 'Every bag is roasted the week you order it and shipped the day after roasting.' ), 'Coffee beans – light, medium and dark roasts', true, 'Single origins and blends, roasted every Tuesday and Friday and shipped the day after.', 'Coffees', '2019-03-12 09:20', 1 ],
	[ 'Coffee subscription', $paragraphs( 'Fresh beans in your letterbox every two or four weeks. Pause or cancel whenever you like.' ), 'Coffee subscription – fresh beans every month', true, '', 'Subscription', '2021-02-01 13:45', $erik ],
	[ 'Wholesale', $paragraphs( 'We roast for cafés, restaurants and offices around Stockholm, and help your staff get the most out of every bag.' ), 'Wholesale coffee for cafés and offices', false, 'Coffee, equipment and barista training for cafés, restaurants and offices in the Stockholm area.', '', '2020-08-19 10:02', $erik ],
	[ 'About us', $paragraphs( 'Tallvik started in 1998 in a small shed by the water in Nacka, with a second-hand roaster and a lot of burnt beans.', '## Where our coffee comes from', 'We buy green coffee from the same farms year after year, and pay well above the market price for it.', '## Visit us', 'The roastery is open on Saturdays. Come by for a cup and watch us roast.' ), 'About us: small batch coffee from Stockholm', true, 'Small batch coffee, roasted to order in Stockholm since 1998.', 'About', '2019-03-12 09:31', 1 ],
	[ 'Visit the roastery', $paragraphs( 'Open Saturdays 10–15. Taste this week’s coffees and buy beans straight from the roaster.' ), '', true, 'Open Saturdays 10–15. Taste this week’s coffees and watch us roast.', '', '2023-05-02 08:40', $erik, 'About us' ],
	[ 'Holiday gift boxes', $paragraphs( 'Three coffees, a tasting card and a handwritten note.' ), '', true, '', '', '2026-09-21 16:05', $erik, '', 'draft' ],
	[ 'Thanks for your order', $paragraphs( 'Your coffee will be roasted on our next roasting day and shipped the day after.' ), '', true, '', '', '2021-02-01 14:10', $erik, '', 'publish', true ],
	[ 'Contact us', $paragraphs( 'Questions about an order or wholesale? Email hello@example.com and we reply within a day.' ), '', true, '', '', '2019-03-12 09:40', 1 ],
	[ 'Journal', '', '', true, '', '', '2024-01-15 10:00', 1 ],
];

$ids = [];
foreach ( $pages as $i => $p ) {
	$parent = ! empty( $p[8] ) ? ( $ids[ $p[8] ] ?? 0 ) : 0;
	$id     = wp_insert_post(
		[
			'post_type'     => 'page',
			'post_title'    => $p[0],
			'post_content'  => $p[1],
			'post_status'   => $p[9] ?? 'publish',
			'post_author'   => $p[7],
			'post_date'     => $p[6] . ':00',
			'post_modified' => $p[6] . ':00',
			'post_parent'   => $parent,
			'menu_order'    => $i + 1,
		]
	);
	update_post_meta( $id, '_simple_seo_demo', 1 );
	SimpleSEO\save_text( $id, SimpleSEO\TITLE_KEY, $p[3] ? $p[2] : '' );
	SimpleSEO\save_text( $id, SimpleSEO\DESCRIPTION_KEY, $p[4] );
	SimpleSEO\save_menu_label( $id, $p[5] );
	update_post_meta( $id, SimpleSEO\NOINDEX_KEY, ! empty( $p[10] ) );
	$ids[ $p[0] ] = $id;
}

if ( $full ) {
	// Simple History shows the email next to the name. tallvikcoffee.se was unregistered on 2026-09-26.
	wp_update_user( [ 'ID' => 1, 'user_email' => 'anna@tallvikcoffee.se' ] );
	update_option( 'timezone_string', 'Europe/Stockholm' );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $ids['Home'] );

	update_option( 'page_for_posts', $ids['Journal'] );

	$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
	if ( $hello ) {
		wp_trash_post( $hello->ID );
	}

	// Blog posts for the Posts list shot: title, category, author, date, SEO title (and whether
	// it's on), meta description, and optional: noindex, status.
	$posts = [
		[ 'Holiday gift boxes are back', 'News', $erik, '2026-09-24 15:40', '', true, '', false, 'draft' ],
		[ 'New harvest from Huila, Colombia', 'Origins', $erik, '2026-09-18 08:30', 'New harvest: Huila, Colombia – red apple and caramel', true, 'Our first lot from the new Colombian harvest is roasted and ready. Red apple, caramel and a long sweet finish.' ],
		[ 'How to brew with an AeroPress', 'Brewing guides', 1, '2026-08-27 14:12', 'AeroPress recipe – our everyday brew guide', true, 'Our everyday AeroPress recipe: 15 grams of coffee, 230 grams of water and two minutes. Step by step.' ],
		[ 'Summer opening hours', 'News', 1, '2026-06-10 09:05', '', true, '' ],
		[ 'Pour-over for beginners', 'Brewing guides', $erik, '2026-04-02 11:48', 'Pour-over coffee for beginners', false, 'All you need to brew great pour-over coffee at home, and the three mistakes everyone makes at first.' ],
		[ 'Win a year of coffee: the rules', 'News', 1, '2026-03-14 16:20', '', true, '', true ],
		[ 'Meet our new roaster', 'News', $erik, '2026-02-03 10:30', 'Meet our new 15 kg roaster', true, '' ],
		[ 'Why we roast lighter than we used to', 'Origins', 1, '2025-11-20 13:00', '', true, 'Lighter roasts let the farm and the variety come through. Here is what changed at the roastery, and why.' ],
	];

	// Anna has hidden the columns the site doesn't use (no tags, comments closed), in Screen Options.
	update_user_meta( 1, 'manageedit-postcolumnshidden', [ 'tags', 'comments' ] );

	require_once ABSPATH . 'wp-admin/includes/taxonomy.php';
	foreach ( $posts as $p ) {
		$category = get_cat_ID( $p[1] ) ?: wp_create_category( $p[1] );
		$id       = wp_insert_post(
			[
				'post_type'     => 'post',
				'post_title'    => $p[0],
				'post_content'  => $paragraphs( 'Coming soon.' ),
				'post_status'   => $p[8] ?? 'publish',
				'post_author'   => $p[2],
				'post_date'     => $p[3] . ':00',
				'post_category' => [ $category ],
			]
		);
		update_post_meta( $id, '_simple_seo_demo', 1 );
		SimpleSEO\save_text( $id, SimpleSEO\TITLE_KEY, $p[5] ? $p[4] : '' );
		SimpleSEO\save_text( $id, SimpleSEO\DESCRIPTION_KEY, $p[6] );
		update_post_meta( $id, SimpleSEO\NOINDEX_KEY, ! empty( $p[7] ) );
	}

	$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
	if ( $privacy ) {
		wp_update_post( [ 'ID' => $privacy, 'post_status' => 'publish', 'post_date' => '2022-05-24 11:20:00', 'post_author' => 1, 'menu_order' => 20 ] );
	}
}

// The demo share image (1200 × 630, made for the screenshots) as the default share image.
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
foreach ( get_posts( [ 'post_type' => 'attachment', 'numberposts' => -1, 'meta_key' => '_simple_seo_demo' ] ) as $old ) {
	wp_delete_attachment( $old->ID, true );
}
$tmp = wp_tempnam( 'tallvik-share-image.png' );
copy( WP_PLUGIN_DIR . '/simple-seo/.claude/skills/visual-check/demo-share-image.png', $tmp );
$image_id = media_handle_sideload( [ 'name' => 'tallvik-share-image.png', 'tmp_name' => $tmp ], 0, 'Tallvik Coffee Roasters' );
update_post_meta( $image_id, '_simple_seo_demo', 1 );
update_post_meta( $image_id, '_wp_attachment_image_alt', 'Tallvik Coffee Roasters, specialty coffee from Stockholm' );
update_option( SimpleSEO\SHARE_IMAGE_OPTION, SimpleSEO\sanitize_share_image( $image_id ) );
// And as About us's own share image, so the editor screenshots show the field in use. Not on
// wp-env: there shot 1 sets it while saving, so the Simple History entry in shot 6 shows the image.
if ( ! $full ) {
	update_post_meta( $ids['About us'], SimpleSEO\SHARE_IMAGE_KEY, $image_id );
}

echo (int) $ids['About us'], ' ', (int) $ids['Our coffees'];
