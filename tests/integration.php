<?php
/** Kiểm chứng bằng WordPress thật trên MySQL tạm; tuyệt đối không đọc wp-config.php. */
if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}
$wp_root = realpath( $argv[1] ?? '' );
$runtime = realpath( $argv[2] ?? '' );
$port    = (int) ( $argv[3] ?? 0 );
if ( ! $wp_root || ! is_file( $wp_root . '/wp-settings.php' ) || ! $runtime || 13377 !== $port ) {
	exit( "Usage: php tests/integration.php WORDPRESS_ROOT TEMP_RUNTIME 13377\n" );
}
$temp_root = realpath( sys_get_temp_dir() );
if ( 0 !== strpos( strtolower( $runtime . DIRECTORY_SEPARATOR ), strtolower( $temp_root . DIRECTORY_SEPARATOR ) ) ) {
	exit( "Runtime must be inside system Temp.\n" );
}
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
$server   = new mysqli( '127.0.0.1', 'root', '', '', $port );
$database = 'jw_eci_test_' . bin2hex( random_bytes( 8 ) );
$server->query( 'CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4' );
register_shutdown_function( static function () use ( $server, $database ) {
	// Tên do test tạo, kiểm tra lại trước thao tác hủy dữ liệu thử nghiệm.
	if ( preg_match( '/^jw_eci_test_[a-f0-9]{16}$/D', $database ) ) {
		$server->query( 'DROP DATABASE IF EXISTS `' . $database . '`' );
	}
} );
define( 'ABSPATH', $wp_root . '/' );
define( 'WP_CONTENT_DIR', $runtime . '/content' );
define( 'WP_CONTENT_URL', 'http://example.invalid/content' );
define( 'WP_PLUGIN_DIR', dirname( __DIR__, 2 ) );
define( 'WP_PLUGIN_URL', 'http://example.invalid/plugins' );
define( 'DB_NAME', $database );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', '127.0.0.1:' . $port );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
define( 'WP_INSTALLING', true );
define( 'WP_ADMIN', true );
define( 'WP_DEBUG', false );
define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
define( 'DISABLE_WP_CRON', true );
define( 'AUTH_KEY', 'isolated-test-only' );
define( 'SECURE_AUTH_KEY', 'isolated-test-only-secure' );
define( 'LOGGED_IN_KEY', 'isolated-test-only-login' );
define( 'NONCE_KEY', 'isolated-test-only-nonce' );
$_SERVER['HTTP_HOST'] = 'example.invalid';
$_SERVER['REQUEST_URI'] = '/wp-admin/admin.php';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$table_prefix = 'wp_';
// Nạp plugin đúng thời điểm trước plugins_loaded/init trong WordPress thật.
$GLOBALS['wp_filter']['muplugins_loaded'][10][] = array(
	'function' => static function () { require dirname( __DIR__ ) . '/jw-eng-customer-images.php'; },
	'accepted_args' => 0,
);
require ABSPATH . 'wp-settings.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';
add_filter( 'pre_wp_mail', '__return_true' );
wp_install( 'JW isolated test', 'testadmin', 'test@example.invalid', false, '', 'test-only-password' );
wp_set_current_user( 1 );

use JW_Eng_Customer_Images\App\Database;
use JW_Eng_Customer_Images\App\Repository;
use JW_Eng_Customer_Images\App\Controllers\Admin\Admin_Settings;

$checks = 0;
function check( bool $ok, string $message ): void {
	global $checks;
	if ( ! $ok ) {
		throw new RuntimeException( $message );
	}
	++$checks;
}
function created( $result ): int {
	check( is_array( $result ) && isset( $result[0] ), 'Create failed: ' . ( is_wp_error( $result ) ? $result->get_error_message() : 'unexpected result' ) );
	return $result[0];
}

