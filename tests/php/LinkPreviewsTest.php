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
}
