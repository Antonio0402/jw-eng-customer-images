<?php
/** API chỉ đọc, không phụ thuộc theme hoặc route quản trị. */
defined( 'ABSPATH' ) || exit;

use JW_Eng_Customer_Images\App\Repository;

function jw_eng_customer_images_get_all(): array {
	return jw_eng_customer_images_get_tree();
}

/**
 * Lấy cây Category và Subcategory, không tải images.
 *
 * @return array<int, array<string, mixed>>
 */
function jw_eng_customer_images_get_tree(): array {
	$repository    = new Repository();
	$categories    = $repository->listing( 'category', 0, 0, 1, null );
	$subcategories = $repository->listing( 'subcategory', 0, 0, 1, null );
	if ( is_wp_error( $categories ) || is_wp_error( $subcategories ) ) {
		return array();
	}
	$grouped = array();
	foreach ( $subcategories as $subcategory ) {
		$grouped[ (int) $subcategory['category_id'] ][] = Repository::present( 'subcategory', $subcategory );
	}
	$result = array();
	foreach ( $categories as $row ) {
		$category = Repository::present( 'category', $row );
		$category['subcategories'] = $grouped[ $category['id'] ] ?? array();
		$result[] = $category;
	}
	return $result;
}

/**
 * Lấy một Category và Subcategory theo ID.
 *
 * @param int $category_id ID Category.
 * @return array<string, mixed>|null
 */
function jw_eng_customer_images_get( int $category_id ): ?array {
	$repository = new Repository();
	$row        = $repository->get( 'category', $category_id );
	if ( ! $row || is_wp_error( $row ) ) {
		return null;
	}
	$children = $repository->listing( 'subcategory', $category_id, 0, 1, null );
	if ( is_wp_error( $children ) ) {
		return null;
	}
	$result = Repository::present( 'category', $row );
	$result['subcategories'] = array_map( 
		static function ( $child ) { 
			return Repository::present( 'subcategory', $child ); 
		}, 
		$children 
	);
	return $result;
}

/**
 * Lấy gallery image theo Subcategory, có phân trang.
 *
 * @param int $subcategory_id ID Subcategory.
 * @param int $page           Trang hiện tại.
 * @param int $per_page       Số ảnh mỗi trang.
 * @return array{items: array<int, array<string, mixed>>, total: int, page: int, per_page: int}
 */
function jw_eng_customer_images_get_images( int $subcategory_id, int $page = 1, int $per_page = 20 ): array {
	$page     = max( 1, $page );
	$per_page = max( 1, min( 100, $per_page ) );
	$page     = min( $page, intdiv( PHP_INT_MAX, $per_page ) );
	$empty    = array( 'items' => array(), 'total' => 0, 'page' => $page, 'per_page' => $per_page );
	if ( 1 > $subcategory_id ) {
		return $empty;
	}
	$result = ( new Repository() )->listing( 'image', 0, $subcategory_id, $page, $per_page );
	if ( is_wp_error( $result ) ) {
		return $empty;
	}
	$result['items'] = array_map( static function ( $row ) { return Repository::present( 'image', $row ); }, $result['items'] );
	return $result;
}

/**
 * Lấy một Image theo ID.
 *
 * @param int $category_id ID Category.
 * @return array<string, mixed>|null
 */
function jw_eng_customer_images_get_image( int $image_id ): ?array {
	$row = ( new Repository() )->get( 'image', $image_id );
	return ! $row || is_wp_error( $row ) ? null : Repository::present( 'image', $row );
}
