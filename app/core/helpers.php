<?php
/**
 * Các hàm helper toàn cục cho ứng dụng Hệ Thống Khám Bệnh YHCT
 */

/**
 * Escape HTML entities để ngăn XSS
 * Tên ngắn gọn để dùng trong template
 *
 * @param mixed $str Giá trị cần escape
 * @return string Chuỗi an toàn cho HTML
 */
function h(mixed $str): string
{
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Tạo URL đầy đủ từ đường dẫn tương đối
 *
 * @param string $path Đường dẫn tương đối (ví dụ: 'auth/login', 'exam/start')
 * @return string URL đầy đủ bắt đầu từ BASE_URL
 */
function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

/**
 * Chuyển hướng người dùng tới một URL
 * Tự động dừng thực thi sau khi redirect
 *
 * @param string $path Đường dẫn tương đối
 */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/**
 * Đặt hoặc lấy flash message (thông báo một lần)
 * Flash message tự động xóa sau khi đọc
 *
 * @param string      $key Tên key flash
 * @param string|null $msg Nội dung thông báo (null = lấy thông báo)
 * @return string|null Nội dung thông báo nếu đọc, null nếu đặt
 */
function flash(string $key, ?string $msg = null): ?string
{
    if ($msg !== null) {
        // Đặt flash message
        $_SESSION['_flash'][$key] = $msg;
        return null;
    } else {
        // Lấy và xóa flash message
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}

/**
 * Kiểm tra có flash message không (không xóa)
 *
 * @param string $key Tên key flash
 * @return bool True nếu có flash message
 */
function hasFlash(string $key): bool
{
    return isset($_SESSION['_flash'][$key]);
}

/**
 * Tạo hoặc lấy CSRF token từ session
 * Token dùng để ngăn tấn công CSRF
 *
 * @return string CSRF token
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Kiểm tra CSRF token từ POST request
 * Dừng thực thi nếu token không hợp lệ
 */
function csrf_check(): void
{
    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        die(json_encode([
            'error'   => true,
            'message' => 'Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.',
        ]));
    }
}

/**
 * Cắt ngắn chuỗi văn bản (hỗ trợ UTF-8/tiếng Việt)
 *
 * @param string $str    Chuỗi cần cắt
 * @param int    $len    Độ dài tối đa
 * @param string $suffix Hậu tố thêm vào nếu cắt (mặc định: '...')
 * @return string Chuỗi đã cắt
 */
function truncate(string $str, int $len = 100, string $suffix = '...'): string
{
    if (mb_strlen($str, 'UTF-8') <= $len) {
        return $str;
    }
    return mb_substr($str, 0, $len, 'UTF-8') . $suffix;
}

/**
 * Định dạng ngày tháng theo kiểu Việt Nam (DD/MM/YYYY)
 *
 * @param string|int|null $date Ngày cần định dạng (timestamp hoặc chuỗi)
 * @param bool            $withTime Có bao gồm giờ phút không
 * @return string Ngày đã định dạng
 */
function formatDate(mixed $date, bool $withTime = false): string
{
    if (empty($date)) {
        return '—';
    }

    // Chuyển đổi thành timestamp nếu là chuỗi
    $timestamp = is_numeric($date) ? (int)$date : strtotime($date);

    if ($timestamp === false || $timestamp === 0) {
        return '—';
    }

    $format = $withTime ? 'd/m/Y H:i' : 'd/m/Y';
    return date($format, $timestamp);
}

/**
 * Tạo UUID version 4 (ngẫu nhiên)
 *
 * @return string UUID dạng xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
 */
function generateUUID(): string
{
    $data = random_bytes(16);

    // Đặt version 4
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    // Đặt variant bits
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Kiểm tra request có phải là AJAX không
 *
 * @return bool True nếu là AJAX request
 */
function isAjax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Debug helper: In biến và dừng thực thi
 * Chỉ nên dùng khi phát triển, không dùng trong production
 *
 * @param mixed ...$vars Các biến cần debug
 */
function dd(mixed ...$vars): void
{
    echo '<pre style="background:#1a1a1a;color:#00ff00;padding:20px;margin:10px;border-radius:4px;font-family:monospace;">';
    foreach ($vars as $var) {
        var_dump($var);
        echo "\n";
    }
    echo '</pre>';
    die();
}

/**
 * Định dạng số tiền theo kiểu Việt Nam
 *
 * @param float|int $amount Số tiền
 * @param string    $currency Ký hiệu tiền tệ
 * @return string Số tiền đã định dạng (ví dụ: 1.500.000 đ)
 */
function money(float|int $amount, string $currency = 'đ'): string
{
    return number_format($amount, 0, ',', '.') . ' ' . $currency;
}

/**
 * Lấy giá trị từ mảng một cách an toàn (tránh lỗi undefined index)
 *
 * @param array  $array   Mảng nguồn
 * @param string $key     Tên key
 * @param mixed  $default Giá trị mặc định
 * @return mixed Giá trị tìm được hoặc giá trị mặc định
 */
function arr(array $array, string $key, mixed $default = null): mixed
{
    return $array[$key] ?? $default;
}

/**
 * Làm sạch chuỗi đầu vào (trim + stripslashes)
 *
 * @param string $str Chuỗi cần làm sạch
 * @return string Chuỗi đã làm sạch
 */
function clean(string $str): string
{
    return trim(stripslashes($str));
}

/**
 * Chuyển đổi chuỗi tiếng Việt thành slug URL-safe
 * Ví dụ: "Khám bệnh YHCT" → "kham-benh-yhct"
 *
 * @param string $str Chuỗi cần chuyển đổi
 * @return string Slug URL-safe
 */
function slugify(string $str): string
{
    // Bảng chuyển đổi ký tự tiếng Việt
    $vn = [
        'à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
        'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
        'ì','í','ị','ỉ','ĩ',
        'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
        'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
        'ỳ','ý','ỵ','ỷ','ỹ',
        'đ',
        'À','Á','Ạ','Ả','Ã','Â','Ầ','Ấ','Ậ','Ẩ','Ẫ','Ă','Ằ','Ắ','Ặ','Ẳ','Ẵ',
        'È','É','Ẹ','Ẻ','Ẽ','Ê','Ề','Ế','Ệ','Ể','Ễ',
        'Ì','Í','Ị','Ỉ','Ĩ',
        'Ò','Ó','Ọ','Ỏ','Õ','Ô','Ồ','Ố','Ộ','Ổ','Ỗ','Ơ','Ờ','Ớ','Ợ','Ở','Ỡ',
        'Ù','Ú','Ụ','Ủ','Ũ','Ư','Ừ','Ứ','Ự','Ử','Ữ',
        'Ỳ','Ý','Ỵ','Ỷ','Ỹ',
        'Đ',
    ];

    $en = [
        'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
        'e','e','e','e','e','e','e','e','e','e','e',
        'i','i','i','i','i',
        'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
        'u','u','u','u','u','u','u','u','u','u','u',
        'y','y','y','y','y',
        'd',
        'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
        'e','e','e','e','e','e','e','e','e','e','e',
        'i','i','i','i','i',
        'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
        'u','u','u','u','u','u','u','u','u','u','u',
        'y','y','y','y','y',
        'd',
    ];

    $str = str_replace($vn, $en, $str);
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9\-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}
