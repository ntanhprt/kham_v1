# TÀI LIỆU THIẾT KẾ — KHÁM TỔNG QUÁN v1.1
**Số:** PRD-16
**Ngày:** 2026-03-13
**Trạng thái:** 🔴 DRAFT — Cần phê duyệt trước khi code
**Tác giả:** Claude + review bởi chủ sở hữu

---

## 1. VẤN ĐỀ HIỆN TẠI

### 1.1 Lỗi thiết kế cốt lõi — Symptom Picker

**Cách đang làm (sai):**
```
User nhập: "tôi già, bị loét thịt do nằm lâu"
  → Hiện ra 345 triệu chứng dạng danh sách
  → User phải đọc tất cả, tìm cái liên quan
  → Không ai làm được điều này
```

**Cách phải làm:**
```
User nhập: "tôi già, bị loét thịt do nằm lâu"
  → Hệ thống HIỂU: người cao tuổi, loét tỳ đè, nằm liệt
  → Hỏi ĐÚNG triệu chứng liên quan:
     ✓ Vết loét ở đâu? (cùng cụt, gót chân, xương sườn?)
     ✓ Vết loét sâu bao nhiêu? (đỏ da / bong da / loét sâu?)
     ✓ Có sốt không? (nhiễm trùng?)
     ✓ Da xung quanh lạnh hay ấm?
     ✓ Đại tiểu tiện có kiểm soát được không?
  → Không hỏi: "Bạn có bị đau thắt ngực không?"
```

### 1.2 Hệ quả

| Vấn đề | Tác động |
|--------|----------|
| Hiện 345 triệu chứng | Người dùng bỏ cuộc ngay lập tức |
| Không ưu tiên | Triệu chứng quan trọng bị vùi trong danh sách |
| Không thích nghi | Chọn "đau đầu" không lọc bỏ "đau bụng" |
| Không semantic | "loét thịt" ≠ "loét tỳ đè" với hệ thống |

---

## 2. MỤC TIÊU THIẾT KẾ

> **Nguyên tắc chính:** Hệ thống phải hành xử như một bác sĩ đang hỏi bệnh — hỏi đúng câu, đúng lúc, đúng người, KHÔNG hỏi tất cả mọi thứ cùng một lúc.

### 2.1 Mục tiêu UX
- User không cần biết thuật ngữ y tế
- Không hiện quá 15 triệu chứng cùng lúc
- Mỗi triệu chứng được chọn → hệ thống thích nghi, đề xuất triệu chứng liên quan
- Có thể hoàn thành trong 3-5 phút

### 2.2 Mục tiêu lâm sàng
- Engine nhận đủ dữ liệu để khớp với K03/K04 pattern
- Bao phủ đủ để không bỏ sót red flag
- Thu thập được dữ liệu Vọng chẩn (lưỡi) + Ngũ Chí (cảm xúc)

---

## 3. LUỒNG KHÁM MỚI

### 3.1 Tổng quan flow

```
[BƯỚC 1] Chief Complaint
   User nhập tự do: "tôi bị đau đầu dữ dội, buồn nôn"
         ↓
   ┌── Phase 0: Semantic Input Processing ──────────────────┐
   │  Xem §3.2 — luôn chạy bất kể K01 match hay không      │
   └────────────────────────────────────────────────────────┘
         ↓
   ┌── Nhánh A: K01 MATCH ───────────────────────────────── ┐
   │  Text search vào K01.phrase/variants → khớp "đau đầu"  │
   │  → Hiển thị câu hỏi disambiguate K01                   │
   │  → Xác định anchor_codes từ K01.options.symptom_codes  │
   └─────────────────────────────────────────────────────── ┘
         ↓ (hoặc)
   ┌── Nhánh B: K01 KHÔNG MATCH ─────────────────────────── ┐
   │  Input: "tôi già, bị loét thịt do nằm lâu"             │
   │  → Không khớp K01 nào                                  │
   │  → anchor_codes = top K02 từ semantic search (§3.2)    │
   │  → BỎ QUA bước Disambiguate, vào thẳng Picker          │
   └─────────────────────────────────────────────────────── ┘
         ↓
[BƯỚC 2] Disambiguate (CHỈ khi Nhánh A)
   "Đau đầu của bạn như thế nào?" → chọn 1 trong 5 kiểu
   → Xác định symptom_codes từ K01.options.symptom_codes
   → Đây là ANCHOR SYMPTOMS (triệu chứng neo)
         ↓
[BƯỚC 3] Smart Symptom Picker ← ĐÂY LÀ PHẦN THIẾT KẾ LẠI
   Top K02 từ semantic search đặt sẵn ở đầu danh sách
   User tick → hệ thống re-rank → hiện thêm
         ↓
[BƯỚC 4] Quick Questions (2-3 câu cố định)
   Lưỡi? | Cảm xúc? | Chức năng sinh hoạt?
         ↓
[BƯỚC 5] Engine + Result
```

