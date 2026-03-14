# KẾ HOẠCH TRIỂN KHAI PHASE 2 — FULL SPEC
**Phiên bản:** 2.0 (viết lại hoàn toàn)
**Ngày:** 2026-03-13
**Stack:** Pure PHP 8.x + SQLite + Alpine.js + Tailwind CSS CDN
**Nguồn:** docs/13_prd_v3.md + docs/14_prd_addendum.md
**Trạng thái:** Phase 2A (nền tảng) hoàn thành — tài liệu này hướng dẫn Phase 2B+

---

## HIỆN TRẠNG — ĐÃ XÂY DỰNG (Phase 2A)

### ✅ Cơ sở hạ tầng
- Router, Controller, View, Auth, Database (PDO SQLite)
- 8 KB tables với 1,261 records đã import
- 4 engines cơ bản: RedFlagEngine, YHCTEngine, ClusterEngine, SafetyFilter
- 5-step exam flow: start → disambiguate → symptoms → process → result
- 3-role auth: Patient (no login), Doctor, Admin/Super Admin
- Admin: KB review (ẩn record), user management, session list

### ❌ Chưa có — Vẫn là kết quả rỗng
| Tính năng | PRD ref | Độ ưu tiên |
|-----------|---------|-----------|
| Kết quả thiếu Chứng/Pháp Trị (engine không match) | Core | **P0 — Lỗi** |
| Câu hỏi lưỡi + rêu lưỡi (Vọng chẩn) | C2 | P1 |
| Câu hỏi Ngũ Chí (cảm xúc 5 tạng) | C1 | P1 |
| Câu hỏi Functional Impact (chống minimization bias) | A3 | P1 |
| Câu hỏi Prior Treatment | A4 | P1 |
| Diagnostic Confidence Engine | B3 | P1 |
| Follow-up Safety Loop (section "Theo dõi") | B5 | P1 |
| Missing Key Questions Engine | B4 | P2 |
| Time-gated Clusters | B2 | P2 |
| Return Visit Recognition | C3 | P2 |
| Clinical Contradiction Detector | C4 | P2 |
| Symptom Chronology | C5 | P3 |
| Ngũ Chí → Bát Cương/Tạng Phủ mapping | C1 | P2 |
| Tongue → Bát Cương mapping | C2 | P2 |

---

## ROOT CAUSE: TẠI SAO KHÔNG CÓ CHỨNG/PHÁP TRỊ

### Vấn đề alignment

Engine K03/K04 dùng `required_symptoms` là các code như:
```
"headache_vertex_afternoon", "irritability", "dry_mouth"
```

Nhưng K02 symptoms được collect qua UI là:
```
"cephalgia", "emotional_dysregulation", "xerostomia"
```

→ Hai bộ code **không khớp nhau** → engine không tìm được pattern match.

### Giải pháp (JOB-FIX-01) — Làm NGAY

**Bước 1:** Thêm bảng cross-reference

```sql
CREATE TABLE IF NOT EXISTS symptom_aliases (
    canonical_code  TEXT NOT NULL,  -- code trong K02
    alias_code      TEXT NOT NULL,  -- code dùng trong K03/K04 required_symptoms
    PRIMARY KEY (canonical_code, alias_code)
);
CREATE INDEX idx_alias_alias ON symptom_aliases(alias_code);
```

**Bước 2:** Khi engine score pathogenesis, expand symptom set qua aliases

Trong `YHCTEngine.php`, thay vì dùng `$codeSet` trực tiếp, dùng:

```php
private function expandWithAliases(array $codes): array
{
    if (empty($codes)) return $codes;
    $ph   = implode(',', array_fill(0, count($codes), '?'));
    $pdo  = Database::get();
    // Tìm alias → canonical và canonical → alias
    $stmt = $pdo->prepare("
        SELECT alias_code FROM symptom_aliases WHERE canonical_code IN ($ph)
        UNION
        SELECT canonical_code FROM symptom_aliases WHERE alias_code IN ($ph)
    ");
    $params = array_merge($codes, $codes);
    $stmt->execute($params);
    $extras = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return array_unique(array_merge($codes, $extras));
}
```

**Bước 3:** Import alias data từ K03/K04 required_symptoms

Script `scripts/build_aliases.php` quét tất cả required_symptoms trong K03/K04, tìm code không có trong K02, và tạo bảng alias mapping bán tự động (cần review).

**Bước 4 (nhanh hơn):** Thêm `symptom_aliases` column vào `kb_symptoms`

```sql
ALTER TABLE kb_symptoms ADD COLUMN aliases TEXT; -- JSON array of alternate codes
```

