<?php
namespace JW_Eng_Customer_Images\App;

defined( 'ABSPATH' ) || exit;

/** Quản lý duy nhất ba bảng thuộc plugin. */
class Database {
	const VERSION = '1.0.0';
	const OPTION  = 'jw_eng_customer_images_db_version';

	public static function tables(): array {
		global $wpdb;
		return array(
			'category'    => $wpdb->prefix . 'jw_eng_customer_categories',
			'subcategory' => $wpdb->prefix . 'jw_eng_customer_subcategories',
			'image'       => $wpdb->prefix . 'jw_eng_customer_images',
		);
	}

	/** dbDelta không hỗ trợ transaction DDL; chỉ công nhận phiên bản sau xác minh. */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$tables  = self::tables();
		$charset = $wpdb->get_charset_collate();
		$schemas = array(
			'category' => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(255) NOT NULL,
				position int unsigned NOT NULL DEFAULT 0,
				promotion_banner_id bigint(20) unsigned NOT NULL DEFAULT 0,
				registration_banner_id bigint(20) unsigned NOT NULL DEFAULT 0,
				icon_id bigint(20) unsigned NOT NULL DEFAULT 0,
				icon_hover_id bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY display_order (position,id)",
			'subcategory' => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				category_id bigint(20) unsigned NOT NULL,
				name varchar(255) NOT NULL,
				description text NOT NULL,
				position int unsigned NOT NULL DEFAULT 0,
				image_id bigint(20) unsigned NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY category_order (category_id,position,id)",
			'image' => "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				subcategory_id bigint(20) unsigned NOT NULL,
				image_id bigint(20) unsigned NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY subcategory_order (subcategory_id,id)",
		);
		$previous = $wpdb->suppress_errors( true );
		try {
			foreach ( $schemas as $entity => $columns ) {
				dbDelta( "CREATE TABLE {$tables[$entity]} (\n$columns\n) ENGINE=InnoDB $charset;" );
				if ( $wpdb->last_error ) {
					return self::error();
				}
				$status = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name = %s', $tables[ $entity ] ) );
				if ( ! $status || 'innodb' !== strtolower( $status->Engine ) ) {
					return self::error();
				}
				$actual = $wpdb->get_col( "SHOW COLUMNS FROM `{$tables[$entity]}`" );
				preg_match_all( '/^\s*([a-z_]+) (?:bigint|int|varchar|text|datetime)/m', $columns, $matches );
				if ( array_diff( $matches[1], $actual ) ) {
					return self::error();
				}
			}
			update_option( self::OPTION, self::VERSION, false );
			return self::VERSION === get_option( self::OPTION ) ? true : self::error();
		} finally {
			$wpdb->suppress_errors( $previous );
		}
	}

	public static function error(): \WP_Error {
		// Không ghi SQL hoặc dữ liệu người dùng vào log.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'JW Eng Customer Images: database operation failed.' );
		}
		return new \WP_Error( 'database_error', __( 'Không thể xử lý dữ liệu. Vui lòng thử lại hoặc kiểm tra cơ sở dữ liệu.', 'jw-eng-customer-images' ) );
	}

	public static function uninstall(): void {
		global $wpdb;
		foreach ( array_reverse( self::tables() ) as $table ) {
			if ( false === $wpdb->query( "DROP TABLE IF EXISTS `$table`" ) ) {
				self::error();
				return;
			}
		}
		delete_option( self::OPTION );
	}
}
