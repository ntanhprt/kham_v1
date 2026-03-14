# Các Mode Tương Tác – Kiến Trúc Đa Mode

> **Vấn đề cốt lõi**: Toàn bộ thiết kế từ doc 01-07 chỉ mô tả **Mode 1**
> (Khám triệu chứng cấp tính). Nhưng một hệ thống hỗ trợ sức khỏe thực sự
> cần ít nhất **8 mode**, mỗi mode có kiến trúc reasoning hoàn toàn khác nhau.

---

## 1. Tổng Quan 8 Mode

```
┌─────────────────────────────────────────────────────────────────┐
│                    MODE SELECTOR (Cổng vào)                      │
│   "Hôm nay bạn cần hỗ trợ gì?"                                 │
└───────────────────────────┬─────────────────────────────────────┘
                            │
     ┌──────────┬───────────┼──────────┬──────────┬──────────┐
     ▼          ▼           ▼          ▼          ▼          ▼
  Mode 1     Mode 2      Mode 3     Mode 4     Mode 5    Mode 6-8
  Khám       Phòng       Quản lý    Tư vấn     Giải      (xem bên
  triệu      ngừa        bệnh       thuốc/     thích     dưới)
  chứng      /tầm soát   mãn tính   lối sống   kết quả
```

| Mode | Tên | Entry Trigger | Reasoning Engine |
|------|-----|--------------|-----------------|
| 1 | Khám triệu chứng | "Tôi bị [triệu chứng]" | Symptom → Disease (Forward) |
| 2 | Phòng ngừa / Tầm soát | "Kiểm tra định kỳ" / không có triệu chứng | Risk → Screening protocol |
| 3 | Quản lý bệnh mãn tính | "Tái khám [bệnh đã biết]" | Target → Gap → Adjustment |
| 4 | Tư vấn thuốc | "Tôi đang dùng / sắp dùng thuốc X" | Drug profile → Risk/Benefit |
| 5 | Tư vấn dinh dưỡng / lối sống | "Tôi muốn [cải thiện cụ thể]" | Goal → Baseline → Plan |
| 6 | Giải thích kết quả | "Kết quả xét nghiệm của tôi là..." | Result → Meaning → Action |
| 7 | Second opinion | "Tôi được chẩn đoán X, muốn xác nhận" | Debiased reassessment |
| 8 | Hỗ trợ quyết định điều trị | "Tôi cần chọn giữa A và B" | Trade-off presentation |

---

## 2. Mode 1: Khám Triệu Chứng (Đã Mô Tả Trong Doc 03, 07)

**Entry**: Bệnh nhân có triệu chứng rõ ràng
**Goal**: Tìm nguyên nhân, chẩn đoán, hướng điều trị
**Engine**: 9 luồng song song (đã mô tả trong doc 07)

*Phần còn lại xem [07_kien_truc_tu_duy_lam_sang.md](./07_kien_truc_tu_duy_lam_sang.md)*

---

## 3. Mode 2: Phòng Ngừa và Tầm Soát

### 3.1 Điểm Khác Biệt Cơ Bản

| | Mode 1 (Khám triệu chứng) | Mode 2 (Phòng ngừa) |
|--|--------------------------|---------------------|
| Entry | "Tôi bị đau đầu" | "Tôi muốn kiểm tra sức khỏe" |
| Câu hỏi chính | "Triệu chứng này là gì?" | "Nguy cơ nào tôi đang có?" |
| Data input | Triệu chứng hiện tại | Tuổi, giới, tiền sử, yếu tố nguy cơ |
| Output | Chẩn đoán + điều trị | Danh sách tầm soát + lịch trình |
| YHCT góc nhìn | Biện chứng bệnh | Thể trạng + phòng bệnh theo tạng |

### 3.2 Engine: Risk-Based Screening Protocol