Import aliases khi biết (ví dụ: `headache` → aliases: `["cephalgia","head_pain","dau_dau"]`).

---

## JOB-FIX-01: Sửa Engine Match (P0 — Làm trước)

**File cần sửa:** `app/engine/YHCTEngine.php`

```php
// Trong __construct, thêm:
private array $aliasMap = []; // canonical_code -> [alias1, alias2, ...]

public function __construct(...)
{
    // ... existing code ...
    $this->buildAliasMap();
}

private function buildAliasMap(): void
{
    $pdo  = Database::get();
    $rows = $pdo->query("SELECT canonical_code, alias_code FROM symptom_aliases")->fetchAll();
    foreach ($rows as $r) {
        $this->aliasMap[$r['canonical_code']][] = $r['alias_code'];
        $this->aliasMap[$r['alias_code']][] = $r['canonical_code'];
    }
}

private function buildSymptomSet(array $codes): array
{
    $expanded = $codes;
    foreach ($codes as $code) {
        if (isset($this->aliasMap[$code])) {
            $expanded = array_merge($expanded, $this->aliasMap[$code]);
        }
    }
    return array_flip(array_unique($expanded)); // O(1) lookup set
}
```

**Giải pháp tạm thời ngay lập tức:** Trong khi chờ alias data đầy đủ, score pathogenesis dùng **fuzzy match** trên zangfu_primary thay vì chỉ required_symptoms:

```php
// Nếu không có required_symptoms nào match → score chỉ dựa trên zangfu + bat_cuong
// Vẫn ra kết quả (dù kém chính xác hơn)
if (empty($required)) {
    // Pattern không có required_symptoms rõ ràng → score bằng zangfu/bat_cuong
    $score = 0.5; // base score
    // ... tiếp tục tính zangfu + bat_cuong bonus
}
```

---

## JOB-UI-01: Kết Quả Đầy Đủ (P0)

Kết quả hiện tại thiếu nhiều section. Đây là spec đầy đủ cho `views/exam/result.php`:

### Cấu trúc kết quả mục tiêu

```
┌─────────────────────────────────────────┐
│ Header: Tên BN, ngày, số triệu chứng   │
├─────────────────────────────────────────┤
│ [Nếu L1] EMERGENCY full-screen đỏ      │ ← Đã có
├─────────────────────────────────────────┤
│ [Nếu L2/L3] Warning banners            │ ← Đã có
├─────────────────────────────────────────┤
│ Bát Cương với bars                      │ ← Đã có
├─────────────────────────────────────────┤
│ Tạng Phủ scores                         │ ← Đã có
├─────────────────────────────────────────┤
│ 🌿 Chứng chính + Tin cậy badge          │ ← THIẾU
│   Dấu hiệu ủng hộ: ✓ ... ✓ ...        │ ← THIẾU
│   Chưa kiểm tra: ? ...                 │ ← THIẾU
├─────────────────────────────────────────┤
│ Pháp Trị: Nguyên tắc + Phương thuốc   │ ← THIẾU (ẩn nếu safety block)
│   Thuốc chủ yếu (tags)                 │ ← Có nhưng phụ thuộc match
│   Huyệt châm cứu                       │ ← THIẾU
│   Lời khuyên sinh hoạt                 │ ← THIẾU
├─────────────────────────────────────────┤
│ Kiêm Chứng (nếu có)                    │ ← Đã có khung
├─────────────────────────────────────────┤
│ YHHD correlates                         │ ← Đã có
├─────────────────────────────────────────┤
│ Clusters cần loại trừ                   │ ← Đã có
├─────────────────────────────────────────┤
│ 📋 THEO DÕI                             │ ← THIẾU
│   Nếu sau X ngày không đỡ → đi khám   │
│   Đi khám ngay nếu: ...               │
├─────────────────────────────────────────┤
│ Gửi cho bác sĩ (assign)                │ ← Đã có
└─────────────────────────────────────────┘
```

### Confidence badge spec

```php
// Trong result view
$confidence = $yhct['primary_pattern']['confidence'] ?? 'low';
$confLabel  = match($confidence) {
    'high'     => ['Khá chắc',       '●●●●', 'bg-green-100 text-green-700'],
    'moderate' => ['Có khả năng',    '●●●○', 'bg-yellow-100 text-yellow-700'],
    'low'      => ['Cần xác nhận',   '●●○○', 'bg-gray-100 text-gray-500'],
    default    => ['Chưa xác định',  '●○○○', 'bg-gray-100 text-gray-400'],
};
```

