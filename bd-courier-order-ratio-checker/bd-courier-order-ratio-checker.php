<?php
/**
 * Plugin Name: BD Courier Order Ratio Checker 
 * Plugin URI: https://rasedulhaque.com/bd-courier-order-ratio-checker
 * Description: A plugin to show customer order ratio from BD Courier with settings and search functionality.
 * Version: 1.9
 * Author: Rasedul Haque
 * Author URI: https://rasedulhaque.com
 * Text Domain: bd-courier-order-ratio-checker
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

include 'lib/class.OrderRatioChecker.php';

// Initialize the plugin
new OrderRatioChecker();
