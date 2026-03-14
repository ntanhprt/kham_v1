<?php
/**
 * Lớp Auth - Quản lý xác thực và phân quyền người dùng
 * Tất cả phương thức là static để dễ dàng gọi từ bất kỳ đâu
 */
class Auth
{
    // Key lưu thông tin user trong session
    private const SESSION_KEY = 'auth_user';

    /**
     * Đăng nhập - Lưu thông tin user vào session
     *
     * @param int    $userId ID của người dùng
     * @param string $role   Vai trò: 'admin', 'doctor', 'patient'
     * @param array  $extra  Thông tin thêm (tên, email, ...)
     */
    public static function login(int $userId, string $role, array $extra = []): void
    {
        // Tái tạo session ID để ngăn session fixation attack
        session_regenerate_id(true);

        $_SESSION[self::SESSION_KEY] = array_merge([
            'id'         => $userId,
            'role'       => $role,
            'logged_in'  => true,
            'login_time' => time(),
        ], $extra);
    }

    /**
     * Đăng xuất - Xóa toàn bộ dữ liệu session
     */
    public static function logout(): void
    {
        // Xóa dữ liệu auth khỏi session
        unset($_SESSION[self::SESSION_KEY]);

        // Xóa toàn bộ session
        $_SESSION = [];

        // Xóa cookie session
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Hủy session
        session_destroy();
    }

    /**
     * Kiểm tra người dùng đã đăng nhập chưa
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]['logged_in'])
            && $_SESSION[self::SESSION_KEY]['logged_in'] === true;
    }

    /**
     * Lấy toàn bộ thông tin user từ session
     *
     * @return array|null Mảng thông tin user hoặc null nếu chưa đăng nhập
     */
    public static function getUser(): ?array
    {
        return self::isLoggedIn() ? $_SESSION[self::SESSION_KEY] : null;
    }

    /**
     * Lấy ID của user đang đăng nhập
     */
    public static function getUserId(): ?int
    {
        return self::isLoggedIn() ? (int)$_SESSION[self::SESSION_KEY]['id'] : null;
    }

    /**
     * Lấy vai trò của user đang đăng nhập
     */
    public static function getRole(): ?string
    {
        return self::isLoggedIn() ? $_SESSION[self::SESSION_KEY]['role'] : null;
    }

    /**
     * Kiểm tra user có phải Admin không
     */
    public static function isAdmin(): bool
    {
        return self::getRole() === 'admin';
    }

    /**
     * Kiểm tra user có phải Bác sĩ không
     */
    public static function isDoctor(): bool
    {
        return self::getRole() === 'doctor';
    }

    /**
     * Kiểm tra user có phải Bệnh nhân không
     */
    public static function isPatient(): bool
    {
        return self::getRole() === 'patient';
    }

    /**
     * Kiểm tra user có vai trò cụ thể không
     *
     * @param string $role Vai trò cần kiểm tra
     */
    public static function hasRole(string $role): bool
    {
        return self::getRole() === $role;
    }

    /**
     * Cập nhật một trường cụ thể trong session của user
     *
     * @param string $key   Tên trường
     * @param mixed  $value Giá trị mới
     */
    public static function updateSession(string $key, mixed $value): void
    {
        if (self::isLoggedIn()) {
            $_SESSION[self::SESSION_KEY][$key] = $value;
        }
    }

    /**
     * Lấy một trường cụ thể từ session của user
     *
     * @param string $key     Tên trường
     * @param mixed  $default Giá trị mặc định nếu không tìm thấy
     */
    public static function getSessionValue(string $key, mixed $default = null): mixed
    {
        if (!self::isLoggedIn()) {
            return $default;
        }
        return $_SESSION[self::SESSION_KEY][$key] ?? $default;
    }

    /**
     * Kiểm tra session có còn hợp lệ không (chưa hết hạn)
     */
    public static function isSessionValid(): bool
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        $loginTime = $_SESSION[self::SESSION_KEY]['login_time'] ?? 0;
        return (time() - $loginTime) < SESSION_LIFETIME;
    }
}