### Follow-up section spec (hardcode fallback)

```php
// Fallback follow-up rules (dùng khi chưa có DB table)
$followUpDays = match($primaryPattern['prevalence_vietnam'] ?? '') {
    'very_common' => 7,
    'common'      => 10,
    default       => 14,
};
// Escalation: dùng Red Flag L2 relevant cho pattern này
```

---

## JOB-EXAM-01: Bổ Sung Câu Hỏi Exam (P1)

### Luồng exam mới (4 steps → 5 steps)

```
1. start         — Triệu chứng chính + thông tin cá nhân
2. disambiguate  — Làm rõ phraseology (nếu có K01 match)
3. symptoms      — Checklist triệu chứng (hiện tại)
4. followup      — CÂU HỎI BỔ SUNG (MỚI) ← thêm bước này
5. process       — Chạy engine
6. result        — Kết quả
```

### Step 4: Trang câu hỏi bổ sung (`exam/followup`)

**File cần tạo:** `app/views/exam/followup.php`
**Controller method cần thêm:** `ExamController::followup()`

Trang này gồm các nhóm câu hỏi:

#### A. Thông tin bổ sung (luôn hỏi)

```
Q1 — Mức độ ảnh hưởng chức năng:
"Vì triệu chứng này, trong 3 ngày qua bạn có:"
○ Vẫn làm việc/sinh hoạt bình thường
○ Giảm hoạt động nhưng vẫn đi lại được
○ Phải nghỉ ở nhà, không đi làm/học
○ Phải nằm trên giường hầu hết thời gian

Q2 — Đã điều trị gì chưa:
"Với triệu chứng này, bạn đã thử:"
○ Chưa làm gì, mới xuất hiện
○ Nghỉ ngơi, uống nhiều nước
○ Tự mua thuốc uống (giảm đau, kháng sinh...)
○ Dùng YHCT (thuốc nam, bấm huyệt, đắp lá...)
○ Đã đi khám, đang điều trị nhưng chưa khỏi
```

#### B. Vọng chẩn tự quan sát

```
Q3 — Màu lưỡi (có hình minh họa):
"Soi gương, lưỡi bạn có màu gì?"
○ Hồng nhạt (bình thường)
○ Đỏ tươi
○ Đỏ thẫm/đỏ sẫm
○ Nhợt nhạt/trắng nhạt
○ Tím sạm

Q4 — Rêu lưỡi:
"Lớp phủ trên mặt lưỡi:"
○ Mỏng trắng (bình thường)
○ Trắng dày
○ Vàng mỏng
○ Vàng dày/vàng nhớt
○ Không có rêu (lưỡi trơn láng)
```

#### C. Ngũ Chí — 5 câu cảm xúc (luôn hỏi)

```
Q5: "Bạn có hay cáu gắt, bực bội, khó kiềm chế không?"
    → ảnh hưởng Can

Q6: "Bạn có hay hồi hộp, lo âu thái quá, tim đập nhanh không?"
    → ảnh hưởng Tâm

Q7: "Bạn hay lo nghĩ, suy tư nhiều, không dứt được không?"
    → ảnh hưởng Tỳ

Q8: "Bạn có hay buồn bã, dễ chảy nước mắt không?"
    → ảnh hưởng Phế

Q9: "Bạn có hay sợ hãi không rõ lý do, dễ giật mình không?"
    → ảnh hưởng Thận

Thang: [Không] [Đôi khi] [Thường xuyên] [Rất thường xuyên]
```

### Data schema bổ sung

```sql
-- Thêm vào exam_sessions
ALTER TABLE exam_sessions ADD COLUMN followup_answers TEXT; -- JSON

-- Tongue mapping table
CREATE TABLE IF NOT EXISTS tongue_mapping (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    obs_type        TEXT NOT NULL,  -- 'color' | 'coating'
    value           TEXT NOT NULL,
    bat_cuong_delta TEXT,           -- JSON {heat:+0.3, cold:-0.2, ...}
    zangfu_hint     TEXT,           -- JSON {liver:+0.2, kidney:+0.1, ...}
    weight          REAL DEFAULT 1.0,
    yhct_note       TEXT
);

-- Ngũ Chí mapping
CREATE TABLE IF NOT EXISTS ngu_chi_mapping (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    emotion_key     TEXT NOT NULL,  -- 'can','tam','ty','phe','than'
    zangfu_primary  TEXT NOT NULL,
    bat_cuong_delta TEXT,           -- JSON
    weight          REAL DEFAULT 1.0
);
```

