<?php
/**
 * Unit tests for the webmaster tools verification code sanitizer.
 *
 * @package simple-seo-improvements
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

final class WmtSanitizerTest extends TestCase {

	public function test_keeps_alphanumeric_dashes_underscores() {
		$this->assertSame( 'AbC-123_xyZ', iworks_ssi_sanitize_wmt_code( 'AbC-123_xyZ' ) );
	}

	public function test_strips_html_tags_but_keeps_word_chars() {
		// The sanitizer keeps [A-Za-z0-9_-] only — it does NOT parse HTML,
		// so tag names/attributes survive as word characters. That's fine:
		// the value is printed via esc_attr() in the meta tag output.
		$this->assertSame( 'metanamegoogle-site-verificationcontentcode123', iworks_ssi_sanitize_wmt_code( '<meta name="google-site-verification" content="code123">' ) );
	}

	public function test_strips_script_special_chars() {
		$this->assertSame( 'abcscriptalert1script', iworks_ssi_sanitize_wmt_code( 'abc"><script>alert(1)</script>' ) );
	}

	public function test_handles_null_and_int() {
		$this->assertSame( '', iworks_ssi_sanitize_wmt_code( null ) );
		$this->assertSame( '123', iworks_ssi_sanitize_wmt_code( 123 ) );
	}

	public function test_empty_string() {
		$this->assertSame( '', iworks_ssi_sanitize_wmt_code( '' ) );
	}
}
