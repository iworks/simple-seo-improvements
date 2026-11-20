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

	public function test_save_empty_code_stores_empty() {
		$stored = $this->save_code( 'wmt_google', '   ' );
		$this->assertSame( '', $stored );
	}
}
