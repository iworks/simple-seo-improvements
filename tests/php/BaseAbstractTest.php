<?php
/**
 * Unit tests for iworks_simple_seo_improvements_base_abstract pure helpers.
 *
 * @package simple-seo-improvements
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

/**
 * Test double exposing protected methods.
 */
class BaseAbstractExposed extends iworks_simple_seo_improvements_base_abstract {
	public function expose_compress( $value ) {
		return $this->compress_all_whitespaces( $value );
	}
	public function expose_clear_string( $value ) {
		return $this->clear_string( $value );
	}
	public function expose_get_post_data() {
		return $this->get_post_data();
	}
}

final class BaseAbstractTest extends TestCase {

	public function test_compress_all_whitespaces_collapses_whitespace() {
		$e = new BaseAbstractExposed();
		$this->assertSame( 'a b c', $e->expose_compress( "  a \t\r\n  b \n c  " ) );
	}

	public function test_compress_all_whitespaces_empty() {
		$e = new BaseAbstractExposed();
		$this->assertSame( '', $e->expose_compress( "  \n\t " ) );
	}

	public function test_clear_string_strips_html_and_compresses() {
		$e = new BaseAbstractExposed();
		$this->assertSame( 'hello world', $e->expose_clear_string( '<p>hello</p> <b>world</b>' ) );
	}

	public function test_clear_string_keeps_non_strings() {
		$e = new BaseAbstractExposed();
		$this->assertSame( 123, $e->expose_clear_string( 123 ) );
		$this->assertNull( $e->expose_clear_string( null ) );
	}

	public function test_clear_string_empty_string() {
		$e = new BaseAbstractExposed();
		$this->assertSame( '', $e->expose_clear_string( '' ) );
	}

	public function test_get_post_data_strips_tags_from_title_and_description() {
		$e             = new BaseAbstractExposed();
		$_POST         = array(
			'iworks_simple_seo_improvements' => array(
				'title'       => '<b>Title</b> with <script>evil()</script>',
				'description' => '  desc   here  ',
				'other'       => '<i>keep</i>',
			),
		);
		$data          = $e->expose_get_post_data();
		// wp_strip_all_tags() removes <script>/<style> elements including their content, like WP core.
		$this->assertSame( 'Title with', $data['title'] );
		// wp_strip_all_tags() without $remove_breaks does not compress whitespace.
		$this->assertSame( 'desc   here', $data['description'] );
		$this->assertSame( '<i>keep</i>', $data['other'] );
		unset( $_POST );
	}

	public function test_get_post_data_empty_when_no_post() {
		$_POST = array();
		$e     = new BaseAbstractExposed();
		$this->assertSame( array(), $e->expose_get_post_data() );
	}

	public function test_field_name_value() {
		$e = new BaseAbstractExposed();
		$p = new ReflectionProperty( 'iworks_simple_seo_improvements_base_abstract', 'field_name' );
		$this->assertSame( 'iworks_simple_seo_improvements', $p->getValue( $e ) );
	}
}