```
Input:
  - Tuổi + Giới sinh học
  - Bệnh nền (nếu có)
  - Tiền sử gia đình
  - Thói quen (hút thuốc, rượu, BMI)
  - Kết quả tầm soát lần trước (nếu có)

Processing:
  1. Lookup bảng tầm soát theo tuổi/giới (guidelines-based)
  2. Điều chỉnh tần suất/ngưỡng dựa trên yếu tố nguy cơ cá nhân
  3. So sánh với những gì đã làm → tìm "gap"

Output:
  - Danh sách tầm soát nên làm ngay
  - Danh sách tầm soát định kỳ (6 tháng, 1 năm, 3 năm...)
  - Vaccine cần nhắc lại
  - Lời khuyên YHCT phòng bệnh theo thể trạng và mùa
```

### 3.3 Bảng Tầm Soát Theo Tuổi/Giới (CSDL Cần Có)

**Nam:**

| Tuổi | Tầm Soát | Tần Suất |
|------|---------|---------|
| 18+ | Huyết áp | Mỗi 2 năm (nếu bình thường) |
| 35+ | Cholesterol toàn phần | Mỗi 5 năm |
| 45+ | Đường huyết đói | Mỗi 3 năm |
| 50+ | Tầm soát ung thư đại tràng (nội soi) | Mỗi 10 năm |
| 55-80 (hút thuốc ≥30 gói-năm) | CT ngực liều thấp (ung thư phổi) | Hàng năm |
| 40+ (có yếu tố nguy cơ) | PSA + thăm trực tràng | Thảo luận với BS |

**Nữ:**

| Tuổi | Tầm Soát | Tần Suất |
|------|---------|---------|
| 21+ | Pap smear (ung thư cổ tử cung) | Mỗi 3 năm (hoặc 5 năm nếu kèm HPV test) |
| 40+ | Mammogram (ung thư vú) | Hàng năm hoặc 2 năm/lần |
| 65+ | Mật độ xương (loãng xương) | Mỗi 2 năm |
| Có thai | Đường huyết (GDM), GBS, v.v. | Theo thai kỳ |

**Chung:**

| Nhóm | Tầm Soát |
|------|---------|
| BMI > 25 + tuổi > 35 | HbA1c hàng năm |
| Hút thuốc | Spirometry, CT ngực |
| Uống rượu nhiều | Chức năng gan, ALT/AST |
| Tiền sử gia đình ung thư ruột < 60 tuổi | Nội soi bắt đầu từ 40 tuổi |
| Tiền sử gia đình bệnh tim sớm | Lipid profile, Calcium score |

### 3.4 YHCT: Phòng Bệnh Theo Thể Trạng và Mùa

```
Xuân (tháng 1-3 âm lịch) – Can kinh vượng:
→ Thể Can Khí Uất: dễ bùng phát trầm cảm, đau đầu, rối loạn kinh nguyệt
→ Phòng: dưỡng Can, tránh tức giận, tập thở, ăn xanh (vị chua nhẹ)

Hạ (tháng 4-6) – Tâm kinh vượng:
→ Thể Tâm Âm Hư: dễ mất ngủ, hồi hộp, say nắng
→ Phòng: dưỡng Tâm, tránh nắng gắt, ăn nhạt, nghỉ trưa

Thu (tháng 7-9) – Phế kinh vượng:
→ Thể Phế Khí Hư: dễ cảm lạnh, ho, khô da
→ Phòng: bổ Phế, tránh lạnh đột ngột, ăn trắng (vị cay nhẹ, ngó sen)

Đông (tháng 10-12) – Thận kinh vượng:
→ Thể Thận Dương Hư: dễ đau lưng, lạnh sâu, giảm libido
→ Phòng: ôn Thận, giữ ấm thắt lưng, ăn đen (vừng, đậu đen)
```

---

## 4. Mode 3: Quản Lý Bệnh Mãn Tính

### 4.1 Điểm Khác Biệt Cơ Bản

Đây **không phải khám**. Là **monitoring và adjustment**.

Không hỏi: "Bạn có vấn đề gì?"
Hỏi: "Mục tiêu lần trước đặt ra đã đạt chưa?"

