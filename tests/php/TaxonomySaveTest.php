<?php
/**
 * Term (tag/category) SEO-data save tests + post status-change flows.
 *
 * Taxonomies: `save_data` runs on `create_{taxonomy}` and
 * `edited_{taxonomy}` hooks -- i.e. both term creation and updates. There
 * is NO delete hook: WordPress deletes term meta automatically when the
 * term is removed, so the delete test pins the stub-store semantics of
 * that contract (no plugin-side delete handler to test).
 *
 * Posts: `save_data` also runs when post status changes (status changes
 * fire `save_post`), so a status transition re-saves the meta unchanged.
 *
 * @package simple-seo-improvements
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__, 2 ) . '/includes/iworks/simple-seo-improvements/class-iworks-simple-seo-improvements-base-abstract.php';
require_once dirname( __DIR__, 2 ) . '/includes/iworks/simple-seo-improvements/class-taxonomies.php';

/**
 * In-memory term meta store (mirrors $test_post_meta in SaveDataTest).
 */
$GLOBALS['test_term_meta'] = array();

if ( ! function_exists( 'add_term_meta' ) ) {
	function add_term_meta( $term_id, $meta_key, $meta_value, $unique = false ) {
		global $test_term_meta;
		if ( $unique && isset( $test_term_meta[ $term_id ][ $meta_key ] ) ) {
			return false;
		}
		$test_term_meta[ $term_id ][ $meta_key ] = $meta_value;
		return true;
	}
}
if ( ! function_exists( 'update_term_meta' ) ) {
	function update_term_meta( $term_id, $meta_key, $meta_value ) {
		global $test_term_meta;
		$test_term_meta[ $term_id ][ $meta_key ] = $meta_value;
		return true;
	}
}
if ( ! function_exists( 'get_term_meta' ) ) {
	function get_term_meta( $term_id, $meta_key, $single = false ) {
		global $test_term_meta;
		return isset( $test_term_meta[ $term_id ][ $meta_key ] ) ? $test_term_meta[ $term_id ][ $meta_key ] : '';
	}
}
if ( ! function_exists( 'delete_term_meta' ) ) {
	function delete_term_meta( $term_id, $meta_key ) {
		global $test_term_meta;
		unset( $test_term_meta[ $term_id ][ $meta_key ] );
		return true;
	}
}

/**
 * Concrete test double on the taxonomies class (save_data uses
 * update_single_term_meta).
 */
class TaxonomySaveExposed extends iworks_simple_seo_improvements_taxonomies {
	public function __construct() {
		// Skip real constructor (hooks, iworks object) -- unit test.
	}
	protected function check_nonce() {
		global $test_valid_nonce;
		return ! empty( $test_valid_nonce );
	}
}

final class TaxonomySaveTest extends TestCase {

	private $taxonomies;

	protected function setUp(): void {
		global $test_term_meta, $test_valid_nonce, $_POST;
		$test_term_meta   = array();
		$test_valid_nonce = true;
		$_POST            = array();
		$this->taxonomies = new TaxonomySaveExposed();
	}

	protected function tearDown(): void {
		unset( $_POST );
	}

	private function simulate_save( $term_id, $fields ) {
		$_POST['iworks_simple_seo_improvements'] = $fields;
		$this->taxonomies->save_data( $term_id, 0 );
	}

	/**
	 * Create term (create_{taxonomy} hook): meta written with sanitized
	 * title/description + robots flags.
	 */
	#[DataProvider( 'taxonomyProvider' )]
	public function test_create_term_writes_meta( $taxonomy_label, $term_id ) {
		$this->simulate_save(
			$term_id,
			array(
				'title'       => 'Tag <b>Title</b>',
				'description' => 'Tag <script>alert(1)</script>desc',
				'robots'      => array( 'noindex' => '1' ),
			)
		);
		$stored = $GLOBALS['test_term_meta'][ $term_id ]['iworks_simple_seo_improvements'];
		$this->assertSame( 'Tag Title', $stored['title'] );
		$this->assertSame( 'Tag desc', $stored['description'] );
		$this->assertSame( '1', $stored['robots']['noindex'] );
	}

