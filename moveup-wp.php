<?php
/**
 * Plugin Name: MoveUp
 * Author: MoveOn Technologies Ltd
 * Author URI: https://moveup.click
 * Version: 1.0.2
 * Description: MoveUp is an easy-to-use plugin that helps you to import data from multiple sites in just a few seconds.It allows the user to import, customize and distribute product data to multiple websites.Perfect add-on for busy Dropshipping sites that wants to manage product data efficiently.Import product data from sites like Alibaba, Aliexpress, 1688, amazon, eBay, Flipcart, Gearbest, Taobao and Walmart.
 * Text-Domain: moveup-wp
 * Copyright 2022-2023 MoveOn Technologies Ltd. All rights reserved.
 * Tested up to: 6.1.1
 * WC tested up to: 7.2.2
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) : exit(); endif; // No direct access allowed.

/**
 * Define Plugins constants
 */
define( 'MOVEUP_WP_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'MOVEUP_WP_URL', trailingslashit( plugins_url( '/', __FILE__ ) ) );
define( 'MOVEUP_WP_API', MOVEUP_WP_PATH . "api" );
define( 'MOVEUP_WP_INCLUDES', MOVEUP_WP_PATH . "includes" );
define( 'MOVEUP_WP_ASSETS', MOVEUP_WP_URL . "assets/" );
define( 'MOVEUP_WP_CSS', MOVEUP_WP_ASSETS . "css/" );
define( 'MOVEUP_WP_IMAGES', MOVEUP_WP_ASSETS . "images/" );
define( 'MOVEUP_WP_VERSION', '1.0.2' );

require_once MOVEUP_WP_PATH . 'environment.php';
require_once MOVEUP_WP_PATH . 'includes/include_files.php';
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

function moveup_woocommerce_warning() {
	?>
    <div id="message" class="error">
        <p><?php _e( 'Please install and activate WooCommerce to use MoveUp WP plugin.', 'moveup-wp' ); ?></p>
    </div>
	<?php
}

if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	add_action( 'admin_notices', "moveup_woocommerce_warning", 100 );
} else {
	$plugin = new MoveUpWPCore();
	$plugin->run();
}

add_action( 'rest_api_init', function () {
	require_once __DIR__ . '/includes/product_attributes_batch_update.php';
	$controller = new MoveUpAttributeBatchUpdateController();
	$controller->register_routes();
} );

/**
 * Class MoveUpWP
 */
class MoveUpWP {
	public function __construct() {
		register_activation_hook( __FILE__, [ $this, 'install' ] );
		register_deactivation_hook( __FILE__, [ $this, 'uninstall' ] );
	}

	/**
	 * When active plugin Function will be call
	 */
	public function install() {
		global $wp_version;
		if ( version_compare( $wp_version, "2.9", "<" ) ) {
			deactivate_plugins( basename( __FILE__ ) ); // Deactivate our plugin
			wp_die( "This plugin requires WordPress version 2.9 or higher." );
		}

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			wp_die( "This plugin requires Woocommerce plugin" );
		}

		add_action( 'activated_plugin', [ $this, 'after_activated' ] );
	}

	public function uninstall() {}


	public function after_activated( $plugin ) {
		if ( $plugin === plugin_basename( __FILE__ ) ) {
			$url = admin_url( '/admin.php?page=moveup-wp' );
			exit( wp_redirect( $url ) );
		}
	}
}

new MoveUpWP();