# Thiết Kế Cơ Sở Dữ Liệu

## 1. Tổng Quan

CSDL là trái tim của hệ thống. Toàn bộ logic y học được mã hóa ở đây, không phải trong code. Điều này đảm bảo:
- **Tính tái hiện**: Cùng dữ liệu → cùng kết quả
- **Dễ bảo trì**: Bác sĩ cập nhật tri thức mà không cần lập trình viên
- **Dễ kiểm tra**: Mọi quyết định đều traceable về 1 row trong CSDL

---

## 2. Sơ Đồ Thực Thể (ERD)

```
symptoms ──────────< disease_symptoms >────────── diseases
    │                                                  │
    │                                            disease_treatments
    │                                                  │
    └──────── decision_nodes ────────┐           treatments
                   │                 │
             decision_edges      question_options
                   │
             symptom_mappings
```

---

## 3. Định Nghĩa Các Bảng

### 3.1 Bảng `diseases` – Danh Mục Bệnh

```sql
CREATE TABLE diseases (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code            VARCHAR(20) UNIQUE NOT NULL,  -- VD: "HA001", "TC002"
    name_vi         VARCHAR(200) NOT NULL,         -- Tên tiếng Việt
    name_yhct       VARCHAR(200),                  -- Tên YHCT (VD: "Can Dương Thượng Cang")
    name_yhhd       VARCHAR(200),                  -- Tên YHHD (VD: "Hypertension")
    category_id     UUID REFERENCES disease_categories(id),
    organ_system    VARCHAR(50),    -- "can", "tam", "ty", "phế", "than"
    severity        SMALLINT CHECK (severity BETWEEN 1 AND 5),
    is_emergency    BOOLEAN DEFAULT false,
    description     TEXT,
    created_at      TIMESTAMP DEFAULT NOW(),
    updated_at      TIMESTAMP DEFAULT NOW()
);
```

### 3.2 Bảng `symptoms` – Danh Mục Triệu Chứng

```sql
CREATE TABLE symptoms (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code            VARCHAR(20) UNIQUE NOT NULL,  -- VD: "S_HEADACHE"
    name_vi         VARCHAR(200) NOT NULL,
    body_location   VARCHAR(100),   -- "đầu", "ngực", "bụng", "toàn thân"
    system          VARCHAR(50),    -- "yhct", "yhhd", "both"
    yhct_category   VARCHAR(50),    -- "khi", "huyet", "am", "duong", "han", "nhiet"
    description     TEXT
);
```

### 3.3 Bảng `disease_symptoms` – Quan Hệ Bệnh–Triệu Chứng

```sql
CREATE TABLE disease_symptoms (
    disease_id      UUID REFERENCES diseases(id),
    symptom_id      UUID REFERENCES symptoms(id),
    weight          DECIMAL(3,2) NOT NULL,  -- 0.00 đến 1.00 (tầm quan trọng)
    is_required     BOOLEAN DEFAULT false,   -- Triệu chứng bắt buộc để chẩn đoán
    is_pathognomonic BOOLEAN DEFAULT false,  -- Triệu chứng đặc trưng duy nhất
    symptom_value   VARCHAR(100),            -- Giá trị cụ thể nếu cần (VD: "> 3 tháng")
    PRIMARY KEY (disease_id, symptom_id)
);
```

### 3.4 Bảng `decision_nodes` – Các Nút Trong Cây Hỏi Bệnh