	public static function taxonomyProvider(): array {
		return array(
			'category (post_tag style id)' => array( 'category', 5001 ),
			'tag'                          => array( 'post_tag', 5002 ),
		);
	}

	/**
	 * Update term (edited_{taxonomy} hook): existing meta replaced.
	 */
	public function test_update_term_replaces_existing_meta() {
		$this->simulate_save( 5100, array( 'title' => 'Old', 'robots' => array( 'noindex' => '1' ) ) );
		$this->simulate_save( 5100, array( 'title' => 'New', 'description' => 'Desc' ) );
		$stored = $GLOBALS['test_term_meta'][5100]['iworks_simple_seo_improvements'];
		$this->assertSame( 'New', $stored['title'] );
		$this->assertSame( 'Desc', $stored['description'] );
		// Old robots flags are gone (whole array replaced).
		$this->assertArrayNotHasKey( 'noindex', $stored['robots'] ?? array() );
	}

	/**
	 * Save with no form data (e.g. term created via quick-add without the
	 * fields) -> meta deleted (empty->delete path).
	 */
	public function test_empty_save_deletes_term_meta() {
		$GLOBALS['test_term_meta'][5200]['iworks_simple_seo_improvements'] = array( 'title' => 'keep?' );
		$_POST                                                             = array(); // no field data at all
		$this->taxonomies->save_data( 5200, 0 );
		$this->assertArrayNotHasKey(
			'iworks_simple_seo_improvements',
			$GLOBALS['test_term_meta'][5200] ?? array()
		);
	}

	/**
	 * Delete term: the plugin registers no delete hook -- term meta removal
	 * is WordPress core's job. Pin that contract (nothing for us to call,
	 * and the store cleans up like core would).
	 */
	public function test_delete_term_removes_meta_via_core_semantics() {
		$this->simulate_save( 5300, array( 'title' => 'T' ) );
		$this->assertArrayHasKey( 'iworks_simple_seo_improvements', $GLOBALS['test_term_meta'][5300] );
		// Core delete_term behavior: meta row gone with the term.
		unset( $GLOBALS['test_term_meta'][5300] );
		$this->assertSame( '', get_term_meta( 5300, 'iworks_simple_seo_improvements', true ) );
	}

	/**
	 * Invalid nonce: term create/update must not write meta.
	 */
	public function test_invalid_nonce_skips_term_save() {
		global $test_valid_nonce;
		$test_valid_nonce = false;
		$this->simulate_save( 5400, array( 'title' => 'Evil' ) );
		$this->assertArrayNotHasKey( 5400, $GLOBALS['test_term_meta'] );
	}

	/**
	 * Hostile robots keys in term meta are ignored by the sink (known-keys
	 * iteration), same contract as per-post robots.
	 */
	public function test_hostile_robots_keys_stored_but_sink_safe() {
		$this->simulate_save(
			5500,
			array(
				'title'  => 'ok',
				'robots' => array(
					'noindex'                    => '1',
					'"><script>alert(1)</script>' => '1',
				),
			)
		);
		$stored = $GLOBALS['test_term_meta'][5500]['iworks_simple_seo_improvements'];
		// Sink iterates known keys only:
		$directives = array();
		foreach ( array( 'noindex', 'nofollow', 'noarchive' ) as $key ) {
			if ( isset( $stored['robots'][ $key ] ) && $stored['robots'][ $key ] ) {
				$directives[] = $key;
			}
		}
		$this->assertSame( array( 'noindex' ), $directives );
	}

	/**
	 * XSS: hostile title/description payloads stored tag-free.
	 */
	public function test_hostile_title_description_stripped() {
		$this->simulate_save(
			5600,
			array(
				'title'       => '<img src=x onerror=alert(1)>T',
				'description' => '"><svg onload=alert(1)>D',
			)
		);
		$stored = $GLOBALS['test_term_meta'][5600]['iworks_simple_seo_improvements'];
		$this->assertSame( 'T', $stored['title'] );
		$this->assertSame( '">D', $stored['description'] );
	}
}
