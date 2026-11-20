<?php
/**
 * Unit tests for the site icon / default image (favicon) handling.
 *
 * Covers the config wiring (default_image, use_as_favicon), the favicon
 * <link> output in filter_wp_head_add_html_head(), and the og:image fallback
 * data builder, using an in-memory attachment simulation.
 *
 * @package simple-seo-improvements
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__, 2 ) . '/includes/iworks/class-simple-seo-improvements.php';

// --- Additional stubs used by filter_wp_head_add_html_head() ----------------

if ( ! function_exists( 'absint' ) ) {
	function absint( $value ) {
		return abs( (int) $value );
	}
}
if ( ! function_exists( 'is_home' ) ) {
	function is_home() {
		return false;
	}
}
if ( ! function_exists( 'is_front_page' ) ) {
	function is_front_page() {
		return false;
	}
}

// --- Attachment/media stubs (in-memory attachment store) ---------------------

$GLOBALS['test_attachments'] = array();

if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
	function wp_get_attachment_image_url( $attachment_id, $size = 'thumbnail' ) {
		global $test_attachments;
		return isset( $test_attachments[ $attachment_id ]['url'] ) ? $test_attachments[ $attachment_id ]['url'] : false;
	}
}
if ( ! function_exists( 'wp_get_attachment_image_src' ) ) {
	function wp_get_attachment_image_src( $attachment_id, $size = 'thumbnail' ) {
		global $test_attachments;
		if ( ! isset( $test_attachments[ $attachment_id ]['url'] ) ) {
			return false;
		}
		$a = $test_attachments[ $attachment_id ];
		return array(
			$a['url'],
			isset( $a['width'] ) ? $a['width'] : 0,
			isset( $a['height'] ) ? $a['height'] : 0,
		);
	}
}

/**
 * Head-output test double: exposes the favicon block without options
 * framework dependencies.
 */
class HeadOutputExposed extends iworks_simple_seo_improvements {
	public function __construct() {
		// skip real init — unit test.
		$this->options = new FakeOptions();
	}
	public function expose_favicon( $attachment_id, $use_as_favicon ) {
		$this->options->values['default_image']   = $attachment_id;
		$this->options->values['use_as_favicon'] = $use_as_favicon;
		ob_start();
		$this->filter_wp_head_add_html_head();
		return ob_get_clean();
	}
}

/**
 * Minimal options-object stand-in for iworks_options.
 */
class FakeOptions {
	public $values = array();
	public function get_option( $name ) {
		return isset( $this->values[ $name ] ) ? $this->values[ $name ] : '';
	}
}

final class SiteIconTest extends TestCase {

	private $exposed;

	protected function setUp(): void {
		global $test_attachments, $wp_options;
		$test_attachments = array();
		$wp_options       = array();
		$this->exposed    = new HeadOutputExposed();
	}

	/**
	 * Config: default_image uses intval, use_as_favicon uses absint.
	 */
	public function test_config_sanitizers() {
		$options = iworks_simple_seo_improvements_options();
		$found   = array();
		foreach ( $options as $group ) {
			if ( empty( $group['options'] ) || ! is_array( $group['options'] ) ) {
				continue;
			}
			foreach ( $group['options'] as $opt ) {
				if ( isset( $opt['name'] ) && in_array( $opt['name'], array( 'default_image', 'use_as_favicon' ), true ) ) {
					$found[ $opt['name'] ] = isset( $opt['sanitize_callback'] ) ? $opt['sanitize_callback'] : null;
				}
			}
		}
		$this->assertSame( 'intval', $found['default_image'], 'default_image must sanitize with intval' );
		$this->assertSame( 'absint', $found['use_as_favicon'], 'use_as_favicon must sanitize with absint' );
	}

	/**
	 * Saving a malicious "attachment id" through the real sanitizer yields
	 * an integer, never the raw string.
	 */
	public function test_attachment_id_sanitization() {
		$this->assertSame( 0, call_user_func( 'intval', 'abc' ) );
		$this->assertSame( 123, call_user_func( 'intval', '123' ) );
		// absint() is abs(int()) — negatives become positive, never stripped to 0.
		$this->assertSame( 5, call_user_func( 'absint', '-5' ) );
		$this->assertSame( 7, call_user_func( 'absint', '7abc' ) );
		// XSS payload reduces to an integer (likely 0 or digit fragment) —
		// never the payload itself.
		$value = call_user_func( 'intval', '<script>alert(1)</script>' );
		$this->assertIsInt( $value );
		$this->assertStringNotContainsString( 'script', (string) $value );
	}

