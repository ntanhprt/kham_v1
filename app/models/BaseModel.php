<?php
/**
 * Lớp BaseModel trừu tượng - Cung cấp các thao tác CRUD cơ bản
 * Tất cả model đều kế thừa từ lớp này
 */
abstract class BaseModel
{
    // Kết nối PDO dùng chung
    protected PDO $db;

    // Tên bảng (cần được định nghĩa trong lớp con)
    protected string $table = '';

    // Tên cột primary key (mặc định là 'id')
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::get();
    }

    /**
     * Tìm một bản ghi theo ID (primary key)
     *
     * @param int|string $id Giá trị primary key
     * @return array|null Bản ghi tìm được hoặc null
     */
    public function find(int|string $id): ?array
    {
        $sql  = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";
        $stmt = $this->query($sql, [$id]);
        $row  = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Tìm các bản ghi theo điều kiện
     *
     * @param array  $conditions Mảng điều kiện ['field' => 'value']
     * @param string $orderBy    Sắp xếp theo cột nào
     * @param int    $limit      Giới hạn số bản ghi (0 = không giới hạn)
     * @return array Danh sách bản ghi tìm được
     */
    public function findBy(array $conditions = [], string $orderBy = '', int $limit = 0): array
    {
        [$whereClause, $params] = $this->buildWhereClause($conditions);

        $sql = "SELECT * FROM {$this->table}";
        if ($whereClause) {
            $sql .= " WHERE {$whereClause}";
        }
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }

        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Tìm một bản ghi duy nhất theo điều kiện
     *
     * @param array $conditions Mảng điều kiện ['field' => 'value']
     * @return array|null Bản ghi tìm được hoặc null
     */
    public function findOneBy(array $conditions = []): ?array
    {
        $results = $this->findBy($conditions, '', 1);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Lấy tất cả bản ghi trong bảng
     *
     * @param string $orderBy Sắp xếp theo cột nào (mặc định: id)
     * @return array Danh sách tất cả bản ghi
     */
    public function findAll(string $orderBy = 'id'): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy}";
        return $this->query($sql)->fetchAll();
    }

    /**
     * Tạo một bản ghi mới
     *
     * @param array $data Dữ liệu cần insert ['column' => 'value']
     * @return int ID của bản ghi vừa tạo (lastInsertId)
     */
    public function create(array $data): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Dữ liệu không được rỗng khi tạo bản ghi.');
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));

        return (int)$this->db->lastInsertId();
    }

    /**
     * Cập nhật bản ghi theo ID
     *
     * @param int|string $id   ID của bản ghi cần cập nhật
     * @param array      $data Dữ liệu cần cập nhật ['column' => 'value']
     * @return bool True nếu cập nhật thành công
     */
    public function update(int|string $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = ?";
        }
        $setClause = implode(', ', $setParts);

        $sql    = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";
        $params = array_merge(array_values($data), [$id]);

        $stmt = $this->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Xóa bản ghi theo ID
     *
     * @param int|string $id ID của bản ghi cần xóa
     * @return bool True nếu xóa thành công
     */
    public function delete(int|string $id): bool
    {
        $sql  = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Thực thi câu SQL tùy chỉnh
     *
     * @param string $sql    Câu SQL có placeholder (?)
     * @param array  $params Tham số cho placeholder
     * @return PDOStatement Statement đã thực thi
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Đếm số bản ghi theo điều kiện
     *
     * @param array $conditions Điều kiện lọc ['field' => 'value']
     * @return int Số bản ghi
     */
    public function count(array $conditions = []): int
    {
        [$whereClause, $params] = $this->buildWhereClause($conditions);

        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if ($whereClause) {
            $sql .= " WHERE {$whereClause}";
        }

        return (int)$this->query($sql, $params)->fetchColumn();
    }

    /**
     * Kiểm tra bản ghi có tồn tại không
     *
     * @param array $conditions Điều kiện kiểm tra ['field' => 'value']
     * @return bool True nếu tồn tại ít nhất một bản ghi
     */
    public function exists(array $conditions = []): bool
    {
        return $this->count($conditions) > 0;
    }

    /**
     * Xây dựng mệnh đề WHERE từ mảng điều kiện
     * Nội bộ - không dùng trực tiếp từ bên ngoài
     *
     * @param array $conditions ['field' => 'value'] hoặc ['field' => ['operator', 'value']]
     * @return array [string $whereClause, array $params]
     */
    protected function buildWhereClause(array $conditions): array
    {
        if (empty($conditions)) {
            return ['', []];
        }

        $parts  = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                // Hỗ trợ ['operator', 'value'] như ['!=', 'deleted']
                [$operator, $val] = $value;
                $parts[]  = "{$column} {$operator} ?";
                $params[] = $val;
            } elseif ($value === null) {
                $parts[] = "{$column} IS NULL";
            } else {
                $parts[]  = "{$column} = ?";
                $params[] = $value;
            }
        }

        return [implode(' AND ', $parts), $params];
    }

    /**
     * Thực hiện transaction
     *
     * @param callable $callback Hàm chứa các thao tác trong transaction
     * @return mixed Kết quả trả về từ callback
     * @throws Throwable Nếu có lỗi trong transaction
     */
    public function transaction(callable $callback): mixed
    {
        $this->db->beginTransaction();
        try {
            $result = $callback($this);
            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