```
TARGET → MEASURE → GAP → CAUSE → ADJUSTMENT
(Mục tiêu) → (Đo hiện tại) → (Chênh lệch) → (Lý do) → (Thay đổi)
```

### 4.2 Template Cho Từng Bệnh Mãn Tính

**Đái Tháo Đường Type 2:**

```
Targets cần theo dõi:
  - HbA1c < 7% (hoặc < 8% nếu người cao tuổi)
  - Huyết áp < 130/80 mmHg
  - LDL < 70 mg/dL (nếu có bệnh tim)
  - Microalbumin niệu (hàng năm)
  - Creatinine (hàng năm)
  - Đáy mắt (hàng năm)
  - Khám bàn chân (mỗi lần tái khám)

Câu hỏi theo dõi:
  1. "HbA1c lần gần nhất bao nhiêu? Khi nào?"
  2. "Huyết đường nhà bạn đo được thường bao nhiêu?"
  3. "Bạn có bỏ liều thuốc không?"
  4. "Chế độ ăn thay đổi không?"
  5. "Có triệu chứng hạ đường huyết không?" (đổ mồ hôi, run, hoa mắt)
  6. "Bàn chân có tê bì, vết thương không?"

Gap analysis:
  HbA1c 9.2% (target 7%) → GAP = 2.2%
  → Hỏi: Tuân thủ thuốc? Chế độ ăn? Stress? Nhiễm trùng?
  → YHCT: Tiêu khát = Vị nhiệt + Âm hư → bổ Vị âm, thanh nhiệt
  → Điều chỉnh: tăng liều? thêm thuốc? chuyển insulin?
```

**Tăng Huyết Áp:**

```
Targets:
  - BP < 130/80 mmHg (chung)
  - BP < 140/90 mmHg (người cao tuổi không có tiểu đường)
  - Creatinine/eGFR hàng năm
  - Kali (nếu dùng lợi tiểu)
  - ECG (tìm LVH, arrhythmia)

Câu hỏi:
  1. "Huyết áp nhà đo được trung bình bao nhiêu?"
  2. "Uống thuốc có đều không?"
  3. "Giảm muối, giảm rượu chưa?"
  4. "Thuốc có gây tác dụng phụ gì không?" (ho → ACE-I; phù chân → CCB)
  5. "Đau đầu, hoa mắt, tức ngực gần đây?"
```

### 4.3 YHCT Trong Quản Lý Mãn Tính

YHCT đặc biệt phù hợp ở mode này vì nó tập trung vào **cân bằng dài hạn** thay vì xử lý triệu chứng cấp:

```
ĐTĐ type 2 lâu năm (YHCT: Tiêu Khát Hậu Kỳ):
→ Theo dõi: sắc mặt xạm (Thận Hư), tóc rụng, lưng đau
→ Phác đồ hỗ trợ: bổ Thận âm (Lục vị) + hoạt huyết (phòng biến chứng mạch máu)
→ Theo dõi đáp ứng: lưỡi bớt đỏ, bớt khát, ngủ tốt hơn
```

---

## 5. Mode 4: Tư Vấn Thuốc

### 5.1 Ba Loại Câu Hỏi Mode 4

**Loại A: Thuốc sắp bắt đầu dùng**
```
Input: Tên thuốc (hoặc nhóm thuốc) + bệnh nền + thuốc đang dùng
Processing:
  1. Tác dụng phụ thường gặp cần biết trước
  2. Tương tác với thuốc đang dùng
  3. Thực phẩm cần tránh (VD: warfarin + rau xanh, statin + bưởi)
  4. Thời điểm uống tối ưu
  5. Cách biết thuốc có tác dụng (endpoint cần theo dõi)
  6. Dấu hiệu cần dừng ngay và báo bác sĩ
```