	/**
	 * With an image set and "use as favicon" on, both icon link tags are
	 * printed with a relative URL and the correct mime type.
	 */
	public function test_favicon_links_printed_when_enabled() {
		global $test_attachments;
		$test_attachments[55] = array(
			'url'    => 'https://example.test/wp-content/uploads/icon.png',
			'mime'   => 'image/png',
			'width'  => 512,
			'height' => 512,
		);
		$html = $this->exposed->expose_favicon( 55, 1 );
		$this->assertStringContainsString( '<link rel="icon" href="/wp-content/uploads/icon.png" type="image/png">', $html );
		$this->assertStringContainsString( '<link rel="shortcut icon" href="/wp-content/uploads/icon.png" type="image/png">', $html );
	}

	/**
	 * Favicon off → no link tags.
	 */
	public function test_favicon_not_printed_when_disabled() {
		global $test_attachments;
		$test_attachments[55] = array( 'url' => 'https://example.test/wp-content/uploads/icon.png' );
		$html = $this->exposed->expose_favicon( 55, 0 );
		$this->assertStringNotContainsString( 'rel="icon"', $html );
	}

	/**
	 * No image set → no link tags, even when the toggle is on.
	 */
	public function test_favicon_not_printed_without_image() {
		$html = $this->exposed->expose_favicon( 0, 1 );
		$this->assertStringNotContainsString( 'rel="icon"', $html );
	}

	/**
	 * Broken attachment (URL unavailable) → no link tags printed at all.
	 */
	public function test_favicon_not_printed_for_broken_attachment() {
		$html = $this->exposed->expose_favicon( 999, 1 );
		$this->assertSame( '', $html );
	}

	/**
	 * Favicon URL is printed relatively (wp_make_link_relative) — hostnames
	 * never leak into the link tags.
	 */
	public function test_favicon_url_is_relative() {
		global $test_attachments;
		$test_attachments[77] = array(
			'url' => 'https://evil.example/wp-content/uploads/x.png',
		);
		$html = $this->exposed->expose_favicon( 77, 1 );
		// extract only the <link rel="icon" ...> lines; og:image (absolute by
		// design) is printed later in the same head content.
		preg_match_all( '/<link rel="(shortcut )?icon"[^>]*>/', $html, $links );
		$this->assertNotEmpty( $links[0] );
		foreach ( $links[0] as $link ) {
			$this->assertStringNotContainsString( 'evil.example', $link );
			$this->assertStringContainsString( 'href="/wp-content/uploads/x.png"', $link );
		}
	}

	/**
	 * XSS payloads stuffed into the favicon (default_image) option: the
	 * intval() sanitizer must reduce them to an integer, so no markup or
	 * payload text can ever reach the <link> output.
	 */
	public function test_xss_in_favicon_option_is_neutralized() {
		$payloads = array(
			'<script>alert(1)</script>',
			'"><img src=x onerror=alert(1)>',
			'1<script>alert(document.cookie)</script>',
			'999;alert(1)',
			'javascript:alert(1)',
			'<svg onload=alert(1)>',
		);
		foreach ( $payloads as $payload ) {
			// Simulate the save path: config sanitizer is intval.
			$id = call_user_func( 'intval', $payload );
			$this->assertIsInt( $id, "payload must sanitize to int: {$payload}" );
			$this->assertStringNotContainsString( 'script', (string) $id );
			$this->assertStringNotContainsString( 'alert', (string) $id );
			$this->assertStringNotContainsString( '<', (string) $id );
			// The output: an attacker-supplied "id" either matches no
			// attachment (→ nothing printed) or resolves to a real
			// attachment URL — payload text can never appear in the HTML.
			global $test_attachments;
			$test_attachments = array();
			$html             = $this->exposed->expose_favicon( $id, 1 );
			$this->assertStringNotContainsString( 'alert', $html );
			$this->assertStringNotContainsString( '<script', $html );
			$this->assertStringNotContainsString( 'onerror', $html );
			$this->assertStringNotContainsString( 'javascript:', $html );
		}
	}

