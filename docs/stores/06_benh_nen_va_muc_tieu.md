# Xử Lý Bệnh Nền và Mục Tiêu Sức Khỏe

## 1. Vấn Đề: Hai Loại Chief Complaint Khác Nhau

Hệ thống cần phân biệt ngay từ đầu:

```
LOẠI 1: TRIỆU CHỨNG (Symptom)
  → Người dùng đang có vấn đề cụ thể
  → Ví dụ: "đau đầu", "ho", "đau bụng"
  → Mục tiêu: tìm nguyên nhân gây triệu chứng

LOẠI 2: MỤC TIÊU SỨC KHỎE (Health Goal)
  → Người dùng muốn cải thiện tình trạng
  → Ví dụ: "giảm cân", "tăng cân", "ngủ ngon hơn", "tăng sinh lý"
  → Mục tiêu: tìm nguyên nhân gốc rễ CỦA tình trạng đó
             (không phải triệu chứng của mục tiêu)

LOẠI 3: KẾT HỢP
  → Ví dụ: "tôi béo và hay mệt mỏi"
  → Cần xử lý đồng thời cả hai
```

**Bảng nhận diện trong CSDL:**

```sql
ALTER TABLE decision_trees ADD COLUMN complaint_type VARCHAR(20);
-- 'symptom'      : Triệu chứng bệnh lý
-- 'health_goal'  : Mục tiêu sức khỏe
-- 'functional'   : Chức năng (VD: "cải thiện trí nhớ")
```

---

## 2. Sàng Lọc Bệnh Nền – Giai Đoạn Bắt Buộc

Với MỌI loại chief complaint, hệ thống phải có **Giai Đoạn 0: Sàng Lọc Bệnh Nền** chạy TRƯỚC tứ chẩn.

### 2.1 Lý Do

Ví dụ "muốn giảm cân":
- Nếu có **suy giáp** → giảm cân phải điều trị tuyến giáp trước, không phải ăn kiêng
- Nếu có **kháng insulin / tiền tiểu đường** → cần kiểm soát đường huyết
- Nếu có **hội chứng Cushing** → nguyên nhân hormon, cần chuyên khoa nội tiết
- Nếu có **trầm cảm / rối loạn lo âu** → thuốc có thể gây béo, cần xử lý tâm lý trước
- Nếu không có bệnh nền → mới là vấn đề ăn uống/sinh hoạt/tỳ vị hư

**Nếu bỏ qua bước này → chẩn đoán sai → lời khuyên nguy hiểm.**

### 2.2 Luồng Tổng Thể Đã Cập Nhật

```
[START] → Người dùng nhập chief complaint
    │
    ▼
[PHÂN LOẠI] Triệu chứng hay Mục tiêu?
    │
    ▼
[GĐ 0: SÀNG LỌC BỆNH NỀN] ← MỚI, BẮT BUỘC
    ├── Hỏi nhóm bệnh nội tiết
    ├── Hỏi nhóm bệnh tim mạch
    ├── Hỏi nhóm bệnh tâm thần
    ├── Hỏi nhóm thuốc đang dùng
    └── Kết quả: danh sách "bệnh nền có thể có"
    │
    ▼
[GĐ 1-4: TỨ CHẨN] Hỏi theo cây quyết định
    │ (cây được điều chỉnh dựa trên bệnh nền đã phát hiện)
    ▼
[GĐ 5: YHHD]
    │
    ▼
[KẾT QUẢ]
    ├── Bệnh nền (nếu phát hiện) → "Ưu tiên điều trị bệnh nền trước"
    └── Giải pháp cho mục tiêu (sau khi kiểm soát bệnh nền)
```

---

## 3. Ví Dụ Chi Tiết: "Muốn Giảm Cân"

### 3.1 Giai Đoạn 0 – Sàng Lọc Bệnh Nền

```
[BN-SÀNG-01] "Bạn có được chẩn đoán hoặc nghi ngờ mắc bệnh gì không?"
  (multiple choice)
  ├── Tiểu đường / tiền tiểu đường
  ├── Bệnh tuyến giáp (giáp to, suy giáp, cường giáp)
  ├── Huyết áp cao
  ├── Bệnh gan / mỡ gan
  ├── Hội chứng buồng trứng đa nang (PCOS) [nếu nữ]
  ├── Trầm cảm / lo âu / rối loạn tâm thần
  ├── Ngủ ngáy / ngưng thở khi ngủ
  └── Chưa có bệnh gì được chẩn đoán

[BN-SÀNG-02] "Bạn đang dùng thuốc gì không?"
  ├── Corticosteroid (prednisone, dexamethasone...)
  ├── Thuốc trầm cảm / an thần
  ├── Thuốc tiểu đường (insulin...)
  ├── Thuốc ngừa thai nội tiết
  ├── Không dùng thuốc gì
  └── Có nhưng không biết tên
```

