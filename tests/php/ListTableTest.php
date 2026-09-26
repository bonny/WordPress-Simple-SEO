<?php
/**
 * The SEO column in the posts lists, and saving from Quick Edit (and the Classic box,
 * which posts the same fields to the same save_post() handler).
 *
 * @package SimpleSEO
 */

use function SimpleSEO\save_field;
use const SimpleSEO\DESCRIPTION_DISABLED_KEY;
use const SimpleSEO\DESCRIPTION_KEY;
use const SimpleSEO\TITLE_DISABLED_KEY;
use const SimpleSEO\TITLE_KEY;

class ListTableTest extends SimpleSEO_TestCase {

	public function tear_down() {
		$_POST = [];
		parent::tear_down();
	}

	public function test_column_goes_after_the_title() {
		$columns = SimpleSEO\add_column( [ 'cb' => '', 'title' => 'Title', 'author' => 'Author', 'date' => 'Date' ], 'post' );

		$this->assertSame( [ 'cb', 'title', 'simple_seo', 'author', 'date' ], array_keys( $columns ) );
		$this->assertSame( [ 'date', 'simple_seo' ], array_keys( SimpleSEO\add_column( [ 'date' => 'Date' ], 'post' ) ) );
		$this->assertArrayNotHasKey( 'simple_seo', SimpleSEO\add_column( [ 'title' => 'Title' ], 'attachment' ) );
	}

	public function test_column_shows_what_is_used() {
		$post_id = self::factory()->post->create();
		save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, true, 'Our story' );
		save_field( $post_id, DESCRIPTION_KEY, DESCRIPTION_DISABLED_KEY, false, 'Kept but off' );
		update_post_meta( $post_id, '_simple_seo_noindex', true );

		$html = get_echo( 'SimpleSEO\\column_content', [ 'simple_seo', $post_id ] );

		$this->assertStringContainsString( '<div><span class="simple-seo-label">Title:</span> <span class="simple-seo-title">Our story</span></div>', $html );
		$this->assertStringContainsString( 'Search engines discouraged', $html );
		// Switched-off text shows greyed out with "(off)" by its label, not as used.
		$this->assertStringContainsString( '<div class="simple-seo-off"><span class="simple-seo-label">Description (off):</span> <span class="simple-seo-description">Kept but off</span></div>', $html );
	}

	public function test_quick_edit_data_keeps_stored_entities() {
		// Regression: esc_attr() doesn't double-encode, so "&amp;" came back from Quick Edit as "&".
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, TITLE_KEY, 'Q&amp;A' );

		$html = get_echo( 'SimpleSEO\\column_content', [ 'simple_seo', $post_id ] );
		preg_match( '/data-values="([^"]*)"/', $html, $match );
		$values = json_decode( html_entity_decode( $match[1], ENT_QUOTES ), true );

		$this->assertSame( 'Q&amp;A', $values['title'] );
	}

	public function test_column_dash_when_nothing_is_set() {
		$html = get_echo( 'SimpleSEO\\column_content', [ 'simple_seo', self::factory()->post->create() ] );

		$this->assertStringContainsString( '&#8212;', $html );
	}

	public function test_save_from_the_form() {
		$page_id = self::factory()->post->create( [ 'post_type' => 'page' ] );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$_POST = [
			'post_ID'          => $page_id,
			'simple_seo_nonce' => wp_create_nonce( 'simple_seo_save' ),
			'simple_seo'       => [
				'title_on'      => '1',
				'title'         => 'Quick title',
				'description'   => 'Kept but off',
				'noindex_on'    => '1',
				'menu_label_on' => '1',
				'menu_label'    => 'Short',
			],
		];
		SimpleSEO\save_post( $page_id, get_post( $page_id ) );

		$this->assertSame( 'Quick title', SimpleSEO\get_title( $page_id ) );
		$this->assertSame( [ false, 'Kept but off' ], SimpleSEO\description_field( $page_id ) );
		$this->assertTrue( SimpleSEO\is_noindex( $page_id ) );
		$this->assertSame( 'Short', get_post_meta( $page_id, '_simple_seo_custom_menu_label_value', true ) );
	}

	public function test_no_save_without_a_valid_nonce_or_for_another_post() {
		$post_id = self::factory()->post->create();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$_POST = [
			'post_ID'          => $post_id,
			'simple_seo_nonce' => 'wrong',
			'simple_seo'       => [ 'noindex_on' => '1' ],
		];
		SimpleSEO\save_post( $post_id, get_post( $post_id ) );
		$this->assertFalse( SimpleSEO\is_noindex( $post_id ) );

		$_POST['simple_seo_nonce'] = wp_create_nonce( 'simple_seo_save' );
		$_POST['post_ID']          = $post_id + 1;
		SimpleSEO\save_post( $post_id, get_post( $post_id ) );
		$this->assertFalse( SimpleSEO\is_noindex( $post_id ) );
	}
}
