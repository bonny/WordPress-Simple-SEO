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
use Simple_History\Event_Details\Event_Details_Item_Image_Diff_Table_Row_Formatter;

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
			'description' => __( 'Logs changes to the SEO title, meta description, "Discourage search engines", menu label and default share image.', 'simple-seo' ),
			'name_via'    => __( 'Using plugin Simple SEO', 'simple-seo' ),
			'capability'  => 'edit_posts',
			'messages'    => [
				'seo_updated'         => __( 'Updated the SEO for "{post_title}"', 'simple-seo' ),
				'share_image_set'     => __( 'Set a default share image', 'simple-seo' ),
				'share_image_changed' => __( 'Changed the default share image', 'simple-seo' ),
				'share_image_removed' => __( 'Removed the default share image', 'simple-seo' ),
			],
			'labels'      => [
				'search' => [
					'label'     => __( 'SEO', 'simple-seo' ),
					'label_all' => __( 'All SEO changes', 'simple-seo' ),
					'options'   => [
						__( 'SEO changes', 'simple-seo' ) => [ 'seo_updated' ],
						__( 'Default share image', 'simple-seo' ) => [ 'share_image_set', 'share_image_changed', 'share_image_removed' ],
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

		add_action( 'add_option_' . SHARE_IMAGE_OPTION, [ $this, 'on_share_image_added' ], 10, 2 );
		add_action( 'update_option_' . SHARE_IMAGE_OPTION, [ $this, 'on_share_image_updated' ], 10, 2 );
		add_action( 'delete_option', [ $this, 'on_delete_option' ] );
	}

	/**
	 * The default share image was set for the first time.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  New value.
	 */
	public function on_share_image_added( $option, $value ): void {
		$this->log_share_image( [], (array) $value );
	}

	/**
	 * The default share image changed. Only logged when the image itself changed, not when
	 * its stored details were refreshed after an edit in the media library.
	 *
	 * @param mixed $old_value Old value.
	 * @param mixed $value     New value.
	 */
	public function on_share_image_updated( $old_value, $value ): void {
		$this->log_share_image( (array) $old_value, (array) $value );
	}

	/**
	 * The default share image option is about to be deleted (by hand, WP-CLI or another plugin; the plugin itself stores [] instead).
	 *
	 * @param string $option Option name.
	 */
	public function on_delete_option( $option ): void {
		if ( SHARE_IMAGE_OPTION === $option ) {
			$this->log_share_image( share_image(), [] );
		}
	}

	/**
	 * Log a change of the default share image, if the image changed.
	 *
	 * @param array<string, mixed> $before Old value (id, url, …), or [].
	 * @param array<string, mixed> $after  New value, or [].
	 */
	private function log_share_image( array $before, array $after ): void {
		$old_id = (int) ( $before['id'] ?? 0 );
		$new_id = (int) ( $after['id'] ?? 0 );

		if ( $old_id === $new_id ) {
			return;
		}

		$message = $new_id ? ( $old_id ? 'share_image_changed' : 'share_image_set' ) : 'share_image_removed';

		$this->info_message(
			$message,
			[
				'share_image_prev'    => (string) ( $before['url'] ?? '' ),
				'share_image_new'     => (string) ( $after['url'] ?? '' ),
				'share_image_prev_id' => $old_id,
				'share_image_new_id'  => $new_id,
			]
		);
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
	 * Links under the event, like Simple History's own post events (Simple History 5.24+, older
	 * versions don't call this): edit and view the post, and the list of that post type.
	 * No revisions link: meta changes don't create revisions.
	 *
	 * @param object $row Log row.
	 * @return array<int, array{url: string, label: string, action: string}>
	 */
	public function get_action_links( $row ) {
		if ( 0 === strpos( (string) ( $row->context['_message_key'] ?? '' ), 'share_image_' ) ) {
			return current_user_can( 'manage_options' )
				? [
					[
						'url'    => admin_url( 'options-general.php' ),
						'label'  => __( 'General settings', 'simple-seo' ),
						'action' => 'view',
					],
				]
				: [];
		}

		$post_id   = (int) ( $row->context['post_id'] ?? 0 );
		$post      = $post_id ? get_post( $post_id ) : null;
		$post_type = get_post_type_object( $post ? $post->post_type : ( $row->context['post_type'] ?? '' ) );
		$links     = [];

		if ( $post && $post_type && current_user_can( 'edit_post', $post_id ) ) {
			$links[] = [
				'url'    => (string) get_edit_post_link( $post_id, 'raw' ),
				/* translators: %s: post type, like "page" or "post". */
				'label'  => sprintf( __( 'Edit %s', 'simple-seo' ), strtolower( $post_type->labels->singular_name ) ),
				'action' => 'edit',
			];
		}

		if ( $post && $post_type && 'publish' === get_post_status( $post ) ) {
			$links[] = [
				'url'    => (string) get_permalink( $post ),
				/* translators: %s: post type, like "page" or "post". */
				'label'  => sprintf( __( 'View %s', 'simple-seo' ), strtolower( $post_type->labels->singular_name ) ),
				'action' => 'view',
			];
		}

		if ( $post_type && current_user_can( $post_type->cap->edit_posts ) ) {
			$links[] = [
				'url'    => admin_url( 'edit.php?post_type=' . $post_type->name ),
				/* translators: %s: post type in plural, like "pages" or "posts". */
				'label'  => sprintf( __( 'All %s', 'simple-seo' ), strtolower( $post_type->labels->name ) ),
				'action' => 'view',
			];
		}

		return array_values( array_filter( $links, fn( $link ) => '' !== $link['url'] ) );
	}

	/**
	 * A before/after table of the fields that changed.
	 *
	 * @param object $row Log row.
	 * @return Event_Details_Group
	 */
	public function get_log_row_details_output( $row ) {
		if ( 0 === strpos( (string) ( $row->context['_message_key'] ?? '' ), 'share_image_' ) ) {
			return $this->share_image_details( $row->context );
		}

		$group = new Event_Details_Group();
		$group->set_formatter( new Event_Details_Group_Diff_Table_Formatter() );
		$group->add_items(
			[
				new Event_Details_Item( [ 'seo_title' ], __( 'SEO title', 'simple-seo' ) ),
				new Event_Details_Item( [ 'meta_description' ], __( 'Meta description', 'simple-seo' ) ),
				new Event_Details_Item( [ 'noindex' ], __( 'Discourage search engines', 'simple-seo' ) ),
				new Event_Details_Item( [ 'menu_label' ], __( 'Menu label', 'simple-seo' ) ),
			]
		);

		return $group;
	}

	/**
	 * The old and new share image side by side (Simple History 5.32+), or their URLs.
	 *
	 * @param array<string, mixed> $context Event context.
	 * @return Event_Details_Group
	 */
	private function share_image_details( array $context ) {
		$item  = new Event_Details_Item( [ 'share_image' ], __( 'Default share image', 'simple-seo' ) );
		$group = new Event_Details_Group();

		// The image formatter draws its own diff row, like Simple History's site icon event.
		if ( class_exists( Event_Details_Item_Image_Diff_Table_Row_Formatter::class ) ) {
			$new = (string) ( $context['share_image_new'] ?? '' );
			$old = (string) ( $context['share_image_prev'] ?? '' );

			$formatter = new Event_Details_Item_Image_Diff_Table_Row_Formatter();
			$formatter->set_new_image( $new, wp_basename( $new ) );
			$formatter->set_prev_image( $old, wp_basename( $old ) );
			$formatter->set_size( 'small' ); // The default size overflows the diff cells.
			$item->set_formatter( $formatter );
		} else {
			$group->set_formatter( new Event_Details_Group_Diff_Table_Formatter() );
		}

		$group->add_items( [ $item ] );

		return $group;
	}
}
