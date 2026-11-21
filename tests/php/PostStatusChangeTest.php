<?php
/**
 * Post status-change flows: status transitions fire save_post, so the
 * plugin's save_data re-runs -- meta must survive unchanged (it is the
 * same save path).
 *
 * Reuses the SaveDataTest harness (post-meta stubs + SaveDataExposed are
 * defined there and load before this file alphabetically).
 *
 * @package simple-seo-improvements
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/SaveDataTest.php';

/**
 * Post status-change flows: status transitions fire save_post, so the
 * plugin's save_data re-runs -- meta must survive unchanged (it is the
 * same save path).
 */
final class PostStatusChangeTest extends TestCase {

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

	/**
	 * @dataProvider statusProvider
	 */
	#[DataProvider( 'statusProvider' )]
	public function test_status_change_reruns_save_and_preserves_meta( $from_status, $to_status, $post_id, $label ) {
		// Initial save (published post with SEO data).
		$this->simulate_save(
			$post_id,
			array(
				'title'       => 'Status <b>Test</b>',
				'description' => 'Status desc',
				'robots'      => array( 'noindex' => '1' ),
			)
		);
		// Status transition publish -> draft (fires save_post again; the
		// form data is re-posted unchanged by the edit screen).
		$this->simulate_save(
			$post_id,
			array(
				'title'       => 'Status <b>Test</b>',
				'description' => 'Status desc',
				'robots'      => array( 'noindex' => '1' ),
			)
		);
		$stored = $GLOBALS['test_post_meta'][ $post_id ]['iworks_simple_seo_improvements'];
		$this->assertSame( 'Status Test', $stored['title'], "{$label}: meta lost across status change" );
		$this->assertSame( 'Status desc', $stored['description'] );
		$this->assertSame( '1', $stored['robots']['noindex'] );
	}

	public static function statusProvider(): array {
		return array(
			'publish to draft (post)'     => array( 'publish', 'draft', 6001, 'post publish to draft' ),
			'draft to publish (page)'     => array( 'draft', 'publish', 6002, 'page draft to publish' ),
			'publish to private (post)'   => array( 'publish', 'private', 6003, 'post publish to private' ),
			'future to publish (page)'    => array( 'future', 'publish', 6004, 'page future to publish' ),
			'publish to redraft (attach)' => array( 'publish', 'draft', 6005, 'attachment re-draft' ),
		);
	}

	/**
	 * Clearing fields during a status change (user emptied the box) must
	 * delete the meta, not keep stale data.
	 */
	public function test_status_change_with_cleared_fields_deletes_meta() {
		$this->simulate_save( 6100, array( 'title' => 'Old' ) );
		$this->assertArrayHasKey( 'iworks_simple_seo_improvements', $GLOBALS['test_post_meta'][6100] );
		$this->simulate_save( 6100, array( 'title' => '', 'description' => '' ) );
		$stored = $GLOBALS['test_post_meta'][6100]['iworks_simple_seo_improvements'];
		$this->assertSame( '', $stored['title'] );
	}

	/**
	 * Revision/autosave during status change must not touch meta.
	 */
	public function test_autosave_during_status_change_is_skipped() {
		global $test_is_autosave;
		$this->simulate_save( 6200, array( 'title' => 'Real' ) );
		$test_is_autosave = true;
		$this->simulate_save( 6200, array( 'title' => 'Autosaved!' ) );
		$test_is_autosave = false;
		$this->assertSame( 'Real', $GLOBALS['test_post_meta'][6200]['iworks_simple_seo_improvements']['title'] );
	}
}