### Tongue mapping data (hardcode trong scripts/seed_tongue.php)

```php
$tongueMappings = [
    // Color
    ['color', 'pale',      ['cold'=>0.3,'deficiency'=>0.3], ['spleen'=>0.2,'kidney'=>0.1]],
    ['color', 'red',       ['heat'=>0.4,'deficiency'=>0.2], ['heart'=>0.2,'liver'=>0.1]],
    ['color', 'dark_red',  ['heat'=>0.5,'excess'=>0.2],     ['heart'=>0.3,'liver'=>0.2]],
    ['color', 'purple',    ['excess'=>0.3,'interior'=>0.3], ['liver'=>0.3]],
    ['color', 'normal',    [],                              []],
    // Coating
    ['coating', 'thin_white',  [],                              []],
    ['coating', 'thick_white', ['cold'=>0.3,'interior'=>0.2],   ['spleen'=>0.2]],
    ['coating', 'thin_yellow', ['heat'=>0.2,'interior'=>0.1],   ['stomach'=>0.1]],
    ['coating', 'thick_yellow',['heat'=>0.4,'excess'=>0.3],     ['stomach'=>0.2,'liver'=>0.1]],
    ['coating', 'greasy',      ['deficiency'=>0.2,'interior'=>0.2], ['spleen'=>0.3]],
    ['coating', 'none',        ['deficiency'=>0.4,'heat'=>0.2], ['kidney'=>0.2,'stomach'=>0.2]],
];
```

### Ngũ Chí → Bát Cương/Tạng Phủ weights

```php
$nguChiWeights = [
    // [emotion, zangfu, bat_cuong_contribution_when_frequent]
    'can' => ['liver', ['heat'=>+0.2, 'excess'=>+0.1]],
    'tam' => ['heart', ['heat'=>+0.1, 'deficiency'=>+0.1]],
    'ty'  => ['spleen',['deficiency'=>+0.2]],
    'phe' => ['lung',  ['deficiency'=>+0.2, 'cold'=>+0.1]],
    'than'=> ['kidney',['deficiency'=>+0.3, 'cold'=>+0.1]],
];
// Scale: none=0, sometimes=0.3, often=0.7, very_often=1.0
```

---

## JOB-ENGINE-01: Cập Nhật YHCTEngine với Vọng Chẩn + Ngũ Chí (P1)

**File:** `app/engine/YHCTEngine.php`

Thêm method nhận followup_answers và điều chỉnh Bát Cương/Tạng Phủ scores trước khi derive:

```php
public function derive(array $collectedCodes, array $followupAnswers = []): array
{
    $symptomMap = $this->buildSymptomMap($collectedCodes);
    $batCuong   = $this->aggregateBatCuong($symptomMap);

    // NEW: Apply tongue observation delta
    $batCuong   = $this->applyTongueDelta($batCuong, $followupAnswers);

    // NEW: Apply Ngũ Chí delta
    $zangfu     = $this->aggregateZangfu($symptomMap);
    $zangfu     = $this->applyNguChiDelta($zangfu, $batCuong, $followupAnswers);

    $batCuong   = $this->applyVeto($batCuong, $symptomMap);

    // ... rest unchanged ...
}

private function applyTongueDelta(array $batCuong, array $answers): array
{
    $tongueColor   = $answers['tongue_color']   ?? null;
    $tongueCoating = $answers['tongue_coating'] ?? null;

    $pdo = Database::get();
    foreach ([['color',$tongueColor],['coating',$tongueCoating]] as [$type,$val]) {
        if (!$val || $val === 'normal' || $val === 'thin_white') continue;
        $row = $pdo->prepare("SELECT bat_cuong_delta FROM tongue_mapping
                              WHERE obs_type=? AND value=?")->execute([$type,$val]);
        // Apply delta...
    }
    return $batCuong;
}

private function applyNguChiDelta(array $zangfu, array &$batCuong, array $answers): array
{
    $scale = ['none'=>0,'sometimes'=>0.3,'often'=>0.7,'very_often'=>1.0];
    $map   = ['can'=>'liver','tam'=>'heart','ty'=>'spleen','phe'=>'lung','than'=>'kidney'];
    $heat  = ['can','tam'];
    $cold  = ['phe','than'];
    $def   = ['ty','phe','than'];

    foreach ($map as $emotion => $organ) {
        $freq = $scale[$answers["emotion_{$emotion}"] ?? 'none'] ?? 0;
        if ($freq < 0.3) continue;
        $zangfu[$organ] = ($zangfu[$organ] ?? 0) + $freq * 0.3;
        if (in_array($emotion, $heat)) $batCuong['heat'] += $freq * 0.1;
        if (in_array($emotion, $cold)) $batCuong['cold'] += $freq * 0.1;
        if (in_array($emotion, $def))  $batCuong['deficiency'] += $freq * 0.15;
    }
    return $zangfu;
}
```

