<?php
/**
 * Unit test bootstrap.
 *
 * Loads the plugin's pure PHP classes with a minimal WordPress
 * function stub set, so tests can run without a WordPress install.
 *
 * @package simple-seo-improvements
 */

define( 'WPINC', 'wp-includes' );
define( 'PLUGIN_VERSION_TEST', 'test' );

// --- Minimal WordPress stubs -------------------------------------------------

$wp_options   = array();
$wp_actions   = array();
$wp_filters   = array();

function add_action( $hook, $cb, $priority = 10, $args = 1 ) {
	return true;
}
function add_filter( $hook, $cb, $priority = 10, $args = 1 ) {
	return true;
}
function apply_filters( $hook, $value ) {
	return $value;
}
function get_option( $name, $default = false ) {
	global $wp_options;
	return isset( $wp_options[ $name ] ) ? $wp_options[ $name ] : $default;
}
function update_option( $name, $value ) {
	global $wp_options;
	$wp_options[ $name ] = $value;
	return true;
}
function wp_strip_all_tags( $string, $remove_breaks = false ) {
	$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
	$string = strip_tags( $string );
	if ( $remove_breaks ) {
		$string = preg_replace( '/[\\r\\n\\t ]+/', ' ', $string );
	}
	return trim( $string );
}
function esc_html( $text ) {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8', false );
}
function esc_attr( $text ) {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8', false );
}
function wp_unslash( $value ) {
	return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( $value );
}
function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, (array) $args );
}
function wp_rand( $min = 0, $max = 0 ) {
	if ( $min > $max ) {
		$tmp = $min; $min = $max; $max = $tmp;
	}
	return $max === $min ? $min : random_int( $min, $max );
}
$GLOBALS['test_is_revision'] = false;
$GLOBALS['test_is_autosave'] = false;
function wp_is_post_revision( $post_id ) {
	global $test_is_revision;
	return ! empty( $test_is_revision );
}
function wp_is_post_autosave( $post_id ) {
	global $test_is_autosave;
	return ! empty( $test_is_autosave );
}
function current_user_can( $cap, ...$args ) {
	return true;
}
function checked( $checked, $current, $display = true ) {
	$result = ( (string) $checked === (string) $current );
	return $display && $result ? 'checked="checked"' : '';
}
function selected( $checked, $current, $display = true ) {
	$result = ( (string) $checked === (string) $current );
	return $display && $result ? 'selected="selected"' : '';
}
function _deprecated_function( $function, $version, $replacement = null ) {
}
function get_locale() {
	return 'pl_PL';
}
function __( $text, $domain = 'default' ) {
	return $text;
}
function esc_html__( $text, $domain = 'default' ) {
	return esc_html( $text );
}
function esc_attr__( $text, $domain = 'default' ) {
	return esc_attr( $text );
}
function _e( $text, $domain = 'default' ) {
	echo esc_html( $text );
}
function _x( $text, $context, $domain = 'default' ) {
	return $text;
}
function sanitize_title( $title ) {
	$title = strtolower( wp_strip_all_tags( $title ) );
	return preg_replace( '/[^a-z0-9]+/', '-', trim( $title ) );
}
function sanitize_text_field( $str ) {
	return trim( preg_replace( '/[\\r\\n\\t ]+/', ' ', wp_strip_all_tags( $str ) ) );
}
function is_admin() {
	return false;
}
function remove_query_arg( $keys, $uri ) {
	$uri = preg_replace( '/([?&])(' . implode( '|', array_map( 'preg_quote', (array) $keys ) ) . ')=[^&]*&?/', '$1', $uri );
	return rtrim( $uri, '?&' );
}
function get_bloginfo( $show = '' ) {
	return 'Test Blog';
}
function wp_safe_redirect( $url, $status = 302 ) {
	return false;
}
function home_url( $path = '' ) {
	return 'https://example.test' . $path;
}
function get_post_mime_type( $id ) {
	return 'image/png';
}
function wp_make_link_relative( $url ) {
	return preg_replace( '|^https?://[^/]+|i', '', $url );
}

// --- Load plugin files -------------------------------------------------------

require dirname( __DIR__, 2 ) . '/etc/options.php';
require dirname( __DIR__, 2 ) . '/includes/iworks/simple-seo-improvements/class-iworks-simple-seo-improvements-base-abstract.php';
