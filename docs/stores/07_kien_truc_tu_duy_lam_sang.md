# Kiến Trúc Tư Duy Lâm Sàng – Nền Tảng Thiết Kế Hệ Thống

> **Nguyên tắc cốt lõi**: Hệ thống không mô phỏng "quy trình hỏi bệnh",
> mà mô phỏng **tư duy song song** của một BS đa khoa giỏi.
> BS không hỏi tuần tự – họ xử lý nhiều luồng đồng thời.

---

## 1. Mô Hình Tư Duy BS Đa Khoa Thực Sự

Không phải cây quyết định tuyến tính. Là **mạng lưới 9 luồng song song**:

```
LUỒNG 1: Context Engine       – "Bệnh nhân này là ai?"
LUỒNG 2: Red Flag Scanner     – "Có nguy hiểm không?" (luôn bật)
LUỒNG 3: Chief Complaint Type – "Đây là loại vấn đề gì?"
LUỒNG 4: YHCT Constitution    – "Thể trạng nền là gì?"
LUỒNG 5: Systems Review       – "Có gì bị bỏ sót không?"
LUỒNG 6: Temporal Analysis    – "Diễn biến như thế nào?"
LUỒNG 7: Backward Reasoning   – "Bệnh nền kéo theo gì?"
LUỒNG 8: Special Group Logic  – "Nhóm đặc biệt nào?"
LUỒNG 9: ICE + Psychosocial   – "Bệnh nhân thực sự lo điều gì?"
```

---

## 2. Luồng 1: Context Engine – Bắt Buộc Trước Mọi Thứ

**Phải thu thập TRƯỚC KHI bắt đầu hỏi triệu chứng.**
Vì context thay đổi hoàn toàn mọi câu hỏi tiếp theo.

### 2.1 Các Context Modifier

| Modifier | Tại Sao Quan Trọng |
|----------|--------------------|
| **Tuổi** | Đau ngực ở 25 tuổi vs 60 tuổi = 2 bảng chẩn đoán hoàn toàn khác |
| **Giới sinh học** | Phụ nữ: nhồi máu cơ tim ít đau ngực điển hình; bệnh đặc hiệu giới |
| **Thai kỳ** | Thay đổi mọi thứ: chẩn đoán, xét nghiệm, thuốc, ngưỡng nguy hiểm |
| **Bệnh nền đã có** | ĐTĐ + sốt = ngưỡng lo ngại cao hơn nhiều người thường |
| **Thuốc đang dùng** | Ho mạn → hỏi ngay ACE inhibitor; táo bón → calcium channel blocker |
| **Miễn dịch** | HIV, corticosteroid, hóa trị → nhiễm trùng cơ hội, biểu hiện không điển hình |
| **Nghề nghiệp** | Công nhân hóa chất + ho = toxic exposure; nông dân + sốt = leptospirosis |
| **Địa lý/Du lịch** | Vừa về Đông Nam Á + sốt → sốt rét cho đến khi chứng minh ngược lại |
| **Thói quen** | Số gói-năm thuốc lá quyết định ngưỡng tầm soát ung thư phổi |
| **Tiền sử gia đình** | Ung thư đại tràng gia đình → tầm soát sớm hơn 10 năm |

### 2.2 Context Thay Đổi Cây Hỏi Như Thế Nào

```
Cùng triệu chứng: "MỆT MỎI"

Bệnh nhân A: Nữ 30 tuổi, không bệnh nền
→ Ưu tiên hỏi: thiếu máu (kinh nguyệt?), tuyến giáp, trầm cảm, thai kỳ

Bệnh nhân B: Nam 60 tuổi, ĐTĐ + THA
→ Ưu tiên hỏi: suy tim, thiếu máu mạn, thận mạn, trầm cảm, ung thư

Bệnh nhân C: Bất kỳ, đang dùng beta-blocker
→ Hỏi ngay: mệt mỏi bắt đầu từ khi nào (trước hay sau khi dùng thuốc?)

Bệnh nhân D: Nữ 25 tuổi có thai 20 tuần
→ Ưu tiên: thiếu máu thai kỳ, ĐTĐ thai kỳ, suy giáp; tránh xét nghiệm có tia X
```

---

## 3. Luồng 2: Red Flag Scanner – Chạy Song Song Liên Tục

**Không phải một bước trong quy trình. Là một luồng riêng, luôn bật từ đầu đến cuối.**

