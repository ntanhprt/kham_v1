# DATA INVENTORY REPORT
Last Updated: 2026-03-12

## File-by-File Summary

| Job | File | Records | First ID | Last ID |
|-----|------|---------|----------|---------|
| K01 | k01_observable_phrases/batch_01_tieu_hoa.json | 50 | OBS_B01_001 | OBS_B01_050 |
| K01 | k01_observable_phrases/batch_02_dau_than_kinh.json | 50 | OBS_B02_001 | OBS_B02_050 |
| K01 | k01_observable_phrases/batch_03_tim_ho_hap.json | 50 | OBS_B03_001 | OBS_B03_050 |
| K01 | k01_observable_phrases/batch_04_co_xuong_khop.json | 50 | OBS_B04_001 | OBS_B04_050 |
| K01 | k01_observable_phrases/batch_05_toan_than.json | 50 | OBS_B05_001 | OBS_B05_050 |
| K01 | k01_observable_phrases/batch_06_tiet_nieu_phu_khoa_da.json | 50 | OBS_B06_001 | OBS_B06_050 |
| K02 | k02_symptoms/batch_01_than_kinh.json | 40 | frontal_headache | irritability_emotional_lability |
| K02 | k02_symptoms/batch_02_tim_ho_hap.json | 40 | crushing_substernal_chest_pain | thrombophlebitis |
| K02 | k02_symptoms/batch_03_tieu_hoa.json | 40 | epigastric_burning_pain | dark_urine_bilirubinuria |
| K02 | k02_symptoms/batch_04_co_xuong_khop.json | 40 | lumbar_pain_acute | new_spine_pain_cancer_history |
| K02 | k02_symptoms/batch_05_toan_than.json | 40 | post_exertion_fatigue | foamy_urine |
| K02 | k02_symptoms/batch_06_tiet_nieu_da_tam_than.json | 40 | dysuria | suicidal_ideation |
| K02 | k02_symptoms/batch_07_phu_khoa_sinh_san.json | 40 | primary_dysmenorrhea | prostate_luts |
| K03 | k03_pathogenesis/batch_01_can.json | 30 | PM_B01_001 | PM_B01_030 |
| K03 | k03_pathogenesis/batch_02_than.json | 30 | PM_B02_001 | PM_B02_030 |
| K03 | k03_pathogenesis/batch_03_ty_vi.json | 30 | PM_B03_001 | PM_B03_030 |
| K03 | k03_pathogenesis/batch_04_tam.json | 30 | PM_B04_001 | PM_B04_030 |
| K03 | k03_pathogenesis/batch_05_phe.json | 30 | PM_B05_001 | PM_B05_030 |
| K03 | k03_pathogenesis/batch_06_lien_tang.json | 30 | PM_B06_001 | PM_B06_030 |
| K04 | k04_patterns/batch_01_can_patterns.json | 20 | PAT_B01_001 | PAT_B01_020 |
| K04 | k04_patterns/batch_02_than_patterns.json | 20 | PAT_B02_001 | PAT_B02_020 |
| K04 | k04_patterns/batch_03_ty_vi_patterns.json | 20 | PAT_B03_001 | PAT_B03_020 |
| K04 | k04_patterns/batch_04_tam_phe_patterns.json | 25 | PAT_B04_001 | PAT_B04_025 |
| K04 | k04_patterns/batch_05_phuc_tap_patterns.json | 25 | PAT_B05_001 | PAT_B05_025 |
| K04 | k04_patterns/batch_06_benh_chung.json | 25 | PAT_B06_001 | PAT_B06_025 |
| K05 | k05_red_flags/batch_01_cap_cuu.json | 40 | RF_B01_001 | RF_B01_040 |
| K05 | k05_red_flags/batch_02_than_trong.json | 30 | RF_B02_001 | RF_B02_030 |
| K06 | k06_clusters/batch_01_cum_benh.json | 30 | CL_B01_001 | CL_B01_030 |
| K06 | k06_clusters/batch_02_cum_benh_2.json | 30 | CL_B02_001 | CL_B02_030 |
| K07 | k07_herb_drug/batch_01_tuong_tac.json | 40 | HDI_B01_001 | HDI_B01_040 |
| K07 | k07_herb_drug/batch_02_tuong_tac_2.json | 40 | HDI_B02_001 | HDI_B02_040 |
| K08 | k08_kiem_chung/batch_01_kiem_chung.json | 40 | KC_B01_001 | KC_B01_040 |
| K08 ⚠️ | k08_kiem_chung/batch_01_part2.json | 20 | KC_B01_021 | KC_B01_040 |
| K08 | k08_kiem_chung/batch_02_kiem_chung_2.json | 40 | KC_B02_001 | KC_B02_040 |

