<?php
/**
 * Lớp View - Xử lý việc render template
 * Sử dụng layout wrapper để đảm bảo giao diện nhất quán
 */
class View
{
    /**
     * Render một template bên trong layout chính
     *
     * @param string $template Đường dẫn template (ví dụ: 'home/index', 'exam/start')
     * @param array  $data     Dữ liệu truyền vào view (sẽ được extract thành biến)
     */
    public function render(string $template, array $data = []): void
    {
        // Trích xuất dữ liệu thành các biến cục bộ
        // Ví dụ: $data['title'] → $title
        extract($data, EXTR_SKIP);

        // Đường dẫn đầy đủ tới file template nội dung
        $content_template = APP_ROOT . '/views/' . $template . '.php';

        // Kiểm tra file template tồn tại
        if (!file_exists($content_template)) {
            if (DEBUG_MODE) {
                throw new RuntimeException('View template không tồn tại: ' . $content_template);
            }
            // Trong production, hiển thị trang lỗi đơn giản
            http_response_code(500);
            echo '<h1>Lỗi hệ thống</h1><p>Không thể tải trang. Vui lòng thử lại sau.</p>';
            exit;
        }

        // Bắt đầu output buffer
        ob_start();

        // Include layout - layout sẽ tự include $content_template
        include APP_ROOT . '/views/_layout.php';

        // Xuất nội dung đã buffer ra
        echo ob_get_clean();
    }

    /**
     * Render một partial/fragment không có layout
     * Dùng cho AJAX hoặc các component nhỏ
     *
     * @param string $template Đường dẫn template
     * @param array  $data     Dữ liệu truyền vào
     */
    public function partial(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $templateFile = APP_ROOT . '/views/' . $template . '.php';

        if (!file_exists($templateFile)) {
            return;
        }

        include $templateFile;
    }

    /**
     * Escape HTML entities để ngăn XSS
     *
     * @param mixed $str Chuỗi cần escape
     * @return string Chuỗi đã được escape
     */
    public function escape(mixed $str): string
    {
        return htmlspecialchars((string)$str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Tạo URL đầy đủ từ đường dẫn tương đối
     *
     * @param string $path Đường dẫn tương đối (ví dụ: 'auth/login')
     * @return string URL đầy đủ
     */
    public function url(string $path = ''): string
    {
        return BASE_URL . ltrim($path, '/');
    }
}
