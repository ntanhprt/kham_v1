# KẾ HOẠCH TRIỂN KHAI HỆ THỐNG KHÁM TỔNG QUÁN

**Phiên bản:** 1.0
**Ngày:** 2026-03-12
**Stack:** PHP 8.3 + PostgreSQL + Redis + Vue.js 3 + Tailwind CSS

---

## PHẦN 1: TRIẾT LÝ XÂY DỰNG DỮ LIỆU

### 1.1 LLM-First Data Strategy

**Nguyên tắc:**
- LLM sinh dữ liệu → hệ thống import hàng loạt → chuyên gia review → xóa những gì sai
- Không yêu cầu chuyên gia ngồi điền từng dòng
- Dữ liệu cần đủ lớn để cover phần lớn trường hợp lâm sàng Việt Nam

**Mục tiêu quy mô tối thiểu để hệ thống hoạt động tốt:**

| Loại dữ liệu | Tối thiểu | Mục tiêu v1.0 | Ghi chú |
|-------------|---------|--------------|---------|
| Observable phrases (từ dân gian) | 300 | 800+ | Bao gồm biến thể vùng miền |
| Disambiguation rules | 150 | 400+ | Mỗi phrase có 4-6 lựa chọn |
| Clinical symptoms (chuẩn hóa) | 300 | 600+ | |
| Observable→Symptom mappings | 600 | 1500+ | |
| Bát Cương weights | 1200 | 3000+ | 6 dimensions × symptoms |
| Tạng Phủ weights | 1500 | 3000+ | 5 tạng × symptoms |
| Pathogenesis rules | 80 | 150+ | |
| Pattern definitions | 120 | 250+ | |
| Pattern differential trees | 50 | 100+ | |
| Pháp Trị | 120 | 250+ | 1:1 với patterns |
| Kiêm Chứng | 30 | 80+ | |
| Pattern key signs | 400 | 1000+ | |
| Follow-up rules | 200 | 500+ | |
| Red flag rules | 47 | 100+ | |
| Cluster rules | 30 | 60+ | |
| Herb-drug contraindications | 80 | 200+ | |
| Disambiguation whitelist | 150 | 500+ | |
| Chief complaint templates | 50 | 150+ | |

### 1.2 Quy Trình Dữ Liệu

```
LLM Batch Generation
       ↓
JSON/CSV output (exact schema)
       ↓
Bulk Import Tool (PHP CLI)
       ↓
Review Dashboard (web UI)
   ├── Approve (default)
   ├── Edit inline
   └── Delete (nếu sai)
       ↓
Active in system
```

### 1.3 Cách dùng prompt trong tài liệu này

Mỗi job dữ liệu có:
1. **SYSTEM PROMPT**: Copy vào system prompt của LLM
2. **USER PROMPT**: Copy vào user message, thay `[BATCH_N]` và tham số
3. **OUTPUT SCHEMA**: Exact JSON format LLM phải trả về
4. **VALIDATION RULES**: Hệ thống tự validate trước khi import

---

## PHẦN 2: DATA GENERATION JOBS

---

### JOB-K01: Observable Phrases & Disambiguation Rules

**Mục tiêu:** 800+ phrases dân gian + 400+ disambiguation rules
**Batch size:** 50 phrases mỗi lần gọi LLM
**Tổng số lần gọi:** ~16 lần

---

**SYSTEM PROMPT:**

```
Bạn là chuyên gia y tế kết hợp Đông Tây y với kinh nghiệm lâm sàng 20 năm tại Việt Nam.
Bạn am hiểu ngôn ngữ dân gian miền Bắc, miền Trung và miền Nam khi mô tả triệu chứng.
Bạn hiểu sâu cả ontology YHCT (TCMM) và YHHD (Western Medicine).

Nhiệm vụ của bạn là tạo ra bảng ánh xạ: từ ngữ dân gian bệnh nhân Việt Nam dùng
→ clinical symptom codes chuẩn hóa, kèm câu hỏi phân loại để làm rõ nghĩa.

Bệnh nhân Việt thường mô tả triệu chứng không chính xác về mặt y học.
Ví dụ: "nóng trong người" có thể là 5 tình trạng khác nhau.
Hệ thống cần hỏi lại (disambiguation) để map đúng sang clinical code.

QUAN TRỌNG: Output phải là JSON hợp lệ, đúng schema, không có text ngoài JSON.
```

**USER PROMPT:**

```
Tạo BATCH [BATCH_N] gồm 50 observable phrases tiếng Việt dân gian.

NHÓM ƯU TIÊN cho batch này: [CHỌN MỘT]
  - Batch 1-3: Triệu chứng tiêu hóa (bụng, dạ dày, đại tiện, ăn uống)
  - Batch 4-6: Triệu chứng đầu, cổ, thần kinh (đau đầu, chóng mặt, mất ngủ)
  - Batch 7-9: Triệu chứng tim mạch, hô hấp (tức ngực, khó thở, hồi hộp)
  - Batch 10-12: Triệu chứng cơ xương khớp (đau lưng, đau khớp, tê bì)
  - Batch 13-15: Triệu chứng toàn thân (mệt mỏi, sốt, ra mồ hôi)
  - Batch 16+: Triệu chứng phụ khoa, tiết niệu, da liễu

QUY TẮC:
1. Bao gồm cả cách nói miền Bắc, Trung, Nam nếu có khác biệt
2. Mỗi phrase có 4-6 disambiguation options (forced choice)
3. Mỗi option map sang danh sách symptom_codes chuẩn
4. Một số phrases không cần disambiguation (rõ nghĩa → map thẳng)
5. Bao gồm các phrase vừa có nghĩa YHCT vừa có nghĩa YHHD

OUTPUT FORMAT (JSON array, 50 objects):

[
  {
    "phrase_id": "OBS_[BATCH_N]_001",
    "phrase_vi": "nóng trong người",
    "variants_vi": ["nóng trong", "nóng bụng", "người nóng từ trong"],
    "regions": ["all"],
    "requires_disambiguation": true,
    "disambiguation_question": "Bạn cảm thấy 'nóng trong người' gần nhất với cảm giác nào?",
    "options": [
      {
        "option_code": "A",
        "label_vi": "Ấm nóng bên trong, hay khát nước, miệng khô",
        "symptom_codes": ["internal_heat", "thirst", "dry_mouth"],
        "yhct_hint": "Nhiệt chứng thực",
        "yhhd_hint": "Inflammation, fever low grade"
      },
      {
        "option_code": "B",
        "label_vi": "Nóng lòng bàn tay, bàn chân, nhiều về chiều tối",
        "symptom_codes": ["palmar_heat", "afternoon_fever_sensation"],
        "yhct_hint": "Âm hư sinh nhiệt",
        "yhhd_hint": "Low-grade fever, autonomic dysfunction"
      },
      {
        "option_code": "C",
        "label_vi": "Nóng rát vùng dạ dày, ợ chua",
        "symptom_codes": ["epigastric_burning", "acid_regurgitation"],
        "yhct_hint": "Vị nhiệt",
        "yhhd_hint": "GERD, gastritis"
      },
      {
        "option_code": "D",
        "label_vi": "Nóng bừng mặt, dễ cáu gắt",
        "symptom_codes": ["facial_flushing", "irritability", "heat_sensation"],
        "yhct_hint": "Can uất hóa hỏa hoặc Can dương vượng",
        "yhhd_hint": "Hypertension, anxiety, menopause"
      },
      {
        "option_code": "E",
        "label_vi": "Sóng nhiệt từ trong, vã mồ hôi nhiều",
        "symptom_codes": ["heat_flush", "diaphoresis", "hot_flashes"],
        "yhct_hint": "Âm hư dương vượng, tiền mãn kinh",
        "yhhd_hint": "Menopause, hyperthyroidism"
      }
    ],
    "red_flag_trigger": null,
    "notes": "Rất phổ biến, cần disambiguation cẩn thận"
  },
  ...49 objects nữa...
]

VALIDATION: Mỗi object PHẢI có đủ các field trên. symptom_codes phải dùng snake_case tiếng Anh.
```

