<?php
/*

Copyright 2022-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

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

if ( class_exists( 'iworks_simple_seo_improvements_robots_txt' ) ) {
	return;
}

require_once dirname( __FILE__ ) . '/class-base.php';

class iworks_simple_seo_improvements_robots_txt extends iworks_simple_seo_improvements_base {

	public function __construct() {
		add_filter( 'robots_txt', array( $this, 'filter_robots_txt_add' ) );
	}

	public function filter_robots_txt_add( $robots ) {
		$this->set_robots_options();
		$entries = apply_filters(
			/**
			 * allow to filter robots.txt aray
			 *
			 * @since 1.5.5
			 */
			'iworks_simple_seo_improvements_filter_robots_txt_add',
			array(
				'Disallow' => preg_split( '/[\r\n]/', $this->options->get_option( 'robots_txt_disallow' ) ),
				'Allow'    => preg_split( '/[\r\n]/', $this->options->get_option( 'robots_txt_allow' ) ),
			)
		);
		foreach ( array( 'tag', 'category' ) as $taxonomy_name ) {
			$url_base = get_option( $taxonomy_name . '_base', '' );
			if ( ! $url_base ) {
				$url_base = $taxonomy_name;
			}
			$entries['Disallow'][] = sprintf( '/%s/*/feed', $url_base );
		}
		/**
		 * Privacy policy
		 */
		$url = get_privacy_policy_url();
		if ( ! empty( $url ) ) {
			$entries['Disallow'][] = wp_make_link_relative( $url );
		}
		/**
		 * sitemap
		 */
		if ( function_exists( 'get_sitemap_url' ) ) {
			$url = get_sitemap_url( 'index' );
			if ( ! empty( $url ) ) {
				$entries['Sitemap'] = array(
					$url,
				);
			}
		}
		/**
		 * replace robots_txt
		 */
		$robots  = '';
		$robots .= PHP_EOL;
		$robots .= '# PLUGIN_NAME PLUGIN_VERSION';
		$robots .= PHP_EOL;
		$robots .= '# PLUGIN_URI';
		$robots .= PHP_EOL;
		$robots .= PHP_EOL;
		$robots .= 'User-agent: *';
		$robots .= PHP_EOL;
		foreach ( $entries as $key => $data ) {
			if ( 'Sitemap' === $key ) {
				$robots .= PHP_EOL;
			}
			foreach ( array_unique( $data ) as $one ) {
				if ( empty( $one ) ) {
					continue;
				}
				$robots .= sprintf( '%s: %s%s', $key, $one, PHP_EOL );
			}
		}

		return $robots;
	}

}


