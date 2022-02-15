<?php
/*

Copyright 2021-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

Copyright 2015 Marios Alexandrou
Copyright 2012 Devin Walker
Copyright 2011 Mines (email: hi@mines.io)
Copyright 2008 Saurabh Gupta (email: saurabh0@gmail.com)

Based on the work by Saurabh Gupta (email : saurabh0@gmail.com)

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

if ( class_exists( 'iworks_simple_seo_improvements_prefixes' ) ) {
	return;
}

require_once dirname( __FILE__ ) . '/class-base.php';

class iworks_simple_seo_improvements_prefixes extends iworks_simple_seo_improvements_base {

	public function __construct( $iworks ) {
		$this->options = get_iworks_simple_seo_improvements_options();
		/**
		 * category_no_slug
		 */
		if ( $this->options->get_option( 'category_no_slug' ) ) {
			add_action( 'created_category', array( $this, 'flush_rules' ) );
			add_action( 'delete_category', array( $this, 'flush_rules' ) );
			add_action( 'edited_category', array( $this, 'flush_rules' ) );
			add_action( 'init', array( $this, 'category_permastruct' ) );
			add_filter( 'category_rewrite_rules', array( $this, 'category_rewrite_rules' ) );
			add_filter( 'query_vars', array( $this, 'category_query_vars' ) );
			add_filter( 'request', array( $this, 'category_request' ) );
		}
		/**
		 * tag_no_slug
		 */
		if ( $this->options->get_option( 'tag_no_slug' ) ) {
			add_action( 'created_post_tag', array( $this, 'flush_rules' ) );
			add_action( 'delete_post_tag', array( $this, 'flush_rules' ) );
			add_action( 'edited_post_tag', array( $this, 'flush_rules' ) );
			add_action( 'init', array( $this, 'tag_permastruct' ) );
			add_filter( 'query_vars', array( $this, 'tag_query_vars' ) );
			add_filter( 'request', array( $this, 'tag_request' ) );
			add_filter( 'tag_rewrite_rules', array( $this, 'tag_rewrite_rules' ) );
		}
	}

	public function flush_rules() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	/**
	 * Removes category base.
	 *
	 * @return void
	 */
	public function category_permastruct() {
		global $wp_rewrite;
		$wp_rewrite->extra_permastructs['category']['struct'] = '%category%';
	}

	/**
	 * Adds our custom category rewrite rules.
	 *
	 * @param  array $category_rewrite Category rewrite rules.
	 *
	 * @return array
	 */
	public function category_rewrite_rules( $category_rewrite ) {
		global $wp_rewrite;
		$category_rewrite = array();
		/* WPML is present: temporary disable terms_clauses filter to get all categories for rewrite */
		if ( class_exists( 'Sitepress' ) ) {
			global $sitepress;
			remove_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ) );
			$categories = get_categories( array( 'hide_empty' => false ) );
			//Fix provided by Albin here https://wordpress.org/support/topic/bug-with-wpml-2/#post-8362218
			//add_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ) );
			add_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10, 4 );
		} else {
			$categories = get_categories( array( 'hide_empty' => false ) );
		}
		foreach ( $categories as $category ) {
			$category_nicename = $category->slug;
			if ( $category->parent == $category->cat_ID ) {
				$category->parent = 0;
			} elseif ( $category->parent != 0 ) {
				$category_nicename = get_category_parents( $category->parent, false, '/', true ) . $category_nicename;
			}
			$category_rewrite[ '(' . $category_nicename . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$' ]    = 'index.php?category_name=$matches[1]&feed=$matches[2]';
			$category_rewrite[ "({$category_nicename})/{$wp_rewrite->pagination_base}/?([0-9]{1,})/?$" ] = 'index.php?category_name=$matches[1]&paged=$matches[2]';
			$category_rewrite[ '(' . $category_nicename . ')/?$' ]                                       = 'index.php?category_name=$matches[1]';
		}
		// Redirect support from Old Category Base
		$old_category_base                                 = get_option( 'category_base' ) ? get_option( 'category_base' ) : 'category';
		$old_category_base                                 = trim( $old_category_base, '/' );
		$category_rewrite[ $old_category_base . '/(.*)$' ] = 'index.php?category_redirect=$matches[1]';
		return $category_rewrite;
	}

	public function category_query_vars( $public_query_vars ) {
		$public_query_vars[] = 'category_redirect';
		return $public_query_vars;
	}

	/**
	 * Handles category redirects.
	 *
	 * @param $query_vars Current query vars.
	 *
	 * @return array $query_vars, or void if category_redirect is present.
	 */
	public function category_request( $query_vars ) {
		if ( isset( $query_vars['category_redirect'] ) ) {
			$catlink = trailingslashit( get_option( 'home' ) ) . user_trailingslashit( $query_vars['category_redirect'], 'category' );
			status_header( 301 );
			header( "Location: $catlink" );
			exit();
		}
		return $query_vars;
	}

	public function tag_permastruct() {
		global $wp_rewrite;
		$wp_rewrite->extra_permastructs['post_tag']['struct'] = '%post_tag%';
	}

	public function tag_rewrite_rules( $tag_rewrite ) {
		$tag_rewrite = array();
		$tags        = get_tags( array( 'hide_empty' => false ) );
		foreach ( $tags as $tag ) {
			$tag_nicename = $tag->slug;
			if ( $tag->parent === $tag->term_id ) {
				$tag->parent = 0;
			}
			//the magic
			$tag_rewrite[ '(' . $tag_nicename . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$' ] = 'index.php?tag=$matches[1]&feed=$matches[2]';
			$tag_rewrite[ '(' . $tag_nicename . ')/page/?([0-9]{1,})/?$' ]                  = 'index.php?tag=$matches[1]&paged=$matches[2]';
			$tag_rewrite[ '(' . $tag_nicename . ')/?$' ]                                    = 'index.php?tag=$matches[1]';
		}
		// Redirect support from Old Category Base
		global $wp_rewrite;
		$old_tag_base                            = get_option( 'tag_base' ) ? get_option( 'tag_base' ) : 'tag';
		$old_tag_base                            = trim( $old_tag_base, '/' );
		$tag_rewrite[ $old_tag_base . '/(.*)$' ] = 'index.php?tag_redirect=$matches[1]';
		return $tag_rewrite;
	}

	// Add 'tag_redirect' query variable
	public function tag_query_vars( $public_query_vars ) {
		$public_query_vars[] = 'tag_redirect';
		return $public_query_vars;
	}

	// Redirect if 'tag_redirect' is set
	public function tag_request( $query_vars ) {
		if ( ! isset( $query_vars['tag_redirect'] ) ) {
			return $query_vars;
		}
		$tag     = user_trailingslashit( $query_vars['tag_redirect'], 'post_tag' );
		$taglink = trailingslashit( get_option( 'home' ) ) . $tag;
		status_header( 301 );
		header( "Location: $taglink" );
		exit();
	}
}

