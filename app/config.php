<?php
/**
 * Cấu hình ứng dụng Hệ Thống Khám Bệnh YHCT
 * Định nghĩa các hằng số toàn cục và khởi tạo session
 */

// Thư mục gốc của ứng dụng (thư mục app/)
defined('APP_ROOT') or define('APP_ROOT', __DIR__);

// Đường dẫn tới file cơ sở dữ liệu SQLite
define('DB_PATH', APP_ROOT . '/storage/kham.db');

// URL gốc của ứng dụng (có dấu / ở cuối)
define('BASE_URL', '/y/kham/');

// Phiên bản ứng dụng
define('APP_VERSION', '1.0.0');

// Tên ứng dụng
define('APP_NAME', 'Hệ Thống Khám Bệnh YHCT');

// Tên session
define('SESSION_NAME', 'kham_session');

// Thời gian sống của session (giây) - 2 giờ
define('SESSION_LIFETIME', 7200);

// Chế độ debug: bật nếu chạy trên localhost
define('DEBUG_MODE', isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']));

// Email quản trị viên
define('ADMIN_EMAIL', 'admin@kham.local');

// Cấu hình báo lỗi PHP dựa trên chế độ debug
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    // Ghi lỗi vào log khi không ở chế độ debug
    ini_set('log_errors', '1');
    ini_set('error_log', APP_ROOT . '/storage/error.log');
}

// Khởi tạo session với tên đã đặt
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => BASE_URL,
        'secure'   => false, // Đổi thành true nếu dùng HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