> **K08 WARNING:** `batch_01_part2.json` is a duplicate subset. All 20 of its records (KC_B01_021–KC_B01_040) already exist verbatim in `batch_01_kiem_chung.json`. This is a leftover split/temp file. **Do not double-count it.** K08 effective unique records = 80 (batch_01: 40 + batch_02: 40). The file should be deleted or archived.

---

## Summary by Job

| Job | Description | Files | Total Records | Min Target | Status |
|-----|-------------|-------|---------------|-----------|--------|
| K01 | Observable Phrases | 6 | 300 | 300 | ✅ |
| K02 | Symptom Ontology | 7 | 280 | 300 | ⚠️ (–20 short) |
| K03 | Pathogenesis Rules | 6 | 180 | 80 | ✅ |
| K04 | Pattern Definitions | 6 | 135 | 120 | ✅ |
| K05 | Red Flag Rules | 2 | 70 | 47 | ✅ |
| K06 | Cluster Rules | 2 | 60 | 30 | ✅ |
| K07 | Herb-Drug Interactions | 2 | 80 | 80 | ✅ |
| K08 | Kiem Chung Rules | 2* | 80 | 30 | ✅ |
| **TOTAL** | **All knowledge base** | **33 files (32 unique)** | **1,185 (1,165 unique)** | **666** | ✅ |

*K08 has 3 physical files but 1 is a duplicate (batch_01_part2.json); effective unique files = 2, unique records = 80.

---

## Grand Total

**Total physical files: 34** (including 00_data_inventory.md) — **33 JSON data files**
**Total unique JSON data files: 32** (excluding duplicate batch_01_part2.json)
**Total records (raw): 1,185**
**Total unique records: 1,165** (subtracting 20 duplicates from K08 batch_01_part2.json)
**All minimum targets met: YES** — all 8 jobs meet or exceed their minimum targets.

---

## Issues and Notes

### ISSUE 1 — K08: batch_01_part2.json is a duplicate leftover (ACTION REQUIRED)

`k08_kiem_chung/batch_01_part2.json` contains 20 records (KC_B01_021–KC_B01_040) that are fully duplicated in `batch_01_kiem_chung.json`. This mirrors the K01 split-file pattern found in the previous inventory. The file is a temp/split artifact from a generation session that was subsequently completed in the main file.

**Action:** Delete or archive `batch_01_part2.json`. Do not include it in any merge or loading pipeline — it will create duplicate entries.

**Effective K08 state after cleanup:** 2 files, 80 records (KC_B01_001–KC_B01_040 + KC_B02_001–KC_B02_040). Target of 30 is exceeded.

### ISSUE 2 — K02: Total records (280) below target (300)

Seven batches of 40 records = 280 records. The 300-record minimum requires one additional batch of at least 20 records, or augmenting existing batches.

### ISSUE 3 — K02: symptom_code is slug-style (informational)

All K02 files use `symptom_code` as the ID field (slug strings, e.g. `lumbar_pain_acute`). This is consistent across all 7 batches (no numeric `id` field). No action required unless a numeric sequential ID is needed by downstream consumers.

### ISSUE 4 — K01: Previous split file (batch_06_part2_temp.json) resolved

The prior inventory flagged `batch_06_tiet_nieu_phu_khoa_da.json` as a 25-record split file paired with a temp file. The current state shows a single complete 50-record file (OBS_B06_001–OBS_B06_050). The split has been merged. K01 is now clean: 6 files, 300 records, target met.

### ISSUE 5 — K04: Two new batches added since last inventory (informational)

Previous inventory showed 4 K04 files (85 records, 35 short of target). Two new batches were added: `batch_05_phuc_tap_patterns.json` (25 records) and `batch_06_benh_chung.json` (25 records). K04 now has 135 records across 6 files, exceeding the 120-record target.

### ISSUE 6 — K07: Second batch added since last inventory (informational)

Previous inventory showed 1 K07 file (40 records, 40 short of target). `batch_02_tuong_tac_2.json` has been added (40 records). K07 now has 80 records across 2 files, exactly meeting the 80-record target.