---

## JOB-ENGINE-02: Diagnostic Confidence (P1)

**File:** `app/engine/YHCTEngine.php` — thêm confidence calculation

```php
private function calculateConfidence(string $chungCode, array $collectedCodes): array
{
    $pdo      = Database::get();
    $codeSet  = array_flip($collectedCodes);

    // Get key signs from kb_patterns diagnostic_criteria
    $pat = $pdo->prepare("SELECT diagnostic_criteria FROM kb_patterns
                          WHERE chung_code=? AND status='active' LIMIT 1");
    $pat->execute([$chungCode]);
    $row = $pat->fetch();
    if (!$row) return ['level'=>'low','supporting'=>[],'missing'=>[],'contradicting'=>[]];

    $criteria     = json_decode($row['diagnostic_criteria'] ?? '{}', true) ?? [];
    $required     = $criteria['required']       ?? [];
    $twoOrMore    = $criteria['two_or_more_of'] ?? [];

    $supporting   = [];
    $missing      = [];
    $contradicting= [];

    foreach ($required as $sym) {
        isset($codeSet[$sym]) ? $supporting[] = $sym : $contradicting[] = $sym;
    }
    foreach ($twoOrMore as $sym) {
        isset($codeSet[$sym]) ? $supporting[] = $sym : $missing[] = $sym;
    }

    $level = 'low';
    if (count($supporting) >= 4 && count($contradicting) === 0)        $level = 'high';
    elseif (count($supporting) >= 2 && count($contradicting) <= 1)     $level = 'moderate';

    return [
        'level'         => $level,
        'supporting'    => array_slice($supporting, 0, 5),
        'missing'       => array_slice($missing,    0, 3),
        'contradicting' => array_slice($contradicting, 0, 3),
    ];
}
```

Gọi trong `scorePatterns()`, thêm vào output:
```php
$scored[] = [
    'pattern'    => $pat,
    'score'      => round($score, 3),
    'confidence' => $this->calculateConfidence($pat['chung_code'], $codes),
];
```

---

## JOB-ENGINE-03: Minimization Bias + Prior Treatment (P1)

**File:** `app/engine/YHCTEngine.php` — thêm method

```php
public function applyFollowupModifiers(array &$result, array $followupAnswers, array $context = []): void
{
    $functional = $followupAnswers['functional_impact'] ?? '';
    $priorTx    = $followupAnswers['prior_treatment']   ?? '';

    // Minimization bias detection
    if (in_array($functional, ['stayed_home','bedridden'])) {
        $result['flags'][]          = 'functional_impact_significant';
        $result['physician_notes'][]= 'Bệnh nhân báo cáo chức năng suy giảm đáng kể.';
        // Boost urgency silently
        $result['urgency_boost']    = ($result['urgency_boost'] ?? 0) + 1;
    }

    // Người cao tuổi / ĐTĐ
    $age = (int)($context['patient_age'] ?? 0);
    if ($age >= 65) {
        $result['flags'][]          = 'elderly_underreport_risk';
        $result['physician_notes'][]= 'Người cao tuổi: có thể underreport đau và khó chịu.';
    }

    // Prior treatment YHCT nhưng không đỡ → tăng Thực, giảm Hư
    if ($priorTx === 'self_yhct') {
        $result['prior_tx_note'] = 'Đã dùng YHCT nhưng chưa cải thiện — ưu tiên xem xét thực chứng.';
        if (isset($result['bat_cuong'])) {
            $result['bat_cuong']['excess']     = min(1, ($result['bat_cuong']['excess'] ?? 0)     + 0.15);
            $result['bat_cuong']['deficiency'] = max(0, ($result['bat_cuong']['deficiency'] ?? 0) - 0.15);
        }
    }

    // Treatment refractory
    if ($priorTx === 'treated_unresolved') {
        $result['flags'][]          = 'treatment_refractory';
        $result['physician_notes'][]= 'Đã điều trị nhưng chưa khỏi — cần đánh giá chuyên sâu hơn.';
        $result['urgency_boost']    = ($result['urgency_boost'] ?? 0) + 1;
    }
}
```

---

## JOB-RESULT-01: Cập Nhật Trang Kết Quả (P1)

**File:** `app/views/exam/result.php`

