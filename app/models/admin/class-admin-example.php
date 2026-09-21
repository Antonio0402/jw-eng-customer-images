<?php
namespace JW_Eng_Customer_Images\App\Models\Admin;

use JW_Eng_Customer_Images\App\Repository;

defined( 'ABSPATH' ) || exit;

/** Chuẩn bị dữ liệu màn hình; Repository giữ mọi quy tắc lưu trữ. */
class Admin_Settings extends Base_Model {
	public function mutate( string $operation, string $entity, array $input, int $id ) {
		$repository = new Repository();
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
}