Bất cứ lúc nào user trả lời, hệ thống đồng thời kiểm tra red flag list.

### 3.1 Red Flags Toàn Thân (Bất Kể Triệu Chứng Chính)

```
NGAY LẬP TỨC → Cấp cứu:
✗ Đau ngực + khó thở + vã mồ hôi
✗ Đột ngột méo miệng / tay yếu / nói khó (FAST stroke)
✗ Đau đầu "tệ nhất trong đời, đột ngột" → SAH
✗ Khó thở cấp không rõ nguyên nhân
✗ Ngất khi đang gắng sức
✗ Sốt + cứng cổ + sợ ánh sáng → viêm màng não
✗ Đau bụng cấp + cứng bụng như gỗ → thủng tạng rỗng

TRONG 24-48h → Khám ngay:
✗ Sút cân > 5% trong 6 tháng, không chủ ý
✗ Đổ mồ hôi đêm thấm quần áo
✗ Ho ra máu bất kỳ lượng nào
✗ Đi cầu ra máu + thay đổi thói quen đại tiện > 50 tuổi
✗ Hạch to không đau, tiến triển
✗ Khó nuốt tiến triển
✗ Xuất huyết âm đạo sau mãn kinh
✗ Đau xương không do chấn thương
```

### 3.2 Red Flags Tâm Thần – Không Bao Giờ Bỏ Qua

```
✗ Bất kỳ gợi ý về ý tưởng tự làm hại bản thân
✗ Cảm giác "không muốn sống nữa"
✗ Kế hoạch tự tử cụ thể
→ DỪNG NGAY quy trình khám thông thường
→ Chuyển sang protocol an toàn tâm thần
```

### 3.3 Red Flags Nhóm Đặc Biệt

```
Thai phụ:
✗ Đau đầu + phù mặt + HA cao → preeclampsia
✗ Chảy máu âm đạo + đau bụng → cấp cứu sản khoa
✗ Thai không cử động

Trẻ sơ sinh/nhũ nhi:
✗ Sốt BẤT KỲ ở trẻ < 3 tháng → cấp cứu
✗ Fontanelle phồng
✗ Không ăn được + li bì

Người cao tuổi:
✗ Lú lẫn cấp tính → luôn có nguyên nhân thực thể, phải tìm
✗ Té ngã + đau → xem xét gãy xương dù không sưng rõ
```

---

## 4. Luồng 3: Phân Loại Chief Complaint – 8 Nhóm Khác Nhau

**Đây là bước đầu tiên sau Context, trước khi hỏi bất cứ điều gì về triệu chứng.**
Mỗi loại có logic xử lý HOÀN TOÀN KHÁC NHAU.

| Loại | Ví Dụ | Logic Xử Lý |
|------|-------|-------------|
| **1. Triệu chứng cấp tính** | Đau đầu, ho, đau bụng | OPQRST + red flags + chẩn đoán phân biệt, loại trừ nguy hiểm trước |
| **2. Bệnh mạn tính diễn biến mới** | ĐTĐ 10 năm, đường huyết bỗng khó kiểm soát | Hỏi "so với bình thường của bạn, khác gì?" không phải hỏi như triệu chứng mới |
| **3. Mục tiêu sức khỏe** | Giảm cân, ngủ ngon hơn, tăng sinh lý | Hỏi "tại sao chưa đạt được mục tiêu" → tìm nguyên nhân gốc |
| **4. Tái khám / Theo dõi** | Kiểm tra sau điều trị | Hỏi theo target outcomes, không theo triệu chứng |
| **5. Phòng ngừa / Tầm soát** | Kiểm tra sức khỏe định kỳ | Driven by tuổi + giới + yếu tố nguy cơ, không theo triệu chứng |
| **6. Đa triệu chứng mơ hồ** | "Tôi mệt, đau, không khỏe, kéo dài mãi" | Tìm pattern tổng thể, sàng lọc tâm lý/xã hội, YHCT thể trạng |
| **7. Lo lắng / Sợ bệnh** | "Tôi đọc thấy triệu chứng này giống ung thư" | Khai thác nỗi lo (ICE), không chỉ xử lý triệu chứng |
| **8. Yêu cầu cụ thể** | "Tôi muốn xét nghiệm cholesterol" | Hỏi lý do đằng sau yêu cầu trước khi đồng ý hay từ chối |

---

## 5. Luồng 4: YHCT Constitution Engine – Thể Trạng Nền