### 3.2 Phase 0: Semantic Input Processing — Đặc tả chi tiết

> Đây là bước chạy **TRƯỚC TIÊN**, cho **mọi** input, bất kể K01 có match hay không.
> Mục tiêu: biến câu nói tự nhiên của user thành danh sách K02 xếp hạng theo độ liên quan.

#### 3.2.1 Thuật toán tokenize tiếng Việt (pure PHP, không cần thư viện)

```
Input: "tôi già, bị loét thịt do nằm lâu"

Step 1 — Chuẩn hóa:
  lowercase → bỏ dấu câu → "tôi già bị loét thịt do nằm lâu"

Step 2 — Tokenize bằng whitespace:
  ["tôi", "già", "bị", "loét", "thịt", "do", "nằm", "lâu"]

Step 3 — Bỏ stopwords tiếng Việt:
  Stopwords: ["tôi", "bị", "do", "có", "và", "của", "là", "không",
              "thì", "mà", "cũng", "còn", "rất", "hay", "hoặc"]
  → Tokens còn lại: ["già", "loét", "thịt", "nằm", "lâu"]

Step 4 — Context clue detection (xem §3.2.2):
  "già" → context_elderly = true → boost category "geriatric", "systemic"
  "nằm" → context_bedridden = true → boost "pressure_ulcer_risk", "skin"
```

#### 3.2.2 Context Clue — Bảng phát hiện từ khóa ngữ cảnh

| Từ khóa gặp | Context flag | Tác động |
|-------------|--------------|---------|
| "già", "cao tuổi", "lão" | `elderly` | Boost category `geriatric`; boost K02 symptoms có zangfu `kidney` ≥ 0.5 |
| "trẻ em", "bé", "con" | `pediatric` | Boost category `pediatric` |
| "nằm", "liệt giường", "giường bệnh" | `bedridden` | Boost K02 code `pressure_ulcer_risk`, `urinary_incontinence`, `muscle_wasting` |
| "mang thai", "có bầu", "thai kỳ" | `pregnant` | Boost category `gynecological`; flag red_flag check bắt buộc |
| "sau sinh", "vừa sinh" | `postpartum` | Boost `blood_deficiency_pattern` symptoms |
| "tai nạn", "chấn thương" | `trauma` | Boost category `musculoskeletal`; trigger L2 triage check |

#### 3.2.3 Thuật toán score từng K02 symptom (TokenMatchScore)

Với mỗi K02 symptom, tính điểm như sau:

```
Nguồn text để search (ưu tiên giảm dần):
  Field A: name_vi              (VD: "Nguy cơ loét do tỳ đè")          → weight 1.0
  Field B: yhct_clinical_note   (mô tả YHCT đầy đủ, VD: "Can khí uất kết...") → weight 0.7
  Field C: yhhd_clinical_note   (mô tả YHHD, VD: "Pressure ulcer stage I...")  → weight 0.5

TokenMatchScore(symptom, tokens) =
    Σ token_t in tokens:
        max(
          found_in_A(t) × 1.0,
          found_in_B(t) × 0.7,
          found_in_C(t) × 0.5
        )
    / len(tokens)                    ← normalize theo số token
    × ContextBoost(symptom)          ← nhân context clue (1.0–1.5)
    × CategoryBoost(symptom)         ← nhân category match (1.0–1.2)

→ Kết quả: float 0.0–1.5+
```

