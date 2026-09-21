# JW Eng Customer Images

- Phạm vi: plugin độc lập, không sửa theme `jw-eng`, taxonomy hoặc bảng ảnh legacy.
- Giữ các method/function của boilerplate. Chỉ bổ sung hoặc sửa thân hàm; không thay toàn bộ MVC bằng một admin class mới.
- Luồng quản trị: `routes.php` → Controller `Admin_Settings` → Model `Admin_Settings` → Repository → View → `app/templates/admin/customer-images.php`.
- Form POST dùng action `jw_eng_ci_admin_action`, `operation=save|delete|bulk_delete`, entity `category|subcategory|image`; quyền `manage_options`, nonce theo thao tác/entity/ID.
- `bulk_delete` chỉ cho entity `image`, ID form bằng 0; `image_record_ids[]` chứa ID bản ghi (tối đa 100), khác `image_ids[]` chứa attachment ID khi thêm nhiều ảnh. Khóa/kiểm tra toàn bộ lô trước khi xóa; lỗi thì rollback, không xóa attachment.
- Schema do `app/class-database.php` sở hữu: ba bảng prefix `jw_eng_customer_*`, option `jw_eng_customer_images_db_version`. Không tự tạo dữ liệu mẫu.
- Attachment ID là nguồn ảnh. Category có bốn ảnh tùy chọn; Subcategory và Image bắt buộc ảnh. Category/Subcategory xếp `position ASC, id ASC` trong từng cha.
- API nằm ở `includes/api.php`, được nạp qua `JW_Eng_Customer_Images::get_api()`. Giữ cả `get_all()` và alias tương thích `get_tree()`.
- Asset quản trị chạy trực tiếp; JS render preview qua PHP `wp.template`, không tự ghép HTML. Chưa có frontend renderer.
- `tests/integration.php` chỉ chạy CLI với WordPress core và MySQL tạm port 13377; không đọc wp-config.php. Runtime phải ở Temp; database thử có tên ngẫu nhiên và tự xóa sau test.
- Hợp đồng API, schema và cách kiểm thử: `docs/customer-images.md`. Lint PHP, `node --check` và kiểm tra diff trước bàn giao; kiểm tra UI trình duyệt được báo riêng.
- Không stage/commit hoặc kích hoạt plugin trên database website nếu chưa được yêu cầu. Giữ nguyên file mẫu người dùng; chúng không thuộc route runtime.
