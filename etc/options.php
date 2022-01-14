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
		'options'         => array(),
		'metaboxes'       => array(
			'assistance' => array(
				'title'    => __( 'We are waiting for your message', 'simple-seo-improvements' ),
				'callback' => 'iworks_iworks_seo_improvementss_options_need_assistance',
				'context'  => 'side',
				'priority' => 'core',
			),
			'love'       => array(
				'title'    => __( 'I love what I do!', 'simple-seo-improvements' ),
				'callback' => 'iworks_iworks_seo_improvements_options_loved_this_plugin',
				'context'  => 'side',
				'priority' => 'core',
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

	return $options;
}

function iworks_iworks_seo_improvements_options_get_robots_params() {
	$options = iworks_simple_seo_improvements_options();
	return $options['settings']['robots'];
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
