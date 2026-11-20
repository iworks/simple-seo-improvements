<?php
/**
 * Unit tests for robots.txt textarea handling: line endings (CRLF/LF/CR,
 * blank lines) in the allow/disallow options must produce one directive
 * per line in the generated robots.txt.
 *
 * @package simple-seo-improvements
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__, 2 ) . '/includes/iworks/simple-seo-improvements/class-iworks-robots-txt.php';

// --- stubs for the robots-txt builder ---------------------------------------

if ( ! function_exists( 'get_privacy_policy_url' ) ) {
	function get_privacy_policy_url() {
		return '';
	}
}
if ( ! function_exists( 'get_sitemap_url' ) ) {
	function get_sitemap_url( $name ) {
		return '';
	}
}
if ( ! function_exists( 'get_option' ) ) {
	// already in bootstrap, but the robots class also reads category_base/tag_base —
	// the bootstrap get_option reads the same in-memory store.
}

/**
 * Robots-txt test double with injectable options.
 */
class RobotsTxtExposed extends iworks_simple_seo_improvements_robots_txt {
	public function __construct() {
		$this->options = new FakeOptions();
	}
	protected function check_option_object() {
		// keep the injected FakeOptions — do not let the base class
		// replace it with the real framework object.
	}
	public function set_robots_option( $name, $value ) {
		$this->options->values[ $name ] = $value;
	}
	public function expose_filter( $robots = '', $public = true ) {
		return $this->filter_robots_txt_add( $robots, $public );
	}
}

final class RobotsTxtLineEndingsTest extends TestCase {

	private $robots;

	protected function setUp(): void {
		$this->robots = new RobotsTxtExposed();
	}

	/**
	 * CR, LF and CRLF line endings all split into separate directives.
	 */
	#[DataProvider( 'lineEndingsProvider' )]
	public function test_mixed_line_endings_split_into_directives( $label, $value, $expected ) {
		$this->robots->set_robots_option( 'robots_txt_disallow_all', 0 );
		$this->robots->set_robots_option( 'robots_txt_allow', $value );
		$this->robots->set_robots_option( 'robots_txt_disallow', '' );
		$out   = $this->robots->expose_filter();
		$lines = array_map(
			static function ( $line ) {
				return trim( $line );
			},
			explode( PHP_EOL, $out )
		);
		foreach ( $expected as $entry ) {
			$this->assertContains(
				'Allow: ' . $entry,
				$lines,
				"[{$label}] missing 'Allow: {$entry}' in robots.txt"
			);
		}
		// no empty Allow directives (blank lines must not produce 'Allow: ').
		$this->assertNotContains( 'Allow:', $lines, "[{$label}] blank line produced an empty Allow directive" );
	}

	public static function lineEndingsProvider(): array {
		return array(
			'unix LF'        => array( 'LF', "/a/*\n/b/*\n/c/*", array( '/a/*', '/b/*', '/c/*' ) ),
			'windows CRLF'   => array( 'CRLF', "/a/*\r\n/b/*\r\n/c/*", array( '/a/*', '/b/*', '/c/*' ) ),
			'old Mac CR'     => array( 'CR', "/a/*\r/b/*\r/c/*", array( '/a/*', '/b/*', '/c/*' ) ),
			'mixed endings'  => array( 'mixed', "/a/*\r\n/b/*\n/c/*\r/d/*", array( '/a/*', '/b/*', '/c/*', '/d/*' ) ),
			'blank lines'    => array( 'blank', "/a/*\n\n\n/b/*", array( '/a/*', '/b/*' ) ),
			'trailing break' => array( 'trailing', "/a/*\n/b/*\n", array( '/a/*', '/b/*' ) ),
		);
	}

	/**
	 * Disallow textarea: same splitting guarantees.
	 */
	public function test_disallow_split_on_crlf() {
		$this->robots->set_robots_option( 'robots_txt_disallow_all', 0 );
		$this->robots->set_robots_option( 'robots_txt_disallow', "/x/*\r\n/y/*" );
		$this->robots->set_robots_option( 'robots_txt_allow', '' );
		$out   = $this->robots->expose_filter();
		$lines = explode( PHP_EOL, $out );
		$this->assertContains( 'Disallow: /x/*', $lines );
		$this->assertContains( 'Disallow: /y/*', $lines );
	}

	/**
	 * Windows line endings must not end up inside a directive value
	 * (the \r would corrupt the path).
	 */
	public function test_no_raw_carriage_returns_in_output() {
		$this->robots->set_robots_option( 'robots_txt_disallow_all', 0 );
		$this->robots->set_robots_option( 'robots_txt_allow', "/a/*\r\n/b/*" );
		$this->robots->set_robots_option( 'robots_txt_disallow', '' );
		$out = $this->robots->expose_filter();
		$this->assertStringNotContainsString( "Allow: /a/*\rAllow", $out, 'CR leaked into directive value' );
		$this->assertStringNotContainsString( "\r:", $out, 'CR stuck to a directive boundary' );
	}

	/**
	 * Duplicate entries (after splitting) are emitted only once.
	 */
	public function test_duplicates_removed() {
		$this->robots->set_robots_option( 'robots_txt_disallow_all', 0 );
		$this->robots->set_robots_option( 'robots_txt_allow', "/a/*\n/a/*\n/a/*" );
		$this->robots->set_robots_option( 'robots_txt_disallow', '' );
		$out    = $this->robots->expose_filter();
		$this->assertSame( 1, substr_count( $out, 'Allow: /a/*' ), 'duplicate Allow entries not deduplicated' );
	}

	/**
	 * disallow_all option short-circuits: textarea content ignored.
	 */
	public function test_disallow_all_short_circuits() {
		$this->robots->set_robots_option( 'robots_txt_disallow_all', 1 );
		$this->robots->set_robots_option( 'robots_txt_allow', "/a/*\n/b/*" );
		$this->robots->set_robots_option( 'robots_txt_disallow', '' );
		$out = $this->robots->expose_filter( 'original-robots' );
		$this->assertSame( 'original-robots', $out, 'disallow_all must leave robots.txt untouched' );
	}
}
