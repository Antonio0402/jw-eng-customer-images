<?php
namespace JW_Eng_Customer_Images\App;

defined( 'ABSPATH' ) || exit;

/** Nguồn đọc/ghi dùng chung cho quản trị và PHP API. */
class Repository {
	const MEDIA_FIELDS = array( 'promotion_banner_id', 'registration_banner_id', 'icon_id', 'icon_hover_id' );

	/** Chỉ chấp nhận số nguyên không âm, không ép giá trị âm thành dương. */
	public static function integer( $value, int $maximum = PHP_INT_MAX ): ?int {
		if ( ! is_int( $value ) && ! is_string( $value ) ) {
			return null;
		}
		if ( ! preg_match( '/^(0|[1-9][0-9]*)$/D', (string) $value ) ) {
			return null;
		}
		$result = filter_var( $value, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 0, 'max_range' => $maximum ) ) );
		return false === $result ? null : $result;
	}

	public function get( string $entity, int $id ) {
		$tables = Database::tables();
		if ( ! isset( $tables[ $entity ] ) || 1 > $id ) {
			return null;
		}
		global $wpdb;
		$rows = $this->read( $wpdb->prepare( "SELECT * FROM `{$tables[$entity]}` WHERE id = %d", $id ) );
		return is_wp_error( $rows ) ? $rows : ( $rows[0] ?? null );
	}

	/** Lọc và sắp xếp tại SQL; null per_page chỉ dùng cho cây/dropdown danh mục. */
	public function listing( string $entity, int $category = 0, int $subcategory = 0, int $page = 1, ?int $per_page = 20 ) {
		global $wpdb;
		$t = Database::tables();
		if ( ! isset( $t[ $entity ] ) ) {
			return new \WP_Error( 'invalid_entity', __( 'Loại dữ liệu không hợp lệ.', 'jw-eng-customer-images' ) );
		}
		$from  = "`{$t[$entity]}` r";
		$where = '1=1';
		$order = 'r.position ASC, r.id ASC';
		if ( 'subcategory' === $entity ) {
			$from .= " INNER JOIN `{$t['category']}` c ON c.id = r.category_id";
			$order = 'c.position ASC, c.id ASC, r.position ASC, r.id ASC';
			if ( 0 < $category ) {
				$where .= $wpdb->prepare( ' AND r.category_id = %d', $category );
			}
		} elseif ( 'image' === $entity ) {
			$from .= " INNER JOIN `{$t['subcategory']}` s ON s.id = r.subcategory_id";
			$order = 'r.id ASC';
			if ( 0 < $category ) {
				$where .= $wpdb->prepare( ' AND s.category_id = %d', $category );
			}
			if ( 0 < $subcategory ) {
				$where .= $wpdb->prepare( ' AND r.subcategory_id = %d', $subcategory );
			}
		}
		$page  = max( 1, $page );
		$limit = '';
		if ( null !== $per_page ) {
			$per_page = max( 1, min( 100, $per_page ) );
			$page     = min( $page, intdiv( PHP_INT_MAX, $per_page ) );
			$limit    = $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, ( $page - 1 ) * $per_page );
		}
		$items = $this->read( "SELECT r.* FROM $from WHERE $where ORDER BY $order$limit" );
		if ( is_wp_error( $items ) ) {
			return $items;
		}
		if ( null === $per_page ) {
			return $items;
		}
		$count = $this->read( "SELECT COUNT(*) AS total FROM $from WHERE $where" );
		return is_wp_error( $count ) ? $count : array( 'items' => $items, 'total' => (int) $count[0]['total'], 'page' => $page, 'per_page' => $per_page );
	}

	private function read( string $sql ) {
		global $wpdb;
		$previous = $wpdb->suppress_errors( true );
		try {
			$rows = $wpdb->get_results( $sql, ARRAY_A );
			if ( $wpdb->last_error ) {
				return Database::error();
			}
			// Nạp attachment và metadata theo lô, tránh query ảnh trong vòng lặp render/API.
			$ids = array();
			foreach ( $rows as $row ) {
				foreach ( array_merge( self::MEDIA_FIELDS, array( 'image_id' ) ) as $field ) {
					if ( ! empty( $row[ $field ] ) ) {
						$ids[] = (int) $row[ $field ];
					}
				}
			}
			if ( $ids ) {
				_prime_post_caches( array_unique( $ids ), false, true );
			}
			return $rows;
		} finally {
			$wpdb->suppress_errors( $previous );
		}
	}

	/** Chuẩn hóa dữ liệu sau wp_unslash tại controller. */
	public function validate( string $entity, array $input, int $id = 0 ) {
		$data = array();
		if ( ! isset( Database::tables()[ $entity ] ) ) {
			return $this->invalid();
		}
		if ( 'image' !== $entity ) {
			if ( ! isset( $input['name'] ) || ! is_string( $input['name'] ) ) {
				return $this->invalid();
			}
			$data['name']     = trim( sanitize_text_field( $input['name'] ) );
			$data['position'] = self::integer( $input['position'] ?? null, 4294967295 );
			if ( '' === $data['name'] || 255 < mb_strlen( $data['name'] ) || null === $data['position'] ) {
				return $this->invalid();
			}
		}
		if ( 'category' === $entity ) {
			foreach ( self::MEDIA_FIELDS as $field ) {
				$value = $input[ $field ] ?? '0';
				$data[ $field ] = self::integer( '' === $value ? '0' : $value );
				if ( null === $data[ $field ] || ( 0 < $data[ $field ] && ! $this->valid_image( $data[ $field ] ) ) ) {
					return $this->invalid();
				}
			}
		} else {
			$parent = 'subcategory' === $entity ? 'category_id' : 'subcategory_id';
			$data[ $parent ] = self::integer( $input[ $parent ] ?? null );
			if ( ! $data[ $parent ] ) {
				return $this->invalid();
			}
			if ( 'subcategory' === $entity ) {
				if ( ! isset( $input['description'] ) || ! is_string( $input['description'] ) ) {
					return $this->invalid();
				}
				$data['description'] = trim( sanitize_textarea_field( $input['description'] ) );
				if ( '' === $data['description'] || 65535 < strlen( $data['description'] ) ) {
					return $this->invalid();
				}
			}
			$ids = 'image' === $entity && 0 === $id ? ( $input['image_ids'] ?? null ) : array( $input['image_id'] ?? null );
			if ( ! is_array( $ids ) || ! $ids ) {
				return $this->invalid();
			}
			$validated = array();
			foreach ( $ids as $value ) {
				$image_id = self::integer( $value );
				if ( ! $image_id || ! $this->valid_image( $image_id ) ) {
					return $this->invalid();
				}
				$validated[] = $image_id;
			}
			$data['image_id'] = reset( $validated );
			if ( 'image' === $entity && 0 === $id ) {
				$data['image_ids'] = array_values( array_unique( $validated ) );
			}
		}
		return $data;
	}

	private function valid_image( int $id ): bool {
		return 'attachment' === get_post_type( $id ) && 'trash' !== get_post_status( $id ) && wp_attachment_is_image( $id );
	}

	private function invalid(): \WP_Error {
		return new \WP_Error( 'invalid_input', __( 'Kiểm tra tên (tối đa 255 ký tự), vị trí nguyên không âm, danh mục cha, mô tả và ảnh từ Media Library.', 'jw-eng-customer-images' ) );
	}

	/** Mọi ghi đều đi qua transaction; khóa cha bảo vệ quan hệ trước khi ghi con. */
	public function save( string $entity, array $input, int $id = 0 ) {
		if ( 0 > $id ) {
			return $this->invalid();
		}
		$data = $this->validate( $entity, $input, $id );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		return $this->transaction( function () use ( $entity, $data, $id ) {
			global $wpdb;
			$t = Database::tables();
			if ( 'category' !== $entity ) {
				$parent = 'subcategory' === $entity ? 'category' : 'subcategory';
				$locked = $this->lock( $parent, $data[ $parent . '_id' ] );
				if ( is_wp_error( $locked ) ) {
					return $locked;
				}
			}
			if ( 0 < $id ) {
				$locked = $this->lock( $entity, $id );
				if ( is_wp_error( $locked ) ) {
					return $locked;
				}
			}
			$ids = $data['image_ids'] ?? array( $data['image_id'] ?? 0 );
			unset( $data['image_ids'] );
			$data['updated_at'] = current_time( 'mysql', true );
			if ( 0 < $id ) {
				return false === $wpdb->update( $t[ $entity ], $data, array( 'id' => $id ) ) ? Database::error() : $id;
			}
			$data['created_at'] = $data['updated_at'];
			$inserted = array();
			foreach ( $ids as $image_id ) {
				if ( 'image' === $entity ) {
					$data['image_id'] = $image_id;
				}
				if ( false === $wpdb->insert( $t[ $entity ], $data ) ) {
					return Database::error();
				}
				$inserted[] = (int) $wpdb->insert_id;
			}
			return $inserted;
		} );
	}

	public function delete( string $entity, int $id ) {
		if ( ! isset( Database::tables()[ $entity ] ) || 1 > $id ) {
			return $this->invalid();
		}
		return $this->transaction( function () use ( $entity, $id ) {
			global $wpdb;
			$t = Database::tables();
			$locked = $this->lock( $entity, $id );
			if ( is_wp_error( $locked ) ) {
				return $locked;
			}
			if ( 'image' !== $entity ) {
				$child = 'category' === $entity ? 'subcategory' : 'image';
				$rows  = $this->read( $wpdb->prepare( "SELECT id FROM `{$t[$child]}` WHERE {$entity}_id = %d LIMIT 1 FOR UPDATE", $id ) );
				if ( is_wp_error( $rows ) ) {
					return $rows;
				}
				if ( $rows ) {
					return new \WP_Error( 'has_children', __( 'Không thể xóa danh mục còn dữ liệu con. Hãy chuyển hoặc xóa dữ liệu con trước.', 'jw-eng-customer-images' ) );
				}
			}
			return false === $wpdb->delete( $t[ $entity ], array( 'id' => $id ), array( '%d' ) ) ? Database::error() : true;
		} );
	}

	private function lock( string $entity, int $id ) {
		global $wpdb;
		$t    = Database::tables();
		$rows = $this->read( $wpdb->prepare( "SELECT id FROM `{$t[$entity]}` WHERE id = %d FOR UPDATE", $id ) );
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}
		return $rows ? true : new \WP_Error( 'not_found', __( 'Bản ghi hoặc danh mục cha không còn tồn tại.', 'jw-eng-customer-images' ) );
	}

	private function transaction( callable $operation ) {
		global $wpdb;
		if ( Database::VERSION !== get_option( Database::OPTION ) ) {
			return new \WP_Error( 'schema_missing', __( 'Schema chưa sẵn sàng. Vui lòng kích hoạt lại plugin.', 'jw-eng-customer-images' ) );
		}
		$previous = $wpdb->suppress_errors( true );
		try {
			if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
				return Database::error();
			}
			$result = $operation();
			if ( is_wp_error( $result ) ) {
				$wpdb->query( 'ROLLBACK' );
				return $result;
			}
			if ( false === $wpdb->query( 'COMMIT' ) ) {
				$wpdb->query( 'ROLLBACK' );
				return Database::error();
			}
			return $result;
		} catch ( \Throwable $error ) {
			$wpdb->query( 'ROLLBACK' );
			return Database::error();
		} finally {
			$wpdb->suppress_errors( $previous );
		}
	}

	public static function media( int $id ): array {
		return array( 'image_id' => $id, 'image_url' => $id && 'trash' !== get_post_status( $id ) ? ( wp_get_attachment_image_url( $id, 'full' ) ?: '' ) : '' );
	}

	public static function present( string $entity, array $row ): array {
		$result = array( 'id' => (int) $row['id'] );
		if ( 'image' !== $entity ) {
			$result['name']     = $row['name'];
			$result['position'] = (int) $row['position'];
		}
		if ( 'category' === $entity ) {
			foreach ( self::MEDIA_FIELDS as $field ) {
				$result[ substr( $field, 0, -3 ) ] = self::media( (int) $row[ $field ] );
			}
		} else {
			$parent = 'subcategory' === $entity ? 'category_id' : 'subcategory_id';
			$result[ $parent ] = (int) $row[ $parent ];
			if ( 'subcategory' === $entity ) {
				$result['description'] = $row['description'];
			}
			$result = array_merge( $result, self::media( (int) $row['image_id'] ) );
		}
		return $result;
	}
}
