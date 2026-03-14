<?php
require_once __DIR__ . '/base_importer.php';

class ImportK07 extends BaseImporter
{
    public function import(): void
    {
        $files = [
            'k07_herb_drug/batch_01_tuong_tac.json',
            'k07_herb_drug/batch_02_tuong_tac_2.json',
            'k07_herb_drug/batch_03_thai_ky_tre_em.json',
        ];

        // Maps to actual kb_herb_drug schema:
        // rule_code, herb_codes, drug_classes, drug_examples,
        // severity, mechanism_vi, message_to_patient, message_to_doctor,
        // evidence_level, status
        $sql = <<<SQL
            INSERT OR REPLACE INTO kb_herb_drug
                (rule_code, herb_codes, drug_classes, drug_examples,
                 severity, mechanism_vi, message_to_patient, message_to_doctor,
                 evidence_level, status)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        SQL;
        $stmt = $this->pdo->prepare($sql);

        foreach ($files as $file) {
            echo "  Importing $file...\n";
            $rows = $this->loadJson($file);
            foreach ($rows as $r) {
                try {
                    // herb_codes: use herb_category as code, store herb_vi as CSV
                    $herbCode = $r['herb_category'] ?? mb_strtolower($r['herb_vi'], 'UTF-8');
                    // drug_classes: CSV of drug_class + drug_generic_names
                    $drugClasses = [$r['drug_class'] ?? ''];
                    if (!empty($r['drug_generic_names'])) {
                        $drugClasses = array_merge($drugClasses, (array)$r['drug_generic_names']);
                    }
                    $drugClassesCsv  = implode(',', array_filter($drugClasses));
                    $drugExamplesCsv = implode(',', $r['drug_brand_names_vn'] ?? []);

                    $stmt->execute([
                        $r['interaction_id'],
                        $herbCode,
                        $drugClassesCsv,
                        $drugExamplesCsv,
                        $r['severity'],
                        $r['interaction_mechanism'] ?? $r['clinical_effect_vi'] ?? null,
                        $r['warning_message_vi'] ?? null,
                        $r['action_required']    ?? null,
                        $r['evidence_level']     ?? null,
                        'active',
                    ]);
                    $this->inserted++;
                } catch (Exception $e) {
                    echo "  [ERROR] {$r['interaction_id']}: {$e->getMessage()}\n";
                    $this->errors++;
                }
            }
        }
    }
}
