# Tổng Quan Hệ Thống Khám Bệnh Tổng Quát

## 1. Mục Tiêu

Xây dựng web application mô phỏng quy trình khám bệnh của bác sĩ thực sự, áp dụng nguyên lý **YHCT-first** (Y học cổ truyền làm nền tảng, Y học hiện đại bổ sung), đảm bảo:

- Khám được mọi loại bệnh
- Hỏi bệnh có hệ thống, xoáy vào triệu chứng cụ thể
- Tính **tái hiện** (reproducibility): cùng triệu chứng → cùng kết quả, mọi lúc
- Tính **nhất quán** (consistency): logic hỏi-đáp được định nghĩa tường minh, không phụ thuộc ngẫu nhiên

---

## 2. Nguyên Tắc Cốt Lõi

### 2.1 YHCT-First
1. **Bước 1 – Tứ chẩn YHCT**: Vọng (nhìn), Văn (nghe/ngửi), Vấn (hỏi), Thiết (sờ/mạch)
2. **Bước 2 – Biện chứng YHCT**: Xác định Tạng-Phủ, Âm-Dương, Hàn-Nhiệt, Hư-Thực, Khí-Huyết-Tân dịch
3. **Bước 3 – Bổ sung YHHD**: Phân tích triệu chứng theo sinh lý-bệnh lý hiện đại
4. **Bước 4 – Tổng hợp kết quả**: Kết luận tích hợp hai hệ thống

### 2.2 Tính Tái Hiện
- Toàn bộ logic hỏi bệnh được lưu dưới dạng **cây quyết định tĩnh** (decision tree) trong CSDL
- Không dùng AI sinh câu hỏi tự do → không có biến thể ngẫu nhiên
- AI chỉ dùng để **đối chiếu pattern** từ tập triệu chứng đã thu thập → tra CSDL → cho kết quả

### 2.3 An Toàn Y Tế
- Mọi kết quả phải kèm **cảnh báo**: "Đây là hỗ trợ tư vấn, không thay thế khám bác sĩ"
- Các trường hợp khẩn cấp (đau ngực dữ dội, khó thở cấp...) → cảnh báo đỏ, chuyển cấp cứu ngay
- Không kê đơn thuốc cụ thể, chỉ gợi ý hướng điều trị

---

## 3. Các Thành Phần Hệ Thống

```
┌─────────────────────────────────────────────────────────┐
│                    WEB APPLICATION                       │
├──────────────┬──────────────────┬───────────────────────┤
│   Frontend   │   Backend API    │      Database         │
│  (Chat UI)   │  (Logic Engine)  │   (Knowledge Base)    │
├──────────────┼──────────────────┼───────────────────────┤
│ - Giao diện  │ - Session mgmt   │ - Triệu chứng         │
│   hỏi-đáp   │ - Decision tree  │ - Bệnh lý             │
│ - Hiển thị  │   traversal      │ - Cây quyết định      │
│   kết quả   │ - Pattern match  │ - Cách điều trị       │
│ - Lịch sử   │ - Result builder │ - Lời khuyên          │
└──────────────┴──────────────────┴───────────────────────┘
```

### 3.1 Frontend
- Giao diện chat giống bác sĩ hỏi bệnh
- Hiển thị tiến trình khám (bước 1/4, 2/4...)
- Báo cáo kết quả có cấu trúc rõ ràng

### 3.2 Backend Logic Engine
- **Session Manager**: theo dõi trạng thái hỏi-đáp từng phiên khám
- **Decision Tree Traversal**: duyệt cây hỏi bệnh từ CSDL
- **Pattern Matcher**: khớp tập triệu chứng thu thập → danh sách bệnh có thể
- **Result Builder**: tổng hợp kết quả YHCT + YHHD

### 3.3 Knowledge Base (Cơ Sở Tri Thức)
- Bộ dữ liệu bệnh, triệu chứng, nguyên nhân, điều trị
- Cây quyết định hỏi bệnh cho từng nhóm triệu chứng
- Ánh xạ triệu chứng → bệnh (có trọng số)

---

## 4. Luồng Hoạt Động Tổng Thể

```
Người dùng mô tả triệu chứng chính
        ↓
Phân loại: Triệu chứng bệnh lý hay Mục tiêu sức khỏe?
  (VD: "đau đầu" = triệu chứng | "giảm cân" = mục tiêu)
        ↓
GĐ 0: SÀNG LỌC BỆNH NỀN ← BẮT BUỘC với mọi loại complaint
  - Bệnh đã chẩn đoán?
  - Thuốc đang dùng?
  - Thay đổi gần đây?
  → Nếu phát hiện bệnh nền → điều chỉnh cây hỏi + kết quả
        ↓
GĐ 1-4: Hỏi theo cây quyết định YHCT
  (Tứ chẩn: Vọng → Văn → Vấn → Thiết)
        ↓
Thu thập đủ thông tin → Pattern matching
        ↓
Biện chứng YHCT (Tạng-Phủ, Âm-Dương...)
        ↓
Phân tích bổ sung YHHD
        ↓
Tổng hợp kết quả:
  - Bệnh nền phát hiện + ưu tiên xử lý (nếu có)
  - Chẩn đoán (YHCT + YHHD)
  - Nguyên nhân gốc rễ
  - Hướng điều trị
  - Lời khuyên sống
  - Cảnh báo (nếu có)
```

---

## 5. Đánh Giá Khả Thi

| Yếu Tố | Đánh Giá | Ghi Chú |
|--------|----------|---------|
| Kỹ thuật | **Khả thi cao** | Công nghệ sẵn có, không cần AI phức tạp |
| Nội dung y học | **Khó nhất** | Cần chuyên gia YHCT + YHHD biên soạn CSDL |
| Chi phí | **Trung bình** | Chủ yếu là công sức biên soạn tri thức |
| Thời gian | **Dài** | CSDL tri thức cần 6-12 tháng để đủ dùng |
| Pháp lý | **Cần chú ý** | Phải có disclaimer y tế rõ ràng |

---

## 6. Tài Liệu Liên Quan

- [01_kien_truc_he_thong.md](./01_kien_truc_he_thong.md) – Kiến trúc kỹ thuật chi tiết
- [02_co_so_du_lieu.md](./02_co_so_du_lieu.md) – Thiết kế CSDL
- [03_cay_quyet_dinh.md](./03_cay_quyet_dinh.md) – Logic cây hỏi bệnh
- [04_cong_nghe.md](./04_cong_nghe.md) – Stack công nghệ đề xuất
- [05_lo_trinh_thuc_hien.md](./05_lo_trinh_thuc_hien.md) – Lộ trình xây dựng
- [06_benh_nen_va_muc_tieu.md](./06_benh_nen_va_muc_tieu.md) – Xử lý bệnh nền và mục tiêu sức khỏe