check( true === Database::install(), 'Fresh schema' );
check( array() === jw_eng_customer_images_get_all(), 'Activation must be empty' );
check( true === Database::install(), 'Repeated activation' );
$repo = new Repository();
$attachments = array();
foreach ( array( 'first', 'second', 'third' ) as $name ) {
	$id = wp_insert_attachment( array( 'post_title' => $name, 'post_status' => 'inherit', 'post_mime_type' => 'image/jpeg', 'guid' => 'http://example.invalid/' . $name . '.jpg' ) );
	update_post_meta( $id, '_wp_attached_file', $name . '.jpg' );
	$attachments[] = $id;
}
$category = array( 'name' => 'Category A', 'position' => '9' );
$a = created( $repo->save( 'category', $category ) );
$b = created( $repo->save( 'category', array( 'name' => 'Category B', 'position' => '1', 'icon_id' => $attachments[0] ) ) );
$c = created( $repo->save( 'category', array( 'name' => 'Category C', 'position' => '1' ) ) );
check( array( $b, $c, $a ) === array_column( jw_eng_customer_images_get_all(), 'id' ), 'Position and ID order' );
check( 0 === jw_eng_customer_images_get( $a )['icon']['image_id'], 'Optional media' );
foreach ( array( '-1', '1.2', 'abc', array(), '4294967296', '' ) as $position ) {
	check( is_wp_error( $repo->save( 'category', array( 'name' => 'Invalid', 'position' => $position ) ) ), 'Invalid position rejected' );
}
check( is_wp_error( $repo->save( 'category', array( 'name' => array(), 'position' => '0' ) ) ), 'Array name rejected' );
check( is_wp_error( $repo->save( 'category', array( 'name' => str_repeat( 'x', 256 ), 'position' => '0' ) ) ), 'Long name rejected' );
check( is_wp_error( $repo->save( 'category', array( 'name' => 'Invalid attachment', 'position' => '0', 'icon_id' => 9999999 ) ) ), 'Invalid image rejected' );
$sub = array( 'category_id' => $a, 'name' => 'Service A', 'description' => '<b>Plain text</b>', 'position' => '5', 'image_id' => $attachments[0] );
$s1 = created( $repo->save( 'subcategory', $sub ) );
$s2 = created( $repo->save( 'subcategory', array_merge( $sub, array( 'name' => 'Service B', 'position' => '0' ) ) ) );
check( array( $s2, $s1 ) === array_column( jw_eng_customer_images_get( $a )['subcategories'], 'id' ), 'Subcategory order' );
check( 'Plain text' === jw_eng_customer_images_get( $a )['subcategories'][0]['description'], 'Plain description' );
check( is_wp_error( $repo->delete( 'category', $a ) ), 'Category deletion blocked' );
check( is_wp_error( $repo->save( 'subcategory', array_merge( $sub, array( 'category_id' => 999999 ) ) ) ), 'Missing parent rejected' );
check( is_wp_error( $repo->save( 'subcategory', array_merge( $sub, array( 'description' => '' ) ) ) ), 'Required description' );
$images = $repo->save( 'image', array( 'subcategory_id' => $s1, 'image_ids' => $attachments ) );
check( is_array( $images ) && 3 === count( $images ), 'Multiple images' );
check( is_wp_error( $repo->delete( 'subcategory', $s1 ) ), 'Subcategory deletion blocked' );
$page = jw_eng_customer_images_get_images( $s1, 2, 2 );
check( 3 === $page['total'] && 1 === count( $page['items'] ) && $images[2] === $page['items'][0]['id'], 'Image pagination' );
check( null === jw_eng_customer_images_get( 99999 ) && null === jw_eng_customer_images_get_image( 99999 ), 'Missing records' );
check( 0 === jw_eng_customer_images_get_images( 99999 )['total'], 'Missing gallery parent' );
check( 1 === jw_eng_customer_images_get_images( $s1, 0, 0 )['per_page'], 'Pagination minimum' );
check( 100 === jw_eng_customer_images_get_images( $s1, 1, 999 )['per_page'], 'Pagination maximum' );
check( is_wp_error( $repo->save( 'image', array( 'subcategory_id' => $s1, 'image_ids' => array( $attachments[0], 999999 ) ) ) ), 'Invalid batch rejected' );
check( 3 === jw_eng_customer_images_get_images( $s1 )['total'], 'Invalid batch leaves data unchanged' );

