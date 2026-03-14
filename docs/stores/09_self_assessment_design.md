# Self-Assessment Design – Thiết Kế Cho Người Không Có Nền Y Khoa

> **Bài toán cốt lõi**: Đây không phải bài toán y học. Đây là bài toán
> **UX + Communication Design** trên nền y học.
> Người dùng không hiểu "đau như thế nào", không nhận ra red flag,
> đã có sẵn chẩn đoán trong đầu, và output không được là chẩn đoán.

---

## 1. Bốn Vấn Đề Đặc Thù Và Giải Pháp

---

### Vấn Đề 1: Bệnh Nhân Không Biết Mình Không Biết

**Triệu chứng của vấn đề này:**
- Hỏi "đau kiểu gì?" → "đau bình thường thôi"
- Hỏi "mức độ đau 1-10?" → luôn trả lời 7 vì không có baseline
- Hỏi "có triệu chứng kèm theo không?" → "không" (vì không biết điều gì là "triệu chứng")

**Giải Pháp: Forced Choice + Analogical Questions**

```
KHÔNG DÙNG:                          THAY BẰNG:
─────────────────────────────────────────────────────────
"Đau kiểu gì?"              →  "Đau giống cái nào nhất?"
                                ○ Đau nhói như kim châm
                                ○ Đau căng như bị bóp chặt
                                ○ Đau âm ỉ như bầm dập
                                ○ Đau rát như bỏng
                                ○ Đau bụng kiểu quặn thắt từng cơn

"Đau mức độ bao nhiêu?"      →  "Đau này ảnh hưởng đến cuộc sống thế nào?"
                                ○ Vẫn làm mọi việc bình thường được
                                ○ Làm chậm lại nhưng vẫn làm được
                                ○ Phải dừng mọi thứ, chỉ muốn nằm
                                ○ Không chịu được, đau nhất từ trước đến nay

"Có triệu chứng gì kèm?"    →  "Trong danh sách này, bạn có gì không?"
                                (checklist cụ thể, không để trống)
                                □ Buồn nôn / nôn
                                □ Chóng mặt
                                □ Sốt / ớn lạnh
                                □ Khó thở
                                □ Không có gì trong danh sách này
```

**Nguyên tắc thiết kế câu hỏi:**

| Sai | Đúng | Lý Do |
|-----|------|-------|
| Open-ended: "Đau thế nào?" | Forced choice với options cụ thể | Bệnh nhân không có từ vựng y tế |
| Scale 1-10 | Scale chức năng ("vẫn làm việc được không?") | Scale số thiếu anchor point |
| "Có triệu chứng kèm không?" | Checklist đầy đủ | Bệnh nhân không nhớ nếu không nhìn thấy |
| "Đau bao lâu?" | "Đau này bắt đầu từ trước hay sau [sự kiện cụ thể]?" | Giúp neo vào mốc thời gian thực tế |
| "Ăn có ngon không?" | "Bữa cuối bạn ăn được đầy đủ là khi nào?" | Cụ thể hơn, bệnh nhân trả lời chính xác hơn |

---

### Vấn Đề 2: Anchoring Của Bệnh Nhân

**Triệu chứng:**
- 80% bệnh nhân đã Google trước → đã có "chẩn đoán" trong đầu
- Khai triệu chứng bị bias bởi chẩn đoán họ đã tự đặt
- Dismiss triệu chứng không khớp với "chẩn đoán" tự đặt
- Ví dụ: tự nghĩ bị "thiếu máu" → không khai đau ngực vì "không liên quan"

**Giải Pháp: Explicit Debiasing Protocol**

```
Bước 1: KHAI THÁC ANCHOR TRƯỚC
Câu hỏi đầu tiên (sau context):
"Trước khi bắt đầu, bạn có nghĩ mình đang bị bệnh gì không?"
○ Có, tôi nghĩ là [text input hoặc common options]
○ Không chắc
○ Không biết

→ Lý do: Hệ thống cần BIẾT anchor để chủ động counter nó,
  không phải để theo nó.

Bước 2: LƯU ANCHOR, KHÔNG ĐỂ NÓ ẢNH HƯỞNG CÂY HỎI
Cây hỏi tiếp theo vẫn theo protocol chuẩn, không bị thay đổi
bởi anchor của bệnh nhân.

Bước 3: KHI KẾT QUẢ KHÁC ANCHOR → EXPLAIN DIPLOMATICALLY
"Triệu chứng bạn mô tả có một số điểm không điển hình với [anchor].
Tuy nhiên, chúng phù hợp hơn với [X]. Đây là lý do..."
→ Không dismiss ("bạn tự chẩn đoán sai rồi")
→ Không confirm mù quáng
→ Explain với data từ chính câu trả lời của họ
```

