<?php
/*

Copyright 2026-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

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

if ( class_exists( 'iworks_simple_seo_improvements_attachments' ) ) {
	return;
}

require_once __DIR__ . '/class-iworks-simple-seo-improvements-base-abstract.php';

class iworks_simple_seo_improvements_attachments extends iworks_simple_seo_improvements_base_abstract {

	/**
	 * are options set?
	 *
	 * @var bool
	 */
	private bool $run = false;

	public function __construct( $iworks ) {
		$this->iworks = $iworks;
		add_action( 'init', array( $this, 'action_init_set_options' ) );
		add_action( 'template_redirect', array( $this, 'filter_template_redirect' ) );
		add_filter( 'iworks/simple-seo-improvements/add_post_type_options/opts', array( $this, 'filter_add_post_type_options' ), 10, 2 );
	}

	/**
	 * set Options
	 *
	 * @since 2.4.0
	 */
	public function action_init_set_options() {
		$this->check_option_object();
		if ( intval( $this->options->get_option( 'atta_redirect' ) ) ) {
			$this->run = true;
		}
	}

	/**
	 * redirect attachment pages to the attachment parent
	 *
	 * @since 2.4.0
	 */
	public function filter_template_redirect( $template ) {
		if ( ! is_attachment() ) {
			return $template;
		}
		$this->check_option_object();
		if ( ! intval( $this->options->get_option( 'atta_redirect' ) ) ) {
			return $template;
		}
		$parent_id = wp_get_post_parent_id( get_queried_object_id() );
		if ( ! $parent_id ) {
			/**
			 * no parent: what to do with unattached attachments?
			 *
			 * @since 2.4.0
			 *
			 * @param string $unattached Behavior for unattached attachments: 'nothing' or 'home'.
			 */
			$unattached = apply_filters(
				'iworks_simple_seo_improvements_attachments_unattached',
				$this->options->get_option( 'atta_redirect_unattached' )
			);
			if ( 'home' !== $unattached ) {
				return $template;
			}
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
		$permalink = get_permalink( $parent_id );
		if ( empty( $permalink ) ) {
			return $template;
		}
		wp_safe_redirect( $permalink, 301 );
		exit;
	}

	/**
	 * Add attachment options to post type options
	 *
	 * @since 2.4.0
	 */
	public function filter_add_post_type_options( $opts, $post_type ) {
		if ( 'attachment' !== $post_type->name ) {
			return $opts;
		}
		$opts[] = array(
			'type'        => 'subheading',
			'label'       => __( 'Single Attachment Page Redirect', 'simple-seo-improvements' ),
			'description' => __( 'A single attachment page is a dedicated webpage automatically generated for an individual media file (such as an image, PDF, or video) uploaded to the Media Library.', 'simple-seo-improvements' ),
		);
		$opts[] = array(
			'name'              => 'atta_redirect',
			'type'              => 'checkbox',
			'th'                => __( 'Redirect', 'simple-seo-improvements' ),
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'classes'           => array( 'switch-button' ),
			'description'       => __( 'Redirect attachment pages to the page they were uploaded to (301).', 'simple-seo-improvements' ),
			'since'             => '2.4.0',
		);
		$opts[] = array(
			'name'              => 'atta_redirect_unattached',
			'type'              => 'radio',
			'th'                => __( 'Unattached attachments', 'simple-seo-improvements' ),
			'default'           => 'nothing',
			'options'           => array(
				'nothing' => array(
					'label' => __( 'Nothing — leave unattached attachment pages as they are.', 'simple-seo-improvements' ),
				),
				'home'    => array(
					'label' => __( 'Redirect to the main page.', 'simple-seo-improvements' ),
				),
			),
			'description'       => __( 'Choose what to do with attachment pages of files that are not attached to any content.', 'simple-seo-improvements' ),
			'since'             => '2.4.0',
			'related_to'        => 'atta_redirect',
		);
		return $opts;
	}
}
