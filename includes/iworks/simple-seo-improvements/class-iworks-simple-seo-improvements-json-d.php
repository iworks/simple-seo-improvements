<?php
/*

Copyright 2023-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

this program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( class_exists( 'iworks_simple_seo_improvements_json_d' ) ) {
	return;
}

require_once dirname( __FILE__ ) . '/class-base.php';

class iworks_simple_seo_improvements_json_d extends iworks_simple_seo_improvements_base {

	private $data = array(
		'@context' => 'https://schema.org',
	);

	public function __construct( $iworks ) {
		$this->options = get_iworks_simple_seo_improvements_options();
		add_action( 'wp_print_scripts', array( $this, 'action_wp_print_scripts' ) );
		/**
		 * set basic
		 *
		 * @since 1.5.7
		 */
		if ( is_home() || is_front_page() ) {
			$this->data['@type'] = 'WebSite';
			$this->data['url']   = get_site_url();
		}
		/**
		 * add postdata
		 *
		 * @since 1.5.7
		 */
		add_filter( 'simple_seo_improvements_json_d_data', array( $this, 'add_post_data' ) );
		/**
		 * add sitelinks search box
		 *
		 * @since 1.5.7
		 */
		add_filter( 'simple_seo_improvements_json_d_data', array( $this, 'add_sitelinks_search_box' ) );
		/**
		 * add logo structured data
		 *
		 * @since 1.5.7
		 */
		add_filter( 'simple_seo_improvements_json_d_data', array( $this, 'add_logo_structured_data' ) );
	}

	/**
	 * add json-d to head
	 *
	 * @since 1.5.7
	 */
	public function action_wp_print_scripts() {
		echo '<script type="application/ld+json" id="simple-seo-improvements-json-d">';
		echo PHP_EOL;
		echo json_encode(
			apply_filters(
				'simple_seo_improvements_json_d_data',
				$this->data
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
		echo PHP_EOL;
		echo '</script>';
		echo PHP_EOL;
	}

		/**
		 * add postdata
		 *
		 * @since 1.5.7
		 */
	public function add_post_data( $data ) {
		if ( ! is_single() ) {
			return $data;
		}
		$data['@type']         = 'NewsArticle';
		$data['headline']      = get_the_title();
		$data['datePublished'] = get_the_date( 'c' );
		$data['dateModified']  = get_the_modified_date( 'c' );

		return $data;
	}
	/**
	 * add sitelinks search box
	 *
	 * @since 1.5.7
	 */
	public function add_sitelinks_search_box( $data ) {
		$data['potentialAction'] = array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => sprintf( '%s/?s={search_term_string}', get_site_url() ),
			),
			'query-input' => 'required name=search_term_string',
		);
		return $data;
	}

	/**
	 * add logo structured data
	 *
	 * @since 1.5.7
	 */
	public function add_logo_structured_data( $data ) {
		$attachment_id = $this->options->get_option( 'default_image' );
		if ( ! empty( $attachment_id ) ) {
			$data['logo'] = wp_get_attachment_image_url( $attachment_id, 'full' );
		}
		return $data;
	}

}

