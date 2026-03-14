<?php
require_once __DIR__ . '/base_importer.php';

class ImportK04 extends BaseImporter
{
    public function import(): void
    {
        $files = [
            'k04_patterns/batch_01_can_patterns.json',
            'k04_patterns/batch_02_than_patterns.json',
            'k04_patterns/batch_03_ty_vi_patterns.json',
            'k04_patterns/batch_04_tam_phe_patterns.json',
            'k04_patterns/batch_05_phuc_tap_patterns.json',
            'k04_patterns/batch_06_benh_chung.json',
        ];

        // Maps to actual kb_patterns schema:
        // chung_code, name_vi, name_en, organ_system,
        // required_symptoms, two_or_more_of, supporting_symptoms,
        // differentiating_symptoms_positive, differentiating_symptoms_negative,
        // differentiates_from, phap_tri_code, phap_tri_vi,
        // phuong_thuoc, huyet_vi, life_advice_vi,
        // clinical_note, yhhd_correlates, key_questions,
        // prevalence_vn, status
        $sql = <<<SQL
            INSERT OR REPLACE INTO kb_patterns
                (chung_code, name_vi, name_en, organ_system,
                 required_symptoms, two_or_more_of, supporting_symptoms,
                 differentiating_symptoms_positive, differentiating_symptoms_negative,
                 differentiates_from, phap_tri_code, phap_tri_vi,
                 phuong_thuoc, huyet_vi, life_advice_vi,
                 clinical_note, yhhd_correlates, key_questions,
                 prevalence_vn, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        SQL;
        $stmt = $this->pdo->prepare($sql);

        foreach ($files as $file) {
            echo "  Importing $file...\n";
            $rows = $this->loadJson($file);
            foreach ($rows as $r) {
                try {
                    $dc       = $r['diagnostic_criteria'] ?? [];
                    $phapTri  = $r['phap_tri'] ?? [];
                    $required = $dc['required'] ?? [];
                    $twoMore  = $dc['two_or_more_of'] ?? [];

                    // Build differentiating symptom lists from differential_diagnosis
                    $diffDiag  = $r['differential_diagnosis'] ?? [];
                    $diffCodes = array_map(fn($d) => $d['pattern_code'] ?? null, $diffDiag);
                    $diffCodes = array_filter($diffCodes);

                    // Tongue + pulse as key questions JSON
                    $keyQ = [];
                    if (!empty($dc['tongue'])) $keyQ['tongue'] = $dc['tongue'];
                    if (!empty($dc['pulse']))  $keyQ['pulse']  = $dc['pulse'];

                    // Acupuncture points: keep as JSON array
                    $huyet = $phapTri['acupuncture_points'] ?? [];

                    // phuong_thuoc: primary + alternatives combined
                    $phuongThuoc = $phapTri['primary_formula'] ?? null;
                    if (!empty($phapTri['alternative_formulas'])) {
                        $phuongThuoc .= ' | ' . implode(' | ', $phapTri['alternative_formulas']);
                    }

                    // clinical_note: key features + safety
                    $clinNote = $r['key_distinguishing_features'] ?? '';
                    if (!empty($r['safety_notes'])) {
                        $clinNote .= "\n[Safety] " . $r['safety_notes'];
                    }

                    $stmt->execute([
                        $r['chung_code'],
                        $r['name_vi'],
                        $r['name_en']              ?? null,
                        $r['zangfu_primary']        ?? null,
                        $this->encode($required),
                        $this->encode($twoMore),
                        $this->encode($twoMore),   // supporting = same as two_or_more_of
                        $this->encode(array_values($diffCodes)), // pos diff
                        $this->encode([]),
                        $this->encode($diffDiag),
                        $phapTri['principle_en']    ?? null,
                        $phapTri['principle_vi']    ?? null,
                        $phuongThuoc,
                        $this->encode($huyet),
                        $phapTri['lifestyle_advice'] ?? null,
                        $clinNote ?: null,
                        $this->encode($r['yhhd_correlations'] ?? []),
                        $this->encode($keyQ),
                        $r['prevalence_vietnam']    ?? null,
                        'active',
                    ]);
                    $this->inserted++;
                } catch (Exception $e) {
                    echo "  [ERROR] {$r['chung_code']}: {$e->getMessage()}\n";
                    $this->errors++;
                }
            }
        }
    }
}