---

### JOB-K02: Clinical Symptom Ontology

**Mục tiêu:** 600+ symptoms chuẩn hóa với full YHCT + YHHD mapping
**Batch size:** 40 symptoms mỗi lần

---

**SYSTEM PROMPT:**

```
Bạn là chuyên gia xây dựng ontology y tế kết hợp ICD-11 (YHHD) và TCM Pattern Ontology.
Bạn tạo bảng triệu chứng chuẩn hóa làm cầu nối giữa ngôn ngữ dân gian và hệ thống suy luận.

Mỗi symptom có:
- Code chuẩn hóa (snake_case tiếng Anh)
- Tên tiếng Việt chuẩn
- Trọng số Bát Cương (6 dimensions: cold, heat, deficiency, excess, exterior, interior)
- Trọng số Tạng Phủ (5 organs: liver, heart, spleen, lung, kidney)
- ICD-11 approximate code (nếu có)
- Severity weight (triệu chứng này nặng đến đâu nếu hiện diện)
- Time sensitivity (triệu chứng cấp hay mạn)

OUTPUT phải là JSON hợp lệ, không text ngoài JSON.
```

**USER PROMPT:**

```
Tạo BATCH [BATCH_N] gồm 40 clinical symptoms thuộc nhóm: [NHÓM]

NHÓM ưu tiên theo batch:
  Batch 1-2: Đau đầu và triệu chứng thần kinh (headache variants, dizziness, etc.)
  Batch 3-4: Tim mạch và hô hấp
  Batch 5-6: Tiêu hóa (toàn bộ GI tract)
  Batch 7-8: Cơ xương khớp
  Batch 9-10: Tiết niệu và sinh dục
  Batch 11-12: Tâm thần kinh và giấc ngủ
  Batch 13-14: Da và nội tiết
  Batch 15+: Toàn thân, sốt, đặc biệt

OUTPUT FORMAT:

[
  {
    "symptom_code": "temporal_headache",
    "name_vi": "Đau đầu vùng thái dương",
    "name_en": "Temporal headache",
    "category": "neurological",
    "icd11_approximate": "MG30.0",
    "severity_weight": 0.6,
    "time_sensitivity": "subacute",
    "bat_cuong_weights": {
      "cold": 0.1,
      "heat": 0.4,
      "deficiency": 0.3,
      "excess": 0.4,
      "exterior": 0.2,
      "interior": 0.7
    },
    "zangfu_weights": {
      "liver": 0.7,
      "heart": 0.2,
      "spleen": 0.1,
      "lung": 0.0,
      "kidney": 0.3
    },
    "red_flag_level": null,
    "cluster_contribution": ["meningitis_triad", "tia_cluster"],
    "yhct_clinical_note": "Đau thái dương → Can kinh đi qua. Nếu kèm đắng miệng → Can đởm uất nhiệt",
    "yhhd_clinical_note": "Temporal headache: tension type, migraine, temporal arteritis (elderly)"
  },
  ...
]

QUY TẮC QUAN TRỌNG:
- bat_cuong_weights: tổng không cần = 1.0, mỗi dimension độc lập (0.0 đến 1.0)
- zangfu_weights: tổng không cần = 1.0, nhiều tạng có thể cùng cao
- severity_weight: 0.1 (rất nhẹ) đến 1.0 (cực kỳ nguy hiểm)
- time_sensitivity: "acute" (<24h) / "subacute" (1-7d) / "chronic" (>1 week)
- red_flag_level: null / "L1_emergency" / "L2_urgent" / "L3_watch"
```

---

### JOB-K03: Pathogenesis Rules (Bệnh Cơ)

**Mục tiêu:** 150+ pathogenesis rules
**Batch size:** 30 rules mỗi lần

---

**SYSTEM PROMPT:**

```
Bạn là thầy thuốc YHCT có 20 năm kinh nghiệm lâm sàng và nghiên cứu học thuật.
Bạn thành thạo biện chứng luận trị (Pattern Identification and Treatment Determination).

Nhiệm vụ: Tạo bảng Bệnh Cơ (Pathogenesis) – tầng 5 trong chuỗi reasoning YHCT.

Bệnh Cơ là cơ chế bệnh sinh cụ thể, được xác định từ tổ hợp:
  Tạng Phủ (Organ) × Bát Cương (Pattern Dimension) → Bệnh Cơ

Ví dụ:
  liver + heat + excess → liver_fire_rising (Can hỏa vượng)
  liver + heat + deficiency → liver_yin_deficiency_with_yang_rising (Can âm hư dương vượng)
  spleen + deficiency + cold → spleen_yang_deficiency (Tỳ dương hư)

Bệnh Cơ quyết định Chứng và Pháp Trị. Không thể bỏ qua tầng này.
OUTPUT phải là JSON hợp lệ.
```

**USER PROMPT:**

```
Tạo BATCH [BATCH_N] gồm 30 Bệnh Cơ (pathogenesis mechanisms) cho tạng: [TẠNG]

Danh sách tạng theo batch:
  Batch 1: Can (Liver) – tất cả bệnh cơ của Can
  Batch 2: Thận (Kidney) – tất cả bệnh cơ của Thận
  Batch 3: Tỳ + Vị (Spleen/Stomach)
  Batch 4: Tâm + Tiểu trường (Heart/SI)
  Batch 5: Phế + Đại trường (Lung/LI)
  Batch 6: Liên tạng (multi-organ mechanisms) – Khí trệ huyết ứ, Đàm, v.v.

OUTPUT FORMAT:

[
  {
    "pathogenesis_code": "liver_fire_rising",
    "name_vi": "Can hỏa vượng",
    "name_en": "Liver Fire Rising",
    "primary_organ": "liver",
    "secondary_organs": [],
    "bat_cuong_profile": {
      "primary_dimension": "heat",
      "secondary_dimension": "excess",
      "interior_exterior": "interior",
      "deficiency_excess": "excess"
    },
    "trigger_conditions": {
      "required_organ_score": 0.5,
      "required_bat_cuong": {
        "heat": ">= 0.6",
        "excess": ">= 0.5"
      },
      "required_bat_cuong_logic": "AND"
    },
    "differentiating_from": [
      {
        "pathogenesis_code": "liver_yang_rising",
        "key_difference": "Can hỏa: sốt cao, táo bón, tiểu vàng (thực nhiệt). Can dương: ít táo bón hơn, kèm mệt mỏi (hư + thực)"
      }
    ],
    "common_symptoms": ["severe_headache", "red_eyes", "bitter_taste", "constipation", "irritability", "tinnitus"],
    "red_flag_potential": "medium",
    "yhhd_correlates": ["hypertension crisis", "migraine", "anxiety disorder"],
    "pháp_trị_direction": "thanh_can_ta_hoa",
    "caution": "Không dùng ôn bổ. Hạn chế cay nóng."
  },
  ...
]
```

---

### JOB-K04: Pattern Definitions + Differential Trees

**Mục tiêu:** 250+ patterns + 100+ differential trees
**Batch size:** 25 patterns mỗi lần

---

**SYSTEM PROMPT:**

