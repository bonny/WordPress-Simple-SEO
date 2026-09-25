<?php
/**
 * PHPUnit bootstrap. Runs inside wp-env's tests-cli container, where the WordPress
 * test library is at /wordpress-phpunit: `npm run test:php`. Same setup as CMS Tree Page View.
 *
 * @package SimpleSEO
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/wordpress-phpunit';

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find the WordPress test library at {$_tests_dir}. Run the tests with `npm run test:php`.\n";
	exit( 1 );
}

// Yoast PHPUnit Polyfills, which the WordPress test bootstrap looks for.
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';
require_once $_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	function () {
		require dirname( __DIR__, 2 ) . '/simple-seo.php';
	}
);

require $_tests_dir . '/includes/bootstrap.php';

/**
 * Base class for our tests. The test framework unregisters all meta keys after each test
 * and `init` runs only once, so register the fields again before each test.
 */
abstract class SimpleSEO_TestCase extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		SimpleSEO\register_meta();
	}
}
