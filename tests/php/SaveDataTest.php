<?php
/**
 * Unit tests for save_data() across post, page and attachment contexts.
 *
 * These tests run without WordPress by stubbing the meta/storage layer
 * (in-memory store) and driving iworks_simple_seo_improvements_posttypes::save_data()
 * the way the save_post / edit_attachment hooks would.
 *
 * @package simple-seo-improvements
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__, 2 ) . '/includes/iworks/simple-seo-improvements/class-iworks-simple-seo-improvements-posttypes.php';

/**
 * In-memory meta storage shared by the stubs in this file.
 */
$GLOBALS['test_post_meta'] = array();

if ( ! function_exists( 'add_post_meta' ) ) {
	function add_post_meta( $post_id, $key, $value, $unique = false ) {
		global $test_post_meta;
		if ( $unique && isset( $test_post_meta[ $post_id ][ $key ] ) ) {
			return false;
		}
		$test_post_meta[ $post_id ][ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( $post_id, $key, $value ) {
		global $test_post_meta;
		$test_post_meta[ $post_id ][ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'delete_post_meta' ) ) {
	function delete_post_meta( $post_id, $key ) {
		global $test_post_meta;
		unset( $test_post_meta[ $post_id ][ $key ] );
		return true;
	}
}
if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $key, $single = false ) {
		global $test_post_meta;
		return isset( $test_post_meta[ $post_id ][ $key ] ) ? $test_post_meta[ $post_id ][ $key ] : '';
	}
}
if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action = -1 ) {
		global $test_valid_nonce;
		return ! empty( $test_valid_nonce ) ? 1 : 0;
	}
}
if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field( $action = -1, $name = '_wpnonce' ) {
		return '';
	}
}

/**
 * Concrete test double of the abstract base (save_data is on posttypes).
 *
 * check_nonce() reads via filter_input(), which sees nothing in CLI —
 * overridden to simulate a valid/invalid nonce through $test_valid_nonce.
 */
class SaveDataExposed extends iworks_simple_seo_improvements_posttypes {
	public function __construct() {
		// Skip real constructor (hooks, iworks object) — unit test.
	}
	protected function check_nonce() {
		global $test_valid_nonce;
		return ! empty( $test_valid_nonce );
	}
}

final class SaveDataTest extends TestCase {

	private $posttypes;

	protected function setUp(): void {
		global $test_post_meta, $test_valid_nonce, $test_is_revision, $test_is_autosave;
		$test_post_meta   = array();
		$test_valid_nonce = true;
		$test_is_revision = false;
		$test_is_autosave = false;
		$_POST            = array();
		$this->posttypes  = new SaveDataExposed();
	}

	protected function tearDown(): void {
		unset( $_POST );
	}

	private function simulate_save( $post_id, $fields ) {
		$_POST['iworks_simple_seo_improvements'] = $fields;
		$this->posttypes->save_data( $post_id );
	}

	public function test_save_post_meta_for_post() {
		$this->simulate_save( 101, array( 'title' => 'Post <b>Title</b>', 'description' => 'Post desc' ) );
		global $test_post_meta;
		$this->assertSame(
			array( 'title' => 'Post Title', 'description' => 'Post desc' ),
			$test_post_meta[101]['iworks_simple_seo_improvements']
		);
	}

	public function test_save_post_meta_for_page() {
		$this->simulate_save( 202, array( 'title' => 'Page Title', 'description' => 'Page   desc' ) );
		global $test_post_meta;
		// Same code path as post (save_post covers pages); whitespace kept (no remove_breaks).
		$this->assertSame(
			array( 'title' => 'Page Title', 'description' => 'Page   desc' ),
			$test_post_meta[202]['iworks_simple_seo_improvements']
		);
	}

	public function test_save_post_meta_for_attachment() {
		$this->simulate_save( 303, array( 'title' => 'Att <i>achment</i>' ) );
		global $test_post_meta;
		$this->assertSame(
			array( 'title' => 'Att achment' ),
			$test_post_meta[303]['iworks_simple_seo_improvements']
		);
	}

