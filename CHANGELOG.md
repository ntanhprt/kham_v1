# Changelog

## [Unreleased] — 2026-03-14

### Bug Fixes
- **FK constraint khi tạo phiên khám**: Guest users được truyền `user_id = null` thay vì negative crc32 ID không tồn tại trong bảng `users`
- **"Đã chọn hơn 15 triệu chứng" hiện sai**: `processStart()` không còn tự động chọn `matched_codes` — `selected_codes` bắt đầu bằng `[]`
- **`BASE_PATH` undefined trong result.php**: Sửa thành `APP_ROOT . '/core/disease_vi.php'`
- **`matchSymptomCodesFromTokens` break 2 sai**: Sửa `break 2` thành `continue 2` trong vòng lặp kiểm tra tên triệu chứng
- **AdminController `docs-detail` PDO chain**: Tách `execute()` ra dòng riêng trước khi gọi `fetchColumn()`

### New Features

#### Luồng khám — Trang "Làm rõ" (Clarify)
- Thêm bước trung gian `exam/clarify` giữa nhập triệu chứng và chọn triệu chứng
- Hệ thống đặt câu hỏi theo ngữ cảnh: thời gian, mức độ, diễn biến, vị trí
- Câu hỏi đặc thù theo từ khóa: ngứa (vị trí, thay đổi da), ho (tính chất), đau đầu (kiểu đau)
- Bỏ qua trang clarify nếu muốn vào thẳng chọn triệu chứng
- Sidebar trong symptoms page hiển thị tóm tắt câu trả lời clarify

#### Engine — Reverse Matching
- Thêm Pass 2 trong `matchSymptomCodesFromTokens()`: nếu forward pass không tìm thấy gì, thực hiện reverse matching (kiểm tra xem tên triệu chứng có chứa token của user không)
- Token phải ≥ 4 ký tự để tránh match quá rộng (ví dụ "đau" 3 ký tự bị bỏ qua)
- Kết quả: "ngứa chân" → 23 triệu chứng liên quan thay vì 0

#### KB — 5 Chứng hội Da liễu (K04 Skin Patterns)
Thêm 5 pattern vào `kb_patterns` (tổng từ 129 → 134):

| Code | Tên | YHHD tương ứng |
|------|-----|----------------|
| `blood_heat_wind_itch` | Huyết nhiệt sinh phong | Mày đay cấp, dị ứng thuốc |
| `blood_deficiency_wind_dryness_itch` | Huyết hư phong táo | Viêm da atopic mạn, ngứa người cao tuổi |
| `damp_heat_skin` | Thấp nhiệt xâm bì | Eczema cấp, hắc lào (tinea) |
| `spleen_deficiency_damp_skin` | Tỳ hư thấp trệ bì phu | Eczema mạn tính, viêm da tiết bã |
| `wind_damp_heat_skin` | Phong thấp nhiệt | Mày đay cấp, tinea pedis, rôm sảy |

Mỗi pattern có đầy đủ: required_symptoms, two_or_more_of, supporting_symptoms, phap_tri_vi, phuong_thuoc (3 bài thuốc kinh điển), huyet_vi (6 huyệt), clinical_note, yhhd_correlates, key_questions.

#### Admin — Embedding Management
- Thêm panel xem chi tiết từng tài liệu embedding (status, timestamp, text preview, pagination)
- Nút "Reset & Nhúng lại" theo loại tài liệu hoặc toàn bộ
- Nút "Seed lại tài liệu" để chạy lại `seed_embedding_documents.php`
- API actions mới trong AdminController: `reset-embeddings`, `docs-detail`, `reseed`

### Changed
- `ExamSessionModel::createSession()`: tham số `$userId` đổi từ `int` → `?int`
- Progress bar exam: 5 bước (Mô tả → Làm rõ → Triệu chứng → Câu hỏi → Kết quả)
- Step labels trong `symptoms.php` và `clarify.php` đồng bộ 5 bước

---

## [1.0.0] — 2026-03-14 — `initsystem`

### Initial Release

#### Core
- PHP MVC framework (Router, Controller, View, Auth, Database singleton)
- SQLite database (`app/storage/kham.db`)
- Session-based authentication (admin, doctor, patient, guest)

#### Engine YHCT
- **YHCTEngine**: parse chief complaint, token matching, K02 symptom scoring, K04 pattern scoring, Bát Cương, Tạng phủ
- **RedFlagEngine**: 70 cảnh báo đỏ, 4 mức độ (emergency/urgent/warning/info)
- **SafetyFilter**: 120 tương tác herb-drug, cảnh báo thai kỳ, trẻ em, dị ứng
- **ClusterEngine**: phân nhóm triệu chứng theo tạng phủ
- **HypothesisEngine**: ranking chứng hội theo xác suất
- **BackwardReasoningEngine**: suy luận ngược từ chứng hội

#### Knowledge Base
- 1852 triệu chứng (kb_symptoms) với alias tiếng Việt
- 3563 symptom aliases (symptom_aliases)
- 129 chứng hội K04 (kb_patterns)
- 70 red flags (kb_red_flags)
- 120 herb-drug interactions (kb_herb_drug)
- 60 clusters (kb_clusters)
- 300 observable phrases (kb_observable_phrases)
- 78 symptom contexts (kb_symptom_contexts)

#### Views
- Luồng khám 4 bước: start → symptoms → questions → result
- Admin dashboard: thống kê, KB review, users, embedding management, clinical tests
- Doctor dashboard: danh sách phiên khám, xem chi tiết
- Auth: login/logout

#### Scripts
- `add_skin_patterns.php` — thêm chứng hội da liễu
- `seed_embedding_documents.php` — seed tài liệu embedding
- `create_schema.php` — DDL schema
- `run_clinical_tests.php` — chạy test KB
- Nhiều scripts debug và kiểm tra KB
