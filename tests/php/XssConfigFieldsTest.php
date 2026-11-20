<?php
/**
 * XSS injection tests against plugin config fields.
 *
 * For every free-text option (config field), simulates the save path
 * (options framework sanitize_callback) with classic XSS payloads and
 * asserts no executable markup survives where it must not.
 *
 * Known, intentional exceptions (not tested here, by design):
 * - html_head / html_body_start / html_body_end are the "Custom Code"
 *   feature: admin-provided raw HTML/JS, output unescaped.
 * - json_other (sameAs) is stored via default sanitize_text_field too.
 *
 * @package simple-seo-improvements
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

final class XssConfigFieldsTest extends TestCase {

	/**
	 * Free-text config fields and their expected save-time sanitization.
	 *
	 * 'default' = the options framework fallback (sanitize_text_field).
	 */
	public static function configFieldsProvider(): array {
		return array(
			'home_meta_description' => array( 'home_meta_description', 'sanitize_text_field' ),
			'robots_txt_disallow'   => array( 'robots_txt_disallow', 'sanitize_textarea_field' ),
			'robots_txt_allow'      => array( 'robots_txt_allow', 'sanitize_textarea_field' ),
			'wmt_google'            => array( 'wmt_google', 'iworks_ssi_sanitize_wmt_code' ),
			'wmt_bing'              => array( 'wmt_bing', 'iworks_ssi_sanitize_wmt_code' ),
			'wmt_yandex'            => array( 'wmt_yandex', 'iworks_ssi_sanitize_wmt_code' ),
			'wmt_pinterest'         => array( 'wmt_pinterest', 'iworks_ssi_sanitize_wmt_code' ),
			'json_name_alt'         => array( 'json_name_alt', 'sanitize_text_field' ),
			'json_org_name'         => array( 'json_org_name', 'sanitize_text_field' ),
			'json_org_alt'          => array( 'json_org_alt', 'sanitize_text_field' ),
			'json_org_pa_st'        => array( 'json_org_pa_st', 'sanitize_text_field' ),
			'json_org_pa_l'         => array( 'json_org_pa_l', 'sanitize_text_field' ),
			'json_org_pa_r'         => array( 'json_org_pa_r', 'sanitize_text_field' ),
			'json_org_pa_pc'        => array( 'json_org_pa_pc', 'sanitize_text_field' ),
			'json_other'            => array( 'json_other', 'sanitize_text_field' ),
		);
	}

	/**
	 * Classic XSS payloads used against every field.
	 */
	public static function xssPayloadsProvider(): array {
		return array(
			'script tag'      => array( '<script>alert(1)</script>' ),
			'event handler'   => array( '<img src=x onerror=alert(1)>' ),
			'svg onload'      => array( '<svg onload=alert(1)>' ),
			'iframe'          => array( '<iframe src="javascript:alert(1)"></iframe>' ),
			'attribute break' => array( '"><script>alert(1)</script>' ),
			'javascript url'  => array( 'javascript:alert(1)' ),
			'quoted handler'  => array( '<img src="x" onerror="alert(String.fromCharCode(88))">' ),
			'encoded script'  => array( '&lt;script&gt;alert(1)&lt;/script&gt;' ),
			'style injection' => array( '<style>body{background:url(javascript:alert(1))}</style>' ),
		);
	}

	/**
	 * Resolve the sanitize_callback an option field actually uses on save:
	 * explicit one from config, or the framework default sanitize_text_field.
	 */
	private function get_sanitize_callback( $option_name ) {
		$options = iworks_simple_seo_improvements_options();
		foreach ( $options as $group ) {
			if ( empty( $group['options'] ) || ! is_array( $group['options'] ) ) {
				continue;
			}
			foreach ( $group['options'] as $opt ) {
				if ( isset( $opt['name'] ) && $opt['name'] === $option_name ) {
					return isset( $opt['sanitize_callback'] ) ? $opt['sanitize_callback'] : 'sanitize_text_field';
				}
			}
		}
		$this->fail( "option {$option_name} not found in config" );
	}

	private function simulate_save( $option_name, $payload ) {
		$callback = $this->get_sanitize_callback( $option_name );
		return call_user_func( $callback, $payload );
	}

	/**
	 * A stored value is safe when it cannot open a tag in the stored form.
	 * WP's sanitize_text_field keeps stray quotes and inert text like
	 * 'javascript:alert(1)' — by design, quotes are neutralized at OUTPUT
	 * time by esc_attr() (the plugin escapes every option echo), so the
	 * save-time guarantee pinned here is: no raw '<'.
	 */
	private static function assert_no_executable_markup( $stored, $option_name, $payload ) {
		self::assertStringNotContainsString( '<', $stored, "{$option_name}: raw '<' survived (payload: {$payload})" );
	}

	/**
	 * Every XSS payload, saved through each field's real sanitize_callback,
	 * must come out unable to open tags / break attributes.
	 */
	#[DataProvider( 'xssPayloadsProvider' )]
	public function test_single_payload_stripped_by_default_fields( $payload ) {
		// Default-sanitized fields (framework fallback).
		foreach ( array(
			'home_meta_description',
			'robots_txt_disallow',
			'robots_txt_allow',
			'json_name_alt',
			'json_org_name',
			'json_org_alt',
			'json_org_pa_st',
			'json_org_pa_l',
			'json_org_pa_r',
			'json_org_pa_pc',
			'json_other',
		) as $option_name ) {
			$stored = $this->simulate_save( $option_name, $payload );
			self::assert_no_executable_markup( $stored, $option_name, $payload );
		}
	}

	/**
	 * WMT fields use the stricter allowlist sanitizer.
	 */
	#[DataProvider( 'xssPayloadsProvider' )]
	public function test_single_payload_stripped_by_wmt_fields( $payload ) {
		foreach ( array( 'wmt_google', 'wmt_bing', 'wmt_yandex', 'wmt_pinterest' ) as $option_name ) {
			$stored = $this->simulate_save( $option_name, $payload );
			// Allowlist: only [A-Za-z0-9_-] may survive.
			$this->assertMatchesRegularExpression(
				'/^[A-Za-z0-9_-]*$/',
				$stored,
				"{$option_name}: non-allowlist chars survived (payload: {$payload})"
			);
		}
	}

	/**
	 * The default framework sanitize must be sanitize_text_field for the
	 * default fields — pin it so a config change can't silently weaken it.
	 */
	#[DataProvider( 'configFieldsProvider' )]
	public function test_field_uses_expected_sanitizer( $option_name, $expected ) {
		$this->assertSame( $expected, $this->get_sanitize_callback( $option_name ) );
	}

	/**
	 * Realistic benign values must survive sanitization untouched.
	 */
	#[DataProvider( 'configFieldsProvider' )]
	public function test_benign_values_survive( $option_name, $expected ) {
		$benign = 'normal-value 123';
		if ( 'iworks_ssi_sanitize_wmt_code' === $expected ) {
			// WMT allowlist strips spaces and dashes are kept, so use a code-like value.
			$benign = 'Xk8dF9-_123';
			$stored = $this->simulate_save( $option_name, $benign );
			$this->assertSame( $benign, $stored, "{$option_name} mangled a benign value" );
			return;
		}
		$stored = $this->simulate_save( $option_name, $benign );
		$this->assertSame( $benign, $stored, "{$option_name} mangled a benign value" );
	}

	/**
	 * Metabox post-meta fields: title/description saved through
	 * get_post_data() must be tag-free.
	 */
	public function test_metabox_fields_strip_tags() {
		$_POST['iworks_simple_seo_improvements'] = array(
			'title'       => '<script>alert(1)</script>Clean Title',
			'description' => '<img src=x onerror=alert(1)>Clean Desc',
		);
		$class = new ReflectionClass( 'iworks_simple_seo_improvements_posttypes' );
		$obj   = $class->newInstanceWithoutConstructor();
		$m     = $class->getMethod( 'get_post_data' );
		$data  = $m->invoke( $obj );
		$this->assertSame( 'Clean Title', $data['title'] );
		$this->assertSame( 'Clean Desc', $data['description'] );
		unset( $_POST['iworks_simple_seo_improvements'] );
	}

	/**
	 * Output layer: even when a stored value contains quotes or angles
	 * (sanitize_text_field keeps some), the plugin prints option values
	 * through esc_attr()/esc_html(), which neutralizes attribute escape
	 * attempts. Pin the escaping behavior the sinks rely on.
	 */
	public function test_output_escaping_neutralizes_stored_payloads() {
		$payloads = array(
			'">alert(1)',            // what sanitize_text_field leaves from the attribute-break payload.
			'javascript:alert(1)',   // inert text in an attribute.
			'plain > text',
		);
		foreach ( $payloads as $payload ) {
			$escaped = esc_attr( $payload );
			$this->assertStringNotContainsString( '"', $escaped, 'esc_attr must encode double quotes' );
			$this->assertStringNotContainsString( '<', $escaped, 'esc_attr must encode <' );
			// Reconstructing the meta tag: the payload cannot close the attribute.
			$meta = sprintf( '<meta name="x" content="%s">', $escaped );
			$this->assertMatchesRegularExpression( '/^<meta name="x" content="[^"]*">$/', $meta, 'meta tag structure must survive payload' );
		}
	}
}