```
Bạn là chuyên gia YHCT chuyên về biện chứng phân biệt (Differential Pattern Diagnosis).
Bạn xây dựng cơ sở tri thức để AI có thể tự suy luận ra Chứng (Pattern) từ Bệnh Cơ.

Chứng là kết quả cuối cùng của quá trình biện chứng YHCT.
Cùng một Bệnh Cơ có thể cho nhiều Chứng khác nhau tùy theo:
  - Triệu chứng phân biệt (differentiating symptoms)
  - Mức độ nặng nhẹ
  - Bối cảnh bệnh nhân (tuổi, thể chất, lịch sử bệnh)

Ví dụ: liver_fire_rising có thể là:
  - Can Hỏa Vượng (thực nhiệt thuần) nếu: đại tiện táo, sốt cao, miệng đắng, đỏ mắt
  - Can Dương Vượng (âm hư + dương vượng) nếu: mệt mỏi + khô miệng + đau đầu chiều

Differential reasoning là điểm phân biệt hệ thống YHCT với symptom checker thông thường.
OUTPUT phải là JSON hợp lệ.
```

**USER PROMPT:**

```
Tạo BATCH [BATCH_N] gồm 25 Pattern definitions với đầy đủ differential trees.

NHÓM cho batch này: [NHÓM]
  Batch 1-2: Can patterns (tất cả biến thể của Can)
  Batch 3-4: Thận patterns
  Batch 5-6: Tỳ Vị patterns
  Batch 7-8: Tâm patterns
  Batch 9-10: Phế patterns
  Batch 11+: Liên tạng, Đặc biệt (Kinh nguyệt, Sau sinh, Người cao tuổi)

OUTPUT FORMAT:

[
  {
    "pattern_id": "liver_yang_rising",
    "pattern_vi": "Can Dương Vượng",
    "pattern_en": "Liver Yang Rising",
    "pathogenesis_primary": "liver_yin_deficiency_with_yang_rising",
    "pathogenesis_secondary": ["kidney_yin_deficiency"],
    "bat_cuong_signature": {
      "heat": "moderate_to_high",
      "deficiency": "moderate",
      "excess": "moderate",
      "interior": "interior"
    },
    "required_symptoms": ["headache", "dizziness"],
    "strongly_supporting_symptoms": ["tinnitus", "irritability", "dry_mouth", "night_sweats"],
    "differentiating_symptoms_positive": ["afternoon_heat", "palmar_heat"],
    "differentiating_symptoms_negative": ["severe_constipation", "high_fever"],
    "phap_tri_code": "ping_gan_qian_yang",
    "phap_tri_vi": "Bình can tiềm dương, tư thận nhu Can",
    "phap_tri_en": "Calm Liver, Subdue Yang, Nourish Kidney Yin",
    "follow_up_days": {
      "self_care": null,
      "routine": 14,
      "urgent_24h": null
    },
    "constitution_affinity": ["yin_deficiency_C4", "qi_stagnation_C7"],
    "age_prevalence": "40-70",
    "gender_prevalence": "both_slightly_female",
    "season_variation": "worse_spring_summer",
    "yhhd_correlates": ["essential_hypertension", "menopausal_syndrome", "migraine"],
    "differential_tree": [
      {
        "competing_pattern": "liver_fire_rising",
        "key_differentiator": "Can Dương Vượng: kèm mệt mỏi, khô miệng, KHÔNG táo bón nặng. Can Hỏa Vượng: táo bón, tiểu vàng đậm, đỏ mắt rõ.",
        "distinguishing_symptoms": {
          "present_in_this": ["fatigue", "dry_mouth", "night_sweats"],
          "absent_in_this": ["severe_constipation", "dark_urine", "red_eyes_intense"]
        }
      },
      {
        "competing_pattern": "kidney_yin_deficiency",
        "key_differentiator": "Nếu đau đầu + chóng mặt nổi bật → Can Dương Vượng. Nếu lưng gối mỏi nổi bật hơn đau đầu → Thận Âm Hư",
        "distinguishing_symptoms": {
          "present_in_this": ["prominent_headache", "irritability"],
          "absent_in_this": ["prominent_lower_back_pain", "knee_weakness"]
        }
      }
    ],
    "key_questions_for_confirmation": [
      {
        "symptom_code": "tinnitus",
        "question_vi": "Bạn có bị ù tai không?",
        "importance": "strongly_supports"
      },
      {
        "symptom_code": "dizziness",
        "question_vi": "Bạn có hay bị chóng mặt không?",
        "importance": "required"
      }
    ],
    "clinical_note": "Rất phổ biến ở phụ nữ >40 và người THA. Cần phân biệt với hội chứng mãn kinh.",
    "herb_cautions": ["warming_herbs", "spicy_food"],
    "icd11_approximate": "MG30 (headache disorders)"
  },
  ...
]
```

---

### JOB-K05: Red Flag Rules Complete Set

**Mục tiêu:** 100+ rules (level 1, 2, 3) covering all organ systems
**Batch size:** 20 rules mỗi lần

---

**SYSTEM PROMPT:**

```
Bạn là bác sĩ cấp cứu và nội khoa với 15 năm kinh nghiệm tại Việt Nam.
Bạn xây dựng bộ quy tắc nhận diện red flag – triệu chứng nguy hiểm cần xử lý ngay.

Red flag trong hệ thống này có 3 mức:
  Level 1 (EMERGENCY): Gọi 115 hoặc đến ER ngay. Nguy cơ tử vong trong giờ.
  Level 2 (URGENT_24H): Phải khám trong 24 giờ. Nguy cơ biến chứng nặng.
  Level 3 (WATCH_72H): Theo dõi chặt, khám trong 72 giờ nếu không cải thiện.

Red flag được trigger khi bệnh nhân trả lời trong session.
Engine check SAU MỖI câu trả lời (continuous scanning, không phải một phase riêng).

OUTPUT phải là JSON hợp lệ, đúng schema.
```

**USER PROMPT:**

```
Tạo BATCH [BATCH_N] gồm 20 red flag rules cho hệ cơ quan: [HỆ CƠ QUAN]

Hệ cơ quan theo batch:
  Batch 1: Tim mạch (cardiac, vascular) – ưu tiên L1
  Batch 2: Thần kinh (neurological, stroke, seizure)
  Batch 3: Hô hấp (respiratory, pulmonary embolism)
  Batch 4: Tiêu hóa (GI bleeding, obstruction, perforation)
  Batch 5: Nội tiết (DKA, thyroid storm, adrenal crisis)
  Batch 6: Người cao tuổi đặc thù + Trẻ tuổi đặc thù
  Batch 7+: Tâm thần, Tiết niệu, Phụ khoa, Nhiễm trùng

OUTPUT FORMAT:

[
  {
    "rule_id": "RF_CARD_001",
    "name": "Classic STEMI presentation",
    "system": "cardiovascular",
    "level": "L1_emergency",
    "action": "EMERGENCY_115",
    "trigger_logic": {
      "type": "AND",
      "conditions": [
        {"variable": "symptom_location", "operator": "contains", "value": "chest"},
        {"variable": "symptom_radiation", "operator": "any_of", "value": ["left_arm", "jaw", "back", "shoulder"]}
      ]
    },
    "message_vi": "Đau ngực lan ra cánh tay trái/hàm/lưng – có thể là nhồi máu cơ tim cấp. GỌI 115 NGAY.",
    "message_brief": "Nghi nhồi máu cơ tim",
    "sensitivity_target": 0.99,
    "specificity_target": 0.70,
    "can_be_overridden": false,
    "context_modifiers": [
      {"context": "age_group < 30 AND gender = male", "note": "Hiếm gặp nhưng vẫn có thể"},
      {"context": "known_diabetes", "note": "ĐTĐ có thể không đau ngực điển hình – xem cluster ACS_ATYPICAL"}
    ],
    "common_mimics": ["GERD", "costochondritis", "anxiety"],
    "do_not_confuse_with": "RF_GI_003 (epigastric pain – check for both)",
    "clinical_note": "STEMI golden hour = 90 phút. Mỗi phút trì hoãn = ~2 triệu tế bào cơ tim chết."
  },
  ...
]

QUY TẮC:
- trigger_logic có thể là AND/OR/NOT/complex
- Các variable phải match với variable_name trong session schema
- message_vi: ngắn gọn, bệnh nhân hiểu được, không dùng thuật ngữ y khoa khó
- message_brief: tối đa 4 từ, dùng trong Emergency UI
```

