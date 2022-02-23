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

	/**
	 * Check for OG plugin: https://wordpress.org/plugins/og/
	 *
	 * @since 1.1.0
	 */
	private $is_og_installed = null;

	/**
	 * get robots options
	 *
	 * @since 1.2.0
	 */
	protected $robots_options = array();

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
		add_action( 'wp_head', array( $this, 'filter_wp_head_add_html_head' ) );
		add_action( 'wp_body_open', array( $this, 'action_wp_body_open_add_html_body_start' ), 0 );
		add_action( 'wp_footer', array( $this, 'action_wp_footer_add_html_body_end' ), PHP_INT_MAX );
		/**
		 * options
		 */
		$this->options = get_iworks_simple_seo_improvements_options();
		add_filter( 'iworks_plugin_get_options', array( $this, 'filter_add_post_types_options' ), 10, 2 );
		add_filter( 'iworks_plugin_get_options', array( $this, 'filter_maybe_add_advertising' ), 10, 2 );
		$this->set_robots_options();
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
		 * prefixes
		 */
		require_once $this->base . '/iworks/simple-seo-improvements/class-iworks-simple-seo-improvements-prefixes.php';
		new iworks_simple_seo_improvements_prefixes( $this );
		/**
		 * iWorks Rate integration - change logo for rate
		 */
		add_filter( 'iworks_rate_notice_logo_style', array( $this, 'filter_plugin_logo' ), 10, 2 );
		/**
		 * check for OG plugin & integrate
		 *
		 * @since 1.1.0
		 */
		$this->check_og_plugin();
		add_filter( 'og_twitter_site', array( $this, 'filter_og_twitter_site' ) );
		add_filter( 'og_array', array( $this, 'filter_og_array_add_fb_app_id' ) );
		add_filter( 'og_image_init', array( $this, 'filter_og_image_init' ) );
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
		$url       = add_query_arg( 'page', $page, admin_url( 'options-general.php' ) );
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

	/**
	 * Filter options for custom added post types
	 */
	public function filter_add_post_types_options( $options, $plugin ) {
		if ( 'simple-seo-improvements' !== $plugin ) {
			return $options;
		}
		$opts = $options['index']['options'];
		/**
		 * remove 'meta-description' group
		 */
		if ( 'posts' !== get_option( 'show_on_front' ) ) {
			foreach ( $opts as $key => $value ) {
				if ( ! isset( $value['group'] ) ) {
					continue;
				}
				if ( 'meta-description' !== $value['group'] ) {
					continue;
				}
				unset( $opts[ $key ] );
			}
		}
		/**
		 * remove category/tag prefix remove
		 */
		$permalink_structure = get_option( 'permalink_structure' );
		if ( empty( $permalink_structure ) ) {
			foreach ( $opts as $key => $value ) {
				if ( ! isset( $value['group'] ) ) {
					continue;
				}
				if ( 'prefixes' !== $value['group'] ) {
					continue;
				}
				unset( $opts[ $key ] );
			}
		}
		/**
		 * post types
		 */
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
				'th'      => esc_html( $post_type->labels->singular_name ),
				'options' => array(
					'allow' => array(
						'label' => __( 'Allow to set for entries separately. Settings will apply as default for new entries.', 'simple-seo-improvements' ),
					),
					'force' => array(
						'label' => __( 'Force setting to all entries', 'simple-seo-improvements' ),
					),
				),
				'default' => $is_attachment ? 'force' : 'allow',
			);
			foreach ( $this->robots_options as $key ) {
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
			foreach ( $this->robots_options as $key ) {
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
		foreach ( $this->robots_options as $key ) {
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

	/**
	 * check for OG plugin
	 *
	 * @since 1.1.0
	 */
	public function check_og_plugin() {
		if ( null !== $this->is_og_installed ) {
			return;
		}
		$this->is_og_installed = false;
		$plugins               = get_option( 'active_plugins' );
		if ( empty( $plugins ) ) {
			return;
		}
		foreach ( $plugins as $plugin ) {
			if ( preg_match( '/og\.php$/', $plugin ) ) {
				$this->is_og_installed = true;
				return;
			}
		}
	}

	/**
	 * fallback if OG plugin is not installed
	 *
	 * @since 1.1.0
	 */
	public function filter_wp_head_add_html_head() {
		$content = '';
		/**
		 * HTML HEAD
		 */
		$value = $this->options->get_option( 'html_head' );
		if ( ! empty( $value ) ) {
			$content .= $value;
			$content .= PHP_EOL;
		}
		/**
		 * html meta description for home with blog posts
		 *
		 * @since 1.2.0
		 */
		if ( is_home() && is_front_page() ) {
			$value = $this->options->get_option( 'home_meta_description' );
			if ( ! empty( $value ) ) {
				$content .= sprintf(
					'<meta name="description" content="%s">%s',
					esc_attr( $value ),
					PHP_EOL
				);
			}
		}
		/**
		 * og:image
		 */
		if ( ! $this->is_og_installed ) {
			$value = $this->get_image_for_og_image();
			if ( ! empty( $value ) ) {
				foreach ( $value as $key => $v ) {
					if ( empty( $v ) ) {
						continue;
					}
					$string = '<meta property="og:image:%2$s" content="%1$s" />';
					if ( 'url' === $key ) {
						$string = '<meta property="og:image" content="%1$s" />';
					}
					$content .= sprintf( $string, esc_attr( $v ), esc_attr( $key ) );
					$content .= PHP_EOL;
				}
			}
			/**
			 * fb:app_id
			 */
			$value = $this->options->get_option( 'fb:app_id' );
			if ( ! empty( $value ) ) {
				$content .= sprintf(
					'<meta property="fb:app_id" content="%s">%s',
					esc_attr( $value ),
					PHP_EOL
				);
			}
			/**
			 * twitter:site
			 */
			$value = $this->options->get_option( 'twitter:site' );
			if ( ! empty( $value ) ) {
				if ( ! preg_match( '/^@/', $value ) ) {
					$value = '@' . $value;
				}
				$content .= sprintf(
					'<meta property="twitter:site" content="%s">%s',
					esc_attr( $value ),
					PHP_EOL
				);
			}
		}
		$content = apply_filters( 'simple_seo_improvements_wp_head', $content );
		if ( empty( $content ) ) {
			return;
		}
		echo $this->wrap_code_in_comments( $content );
	}

	/**
	 * Add twitter:site to OG plugin
	 *
	 * @since 1.1.0
	 */
	public function filter_og_twitter_site( $value ) {
		$v = $this->options->get_option( 'twitter:site' );
		if ( empty( $v ) ) {
			return $value;
		}
		if ( ! preg_match( '/^@/', $v ) ) {
			return '@' . $value;
		}
		return $v;
	}

	/**
	 * Add FB:app_id to OG plugin
	 *
	 * @since 1.1.0
	 */
	public function filter_og_array_add_fb_app_id( $og ) {
		$value = $this->options->get_option( 'fb:app_id' );
		if ( ! empty( $value ) ) {
			if ( ! isset( $og['fb'] ) ) {
				$og['fb'] = array();
			}
			$og['fb']['app_id'] = $value;
		}
		return $og;
	}

	/**
	 * add default OG image to OG plugin
	 *
	 * @since 1.2.0
	 */
	public function filter_og_image_init( $og ) {
		return $this->get_image_for_og_image();
	}

	/**
	 * Add code after <body> tag.
	 *
	 * @since 1.1.0
	 */
	public function action_wp_body_open_add_html_body_start() {
		$value = $this->options->get_option( 'html_body_start' );
		if ( empty( $value ) ) {
			return;
		}
		echo $this->wrap_code_in_comments( $value );
	}

	/**
	 * Add code before </body> tag.
	 *
	 * @since 1.1.0
	 */
	public function action_wp_footer_add_html_body_end() {
		$value = $this->options->get_option( 'html_body_end' );
		if ( empty( $value ) ) {
			return;
		}
		echo $this->wrap_code_in_comments( $value );
	}

	/**
	 * Wrap output code with comment
	 *
	 * @since 1.1.1
	 */
	private function wrap_code_in_comments( $code ) {
		$content  = sprintf(
			'%3$s<!-- %1$s - %2$s -->%3$s',
			__( 'Simple SEO Improvements', 'simple-seo-improvements' ),
			$this->version,
			PHP_EOL
		);
		$content .= $code;
		$content .= sprintf(
			'%3$s<!-- /%1$s -->%3$s',
			__( 'Simple SEO Improvements', 'simple-seo-improvements' ),
			$this->version,
			PHP_EOL
		);
		return $content;
	}

	/**
	 * Filter options for some advertising
	 *
	 * @since 1.2.0
	 */
	public function filter_maybe_add_advertising( $options, $plugin ) {
		if ( 'simple-seo-improvements' !== $plugin ) {
			return $options;
		}
		if ( ! isset( $options['index']['metaboxes'] ) ) {
			$options['index']['metaboxes'] = array();
		}
		if ( ! $this->is_og_installed ) {
			$data = apply_filters( 'iworks_rate_advertising_og', array() );
			if ( ! empty( $data ) ) {
				$options['index']['metaboxes'] = array_merge( $options['index']['metaboxes'], $data );
			}
		}
		return $options;
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
		$settings = $this->options->get_group( 'settings' );
		if ( isset( $settings['robots'] ) ) {
			$this->robots_options = $settings['robots'];
		}
	}

	/**
	 * get og:image
	 *
	 * @since 1.2.0
	 */
	private function get_image_for_og_image() {
		$attachment_id = $this->options->get_option( 'default_image' );
		if ( empty( $attachment_id ) ) {
			return array();
			return $image;
		}
		$mime_type = get_post_mime_type( $attachment_id );
		if ( ! preg_match( '/^image/', $mime_type ) ) {
			return array();
		}
		$data = wp_get_attachment_image_src( $attachment_id, 'full' );
		if ( empty( $data ) ) {
			return array();
		}
		return array(
			'url'    => $data[0],
			'width'  => $data[1],
			'height' => $data[2],
			'mime'   => $mime_type,
			'alt'    => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
		);
	}
}