### Thêm section Confidence

```html
<!-- Sau phần tên Chứng -->
<?php $conf = $yhct['primary_pattern']['confidence'] ?? [] ?>
<?php if (!empty($conf)): ?>
<div class="mt-2 flex flex-wrap gap-2 items-center">
  <span class="text-xs px-2 py-0.5 rounded-full font-medium
               <?= match($conf['level']??'low'){
                   'high'=>'bg-green-100 text-green-700',
                   'moderate'=>'bg-yellow-100 text-yellow-700',
                   default=>'bg-gray-100 text-gray-500'
               } ?>">
    <?= match($conf['level']??'low'){
        'high'=>'Khá chắc ●●●●',
        'moderate'=>'Có khả năng ●●●○',
        default=>'Cần xác nhận ●●○○'
    } ?>
  </span>
</div>
<?php if (!empty($conf['supporting'])): ?>
<p class="text-xs text-green-700 mt-1">
  ✓ <?= htmlspecialchars(implode('  ✓ ', $conf['supporting'])) ?>
</p>
<?php endif ?>
<?php if (!empty($conf['missing'])): ?>
<p class="text-xs text-gray-400 mt-0.5">
  ? Chưa kiểm tra: <?= htmlspecialchars(implode(', ', $conf['missing'])) ?>
</p>
<?php endif ?>
<?php endif ?>
```

### Thêm section Follow-up

```html
<!-- Cuối trang, trước disclaimer -->
<?php
$primaryName = $yhct['primary_pattern']['pattern']['name_vi'] ?? '';
$followDays  = 7; // default fallback
// TODO: Query follow_up_rules table khi có data
?>
<div class="bg-blue-50 rounded-2xl border border-blue-200 p-5">
  <h2 class="text-base font-semibold text-blue-800 mb-2">📋 Theo dõi</h2>
  <p class="text-sm text-gray-700">
    Nếu sau <strong><?= $followDays ?> ngày</strong> triệu chứng không cải thiện hoặc tệ hơn,
    hãy đến gặp bác sĩ.
  </p>
  <?php if (!empty($result['red_flags']['all'])): ?>
    <div class="mt-3">
      <p class="text-xs font-semibold text-red-600 mb-1">Đi khám ngay nếu xuất hiện:</p>
      <ul class="text-xs text-red-700 space-y-0.5">
        <?php foreach (array_slice($result['red_flags']['all'], 0, 3) as $rf): ?>
          <?php if ($rf['triage_level'] !== 'L1_emergency'): ?>
            <li>• <?= htmlspecialchars($rf['name_vi']) ?></li>
          <?php endif ?>
        <?php endforeach ?>
      </ul>
    </div>
  <?php endif ?>
  <?php if (!empty($result['yhct']['physician_notes'])): ?>
    <div class="mt-3 text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2">
      <?php foreach ($result['yhct']['physician_notes'] as $note): ?>
        <p>• <?= htmlspecialchars($note) ?></p>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</div>
```

### Thêm acupuncture + lifestyle advice

```html
<!-- Trong phần Pháp Trị, sau key_herbs -->
<?php if (!empty($pt['acupuncture_points'])): ?>
<div class="mt-2">
  <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Huyệt châm cứu gợi ý</p>
  <div class="flex flex-wrap gap-1">
    <?php foreach ($pt['acupuncture_points'] as $acup): ?>
      <span class="bg-purple-50 text-purple-700 text-xs px-2 py-0.5 rounded-full border border-purple-200">
        <?= htmlspecialchars($acup) ?>
      </span>
    <?php endforeach ?>
  </div>
</div>
<?php endif ?>

<?php if (!empty($pt['lifestyle_advice'])): ?>
<div class="mt-2">
  <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Lời khuyên sinh hoạt</p>
  <?php $advices = is_array($pt['lifestyle_advice']) ? $pt['lifestyle_advice'] : [$pt['lifestyle_advice']] ?>
  <ul class="text-xs text-gray-600 space-y-0.5">
    <?php foreach ($advices as $adv): ?>
      <li>• <?= htmlspecialchars($adv) ?></li>
    <?php endforeach ?>
  </ul>
</div>
<?php endif ?>
```

---

## JOB-EXAM-02: Controller ExamController — Cập Nhật (P1)

### Thêm route followup

**File:** `app/controllers/ExamController.php`

