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

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( class_exists( 'iworks_simple_seo_improvements_links' ) ) {
	return;
}

require_once __DIR__ . '/class-iworks-simple-seo-improvements-base-abstract.php';

class iworks_simple_seo_improvements_links extends iworks_simple_seo_improvements_base_abstract {

	private $iworks;

	private bool $run = false;

	public function __construct( $iworks ) {
		$this->iworks = $iworks;
		/**
		 * WordPress Hooks
		 */
		add_action( 'init', array( $this, 'action_init_set_options' ) );
		if ( ! is_admin() ) {
			add_filter( 'the_content', array( $this, 'filter_replace' ) );
			add_filter( 'the_excerpt', array( $this, 'filter_replace' ) );
		}
	}

	/**
	 * set Options
	 *
	 * @since 2.2.0
	 */
	public function action_init_set_options() {
		$this->options = get_iworks_simple_seo_improvements_options();
		if ( intval( $this->options->get_option( 'exli:rel:nofollow' ) ) ) {
			$this->run = true;
		} elseif ( intval( $this->options->get_option( 'exli:target:blank' ) ) ) {
			$this->run = true;
		} elseif ( ! empty( $this->options->get_option( 'exli:class' ) ) ) {
			$this->run = true;
		}
		if ( $this->run && ! defined( 'HDOM_TYPE_ELEMENT' ) ) {
			include_once dirname( dirname( dirname( __DIR__ ) ) ) . '/vendor/simple_html_dom.php';
		}
	}

	/**
	 * get domain regexp
	 *
	 * @since 2.2.0
	 */
	private function get_domain_regexp() {
		$data = parse_url( get_site_url() );
		if ( preg_match( '/^www\./', $data['host'] ) ) {
			return sprintf( '@https?://%s@', $data['host'] );
		}
		return sprintf( '@https?://(www\.)?%s@', $data['host'] );
	}

	/**
	 * replace
	 *
	 * @since 2.2.0
	 */
	public function filter_replace( $content ) {
		if ( empty( $content ) ) {
			return $content;
		}
		/**
		 * check for `<a ` string, no tags, no work
		 */
		if ( ! preg_match( '/<a /', $content ) ) {
			return $content;
		}
		/**
		 * try to parse `$content`
		 */
		$html = str_get_html( $content );
		/**
		 * check parsed string
		 *
		 * @since 2.2.9
		 */
		if ( false === $html ) {
			return $content;
		}
		/**
		 * get settings
		 */
		$class            = esc_attr( $this->options->get_option( 'exli:class' ) );
		$set_rel_nofollow = 0 < intval( $this->options->get_option( 'exli:rel:nofollow' ) );
		$set_target_blank = 0 < intval( $this->options->get_option( 'exli:target:blank' ) );
		$domain_regexp    = $this->get_domain_regexp();
		/**
		 * find links
		 */
		$elements = $html->find( 'a' );
		if ( empty( $elements ) || ! is_array( $elements ) ) {
			return $content;
		}
		/**
		 * parse & maybe add class
		 */
		foreach ( $elements as $one ) {
			/**
			 * check exists href
			 */
			if ( ! isset( $one->href ) ) {
				continue;
			}
			/**
			 * check only started with http
			 *
			 * @since 2.2.6
			 */
			if ( ! preg_match( '/^http/', $one->href ) ) {
				continue;
			}
			/**
			 * check domain
			 */
			if ( preg_match( $domain_regexp, $one->href ) ) {
				continue;
			}
			$re = $one->__toString();
			if ( ! empty( $class ) ) {
				$one->addClass( $class );
			}
			if ( $set_rel_nofollow && ! isset( $one->rel ) ) {
				$one->setAttribute( 'rel', 'nofollow' );
			}
			if ( $set_target_blank && ! isset( $one->target ) ) {
				$one->setAttribute( 'target', 'blank' );
			}
			$content = str_replace( $re, $one->__toString(), $content );
		}
		return $content;
	}
}


