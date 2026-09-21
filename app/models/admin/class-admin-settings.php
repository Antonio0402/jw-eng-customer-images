<?php
namespace JW_Eng_Customer_Images\App\Models\Admin;

use JW_Eng_Customer_Images\App\Models\Settings as Settings_Model;
use JW_Eng_Customer_Images\App\Models\Admin\Base_Model;
use JW_Eng_Customer_Images\App\Repository;
use JW_Eng_Customer_Images\App\Database;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\' . 'Admin_Settings' ) ) {
	/**
	 * Model class that implements Plugin Admin Settings
	 *
	 * @since      1.0.0
	 * @package    JW_Eng_Customer_Images
	 * @subpackage JW_Eng_Customer_Images/models/admin
	 */
	class Admin_Settings extends Base_Model {

		/**
		 * Constructor
		 *
		 * @since    1.0.0
		 */
		protected function __construct() {
			$this->register_hook_callbacks();
		}

		/**
		 * Register callbacks for actions and filters
		 *
		 * @since    1.0.0
		 */
		protected function register_hook_callbacks() {
			/**
			 * If you think all model related add_actions & filters should be in
			 * the model class only, then this this the place where you can place
			 * them.
			 *
			 * You can remove this method if you are not going to use it.
			 */
		}

		/**
		 * Register settings
		 *
		 * @since    1.0.0
		 */
		public function register_settings() {

			// The settings container.
			register_setting(
				Settings_Model::SETTINGS_NAME,     // Option group Name.
				Settings_Model::SETTINGS_NAME,     // Option Name.
				array( $this, 'sanitize' ) // Sanitize.
			);
		}

		/**
		 * Validates submitted setting values before they get saved to the database.
		 *
		 * @param array $input Settings Being Saved.
		 * @since    1.0.0
		 * @return array
		 */
		public function sanitize( $input ) {
			$new_input = array();
			if ( isset( $input ) && ! empty( $input ) ) {
				$new_input = $input;
			}

			return $new_input;
		}

		/**
		 * Returns the option key used to store the settings in database
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public function get_plugin_settings_option_key() {
			return Settings_Model::get_plugin_settings_option_key();
		}

		/**
		 * Retrieves all of the settings from the database
		 *
		 * @param string $setting_name Setting to be retrieved.
		 * @since    1.0.0
		 * @return array
		 */
		public function get_setting( $setting_name ) {
			return Settings_Model::get_setting( $setting_name );
		}

		/** Ghi dữ liệu qua repository, giữ nguyên các method Settings của boilerplate. */
		public function mutate( string $operation, string $entity, array $input, int $id ) {
			$repository = new Repository();
			if ( 'bulk_delete' === $operation ) {
				return 'image' === $entity && 0 === $id
					? $repository->bulk_delete_images( $input['image_record_ids'] ?? null )
					: new \WP_Error( 'invalid_operation', __( 'Chỉ hỗ trợ xóa hàng loạt bản ghi ảnh.', 'jw-eng-customer-images' ) );
			}
			return 'delete' === $operation ? $repository->delete( $entity, $id ) : $repository->save( $entity, $input, $id );
		}

		public function screen( string $entity, int $category, int $subcategory, int $page, int $id ) {
			$repository = new Repository();
			$state = array(
				'categories' => $repository->listing( 'category', 0, 0, 1, null ),
				'subcategories' => $repository->listing( 'subcategory', 0, 0, 1, null ),
				'listing' => $repository->listing( $entity, $category, $subcategory, $page ),
				'edit' => 0 < $id ? $repository->get( $entity, $id ) : null,
			);
			foreach ( $state as $value ) {
				if ( is_wp_error( $value ) ) {
					return $value;
				}
			}
			return $state;
		}
		
		/**
		 * Chuẩn bị contract dữ liệu cho template theo màn hình.
		 *
		 * @param string $screen  Màn hình hiện tại.
		 * @param array  $request Query string đã unslash.
		 * @return array<string, mixed>
		 */
		public function get_screen_data( string $screen, array $request ): array {
			$entities = array( 'categories' => 'category', 'subcategories' => 'subcategory', 'images' => 'image' );
			$entity = $entities[ $screen ];
			$category = Repository::integer( $request['category_id'] ?? '0' ) ?? 0;
			$subcategory = Repository::integer( $request['subcategory_id'] ?? '0' ) ?? 0;
			$page = max( 1, Repository::integer( $request['paged'] ?? '1' ) ?? 1 );
			$id = Repository::integer( $request['edit'] ?? '0' ) ?? 0;
			$notice = null;
			$old = null;
			$token = $request['result'] ?? '';
			if ( is_string( $token ) && preg_match( '/^[a-f0-9]{32}$/D', $token ) ) {
				$key = 'jw_eci_' . get_current_user_id() . '_' . $token;
				$flash = get_transient( $key );
				if ( is_array( $flash ) && $entity === $flash['entity'] ) {
					$notice = $flash['notice'];
					$old = $flash['input'];
					delete_transient( $key );
				}
			}
			$ready = Database::VERSION === get_option( Database::OPTION );
			$state = $ready ? $this->screen( $entity, $category, $subcategory, $page, $id ) : array();
			if ( is_wp_error( $state ) ) {
				$notice = array( 'type' => 'error', 'message' => $state->get_error_message() );
				$ready = false;
				$state = array();
			}
			return array_merge( $state, array(
				'entity' => $entity, 'category_filter' => $category, 'subcategory_filter' => $subcategory,
				'edit_id' => $id, 'notice' => $notice, 'old' => $old, 'ready' => $ready,
			) );
		}

	}

}