```php
// Thêm method:
public function followup(?string $token): void
{
    if ($this->isPost()) {
        $this->csrfCheck();
        $token   = $this->post('token');
        $session = $this->sessions->findByToken($token);
        if (!$session) { $this->notFound(); }

        // Collect all followup answers
        $answers = [
            'functional_impact'  => $this->post('functional_impact'),
            'prior_treatment'    => $this->post('prior_treatment'),
            'tongue_color'       => $this->post('tongue_color'),
            'tongue_coating'     => $this->post('tongue_coating'),
            'emotion_can'        => $this->post('emotion_can', 'none'),
            'emotion_tam'        => $this->post('emotion_tam', 'none'),
            'emotion_ty'         => $this->post('emotion_ty',  'none'),
            'emotion_phe'        => $this->post('emotion_phe', 'none'),
            'emotion_than'       => $this->post('emotion_than','none'),
        ];

        $this->sessions->update($session['id'], [
            'followup_answers' => json_encode($answers, JSON_UNESCAPED_UNICODE),
        ]);
        $this->sessions->saveAnswer($session['id'], 'followup', 'all', $answers);
        $_SESSION['followup_answers'][$token] = $answers;

        $this->redirect('exam/process/' . $token);
        return;
    }

    // GET
    if (!$token) { $this->notFound(); }
    $session = $this->sessions->findByToken($token);
    if (!$session || $session['status'] !== 'in_progress') { $this->notFound(); }

    View::setTitle('Thêm thông tin');
    $this->render('exam/followup', [
        'session' => $session,
        'csrf'    => $this->csrfToken(),
    ]);
}
```

### Cập nhật process() để dùng followup_answers

```php
public function process(?string $token): void
{
    // ... existing code ...
    $followup = $_SESSION['followup_answers'][$token] ?? [];
    // Hoặc lấy từ DB nếu session đã lưu
    if (empty($followup) && $session['followup_answers']) {
        $followup = json_decode($session['followup_answers'], true) ?? [];
    }

    // Pass followup vào engine
    $yhctResult = $yhctEngine->derive($symptoms, $followup);

    // Apply followup modifiers
    $yhctEngine->applyFollowupModifiers($yhctResult, $followup, [
        'patient_age' => $session['patient_age'],
    ]);

    // ... rest unchanged ...
}
```

### Cập nhật symptoms() — redirect đến followup thay vì process

```php
// Trong symptoms() POST handler, thay redirect:
// OLD: $this->redirect('exam/process/' . $token);
// NEW:
$this->redirect('exam/followup/' . $token);
```

### Cập nhật router App.php — thêm route 'followup'

Route `exam/followup/{token}` đã được xử lý tự động vì App.php parse `controller/action/param`.
Không cần thay đổi router.

---

## JOB-DB-01: Schema Bổ Sung (P1)

**File:** `scripts/create_schema.php` — thêm vào cuối:

```sql
-- Followup answers column
-- ALTER TABLE exam_sessions ADD COLUMN followup_answers TEXT;
-- (SQLite không support ALTER ADD nếu column đã có CHECK constraint phức tạp)
-- → Chạy migration script riêng

CREATE TABLE IF NOT EXISTS tongue_mapping (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    obs_type    TEXT NOT NULL CHECK(obs_type IN ('color','coating')),
    value       TEXT NOT NULL,
    bat_cuong_delta TEXT,  -- JSON {cold:0.3, heat:-0.1, ...}
    zangfu_hint TEXT,      -- JSON {spleen:0.2, kidney:0.1}
    weight      REAL DEFAULT 1.0,
    yhct_note   TEXT,
    UNIQUE(obs_type, value)
);

CREATE TABLE IF NOT EXISTS ngu_chi_mapping (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    emotion_key     TEXT NOT NULL UNIQUE,  -- can,tam,ty,phe,than
    zangfu_primary  TEXT NOT NULL,
    bat_cuong_delta TEXT,                  -- JSON applied at scale=1.0
    weight          REAL DEFAULT 1.0
);

CREATE TABLE IF NOT EXISTS symptom_aliases (
    canonical_code TEXT NOT NULL,
    alias_code     TEXT NOT NULL,
    PRIMARY KEY (canonical_code, alias_code)
);
CREATE INDEX IF NOT EXISTS idx_alias ON symptom_aliases(alias_code);

CREATE TABLE IF NOT EXISTS follow_up_rules (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    pattern_id          TEXT,              -- chung_code từ kb_patterns
    triage_level        TEXT,
    follow_up_days      INTEGER DEFAULT 7,
    escalation_trigger  TEXT,
    message_vi          TEXT,
    status              TEXT DEFAULT 'active'
);
```

**Script migration:** `scripts/migrate_followup.php`