**Loại B: Đang dùng thuốc, gặp vấn đề**
```
Input: Thuốc X + triệu chứng mới Y
Processing:
  1. Liệu Y có phải tác dụng phụ của X không?
  2. Mức độ nghiêm trọng (dừng ngay / giảm liều / tiếp tục theo dõi / bình thường)
  3. Tương tác với thứ gì khác bắt đầu gần đây?
```

**Loại C: Tương tác YHHD × YHCT**
```
Đây là vùng quan trọng và ít được biết đến:
- Danshen (Đan Sâm) + Warfarin → tăng nguy cơ chảy máu nghiêm trọng
- St John's Wort (Liên kiều) + SSRI → serotonin syndrome
- Ginseng (Nhân sâm) + Warfarin, Aspirin → tăng chảy máu
- Cam thảo (Glycyrrhiza) dài ngày → giả aldosteronism, tăng huyết áp
- Ephedra (Ma hoàng) + MAOIs → tăng huyết áp khủng hoảng

Hệ thống phải hỏi: "Bạn có dùng thảo dược, thuốc nam, thực phẩm chức năng nào không?"
```

### 5.2 CSDL Cần Cho Mode 4

```sql
CREATE TABLE drug_interactions (
    drug_a          VARCHAR(100),
    drug_b          VARCHAR(100),  -- Có thể là thuốc YHHD hoặc thảo dược YHCT
    severity        VARCHAR(20),   -- 'contraindicated', 'major', 'moderate', 'minor'
    mechanism       TEXT,
    clinical_effect TEXT,
    management      TEXT
);

CREATE TABLE drug_food_interactions (
    drug_name       VARCHAR(100),
    food_item       VARCHAR(100),
    effect          TEXT,
    recommendation  TEXT
);

CREATE TABLE herb_drug_interactions (
    herb_name_vi    VARCHAR(100),
    herb_name_latin VARCHAR(100),
    drug_class      VARCHAR(100),
    severity        VARCHAR(20),
    effect          TEXT
);
```

---

## 6. Mode 5: Tư Vấn Dinh Dưỡng và Lối Sống

### 6.1 Điểm Khác Biệt

Không có bệnh cụ thể để chẩn đoán. Logic là:
```
MỤC TIÊU cụ thể → BASELINE hiện tại → GAP → KẾ HOẠCH thực tế
```

### 6.2 Framework Hỏi Mode 5

```
Bước 1 – Cụ thể hóa mục tiêu:
"Bạn muốn giảm cân" → "Bạn muốn giảm bao nhiêu kg, trong bao lâu, vì lý do gì?"
(Mục tiêu không cụ thể = không thể lập kế hoạch)

Bước 2 – Baseline thực tế:
Không hỏi "bạn ăn có khỏe không" mà hỏi:
→ "Hôm qua bạn ăn gì từ sáng đến tối?" (24-hour recall)
→ "Một tuần bạn tập thể dục mấy ngày, mỗi lần bao lâu?"
→ "Bạn ngủ mấy tiếng, ngủ lúc mấy giờ?"

Bước 3 – Phát hiện rào cản:
"Điều gì khiến bạn khó duy trì thói quen tốt?"
→ Không có thời gian (giải pháp: micro-habits)
→ Không có tiền (giải pháp: thực phẩm địa phương rẻ mà tốt)
→ Thiếu động lực (giải pháp: motivational interviewing, không phán xét)
→ Bệnh lý cản trở (→ chuyển sang Mode 1 hoặc 3 trước)

Bước 4 – Kế hoạch SMART:
Specific, Measurable, Achievable, Relevant, Time-bound
Không: "hãy ăn ít hơn"
Có: "thay cơm trắng bằng gạo lứt bữa tối, thứ 2-4-6"
```

### 6.3 YHCT Trong Mode 5

YHCT có lợi thế lớn trong mode này vì nó đề cập dinh dưỡng theo tính vị:

