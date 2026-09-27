<?php
/**
 * CMS Tree Page View: our rows in its page card, and the page tree link under our Simple
 * History events.
 *
 * CMS Tree Page View isn't installed in wp-env. Its filter is applied by hand, and a stand-in
 * for its Menu class gives the tree URL (empty unless a test sets one, so other tests see no
 * page tree).
 *
 * @package SimpleSEO
 */

namespace CMS_Tree_Page_View\Admin {
	if ( ! class_exists( __NAMESPACE__ . '\\Menu' ) ) {
		class Menu {
			/**
			 * @var string
			 */
			public static $url = '';

			public static function get_tree_view_url( $post_type ) {
				return 'page' === $post_type ? self::$url : '';
			}
		}
	}
}

namespace {

	use const SimpleSEO\DESCRIPTION_KEY;
	use const SimpleSEO\NOINDEX_KEY;
	use const SimpleSEO\TITLE_KEY;

	class CmsTreePageViewTest extends SimpleSEO_TestCase {

		public function tear_down() {
			\CMS_Tree_Page_View\Admin\Menu::$url = '';
			remove_all_filters( 'simple_seo_active_seo_plugin' );
			parent::tear_down();
		}

		/**
		 * The rows CMS Tree Page View would get for a post.
		 *
		 * @param int $post_id Post ID.
		 * @return array<int, array<string, string>>
		 */
		private function rows( int $post_id ): array {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- CMS Tree Page View's filter.
			return apply_filters( 'cms_tree_page_view_detail_rows', [], $post_id, get_post( $post_id ) );
		}

		public function test_rows_for_the_fields_in_use() {
			$page_id = self::factory()->post->create( [ 'post_type' => 'page' ] );
			update_post_meta( $page_id, TITLE_KEY, 'Our story' );
			update_post_meta( $page_id, DESCRIPTION_KEY, 'Coffee from Nacka.' );
			update_post_meta( $page_id, NOINDEX_KEY, true );
			SimpleSEO\save_menu_label( $page_id, 'About' );

			$this->assertSame(
				[
					[ 'id' => 'simple-seo-title', 'label' => 'SEO title', 'value' => 'Our story' ],
					[ 'id' => 'simple-seo-description', 'label' => 'Meta description', 'value' => 'Coffee from Nacka.' ],
					[ 'id' => 'simple-seo-noindex', 'label' => 'Search engines', 'value' => 'Discouraged' ],
					[ 'id' => 'simple-seo-menu-label', 'label' => 'Menu label', 'value' => 'About' ],
				],
				$this->rows( $page_id )
			);
		}

		public function test_no_rows_for_empty_fields() {
			$this->assertSame( [], $this->rows( self::factory()->post->create( [ 'post_type' => 'page' ] ) ) );
		}

		public function test_only_the_menu_label_when_another_seo_plugin_is_in_charge() {
			$page_id = self::factory()->post->create( [ 'post_type' => 'page' ] );
			update_post_meta( $page_id, TITLE_KEY, 'Our story' );
			SimpleSEO\save_menu_label( $page_id, 'About' );
			add_filter( 'simple_seo_active_seo_plugin', fn() => 'Other SEO' );

			$this->assertSame( [ 'Menu label' ], wp_list_pluck( $this->rows( $page_id ), 'label' ) );
		}

		public function test_other_plugins_rows_are_kept() {
			$page_id = self::factory()->post->create( [ 'post_type' => 'page' ] );
			update_post_meta( $page_id, TITLE_KEY, 'Our story' );
			$theirs = [ 'id' => 'theirs', 'label' => 'Theirs', 'value' => 'x' ];

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- CMS Tree Page View's filter.
			$rows = apply_filters( 'cms_tree_page_view_detail_rows', [ $theirs ], $page_id, get_post( $page_id ) );

			$this->assertSame( [ 'Theirs', 'SEO title' ], wp_list_pluck( $rows, 'label' ) );
		}

		public function test_page_tree_link_under_our_events() {
			$logger  = Simple_History\Simple_History::get_instance()->get_instantiated_logger_by_slug( 'SimpleSEOLogger' );
			$page_id = self::factory()->post->create( [ 'post_type' => 'page' ] );
			$row     = (object) [ 'context' => [ 'post_id' => $page_id, 'post_type' => 'page' ] ];
			wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
			\CMS_Tree_Page_View\Admin\Menu::$url = admin_url( 'edit.php?post_type=page&page=cms-tpv-page-page' );

			$links = $logger->get_action_links( $row );

			$this->assertSame( [ 'Edit page', 'View page', 'Page tree', 'All pages' ], wp_list_pluck( $links, 'label' ) );
			$this->assertSame( admin_url( 'edit.php?post_type=page&page=cms-tpv-page-page&selected=' . $page_id ), $links[2]['url'] );

			// No tree for posts, and none for a trashed page.
			$post_id = self::factory()->post->create();
			$this->assertNotContains( 'Page tree', wp_list_pluck( $logger->get_action_links( (object) [ 'context' => [ 'post_id' => $post_id ] ] ), 'label' ) );
			wp_trash_post( $page_id );
			$this->assertNotContains( 'Page tree', wp_list_pluck( $logger->get_action_links( $row ), 'label' ) );
		}
	}
}
