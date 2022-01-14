<?php
/*
Plugin Name: Simple SEO Improvements
Text Domain: simple-seo-improvements
Plugin URI: http://iworks.pl/simple-seo-improvements/
Description: Simple SEO improvements - only super necessary elements.
Version: PLUGIN_VERSION
Author: Marcin Pietrzak
Author URI: http://iworks.pl/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

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

/**
 * static options
 */
$base     = dirname( __FILE__ );
$includes = $base . '/includes';


/**
 * get plugin settings
 *
 * @since 1.0.6
 */
include_once $base . '/etc/options.php';

/**
 * @since 1.0.6
 */
if ( ! class_exists( 'iworks_options' ) ) {
	include_once $includes . '/iworks/options/options.php';
}

/**
 * require: Iworkssimple-seo-improvements Class
 */
if ( ! class_exists( 'iworks_simple_seo_improvements' ) ) {
	require_once $includes . '/iworks/class-simple-seo-improvements.php';
}
new iworks_simple_seo_improvements();

/**
 * i18n
 */
load_plugin_textdomain( 'simple-seo-improvements', false, plugin_basename( $base ) . '/languages' );


/**
 * install & uninstall plugin
 */
register_activation_hook( __FILE__, 'iworks_iworks_simple_seo_improvements_activate' );
register_deactivation_hook( __FILE__, 'iworks_iworks_simple_seo_improvements_deactivate' );

/**
 * load options
 *
 * since 2.6.8
 *
 */
global $iworks_simple_seo_improvements_options;
$iworks_simple_seo_improvements_options = null;

function get_iworks_simple_seo_improvements_options() {
	global $iworks_simple_seo_improvements_options;
	if ( is_object( $iworks_simple_seo_improvements_options ) ) {
		return $iworks_simple_seo_improvements_options;
	}
	$options = new iworks_options();
	$options->set_option_function_name( 'iworks_simple_seo_improvements_options' );
	$options->set_option_prefix( 'iworks_ssi_' );
	$options->init();
	$iworks_simple_seo_improvements_options = $options;
	return $iworks_simple_seo_improvements_options;
}

/**
 * Ask for vote
 */
include_once $includes . '/iworks/rate/rate.php';
do_action(
	'iworks-register-plugin',
	plugin_basename( __FILE__ ),
	__( 'Simple SEO Improvements', 'simple-seo-improvements' ),
	'simple-seo-improvements'
);

/**
 * Activate plugin function
 *
 * @since 2.6.0
 *
 */
function iworks_iworks_simple_seo_improvements_activate() {
	$options = get_iworks_simple_seo_improvements_options();
	$options->activate();
}

/**
 * Deactivate plugin function
 *
 * @since 2.6.0
 *
 */
function iworks_iworks_simple_seo_improvements_deactivate() {
}

