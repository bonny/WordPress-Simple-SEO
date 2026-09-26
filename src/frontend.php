<?php
/**
 * Front end: the title, meta description and robots tag, the sitemap, and the menu label in page lists.
 * Link previews are in link-previews.php.
 *
 * Reads meta only for the queried post, which the main query has already cached. No extra queries.
 *
 * @package SimpleSEO
 */

namespace SimpleSEO;

use WP_Post;

defined( 'ABSPATH' ) || exit;

add_action( 'plugins_loaded', __NAMESPACE__ . '\\add_seo_hooks' );
add_filter( 'get_pages', __NAMESPACE__ . '\\menu_labels' );

/**
 * Output title, description and robots tags and filter the sitemap, unless another SEO plugin
 * does that. On plugins_loaded, because the other plugins may load after this one.
 */
function add_seo_hooks(): void {
	if ( active_seo_plugin() ) {
		return;
	}

	add_filter( 'single_post_title', __NAMESPACE__ . '\\post_title', 10, 2 );
	add_filter( 'document_title_parts', __NAMESPACE__ . '\\front_page_title' );
	add_filter( 'wp_title', __NAMESPACE__ . '\\wp_title_front_page', 10, 2 );
	add_action( 'wp_head', __NAMESPACE__ . '\\meta_description', 1 );
	add_filter( 'wp_robots', __NAMESPACE__ . '\\robots' );
	add_filter( 'wp_sitemaps_posts_query_args', __NAMESPACE__ . '\\sitemap_skip_noindex' );
	add_action( 'wp_head', __NAMESPACE__ . '\\link_preview_tags', 2 );
	add_filter( 'jetpack_enable_open_graph', __NAMESPACE__ . '\\jetpack_open_graph' );
}

/**
 * The name of another active SEO plugin, or '' when there is none.
 * Checks constants they define while loading, so no queries.
 */
function active_seo_plugin(): string {
	$plugins = [
		'WPSEO_VERSION'             => 'Yoast SEO',
		'RANK_MATH_VERSION'         => 'Rank Math',
		'AIOSEO_FILE'               => 'All in One SEO',
		'SEOPRESS_VERSION'          => 'SEOPress',
		'THE_SEO_FRAMEWORK_VERSION' => 'The SEO Framework',
	];

	$active = '';

	foreach ( $plugins as $constant => $name ) {
		if ( defined( $constant ) ) {
			$active = $name;
			break;
		}
	}

	/**
	 * Filters the name of another active SEO plugin. While it's not '', Simple SEO outputs
	 * nothing and the editor fields say that plugin is in charge.
	 *
	 * Return a name to step aside for a plugin Simple SEO doesn't know, or '' to output
	 * anyway. Runs on plugins_loaded, so add the filter from a plugin, not a theme.
	 *
	 * @param string $active Name of the detected plugin, or ''.
	 */
	return (string) apply_filters( 'simple_seo_active_seo_plugin', $active );
}

/**
 * The SEO title to output for a post, or '' for the normal title.
 *
 * @param int $post_id Post ID.
 */
function seo_title( int $post_id ): string {
	// 0 is "no post", for example static_front_page_id() on any page but a static front page.
	if ( ! $post_id ) {
		return '';
	}

	/**
	 * Filters the SEO title of a post before it's used in the <title> tag and link previews.
	 * Return '' to use the normal title. The site name is added after it, except on the front page.
	 *
	 * @param string $title   The SEO title, '' when none is set or it's switched off.
	 * @param int    $post_id Post ID.
	 */
	return trim( (string) apply_filters( 'simple_seo_title', get_title( $post_id ), $post_id ) );
}

/**
 * The post the current page is about: a single post or page, the static front page or the blog page.
 * 0 on archives, search and a front page showing the latest posts.
 */
function queried_post_id(): int {
	return is_singular() || is_home() ? get_queried_object_id() : 0;
}

/**
 * The static front page's ID, or 0 when the front page shows the latest posts.
 */
function static_front_page_id(): int {
	return is_front_page() && 'page' === get_option( 'show_on_front' ) ? get_queried_object_id() : 0;
}

/**
 * Use the SEO title for the post's part of the document title. Core adds " – Site name" after it.
 *
 * Also covers old themes calling wp_title(), which uses the same filter.
 *
 * @param string       $title Post title.
 * @param WP_Post|null $post  The post the title is for. On the blog page this is the
 *                            page, while global $post is the first post in the list.
 * @return string
 */
