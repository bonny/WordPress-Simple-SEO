# Hooks

Filters for developers, all prefixed `simple_seo_`. Each is documented where it's applied in `src/`. Source for the readme's developer FAQ (Phase 4).

| Filter | Arguments | Use it to |
|---|---|---|
| `simple_seo_title` | `string $title`, `int $post_id` | Change the SEO title used in `<title>` and `og:title`. Return `''` for the normal title. |
| `simple_seo_description` | `string $description`, `int $post_id` (0 on a latest-posts front page) | Change the meta description, also used as `og:description`. Return `''` for none. |
| `simple_seo_noindex` | `bool $noindex`, `int $post_id` | Hide or show a post to search engines in the robots tag. The sitemap follows the stored setting. |
| `simple_seo_link_previews` | `bool $enabled` | Return `false` to output no Open Graph or Twitter tags (Jetpack's come back then). |
| `simple_seo_link_preview_image` | `array\|null $image` (`url`, `width`, `height`, `alt`), `int $post_id` | Change the preview image. By default the featured image, else the default share image from Settings → General. |
| `simple_seo_link_preview_tags` | `array $tags` (property => content), `int $post_id` | Change, add (like `og:locale`) or remove any link preview tag. `og:*` print as `property`, the rest as `name`. |
| `simple_seo_active_seo_plugin` | `string $name` | Return a name to make Simple SEO step aside for an SEO plugin it doesn't know, or `''` to output anyway. Runs on `plugins_loaded`, so add it from a plugin. |

No actions of our own: extra head tags go on `wp_head`, and saves can be followed with core's `added_post_meta` / `updated_post_meta` / `deleted_post_meta` (keys in CLAUDE.md).

Examples:

```php
// A different image for one post type (the default share image is set in Settings → General).
add_filter( 'simple_seo_link_preview_image', function ( $image, $post_id ) {
	return 'product' === get_post_type( $post_id )
		? [ 'url' => 'https://example.com/product-share.png', 'width' => 1200, 'height' => 630, 'alt' => '' ]
		: $image;
}, 10, 2 );

// Add the language of the page.
add_filter( 'simple_seo_link_preview_tags', function ( $tags ) {
	$tags['og:locale'] = 'sv_SE';
	return $tags;
} );
```
