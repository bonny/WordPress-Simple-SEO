<?php
/**
 * Simple History logger for the SEO fields. Loaded only by register_simple_history_logger(),
 * when Simple History 4.0+ is active: nothing else may reference this class.
 *
 * @package SimpleSEO
 */

namespace SimpleSEO;

use Simple_History\Event_Details\Event_Details_Group;
use Simple_History\Event_Details\Event_Details_Group_Diff_Table_Formatter;
use Simple_History\Event_Details\Event_Details_Item;

defined( 'ABSPATH' ) || exit;

/**
 * Logs one "Updated the SEO" event per post and request, with the fields that changed.
 */
class Simple_History_Logger extends \Simple_History\Loggers\Logger {

	/**
	 * Logger slug.
	 *
	 * @var string
	 */
	public $slug = 'SimpleSEOLogger';

	/**
	 * Logged values of each post before this request first changed one of our keys.
	 *
	 * @var array<int, array<string, string>>
	 */
	private array $before = [];

	/**
	 * About this logger.
	 *
	 * @return array<string, mixed>
	 */
	public function get_info() {
		return [
			'name'        => __( 'Simple SEO', 'simple-seo' ),
			'description' => __( 'Logs changes to the SEO title, meta description, "Hide from search engines" and menu label.', 'simple-seo' ),
			'name_via'    => __( 'Using plugin Simple SEO', 'simple-seo' ),
			'capability'  => 'edit_posts',
			'messages'    => [
				'seo_updated' => __( 'Updated the SEO for "{post_title}"', 'simple-seo' ),
			],
			'labels'      => [
				'search' => [
					'label'     => __( 'SEO', 'simple-seo' ),
					'label_all' => __( 'All SEO changes', 'simple-seo' ),
					'options'   => [
						__( 'SEO changes', 'simple-seo' ) => [ 'seo_updated' ],
					],
				],
			],
		];
	}

	/**
	 * Remember the values before any write to our keys (editor, REST API, WP-CLI), and log at
	 * the end of the request, so a save that writes several keys is one event.
	 */
	public function loaded(): void {
		add_filter( 'add_post_metadata', [ $this, 'remember_before' ], 10, 3 );
		add_filter( 'update_post_metadata', [ $this, 'remember_before' ], 10, 3 );
		add_filter( 'delete_post_metadata', [ $this, 'remember_before' ], 10, 3 );
		add_action( 'shutdown', [ $this, 'log_changes' ] );
	}

	/**
	 * Short-circuit filter used as a "before write" hook. Never changes the write.
	 *
	 * @param mixed  $check    Null, or a value that short-circuits the write.
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta key.
	 * @return mixed
	 */
	public function remember_before( $check, $post_id, $meta_key ) {
		$post_id = (int) $post_id;

		if ( 0 === strpos( (string) $meta_key, '_simple_seo_' ) && ! isset( $this->before[ $post_id ] ) ) {
			$this->before[ $post_id ] = logged_values( $post_id );
		}

		return $check;
	}

	/**
	 * Log the posts whose logged values changed.
	 */
	public function log_changes(): void {
		foreach ( $this->before as $post_id => $before ) {
			$post  = get_post( $post_id );
			$after = logged_values( $post_id );

			// Deleted in this request, or nothing that shows changed.
			if ( ! $post || $after === $before ) {
				continue;
			}

			$context = [
				'post_id'    => $post_id,
				'post_type'  => $post->post_type,
				'post_title' => $post->post_title,
			];

			foreach ( $after as $key => $value ) {
				if ( $value !== $before[ $key ] ) {
					$context[ "{$key}_prev" ] = $before[ $key ];
					$context[ "{$key}_new" ]  = $value;
				}
			}

			$this->info_message( 'seo_updated', $context );
		}

		$this->before = [];
	}

	/**
	 * A before/after table of the fields that changed.
	 *
	 * @param object $row Log row.
	 * @return Event_Details_Group
	 */
	public function get_log_row_details_output( $row ) {
		$group = new Event_Details_Group();
		$group->set_formatter( new Event_Details_Group_Diff_Table_Formatter() );
		$group->add_items(
			[
				new Event_Details_Item( [ 'seo_title' ], __( 'SEO title', 'simple-seo' ) ),
				new Event_Details_Item( [ 'meta_description' ], __( 'Meta description', 'simple-seo' ) ),
				new Event_Details_Item( [ 'noindex' ], __( 'Hide from search engines', 'simple-seo' ) ),
				new Event_Details_Item( [ 'menu_label' ], __( 'Menu label', 'simple-seo' ) ),
			]
		);

		return $group;
	}
}
