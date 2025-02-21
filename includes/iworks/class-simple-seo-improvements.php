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

require_once __DIR__ . '/class-simple-seo-improvements-base.php';

class iworks_simple_seo_improvements extends iworks_simple_seo_improvements_base {

	protected $posttypes;
	protected $taxonomies;

	/**
	 * iWorks Options object
	 *
	 * @since 1.0.6
	 */
	protected $options;

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
		add_filter( 'simple-seo-improvements/is_active', '__return_true' );
		/**
		 * WordPress Hooks
		 */
		add_action( 'init', array( $this, 'action_init_set_options' ) );
		add_action( 'init', array( $this, 'maybe_load_index_now' ) );
		add_action( 'init', array( $this, 'maybe_load_robots_txt' ) );
		add_action( 'init', array( $this, 'action_init_register_iworks_rate' ), PHP_INT_MAX );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'wp_head', array( $this, 'add_robots' ) );
		add_action( 'wp_head', array( $this, 'filter_wp_head_add_html_head' ), 0 );
		add_action( 'wp_body_open', array( $this, 'action_wp_body_open_add_html_body_start' ), 0 );
		add_action( 'wp_footer', array( $this, 'action_wp_footer_add_html_body_end' ), PHP_INT_MAX );
		/**
		 * options
		 */
		add_filter( 'iworks_plugin_get_options', array( $this, 'filter_add_post_types_options' ), 10, 2 );
		add_filter( 'iworks_plugin_get_options', array( $this, 'filter_maybe_add_advertising' ), 10, 2 );
		/**
		 * post types
		 */
		require_once $this->base . '/iworks/simple-seo-improvements/class-iworks-simple-seo-improvements-posttypes.php';
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
		 * Archives: day, month, year & author
		 */
		require_once $this->base . '/iworks/simple-seo-improvements/class-iworks-simple-seo-improvements-archives.php';
		new iworks_simple_seo_improvements_archives( $this );
		/**
		 * prefixes
		 */
		require_once $this->base . '/iworks/simple-seo-improvements/class-iworks-simple-seo-improvements-prefixes.php';
		new iworks_simple_seo_improvements_prefixes( $this );
		/**
		 * links
		 */
		require_once $this->base . '/iworks/simple-seo-improvements/class-iworks-simple-seo-improvements-links.php';
		new iworks_simple_seo_improvements_links( $this );
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
		add_filter( 'og_is_schema_org_enabled', '__return_false' );
		/**
		 * get user list for options
		 *
		 * @since 2.0.0
		 */
		add_filter( 'iworks_simple_seo_improvements_get_users', array( $this, 'filter_get_user_list_options' ) );
		/**
		 * load github class
		 *
		 * @since 1.0.8
		 */
		$filename = __DIR__ . '/class-simple-seo-improvements-github.php';
		if ( is_file( $filename ) ) {
			include_once $filename;
			new iworks_simple_seo_improvements_github();
		}
	}

	/**
	 * set Options
	 *
	 * @since 2.2.0
	 */
	public function action_init_set_options() {
		$this->options = get_iworks_simple_seo_improvements_options();
		$this->set_robots_options();
		/**
		 * JSON-D
		 *
		 * @since 1.5.7
		 */
		if ( $this->options->get_option( 'use_json_ld' ) ) {
			require_once $this->base . '/iworks/simple-seo-improvements/class-iworks-simple-seo-improvements-json-ld.php';
			new iworks_simple_seo_improvements_json_ld( $this );
		}
	}

	/**
	 * Inicialize admin area
	 *
	 * @since 1.0.6
	 */
	public function admin_init() {
		add_filter(
			'iworks_simple_seo_improvements_get_pages',
			array( $this, 'filter_get_pages' )
		);
		add_filter(
			'iworks_simple_seo_improvements_get_lb_types',
			array( $this, 'filter_get_lb_types' )
		);
		add_filter(
			'iworks_simple_seo_improvements_get_countries',
			array( $this, 'filter_get_countries' )
		);
		$this->options->options_init();
		add_filter( 'plugin_action_links_' . $this->plugin_file, array( $this, 'add_settings_link' ) );
		wp_register_script(
			'simple-seo-improvements-admin',
			plugins_url( 'assets/scripts/admin/simple-seo-improvements' . $this->dev . '.js', $this->base ),
			array(),
			$this->get_version()
		);
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
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Settings', 'simple-seo-improvements' ) );
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
		/**
		 * Do not handle
		 *
		 * @since 1.5.0
		 *
		 */
		if ( 'no' === get_option( 'iworks_ssi_post_types', 'per_type' ) ) {
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
		/**
		 * common settings
		 */
		$post_types = array(
			'any_post_type' => (object) array(
				'name'            => 'any_post_type',
				'label'           => __( 'Any post type', 'simple-seo-improvements' ),
				'labels'          => (object) array(
					'singular_name' => __( 'Any post type', 'simple-seo-improvements' ),
					'archives'      => __( 'Any post type archive', 'simple-seo-improvements' ),
				),
				'has_archive'     => true,
				'capability_type' => 'any',
			),
		);
		/**
		 * post types
		 */
		if ( 'per_type' === get_option( 'iworks_ssi_post_types', 'per_type' ) ) {
			$post_types = get_post_types(
				array(
					'public' => true,
				),
				'objects'
			);
		}
		foreach ( $post_types as $post_type_slug => $post_type ) {
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
						/* translators: %s robots option */
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
					/* translators: %s archive name */
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
						/* translators: %s robots option */
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
		} elseif ( is_front_page() ) {
			$post_type = 'page';
		} elseif ( is_singular() ) {
			$post_type = get_queried_object()->name;
		} elseif ( is_archive() ) {
			$post_type = 'post';
		}
		if ( 'common' === $this->options->get_option( 'post_types' ) ) {
			// $post_type = 'any_post_type';
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
			'<meta name="robots" content="%s">%s',
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
		 * favicon
		 *
		 * @since 1.4.2
	 */
		$attachment_id = $this->options->get_option( 'default_image' );
		if ( ! empty( $attachment_id ) && $this->options->get_option( 'use_as_favicon' ) ) {
			$url       = wp_make_link_relative( wp_get_attachment_image_url( $attachment_id, 'full' ) );
			$mime_type = get_post_mime_type( $attachment_id );
			foreach ( array( 'icon', 'shortcut icon' ) as $key ) {
				printf(
					'<link rel="%s" href="%s" type="%s">%s',
					esc_attr( $key ),
					esc_attr( $url ),
					esc_attr( $mime_type ),
					PHP_EOL
				);
			}
		}
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
					$string = '<meta property="og:image:%2$s" content="%1$s">';
					if ( 'url' === $key ) {
						$string = '<meta property="og:image" content="%1$s">';
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

		/**
		 * get or generate IndexNow api key
		 *
		 * @since 1.3.0
		 */
	private function get_indexnow_key() {
		$key = $this->options->get_option( 'indexnow' );
		if ( empty( $key ) ) {
			$key = wp_generate_password( 32, false, false );
			$this->options->add_option( 'indexnow', $key, false );
		}
		return $key;
	}

		/**
		 * IndexNow
		 *
		 * @since 1.3.0
		 */
	public function maybe_load_index_now() {
		if ( ! empty( $this->options->get_option( 'indexnow_bing' ) ) ) {
			$key = $this->get_indexnow_key();
			require_once( $this->base . '/iworks/simple-seo-improvements/index-now/class-iworks-index-now-bing.php' );
			new iworks_simple_seo_improvements_index_now_bing( $key );
		}
	}

		/**
		 * IndexNow
		 *
		 * @since 1.4.0
		 */
	public function maybe_load_robots_txt() {
		if ( ! empty( $this->options->get_option( 'robots_txt' ) ) ) {
			require_once( $this->base . '/iworks/simple-seo-improvements/class-iworks-robots-txt.php' );
			new iworks_simple_seo_improvements_robots_txt();
		}
	}

		/**
		 * get user list for options
		 *
		 * @since 2.0.0
		 */
	public function filter_get_user_list_options( $options ) {
		$args  = array(
			'fields' => array( 'id', 'display_name' ),
			'number' => -1,
		);
		$users = get_users( $args );
		foreach ( $users as $user ) {
			$options[ $user->id ] = $user->display_name;
		}
		return $options;
	}

		/**
		 * helper for page lists.
		 *
		 * @since 2.0.0
		 */
	public function filter_get_pages() {
		$options    = array();
		$options[0] = esc_html__( '--- select ---', 'simple-seo-improvements' );
		$args       = array();
		foreach ( get_pages( $args ) as $page ) {
			$options[ $page->ID ] = $page->post_title;
		}
		return $options;
	}

		/**
		 * helper for LocalBuissness type list.
		 *
		 * @since 2.1.0
		 */
	public function filter_get_lb_types( $options ) {
		$data = array(
			'Animal Shelter'                 => esc_html__( 'Animal Shelter', 'simple-seo-improvements' ),
			'Automotive Business'            => esc_html__( 'Automotive Business', 'simple-seo-improvements' ),
			'Child Care'                     => esc_html__( 'Child Care', 'simple-seo-improvements' ),
			'Dentist'                        => esc_html__( 'Dentist', 'simple-seo-improvements' ),
			'Dry Cleaning or Laundry'        => esc_html__( 'Dry Cleaning or Laundry', 'simple-seo-improvements' ),
			'Emergency Service'              => esc_html__( 'Emergency Service', 'simple-seo-improvements' ),
			'Employment Agency'              => esc_html__( 'Employment Agency', 'simple-seo-improvements' ),
			'Entertainment Business'         => esc_html__( 'Entertainment Business', 'simple-seo-improvements' ),
			'Financial Service'              => esc_html__( 'Financial Service', 'simple-seo-improvements' ),
			'Food Establishment'             => esc_html__( 'Food Establishment', 'simple-seo-improvements' ),
			'Government Office'              => esc_html__( 'Government Office', 'simple-seo-improvements' ),
			'Health and Beauty Business'     => esc_html__( 'Health and Beauty Business', 'simple-seo-improvements' ),
			'Home and Construction Business' => esc_html__( 'Home and Construction Business', 'simple-seo-improvements' ),
			'Internet Cafe'                  => esc_html__( 'Internet Cafe', 'simple-seo-improvements' ),
			'Legal Service'                  => esc_html__( 'Legal Service', 'simple-seo-improvements' ),
			'Library'                        => esc_html__( 'Library', 'simple-seo-improvements' ),
			'Lodging Business'               => esc_html__( 'Lodging Business', 'simple-seo-improvements' ),
			'Medical Business'               => esc_html__( 'Medical Business', 'simple-seo-improvements' ),
			'Professional Service'           => esc_html__( 'Professional Service', 'simple-seo-improvements' ),
			'Radio Station'                  => esc_html__( 'Radio Station', 'simple-seo-improvements' ),
			'Real Estate Agent'              => esc_html__( 'Real Estate Agent', 'simple-seo-improvements' ),
			'Recycling Center'               => esc_html__( 'Recycling Center', 'simple-seo-improvements' ),
			'Self Storage'                   => esc_html__( 'Self Storage', 'simple-seo-improvements' ),
			'Shopping Center'                => esc_html__( 'Shopping Center', 'simple-seo-improvements' ),
			'Sports Activity Location'       => esc_html__( 'Sports Activity Location', 'simple-seo-improvements' ),
			'Store'                          => esc_html__( 'Store', 'simple-seo-improvements' ),
			'Television Station'             => esc_html__( 'Television Station', 'simple-seo-improvements' ),
			'Tourist Information Center'     => esc_html__( 'Tourist Information Center', 'simple-seo-improvements' ),
			'Travel Agency'                  => esc_html__( 'Travel Agency', 'simple-seo-improvements' ),
			'Auto Body Shop'                 => esc_html__( 'Auto Body Shop', 'simple-seo-improvements' ),
			'Auto Dealer'                    => esc_html__( 'Auto Dealer', 'simple-seo-improvements' ),
			'Auto Parts Store'               => esc_html__( 'Auto Parts Store', 'simple-seo-improvements' ),
			'Auto Rental'                    => esc_html__( 'Auto Rental', 'simple-seo-improvements' ),
			'Auto Repair'                    => esc_html__( 'Auto Repair', 'simple-seo-improvements' ),
			'Auto Wash'                      => esc_html__( 'Auto Wash', 'simple-seo-improvements' ),
			'Gas Station'                    => esc_html__( 'Gas Station', 'simple-seo-improvements' ),
			'Motorcycle Dealer'              => esc_html__( 'Motorcycle Dealer', 'simple-seo-improvements' ),
			'Motorcycle Repair'              => esc_html__( 'Motorcycle Repair', 'simple-seo-improvements' ),
			'Fire Station'                   => esc_html__( 'Fire Station', 'simple-seo-improvements' ),
			'Hospital'                       => esc_html__( 'Hospital', 'simple-seo-improvements' ),
			'Police Station'                 => esc_html__( 'Police Station', 'simple-seo-improvements' ),
			'Adult Entertainment'            => esc_html__( 'Adult Entertainment', 'simple-seo-improvements' ),
			'Amusement Park'                 => esc_html__( 'Amusement Park', 'simple-seo-improvements' ),
			'Art Gallery'                    => esc_html__( 'Art Gallery', 'simple-seo-improvements' ),
			'Casino'                         => esc_html__( 'Casino', 'simple-seo-improvements' ),
			'Comedy Club'                    => esc_html__( 'Comedy Club', 'simple-seo-improvements' ),
			'Movie Theater'                  => esc_html__( 'Movie Theater', 'simple-seo-improvements' ),
			'Night Club'                     => esc_html__( 'Night Club', 'simple-seo-improvements' ),
			'Accounting Service'             => esc_html__( 'Accounting Service', 'simple-seo-improvements' ),
			'Automated Teller'               => esc_html__( 'Automated Teller', 'simple-seo-improvements' ),
			'Bank or CreditUnion'            => esc_html__( 'Bank or CreditUnion', 'simple-seo-improvements' ),
			'Insurance Agency'               => esc_html__( 'Insurance Agency', 'simple-seo-improvements' ),
			'Bakery'                         => esc_html__( 'Bakery', 'simple-seo-improvements' ),
			'Bar or Pub'                     => esc_html__( 'Bar or Pub', 'simple-seo-improvements' ),
			'Brewery'                        => esc_html__( 'Brewery', 'simple-seo-improvements' ),
			'Cafe or Coffee Shop'            => esc_html__( 'Cafe or Coffee Shop', 'simple-seo-improvements' ),
			'Fast Food Restaurant'           => esc_html__( 'Fast Food Restaurant', 'simple-seo-improvements' ),
			'Ice Cream Shop'                 => esc_html__( 'Ice Cream Shop', 'simple-seo-improvements' ),
			'Restaurant'                     => esc_html__( 'Restaurant', 'simple-seo-improvements' ),
			'Winery'                         => esc_html__( 'Winery', 'simple-seo-improvements' ),
			'Distillery'                     => esc_html__( 'Distillery', 'simple-seo-improvements' ),
			'Post Office'                    => esc_html__( 'Post Office', 'simple-seo-improvements' ),
			'Beauty Salon'                   => esc_html__( 'Beauty Salon', 'simple-seo-improvements' ),
			'Day Spa'                        => esc_html__( 'Day Spa', 'simple-seo-improvements' ),
			'Hair Salon'                     => esc_html__( 'Hair Salon', 'simple-seo-improvements' ),
			'Health Club'                    => esc_html__( 'Health Club', 'simple-seo-improvements' ),
			'Nail Salon'                     => esc_html__( 'Nail Salon', 'simple-seo-improvements' ),
			'Tattoo Parlor'                  => esc_html__( 'Tattoo Parlor', 'simple-seo-improvements' ),
			'Electrician'                    => esc_html__( 'Electrician', 'simple-seo-improvements' ),
			'General Contractor'             => esc_html__( 'General Contractor', 'simple-seo-improvements' ),
			'HVAC Business'                  => esc_html__( 'HVAC Business', 'simple-seo-improvements' ),
			'House Painter'                  => esc_html__( 'House Painter', 'simple-seo-improvements' ),
			'Locksmith'                      => esc_html__( 'Locksmith', 'simple-seo-improvements' ),
			'Moving Company'                 => esc_html__( 'Moving Company', 'simple-seo-improvements' ),
			'Plumber'                        => esc_html__( 'Plumber', 'simple-seo-improvements' ),
			'Roofing Contractor'             => esc_html__( 'Roofing Contractor', 'simple-seo-improvements' ),
			'Attorney'                       => esc_html__( 'Attorney', 'simple-seo-improvements' ),
			'Notary'                         => esc_html__( 'Notary', 'simple-seo-improvements' ),
			'Bed and Breakfast'              => esc_html__( 'Bed and Breakfast', 'simple-seo-improvements' ),
			'Campground'                     => esc_html__( 'Campground', 'simple-seo-improvements' ),
			'Hostel'                         => esc_html__( 'Hostel', 'simple-seo-improvements' ),
			'Hotel'                          => esc_html__( 'Hotel', 'simple-seo-improvements' ),
			'Motel'                          => esc_html__( 'Motel', 'simple-seo-improvements' ),
			'Resort'                         => esc_html__( 'Resort', 'simple-seo-improvements' ),
			'Community Health'               => esc_html__( 'Community Health', 'simple-seo-improvements' ),
			'Dentist'                        => esc_html__( 'Dentist', 'simple-seo-improvements' ),
			'Dermatology'                    => esc_html__( 'Dermatology', 'simple-seo-improvements' ),
			'Diet Nutrition'                 => esc_html__( 'Diet Nutrition', 'simple-seo-improvements' ),
			'Emergency'                      => esc_html__( 'Emergency', 'simple-seo-improvements' ),
			'Geriatric'                      => esc_html__( 'Geriatric', 'simple-seo-improvements' ),
			'Gynecologic'                    => esc_html__( 'Gynecologic', 'simple-seo-improvements' ),
			'Medical Clinic'                 => esc_html__( 'Medical Clinic', 'simple-seo-improvements' ),
			'Midwifery'                      => esc_html__( 'Midwifery', 'simple-seo-improvements' ),
			'Nursing'                        => esc_html__( 'Nursing', 'simple-seo-improvements' ),
			'Obstetric'                      => esc_html__( 'Obstetric', 'simple-seo-improvements' ),
			'Oncologic'                      => esc_html__( 'Oncologic', 'simple-seo-improvements' ),
			'Optician'                       => esc_html__( 'Optician', 'simple-seo-improvements' ),
			'Optometric'                     => esc_html__( 'Optometric', 'simple-seo-improvements' ),
			'Otolaryngologic'                => esc_html__( 'Otolaryngologic', 'simple-seo-improvements' ),
			'Pediatric'                      => esc_html__( 'Pediatric', 'simple-seo-improvements' ),
			'Pharmacy'                       => esc_html__( 'Pharmacy', 'simple-seo-improvements' ),
			'Physician'                      => esc_html__( 'Physician', 'simple-seo-improvements' ),
			'Physiotherapy'                  => esc_html__( 'Physiotherapy', 'simple-seo-improvements' ),
			'Plastic Surgery'                => esc_html__( 'Plastic Surgery', 'simple-seo-improvements' ),
			'Podiatric'                      => esc_html__( 'Podiatric', 'simple-seo-improvements' ),
			'Primary Care'                   => esc_html__( 'Primary Care', 'simple-seo-improvements' ),
			'Psychiatric'                    => esc_html__( 'Psychiatric', 'simple-seo-improvements' ),
			'Public Health'                  => esc_html__( 'Public Health', 'simple-seo-improvements' ),
			'Bowling Alleyt'                 => esc_html__( 'Bowling Alleyt', 'simple-seo-improvements' ),
			'Exercise Gym'                   => esc_html__( 'Exercise Gym', 'simple-seo-improvements' ),
			'Golf Course'                    => esc_html__( 'Golf Course', 'simple-seo-improvements' ),
			'Health Club'                    => esc_html__( 'Health Club', 'simple-seo-improvements' ),
			'Public Swimming Pool'           => esc_html__( 'Public Swimming Pool', 'simple-seo-improvements' ),
			'Ski Resort'                     => esc_html__( 'Ski Resort', 'simple-seo-improvements' ),
			'Sports Club'                    => esc_html__( 'Sports Club', 'simple-seo-improvements' ),
			'Stadium or Arena'               => esc_html__( 'Stadium or Arena', 'simple-seo-improvements' ),
			'Tennis Complex'                 => esc_html__( 'Tennis Complex', 'simple-seo-improvements' ),
			'Auto Parts Store'               => esc_html__( 'Auto Parts Store', 'simple-seo-improvements' ),
			'Bike Store'                     => esc_html__( 'Bike Store', 'simple-seo-improvements' ),
			'Book Store'                     => esc_html__( 'Book Store', 'simple-seo-improvements' ),
			'Clothing Store'                 => esc_html__( 'Clothing Store', 'simple-seo-improvements' ),
			'Computer Store'                 => esc_html__( 'Computer Store', 'simple-seo-improvements' ),
			'Convenience Store'              => esc_html__( 'Convenience Store', 'simple-seo-improvements' ),
			'Department Store'               => esc_html__( 'Department Store', 'simple-seo-improvements' ),
			'Electronics Store'              => esc_html__( 'Electronics Store', 'simple-seo-improvements' ),
			'Florist'                        => esc_html__( 'Florist', 'simple-seo-improvements' ),
			'Furniture Store'                => esc_html__( 'Furniture Store', 'simple-seo-improvements' ),
			'Garden Store'                   => esc_html__( 'Garden Store', 'simple-seo-improvements' ),
			'Grocery Store'                  => esc_html__( 'Grocery Store', 'simple-seo-improvements' ),
			'Hardware Store'                 => esc_html__( 'Hardware Store', 'simple-seo-improvements' ),
			'Hobby Shop'                     => esc_html__( 'Hobby Shop', 'simple-seo-improvements' ),
			'Home Goods Store'               => esc_html__( 'Home Goods Store', 'simple-seo-improvements' ),
			'Jewelry Store'                  => esc_html__( 'Jewelry Store', 'simple-seo-improvements' ),
			'Liquor Store'                   => esc_html__( 'Liquor Store', 'simple-seo-improvements' ),
			'Mens Clothing Store'            => esc_html__( 'Mens Clothing Store', 'simple-seo-improvements' ),
			'Mobile Phone Store'             => esc_html__( 'Mobile Phone Store', 'simple-seo-improvements' ),
			'Movie Rental Store'             => esc_html__( 'Movie Rental Store', 'simple-seo-improvements' ),
			'Music Store'                    => esc_html__( 'Music Store', 'simple-seo-improvements' ),
			'Office Equipment Store'         => esc_html__( 'Office Equipment Store', 'simple-seo-improvements' ),
			'Outlet Store'                   => esc_html__( 'Outlet Store', 'simple-seo-improvements' ),
			'Pawn Shop'                      => esc_html__( 'Pawn Shop', 'simple-seo-improvements' ),
			'Pet Store'                      => esc_html__( 'Pet Store', 'simple-seo-improvements' ),
			'Shoe Store'                     => esc_html__( 'Shoe Store', 'simple-seo-improvements' ),
			'Sporting Goods Store'           => esc_html__( 'Sporting Goods Store', 'simple-seo-improvements' ),
			'Tire Shop'                      => esc_html__( 'Tire Shop', 'simple-seo-improvements' ),
			'Toy Store'                      => esc_html__( 'Toy Store', 'simple-seo-improvements' ),
			'Wholesale Store'                => esc_html__( 'Wholesale Store', 'simple-seo-improvements' ),
		);
		asort( $data );
		$options = array();
		foreach ( $data as $key => $value ) {
			$key             = preg_replace( '/ /', '', ucwords( $key ) );
			$options[ $key ] = $value;
		}
		array_unshift( $options, esc_html__( '--- select ---', 'simple-seo-improvements' ) );
		return $options;
	}


		/**
		 * helper for LocalBuissness countries list.
		 *
		 * @since 2.1.0
		 */
	public function filter_get_countries( $options ) {
		$options = array(
			'AF' => esc_html__( 'Afghanistan', 'simple-seo-improvements' ),
			'AX' => esc_html__( 'Åland Islands', 'simple-seo-improvements' ),
			'AL' => esc_html__( 'Albania', 'simple-seo-improvements' ),
			'DZ' => esc_html__( 'Algeria', 'simple-seo-improvements' ),
			'AS' => esc_html__( 'American Samoa', 'simple-seo-improvements' ),
			'AD' => esc_html__( 'Andorra', 'simple-seo-improvements' ),
			'AO' => esc_html__( 'Angola', 'simple-seo-improvements' ),
			'AI' => esc_html__( 'Anguilla', 'simple-seo-improvements' ),
			'AQ' => esc_html__( 'Antarctica', 'simple-seo-improvements' ),
			'AG' => esc_html__( 'Antigua and Barbuda', 'simple-seo-improvements' ),
			'AR' => esc_html__( 'Argentina', 'simple-seo-improvements' ),
			'AM' => esc_html__( 'Armenia', 'simple-seo-improvements' ),
			'AW' => esc_html__( 'Aruba', 'simple-seo-improvements' ),
			'AU' => esc_html__( 'Australia', 'simple-seo-improvements' ),
			'AT' => esc_html__( 'Austria', 'simple-seo-improvements' ),
			'AZ' => esc_html__( 'Azerbaijan', 'simple-seo-improvements' ),
			'BS' => esc_html__( 'Bahamas', 'simple-seo-improvements' ),
			'BH' => esc_html__( 'Bahrain', 'simple-seo-improvements' ),
			'BD' => esc_html__( 'Bangladesh', 'simple-seo-improvements' ),
			'BB' => esc_html__( 'Barbados', 'simple-seo-improvements' ),
			'BY' => esc_html__( 'Belarus', 'simple-seo-improvements' ),
			'BE' => esc_html__( 'Belgium', 'simple-seo-improvements' ),
			'BZ' => esc_html__( 'Belize', 'simple-seo-improvements' ),
			'BJ' => esc_html__( 'Benin', 'simple-seo-improvements' ),
			'BM' => esc_html__( 'Bermuda', 'simple-seo-improvements' ),
			'BT' => esc_html__( 'Bhutan', 'simple-seo-improvements' ),
			'BO' => esc_html__( 'Bolivia, Plurinational State of', 'simple-seo-improvements' ),
			'BQ' => esc_html__( 'Bonaire, Sint Eustatius and Saba', 'simple-seo-improvements' ),
			'BA' => esc_html__( 'Bosnia and Herzegovina', 'simple-seo-improvements' ),
			'BW' => esc_html__( 'Botswana', 'simple-seo-improvements' ),
			'BV' => esc_html__( 'Bouvet Island', 'simple-seo-improvements' ),
			'BR' => esc_html__( 'Brazil', 'simple-seo-improvements' ),
			'IO' => esc_html__( 'British Indian Ocean Territory', 'simple-seo-improvements' ),
			'BN' => esc_html__( 'Brunei Darussalam', 'simple-seo-improvements' ),
			'BG' => esc_html__( 'Bulgaria', 'simple-seo-improvements' ),
			'BF' => esc_html__( 'Burkina Faso', 'simple-seo-improvements' ),
			'BI' => esc_html__( 'Burundi', 'simple-seo-improvements' ),
			'CV' => esc_html__( 'Cabo Verde', 'simple-seo-improvements' ),
			'KH' => esc_html__( 'Cambodia', 'simple-seo-improvements' ),
			'CM' => esc_html__( 'Cameroon', 'simple-seo-improvements' ),
			'CA' => esc_html__( 'Canada', 'simple-seo-improvements' ),
			'KY' => esc_html__( 'Cayman Islands', 'simple-seo-improvements' ),
			'CF' => esc_html__( 'Central African Republic', 'simple-seo-improvements' ),
			'TD' => esc_html__( 'Chad', 'simple-seo-improvements' ),
			'CL' => esc_html__( 'Chile', 'simple-seo-improvements' ),
			'CN' => esc_html__( 'China', 'simple-seo-improvements' ),
			'CX' => esc_html__( 'Christmas Island', 'simple-seo-improvements' ),
			'CC' => esc_html__( 'Cocos (Keeling) Islands', 'simple-seo-improvements' ),
			'CO' => esc_html__( 'Colombia', 'simple-seo-improvements' ),
			'KM' => esc_html__( 'Comoros', 'simple-seo-improvements' ),
			'CG' => esc_html__( 'Congo', 'simple-seo-improvements' ),
			'CD' => esc_html__( 'Congo, Democratic Republic of the', 'simple-seo-improvements' ),
			'CK' => esc_html__( 'Cook Islands', 'simple-seo-improvements' ),
			'CR' => esc_html__( 'Costa Rica', 'simple-seo-improvements' ),
			'CI' => esc_html__( 'Côte d\'Ivoire', 'simple-seo-improvements' ),
			'HR' => esc_html__( 'Croatia', 'simple-seo-improvements' ),
			'CU' => esc_html__( 'Cuba', 'simple-seo-improvements' ),
			'CW' => esc_html__( 'Curaçao', 'simple-seo-improvements' ),
			'CY' => esc_html__( 'Cyprus', 'simple-seo-improvements' ),
			'CZ' => esc_html__( 'Czechia', 'simple-seo-improvements' ),
			'DK' => esc_html__( 'Denmark', 'simple-seo-improvements' ),
			'DJ' => esc_html__( 'Djibouti', 'simple-seo-improvements' ),
			'DM' => esc_html__( 'Dominica', 'simple-seo-improvements' ),
			'DO' => esc_html__( 'Dominican Republic', 'simple-seo-improvements' ),
			'EC' => esc_html__( 'Ecuador', 'simple-seo-improvements' ),
			'EG' => esc_html__( 'Egypt', 'simple-seo-improvements' ),
			'SV' => esc_html__( 'El Salvador', 'simple-seo-improvements' ),
			'GQ' => esc_html__( 'Equatorial Guinea', 'simple-seo-improvements' ),
			'ER' => esc_html__( 'Eritrea', 'simple-seo-improvements' ),
			'EE' => esc_html__( 'Estonia', 'simple-seo-improvements' ),
			'SZ' => esc_html__( 'Eswatini', 'simple-seo-improvements' ),
			'ET' => esc_html__( 'Ethiopia', 'simple-seo-improvements' ),
			'FK' => esc_html__( 'Falkland Islands( Malvinas )', 'simple-seo-improvements' ),
			'FO' => esc_html__( 'Faroe Islands', 'simple-seo-improvements' ),
			'FJ' => esc_html__( 'Fiji', 'simple-seo-improvements' ),
			'FI' => esc_html__( 'Finland', 'simple-seo-improvements' ),
			'FR' => esc_html__( 'France', 'simple-seo-improvements' ),
			'GF' => esc_html__( 'French Guiana', 'simple-seo-improvements' ),
			'PF' => esc_html__( 'French Polynesia', 'simple-seo-improvements' ),
			'TF' => esc_html__( 'French Southern Territories', 'simple-seo-improvements' ),
			'GA' => esc_html__( 'Gabon', 'simple-seo-improvements' ),
			'GM' => esc_html__( 'Gambia', 'simple-seo-improvements' ),
			'GE' => esc_html__( 'Georgia', 'simple-seo-improvements' ),
			'DE' => esc_html__( 'Germany', 'simple-seo-improvements' ),
			'GH' => esc_html__( 'Ghana', 'simple-seo-improvements' ),
			'GI' => esc_html__( 'Gibraltar', 'simple-seo-improvements' ),
			'GR' => esc_html__( 'Greece', 'simple-seo-improvements' ),
			'GL' => esc_html__( 'Greenland', 'simple-seo-improvements' ),
			'GD' => esc_html__( 'Grenada', 'simple-seo-improvements' ),
			'GP' => esc_html__( 'Guadeloupe', 'simple-seo-improvements' ),
			'GU' => esc_html__( 'Guam', 'simple-seo-improvements' ),
			'GT' => esc_html__( 'Guatemala', 'simple-seo-improvements' ),
			'GG' => esc_html__( 'Guernsey', 'simple-seo-improvements' ),
			'GN' => esc_html__( 'Guinea', 'simple-seo-improvements' ),
			'GW' => esc_html__( 'Guinea - Bissau', 'simple-seo-improvements' ),
			'GY' => esc_html__( 'Guyana', 'simple-seo-improvements' ),
			'HT' => esc_html__( 'Haiti', 'simple-seo-improvements' ),
			'HM' => esc_html__( 'Heard Island and McDonald Islands', 'simple-seo-improvements' ),
			'VA' => esc_html__( 'Holy See', 'simple-seo-improvements' ),
			'HN' => esc_html__( 'Honduras', 'simple-seo-improvements' ),
			'HK' => esc_html__( 'Hong Kong', 'simple-seo-improvements' ),
			'HU' => esc_html__( 'Hungary', 'simple-seo-improvements' ),
			'IS' => esc_html__( 'Iceland', 'simple-seo-improvements' ),
			'IN' => esc_html__( 'India', 'simple-seo-improvements' ),
			'ID' => esc_html__( 'Indonesia', 'simple-seo-improvements' ),
			'IR' => esc_html__( 'Iran, Islamic Republic of', 'simple-seo-improvements' ),
			'IQ' => esc_html__( 'Iraq', 'simple-seo-improvements' ),
			'IE' => esc_html__( 'Ireland', 'simple-seo-improvements' ),
			'IM' => esc_html__( 'Isle of Man', 'simple-seo-improvements' ),
			'IL' => esc_html__( 'Israel', 'simple-seo-improvements' ),
			'IT' => esc_html__( 'Italy', 'simple-seo-improvements' ),
			'JM' => esc_html__( 'Jamaica', 'simple-seo-improvements' ),
			'JP' => esc_html__( 'Japan', 'simple-seo-improvements' ),
			'JE' => esc_html__( 'Jersey', 'simple-seo-improvements' ),
			'JO' => esc_html__( 'Jordan', 'simple-seo-improvements' ),
			'KZ' => esc_html__( 'Kazakhstan', 'simple-seo-improvements' ),
			'KE' => esc_html__( 'Kenya', 'simple-seo-improvements' ),
			'KI' => esc_html__( 'Kiribati', 'simple-seo-improvements' ),
			'KP' => esc_html__( 'Korea, Democratic People\'s Republic of', 'simple-seo-improvements' ),
			'KR' => esc_html__( 'Korea, Republic of', 'simple-seo-improvements' ),
			'KW' => esc_html__( 'Kuwait', 'simple-seo-improvements' ),
			'KG' => esc_html__( 'Kyrgyzstan', 'simple-seo-improvements' ),
			'LA' => esc_html__( 'Lao People\'s Democratic Republic', 'simple-seo-improvements' ),
			'LV' => esc_html__( 'Latvia', 'simple-seo-improvements' ),
			'LB' => esc_html__( 'Lebanon', 'simple-seo-improvements' ),
			'LS' => esc_html__( 'Lesotho', 'simple-seo-improvements' ),
			'LR' => esc_html__( 'Liberia', 'simple-seo-improvements' ),
			'LY' => esc_html__( 'Libya', 'simple-seo-improvements' ),
			'LI' => esc_html__( 'Liechtenstein', 'simple-seo-improvements' ),
			'LT' => esc_html__( 'Lithuania', 'simple-seo-improvements' ),
			'LU' => esc_html__( 'Luxembourg', 'simple-seo-improvements' ),
			'MO' => esc_html__( 'Macao', 'simple-seo-improvements' ),
			'MG' => esc_html__( 'Madagascar', 'simple-seo-improvements' ),
			'MW' => esc_html__( 'Malawi', 'simple-seo-improvements' ),
			'MY' => esc_html__( 'Malaysia', 'simple-seo-improvements' ),
			'MV' => esc_html__( 'Maldives', 'simple-seo-improvements' ),
			'ML' => esc_html__( 'Mali', 'simple-seo-improvements' ),
			'MT' => esc_html__( 'Malta', 'simple-seo-improvements' ),
			'MH' => esc_html__( 'Marshall Islands', 'simple-seo-improvements' ),
			'MQ' => esc_html__( 'Martinique', 'simple-seo-improvements' ),
			'MR' => esc_html__( 'Mauritania', 'simple-seo-improvements' ),
			'MU' => esc_html__( 'Mauritius', 'simple-seo-improvements' ),
			'YT' => esc_html__( 'Mayotte', 'simple-seo-improvements' ),
			'MX' => esc_html__( 'Mexico', 'simple-seo-improvements' ),
			'FM' => esc_html__( 'Micronesia, Federated States of', 'simple-seo-improvements' ),
			'MD' => esc_html__( 'Moldova, Republic of', 'simple-seo-improvements' ),
			'MC' => esc_html__( 'Monaco', 'simple-seo-improvements' ),
			'MN' => esc_html__( 'Mongolia', 'simple-seo-improvements' ),
			'ME' => esc_html__( 'Montenegro', 'simple-seo-improvements' ),
			'MS' => esc_html__( 'Montserrat', 'simple-seo-improvements' ),
			'MA' => esc_html__( 'Morocco', 'simple-seo-improvements' ),
			'MZ' => esc_html__( 'Mozambique', 'simple-seo-improvements' ),
			'MM' => esc_html__( 'Myanmar', 'simple-seo-improvements' ),
			'NA' => esc_html__( 'Namibia', 'simple-seo-improvements' ),
			'NR' => esc_html__( 'Nauru', 'simple-seo-improvements' ),
			'NP' => esc_html__( 'Nepal', 'simple-seo-improvements' ),
			'NL' => esc_html__( 'Netherlands, Kingdom of the', 'simple-seo-improvements' ),
			'NC' => esc_html__( 'New Caledonia', 'simple-seo-improvements' ),
			'NZ' => esc_html__( 'New Zealand', 'simple-seo-improvements' ),
			'NI' => esc_html__( 'Nicaragua', 'simple-seo-improvements' ),
			'NE' => esc_html__( 'Niger', 'simple-seo-improvements' ),
			'NG' => esc_html__( 'Nigeria', 'simple-seo-improvements' ),
			'NU' => esc_html__( 'Niue', 'simple-seo-improvements' ),
			'NF' => esc_html__( 'Norfolk Island', 'simple-seo-improvements' ),
			'MK' => esc_html__( 'North Macedonia', 'simple-seo-improvements' ),
			'MP' => esc_html__( 'Northern Mariana Islands', 'simple-seo-improvements' ),
			'NO' => esc_html__( 'Norway', 'simple-seo-improvements' ),
			'OM' => esc_html__( 'Oman', 'simple-seo-improvements' ),
			'PK' => esc_html__( 'Pakistan', 'simple-seo-improvements' ),
			'PW' => esc_html__( 'Palau', 'simple-seo-improvements' ),
			'PS' => esc_html__( 'Palestine, State of', 'simple-seo-improvements' ),
			'PA' => esc_html__( 'Panama', 'simple-seo-improvements' ),
			'PG' => esc_html__( 'Papua New Guinea', 'simple-seo-improvements' ),
			'PY' => esc_html__( 'Paraguay', 'simple-seo-improvements' ),
			'PE' => esc_html__( 'Peru', 'simple-seo-improvements' ),
			'PH' => esc_html__( 'Philippines', 'simple-seo-improvements' ),
			'PN' => esc_html__( 'Pitcairn', 'simple-seo-improvements' ),
			'PL' => esc_html__( 'Poland', 'simple-seo-improvements' ),
			'PT' => esc_html__( 'Portugal', 'simple-seo-improvements' ),
			'PR' => esc_html__( 'Puerto Rico', 'simple-seo-improvements' ),
			'QA' => esc_html__( 'Qatar', 'simple-seo-improvements' ),
			'RE' => esc_html__( 'Réunion', 'simple-seo-improvements' ),
			'RO' => esc_html__( 'Romania', 'simple-seo-improvements' ),
			'RU' => esc_html__( 'Russian Federation', 'simple-seo-improvements' ),
			'RW' => esc_html__( 'Rwanda', 'simple-seo-improvements' ),
			'BL' => esc_html__( 'Saint Barthélemy', 'simple-seo-improvements' ),
			'SH' => esc_html__( 'Saint Helena, Ascension and Tristan da Cunha', 'simple-seo-improvements' ),
			'KN' => esc_html__( 'Saint Kitts and Nevis', 'simple-seo-improvements' ),
			'LC' => esc_html__( 'Saint Lucia', 'simple-seo-improvements' ),
			'MF' => esc_html__( 'Saint Martin (French part)', 'simple-seo-improvements' ),
			'PM' => esc_html__( 'Saint Pierre and Miquelon', 'simple-seo-improvements' ),
			'VC' => esc_html__( 'Saint Vincent and the Grenadines', 'simple-seo-improvements' ),
			'WS' => esc_html__( 'Samoa', 'simple-seo-improvements' ),
			'SM' => esc_html__( 'San Marino', 'simple-seo-improvements' ),
			'ST' => esc_html__( 'Sao Tome and Principe', 'simple-seo-improvements' ),
			'SA' => esc_html__( 'Saudi Arabia', 'simple-seo-improvements' ),
			'SN' => esc_html__( 'Senegal', 'simple-seo-improvements' ),
			'RS' => esc_html__( 'Serbia', 'simple-seo-improvements' ),
			'SC' => esc_html__( 'Seychelles', 'simple-seo-improvements' ),
			'SL' => esc_html__( 'Sierra Leone', 'simple-seo-improvements' ),
			'SG' => esc_html__( 'Singapore', 'simple-seo-improvements' ),
			'SX' => esc_html__( 'Sint Maarten (Dutch part)', 'simple-seo-improvements' ),
			'SK' => esc_html__( 'Slovakia', 'simple-seo-improvements' ),
			'SI' => esc_html__( 'Slovenia', 'simple-seo-improvements' ),
			'SB' => esc_html__( 'Solomon Islands', 'simple-seo-improvements' ),
			'SO' => esc_html__( 'Somalia', 'simple-seo-improvements' ),
			'ZA' => esc_html__( 'South Africa', 'simple-seo-improvements' ),
			'GS' => esc_html__( 'South Georgia and the South Sandwich Islands', 'simple-seo-improvements' ),
			'SS' => esc_html__( 'South Sudan', 'simple-seo-improvements' ),
			'ES' => esc_html__( 'Spain', 'simple-seo-improvements' ),
			'LK' => esc_html__( 'Sri Lanka', 'simple-seo-improvements' ),
			'SD' => esc_html__( 'Sudan', 'simple-seo-improvements' ),
			'SR' => esc_html__( 'Suriname', 'simple-seo-improvements' ),
			'SJ' => esc_html__( 'Svalbard and Jan Mayen', 'simple-seo-improvements' ),
			'SE' => esc_html__( 'Sweden', 'simple-seo-improvements' ),
			'CH' => esc_html__( 'Switzerland', 'simple-seo-improvements' ),
			'SY' => esc_html__( 'Syrian Arab Republic', 'simple-seo-improvements' ),
			'TW' => esc_html__( 'Taiwan, Province of China', 'simple-seo-improvements' ),
			'TJ' => esc_html__( 'Tajikistan', 'simple-seo-improvements' ),
			'TZ' => esc_html__( 'Tanzania, United Republic of', 'simple-seo-improvements' ),
			'TH' => esc_html__( 'Thailand', 'simple-seo-improvements' ),
			'TL' => esc_html__( 'Timor-Leste', 'simple-seo-improvements' ),
			'TG' => esc_html__( 'Togo', 'simple-seo-improvements' ),
			'TK' => esc_html__( 'Tokelau', 'simple-seo-improvements' ),
			'TO' => esc_html__( 'Tonga', 'simple-seo-improvements' ),
			'TT' => esc_html__( 'Trinidad and Tobago', 'simple-seo-improvements' ),
			'TN' => esc_html__( 'Tunisia', 'simple-seo-improvements' ),
			'TR' => esc_html__( 'Türkiye', 'simple-seo-improvements' ),
			'TM' => esc_html__( 'Turkmenistan', 'simple-seo-improvements' ),
			'TC' => esc_html__( 'Turks and Caicos Islands', 'simple-seo-improvements' ),
			'TV' => esc_html__( 'Tuvalu', 'simple-seo-improvements' ),
			'UG' => esc_html__( 'Uganda', 'simple-seo-improvements' ),
			'UA' => esc_html__( 'Ukraine', 'simple-seo-improvements' ),
			'AE' => esc_html__( 'United Arab Emirates', 'simple-seo-improvements' ),
			'GB' => esc_html__( 'United Kingdom of Great Britain and Northern Ireland', 'simple-seo-improvements' ),
			'US' => esc_html__( 'United States of America', 'simple-seo-improvements' ),
			'UM' => esc_html__( 'United States Minor Outlying Islands', 'simple-seo-improvements' ),
			'UY' => esc_html__( 'Uruguay', 'simple-seo-improvements' ),
			'UZ' => esc_html__( 'Uzbekistan', 'simple-seo-improvements' ),
			'VU' => esc_html__( 'Vanuatu', 'simple-seo-improvements' ),
			'VE' => esc_html__( 'Venezuela, Bolivarian Republic of', 'simple-seo-improvements' ),
			'VN' => esc_html__( 'Viet Nam', 'simple-seo-improvements' ),
			'VG' => esc_html__( 'Virgin Islands (British)', 'simple-seo-improvements' ),
			'VI' => esc_html__( 'Virgin Islands (U.S.)', 'simple-seo-improvements' ),
			'WF' => esc_html__( 'Wallis and Futuna', 'simple-seo-improvements' ),
			'EH' => esc_html__( 'Western Sahara', 'simple-seo-improvements' ),
			'YE' => esc_html__( 'Yemen', 'simple-seo-improvements' ),
			'ZM' => esc_html__( 'Zambia', 'simple-seo-improvements' ),
			'ZW' => esc_html__( 'Zimbabwe', 'simple-seo-improvements' ),
		);
		asort( $options );
		array_unshift( $options, esc_html__( '--- select ---', 'simple-seo-improvements' ) );
		return $options;
	}

		/**
		 * register plugin to iWorks Rate Helper
		 *
		 * @since 1.0.0
		 */
	public function action_init_register_iworks_rate() {
		if ( ! class_exists( 'iworks_rate' ) ) {
			include_once dirname( __FILE__ ) . '/rate/rate.php';
		}
		do_action(
			'iworks-register-plugin',
			plugin_basename( $this->plugin_file ),
			__( 'Simple SEO Improvements', 'simple-seo-improvements' ),
			'simple-seo-improvements'
		);
	}

}

