<?php
namespace JW_Eng_Customer_Images\App\Controllers\Admin;

use JW_Eng_Customer_Images\App\Database;
use JW_Eng_Customer_Images\App\Repository;

defined( 'ABSPATH' ) || exit;

/** Điều phối ba màn hình và các POST có xác thực. */
class Admin_Settings extends Base_Controller {
	const SLUGS = array(
		'category' => 'jw-eng-customer-images',
		'subcategory' => 'jw-eng-customer-images-subcategories',
		'image' => 'jw-eng-customer-images-images',
	);
	private array $hooks = array();

	public function register_hook_callbacks() {
		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_jw_eng_customer_images_save', array( $this, 'handle_post' ) );
		add_action( 'admin_post_jw_eng_customer_images_delete', array( $this, 'handle_post' ) );
	}

	public function plugin_menu(): void {
		$this->hooks[] = add_menu_page( 
			'JW Service Categories', 
			'JW Service Categories', 
			'manage_options', 
			self::SLUGS['category'], 
			array( $this, 'render' ), 'dashicons-format-gallery' 
		);
		foreach ( $this->labels() as $entity => $label ) {
			$this->hooks[] = add_submenu_page( 
				self::SLUGS['category'], 
				$label,
				$label, 
				'manage_options', 
				self::SLUGS[ $entity ], 
				array( $this, 'render' ) 
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
		if ( ! in_array( $hook, $this->hooks, true ) ) {
			return;
		}
		wp_enqueue_media();
		$base = JW_Eng_Customer_Images::get_plugin_url();
		$path = JW_Eng_Customer_Images::get_plugin_path();
		wp_enqueue_script( 
			'jw-eng-customer-images-admin', 
			$base . 'assets/js/admin/jw-eng-customer-images.js', array( 'media-views', 'wp-util' ), 
			JW_Eng_Customer_Images::PLUGIN_VERSION, 
			true 
		);
		wp_localize_script( 
			'jw-eng-customer-images-admin', 'jwEngCustomerImages', 
			array(
				'title' => __( 'Chọn hình ảnh', 'jw-eng-customer-images' ),
				'useImage' => __( 'Sử dụng ảnh đã chọn', 'jw-eng-customer-images' ),
			) 
		);
		wp_enqueue_style( 
			'jw-eng-customer-images-admin', 
			$base . 'assets/css/admin/jw-eng-customer-images.css', 
			array(), 
			JW_Eng_Customer_Images::PLUGIN_VERSION,
		);
	}

	public function render(): void {
		$this->authorize();
		$query  = wp_unslash( $_GET );
		$entity = array_search( 
			is_string( $query['page'] ?? null ) ? 
			$query['page'] : '', 
			self::SLUGS, true 
		);
		if ( false === $entity ) {
			wp_die( esc_html__( 'Màn hình không hợp lệ.', 'jw-eng-customer-images' ) );
		}
		$category    = Repository::integer( $query['category_id'] ?? '0' ) ?? 0;
		$subcategory = Repository::integer( $query['subcategory_id'] ?? '0' ) ?? 0;
		$page        = max( 1, Repository::integer( $query['paged'] ?? '1' ) ?? 1 );
		$id          = Repository::integer( $query['edit'] ?? '0' ) ?? 0;
		$notice      = null;
		$old         = null;
		$token       = $query['result'] ?? '';
		if ( is_string( $token ) && preg_match( '/^[a-f0-9]{32}$/D', $token ) ) {
			$key   = 'jw_eci_' . get_current_user_id() . '_' . $token;
			$flash = get_transient( $key );
			if ( is_array( $flash ) && $entity === $flash['entity'] ) {
				$notice = $flash['notice'];
				$old    = $flash['input'];
				delete_transient( $key );
			}
		}
		$ready = Database::VERSION === get_option( Database::OPTION );
		$state = $ready ? $this->model->screen( $entity, $category, $subcategory, $page, $id ) : array();
		if ( is_wp_error( $state ) ) {
			$notice = array( 'type' => 'error', 'message' => $state->get_error_message() );
			$ready  = false;
			$state  = array();
		}
		$this->view->admin_settings_page( array_merge( $state, array(
			'entity' => $entity, 'labels' => $this->labels(), 'slugs' => self::SLUGS,
			'category_filter' => $category, 'subcategory_filter' => $subcategory,
			'edit_id' => $id, 'notice' => $notice, 'old' => $old, 'ready' => $ready,
		) ) );
	}

	private function authorize(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'jw-eng-customer-images' ), '', array( 'response' => 403 ) );
		}
	}


	public function handle_post(): void {
		$this->authorize();
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			wp_die( esc_html__( 'Yêu cầu phải dùng POST.', 'jw-eng-customer-images' ), '', array( 'response' => 405 ) );
		}
		$input     = wp_unslash( $_POST );
		$entity    = $input['entity'] ?? null;
		$id        = Repository::integer( $input['id'] ?? null );
		$operation = 'admin_post_jw_eng_customer_images_delete' === current_action() ? 'delete' : 'save';
		if ( ! is_string( $entity ) || ! isset( self::SLUGS[ $entity ] ) || null === $id || ( 'delete' === $operation && 0 === $id ) ) {
			wp_die( esc_html__( 'Yêu cầu không hợp lệ.', 'jw-eng-customer-images' ), '', array( 'response' => 400 ) );
		}
		$nonce = $input['_wpnonce'] ?? null;
		if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'jw_eci_' . $operation . '_' . $entity . '_' . $id ) ) {
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
		set_transient( 'jw_eci_' . get_current_user_id() . '_' . $token, array(
			'entity' => $entity, 'input' => $old,
			'notice' => array( 'type' => $failed ? 'error' : 'success', 'message' => $failed ? $result->get_error_message() : __( 'Đã lưu thay đổi. File trong Media Library được giữ nguyên.', 'jw-eng-customer-images' ) ),
		), 5 * MINUTE_IN_SECONDS );
		$url = add_query_arg( array( 'page' => self::SLUGS[ $entity ], 'result' => $token ), admin_url( 'admin.php' ) );
		if ( $failed && 0 < $id && 'save' === $operation ) {
			$url = add_query_arg( 'edit', $id, $url );
		}
		wp_safe_redirect( $url );
		exit;
	}
}
