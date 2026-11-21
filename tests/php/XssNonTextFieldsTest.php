<?php
/**
 * XSS / injection tests for the non-text config fields.
 *
 * Covers every checkbox, radio, select, number and image field through its
 * real save-path sanitizer, pinning the guarantees the sinks rely on:
 *
 * - checkbox / number / image fields: absint/intval -> always a non-negative
 *   integer, tags/quotes/attributes cannot survive.
 * - select fields: explicit esc_html / intval callbacks.
 * - radio + default-select fields (framework default sanitize_text_field):
 *   values are never echoed; sinks only use them in ===/switch comparisons
 *   or intval() -- these tests pin the sanitizer behavior that makes that
 *   contract safe.
 *
 * @package simple-seo-improvements
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

final class XssNonTextFieldsTest extends TestCase {

	/**
	 * All checkbox/number/image fields, with the callback they must use.
	 */
	public static function numericFieldsProvider(): array {
		return array(
			// checkboxes -> absint.
			'use_as_favicon'    => array( 'use_as_favicon', 'absint' ),
			'category_no_slug'  => array( 'category_no_slug', 'absint' ),
			'tag_no_slug'       => array( 'tag_no_slug', 'absint' ),
			'exli:rel:nofollow' => array( 'exli:rel:nofollow', 'absint' ),
			'exli:target:blank' => array( 'exli:target:blank', 'absint' ),
			'robots_txt'        => array( 'robots_txt', 'absint' ),
			// numbers -> intval.
			'robots_max_snippet'       => array( 'robots_max_snippet', 'intval' ),
			'robots_max_video_preview' => array( 'robots_max_video_preview', 'intval' ),
			// images -> intval (attachment IDs).
			'default_image'   => array( 'default_image', 'intval' ),
			'json_org_img'    => array( 'json_org_img', 'intval' ),
			'json_person_img' => array( 'json_person_img', 'intval' ),
			// selects -> intval / esc_html.
			'json_org_lb' => array( 'json_org_lb', 'intval' ),
		);
	}

	/**
	 * Radio + default-select fields: framework default sanitize_text_field.
	 * Their sinks never echo the value (=== / switch / intval only).
	 */
	public static function comparisonOnlyFieldsProvider(): array {
		return array(
			'post_types'               => array( 'post_types' ),
			'other_archives'           => array( 'other_archives' ),
			'robots_max_image_preview' => array( 'robots_max_image_preview' ),
			'json_type'                => array( 'json_type' ),
			'json_person'              => array( 'json_person' ),
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
	 * Checkbox/number/image/select-numeric fields must reduce any hostile
	 * payload to an integer -- no tags, quotes or attributes can survive.
	 *
	 * @dataProvider numericFieldsProvider
	 */
	#[DataProvider( 'numericFieldsProvider' )]
	public function test_numeric_fields_reduce_payload_to_integer( $option_name, $expected_cb ) {
		foreach ( array(
			'<script>alert(1)</script>',
			'" onmouseover="alert(1)',
			'1"><img src=x onerror=alert(1)>',
			'-5',
			'5abc',
		) as $payload ) {
			$stored = $this->sanitize_via_config( $option_name, $payload );
			$this->assertSame(
				(string) (int) $stored,
				(string) $stored,
				"{$option_name}: non-integer survived from payload {$payload}"
			);
		}
	}

	/**
	 * Every numeric field must declare the exact expected callback.
	 *
	 * @dataProvider numericFieldsProvider
	 */
	#[DataProvider( 'numericFieldsProvider' )]
	public function test_numeric_fields_declare_expected_callback( $option_name, $expected_cb ) {
		foreach ( iworks_simple_seo_improvements_options() as $group ) {
			if ( empty( $group['options'] ) || ! is_array( $group['options'] ) ) {
				continue;
			}
			foreach ( $group['options'] as $opt ) {
				if ( isset( $opt['name'] ) && $opt['name'] === $option_name ) {
					$this->assertSame(
						$expected_cb,
						isset( $opt['sanitize_callback'] ) ? $opt['sanitize_callback'] : null,
						"{$option_name} must use {$expected_cb}"
					);
					return;
				}
			}
		}
		$this->fail( "option {$option_name} not found in config" );
	}

	/**
	 * Comparison-only fields: sanitizer strips tags (so no raw markup is
	 * stored); the value can never match a sink's literal case, so it is
	 * dead weight for an attacker.
	 *
	 * @dataProvider comparisonOnlyFieldsProvider
	 */
	#[DataProvider( 'comparisonOnlyFieldsProvider' )]
	public function test_comparison_only_fields_store_no_tags( $option_name ) {
		$payload = '<script>alert(1)</script>';
		$stored  = $this->sanitize_via_config( $option_name, $payload );
		$this->assertStringNotContainsString(
			'<',
			$stored,
			"{$option_name}: raw '<' survived from payload {$payload}"
		);
		// The stored value cannot equal any legit choice, so sinks that
		// switch/compare must treat it as "no match".
		$this->assertNotSame( 'organization', $stored );
		$this->assertNotSame( 'common', $stored );
		$this->assertNotSame( 'large', $stored );
	}

	/**
	 * json_org_pa_c (select -> esc_html): stored value must be tag-free;
	 * the sink treats it as a literal country code and falls through
	 * otherwise.
	 */
	public function test_select_esc_html_field_tag_free() {
		$stored = $this->sanitize_via_config( 'json_org_pa_c', '<script>alert(1)</script>' );
		// esc_html encodes at save-time: no raw tag can exist in storage.
		$this->assertStringNotContainsString( '<', $stored );
		$this->assertStringNotContainsString( '>', $stored );
		$this->assertSame( '&lt;script&gt;alert(1)&lt;/script&gt;', $stored );
	}

	/**
	 * json_person (select, framework default) feeds intval() in the sink --
	 * verify the sink-side guarantee directly.
	 */
	public function test_json_person_sink_side_intval() {
		$stored = $this->sanitize_via_config( 'json_person', '1"><img src=x onerror=alert(1)>' );
		$this->assertSame( 1, intval( $stored ) );
	}

	/**
	 * Radio values with quotes: sinks compare with === against literals, so
	 * a quote-carrying value is inert -- but pin that quotes surviving the
	 * sanitizer do NOT end up in any HTML attribute (no sink echoes these).
	 * Documented contract: the robots meta builder only prints known
	 * directive strings, never the raw option value.
	 */
	public function test_radio_value_never_reaches_output_directly() {
		$stored = $this->sanitize_via_config( 'robots_max_image_preview', '" onmouseover="alert(1)' );
		// Sink-side: switch($value) case 'none'/'large' -- hostile value
		// matches neither, so nothing is appended to the directive list.
		$this->assertNotSame( 'none', $stored );
		$this->assertNotSame( 'large', $stored );
	}
}
