<?php
/**
 * Lớp Database - Quản lý kết nối PDO/SQLite
 * Sử dụng Singleton pattern để đảm bảo chỉ có một kết nối duy nhất
 */
class Database
{
    // Instance PDO duy nhất (Singleton)
    private static ?PDO $instance = null;

    /**
     * Ngăn không cho khởi tạo trực tiếp
     */
    private function __construct() {}

    /**
     * Ngăn không cho clone instance
     */
    private function __clone() {}

    /**
     * Lấy instance PDO duy nhất (tạo mới nếu chưa có)
     *
     * @return PDO Kết nối PDO tới SQLite
     * @throws PDOException Nếu không thể kết nối
     */
    public static function get(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::connect();
        }

        return self::$instance;
    }

    /**
     * Tạo kết nối PDO mới tới SQLite
     */
    private static function connect(): PDO
    {
        // Đảm bảo thư mục storage tồn tại
        $storageDir = dirname(DB_PATH);
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $dsn = 'sqlite:' . DB_PATH;

        $options = [
            // Bắt ngoại lệ khi có lỗi SQL
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Trả về kết quả dạng mảng kết hợp theo mặc định
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Không dùng prepared statement giả lập
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, null, null, $options);

        // Bật ràng buộc khóa ngoại (SQLite tắt mặc định)
        $pdo->exec('PRAGMA foreign_keys = ON');

        // Dùng WAL mode để cho phép đọc đồng thời tốt hơn
        $pdo->exec('PRAGMA journal_mode = WAL');

        // Tăng timeout khi database bị khóa (giây)
        $pdo->exec('PRAGMA busy_timeout = 5000');

        // Tối ưu hiệu năng: cache size và synchronous mode
        $pdo->exec('PRAGMA cache_size = -4000'); // 4MB cache
        $pdo->exec('PRAGMA synchronous = NORMAL'); // Cân bằng an toàn/tốc độ

        return $pdo;
    }

    /**
     * Đặt lại instance (dùng trong testing)
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
