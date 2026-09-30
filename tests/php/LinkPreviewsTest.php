<?php
/**
 * Link previews (Open Graph + Twitter card) and the default share image.
 *
 * @package SimpleSEO
 */

use function SimpleSEO\share_image;

class LinkPreviewsTest extends SimpleSEO_TestCase {

	private function tags(): string {
		return get_echo( 'SimpleSEO\\link_preview_tags' );
	}

	private function image(): int {
		return self::factory()->attachment->create_upload_object( dirname( __DIR__, 2 ) . '/.wordpress-org/banner-772x250.png' );
	}

	public function test_post_with_featured_image() {
		$post_id  = self::factory()->post->create( [ 'post_title' => 'Hello' ] );
		$image_id = $this->image();
		set_post_thumbnail( $post_id, $image_id );
		update_post_meta( $image_id, '_wp_attachment_image_alt', 'Banner' );
		update_post_meta( $post_id, '_simple_seo_description', 'About hello' );
		$this->go_to( get_permalink( $post_id ) );

		$tags = $this->tags();

		$this->assertStringContainsString( '<meta property="og:type" content="article" />', $tags );
		$this->assertStringContainsString( '<meta property="og:title" content="Hello" />', $tags );
		$this->assertStringContainsString( '<meta property="og:description" content="About hello" />', $tags );
		$this->assertStringContainsString( '<meta property="og:url" content="' . get_permalink( $post_id ) . '" />', $tags );
		$this->assertStringContainsString( '<meta property="og:image:width" content="772" />', $tags );
		$this->assertStringContainsString( '<meta property="og:image:alt" content="Banner" />', $tags );
		$this->assertStringContainsString( '<meta name="twitter:card" content="summary_large_image" />', $tags );
	}

	public function test_seo_title_is_the_og_title() {
		$post_id = self::factory()->post->create( [ 'post_title' => 'Hello' ] );
		update_post_meta( $post_id, '_simple_seo_title', 'SEO hello' );
		$this->go_to( get_permalink( $post_id ) );

		$this->assertStringContainsString( 'og:title" content="SEO hello"', $this->tags() );
	}

	public function test_no_image_is_a_small_card() {
		$this->go_to( get_permalink( self::factory()->post->create() ) );

		$tags = $this->tags();
		$this->assertStringNotContainsString( 'og:image', $tags );
		$this->assertStringContainsString( 'twitter:card" content="summary"', $tags );
	}

	public function test_small_or_square_image_is_a_small_card() {
		$post_id = self::factory()->post->create();
		add_filter( 'simple_seo_link_preview_image', fn() => [ 'url' => 'https://example.com/icon.png', 'width' => 512, 'height' => 512, 'alt' => '' ] );
		$this->go_to( get_permalink( $post_id ) );

		$tags = $this->tags();
		$this->assertStringContainsString( 'og:image" content="https://example.com/icon.png"', $tags );
		$this->assertStringContainsString( 'twitter:card" content="summary"', $tags );
	}

	public function test_excerpt_when_there_is_no_description() {
		$post_id = self::factory()->post->create( [ 'post_excerpt' => "  A hand-written\n<b>summary</b>. " ] );
		$this->go_to( get_permalink( $post_id ) );

		$this->assertStringContainsString( 'og:description" content="A hand-written summary."', $this->tags() );
		// Only in link previews, not the meta description.
		$this->assertSame( '', SimpleSEO\current_description() );

		update_post_meta( $post_id, '_simple_seo_description', 'The SEO description' );
		$this->assertStringContainsString( 'og:description" content="The SEO description"', $this->tags() );
	}

	public function test_automatic_excerpt_as_the_last_resort() {
		$content = "<!-- wp:heading --><h2>Welcome</h2><!-- /wp:heading -->\n"
			. "<!-- wp:paragraph --><p>First <b>sentence</b> here. [gallery] Second one.</p><!-- /wp:paragraph -->\n"
			. '<!-- wp:paragraph --><p>' . str_repeat( 'word ', 40 ) . '</p><!-- /wp:paragraph -->';
		$post_id = self::factory()->post->create( [ 'post_content' => $content, 'post_excerpt' => '' ] );
		$this->go_to( get_permalink( $post_id ) );

		// No heading, no shortcode, no HTML, 30 words and an ellipsis.
		$this->assertStringContainsString( 'og:description" content="First sentence here. Second one. word word', $this->tags() );
		$this->assertSame( 'First sentence here. Second one. ' . trim( str_repeat( 'word ', 25 ) ) . '&hellip;', SimpleSEO\automatic_excerpt( $post_id ) );
		// Link previews only: no meta description.
		$this->assertSame( '', SimpleSEO\current_description() );
	}