**Ví dụ thực tế:**
```
Token: "loét"
  → K02 "pressure_ulcer_risk": name_vi = "Nguy cơ loét do tỳ đè" → found_in_A → +1.0
  → K02 "skin_ulcer_wound":    name_vi = "Vết thương, loét da"    → found_in_A → +1.0
  → K02 "liver_qi_stagnation": yhct_clinical_note có "..." không có "loét" → +0

Token: "già"
  → Không match name_vi hay note của triệu chứng nào
  → Nhưng gây ContextBoost: mọi K02 có zangfu kidney ≥ 0.5 được nhân 1.3

→ K02 "pressure_ulcer_risk": context_bedridden boost 1.4 → final score cao nhất
→ K02 "lower_back_knee_soreness": kidney boost 1.3 → vào top 5
```

#### 3.2.4 Kết quả Phase 0

```
Output của Phase 0:
  $semanticRanked = [
    ["code" => "pressure_ulcer_risk",     "score" => 1.35, "matched_tokens" => ["loét", "nằm"]],
    ["code" => "lower_back_knee_soreness","score" => 0.91, "matched_tokens" => ["lâu", "nằm"]],
    ["code" => "generalized_fatigue",     "score" => 0.78, "matched_tokens" => ["già"]],
    ...
  ]

  $contextFlags = ["elderly" => true, "bedridden" => true]

→ Nếu K01 match: $semanticRanked dùng để PRE-SORT danh sách picker,
                  anchor_codes vẫn lấy từ K01
→ Nếu K01 KHÔNG match: $semanticRanked[0..4]["code"] = anchor_codes thay thế
                         Picker hiện top 12 từ $semanticRanked ngay lập tức
```

---

## 4. THIẾT KẾ BƯỚC 3 — SMART SYMPTOM PICKER

### 4.1 Nguyên tắc xếp hạng triệu chứng

Mỗi triệu chứng K02 được tính **Relevance Score** (0–100) dựa trên 4 tín hiệu:

```
RelevanceScore(symptom) =
    W1 × AnchorScore        (từ K01 disambiguation)
  + W2 × OrganScore         (cùng tạng phủ với anchor)
  + W3 × CoOccurrenceScore  (xuất hiện cùng trong K03/K04)
  + W4 × CategoryScore      (cùng category body system)

W1=0.40, W2=0.25, W3=0.25, W4=0.10
```

#### Tín hiệu 1 — AnchorScore
- Anchor symptoms (từ K01 options.symptom_codes) → score = 100
- Sau khi alias-expand: K02 codes tương đương anchor → score = 80
- Không liên quan → 0

#### Tín hiệu 2 — OrganScore
- K01 option có `yhct_hint` → extract tạng phủ (e.g. "Can dương vượng" → liver)
- Các triệu chứng K02 có `zangfu_weights[liver] > 0.5` → score tỷ lệ
- VD: user chọn option "đau đầu nhịp đập + buồn nôn" → yhct_hint "Can dương vượng"
  → liver-weighted symptoms được ưu tiên

#### Tín hiệu 3 — CoOccurrenceScore
- Scan toàn bộ K03 pathogenesis rules: trong `required_symptoms` + `supporting_symptoms`
- Nếu anchor_code X và symptom Y xuất hiện cùng trong ≥ 2 rules → co-occurrence += 1
- Score = co_occurrence_count / max_count × 100

#### Tín hiệu 4 — CategoryScore
- Anchor thuộc category "neurological" → các triệu chứng neurological khác được +10 điểm
- Cross-category (e.g. neurological + gastrointestinal hay đi cùng) → +5

### 4.2 Giao diện Symptom Picker

