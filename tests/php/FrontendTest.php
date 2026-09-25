<?php
/**
 * Front end: title, meta description, robots, sitemap, and stepping aside for other SEO plugins.
 *
 * @package SimpleSEO
 */

use function SimpleSEO\save_field;
use const SimpleSEO\DESCRIPTION_DISABLED_KEY;
use const SimpleSEO\DESCRIPTION_KEY;
use const SimpleSEO\TITLE_DISABLED_KEY;
use const SimpleSEO\TITLE_KEY;

class FrontendTest extends SimpleSEO_TestCase {

	public function set_up() {
		parent::set_up();
		update_option( 'blogname', 'Site' );
		update_option( 'blogdescription', 'Tagline' );
	}

	/**
	 * A page with an SEO title and description.
	 *
	 * @param array<string, mixed> $args Post args.
	 */
	private function page( array $args = [] ): int {
		$post_id = self::factory()->post->create( $args + [ 'post_type' => 'page', 'post_title' => 'Post title' ] );
		save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, true, 'SEO title' );
		save_field( $post_id, DESCRIPTION_KEY, DESCRIPTION_DISABLED_KEY, true, 'SEO description' );

		return $post_id;
	}

	private function description_tag(): string {
		return get_echo( 'SimpleSEO\\meta_description' );
	}

	public function test_title_replaces_the_post_title_and_keeps_the_site_name() {
		$this->go_to( get_permalink( $this->page() ) );

		$this->assertSame( 'SEO title &#8211; Site', wp_get_document_title() );
	}

	public function test_post_without_fields_is_untouched() {
		$this->go_to( get_permalink( self::factory()->post->create( [ 'post_title' => 'Plain' ] ) ) );

		$this->assertSame( 'Plain &#8211; Site', wp_get_document_title() );
		$this->assertSame( '', $this->description_tag() );
	}

	public function test_static_front_page_title_is_the_whole_title() {
		$front = $this->page();
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front );
		$this->go_to( home_url( '/' ) );

		$this->assertSame( 'SEO title', wp_get_document_title() );
	}

	public function test_blog_page_uses_its_fields() {
		$blog = $this->page();
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', self::factory()->post->create( [ 'post_type' => 'page' ] ) );
		update_option( 'page_for_posts', $blog );
		$this->go_to( get_permalink( $blog ) );

		$this->assertSame( 'SEO title &#8211; Site', wp_get_document_title() );
		$this->assertStringContainsString( 'content="SEO description"', $this->description_tag() );
	}

	public function test_latest_posts_front_page_uses_the_tagline() {
		$this->go_to( home_url( '/' ) );

		$this->assertSame( 'Site &#8211; Tagline', wp_get_document_title() );
		$this->assertStringContainsString( 'content="Tagline"', $this->description_tag() );
	}

	public function test_description_is_escaped() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, DESCRIPTION_KEY, 'Say "hi" & bye' );
		$this->go_to( get_permalink( $post_id ) );

		$this->assertStringContainsString( 'content="Say &quot;hi&quot; &amp; bye"', $this->description_tag() );
	}

	public function test_noindex() {
		$post_id = self::factory()->post->create();
		$this->go_to( get_permalink( $post_id ) );
		$this->assertArrayNotHasKey( 'noindex', apply_filters( 'wp_robots', [] ) );

		update_post_meta( $post_id, '_simple_seo_noindex', true );
		$this->go_to( get_permalink( $post_id ) );
		$this->assertTrue( apply_filters( 'wp_robots', [] )['noindex'] );
	}

	public function test_sitemap_leaves_out_noindexed_posts() {
		$shown  = self::factory()->post->create();
		$hidden = self::factory()->post->create();
		update_post_meta( $hidden, '_simple_seo_noindex', true );

		$urls = wp_list_pluck( ( new WP_Sitemaps_Posts() )->get_url_list( 1, 'post' ), 'loc' );

		$this->assertContains( get_permalink( $shown ), $urls );
		$this->assertNotContains( get_permalink( $hidden ), $urls );
	}

	public function test_filters_change_the_output() {
		add_filter( 'simple_seo_title', fn( $title ) => "$title (filtered)" );
		add_filter( 'simple_seo_description', fn() => 'Filtered' );
		add_filter( 'simple_seo_noindex', '__return_true' );
		$this->go_to( get_permalink( $this->page() ) );

		$this->assertSame( 'SEO title (filtered) &#8211; Site', wp_get_document_title() );
		$this->assertStringContainsString( 'content="Filtered"', $this->description_tag() );
		$this->assertTrue( apply_filters( 'wp_robots', [] )['noindex'] );
	}

	public function test_title_filter_runs_only_for_real_posts() {
		// Regression: the front page check asked for the SEO title of post 0, and this filter
		// turned that into " (filtered)", which replaced the page's own SEO title.
		add_filter( 'simple_seo_title', fn( $title ) => "$title (filtered)" );
		$this->go_to( get_permalink( $this->page() ) );

		$this->assertSame( 'SEO title (filtered) &#8211; Site', wp_get_document_title() );
	}

	public function test_steps_aside_for_another_seo_plugin() {
		// Undo what plugins_loaded added, then run it again with another plugin "active".
		remove_filter( 'single_post_title', 'SimpleSEO\\post_title' );
		remove_action( 'wp_head', 'SimpleSEO\\meta_description', 1 );
		remove_filter( 'wp_robots', 'SimpleSEO\\robots' );
		remove_action( 'wp_head', 'SimpleSEO\\link_preview_tags', 2 );
		add_filter( 'simple_seo_active_seo_plugin', fn() => 'Other SEO' );

		SimpleSEO\add_seo_hooks();

		$this->assertSame( 'Other SEO', SimpleSEO\active_seo_plugin() );
		$this->assertFalse( has_filter( 'single_post_title', 'SimpleSEO\\post_title' ) );
		$this->assertFalse( has_action( 'wp_head', 'SimpleSEO\\meta_description' ) );
		$this->assertFalse( has_action( 'wp_head', 'SimpleSEO\\link_preview_tags' ) );
		$this->assertFalse( has_filter( 'wp_robots', 'SimpleSEO\\robots' ) );
	}

	public function test_menu_label_in_page_lists() {
		$page_id = self::factory()->post->create( [ 'post_type' => 'page', 'post_title' => 'About our company' ] );
		update_post_meta( $page_id, '_simple_seo_use_custom_menu_label', 1 );
		update_post_meta( $page_id, '_simple_seo_custom_menu_label_value', 'About' );

		$this->assertStringContainsString( '>About</a>', wp_list_pages( [ 'echo' => false, 'include' => $page_id ] ) );
	}
}