```
Thể Tỳ Hư (tiêu hóa yếu):
→ Ưu tiên: cháo gạo nếp, khoai lang, bí đỏ (ngọt, bổ Tỳ)
→ Tránh: đồ sống lạnh, nhiều đường, béo (làm khó tiêu)
→ Thời điểm: ăn đúng giờ, không ăn khuya (Tỳ vượng giờ Tý)

Thể Âm Hư:
→ Ưu tiên: ngô, lê, mộc nhĩ trắng, sữa (dưỡng âm sinh tân)
→ Tránh: cay nóng, nướng BBQ, cafein (hao âm)

Thể Đàm Thấp (béo phì):
→ Ưu tiên: ý dĩ, đông qua (bí xanh), hải tảo, rau cần (hóa đàm, lợi thấp)
→ Tránh: mỡ, đồ ngọt, rượu bia (sinh đàm)
```

---

## 7. Mode 6: Giải Thích Kết Quả Xét Nghiệm / Chẩn Đoán Hình Ảnh

### 7.1 Nhu Cầu Thực Tế

Bệnh nhân cầm kết quả trong tay, không hiểu con số có nghĩa gì, lo lắng không cần thiết hoặc ngược lại – bỏ qua kết quả bất thường nghiêm trọng.

### 7.2 Framework Xử Lý Mode 6

```
Input: Tên xét nghiệm + Giá trị + (tùy chọn) Giá trị tham chiếu

Processing:
  1. Normalize tên xét nghiệm → standard name
  2. Xác định bình thường/bất thường và mức độ
  3. Giải thích ý nghĩa bằng ngôn ngữ đơn giản
  4. Context: kết quả này có ý nghĩa gì trong bối cảnh bệnh nhân?
  5. Bước tiếp theo: không cần làm gì / tái kiểm tra / khám BS / cấp cứu

Output:
  Cấu trúc: Kết quả của bạn | Ý nghĩa | Bạn cần làm gì
```

### 7.3 Ví Dụ

```
User: "TSH của tôi là 8.2, phòng lab ghi bình thường là 0.4-4.0"

Output:
  Kết quả: TSH 8.2 mIU/L - CAO HƠN bình thường (ngưỡng 0.4-4.0)

  TSH là gì: Hormone từ não điều khiển tuyến giáp. TSH cao = não
  đang "thúc" tuyến giáp làm việc nhiều hơn vì tuyến giáp đang
  hoạt động yếu (suy giáp).

  Ý nghĩa: Kết quả này cho thấy tuyến giáp của bạn có thể đang
  hoạt động không đủ. Mức 8.2 là khá cao, cần được đánh giá.

  Triệu chứng bạn nên kiểm tra lại xem có không:
  → Mệt mỏi, lạnh tay chân, táo bón, da khô, tăng cân, trầm cảm

  Bạn cần làm gì:
  → TRONG 1-2 TUẦN: Đặt lịch khám bác sĩ nội tiết hoặc đa khoa
     với kết quả này. Không phải cấp cứu, nhưng không nên bỏ qua.
  → Bác sĩ có thể sẽ xét nghiệm thêm FT4 và kháng thể Anti-TPO.

  YHCT góc nhìn: TSH cao thường tương ứng Thận Dương Hư hoặc
  Tỳ Thận lưỡng hư. Người YHCT có thể hỗ trợ điều trị song song.

⚠️ Kết quả này chỉ mang tính tham khảo. Chẩn đoán chính xác
cần bác sĩ đánh giá toàn diện.
```

### 7.4 Danh Sách Xét Nghiệm Cần Có Trong CSDL

```
Nhóm máu cơ bản: CBC, CMP, Lipid panel, HbA1c, TSH/FT4
Gan: ALT, AST, GGT, ALP, Bilirubin, Albumin
Thận: Creatinine, BUN, eGFR, Uric acid, Microalbumin
Tim: Troponin, BNP/NT-proBNP, CK-MB
Tuyến giáp: TSH, FT4, FT3, Anti-TPO, Anti-Tg
Viêm: CRP, ESR, Procalcitonin
Nội tiết: Cortisol, Testosterone, FSH/LH, Estradiol, Insulin
Nước tiểu: Urinalysis, protein/creatinine ratio
Hình ảnh: Cách đọc báo cáo siêu âm, X-quang thông thường
```

