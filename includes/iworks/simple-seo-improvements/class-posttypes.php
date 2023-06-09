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

if ( class_exists( 'iworks_simple_seo_improvements_posttypes' ) ) {
	return;
}

require_once dirname( __FILE__ ) . '/class-base.php';

class iworks_simple_seo_improvements_posttypes extends iworks_simple_seo_improvements_base {

	private $iworks;

	private $fields;

	public function __construct( $iworks ) {
		$this->iworks = $iworks;
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'edit_attachment', array( $this, 'save_data' ) );
		add_action( 'save_post', array( $this, 'save_data' ) );
		add_filter( 'simple_seo_improvements_wp_head', array( $this, 'filter_add_robots' ) );
		add_filter( 'simple_seo_improvements_wp_head', array( $this, 'filter_add_meta_description' ) );
		add_filter( 'document_title_parts', array( $this, 'change_wp_title' ) );
		/**
		 * integration with OG plugin
		 *
		 * @since 1.0.1
		 */
		add_filter( 'og_array', array( $this, 'filter_og_array' ) );
		/**
		 * options
		 */
		$this->options = get_iworks_simple_seo_improvements_options();
		$this->set_robots_options();
	}

	/**
	 * get data
	 *
	 * @since 1.0.0
	 */
	private function get_data( $post_id, $mode = 'front' ) {
		$data = wp_parse_args(
			get_post_meta( $post_id, $this->field_name, true ),
			array(
				'robots'      => array(),
				'title'       => '',
				'description' => '',
			)
		);
		/**
		 * set params
		 */
		$description = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
		if ( empty( $description ) ) {
			$description = html_entity_decode( get_the_excerpt() );
		}
		/**
		 * get post type
		 */
		$post_type = 'any_post_type';
		if ( 'common' !== $this->options->get_option( 'post_types' ) ) {
			$post_type = get_post_type();
		}
		/**
		 * force?
		 */
		if ( 'front' === $mode ) {
			$name = sprintf( '%s_mode', $post_type );
			if ( 'force' === $this->options->get_option( $name ) ) {
				foreach ( $this->robots_options as $key ) {
					$data['robots'][ $key ] = intval( $this->options->get_option( sprintf( '%s_%s', $post_type, $key ) ) );
				}
			}
		}
		/**
		 * always for auto draw
		 */
		if ( 'auto-draft' === get_post_status() ) {
			foreach ( $this->robots_options as $key ) {
				$data['robots'][ $key ] = intval( $this->options->get_option( sprintf( '%s_%s', $post_type, $key ) ) );
			}
		}
		/**
		 * defaults
		 */
		foreach ( $this->robots_options as $key ) {
			if ( ! isset( $data['robots'][ $key ] ) ) {
				$data['robots'][ $key ] = 0;
			}
		}
		/**
		 * return
		 */
		$data = wp_parse_args(
			$data,
			array(
				'robots'      => array(),
				'title'       => get_post_meta( $post_id, '_yoast_wpseo_title', true ),
				'description' => $description,
			)
		);
		return $data;
	}

	/**
	 * check shuld we change?
	 *
	 * @since 1.0.0
	 */
	private function should_we_change_anything() {
		if ( is_admin() ) {
			return false;
		}
		if ( is_singular() ) {
			return true;
		}
		if ( is_home() ) {
			return true;
		}
		return false;
	}

	/**
	 * get custom title from meta
	 *
	 * @since 1.0.1
	 */
	private function get_custom_title_of_current_post() {
		if ( ! $this->should_we_change_anything() ) {
			return;
		}
		$data = $this->get_custom_data();
		if (
			empty( $data )
			|| ! is_array( $data )
			|| ! isset( $data['title'] )
			|| ! is_string( $data['title'] )
			|| empty( $data['title'] )
		) {
			return;
		}
		return $this->compress_all_whitespaces( $data['title'] );
	}

	/**
	 * Change HTML title element
	 *
	 * @since 1.0.0
	 */
	public function change_wp_title( $parts ) {
		if ( ! $this->should_we_change_anything() ) {
			return $parts;
		}
		$title = $this->get_custom_title_of_current_post();
		if ( empty( $title ) ) {
			return $parts;
		}
		$parts['title'] = $title;
		return $parts;
	}

	public function filter_add_meta_description( $content ) {
		if ( ! $this->should_we_change_anything() ) {
			return $content;
		}
		if ( is_home() && is_front_page() ) {
			return $content;
		}
		$value = '';
		$data  = $this->get_custom_data();
		if (
			! empty( $data )
			&& is_array( $data )
			&& isset( $data['description'] )
			&& is_string( $data['description'] )
			&& ! empty( $data['description'] )
		) {
			$value = $data['description'];
		}
		if ( empty( $value ) ) {
			$value = get_the_excerpt();
		}
		if ( empty( $value ) ) {
			return $content;
		}
		$content .= sprintf(
			'<meta name="description" content="%s" />%s',
			esc_attr( $this->compress_all_whitespaces( $value ) ),
			PHP_EOL
		);
		return $content;
	}

	/**
	 * Add meta "robots" tag.
	 *
	 * @since 1.0.0
	 * @since 1.2.0 become be a filter
	 */
	public function filter_add_robots( $content ) {
		if ( is_admin() || ! is_singular() ) {
			return $content;
		}
		/**
		 * Google
		 */
		if ( '0' === $this->options->get_option( 'robots_googlebot' ) ) {
			$content .= '<meta name="googlebot" content="noindex">' . PHP_EOL;
		}
		if ( '0' === $this->options->get_option( 'robots_googlebot_news' ) ) {
			$content .= '<meta name="googlebot-news" content="noindex">' . PHP_EOL;
		}

		/**
		 * maybe do not add?
		 *
		 * @since 1.5.0
		 */
		if ( 'no' === $this->options->get_option( 'post_types' ) ) {
			return $content;
		}
		$data  = $this->get_data( get_the_ID() );
		$value = $this->get_meta_robots_commons();
		foreach ( $this->robots_options as $key ) {
			if ( isset( $data['robots'][ $key ] ) && $data['robots'][ $key ] ) {
				$value[] = $key;
			}
		}
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
	 * Add entry edit metabox.
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {
		$args       = apply_filters(
			'iworks_simple_seo_improvements_get_post_types_args',
			array(
				'public' => true,
			)
		);
		$post_types = get_post_types( $args );
		foreach ( $post_types as $post_type ) {
			if ( 'force' === $this->options->get_option( $post_type . '_mode' ) ) {
				continue;
			}
			add_meta_box(
				'iworks_simple_seo_improvements',
				__( 'Simple SEO Improvements', 'simple-seo-improvements' ),
				array( $this, 'meta_box_html' ),
				$post_type
			);
		}
	}

	/**
	 * entry metabox html content
	 *
	 * @since 1.0.0
	 */
	public function meta_box_html( $post ) {
		$this->add_nonce();
		$data = $this->get_data( $post->ID, 'admin' );
		?>
<div>
	<h3><label for="iworks_simple_seo_improvements_html_title"><?php esc_html_e( 'HTML Title', 'simple-seo-improvements' ); ?></label></h3>
	<input type="text" name="<?php echo esc_attr( $this->field_name ); ?>[title]" value="<?php echo esc_attr( $data['title'] ); ?>" id="iworks_simple_seo_improvements_html_title" class="large-text" autocomplete="off" />
</div>
<div>
	<h3><?php esc_html_e( 'Robots', 'simple-seo-improvements' ); ?></h3>
	<ul>
		<?php
		foreach ( $this->robots_options as $key ) {
			$value = isset( $data['robots'][ $key ] ) ? $data['robots'][ $key ] : 0;
			echo '<li><label>';
			printf(
				'<input type="checkbox" name="%s[robots][%s]" value="1" %s /> %s',
				esc_attr( $this->field_name ),
				esc_attr( $key ),
				checked( $value, 1, false ),
				sprintf(
					esc_html__( 'Add "%s".', 'simple-seo-improvements' ),
					$key
				)
			);
			echo '</label></li>';
		}
		?>
	</ul>
</div>
<div>
	<h3><label for="iworks_simple_seo_improvements_html_description"><?php esc_html_e( 'HTML description', 'simple-seo-improvements' ); ?></label></h3>
	<textarea rows="6" name="<?php echo esc_attr( $this->field_name ); ?>[description]" id="iworks_simple_seo_improvements_html_description" class="large-text" autocomplete="off"><?php echo esc_attr( $data['description'] ); ?></textarea>
</div>
		<?php
	}

	/**
	 * save custom data
	 *
	 * @since 1.0.0
	 */
	public function save_data( $post_id ) {
		if ( ! $this->check_nonce() ) {
			return;
		}
		$data = $this->get_post_data();
		$this->update_single_post_meta( $post_id, $this->field_name, $data );
	}

	/**
	 * Filter OG array from OG plugin
	 *
	 * @since 1.0.1
	 */
	public function filter_og_array( $og ) {
		$title = $this->get_custom_title_of_current_post();
		if ( ! empty( $title ) ) {
			$og['og']['title'] = $title;
			if ( isset( $og['schema'] ) ) {
				$og['schema']['name'] = $title;
			}
		}
		/**
		 * @since 1.5.3
		 */
		$data = $this->get_custom_data();
		if (
			isset( $data['description'] )
			&& ! empty( $data['description'] )
		) {
			$description = $this->compress_all_whitespaces( $data['description'] );
			if ( ! empty( $description ) ) {
				$og['og']['description'] = $description;
				if ( isset( $og['schema'] ) ) {
					$og['schema']['description'] = $description;
				}
			}
		}
		return $og;
	}

	/**
	 * get data
	 *
	 * @since 1.0.3
	 */
	private function get_custom_data() {
		if ( is_home() ) {
			$post_id = intval( get_option( 'page_for_posts' ) );
			if ( empty( $post_id ) ) {
				return array();
			}
			return $this->get_data( $post_id );
		}
		return $this->get_data( get_the_ID() );
	}
}


