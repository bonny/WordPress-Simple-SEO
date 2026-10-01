<?php
/**
 * The fields: storage, "used when not empty", and the pre-1.0 title and menu label.
 *
 * @package SimpleSEO
 */

use function SimpleSEO\get_description;
use function SimpleSEO\get_menu_label;
use function SimpleSEO\get_title;
use function SimpleSEO\is_noindex;
use function SimpleSEO\save_text;
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

		$this->assertSame( '', get_title( $post_id ) );
		$this->assertSame( '', get_description( $post_id ) );
		$this->assertSame( '', get_menu_label( $post_id ) );
		$this->assertFalse( is_noindex( $post_id ) );
	}

	public function test_text_is_used() {
		// Also what WP-CLI and the REST API do: set the text, and it's used.
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, TITLE_KEY, 'SEO title' );

		$this->assertSame( 'SEO title', get_title( $post_id ) );
	}

	public function test_whitespace_counts_as_empty() {
		$post_id = self::factory()->post->create();
		save_text( $post_id, TITLE_KEY, "  \t " );

		$this->assertSame( '', get_title( $post_id ) );
		$this->assertFalse( metadata_exists( 'post', $post_id, TITLE_KEY ) );
	}

	public function test_noindex_strings_from_wp_cli() {
		$post_id = self::factory()->post->create();

		update_post_meta( $post_id, '_simple_seo_noindex', 'true' );
		$this->assertTrue( is_noindex( $post_id ) );

		update_post_meta( $post_id, '_simple_seo_noindex', 'false' );
		$this->assertFalse( is_noindex( $post_id ) );
	}

	public function test_legacy_title_is_used_only_when_ticked() {
		$this->assertSame( 'Old title', get_title( $this->legacy_post( true ) ) );
		// An unticked old title stays unused: nothing starts showing after the update.
		$this->assertSame( '', get_title( $this->legacy_post( false ) ) );
	}

	public function test_legacy_title_is_the_default_of_the_new_key() {
		// So the REST API and the block editor panel show it.
		$this->assertSame( 'Old title', get_post_meta( $this->legacy_post( true ), TITLE_KEY, true ) );
		$this->assertSame( '', get_post_meta( $this->legacy_post( false ), TITLE_KEY, true ) );
	}

	public function test_saving_replaces_the_legacy_title() {
		$post_id = $this->legacy_post( true );
		save_text( $post_id, TITLE_KEY, 'New title' );

		$this->assertSame( 'New title', get_title( $post_id ) );
		$this->assertFalse( metadata_exists( 'post', $post_id, '_simple_seo_use_custom_page_title' ) );
		$this->assertFalse( metadata_exists( 'post', $post_id, '_simple_seo_custom_page_title_value' ) );
	}

	public function test_emptying_removes_the_legacy_title() {
		// Nothing stored under the new key, so deleting it fires no hook; the old title must still go.
		$post_id = $this->legacy_post( true );
		save_text( $post_id, TITLE_KEY, '' );

		$this->assertSame( '', get_title( $post_id ) );
		$this->assertFalse( metadata_exists( 'post', $post_id, '_simple_seo_custom_page_title_value' ) );
	}

	public function test_legacy_menu_label_is_used_only_when_ticked() {
		$page_id = self::factory()->post->create( [ 'post_type' => 'page' ] );
		update_post_meta( $page_id, '_simple_seo_custom_menu_label_value', 'Short' );
		update_post_meta( $page_id, '_simple_seo_use_custom_menu_label', 0 );
		$this->assertSame( '', get_menu_label( $page_id ) );

		update_post_meta( $page_id, '_simple_seo_use_custom_menu_label', 1 );
		$this->assertSame( 'Short', get_menu_label( $page_id ) );
	}

	public function test_text_is_sanitized() {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, TITLE_KEY, '  <b>Bold</b> title ' );

		$this->assertSame( 'Bold title', get_title( $post_id ) );
	}

	public function test_share_image_is_an_image_or_nothing() {
		$post_id  = self::factory()->post->create();
		$image_id = self::factory()->attachment->create_upload_object( dirname( __DIR__, 2 ) . '/.wordpress-org/banner-772x250.png' );

		update_post_meta( $post_id, '_simple_seo_share_image', (string) $image_id );
		$this->assertSame( $image_id, SimpleSEO\get_share_image_id( $post_id ) );

		// Not an image: not stored.
		update_post_meta( $post_id, '_simple_seo_share_image', self::factory()->post->create() );
		$this->assertSame( 0, SimpleSEO\get_share_image_id( $post_id ) );
		$this->assertFalse( metadata_exists( 'post', $post_id, '_simple_seo_share_image' ) );

		// 0, as the editor sends on Remove, deletes the key.
		update_post_meta( $post_id, '_simple_seo_share_image', $image_id );
		update_post_meta( $post_id, '_simple_seo_share_image', 0 );
		$this->assertFalse( metadata_exists( 'post', $post_id, '_simple_seo_share_image' ) );
	}

	public function test_classic_save_keeps_the_share_image_when_the_form_has_none() {
		$post_id  = self::factory()->post->create();
		$image_id = self::factory()->attachment->create_upload_object( dirname( __DIR__, 2 ) . '/.wordpress-org/banner-772x250.png' );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
		update_post_meta( $post_id, '_simple_seo_share_image', $image_id );

		$_POST = [
			'simple_seo_nonce' => wp_create_nonce( 'simple_seo_save' ),
			'post_ID'          => $post_id,
			'simple_seo'       => [ 'title' => 'Quick' ],
		];

		// Quick Edit: no share image field, so it stays.
		SimpleSEO\save_post( $post_id, get_post( $post_id ) );
		$this->assertSame( $image_id, SimpleSEO\get_share_image_id( $post_id ) );

		// The Classic box with the image removed.
		$_POST['simple_seo']['share_image'] = '';
		SimpleSEO\save_post( $post_id, get_post( $post_id ) );
		$this->assertSame( 0, SimpleSEO\get_share_image_id( $post_id ) );

		$_POST = [];
	}

	public function test_deleting_the_image_removes_it_as_share_image() {
		$image_id = self::factory()->attachment->create_upload_object( dirname( __DIR__, 2 ) . '/.wordpress-org/banner-772x250.png' );
		$posts    = self::factory()->post->create_many( 2 );
		$other    = self::factory()->post->create();
		$other_id = self::factory()->attachment->create_upload_object( dirname( __DIR__, 2 ) . '/.wordpress-org/banner-772x250.png' );
		foreach ( $posts as $post_id ) {
			update_post_meta( $post_id, '_simple_seo_share_image', $image_id );
		}
		update_post_meta( $other, '_simple_seo_share_image', $other_id );

		wp_delete_attachment( $image_id, true );

		foreach ( $posts as $post_id ) {
			$this->assertFalse( metadata_exists( 'post', $post_id, '_simple_seo_share_image' ) );
		}
		$this->assertSame( $other_id, SimpleSEO\get_share_image_id( $other ) );
	}

	public function test_classic_box_shows_the_share_image_only_to_users_who_can_upload() {
		$post_id = self::factory()->post->create();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'contributor' ] ) );
		$this->assertStringNotContainsString( 'simple_seo[share_image]', get_echo( 'SimpleSEO\\meta_box', [ get_post( $post_id ) ] ) );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'author' ] ) );
		$this->assertStringContainsString( 'simple_seo[share_image]', get_echo( 'SimpleSEO\\meta_box', [ get_post( $post_id ) ] ) );
	}
}
