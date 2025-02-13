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

if ( class_exists( 'iworks_simple_seo_improvements_taxonomies' ) ) {
	return;
}

require_once __DIR__ . '/class-iworks-simple-seo-improvements-base-abstract.php';

class iworks_simple_seo_improvements_taxonomies extends iworks_simple_seo_improvements_base_abstract {

	private $iworks;

	public function __construct( $iworks ) {
		$this->iworks = $iworks;
		add_action( 'admin_init', array( $this, 'admin_init' ), PHP_INT_MAX );
		add_action( 'wp_head', array( $this, 'add_robots' ) );
		add_action( 'wp_head', array( $this, 'add_meta_description' ) );
		add_filter( 'document_title_parts', array( $this, 'change_wp_title' ) );
	}

	public function admin_init() {
		$args       = apply_filters(
			'iworks_simple_seo_improvements_get_taxonomies_args',
			array(
				'public' => true,
			)
		);
		$taxonomies = get_taxonomies( $args );
		foreach ( $taxonomies as $taxonomy ) {
			add_action( $taxonomy . '_add_form_fields', array( $this, 'add_fields_to_add_form' ) );
			add_action( $taxonomy . '_edit_form_fields', array( $this, 'add_fields_to_edit_form' ), 10, 2 );
			add_action( 'edited_' . $taxonomy, array( $this, 'save_data' ), 10, 2 );
			add_action( 'create_' . $taxonomy, array( $this, 'save_data' ), 10, 2 );
		}
	}

	private function get_data( $term_id ) {
		$description = get_term_meta( $term_id, 'custom_description', true );
		if ( empty( $description ) ) {
			$description = html_entity_decode( strip_tags( term_description() ) );
		}
		$data = get_term_meta( $term_id, $this->field_name, true );
		if ( ! empty( $data ) && is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( empty( $value ) ) {
					unset( $data[ $key ] );
				}
			}
		}
		return wp_parse_args(
			$data,
			array(
				'robots'      => array(),
				'title'       => get_term_meta( $term_id, 'custom_title', true ),
				'description' => is_wp_error( $description ) ? '' : $description,
			)
		);
	}

	private function get_title_label() {
		return sprintf(
			'<label for="iworks_simple_seo_improvements_html_title">%s</label>',
			esc_html__( 'HTML Title', 'simple-seo-improvements' )
		);
	}

	private function get_title_field( $data ) {
		return sprintf(
			'<input type="text" name="%s[title]" value="%s" id="iworks_simple_seo_improvements_html_title" class="large-text" autocomplete="off" />',
			esc_attr( $this->field_name ),
			esc_attr( $data['title'] )
		);
	}

	private function get_description_label() {
		return sprintf(
			'<label for="iworks_simple_seo_improvements_html_description">%s</label>',
			esc_html__( 'HTML description', 'simple-seo-improvements' )
		);
	}

	private function get_description_field( $data ) {
		return sprintf(
			'<textarea rows=6" name="%s[description]" id="iworks_simple_seo_improvements_html_description" class="large-text" autocomplete="off">%s</textarea>',
			esc_attr( $this->field_name ),
			esc_attr( $data['description'] )
		);
	}

	private function get_robots_label() {
		return sprintf(
			'<label>%s</label>',
			esc_html__( 'Robots', 'simple-seo-improvements' )
		);
	}

	private function get_robots_field( $data ) {
		$content = '<ul>';
		$this->set_robots_options();
		foreach ( $this->robots_options as $key ) {
			$value = false;
			if ( isset( $data['robots'] ) && isset( $data['robots'][ $key ] ) ) {
				$value = true;
			}
			$content .= '<li><label>';
			$content .= sprintf(
				'<input type="checkbox" name="%s[robots][%s]" value="1" %s /> %s',
				esc_attr( $this->field_name ),
				esc_attr( $key ),
				checked( $value, 1, false ),
				sprintf(
					esc_html__( 'Add "%s".', 'simple-seo-improvements' ),
					$key
				)
			);
			$content .= '</label></li>';
		}
		$content .= '</ul>';
		return $content;
	}

	public function add_fields_to_add_form( $taxonomy ) {
		$this->add_nonce();
		$data = array(
			'title'       => '',
			'robots'      => array(),
			'description' => '',
		);
		?>
<div class="form-field">
		<?php echo $this->get_title_label(); ?>
		<?php echo $this->get_title_field( $data ); ?>
</div>
<div class="form-field">
		<?php echo $this->get_description_label(); ?>
		<?php echo $this->get_description_field( $data ); ?>
</div>
<div class="form-field">
		<?php echo $this->get_robots_label(); ?>
		<?php echo $this->get_robots_field( $data ); ?>
</div>
		<?php
	}

	public function add_fields_to_edit_form( $tag, $taxonomy ) {
		$data = $this->get_data( $tag->term_id );
		?>
<tr class="form-field">
	<th scope="row" valign="top"><?php echo $this->get_title_label(); ?></th>
	<td>
		<?php
		echo $this->get_title_field( $data );
		$this->add_nonce();
		?>
	</td>
</tr>
<tr class="form-field">
	<th scope="row" valign="top"><?php echo $this->get_description_label(); ?></th>
	<td><?php echo $this->get_description_field( $data ); ?></td>
</tr>
<tr class="form-field">
	<th scope="row" valign="top"><?php echo $this->get_robots_label(); ?></th>
	<td><?php echo $this->get_robots_field( $data ); ?></td>
</tr>
		<?php
	}

	public function save_data( $term_id, $tt_id ) {
		if ( ! $this->check_nonce() ) {
			return;
		}
		$data = $this->get_post_data();
		$this->update_single_term_meta( $term_id, $this->field_name, $data );
	}

	private function should_we_change_anything() {
		if ( is_admin() ) {
			return false;
		}
		return is_tag() || is_category() || is_tax();
	}

	public function change_wp_title( $parts ) {
		if ( ! $this->should_we_change_anything() ) {
			return $parts;
		}
		$data = $this->get_data( get_queried_object_id() );
		if (
			empty( $data )
			|| ! is_array( $data )
			|| ! isset( $data['title'] )
			|| ! is_string( $data['title'] )
			|| empty( $data['title'] )
		) {
			return $parts;
		}
		$parts['title'] = $this->compress_all_whitespaces( $data['title'] );
		return $parts;
	}

	public function add_meta_description() {
		if ( ! $this->should_we_change_anything() ) {
			return;
		}
		$data  = $this->get_data( get_queried_object_id() );
		$value = '';
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
			'<meta name="description" content="%s">%s',
			esc_attr( $this->compress_all_whitespaces( $value ) ),
			PHP_EOL
		);
	}

	public function add_robots() {
		if ( is_admin() || ! is_tax() ) {
			return;
		}
		$data = $this->get_data( get_queried_object_id() );
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
			'<meta name="robots" content="%s">%s',
			esc_attr( implode( ', ', array_keys( $data['robots'] ) ) ),
			PHP_EOL
		);
	}
}


