# Skill: PHP YHCT Web — Project Architecture & Conventions

> This skill file is for Claude agents working on the YHCT medical diagnosis web app.
> Load it at the start of any coding session on this project.

---

## 1. Project Overview

**Name:** Hệ thống Hỗ trợ Chẩn đoán YHCT (Traditional Vietnamese Medicine Diagnosis Support System)

**Purpose:** Help licensed YHCT doctors record patient chief complaints in free text, guide them through structured symptom selection, apply YHCT diagnostic logic (Bát Cương + Tạng Phủ), surface safety red flags, and produce a differential diagnosis result with ranked patterns (chứng).

**Critical safety rule:** The system is a *decision support tool only*. It never replaces a physician. Emergency red flags always suppress the YHCT result display and show an urgent referral message.

---

## 2. Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.1+ |
| Database | SQLite 3 (single file: `app/storage/kham.db`) |
| Frontend | Bootstrap 5.3, Vanilla JS (ES2020+) |
| CSS | Custom (`public/css/app.css`) with CSS variables |
| JS | Custom (`public/js/app.js`) — no jQuery |
| Embeddings | @xenova/transformers (browser, MiniLM-L6-v2) or OpenAI API |
| Server | Apache / PHP built-in dev server |
| Sessions | PHP native sessions + `exam_sessions` DB table for exam state |

---

## 3. URL Structure

```
Base: http://localhost/y/kham/
      http://cp1.step.com.vn:2083/y/kham/   (production)

Pattern: /{controller}/{method}/{param1}/{param2}

Examples:
  /                         → HomeController::index()
  /exam/start               → ExamController::start()
  /exam/symptoms/{sess_id}  → ExamController::symptoms($sessId)
  /exam/questions/{sess_id} → ExamController::questions($sessId)
  /exam/result/{sess_id}    → ExamController::result($sessId)
  /exam/api_rank            → ExamController::apiRank()  [POST JSON]
  /auth/login               → AuthController::login()
  /auth/logout              → AuthController::logout()
  /doctor/dashboard         → DoctorController::dashboard()
  /admin/dashboard          → AdminController::dashboard()
  /admin/users              → AdminController::users()
  /admin/kb_review          → AdminController::kbReview()
  /admin/exam_sessions      → AdminController::examSessions()
  /admin/embeddings         → AdminController::embeddings()
  /admin/api/docs-pending   → AdminController::apiDocsPending()   [GET JSON]
  /admin/api/save-embeddings → AdminController::apiSaveEmbeddings() [POST JSON]
  /admin/api/build-index    → AdminController::apiBuildIndex()    [POST JSON]
  /admin/api/openai-embed   → AdminController::apiOpenaiEmbed()   [POST JSON]
```

The router is `app/core/App.php`. It splits `REQUEST_URI`, strips the base path `/y/kham/`, and dispatches to `app/controllers/{Name}Controller.php`.

---

## 4. Directory Structure

