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

	/**
	 * LocalBusiness page meta name
	 *
	 * @since 2.0.0
	 */
	private $meta_field_local_business = '_ssi_local_business';

	/**
	 * fields for LocalBusiness
	 *
	 * @since 2.0.0
	 */
	private $local_business_fields = null;

	/**
	 * Types for LocalBusiness
	 *
	 * @since 2.0.0
	 */
	private $local_business_types = null;

	public function __construct( $iworks ) {
		$this->options = get_iworks_simple_seo_improvements_options();
		/**
		 * dev
		 */
		$this->dev = defined( 'IWORKS_DEV_MODE' ) ? IWORKS_DEV_MODE : ( defined( 'WP_DEBUG' ) ? WP_DEBUG : false );
		/**
		 * hooks
		 */
		add_filter( 'simple_seo_improvements_wp_head', array( $this, 'get_json_ld' ) );
		add_filter( 'display_post_states', array( $this, 'filter_display_post_states' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box_for_local_business' ) );
		add_action( 'save_post', array( $this, 'action_save_post_local_business_fields' ), 10, 3 );
		add_filter( 'index_iworks_ssi_json_org_lb', array( $this, 'filter_index_iworks_ssi_json_org_lb' ), 10, 2 );
	}

	public function filter_index_iworks_ssi_json_org_lb( $select, $option = array() ) {
		if (
			empty( $option )
			|| ! is_array( $option )
			|| ! isset( $option['name'] )
		) {
			return $select;
		}
		$local_business_page_id = intval( $this->options->get_option( $option['name'] ) );
		if (
			$local_business_page_id
			&& 'page' === get_post_type( $local_business_page_id )
		) {
			$select .= sprintf(
				' <a href="%s" target="_blank">%s</a>',
				esc_url( get_permalink( $local_business_page_id ) ),
				esc_html__(
					sprintf(
						__( 'View Page: %s', 'simple-seo-improvements' ),
						get_the_title( $local_business_page_id )
					)
				)
			);
		}
		return $select;
	}

	private function get_local_business_nonce_name() {
		return $this->options->get_option_name( 'local_business_nonce_name' );
	}

	private function get_local_business_nonce_action() {
		return $this->options->get_option_name( 'local_business_nonce_action' );
	}

	private function set_local_business_data() {
		$this->local_business_fields = array(
			'address'                   => array(
				'type'   => 'text',
				'label'  => esc_html__( 'Address', 'simple-seo-improvements' ),
				'fields' => array(
					'name'            => esc_html__( 'Name', 'simple-seo-improvements' ),
					'streetAddress'   => esc_html__( 'Street Address', 'simple-seo-improvements' ),
					'addressLocality' => esc_html__( 'Locality', 'simple-seo-improvements' ),
					'addressRegion'   => esc_html__( 'Region', 'simple-seo-improvements' ),
					'postalCode'      => esc_html__( 'Postal Code', 'simple-seo-improvements' ),
					'addressCountry'  => array(
						'type'    => 'select',
						'label'   => esc_html__( 'Country', 'simple-seo-improvements' ),
						'options' => apply_filters( 'iworks_simple_seo_improvements_get_countries', array() ),
					),
				),
			),
			'contact'                   => array(
				'type'   => 'text',
				'label'  => esc_html__( 'Contact Data', 'simple-seo-improvements' ),
				'fields' => array(
					'telephone' => esc_html__( 'Telephone', 'simple-seo-improvements' ),
				),
			),
			'geo'                       => array(
				'type'   => 'text',
				'label'  => esc_html__( 'Geo Coordinates', 'simple-seo-improvements' ),
				'fields' => array(
					'latitude'  => esc_html__( 'Latitude', 'simple-seo-improvements' ),
					'longitude' => esc_html__( 'Longitude', 'simple-seo-improvements' ),
				),
			),
			'OpeningHoursSpecification' => array(
				'type'   => 'hours',
				'label'  => esc_html__( 'Opening Hours Specification', 'simple-seo-improvements' ),
				'fields' => array(
					'Monday'    => esc_html__( 'Monday', 'simple-seo-improvements' ),
					'Tuesday'   => esc_html__( 'Tuesday', 'simple-seo-improvements' ),
					'Wednesday' => esc_html__( 'Wednesday', 'simple-seo-improvements' ),
					'Thursday'  => esc_html__( 'Thursday', 'simple-seo-improvements' ),
					'Friday'    => esc_html__( 'Friday', 'simple-seo-improvements' ),
					'Saturday'  => esc_html__( 'Saturday', 'simple-seo-improvements' ),
					'Sunday'    => esc_html__( 'Sunday', 'simple-seo-improvements' ),
				),
			),
		);
		$this->local_business_types  = apply_filters( 'iworks_simple_seo_improvements_get_lb_types', array() );
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
			'name'       => $this->clear_string( wp_title( '&raquo;', false ) ),
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
	 * BreadcrumbList::Page::archive
	 *
	 * @since 2.0.0
	 */
	private function get_part_breadcrumb_list_page_archive( $ID ) {
		$post_type = get_post_type( $ID );
		$link      = get_post_type_archive_link( $post_type );
		if ( $link ) {
			$post_type_object = get_post_type_object( $post_type );
			return  array(
				'@type' => 'ListItem',
				'name'  => $post_type_object->label,
				'item'  => $link,
			);
		}
		return false;
	}

	/**
	 * BreadcrumbList::Page
	 *
	 * @since 2.0.0
	 */
	private function get_part_breadcrumb_list_page( $pages, $ID ) {
		$pages[] = array(
			'@type' => 'ListItem',
			'name'  => $this->clear_string( get_the_title( $ID ) ),
			'item'  => get_permalink( $ID ),
		);
		/**
		 * parents
		 *
		 * @since 2.0.0
		 */
		$post_parent_id = wp_get_post_parent_id( $ID );
		if ( 0 < $post_parent_id ) {
			$pages = $this->get_part_breadcrumb_list_page( $pages, $post_parent_id );
		}
		/**
		 * post type archive page
		 *
		 * @since 2.0.0
		 */
		$post_type_archive_page = $this->get_part_breadcrumb_list_page_archive( $ID );
		if ( $post_type_archive_page ) {
			$pages[] = $post_type_archive_page;
		}
		return $pages;
	}

	/**
	 * BreadcrumbList::Author
	 *
	 * @since 2.0.0
	 */
	private function get_part_breadcrumb_list_author( $author_id ) {
		if ( $author_id ) {
			$user = get_userdata( $author_id );
			return
				array(
					'@type' => 'ListItem',
					'name'  => sprintf( esc_html__( 'Archives for %s', 'simple-seo-improvements' ), $user->display_name ),
					'item'  => get_author_posts_url( $author_id ),
				);
		}
		return false;
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
		 *
		 * @since 2.0.0
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
		 *
		 * @since 2.0.0
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
		 *
		 * @since 2.0.0
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
		 * is_post_type_archive
		 *
		 * @since 2.0.0
		 */
		if ( is_post_type_archive() ) {
			$queried_object = get_queried_object();
			if ( is_a( $queried_object, 'WP_Post_Type' ) ) {
				$link = get_post_type_archive_link( $queried_object->name );
				if ( $link ) {
					$data['itemListElement'][] = array(
						'@type'    => 'ListItem',
						'position' => sizeof( $data['itemListElement'] ) + 1,
						'name'     => $queried_object->label,
						'item'     => $link,
					);
				}
			}
		}
		/**
		 * is_date
		 *
		 * @since 2.0.0
		 */
		if ( is_date() ) {
			$day = $month = $year = false;
			if ( is_day() ) {
				$day = $month = $year = true;
			} elseif ( is_month() ) {
				$month = $year = true;
			} elseif ( is_year() ) {
				$year = true;
			}
			if ( $year ) {
				$year                      = get_query_var( 'year' );
				$data['itemListElement'][] = array(
					'@type'    => 'ListItem',
					'position' => sizeof( $data['itemListElement'] ) + 1,
					'name'     => sprintf( esc_html__( 'Archives for %d', 'simple-seo-improvements' ), $year ),
					'item'     => get_year_link( $year ),
				);
			}
			if ( $month ) {
				$month                     = get_query_var( 'month' );
				$data['itemListElement'][] = array(
					'@type'    => 'ListItem',
					'position' => sizeof( $data['itemListElement'] ) + 1,
					'name'     => sprintf(
						esc_html__( 'Archives for %s', 'simple-seo-improvements' ),
						date_i18n(
							'F Y',
							strtotime(
								sprintf(
									'%d-%02d',
									$year,
									$month
								)
							)
						)
					),
					'item'     => get_month_link( $year, $month ),
				);
			}
			if ( $day ) {
				$day                       = get_query_var( 'day' );
				$data['itemListElement'][] = array(
					'@type'    => 'ListItem',
					'position' => sizeof( $data['itemListElement'] ) + 1,
					'name'     => sprintf(
						esc_html__( 'Archives for %s', 'simple-seo-improvements' ),
						date_i18n(
							'j F Y',
							strtotime(
								sprintf(
									'%d-%02d-%02d',
									$year,
									$month,
									$day
								)
							)
						)
					),
					'item'     => get_day_link( $year, $month, $day ),
				);
			}
		}
		/**
		 * is_author
		 *
		 * @since 2.0.0
		 */
		if ( is_author() ) {
			$author_name = get_query_var( 'author_name' );
			if ( ! empty( $author_name ) ) {
				$user = get_user_by( 'login', $author_name );
				if ( is_a( $user, 'WP_User' ) ) {
					$item = $this->get_part_breadcrumb_list_author( $user->ID );
					if ( $item ) {
						$item['position']          = sizeof( $data['itemListElement'] ) + 1;
						$data['itemListElement'][] = $item;
					}
				}
			}
		}
		/**
		 * is_404
		 *
		 * @since 2.0.0
		 */
		if ( is_404() ) {
			$data['itemListElement'][] = array(
				'@type'    => 'ListItem',
				'position' => sizeof( $data['itemListElement'] ) + 1,
				'name'     => __( '404 Error Page', 'simple-seo-improvements' ),
				'item'     => '',
			);
		}
		/**
		 * remove last item url from breadcrumb
		 *
		 * @since 2.0.0
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
			'name'  => $this->clear_string( get_bloginfo( 'name' ) ),
		);
		/**
		 * Alternate website name
		 */
		$value = $this->options->get_option( 'json_name_alt' );
		if ( ! empty( $value ) ) {
			$data['alternateName'] = $this->clear_string( $value );
		}
		/**
		 * description
		 */
		$value = get_bloginfo( 'description' );
		if ( ! empty( $value ) ) {
			$data['description'] = $this->clear_string( $value );
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
	 * potentialAction: CommentAction
	 *
	 * @since 2.0.0
	 */
	private function get_part_potential_action_comment_action() {
		if ( ! is_singular() ) {
			return array();
		}
		if ( ! comments_open() ) {
			return array();
		}
		return apply_filters(
			'iworks_simple_seo_improvements_json_ld::potentialAction::CommentAction',
			array(
				'@type'  => 'CommentAction',
				'name'   => __( 'Comment', 'simple-seo-improvements' ),
				'target' => array(
					get_permalink() . '#response',
				),
			)
		);
	}

	/**
	 * author
	 *
	 * @since 2.0.0
	 */
	private function get_part_author() {
		global $post;
		$author_id = $post->post_author;
		$user      = get_userdata( $author_id );
		if ( empty( $user ) ) {
			return array();
		}
		$data = array(
			'@type' => 'Person',
			'@id'   => home_url() . '/#/schema/person/' . md5( $user->user_email ),
			'name'  => $this->clear_string( $user->display_name ),
			'url'   => get_author_posts_url( $author_id ),
			'image' => array(
				'@type'      => 'ImageObject',
				'inLanguage' => $this->get_locale(),
				'@id'        => home_url() . '/#/schema/person/image/',
				'url'        => get_avatar_url( $user->user_email, array( 'size' => 512 ) ),
				'contentUrl' => get_avatar_url( $user->user_email, array( 'size' => 512 ) ),
				'caption'    => $user->display_name,
			),
		);
		/**
		 * sameAs
		 */
		$same_as = array();
		$value   = $user->user_url;
		if ( $value && home_url() !== $value ) {
			$same_as[] = $value;
		}
		if ( ! empty( $same_as ) ) {
			$data['sameAs'] = $same_as;
		}
		/**
		 * return
		 */
		return apply_filters(
			'iworks_simple_seo_improvements_json_ld::author',
			$data
		);

	}

	/**
	 * Article
	 *
	 * @since 2.0.0
	 */
	private function get_part_article() {
		$data = array(
			'@type'        => 'Article',
			'@id'          => get_permalink() . '#article',
			'isPartOf'     => array(
				'@id' => get_permalink(),
			),
			'wordCount'    => str_word_count( strip_tags( get_the_content() ) ),
			'headline'     => $this->clear_string( get_the_title() ),
			'commentCount' => intval( get_comments_number( get_the_ID() ) ),
		);
		/**
		 * thumbnail
		 */
		if ( has_post_thumbnail() ) {
			$data = wp_parse_args(
				array(
					'thumbnail' => $this->get_part_image_object( get_post_thumbnail_id(), '#thumbnail' ),
				),
				$data
			);
		}
		/**
		 * Author
		 */
		$value = $this->get_part_author();
		if ( ! empty( $value ) ) {
			$data['author'] = $this->clear_string( $value );
		}
		/**
		 * inLanguage
		 */
		$data = wp_parse_args(
			$this->get_part_in_language(),
			$data
		);
		/**
		 * potentialAction:CommentAction
		 */
		$value = $this->get_part_potential_action_comment_action();
		if ( ! empty( $value ) ) {
			if ( ! isset( $data['potentialAction'] ) ) {
				$data['potentialAction'] = array();
			}
			$data['potentialAction'][] = $value;
		}
		/**
		 * return
		 */
		return apply_filters(
			'iworks_simple_seo_improvements_json_ld::Article',
			$data
		);
	}

	/**
	 * thumbnail
	 *
	 * @since 2.0.0
	 */
	private function get_part_thumbnail() {
		$data = array();
		if ( has_post_thumbnail() ) {
			$data = array(
				'primaryImageOfPage' => array(
					'@id' => get_permalink() . '#primaryimage',
				),
				'image'              => array(
					'@id' => get_permalink() . '#primaryimage',
				),
				'thumbnailUrl'       => get_the_post_thumbnail_url( get_the_ID(), 'full' ),
				'datePublished'      => get_the_date( 'c' ),
				'dateModified'       => get_the_modified_date( 'c' ),
			);
			if ( ! is_single() ) {
				$data['breadcrumb'] = array(
					'@id' => get_permalink() . '#breadcrumb',
				);
			}
		}
		/**
		 * return
		 */
		return apply_filters(
			'iworks_simple_seo_improvements_json_ld::primaryImageOfPage',
			$data
		);
	}
	/**
	 * inLanguage
	 *
	 * @since 2.0.0
	 */
	private function get_part_in_language() {
		return array(
			'inLanguage' => $this->get_locale(),
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
		$data = wp_parse_args(
			$this->get_part_thumbnail(),
			$data
		);
		$data = wp_parse_args(
			$this->get_part_in_language(),
			$data
		);
		$data = wp_parse_args(
			array(
				'potentialAction' => array(
					$this->get_part_potential_action_read_action( get_permalink() ),
				),
			),
			$data
		);
		/**
		 * return
		 */
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
		);
		if ( 0 < $image[1] ) {
			$data['width'] = $image[1];
		}
		if ( 0 < $image[2] ) {
			$data['height'] = $image[2];
		}
		/**
		 * caption
		 */
		$value = wp_get_attachment_caption( $attachment_id );
		if ( ! empty( $value ) ) {
			$data['caption'] = $this->clear_string( $value );
		}
		return apply_filters(
			'iworks_simple_seo_improvements_json_ld::ImageObject',
			$data,
			$attachment_id
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
					'name'  => $this->clear_string( $this->options->get_option( 'json_org_name' ) ),
					'url'   => home_url(),
				);
				/**
				 * alternateName
				 */
				$value = $this->options->get_option( 'json_org_alt' );
				if ( $value ) {
					$data['alternateName'] = $this->clear_string( $value );
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
					$data['address'] = $this->clear_string( $value );
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
					'name'  => $this->clear_string( $user->display_name ),
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
			$data['streetAddress'] = $this->clear_string( $value );
		}
		/**
		 * addressLocality
		 */
		$value = $this->options->get_option( 'json_org_pa_l' );
		if ( ! empty( $value ) ) {
			$data['addressLocality'] = $this->clear_string( $value );
		}
		/**
		 * addressRegion
		 */
		$value = $this->options->get_option( 'json_org_pa_r' );
		if ( ! empty( $value ) ) {
			$data['addressRegion'] = $this->clear_string( $value );
		}
		/**
		 * postalCode
		 */
		$value = $this->options->get_option( 'json_org_pa_pc' );
		if ( ! empty( $value ) ) {
			$data['postalCode'] = $this->clear_string( $value );
		}
		/**
		 * addressCountry
		 */
		$value = $this->options->get_option( 'json_org_pa_c' );
		if ( ! empty( $value ) ) {
			$data['addressCountry'] = $this->clear_string( $value );
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
	 * LocalBusiness
	 *
	 * @since 2.0.0
	 */
	private function get_part_local_business() {
		$this->set_local_business_data();
		$value = get_post_meta( get_the_ID(), $this->meta_field_local_business, true );
		if ( ! isset( $value['type'] ) ) {
			return apply_filters(
				'iworks_simple_seo_improvements_json_ld::LocalBusiness',
				array(),
				get_the_ID()
			);
		}
		$data = array(
			'@type' => $value['type'],
			'@id'   => get_permalink(),
			'url'   => get_permalink(),
			// 'v' => $value,
		);
		/**
		 * address
		 */
		$address = array();
		foreach ( $this->local_business_fields['address']['fields'] as $key => $label ) {
			if ( ! isset( $value[ $key ] ) ) {
				continue;
			}
			if ( empty( $value[ $key ] ) ) {
				continue;
			}
			if ( 'name' === $key ) {
				$data[ $key ] = $value[ $key ];
				continue;
			}
			$address[ $key ] = $value[ $key ];
		}
		if ( ! empty( $address ) ) {
			$data['address'] = wp_parse_args(
				$address,
				array(
					'@type' => 'PostalAddress',
				)
			);
		}
		/**
		 * telephone
		 */
		if (
			isset( $value['telephone'] )
			&& $value['telephone']
		) {
			$data['telephone'] = $this->clear_string( $value['telephone'] );
		}
		/**
		 * geo
		 */
		if (
			isset( $value['latitude'] )
			&& $value['latitude']
			&& isset( $value['longitude'] )
			&& $value['longitude']
		) {
			$data['geo'] = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => sprintf( '%f', $value['latitude'] ),
				'longitude' => sprintf( '%f', $value['longitude'] ),
			);
		}
		/**
		 * openingHoursSpecification
		 */
		$opening_hours = array();
		foreach ( $this->local_business_fields['OpeningHoursSpecification']['fields'] as $key => $label ) {
			if ( ! isset( $value[ $key ] ) ) {
				continue;
			}
			if ( ! is_array( $value[ $key ] ) ) {
				continue;
			}
			if ( ! isset( $value[ $key ]['closes'] ) ) {
				continue;
			}
			if ( ! isset( $value[ $key ]['opens'] ) ) {
				continue;
			}
			$k = sprintf( '%s:%s', $value[ $key ]['opens'], $value[ $key ]['closes'] );
			if ( ! isset( $opening_hours[ $k ] ) ) {
				$opening_hours[ $k ] = array(
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => array(),
					'opens'     => $value[ $key ]['opens'],
					'closes'    => $value[ $key ]['closes'],
				);
			}
			$opening_hours[ $k ]['dayOfWeek'][] = $key;
		}
		if ( ! empty( $opening_hours ) ) {
			$data['openingHoursSpecification'] = array();
			foreach ( $opening_hours as $k => $v ) {
				$data['openingHoursSpecification'][] = $v;
			}
		}

		// $data['openingHoursSpecification'] = $value;
					// '@type': 'OpeningHoursSpecification',
		/**
		 * filter & return
		 */
		return apply_filters(
			'iworks_simple_seo_improvements_json_ld::LocalBusiness',
			$data,
			get_the_ID()
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
		/**
		 * LocalBusiness
		 */
		$is_local_business = false;
		if ( is_page() ) {
			$local_business_page_id = intval( $this->options->get_option( 'json_org_lb' ) );
			if (
				0 < $local_business_page_id
				&& is_page( $local_business_page_id )
			) {
				$data = $this->get_part_local_business();
				if ( ! empty( $data ) ) {
					$this->data['@graph'][] = $data;
					$is_local_business      = true;
				}
			}
		}
		/**
		 * home
		 * archive
		 * search
		 */
		if (
			is_home()
			|| is_archive()
			|| is_search()
		) {
			$this->data['@graph'][] = $this->get_part_collection_page();
		}
		/**
		 * is singular
		 */
		if ( is_singular() && ! $is_local_business ) {
			switch ( get_post_type() ) {
				case 'product':
					$this->data['@graph'][] = $this->get_part_product();
					break;
				case 'page':
				case 'post':
					$this->data['@graph'][] = $this->get_part_article();
					$this->data['@graph'][] = $this->get_part_web_page();
					if ( has_post_thumbnail() ) {
						$this->data['@graph'][] = $this->get_part_image_object( get_post_thumbnail_id() );
					}
					break;
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
		$flags    = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		if ( $this->dev ) {
			$flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		}
		$content .= json_encode(
			apply_filters(
				'iworks_simple_seo_improvements_json_ld::json_encode::json',
				array_filter( $this->get_data() )
			),
			apply_filters(
				'iworks_simple_seo_improvements_json_ld::json_encode::flags',
				$flags
			)
		);
		$content .= PHP_EOL;
		$content .= '</script>';
		$content .= PHP_EOL;
		return $content;
	}

	/**
	 * Mark selected LocalBusiness page.
	 *
	 * @since 2.0.0
	 */
	public function filter_display_post_states( $post_states, $post ) {
		if ( intval( $this->options->get_option( 'json_org_lb' ) ) === intval( $post->ID ) ) {
			$post_states[] = esc_html__( 'Local Business Page', 'simple-seo-improvements' );
		}
		return $post_states;
	}

	private function check_is_local_business_page( $post_id ) {
		$id = intval( $this->options->get_option( 'json_org_lb' ) );
		if ( 0 === $id ) {
			return false;
		}
		return $id === intval( $post_id );
	}

	/**
	 * Add entry edit metabox.
	 *
	 * @since 1.0.0
	 */
	public function add_meta_box_for_local_business() {
		if (
			! $this->check_is_local_business_page(
				filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT )
			)
		) {
			return;
		}
		add_meta_box(
			$this->options->get_option_name( 'localbusiness' ),
			__( 'Simple SEO Improvements: LocalBusiness', 'simple-seo-improvements' ),
			array( $this, 'meta_box_html_local_business' ),
			'page'
		);
	}

	private function get_local_business_values( $ID ) {
		$values = get_post_meta( $ID, $this->meta_field_local_business, true );
		if ( empty( $values ) ) {
			$values = array();
		}
		$map = array(
			'name'            => 'json_org_name',
			'streetAddress'   => 'json_org_pa_st',
			'addressLocality' => 'json_org_pa_l',
			'addressRegion'   => 'json_org_pa_r',
			'postalCode'      => 'json_org_pa_pc',
			'addressCountry'  => 'json_org_pa_c',
		);
		foreach ( $map as $key => $option_name ) {
			if ( isset( $values[ $key ] ) ) {
				continue;
			}
			$values[ $key ] = $this->options->get_option( $option_name );
		}
		if ( ! isset( $values['type'] ) ) {
			$values['type'] = '';
		}
		return $values;
	}

	public function meta_box_html_local_business( $post ) {
		$this->set_local_business_data();
		$file  = dirname( dirname( dirname( dirname( __FILE__ ) ) ) );
		$file .= '/assets/templates/meta-boxes/localbusiness.php';
		$args  = array(
			'name'   => $this->meta_field_local_business,
			'types'  => $this->local_business_types,
			'value'  => $this->get_local_business_values( $post->ID ),
			'fields' => $this->local_business_fields,
			'nonce'  => array(
				'name'   => $this->get_local_business_nonce_name(),
				'action' => $this->get_local_business_nonce_action(),
			),
		);
		load_template( $file, true, $args );
	}

	public function action_save_post_local_business_fields( $post_id, $post, $update ) {
		if ( ! $this->check_is_local_business_page( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST[ $this->get_local_business_nonce_name() ] ) ) {
			return;
		}
		if ( ! wp_verify_nonce(
			$_POST[ $this->get_local_business_nonce_name() ],
			$this->get_local_business_nonce_action()
		) ) {
			return;
		}
		$value = $_POST[ $this->meta_field_local_business ];
		$this->set_local_business_data();
		$data = array();
		/**
		 * type
		 */
		$name = 'type';
		foreach ( $value[ $name ] as $subkey ) {
			if ( empty( $subkey ) ) {
				continue;
			}
			if ( isset( $this->local_business_types[ $subkey ] ) ) {
				if ( ! isset( $data[ $name ] ) ) {
					$data[ $name ] = array();
				}
				$data[ $name ][] = $subkey;
			}
		}
		/**
		 * fields
		 */
		foreach ( $this->local_business_fields as $group ) {
			foreach ( $group['fields'] as $name => $label ) {
				switch ( $group['type'] ) {
					case 'text':
						$v = isset( $value[ $name ] ) ? sanitize_text_field( $value[ $name ] ) : false;
						if ( $v ) {
							$data[ $name ] = $v;
						}
						break;
					case 'hours':
						foreach ( array( 'opens', 'closes' ) as $subkey ) {
							if (
							isset( $value[ $name ][ $subkey ] )
							&& preg_match( '/^\d\d:\d\d$/', $value[ $name ][ $subkey ] )
							) {
								if ( ! isset( $data[ $name ] ) ) {
									$data[ $name ] = array();
								}
								$data[ $name ][ $subkey ] = $value[ $name ][ $subkey ];
							}
						}
						break;
				}
			}
		}
		$this->update_single_post_meta( $post_id, $this->meta_field_local_business, $data );
	}

	/**
	 * product
	 *
	 * @since 2.0.5
	 */
	private function get_part_product() {
		$data = array();
		if (
			defined( 'WC_PLUGIN_FILE' )
			&& defined( 'WC_VERSION' )
		) {
			$data = $this->get_part_product_woocommerce();
		}
		return apply_filters(
			'iworks/simple_seo_improvements/json_ld/product',
			$data
		);
	}

	/**
	 * product
	 *
	 * @since 2.0.5
	 */
	private function get_part_product_woocommerce() {
		$product = wc_get_product( get_the_ID() );
		if ( ! $product ) {
			return array();
		}
		/**
		 * commons
		 */
		$price_valid_until = date( 'Y-m-01', strtotime( '+1 year' ) );
		$sku               = $product->get_sku();
		if ( empty( $sku ) ) {
			$sku = get_post_field( 'post_name', get_post() );
		}
		/**
		 * data
		 */
		$data = array(
			'@type'       => 'Product',
			'@id'         => get_permalink() . '#product',
			'name'        => $this->clear_string( $product->get_name() ),
			'description' => $this->clear_string( $product->get_description() ),
			'brand'       => get_bloginfo( 'name' ),
			'sku'         => $this->clear_string( $sku ),
			'mpn'         => $this->clear_string( $sku ),
			'offers'      => array(),
		);
		/**
		 * $product->get_type()
		 */
		switch ( $product->get_type() ) {
			case 'variable':
				$data['offers']             = array(
					'@type'         => 'AggregateOffer',
					'priceCurrency' => get_woocommerce_currency(),
					'offers'        => array(),
				);
				$data['offers']['lowPrice'] = floatval( $product->get_variation_price() );
				$variations                 = $product->get_available_variations();
				foreach ( $variations as $variant ) {
					$data_variant = array(
						'@type' => 'Offer',
						'url'   => get_permalink( $variant['variation_id'] ),
					);
					/**
					 * description
					 */
					if ( $variant['variant_description'] ) {
						$data_variant['description'] = $variant['variant_description'];
					}
					/**
					 * price
					 */
					if ( $variant['display_price'] ) {
						$data_variant['price']           = $variant['display_price'];
						$data_variant['priceValidUntil'] = $price_valid_until;
					}
					/**
					 * is in stock?
					 */
					if ( $variant['is_in_stock'] ) {
						$data_variant['availability'] = 'https://schema.org/InStock';
					}
					/**
					 * has image
					 */
					if (
						isset( $variant['image'] )
						&& isset( $variant['image']['url'] )
					) {
						$data_variant['image'] = array(
							$variant['image']['url'],
						);
					}
					$data['offers']['offers'][] = apply_filters(
						'iworks/simple_seo_improvements/json_ld/product/variant/offer/woocommerce',
						$data_variant,
						$variant
					);
				}
				break;
			default:
				$data['offers'] = array(
					'@type'           => 'Offer',
					'price'           => floatval( $product->get_regular_price() ),
					'priceCurrency'   => get_woocommerce_currency(),
					'priceValidUntil' => $price_valid_until,
					'url'             => get_permalink(),
				);
				/**
				 * sale?
				 */
				if ( $product->is_on_sale() ) {
					$data['offers']['lowPrice'] = floatval( $product->get_sale_price() );
				}
				/**
				 * is in stock?
				 */
				if ( $product->is_in_stock() ) {
					$data['offers']['availability'] = 'https://schema.org/InStock';
				}
				break;
		}
		/**
		 * is managing_stock
		 */
		if ( $product->managing_stock() ) {
			$data['offers']['offerCount'] = $product->get_stock_quantity();
		}
		/**
		 * images
		 */
		$images = array();
		if ( has_post_thumbnail() ) {
			$images[] = wp_get_attachment_url( get_post_thumbnail_id() );
		}
		$ids = $product->get_gallery_image_ids();
		if ( $ids ) {
			foreach ( $ids as $image_id ) {
				$images[] = wp_get_attachment_url( $image_id );
			}
		}
		/**
		 * children
		 */
		$children = $product->get_children();
		if ( $children && ! empty( $children ) ) {
			$max = $min = null;
			foreach ( $children as $id ) {
				$child_product = wc_get_product( $id );
				$child_max     = floatval( $child_product->get_regular_price() );
				$child_min     = floatval( $child_product->get_sale_price() );
				if ( null === $max ) {
					$max = $child_min;
				}
				if ( null === $min ) {
					$min = $child_max;
				}
				if ( $max < $child_max ) {
					$max = $child_max;
				}
				if ( $min > $child_min && 0 < $child_min ) {
					$min = $child_min;
				}
				/**
				 * images
				 */
				if ( has_post_thumbnail( $id ) ) {
					$images[] = wp_get_attachment_url( get_post_thumbnail_id( $id ) );
				}
				$ids = $child_product->get_gallery_image_ids();
				if ( $ids ) {
					foreach ( $ids as $image_id ) {
						$images[] = wp_get_attachment_url( $image_id );
					}
				}
			}
			if ( null !== $max ) {
				$data['offers']['highPrice'] = $max;
				$data['offers']['lowPrice']  = $min;
			}
		}
		/**
		 * images assign
		 */
		if ( ! empty( $images ) ) {
			$data = wp_parse_args(
				array(
					'image' => array_unique( $images ),
				),
				$data
			);
		}
		/**
		 * return
		 */
		return apply_filters(
			'iworks/simple_seo_improvements/json_ld/product/woocommerce',
			$data,
			$product
		);
	}
}