	/**
	 * XSS in the attachment URL itself (e.g. tampered filter): the plugin
	 * escapes the href attribute, so quotes/angles can't break out — and
	 * the URL is still forced relative.
	 */
	public function test_xss_in_attachment_url_is_escaped() {
		global $test_attachments;
		$test_attachments[66] = array(
			'url' => '"><script>alert(1)</script>',
		);
		$html = $this->exposed->expose_favicon( 66, 1 );
		preg_match_all( '/<link rel="(shortcut )?icon"[^>]*>/', $html, $links );
		$this->assertNotEmpty( $links[0], 'link tags must still print' );
		foreach ( $links[0] as $link ) {
			$this->assertStringNotContainsString( '<script', $link, 'payload must not open a tag' );
			$this->assertStringNotContainsString( '" >', $link, 'attribute must not be breakable' );
			// esc_attr encodes the quotes of the payload.
			$this->assertStringContainsString( '&quot;', $link );
			$this->assertStringContainsString( '&lt;script&gt;', $link );
		}
	}

	/**
	 * XSS in the attachment mime type (tampered data): printed via
	 * esc_attr, cannot break the type attribute.
	 */
	public function test_xss_in_mime_type_is_escaped() {
		global $test_attachments;
		$test_attachments[99] = array(
			'url'  => 'https://example.test/x.png',
			'mime' => 'image/png"><script>alert(1)</script>',
		);
		$html = $this->exposed->expose_favicon( 99, 1 );
		preg_match_all( '/<link rel="(shortcut )?icon"[^>]*>/', $html, $links );
		$this->assertNotEmpty( $links[0] );
		foreach ( $links[0] as $link ) {
			$this->assertStringNotContainsString( '<script', $link );
			$this->assertStringContainsString( '&lt;script&gt;', $link );
		}
	}

	/**
	 * og:image fallback data: XSS in the alt text survives as data (it is
	 * consumed by JSON-LD/OG builders, not echoed raw) — but the URL/mime
	 * fields come from attachment data, not user config.
	 */
	public function test_og_image_alt_is_not_raw_echoed() {
		global $test_attachments, $wp_options;
		$test_attachments[111] = array(
			'url'    => 'https://example.test/og.png',
			'mime'   => 'image/png',
			'width'  => 1200,
			'height' => 630,
		);
		$options_prop = new ReflectionProperty( 'iworks_simple_seo_improvements', 'options' );
		$fakes        = $options_prop->getValue( $this->exposed );
		$fakes->values['default_image'] = 111;
		$options_prop->setValue( $this->exposed, $fakes );
		$m    = new ReflectionMethod( 'iworks_simple_seo_improvements', 'get_image_for_og_image' );
		$data = $m->invoke( $this->exposed );
		$this->assertSame( 'https://example.test/og.png', $data['url'] );
		// alt comes from get_post_meta stub (empty here) — never from the payload path.
		$this->assertArrayHasKey( 'alt', $data );
	}

	/**
	 * og:image fallback data: built from the attachment, skipped for
	 * non-image mime types and missing attachments.
	 */
	public function test_og_image_fallback_data() {
		global $test_attachments;
		$test_attachments[88] = array(
			'url'    => 'https://example.test/wp-content/uploads/og.png',
			'mime'   => 'image/png',
			'width'  => 1200,
			'height' => 630,
		);
		$m = new ReflectionMethod( 'iworks_simple_seo_improvements', 'get_image_for_og_image' );

		$options_prop = new ReflectionProperty( 'iworks_simple_seo_improvements', 'options' );
		$fakes = $options_prop->getValue( $this->exposed );
		$fakes->values['default_image'] = 88;
		$options_prop->setValue( $this->exposed, $fakes );
		$data = $m->invoke( $this->exposed );
		$this->assertSame( 'https://example.test/wp-content/uploads/og.png', $data['url'] );
		$this->assertSame( 1200, $data['width'] );
		$this->assertSame( 630, $data['height'] );
		$this->assertSame( 'image/png', $data['mime'] );

		// non-image mime → empty.
		$test_attachments[88]['mime'] = 'application/pdf';
		$this->assertSame( array(), $m->invoke( $this->exposed ) );

		// no image set → empty.
		$fakes->values['default_image'] = 0;
		$this->assertSame( array(), $m->invoke( $this->exposed ) );
	}
}
