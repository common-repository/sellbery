<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://christophercasper.com/
 * @since      1.0.0
 *
 * @package    Sellbery_WooCommerce
 * @subpackage Sellbery_WooCommerce/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Sellbery_WooCommerce
 * @subpackage Sellbery_WooCommerce/includes
 * @author     Christopher Casper <me@christophercasper.com>
 */
class Sellbery_WooCommerce_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'SellberyWooCommerce',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
