<?php

function iworks_simple_seo_improvements_options() {
	$options = array();
	/**
	 * main settings
	 */
	$options['index'] = array(
		'use_tabs'        => true,
		'version'         => '0.0',
		'page_title'      => __( 'Simple SEO Improvements Configuration', 'simple-seo-improvements' ),
		'menu_title'      => __( 'SEO Improvements', 'simple-seo-improvements' ),
		'menu'            => 'options',
		'enqueue_scripts' => array(),
		'enqueue_styles'  => array(),
		'options'         => array(
			array(
				'type'  => 'heading',
				'label' => __( 'General', 'simple-seo-improvements' ),
			),
			array(
				'name'              => 'default_image',
				'type'              => 'image',
				'th'                => __( 'Default site Icon', 'iworks-pwa' ),
				'sanitize_callback' => 'intval',
				'max-width'         => 64,
			),
			array(
				'name'              => 'use_as_favicon',
				'type'              => 'checkbox',
				'th'                => __( 'Use as favicon', 'simple-seo-improvements' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'group'             => 'prefixes',
			),
			array(
				'name'        => 'home_meta_description',
				'type'        => 'textarea',
				'th'          => __( 'Home meta description', 'simple-seo-improvements' ),
				'description' => __( 'This field is avaialble only when your homepage show blog posts not as a page', 'simple-seo-improvements' ),
				'classes'     => array(
					'large-text',
					'code',
				),
				'rows'        => 2,
				'group'       => 'meta-description',
			),
			/**
			 * category/tag prefixes remover
			 */
			array(
				'name'                => 'category_no_slug',
				'type'                => 'checkbox',
				'th'                  => __( 'Remove category URL prefix', 'simple-seo-improvements' ),
				'default'             => 0,
				'sanitize_callback'   => 'absint',
				'classes'             => array( 'switch-button' ),
				'description'         => __( 'Turn it on to remove the category prefix.', 'simple-seo-improvements' ),
				'flush_rewrite_rules' => true,
				'group'               => 'prefixes',
			),
			array(
				'name'                => 'tag_no_slug',
				'type'                => 'checkbox',
				'th'                  => __( 'Remove tag URL prefix', 'simple-seo-improvements' ),
				'default'             => 0,
				'sanitize_callback'   => 'absint',
				'classes'             => array( 'switch-button' ),
				'description'         => __( 'Turn it on to remove the tag prefix.', 'simple-seo-improvements' ),
				'flush_rewrite_rules' => true,
				'group'               => 'prefixes',
			),
			/**
			 * Add social media
			 *
			 * @since 1.1.0
			 */
			array(
				'type'        => 'subheading',
				'label'       => __( 'Social Media', 'simple-seo-improvements' ),
				'description' => __( 'If you are using Facebook or Twitter analytic tools, enter the details below. Omitting them has no effect on how a shared web page appears on a Facebook timeline or Twitter feed.', 'simple-seo-improvements' ),
			),
			array(
				'name'              => 'fb:app_id',
				'type'              => 'text',
				'th'                => __( 'Facebook app ID', 'simple-seo-improvements' ),
				'sanitize_callback' => 'esc_html',
				'description'       => __( 'A Facebook App ID is a unique number that identifies your app when you request ads from Audience Network.', 'simple-seo-improvements' ),
			),
			array(
				'name'              => 'twitter:site',
				'type'              => 'text',
				'th'                => __( 'Twitter site', 'simple-seo-improvements' ),
				'sanitize_callback' => 'esc_html',
				'placeholder'       => _x( '@account_name', 'placeholder', 'simple-seo-improvements' ),
				'description'       => __( '@username for the website used in the card footer.', 'simple-seo-improvements' ),
			),
			/**
			 * IndexNow
			 *
			 * @since 1.3.0
			 */
			array(
				'type'  => 'subheading',
				'label' => __( 'Other', 'simple-seo-improvements' ),
			),
			/**
			 * IndexNow for Bing
			 *
			 * @since 1.3.0
			 */
			array(
				'name'              => 'indexnow_bing',
				'type'              => 'checkbox',
				'th'                => __( 'IndexNow for Bing', 'simple-seo-improvements' ),
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'sanitize_callback' => 'intval',
				'description'       => __( 'IndexNow is an easy way for websites owners to instantly inform search engines about latest content changes on their website. In its simplest form.', 'simple-seo-improvements' ),
			),
			/**
			 * robots.txt
			 *
			 * @since 1.4.0
			 */
			array(
				'name'              => 'robots_txt',
				'type'              => 'checkbox',
				'th'                => __( 'Improve robots.txt', 'simple-seo-improvements' ),
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'sanitize_callback' => 'intval',
				'description'       => __( 'A robots.txt file tells search engine crawlers which URLs the crawler can access on your site.', 'simple-seo-improvements' ),
			),
			/**
			 * Add custom code
			 *
			 * @since 1.1.0
			 */
			array(
				'type'        => 'heading',
				'label'       => __( 'Custom code', 'simple-seo-improvements' ),
				'description' => __( 'Use these settings to insert code from Google Tag Manager, Google Analytics or webmaster tools verification.', 'simple-seo-improvements' ),
			),
			array(
				'name'        => 'html_head',
				'type'        => 'textarea',
				'th'          => __( 'Header Code', 'simple-seo-improvements' ),
				'description' => __( 'Code entered in this box will be printed in the <code>&lt;head&gt;</code> section.', 'simple-seo-improvements' ),
				'classes'     => array(
					'large-text',
					'code',
				),
				'rows'        => 10,
			),
			array(
				'name'        => 'html_body_start',
				'type'        => 'textarea',
				'th'          => __( 'Body Start', 'simple-seo-improvements' ),
				'description' => __( 'Code entered in this box will be printed after the opening <code>&lt;body&gt;</code> tag.', 'simple-seo-improvements' ),
				'classes'     => array(
					'large-text',
					'code',
				),
				'rows'        => 10,
			),
			array(
				'name'        => 'html_body_end',
				'type'        => 'textarea',
				'th'          => __( 'Body End', 'simple-seo-improvements' ),
				'description' => __( 'Code entered in this box will be printed before the closing <code>&lt;/body&gt;</code> tag.', 'simple-seo-improvements' ),
				'classes'     => array(
					'large-text',
					'code',
				),
				'rows'        => 10,
			),
		),
		'metaboxes'       => array(
			'assistance' => array(
				'title'    => __( 'We are waiting for your message', 'simple-seo-improvements' ),
				'callback' => 'iworks_iworks_seo_improvementss_options_need_assistance',
				'context'  => 'side',
				'priority' => 'default',
			),
			'love'       => array(
				'title'    => __( 'I love what I do!', 'simple-seo-improvements' ),
				'callback' => 'iworks_iworks_seo_improvements_options_loved_this_plugin',
				'context'  => 'side',
				'priority' => 'low',
			),
		),
	);
	/**
	 * params
	 */
	$options['settings'] = array(
		'robots' => array(
			'noindex',
			'nofollow',
			'noimageindex',
			'noarchive',
			'nocache',
			'nosnippet',
			'notranslate',
			'noyaca',
		),
	);
	return apply_filters( 'iworks_plugin_get_options', $options, 'simple-seo-improvements' );
}

function iworks_iworks_seo_improvements_options_loved_this_plugin( $iworks_iworks_seo_improvements ) {
	$content = apply_filters( 'iworks_rate_love', '', 'simple-seo-improvements' );
	if ( ! empty( $content ) ) {
		echo $content;
		return;
	}
	?>
<p><?php _e( 'Below are some links to help spread this plugin to other users', 'simple-seo-improvements' ); ?></p>
<ul>
	<li><a href="https://wordpress.org/support/plugin/simple-seo-improvements/reviews/#new-post"><?php _e( 'Give it a five stars on WordPress.org', 'simple-seo-improvements' ); ?></a></li>
	<li><a href="<?php _ex( 'https://wordpress.org/plugins/simple-seo-improvements/', 'plugin home page on WordPress.org', 'simple-seo-improvements' ); ?>"><?php _e( 'Link to it so others can easily find it', 'simple-seo-improvements' ); ?></a></li>
</ul>
	<?php
}
function iworks_iworks_seo_improvements_taxonomies() {
	$data       = array();
	$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
	foreach ( $taxonomies as $taxonomy ) {
		$data[ $taxonomy->name ] = $taxonomy->labels->name;
	}
	return $data;
}
function iworks_iworks_seo_improvements_post_types() {
	$args       = array(
		'public' => true,
	);
	$p          = array();
	$post_types = get_post_types( $args, 'names' );
	foreach ( $post_types as $post_type ) {
		$a               = get_post_type_object( $post_type );
		$p[ $post_type ] = $a->labels->name;
	}
	return $p;
}

function iworks_iworks_seo_improvementss_options_need_assistance( $iworks_iworks_seo_improvementss ) {
	$content = apply_filters( 'iworks_rate_assistance', '', 'simple-seo-improvements' );
	if ( ! empty( $content ) ) {
		echo $content;
		return;
	}

	?>
<p><?php _e( 'We are waiting for your message', 'simple-seo-improvements' ); ?></p>
<ul>
	<li><a href="<?php _ex( 'https://wordpress.org/support/plugin/simple-seo-improvements/', 'link to support forum on WordPress.org', 'simple-seo-improvements' ); ?>"><?php _e( 'WordPress Help Forum', 'simple-seo-improvements' ); ?></a></li>
</ul>
	<?php
}
