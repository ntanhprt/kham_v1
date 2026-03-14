# Kiến Trúc Hệ Thống Kỹ Thuật

## 1. Sơ Đồ Kiến Trúc Tổng Thể

```
┌────────────────────────────────────────────────────────────────┐
│                         CLIENT BROWSER                          │
│                    (React / Next.js Frontend)                   │
└─────────────────────────────┬──────────────────────────────────┘
                              │ HTTPS / WebSocket
┌─────────────────────────────▼──────────────────────────────────┐
│                         API GATEWAY                             │
│                     (Next.js API Routes)                        │
├──────────────────────────────────────────────────────────────── │
│                      BACKEND SERVICES                           │
│                                                                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌────────────────┐  │
│  │  Session Service│  │  Engine Service │  │ Result Service │  │
│  │                 │  │                 │  │                │  │
│  │ - Tạo phiên KCB │  │ - Duyệt cây QĐ │  │ - Tổng hợp KQ │  │
│  │ - Lưu trạng thái│  │ - Thu thập TC  │  │ - Xếp hạng bệnh│  │
│  │ - Timeout/resume│  │ - Pattern match│  │ - Tạo báo cáo │  │
│  └────────┬────────┘  └────────┬────────┘  └───────┬────────┘  │
│           └───────────────────┴───────────────────┘           │
│                                 │                               │
│  ┌──────────────────────────────▼──────────────────────────┐   │
│  │              Knowledge Base Service                      │   │
│  │  - Query symptoms, diseases, decision trees              │   │
│  │  - Cache hot data in Redis                               │   │
│  └──────────────────────────────┬───────────────────────────┘   │
└─────────────────────────────────┼──────────────────────────────┘
                                  │
┌─────────────────────────────────▼──────────────────────────────┐
│                         DATA LAYER                              │
│                                                                  │
│  ┌──────────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │   PostgreSQL      │  │    Redis     │  │   File Storage   │  │
│  │  (Knowledge Base) │  │   (Cache +  │  │  (Reports PDF)   │  │
│  │  - Diseases       │  │   Sessions) │  │                  │  │
│  │  - Symptoms       │  │             │  │                  │  │
│  │  - Decision Trees │  │             │  │                  │  │
│  │  - Treatments     │  │             │  │                  │  │
│  └──────────────────┘  └──────────────┘  └──────────────────┘  │
└────────────────────────────────────────────────────────────────┘
```

---

## 2. Mô Tả Các Service

### 2.1 Session Service
Quản lý vòng đời một phiên khám bệnh.

```
Phiên KCB (Examination Session):
{
  session_id: UUID,
  created_at: timestamp,
  status: "active" | "completed" | "abandoned",
  current_node_id: ID trong cây quyết định,
  collected_symptoms: [
    { symptom_id, value, timestamp }
  ],
  phase: "yhct_vong" | "yhct_van" | "yhct_van" | "yhct_thiet" | "yhhd" | "result"
}
```

**Nguyên tắc**: Session được lưu trong Redis với TTL 2 giờ. Khi hết TTL, session tự động kết thúc.

### 2.2 Engine Service (Trái Tim Hệ Thống)
Điều phối logic hỏi-đáp theo cây quyết định.

**Luồng xử lý mỗi câu trả lời:**
```
1. Nhận câu trả lời từ user (answer)
2. Xác định node hiện tại trong cây QĐ
3. Ánh xạ answer → nhánh tiếp theo
4. Ghi nhận triệu chứng vào session
5. Kiểm tra điều kiện kết thúc giai đoạn
6. Trả về câu hỏi tiếp theo HOẶC chuyển sang giai đoạn mới
```

**Điều kiện chuyển giai đoạn:**
- Đã hỏi đủ câu hỏi bắt buộc của giai đoạn hiện tại
- Hoặc đạt ngưỡng triệu chứng đủ để kết luận (confidence threshold)

### 2.3 Result Service
Tổng hợp kết quả từ tập triệu chứng thu thập.

**Thuật toán Pattern Matching:**
```
Input: tập triệu chứng S = {s1, s2, ..., sn}

Với mỗi bệnh D trong CSDL:
  score(D) = Σ weight(si, D) với mọi si ∈ S
           / Σ weight_required(D)

Kết quả: Top 3 bệnh có score cao nhất
Chẩn đoán chính: bệnh có score > threshold (0.7)
Chẩn đoán phân biệt: các bệnh có score > 0.4
```

