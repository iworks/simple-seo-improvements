<?php
/**
 *
 * @since 2.3.0
 */
require_once __DIR__ . '/class-iworks-simple-seo-improvements-base-abstract.php';

class iworks_simple_seo_improvements_opensearch extends iworks_simple_seo_improvements_base_abstract {

	/**
	 * Menu ID used in `manifest.json` as `shortcuts`.
	 *
	 * @since 2.3.0
	 */
	private $menu_location_id = 'iworks-pwa-shortcuts';

	/**
	 * Meta name for `manifest.json` short_name value.
	 *
	 * @since 2.3.0
	 */
	private $meta_option_name_sort_menu_name = 'iworks-pwa-short-name';

	public function __construct() {
		parent::__construct();
		/**
		 * WordPress Hooks
		 */
		add_action( 'parse_request', array( $this, 'parse_request' ) );
	}

	public function parse_request() {
		if (
			! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$uri = remove_query_arg( array_keys( $_GET ), esc_url( $_SERVER['REQUEST_URI'] ) );
		/**
		 * opensearch.xml
		 */
		if ( $this->is_opensearch_xml_request( $uri ) ) {
			$this->print_opensearch_xml();
			return;
		}
	}

	private function print_opensearch_xml() {
		header( 'Content-Type: application/opensearchdescription+xml; charset=' . get_option( 'blog_charset' ) );
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">';
		echo '<ShortName>' . get_bloginfo( 'name' ) . '</ShortName>';
		echo '<Description>' . get_bloginfo( 'description' ) . '</Description>';
		echo '<InputEncoding>' . get_option( 'blog_charset' ) . '</InputEncoding>';
		echo '<Image width="16" height="16" type="image/x-icon">' . esc_url( get_site_icon_url( 16 ) ) . '</Image>';
		echo '<Url type="text/html" template="' . esc_url( home_url( '/?s={searchTerms}' ) ) . '"/>';
		echo '</OpenSearchDescription>';
		exit;
	}


	private function is_opensearch_xml_request( $uri ) {
		if ( '/opensearch.xml' === $uri ) {
			return true;
		}
		return apply_filters( 'iworks/simple-seo-improvements/is_opensearch_xml_request', false, $uri );
	}
}
