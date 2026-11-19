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
		$this->check_option_object();
		if ( intval( $this->options->get_option( 'exli:rel:nofollow' ) ) ) {
			$this->run = true;
		} elseif ( intval( $this->options->get_option( 'exli:target:blank' ) ) ) {
			$this->run = true;
		} elseif ( ! empty( $this->options->get_option( 'exli:class' ) ) ) {
			$this->run = true;
		}
	}

	/**
	 * get domain regexp
	 *
	 * @since 2.2.0
	 */
	private function get_domain_regexp() {
		$data = wp_parse_url( get_site_url() );
		if ( preg_match( '/^www\./', $data['host'] ) ) {
			return sprintf( '@https?://%s@', $data['host'] );
		}
		return sprintf( '@https?://(www\.)?%s@', $data['host'] );
	}

	/**
	 * replace
	 *
	 * Uses the WP HTML API (`WP_HTML_Tag_Processor`, WP 6.2+) instead of
	 * the bundled Simple HTML DOM parser.
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
		 * WP HTML API is available since WordPress 6.2; the plugin
		 * requires 6.0, so bail out on older installs.
		 */
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $content;
		}
		/**
		 * Options
		 */
		$this->check_option_object();
		/**
		 * get settings
		 */
		$class            = esc_attr( $this->options->get_option( 'exli:class' ) );
		$set_rel_nofollow = 0 < intval( $this->options->get_option( 'exli:rel:nofollow' ) );
		$set_target_blank = 0 < intval( $this->options->get_option( 'exli:target:blank' ) );
		$domain_regexp    = $this->get_domain_regexp();
		/**
		 * parse & update links
		 */
		$html    = new WP_HTML_Tag_Processor( $content );
		$changed = false;
		while ( $html->next_tag( array( 'tag_name' => 'a' ) ) ) {
			/**
			 * check exists href
			 */
			$href = $html->get_attribute( 'href' );
			if ( ! is_string( $href ) || '' === $href ) {
				continue;
			}
			/**
			 * check only started with http
			 *
			 * @since 2.2.6
			 */
			if ( ! preg_match( '/^http/', $href ) ) {
				continue;
			}
			/**
			 * check domain
			 */
			if ( preg_match( $domain_regexp, $href ) ) {
				continue;
			}
			/**
			 * maybe add class
			 */
			if ( ! empty( $class ) ) {
				$html->add_class( $class );
			}
			/**
			 * maybe set rel
			 */
			if ( $set_rel_nofollow && ! $html->get_attribute( 'rel' ) ) {
				$html->set_attribute( 'rel', 'nofollow' );
			}
			/**
			 * maybe set target
			 */
			if ( $set_target_blank && ! $html->get_attribute( 'target' ) ) {
				$html->set_attribute( 'target', 'blank' );
			}
			$changed = true;
		}
		if ( ! $changed ) {
			return $content;
		}
		return $html->get_updated_html();
	}
}
