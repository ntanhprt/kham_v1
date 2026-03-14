<?php
/**
 * Lớp Controller cơ sở
 * Tất cả controller đều kế thừa từ lớp này
 */
class Controller
{
    // Instance của View để render template
    protected View $view;

    public function __construct()
    {
        $this->view = new View();
    }

    /**
     * Render một template với dữ liệu truyền vào
     *
     * @param string $template Đường dẫn template (ví dụ: 'home/index', 'exam/start')
     * @param array  $data     Dữ liệu truyền vào view
     */
    protected function render(string $template, array $data = []): void
    {
        $this->view->render($template, $data);
    }

    /**
     * Chuyển hướng tới một URL (tương đối với BASE_URL)
     *
     * @param string $path Đường dẫn tương đối, ví dụ: 'auth/login'
     */
    protected function redirect(string $path): void
    {
        $url = BASE_URL . ltrim($path, '/');
        header('Location: ' . $url);
        exit;
    }

    /**
     * Trả về response dạng JSON
     *
     * @param mixed $data Dữ liệu cần trả về
     * @param int   $code HTTP status code
     */
    protected function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Yêu cầu người dùng đã đăng nhập
     * Nếu chưa đăng nhập → chuyển hướng về trang đăng nhập
     */
    protected function requireLogin(): void
    {
        if (!Auth::isLoggedIn()) {
            // Lưu lại URL hiện tại để sau khi đăng nhập có thể quay lại
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
            $this->redirect('auth/login');
        }
    }

    /**
     * Yêu cầu người dùng có quyền Admin
     * Nếu không có quyền → hiển thị trang 403
     */
    protected function requireAdmin(): void
    {
        $this->requireLogin();
        if (!Auth::isAdmin()) {
            http_response_code(403);
            $this->render('errors/403', ['title' => 'Không Có Quyền Truy Cập']);
            exit;
        }
    }

    /**
     * Yêu cầu người dùng có quyền Doctor hoặc Admin
     * Nếu không có quyền → hiển thị trang 403
     */
    protected function requireDoctor(): void
    {
        $this->requireLogin();
        if (!Auth::isDoctor() && !Auth::isAdmin()) {
            http_response_code(403);
            $this->render('errors/403', ['title' => 'Không Có Quyền Truy Cập']);
            exit;
        }
    }
}
