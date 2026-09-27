<?php
/**
 * A WebSite JSON-LD block on the front page: Google's main signal for the site name it shows in
 * search results. Name and URL only, from autoloaded options, so no queries.
 *
 * On by default. Change it, or turn it off, with the simple_seo_website_schema filter.
 * Printed from head_tags(), so nothing is output when another SEO plugin is active.
 *
 * @package SimpleSEO
 */

namespace SimpleSEO;

defined( 'ABSPATH' ) || exit;

/**
 * Print the block, on the first page of the front page only.
 */
function website_schema(): void {
	if ( ! is_front_page() || is_paged() ) {
		return;
	}

	$schema = [
		'@context' => 'https://schema.org',
		'@type'    => 'WebSite',
		// The option is stored with HTML entities, JSON wants the plain text.
		'name'     => wp_specialchars_decode( (string) get_option( 'blogname' ), ENT_QUOTES ),
		'url'      => home_url( '/' ),
	];

	/**
	 * Filters the WebSite JSON-LD on the front page. Change or add values (like 'alternateName'),
	 * or return an empty array to print nothing.
	 *
	 * @param array<string, mixed> $schema The schema.org WebSite object.
	 */
	$schema = (array) apply_filters( 'simple_seo_website_schema', $schema );

	// Google needs a name.
	if ( empty( $schema['name'] ) ) {
		return;
	}

	// JSON_HEX_TAG so a name with "</script>" can't end the tag.
	$json = wp_json_encode( $schema, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

	if ( $json ) {
		printf( "<script type=\"application/ld+json\">%s</script>\n", $json ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON, with < and > escaped.
	}
}
