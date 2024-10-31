<?php

/**
 *
 * @since             1.0.0
 * @package           Sellbery_WooCommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Sellbery
 * Plugin URI:        https://gitlab.ecombix.com/pub/sellberywoocommerce
 * Description:       Sellbery Sellbery is a multichannel listing application designed to automate and optimize data synchronization between various eCommerce platforms, online marketplaces, shopping engines, and even social media such as Facebook.
 * Version:           1.0.1
 * Author:            Sellbery
 * Author URI:        https://sellbery.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'SELLBERY_VERSION', '1.0.1' );


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-SellberyWooCommerce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_Sellbery_WooCommerce() {

	$plugin = new Sellbery_WooCommerce();
	$plugin->run();

}
run_Sellbery_WooCommerce();
