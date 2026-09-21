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
 * Description:       Quản lý danh mục, dịch vụ con và hình ảnh khách hàng của Website jwhospital.vn.
 * Version:           1.0.0
 * Author:            JW Hospital
 * Author URI:        https://jwhospital.vn
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       jw-eng-customer-images
 * Domain Path:       /languages
 * Requires at least: 6.7
 * Requires PHP:      8.1
 */

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Creates/Maintains the object of Requirements Checker Class
 *
 * @return \JW_Eng_Customer_Images\Includes\Requirements_Checker
 * @since 1.0.0
 */
function jw_eng_customer_images_requirements_checker() {
	static $requirements_checker = null;

	if ( null === $requirements_checker ) {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-requirements-checker.php';
		$requirements_conf = apply_filters( 'jw_eng_customer_images_minimum_requirements', include_once( plugin_dir_path( __FILE__ ) . 'requirements-config.php' ) );
		$requirements_checker = new JW_Eng_Customer_Images\Includes\Requirements_Checker( $requirements_conf );
	}

	return $requirements_checker;
}

// Giữ callable của boilerplate; bootstrap nội bộ dùng prefix riêng để tránh xung đột.
if ( ! function_exists( 'plugin_requirements_checker' ) ) {
	function plugin_requirements_checker() {
		return jw_eng_customer_images_requirements_checker();
	}
}

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_jw_eng_customer_images() {

	// If Plugins Requirements are not met.
	if ( ! jw_eng_customer_images_requirements_checker()->requirements_met() ) {
		add_action( 'admin_notices', array( jw_eng_customer_images_requirements_checker(), 'show_requirements_errors' ) );

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
