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

if ( class_exists( 'iworks_simple_seo_improvements_index_now_bing' ) ) {
	return;
}

require_once dirname( __FILE__ ) . '/class-iworks-index-now.php';

class iworks_simple_seo_improvements_index_now_bing extends iworks_simple_seo_improvements_index_now {

	public function __construct( $key ) {
		$this->api_key_bing = $key;
		if ( empty( $this->api_key_bing ) ) {
			return;
		}
		if ( 32 > strlen( $this->api_key_bing ) ) {
			return;
		}
		/**
		 * IndexNow URL
		 */
		$this->index_now_url = 'https://www.bing.com/indexnow';
		/**
		 * WordPress Hooks
		 */
		add_action( 'parse_request', array( $this, 'action_parse_request' ) );
		add_action( 'save_post', array( $this, 'action_save_post' ), 10, 3 );
		add_action( 'add_attachment', array( $this, 'action_add_attachment' ) );
	}

	/**
	 * Parse request for api key confirmation
	 *
	 * @since 1.3.0
	 */
	public function action_parse_request() {
		if (
			! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$uri = remove_query_arg( array_keys( $_GET ), $_SERVER['REQUEST_URI'] );
		if ( '/' . $this->api_key_bing . '.txt' === $uri ) {
			echo $this->api_key_bing;
			exit;
		}
	}

	/**
	 * IndexNow attachement
	 *
	 * @since 1.3.0
	 */
	public function action_add_attachment( $post_id ) {
		$uri = add_query_arg(
			array(
				'url' => get_attachment_link( $post_id ),
				'key' => $this->api_key_bing,
			),
			$this->index_now_url
		);
		wp_remote_get( $uri );
	}

	/**
	 * IndexNow entry
	 *
	 * @since 1.3.0
	 */
	public function action_save_post( $post_id, $post, $update ) {
		if ( 'auto-draft' === get_post_status( $post ) ) {
			return;
		}
		if ( 'draft' === get_post_status( $post ) ) {
			return;
		}
		$uri = add_query_arg(
			array(
				'url' => get_permalink( $post ),
				'key' => $this->api_key_bing,
			),
			$this->index_now_url
		);
		wp_remote_get( $uri );
	}

}