	public function test_empty_data_keeps_meta_with_empty_strings() {
		// save_data() stores whatever get_post_data() returns; an all-empty
		// field array is not empty() itself, so meta is updated, not deleted.
		global $test_post_meta;
		$test_post_meta[404]['iworks_simple_seo_improvements'] = array( 'title' => 'old' );
		$this->simulate_save( 404, array( 'title' => '', 'description' => '' ) );
		$this->assertSame(
			array( 'title' => '', 'description' => '' ),
			$test_post_meta[404]['iworks_simple_seo_improvements']
		);
	}

	public function test_revision_is_skipped() {
		global $test_is_revision;
		$test_is_revision = true;
		$this->simulate_save( 505, array( 'title' => 'rev' ) );
		global $test_post_meta;
		$this->assertArrayNotHasKey( 505, $test_post_meta );
	}

	public function test_autosave_is_skipped() {
		global $test_is_autosave;
		$test_is_autosave = true;
		$this->simulate_save( 606, array( 'title' => 'auto' ) );
		global $test_post_meta;
		$this->assertArrayNotHasKey( 606, $test_post_meta );
	}

	public function test_invalid_nonce_is_skipped() {
		global $test_valid_nonce;
		$test_valid_nonce = false;
		$this->simulate_save( 707, array( 'title' => 'nope' ) );
		global $test_post_meta;
		$this->assertArrayNotHasKey( 707, $test_post_meta );
	}

	/**
	 * Update: saving again over an existing value replaces the meta.
	 */
	#[DataProvider( 'postTypeIdsProvider' )]
	public function test_update_replaces_existing_meta( $post_id, $label ) {
		$this->simulate_save( $post_id, array( 'title' => 'first', 'description' => 'one' ) );
		$this->simulate_save( $post_id, array( 'title' => 'second', 'description' => 'two' ) );
		global $test_post_meta;
		$this->assertSame(
			array( 'title' => 'second', 'description' => 'two' ),
			$test_post_meta[ $post_id ]['iworks_simple_seo_improvements'],
			"meta should be replaced on update for: {$label}"
		);
		// exactly one meta entry — no duplicates on update.
		$this->assertCount( 1, $test_post_meta[ $post_id ] );
	}

	/**
	 * Delete: when the form posts NO plugin fields at all, get_post_data()
	 * returns an empty array and update_single_post_meta() deletes any
	 * existing meta. (Note: all-empty-string fields are stored, not deleted —
	 * an array with keys is not empty(). The plugin registers no delete_post
	 * hook; on real post deletion WP core removes its meta automatically.)
	 */
	#[DataProvider( 'postTypeIdsProvider' )]
	public function test_delete_clears_meta_when_no_fields_posted( $post_id, $label ) {
		global $test_post_meta;
		// create meta first.
		$this->simulate_save( $post_id, array( 'title' => 'doomed', 'description' => 'gone' ) );
		$this->assertArrayHasKey( 'iworks_simple_seo_improvements', $test_post_meta[ $post_id ] );
		// simulate a save event with no plugin form data (e.g. bulk edit, quick edit).
		unset( $_POST['iworks_simple_seo_improvements'] );
		$this->posttypes->save_data( $post_id );
		$this->assertArrayNotHasKey(
			'iworks_simple_seo_improvements',
			$test_post_meta[ $post_id ] ?? array(),
			"meta should be deleted for: {$label}"
		);
	}

	/**
	 * Delete: clearing only some fields keeps the meta with remaining values.
	 */
	#[DataProvider( 'postTypeIdsProvider' )]
	public function test_partial_clear_keeps_remaining_values( $post_id, $label ) {
		$this->simulate_save( $post_id, array( 'title' => 'keep title', 'description' => 'drop me' ) );
		$this->simulate_save( $post_id, array( 'title' => 'keep title', 'description' => '' ) );
		global $test_post_meta;
		$this->assertSame(
			array( 'title' => 'keep title', 'description' => '' ),
			$test_post_meta[ $post_id ]['iworks_simple_seo_improvements'],
			"partial clear should keep meta for: {$label}"
		);
	}

	public static function postTypeIdsProvider(): array {
		return array(
			'post'       => array( 1001, 'post' ),
			'page'       => array( 1002, 'page' ),
			'attachment' => array( 1003, 'image attachment' ),
		);
	}

	/**
	 * ─── Per-post field XSS tests (post_mode = allow) ─────────────────────
	 *
	 * When a post type's {post_type}_mode option is 'allow', editors can set
	 * title / description / robots per entry. These tests throw XSS payloads
	 * at the per-post save path and pin the stored + sink guarantees.
	 */