// Ép lỗi SQL ở bản ghi thứ hai để chứng minh rollback thật.
$t = Database::tables();
$wpdb->query( "CREATE TRIGGER jw_eci_fail BEFORE INSERT ON `{$t['image']}` FOR EACH ROW BEGIN IF NEW.image_id = {$attachments[1]} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'isolated test'; END IF; END" );
check( '' === $wpdb->last_error, 'Create fault trigger' );
check( is_wp_error( $repo->save( 'image', array( 'subcategory_id' => $s1, 'image_ids' => array( $attachments[0], $attachments[1] ) ) ) ), 'SQL failure returned' );
check( 3 === jw_eng_customer_images_get_images( $s1 )['total'], 'SQL batch rolled back' );
$wpdb->query( 'DROP TRIGGER jw_eci_fail' );
check( $s1 === $repo->save( 'subcategory', array_merge( $sub, array( 'category_id' => $b, 'position' => '0' ) ), $s1 ), 'Move subcategory' );
check( 3 === jw_eng_customer_images_get_images( $s1 )['total'], 'Moving parent preserves images' );
check( 3 === $repo->listing( 'image', $b )['total'] && 0 === $repo->listing( 'image', $a )['total'], 'Category image filter' );
check( $images[0] === $repo->save( 'image', array( 'subcategory_id' => $s2, 'image_id' => $attachments[2] ), $images[0] ), 'Move and replace image' );
check( true === Database::install() && 2 === jw_eng_customer_images_get_images( $s1 )['total'], 'Reactivation preserves records' );
( new JW_Eng_Customer_Images\App\Deactivator() )->deactivate();
check( 2 === jw_eng_customer_images_get_images( $s1 )['total'], 'Deactivation preserves records' );

// Kiểm tra route thật, menu và render cả ba màn hình.
check( false !== has_action( 'admin_post_' . Admin_Settings::ACTION ), 'Mutation route registered' );
$controller = Admin_Settings::get_instance( 'JW_Eng_Customer_Images\\App\\Models\\Admin\\Admin_Settings', 'JW_Eng_Customer_Images\\App\\Views\\Admin\\Admin_Settings' );
$menu = $submenu = array();
do_action( 'admin_menu' );
foreach ( Admin_Settings::SLUGS as $entity => $slug ) {
	$_GET = array( 'page' => $slug );
	ob_start();
	$callback = array( 'category' => 'markup_settings_page', 'subcategory' => 'markup_subcategory_page', 'image' => 'markup_image_page' )[ $entity ];
	$controller->$callback();
	$html = ob_get_clean();
	check( str_contains( $html, Admin_Settings::ACTION ) && str_contains( $html, 'jw-eng-ci-table' ), 'Render ' . $entity );
}
set_current_screen( 'toplevel_page_jw-eng-customer-images' );
$controller->enqueue_assets( 'unrelated_screen' );
check( ! wp_script_is( 'jw-eng-customer-images_admin-js', 'enqueued' ), 'Assets scoped' );
$controller->enqueue_assets( 'toplevel_page_jw-eng-customer-images' );
check( wp_script_is( 'jw-eng-customer-images_admin-js', 'enqueued' ), 'Admin assets enqueued' );

// wp_die trở thành exception chỉ trong test để kiểm tra ranh giới quyền/nonce.
add_filter( 'wp_die_handler', static function () { return static function ( $message ) { throw new RuntimeException( (string) $message ); }; } );
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = array( 'operation' => 'save', 'entity' => 'category', 'id' => '0', 'name' => 'Denied', 'position' => '0', '_wpnonce' => 'bad' );
$count = count( jw_eng_customer_images_get_all() );
foreach ( array( 0, 1 ) as $user_id ) {
	wp_set_current_user( $user_id );
	$denied = false;
	try { $controller->handle_post(); } catch ( RuntimeException $error ) { $denied = true; }
	check( $denied, 'Capability/nonce rejection ' . $user_id );
}
check( $count === count( jw_eng_customer_images_get_all() ), 'Rejected writes do not change data' );