---

### Vấn Đề 3: Red Flag Bị Bỏ Qua

**Vấn đề:** Bệnh nhân trả lời "đau đầu đột ngột dữ dội" nhưng tiếp tục khai bình thường vì không biết đây là red flag.

**Giải Pháp: Interrupt Pattern + Active Clarification**

**Rule 1: Red Flag Scanner phải interrupt ngay lập tức**

```
Khi phát hiện BẰNG BẤT KỲ CÂU TRẢ LỜI NÀO:
  → Đau đầu + "đột ngột" + "dữ dội" / "chưa bao giờ đau thế này"
  → DỪNG NGAY, không hỏi câu tiếp theo
  → Hiển thị CLARIFICATION CARD:

  ┌─────────────────────────────────────────────────────┐
  │ ⚠️ Bạn vừa mô tả đau đầu khởi phát rất đột ngột   │
  │    và dữ dội. Tôi cần xác nhận lại điều này:       │
  │                                                     │
  │ "Đau đầu này đến NHANH như thế nào?"                │
  │ ○ Đau dần dần tăng lên trong vài phút/giờ          │
  │ ○ Đau xuất hiện trong vòng vài giây, như sét đánh  │
  │                                                     │
  │ (Câu hỏi này quan trọng vì hai trường hợp cần      │
  │  xử lý rất khác nhau)                              │
  └─────────────────────────────────────────────────────┘
```

**Rule 2: Không bao giờ dùng ngôn ngữ y tế trong câu hỏi red flag**

```
KHÔNG: "Bạn có headache thunderclap không?"
KHÔNG: "Đây có phải worst headache of your life không?"
CÓ:    "Trong cuộc đời bạn, bạn đã từng bị đau đầu lần nào
        dữ dội hơn lần này chưa?"
        ○ Có, tôi từng đau hơn thế này
        ○ Không, đây là lần đau đầu kinh khủng nhất từ trước đến nay
```

**Rule 3: Red flag không phải câu hỏi cuối chuỗi – phải hỏi SỚM**

```
Với chief complaint "đau đầu":
→ Câu hỏi số 3 (không phải cuối):
  "Cơn đau này bắt đầu từ từ hay đột ngột?"
→ Không chờ đến khi đã hỏi xong 10 câu mới hỏi red flag
→ Nếu answer là red flag → dừng toàn bộ → escalate
```

**Rule 4: Ngôn ngữ Escalation phải rõ ràng, không mơ hồ**

```
CẤP ĐỘ 1 – TỰ THEO DÕI:
  "Triệu chứng của bạn hiện tại có vẻ không cấp bách.
   Theo dõi 24-48 giờ và..."

CẤP ĐỘ 2 – GẶP BÁC SĨ TRONG VÀI NGÀY:
  "Triệu chứng này cần được BS đánh giá, nhưng không khẩn cấp.
   Hãy đặt lịch trong 3-5 ngày tới."

CẤP ĐỘ 3 – GẶP BÁC SĨ HÔM NAY:
  "Triệu chứng này cần được khám trong ngày hôm nay.
   Hãy liên hệ phòng khám hoặc bệnh viện ngay."

CẤP ĐỘ 4 – CẤP CỨU NGAY:
  ┌─────────────────────────────────────────────────────┐
  │ 🚨 TRIỆU CHỨNG CỦA BẠN CẦN ĐƯỢC ĐÁNH GIÁ NGAY    │
  │                                                     │
  │ Hãy gọi 115 hoặc đến phòng cấp cứu NGAY BÂY GIỜ. │
  │                                                     │
  │ Lý do: [Giải thích ngắn gọn bằng ngôn ngữ thường]  │
  │                                                     │
  │ KHÔNG tự lái xe. Nhờ người đưa đi hoặc gọi 115.   │
  └─────────────────────────────────────────────────────┘
  → KHÔNG hỏi thêm câu nào nữa.
  → KHÔNG hiển thị "kết quả khám" bên dưới.
```

---

### Vấn Đề 4: Output Không Được Là Chẩn Đoán

**Lý do:**
1. **Pháp lý**: Chẩn đoán y khoa là hành vi nghề nghiệp có kiểm soát
2. **Đạo đức**: Chẩn đoán sai → bệnh nhân tự điều trị sai → hại
3. **Thực tế**: Không có khám thực thể → thiếu dữ liệu quan trọng để chẩn đoán

**Giải Pháp: Triage Recommendation Framework**

