# Logic Cây Quyết Định Hỏi Bệnh

## 1. Tổng Quan

Cây quyết định (Decision Tree) là linh hồn của hệ thống. Nó quy định:
- **Hỏi gì** – câu hỏi nào
- **Hỏi khi nào** – điều kiện để hỏi
- **Hỏi theo thứ tự nào** – luồng điều hướng

Toàn bộ cây được lưu trong CSDL, không hard-code trong code.

---

## 2. Cấu Trúc 4 Giai Đoạn (Tứ Chẩn YHCT)

```
GIAI ĐOẠN 1: VỌNG (Quan sát)
├── Sắc mặt (đỏ/xanh/vàng/trắng/đen)
├── Ánh mắt (sáng/mờ/đỏ)
├── Lưỡi (màu, rêu lưỡi, hình dáng)
└── Dáng người, tinh thần tổng thể

GIAI ĐOẠN 2: VĂN (Nghe/Ngửi) [*)
├── Giọng nói (to/nhỏ/khàn)
├── Hơi thở (nặng/nhẹ/mùi)
└── Tiếng ho (khô/đàm/mạnh/yếu)

GIAI ĐOẠN 3: VẤN (Hỏi) ← Giai đoạn chính
├── Triệu chứng chính (từ chief complaint)
├── Các triệu chứng kèm theo
├── Thời gian, diễn biến
├── Yếu tố tăng/giảm
├── Tiền sử bệnh
└── Sinh hoạt (ngủ, ăn, tiêu tiểu)

GIAI ĐOẠN 4: THIẾT (Sờ/Mạch) [*]
├── Mạch (Phù/Trầm, Hoạt/Sáp, Sác/Trì, Hư/Thực...)
└── Vùng đau (đau khi ấn/không đau)

(*) Các giai đoạn Văn và Thiết được thực hiện qua mô tả của user
    vì đây là ứng dụng web, không có bác sĩ trực tiếp khám.

GIAI ĐOẠN 5: BỔ SUNG YHHD
└── Các câu hỏi bổ sung từ góc độ y học hiện đại
```

---

## 3. Ví Dụ Chi Tiết: Cây Hỏi Bệnh "Đau Đầu"

### 3.1 Luồng Tổng Thể

