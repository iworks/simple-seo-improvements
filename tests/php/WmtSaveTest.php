<?php
/**
 * Unit tests for Webmaster Tools verification code saving and output.
 *
 * Covers the option config (sanitize_callback wiring) and the wp-head
 * meta tag generation, simulating the save path the options framework
 * uses (apply sanitize_callback, then store).
 *
 * @package simple-seo-improvements
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8', false );
	}
}
if ( ! function_exists( '_x' ) ) {
	function _x( $text, $context, $domain = 'default' ) {
		return $text;
	}
}

final class WmtSaveTest extends TestCase {

	/**
	 * In-memory options store simulating the framework save:
	 * sanitize_callback applied, then value stored.
	 */
	private function save_code( $option_name, $raw_value ) {
		global $wp_options;
		$config = $this->get_wmt_config( $option_name );
		$this->assertNotNull( $config, "option {$option_name} must exist in etc/options.php config" );
		$sanitized = call_user_func( $config['sanitize_callback'], $raw_value );
		$wp_options[ 'iworks_ssi_' . $option_name ] = $sanitized;
		return $sanitized;
	}

	/**
	 * Extract one wmt_* option definition from etc/options.php structure.
	 */
	private function get_wmt_config( $option_name ) {
		$options = iworks_simple_seo_improvements_options();
		foreach ( $options as $group ) {
			if ( empty( $group['options'] ) || ! is_array( $group['options'] ) ) {
				continue;
			}
			foreach ( $group['options'] as $opt ) {
				if ( isset( $opt['name'] ) && $opt['name'] === $option_name ) {
					return $opt;
				}
			}
		}
		return null;
	}

	public static function wmtServicesProvider(): array {
		return array(
			'google'    => array( 'wmt_google', 'google-site-verification' ),
			'bing'      => array( 'wmt_bing', 'msvalidate.01' ),
			'yandex'    => array( 'wmt_yandex', 'yandex-verification' ),
			'pinterest' => array( 'wmt_pinterest', 'p:domain_verify' ),
		);
	}

	#[DataProvider( 'wmtServicesProvider' )]
	public function test_config_declares_wmt_sanitizer( $option_name, $meta_name ) {
		$config = $this->get_wmt_config( $option_name );
		$this->assertNotNull( $config, "{$option_name} missing from options config" );
		$this->assertSame( 'iworks_ssi_sanitize_wmt_code', $config['sanitize_callback'], "{$option_name} must use iworks_ssi_sanitize_wmt_code" );
	}

	#[DataProvider( 'wmtServicesProvider' )]
	public function test_save_sanitizes_code( $option_name, $meta_name ) {
		$stored = $this->save_code( $option_name, 'Xk8dF9-_"x><script>' );
		$this->assertSame( 'Xk8dF9-_xscript', $stored, "{$option_name} should store only [A-Za-z0-9_-]" );
	}

	#[DataProvider( 'wmtServicesProvider' )]
	public function test_saved_code_roundtrip_via_get_option( $option_name, $meta_name ) {
		$this->save_code( $option_name, 'AbC-123_xyZ' );
		$this->assertSame( 'AbC-123_xyZ', get_option( 'iworks_ssi_' . $option_name ) );
	}

	#[DataProvider( 'wmtServicesProvider' )]
	public function test_saved_code_strips_injection( $option_name, $meta_name ) {
		$stored = $this->save_code( $option_name, '"><script>alert(document.cookie)</script>' );
		$this->assertSame( 'scriptalertdocumentcookiescript', $stored );
	}

	/**
	 * Regression: robots.txt textareas must keep line endings on save.
	 *
	 * The options framework defaults textareas to sanitize_text_field, which
	 * converts newlines to spaces and destroyed the one-entry-per-line format.
	 * Both robots textareas must declare sanitize_textarea_field instead.
	 */
	public function test_robots_textareas_preserve_line_endings_on_save() {
		foreach ( array( 'robots_txt_allow', 'robots_txt_disallow' ) as $name ) {
			$config = $this->get_wmt_config( $name );
			$this->assertNotNull( $config, "{$name} missing from options config" );
			$this->assertSame(
				'sanitize_textarea_field',
				isset( $config['sanitize_callback'] ) ? $config['sanitize_callback'] : null,
				"{$name} must use sanitize_textarea_field (sanitize_text_field destroys newlines)"
			);
			// Behavior: newlines survive the sanitizer.
			$saved = call_user_func( $config['sanitize_callback'], "/a/*\r\n/b/*\n/c/*" );
			$this->assertSame( "/a/*\r\n/b/*\n/c/*", $saved, "{$name}: line endings lost on save" );
		}
	}

	/**
	 * Regression: no output must be produced for empty WMT fields.
	 *
	 * The head builder skips every service whose option is empty — no empty
	 * <meta name="google-site-verification" content=""> tags.
	 */
	public function test_no_output_for_empty_wmt_fields() {
		global $test_post_meta, $test_attachments;
		$test_post_meta   = array();
		$test_attachments = array();

		$exposed = new HeadOutputExposed();
		// All WMT options unset → FakeOptions returns '' → all skipped.
		$html = $exposed->expose_favicon( 0, 0 );
		foreach ( array( 'google-site-verification', 'msvalidate.01', 'yandex-verification', 'p:domain_verify' ) as $meta_name ) {
			$this->assertStringNotContainsString(
				$meta_name,
				$html,
				"empty wmt field still produced a meta tag for {$meta_name}"
			);
		}
	}

	/**
	 * Only services with a non-empty saved code produce meta tags.
	 */
	public function test_only_non_empty_wmt_fields_produce_output() {
		$exposed = new HeadOutputExposed();
		$prop    = new ReflectionProperty( 'iworks_simple_seo_improvements', 'options' );
		$fakes   = $prop->getValue( $exposed );
		$fakes->values['wmt_google'] = 'AbC-123';
		$prop->setValue( $exposed, $fakes );
		// bing/yandex/pinterest left empty.
		$html = $exposed->expose_favicon( 0, 0 );
		$this->assertStringContainsString( '<meta name="google-site-verification" content="AbC-123">', $html );
		$this->assertStringNotContainsString( 'msvalidate.01', $html );
		$this->assertStringNotContainsString( 'yandex-verification', $html );
		$this->assertStringNotContainsString( 'p:domain_verify', $html );
	}

	/**
	 * A whitespace-only code sanitizes to '' and therefore produces no output.
	 */
	public function test_whitespace_only_code_produces_no_output() {
		$this->assertSame( '', iworks_ssi_sanitize_wmt_code( '   ' ) );
		$exposed = new HeadOutputExposed();
		// simulating a save that stored the sanitized '' value:
		$prop    = new ReflectionProperty( 'iworks_simple_seo_improvements', 'options' );
		$fakes   = $prop->getValue( $exposed );
		$fakes->values['wmt_google'] = iworks_ssi_sanitize_wmt_code( '   ' );
		$prop->setValue( $exposed, $fakes );
		$html = $exposed->expose_favicon( 0, 0 );
		$this->assertStringNotContainsString( 'google-site-verification', $html );
	}

	/**
	 * Regression: every multi-line textarea must use sanitize_textarea_field.
	 * Covers json_other (sameAs, one URL per line) and home_meta_description.
	 */
	public function test_all_multiline_textareas_preserve_line_endings_on_save() {
		foreach ( array( 'json_other', 'home_meta_description' ) as $name ) {
			$config = $this->get_wmt_config( $name );
			$this->assertNotNull( $config, "{$name} missing from options config" );
			$this->assertSame(
				'sanitize_textarea_field',
				isset( $config['sanitize_callback'] ) ? $config['sanitize_callback'] : null,
				"{$name} must use sanitize_textarea_field (sanitize_text_field destroys newlines)"
			);
		}
	}

	public function test_save_empty_code_stores_empty() {
		$stored = $this->save_code( 'wmt_google', '   ' );
		$this->assertSame( '', $stored );
	}
}