```
kham/
├── app/
│   ├── .htaccess                  # Rewrite rules → index.php
│   ├── config.php                 # DB path, base URL, app settings
│   ├── index.php                  # Entry point (front controller)
│   ├── core/
│   │   ├── App.php                # Router / dispatcher
│   │   ├── Auth.php               # Static auth helpers
│   │   ├── Controller.php         # Base controller
│   │   ├── Database.php           # PDO singleton (get(), query(), row(), etc.)
│   │   ├── View.php               # View::render($template, $data)
│   │   └── helpers.php            # flash(), redirect(), csrf_token(), h(), etc.
│   ├── controllers/
│   │   ├── AdminController.php
│   │   ├── AuthController.php
│   │   ├── DoctorController.php
│   │   ├── ExamController.php
│   │   └── HomeController.php
│   ├── engine/
│   │   ├── YHCTEngine.php         # Main orchestrator (runs phases 0-3)
│   │   ├── RedFlagEngine.php      # Safety layer (L1/L2/L3)
│   │   ├── ClusterEngine.php      # Symptom cluster grouping
│   │   ├── SafetyFilter.php       # Post-result safety suppression
│   │   └── HypothesisEngine.php   # Live rerank for sidebar AJAX
│   ├── models/
│   │   ├── BaseModel.php          # PDO helpers (find, findAll, insert, update)
│   │   ├── ExamSessionModel.php   # exam_sessions table CRUD
│   │   ├── KBModel.php            # K01–K08 knowledge base queries
│   │   └── UserModel.php          # users table CRUD
│   ├── storage/
│   │   └── kham.db                # SQLite database file
│   └── views/
│       ├── _layout.php            # Main HTML shell (navbar, footer, meta)
│       ├── auth/
│       │   └── login.php
│       ├── home/
│       │   └── index.php
│       ├── exam/
│       │   ├── start.php          # Chief complaint form (Phase 0)
│       │   ├── symptoms.php       # Symptom picker grid (Phase 1)
│       │   ├── disambiguate.php   # Disambiguation questions (Phase 2)
│       │   └── result.php         # Final result (Phase 3)
│       ├── doctor/
│       │   ├── dashboard.php
│       │   └── session.php
│       ├── admin/
│       │   ├── dashboard.php
│       │   ├── users.php
│       │   ├── kb_review.php
│       │   ├── exam_sessions.php
│       │   └── embeddings.php
│       └── errors/
│           ├── 403.php
│           └── 404.php
├── data/                          # Raw JSON knowledge base (import source)
│   ├── 00_data_inventory.md
│   ├── k01_observable_phrases/    # Batch JSONs: observable symptom phrases
│   ├── k02_symptoms/              # Batch JSONs: canonical symptoms
│   ├── k03_pathogenesis/          # Batch JSONs: pathogenesis mechanisms
│   ├── k04_patterns/              # Batch JSONs: diagnostic patterns (chứng)
│   ├── k05_red_flags/             # Batch JSONs: red flag definitions
│   ├── k06_clusters/              # Batch JSONs: symptom clusters
│   ├── k07_herb_drug/             # Batch JSONs: herb-drug interactions
│   └── k08_kiem_chung/            # Batch JSONs: validation rules
├── docs/                          # PRDs and architecture documents
├── public/
│   ├── css/app.css
│   ├── js/app.js
│   └── embeddings/                # Saved .bin or .json vector files
├── scripts/
│   ├── create_schema.php          # Create/reset DB schema
│   ├── import_all.php             # Master import runner
│   ├── import/                    # Per-table importers (import_k01.php … k08)
│   ├── build_aliases.php
│   ├── check_alignment.php
│   ├── debug_patterns.php
│   ├── seed_yhct_symptoms.php
│   └── test_engine.php
├── skills/
│   └── php-yhct-web.md            # This file
└── SKILLS.md                      # Skill index
```

---

## 5. Core Conventions

### MVC Pattern

- **Router** (`App.php`): maps `{Controller}/{method}/{params}` → instantiates controller, calls method.
- **Controllers** extend `Controller` base class. Access view via `$this->view->render('folder/template', $data)`.
- **Models** extend `BaseModel` which wraps `Database::get()` PDO singleton.
- **Views** are plain PHP files. Always include via `View::render($template, $data)` — never `include` directly from a controller.

### Database Access

```php
// Singleton connection
$db = Database::get();

// Via BaseModel
$model = new ExamSessionModel();
$session = $model->find($id);          // SELECT * WHERE id = ?
$all = $model->findAll('user_id=?', [$uid]);
$model->insert([...]);
$model->update($id, [...]);

// Raw query
$rows = $db->query("SELECT ...", [$param])->fetchAll(PDO::FETCH_ASSOC);
$row  = $db->row("SELECT ...", [$id]);
```

**IMPORTANT:** SQLite stores JSON fields as TEXT. Always `json_decode($row['field'], true)` before use, and `json_encode($array)` before storing.

### CSRF Protection

Every POST form must include `<?= csrf_field() ?>` (outputs a hidden input).
Every AJAX POST must include the header `X-CSRF-Token: {token}`.
Verification is done in `Controller::requireCsrf()` (call at top of POST handlers).

### Flash Messages

```php
flash('success', 'Lưu thành công!');
redirect('/doctor/dashboard');

// In views:
// _layout.php automatically renders flash messages as dismissible alerts
```

### Authentication

```php
Auth::check()           // bool — is user logged in?
Auth::user()            // array — current user row, or null
Auth::role()            // 'admin' | 'doctor' | null
Auth::requireLogin()    // redirect to /auth/login if not logged in
Auth::requireRole('admin') // 403 if wrong role
```