```sql
CREATE TABLE decision_nodes (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code            VARCHAR(50) UNIQUE NOT NULL,
    node_type       VARCHAR(20) NOT NULL,
    -- 'question'    : Nút hỏi (có options)
    -- 'branch'      : Nút rẽ nhánh (không hỏi, chỉ định tuyến)
    -- 'terminal'    : Nút kết thúc giai đoạn

    phase           VARCHAR(30) NOT NULL,
    -- 'yhct_vong'   : Giai đoạn Vọng (quan sát)
    -- 'yhct_van_1'  : Giai đoạn Văn (nghe/ngửi)
    -- 'yhct_van_2'  : Giai đoạn Vấn (hỏi)
    -- 'yhct_thiet'  : Giai đoạn Thiết (sờ/mạch)
    -- 'yhhd'        : Giai đoạn YHHD

    question_text   TEXT,           -- Câu hỏi hiển thị cho user
    question_type   VARCHAR(20),    -- 'single_choice', 'multiple_choice', 'scale', 'yes_no'

    symptom_id      UUID REFERENCES symptoms(id),  -- Triệu chứng nào được thu thập
    is_required     BOOLEAN DEFAULT false,           -- Bắt buộc hỏi hay có thể skip

    chief_complaint_pattern VARCHAR(100),  -- Regex match với triệu chứng chính của user
    -- VD: "đau đầu|nhức đầu|đau nửa đầu" → nút này chỉ xuất hiện nếu TC chính khớp

    display_order   SMALLINT DEFAULT 0,
    notes           TEXT  -- Ghi chú cho admin/bác sĩ
);
```

### 3.5 Bảng `question_options` – Các Lựa Chọn Trả Lời

```sql
CREATE TABLE question_options (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    node_id         UUID REFERENCES decision_nodes(id),
    option_text     VARCHAR(200) NOT NULL,  -- Text hiển thị
    option_value    VARCHAR(100) NOT NULL,   -- Giá trị lưu vào CSDL
    symptom_value   VARCHAR(100),            -- Giá trị gán cho triệu chứng khi chọn option này
    display_order   SMALLINT DEFAULT 0,
    is_red_flag     BOOLEAN DEFAULT false,   -- Option này = dấu hiệu nguy hiểm?
    red_flag_message TEXT                    -- Cảnh báo nếu chọn option này
);
```

### 3.6 Bảng `decision_edges` – Các Cạnh (Logic Rẽ Nhánh)

```sql
CREATE TABLE decision_edges (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    from_node_id    UUID REFERENCES decision_nodes(id),
    to_node_id      UUID REFERENCES decision_nodes(id),
    condition_type  VARCHAR(30) NOT NULL,
    -- 'option_selected'   : Nếu user chọn option này
    -- 'symptom_present'   : Nếu triệu chứng X đã được ghi nhận
    -- 'symptom_absent'    : Nếu triệu chứng X không có
    -- 'always'            : Luôn đi theo cạnh này (default)

    condition_value VARCHAR(200),  -- Giá trị điều kiện (VD: option_value, symptom_code)
    priority        SMALLINT DEFAULT 0  -- Ưu tiên khi có nhiều cạnh từ một nút
);
```

### 3.7 Bảng `symptom_mappings` – Ánh Xạ Node → Symptom

```sql
-- Khi user trả lời một câu hỏi, triệu chứng nào được ghi nhận?
CREATE TABLE symptom_mappings (
    node_id         UUID REFERENCES decision_nodes(id),
    option_id       UUID REFERENCES question_options(id),
    symptom_id      UUID REFERENCES symptoms(id),
    symptom_value   VARCHAR(100),   -- Giá trị cụ thể của triệu chứng
    confidence      DECIMAL(3,2) DEFAULT 1.0,  -- Độ tin cậy của ánh xạ này
    PRIMARY KEY (node_id, option_id, symptom_id)
);
```

### 3.8 Bảng `treatments` – Phác Đồ Điều Trị

```sql
CREATE TABLE treatments (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    disease_id      UUID REFERENCES diseases(id),
    system          VARCHAR(10) NOT NULL,  -- 'yhct', 'yhhd'
    treatment_type  VARCHAR(30),
    -- YHCT: 'herb', 'acupuncture', 'massage', 'qigong', 'diet', 'lifestyle'
    -- YHHD: 'medication_class', 'procedure', 'referral', 'lifestyle'

    principle       TEXT,          -- Nguyên tắc điều trị
    details         TEXT,          -- Chi tiết (huyệt vị, thảo dược, v.v.)
    contraindications TEXT,        -- Chống chỉ định
    advice          TEXT,          -- Lời khuyên cho bệnh nhân
    priority        SMALLINT DEFAULT 0
);
```