// Chặn redirect ở biên HTTP để kiểm tra POST thật mà không kết thúc tiến trình test.
class Test_Redirect extends RuntimeException {}
$capture_redirect = static function ( $location ) { throw new Test_Redirect( $location ); };
add_filter( 'wp_redirect', $capture_redirect );
function post_action( $controller, array $input ): array {
	$_POST = wp_slash( array_merge( $input, array(
		'_wpnonce' => wp_create_nonce( Admin_Settings::NONCE_ACTION . '_' . $input['operation'] . '_' . $input['entity'] . '_' . $input['id'] ),
	) ) );
	try {
		$controller->handle_post();
	} catch ( Test_Redirect $redirect ) {
		parse_str( (string) parse_url( $redirect->getMessage(), PHP_URL_QUERY ), $query );
		return get_transient( 'jw_eci_' . get_current_user_id() . '_' . $query['result'] );
	}
	throw new RuntimeException( 'Expected redirect' );
}
$posted = array( 'operation' => 'save', 'entity' => 'category', 'id' => '0', 'name' => "O'Brien <b>test</b>", 'position' => '3' );
$flash = post_action( $controller, $posted );
check( 'success' === $flash['notice']['type'], 'POST create success' );
$new_rows = $repo->listing( 'category', 0, 0, 1, null );
$posted_id = (int) max( array_column( $new_rows, 'id' ) );
check( "O'Brien test" === $repo->get( 'category', $posted_id )['name'], 'POST unslash and sanitize' );
$posted['id'] = (string) $posted_id;
$posted['position'] = '0';
$flash = post_action( $controller, $posted );
check( 'success' === $flash['notice']['type'] && $posted_id === jw_eng_customer_images_get_all()[0]['id'], 'POST update position' );
$posted['position'] = '-9';
$flash = post_action( $controller, $posted );
check( 'error' === $flash['notice']['type'] && '-9' === $flash['input']['position'], 'Invalid form retained' );
check( '0' === $repo->get( 'category', $posted_id )['position'], 'Invalid POST leaves row intact' );
$flash = post_action( $controller, array( 'operation' => 'delete', 'entity' => 'category', 'id' => (string) $posted_id ) );
check( 'success' === $flash['notice']['type'] && null === $repo->get( 'category', $posted_id ), 'POST delete success' );
remove_filter( 'wp_redirect', $capture_redirect );

// Phiên thứ hai phải đợi khóa cha; sau khi xóa commit, phiên đó thấy cha đã mất.
$other = new mysqli( '127.0.0.1', 'root', '', $database, $port );
$other->query( 'START TRANSACTION' );
$lock_waited = false;
$probe = static function ( $sql ) use ( $other, $t, $c, &$lock_waited ) {
	if ( str_starts_with( $sql, 'DELETE FROM `' . $t['category'] . '`' ) ) {
		$other->query( "SELECT id FROM `{$t['category']}` WHERE id = $c FOR UPDATE", MYSQLI_ASYNC );
		$read = $errors = $reject = array( $other );
		$lock_waited = 0 === mysqli_poll( $read, $errors, $reject, 0, 100000 );
	}
	return $sql;
};
add_filter( 'query', $probe );
check( true === $repo->delete( 'category', $c ), 'Delete empty category' );
remove_filter( 'query', $probe );
$locked_result = $other->reap_async_query();
check( $lock_waited && 0 === $locked_result->num_rows, 'Concurrent parent lookup waits then sees deleted parent' );
$other->query( 'ROLLBACK' );
$other->close();

wp_delete_attachment( $attachments[0], true );
check( '' === jw_eng_customer_images_get( $b )['icon']['image_url'], 'Deleted attachment URL empty' );
check( true === $repo->delete( 'image', $images[0] ), 'Delete image record' );
check( 'attachment' === get_post_type( $attachments[2] ), 'Media preserved by record deletion' );
$wpdb->query( 'CREATE TABLE wp_images_before_and_after (id bigint PRIMARY KEY) ENGINE=InnoDB' );
$wpdb->query( 'INSERT INTO wp_images_before_and_after VALUES (42)' );
( new JW_Eng_Customer_Images\App\Uninstaller() )->uninstall();
foreach ( $t as $table ) {
	check( null === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ), 'Owned table removed' );
}
check( false === get_option( Database::OPTION ), 'Version removed' );
check( '42' === $wpdb->get_var( 'SELECT id FROM wp_images_before_and_after' ), 'Legacy data preserved' );
check( 'attachment' === get_post_type( $attachments[2] ), 'Uninstall preserves media' );
echo "PASS: $checks checks; isolated database $database removed on exit.\n";
