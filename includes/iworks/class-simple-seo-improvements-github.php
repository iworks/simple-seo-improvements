<?php
/*

Copyright 2025-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

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
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'iworks_wp_sitemap_control_github' ) ) {
	return;
}

class iworks_wp_sitemap_control_github {

	public function __construct() {
		/**
		 * WordPress Hooks
		 */
		add_action( 'init', array( $this, 'action_init_load_plugin_textdomain' ) );
	}

	/**
	 * i18n
	 *
	 * @since 1.0.0
	 */
	public function action_init_load_plugin_textdomain() {
		$file = dirname( dirname( __FILE__ ) );
		$root = rtrim( plugin_dir_path( $file ), '/' );
		load_plugin_textdomain(
			'simple-seo-improvements',
			false,
			plugin_basename( $root ) . '/languages'
		);
	}
}