**Thể trạng là "địa hình" nền – mọi bệnh đều biểu hiện trên địa hình này.**
Xác định thể trạng từ đầu → điều chỉnh toàn bộ cách hỏi + kết quả.

### 5.1 Câu Hỏi Xác Định Thể Trạng (5 câu đủ xác định 80%)

```
1. "Bạn thường cảm thấy lạnh hay nóng hơn người bình thường?"
   Lạnh hơn → Dương hư / Hàn thể
   Nóng hơn → Âm hư / Nhiệt thể

2. "Chân tay bạn có thường lạnh không?"
   Có → Dương hư / Khí hư

3. "Bạn có hay bị mệt không? Ra mồ hôi dễ không?"
   Mệt + mồ hôi tự nhiên → Khí hư
   Mồ hôi trộm đêm → Âm hư

4. "Bạn có hay lo lắng, ngực tức, hay thở dài không?"
   Có → Khí uất thể

5. "Cân nặng và sức khỏe tiêu hóa thế nào?"
   Béo + đầy bụng + mệt → Đàm thấp thể
   Gầy + táo bón + nóng → Âm hư thể
```

### 5.2 Thể Trạng Điều Chỉnh Gì

```
Thể Khí Hư:
→ Khi gặp "mệt mỏi" → ưu tiên hỏi Tỳ Khí Hư, Phế Khí Hư
→ Khi gặp "hay cảm" → ưu tiên Vệ Khí bất cố
→ Điều trị: bổ khí, tránh tả hạ, tránh thuốc lạnh

Thể Âm Hư:
→ Khi gặp "mất ngủ" → ưu tiên Tâm Thận bất giao, Can âm hư
→ Khi gặp "đau đầu" → ưu tiên Can Dương Thượng Cang
→ Điều trị: dưỡng âm, tránh ôn táo, tránh thuốc quá nóng

Thể Đàm Thấp:
→ Khi gặp "đau đầu" → ưu tiên Đàm Thấp Thượng Nhiễu
→ Khi gặp "chóng mặt" → Đàm Thấp > Huyết Hư
→ Điều trị: kiện Tỳ hóa đàm, tránh béo ngọt
```

### 5.3 Ngũ Tạng Tương Quan – Bệnh Biểu Hiện Chéo

```
Can bệnh biểu hiện ở: mắt, móng, cơ, kinh nguyệt, cảm xúc (dễ cáu)
Tâm bệnh biểu hiện ở: lưỡi, giấc ngủ, thần trí, mặt đỏ
Tỳ bệnh biểu hiện ở: miệng, môi, cơ, chân tay
Phế bệnh biểu hiện ở: mũi, da, lông, giọng nói, đại trường
Thận bệnh biểu hiện ở: tai, tóc, xương, sinh dục, nước tiểu

Tương sinh: Thận âm → nuôi Can âm → Can Dương mới không vượng
Tương khắc: Stress (Can uất) → khắc Tỳ → tiêu hóa kém
           → Không điều trị tiêu hóa mà phải sơ Can trước
```

---

## 6. Luồng 5: Systems Review – 14 Hệ Cơ Quan

**Không hỏi tất cả 14 hệ với mọi bệnh nhân.**
Context engine + chief complaint type → chọn hệ thống nào ưu tiên.

### 6.1 Ma Trận Ưu Tiên Systems Review

| Chief Complaint | Hệ Ưu Tiên Cao | Hệ Bắt Buộc Sàng Lọc |
|----------------|----------------|----------------------|
| Đau đầu | Thần kinh, Tim mạch | Mắt, Tâm thần |
| Mệt mỏi | Nội tiết, Huyết học | Tâm thần, Tim mạch |
| Giảm cân | Tiêu hóa, Nội tiết, Huyết học | Tâm thần (ăn uống rối loạn) |
| Đau ngực | Tim mạch, Hô hấp, Tiêu hóa | Thần kinh, Cơ xương |
| Khó thở | Hô hấp, Tim mạch | Thần kinh (lo âu) |
| Đau bụng | Tiêu hóa, Tiết niệu | Tim mạch (ở người cao tuổi) |
| Mất ngủ | Tâm thần | Nội tiết, Tim mạch, Hô hấp |

### 6.2 Câu Hỏi "Sentinel" – Một Câu Mở Ra Nhiều Hệ

Thay vì hỏi từng hệ, dùng câu hỏi sentinel:

