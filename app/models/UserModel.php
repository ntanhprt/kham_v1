<?php
/**
 * UserModel - Quản lý dữ liệu người dùng
 *
 * Bảng users gồm các cột:
 *   id, username, email, password_hash, role (admin/doctor/patient),
 *   status (active/inactive/banned), created_at, last_login_at
 */
class UserModel extends BaseModel
{
    protected string $table = 'users';

    // Các vai trò hợp lệ
    public const ROLE_ADMIN   = 'admin';
    public const ROLE_DOCTOR  = 'doctor';
    public const ROLE_PATIENT = 'patient';

    // Các trạng thái tài khoản hợp lệ
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_BANNED   = 'banned';

    /**
     * Tìm người dùng theo tên đăng nhập
     *
     * @param string $username Tên đăng nhập
     * @return array|null Thông tin user hoặc null nếu không tìm thấy
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE username = ? LIMIT 1",
            [trim($username)]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Tìm người dùng theo địa chỉ email
     *
     * @param string $email Email cần tìm
     * @return array|null Thông tin user hoặc null nếu không tìm thấy
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1",
            [strtolower(trim($email))]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Xác thực mật khẩu của người dùng
     *
     * @param array  $user     Mảng thông tin user (phải có trường password_hash)
     * @param string $password Mật khẩu plaintext cần kiểm tra
     * @return bool True nếu mật khẩu đúng
     */
    public function verifyPassword(array $user, string $password): bool
    {
        if (empty($user['password_hash'])) {
            return false;
        }
        return password_verify($password, $user['password_hash']);
    }

    /**
     * Tạo tài khoản người dùng mới
     *
     * @param string $username Tên đăng nhập (duy nhất)
     * @param string $email    Email (duy nhất)
     * @param string $password Mật khẩu plaintext (sẽ được hash)
     * @param string $role     Vai trò (mặc định: patient)
     * @return int ID của user vừa tạo
     * @throws InvalidArgumentException Nếu dữ liệu không hợp lệ
     */
    public function createUser(
        string $username,
        string $email,
        string $password,
        string $role = self::ROLE_PATIENT
    ): int {
        // Kiểm tra vai trò hợp lệ
        if (!in_array($role, [self::ROLE_ADMIN, self::ROLE_DOCTOR, self::ROLE_PATIENT], true)) {
            throw new InvalidArgumentException("Vai trò không hợp lệ: {$role}");
        }

        // Kiểm tra tên đăng nhập đã tồn tại chưa
        if ($this->findByUsername($username) !== null) {
            throw new RuntimeException("Tên đăng nhập '{$username}' đã được sử dụng.");
        }

        // Kiểm tra email đã tồn tại chưa
        if ($this->findByEmail($email) !== null) {
            throw new RuntimeException("Email '{$email}' đã được đăng ký.");
        }

        return $this->create([
            'username'      => trim($username),
            'email'         => strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'role'          => $role,
            'status'        => self::STATUS_ACTIVE,
            'created_at'    => date('Y-m-d H:i:s'),
            'last_login_at' => null,
        ]);
    }

    /**
     * Cập nhật thời gian đăng nhập gần nhất
     *
     * @param int $userId ID của người dùng
     */
    public function updateLastLogin(int $userId): void
    {
        $this->update($userId, [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Lấy danh sách tất cả tài khoản đang hoạt động
     *
     * @return array Danh sách user có status = active
     */
    public function getActiveUsers(): array
    {
        return $this->query(
            "SELECT id, username, email, role, created_at, last_login_at
             FROM {$this->table}
             WHERE status = ?
             ORDER BY created_at DESC",
            [self::STATUS_ACTIVE]
        )->fetchAll();
    }

    /**
     * Lấy danh sách tất cả bác sĩ
     *
     * @return array Danh sách user có role = doctor
     */
    public function getDoctors(): array
    {
        return $this->findBy(['role' => self::ROLE_DOCTOR, 'status' => self::STATUS_ACTIVE], 'username ASC');
    }

    /**
     * Thay đổi trạng thái tài khoản
     *
     * @param int    $userId ID người dùng
     * @param string $status Trạng thái mới
     * @return bool True nếu cập nhật thành công
     */
    public function setStatus(int $userId, string $status): bool
    {
        if (!in_array($status, [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_BANNED], true)) {
            return false;
        }
        return $this->update($userId, ['status' => $status]);
    }

    /**
     * Thay đổi mật khẩu người dùng
     *
     * @param int    $userId      ID người dùng
     * @param string $newPassword Mật khẩu mới (plaintext)
     * @return bool True nếu cập nhật thành công
     */
    public function changePassword(int $userId, string $newPassword): bool
    {
        return $this->update($userId, [
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]),
        ]);
    }

    /**
     * Lấy thống kê số lượng user theo vai trò
     *
     * @return array Mảng ['admin' => n, 'doctor' => n, 'patient' => n]
     */
    public function getCountByRole(): array
    {
        $rows = $this->query(
            "SELECT role, COUNT(*) as total FROM {$this->table} GROUP BY role"
        )->fetchAll();

        $result = [self::ROLE_ADMIN => 0, self::ROLE_DOCTOR => 0, self::ROLE_PATIENT => 0];
        foreach ($rows as $row) {
            $result[$row['role']] = (int)$row['total'];
        }
        return $result;
    }
}
