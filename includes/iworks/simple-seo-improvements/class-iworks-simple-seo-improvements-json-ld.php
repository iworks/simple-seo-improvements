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

if ( class_exists( 'iworks_simple_seo_improvements_json_ld' ) ) {
	return;
}

require_once dirname( __FILE__ ) . '/class-base.php';

class iworks_simple_seo_improvements_json_ld extends iworks_simple_seo_improvements_base {

	/**
	 * JSON-LD data
	 *
	 * @since 1.5.7
	 */
	private $data = array();
	/**
	 * locale
	 *
	 * @since 2.0.0
	 */
	private $locale = null;

	public function __construct( $iworks ) {
		$this->options = get_iworks_simple_seo_improvements_options();
		add_filter( 'simple_seo_improvements_wp_head', array( $this, 'get_json_ld' ) );
	}

	/**
	 * CollectionPage
	 *
	 * @since 2.0.0
	 */
	private function get_part_collection_page() {
		global $wp;
		$data = array(
			'@type'      => 'CollectionPage',
			'@id'        => esc_url( home_url( $wp->request ) ),
			'url'        => esc_url( home_url( $wp->request ) ),
			'name'       => wp_title( '&raquo;', false ),
			'isPartOf'   => array(
				'@id' => home_url( '/#website' ),
			),
			'breadcrumb' => array(
				'@id' => home_url( '/#breadcrumb' ),
			),
			'inLanguage' => $this->get_locale(),
		);
		if ( is_search() ) {
			$data['@type'] = array(
				'CollectionPage',
				'SearchResultsPage',
			);
		}
		return apply_filters(
			'iworks_simple_seo_improvements_json_ld::CollectionPage',
			$data
		);
	}

	/**
	 * BreadcrumbList::Page
	 *
	 * @since 2.0.0
	 */
	private function get_part_breadcrumb_list_page( $pages, $ID ) {
		$pages[]        = array(
			'@type' => 'ListItem',
			'name'  => get_the_title( $ID ),
			'item'  => get_permalink( $ID ),
		);
		$post_parent_id = wp_get_post_parent_id( $ID );
		if ( 0 < $post_parent_id ) {
			$pages = $this->get_part_breadcrumb_list_page( $pages, $post_parent_id );
		}
		return $pages;
	}

	/**
	 * BreadcrumbList
	 *
	 * @since 2.0.0
	 */
	private function get_part_breadcrumb_list() {
		$data = array(
			'@type'           => 'BreadcrumbList',
			'@id'             => home_url( '/#breadcrumb' ),
			'itemListElement' => array(
				array(
					'@type'    => 'ListItem',
					'position' => 1,
					'name'     => strip_tags( __( 'Home Page', 'simple-seo-improvements' ) ),
					'item'     => home_url(),
				),
			),
		);
		/**
		 * singular
		 */
		if ( is_singular() ) {
			foreach (
				array_reverse( $this->get_part_breadcrumb_list_page( array(), get_the_ID() ) )
				as $page
			) {
				$page['position']          = sizeof( $data['itemListElement'] ) + 1;
				$data['itemListElement'][] = $page;
			}
		}
		/**
		 * term
		 */
		if (
			is_tag()
			|| is_category()
			|| is_tax()
		) {
			$data['itemListElement'][] = array(
				'@type'    => 'ListItem',
				'position' => sizeof( $data['itemListElement'] ) + 1,
				'name'     => strip_tags( single_term_title( '', false ) ),
				'item'     => get_term_link( get_queried_object()->term_id ),
			);
		}
		/**
		 * is_search
		 */
		if ( is_search() ) {
			$data['itemListElement'][] = array(
				'@type'    => 'ListItem',
				'position' => sizeof( $data['itemListElement'] ) + 1,
				'name'     => sprintf( __( 'Search Results for: %s' ), strip_tags( get_query_var( 's' ) ) ),
				'item'     => '',
			);
		}
		/**
		 * remove last item url from breadcrumb
		 */
		unset( $data['itemListElement'][ sizeof( $data['itemListElement'] ) - 1 ]['item'] );
		/**
		 * return data
		 */
		return apply_filters(
			'iworks_simple_seo_improvements_json_ld::BreadcrumbList',
			$data
		);
	}

	/**
	 * WebSite
	 *
	 * @since 2.0.0
	 */
	private function get_part_web_site() {
		$data = array(
			'@type' => 'WebSite',
			'@id'   => home_url( '/#website' ),
			'url'   => home_url( '/' ),
			'name'  => get_bloginfo( 'name' ),
		);
		/**
		 * Alternate website name
		 */
		$value = $this->options->get_option( 'json_name_alt' );
		if ( ! empty( $value ) ) {
			$data['alternateName'] = $value;
		}
		/**
		 * description
		 */
		$value = get_bloginfo( 'description' );
		if ( ! empty( $value ) ) {
			$data['description'] = $value;
		}
		/**
		 * potentialAction
		 */
		$data['potentialAction'] = array(
			$this->get_part_potential_action_search_action(),
		);
		return apply_filters(
			'iworks_simple_seo_improvements_json_ld::WebSite',
			$data
		);
	}

