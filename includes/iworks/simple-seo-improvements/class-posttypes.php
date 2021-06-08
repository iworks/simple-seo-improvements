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
		add_action( 'save_post', array( $this, 'save_data' ) );
		add_action( 'wp_head', array( $this, 'add_robots' ) );
		add_action( 'wp_head', array( $this, 'add_meta_description' ) );
		add_filter( 'document_title_parts', array( $this, 'change_wp_title' ) );
		/**
		 * integration with OG plugin
		 *
		 * @since 1.0.1
		 */
		add_filter( 'og_array', array( $this, 'filter_og_array' ) );
	}

	/**
	 * get data
	 *
	 * @since 1.0.0
	 */
	private function get_data( $post_id ) {
		$data = get_post_meta( $post_id, $this->field_name, true );
		if ( ! empty( $data ) && is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( empty( $value ) ) {
					unset( $data[ $key ] );
				}
			}
		}
		$description = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
		if ( empty( $description ) ) {
			$description = html_entity_decode( get_the_excerpt() );
		}
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
		return is_singular();
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
		$data = $this->get_data( get_the_ID() );
		if (
			empty( $data )
			|| ! is_array( $data )
			|| ! isset( $data['title'] )
			|| ! is_string( $data['title'] )
			|| empty( $data['title'] )
		) {
			return;
		}
		return $data['title'];
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

	public function add_meta_description() {
		if ( ! $this->should_we_change_anything() ) {
			return;
		}
		$value = get_the_excerpt();
		$data  = get_post_meta( get_the_ID(), $this->field_name, true );
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
			return;
		}
		printf(
			'<meta name="description" content="%s" />%s',
			esc_attr( $value ),
			PHP_EOL
		);
	}

	/**
	 * Add meta "robots" tag.
	 *
	 * @since 1.0.0
	 */
	public function add_robots() {
		if ( is_admin() || ! is_singular() ) {
			return;
		}
		$data = get_post_meta( get_the_ID(), $this->field_name, true );
		if (
			empty( $data )
			|| ! is_array( $data )
			|| ! isset( $data['robots'] )
			|| ! is_array( $data['robots'] )
			|| empty( $data['robots'] )
		) {
			return;
		}
		printf(
			'<meta name="robots" content="%s" />%s',
			esc_attr( implode( ', ', array_keys( $data['robots'] ) ) ),
			PHP_EOL
		);
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
			add_meta_box( 'iworks_simple_seo_improvements', __( 'Simple SEO Improvements', 'Simple' ), array( $this, 'meta_box_html' ), $post_type );
		}
	}

	/**
	 * entry metabox html content
	 *
	 * @since 1.0.0
	 */
	public function meta_box_html( $post ) {
		$this->add_nonce();
		$data = $this->get_data( $post->ID );
		?>
<div>
	<h3><label for="iworks_simple_seo_improvements_html_title"><?php esc_html_e( 'HTML Title', 'simple-seo-improvements' ); ?></label></h3>
	<input type="text" name="<?php echo esc_attr( $this->field_name ); ?>[title]" value="<?php echo esc_attr( $data['title'] ); ?>" id="iworks_simple_seo_improvements_html_title" class="large-text" autocomplete="off" />
</div>
<div>
	<h3><?php esc_html_e( 'Robots', 'simple-seo-improvements' ); ?></h3>
	<ul>
		<?php
		$options = array(
			'noindex',
			'nofollow',
			'noimageindex',
			'noarchive',
			'nocache',
			'nosnippet',
			'notranslate',
			'noyaca',
		);
		foreach ( $options as $key ) {
			$value = false;
			if ( isset( $data['robots'] ) && isset( $data['robots'][ $key ] ) ) {
				$value = true;
			}
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
		}
		return $og;
	}
}


