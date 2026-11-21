<?php
/**
 * External links feature tests (exli:* options).
 *
 * Covers `filter_replace()` end-to-end through WP_HTML_Tag_Processor:
 * - exli:rel:nofollow adds rel="nofollow" to external links only
 * - exli:target:blank adds target="blank" (existing target preserved)
 * - exli:class adds the configured class(es)
 * - internal links (same host, optional www) are never touched
 * - XSS: a hostile exli:class value is esc_attr()'d at composition and
 *   re-escaped by the HTML API -- no tag breakout possible.
 *
 * @package simple-seo-improvements
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
}
require_once dirname( __DIR__, 2 ) . '/includes/iworks/options/options.php';
require_once dirname( __DIR__, 2 ) . '/includes/iworks/simple-seo-improvements/class-iworks-simple-seo-improvements-base-abstract.php';
require_once dirname( __DIR__, 2 ) . '/includes/iworks/simple-seo-improvements/class-iworks-simple-seo-improvements-links.php';

/**
 * Test double: exposes filter_replace, skips constructor hooks, and
 * injects a FakeOptions object. get_site_url()/wp_parse_url() stubs are
 * provided via a shared helper (guarded for double-require).
 */
/**
 * Options stand-in extending the real iworks_options so that
 * check_option_object() keeps it (is_a check) -- overrides the accessors
 * to read the $values array.
 */
class ExliFakeOptions extends iworks_options {
	public $values = array();
	public function __construct() {
		// Skip the heavy parent constructor -- unit test stand-in.
	}
	public function get_option( $option_name, $option_group = 'index', $default_value = null, $forece_default = false ) {
		return isset( $this->values[ $option_name ] ) ? $this->values[ $option_name ] : '';
	}
	public function get_group( $option_group = null ) {
		return array();
	}
}

class ExternalLinksExposed extends iworks_simple_seo_improvements_links {
	public function __construct() {
		// Skip real constructor (hooks, iworks object) -- unit test.
		$prop = new ReflectionProperty( 'iworks_simple_seo_improvements_base_abstract', 'options' );
		$prop->setValue( $this, new ExliFakeOptions() );
	}
	public function get_options_object() {
		$prop = new ReflectionProperty( 'iworks_simple_seo_improvements_base_abstract', 'options' );
		return $prop->getValue( $this );
	}
	public function expose_filter_replace( $content ) {
		return $this->filter_replace( $content );
	}
}

if ( ! function_exists( 'get_site_url' ) ) {
	function get_site_url() {
		return 'https://wordpress.test';
	}
}
if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url ) {
		return parse_url( $url );
	}
}

final class ExternalLinksTest extends TestCase {

	private $links;

