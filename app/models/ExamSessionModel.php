<?php
/**
 * ExamSessionModel - Quản lý phiên khám bệnh
 *
 * Bảng exam_sessions gồm các cột:
 *   id, session_id (UUID), user_id, chief_complaint,
 *   phase (0/1/2/3/4 - giai đoạn khám),
 *   selected_codes (JSON - mã triệu chứng đã chọn),
 *   context_flags (JSON - cờ ngữ cảnh: giới tính, tuổi, thai kỳ...),
 *   quick_answers (JSON - câu trả lời nhanh từ bệnh nhân),
 *   result_data (JSON - kết quả chẩn đoán),
 *   status (active/completed/abandoned),
 *   created_at, completed_at
 */
class ExamSessionModel extends BaseModel
{
    protected string $table = 'exam_sessions';

    // Các giai đoạn của quá trình khám
    public const PHASE_INIT         = 0; // Khởi tạo
    public const PHASE_CHIEF        = 1; // Nhập triệu chứng chính
    public const PHASE_SYMPTOMS     = 2; // Thu thập triệu chứng
    public const PHASE_DISAMBIGUATE = 3; // Làm rõ (disambiguation)
    public const PHASE_RESULT       = 4; // Hiển thị kết quả

    // Trạng thái phiên khám
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ABANDONED = 'abandoned';

    /**
     * Tạo phiên khám mới
     *
     * @param int    $userId    ID người dùng (bác sĩ/bệnh nhân)
     * @param string $sessionId UUID của phiên khám (do caller tạo)
     * @return int ID bản ghi vừa tạo
     */
    public function createSession(?int $userId, string $sessionId): int
    {
        return $this->create([
            'session_id'     => $sessionId,
            'user_id'        => $userId,
            'chief_complaint'=> null,
            'phase'          => self::PHASE_INIT,
            'selected_codes' => json_encode([], JSON_UNESCAPED_UNICODE),
            'context_flags'  => json_encode([], JSON_UNESCAPED_UNICODE),
            'quick_answers'  => json_encode([], JSON_UNESCAPED_UNICODE),
            'result_data'    => null,
            'status'         => self::STATUS_ACTIVE,
            'created_at'     => date('Y-m-d H:i:s'),
            'completed_at'   => null,
        ]);
    }

    /**
     * Lấy phiên khám theo session_id (UUID)
     *
     * @param string $sessionId UUID của phiên khám
     * @return array|null Thông tin phiên khám hoặc null
     */
    public function getSession(string $sessionId): ?array
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE session_id = ? LIMIT 1",
            [$sessionId]
        );
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        // Tự động decode các trường JSON
        return $this->decodeJsonFields($row);
    }

    /**
     * Cập nhật phiên khám theo session_id (UUID)
     * Tự động encode mảng PHP thành JSON
     *
     * @param string $sessionId UUID của phiên khám
     * @param array  $data      Dữ liệu cập nhật
     * @return bool True nếu cập nhật thành công
     */
    public function updateSession(string $sessionId, array $data): bool
    {
        // Encode các trường JSON nếu được truyền vào dạng mảng
        $jsonFields = ['selected_codes', 'context_flags', 'quick_answers', 'result_data'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field], JSON_UNESCAPED_UNICODE);
            }
        }

        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = ?";
        }
        $setClause = implode(', ', $setParts);
        $params    = array_merge(array_values($data), [$sessionId]);

        $stmt = $this->query(
            "UPDATE {$this->table} SET {$setClause} WHERE session_id = ?",
            $params
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * Lấy tất cả phiên khám của một người dùng
     *
     * @param int $userId ID người dùng
     * @return array Danh sách phiên khám, mới nhất trước
     */
    public function getSessionsForUser(int $userId): array
    {
        $rows = $this->query(
            "SELECT * FROM {$this->table}
             WHERE user_id = ?
             ORDER BY created_at DESC",
            [$userId]
        )->fetchAll();

        return array_map([$this, 'decodeJsonFields'], $rows);
    }

    /**
     * Lấy danh sách phiên khám gần đây nhất (tất cả user)
     *
     * @param int $limit Số lượng bản ghi tối đa
     * @return array Danh sách phiên khám
     */
    public function getRecentSessions(int $limit = 20): array
    {
        $rows = $this->query(
            "SELECT es.*, u.username, u.role as user_role
             FROM {$this->table} es
             LEFT JOIN users u ON es.user_id = u.id
             ORDER BY es.created_at DESC
             LIMIT ?",
            [$limit]
        )->fetchAll();

        return array_map([$this, 'decodeJsonFields'], $rows);
    }

    /**
     * Đánh dấu phiên khám hoàn tất và lưu kết quả
     *
     * @param string $sessionId  UUID của phiên khám
     * @param array  $resultData Dữ liệu kết quả chẩn đoán
     * @return bool True nếu cập nhật thành công
     */
    public function markComplete(string $sessionId, array $resultData): bool
    {
        return $this->updateSession($sessionId, [
            'phase'        => self::PHASE_RESULT,
            'result_data'  => json_encode($resultData, JSON_UNESCAPED_UNICODE),
            'status'       => self::STATUS_COMPLETED,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Đánh dấu phiên khám bị bỏ dở
     *
     * @param string $sessionId UUID của phiên khám
     * @return bool True nếu cập nhật thành công
     */
    public function markAbandoned(string $sessionId): bool
    {
        return $this->updateSession($sessionId, [
            'status' => self::STATUS_ABANDONED,
        ]);
    }

    /**
     * Lấy phiên khám đang hoạt động của user (nếu có)
     *
     * @param int $userId ID người dùng
     * @return array|null Phiên khám đang active hoặc null
     */
    public function getActiveSession(int $userId): ?array
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table}
             WHERE user_id = ? AND status = ?
             ORDER BY created_at DESC LIMIT 1",
            [$userId, self::STATUS_ACTIVE]
        );
        $row = $stmt->fetch();
        return $row ? $this->decodeJsonFields($row) : null;
    }

    /**
     * Cập nhật giai đoạn của phiên khám
     *
     * @param string $sessionId UUID của phiên khám
     * @param int    $phase     Giai đoạn mới (PHASE_*)
     * @return bool True nếu cập nhật thành công
     */
    public function setPhase(string $sessionId, int $phase): bool
    {
        return $this->updateSession($sessionId, ['phase' => $phase]);
    }

    /**
     * Thống kê phiên khám theo trạng thái
     *
     * @return array Mảng ['active' => n, 'completed' => n, 'abandoned' => n]
     */
    public function getStatsByStatus(): array
    {
        $rows = $this->query(
            "SELECT status, COUNT(*) as total FROM {$this->table} GROUP BY status"
        )->fetchAll();

        $result = [
            self::STATUS_ACTIVE    => 0,
            self::STATUS_COMPLETED => 0,
            self::STATUS_ABANDONED => 0,
        ];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['total'];
        }
        return $result;
    }

    /**
     * Decode các trường JSON trong bản ghi thành mảng PHP
     * Phương thức nội bộ dùng sau khi fetch từ DB
     *
     * @param array $row Bản ghi thô từ DB
     * @return array Bản ghi với các trường JSON đã được decode
     */
    private function decodeJsonFields(array $row): array
    {
        $jsonFields = ['selected_codes', 'context_flags', 'quick_answers', 'result_data'];
        foreach ($jsonFields as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $decoded = json_decode($row[$field], true);
                $row[$field] = ($decoded !== null) ? $decoded : [];
            }
        }
        return $row;
    }
}
