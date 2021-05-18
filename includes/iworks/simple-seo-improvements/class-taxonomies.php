<?php
/*

Copyright 2021-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

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

if ( class_exists( 'iworks_simple_seo_improvements_taxonomies' ) ) {
	return;
}

require_once dirname( __FILE__ ) . '/class-base.php';

class iworks_simple_seo_improvements_taxonomies extends iworks_simple_seo_improvements_base {

	private $iworks;

	public function __construct( $iworks ) {
		$this->iworks = $iworks;
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
	}

	public function add_meta_boxes() {
		// $args = apply_filters(
			// 'iworks_simple_seo_improvements_get_post_types_args',
			// array(
				// 'public' => true,
			// )
		// );
		// $post_types = get_post_types( $post_types );
		// foreach( $post_types as $post_type ) {
			// add_meta_box( 'iworks_simple_seo_improvements', __( 'Simple SEO Improvements', 'Simple' ), array( $this, 'meta_box_html' ), $post_type );
		// }
	}

	public function meta_box_html( $a ) {
	}

	public function save_data( $element ) {
	}

}


