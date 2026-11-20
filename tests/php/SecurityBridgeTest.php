<?php
/**
 * Cross-sink security bridge tests: a value saved once through a textarea
 * option is consumed in several output contexts. Each context must render
 * the (possibly hostile) value inert for its own format:
 *
 *   - robots.txt:          raw lines, one directive per line (no newline
 *                          smuggling of extra directives)
 *   - JSON-LD:             values must survive json_encode() unbroken —
 *                          the stored value can never close the script tag
 *   - head <meta> tags:    esc_attr() must neutralize attribute escapes
 *
 * The bridge = save path (sanitize) → storage → sink (output).
 *
 * @package simple-seo-improvements
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__, 2 ) . '/includes/iworks/simple-seo-improvements/class-iworks-robots-txt.php';

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

/**
 * Shared helper: resolve the real sanitize_callback of a config field.
 */
function xss_bridge_sanitize( $option_name, $payload ) {
	foreach ( iworks_simple_seo_improvements_options() as $group ) {
		if ( empty( $group['options'] ) || ! is_array( $group['options'] ) ) {
			continue;
		}
		foreach ( $group['options'] as $opt ) {
			if ( isset( $opt['name'] ) && $opt['name'] === $option_name ) {
				$cb = isset( $opt['sanitize_callback'] ) ? $opt['sanitize_callback'] : 'sanitize_text_field';
				return call_user_func( $cb, $payload );
			}
		}
	}
	throw new RuntimeException( "option {$option_name} not found" );
}

final class SecurityBridgeTest extends TestCase {

	// ------------------------------------------------------------- robots.txt

	/**
	 * Bridge: robots textarea → robots.txt.
	 *
	 * NOTE (documented behavior): sanitize_textarea_field deliberately keeps
	 * newlines — only administrators (manage_options) can save options, so
	 * multi-line input is trusted admin intent, not an injection vector.
	 * This test pins that each input line becomes exactly one directive line
	 * and that no CR residue merges directives.
	 */
	public function test_robots_sink_lines_map_one_to_one() {
		$exposed = new RobotsTxtExposed();
		$raw     = "/safe/*\r\nSitemap: https://evil.example/sitemap.xml";
		$saved   = xss_bridge_sanitize( 'robots_txt_allow', $raw );
		$exposed->set_robots_option( 'robots_txt_disallow_all', 0 );
		$exposed->set_robots_option( 'robots_txt_allow', $saved );
		$exposed->set_robots_option( 'robots_txt_disallow', '' );
		$out = $exposed->expose_filter();
		// Every input line becomes exactly one directive line — no merging,
		// no CR residue.
		$this->assertStringContainsString( 'Allow: /safe/*', $out );
		$this->assertSame( 0, preg_match( '/Allow: [^\n]*\r/', $out ), 'CR leaked into directive' );
	}

	// ---------------------------------------------------------------- JSON-LD

	/**
	 * Bridge: json_other textarea → JSON-LD sameAs. XSS payloads stored in
	 * the option must not be able to break out of the JSON document —
	 * json_encode() escapes quotes/angles, so </script> can never appear
	 * raw in the encoded output.
	 */
	public function test_jsonld_sink_xss_cannot_break_document() {
		$payloads = array(
			'"><script>alert(1)</script>',
			'</script><script>alert(1)</script>',
			'javascript:alert(1)',
			"https://x.test/a\r\nhttps://y.test/b<script>alert(1)</script>",
		);
		foreach ( $payloads as $payload ) {
			$saved  = xss_bridge_sanitize( 'json_other', $payload );
			$sameAs = array();
			foreach ( explode( "\n", $saved ) as $one ) {
				$one = trim( $one );
				if ( '' !== $one ) {
					$sameAs[] = $one;
				}
			}
			$encoded = json_encode(
				array( 'sameAs' => $sameAs ),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			);
			$this->assertNotFalse( $encoded, 'json_encode must succeed' );
			// Raw closing of the HTML script context must never appear.
			$this->assertStringNotContainsString( '</script>', $encoded, 'payload broke out of the JSON-LD script element' );
			// The document must remain valid JSON (no injected raw markup).
			$decoded = json_decode( $encoded, true );
			$this->assertIsArray( $decoded, 'encoded value must decode back to JSON' );
			// If an attacker URL survived as a sameAs *string value*, it must
			// still be plain text inside the JSON — no unescaped quote.
			foreach ( $decoded['sameAs'] as $entry ) {
				$this->assertStringNotContainsString( '<', $entry );
			}
		}
	}

	/**
	 * Bridge: saved multi-line sameAs → one clean array of URLs.
	 */
	public function test_jsonld_sink_multiline_admin_input_ok() {
		$saved   = xss_bridge_sanitize( 'json_other', "https://a.test\r\nhttps://b.test" );
		$sameAs  = array();
		foreach ( explode( "\n", $saved ) as $one ) {
			$one = trim( $one );
			if ( '' !== $one ) {
				$sameAs[] = $one;
			}
		}
		$this->assertSame( array( 'https://a.test', 'https://b.test' ), $sameAs );
	}

	// -------------------------------------------------------------- head meta

	/**
	 * Bridge: home_meta_description textarea → <meta> attribute. The value
	 * is printed through esc_attr(); attribute-break payloads must not be
	 * able to close the content attribute or open a tag.
	 */
	public function test_head_meta_sink_attribute_escape_holds() {
		$payloads = array(
			'"><script>alert(1)</script>',
			'" onmouseover="alert(1)',
			'<script>alert(1)</script>text',
		);
		foreach ( $payloads as $payload ) {
			$saved    = xss_bridge_sanitize( 'home_meta_description', $payload );
			$meta_tag = sprintf( '<meta name="description" content="%s">', esc_attr( $saved ) );
			// The tag must remain exactly one tag: no <script> element, and
			// any event-handler text must exist only as encoded text (&quot;
			// separates it), never as a real attribute.
			$this->assertDoesNotMatchRegularExpression( '/<script/i', $meta_tag );
			// A live attribute would require a raw quote immediately before
			// the handler name; esc_attr encodes it to &quot;, leaving the
			// handler as inert text. Extract the content value and require
			// every quote inside it to be encoded.
			if ( preg_match( '/content="(.*)">$/s', $meta_tag, $m ) ) {
				$this->assertStringNotContainsString( '"', $m[1], 'raw quote inside content value' );
			} else {
				$this->fail( 'meta tag structure broken' );
			}
			// Content attribute cannot be closed early: exactly one closing
			// '>' (the tag's own), and quotes inside are always encoded.
			$this->assertSame( 1, preg_match_all( '/>/', $meta_tag ), 'meta tag structure broken by payload' );
			$this->assertSame( 4, substr_count( $meta_tag, '"' ), 'unencoded quote inside content attribute' );
		}
	}
}