```
"Buổi sáng thức dậy bạn cảm thấy thế nào?"
→ Không tỉnh táo    → sleep apnea
→ Không muốn dậy    → trầm cảm
→ Ho ngay           → COPD / GERD
→ Cứng khớp         → viêm khớp dạng thấp
→ Mệt như chưa ngủ  → suy tim / thiếu máu

"Bạn có phải thức dậy đêm đi tiểu không?"
→ > 2 lần/đêm → BPH, ĐTĐ, suy tim, UTI mạn

"Quần áo bạn có vừa hơn hay rộng hơn so với 6 tháng trước?"
→ Rộng hơn (không chủ ý) → RED FLAG: tầm soát ung thư, bệnh tiêu hóa
→ Vừa hơn (không chủ ý) → tầm soát suy tim, suy giáp, Cushing
```

---

## 7. Luồng 6: Temporal Analysis – Chiều Thời Gian

### 7.1 Ma Trận Onset × Tiến Triển

```
             Đột ngột (giây-phút)    Cấp (giờ-ngày)    Mạn (tuần-tháng)
Worsening  │ SAH, STEMI, stroke  │ Viêm cấp         │ Ung thư, bệnh tự miễn
Stable     │ (không thường gặp)  │ Nhiễm trùng đang │ Bệnh mạn kiểm soát
           │                     │ điều trị          │ tốt
Improving  │                     │ Bệnh tự giới hạn │ Điều trị có tác dụng
Episodic   │ Cardiac syncope     │ Cơn hen, panic   │ Migraine, RA flare
```

### 7.2 Circadian Pattern – YHHD ↔ YHCT

| Thời điểm | Triệu chứng | YHHD | YHCT |
|-----------|-------------|------|------|
| 3-5h sáng | Ho, khó thở | Cortisol thấp, peak airway resistance | Giờ Dần – Phế kinh vượng |
| 5-7h sáng | Cứng khớp | RA peak inflammation | Giờ Mão – Đại trường |
| Chiều tối | Đau đầu, mệt mỏi | Cortisol giảm | Giờ Thân-Dậu – Âm hư nội nhiệt |
| Ban đêm | Đau xương, ngứa | Histamine peak, PGE2 peak | Giờ Tý-Sửu – Can-Đởm |

---

## 8. Luồng 7: Backward Reasoning – Từ Bệnh Nền Ra Biến Chứng

**Khi phát hiện bệnh nền → hệ thống chủ động hỏi biến chứng, không chờ bệnh nhân khai.**

### 8.1 Template Backward Reasoning

```
Bệnh nền: ĐÁI THÁO ĐƯỜNG TYPE 2
→ Tự động thêm câu hỏi:
  - Thị lực thay đổi không? (retinopathy)
  - Chân tê bì, kim châm không? (neuropathy)
  - Vết thương lâu lành không? (PVD)
  - Tiểu đêm nhiều hơn? (nephropathy)
  - Đau ngực khi gắng sức? (silent MI)
  - Huyết áp kiểm soát không? (thường đi kèm)
  - Cholesterol đã kiểm tra chưa? (dyslipidemia)

Bệnh nền: TĂNG HUYẾT ÁP
→ Tự động thêm câu hỏi:
  - Đau đầu buổi sáng? (BP surge)
  - Chức năng thận gần nhất? (hypertensive nephropathy)
  - Đau ngực khi gắng sức? (LVH, coronary artery disease)
  - Nhìn mờ? (hypertensive retinopathy)

Bệnh nền: TRẦM CẢM đang dùng thuốc
→ Tự động thêm câu hỏi:
  - Tăng cân từ khi dùng thuốc? (nhiều antidepressant gây tăng cân)
  - Khó ngủ hay ngủ nhiều? (side effect)
  - Giảm ham muốn tình dục? (SSRI)
  - Cân nhắc lại: có phải mệt mỏi là side effect thuốc không?

Bệnh nền: VIÊM KHỚP DẠNG THẤP
→ Tự động thêm:
  - Đang dùng methotrexate? → hỏi tác dụng phụ gan, phổi
  - Đau ngực không? (RA tăng nguy cơ tim mạch 2-3 lần)
  - Loãng xương? (corticosteroid dài ngày)
```

### 8.2 Lateral Reasoning – Bệnh Đi Kèm Thường Gặp

