<?php
namespace JW_Eng_Customer_Images\Core\Registry;

if ( ! class_exists( __NAMESPACE__ . '\\' . 'Model' ) ) {
	/**
	 * Model Registry
	 *
	 * Maintains the list of all models objects
	 *
	 * @since      1.0.0
	 * @package    JW_Eng_Customer_Images
	 * @subpackage JW_Eng_Customer_Images/Core/Registry
	 * @author     Your Name <email@example.com>
	 */
	class Model {
		use Base_Registry;
	}
}