```
┌────────────────────────────────────────────────────┐
│ Bước 3/5  |  Triệu chứng hiện tại                 │
│                                                    │
│ Dựa trên mô tả của bạn, bạn có đang bị:           │
│ (tick tất cả triệu chứng bạn đang có)              │
│                                                    │
│ ┌── 💡 Liên quan đến "đau đầu nhịp đập" ─────────┐ │
│ │ ☑ Đau đầu một bên, đập theo mạch               │ │
│ │ ☑ Buồn nôn kèm đau đầu                         │ │
│ │ ☐ Sợ ánh sáng                                   │ │
│ │ ☐ Hoa mắt, chóng mặt                            │ │
│ │ ☐ Cứng gáy                                      │ │
│ └────────────────────────────────────────────────┘ │
│                                                    │
│ ┌── 🔗 Triệu chứng đi kèm thường gặp ───────────┐ │
│ │ ☐ Mặt đỏ, bừng nóng mặt                        │ │
│ │ ☐ Miệng đắng                                    │ │
│ │ ☐ Tức sườn, căng tức dưới sườn                  │ │
│ │ ☐ Dễ cáu kỉnh, bực bội                          │ │
│ └────────────────────────────────────────────────┘ │
│                                                    │
│  🔍 Tìm thêm triệu chứng...                        │
│  [Tiếp theo →]                                     │
└────────────────────────────────────────────────────┘
```

**Rules:**
- Nhóm 1 "Liên quan trực tiếp": anchor + cao nhất theo RelevanceScore
- Nhóm 2 "Thường gặp kèm theo": tiếp theo theo RelevanceScore
- Mỗi nhóm tối đa 8 triệu chứng → tổng ≤ 16 triệu chứng mặc định
- "Tìm thêm" → full-text search (giữ nguyên tính năng search hiện tại)
- Khi tick thêm triệu chứng → **re-rank** danh sách chưa tick theo triệu chứng mới

### 4.3 Adaptive Re-ranking

Khi user tick triệu chứng mới:
```
Mới tích: "miệng đắng"
→ Tìm K03 rules có "bitter_taste_in_mouth" trong required/supporting
→ Các rules đó chứa thêm: ["red_face_or_eyes", "constipation", "dark_urine"]
→ Translate sang K02 qua alias table
→ Đẩy chúng lên đầu nhóm 2
```

Khi tick ≥ 3 triệu chứng:
```
→ Run mini-scoring ngay tại client (hoặc AJAX call)
→ Top 3 bệnh cơ candidate được tính
→ Load supporting symptoms của top candidate
→ Hiện thêm nhóm 3: "Để chẩn đoán chính xác hơn, bạn có thêm không?"
```

### 4.4 Giới hạn bắt buộc

| Rule | Giá trị |
|------|---------|
| Max triệu chứng hiển thị cùng lúc (mặc định) | 16 |
| Max triệu chứng user có thể chọn | 30 |
| Min triệu chứng để tiến sang bước tiếp | 1 |
| Thời gian re-rank sau mỗi lựa chọn | < 200ms |

---

## 5. THIẾT KẾ BƯỚC 4 — QUICK QUESTIONS

> Thay thế toàn bộ "followup" phức tạp bằng 3 câu hỏi ngắn gọn.

### 5.1 UI

```
┌────────────────────────────────────────────────────┐
│ Bước 4/5  |  Thêm thông tin (3 câu hỏi)            │
│                                                    │
│ 👁 Lưỡi bạn trông như thế nào?                     │
│    [Hồng (bình thường)] [Đỏ] [Nhợt] [Tím] [Không biết]│
│                                                    │
│ 🧠 Trong tuần qua cảm xúc của bạn:                 │
│    Dễ cáu giận? [Không] [Đôi khi] [Hay] [Rất hay] │
│    Lo lắng nhiều? [Không] [Đôi khi] [Hay] [Rất hay]│
│    Buồn bã?      [Không] [Đôi khi] [Hay] [Rất hay] │
│                                                    │
│ 🏃 Vì triệu chứng này, bạn:                        │
│    [Vẫn sinh hoạt bình thường]                     │
│    [Giảm hoạt động, vẫn đi lại được]               │
│    [Phải ở nhà, không đi làm/học được]             │
│    [Phải nằm, không dậy được]                      │
│                                                    │
│  [← Quay lại]  [Xem kết quả →]                    │
└────────────────────────────────────────────────────┘
```

**Lưu ý thiết kế:**
- Câu hỏi lưỡi: chỉ 5 lựa chọn, có hình minh họa màu
- Cảm xúc: chỉ hỏi 3 cảm xúc phổ biến nhất (không hỏi 5 Ngũ Chí đầy đủ — quá phức tạp)
- Chức năng: bắt buộc chọn (để detect minimization bias)
- Không có "rêu lưỡi" — quá phức tạp cho self-report, bỏ đi