	protected function setUp(): void {
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$api = '/var/virtuals/wordpress/wp-includes/html-api/';
			foreach ( array(
				'class-wp-html-attribute-token.php',
				'class-wp-html-text-replacement.php',
				'class-wp-html-span.php',
				'class-wp-html-decoder.php',
				'class-wp-html-doctype-info.php',
				'class-wp-html-tag-processor.php',
			) as $f ) {
				require_once $api . $f;
			}
		}
		if ( ! function_exists( 'wp_has_noncharacters' ) ) {
			require_once '/var/virtuals/wordpress/wp-includes/utf8.php';
		}
		if ( ! function_exists( 'wp_kses_uri_attributes' ) ) {
			/**
			 * Stub: tests only set rel/target/class -- no URI attributes.
			 */
			function wp_kses_uri_attributes() {
				return array( 'href', 'src', 'action' );
			}
		}
		if ( ! function_exists( 'esc_url' ) ) {
			function esc_url( $url ) {
				return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
			}
		}
		$this->links = new ExternalLinksExposed();
	}

	private function set_options( $nofollow = 0, $target = 0, $class = '' ) {
		$prop = new ReflectionProperty( 'iworks_simple_seo_improvements_base_abstract', 'options' );
		$fakes = $prop->getValue( $this->links );
		$fakes->values['exli:rel:nofollow'] = $nofollow;
		$fakes->values['exli:target:blank'] = $target;
		$fakes->values['exli:class']        = $class;
		$prop->setValue( $this->links, $fakes );
	}

	private function replace( $content ) {
		return $this->links->expose_filter_replace( $content );
	}

	public function test_nofollow_added_to_external_links_only() {
		$this->set_options( 1 );
		$in  = '<a href="https://example.com/x">out</a> <a href="https://wordpress.test/in">in</a>';
		$out = $this->replace( $in );
		$this->assertStringContainsString( 'rel="nofollow"', $out );
		$this->assertSame( 1, preg_match_all( '/rel="nofollow"/', $out ) );
		$this->assertStringContainsString( 'href="https://wordpress.test/in"', $out );
		// Internal link unchanged (no rel).
		$this->assertDoesNotMatchRegularExpression(
			'/href="https:\/\/wordpress\.test\/in"[^>]*rel=/',
			$out
		);
	}

	public function test_existing_rel_not_overwritten() {
		$this->set_options( 1 );
		$out = $this->replace( '<a href="https://example.com/x" rel="external noopener">x</a>' );
		$this->assertStringContainsString( 'rel="external noopener"', $out );
		$this->assertDoesNotMatchRegularExpression( '/rel="nofollow"/', $out );
	}

	public function test_target_blank_added_and_existing_target_preserved() {
		$this->set_options( 0, 1 );
		$out = $this->replace(
			'<a href="https://example.com/a">a</a> <a href="https://example.com/b" target="_self">b</a>'
		);
		$this->assertSame( 1, preg_match_all( '/target="blank"/', $out ) );
		$this->assertStringContainsString( 'target="_self"', $out );
	}

	public function test_custom_class_added() {
		$this->set_options( 0, 0, 'external-link my-class' );
		$out = $this->replace( '<a href="https://example.com/x">x</a>' );
		$this->assertStringContainsString( 'class="external-link my-class"', $out );
	}

	public function test_www_internal_link_not_touched() {
		$this->set_options( 1, 1, 'external' );
		$out = $this->replace( '<a href="https://www.wordpress.test/in">in</a>' );
		$this->assertStringNotContainsString( 'nofollow', $out );
		$this->assertStringNotContainsString( 'target=', $out );
		$this->assertStringNotContainsString( 'class=', $out );
	}

	/**
	 * XSS: hostile class value survives storage entity-encoded (esc_html in
	 * config) and is esc_attr()'d again in filter_replace -- the HTML API
	 * stores it fully encoded; no raw markup can reach the attribute.
	 */
	public function test_hostile_class_cannot_break_out() {
		$this->set_options( 0, 0, 'external"><script>alert(1)</script>' );
		$out = $this->replace( '<a href="https://example.com/x">x</a>' );
		// No raw tag may appear inside the class attribute.
		$this->assertDoesNotMatchRegularExpression( '/class="[^"]*</', $out );
		$this->assertStringNotContainsString( '<script>', $out );
		// The attribute must stay intact: exactly one class attribute.
		$this->assertSame( 1, preg_match_all( '/class="/', $out ) );
		// And the stored/escaped payload must be inert when re-parsed:
		// no quote inside the attribute value (no attribute smuggling).
		$this->assertSame( 1, preg_match( '/class="([^"]*)"/', $out, $m ) );
		$this->assertStringNotContainsString( '"', $m[1] );
	}

	/**
	 * Attribute-smuggling payloads in the class value stay encoded.
	 */
	public function test_class_attribute_smuggling_neutralized() {
		$this->set_options( 0, 0, '" onmouseover="alert(1)' );
		$out = $this->replace( '<a href="https://example.com/x">x</a>' );
		$this->assertStringNotContainsString( 'onmouseover="alert', $out );
		$this->assertDoesNotMatchRegularExpression( '/class="[^"]*"[^>]*onmouseover/', $out );
	}

	/**
	 * Content without anchors passes through untouched.
	 */
	public function test_no_anchors_means_no_changes() {
		$this->set_options( 1, 1, 'external' );
		$in = '<p>no links here</p>';
		$this->assertSame( $in, $this->replace( $in ) );
	}

	/**
	 * Relative hrefs are skipped (only http* links are candidates).
	 */
	public function test_relative_links_skipped() {
		$this->set_options( 1, 1, 'external' );
		$in = '<a href="/relative">r</a> <a href="#anchor">a</a>';
		$this->assertSame( $in, $this->replace( $in ) );
	}
}
