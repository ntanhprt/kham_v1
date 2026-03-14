<?php
/**
 * KBModel - Truy vấn cơ sở tri thức Y Học Cổ Truyền (YHCT)
 *
 * Các bảng trong knowledge base:
 *   kb_observable_phrases  - Cụm từ mô tả triệu chứng quan sát được
 *   kb_symptoms            - Triệu chứng YHCT đã chuẩn hóa
 *   kb_pathogenesis        - Bệnh nguyên/bệnh cơ
 *   kb_patterns            - Chứng (biện chứng luận trị)
 *   kb_red_flags           - Cờ đỏ (dấu hiệu nguy hiểm cần chú ý)
 *   kb_clusters            - Cụm triệu chứng gợi ý
 *   kb_herb_drug           - Tương tác thuốc nam - thuốc tây
 *   kb_kiem_chung          - Kiểm chứng (cross-validation)
 */
class KBModel extends BaseModel
{
    // Bảng mặc định (có thể ghi đè khi cần)
    protected string $table = 'kb_symptoms';

    // =========================================================================
    // TRIỆU CHỨNG (kb_symptoms)
    // =========================================================================

    /**
     * Lấy danh sách triệu chứng với bộ lọc tùy chọn
     *
     * @param array $filters Bộ lọc: category, subcategory, status, ...
     * @return array Danh sách triệu chứng
     */
    public function getSymptoms(array $filters = []): array
    {
        $sql    = "SELECT * FROM kb_symptoms WHERE 1=1";
        $params = [];

        // Chỉ lấy triệu chứng đang hoạt động
        if (!isset($filters['status'])) {
            $sql .= " AND status = 'active'";
        } elseif ($filters['status'] !== 'all') {
            $sql    .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $sql    .= " AND category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['subcategory'])) {
            $sql    .= " AND subcategory = ?";
            $params[] = $filters['subcategory'];
        }

        if (!empty($filters['tang'])) {
            $sql    .= " AND tang = ?";
            $params[] = $filters['tang'];
        }

        $sql .= " ORDER BY symptom_code ASC";

        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Lấy triệu chứng theo mã code
     *
     * @param string $code Mã triệu chứng (ví dụ: 'S001', 'S_TH_001')
     * @return array|null Thông tin triệu chứng hoặc null
     */
    public function getSymptomByCode(string $code): ?array
    {
        $stmt = $this->query(
            "SELECT * FROM kb_symptoms WHERE symptom_code = ? LIMIT 1",
            [$code]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Tìm kiếm triệu chứng theo từ khóa (token matching trên tên và bí danh)
     * Hỗ trợ tìm kiếm tiếng Việt
     *
     * @param string $query Từ khóa tìm kiếm
     * @return array Danh sách triệu chứng phù hợp
     */
    public function searchSymptoms(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $like = '%' . $query . '%';

        return $this->query(
            "SELECT * FROM kb_symptoms
             WHERE status = 'active'
               AND (
                   name_vi LIKE ?
                   OR name_en LIKE ?
                   OR aliases LIKE ?
                   OR symptom_code LIKE ?
               )
             ORDER BY
               CASE WHEN name_vi LIKE ? THEN 0 ELSE 1 END,
               symptom_code ASC
             LIMIT 50",
            [$like, $like, $like, $like, $like]
        )->fetchAll();
    }

    // =========================================================================
    // CHỨNG/MẪU BỆNH (kb_patterns)
    // =========================================================================

    /**
     * Lấy danh sách chứng với bộ lọc
     *
     * @param array $filters Bộ lọc: tang, category, ...
     * @return array Danh sách chứng
     */
    public function getPatterns(array $filters = []): array
    {
        $sql    = "SELECT * FROM kb_patterns WHERE 1=1";
        $params = [];

        if (!isset($filters['status'])) {
            $sql .= " AND status = 'active'";
        } elseif ($filters['status'] !== 'all') {
            $sql    .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['tang'])) {
            $sql    .= " AND primary_tang = ?";
            $params[] = $filters['tang'];
        }

        if (!empty($filters['category'])) {
            $sql    .= " AND category = ?";
            $params[] = $filters['category'];
        }

        $sql .= " ORDER BY pattern_code ASC";

        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Lấy chứng theo mã code
     *
     * @param string $code Mã chứng (ví dụ: 'P_CAN_001')
     * @return array|null Thông tin chứng hoặc null
     */
    public function getPatternByCode(string $code): ?array
    {
        $stmt = $this->query(
            "SELECT * FROM kb_patterns WHERE pattern_code = ? LIMIT 1",
            [$code]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Lấy danh sách chứng theo mảng mã code
     *
     * @param array $codes Mảng mã chứng
     * @return array Danh sách chứng
     */
    public function getPatternsByCodes(array $codes): array
    {
        if (empty($codes)) {
            return [];
        }
        return $this->getByCodes($codes, 'kb_patterns', 'pattern_code');
    }

    // =========================================================================
    // CỜ ĐỎ - DẤU HIỆU NGUY HIỂM (kb_red_flags)
    // =========================================================================

    /**
     * Lấy danh sách cờ đỏ (dấu hiệu cần chú ý hoặc cấp cứu)
     *
     * @param string|null $level Mức độ: 'emergency' (cấp cứu) hoặc 'caution' (thận trọng)
     * @return array Danh sách cờ đỏ
     */
    public function getRedFlags(?string $level = null): array
    {
        $sql    = "SELECT * FROM kb_red_flags WHERE 1=1";
        $params = [];

        if ($level !== null) {
            $sql    .= " AND level = ?";
            $params[] = $level;
        }

        $sql .= " ORDER BY level DESC, code ASC";

        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Kiểm tra một mã triệu chứng có phải cờ đỏ không
     *
     * @param string $symptomCode Mã triệu chứng
     * @return array|null Thông tin cờ đỏ nếu có, null nếu không
     */
    public function getRedFlagBySymptom(string $symptomCode): ?array
    {
        $stmt = $this->query(
            "SELECT * FROM kb_red_flags WHERE symptom_codes LIKE ? LIMIT 1",
            ['%' . $symptomCode . '%']
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // =========================================================================
    // CỤM TRIỆU CHỨNG (kb_clusters)
    // =========================================================================

    /**
     * Lấy danh sách tất cả cụm triệu chứng
     *
     * @return array Danh sách cluster
     */
    public function getClusters(): array
    {
        return $this->query(
            "SELECT * FROM kb_clusters WHERE status = 'active' ORDER BY cluster_code ASC"
        )->fetchAll();
    }

    /**
     * Tìm cụm triệu chứng theo mã
     *
     * @param string $code Mã cluster
     * @return array|null Thông tin cluster hoặc null
     */
    public function getClusterByCode(string $code): ?array
    {
        $stmt = $this->query(
            "SELECT * FROM kb_clusters WHERE cluster_code = ? LIMIT 1",
            [$code]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // =========================================================================
    // TƯƠNG TÁC THUỐC NAM - THUỐC TÂY (kb_herb_drug)
    // =========================================================================

    /**
     * Lấy cảnh báo tương tác thuốc nam với thuốc tây
     *
     * @param array $herbCodes   Mảng mã thuốc nam
     * @param array $drugClasses Mảng nhóm thuốc tây
     * @return array Danh sách cảnh báo tương tác
     */
    public function getHerbDrugInteractions(array $herbCodes = [], array $drugClasses = []): array
    {
        if (empty($herbCodes) && empty($drugClasses)) {
            return $this->query("SELECT * FROM kb_herb_drug ORDER BY severity DESC")->fetchAll();
        }

        $sql    = "SELECT * FROM kb_herb_drug WHERE 1=1";
        $params = [];

        // Tìm kiếm theo mã thuốc nam
        if (!empty($herbCodes)) {
            $likeParts = [];
            foreach ($herbCodes as $code) {
                $likeParts[] = "herb_code LIKE ?";
                $params[]    = '%' . $code . '%';
            }
            $sql .= " AND (" . implode(' OR ', $likeParts) . ")";
        }

        // Tìm kiếm theo nhóm thuốc tây
        if (!empty($drugClasses)) {
            $likeParts = [];
            foreach ($drugClasses as $cls) {
                $likeParts[] = "drug_class LIKE ?";
                $params[]    = '%' . $cls . '%';
            }
            $sql .= " AND (" . implode(' OR ', $likeParts) . ")";
        }

        $sql .= " ORDER BY severity DESC";

        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Lấy cảnh báo đặc biệt cho thai kỳ và trẻ em
     *
     * @param string $context 'pregnancy' | 'children' | 'elderly'
     * @return array Danh sách cảnh báo
     */
    public function getSpecialWarnings(string $context): array
    {
        return $this->query(
            "SELECT * FROM kb_herb_drug WHERE context = ? ORDER BY severity DESC",
            [$context]
        )->fetchAll();
    }

    // =========================================================================
    // KIỂM CHỨNG - CROSS VALIDATION (kb_kiem_chung)
    // =========================================================================

    /**
     * Lấy dữ liệu kiểm chứng giữa chứng chính và chứng phụ
     * Dùng để xác nhận hoặc bác bỏ kết quả biện chứng
     *
     * @param string $primaryCode   Mã chứng chính
     * @param string $secondaryCode Mã chứng phụ hoặc triệu chứng
     * @return array|null Dữ liệu kiểm chứng hoặc null
     */
    public function getKiemChung(string $primaryCode, string $secondaryCode): ?array
    {
        $stmt = $this->query(
            "SELECT * FROM kb_kiem_chung
             WHERE (primary_code = ? AND secondary_code = ?)
                OR (primary_code = ? AND secondary_code = ?)
             LIMIT 1",
            [$primaryCode, $secondaryCode, $secondaryCode, $primaryCode]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Lấy tất cả kiểm chứng liên quan đến một chứng
     *
     * @param string $code Mã chứng
     * @return array Danh sách kiểm chứng
     */
    public function getKiemChungByCode(string $code): array
    {
        return $this->query(
            "SELECT * FROM kb_kiem_chung
             WHERE primary_code = ? OR secondary_code = ?
             ORDER BY confidence DESC",
            [$code, $code]
        )->fetchAll();
    }

    // =========================================================================
    // CỤM TỪ QUAN SÁT (kb_observable_phrases)
    // =========================================================================

    /**
     * Lấy tất cả cụm từ quan sát được (biểu hiện bệnh theo ngôn ngữ thường)
     *
     * @return array Danh sách cụm từ
     */
    public function getObservablePhrases(): array
    {
        return $this->query(
            "SELECT * FROM kb_observable_phrases
             WHERE status = 'active'
             ORDER BY phrase_code ASC"
        )->fetchAll();
    }

    /**
     * Lấy cụm từ quan sát theo mã code
     *
     * @param string $code Mã cụm từ
     * @return array|null Thông tin cụm từ hoặc null
     */
    public function getPhraseByCode(string $code): ?array
    {
        $stmt = $this->query(
            "SELECT * FROM kb_observable_phrases WHERE phrase_code = ? LIMIT 1",
            [$code]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Tìm kiếm cụm từ quan sát theo từ khóa
     *
     * @param string $query Từ khóa tìm kiếm
     * @return array Danh sách cụm từ phù hợp
     */
    public function searchObservablePhrases(string $query): array
    {
        $like = '%' . trim($query) . '%';
        return $this->query(
            "SELECT * FROM kb_observable_phrases
             WHERE status = 'active'
               AND (phrase_vi LIKE ? OR phrase_en LIKE ? OR aliases LIKE ?)
             ORDER BY phrase_code ASC
             LIMIT 30",
            [$like, $like, $like]
        )->fetchAll();
    }

    // =========================================================================
    // BỆNH NGUYÊN / BỆNH CƠ (kb_pathogenesis)
    // =========================================================================

    /**
     * Lấy danh sách bệnh nguyên theo tạng
     *
     * @param string|null $tang Tên tạng (can/than/ty_vi/tam/phe/...) hoặc null để lấy tất cả
     * @return array Danh sách bệnh nguyên
     */
    public function getPathogenesis(?string $tang = null): array
    {
        $sql    = "SELECT * FROM kb_pathogenesis_rules WHERE 1=1";
        $params = [];

        if ($tang !== null) {
            $sql    .= " AND organ_system = ?";
            $params[] = $tang;
        }

        $sql .= " ORDER BY rule_code ASC";

        return $this->query($sql, $params)->fetchAll();
    }

    // =========================================================================
    // TIỆN ÍCH CHUNG
    // =========================================================================

    /**
     * Lấy nhiều bản ghi theo mảng mã code
     * Dùng chung cho các bảng có trường code
     *
     * @param array  $codes     Mảng mã cần lấy
     * @param string $table     Tên bảng
     * @param string $codeField Tên cột chứa mã
     * @return array Danh sách bản ghi
     */
    public function getByCodes(array $codes, string $table = 'kb_symptoms', string $codeField = 'symptom_code'): array
    {
        if (empty($codes)) {
            return [];
        }

        // Tạo placeholder an toàn
        $placeholders = implode(', ', array_fill(0, count($codes), '?'));
        $sql = "SELECT * FROM {$table} WHERE {$codeField} IN ({$placeholders}) ORDER BY {$codeField}";

        return $this->query($sql, array_values($codes))->fetchAll();
    }

    /**
     * Lấy tất cả các giá trị distinct của một cột (dùng để xây dựng bộ lọc)
     *
     * @param string $column Tên cột
     * @param string $table  Tên bảng
     * @return array Mảng các giá trị distinct
     */
    public function getDistinctValues(string $column, string $table = 'kb_symptoms'): array
    {
        $rows = $this->query(
            "SELECT DISTINCT {$column} FROM {$table} WHERE {$column} IS NOT NULL ORDER BY {$column}"
        )->fetchAll(PDO::FETCH_COLUMN);

        return $rows;
    }
}
