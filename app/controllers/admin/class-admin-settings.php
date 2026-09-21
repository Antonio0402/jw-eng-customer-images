<?php
namespace JW_Eng_Customer_Images\App\Controllers\Admin;

use JW_Eng_Customer_Images\App\Controllers\Admin\Base_Controller;
use JW_Eng_Customer_Images as JW_Eng_Customer_Images;
use JW_Eng_Customer_Images\App\Repository;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( __NAMESPACE__ . '\\' . 'Admin_Settings' ) ) {

	/**
	 * Controller class that implements Plugin Admin Settings configurations
	 *
	 * @since      1.0.0
	 * @package    JW_Eng_Customer_Images
	 * @subpackage JW_Eng_Customer_Images/controllers/admin
	 */
	class Admin_Settings extends Base_Controller {

		/**
		 * Holds suffix for dynamic add_action called on settings page.
		 *
		 * @var string
		 * @since 1.0.0
		 */
		private static $hook_suffix = '';

		/**
		 * @var string[] Hook của ba màn hình quản trị.
		 * @since 1.0.0
		 * */
		private static $hook_suffixes = array();

		/**
		 * Slug of the Settings Page
		 *
		 * @since    1.0.0
		 */
		const SETTINGS_PAGE_SLUG = JW_Eng_Customer_Images::PLUGIN_ID;
		const SUBCATEGORIES_PAGE_SLUG = JW_Eng_Customer_Images::PLUGIN_ID . '-subcategories';
		const IMAGES_PAGE_SLUG = JW_Eng_Customer_Images::PLUGIN_ID . '-images';
	// const VIDEOS_PAGE_SLUG = JW_Eng_Customer_Images::PLUGIN_ID . '-videos';

		const SLUGS = array(
			'category' => self::SETTINGS_PAGE_SLUG,
			'subcategory' => self::SUBCATEGORIES_PAGE_SLUG,
			'image' => self::IMAGES_PAGE_SLUG,
		);
		private static $slugs = self::SLUGS;

		/**
		 * Capability required to access settings page
		 *
		 * @since 1.0.0
		 */
		const REQUIRED_CAPABILITY = 'manage_options';
		const EDIT_REQUIRED_CAPABILITY = self::REQUIRED_CAPABILITY;
		const ACTION = 'jw_eng_ci_admin_action';
		const NONCE_ACTION = 'jw_eng_ci_admin_action';

		/**
		 * Register callbacks for actions and filters
		 *
		 * @since    1.0.0
		 */
		public function register_hook_callbacks() {
			// Create Menu.
			add_action( 'admin_menu', array( $this, 'plugin_menu' ) );

			// Enqueue Styles & Scripts.
			// add_action( 'admin_print_scripts-' . static::$hook_suffix, array( $this, 'enqueue_scripts' ) );
			// add_action( 'admin_print_styles-' . static::$hook_suffix, array( $this, 'enqueue_styles' ) );

			// Register Fields.
			// add_action( 'load-' . static::$hook_suffix, array( $this, 'register_fields' ) );

			// Enqueue Styles & Scripts.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

			add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_post' ) );

			// Register Settings.
			add_action( 'admin_init', array( $this->get_model(), 'register_settings' ) );

			// Settings Link on Plugin's Page.
			add_filter(
				'plugin_action_links_' . JW_Eng_Customer_Images::PLUGIN_ID . '/' . JW_Eng_Customer_Images::PLUGIN_ID . '.php',
				array( $this, 'add_plugin_action_links' )
			);
		}

		/**
		 * Create menu for Plugin inside Settings menu
		 *
		 * @since    1.0.0
		 */
		public function plugin_menu() {
			self::$hook_suffix = add_menu_page(
				__( 'JW Service Categories', 'jw-eng-customer-images' ),
				__( 'JW Service Categories', 'jw-eng-customer-images' ),
				self::REQUIRED_CAPABILITY, self::SETTINGS_PAGE_SLUG,
				array( $this, 'markup_settings_page' ), 'dashicons-format-gallery', 58
			);
			self::$hook_suffixes = array( self::$hook_suffix );
			$callbacks = array( 'category' => 'markup_settings_page', 'subcategory' => 'markup_subcategory_page', 'image' => 'markup_image_page' );
			foreach ( $this->labels() as $entity => $label ) {
				self::$hook_suffixes[] = add_submenu_page(
					self::SETTINGS_PAGE_SLUG, $label, $label, self::REQUIRED_CAPABILITY,
					self::$slugs[ $entity ], array( $this, $callbacks[ $entity ] )
				);
			}
		}


		private function labels(): array {
			return array(
				'category' => __( 'Categories', 'jw-eng-customer-images' ),
				'subcategory' => __( 'Subcategories', 'jw-eng-customer-images' ),
				'image' => __( 'Images', 'jw-eng-customer-images' ),
			);
		}

		public function enqueue_assets( string $hook ): void {
			if ( ! in_array( $hook, self::$hook_suffixes, true ) ) {
				return;
			}
			$this->enqueue_scripts();
			$this->enqueue_styles();
		}

		/**
		 * Register the JavaScript for the admin area.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_scripts() {
			wp_enqueue_media();
			$handle = JW_Eng_Customer_Images::PLUGIN_ID . '_admin-js';
			wp_enqueue_script(
				$handle, JW_Eng_Customer_Images::get_plugin_url() . 'assets/js/admin/jw-eng-customer-images.js',
				array( 'media-views', 'wp-util' ),
				(string) filemtime( JW_Eng_Customer_Images::get_plugin_path() . 'assets/js/admin/jw-eng-customer-images.js' ), true
			);
			wp_localize_script( $handle, 'jwEngCustomerImages', array(
				'title' => __( 'Chọn hình ảnh', 'jw-eng-customer-images' ),
				'useImage' => __( 'Sử dụng ảnh đã chọn', 'jw-eng-customer-images' ),
			) );
		}

		/**
		 * Register the JavaScript for the admin area.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_styles() {
			wp_enqueue_style(
				JW_Eng_Customer_Images::PLUGIN_ID . '_admin-css',
				JW_Eng_Customer_Images::get_plugin_url() . 'assets/css/admin/jw-eng-customer-images.css',
				array(), (string) filemtime( JW_Eng_Customer_Images::get_plugin_path() . 'assets/css/admin/jw-eng-customer-images.css' ), 'all'
			);
		}

		/** Render dữ liệu đã được model chuẩn hóa. */
		private function render_management_page( string $screen ): void {
			$this->authorize();
			if ( ! in_array( $screen, array( 'categories', 'subcategories', 'images' ), true ) ) {
				wp_die( esc_html__( 'Màn hình không thuộc phạm vi quản lý ảnh.', 'jw-eng-customer-images' ) );
			}
			$args = $this->get_model()->get_screen_data( $screen, wp_unslash( $_GET ) );
			$args['action'] = self::ACTION;
			$args['nonce_action'] = self::NONCE_ACTION;
			$args['labels'] = $this->labels();
			$args['slugs'] = self::$slugs;
			$this->view->render( $args );
		}

		/** Trả về capability theo màn hình hoặc entity quản trị. */
		private function required_capability_for_context( string $context ): string {
			// V1 yêu cầu manage_options cho cả ba loại dữ liệu.
			return self::REQUIRED_CAPABILITY;
		}

		/**
		 * Creates the markup for the Settings page
		 *
		 * @since    1.0.0
		 */
		public function markup_settings_page() {
			$this->render_management_page( 'categories' );
		}

		/** Render màn hình Subcategory. */
		public function markup_subcategory_page() {
			$this->render_management_page( 'subcategories' );
		}

		/** Render màn hình gallery image. */
		public function markup_image_page() {
			$this->render_management_page( 'images' );
		}

		/** Render màn hình video theo danh mục. */
		public function markup_video_page() {
			$this->render_management_page( 'videos' );
		}

		private function authorize(): void {
			if ( ! current_user_can( $this->required_capability_for_context( '' ) ) ) {
				wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'jw-eng-customer-images' ), '', array( 'response' => 403 ) );
			}
		}

		public function handle_post(): void {
			$this->authorize();
			if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
				wp_die( esc_html__( 'Yêu cầu phải dùng POST.', 'jw-eng-customer-images' ), '', array( 'response' => 405 ) );
			}
			$input     = wp_unslash( $_POST );
			$entity    = isset( $input['entity'] ) && is_string( $input['entity'] ) ? sanitize_key( $input['entity'] ) : '';
			$id        = Repository::integer( $input['id'] ?? null );

			$operation = $input['operation'] ?? null;
			if ( ! is_string( $operation ) || ! in_array( $operation, array( 'save', 'delete', 'bulk_delete' ), true ) || ! is_string( $entity ) || ! isset( self::SLUGS[ $entity ] ) || null === $id || ( 'delete' === $operation && 0 === $id ) || ( 'bulk_delete' === $operation && ( 'image' !== $entity || 0 !== $id ) ) ) {
				wp_die( esc_html__( 'Yêu cầu không hợp lệ.', 'jw-eng-customer-images' ), '', array( 'response' => 400 ) );
			}
			$nonce = $input['_wpnonce'] ?? null;
			if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, self::NONCE_ACTION . '_' . $operation . '_' . $entity . '_' . $id ) ) {
				wp_die( esc_html__( 'Phiên xác thực hết hạn. Vui lòng tải lại trang.', 'jw-eng-customer-images' ), '', array( 'response' => 403 ) );
			}
			$result = $this->model->mutate( $operation, $entity, $input, $id );
			$failed = is_wp_error( $result );
			$token  = str_replace( '-', '', wp_generate_uuid4() );
			// Chỉ giữ trường form, không lưu nonce hoặc dữ liệu ngoài hợp đồng.
			$old = null;
			if ( $failed && 'save' === $operation ) {
				$old = array();
				foreach ( array_merge( Repository::MEDIA_FIELDS, array( 'name', 'position', 'description', 'category_id', 'subcategory_id', 'image_id' ) ) as $field ) {
					$old[ $field ] = is_string( $input[ $field ] ?? null ) ? sanitize_textarea_field( $input[ $field ] ) : '';
				}
				$old['image_ids'] = array();
				foreach ( is_array( $input['image_ids'] ?? null ) ? $input['image_ids'] : array() as $value ) {
					$valid = Repository::integer( $value );
					if ( $valid ) {
						$old['image_ids'][] = $valid;
					}
				}
			}
			$message = $failed ? $result->get_error_message() : __( 'Đã lưu thay đổi. File trong Media Library được giữ nguyên.', 'jw-eng-customer-images' );
			if ( ! $failed && 'bulk_delete' === $operation ) {
				/* translators: %d: Số bản ghi ảnh đã xóa. */
				$message = sprintf( __( 'Đã xóa %d bản ghi ảnh. File trong Media Library được giữ nguyên.', 'jw-eng-customer-images' ), $result );
			} elseif ( ! $failed && 'save' === $operation && 'image' === $entity && 0 === $id ) {
				/* translators: %d: Số ảnh được thêm trong một lần lưu. */
				$message = sprintf( __( 'Đã thêm %d ảnh khách hàng.', 'jw-eng-customer-images' ), count( $result ) );
			}
			set_transient( 'jw_eci_' . get_current_user_id() . '_' . $token, array(
				'entity' => $entity, 'input' => $old,
				'notice' => array( 'type' => $failed ? 'error' : 'success', 'message' => $message ),
			), 5 * MINUTE_IN_SECONDS );
			$url = add_query_arg( array( 'page' => self::SLUGS[ $entity ], 'result' => $token ), admin_url( 'admin.php' ) );
			if ( $failed && 0 < $id && 'save' === $operation ) {
				$url = add_query_arg( 'edit', $id, $url );
			}
			wp_safe_redirect( $url );
			exit;
		}

		/**
		 * Registers settings sections and fields
		 *
		 * @since    1.0.0
		 */
		public function register_fields() {

			// Add Settings Page Section.
			add_settings_section(
				'jw_eng_customer_images_section',                    // Section ID.
				__( 'Settings', JW_Eng_Customer_Images::PLUGIN_ID ), // Section Title.
				array( $this, 'markup_section_headers' ), // Section Callback.
				static::SETTINGS_PAGE_SLUG                 // Page URL.
			);

			// Add Settings Page Field.
			add_settings_field(
				'jw_eng_customer_images_field',                                // Field ID.
				__( 'JW Eng Customer Images Field:', JW_Eng_Customer_Images::PLUGIN_ID ), // Field Title.
				array( $this, 'markup_fields' ),                    // Field Callback.
				static::SETTINGS_PAGE_SLUG,                          // Page.
				'jw_eng_customer_images_section',                              // Section ID.
				array(                                              // Field args.
					'id'        => 'jw_eng_customer_images_field',
					'label_for' => 'jw_eng_customer_images_field',
				)
			);
		}

		/**
		 * Adds the section introduction text to the Settings page
		 *
		 * @param array $section Array containing information Section Id, Section
		 *                       Title & Section Callback.
		 *
		 * @since    1.0.0
		 */
		public function markup_section_headers( $section ) {
			$this->view->section_headers(
				array(
					'section'      => $section,
					'text_example' => __( 'This is a text example for section header', JW_Eng_Customer_Images::PLUGIN_ID ),
				)
			);
		}

		/**
		 * Delivers the markup for settings fields
		 *
		 * @param array $field_args Field arguments passed in `add_settings_field`
		 *                          function.
		 *
		 * @since    1.0.0
		 */
		public function markup_fields( $field_args ) {
			$field_id = $field_args['id'];
			$settings_value = $this->get_model()->get_setting( $field_id );
			$this->view->markup_fields(
				array(
					'field_id'       => esc_attr( $field_id ),
					'settings_name'  => $this->get_model()->get_plugin_settings_option_key(),
					'settings_value' => ! empty( $settings_value ) ? esc_attr( $settings_value ) : '',
				)
			);
		}

		/**
		 * Adds links to the plugin's action link section on the Plugins page
		 *
		 * @param array $links The links currently mapped to the plugin.
		 * @return array
		 *
		 * @since    1.0.0
		 */
		public function add_plugin_action_links( $links ) {
			$url = add_query_arg( 'page', self::SETTINGS_PAGE_SLUG, admin_url( 'admin.php' ) );
			$settings_link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Quản lý', JW_Eng_Customer_Images::PLUGIN_ID ) . '</a>';
			array_unshift( $links, $settings_link );

			return $links;
		}

		/** Map entity sang page slug cố định. */
		private function page_slug_for_entity( string $entity ): string {
			// if ( 'video' === $entity ) {
			// 	return self::VIDEOS_PAGE_SLUG;
			// }
			if ( 'subcategory' === $entity ) {
				return self::SUBCATEGORIES_PAGE_SLUG;
			}
			if ( 'image' === $entity ) {
				return self::IMAGES_PAGE_SLUG;
			}
			return self::SETTINGS_PAGE_SLUG;
		}

	}

}
