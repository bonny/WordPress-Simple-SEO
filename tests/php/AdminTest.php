<?php
/**
 * Admin help texts and the Simple History tip condition.
 *
 * @package SimpleSEO
 */

use function SimpleSEO\title_help;
use function SimpleSEO\uses_seo_fields;

class AdminTest extends SimpleSEO_TestCase {

	public function test_title_help_says_what_happens() {
		$front = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$other = self::factory()->post->create( [ 'post_type' => 'page' ] );

		$this->assertSame( 'About 50 characters. The site name is added after it.', title_help( $front ) );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front );

		$this->assertSame( 'The whole title of the front page.', title_help( $front ) );
		$this->assertSame( 'About 50 characters. The site name is added after it.', title_help( $other ) );
	}

	public function test_uses_seo_fields() {
		$post_id = self::factory()->post->create();
		$this->assertFalse( uses_seo_fields( $post_id ) );

		update_post_meta( $post_id, '_simple_seo_noindex', true );
		$this->assertTrue( uses_seo_fields( $post_id ) );

		$other_id = self::factory()->post->create();
		update_post_meta( $other_id, '_simple_seo_share_image', self::factory()->attachment->create_upload_object( dirname( __DIR__, 2 ) . '/.wordpress-org/banner-772x250.png' ) );
		$this->assertTrue( uses_seo_fields( $other_id ) );
	}

	public function test_column_mutes_values_when_another_plugin_is_in_charge() {
		add_filter( 'simple_seo_active_seo_plugin', fn() => 'Other SEO' );
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, '_simple_seo_title', 'Our story' );

		$html = get_echo( 'SimpleSEO\\column_content', [ 'simple_seo', $post_id ] );

		$this->assertStringContainsString( 'simple-seo-not-used', $html );
		$this->assertStringContainsString( 'Not used, Other SEO handles SEO:', $html );
	}
}
