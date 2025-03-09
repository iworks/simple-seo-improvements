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
		'enqueue_scripts' => array(
			'simple-seo-improvements-admin',
		),
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
				'th'                => __( 'Default Site Icon', 'simple-seo-improvements' ),
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
			/**
			 * Other
			 *
			 * @since 2.2.0
			 */
			array(
				'type'  => 'subheading',
				'label' => __( 'External Links', 'simple-seo-improvements' ),
				'since' => '2.2.0',
			),
			array(
				'name'              => 'exli:rel:nofollow',
				'type'              => 'checkbox',
				'th'                => __( 'Nofollow', 'simple-seo-improvements' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'description'       => __( 'The nofollow attribute in the &lt;a&gt; tag tells search engines not to follow the link or pass link authority to the destination.', 'simple-seo-improvements' ),
				'since'             => '2.2.0',
			),
			array(
				'name'              => 'exli:target:blank',
				'type'              => 'checkbox',
				'th'                => __( 'Open in new window', 'simple-seo-improvements' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'description'       => __( 'The target="_blank" attribute is used in HTML to specify that a link should open in a new browser tab or window. By adding this attribute to a hyperlink (<a> tag), users can navigate to the linked page without leaving the current page. This is often used for external links, ensuring users can easily return to the original site.', 'simple-seo-improvements' ),
				'since'             => '2.2.0',
			),
			array(
				'name'              => 'exli:class',
				'type'              => 'text',
				'th'                => __( 'CSS class', 'simple-seo-improvements' ),
				'sanitize_callback' => 'esc_html',
				'default'           => 'external',
				'description'       => __( 'Separate classes by space, leave empty to no changes.', 'simple-seo-improvements' ),
				'since'             => '2.2.0',
			),
			/**
			 * Section: ROBOTS
			 */
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
				'type'        => 'subheading',
				'description' => __( 'A robots.txt is nothing but a text file instructs robots, such as search engine robots, how to crawl and index pages on their website. You can block/allow good or bad bots that follow your robots.txt file.', 'simple-seo-improvements' ),
				'label'       => __( 'robots.txt', 'simple-seo-improvements' ),
				'since'       => '1.5.5',
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
			 * Block AI Crawlers Bots
			 *
			 * @since 2.0.2
			 */
			array(
				'type'  => 'subheading',
				'label' => __( 'Block AI Crawlers Bots', 'simple-seo-improvements' ),
				'since' => '2.0.2',
			),
			array(
				'name'              => 'ai_block_chatgpt',
				'type'              => 'checkbox',
				'th'                => __( 'ChatGPT', 'simple-seo-improvements' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'since'             => '2.0.2',
			),
			array(
				'name'              => 'ai_block_google',
				'type'              => 'checkbox',
				'th'                => __( 'Google AI', 'simple-seo-improvements' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'since'             => '2.0.2',
			),
			array(
				'name'              => 'ai_block_CCBot',
				'type'              => 'checkbox',
				'th'                => __( 'CCBot', 'simple-seo-improvements' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'since'             => '2.0.2',
			),
			/**
			 * Structured Data Markup - LD+JSON
			 *
			 * @since 2.0.0
			 */
			array(
				'type'        => 'heading',
				'label'       => __( 'Structured Data', 'simple-seo-improvements' ),
				'description' => __( 'Structured data is a standardized format for providing information about a page and classifying the page content.', 'simple-seo-improvements' ),
				'since'       => '2.0.0',
			),
			array(
				'name'              => 'use_json_ld',
				'type'              => 'checkbox',
				'th'                => __( 'Use Structured Data Markup', 'simple-seo-improvements' ),
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'classes'           => array( 'switch-button' ),
				'since'             => '2.0.0',
			),
			array(
				'type'  => 'subheading',
				'label' => __( 'Site Representation', 'simple-seo-improvements' ),
				'since' => '2.0.0',
			),
			array(
				'name'        => 'json_name_alt',
				'type'        => 'text',
				'th'          => __( 'Alternate Website Name', 'simple-seo-improvements' ),
				'description' => __( 'Use the alternate website name for acronyms, or a shorter version of your website\'s name.', 'simple-seo-improvements' ),
				'classes'     => array(
					'large-text',
				),
				'since'       => '2.0.0',
			),

			array(
				'name'        => 'json_type',
				'type'        => 'radio',
				'th'          => __( 'Type', 'simple-seo-improvements' ),
				'description' => __( 'Choose whether your site represents an organization or a person.', 'simple-seo-improvements' ),
				'default'     => 'none',
				'options'     => array(
					'none'         => array(
						'label' => __( 'Don\'t use organization/person structure data.', 'simple-seo-improvements' ),
					),
					'organization' => array(
						'label' => __( 'Organization', 'simple-seo-improvements' ),
					),
					'person'       => array(
						'label' => __( 'Person', 'simple-seo-improvements' ),
					),
				),
				'since'       => '2.0.0',
			),
			array(
				'name'    => 'json_org_name',
				'type'    => 'text',
				'th'      => __( 'Organization name', 'simple-seo-improvements' ),
				'classes' => array(
					'large-text',
				),
				'group'   => 'organization',
				'since'   => '2.0.0',
			),
			array(
				'name'        => 'json_org_alt',
				'type'        => 'text',
				'th'          => __( 'Alternate organization name', 'simple-seo-improvements' ),
				'description' => __( 'Use the alternate organization name for acronyms, or a shorter version of your organization\'s name.', 'simple-seo-improvements' ),
				'classes'     => array(
					'large-text',
				),
				'since'       => '2.0.0',
			),
			array(
				'name'              => 'json_org_img',
				'type'              => 'image',
				'th'                => __( 'Organization logo', 'simple-seo-improvements' ),
				'sanitize_callback' => 'intval',
				'max-width'         => 64,
				'since'             => '2.0.0',
			),
			array(
				'name'    => 'json_org_pa_st',
				'type'    => 'text',
				'th'      => __( 'Street Address', 'simple-seo-improvements' ),
				'classes' => array(
					'large-text',
				),
				'since'   => '2.0.0',
			),
			array(
				'name'    => 'json_org_pa_l',
				'type'    => 'text',
				'th'      => __( 'Address Locality', 'simple-seo-improvements' ),
				'classes' => array(
					'large-text',
				),
				'since'   => '2.0.0',
			),
			array(
				'name'        => 'json_org_pa_r',
				'type'        => 'text',
				'th'          => __( 'Address Region', 'simple-seo-improvements' ),
				'description' => __( 'E.g. state, voivodeship', 'simple-seo-improvements' ),
				'classes'     => array(
					'large-text',
				),
				'since'       => '2.0.0',
			),
			array(
				'name'  => 'json_org_pa_pc',
				'type'  => 'text',
				'th'    => __( 'Postal Code', 'simple-seo-improvements' ),
				'since' => '2.0.0',
			),
			array(
				'name'              => 'json_org_pa_c',
				'type'              => 'select',
				'th'                => __( 'Address Country', 'simple-seo-improvements' ),
				'since'             => '2.0.0',
				'options'           => iworks_simple_seo_improvements_get_countries(),
				'sanitize_callback' => 'esc_html',
			),
			array(
				'name'              => 'json_org_lb',
				'type'              => 'select',
				'th'                => __( 'Local Buissnes Page', 'simple-seo-improvements' ),
				'description'       => __( 'Select a page to fill local business (LocalBusiness) structured data. Edit selected page to add or change LocalBusiness data.', 'simple-seo-improvements' ),
				'options'           => iworks_simple_seo_improvements_get_pages(),
				'sanitize_callback' => 'intval',
				'since'             => '2.0.0',
			),
			array(
				'name'        => 'json_person',
				'type'        => 'select',
				'th'          => __( 'Select User', 'simple-seo-improvements' ),
				'options'     => iworks_simple_seo_improvements_get_users(),
				'description' => __( 'You have selected the user admin as the person this site represents. Their user profile information will now be used in search results. Update their profile to make sure the information is correct.', 'simple-seo-improvements' ),
				'group'       => 'person',
				'since'       => '2.0.0',
			),
			array(
				'name'              => 'json_person_img',
				'type'              => 'image',
				'th'                => __( 'Personal logo or avatar', 'simple-seo-improvements' ),
				'sanitize_callback' => 'intval',
				'max-width'         => 64,
				'since'             => '2.0.0',
			),
			array(
				'name'        => 'json_other',
				'type'        => 'textarea',
				'th'          => __( 'Other profiles', 'simple-seo-improvements' ),
				'description' => __( 'Tell us if you have any other profiles on the web that belong to your organization. This can be any number of profiles, like YouTube, LinkedIn, Pinterest, or even Wikipedia. Put one URL per line.', 'simple-seo-improvements' ),
				'classes'     => array(
					'large-text',
					'code',
				),
				'rows'        => 10,
				'since'       => '2.0.0',
			),

			/**
			 * Add custom code
			 *
			 * @since 1.1.0
			 */
			array(
				'type'        => 'heading',
				'label'       => __( 'Custom Code', 'simple-seo-improvements' ),
				'description' => __( 'Use these settings to insert code from Google Tag Manager, Google Analytics or webmaster tools verification.', 'simple-seo-improvements' ),
			),
			array(
				'name'              => 'html_head',
				'type'              => 'textarea',
				'th'                => __( 'Header Code', 'simple-seo-improvements' ),
				'description'       => __( 'Code entered in this box will be printed in the <code>&lt;head&gt;</code> section.', 'simple-seo-improvements' ),
				'classes'           => array(
					'large-text',
					'code',
				),
				'rows'              => 10,
				'sanitize_callback' => 'iworks_seo_improvements_no_sanitization',
			),
			array(
				'name'              => 'html_body_start',
				'type'              => 'textarea',
				'th'                => __( 'Body Start', 'simple-seo-improvements' ),
				'description'       => __( 'Code entered in this box will be printed after the opening <code>&lt;body&gt;</code> tag.', 'simple-seo-improvements' ),
				'classes'           => array(
					'large-text',
					'code',
				),
				'rows'              => 10,
				'sanitize_callback' => 'iworks_seo_improvements_no_sanitization',
			),
			array(
				'name'              => 'html_body_end',
				'type'              => 'textarea',
				'th'                => __( 'Body End', 'simple-seo-improvements' ),
				'description'       => __( 'Code entered in this box will be printed before the closing <code>&lt;/body&gt;</code> tag.', 'simple-seo-improvements' ),
				'classes'           => array(
					'large-text',
					'code',
				),
				'rows'              => 10,
				'sanitize_callback' => 'iworks_seo_improvements_no_sanitization',
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

/**
 * get users list
 *
 * @since 2.0.0
 */
function iworks_simple_seo_improvements_get_users() {
	return apply_filters( 'iworks_simple_seo_improvements_get_users', array() );
}

/**
 * get pages list
 *
 * @since 2.0.0
 */
function iworks_simple_seo_improvements_get_pages() {
	return apply_filters( 'iworks_simple_seo_improvements_get_pages', array() );
}

/**
 * get Countries list
 *
 * @since 2.1.0
 */
function iworks_simple_seo_improvements_get_countries() {
	return apply_filters( 'iworks_simple_seo_improvements_get_countries', array() );
}

/**
 * custom avoid sanityzation
 *
 * @saince 2.2.4
 */
function iworks_seo_improvements_no_sanitization( $content ) {
	return $content;
}

