<?php
namespace JW_Eng_Customer_Images\App\Views\Admin;

defined( 'ABSPATH' ) || exit;

use JW_Eng_Customer_Images\Core\View;
use JW_Eng_Customer_Images as JW_Eng_Customer_Images;

if ( ! class_exists( __NAMESPACE__ . '\\' . 'Admin_Settings' ) ) {
	/**
	 * View class to load all templates related to Plugin's Admin Settings Page
	 *
	 * @since      1.0.0
	 * @package    JW_Eng_Customer_Images
	 * @subpackage JW_Eng_Customer_Images/views/admin
	 */
	class Admin_Settings extends View {
		public function render( array $args ): void {
			$this->admin_settings_page( $args );
		}
		/**
		 * Prints Settings Page.
		 *
		 * @param  array $args Arguments passed by `markup_settings_page` method from `JW_Eng_Customer_Images\App\Controllers\Admin\Admin_Settings` controller.
		 * @return void
		 * @since 1.0.0
		 */
		public function admin_settings_page( $args = [] ) {
		/**
		 * Render giao diện quản trị Category, Subcategory và Images.
		 *
		 * @param array $args Contract dữ liệu do controller cung cấp.
		 * @return void
		 */
			echo $this->render_template(
				'admin/customer-images.php',
				$args
			); // WPCS: XSS OK.
		}

		/**
		 * Prints Section's Description.
		 *
		 * @param  array $args Arguments passed by `markup_section_headers` method from  `JW_Eng_Customer_Images\App\Controllers\Admin\Admin_Settings` controller.
		 * @return void
		 * @since 1.0.0
		 */
		public function section_headers( $args = [] ) {
			echo $this->render_template(
				'admin/page-settings/page-settings-section-headers.php',
				$args
			); // WPCS: XSS OK.
		}

		/**
		 * Prints text field
		 *
		 * @param  array $args Arguments passed by `markup_fields` method from `JW_Eng_Customer_Images\App\Controllers\Admin\Admin_Settings` controller.
		 * @return void
		 * @since 1.0.0
		 */
		public function markup_fields( $args = [] ) {
			echo $this->render_template(
				'admin/page-settings/page-settings-fields.php',
				$args
			); // WPCS: XSS OK.
		}
	}
}