```
[START] → Nhận "đau đầu" từ user
    │
    ▼
[VỌNG-ĐẦ-01] "Bạn có thể mô tả sắc mặt gần đây?"
    ├── "Đỏ, bừng nóng"     → ghi TC: Mặt đỏ (can_duong)
    ├── "Xanh xao, nhợt nhạt" → ghi TC: Mặt xanh (huyet_hư)
    ├── "Vàng vọt"           → ghi TC: Mặt vàng (ty_hư)
    └── "Bình thường"        → không ghi TC đặc biệt
    │
    ▼
[VỌNG-ĐẦ-02] "Lưỡi bạn thường có màu gì, rêu lưỡi như thế nào?"
    ├── "Đỏ, rêu vàng"      → ghi TC: Lưỡi đỏ-rêu vàng (nhiệt chứng)
    ├── "Nhạt, rêu trắng"   → ghi TC: Lưỡi nhạt-rêu trắng (hàn/hư)
    ├── "Tím, rêu dày"      → ghi TC: Lưỡi tím-rêu dày (ứ huyết/đàm)
    └── "Không để ý"        → skip
    │
    ▼
[VẤN-ĐẦ-01] "Bạn bị đau đầu bao lâu rồi?"
    ├── "Vừa mới đau (< 1 ngày)"  → ghi TC: đau đầu cấp tính
    ├── "Vài ngày đến 1 tuần"     → ghi TC: đau đầu bán cấp
    ├── "1 tuần đến 1 tháng"      → ghi TC: đau đầu mạn tính nhẹ
    └── "Hơn 1 tháng"             → ghi TC: đau đầu mạn tính
    │
    ▼
[VẤN-ĐẦ-02] "Đau ở vị trí nào trên đầu?"
    ├── "Vùng đỉnh đầu"          → ghi TC: đau đỉnh (kinh Can)
    ├── "Hai bên thái dương"      → ghi TC: đau thái dương (kinh Đởm)
    ├── "Vùng trán"              → ghi TC: đau trán (kinh Vị)
    ├── "Vùng chẩm (sau gáy)"    → ghi TC: đau chẩm (kinh Thận/Bàng quang)
    └── "Lan tỏa khắp đầu"      → ghi TC: đau lan tỏa
    │
    ▼
[VẤN-ĐẦ-03] "Tính chất đau như thế nào?"
    ├── "Đau nhói, theo nhịp tim" → ghi TC: đau mạch (ứ huyết/can dương)
    ├── "Đau căng, như bị ép"    → ghi TC: đau căng (can khí uất)
    ├── "Đau âm ỉ, nặng đầu"    → ghi TC: đau âm ỉ (hư chứng)
    └── "Đau dữ dội, đột ngột"   → GHI TC RED FLAG → [CẢNH BÁO KHẨN CẤP]
    │
    ▼ (nếu không phải red flag)
[VẤN-ĐẦ-04] "Đau đầu vào thời điểm nào?"
    ├── "Sáng sớm mới thức dậy"  → ghi TC: đau buổi sáng (đàm thấp/huyết áp)
    ├── "Chiều tối, sau làm việc" → ghi TC: đau chiều tối (can dương/âm hư)
    ├── "Ban đêm"                → ghi TC: đau ban đêm (âm hư/huyết hư)
    └── "Không cố định"          → ghi TC: đau không theo quy luật
    │
    ▼
[VẤN-ĐẦ-05] "Có triệu chứng nào đi kèm không?" (multiple choice)
    ├── "Chóng mặt"          → ghi TC: chóng mặt
    ├── "Buồn nôn/nôn"       → ghi TC: buồn nôn
    ├── "Mắt mờ, hoa mắt"    → ghi TC: rối loạn thị giác
    ├── "Ù tai"              → ghi TC: ù tai (thận hư)
    ├── "Cổ vai gáy cứng"    → ghi TC: cứng gáy
    ├── "Khô miệng, khát"    → ghi TC: miệng khô (âm hư)
    └── "Không có"           → tiếp tục
    │
    ▼
[VẤN-ĐẦ-06] "Đau đầu có liên quan đến stress/áp lực không?"
    ├── "Có, hay bị stress"  → ghi TC: can khí uất
    ├── "Đôi khi"           → ghi TC nhẹ
    └── "Không"             → tiếp tục
    │
    ▼
[VẤN-ĐẦ-07] "Giấc ngủ của bạn như thế nào?"
    ├── "Khó ngủ, mất ngủ"       → ghi TC: mất ngủ (tâm thận bất giao)
    ├── "Ngủ nhiều, nặng đầu"    → ghi TC: đàm thấp
    └── "Ngủ bình thường"        → không ghi TC
    │
    ▼
[THIẾT-ĐẦ-01] "Mạch đập của bạn như thế nào? (Thử bắt mạch hoặc mô tả cảm giác)"
    ├── "Nhanh, căng"        → ghi TC: mạch Huyền Sác (can hỏa)
    ├── "Chậm, yếu"          → ghi TC: mạch Trầm Trì (hư hàn)
    └── "Không biết bắt mạch" → skip (không bắt buộc)
    │
    ▼
[YHHD-ĐẦ-01] "Bạn có tiền sử bệnh gì không?"
    ├── "Huyết áp cao"       → ghi TC: tiền sử THA
    ├── "Tiểu đường"         → ghi TC: tiền sử ĐTĐ
    ├── "Tai biến/đột quỵ"   → ghi TC: tiền sử TBMMN (RED FLAG)
    └── "Không có"           → tiếp tục
    │
    ▼
[YHHD-ĐẦ-02] "Bạn có uống đủ nước không? Chế độ ăn thế nào?"
    ├── "Ít uống nước"        → ghi TC: mất nước
    ├── "Ăn nhiều cay nóng"   → ghi TC: ăn nhiệt (trợ dương)
    └── "Bình thường"         → tiếp tục
    │
    ▼
[HOÀN THÀNH] → Pattern Matching → Kết Quả
```

### 3.2 Cạnh RED FLAG – Cảnh Báo Khẩn Cấp

