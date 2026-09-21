<?php
/** Giao diện quản trị; mọi dữ liệu đều escape tại điểm xuất. */
defined( 'ABSPATH' ) || exit;

use JW_Eng_Customer_Images\App\Repository;

$media_labels = array(
	'promotion_banner_id' => __( 'Banner khuyến mãi theo tháng', 'jw-eng-customer-images' ),
	'registration_banner_id' => __( 'Banner đăng ký', 'jw-eng-customer-images' ),
	'icon_id' => __( 'Icon', 'jw-eng-customer-images' ),
	'icon_hover_id' => __( 'Icon hover', 'jw-eng-customer-images' ),
);
$nonce_fields = static function ( string $operation, string $entity, int $id ) use ( $action, $nonce_action ): void {
	?>
	<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
	<input type="hidden" name="operation" value="<?php echo esc_attr( $operation ); ?>">
	<input type="hidden" name="entity" value="<?php echo esc_attr( $entity ); ?>">
	<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
	<?php wp_nonce_field( $nonce_action . '_' . $operation . '_' . $entity . '_' . $id ); ?>
	<?php
};
$preview = static function ( int $id, string $class = 'jw-eng-ci-thumb' ): void {
	$media = Repository::media( $id );
	if ( $media['image_url'] ) {
		?><img class="<?php echo esc_attr( $class ); ?>" src="<?php echo esc_url( $media['image_url'] ); ?>" alt="" loading="lazy" width="100" height="70"><?php
	} else {
		echo esc_html( $id ? __( 'Ảnh không còn tồn tại', 'jw-eng-customer-images' ) : '—' );
	}
};
$image_field = static function ( string $field, string $label, int $value, bool $required = false ): void {
	$id    = 'jw-eng-ci-' . $field;
	$media = Repository::media( $value );
	?>
	<tr><th scope="row"><label for="<?php echo esc_attr( $id . '-choose' ); ?>"><?php echo esc_html( $label ); ?><?php echo $required ? ' *' : ''; ?></label></th><td>
		<img class="jw-eng-ci-preview" data-preview-for="<?php echo esc_attr( $id ); ?>" <?php if ( $media['image_url'] ) : ?>src="<?php echo esc_url( $media['image_url'] ); ?>"<?php endif; ?> alt="">
		<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( $value ); ?>">
		<button id="<?php echo esc_attr( $id . '-choose' ); ?>" type="button" class="button jw-eng-ci-media" data-target="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Chọn ảnh', 'jw-eng-customer-images' ); ?></button>
		<button type="button" class="button jw-eng-ci-media-clear" data-target="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Bỏ chọn', 'jw-eng-customer-images' ); ?></button>
		<?php if ( $value && ! $media['image_url'] ) : ?><p class="description"><?php esc_html_e( 'Ảnh không còn tồn tại. Vui lòng chọn lại.', 'jw-eng-customer-images' ); ?></p><?php endif; ?>
	</td></tr>
	<?php
};
?>
<div class="wrap jw-eng-ci">
	<h1><?php echo esc_html( 'JW Service Categories — ' . $labels[ $entity ] ); ?></h1>
	<?php if ( $notice ) : ?>
		<div role="status" class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! $ready ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Dữ liệu chưa sẵn sàng. Vui lòng kiểm tra cơ sở dữ liệu và kích hoạt lại plugin.', 'jw-eng-customer-images' ); ?></p></div>
	</div>
		<?php return; ?>
	<?php endif; ?>
	<?php
	$category_names = array_column( $categories, 'name', 'id' );
	$sub_names      = array_column( $subcategories, 'name', 'id' );
	$form           = is_array( $old ) ? $old : ( $edit ?? array() );
	$missing        = 0 < $edit_id && ! $edit;
	$category_options = static function ( int $selected_id ) use ( $categories ): void {
		foreach ( $categories as $row ) {
			?><option value="<?php echo esc_attr( $row['id'] ); ?>" <?php selected( $selected_id, $row['id'] ); ?>><?php echo esc_html( $row['name'] ); ?></option><?php
		}
	};
	$subcategory_options = static function ( int $selected_id, int $filter = 0 ) use ( $subcategories, $category_names ): void {
		foreach ( $subcategories as $row ) {
			?><option value="<?php echo esc_attr( $row['id'] ); ?>" data-category-id="<?php echo esc_attr( $row['category_id'] ); ?>" <?php selected( $selected_id, $row['id'] ); ?> <?php if ( $filter && $filter !== (int) $row['category_id'] ) : ?>hidden<?php endif; ?>><?php echo esc_html( ( $category_names[ $row['category_id'] ] ?? '' ) . ' — ' . $row['name'] ); ?></option><?php
		}
	};
	?>
	<?php if ( $missing ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Bản ghi cần sửa không còn tồn tại.', 'jw-eng-customer-images' ); ?></p></div>
	<?php else : ?>
		<h2><?php echo esc_html( $edit_id ? __( 'Sửa bản ghi', 'jw-eng-customer-images' ) : __( 'Thêm bản ghi', 'jw-eng-customer-images' ) ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php $nonce_fields( 'save', $entity, $edit_id ); ?>
			<table class="form-table"><tbody>
			<?php if ( 'subcategory' === $entity ) : ?>
				<tr><th><label for="jw-eng-ci-category"><?php esc_html_e( 'Category cha', 'jw-eng-customer-images' ); ?> *</label></th><td><select id="jw-eng-ci-category" name="category_id" required><option value=""><?php esc_html_e( 'Chọn Category', 'jw-eng-customer-images' ); ?></option><?php $category_options( (int) ( $form['category_id'] ?? $category_filter ) ); ?></select></td></tr>
			<?php elseif ( 'image' === $entity ) : ?>
				<tr><th><label for="jw-eng-ci-subcategory"><?php esc_html_e( 'Subcategory', 'jw-eng-customer-images' ); ?> *</label></th><td><select id="jw-eng-ci-subcategory" name="subcategory_id" required><option value=""><?php esc_html_e( 'Chọn Subcategory', 'jw-eng-customer-images' ); ?></option><?php $subcategory_options( (int) ( $form['subcategory_id'] ?? $subcategory_filter ) ); ?></select></td></tr>
			<?php endif; ?>
			<?php if ( 'image' !== $entity ) : ?>
				<tr><th><label for="jw-eng-ci-name"><?php esc_html_e( 'Tên', 'jw-eng-customer-images' ); ?> *</label></th><td><input class="regular-text" id="jw-eng-ci-name" name="name" maxlength="255" required value="<?php echo esc_attr( $form['name'] ?? '' ); ?>"></td></tr>
				<tr><th><label for="jw-eng-ci-position"><?php esc_html_e( 'Vị trí', 'jw-eng-customer-images' ); ?> *</label></th><td><input class="small-text" id="jw-eng-ci-position" name="position" type="number" min="0" max="4294967295" step="1" required value="<?php echo esc_attr( $form['position'] ?? 0 ); ?>"><p class="description"><?php esc_html_e( 'Số nhỏ hiển thị trước. Trùng vị trí thì ID nhỏ trước.', 'jw-eng-customer-images' ); ?></p></td></tr>
			<?php endif; ?>
			<?php if ( 'category' === $entity ) : ?>
				<?php foreach ( $media_labels as $field => $label ) { $image_field( $field, $label, (int) ( $form[ $field ] ?? 0 ) ); } ?>
			<?php elseif ( 'subcategory' === $entity ) : ?>
				<tr><th><label for="jw-eng-ci-description"><?php esc_html_e( 'Mô tả', 'jw-eng-customer-images' ); ?> *</label></th><td><textarea class="large-text" id="jw-eng-ci-description" name="description" rows="5" required><?php echo esc_textarea( $form['description'] ?? '' ); ?></textarea></td></tr>
				<?php $image_field( 'image_id', __( 'Ảnh đại diện', 'jw-eng-customer-images' ), (int) ( $form['image_id'] ?? 0 ), true ); ?>
			<?php elseif ( $edit_id ) : ?>
				<?php $image_field( 'image_id', __( 'Ảnh khách hàng', 'jw-eng-customer-images' ), (int) ( $form['image_id'] ?? 0 ), true ); ?>
			<?php else : ?>
				<tr><th><label for="jw-eng-ci-images-choose"><?php esc_html_e( 'Ảnh khách hàng', 'jw-eng-customer-images' ); ?> *</label></th><td>
					<button id="jw-eng-ci-images-choose" type="button" class="button jw-eng-ci-media-multiple" data-preview="jw-eng-ci-multi-preview"><?php esc_html_e( 'Chọn nhiều ảnh', 'jw-eng-customer-images' ); ?></button>
					<p class="description"><?php esc_html_e( 'Mỗi ảnh đã ghép trước–sau sẽ tạo một bản ghi riêng.', 'jw-eng-customer-images' ); ?></p>
					<div id="jw-eng-ci-multi-preview" class="jw-eng-ci-multi-preview" aria-live="polite">
					<?php foreach ( $form['image_ids'] ?? array() as $selected_id ) : ?>
						<div class="jw-eng-ci-selected-image"><input type="hidden" name="image_ids[]" value="<?php echo esc_attr( $selected_id ); ?>"><?php $preview( (int) $selected_id ); ?></div>
					<?php endforeach; ?>
					</div>
				</td></tr>
			<?php endif; ?>
			</tbody></table>
			<?php submit_button( $edit_id ? __( 'Cập nhật', 'jw-eng-customer-images' ) : __( 'Thêm mới', 'jw-eng-customer-images' ) ); ?>
			<?php if ( $edit_id ) : ?><a class="button" href="<?php echo esc_url( add_query_arg( 'page', $slugs[ $entity ], admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Hủy sửa', 'jw-eng-customer-images' ); ?></a><?php endif; ?>
		</form>
	<?php endif; ?>
	<h2><?php esc_html_e( 'Danh sách', 'jw-eng-customer-images' ); ?></h2>
	<?php if ( 'category' !== $entity ) : ?>
		<form method="get" class="jw-eng-ci-filter">
			<input type="hidden" name="page" value="<?php echo esc_attr( $slugs[ $entity ] ); ?>">
			<label for="jw-eng-ci-image-category-filter">Category</label>
			<select id="jw-eng-ci-image-category-filter" name="category_id"><option value="0"><?php esc_html_e( 'Tất cả', 'jw-eng-customer-images' ); ?></option><?php $category_options( $category_filter ); ?></select>
			<?php if ( 'image' === $entity ) : ?>
				<label for="jw-eng-ci-image-subcategory-filter">Subcategory</label>
				<select id="jw-eng-ci-image-subcategory-filter" name="subcategory_id"><option value="0"><?php esc_html_e( 'Tất cả', 'jw-eng-customer-images' ); ?></option><?php $subcategory_options( $subcategory_filter, $category_filter ); ?></select>
			<?php endif; ?>
			<?php submit_button( __( 'Lọc', 'jw-eng-customer-images' ), 'secondary', 'submit', false ); ?>
		</form>
	<?php endif; ?>
	<div class="jw-eng-ci-table-scroll"><table class="widefat striped jw-eng-ci-table"><thead><tr>
		<th scope="col">ID</th>
		<?php if ( 'category' !== $entity ) : ?><th scope="col"><?php esc_html_e( 'Danh mục cha', 'jw-eng-customer-images' ); ?></th><?php endif; ?>
		<?php if ( 'image' !== $entity ) : ?><th scope="col"><?php esc_html_e( 'Tên', 'jw-eng-customer-images' ); ?></th><th scope="col"><?php esc_html_e( 'Vị trí', 'jw-eng-customer-images' ); ?></th><?php endif; ?>
		<?php foreach ( 'category' === $entity ? $media_labels : array( 'image_id' => __( 'Ảnh', 'jw-eng-customer-images' ) ) as $label ) : ?><th scope="col"><?php echo esc_html( $label ); ?></th><?php endforeach; ?>
		<th scope="col"><?php esc_html_e( 'Thao tác', 'jw-eng-customer-images' ); ?></th>
	</tr></thead><tbody>
	<?php foreach ( $listing['items'] as $row ) : ?>
		<tr><td><?php echo esc_html( $row['id'] ); ?></td>
		<?php if ( 'category' !== $entity ) : ?><td><?php echo esc_html( 'subcategory' === $entity ? ( $category_names[ $row['category_id'] ] ?? '' ) : ( $sub_names[ $row['subcategory_id'] ] ?? '' ) ); ?></td><?php endif; ?>
		<?php if ( 'image' !== $entity ) : ?><td><?php echo esc_html( $row['name'] ); ?></td><td><?php echo esc_html( $row['position'] ); ?></td><?php endif; ?>
		<?php foreach ( 'category' === $entity ? array_keys( $media_labels ) : array( 'image_id' ) as $field ) : ?><td><?php $preview( (int) $row[ $field ] ); ?></td><?php endforeach; ?>
		<td><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => $slugs[ $entity ], 'edit' => $row['id'] ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Sửa', 'jw-eng-customer-images' ); ?></a>
		<form class="jw-eng-ci-inline" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php $nonce_fields( 'delete', $entity, (int) $row['id'] ); ?><button class="button button-link-delete" data-confirm="<?php esc_attr_e( 'Xóa bản ghi này? File trong Media Library vẫn được giữ lại.', 'jw-eng-customer-images' ); ?>"><?php esc_html_e( 'Xóa', 'jw-eng-customer-images' ); ?></button></form></td></tr>
	<?php endforeach; ?>
	<?php if ( ! $listing['items'] ) : ?><tr><td colspan="8"><?php esc_html_e( 'Chưa có dữ liệu.', 'jw-eng-customer-images' ); ?></td></tr><?php endif; ?>
	</tbody></table></div>
	<?php
	$pagination_base = add_query_arg( array( 'page' => $slugs[ $entity ], 'category_id' => $category_filter, 'subcategory_id' => $subcategory_filter, 'paged' => 999999999 ), admin_url( 'admin.php' ) );
	echo wp_kses_post( paginate_links( array(
		'base' => str_replace( '999999999', '%#%', $pagination_base ),
		'current' => $listing['page'], 'total' => (int) ceil( $listing['total'] / $listing['per_page'] ),
		'add_args' => false,
	) ) );
	?>
</div>
<script type="text/html" id="tmpl-jw-eng-ci-selected-image">
	<div class="jw-eng-ci-selected-image">
		<input type="hidden" name="image_ids[]" value="{{ data.id }}">
		<img src="{{ data.url }}" alt="">
		<small>{{ data.filename }}</small>
	</div>
</script>