`session_id` for exam sessions is a UUID v4 string generated on exam start — it is **not** the PHP `session_id()`.

---

## 6. Knowledge Base Structure (K01–K08)

| Table | Description | Key columns |
|---|---|---|
| `k01_phrases` | Observable patient phrases (what patients say) | `id`, `phrase`, `symptom_code`, `weight`, `aliases` (JSON) |
| `k02_symptoms` | Canonical YHCT symptoms | `code`, `name_vn`, `name_en`, `organ_codes` (JSON), `bat_cuong_axes` (JSON), `category` |
| `k03_pathogenesis` | Pathogenesis / etiology mechanisms | `id`, `organ`, `mechanism`, `linked_symptoms` (JSON) |
| `k04_patterns` | Diagnostic patterns (Chứng) | `code`, `name_vn`, `name_en`, `organ`, `anchor_symptoms` (JSON), `supporting_symptoms` (JSON), `bat_cuong` (JSON), `treatment_principle`, `herb_formula`, `acupoints` (JSON) |
| `k05_red_flags` | Red flag symptoms | `code`, `name_vn`, `urgency_level` (L1/L2/L3), `action`, `suppress_yhct` (bool) |
| `k06_clusters` | Symptom clusters | `id`, `cluster_name`, `symptom_codes` (JSON), `pattern_hints` (JSON) |
| `k07_herb_drug` | Herb-drug interactions | `id`, `herb`, `drug`, `interaction_type`, `severity`, `population` |
| `k08_validation` | Validation / kiem chung rules | `id`, `rule_name`, `conditions` (JSON), `action` |

All JSON columns are stored as TEXT in SQLite. Always decode on read.

---

## 7. Engine Pipeline

```
User input (chief complaint text)
    │
    ▼
Phase 0 — Context Extraction (browser JS + PHP)
    • ContextExtractor.extract(text) finds trigger phrases
    • Boosts weight of related symptom codes
    • PHP side: YHCTEngine::extractContext($chiefComplaint)
    │
    ▼
Phase 1 — Symptom Selection (exam/symptoms view)
    • User selects from rendered symptom cards
    • Contradiction checking (client-side)
    • Live rerank via AJAX → HypothesisEngine::rank($codes)
    │
    ▼
Phase 2 — Disambiguation (exam/disambiguate view)
    • QuickQuestions: yes/no/severity questions for ambiguous symptoms
    • Answers stored in exam_sessions.context_json
    │
    ▼
Phase 3 — Full Analysis (YHCTEngine::analyze)
    • RedFlagEngine::screen($codes)          → L1/L2/L3 flags
    • ClusterEngine::match($codes)           → cluster matches
    • Pattern scoring via RelevanceScore formula (see §8)
    • SafetyFilter::apply($results, $flags)  → suppress if needed
    │
    ▼
Result (exam/result view)
    • Top pattern + alternatives
    • Bat Cuong axes
    • Treatment principle + formula
    • Safety warnings
```

---

## 8. RelevanceScore Formula

```
Score = W1×Anchor + W2×Organ + W3×CoOccurrence + W4×Category + W5×RedFlag + W6×Context

Where:
  W1 = 3.0  — Anchor symptom match (symptom is in pattern's anchor_symptoms list)
  W2 = 1.5  — Organ system match (symptom organ overlaps with pattern organ)
  W3 = 1.2  — Co-occurrence bonus (symptom commonly co-occurs with other selected symptoms)
  W4 = 0.8  — Category match (same YHCT category group)
  W5 = 2.0  — Red flag modifier (severity multiplier or suppression)
  W6 = 1.0  — Context boost (from Phase 0 trigger detection)

Final score is normalized to [0, 100] for display.
Minimum 2 matching symptoms required for a pattern to appear in results.
```

---

## 9. Bát Cương (Eight Principles) Axes

Each axis is a bipolar dimension. A symptom contributes positive or negative values.