---

## 6. THIẾT KẾ BƯỚC 5 — RESULT PAGE

### 6.1 Cấu trúc

```
┌─ [L1 EMERGENCY] ───────────────────────────────────┐
│ Nếu red_flag.triage_level = L1:                    │
│   Full-screen đỏ, chỉ hiện "GỌI 115"              │
│   KHÔNG hiện kết quả YHCT                         │
└────────────────────────────────────────────────────┘

┌─ [NORMAL RESULT] ──────────────────────────────────┐
│                                                    │
│ ① Cảnh báo (L2/L3 nếu có)                         │
│    Banner vàng/cam — tên + hành động cần làm       │
│                                                    │
│ ② Phân tích YHCT chính                             │
│    Tên Chứng (tên tiếng Việt to, rõ)               │
│    Badge độ tin cậy: [Khá chắc ●●●] / [Cần xác nhận ●●○]│
│    Pháp trị: nguyên tắc + phương thuốc + thuốc    │
│    Huyệt châm cứu (tags)                           │
│    Lời khuyên sinh hoạt                            │
│                                                    │
│ ③ Bát Cương — bars trực quan                       │
│    Hàn/Nhiệt | Hư/Thực | Biểu/Lý                  │
│                                                    │
│ ④ Tạng phủ chính (top 3)                           │
│    Progress bars                                   │
│                                                    │
│ ⑤ Kiêm Chứng (nếu có)                             │
│    Tags vàng + lưu ý kết hợp                       │
│                                                    │
│ ⑥ Theo dõi                                        │
│    "Nếu sau X ngày không đỡ → đi khám"            │
│    "Đi khám NGAY nếu: ..."                         │
│                                                    │
│ ⑦ Gửi cho bác sĩ (optional)                       │
│                                                    │
│ ⑧ Disclaimer                                      │
└────────────────────────────────────────────────────┘
```

### 6.2 Confidence Badge — Logic

```
Dựa trên expandedCodes vs K04.diagnostic_criteria:

required_matched   = số required symptoms matched
required_total     = tổng required symptoms
twoOf_matched      = số two_or_more_of matched

HIGH     = required_matched == required_total AND twoOf_matched >= 2
MODERATE = required_matched == required_total AND twoOf_matched >= 1
LOW      = required_matched >= 1 (không match hết required)
```

Display:
```
HIGH:     "Khá chắc ●●●●"  (xanh)
MODERATE: "Có khả năng ●●●○" (vàng)
LOW:      "Cần xác nhận ●●○○" (xám)
```

---

## 7. THIẾT KẾ KỸ THUẬT

### 7.0 Classes mới cần tạo

| Class | File | Vai trò |
|-------|------|---------|
| `SemanticInputProcessor` | `app/engine/SemanticInputProcessor.php` | Phase 0: tokenize input → rank K02 (§3.2) |
| `SymptomRanker` | `app/engine/SymptomRanker.php` | Phase 3: re-rank dựa trên anchor + co-occurrence |

`SemanticInputProcessor` chạy TRƯỚC, cung cấp `$semanticRanked` và `$contextFlags` cho `SymptomRanker`.

### 7.1 Scoring Engine (PHP, server-side)

**Class: `SymptomRanker`** — mới, trong `app/engine/`

```
Input:
  - $anchorCodes: array  (từ K01 option.symptom_codes)
  - $yhctHint: string    (từ K01 option.yhct_hint)
  - $selectedCodes: array (đã tick rồi)
  - $allSymptoms: array   (K02 rows)
  - $pathogenesisRules: array (K03 rows)

Output:
  - array of [symptom_code, relevance_score, group]
    sorted by relevance_score DESC
    grouped by 'anchor_related' | 'co_occurring' | 'other'
```

**Thuật toán:**

