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
		global $test_post_meta, $test_valid_nonce;
		$test_post_meta   = array();
		$test_valid_nonce = true;
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
}