function post_title( $title, $post = null ) {
	if ( ! $post instanceof WP_Post ) {
		return $title;
	}

	$seo_title = seo_title( $post->ID );

	return '' !== $seo_title ? $seo_title : $title;
}

/**
 * On a static front page, core builds the title from the site name and tagline and never
 * asks for the page title. An SEO title set on that page replaces both.
 *
 * @param array<string, string> $parts Title parts: title, page, tagline, site.
 * @return array<string, string>
 */
function front_page_title( array $parts ): array {
	$seo_title = seo_title( static_front_page_id() );

	if ( '' === $seo_title ) {
		return $parts;
	}

	unset( $parts['tagline'] );
	$parts['title'] = $seo_title;

	return $parts;
}

/**
 * The same for old themes that build the front page title with wp_title().
 *
 * @param string $title Title so far.
 * @param string $sep   Title separator.
 * @return string
 */
function wp_title_front_page( $title, $sep ) {
	$seo_title = seo_title( static_front_page_id() );

	return '' !== $seo_title ? "$seo_title $sep " : $title;
}

/**
 * The meta description of the current page: the post's, or the tagline on a front page
 * with the latest posts. '' for none.
 */
function current_description(): string {
	$post_id     = queried_post_id();
	$description = '';

	if ( $post_id ) {
		$description = get_description( $post_id );
	} elseif ( is_front_page() ) {
		$description = get_bloginfo( 'description' );
	}

	/**
	 * Filters the meta description, also used in link previews. Return '' for none.
	 *
	 * @param string $description The description.
	 * @param int    $post_id     The post, 0 on a front page with the latest posts.
	 */
	return trim( (string) apply_filters( 'simple_seo_description', $description, $post_id ) );
}

/**
 * Print the meta description.
 */
function meta_description(): void {
	$description = current_description();

	if ( '' !== $description ) {
		printf( "<meta name=\"description\" content=\"%s\" />\n", esc_attr( $description ) );
	}
}

/**
 * Add noindex for posts where search engines are discouraged.
 *
 * @param array<string, bool|string> $robots Robots directives.
 * @return array<string, bool|string>
 */
function robots( array $robots ): array {
	$post_id = queried_post_id();

	/**
	 * Filters whether search engines are asked not to index a post (the robots noindex tag).
	 * The sitemap follows the stored setting, not this filter.
	 *
	 * @param bool $noindex Whether the post is hidden.
	 * @param int  $post_id Post ID.
	 */
	if ( $post_id && apply_filters( 'simple_seo_noindex', is_noindex( $post_id ), $post_id ) ) {
		$robots['noindex'] = true;
	}

	return $robots;
}

/**
 * Leave noindexed posts out of core's sitemap. The page count uses the same
 * arguments, so it stays right. Noindex is stored as '1' (the meta's sanitize callback makes it so).
 *
 * @param array<string, mixed> $args WP_Query arguments for one post type's sitemap page.
 * @return array<string, mixed>
 */
function sitemap_skip_noindex( array $args ): array {
	$args['meta_query']   = $args['meta_query'] ?? []; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Only runs for sitemap requests.
	$args['meta_query'][] = [
		'relation' => 'OR',
		[
			'key'     => NOINDEX_KEY,
			'compare' => 'NOT EXISTS',
		],
		[
			'key'     => NOINDEX_KEY,
			'value'   => '1',
			'compare' => '!=',
		],
	];

	return $args;
}

/**
 * Use the custom menu label as the page title in get_pages(), which wp_list_pages()
 * and the Page List block use.
 *
 * @param WP_Post[]|false $pages Pages found by get_pages().
 * @return WP_Post[]|false
 */
function menu_labels( $pages ) {
	if ( ! $pages ) {
		return $pages;
	}

	// Load the meta for all pages in one query, so get_post_meta() below is free.
	update_postmeta_cache( wp_list_pluck( $pages, 'ID' ) );

	foreach ( $pages as $page ) {
		$label = trim( (string) get_post_meta( $page->ID, MENU_LABEL_KEY, true ) );

		// Checked but left empty: keep the page title instead of an empty link.
		if ( '' !== $label && get_post_meta( $page->ID, USE_MENU_LABEL_KEY, true ) ) {
			$page->post_title = $label;
		}
	}

	return $pages;
}