Output phải có cấu trúc **4 tầng**:

```
TẦNG 1: TRIAGE (Quan trọng nhất, hiển thị đầu tiên)
┌─────────────────────────────────────────────────────────────┐
│ Dựa trên những gì bạn mô tả, tôi khuyên bạn nên:           │
│                                                              │
│ ⬜ Tự theo dõi tại nhà                                     │
│ 🟡 Gặp bác sĩ trong vòng 3-5 ngày                         │
│ 🟠 Gặp bác sĩ trong ngày hôm nay                          │
│ 🔴 Đến cấp cứu ngay                                        │
└─────────────────────────────────────────────────────────────┘

TẦNG 2: LÝ DO (Giải thích quyết định triage)
"Lý do tôi đưa ra khuyến nghị này: [giải thích bằng ngôn ngữ thường,
dựa trên triệu chứng cụ thể bệnh nhân đã khai]"

TẦNG 3: THÔNG TIN BỐI CẢNH (Không phải chẩn đoán)
"Các triệu chứng bạn mô tả thường liên quan đến một số tình trạng
phổ biến như: [liệt kê possibilities, không xác định một bệnh]
Bác sĩ sẽ có thể xác định chính xác hơn sau khi thăm khám."

TẦNG 4: CHECKLIST CHO BÁC SĨ (Nếu được chỉ định đi khám)
"Khi gặp bác sĩ, hãy kể:
  ✓ [Triệu chứng chính với timeline]
  ✓ [Yếu tố tăng/giảm đau]
  ✓ [Các triệu chứng kèm theo]
  ✓ [Thuốc đang dùng]
  ✓ [Câu hỏi bạn muốn hỏi bác sĩ]"
```

**Ngôn Ngữ Phải Dùng:**

| Không nói | Thay bằng |
|-----------|-----------|
| "Bạn bị X" | "Triệu chứng của bạn phù hợp với X" |
| "Bạn bị X" | "Bác sĩ có thể sẽ xem xét khả năng X" |
| "Đây là bệnh X" | "Một số người có triệu chứng như bạn được chẩn đoán là X" |
| "Uống thuốc Y" | "Bác sĩ có thể sẽ đề nghị điều trị bao gồm Y" |
| "Không cần lo" | "Triệu chứng này thường không nghiêm trọng, nhưng nếu [X] xảy ra, hãy đi khám ngay" |

---

## 2. Design Principles Cho Self-Assessment UI

### 2.1 Progressive Disclosure

```
Không hỏi 15 câu một lúc.
Hỏi từng câu, một màn hình một câu.
→ Giảm cognitive load
→ Bệnh nhân trả lời cẩn thận hơn từng câu
→ Cho phép branching linh hoạt hơn
```

### 2.2 Explain the "Why" (Giải thích lý do câu hỏi)

```
Mỗi câu hỏi quan trọng nên có micro-explanation:

"Đau đầu xuất hiện đột ngột hay từ từ?"
[Tại sao hỏi điều này? ℹ️]
→ Cách khởi phát đau đầu giúp phân biệt nhiều loại tình trạng khác nhau.
  Đau đầu xuất hiện đột ngột trong vài giây có thể cần đánh giá khẩn cấp.

→ Kết quả: bệnh nhân trả lời cẩn thận hơn, ít bỏ qua chi tiết quan trọng
```

### 2.3 Normalize Sensitive Questions

```
Trước khi hỏi câu nhạy cảm, normalize trước:

"Tôi sẽ hỏi một số câu hỏi về lối sống. Câu trả lời của bạn
không bị lưu trữ theo tên và chỉ dùng để đưa ra khuyến nghị
phù hợp nhất. Không có câu trả lời nào là 'xấu'."

→ Rồi mới hỏi về rượu bia, thuốc lá, sinh hoạt tình dục (nếu cần)
```

### 2.4 Anxiety Management

```
Bệnh nhân lo lắng → trả lời cực đoan → hệ thống triage sai.
Ví dụ: "Đau mức độ bao nhiêu?" → lo lắng → nói 9/10 dù thực ra 5/10.

Giải pháp: Functional anchors thay vì số:
"Đau này làm bạn dừng hoạt động bình thường không?"
→ Ít bias hơn "mức độ 1-10"

Và: Câu hỏi kiểm tra chéo (cross-validation):
Nếu bệnh nhân nói "đau 9/10" nhưng trả lời "vẫn đi làm bình thường"
→ Hệ thống ghi nhận inconsistency, không lấy giá trị extreme
```

### 2.5 Memory Aid (Giúp Bệnh Nhân Nhớ)