```
Có ĐTĐ type 2 → sàng lọc THA, dyslipidemia, NAFLD, sleep apnea
Có THA → sàng lọc ĐTĐ, thận mạn, bệnh tim
Có GERD mạn → hỏi triệu chứng Barrett, có thể là biểu hiện ĐTĐ (gastroparesis)
Có Psoriasis → hỏi khớp (psoriatic arthritis), tim mạch
Có trầm cảm → hỏi lo âu, substance use, tuyến giáp, vitamin D
```

---

## 9. Luồng 8: Special Group Logic

Khi Context Engine xác định nhóm đặc biệt, toàn bộ cây hỏi chuyển sang version riêng:

### 9.1 Thai Phụ

```
Phụ nữ 12-50 tuổi + BẤT KỲ triệu chứng gì:
→ HỎI ĐẦU TIÊN: "Bạn có thể đang mang thai không?"
→ Nếu có/không chắc → kích hoạt Thai Kỳ Mode:
   - Loại trừ: xét nghiệm có tia X
   - Ưu tiên hỏi: thai ngoài tử cung (đau bụng + trễ kinh)
   - Thêm vào bất kỳ kết quả nào: "Tham khảo bác sĩ sản khoa"
   - Thuốc đề xuất: chỉ category A/B trong thai kỳ
```

### 9.2 Trẻ Em

```
< 3 tháng:
→ Sốt BẤT KỲ = cấp cứu ngay, không hỏi thêm

3 tháng - 5 tuổi:
→ Thêm: hỏi vaccine (tiêm chủng có đầy đủ không?)
→ Thêm: mốc phát triển (phát hiện bất thường phát triển)
→ Ngôn ngữ: mô tả triệu chứng qua phụ huynh

6-18 tuổi:
→ Thiếu niên: khi hỏi về lối sống, sức khỏe tâm thần
   → Gợi ý hỏi riêng (không có phụ huynh)
→ Thêm sàng lọc: trầm cảm thiếu niên (PHQ-A), rối loạn ăn uống
```

### 9.3 Người Cao Tuổi (≥ 65 tuổi)

```
Tự động thêm vào bất kỳ phiên khám nào:
→ Té ngã: "Bạn có bị té ngã trong 6 tháng gần đây không?"
→ Polypharmacy: "Bạn đang dùng bao nhiêu loại thuốc?"
→ Chức năng: "Bạn có tự làm được việc nhà không?"
→ Nhận thức: "Gia đình có nhận xét gì về trí nhớ của bạn không?"
→ Dinh dưỡng: "Bạn ăn có đủ bữa không?"

Thay đổi ngưỡng:
→ Lú lẫn cấp = LUÔN có nguyên nhân thực thể, không phải "già lẫn"
→ Đau có thể biểu hiện không điển hình (nhồi máu cơ tim không đau ngực)
→ Nhiễm trùng có thể không sốt (nhiệt độ nền thấp hơn)
```

---

## 10. Luồng 9: ICE + Psychosocial

**Câu quan trọng nhất trong toàn bộ cuộc khám:**
> "Bạn lo lắng điều gì nhất về tình trạng này?"

### 10.1 ICE Framework

```
Ideas (Bệnh nhân nghĩ gì?):
→ "Bạn nghĩ điều gì đang xảy ra với cơ thể bạn?"
→ Trả lời sẽ tiết lộ: niềm tin sai (cần chỉnh), lo lắng ẩn (cần xử lý)

Concerns (Lo lắng gì nhất?):
→ "Bạn lo lắng nhất điều gì?"
→ Thường khác với triệu chứng chính: "Tôi sợ giống bố tôi"

Expectations (Kỳ vọng gì?):
→ "Bạn mong muốn gì từ cuộc khám hôm nay?"
→ Nếu không khớp với những gì hệ thống có thể cung cấp → thông báo rõ ràng
```

### 10.2 Sàng Lọc Tâm Lý Xã Hội

```
Với MỌIBỆNH NHÂN có triệu chứng mạn tính hoặc đa triệu chứng:
→ "Gần đây có chuyện gì lớn xảy ra trong cuộc sống của bạn không?"
→ PHQ-2: "Trong 2 tuần qua, bạn có hay buồn bã/mất hứng thú không?"
   Nếu dương tính → PHQ-9 đầy đủ
→ GAD-2: "Bạn có hay lo lắng không kiểm soát được không?"

Với phụ nữ:
→ Tầm soát bạo lực gia đình (tế nhị, khi không có người đi cùng)
   "Đôi khi triệu chứng như bạn mô tả liên quan đến căng thẳng ở nhà.
    Ở nhà bạn có an toàn không?"
```