| Axis | Pole A | Pole B | CSS class |
|---|---|---|---|
| Yin/Yang | Âm (Yin) | Dương (Yang) | `yin` / `yang` |
| Interior/Exterior | Lý (Interior) | Biểu (Exterior) | `interior` / `exterior` |
| Cold/Heat | Hàn (Cold) | Nhiệt (Heat) | `cold` / `heat` |
| Deficiency/Excess | Hư (Deficiency) | Thực (Excess) | `deficiency` / `excess` |

Stored in `k02_symptoms.bat_cuong_axes` as JSON:
```json
{"heat": 0.8, "cold": 0.0, "yang": 0.6, "yin": 0.0, "interior": 0.5, "exterior": 0.0, "excess": 0.7, "deficiency": 0.0}
```

---

## 10. YHCT Organ Systems (Tạng Phủ)

| Organ | Vietnamese | Color | CSS class |
|---|---|---|---|
| Liver | Can (Gan) | Blue #3a7bbf | `badge-organ-can` |
| Kidney | Thận | Near-black #2c2c2c | `badge-organ-than` |
| Spleen-Stomach | Tỳ Vị | Gold #c9a227 | `badge-organ-ty-vi` |
| Heart | Tâm | Red #c0392b | `badge-organ-tam` |
| Lung | Phế | Gray #7f8c8d | `badge-organ-phe` |

Organ codes used in DB: `can`, `than`, `ty_vi`, `tam`, `phe`.

---

## 11. Safety Rules

| Level | Name | Behavior |
|---|---|---|
| L1 | Emergency | Show full-screen red emergency banner. Suppress YHCT result entirely. Display 115 hotline. |
| L2 | Urgent | Show red warning banner above result. If `suppress_yhct=true` on flag, hide pattern result. Show referral note. |
| L3 | Watch | Show yellow caution note alongside YHCT result. YHCT result still fully shown. |

`RedFlagEngine::screen(array $symptomCodes): array` returns:
```php
[
  'level'       => 'L1' | 'L2' | 'L3' | null,
  'flags'       => [...],        // matched red flag rows
  'suppress'    => bool,
  'message_vn'  => string,
]
```

---

## 12. Common Gotchas

1. **JSON fields in SQLite** — All array/object fields (`anchor_symptoms`, `bat_cuong_axes`, `organ_codes`, `aliases`, etc.) are stored as JSON text. Always `json_decode($val, true)` on read. Never assume they are arrays.

2. **`session_id` is UUID** — `exam_sessions.session_id` is a `UUID v4` string (e.g. `"a1b2c3d4-..."`), not PHP's `session_id()`. Never confuse them. The PHP session stores the logged-in user; the DB record stores exam state.

3. **Base URL in links** — Always use the `base_url()` helper or `APP_BASE` constant when generating hrefs. Never hardcode `/y/kham/`.

4. **CSRF on every POST** — Both form POSTs and AJAX POSTs require the token. Missing it returns a 403 JSON error.

5. **View data escaping** — Use `h($value)` helper (wraps `htmlspecialchars`) for any user-supplied data echoed in views.

6. **Pattern minimum threshold** — Patterns with score < 5.0 (after normalization) are not shown in results to avoid noise.

7. **Embedding vectors** — Stored as JSON arrays (float[]) in the `embeddings` table. For HNSW/cosine search, vectors are also serialized to `public/embeddings/{doc_type}.bin` by the build-index job.

8. **`suppress_yhct` flag** — When any L2 red flag has `suppress_yhct=1`, the entire YHCT pattern section must be hidden from the result view. Only the warning + referral is shown.

9. **Locale** — All UI text is Vietnamese (`vi-VN`). Use UTF-8 everywhere. DB pragma: `PRAGMA encoding = "UTF-8"`.

10. **Mobile first** — CSS is written mobile-first. The symptom grid collapses to 1 column on small screens. Test at 375px width.

---

## 13. Adding a New Feature — Checklist

- [ ] Create/modify controller method
- [ ] Add route awareness in `App.php` if new controller
- [ ] Create/modify model if new DB interaction
- [ ] Create view file in correct subfolder
- [ ] Add CSRF token to any new form
- [ ] Add `Auth::requireLogin()` / `Auth::requireRole()` to new controller methods
- [ ] Escape all user output with `h()`
- [ ] Update `body data-page` attribute in view if JS module needed
- [ ] json_decode any JSON columns before use
- [ ] Write test in `scripts/test_engine.php` if engine change