```
Bệnh nhân không nhớ chính xác. Giúp họ neo vào mốc thực tế:

KHÔNG: "Bạn đau bao lâu rồi?"
CÓ:   "Đau này bắt đầu:
       ○ Hôm nay (trong ngày)
       ○ Hôm qua hoặc hôm kia
       ○ Tuần trước (khoảng [ngày hiện tại - 7 ngày])
       ○ Tháng trước hoặc lâu hơn"

KHÔNG: "Bạn dùng thuốc gì?"
CÓ:   "Trong hộp thuốc / tủ thuốc nhà bạn, bạn có đang dùng
       thường xuyên loại nào trong số này không?" [checklist nhóm thuốc phổ biến]
```

---

## 3. Kiến Trúc Output Đa Tầng

### 3.1 Output Chính: Triage Card

```
┌──────────────────────────────────────────────────────────────┐
│  KẾT QUẢ ĐÁNH GIÁ SỨC KHỎE                                │
│  Dựa trên thông tin bạn cung cấp lúc [giờ], ngày [ngày]    │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  🟠 KHUYẾN NGHỊ: GẶP BÁC SĨ TRONG 24-48 GIỜ              │
│                                                              │
│  Lý do: Bạn mô tả đau đầu kéo dài hơn 3 ngày, kèm          │
│  theo mờ mắt. Sự kết hợp này cần được bác sĩ đánh giá       │
│  để loại trừ một số tình trạng cần điều trị sớm.            │
│                                                              │
├──────────────────────────────────────────────────────────────┤
│  TRIỆU CHỨNG CẢNH BÁO – Nếu xuất hiện, đến cấp cứu ngay:  │
│  • Đau đầu đột ngột dữ dội hơn nhiều so với hiện tại       │
│  • Yếu hoặc tê liệt tay chân                               │
│  • Nói chuyện khó khăn                                      │
│  • Mất ý thức                                               │
├──────────────────────────────────────────────────────────────┤
│  KHI GẶP BÁC SĨ, HÃY KỂ:                                  │
│  ✓ Đau đầu 5 ngày, tăng dần, hai bên thái dương            │
│  ✓ Kèm mờ mắt buổi chiều                                   │
│  ✓ Không sốt, không nôn                                     │
│  ✓ Đang dùng: [liệt kê thuốc đã khai]                      │
└──────────────────────────────────────────────────────────────┘
```

### 3.2 Output Mở Rộng (Bệnh Nhân Có Thể Xem Thêm)

```
[Xem thêm thông tin bối cảnh ▼]

THÔNG TIN BỐI CẢNH (không phải chẩn đoán):
Các triệu chứng bạn mô tả thường gặp trong một số tình trạng
phổ biến. Bác sĩ sẽ cân nhắc và loại trừ dần trong quá trình khám:

• [Tình trạng 1] – Phổ biến, thường lành tính
• [Tình trạng 2] – Cần kiểm tra thêm
• Một số tình trạng khác ít gặp hơn

ĐỌC THÊM VỀ YHCT:
Trong Y học cổ truyền, tổ hợp triệu chứng này thường liên
quan đến [mô tả pattern YHCT bằng ngôn ngữ đơn giản].

⚠️ QUAN TRỌNG: Thông tin này chỉ để tham khảo. Không dùng để
tự điều trị. Chỉ bác sĩ mới có thể chẩn đoán chính xác.
```

---

## 4. Vấn Đề Sức Khỏe Tâm Thần Trong Self-Assessment

### 4.1 Phát Hiện Lo Âu Về Sức Khỏe (Health Anxiety)

```
Dấu hiệu hệ thống cần nhận ra:
- Bệnh nhân mô tả rất nhiều triệu chứng (> 8 triệu chứng đa hệ thống)
- Triệu chứng kéo dài nhưng không tiến triển
- Tự chẩn đoán bệnh nghiêm trọng (ung thư, ALS...)
- Đã gặp nhiều bác sĩ, kết quả bình thường nhưng vẫn không yên tâm

→ Hệ thống không dismiss: "Bạn không sao đâu"
→ Acknowledge: "Tôi thấy bạn đang lo lắng về sức khỏe của mình"
→ Triage: Vẫn khuyên gặp bác sĩ, nhưng thêm: "Bác sĩ cũng có thể
   thảo luận về lo lắng sức khỏe với bạn"
```

### 4.2 Sàng Lọc Trầm Cảm/Lo Âu Nhúng Vào

