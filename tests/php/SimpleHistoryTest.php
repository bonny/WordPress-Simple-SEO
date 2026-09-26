<?php
/**
 * Logging SEO changes to Simple History.
 *
 * @package SimpleSEO
 */

use function SimpleSEO\save_field;
use const SimpleSEO\TITLE_DISABLED_KEY;
use const SimpleSEO\TITLE_KEY;

class SimpleHistoryTest extends SimpleSEO_TestCase {

	private function logger(): SimpleSEO\Simple_History_Logger {
		$logger = Simple_History\Simple_History::get_instance()->get_instantiated_logger_by_slug( 'SimpleSEOLogger' );
		$this->assertInstanceOf( SimpleSEO\Simple_History_Logger::class, $logger );

		return $logger;
	}

	/**
	 * Our events for a post, newest first, with their context.
	 *
	 * @return array<int, array<string, string>>
	 */
	private function events( int $post_id ): array {
		global $wpdb;
		$events   = Simple_History\Simple_History::get_instance()->get_events_table_name();
		$contexts = Simple_History\Simple_History::get_instance()->get_contexts_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT h.id FROM {$events} h JOIN {$contexts} c ON c.history_id = h.id WHERE h.logger = 'SimpleSEOLogger' AND c.key = 'post_id' AND c.value = %d ORDER BY h.id DESC", $post_id ) );

		return array_map(
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			fn( $id ) => wp_list_pluck( $wpdb->get_results( $wpdb->prepare( "SELECT `key`, value FROM {$contexts} WHERE history_id = %d", $id ) ), 'value', 'key' ),
			$ids
		);
	}

	public function test_logger_is_registered() {
		$this->logger();
	}

	public function test_one_event_with_the_changed_fields() {
		$post_id = self::factory()->post->create( [ 'post_title' => 'About us' ] );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, true, 'Our story' );
		update_post_meta( $post_id, '_simple_seo_noindex', true );
		$this->logger()->log_changes();

		$events = $this->events( $post_id );
		$this->assertCount( 1, $events );
		$this->assertSame( 'seo_updated', $events[0]['_message_key'] );
		$this->assertSame( 'About us', $events[0]['post_title'] );
		$this->assertSame( 'No', $events[0]['seo_title_on_prev'] );
		$this->assertSame( 'Yes', $events[0]['seo_title_on_new'] );
		$this->assertSame( '', $events[0]['seo_title_prev'] );
		$this->assertSame( 'Our story', $events[0]['seo_title_new'] );
		$this->assertSame( 'Yes', $events[0]['noindex_new'] );
		$this->assertArrayNotHasKey( 'meta_description_new', $events[0] );
	}

	public function test_action_links() {
		$page_id = self::factory()->post->create( [ 'post_type' => 'page' ] );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
		$row = (object) [ 'context' => [ 'post_id' => $page_id, 'post_type' => 'page' ] ];

		$links = $this->logger()->get_action_links( $row );

		$this->assertSame( [ 'Edit page', 'View page', 'All pages' ], wp_list_pluck( $links, 'label' ) );
		$this->assertSame( [ 'edit', 'view', 'view' ], wp_list_pluck( $links, 'action' ) );

		// A deleted page still gets the overview link.
		wp_delete_post( $page_id, true );
		$this->assertSame( [ 'All pages' ], wp_list_pluck( $this->logger()->get_action_links( $row ), 'label' ) );
	}

	public function test_ticking_a_box_logs_only_the_checkbox() {
		$post_id = self::factory()->post->create();
		save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, false, 'Kept but off' );
		$this->logger()->log_changes();

		save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, true, 'Kept but off' );
		$this->logger()->log_changes();

		$event = $this->events( $post_id )[0];
		$this->assertSame( 'No', $event['seo_title_on_prev'] );
		$this->assertSame( 'Yes', $event['seo_title_on_new'] );
		$this->assertArrayNotHasKey( 'seo_title_new', $event );
	}

	public function test_clearing_the_text_logs_the_box_as_off() {
		// Regression (code review): the log said the box stayed on while the block editor showed it off.
		$post_id = self::factory()->post->create();
		save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, true, 'Hi' );
		$this->logger()->log_changes();

		save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, true, '' );
		$this->logger()->log_changes();

		$event = $this->events( $post_id )[0];
		$this->assertSame( 'Yes', $event['seo_title_on_prev'] );
		$this->assertSame( 'No', $event['seo_title_on_new'] );
		$this->assertSame( '', $event['seo_title_new'] );
	}

	public function test_no_event_when_nothing_changed() {
		$post_id = self::factory()->post->create();
		save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, true, 'Same' );
		$this->logger()->log_changes();
		$before = count( $this->events( $post_id ) );

		save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, true, 'Same' );
		$this->logger()->log_changes();

		$this->assertCount( $before, $this->events( $post_id ) );
	}

	public function test_legacy_title_moving_is_not_a_change() {
		$post_id = self::factory()->post->create();
		add_post_meta( $post_id, '_simple_seo_use_custom_page_title', 1 );
		add_post_meta( $post_id, '_simple_seo_custom_page_title_value', 'Old title' );
		$this->logger()->log_changes(); // Setting up the old keys is a write too; on real sites they already exist.
		$before = count( $this->events( $post_id ) );

		save_field( $post_id, TITLE_KEY, TITLE_DISABLED_KEY, true, 'Old title' );
		$this->logger()->log_changes();

		$this->assertCount( $before, $this->events( $post_id ) );
	}

	public function test_share_image_changes_are_logged() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$path  = dirname( __DIR__, 2 ) . '/.claude/skills/visual-check/demo-share-image.png';
		$first = self::factory()->attachment->create_upload_object( $path );
		$other = self::factory()->attachment->create_upload_object( $path );

		update_option( 'simple_seo_share_image', SimpleSEO\sanitize_share_image( $first ) );
		update_post_meta( $first, '_wp_attachment_image_alt', 'New alt' ); // A refresh, not a change.
		update_option( 'simple_seo_share_image', SimpleSEO\sanitize_share_image( $other ) );
		wp_delete_attachment( $other, true );

		$this->assertSame( [ 'share_image_removed', 'share_image_changed', 'share_image_set' ], $this->message_keys( 3 ) );

		$links = $this->logger()->get_action_links( (object) [ 'context' => [ '_message_key' => 'share_image_set' ] ] );
		$this->assertSame( [ 'General settings' ], wp_list_pluck( $links, 'label' ) );
	}

	/**
	 * Message keys of our newest events.
	 *
	 * @return string[]
	 */
	private function message_keys( int $count ): array {
		global $wpdb;
		$events   = Simple_History\Simple_History::get_instance()->get_events_table_name();
		$contexts = Simple_History\Simple_History::get_instance()->get_contexts_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_col( $wpdb->prepare( "SELECT c.value FROM {$events} h JOIN {$contexts} c ON c.history_id = h.id WHERE h.logger = 'SimpleSEOLogger' AND c.key = '_message_key' ORDER BY h.id DESC LIMIT %d", $count ) );
	}

	public function test_post_logger_ignores_our_keys() {
		$this->assertContains( '_simple_seo_*', apply_filters( 'simple_history/post_logger/meta_keys_to_ignore', [], [], [], [] ) );
	}
}
