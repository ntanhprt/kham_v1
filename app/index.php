<?php
/**
 * Điểm vào chính của ứng dụng Hệ Thống Khám Bệnh YHCT
 * Tất cả các request đều được định tuyến qua file này (qua .htaccess)
 */

define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/core/helpers.php';
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/Auth.php';
require_once APP_ROOT . '/core/Controller.php';
require_once APP_ROOT . '/core/View.php';
require_once APP_ROOT . '/core/App.php';

// Khởi tạo và chạy ứng dụng
$app = new App();
$app->run();