### 2.4 Knowledge Base Service
Giao tiếp với CSDL, cache dữ liệu thường dùng.

- Cache toàn bộ cây quyết định vào Redis khi khởi động
- Lazy-load chi tiết bệnh/điều trị khi cần
- Invalidate cache khi admin cập nhật CSDL

---

## 3. API Endpoints

### 3.1 Quản Lý Phiên

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| POST | `/api/session/start` | Bắt đầu phiên khám mới |
| GET | `/api/session/:id` | Lấy trạng thái phiên |
| DELETE | `/api/session/:id` | Kết thúc phiên |

### 3.2 Hỏi-Đáp

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/exam/:sessionId/question` | Lấy câu hỏi tiếp theo |
| POST | `/api/exam/:sessionId/answer` | Gửi câu trả lời |
| GET | `/api/exam/:sessionId/result` | Lấy kết quả (khi hoàn thành) |

### 3.3 Request/Response Schema

**POST /api/session/start**
```json
Request:
{
  "chief_complaint": "đau đầu",
  "patient_info": {
    "age": 35,
    "gender": "male"
  }
}

Response:
{
  "session_id": "uuid-xxx",
  "first_question": {
    "question_id": "q001",
    "text": "Bạn đau đầu bao lâu rồi?",
    "type": "single_choice",
    "options": ["Dưới 1 ngày", "1-7 ngày", "1-4 tuần", "Hơn 1 tháng"]
  },
  "phase": "yhct_vong",
  "progress": { "current": 1, "estimated_total": 15 }
}
```

**POST /api/exam/:sessionId/answer**
```json
Request:
{
  "question_id": "q001",
  "answer": "1-7 ngày"
}

Response:
{
  "next_question": {
    "question_id": "q005",
    "text": "Đau đầu vào thời điểm nào trong ngày?",
    "type": "multiple_choice",
    "options": ["Sáng sớm", "Ban ngày", "Chiều tối", "Ban đêm", "Không cố định"]
  },
  "phase": "yhct_vong",
  "progress": { "current": 2, "estimated_total": 15 }
}
```

**GET /api/exam/:sessionId/result**
```json
{
  "yhct_diagnosis": {
    "primary": "Can Dương Thượng Cang",
    "organ_affected": "Can (Gan)",
    "pattern": "Dương Thịnh - Hư Nhiệt",
    "reasoning": "Đau đầu vùng đỉnh-thái dương, thường chiều tối, kèm khô miệng, mắt mờ..."
  },
  "yhhd_diagnosis": {
    "primary": "Hội chứng tăng huyết áp / Căng thẳng thần kinh",
    "differential": ["Migraine", "Đau đầu do căng cơ"],
    "red_flags": []
  },
  "root_causes": [
    "Căng thẳng kéo dài gây Can khí uất kết",
    "Thiếu ngủ, âm hư sinh nội nhiệt",
    "Chế độ ăn nhiều cay nóng"
  ],
  "treatment": {
    "yhct": {
      "principle": "Bình Can tiềm Dương, tư âm thanh nhiệt",
      "herbs": "Tham khảo bác sĩ YHCT",
      "acupuncture": ["Thái xung (LR3)", "Phong trì (GB20)", "Bách hội (GV20)"],
      "lifestyle": ["Ngủ đủ giấc", "Tránh stress", "Ăn thanh đạm"]
    },
    "yhhd": {
      "principle": "Kiểm soát huyết áp, giảm stress",
      "recommendation": "Nên đo huyết áp và khám chuyên khoa Tim mạch/Thần kinh"
    }
  },
  "warnings": [],
  "disclaimer": "Kết quả này chỉ mang tính tham khảo. Vui lòng gặp bác sĩ để được chẩn đoán chính xác."
}
```

---

## 4. Bảo Mật

- Không lưu tên/CCCD người dùng (bảo mật thông tin y tế)
- Session ID là UUID ngẫu nhiên, không đoán được
- HTTPS bắt buộc
- Rate limiting: tối đa 10 phiên/IP/giờ
- Input sanitization: tất cả câu trả lời chỉ chọn từ options định sẵn (không có free-text injection)