---

## 8. Mode 7: Second Opinion

### 8.1 Thách Thức: Anchoring Bias

Khi bệnh nhân đã có chẩn đoán từ BS khác, hệ thống (và BS) có xu hướng:
- **Anchoring bias**: Xác nhận chẩn đoán cũ thay vì đánh giá lại thực sự
- **Confirmation bias**: Chỉ hỏi những câu ủng hộ chẩn đoán đã có

### 8.2 Protocol Debiased Second Opinion

```
Bước 1: THU THẬP CÓ CẤU TRÚC (không xem chẩn đoán cũ trước)
"Hãy kể lại từ đầu: Triệu chứng đầu tiên là gì? Khi nào?"
→ Lấy lại toàn bộ history như chưa biết gì

Bước 2: XÁC NHẬN hay NGHI NGỜ chẩn đoán cũ
"Bạn được chẩn đoán là [X]. Bây giờ hãy cho tôi biết..."
→ Điều gì ủng hộ chẩn đoán X? (check)
→ Điều gì KHÔNG khớp với X? (quan trọng hơn)
→ Có chẩn đoán phân biệt nào chưa được loại trừ?

Bước 3: ĐẶT CÂU HỎI "BÁC BỎ"
"Nếu KHÔNG phải X, thì có thể là gì?"
→ Liệt kê differential diagnosis thay thế
→ Hỏi các câu hỏi phân biệt

Bước 4: KẾT LUẬN MINH BẠCH
Option A: "Dữ liệu ủng hộ chẩn đoán [X] - đây là lý do..."
Option B: "Có điểm chưa rõ, nên xét nghiệm thêm [Y] để xác nhận"
Option C: "Cân nhắc thêm chẩn đoán [Z] vì [lý do cụ thể]"
```

---

## 9. Mode 8: Hỗ Trợ Quyết Định Điều Trị (Shared Decision Making)

### 9.1 Khi Nào Cần Mode Này

- Bệnh nhân biết mình bị bệnh gì, đang cân nhắc giữa các option điều trị
- Ví dụ: "Tôi bị thoát vị đĩa đệm, bác sĩ nói có thể phẫu thuật hoặc vật lý trị liệu"
- Ví dụ: "Tôi bị ung thư tuyến tiền liệt giai đoạn sớm, có 3 lựa chọn"

### 9.2 Framework Trình Bày Trade-Off

```
Cấu trúc output:

Option A: [Tên điều trị]
  Ưu điểm: [...]
  Nhược điểm / Rủi ro: [...]
  Phù hợp với ai: [...]
  Không phù hợp với ai: [...]
  YHCT quan điểm: [Nếu có]

Option B: [Tên điều trị]
  ...

Câu hỏi để xác định ưu tiên CỦA BỆNH NHÂN:
  "Điều gì quan trọng hơn với bạn: hồi phục nhanh hay tránh phẫu thuật?"
  "Bạn có thể dành [X] tuần cho vật lý trị liệu không?"
  "Tác dụng phụ nào bạn lo ngại nhất?"

Khuyến nghị cá nhân hóa:
  Dựa trên câu trả lời → "Với ưu tiên của bạn, Option [X] phù hợp hơn vì..."
```

---

## 10. Mode Selector – Cổng Vào Hệ Thống

### 10.1 Câu Hỏi Phân Loại Ban Đầu

```
"Hôm nay bạn cần hỗ trợ gì?"

A) Tôi đang có triệu chứng / khó chịu          → Mode 1
B) Tôi muốn kiểm tra sức khỏe định kỳ          → Mode 2
C) Tôi muốn theo dõi bệnh [đã có chẩn đoán]   → Mode 3
D) Tôi muốn hỏi về thuốc                        → Mode 4
E) Tôi muốn cải thiện chế độ ăn / lối sống     → Mode 5
F) Tôi có kết quả xét nghiệm muốn hiểu         → Mode 6
G) Tôi muốn ý kiến thứ hai về chẩn đoán của mình → Mode 7
H) Tôi cần giúp chọn giữa các phương án điều trị → Mode 8
```

