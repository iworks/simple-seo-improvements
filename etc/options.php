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
				'type'  => 'subheading',
				'label' => __( 'Appearance', 'simple-seo-improvements' ),
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
			 * Other
			 *
			 * @since 1.3.0
			 */
			array(
				'type'  => 'subheading',
				'label' => __( 'Other', 'simple-seo-improvements' ),
			),
			array(
				'name'    => 'post_types',
				'type'    => 'radio',
				'th'      => __( 'Post types', 'simple-seo-improvements' ),
				'default' => 'per_type',
				'options' => array(
					'per_type' => array(
						'label' => __( 'Allow each post type to have individual settings.', 'simple-seo-improvements' ),
					),
					'common'   => array(
						'label' => __( 'Common settings for all post types.', 'simple-seo-improvements' ),
					),
					'no'       => array(
						'label' => __( 'Do not support post types.', 'simple-seo-improvements' ),
					),
				),
			),
			array(
				'name'        => 'other_archives',
				'type'        => 'radio',
				'th'          => __( 'Other Archives', 'simple-seo-improvements' ),
				'default'     => 'no',
				'options'     => array(
					'per_type' => array(
						'label' => __( ' Allow each archive type to have individual settings.', 'simple-seo-improvements' ),
					),
					'common'   => array(
						'label' => __( 'Common settings for all archives.', 'simple-seo-improvements' ),
					),
					'no'       => array(
						'label' => __( 'Do not support on archives.', 'simple-seo-improvements' ),
					),
				),
				'description' => __( 'Date (day/month/year) and author archive.', 'simple-seo-improvements' ),
			),
			array(
				'type'  => 'heading',
				'label' => __( 'Robots', 'simple-seo-improvements' ),
			),
			/**
			 * subheader for robots.txt
			 *
			 * @since 1.5.5
			 */
			array(
				'type'  => 'subheading',
				'label' => __( 'robots.txt', 'simple-seo-improvements' ),
				'since' => '1.5.5',
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
				'classes'           => array( 'switch-button' ),
				'sanitize_callback' => 'absint',
				'description'       => __( 'A robots.txt file tells search engine crawlers which URLs the crawler can access on your site.', 'simple-seo-improvements' ),
			),
			/**
			 * Disallow: for robots.txt
			 *
			 * @since 1.5.5
			 */
			array(
				'name'        => 'robots_txt_disallow',
				'type'        => 'textarea',
				'th'          => __( 'Disallow:', 'simple-seo-improvements' ),
				'classes'     => array( 'large-text code' ),
				'default'     => simple_seo_improvements_robots_txt_disallow(),
				'description' => __( 'Add one entry per line.', 'simple-seo-improvements' ),
				'rows'        => 10,
				'since'       => '1.5.5',
			),
			/**
			 * Allow: for robots.txt
			 *
			 * @since 1.5.5
			 */
			array(
				'name'        => 'robots_txt_allow',
				'type'        => 'textarea',
				'th'          => __( 'Allow:', 'simple-seo-improvements' ),
				'classes'     => array( 'large-text code' ),
				'rows'        => 10,
				'default'     => simple_seo_improvements_robots_txt_allow(),
				'description' => __( 'Add one entry per line.', 'simple-seo-improvements' ),
				'since'       => '1.5.5',
			),
			/**
			 * IndexNow for Bing
			 *
			 * @since 1.3.0
			 */
			array(
				'type'  => 'subheading',
				'label' => __( 'Bing', 'simple-seo-improvements' ),
			),
			array(
				'name'              => 'indexnow_bing',
				'type'              => 'checkbox',
				'th'                => __( 'IndexNow for Bing', 'simple-seo-improvements' ),
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'description'       => __( 'IndexNow is an easy way for websites owners to instantly inform search engines about latest content changes on their website. In its simplest form.', 'simple-seo-improvements' ),
			),
			/**
			 * Google
			 *
			 * @since 1.5.2
			 */
			array(
				'type'        => 'subheading',
				'label'       => __( 'Google', 'simple-seo-improvements' ),
				'description' => __( 'The robots meta tag lets you utilize a granular, page-specific approach to controlling how an individual page should be indexed and served to users in Google Search results.', 'simple-seo-improvements' ),
			),
			/**
			 * googlebot
			 *
			 * @since 1.5.2
			 */
			array(
				'name'              => 'robots_googlebot',
				'type'              => 'checkbox',
				'th'                => __( 'Show in Google', 'simple-seo-improvements' ),
				'default'           => 1,
				'classes'           => array( 'switch-button' ),
				'sanitize_callback' => 'absint',
				'description'       => __( 'To show a page in Google\'s web search results.', 'simple-seo-improvements' ),
			),
			/**
			 * googlebot-news
			 *
			 * @since 1.5.2
			 */
			array(
				'name'              => 'robots_googlebot_news',
				'type'              => 'checkbox',
				'th'                => __( 'Show in Google News', 'simple-seo-improvements' ),
				'default'           => 1,
				'classes'           => array( 'switch-button' ),
				'sanitize_callback' => 'absint',
				'description'       => __( 'To show a page in Google\'s web search results, but not in Google News, use the googlebot-news meta tag', 'simple-seo-improvements' ),
			),
			/**
			 * max-snippet: [number]
			 *
			 * @since 1.5.2
			 */
			array(
				'name'              => 'robots_max_snippet',
				'type'              => 'number',
				'th'                => __( 'Max Snippet', 'simple-seo-improvements' ),
				'default'           => -1,
				'sanitize_callback' => 'intval',
				'description'       => __( 'Use a maximum of [number] characters as a textual snippet for this search result. If you don\'t specify this rule, Google will choose the length of the snippet. Set -1 and Google will choose the snippet length that it believes is most effective to help users discover your content and direct users to your site. Set 0 - no snippet is to be shown. Equivalent to nosnippet.', 'simple-seo-improvements' ),
			),
			/**
			 * max-image-preview: [setting]
			 *
			 * @since 1.5.2
			 */
			array(
				'name'        => 'robots_max_image_preview',
				'type'        => 'radio',
				'th'          => __( 'Max Image Preview', 'simple-seo-improvements' ),
				'default'     => 'large',
				'options'     => array(
					'none'     => array(
						'label' => __( 'No image preview is to be shown.', 'simple-seo-improvements' ),
					),
					'standard' => array(
						'label' => __( 'A default image preview may be shown.', 'simple-seo-improvements' ),
					),
					'large'    => array(
						'label' => __( 'A larger image preview, up to the width of the viewport, may be shown.', 'simple-seo-improvements' ),
					),
				),
				'description' => __( 'Set the maximum size of an image preview for this page in a search results. If you don\'t specify the max-image-preview rule, Google may show an image preview of the default size.', 'simple-seo-improvements' ),
			),
			/**
			 * max-video-preview: [setting]
			 *
			 * @since 1.5.2
			 */
			array(
				'name'              => 'robots_max_video_preview',
				'type'              => 'number',
				'th'                => __( 'Max Video Preview', 'simple-seo-improvements' ),
				'default'           => -1,
				'sanitize_callback' => 'intval',
				'description'       => __( 'This applies to all forms of search results (at Google: web search, Google Images, Google Videos, Discover, Assistant). This rule is ignored if no parseable [number] is specified.', 'simple-seo-improvements' ),
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

/**
 * get defaults for robots_txt_allow field
 *
 * @since 1.5.5
 */
function simple_seo_improvements_robots_txt_allow() {
	return implode(
		PHP_EOL,
		apply_filters(
			'simple_seo_improvements_robots_txt_allow',
			array(
				'/*/*.css',
				'/files/*',
				'/*/*.jpg',
				'/*/*.js',
				'/*/*.png',
				'/*/*.svg',
				'/*/*.webp',
				'/wp-admin/admin-ajax.php',
				'/wp-content/uploads/*',
			)
		)
	);
}

/**
 * get defaults for robots_txt_disallow field
 *
 * @since 1.5.5
 */
function simple_seo_improvements_robots_txt_disallow() {
	return implode(
		PHP_EOL,
		apply_filters(
			'simple_seo_improvements_robots_txt_disallow',
			array(
				'*?attachment_id=',
				'/category/*/feed',
				'*cf_action=*',
				'*/disclaimer/*',
				'*doing_wp_cron*',
				'/*/feed',
				'/.htaccess',
				'/license.txt',
				'*preview=true*',
				'/readme.html',
				'*replytocom=*',
				'/tag/*/feed',
				'*/trackback/',
				'/wp-admin/',
				'/wp-content/languages/',
				'/wp-*.php',
				'/xmlrpc.php',
				'/yoast-ga/outbound-article/',
			)
		)
	);
}

