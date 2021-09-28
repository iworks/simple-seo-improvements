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

if ( class_exists( 'iworks_simple_seo_improvements' ) ) {
	return;
}

require_once dirname( dirname( __FILE__ ) ) . '/class-iworks.php';

class iworks_simple_seo_improvements extends iworks {

	protected $posttypes;
	protected $taxonomies;

	public function __construct() {
		parent::__construct();
		/**
		 * settings
		 */
		$this->base    = dirname( dirname( __FILE__ ) );
		$this->dir     = basename( dirname( $this->base ) );
		$this->version = 'PLUGIN_VERSION';
		/**
		 * post types
		 */
		require_once $this->base . '/iworks/simple-seo-improvements/class-posttypes.php';
		new iworks_simple_seo_improvements_posttypes( $this );
		/**
		 * bind taxonomies
		 */
		require_once $this->base . '/iworks/simple-seo-improvements/class-taxonomies.php';
		new iworks_simple_seo_improvements_taxonomies( $this );
		/**
		 * sitemap
		 */
		require_once $this->base . '/iworks/simple-seo-improvements/class-sitemap.php';
		new iworks_simple_seo_improvements_sitemap( $this );
		/**
		 * change logo for rate
		 */
		add_filter( 'iworks_rate_notice_logo_style', array( $this, 'filter_plugin_logo' ), 10, 2 );
	}

	/**
	 * Plugin logo for rate messages
	 *
	 * @since 1.0.1
	 *
	 * @param string $logo Logo, can be empty.
	 * @param object $plugin Plugin basic data.
	 */
	public function filter_plugin_logo( $logo, $plugin ) {
		if ( is_object( $plugin ) ) {
			$plugin = (array) $plugin;
		}
		if ( 'simple-seo-improvements' === $plugin['slug'] ) {
			return plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . '/assets/images/logo.svg';
		}
		return $logo;
	}
}

