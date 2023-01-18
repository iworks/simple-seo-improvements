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

if ( class_exists( 'iworks_simple_seo_improvements_archives' ) ) {
	return;
}

require_once dirname( __FILE__ ) . '/class-base.php';

class iworks_simple_seo_improvements_archives extends iworks_simple_seo_improvements_base {

	private $iworks;

	public function __construct( $iworks ) {
		$this->iworks = $iworks;
		add_filter( 'simple_seo_improvements_wp_head', array( $this, 'filter_add_robots' ), 0 );
		/**
		 * options
		 */
		add_filter( 'iworks_plugin_get_options', array( $this, 'filter_add_options' ), 10, 2 );
		$this->options = get_iworks_simple_seo_improvements_options();
		$this->set_robots_options();
	}

	/**
	 * Add meta "robots" tag.
	 *
	 * @since 1.5.0
	 */
	public function filter_add_robots( $content ) {
		if ( is_admin() ) {
			return $content;
		}
		$type = $this->options->get_option( 'other_archives' );
		if ( 'no' === $type ) {
			return $content;
		}
		$value = array();
		/**
		 * author
		 */
		if ( is_author() ) {
			$key = 'other_archive_%s';
			if ( 'common' !== $type ) {
				$key = 'other_archive_author_%s';
			}
			foreach ( $this->robots_options as $name ) {
				if ( intval( $this->options->get_option( sprintf( $key, $name ) ) ) ) {
					$value[] = $name;
				}
			}
		}
		/**
		 * date related Archive
		 */
		if ( is_date() ) {
			$key = 'other_archive_%s';
			if ( 'common' !== $type ) {
				$key = 'other_archive_date_%s';
			}
			foreach ( $this->robots_options as $name ) {
				if ( intval( $this->options->get_option( sprintf( $key, $name ) ) ) ) {
					$value[] = $name;
				}
			}
		}
		/**
		 * empty
		 */
		if ( empty( $value ) ) {
			return $content;
		}
		$content .= sprintf(
			'<meta name="robots" content="%s" />%s',
			esc_attr( implode( ', ', $value ) ),
			PHP_EOL
		);
		return $content;
	}

	/**
	 * Filter options for custom added post types
	 */
	public function filter_add_options( $options, $plugin ) {
		if ( 'simple-seo-improvements' !== $plugin ) {
			return $options;
		}
		/**
		 * Do not handle
		 *
		 * @since 1.5.0
		 *
		 */
		switch ( get_option( 'iworks_ssi_other_archives', 'per_type' ) ) {
			case 'no':
				return $options;
			case 'common':
				return $this->add_options_common( $options );
			case 'per_type':
				return $this->add_options_per_type( $options );
		}
		return $options;
	}

	private function add_options_common( $options ) {
		$options['index']['options'][] = array(
			'type'        => 'heading',
			'label'       => __( 'Other Archives', 'simple-seo-improvements' ),
			'description' => __( 'Use these settings to controll date related & author archive.', 'simple-seo-improvements' ),
		);
		return $this->add_options( $options );
	}

	private function add_options_per_type( $options ) {
		$options['index']['options'][] = array(
			'type'  => 'heading',
			'label' => __( 'Author Archive', 'simple-seo-improvements' ),
		);
		$options                       = $this->add_options( $options, 'author_' );
		$options['index']['options'][] = array(
			'type'        => 'heading',
			'label'       => __( 'Date Archive', 'simple-seo-improvements' ),
			'description' => __( 'Use these settings to controll day, month & year archive.', 'simple-seo-improvements' ),
		);
		$options                       = $this->add_options( $options, 'date_' );
		return $options;
	}

	private function add_options( $options, $name = '' ) {
		if ( ! is_array( $this->robots_options ) ) {
			return $options;
		}
		foreach ( $this->robots_options as $key ) {
			$options['index']['options'][] = array(
				'name'              => sprintf(
					'other_archive_%s%s',
					$name,
					$key
				),
				'type'              => 'checkbox',
				'th'                => sprintf(
					esc_html__( 'Add "%s".', 'simple-seo-improvements' ),
					$key
				),
				'sanitize_callback' => 'absint',
				'default'           => 0,
				'classes'           => array( 'switch-button' ),
			);
		}
		return $options;
	}
}


