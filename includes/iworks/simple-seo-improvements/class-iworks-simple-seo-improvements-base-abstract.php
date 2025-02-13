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

if ( class_exists( 'iworks_simple_seo_improvements_base_abstract' ) ) {
	return;
}


abstract class iworks_simple_seo_improvements_base_abstract {

	private $nonce_name   = 'iworks_simple_seo_improvements_nonce';
	protected $field_name = 'iworks_simple_seo_improvements';

	protected $options;

	protected $robots_options = array();

	protected $dev = false;

	protected function add_nonce() {
		wp_nonce_field( __CLASS__, $this->nonce_name );
	}

	protected function check_nonce() {
		$value = filter_input( INPUT_POST, $this->nonce_name, FILTER_DEFAULT );
		if ( ! empty( $value ) ) {
			return wp_verify_nonce( $value, __CLASS__ );
		}
		return false;
	}

	private function filter_input_filter_sanitize_string( $data ) {
		if ( is_array( $data ) ) {
			foreach ( $data as &$one ) {
				$this->filter_input_filter_sanitize_string( $one );
			}
		} elseif ( is_string( $data ) ) {
			$data = filter_var( $data, FILTER_DEFAULT );
		}
		return $data;
	}

	protected function get_post_data() {
		$data = array();
		if ( isset( $_POST[ $this->field_name ] ) ) {
			$data = $this->filter_input_filter_sanitize_string( $_POST[ $this->field_name ] );
		}
		foreach ( $data as $key => $value ) {
			switch ( $key ) {
				case 'description':
				case 'title':
					$data[ $key ] = strip_tags( $value );
					break;
			}
		}
		return $data;
	}

	protected function update_single_post_meta( $post_ID, $meta_key, $meta_value ) {
		if ( empty( $meta_value ) ) {
			delete_post_meta( $post_ID, $meta_key );
			return;
		}
		if ( add_post_meta( $post_ID, $meta_key, $meta_value, true ) ) {
			return;
		}
		update_post_meta( $post_ID, $meta_key, $meta_value );
	}

	protected function update_single_user_meta( $user_ID, $meta_key, $meta_value ) {
		if ( empty( $meta_value ) ) {
			delete_user_meta( $user_ID, $meta_key );
			return;
		}
		if ( add_user_meta( $user_ID, $meta_key, $meta_value, true ) ) {
			return;
		}
		update_user_meta( $user_ID, $meta_key, $meta_value );
	}

	protected function update_single_term_meta( $term_ID, $meta_key, $meta_value ) {
		if ( empty( $meta_value ) ) {
			delete_term_meta( $term_ID, $meta_key );
			return;
		}
		if ( add_term_meta( $term_ID, $meta_key, $meta_value, true ) ) {
			return;
		}
		update_term_meta( $term_ID, $meta_key, $meta_value );
	}

	/**
	 * Compress all whitespaces and trim string.
	 *
	 * @since 1.0.2
	 */
	protected function compress_all_whitespaces( $value ) {
		return trim( preg_replace( '/\s+/', ' ', $value ) );
	}

	/**
	 * set robots options
	 *
	 * @since 1.2.0
	 */
	protected function set_robots_options() {
		if ( ! empty( $this->robots_options ) ) {
			return;
		}
		if ( empty( $this->options ) ) {
			$this->options = get_iworks_simple_seo_improvements_options();
		}
		$settings = $this->options->get_group( 'settings' );
		if ( isset( $settings['robots'] ) ) {
			$this->robots_options = $settings['robots'];
		}
	}

	protected function get_meta_robots_commons() {
		$options = array();
		/**
		 * Google: Max Snippet
		 */
		$value = $this->options->get_option( 'robots_max_snippet' );
		if ( '-1' !== $value ) {
			$options[] = sprintf( 'max-snippet:%d', $value );
		}
		/**
		 * Google: Max Image Preview
		 */
		$value = $this->options->get_option( 'robots_max_image_preview' );
		switch ( $value ) {
			case 'none':
			case 'large':
				$options[] = sprintf( 'max-image-preview:%s', $value );
				break;
		}
		/**
		 * Google: Max Video Preview
		 */
		$value = $this->options->get_option( 'robots_max_video_preview' );
		if ( '-1' !== $value ) {
			$options[] = sprintf( 'max-video-preview:%d', $value );
		}
		return apply_filters( 'iworks_simple_seo_improvements_meta_robots_init', $options );
	}

	/**
	 * clear string
	 *
	 * @since 2.0.6
	 */
	protected function clear_string( $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}
		if ( empty( $value ) ) {
			return $value;
		}
		$value = preg_replace( '/>/', '> ', $value );
		$value = wp_strip_all_tags( $value );
		$value = preg_replace( '/[\r\t\n]+/', ' ', $value );
		$value = preg_replace( '/ +/', ' ', $value );
		$value = trim( $value );
		return $value;
	}
}
