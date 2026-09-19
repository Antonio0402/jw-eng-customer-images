<?php
/**
 * Main Plugin File
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           JW_Eng_Customer_Images
 *
 * @wordpress-plugin
 * Plugin Name:       JW Eng Customer Images
 * Plugin URI:        http://example.com/jw-eng-customer-images-uri/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Your Name or Your Company
 * Author URI:        http://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       jw-eng-customer-images
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Creates/Maintains the object of Requirements Checker Class
 *
 * @return \JW_Eng_Customer_Images\Includes\Requirements_Checker
 * @since 1.0.0
 */
function plugin_requirements_checker() {
	static $requirements_checker = null;

	if ( null === $requirements_checker ) {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-requirements-checker.php';
		$requirements_conf = apply_filters( 'jw_eng_customer_images_minimum_requirements', include_once( plugin_dir_path( __FILE__ ) . 'requirements-config.php' ) );
		$requirements_checker = new JW_Eng_Customer_Images\Includes\Requirements_Checker( $requirements_conf );
	}

	return $requirements_checker;
}

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_jw_eng_customer_images() {

	// If Plugins Requirements are not met.
	if ( ! plugin_requirements_checker()->requirements_met() ) {
		add_action( 'admin_notices', array( plugin_requirements_checker(), 'show_requirements_errors' ) );

		// Deactivate plugin immediately if requirements are not met.
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		deactivate_plugins( plugin_basename( __FILE__ ) );

		return;
	}

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and frontend-facing site hooks.
	 */
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-jw-eng-customer-images.php';

	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since    1.0.0
	 */
	$router_class_name = apply_filters( 'jw_eng_customer_images_router_class_name', '\JW_Eng_Customer_Images\Core\Router' );
	$routes = apply_filters( 'jw_eng_customer_images_routes_file', plugin_dir_path( __FILE__ ) . 'routes.php' );
	$GLOBALS['jw_eng_customer_images'] = new JW_Eng_Customer_Images( $router_class_name, $routes );

	register_activation_hook( __FILE__, array( new JW_Eng_Customer_Images\App\Activator(), 'activate' ) );
	register_deactivation_hook( __FILE__, array( new JW_Eng_Customer_Images\App\Deactivator(), 'deactivate' ) );
}

run_jw_eng_customer_images();
