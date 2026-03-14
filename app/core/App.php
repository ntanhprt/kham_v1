<?php
/**
 * Lớp App - Điều phối routing và khởi tạo controller
 * Xử lý URL dạng: /controller/method/param1/param2...
 */
class App
{
    // Danh sách các controller hợp lệ
    private array $validControllers = ['home', 'auth', 'exam', 'doctor', 'admin'];

    public function __construct()
    {
        // Tự động nạp tất cả model từ thư mục models/
        $this->autoloadDirectory(APP_ROOT . '/models');

        // Tự động nạp tất cả engine từ thư mục engine/
        $this->autoloadDirectory(APP_ROOT . '/engine');
    }

    /**
     * Tự động nạp tất cả file PHP trong một thư mục
     */
    private function autoloadDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = glob($directory . '/*.php');
        if ($files) {
            foreach ($files as $file) {
                require_once $file;
            }
        }
    }

    /**
     * Phân tích URL và điều hướng tới controller/method tương ứng
     */
    public function run(): void
    {
        // Lấy URL từ query string (được đặt bởi .htaccess)
        $url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';

        // Tách URL thành các phần
        $parts = $url !== '' ? explode('/', $url) : [];

        // Xác định controller (mặc định: home)
        $controllerName = !empty($parts[0]) ? strtolower($parts[0]) : 'home';

        // Xác định method (mặc định: index)
        $method = !empty($parts[1]) ? $parts[1] : 'index';

        // Các tham số còn lại
        $params = array_slice($parts, 2);

        // Kiểm tra controller có hợp lệ không
        if (!in_array($controllerName, $this->validControllers, true)) {
            $this->show404();
            return;
        }

        // Kiểm tra quyền truy cập trước khi nạp controller
        $this->checkAccess($controllerName);

        // Tên class controller (ví dụ: home → HomeController)
        $controllerClass = ucfirst($controllerName) . 'Controller';
        $controllerFile  = APP_ROOT . '/controllers/' . $controllerClass . '.php';

        // Kiểm tra file controller tồn tại
        if (!file_exists($controllerFile)) {
            $this->show404();
            return;
        }

        require_once $controllerFile;

        // Kiểm tra class controller tồn tại
        if (!class_exists($controllerClass)) {
            $this->show404();
            return;
        }

        // Khởi tạo controller
        $controller = new $controllerClass();

        // Kiểm tra method tồn tại và có thể gọi được
        // Chỉ cho phép các method public, không cho phép gọi method nội bộ
        if (!method_exists($controller, $method) || !$this->isPublicMethod($controllerClass, $method)) {
            $this->show404();
            return;
        }

        // Gọi method với các tham số
        call_user_func_array([$controller, $method], $params);
    }

    /**
     * Kiểm tra xem method có phải là public không
     */
    private function isPublicMethod(string $class, string $method): bool
    {
        try {
            $reflection = new ReflectionMethod($class, $method);
            return $reflection->isPublic() && !$reflection->isStatic();
        } catch (ReflectionException $e) {
            return false;
        }
    }

    /**
     * Kiểm tra quyền truy cập theo route
     */
    private function checkAccess(string $controllerName): void
    {
        // Các route yêu cầu quyền admin
        if ($controllerName === 'admin') {
            if (!Auth::isLoggedIn()) {
                redirect('auth/login');
            }
            if (!Auth::isAdmin()) {
                $this->show403();
            }
        }

        // Các route yêu cầu đăng nhập (bác sĩ hoặc admin)
        if ($controllerName === 'doctor') {
            if (!Auth::isLoggedIn()) {
                redirect('auth/login');
            }
            if (!Auth::isDoctor() && !Auth::isAdmin()) {
                $this->show403();
            }
        }

        // Route exam yêu cầu đăng nhập
        if ($controllerName === 'exam') {
            if (!Auth::isLoggedIn()) {
                redirect('auth/login');
            }
        }
    }

    /**
     * Hiển thị trang 404 - Không tìm thấy
     */
    private function show404(): void
    {
        http_response_code(404);
        $view = new View();
        $view->render('errors/404', ['title' => 'Không Tìm Thấy Trang']);
        exit;
    }

    /**
     * Hiển thị trang 403 - Không có quyền truy cập
     */
    private function show403(): void
    {
        http_response_code(403);
        $view = new View();
        $view->render('errors/403', ['title' => 'Không Có Quyền Truy Cập']);
        exit;
    }
}
