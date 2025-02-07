=== BD Courier Order Ratio Checker ===
Contributors: synthiasoft,rashedulhaquerumi
Donate link: https://bdcourier.com/donate
Tags: order ratio, courier, woocommerce, bd courier, tracking
Requires at least: 6.0
Tested up to: 6.6.2
Stable tag: 1.9
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin lets users fetch and display customer order ratios from BD Courier using their API. 
== Description ==

Order Ratio Checker integrates with BD Courier's API to display customer order ratios in a simple and user-friendly way. The plugin offers easy setup, making it accessible to users who want to track order statistics without any complex configuration.

**Note:** You need to register for free on BD Courier’s website to access the API. Once registered, you can start using the plugin with the API token provided.

= Features: =
* Fetch and display order ratios (Total, Success, Cancel) using BD Courier’s API.
* WooCommerce integration to display courier information on the order page.
* Customizable search form with AJAX-based submission.
* Admin settings for entering API token after registration.

== Installation ==

1. Register for free on BD Courier's website to obtain your API token.
2. Upload the plugin files to the `/wp-content/plugins/order-ratio-checker` directory, or install the plugin through the WordPress plugins screen directly.
3. Activate the plugin through the 'Plugins' screen in WordPress.
4. Navigate to the plugin settings and enter your API token to start fetching data from the BD Courier API.
5. Add the search form to any page using the `[bdcourier_search]` shortcode.

== Frequently Asked Questions ==

= Do I need an API key to use this plugin? =

Yes, you need to register for free on BD Courier’s website to get your API token, which is required to use the plugin.

= How do I obtain the API token? =

You can obtain the API token by registering for free on BD Courier’s website. After registration, you will receive the token in your account settings.
Website: https://bdcourier.com/

= Can I customize the search form? =

Yes, the search form can be customized using CSS.

== Screenshots ==

1. Screenshot-1.png — Shows the search form for customers to input their phone number.
2. Screenshot-2.png — Displays the order ratios fetched from BD Courier's API.

== Changelog ==
= 1.8 =
*Security Issue Fixed
= 1.7 =
* Initial release of the plugin.
* Added support for WooCommerce integration.
* Implemented AJAX-based search form and results display.

== Upgrade Notice ==
= 1.8 =
*Security Issue Fixed
= 1.7 =
Upgrade to the latest version to ensure compatibility with WooCommerce and WordPress 5.8.

== License ==

This plugin is licensed under the GPLv2 or later.