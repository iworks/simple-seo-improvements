<?php
/**
 * Class to handle OpenSearch functionality for the Simple SEO Improvements plugin.
 *
 * This class provides functionality to add OpenSearch support to a WordPress site,
 * allowing browsers and other clients to discover the site's search functionality.
 *
 * @since 2.3.0
 * @package Simple_SEO_Improvements
 * @subpackage OpenSearch
 * @category Core
 */

/**
 * OpenSearch functionality handler.
 *
 * Handles the generation and serving of OpenSearch description documents,
 * and adds necessary link tags to the site's header.
 *
 * @since 2.3.0
 * @package Simple_SEO_Improvements
 * @subpackage OpenSearch
 * @category Core
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

	/**
	 * Class constructor.
	 *
	 * Sets up the OpenSearch functionality by initializing options and hooks.
	 *
	 * @since 2.3.0
	 * @param object $iworks The main plugin instance.
	 */
	public function __construct( $iworks ) {
		$this->iworks = $iworks;
		/**
		 * WordPress Hooks
		 */
		add_action( 'parse_request', array( $this, 'parse_request' ) );
		add_action( 'wp_head', array( $this, 'wp_head' ) );
	}

	/**
	 * Check if OpenSearch is enabled.
	 *
	 * @since 2.3.0
	 * @return bool True if OpenSearch is enabled, false otherwise.
	 */
	private function is_on() {
		/**
		 * Options
		 */
		$this->options = get_iworks_simple_seo_improvements_options();
		return $this->options->get_option( 'opensearch_on' );
	}

	/**
	 * Get the URL for the OpenSearch XML file.
	 *
	 * @since 2.3.0
	 * @return string Filtered OpenSearch XML URL.
	 */
	private function get_opensearch_xml_url() {
		return apply_filters( 'iworks/simple-seo-improvements/opensearch_xml_url', home_url( '/opensearch.xml' ) );
	}

	/**
	 * Output OpenSearch meta tag in the site header.
	 *
	 * @since 2.3.0
	 * @return void
	 */
	public function wp_head() {
		$link = $this->get_opensearch_xml_url();
		if ( ! $link ) {
			return;
		}
		?>
		<link rel="search" type="application/opensearchdescription+xml" title="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" href="<?php echo esc_url( $link ); ?>" />
		<?php
	}

	/**
	 * Parse the current request to handle OpenSearch XML.
	 *
	 * @since 2.3.0
	 * @return void
	 */
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

	/**
	 * Output the OpenSearch XML description document.
	 *
	 * @since 2.3.0
	 * @return void
	 */
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

	/**
	 * Check if the current request is for the OpenSearch XML file.
	 *
	 * @since 2.3.0
	 * @param string $uri The request URI to check.
	 * @return bool True if the request is for OpenSearch XML, false otherwise.
	 */
	private function is_opensearch_xml_request( $uri ) {
		if ( '/opensearch.xml' === $uri ) {
			return true;
		}
		return apply_filters( 'iworks/simple-seo-improvements/is_opensearch_xml_request', false, $uri );
	}
}
