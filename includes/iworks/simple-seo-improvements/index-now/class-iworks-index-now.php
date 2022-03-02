<?php
/*

Copyright 2022-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

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

if ( class_exists( 'iworks_simple_seo_improvements_index_now' ) ) {
	return;
}

require_once dirname( dirname( __FILE__ ) ) . '/class-base.php';

class iworks_simple_seo_improvements_index_now extends iworks_simple_seo_improvements_base {

	/**
	 * bing api key
	 */
	protected $api_key_bing = '';

	/**
	 * IndexNow URL
	 */
	protected $index_now_url = '';
}