### 3.2 Phân Nhánh Sau Sàng Lọc

**Nhánh A: Có bệnh nền rõ ràng**
```
Phát hiện: "Suy giáp" được chẩn đoán
→ Hỏi sâu về suy giáp:
  "Bạn đang điều trị suy giáp chưa? Dùng thuốc gì?"
  "TSH gần nhất bao nhiêu?"
→ Kết quả: "Cần kiểm soát suy giáp trước.
            Khi TSH ổn định, việc giảm cân sẽ thuận lợi hơn nhiều."
→ Vẫn tiếp tục hỏi để đưa thêm lời khuyên hỗ trợ
```

**Nhánh B: Nghi ngờ bệnh nền (chưa chẩn đoán)**
```
Phát hiện: Hay mệt mỏi + tăng cân không rõ lý do + lạnh tay chân + da khô
→ YHCT: Thận Dương Hư / Tỳ Dương Hư
→ YHHD: Nghi ngờ suy giáp / kháng insulin
→ Kết quả: "Trước khi giảm cân, nên xét nghiệm:
            - TSH, FT4 (tuyến giáp)
            - Đường huyết đói, HbA1c
            Nếu bình thường → tiếp tục kế hoạch giảm cân."
```

**Nhánh C: Không có bệnh nền**
```
→ Tiếp tục hỏi về lối sống, tỳ vị, đàm thấp...
→ Kết quả: Giải pháp YHCT + YHHD thuần túy cho giảm cân
```

### 3.3 Luồng Hỏi Đầy Đủ Cho "Giảm Cân" (Nhánh C)

```
[VỌNG-GC-01] "Mô tả vóc dáng của bạn:"
  ├── Béo đều toàn thân  → Tỳ Hư / Đàm Thấp
  ├── Béo vùng bụng      → Can Khí Uất / Đàm Thấp
  ├── Béo vùng mông-đùi  → Thận Hư / Thấp Nhiệt
  └── Béo vùng mặt-cổ   → Nghi Cushing (RED FLAG → xét nghiệm nội tiết)

[VẤN-GC-01] "Bạn tăng cân từ khi nào?"
  ├── Từ nhỏ             → Thể trạng di truyền / cơ địa
  ├── Sau sinh           → Khí huyết hư hậu sản
  ├── Sau tuổi 30-40     → Thận khí suy giảm sinh lý
  ├── Sau stress lớn     → Can khí uất → ảnh hưởng Tỳ Vị
  └── Gần đây, không rõ lý do → Cần sàng lọc nội tiết

[VẤN-GC-02] "Chế độ ăn của bạn thế nào?"
  ├── Ăn ít vẫn béo     → Tỳ Hư không vận hóa / nội tiết
  ├── Ăn nhiều, thích đồ ngọt → Đàm Thấp / Vị Nhiệt
  ├── Thèm ăn đêm       → Can Hỏa / Tâm Thận bất giao
  └── Ăn bình thường    → Vận động kém / chuyển hóa chậm

[VẤN-GC-03] "Bạn có các triệu chứng này không?" (multiple)
  ├── Hay mệt mỏi, thiếu năng lượng → Tỳ Khí Hư
  ├── Chân tay lạnh                  → Thận Dương Hư
  ├── Hay lo lắng, ăn nhiều hơn khi stress → Can Tỳ bất hòa
  ├── Kinh nguyệt không đều          → Can Thận Hư / Huyết Hư
  ├── Ngủ nhiều, nặng đầu            → Đàm Thấp
  └── Không có triệu chứng gì đặc biệt

[VẤN-GC-04] "Giấc ngủ thế nào?"
  ├── Ngủ ngáy, hay bị ngưng thở    → RED FLAG: Ngưng thở khi ngủ
  ├── Khó ngủ, trằn trọc            → Tâm Thận bất giao
  ├── Ngủ nhiều vẫn mệt             → Tỳ Hư Đàm Thấp / Suy giáp
  └── Ngủ bình thường

[VẤN-GC-05] "Vận động của bạn thế nào?"
  ├── Ngồi nhiều, ít vận động       → Khí Trệ Huyết Ứ
  ├── Tập nhiều nhưng không giảm    → Tỳ Hư / nội tiết
  └── Vận động vừa phải

→ PATTERN MATCHING → KẾT QUẢ
```

