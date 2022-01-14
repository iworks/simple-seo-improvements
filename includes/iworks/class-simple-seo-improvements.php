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

	/**
	 * iWorks Options object
	 *
	 * @since 1.0.6
	 */
	private $options;

	/**
	 * plugin file
	 *
	 * @since 1.0.6
	 */
	private $plugin_file;

	private $params = array();

	public function __construct() {
		parent::__construct();
		/**
		 * settings
		 */
		$this->base    = dirname( dirname( __FILE__ ) );
		$this->dir     = basename( dirname( $this->base ) );
		$this->version = 'PLUGIN_VERSION';
		/**
		 * plugin ID
		 */
		$this->plugin_file = plugin_basename( dirname( $this->base ) . '/simple-seo-improvements.php' );
		/**
		 * WordPress Hooks
		 */
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'wp_head', array( $this, 'add_robots' ) );
		/**
		 * options
		 */
		$this->options = get_iworks_simple_seo_improvements_options();
		add_filter( 'iworks_simple_seo_improvements_options', array( $this, 'set_options' ) );
		$this->params = iworks_iworks_seo_improvements_options_get_robots_params();
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
	 * Inicialize admin area
	 *
	 * @since 1.0.6
	 */
	public function admin_init() {
		$this->options->options_init();
		add_filter( 'plugin_action_links_' . $this->plugin_file, array( $this, 'add_settings_link' ) );
	}

	/**
	 * Add settings link to plugin_action_links.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $actions     An array of plugin action links.
	 */
	public function add_settings_link( $actions ) {
		$page      = $this->options->get_pagehook();
		$url       = add_query_arg( 'page', $page, admin_url( 'options.php' ) );
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Settings', 'sierotki' ) );
		return $actions;
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

	public function set_options( $options ) {
		if ( ! empty( $options['index']['options'] ) ) {
			return $options;
		}
		$opts       = array();
		$post_types = get_post_types();
		foreach ( get_post_types() as $post_type_slug ) {
			$post_type = get_post_type_object( $post_type_slug );
			if ( ! $post_type->public ) {
				continue;
			}
			/**
			 *  settings for attachements
			 */
			$is_attachment = 'attachment' === $post_type->name;
			/**
			 * $opts
			 */
			$opts[] = array(
				'type'  => 'heading',
				'label' => $post_type->label,
			);
			$opts[] = array(
				'name'    => sprintf( '%s_mode', $post_type->name ),
				'type'    => 'radio',
				'th'      => esc_html__( 'Single entry', 'simple-seo-improvements' ),
				'options' => array(
					'allow' => array(
						'label' => __( 'Allow to set for entries separately. Seeting will apply as default for new entries.', 'simple-seo-improvements' ),
					),
					'force' => array(
						'label' => __( 'Force setting to all entries', 'simple-seo-improvements' ),
					),
				),
				'default' => $is_attachment ? 'force' : 'allow',
			);
			foreach ( $this->params as $key ) {

				$opts[] = array(
					'name'              => sprintf(
						'%s_%s',
						$post_type->name,
						$key
					),
					'type'              => 'checkbox',
					'th'                => sprintf(
						esc_html__( 'Add "%s".', 'simple-seo-improvements' ),
						$key
					),
					'sanitize_callback' => 'absint',
					'default'           => ( 'noindex' === $key && $is_attachment ) ? 1 : 0,
					'classes'           => array( 'switch-button' ),
				);
			}
			if ( 'page' === $post_type->capability_type ) {
				continue;
			}
			if ( ! $post_type->has_archive ) {
				continue;
			}
			$opts[] = array(
				'type'  => 'subheading',
				'label' => sprintf(
					esc_html__( 'Add meta tags for %s.', 'simple-seo-improvements' ),
					strtolower( $post_type->labels->archives )
				),
			);
			foreach ( $this->params as $key ) {
				$opts[] = array(
					'name'              => sprintf(
						'%s_archive_%s',
						$post_type->name,
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
		}
		/**
		 * return;
		 */
		$options['index']['options'] = $opts;
		return $options;
	}

	/**
	 * Add meta "robots" tag.
	 *
	 * @since 1.0.6
	 */
	public function add_robots() {
		if ( is_admin() ) {
			return;
		}
		$post_type = null;
		/**
		 * home page
		 */
		if ( is_home() ) {
			$post_type = 'post';
		} elseif ( is_archive() ) {
			$post_type = get_queried_object()->name;
		}
		if ( empty( $post_type ) ) {
			return;
		}
		/**
		 * get values
		 */
		$robots = array();
		foreach ( $this->params  as $key ) {
			$name = sprintf( '%s_archive_%s', $post_type, $key );
			if ( $this->options->get_option( $name ) ) {
				$robots[] = $key;
			}
		}
		if ( empty( $robots ) ) {
			return;
		}
		printf(
			'<meta name="robots" content="%s" />%s',
			esc_attr( implode( ', ', $robots ) ),
			PHP_EOL
		);
	}
}