```php
foreach ($allSymptoms as $sym) {
    $score = 0;

    // Signal 1: Anchor match
    if (in_array($sym['code'], $expandedAnchorCodes)) {
        $score += 40;
    }

    // Signal 2: Organ match (from yhct_hint)
    $hint_organ = extractOrgan($yhctHint); // "Can dương vượng" → "liver"
    $zf = json_decode($sym['zangfu_weights'], true);
    $score += ($zf[$hint_organ] ?? 0) * 25;

    // Signal 3: Co-occurrence with already-selected
    $coOcc = $this->coOccurrenceScore($sym['code'], $selectedCodes);
    $score += $coOcc * 25;

    // Signal 4: Category match
    if ($sym['category'] === $anchorCategory) {
        $score += 10;
    }

    $ranked[$sym['code']] = round($score);
}
arsort($ranked);
```

### 7.2 Co-occurrence Pre-computation

**Khi nào tính:** Tại server, lazy-load khi cần, cache trong PHP session.

```php
// Từ K03 rules, build co-occurrence map:
// [symptom_A][symptom_B] = số lần xuất hiện cùng nhau trong 1 rule

foreach ($pathogenesisRules as $rule) {
    $allCodes = array_merge(
        $rule['required_symptoms'],
        $rule['supporting_symptoms']
    );
    foreach ($allCodes as $a) {
        foreach ($allCodes as $b) {
            if ($a !== $b) $coMatrix[$a][$b] = ($coMatrix[$a][$b] ?? 0) + 1;
        }
    }
}
// Store in $_SESSION['coMatrix'] — ~50KB, acceptable
```

### 7.3 API Endpoint cho re-ranking (AJAX)

**Route:** `GET /api/rank-symptoms?selected[]=code1&selected[]=code2&anchor=OBS_B02_001&opt=A`

**Response:**
```json
{
  "ranked": [
    {"code": "facial_flushing", "name_vi": "Mặt đỏ, bừng nóng", "score": 87, "group": "co_occurring"},
    {"code": "bitter_taste_mouth", "name_vi": "Miệng đắng", "score": 82, "group": "co_occurring"},
    ...
  ]
}
```

**Implementation:** `ApiController::rankSymptoms()` — mới

### 7.4 Semantic Search — Hai trường hợp

#### Trường hợp 1: Input lần đầu (Chief Complaint)

Xem đặc tả đầy đủ tại §3.2. Tóm tắt implementation:

```php
class SemanticInputProcessor {
    public function process(string $input, array $k02Symptoms, array $k01Phrases): array {
        $tokens = $this->tokenize($input);         // §3.2.1
        $ctx    = $this->detectContext($tokens);   // §3.2.2

        // Check K01 match
        $k01Match = $this->searchK01($input, $tokens, $k01Phrases);

        // Always compute semantic scores
        $ranked = [];
        foreach ($k02Symptoms as $sym) {
            $score = $this->tokenMatchScore($tokens, $sym);  // §3.2.3
            $score *= $this->contextBoost($sym, $ctx);
            $ranked[] = ['code' => $sym['symptom_code'], 'score' => $score];
        }
        usort($ranked, fn($a,$b) => $b['score'] <=> $a['score']);

        return [
            'k01_match'      => $k01Match,
            'semantic_ranked'=> $ranked,
            'context_flags'  => $ctx,
        ];
    }

    private function tokenMatchScore(array $tokens, array $sym): float {
        $sourceA = $sym['name_vi'] ?? '';
        $sourceB = $sym['yhct_clinical_note'] ?? '';  // ← QUAN TRỌNG
        $sourceC = $sym['yhhd_clinical_note'] ?? '';  // ← QUAN TRỌNG

        $score = 0;
        foreach ($tokens as $t) {
            $score += max(
                (mb_strpos($sourceA, $t) !== false ? 1.0 : 0),
                (mb_strpos($sourceB, $t) !== false ? 0.7 : 0),
                (mb_strpos($sourceC, $t) !== false ? 0.5 : 0)
            );
        }
        return count($tokens) > 0 ? $score / count($tokens) : 0;
    }
}
```

**Nguồn text được search (theo thứ tự ưu tiên):**
1. `kb_symptoms.name_vi` — weight 1.0 (tên ngắn, chính xác nhất)
2. `kb_symptoms.yhct_clinical_note` — weight 0.7 (mô tả YHCT đầy đủ, tiếng Việt)
3. `kb_symptoms.yhhd_clinical_note` — weight 0.5 (mô tả YHHD, có thể tiếng Anh một phần)

Không search: `symptom_code` (snake_case, không phải tiếng Việt)

