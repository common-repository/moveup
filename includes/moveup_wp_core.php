<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.0.1
 * @package    moveup-wp
 * @subpackage moveup-wp/includes
 * @author     MoveOn
 */
class MoveUpWPCore {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    0.0.1
	 * @since    0.0.1
	 * @access   protected
	 * @var      $loader //Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    0.0.1
	 */
	public function __construct() {
		if ( defined( 'MOVEUP_WP_VERSION' ) ) {
			$this->version = MOVEUP_WP_VERSION;
		} else {
			$this->version = '0.0.1';
		}
		$this->plugin_name = 'moveup-wp';

		$this->load_dependencies();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Woo_Customers_Order_History_Loader. Orchestrates the hooks of the plugin.
	 * - Woo_Customers_Order_History_i18n. Defines internationalization functionality.
	 * - Woo_Customers_Order_History_Admin. Defines all hooks for the admin area.
	 * - Woo_Customers_Order_History_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function load_dependencies() {
		$this->loader = new MoveUpWPLoader();
	}


	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new MoveUpWPAdmin();
		# Hooks
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'admin_enqueue_scripts', 99 );
		# Menus
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'create_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'connect_to_moveup' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'admin_notices' );
		$this->loader->add_action( 'woocommerce_thankyou', $plugin_admin,
			'moveup_wp_new_order_place_add_meta', 10, 2 );
		$this->loader->add_filter( 'woocommerce_rest_api_get_rest_namespaces', $plugin_admin, 'register_custom_api_routes' );

        // Webp support
        $this->loader->add_filter( 'woocommerce_rest_allowed_image_mime_types', $plugin_admin, 'webp_upload_mimes' );
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     0.0.1
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     0.0.1
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    0.0.1
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    MoveUpWPLoader    Orchestrates the hooks of the plugin.
	 * @since     0.0.1
	 */
	public function get_loader() {
		return $this->loader;
	}
}