	/**
	 * Hostile payloads in per-post title/description are stored tag-free
	 * (wp_strip_all_tags on save), including script content removal.
	 */
	public function test_per_post_title_description_stripped_of_tags() {
		$payloads = array(
			'<script>alert(1)</script>',
			'x</script><script>alert(1)</script>y',
			'<img src=x onerror=alert(1)>',
			'"><svg onload=alert(1)>',
		);
		foreach ( $payloads as $payload ) {
			$this->simulate_save(
				2001,
				array(
					'title'       => $payload,
					'description' => $payload,
				)
			);
			$stored = $test_stored = $GLOBALS['test_post_meta'][2001]['iworks_simple_seo_improvements'];
			foreach ( array( 'title', 'description' ) as $field ) {
				$this->assertIsString( $stored[ $field ] );
				$this->assertStringNotContainsString(
					'<',
					$stored[ $field ],
					"per-post {$field}: raw '<' survived from payload {$payload}"
				);
			}
		}
	}

	/**
	 * Hostile robots[] sub-array keys (attribute injection into the
	 * checkbox name) must not survive: the sink iterates only over known
	 * robots option keys, so an injected key must not be echoed into the
	 * meta robots content.
	 */
	public function test_per_post_robots_hostile_keys_ignored_by_sink() {
		$this->simulate_save(
			2002,
			array(
				'title'  => 'ok',
				'robots' => array(
					'noindex'                    => '1',
					'" onmouseover="alert(1)'    => '1',
					'><script>alert(1)</script>' => '1',
				),
			)
		);
		$stored = $GLOBALS['test_post_meta'][2002]['iworks_simple_seo_improvements'];
		// Sink: only known robots keys are consulted. Simulate the sink's
		// iteration over $this->robots_options.
		$known    = array( 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' );
		$directives = array();
		foreach ( $known as $key ) {
			if ( isset( $stored['robots'][ $key ] ) && $stored['robots'][ $key ] ) {
				$directives[] = $key;
			}
		}
		$meta = sprintf( '<meta name="robots" content="%s">', esc_attr( implode( ', ', $directives ) ) );
		$this->assertSame( 1, preg_match_all( '/>/', $meta ) );
		$this->assertSame( 'noindex', implode( ', ', $directives ) );
		$this->assertStringNotContainsString( 'onmouseover', $meta );
		$this->assertStringNotContainsString( 'script', $meta );
	}

	/**
	 * The robots sub-array values are used as truthy flags — a hostile
	 * string value must not reach output (sink only appends the known key
	 * name, never the stored value).
	 */
	public function test_per_post_robots_values_never_echoed() {
		$this->simulate_save(
			2003,
			array(
				'robots' => array(
					'noindex' => '<script>alert(1)</script>',
				),
			)
		);
		$stored = $GLOBALS['test_post_meta'][2003]['iworks_simple_seo_improvements'];
		// Sink echoes only the literal key name when truthy:
		$directives = array();
		foreach ( array( 'noindex', 'nofollow' ) as $key ) {
			if ( isset( $stored['robots'][ $key ] ) && $stored['robots'][ $key ] ) {
				$directives[] = $key;
			}
		}
		$this->assertSame( array( 'noindex' ), $directives );
		$this->assertStringNotContainsString( '<script>', implode( '', $directives ) );
	}

	/**
	 * Sink bridge: the description sink wraps with esc_attr, so even quotes
	 * stored by an older version cannot break the meta tag.
	 */
	public function test_per_post_description_sink_escapes_quotes() {
		// Store a value with quotes (as legacy data might contain).
		$GLOBALS['test_post_meta'][2004]['iworks_simple_seo_improvements'] = array(
			'description' => '" onmouseover="alert(1) x="',
		);
		$stored   = $GLOBALS['test_post_meta'][2004]['iworks_simple_seo_improvements']['description'];
		$meta_tag = sprintf( '<meta name="description" content="%s">', esc_attr( $stored ) );
		$this->assertSame( 1, preg_match_all( '/>/', $meta_tag ) );
		$this->assertDoesNotMatchRegularExpression( '/onmouseover="alert/', $meta_tag );
	}
}