---

### JOB-K06: Cluster Rules

**Mục tiêu:** 60+ clusters
**Batch size:** 15 clusters mỗi lần

---

**SYSTEM PROMPT:**

```
Bạn là bác sĩ lâm sàng chuyên về nhận diện pattern bệnh phức tạp.
Cluster rules phát hiện TỔ HỢP triệu chứng nguy hiểm mà từng triệu chứng đơn lẻ không đủ trigger red flag.

Cluster dùng probability-weighted scoring (không phải min_count cứng).
Threshold là tổng weighted score >= threshold_value.

Điều này cho phép: 2 triệu chứng quan trọng = trigger, dù chưa đủ 3.

OUTPUT phải là JSON hợp lệ.
```

**USER PROMPT:**

```
Tạo BATCH [BATCH_N] gồm 15 cluster rules.

NHÓM cho batch này: [NHÓM]
  Batch 1: Life-threatening clusters (meningitis, sepsis, PE, ACS atypical)
  Batch 2: Việt Nam specific (Dengue, TIA, stroke mimics)
  Batch 3: Người cao tuổi (fall risk, delirium, atypical presentations)
  Batch 4: Phụ nữ đặc thù (ectopic pregnancy, ovarian torsion, mastitis)
  Batch 5+: GI emergencies, metabolic emergencies, medication reactions

OUTPUT FORMAT:

[
  {
    "cluster_id": "CLUST_DENG_001",
    "name": "Dengue Fever vs Influenza Differentiation",
    "clinical_rationale": "Dengue phổ biến tại Việt Nam. Phân biệt với cúm quan trọng vì Dengue cấm NSAID/Aspirin và cần theo dõi tiểu cầu.",
    "symptoms": [
      {"code": "fever", "weight": 0.15, "note": "Không đặc hiệu"},
      {"code": "severe_myalgia_arthralgia", "weight": 0.25, "note": "Đau nhức toàn thân dữ dội – đặc trưng Dengue"},
      {"code": "retroorbital_pain", "weight": 0.40, "note": "Đau sau hố mắt – KEY DIFFERENTIATOR khỏi cúm"},
      {"code": "absence_of_nasal_congestion", "weight": 0.20, "note": "KHÔNG sổ mũi – phân biệt cúm"},
      {"code": "petechiae_skin", "weight": 0.35, "note": "Chấm xuất huyết dưới da"},
      {"code": "nausea_vomiting", "weight": 0.10},
      {"code": "context_dengue_season", "weight": 0.10, "note": "Tháng 6-11, sau mưa"}
    ],
    "threshold": 0.55,
    "action_base": "URGENT_24H",
    "time_factor": {
      "onset_duration_variable": "fever_duration_days",
      "ranges": [
        {"min_days": 0, "max_days": 3, "action": "URGENT_24H", "note": "Giai đoạn sốt. Uống nhiều nước, tránh NSAID/Aspirin. Theo dõi CBC."},
        {"min_days": 4, "max_days": 7, "action": "EMERGENCY_ER", "note": "GIAI ĐOẠN NGUY HIỂM. Tiểu cầu giảm, nguy cơ xuất huyết nội tạng."},
        {"min_days": 8, "max_days": null, "action": "URGENT_24H", "note": "Giai đoạn hồi phục. Theo dõi phát ban dạng 'đảo trắng trong biển đỏ'."}
      ]
    },
    "safety_filter_flags": ["suppress_yhct_diaphoresis_method"],
    "probe_questions": [],
    "message_vi": "Nghi ngờ sốt xuất huyết Dengue – cần theo dõi tiểu cầu. TUYỆT ĐỐI không dùng Ibuprofen, Aspirin.",
    "icd11": "1D2Z",
    "vietnam_epidemiology": "150.000-400.000 ca/năm tại Việt Nam. Cao điểm tháng 6-11."
  },
  ...
]
```

---

### JOB-K07: Herb-Drug Interaction Matrix

**Mục tiêu:** 200+ interactions
**Batch size:** 30 interactions mỗi lần

---

**SYSTEM PROMPT:**

```
Bạn là dược sĩ lâm sàng chuyên về tương tác thuốc Tây y – dược liệu YHCT.
Bạn xây dựng cơ sở dữ liệu tương tác để ngăn chặn nguy hiểm cho bệnh nhân.

QUAN TRỌNG: Hệ thống không kê bài thuốc cụ thể, chỉ gợi ý Pháp Trị (hướng điều trị).
Nhưng bệnh nhân Việt Nam sau khi biết "Hoạt huyết hóa ứ" sẽ tự mua vị thuốc phù hợp.
Do đó phải block/warn ở mức Pháp Trị.

Severity:
  block = Ẩn hoàn toàn Pháp Trị, hiện cảnh báo rõ
  warn  = Hiện Pháp Trị + cảnh báo nổi bật
  note  = Hiện Pháp Trị + ghi chú nhỏ

OUTPUT phải là JSON hợp lệ.
```

**USER PROMPT:**

```
Tạo BATCH [BATCH_N] gồm 30 herb-drug interaction rules.

NHÓM thuốc YHHD cho batch này: [NHÓM]
  Batch 1: Anticoagulants (Warfarin, Aspirin cao liều, Clopidogrel, Rivaroxaban)
  Batch 2: Cardiovascular (Digoxin, ACE inhibitors, Beta blockers, Nitrates)
  Batch 3: Hormonal (Corticosteroids, Thyroid hormones, Oral contraceptives)
  Batch 4: Metabolic (Metformin, Insulin, Sulfonylureas, Statins)
  Batch 5: Neurological/Psychiatric (SSRIs, MAOIs, Benzodiazepines, Antiepileptics)
  Batch 6: Immunosuppressants (Cyclosporine, Tacrolimus, Methotrexate, Chemotherapy)
  Batch 7+: Antibiotics, NSAIDs, Antivirals, HIV meds

OUTPUT FORMAT:

[
  {
    "interaction_id": "HDI_ANTICOAG_001",
    "drug_class": "anticoagulant",
    "drug_examples": ["Warfarin", "Acenocoumarol"],
    "phap_tri_code": "hoat_huyet_hoa_u",
    "phap_tri_vi": "Hoạt huyết hóa ứ",
    "severity": "block",
    "mechanism_vi": "Các vị hoạt huyết (Đan sâm, Tam thất, Ích mẫu) có tác dụng antiplatelet/anticoagulant, kết hợp Warfarin gây tăng INR → xuất huyết nội tạng, não.",
    "clinical_evidence": "Case reports documented. Theoretical mechanism strong.",
    "dangerous_herbs_vi": ["Đan sâm (Danshen)", "Tam thất", "Ích mẫu", "Hồng hoa", "Đào nhân"],
    "message_to_patient": "Pháp trị Hoạt huyết tạm ẩn vì bạn đang dùng thuốc chống đông máu. Nguy cơ xuất huyết nghiêm trọng nếu kết hợp. Thầy thuốc YHCT cần biết bạn đang dùng Warfarin.",
    "alternative_phap_tri": "duong_huyet_ho_y",
    "alternative_note": "Có thể dùng Dưỡng huyết nhẹ thay vì Hoạt huyết nếu thầy thuốc quyết định điều trị",
    "monitoring_note": "INR monitoring required if combination used under physician supervision",
    "reference": "WHO TCM-Conventional Medicine Interactions Database 2023"
  },
  ...
]
```

