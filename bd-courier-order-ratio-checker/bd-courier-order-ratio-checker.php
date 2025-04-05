<?php
/**
 * Plugin Name: BD Courier Order Ratio Checker 
 * Plugin URI: https://rasedulhaque.com/bd-courier-order-ratio-checker
 * Description: A plugin to show customer order ratio from BD Courier with settings and search functionality.
 * Version: 2.0.1
 * Author: Rasedul Haque
 * Author URI: https://rasedulhaque.com
 * Text Domain: bd-courier-order-ratio-checker
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin directory path.
define( 'BD_COURIER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Include necessary class files.
require_once BD_COURIER_PLUGIN_DIR . 'lib/class.CourierAPI.php';
require_once BD_COURIER_PLUGIN_DIR . 'lib/class.CourierAdmin.php';
require_once BD_COURIER_PLUGIN_DIR . 'lib/class.CourierSearch.php';
require_once BD_COURIER_PLUGIN_DIR . 'lib/class.CourierSettings.php';

/**
 * Initialize the plugin by instantiating all classes.
 */
function initialize_courier_plugin() {
    new CourierAdmin();
    new CourierSearch();
    new CourierSettings();
}
add_action( 'plugins_loaded', 'initialize_courier_plugin' );