	/**
	 * potentialAction: SearchAction
	 *
	 * @since 2.0.0
	 */
	private function get_part_potential_action_search_action() {
		return apply_filters(
			'iworks_simple_seo_improvements_json_ld::potentialAction::SearchAction',
			array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => home_url( '/?s={search_term_string}' ),
				),
				'query-input' => 'required name=search_term_string',
			)
		);
	}

	/**
	 * potentialAction: ReadAction
	 *
	 * @since 2.0.0
	 */
	private function get_part_potential_action_read_action( $target ) {
		return apply_filters(
			'iworks_simple_seo_improvements_json_ld::potentialAction::ReadAction',
			array(
				'@type'  => 'ReadAction',
				'target' => array(
					$target,
				),
			)
		);
	}

	/**
	 * WebPage
	 *
	 * @since 2.0.0
	 */
	private function get_part_web_page() {
		$data = array(
			'@type'    => 'WebPage',
			'@id'      => get_permalink(),
			'url'      => get_permalink(),
			'isPartOf' => array(
				'@id' => home_url( '/#website' ),
			),
		);
		if ( has_post_thumbnail() ) {
			$data = wp_parse_args(
				array(
					'primaryImageOfPage' => array(
						'@id' => get_permalink() . '#primaryimage',
					),
					'image'              => array(
						'@id' => get_permalink() . '#primaryimage',
					),
					'thumbnailUrl'       => get_the_post_thumbnail_url( get_the_ID(), 'full' ),
					'datePublished'      => get_the_date( 'c' ),
					'dateModified'       => get_the_modified_date( 'c' ),
					'breadcrumb'         => array(
						'@id' => get_permalink() . '#breadcrumb',
					),
				),
				$data
			);
		}
		$data = wp_parse_args(
			array(
				'inLanguage'      => $this->get_locale(),
				'potentialAction' => array(
					$this->get_part_potential_action_read_action( get_permalink() ),
				),
			),
			$data
		);
		return apply_filters(
			'iworks_simple_seo_improvements_json_ld::WebPage',
			$data
		);
	}

	/**
	 * ImageObject
	 *
	 * @since 2.0.0
	 */
	private function get_part_image_object( $attachment_id, $id = '#primaryimage' ) {
		$image = wp_get_attachment_image_src( $attachment_id, 'full' );
		if ( empty( $image ) ) {
			return array();
		};
		$data = array(
			'@type'      => 'ImageObject',
			'inLanguage' => $this->get_locale(),
			'@id'        => get_permalink() . $id,
			'url'        => $image[0],
			'contentUrl' => $image[0],
			'width'      => $image[1],
			'height'     => $image[2],
		);
		/**
		 * caption
		 */
		$value = wp_get_attachment_caption( $attachment_id );
		if ( ! empty( $value ) ) {
			$data['caption'] = $value;
		}
		return apply_filters(
			'iworks_simple_seo_improvements_json_ld::ImageObject',
			$data
		);
	}

	/**
	 * Organization
	 *
	 * @since 2.0.0
	 */
	private function get_part_organization() {
		$data    = array();
		$same_as = array();
		switch ( $this->options->get_option( 'json_type' ) ) {
			case 'organization':
				$data = array(
					'@type' => 'Organization',
					'@id'   => home_url( '/#organization' ),
					'name'  => $this->options->get_option( 'json_org_name' ),
					'url'   => home_url(),
				);
				/**
				 * alternateName
				 */
				$value = $this->options->get_option( 'json_org_alt' );
				if ( $value ) {
					$data['alternateName'] = $value;
				}
				/**
				 * logo
				 */
				$value = $this->options->get_option( 'json_org_img' );
				if ( $value ) {
					$logo = $this->get_part_image_object( $value, '#/schema/logo/image/' );
					if ( ! empty( $logo ) ) {
						$logo['image'] = array(
							'@id' => home_url( '/#/schema/logo/image/' ),
						);
						$data['logo']  = $logo;
					}
				}
				/**
				 * PostalAddress
				 */
				$value = $this->get_part_postal_address();
				if ( ! empty( $value ) ) {
					$data['address'] = $value;
				}
				break;
			case 'person':
				$user_id = intval( $this->options->get_option( 'json_person' ) );
				if ( 1 > $user_id ) {
					return;
				}
				$user = get_userdata( $user_id );
				$data = array(
					'@type' => array(
						'Person',
						'Organization',
					),
					'@id'   => home_url( '/#/schema/person/' . md5( $user->user_email ) ),
					'name'  => $user->display_name,
				);
				/**
				 * logo
				 */
				$value = $this->options->get_option( 'json_person_img' );
				if ( $value ) {
					$image = $this->get_part_image_object( $value, '#/schema/person/image/' );
					if ( ! empty( $image ) ) {
						$image['image'] = array(
							'@id' => home_url( '/#/schema/person/image/' ),
						);
						$data['image']  = $image;
					}
				}
				/**
				 * sameAs
				 */
				$value = $user->user_url;
				if ( $value && home_url() !== $value ) {
					$same_as[] = $value;
				}
				break;
			default:
				return $data;
		}
		/**
		 * sameAs
		 */
		$value = $this->options->get_option( 'json_other' );
		if ( ! empty( $value ) ) {
			foreach ( explode( "\n", $value ) as $one ) {
				$one = trim( $one );
				if ( empty( $one ) ) {
					continue;
				}
				$same_as[] = $one;
			}
		}
		if ( ! empty( $same_as ) ) {
			$data['sameAs'] = $same_as;
		}
		/**
		 * filter & return
		 */
		return apply_filters(
			'iworks_simple_seo_improvements_json_ld::Organization',
			$data
		);
	}

	/**
	 * PostalAddress
	 *
	 * @since 2.0.0
	 */
	private function get_part_postal_address() {
		$data = array();
		/**
		 * streetAddress
		 */
		$value = $this->options->get_option( 'json_org_pa_st' );
		if ( ! empty( $value ) ) {
			$data['streetAddress'] = $value;
		}
		/**
		 * addressLocality
		 */
		$value = $this->options->get_option( 'json_org_pa_l' );
		if ( ! empty( $value ) ) {
			$data['addressLocality'] = $value;
		}
		/**
		 * addressRegion
		 */
		$value = $this->options->get_option( 'json_org_pa_r' );
		if ( ! empty( $value ) ) {
			$data['addressRegion'] = $value;
		}
		/**
		 * postalCode
		 */
		$value = $this->options->get_option( 'json_org_pa_pc' );
		if ( ! empty( $value ) ) {
			$data['postalCode'] = $value;
		}
		/**
		 * addressCountry
		 */
		$value = $this->options->get_option( 'json_org_pa_c' );
		if ( ! empty( $value ) ) {
			$data['addressCountry'] = $value;
		}
		/**
		 * @type
		 */
		if ( ! empty( $data ) ) {
			$data = wp_parse_args(
				array(
					'@type' => 'PostalAddress',
				),
				$data
			);
		}
		/**
		 * filter & return
		 */
		return apply_filters(
			'iworks_simple_seo_improvements_json_ld::Organization',
			$data
		);
	}

	/**
	 * get JSON-LD data
	 *
	 * @since 2.0.0
	 */
	private function get_data() {
		if ( ! empty( $this->data ) ) {
			return $this->data;
		}
		$this->data['@context'] = 'https://schema.org';
		$this->data['@graph']   = array();
		if (
			is_home()
			|| is_archive()
			|| is_search()
		) {
			$this->data['@graph'][] = $this->get_part_collection_page();
		}
		if ( is_singular() ) {
			$this->data['@graph'][] = $this->get_part_web_page();
			if ( has_post_thumbnail() ) {
				$this->data['@graph'][] = $this->get_part_image_object( get_post_thumbnail_id() );
			}
		}
		$this->data['@graph'][] = $this->get_part_breadcrumb_list();
		$this->data['@graph'][] = $this->get_part_web_site();
		/**
		 * Organization
		 */
		$value = $this->get_part_organization();
		if ( ! empty( $value ) ) {
			$this->data['@graph'][] = $value;
		}
		/**
		 * return
		 */
		return apply_filters(
			'iworks_simple_seo_improvements_json_ld',
			$this->data
		);
	}

	private function get_locale() {
		if ( ! empty( $this->locale ) ) {
			return $this->locale;
		}
		$this->locale = preg_replace( '/_/', '-', get_locale() );
		return $this->locale;
	}

	/**
	 * add json-ld to head
	 *
	 * @since 1.5.7
	 */
	public function get_json_ld( $content ) {
		$content .= '<script type="application/ld+json" id="simple-seo-improvements-json-ld">';
		$content .= PHP_EOL;
		$content .= json_encode(
			apply_filters(
				'simple_seo_improvements_json_d_data',
				array_filter( $this->get_data() )
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);
		$content .= PHP_EOL;
		$content .= '</script>';
		$content .= PHP_EOL;
		return $content;
	}

}