---

## 4. Danh Sách Mục Tiêu Sức Khỏe Cần Xây Dựng Cây Riêng

| Mục Tiêu | Bệnh Nền Cần Sàng Lọc |
|----------|----------------------|
| Giảm cân | Suy giáp, tiểu đường, Cushing, trầm cảm, PCOS, ngưng thở khi ngủ |
| Tăng cân | Cường giáp, bệnh viêm ruột, ung thư, trầm cảm, tiểu đường type 1 |
| Cải thiện sinh lý nam | Tiểu đường, tim mạch, huyết áp, trầm cảm, thiếu testosterone |
| Cải thiện sinh lý nữ | Rối loạn nội tiết, PCOS, suy giáp, trầm cảm, mãn kinh sớm |
| Ngủ ngon hơn | Ngưng thở khi ngủ, trầm cảm, lo âu, đau mạn tính, suy giáp |
| Tăng năng lượng | Thiếu máu, suy giáp, tiểu đường, trầm cảm, bệnh gan |
| Cải thiện trí nhớ | Tiểu đường, huyết áp, trầm cảm, suy giáp, thiếu vitamin B12 |
| Giảm đau khớp | Gout, viêm khớp dạng thấp, lupus, bệnh tuyến giáp |

---

## 5. Cập Nhật CSDL Cần Thiết

### 5.1 Bảng Mới: `comorbidity_screens`

```sql
CREATE TABLE comorbidity_screens (
    id              UUID PRIMARY KEY,
    chief_complaint_pattern VARCHAR(200),  -- Áp dụng cho chief complaint nào
    screen_question TEXT NOT NULL,          -- Câu hỏi sàng lọc
    screen_type     VARCHAR(30),
    -- 'known_diagnosis'   : Bệnh đã được chẩn đoán
    -- 'current_medication': Thuốc đang dùng
    -- 'recent_change'     : Thay đổi gần đây
    display_order   SMALLINT
);

CREATE TABLE comorbidity_findings (
    id              UUID PRIMARY KEY,
    screen_id       UUID REFERENCES comorbidity_screens(id),
    finding_text    VARCHAR(200),    -- VD: "Suy giáp"
    finding_code    VARCHAR(50),     -- VD: "HYPOTHYROID"
    priority        VARCHAR(20),     -- 'red_flag', 'high', 'medium', 'low'
    action          VARCHAR(30),
    -- 'redirect_tree'  : Chuyển sang cây khác
    -- 'add_warning'    : Thêm cảnh báo vào kết quả
    -- 'add_context'    : Thêm ngữ cảnh, điều chỉnh câu hỏi tiếp theo
    redirect_tree_id UUID REFERENCES decision_trees(id)
);
```

### 5.2 Bảng Mới: `decision_trees`

```sql
-- Nhóm các cây quyết định theo chief complaint
CREATE TABLE decision_trees (
    id              UUID PRIMARY KEY,
    code            VARCHAR(50) UNIQUE,  -- VD: "TREE_WEIGHT_LOSS"
    name_vi         VARCHAR(200),
    complaint_type  VARCHAR(20),  -- 'symptom', 'health_goal', 'functional'
    entry_node_id   UUID REFERENCES decision_nodes(id),
    comorbidity_screen_required BOOLEAN DEFAULT true
);
```

---

## 6. Nguyên Tắc Quan Trọng

> **"Bệnh nền là nguyên nhân gốc rễ của hầu hết mọi vấn đề sức khỏe."**
>
> Hệ thống phải luôn hỏi: **"Tại sao bệnh nhân có tình trạng này?"**
> trước khi hỏi: **"Tình trạng này như thế nào?"**
>
> Sai lầm phổ biến: Khuyên bệnh nhân béo ăn kiêng tập thể dục,
> trong khi nguyên nhân thật sự là suy giáp chưa được điều trị.
> → Vô ích, thậm chí gây hại (kiệt sức không giảm cân được).