---

### JOB-K08: Kiêm Chứng (Combined Patterns)

**Mục tiêu:** 80+ kiêm chứng
**Batch size:** 20 mỗi lần

---

**SYSTEM PROMPT:**

```
Bạn là thầy thuốc YHCT chuyên về điều trị bệnh mạn tính và bệnh phức tạp.
Trong thực hành, >70% bệnh nhân có Kiêm Chứng (kết hợp 2+ chứng).

Pháp Trị cho Kiêm Chứng KHÔNG phải cộng 2 Pháp Trị đơn.
Cần cân nhắc: chứng nào ưu tiên, phép trị nào có thể mâu thuẫn, điều gì cần tránh.

Ví dụ: Can Dương Vượng + Tỳ Vị Hư → không được dùng thanh nhiệt mạnh (hại Tỳ thêm).
Pháp Trị đúng: Bình can là chính, kiện tỳ là phụ, tránh khổ hàn.

OUTPUT phải là JSON hợp lệ.
```

**USER PROMPT:**

```
Tạo BATCH [BATCH_N] gồm 20 Kiêm Chứng thường gặp trong lâm sàng.

NHÓM cho batch này: [NHÓM]
  Batch 1: Can-Thận kiêm chứng (rất phổ biến)
  Batch 2: Can-Tỳ kiêm chứng
  Batch 3: Tâm-Thận, Tâm-Tỳ kiêm chứng
  Batch 4: Khí-Huyết kiêm chứng (khí trệ, huyết ứ, huyết hư)
  Batch 5+: Phức tạp 3 tạng, Đặc thù theo tuổi/giới

OUTPUT FORMAT:

[
  {
    "combo_id": "KC_CANSI_001",
    "pattern_1_id": "liver_yang_rising",
    "pattern_2_id": "kidney_yin_deficiency",
    "kiem_chung_vi": "Can Dương Vượng kiêm Thận Âm Hư",
    "kiem_chung_en": "Liver Yang Rising with Kidney Yin Deficiency",
    "prevalence_note": "Rất phổ biến ở phụ nữ >40, THA, căng thẳng mãn tính",
    "clinical_presentation": "Đau đầu + chóng mặt chiều tối, ù tai, mất ngủ, lưng gối mỏi, nóng lòng bàn tay",
    "phap_tri_combined_vi": "Bình can tiềm dương, tư thận nhu Can",
    "phap_tri_combined_en": "Calm Liver and Subdue Yang, Nourish Kidney to Soften Liver",
    "priority_rule": "Ưu tiên tiềm dương trước, tư âm là gốc lâu dài",
    "caution_list": [
      "Không dùng thuốc ôn dương (sẽ tổn thêm Thận Âm)",
      "Không dùng khổ hàn quá mạnh (có Thận hư nên không chịu được)",
      "Tránh căng thẳng, thức khuya"
    ],
    "lifestyle_advice_vi": "Ngủ sớm trước 23h. Tránh rượu bia. Hạn chế cay nóng. Tập dưỡng sinh nhẹ.",
    "detection_logic": {
      "min_pattern_1_confidence": 0.6,
      "min_pattern_2_confidence": 0.5,
      "additional_confirmation_symptom": "lower_back_weakness"
    },
    "yhhd_correlates": ["hypertension", "menopausal syndrome", "chronic fatigue"],
    "too_complex_for_ai": false,
    "requires_practitioner_note": "Thể Kiêm Chứng này thường cần điều trị 3-6 tháng, không phải cấp tính."
  },
  ...
]
```

---

## PHẦN 3: KIẾN TRÚC KỸ THUẬT

### 3.1 Tech Stack

```
Backend:   PHP 8.3 + Laravel 11
Database:  PostgreSQL 16 (primary) + Redis 7 (cache/session)
Frontend:  Vue.js 3 + Inertia.js (SPA-like, không API riêng)
Styling:   Tailwind CSS 3 + Headless UI
Mobile:    PWA (Progressive Web App) + Responsive first
Build:     Vite 5
Deploy:    Docker + Nginx (on-premise hoặc cloud)
CLI:       Laravel Artisan (batch import, maintenance)
```

### 3.2 Module Structure

```
/
├── app/
│   ├── Modules/                    ← Core module system
│   │   ├── Session/                ← Session management
│   │   │   ├── SessionService.php
│   │   │   ├── SessionRepository.php
│   │   │   └── Events/
│   │   │
│   │   ├── Engine/                 ← Clinical reasoning engine
│   │   │   ├── RedFlagEngine.php       ← Tier 1
│   │   │   ├── ClusterEngine.php       ← Tier 1.5
│   │   │   ├── DisambiguationEngine.php← Layer 1.5
│   │   │   ├── IntakeEngine.php        ← Tier 2
│   │   │   ├── YHCTReasoningEngine.php ← Tier 3 (7-layer chain)
│   │   │   ├── YHHDOrientationEngine.php
│   │   │   ├── SafetyFilterEngine.php  ← Tier 3.5
│   │   │   ├── OutputSynthesizer.php   ← Tier 4
│   │   │   └── Pipeline/
│   │   │       ├── ReasoningPipeline.php
│   │   │       └── Stages/
│   │   │           ├── BatCuangInference.php
│   │   │           ├── ZangfuMapping.php
│   │   │           ├── PathogenesisInference.php
│   │   │           ├── PatternMatching.php
│   │   │           ├── DifferentialResolution.php
│   │   │           └── PhapTriLookup.php
│   │   │
│   │   ├── KnowledgeBase/          ← Knowledge management
│   │   │   ├── ImportService.php       ← Batch import LLM data
│   │   │   ├── ValidationService.php   ← Validate before import
│   │   │   └── Repositories/
│   │   │       ├── PatternRepository.php
│   │   │       ├── RedFlagRepository.php
│   │   │       └── ClusterRepository.php
│   │   │
│   │   ├── Output/                 ← Output generation
│   │   │   ├── PatientOutputBuilder.php    ← Output A
│   │   │   ├── PhysicianOutputBuilder.php  ← Output B
│   │   │   ├── PractitionerOutputBuilder.php ← Output C
│   │   │   └── EmergencyOutputBuilder.php
│   │   │
│   │   └── Review/                 ← Knowledge base review UI
│   │       ├── ReviewDashboardController.php
│   │       └── BulkActionService.php
│   │
│   ├── Http/Controllers/
│   │   ├── SessionController.php
│   │   ├── QuestionController.php
│   │   └── OutputController.php
│   │
│   └── Console/Commands/           ← Artisan CLI
│       ├── ImportKnowledgeBatch.php
│       ├── ValidateKnowledgeBase.php
│       └── GenerateTestCases.php
│
├── config/
│   ├── engine.php                  ← Engine configuration
│   ├── knowledge.php               ← KB configuration
│   └── ui.php                      ← UI/UX settings
│
├── knowledge/                      ← YAML rule files (source of truth)
│   ├── red_flags/
│   │   ├── cardiovascular.yaml
│   │   ├── neurological.yaml
│   │   └── ...
│   ├── clusters/
│   │   ├── life_threatening.yaml
│   │   ├── vietnam_specific.yaml
│   │   └── ...
│   └── disambiguation/
│       └── phrases.yaml
│
├── resources/
│   ├── js/
│   │   ├── Pages/              ← Vue.js pages (Inertia)
│   │   │   ├── Session/
│   │   │   │   ├── Start.vue
│   │   │   │   ├── Question.vue
│   │   │   │   ├── Output.vue
│   │   │   │   └── Emergency.vue
│   │   │   └── Admin/
│   │   │       ├── KnowledgeReview.vue
│   │   │       └── BatchImport.vue
│   │   │
│   │   ├── Components/         ← Reusable Vue components
│   │   │   ├── Questions/
│   │   │   │   ├── SingleChoice.vue
│   │   │   │   ├── MultiChoice.vue
│   │   │   │   ├── BodyMapSelector.vue
│   │   │   │   ├── ScaleSelector.vue
│   │   │   │   └── TextInput.vue
│   │   │   ├── Output/
│   │   │   │   ├── TriageCard.vue
│   │   │   │   ├── YHCTPatternCard.vue
│   │   │   │   ├── FollowUpCard.vue
│   │   │   │   └── EmergencyOverlay.vue
│   │   │   └── UI/
│   │   │       ├── ProgressBar.vue
│   │   │       ├── PhaseIndicator.vue
│   │   │       └── ConsentModal.vue
│   │   │
│   │   └── Composables/        ← Vue composables
│   │       ├── useSession.js
│   │       ├── useQuestion.js
│   │       └── useOutput.js
│   │
│   └── views/
│       └── app.blade.php       ← Single Blade template (SPA entry)
│
└── database/
    ├── migrations/             ← Schema migrations
    ├── seeders/                ← Initial data seeders
    └── factories/              ← Test data factories
```

