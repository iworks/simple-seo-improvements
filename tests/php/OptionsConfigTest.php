<?php
/**
 * Unit tests for options config helpers (etc/options.php).
 *
 * @package simple-seo-improvements
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

final class OptionsConfigTest extends TestCase {

	public function test_robots_txt_allow_defaults_contain_expected_paths() {
		$value = iworks_simple_seo_improvements_robots_txt_allow();
		$lines = explode( PHP_EOL, $value );
		$this->assertContains( '/*/*.css', $lines );
		$this->assertContains( '/wp-admin/admin-ajax.php', $lines );
		$this->assertContains( '/wp-content/uploads/*', $lines );
	}

	public function test_robots_txt_allow_is_newline_separated() {
		$value = iworks_simple_seo_improvements_robots_txt_allow();
		$this->assertGreaterThan( 1, substr_count( $value, PHP_EOL ) );
	}

	public function test_deprecated_robots_alias_returns_same_value() {
		$this->assertSame(
			iworks_simple_seo_improvements_robots_txt_allow(),
			simple_seo_improvements_robots_txt_allow()
		);
		$this->assertSame(
			iworks_simple_seo_improvements_robots_txt_disallow(),
			simple_seo_improvements_robots_txt_disallow()
		);
	}

	public function test_no_sanitization_passthrough() {
		$this->assertSame( '<b>x</b>', iworks_seo_improvements_no_sanitization( '<b>x</b>' ) );
	}
}