```php
<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../app/storage/kham.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Add column if not exists (SQLite workaround)
$cols = $pdo->query("PRAGMA table_info(exam_sessions)")->fetchAll(PDO::FETCH_COLUMN, 1);
if (!in_array('followup_answers', $cols)) {
    $pdo->exec("ALTER TABLE exam_sessions ADD COLUMN followup_answers TEXT");
    echo "Added followup_answers column.\n";
}

// Seed tongue mapping
$tongueData = [
    ['color','pale',       '{"cold":0.3,"deficiency":0.3}', '{"spleen":0.2,"kidney":0.1}'],
    ['color','red',        '{"heat":0.4,"deficiency":0.2}', '{"heart":0.2,"liver":0.1}'],
    ['color','dark_red',   '{"heat":0.5,"excess":0.2}',     '{"heart":0.3,"liver":0.2}'],
    ['color','purple',     '{"excess":0.3,"interior":0.3}', '{"liver":0.3}'],
    ['color','normal',     null, null],
    ['coating','thin_white', null, null],
    ['coating','thick_white','{"cold":0.3,"interior":0.2}',  '{"spleen":0.2}'],
    ['coating','thin_yellow','{"heat":0.2,"interior":0.1}',  '{"stomach":0.1}'],
    ['coating','thick_yellow','{"heat":0.4,"excess":0.3}',   '{"stomach":0.2,"liver":0.1}'],
    ['coating','greasy',   '{"deficiency":0.2,"interior":0.2}','{"spleen":0.3}'],
    ['coating','none',     '{"deficiency":0.4,"heat":0.2}',  '{"kidney":0.2,"stomach":0.2}'],
];
$stmt = $pdo->prepare("INSERT OR IGNORE INTO tongue_mapping (obs_type,value,bat_cuong_delta,zangfu_hint) VALUES (?,?,?,?)");
foreach ($tongueData as $row) $stmt->execute($row);

// Seed ngu_chi mapping
$nguChiData = [
    ['can',  'liver',  '{"heat":0.2,"excess":0.1}'],
    ['tam',  'heart',  '{"heat":0.1,"deficiency":0.1}'],
    ['ty',   'spleen', '{"deficiency":0.2}'],
    ['phe',  'lung',   '{"deficiency":0.2,"cold":0.1}'],
    ['than', 'kidney', '{"deficiency":0.3,"cold":0.1}'],
];
$stmt = $pdo->prepare("INSERT OR IGNORE INTO ngu_chi_mapping (emotion_key,zangfu_primary,bat_cuong_delta) VALUES (?,?,?)");
foreach ($nguChiData as $row) $stmt->execute($row);

echo "Migration complete.\n";
```

---

## THỨ TỰ THỰC HIỆN

```
TUẦN 1 — Sửa lỗi nền + thêm câu hỏi
├── JOB-DB-01:    Chạy migrate_followup.php          [1h]
├── JOB-FIX-01:   Sửa engine match (symptom_aliases) [4h]
├── JOB-EXAM-01:  Tạo views/exam/followup.php        [3h]
├── JOB-EXAM-02:  Cập nhật ExamController + process  [2h]
└── JOB-ENGINE-01: Update YHCTEngine với Ngũ Chí/Lưỡi[3h]

TUẦN 2 — Kết quả đầy đủ
├── JOB-ENGINE-02: Diagnostic Confidence             [2h]
├── JOB-ENGINE-03: Minimization Bias + Prior Tx      [1h]
└── JOB-RESULT-01: Cập nhật result.php đầy đủ       [3h]

TUẦN 3 — Hoàn thiện
├── Time-gated Clusters                              [2h]
├── Follow-up rules table + seed data               [2h]
├── Return Visit Recognition                         [2h]
└── Clinical test cases                             [4h]
```

---

## CHECKLIST ĐỂ KẾT QUẢ ĐẦY ĐỦ

- [ ] Engine tìm được Chứng/Pháp Trị (symptom aliases fix)
- [ ] Hiển thị tên Chứng + Confidence badge
- [ ] Dấu hiệu ủng hộ / Chưa kiểm tra
- [ ] Pháp Trị: nguyên tắc + phương thuốc + thuốc chủ yếu
- [ ] Huyệt châm cứu gợi ý
- [ ] Lời khuyên sinh hoạt
- [ ] YHHD correlates
- [ ] Kiêm Chứng (nếu có)
- [ ] Clusters cần loại trừ
- [ ] Section "Theo dõi" (follow-up)
- [ ] Herb-drug safety warnings
- [ ] Physician notes (minimization bias, prior tx)
- [ ] Assign to doctor