#### Trường hợp 2: Ô "Tìm thêm triệu chứng" trong Picker (inline search)

Khi user gõ vào ô tìm trong bước 3:
```
Input: "mặt đỏ"
→ Tokenize → ["mặt", "đỏ"]
→ Search name_vi + yhct_clinical_note + yhhd_clinical_note
→ Hiện top 10 kết quả ngay lập tức (debounce 300ms)
→ User click → thêm vào danh sách đã tick → trigger re-rank
```

Implementation: Client-side JS search trên JSON đã load trước (không AJAX),
hoặc AJAX endpoint `GET /api/search-symptoms?q=mặt+đỏ&exclude[]=...`

---

## 8. DATA — NHỮNG GÌ CẦN THÊM/SỬA

### 8.1 K01 — Cần bổ sung `symptom_codes` vào DB

Hiện tại: K01 `options` column lưu JSON có `symptom_codes` nhưng nhiều record có thể NULL.

**Cần kiểm tra:** Bao nhiêu K01 options có symptom_codes?

```sql
SELECT COUNT(*) FROM kb_observable_phrases WHERE options IS NOT NULL;
-- Kỳ vọng: tất cả đều có
```

### 8.2 K02 — Cần thêm `description_vi` cho semantic search

Hiện tại K02 chỉ có `name_vi` (ngắn). Cần thêm mô tả dài hơn để search.

**Giải pháp:** Thêm column `description_vi TEXT` vào `kb_symptoms`, nhưng không cần ngay — name_vi đủ cho MVP.

### 8.3 K02 — `yhct_clinical_note` và `yhhd_clinical_note` ✓ ĐÃ CÓ

Cả hai column đã tồn tại trong `kb_symptoms` và có nội dung thực (không NULL cho phần lớn records).
Đây là nguồn text chính cho semantic search (§3.2.3, §7.4).

**Không cần thêm column mới.** Chỉ cần đảm bảo `SymptomRanker` và `SemanticInputProcessor`
đọc đúng hai field này khi scoring.

### 8.4 Không cần thêm gì khác

- `symptom_aliases` table: đã có ✓
- `tongue_mapping`, `ngu_chi_mapping`: cần cho engine nhưng có thể hardcode trong PHP
- `follow_up_rules`: hardcode fallback trong result view là đủ

---

## 9. NHỮNG GÌ KHÔNG LÀM (SCOPE OUT)

| Tính năng | Lý do không làm |
|-----------|----------------|
| Embedding/vector search | Không có inference API; overkill |
| Rêu lưỡi (Q4 chi tiết) | Quá phức tạp cho self-report, không reliable |
| Tất cả 5 Ngũ Chí riêng lẻ | Gộp thành 3 câu đơn giản hơn |
| Return Visit Recognition | Phase 3 |
| Time-gated Clusters | Phase 3 |
| Clinical Contradiction Detector | Phase 3 |

---

## 10. FILE CẦN THAY ĐỔI

| File | Thay đổi | Ưu tiên |
|------|----------|---------|
| `app/engine/SemanticInputProcessor.php` | **TẠO MỚI** — tokenize + K02 token-match search (§3.2, §7.4) | P0 |
| `app/engine/SymptomRanker.php` | **TẠO MỚI** — anchor + organ + co-occurrence scoring (§4.1) | P0 |
| `app/controllers/ExamController.php` | `chief_complaint()` dùng SemanticInputProcessor; `symptoms()` dùng SymptomRanker, thêm `followup()` | P0 |
| `app/controllers/ApiController.php` | Thêm `rankSymptoms()` endpoint | P0 |
| `app/views/exam/symptoms.php` | **VIẾT LẠI HOÀN TOÀN** | P0 |
| `app/views/exam/followup.php` | **TẠO MỚI** (3 câu hỏi) | P1 |
| `app/views/exam/result.php` | Thêm confidence badge + follow-up section | P1 |
| `app/engine/YHCTEngine.php` | Thêm `applyTongueDelta()`, `applyNguChiDelta()` | P1 |
| `scripts/migrate_followup.php` | Chạy 1 lần: add column + seed tongue/nguChi | P1 |

