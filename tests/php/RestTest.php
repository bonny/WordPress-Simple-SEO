<?php
/**
 * The fields in the REST API: edit context only, and only for users who can edit the post.
 *
 * @package SimpleSEO
 */

class RestTest extends SimpleSEO_TestCase {

	private int $post_id;

	public function set_up() {
		parent::set_up();

		$this->post_id = self::factory()->post->create();
		update_post_meta( $this->post_id, '_simple_seo_title', 'Secret draft title' );
	}

	/**
	 * GET the post and return its meta.
	 *
	 * @param string $context REST context.
	 * @return array<string, mixed>
	 */
	private function get_meta( string $context = 'view' ): array {
		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$this->post_id}" );
		$request->set_param( 'context', $context );

		return rest_do_request( $request )->get_data()['meta'] ?? [];
	}

	public function test_anonymous_users_see_nothing() {
		$this->assertArrayNotHasKey( '_simple_seo_title', $this->get_meta() );
	}

	public function test_editors_see_the_fields_in_the_edit_context() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$this->assertArrayNotHasKey( '_simple_seo_title', $this->get_meta() );

		$meta = $this->get_meta( 'edit' );
		$this->assertSame( 'Secret draft title', $meta['_simple_seo_title'] );
		$this->assertFalse( $meta['_simple_seo_noindex'] );
	}

	public function test_editors_can_write() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$this->post_id}" );
		$request->set_body_params( [ 'meta' => [ '_simple_seo_noindex' => true ] ] );

		$this->assertSame( 200, rest_do_request( $request )->get_status() );
		$this->assertTrue( SimpleSEO\is_noindex( $this->post_id ) );
	}

	public function test_clearing_an_old_title_in_the_block_editor() {
		// The panel shows a pre-1.0 title as the key's default; emptying the field must stop it being used.
		$post_id = self::factory()->post->create();
		add_post_meta( $post_id, '_simple_seo_use_custom_page_title', 1 );
		add_post_meta( $post_id, '_simple_seo_custom_page_title_value', 'Old title' );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$post_id}" );
		$request->set_body_params( [ 'meta' => [ '_simple_seo_title' => '' ] ] );

		$this->assertSame( 200, rest_do_request( $request )->get_status() );
		$this->assertSame( '', SimpleSEO\get_title( $post_id ) );
		$this->assertFalse( metadata_exists( 'post', $post_id, '_simple_seo_custom_page_title_value' ) );
	}

	public function test_editors_can_set_the_share_image() {
		$image_id = self::factory()->attachment->create_upload_object( dirname( __DIR__, 2 ) . '/.wordpress-org/banner-772x250.png' );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$this->post_id}" );
		$request->set_body_params( [ 'meta' => [ '_simple_seo_share_image' => $image_id ] ] );

		$this->assertSame( 200, rest_do_request( $request )->get_status() );
		$this->assertSame( $image_id, $this->get_meta( 'edit' )['_simple_seo_share_image'] );
		$this->assertArrayNotHasKey( '_simple_seo_share_image', $this->get_meta() );
	}

	public function test_subscribers_cannot_write() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$this->post_id}" );
		$request->set_body_params( [ 'meta' => [ '_simple_seo_noindex' => true ] ] );

		$this->assertGreaterThanOrEqual( 400, rest_do_request( $request )->get_status() );
		$this->assertFalse( SimpleSEO\is_noindex( $this->post_id ) );
	}
}