	public function test_blog_page_uses_the_tagline_not_its_own_content() {
		update_option( 'blogdescription', 'Our tagline' );
		$blog_page = self::factory()->post->create( [ 'post_type' => 'page', 'post_content' => 'Text.', 'post_excerpt' => '' ] );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', self::factory()->post->create( [ 'post_type' => 'page' ] ) );
		update_option( 'page_for_posts', $blog_page );
		$this->go_to( get_permalink( $blog_page ) );

		$this->assertStringContainsString( 'og:description" content="Our tagline"', $this->tags() );
	}

	public function test_no_automatic_excerpt_for_password_protected_posts() {
		$post_id = self::factory()->post->create( [ 'post_content' => 'Secret text.', 'post_password' => 'pw' ] );
		$this->assertSame( '', SimpleSEO\automatic_excerpt( $post_id ) );
	}

	public function test_nothing_on_archives() {
		self::factory()->post->create();
		$this->go_to( get_category_link( 1 ) );

		$this->assertSame( '', $this->tags() );
	}

	public function test_default_share_image_when_no_featured_image() {
		$image_id = $this->image();
		update_option( 'simple_seo_share_image', SimpleSEO\sanitize_share_image( $image_id ) );
		$this->go_to( get_permalink( self::factory()->post->create() ) );

		$this->assertStringContainsString( 'og:image" content="' . wp_get_attachment_url( $image_id ) . '"', $this->tags() );
	}

	public function test_default_share_image_follows_edits_and_deletes() {
		$image_id = $this->image();
		update_option( 'simple_seo_share_image', SimpleSEO\sanitize_share_image( $image_id ) );

		update_post_meta( $image_id, '_wp_attachment_image_alt', 'New alt' );
		$this->assertSame( 'New alt', share_image()['alt'] );

		wp_delete_attachment( $image_id, true );
		$this->assertSame( [], share_image() );
	}

	public function test_share_image_option_stays_autoloaded_without_an_image() {
		// Regression: a missing option cost a query on every front-end request.
		delete_option( 'simple_seo_share_image' );
		SimpleSEO\add_share_image_option();
		$this->assertArrayHasKey( 'simple_seo_share_image', wp_load_alloptions() );

		$image_id = $this->image();
		update_option( 'simple_seo_share_image', SimpleSEO\sanitize_share_image( $image_id ) );
		wp_delete_attachment( $image_id, true );

		$this->assertArrayHasKey( 'simple_seo_share_image', wp_load_alloptions() );
		$this->assertSame( [], share_image() );
	}

	public function test_non_image_is_not_a_share_image() {
		$this->assertSame( [], SimpleSEO\sanitize_share_image( self::factory()->post->create() ) );
	}

	public function test_filters() {
		add_filter( 'simple_seo_link_preview_tags', fn( $tags ) => [ 'og:locale' => 'sv_SE' ] + $tags );
		$this->go_to( get_permalink( self::factory()->post->create() ) );
		$this->assertStringContainsString( '<meta property="og:locale" content="sv_SE" />', $this->tags() );

		add_filter( 'simple_seo_link_previews', '__return_false' );
		$this->assertSame( '', $this->tags() );
		$this->assertTrue( SimpleSEO\jetpack_open_graph( true ) );
	}

	public function test_jetpack_open_graph_is_off_while_ours_are_on() {
		$this->assertFalse( apply_filters( 'jetpack_enable_open_graph', true ) );
	}

	public function test_jetpack_seo_tools_are_off() {
		$this->assertTrue( apply_filters( 'jetpack_disable_seo_tools', false ) );
		$this->assertFalse( apply_filters( 'jetpack_seo_meta_tags_enabled', true ) );
	}
}
