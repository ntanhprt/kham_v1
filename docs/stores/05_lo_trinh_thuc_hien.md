# Lộ Trình Thực Hiện

## 1. Tổng Quan

Dự án này có **2 thách thức song song**:
1. **Kỹ thuật** – Xây dựng phần mềm (lập trình viên làm)
2. **Nội dung y học** – Biên soạn knowledge base (bác sĩ làm)

Thách thức #2 **khó hơn và tốn thời gian hơn** #1.

---

## 2. Các Giai Đoạn

### GIAI ĐOẠN 0: Chuẩn Bị (Trước khi code)

**Mục tiêu**: Có đủ tri thức y học để bắt đầu xây dựng.

**Việc cần làm:**

1. **Tìm chuyên gia y tế cộng tác**
   - Ít nhất 1 bác sĩ YHCT
   - Ít nhất 1 bác sĩ YHHD (hoặc nội khoa tổng quát)
   - Họ sẽ biên soạn và review nội dung CSDL

2. **Chọn 5-10 bệnh phổ biến để bắt đầu**
   - Đau đầu (headache)
   - Ho (cough)
   - Đau bụng (abdominal pain)
   - Mệt mỏi (fatigue)
   - Mất ngủ (insomnia)
   - Đau lưng (back pain)
   - Đau khớp (joint pain)
   - Ăn uống kém (poor appetite)

3. **Thiết kế template cây quyết định**
   - Bác sĩ điền template (không cần biết code)
   - Lập trình viên convert vào CSDL

**Template Cho Bác Sĩ:**
```
BỆNH: [Tên bệnh]
MÃ: [HA001]
YHCT: [Tên YHCT]
YHHD: [Tên YHHD]
TẠNG PHỦ: [Can/Tâm/Tỳ/Phế/Thận]

TRIỆU CHỨNG CHẨN ĐOÁN:
  Bắt buộc:
    1. [Triệu chứng] - Trọng số: [0.0-1.0]
  Hỗ trợ:
    2. [Triệu chứng] - Trọng số: [0.0-1.0]

CÂU HỎI VỌNG:
  Q1: [Câu hỏi]
      A: [Trả lời 1] → Triệu chứng ghi nhận: [TC]
      B: [Trả lời 2] → Triệu chứng ghi nhận: [TC]

CÂU HỎI VẤN:
  ...

ĐIỀU TRỊ YHCT:
  Nguyên tắc: [Bình Can tiềm Dương...]
  Huyệt vị: [Thái xung, Phong trì...]
  Sinh hoạt: [...]

ĐIỀU TRỊ YHHD:
  Hướng điều trị: [...]
  Chuyên khoa nên khám: [...]

CẢNH BÁO:
  Red flags: [Dấu hiệu nguy hiểm]
```

---

### GIAI ĐOẠN 1: MVP (Minimum Viable Product)

**Mục tiêu**: Web chạy được với 5 nhóm bệnh.

**Sprint 1 – Nền Tảng CSDL (2 tuần)**
- [ ] Thiết kế và tạo schema PostgreSQL
- [ ] Viết script nhập dữ liệu từ template bác sĩ
- [ ] Nhập dữ liệu cho bệnh "Đau đầu" (test case đầu tiên)
- [ ] Viết unit test cho pattern matching

**Sprint 2 – Backend Core (2 tuần)**
- [ ] Session Service (tạo, lưu, đọc phiên)
- [ ] Decision Tree traversal engine
- [ ] Pattern matching algorithm
- [ ] API endpoints (start, answer, result)
- [ ] Test với dữ liệu đau đầu

**Sprint 3 – Frontend (2 tuần)**
- [ ] Giao diện chat cơ bản
- [ ] Component hỏi-đáp (buttons cho options)
- [ ] Thanh tiến trình
- [ ] Trang kết quả

**Sprint 4 – Tích Hợp & Test (1 tuần)**
- [ ] End-to-end test toàn bộ luồng
- [ ] Nhập thêm 4 bệnh (ho, đau bụng, mệt mỏi, mất ngủ)
- [ ] Review với bác sĩ

**Sprint 5 – Deploy MVP (1 tuần)**
- [ ] Deploy lên Vercel + Supabase
- [ ] Setup domain
- [ ] Monitoring cơ bản

**Kết quả Giai Đoạn 1**: Web chạy được, có 5 bệnh, bác sĩ review và confirm kết quả đúng.

---

