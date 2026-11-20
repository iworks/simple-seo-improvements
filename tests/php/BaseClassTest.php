<?php
/**
 * Unit tests for iworks_simple_seo_improvements_base (module base class).
 *
 * @package simple-seo-improvements
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__, 2 ) . '/includes/iworks/class-simple-seo-improvements-base.php';

final class BaseClassTest extends TestCase {

	public function test_get_meta_name_prefixes_and_slugs() {
		$c = new iworks_simple_seo_improvements_base();
		// sanitize_title() in WP strips underscores too (it maps to lowercase alphanumerics+dashes).
		$this->assertSame( '__robots-index', $c->get_meta_name( 'robots_index' ) );
		$this->assertSame( '__robots-index', $c->get_meta_name( 'robots-index' ) );
		$this->assertSame( '__title', $c->get_meta_name( 'Title' ) );
	}

	public function test_get_meta_name_sanitizes_specials() {
		$c = new iworks_simple_seo_improvements_base();
		$this->assertSame( '__my-key-2', $c->get_meta_name( 'My Key!! 2' ) );
	}

	public function test_get_version_in_non_dev_mode_returns_null_when_unset() {
		$c = new iworks_simple_seo_improvements_base();
		$this->assertNull( $c->get_version() );
	}

	public function test_get_version_dev_mode_random_but_bounded() {
		if ( ! defined( 'IWORKS_DEV_MODE' ) ) {
			define( 'IWORKS_DEV_MODE', true );
		}
		$c = new iworks_simple_seo_improvements_base();
		$v = $c->get_version();
		$this->assertIsInt( $v );
		$this->assertGreaterThanOrEqual( 0, $v );
	}
}