---

## 11. Tổng Hợp: Sơ Đồ Kiến Trúc Cập Nhật

```
┌──────────────────────────────────────────────────────────────┐
│                    USER INPUT                                 │
│            "Tôi muốn [triệu chứng/mục tiêu]"                │
└────────────────────────┬─────────────────────────────────────┘
                         │
┌────────────────────────▼─────────────────────────────────────┐
│                LUỒNG 1: CONTEXT ENGINE                        │
│   Tuổi + Giới + Thai + Bệnh nền + Thuốc + Nghề + Địa lý    │
│   → Điều chỉnh TẤT CẢ luồng bên dưới                       │
└────────────────────────┬─────────────────────────────────────┘
                         │
         ┌───────────────┼──────────────────────────────┐
         │               │                              │
┌────────▼──────┐ ┌──────▼──────┐             ┌────────▼───────┐
│LUỒNG 2        │ │LUỒNG 3      │             │LUỒNG 4         │
│RED FLAG       │ │COMPLAINT    │             │YHCT            │
│SCANNER        │ │TYPE         │             │CONSTITUTION    │
│(luôn bật)     │ │CLASSIFIER   │             │ENGINE          │
└───────────────┘ └──────┬──────┘             └────────┬───────┘
                         │                             │
         ┌───────────────▼─────────────────────────────▼──────┐
         │              LUỒNG 5: SYSTEMS REVIEW               │
         │         (14 hệ, ưu tiên theo context)              │
         └───────────────────────┬─────────────────────────────┘
                                 │
         ┌───────────────────────┼──────────────────────────┐
         │                       │                          │
┌────────▼──────┐       ┌────────▼──────┐        ┌─────────▼──────┐
│LUỒNG 6        │       │LUỒNG 7        │        │LUỒNG 8         │
│TEMPORAL       │       │BACKWARD       │        │SPECIAL GROUP   │
│ANALYSIS       │       │REASONING      │        │LOGIC           │
└───────────────┘       └───────────────┘        └────────────────┘
                                 │
                    ┌────────────▼─────────────┐
                    │ LUỒNG 9: ICE + PSYCHOSOC │
                    └────────────┬─────────────┘
                                 │
         ┌───────────────────────▼──────────────────────────────┐
         │              SYNTHESIS ENGINE                         │
         │   YHCT pattern + YHHD differential                   │
         │   + Confidence calibration                           │
         │   + Safety netting + Follow-up triggers              │
         └──────────────────────────────────────────────────────┘
```

---

## 12. Hệ Quả Thiết Kế

### Những Gì Phải Thay Đổi So Với Thiết Kế Cũ

| Thiết Kế Cũ | Thiết Kế Mới |
|-------------|-------------|
| Cây quyết định tuyến tính | Mạng lưới 9 luồng song song |
| Red flags là 1 bước | Red flags là luồng riêng, luôn chạy |
| Context thu thập nhưng không dùng nhiều | Context điều chỉnh mọi câu hỏi |
| Không phân loại chief complaint | 8 loại, xử lý khác nhau |
| YHCT ở cuối như bổ sung | YHCT constitution xác định ngay từ đầu |
| Không có special group logic | Pediatric/Geriatric/Pregnancy có cây riêng |
| Không có backward reasoning | Mỗi bệnh nền kéo theo checklist biến chứng |
| Không hỏi bệnh nhân lo điều gì | ICE là câu hỏi bắt buộc |
| Systems review không có thứ tự ưu tiên | Ma trận ưu tiên theo chief complaint |

### Hệ Quả Với CSDL

1. **Bảng `context_profiles`**: Lưu profile context + điều chỉnh tương ứng
2. **Bảng `red_flag_rules`**: Red flags tách riêng, không lẫn trong cây QĐ
3. **Bảng `complaint_types`**: 8 loại, mỗi loại có entry logic khác
4. **Bảng `constitution_types`**: 9 thể trạng YHCT + câu hỏi xác định + ảnh hưởng
5. **Bảng `backward_rules`**: Mỗi bệnh nền → danh sách biến chứng cần hỏi
6. **Bảng `special_group_overrides`**: Override cây QĐ cho nhóm đặc biệt
7. **Bảng `system_review_priorities`**: Ma trận chief complaint × hệ cơ quan