### GIAI ĐOẠN 2: Mở Rộng Nội Dung

**Mục tiêu**: Đủ 50-100 bệnh phổ biến.

**Nhóm bệnh ưu tiên:**

| Nhóm | Bệnh |
|------|------|
| Đầu-cổ | Đau đầu, chóng mặt, ù tai, đau cổ |
| Hô hấp | Ho, khó thở, viêm mũi, viêm họng |
| Tiêu hóa | Đau bụng, tiêu chảy, táo bón, trào ngược |
| Tim mạch | Hồi hộp, tức ngực, phù chân |
| Cơ xương khớp | Đau lưng, đau khớp, tê bì |
| Tổng quát | Mệt mỏi, sốt, sụt cân, mất ngủ |
| Tâm thần kinh | Lo âu, trầm cảm, stress |
| Da liễu | Mẩn ngứa, nổi mề đay |

**Ước tính**: Mỗi bác sĩ có thể biên soạn 2-3 bệnh/tuần.
Với 2 bác sĩ × 3 bệnh × 12 tháng = ~70 bệnh.

---

### GIAI ĐOẠN 3: Nâng Cao

**Mục tiêu**: Trải nghiệm tốt hơn, chính xác hơn.

- [ ] Admin Panel cho bác sĩ tự cập nhật CSDL (không cần lập trình viên)
- [ ] Thống kê: bệnh nào được hỏi nhiều nhất, kết quả có đúng không
- [ ] Phản hồi từ người dùng: "Kết quả có đúng không?" → cải thiện trọng số
- [ ] Tích hợp AI phân loại chief complaint (NLP)
- [ ] Hỗ trợ mobile (Progressive Web App)
- [ ] Xuất kết quả dạng PDF

---

## 3. Phân Công Công Việc

### Nếu Làm Một Mình

| Vai Trò | Giải Pháp |
|---------|-----------|
| Lập trình viên | Tự làm (học Next.js nếu cần) |
| Bác sĩ YHCT | Tìm cộng tác viên/advisor |
| Bác sĩ YHHD | Tìm cộng tác viên/advisor |
| Thiết kế UI | Dùng template có sẵn (shadcn/ui) |

### Nếu Có Đội

| Vai Trò | Số Lượng |
|---------|---------|
| Frontend Dev | 1 |
| Backend Dev | 1 |
| Bác sĩ YHCT biên soạn | 1-2 |
| Bác sĩ YHHD review | 1 |
| Product Manager | 1 (có thể là bạn) |

---

## 4. Rủi Ro và Cách Giảm Thiểu

| Rủi Ro | Mức Độ | Cách Xử Lý |
|--------|--------|------------|
| Không tìm được bác sĩ cộng tác | Cao | Tiếp cận trường y, hội YHCT |
| CSDL tri thức không đủ lớn | Cao | Bắt đầu nhỏ, mở rộng dần |
| Chẩn đoán sai → người dùng tự điều trị sai | Rất cao | Disclaimer rõ ràng, không tự ý điều trị |
| Pháp lý y tế | Trung bình | Tư vấn luật, rõ ràng là "hỗ trợ" không phải "thay thế" bác sĩ |
| Người dùng nhập sai triệu chứng | Thấp | Câu hỏi có options cố định, giảm sai sót |

---

## 5. Câu Hỏi Cần Trả Lời Trước Khi Bắt Đầu

1. **Bạn có kỹ năng lập trình không?** (JavaScript/Python/khác)
2. **Bạn có quan hệ với bác sĩ YHCT hoặc YHHD không?**
3. **Mục tiêu là sản phẩm thương mại hay phi lợi nhuận?**
4. **Bạn có thể đầu tư bao nhiêu thời gian mỗi tuần?**

Câu trả lời sẽ ảnh hưởng lớn đến lộ trình thực tế.

---

## 6. Đánh Giá Tóm Tắt

> **Có thể làm được không?**
>
> **CÓ** – nhưng cần:
> - Kỹ năng lập trình (hoặc học)
> - Bác sĩ cộng tác (bắt buộc, không thể thay thế)
> - Kiên nhẫn xây dựng CSDL tri thức
> - Tuân thủ đạo đức y tế (disclaimer, không kê đơn)
>
> **Khó nhất không phải kỹ thuật, mà là nội dung y học.**
> Một hệ thống tốt cần bác sĩ có chuyên môn YHCT lẫn YHHD
> ngồi xuống và hệ thống hóa tri thức vào template.