### 3.9 Bảng `exam_sessions` – Lịch Sử Phiên Khám

```sql
CREATE TABLE exam_sessions (
    id              UUID PRIMARY KEY,
    started_at      TIMESTAMP DEFAULT NOW(),
    completed_at    TIMESTAMP,
    status          VARCHAR(20) DEFAULT 'active',

    -- Thông tin ẩn danh (không lưu tên/CCCD)
    patient_age     SMALLINT,
    patient_gender  CHAR(1),  -- 'M', 'F', 'O'
    chief_complaint TEXT,

    -- Snapshot kết quả (JSON)
    collected_symptoms JSONB,
    final_result    JSONB
);
```

---

## 4. Ví Dụ Dữ Liệu: Bệnh Đau Đầu

### 4.1 Thêm bệnh

```sql
INSERT INTO diseases (code, name_vi, name_yhct, name_yhhd, organ_system, severity)
VALUES
  ('HA001', 'Đau đầu do Can Dương Thượng Cang', 'Can Dương Thượng Cang', 'Tension-type headache / Hypertension', 'can', 2),
  ('HA002', 'Đau đầu do Đàm Thấp', 'Đàm Thấp Trở Trệ', 'Migraine', 'ty', 2),
  ('HA003', 'Đau đầu do Thận Hư', 'Thận Tinh Bất Túc', 'Cervicogenic headache', 'than', 2),
  ('HA999', 'Đau đầu cấp tính nguy hiểm', 'Nhiệt Nhập Tâm Bào', 'Subarachnoid hemorrhage', 'tam', 5);
```

### 4.2 Thêm triệu chứng

```sql
INSERT INTO symptoms (code, name_vi, body_location, yhct_category)
VALUES
  ('S_HA_LOCATION_TOP',    'Đau đầu vùng đỉnh',         'đầu', 'can'),
  ('S_HA_LOCATION_TEMPLE', 'Đau đầu vùng thái dương',   'đầu', 'can'),
  ('S_HA_LOCATION_BACK',   'Đau đầu vùng chẩm-gáy',    'đầu', 'than'),
  ('S_HA_DURATION_ACUTE',  'Đau đầu dưới 1 tuần',       'đầu', NULL),
  ('S_HA_DURATION_CHRONIC','Đau đầu trên 1 tháng',       'đầu', NULL),
  ('S_HA_TIME_AFTERNOON',  'Đau nhiều vào chiều tối',    'đầu', 'am_hư'),
  ('S_HA_RED_FLAG_THUNDER','Đau đầu dữ dội đột ngột',   'đầu', NULL);
```

---

## 5. Chỉ Số Hiệu Năng

### 5.1 Index cần thiết

```sql
CREATE INDEX idx_disease_symptoms_disease ON disease_symptoms(disease_id);
CREATE INDEX idx_disease_symptoms_symptom ON disease_symptoms(symptom_id);
CREATE INDEX idx_decision_edges_from ON decision_edges(from_node_id);
CREATE INDEX idx_decision_nodes_phase ON decision_nodes(phase);
CREATE INDEX idx_decision_nodes_complaint ON decision_nodes(chief_complaint_pattern);
```

### 5.2 Ước tính kích thước CSDL (giai đoạn đầu)

| Bảng | Số bản ghi dự kiến |
|------|-------------------|
| diseases | 200-500 bệnh |
| symptoms | 500-1000 triệu chứng |
| disease_symptoms | 5,000-20,000 quan hệ |
| decision_nodes | 500-2,000 nút |
| decision_edges | 1,000-5,000 cạnh |
| question_options | 2,000-8,000 lựa chọn |

Tổng: < 100 MB – rất nhỏ, hoàn toàn cache được trong RAM.
