# JW Eng Customer Images

## Quản trị

Kích hoạt plugin trong WordPress 6.7+, PHP 8.1+, single-site để tạo ba bảng rỗng. Menu **JW Service Categories** yêu cầu `manage_options` trên cả ba màn hình:

- **Categories:** tên, vị trí, banner khuyến mãi theo tháng, banner đăng ký, icon và icon hover. Bốn ảnh không bắt buộc; banner tháng được thay thủ công.
- **Subcategories:** Category cha, tên, vị trí, mô tả văn bản thuần và ảnh đại diện; tất cả bắt buộc.
- **Images:** ảnh khách hàng đã ghép trước–sau, thuộc một Subcategory. Nút **Chọn/upload nhiều ảnh** mở Media Library để chọn hoặc upload nhiều file, rồi bấm **Thêm mới** lưu cùng lúc; mỗi ảnh tạo một bản ghi, attachment trùng trong một lần chọn được gộp. Sửa để thay ảnh/chuyển Subcategory; xóa từng bản ghi hoặc đánh dấu các dòng rồi bấm **Xóa ảnh đã chọn**.

Chọn tất cả chỉ áp dụng cho trang hiện tại. Xóa hàng loạt có xác nhận, nonce riêng và chỉ xóa bản ghi ảnh, không xóa attachment. Backend nhận tối đa 100 ID bản ghi mỗi lần; danh sách rỗng/sai hoặc có bản ghi đã mất sẽ báo lỗi và không xóa một phần. Thành công hiển thị số bản ghi đã thêm/xóa.

Vị trí là số nguyên từ 0 đến 4294967295, mặc định 0. Số nhỏ trước; trùng vị trí dùng ID tăng dần. Subcategory sắp trong từng Category. Danh sách phân trang 20 mục; ảnh khách hàng sắp ID tăng dần. Không tự đánh lại vị trí khi sửa/xóa/chuyển cha.

Media Library là nguồn ảnh duy nhất. Bỏ chọn hoặc xóa bản ghi không xóa file. Chặn xóa cha còn dữ liệu con. Attachment bị xóa bên ngoài sẽ có URL rỗng và thông báo thiếu ảnh trong admin.

## Schema và vòng đời

Tên bảng dùng `$wpdb->prefix`, InnoDB:

| Hậu tố | Trường |
| --- | --- |
| `jw_eng_customer_categories` | `id`, `name`, `position`, `promotion_banner_id`, `registration_banner_id`, `icon_id`, `icon_hover_id`, `created_at`, `updated_at` |
| `jw_eng_customer_subcategories` | `id`, `category_id`, `name`, `description`, `position`, `image_id`, `created_at`, `updated_at` |
| `jw_eng_customer_images` | `id`, `subcategory_id`, `image_id`, `created_at`, `updated_at` |

Thời gian UTC. Option schema: `jw_eng_customer_images_db_version`. Activation dùng `dbDelta()` và xác minh bảng/cột/InnoDB trước khi ghi phiên bản. Activation lặp giữ bản ghi. CRUD khóa cha trong transaction; thêm nhiều ảnh rollback toàn bộ khi lỗi.

Deactivate giữ dữ liệu. **Uninstall xóa vĩnh viễn ba bảng và option phiên bản của plugin**, giữ nguyên Media Library, taxonomy và bảng legacy. Sao lưu trước khi uninstall nếu cần khôi phục.

Chưa nhập dữ liệu cũ, chưa nối frontend, chưa thêm REST/shortcode. ID của plugin không phải taxonomy ID của theme. Giữ cấu trúc MVC và mọi method của boilerplate; các file sample/example hiện có không được đăng ký vào route.

## PHP API

API sẵn sàng sau khi plugin được nạp; consumer kiểm tra `function_exists()` nếu plugin có thể bị tắt.

```php
$categories = jw_eng_customer_images_get_all();
$category   = jw_eng_customer_images_get( $category_id );
$gallery    = jw_eng_customer_images_get_images( $subcategory_id, 1, 20 );
$image      = jw_eng_customer_images_get_image( $record_id );
```

- Category: `id`, `name`, `position`, `promotion_banner`, `registration_banner`, `icon`, `icon_hover`, `subcategories`. Mỗi trường ảnh là `['image_id' => int, 'image_url' => string]`.
- Subcategory: `id`, `category_id`, `name`, `description`, `position`, `image_id`, `image_url`.
- Image: `id`, `subcategory_id`, `image_id`, `image_url`.
- Gallery: `items`, `total`, `page`, `per_page`. Trang tối thiểu 1; số mục giới hạn 1–100. ID cha không hợp lệ trả danh sách rỗng.
- `get()`/`get_image()` trả `null` khi không tìm thấy. API cây trả mảng rỗng khi DB lỗi; gallery trả cấu trúc rỗng. Repository nội bộ trả `WP_Error`; debug log chỉ ghi lỗi chung, không SQL/dữ liệu người dùng.
- Cây không nhúng gallery. URL lấy qua WordPress, ảnh chưa chọn hoặc bị mất trả chuỗi rỗng. Consumer escape theo ngữ cảnh output.

## Kiểm thử

```powershell
php -l app/class-repository.php
node --check assets/js/admin/jw-eng-customer-images.js
git diff --check
```

Integration test cần một MySQL **riêng** trên `127.0.0.1:13377`, tài khoản root không mật khẩu chỉ trong daemon thử nghiệm, datadir nằm trong Temp. Không trỏ script vào MySQL của website. Test đọc WordPress core, tự bootstrap với cấu hình cô lập, không đọc `wp-config.php` và không tải theme/plugin khác.

```powershell
php tests/integration.php D:/laragon/www/jwhospital C:/Users/BENHVIENJW/AppData/Local/Temp/THU_MUC_TEST 13377
```

Script tạo database tên `jw_eci_test_<random>`, chạy kiểm tra rồi xóa database đó kể cả khi lỗi. Bao phủ schema lặp, CRUD, vị trí, validation, API, rollback bằng lỗi SQL chủ động, menu/render, quyền/nonce, POST, khóa giữa hai kết nối, giữ attachment và uninstall giới hạn đúng bảng. Sau test dừng daemon MySQL thử nghiệm.

Kiểm thử trình duyệt riêng: mở ba màn hình, chọn/thay/bỏ ảnh, mở lại lựa chọn nhiều ảnh, lọc/phân trang, xác nhận xóa và kiểm tra thông báo lỗi. Lint/integration không thay thế việc kiểm tra tương tác Media Library trong trình duyệt.
