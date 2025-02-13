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

if ( class_exists( 'iworks_simple_seo_improvements_sitemap' ) ) {
	return;
}

require_once __DIR__ . '/class-iworks-simple-seo-improvements-base-abstract.php';

class iworks_simple_seo_improvements_sitemap extends iworks_simple_seo_improvements_base_abstract {

	private $option_name = 'iworks_ssi_sitemap';

	public function __construct() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		if ( 'yes' === get_option( $this->option_name ) ) {
			add_action( 'init', array( $this, 'init' ) );
		}
	}

	public function admin_init() {
		register_setting( 'media', $this->option_name );
		add_settings_field(
			$this->option_name,
			__( 'Sitemap XML', 'simple-seo-improvements' ),
			array( $this, 'add_fields_html' ),
			'media',
			'default'
		);

	}
	public function add_fields_html() {
		echo '<label>';
		printf(
			'<input type="checkbox" name="%s" value="yes" %s />',
			esc_attr( $this->option_name ),
			checked( 'yes', get_option( $this->option_name ), false )
		);
		esc_html_e( 'Add images to WordPress Sitemap XML?', 'simple-seo-improvements' );
		echo '</label>';
	}

	public function add_attachments( $post_types ) {
		$types = get_post_types( array( 'public' => true ), 'objects' );
		if ( isset( $types['attachment'] ) ) {
			$post_types['attachment'] = $types['attachment'];
		}
		return $post_types;
	}

	public function init() {
		include_once 'class-sitemaps-attachements.php';
		$provider = new iworks_simple_seo_improvements_sitemaps_attachements;
		wp_register_sitemap_provider( 'attachments', $provider );
	}
}