### 3.3 UI Template System

#### Layout Templates

**Template 1: Question Flow (Mobile-first)**
```
┌─────────────────────────────┐
│ [Logo]  Bước 4/8  [░░░░░░░] │  ← Progress bar
│─────────────────────────────│
│                             │
│  GIAI ĐOẠN: Triệu chứng    │  ← Phase indicator
│                             │
│  Triệu chứng này ảnh hưởng │  ← Question text
│  sinh hoạt của bạn như thế │     (max 2 dòng)
│  nào?                       │
│                             │
│  ┌─────────────────────┐    │
│  │ ○ Bình thường       │    │  ← Option cards
│  └─────────────────────┘    │     (touch-friendly)
│  ┌─────────────────────┐    │
│  │ ○ Giảm một ít       │    │
│  └─────────────────────┘    │
│  ┌─────────────────────┐    │
│  │ ○ Phải nghỉ ở nhà   │    │
│  └─────────────────────┘    │
│  ┌─────────────────────┐    │
│  │ ○ Phải nằm nghỉ     │    │
│  └─────────────────────┘    │
│                             │
│  [← Quay lại]  [Tiếp theo →]│
└─────────────────────────────┘
```

**Template 2: Emergency Screen (Exclusive)**
```
┌─────────────────────────────┐
│  ████████████████████████   │  ← Red background full screen
│                             │
│        🚨 KHẨN CẤP 🚨       │
│                             │
│     GỌI 115 NGAY            │  ← 48px bold
│                             │
│  • Nghi nhồi máu cơ tim     │  ← 3 bullets max
│  • Cần xử lý trong          │
│    vòng 90 phút             │
│                             │
│  ┌─────────────────────┐    │
│  │   📞  GỌI 115       │    │  ← Big button (tel:115)
│  └─────────────────────┘    │
│                             │
│  [ Xem thêm chi tiết ]      │  ← Small, muted
└─────────────────────────────┘
```

**Template 3: Output Screen**
```
┌─────────────────────────────┐
│  KẾT QUẢ KHẢO SÁT           │
│─────────────────────────────│
│  🟡 CẦN KHÁM TRONG 24H      │  ← Triage card (color-coded)
│─────────────────────────────│
│  BƯỚC 1 – CẦN LÀM           │
│  → Đo huyết áp              │
│  → Kiểm tra thuốc           │
│─────────────────────────────│
│  BƯỚC 2 – ĐÔNG Y            │  ← Collapsible
│  ▼ Chứng: Can Dương Vượng   │
│    Hướng điều trị: Bình can │
│    Tin cậy: ●●●○            │
│─────────────────────────────│
│  BƯỚC 3 – TÂY Y             │  ← Collapsible
│  ▼ Hệ tim mạch/thần kinh   │
│─────────────────────────────│
│  THEO DÕI: Sau 14 ngày...   │
│─────────────────────────────│
│  [QR Code]  [In PDF]        │
└─────────────────────────────┘
```

**Template 4: Admin Review Dashboard**
```
┌──────────────────────────────────────────────┐
│  Review Dashboard – Patterns  [Import Batch] │
│──────────────────────────────────────────────│
│  Filter: [Tất cả ▼] [Chưa review ▼] [Search]│
│──────────────────────────────────────────────│
│  ✓ liver_yang_rising  Can Dương Vượng  [OK]  │
│  ✓ spleen_qi_def      Tỳ Khí Hư       [OK]  │
│  ? heart_fire         Tâm Hỏa     [Review]  │  ← flagged
│  ✓ kidney_yin_def     Thận Âm Hư      [OK]  │
│──────────────────────────────────────────────│
│  [✓ Approve All] [Export] [Delete Selected]  │
└──────────────────────────────────────────────┘
```

### 3.4 Responsive Breakpoints

```css
/* Mobile first approach */
/* xs: < 640px  – phone portrait */
/* sm: 640px+   – phone landscape / small tablet */
/* md: 768px+   – tablet */
/* lg: 1024px+  – desktop */
/* xl: 1280px+  – wide desktop */

/* Question cards: full width mobile, 600px max tablet+ */
/* Output cards: stack mobile, 2-column tablet+ */
/* Admin dashboard: hidden on mobile, full on desktop */
```

### 3.5 Performance Optimization

```php
// Caching strategy:

// 1. Knowledge base cache (30 phút) – ít thay đổi
Cache::remember('red_flag_rules', 1800, fn() =>
    RedFlagRule::active()->with('conditions')->get()
);

// 2. Session state (Redis, 2 giờ)
// Không dùng DB session – dùng Redis

// 3. Pattern matching results (per session, 30 phút)
// Cache riêng theo session_id

// 4. Lazy loading: chỉ load knowledge base của CC-type hiện tại
// Không load toàn bộ 250 patterns khi bắt đầu session

// 5. Question tree: precompute và cache per CC-type
// Rebuild khi knowledge base thay đổi
```

### 3.6 Bulk Import CLI

```bash
# Import từ JSON file (output của LLM)
php artisan kb:import patterns --file=data/patterns_batch_01.json --validate-only
php artisan kb:import patterns --file=data/patterns_batch_01.json --commit

# Import với review queue (không commit ngay)
php artisan kb:import red-flags --file=data/rf_cardiac.json --queue-review

# Validate toàn bộ KB hiện tại
php artisan kb:validate --report

# Export để gửi cho LLM review/enhance
php artisan kb:export patterns --format=json --filter=low-confidence

# Generate test cases từ KB
php artisan kb:generate-tests --cc-type=CC1 --count=50
```

### 3.7 Extension Points

Hệ thống được thiết kế để thêm module mà không sửa core:

```php
// Thêm CC type mới: implement interface
interface ChiefComplaintHandler
{
    public function getQuestionTree(): QuestionTree;
    public function processSymptoms(SymptomSet $symptoms): PatternResult;
    public function getCCType(): string;
}

// Thêm reasoning engine mới (future ML):
interface ReasoningEngine
{
    public function infer(SessionData $data): ReasoningResult;
    public function getConfidence(): float;
}

// Register trong config/engine.php:
'cc_handlers' => [
    'CC-1' => AcuteSymptomHandler::class,
    'CC-2' => ChronicChangeHandler::class,
    // Thêm CC type mới ở đây, không sửa core
],

'reasoning_engines' => [
    'yhct_primary' => YHCTReasoningEngine::class,
    'yhhd_orientation' => YHHDOrientationEngine::class,
    // Future: 'ml_assist' => MLAssistEngine::class,
],
```