```
PHQ-2 (2 câu, nhúng tự nhiên vào flow):

Khi chief complaint là mệt mỏi / mất ngủ / đau mơ hồ / chán ăn:
→ Thêm vào tự nhiên:

"Cuối cùng, hai câu hỏi về tinh thần:"

"Trong 2 tuần vừa qua, bạn có thường xuyên cảm thấy buồn bã,
trống rỗng, hoặc mất hy vọng không?"
○ Không ngày nào
○ Vài ngày
○ Hơn một nửa số ngày
○ Gần như mỗi ngày

"Trong 2 tuần vừa qua, bạn có ít hứng thú hoặc niềm vui
trong những việc thường ngày không?"
○ Không ngày nào
○ Vài ngày
○ Hơn một nửa số ngày
○ Gần như mỗi ngày

→ Nếu ≥ 1 câu trả lời "hơn một nửa số ngày" hoặc "gần như mỗi ngày":
   → Flag trong output: Gợi ý thảo luận về sức khỏe tâm thần với bác sĩ
```

### 4.3 Ý Tưởng Tự Tử – Protocol Bắt Buộc

```
Nếu phát hiện BẤT KỲ gợi ý nào:
- Bệnh nhân mention "không muốn sống nữa"
- "Mọi người sẽ tốt hơn nếu không có tôi"
- "Tôi đang nghĩ đến chuyện kết thúc tất cả"

→ DỪNG NGAY toàn bộ flow
→ Hiển thị:

┌─────────────────────────────────────────────────────────────┐
│  Bạn vừa chia sẻ điều gì đó rất quan trọng.                │
│  Tôi muốn bạn biết: bạn không một mình.                    │
│                                                              │
│  Hãy liên hệ ngay:                                         │
│  📞 Đường dây hỗ trợ sức khỏe tâm thần: [số điện thoại]   │
│  📞 Cấp cứu: 115                                           │
│                                                              │
│  Nếu bạn đang trong tình trạng nguy hiểm ngay lúc này,     │
│  hãy gọi 115 hoặc đến cơ sở y tế gần nhất.                │
└─────────────────────────────────────────────────────────────┘
```

---

## 5. Hệ Quả Thiết Kế Kỹ Thuật

### 5.1 Không Có Free-Text Input Trong Câu Hỏi Lâm Sàng

```
Ngoại trừ câu đầu tiên (chief complaint), mọi câu hỏi đều là:
- Single choice (radio buttons)
- Multiple choice (checkboxes)
- Scale với functional anchors (không phải số trừu tượng)
- Yes/No

→ Lý do: Free-text = không nhất quán, không thể pattern match,
  không thể scan red flags đáng tin cậy
→ AI NLP chỉ dùng để parse câu đầu (chief complaint) → classify
  vào cây quyết định, không dùng trong toàn bộ phiên hỏi
```

### 5.2 Session Replay

```
Sau khi hoàn thành, bệnh nhân có thể xem lại:
- Tóm tắt những gì đã trả lời
- Timeline triệu chứng được hệ thống tổng hợp
- Có thể xuất/in để mang đến gặp bác sĩ

→ Đây là output có giá trị nhất: chuẩn bị cho cuộc gặp bác sĩ
```

### 5.3 Consistency Check

```
Tự động phát hiện mâu thuẫn trong câu trả lời:

VD: Nói "đau 9/10" nhưng "vẫn đi làm và sinh hoạt bình thường"
→ Hệ thống hỏi lại: "Bạn nói đau rất dữ dội nhưng vẫn làm việc
   bình thường được. Giúp tôi hiểu hơn:
   ○ Đau dữ dội khi đột ngột cử động, nhưng khi ngồi yên thì chịu được
   ○ Đau liên tục nhưng tôi cố gắng làm việc dù rất khó khăn
   ○ Thực ra có lẽ tôi đã nói quá mức, điều chỉnh lại: khoảng [scale]"
```

---

## 6. Tóm Tắt: Self-Assessment ≠ Chẩn Đoán Tự Động

```
Self-assessment tốt không phải là "chẩn đoán bệnh tự động".
Self-assessment tốt là:

1. Thu thập thông tin có cấu trúc từ người không có nền y tế
2. Phát hiện red flags và escalate an toàn
3. Triage: ưu tiên mức độ khẩn cấp
4. Chuẩn bị thông tin cho cuộc gặp bác sĩ tiếp theo
5. Cung cấp bối cảnh để giảm lo lắng không cần thiết

OUTPUT ĐÍCH: Không phải "bạn bị X"
OUTPUT ĐÍCH: "Bạn nên gặp bác sĩ [ngay/trong vài ngày/không gấp]
              vì [lý do cụ thể], và đây là những gì bạn nên kể với bác sĩ."
```
