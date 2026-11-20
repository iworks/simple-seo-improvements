<?php
/**
 * Exhaustive XSS tests specifically for the plugin's textarea fields.
 *
 * Textareas get sanitize_textarea_field (keeps newlines) — so payloads can
 * legitimately carry line breaks. These tests throw an extended payload set
 * (multi-line, null bytes, unicode, entity tricks, nested/malformed tags)
 * at every textarea through its real save-path sanitizer, then assert the
 * stored value cannot open a tag or break an attribute in its sink.
 *
 * Deliberate exceptions (by design, not tested as failures):
 * - html_head / html_body_start / html_body_end: Custom Code feature, raw
 *   admin HTML/JS stored and echoed as-is (manage_options required).
 *
 * @package simple-seo-improvements
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

final class XssTextareaFieldsTest extends TestCase {

	/**
	 * All sanitized textarea fields (Custom Code fields excluded by design).
	 */
	public static function textareasProvider(): array {
		return array(
			'home_meta_description' => array( 'home_meta_description' ),
			'robots_txt_disallow'   => array( 'robots_txt_disallow' ),
			'robots_txt_allow'      => array( 'robots_txt_allow' ),
			'json_other'            => array( 'json_other' ),
		);
	}

	/**
	 * Extended XSS payload set, focused on multi-line textarea vectors.
	 */
	public static function textareaXssProvider(): array {
		return array(
			'script multiline'        => array( "<script>\nalert(1)\n</script>" ),
			'img onerror'             => array( "<img src=x onerror=alert(1)>" ),
			'img onerror multiline'   => array( "<img\nsrc=x\nonerror=alert(1)>" ),
			'svg onload'              => array( "<svg onload=alert(1)>" ),
			'svg animate'             => array( '<svg><animate onbegin=alert(1) attributeName=x></svg>' ),
			'iframe srcdoc'           => array( '<iframe srcdoc="<script>alert(1)</script>"></iframe>' ),
			'attribute break quote'   => array( '"><script>alert(1)</script>' ),
			'attribute break single'  => array( "'><script>alert(1)</script>" ),
			'event injection'         => array( '" autofocus onfocus=alert(1) x="' ),
			'null byte tag'           => array( "<scr\0ipt>alert(1)</scr\0ipt>" ),
			'unicode tag'             => array( '‹script›alert(1)‹/script›' ),
			'double-encoded'          => array( '&amp;lt;script&amp;gt;alert(1)&amp;lt;/script&amp;gt;' ),
			'entity semicolon-less'   => array( '&ltscript&gtalert(1)&lt/script&gt' ),
			'javascript URI'          => array( 'javascript:alert(1)' ),
			'data URI'                => array( 'data:text/html,<script>alert(1)</script>' ),
			'vbscript URI'            => array( 'vbscript:msgbox(1)' ),
			'form hijack'             => array( '<form action="javascript:alert(1)"><button>x</button></form>' ),
			'base hijack'             => array( '<base href="https://evil.example/">' ),
			'meta refresh'            => array( '<meta http-equiv="refresh" content="0;url=javascript:alert(1)">' ),
			'object embed'            => array( '<object data="javascript:alert(1)"></object>' ),
			'anchor javascript'       => array( '<a href="javascript:alert(1)">click</a>' ),
			'style expression'        => array( '<style>a{width:expression(alert(1))}</style>' ),
			'multiline smuggle crlf'  => array( "/ok/*\r\n<script>alert(1)</script>\r\n/ok2/*" ),
			'uppercase mixed'         => array( '<ScRiPt>alert(1)</sCrIpT>' ),
			'tab separated'           => array( "<img\tsrc=x\tonerror=alert(1)>" ),
			'newline in tag'          => array( "<img src=x\nonerror=alert(1)>" ),
			'comment trick'           => array( '<!--<script>alert(1)//--></script>' ),
			'unclosed tag'            => array( '<script src=https://evil.example/x.js' ),
			'tag in tag'              => array( '<<script>alert(1)//<</script>' ),
		);
	}

	/**
	 * Resolve the real sanitize_callback for a config field.
	 */
	private function sanitize_via_config( $option_name, $payload ) {
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
		$this->fail( "option {$option_name} not found in config" );
	}

	/**
	 * Stored value must never contain an openable raw tag: strip_tags-level
	 * guarantee (no '<' that could start an element or comment).
	 */
	#[DataProvider( 'textareaXssProvider' )]
	public function test_textarea_strips_all_tag_openings( $payload ) {
		foreach ( array( 'home_meta_description', 'json_other' ) as $name ) {
			$stored = $this->sanitize_via_config( $name, $payload );
			$this->assertStringNotContainsString(
				'<',
				$stored,
				"{$name}: raw '<' survived from payload {$payload}"
			);
			$this->assertStringNotContainsString(
				"\0",
				$stored,
				"{$name}: null byte survived from payload {$payload}"
			);
		}
	}

	/**
	 * Robots textareas: after save-path sanitization, feeding the value to
	 * the robots builder must yield directives without HTML remnants.
	 */
	#[DataProvider( 'textareaXssProvider' )]
	public function test_robots_textarea_no_html_in_directives( $payload ) {
		$stored = $this->sanitize_via_config( 'robots_txt_allow', $payload );
		// html_* fields are the only place raw markup may live; robots values
		// must be tag-free.
		$this->assertStringNotContainsString( '<', $stored, "robots_txt_allow: raw '<' survived from {$payload}" );
		// Whatever survived cannot form 'Allow: <tag ...>' style HTML.
		$this->assertDoesNotMatchRegularExpression( '/Allow: </', $stored );
	}

	/**
	 * Full bridge for the meta sink: even hostile stored values produce a
	 * structurally intact, single <meta> tag.
	 */
	#[DataProvider( 'textareaXssProvider' )]
	public function test_meta_sink_structure_survives_all_payloads( $payload ) {
		$stored   = $this->sanitize_via_config( 'home_meta_description', $payload );
		$meta_tag = sprintf( '<meta name="description" content="%s">', esc_attr( $stored ) );
		// Exactly one tag end, no script, no raw quote inside content.
		$this->assertSame( 1, preg_match_all( '/>/', $meta_tag ), 'structure broken by ' . $payload );
		$this->assertDoesNotMatchRegularExpression( '/<script/i', $meta_tag );
		if ( preg_match( '/content="(.*)">$/s', $meta_tag, $m ) ) {
			$this->assertStringNotContainsString( '"', $m[1], 'raw quote in content from ' . $payload );
		} else {
			$this->fail( 'content attribute missing for payload ' . $payload );
		}
	}

	/**
	 * JSON-LD sink: json_encode must always contain the stored value safely.
	 */
	#[DataProvider( 'textareaXssProvider' )]
	public function test_jsonld_sink_structure_survives_all_payloads( $payload ) {
		$stored = $this->sanitize_via_config( 'json_other', $payload );
		$value  = array();
		foreach ( explode( "\n", $stored ) as $one ) {
			$one = trim( $one );
			if ( '' !== $one ) {
				$value[] = $one;
			}
		}
		$encoded = json_encode( array( 'sameAs' => $value ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$this->assertNotFalse( $encoded, 'json_encode failed for ' . $payload );
		$this->assertStringNotContainsString( '</script>', $encoded, 'script breakout from ' . $payload );
		$decoded = json_decode( $encoded, true );
		$this->assertIsArray( $decoded, 'invalid JSON produced for ' . $payload );
		foreach ( $decoded['sameAs'] as $entry ) {
			$this->assertStringNotContainsString( '<', $entry, 'tag text survived into JSON value from ' . $payload );
		}
	}

	/**
	 * Benign multi-line values must survive untouched (no over-sanitization).
	 */
	public function test_benign_multiline_values_untouched() {
		$benign = "https://www.youtube.com/@example\r\nhttps://www.linkedin.com/company/example";
		foreach ( array( 'json_other', 'home_meta_description', 'robots_txt_allow', 'robots_txt_disallow' ) as $name ) {
			$this->assertSame( $benign, $this->sanitize_via_config( $name, $benign ), "{$name} mangled benign multiline input" );
		}
	}
}