---

## PHẦN 4: WORKFLOW TỔNG THỂ

### 4.1 Thứ Tự Thực Hiện

```
TUẦN 1-2: Data Generation (song song)
  ├── Chạy JOB-K01 (Observable + Disambiguation) → 16 LLM calls
  ├── Chạy JOB-K02 (Symptoms) → 15 LLM calls
  ├── Chạy JOB-K03 (Pathogenesis) → 6 LLM calls
  ├── Chạy JOB-K04 (Patterns + Differential) → 12 LLM calls
  ├── Chạy JOB-K05 (Red Flags) → 7 LLM calls
  ├── Chạy JOB-K06 (Clusters) → 5 LLM calls
  ├── Chạy JOB-K07 (Herb-Drug) → 7 LLM calls
  └── Chạy JOB-K08 (Kiêm Chứng) → 5 LLM calls

  Tổng: ~73 LLM calls × 15-30 giây = ~1-2 giờ chạy

TUẦN 2-3: Infrastructure
  ├── Setup Laravel + PostgreSQL + Redis
  ├── Run migrations
  ├── Build Bulk Import CLI tool
  ├── Build Review Dashboard UI
  └── Import tất cả LLM data → Review Queue

TUẦN 3-4: Review Data
  ├── Bác sĩ YHCT review patterns (ưu tiên)
  ├── Bác sĩ YHHD review red flags + clusters
  └── Dược sĩ review herb-drug matrix

THÁNG 2-3: Core Engine Development
  ├── RedFlagEngine + ClusterEngine (Tier 1, 1.5)
  ├── DisambiguationEngine
  ├── IntakeEngine (question tree traversal)
  ├── YHCT 7-layer reasoning chain
  ├── SafetyFilterEngine (Tier 3.5)
  └── OutputSynthesizer (3 output types)

THÁNG 3-4: Frontend + UX
  ├── Question flow UI (mobile-first)
  ├── Emergency screen
  ├── Output screen (3 versions)
  ├── Constitution module UI
  └── PWA setup

THÁNG 4: Testing + Validation
  ├── 200 test sessions với bác sĩ
  ├── Performance testing
  └── Security audit
```

### 4.2 Quy Trình Review Data

```
LLM Output (JSON)
      ↓
php artisan kb:import [type] --file=[path] --queue-review
      ↓
Review Queue (database table: kb_review_queue)
      ↓
Review Dashboard URL: /admin/review
      ↓
Reviewer actions:
  [Approve] → move to active table
  [Edit]    → inline edit → approve
  [Delete]  → soft delete (can recover)
  [Flag]    → mark needs clinical review
      ↓
Active Knowledge Base
```

### 4.3 Data Quality Rules (Auto-Validation)

```php
// ValidateKnowledgeBase.php – chạy trước khi approve

class PatternValidator
{
    public function validate(array $pattern): ValidationResult
    {
        $errors = [];

        // Required fields
        foreach (['pattern_id', 'pattern_vi', 'phap_tri_code'] as $field) {
            if (empty($pattern[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        // Pattern ID uniqueness
        if (Pattern::where('pattern_id', $pattern['pattern_id'])->exists()) {
            $errors[] = "Duplicate pattern_id: {$pattern['pattern_id']}";
        }

        // Must reference existing pathogenesis
        if (!Pathogenesis::where('code', $pattern['pathogenesis_primary'])->exists()) {
            $errors[] = "Unknown pathogenesis: {$pattern['pathogenesis_primary']}";
        }

        // Phap tri must exist
        if (!PhapTri::where('code', $pattern['phap_tri_code'])->exists()) {
            $errors[] = "Unknown phap_tri: {$pattern['phap_tri_code']}";
        }

        return new ValidationResult($errors);
    }
}
```

---

## PHẦN 5: DATABASE SCHEMA ĐẦY ĐỦ

