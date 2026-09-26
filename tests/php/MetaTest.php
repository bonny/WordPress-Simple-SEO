<?php
/**
 * The fields: storage, the on/off flags, and the pre-1.0 title.
 *
 * @package SimpleSEO
 */

use function SimpleSEO\description_field;
use function SimpleSEO\get_description;
use function SimpleSEO\get_title;
use function SimpleSEO\is_noindex;
use function SimpleSEO\save_field;
use function SimpleSEO\title_field;
use const SimpleSEO\DESCRIPTION_DISABLED_KEY;
use const SimpleSEO\DESCRIPTION_KEY;
use const SimpleSEO\TITLE_DISABLED_KEY;
use const SimpleSEO\TITLE_KEY;

class MetaTest extends SimpleSEO_TestCase {

	/**
	 * A post saved with the 0.3.x title fields.
	 *
	 * @param bool $on Whether the old "use custom page title" box was ticked.
	 */
	private function legacy_post( bool $on ): int {
		$post_id = self::factory()->post->create();
		add_post_meta( $post_id, '_simple_seo_use_custom_page_title', $on ? 1 : 0 );
		add_post_meta( $post_id, '_simple_seo_custom_page_title_value', 'Old title' );

		return $post_id;
	}

	public function test_no_fields_means_nothing_used() {
		$post_id = self::factory()->post->create();

		$this->assertSame( [ false, '' ], title_field( $post_id ) );
		$this->assertSame( '', get_title( $post_id ) );
		$this->assertSame( '', get_description( $post_id ) );
		$this->assertFalse( is_noindex( $post_id ) );
	}

	public function test_ticked_field_is_used() {
		$post_id = self::factory()->post->create();
		save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, true, 'SEO title' );

		$this->assertSame( [ true, 'SEO title' ], title_field( $post_id ) );
		$this->assertSame( 'SEO title', get_title( $post_id ) );
	}

	public function test_unticking_keeps_the_text_but_stops_using_it() {
		$post_id = self::factory()->post->create();
		save_field( $post_id, DESCRIPTION_KEY, DESCRIPTION_DISABLED_KEY, false, 'Kept' );

		$this->assertSame( [ false, 'Kept' ], description_field( $post_id ) );
		$this->assertSame( '', get_description( $post_id ) );
	}

	public function test_ticked_but_empty_counts_as_off() {
		// Same as the block editor panel shows it after a reload, so the editors, the list and
		// the Simple History log agree. The front end uses the default either way.
		$post_id = self::factory()->post->create();
		save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, true, '' );

		$this->assertSame( [ false, '' ], title_field( $post_id ) );
		$this->assertSame( '', get_title( $post_id ) );
	}

	public function test_text_without_a_flag_is_on() {
		// What WP-CLI or the REST API do when they set only the text.
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, TITLE_KEY, 'Set by WP-CLI' );

		$this->assertSame( 'Set by WP-CLI', get_title( $post_id ) );
	}

	public function test_noindex_strings_from_wp_cli() {
		$post_id = self::factory()->post->create();

		update_post_meta( $post_id, '_simple_seo_noindex', 'true' );
		$this->assertTrue( is_noindex( $post_id ) );

		update_post_meta( $post_id, '_simple_seo_noindex', 'false' );
		$this->assertFalse( is_noindex( $post_id ) );
	}

	public function test_legacy_title_is_read() {
		$this->assertSame( 'Old title', get_title( $this->legacy_post( true ) ) );
		$this->assertSame( [ false, 'Old title' ], title_field( $this->legacy_post( false ) ) );
	}

	public function test_legacy_title_is_the_default_of_the_new_keys() {
		// So the REST API and the block editor panel show it.
		$post_id = $this->legacy_post( false );

		$this->assertSame( 'Old title', get_post_meta( $post_id, TITLE_KEY, true ) );
		$this->assertTrue( get_post_meta( $post_id, TITLE_DISABLED_KEY, true ) );
	}

	public function test_writing_only_the_flag_keeps_the_legacy_text() {
		// The block editor panel does this when the box is ticked: the text equals the
		// legacy default, so the REST API writes only the flag.
		$post_id = $this->legacy_post( false );
		update_post_meta( $post_id, TITLE_DISABLED_KEY, false );

		$this->assertSame( [ true, 'Old title' ], title_field( $post_id ) );
		$this->assertFalse( metadata_exists( 'post', $post_id, '_simple_seo_custom_page_title_value' ) );
	}

	public function test_saving_moves_the_legacy_title() {
		$post_id = $this->legacy_post( true );
		save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, true, 'New title' );

		$this->assertSame( 'New title', get_title( $post_id ) );
		$this->assertFalse( metadata_exists( 'post', $post_id, '_simple_seo_use_custom_page_title' ) );
		$this->assertFalse( metadata_exists( 'post', $post_id, '_simple_seo_custom_page_title_value' ) );
	}

	public function test_text_is_sanitized() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, TITLE_KEY, '  <b>Bold</b> title ' );

		$this->assertSame( 'Bold title', get_title( $post_id ) );
	}
}