```
Nếu phát hiện bất kỳ dấu hiệu nào sau:
- Đau đầu "sét đánh" – dữ dội đột ngột (worst headache of life)
- Đau đầu + sốt cao + cứng cổ
- Đau đầu sau chấn thương đầu
- Đau đầu + yếu liệt tay chân/miệng méo

→ DỪNG HỎI NGAY
→ Hiển thị cảnh báo đỏ:
   "CẢNH BÁO: Triệu chứng của bạn có thể là dấu hiệu của tình trạng
    nguy hiểm đến tính mạng (xuất huyết não, viêm màng não...).
    Hãy đến cấp cứu NGAY LẬP TỨC hoặc gọi 115."
→ KHÔNG tiếp tục khám
```

---

## 4. Quy Tắc Pattern Matching

### 4.1 Công Thức Tính Điểm

```
Với bộ triệu chứng thu thập được S = {s1, s2, ..., sn}
Với mỗi bệnh D trong danh sách bệnh phù hợp với chief complaint:

  score(D) = (Σ weight(si, D) × confidence(si)) / total_weight_required(D)

  Trong đó:
  - weight(si, D)       = trọng số của triệu chứng si với bệnh D (0.0-1.0)
  - confidence(si)       = độ tin cậy từ câu trả lời của user (0.0-1.0)
  - total_weight_required = tổng trọng số của tất cả triệu chứng bắt buộc

Bệnh được chọn khi score >= 0.6
Chẩn đoán chính: bệnh có score cao nhất
Chẩn đoán phân biệt: các bệnh có score >= 0.4
```

### 4.2 Ví Dụ Tính Điểm

Bệnh "Can Dương Thượng Cang" (HA001) có các triệu chứng với trọng số:

| Triệu chứng | Trọng số | Bắt buộc |
|-------------|----------|---------|
| Đau vùng thái dương/đỉnh | 0.8 | Có |
| Đau nhiều chiều tối | 0.7 | Không |
| Mặt đỏ | 0.6 | Không |
| Miệng khô | 0.5 | Không |
| Mạch Huyền | 0.7 | Không |
| Lưỡi đỏ | 0.6 | Không |
| Khó ngủ | 0.4 | Không |

User có triệu chứng: đau thái dương (✓), đau chiều tối (✓), miệng khô (✓), khó ngủ (✓)

```
score = (0.8×1.0 + 0.7×1.0 + 0.5×1.0 + 0.4×1.0) / 0.8
      = 2.4 / 0.8
      = 3.0 → Vượt chuẩn → normalize về 1.0 khi vượt quá
      → Kết luận: rất phù hợp với HA001
```

---

## 5. Chiến Lược Hỏi Thích Nghi

### 5.1 Skip Câu Hỏi Không Liên Quan

Khi đã thu thập đủ triệu chứng để xác định bệnh với confidence cao (>0.85), hệ thống có thể:
1. Bỏ qua các câu hỏi không bắt buộc còn lại
2. Chuyển thẳng sang kết quả

### 5.2 Hỏi Thêm Khi Không Rõ

Khi có 2+ bệnh cùng điểm cao (chênh nhau < 0.1), hệ thống sẽ:
1. Hỏi thêm câu phân biệt giữa 2 bệnh này
2. Chọn câu hỏi có giá trị phân biệt cao nhất (tính bằng entropy)

---

## 6. Quản Lý Cây Quyết Định

### 6.1 Ai Quản Lý?

- **Bác sĩ YHCT** biên soạn: Vọng, Thiết, phần YHCT của Vấn
- **Bác sĩ YHHD** biên soạn: phần YHHD của Vấn, red flags
- **Lập trình viên** xây dựng công cụ nhập liệu cho bác sĩ (Admin Panel)

### 6.2 Admin Panel Cần Có

```
- Quản lý bệnh (CRUD)
- Quản lý triệu chứng (CRUD)
- Trình soạn thảo cây quyết định (visual editor)
- Test chạy thử một luồng hỏi-đáp
- Xem log các phiên khám để cải thiện
- Xuất/Nhập cây quyết định dạng JSON
```

### 6.3 Version Control Cây Quyết Định

Mỗi lần cập nhật cây QĐ → tạo version mới, không xóa version cũ.
Phiên khám đang chạy sử dụng version tại thời điểm bắt đầu phiên.
Đảm bảo tính nhất quán trong suốt một phiên khám.