### 10.2 Auto-Detection (Không Cần Bệnh Nhân Chọn)

Hệ thống có thể tự phát hiện mode từ câu đầu tiên:

```
"Tôi bị ho 3 ngày nay"           → Mode 1 (triệu chứng rõ)
"Tôi 50 tuổi, chưa tầm soát ung thư" → Mode 2 (phòng ngừa)
"HbA1c của tôi tuần trước là 8.5" → Mode 3 hoặc Mode 6
"Tôi uống metformin bị đau bụng"  → Mode 4 (tác dụng phụ thuốc)
"Tôi muốn giảm 5kg"               → Mode 5 (lối sống)
"Kết quả siêu âm của tôi là..."   → Mode 6 (giải thích)
"BS nói tôi bị [X], tôi không chắc" → Mode 7 (second opinion)
"Tôi đang cân nhắc phẫu thuật"    → Mode 8 (quyết định)
```

---

## 11. Hệ Quả Với Kiến Trúc Kỹ Thuật

### 11.1 Cập Nhật Cấu Trúc Thư Mục

```
src/
├── modes/
│   ├── mode1-symptom/         # Khám triệu chứng
│   │   ├── engine.ts
│   │   ├── decision-tree.ts
│   │   └── yhct-bienchung.ts
│   ├── mode2-preventive/      # Phòng ngừa
│   │   ├── screening-engine.ts
│   │   └── risk-calculator.ts
│   ├── mode3-chronic/         # Quản lý mãn tính
│   │   ├── monitoring-engine.ts
│   │   └── target-tracker.ts
│   ├── mode4-medication/      # Tư vấn thuốc
│   │   ├── drug-lookup.ts
│   │   └── interaction-check.ts
│   ├── mode5-lifestyle/       # Lối sống
│   │   ├── goal-setter.ts
│   │   └── plan-builder.ts
│   ├── mode6-results/         # Giải thích kết quả
│   │   └── lab-interpreter.ts
│   ├── mode7-second-opinion/  # Second opinion
│   │   └── debiased-engine.ts
│   └── mode8-decision/        # Shared decision making
│       └── tradeoff-presenter.ts
├── shared/
│   ├── mode-selector.ts       # Phân loại mode
│   ├── context-engine.ts      # Context (dùng chung)
│   └── red-flag-scanner.ts    # Red flags (dùng chung)
```

### 11.2 Bảng CSDL Mới Cần Thêm

```sql
-- Mode 2: Screening protocols
CREATE TABLE screening_protocols (
    id UUID PRIMARY KEY,
    condition_screened VARCHAR(100),
    target_age_min SMALLINT,
    target_age_max SMALLINT,
    target_gender CHAR(1),  -- 'M', 'F', 'A' (all)
    risk_factors JSONB,      -- Điều kiện bổ sung
    test_name VARCHAR(200),
    frequency_months SMALLINT,
    guideline_source VARCHAR(100)
);

-- Mode 3: Chronic disease targets
CREATE TABLE chronic_disease_targets (
    disease_code VARCHAR(50),
    target_name VARCHAR(100),
    target_value VARCHAR(100),
    unit VARCHAR(50),
    test_frequency_months SMALLINT,
    notes TEXT
);

-- Mode 4: Drug interactions
CREATE TABLE drug_interactions (...);
CREATE TABLE herb_drug_interactions (...);

-- Mode 6: Lab reference ranges
CREATE TABLE lab_references (
    test_name VARCHAR(100),
    test_name_vi VARCHAR(100),
    normal_min DECIMAL,
    normal_max DECIMAL,
    unit VARCHAR(50),
    gender CHAR(1),
    age_group VARCHAR(20),
    critical_low DECIMAL,
    critical_high DECIMAL,
    plain_language_explanation TEXT
);
```
