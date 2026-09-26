<?php
/**
 * The whole rendered <head>, as a theme prints it with wp_head(). The other front-end tests
 * check each piece on its own; these catch duplicate tags, tags that never get printed and
 * hook order.
 *
 * @package SimpleSEO
 */

use function SimpleSEO\save_field;
use const SimpleSEO\DESCRIPTION_DISABLED_KEY;
use const SimpleSEO\DESCRIPTION_KEY;
use const SimpleSEO\TITLE_DISABLED_KEY;
use const SimpleSEO\TITLE_KEY;

class HeadTest extends SimpleSEO_TestCase {

	public function set_up() {
		parent::set_up();
		update_option( 'blogname', 'Site' );
		update_option( 'blog_public', '1' );
		// Like a theme with add_theme_support( 'title-tag' ), which warns when called this late.
		$GLOBALS['_wp_theme_features']['title-tag'] = true;
	}

	public function tear_down() {
		unset( $GLOBALS['_wp_theme_features']['title-tag'] );
		parent::tear_down();
	}

	/**
	 * The head of a post's page.
	 *
	 * @param int $post_id Post ID.
	 */
	private function head( int $post_id ): string {
		$this->go_to( get_permalink( $post_id ) );

		return get_echo( 'wp_head' );
	}

	/**
	 * How often a pattern matches the head.
	 *
	 * @param string $pattern Regex.
	 * @param string $head    The head.
	 */
	private function tag_count( string $pattern, string $head ): int {
		return preg_match_all( $pattern, $head );
	}

	public function test_every_field_once() {
		$post_id = self::factory()->post->create( [ 'post_type' => 'page', 'post_title' => 'Post title' ] );
		save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, true, 'SEO title' );
		save_field( $post_id, DESCRIPTION_KEY, DESCRIPTION_DISABLED_KEY, true, 'SEO description' );
		update_post_meta( $post_id, '_simple_seo_noindex', true );

		$head = $this->head( $post_id );

		$this->assertSame( 1, $this->tag_count( '/<title>/', $head ) );
		$this->assertStringContainsString( '<title>SEO title &#8211; Site</title>', $head );
		$this->assertSame( 1, $this->tag_count( '/<meta name=["\']description["\']/', $head ) );
		$this->assertStringContainsString( '<meta name="description" content="SEO description" />', $head );
		$this->assertSame( 1, $this->tag_count( '/<meta name=["\']robots["\']/', $head ) );
		$this->assertMatchesRegularExpression( '/<meta name=["\']robots["\'] content=["\'][^"\']*noindex/', $head );
		$this->assertSame( 1, $this->tag_count( '/<meta property="og:title"/', $head ) );
		$this->assertStringContainsString( '<meta property="og:title" content="SEO title" />', $head );
		$this->assertStringContainsString( '<meta property="og:description" content="SEO description" />', $head );
		$this->assertSame( 1, $this->tag_count( '/<meta name="twitter:card"/', $head ) );
	}

	public function test_post_without_fields() {
		$head = $this->head( self::factory()->post->create( [ 'post_title' => 'Plain' ] ) );

		$this->assertSame( 1, $this->tag_count( '/<title>/', $head ) );
		$this->assertStringContainsString( '<title>Plain &#8211; Site</title>', $head );
		$this->assertSame( 0, $this->tag_count( '/<meta name=["\']description["\']/', $head ) );
		$this->assertStringNotContainsString( 'noindex', $head );
		// Link previews still work, from the post title.
		$this->assertStringContainsString( '<meta property="og:title" content="Plain" />', $head );
	}

	public function test_nothing_of_ours_when_another_seo_plugin_is_active() {
		$post_id = self::factory()->post->create( [ 'post_title' => 'Post title' ] );
		save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, true, 'SEO title' );
		save_field( $post_id, DESCRIPTION_KEY, DESCRIPTION_DISABLED_KEY, true, 'SEO description' );
		update_post_meta( $post_id, '_simple_seo_noindex', true );

		// Undo what plugins_loaded added, then run it again with another plugin "active".
		remove_filter( 'single_post_title', 'SimpleSEO\\post_title' );
		remove_action( 'wp_head', 'SimpleSEO\\meta_description', 1 );
		remove_filter( 'wp_robots', 'SimpleSEO\\robots' );
		remove_action( 'wp_head', 'SimpleSEO\\link_preview_tags', 2 );
		add_filter( 'simple_seo_active_seo_plugin', fn() => 'Other SEO' );
		SimpleSEO\add_seo_hooks();

		$head = $this->head( $post_id );

		$this->assertStringContainsString( '<title>Post title &#8211; Site</title>', $head );
		$this->assertStringNotContainsString( 'SEO description', $head );
		$this->assertStringNotContainsString( 'noindex', $head );
		$this->assertStringNotContainsString( 'og:', $head );
	}

	public function test_no_html_comment() {
		$post_id = self::factory()->post->create();
		save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, true, 'SEO title' );

		// No "optimized with Simple SEO" advert in every page's source (2026-09-26).
		$this->assertStringNotContainsStringIgnoringCase( 'simple seo', $this->head( $post_id ) );
	}
}