**KHÔNG thay đổi:**
- Router (`App.php`) — không cần, route `exam/followup` tự parse
- Schema (`create_schema.php`) — chạy migration script riêng
- Import scripts — data đã đủ
- Auth, Admin, Doctor views — không liên quan

---

## 11. ACCEPTANCE CRITERIA

Hệ thống được xem là DONE khi:

### Symptom Picker
- [ ] Không bao giờ hiển thị > 16 triệu chứng mặc định
- [ ] Anchor symptoms (từ K01) luôn ở đầu danh sách
- [ ] Sau khi tick 1 triệu chứng, danh sách tự cập nhật trong < 500ms
- [ ] Ô tìm kiếm hoạt động với tiếng Việt có dấu
- [ ] User có thể hoàn thành bước này trong < 2 phút

### Quick Questions
- [ ] Chỉ có 3 câu hỏi
- [ ] Tất cả có thể chọn bằng 1 click
- [ ] Câu hỏi lưỡi có hình màu minh họa

### Result Page
- [ ] Luôn hiển thị tên Chứng (không được trống) nếu input ≥ 3 triệu chứng
- [ ] Confidence badge hiển thị đúng level
- [ ] Pháp trị hiển thị: nguyên tắc + phương thuốc + thuốc + huyệt + lifestyle
- [ ] Section "Theo dõi" luôn có, không bao giờ trống
- [ ] L1 emergency = full red screen, KHÔNG có kết quả YHCT

### Technical
- [ ] PHP built-in server test: tất cả 6 routes trả 200/302, không có 500
- [ ] SQLite < 10MB sau khi chạy đủ data

---

## 12. THỨ TỰ THỰC HIỆN (SAU KHI PHÊ DUYỆT)

```
Sprint 1 — Smart Symptom Picker (ưu tiên cao nhất)
  1. Tạo SemanticInputProcessor (tokenize + 3-field token search + context clues)
  2. Tạo SymptomRanker engine (anchor + organ + co-occurrence scoring)
  3. Cập nhật ExamController::chief_complaint() dùng SemanticInputProcessor
     → Nhánh A (K01 match): tiếp tục flow cũ
     → Nhánh B (K01 no match): dùng semantic top-5 làm anchor, bỏ qua disambiguate
  4. Viết lại symptoms.php view (max 16, pre-sorted theo semantic)
  5. Thêm ApiController::rankSymptoms() + AJAX re-rank
  6. Test với 5 chief complaints: 2 K01-match, 3 K01-no-match

Sprint 2 — Quick Questions + Engine Update
  1. Tạo followup.php (3 câu hỏi)
  2. ExamController::followup() + cập nhật symptoms() redirect
  3. Chạy migrate_followup.php (add column + seed)
  4. Cập nhật YHCTEngine: applyTongueDelta, applyNguChiDelta
  5. Cập nhật ExamController::process() dùng followup data

Sprint 3 — Result Page hoàn chỉnh
  1. Thêm confidence badge vào result.php
  2. Thêm section "Theo dõi"
  3. Verify tất cả acceptance criteria
```

---

## 13. CÂU HỎI CẦN TRẢ LỜI TRƯỚC KHI CODE

1. **UX chuyển hướng:** Sau khi user tick triệu chứng và hệ thống re-rank, triệu chứng đã tick có bị xóa không? → *Đề xuất: KHÔNG — chỉ re-rank phần chưa tick*

2. **Ngưỡng hiển thị nhóm 3:** Sau bao nhiêu lần tick mới hiện nhóm "Để chẩn đoán chính xác hơn"? → *Đề xuất: sau 3 lần tick*

3. **Màu lưỡi:** 5 lựa chọn đủ chưa? Cần hình ảnh thực hay icon màu? → *Đề xuất: icon màu đơn giản là đủ, không cần ảnh thực*

4. **Skipping steps:** User có thể bỏ qua bước Quick Questions không? → *Đề xuất: KHÔNG — 3 câu hỏi bắt buộc, nhưng có option "Không biết"*

5. **Mobile first:** Trên điện thoại, nút tick có đủ to không? → *Đề xuất: minimum 44px touch target theo WCAG*

---

*Tài liệu này chờ phê duyệt. Không code bất kỳ tính năng nào cho đến khi được xác nhận.*