```sql
-- ============================================================
-- KNOWLEDGE BASE TABLES
-- ============================================================

-- Observable phrases (từ dân gian)
CREATE TABLE kb_observables (
    id                  BIGSERIAL PRIMARY KEY,
    phrase_id           TEXT UNIQUE NOT NULL,
    phrase_vi           TEXT NOT NULL,
    variants_vi         TEXT[],
    regions             TEXT[] DEFAULT ARRAY['all'],
    requires_disambiguation BOOLEAN DEFAULT TRUE,
    disambiguation_question TEXT,
    direct_symptom_codes TEXT[],  -- nếu không cần disambiguation
    red_flag_trigger    TEXT,
    notes               TEXT,
    status              TEXT DEFAULT 'pending_review',  -- pending_review/active/deleted
    created_by          TEXT DEFAULT 'llm_batch',
    reviewed_by         INTEGER REFERENCES users(id),
    reviewed_at         TIMESTAMPTZ,
    created_at          TIMESTAMPTZ DEFAULT NOW()
);

-- Disambiguation options
CREATE TABLE kb_disambiguation_options (
    id              BIGSERIAL PRIMARY KEY,
    observable_id   BIGINT REFERENCES kb_observables(id),
    option_code     TEXT NOT NULL,  -- A, B, C, D...
    label_vi        TEXT NOT NULL,
    symptom_codes   TEXT[] NOT NULL,
    yhct_hint       TEXT,
    yhhd_hint       TEXT,
    sort_order      INTEGER DEFAULT 0
);

-- Clinical symptoms (chuẩn hóa)
CREATE TABLE kb_symptoms (
    id                  BIGSERIAL PRIMARY KEY,
    symptom_code        TEXT UNIQUE NOT NULL,
    name_vi             TEXT NOT NULL,
    name_en             TEXT,
    category            TEXT,
    icd11_approximate   TEXT,
    severity_weight     NUMERIC(3,2) DEFAULT 0.5,
    time_sensitivity    TEXT,  -- acute/subacute/chronic
    red_flag_level      TEXT,
    cluster_contributions TEXT[],
    yhct_note           TEXT,
    yhhd_note           TEXT,
    status              TEXT DEFAULT 'pending_review',
    created_at          TIMESTAMPTZ DEFAULT NOW()
);

-- Bát Cương weights
CREATE TABLE kb_bat_cuong_weights (
    symptom_code    TEXT REFERENCES kb_symptoms(symptom_code),
    dimension       TEXT NOT NULL,  -- cold/heat/deficiency/excess/exterior/interior
    weight          NUMERIC(3,2) NOT NULL,
    is_pathognomonic BOOLEAN DEFAULT FALSE,
    is_veto_trigger BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (symptom_code, dimension)
);

-- Tạng Phủ weights
CREATE TABLE kb_zangfu_weights (
    symptom_code    TEXT REFERENCES kb_symptoms(symptom_code),
    organ           TEXT NOT NULL,  -- liver/heart/spleen/lung/kidney
    weight          NUMERIC(3,2) NOT NULL,
    PRIMARY KEY (symptom_code, organ)
);

-- Pathogenesis rules (Bệnh Cơ)
CREATE TABLE kb_pathogenesis (
    id                  BIGSERIAL PRIMARY KEY,
    pathogenesis_code   TEXT UNIQUE NOT NULL,
    name_vi             TEXT NOT NULL,
    name_en             TEXT,
    primary_organ       TEXT NOT NULL,
    secondary_organs    TEXT[],
    trigger_conditions  JSONB NOT NULL,
    common_symptoms     TEXT[],
    red_flag_potential  TEXT,
    yhhd_correlates     TEXT[],
    phap_tri_direction  TEXT,
    caution             TEXT,
    status              TEXT DEFAULT 'pending_review',
    created_at          TIMESTAMPTZ DEFAULT NOW()
);

-- Pattern definitions (Chứng)
CREATE TABLE kb_patterns (
    id                      BIGSERIAL PRIMARY KEY,
    pattern_id              TEXT UNIQUE NOT NULL,
    pattern_vi              TEXT NOT NULL,
    pattern_en              TEXT,
    pathogenesis_primary    TEXT REFERENCES kb_pathogenesis(pathogenesis_code),
    pathogenesis_secondary  TEXT[],
    required_symptoms       TEXT[],
    supporting_symptoms     TEXT[],
    differentiating_positive TEXT[],
    differentiating_negative TEXT[],
    phap_tri_code           TEXT NOT NULL,
    phap_tri_vi             TEXT NOT NULL,
    phap_tri_en             TEXT,
    follow_up_self_care     INTEGER,
    follow_up_routine       INTEGER,
    constitution_affinity   TEXT[],
    age_prevalence          TEXT,
    gender_prevalence       TEXT,
    season_variation        TEXT,
    yhhd_correlates         TEXT[],
    herb_cautions           TEXT[],
    clinical_note           TEXT,
    status                  TEXT DEFAULT 'pending_review',
    created_at              TIMESTAMPTZ DEFAULT NOW()
);

-- Pattern differential trees
CREATE TABLE kb_pattern_differentials (
    id                  BIGSERIAL PRIMARY KEY,
    pattern_id          TEXT REFERENCES kb_patterns(pattern_id),
    competing_pattern   TEXT REFERENCES kb_patterns(pattern_id),
    key_differentiator  TEXT NOT NULL,
    present_in_this     TEXT[],
    absent_in_this      TEXT[]
);

-- Pattern key signs
CREATE TABLE kb_pattern_key_signs (
    id              BIGSERIAL PRIMARY KEY,
    pattern_id      TEXT REFERENCES kb_patterns(pattern_id),
    symptom_code    TEXT,
    importance      TEXT NOT NULL,  -- required/strongly_supports/differentiating
    question_vi     TEXT NOT NULL
);

-- Kiêm Chứng
CREATE TABLE kb_kiem_chung (
    id                  BIGSERIAL PRIMARY KEY,
    combo_id            TEXT UNIQUE NOT NULL,
    pattern_1_id        TEXT REFERENCES kb_patterns(pattern_id),
    pattern_2_id        TEXT REFERENCES kb_patterns(pattern_id),
    kiem_chung_vi       TEXT NOT NULL,
    clinical_presentation TEXT,
    phap_tri_combined_vi TEXT NOT NULL,
    priority_rule       TEXT,
    caution_list        TEXT[],
    lifestyle_advice_vi TEXT,
    too_complex_for_ai  BOOLEAN DEFAULT FALSE,
    detection_logic     JSONB,
    status              TEXT DEFAULT 'pending_review',
    created_at          TIMESTAMPTZ DEFAULT NOW()
);

-- Red flag rules
CREATE TABLE kb_red_flags (
    id              BIGSERIAL PRIMARY KEY,
    rule_id         TEXT UNIQUE NOT NULL,
    name            TEXT NOT NULL,
    system          TEXT NOT NULL,
    level           TEXT NOT NULL,  -- L1_emergency/L2_urgent/L3_watch
    action          TEXT NOT NULL,
    trigger_logic   JSONB NOT NULL,
    message_vi      TEXT NOT NULL,
    message_brief   TEXT NOT NULL,
    sensitivity_target NUMERIC(3,2),
    specificity_target NUMERIC(3,2),
    can_be_overridden BOOLEAN DEFAULT FALSE,
    context_modifiers JSONB,
    clinical_note   TEXT,
    status          TEXT DEFAULT 'pending_review',
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Cluster rules
CREATE TABLE kb_clusters (
    id              BIGSERIAL PRIMARY KEY,
    cluster_id      TEXT UNIQUE NOT NULL,
    name            TEXT NOT NULL,
    clinical_rationale TEXT,
    threshold       NUMERIC(3,2) NOT NULL,
    action_base     TEXT NOT NULL,
    time_factor     JSONB,
    safety_filter_flags TEXT[],
    probe_questions TEXT[],
    message_vi      TEXT NOT NULL,
    icd11           TEXT,
    status          TEXT DEFAULT 'pending_review',
    created_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Cluster symptoms
CREATE TABLE kb_cluster_symptoms (
    cluster_id      TEXT REFERENCES kb_clusters(cluster_id),
    symptom_code    TEXT,
    weight          NUMERIC(3,2) NOT NULL,
    note            TEXT,
    PRIMARY KEY (cluster_id, symptom_code)
);

-- Herb-drug interactions
CREATE TABLE kb_herb_drug_interactions (
    id                  BIGSERIAL PRIMARY KEY,
    interaction_id      TEXT UNIQUE NOT NULL,
    drug_class          TEXT NOT NULL,
    drug_examples       TEXT[],
    phap_tri_code       TEXT NOT NULL,
    severity            TEXT NOT NULL,  -- block/warn/note
    mechanism_vi        TEXT NOT NULL,
    dangerous_herbs_vi  TEXT[],
    message_to_patient  TEXT NOT NULL,
    alternative_phap_tri TEXT,
    alternative_note    TEXT,
    monitoring_note     TEXT,
    reference           TEXT,
    status              TEXT DEFAULT 'pending_review',
    created_at          TIMESTAMPTZ DEFAULT NOW()
);

-- Follow-up rules
CREATE TABLE kb_follow_up_rules (
    id                  BIGSERIAL PRIMARY KEY,
    pattern_id          TEXT REFERENCES kb_patterns(pattern_id),
    triage_level        TEXT NOT NULL,
    follow_up_days      INTEGER NOT NULL,
    escalation_trigger  TEXT,
    message_vi          TEXT NOT NULL
);

-- ============================================================
-- REVIEW QUEUE
-- ============================================================
CREATE TABLE kb_review_queue (
    id              BIGSERIAL PRIMARY KEY,
    entity_type     TEXT NOT NULL,  -- pattern/red_flag/cluster/...
    entity_id       TEXT NOT NULL,
    data            JSONB NOT NULL,
    import_batch    TEXT,
    flagged         BOOLEAN DEFAULT FALSE,
    flag_reason     TEXT,
    reviewed_by     INTEGER REFERENCES users(id),
    reviewed_at     TIMESTAMPTZ,
    action          TEXT,  -- approved/edited/deleted
    created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

---

## PHẦN 6: TÓM TẮT THỰC HIỆN

| Hạng mục | Nội dung | Thực hiện bởi |
|---------|---------|--------------|
| Data Generation | 73 LLM calls, ~1-2 giờ | Kỹ thuật viên + LLM |
| Infrastructure | Laravel setup, DB schema, CLI tools | Backend Engineer |
| Review Dashboard | Admin UI cho knowledge review | Full-stack Engineer |
| Data Review | Review ~2000+ records LLM sinh ra | Bác sĩ YHCT + YHHD |
| Engine Development | 7-layer YHCT + Red Flag + Cluster | Backend Engineer |
| Frontend | Vue.js + Tailwind, PWA, responsive | Frontend Engineer |
| Testing | 200 test sessions | Bác sĩ + QA |

**Nguyên tắc mở rộng:**
- Thêm CC type mới: implement `ChiefComplaintHandler`
- Thêm language: thêm `name_[lang]` columns + translations
- Thêm reasoning engine: implement `ReasoningEngine` interface
- Thêm data mới: chạy LLM batch → import → review → active
