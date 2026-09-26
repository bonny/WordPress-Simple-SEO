<?php
/**
 * Link previews: Open Graph and Twitter card tags, for Slack, iMessage, LinkedIn, Mastodon, Bluesky, Facebook etc.
 *
 * On by default. Turn off with add_filter( 'simple_seo_link_previews', '__return_false' ).
 * Hooked from add_seo_hooks(), so nothing is output when another SEO plugin is active.
 *
 * @package SimpleSEO
 */

namespace SimpleSEO;

defined( 'ABSPATH' ) || exit;

/**
 * Whether to output link previews. Checked late, so themes can use the filter too.
 */
function link_previews_enabled(): bool {
	return (bool) apply_filters( 'simple_seo_link_previews', true );
}

/**
 * Jetpack prints its own Open Graph tags. Turn them off while we print ours.
 *
 * @param bool $enabled Whether Jetpack outputs Open Graph tags.
 */
function jetpack_open_graph( $enabled ): bool {
	return link_previews_enabled() ? false : (bool) $enabled;
}

/**
 * Print the tags for a post, a page, the blog page or the front page. Not on archives or search.
 */
function link_preview_tags(): void {
	if ( ! link_previews_enabled() ) {
		return;
	}

	$post_id = queried_post_id();

	if ( ! $post_id && ! is_front_page() ) {
		return;
	}

	$title = get_bloginfo( 'name' );
	$url   = home_url( '/' );
	$image = null;

	if ( $post_id ) {
		$title = seo_title( $post_id );
		$title = '' !== $title ? $title : wp_strip_all_tags( get_the_title( $post_id ) );
		$url   = (string) get_permalink( $post_id );
		$image = featured_image( $post_id );
	}

	// The default share image from Settings → General, when there's no featured image.
	if ( ! $image && share_image() ) {
		$image = share_image();
	}

	/**
	 * The link preview image: [ 'url', 'width', 'height', 'alt' ], or null for none.
	 * By default the featured image, else the default share image from Settings → General.
	 *
	 * @param array{url: string, width: int, height: int, alt: string}|null $image   The featured image, if any.
	 * @param int                                                           $post_id The post, 0 on a front page with the latest posts.
	 */
	$image = apply_filters( 'simple_seo_link_preview_image', $image, $post_id );

	$tags = [
		'og:type'         => is_front_page() ? 'website' : 'article',
		'og:site_name'    => get_bloginfo( 'name' ),
		'og:title'        => $title,
		'og:description'  => current_description(),
		'og:url'          => $url,
		'og:image'        => $image['url'] ?? '',
		'og:image:width'  => $image['width'] ?? '',
		'og:image:height' => $image['height'] ?? '',
		'og:image:alt'    => $image['alt'] ?? '',
		// Apps that read twitter:card take everything else from the og: tags.
		'twitter:card'    => $image ? 'summary_large_image' : 'summary',
	];

	/**
	 * Filters the link preview tags before output. Change, add (e.g. 'og:locale')
	 * or remove tags. Empty values are skipped.
	 *
	 * @param array<string, string|int> $tags    Property => content. og:* print as property, the rest as name.
	 * @param int                       $post_id The post, 0 on a front page with the latest posts.
	 */
	$tags = (array) apply_filters( 'simple_seo_link_preview_tags', $tags, $post_id );

	foreach ( $tags as $property => $content ) {
		if ( '' === (string) $content ) {
			continue;
		}

		printf(
			"<meta %s=\"%s\" content=\"%s\" />\n",
			0 === strpos( (string) $property, 'og:' ) ? 'property' : 'name',
			esc_attr( (string) $property ),
			esc_attr( (string) $content )
		);
	}
}

/**
 * The post's featured image at full size, or null.
 *
 * @param int $post_id Post ID.
 * @return array{url: string, width: int, height: int, alt: string}|null
 */
function featured_image( int $post_id ): ?array {
	$attachment_id = (int) get_post_thumbnail_id( $post_id );
	$src           = $attachment_id ? wp_get_attachment_image_src( $attachment_id, 'full' ) : false;

	if ( ! $src ) {
		return null;
	}

	return [
		'url'    => $src[0],
		'width'  => (int) $src[1],
		'height' => (int) $src[2],
		'alt'    => trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ),
	];
}
