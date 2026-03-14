<?php
require_once __DIR__ . '/base_importer.php';

class ImportK08 extends BaseImporter
{
    public function import(): void
    {
        $files = [
            'k08_kiem_chung/batch_01_kiem_chung.json',
            'k08_kiem_chung/batch_02_kiem_chung_2.json',
        ];

        // Maps to actual kb_kiem_chung schema:
        // primary_pattern, secondary_pattern, phap_tri_combined_vi,
        // caution_list, priority_rule, clinical_note
        $sql = <<<SQL
            INSERT OR REPLACE INTO kb_kiem_chung
                (primary_pattern, secondary_pattern, phap_tri_combined_vi,
                 caution_list, priority_rule, clinical_note)
            VALUES (?,?,?,?,?,?)
        SQL;
        $stmt = $this->pdo->prepare($sql);

        foreach ($files as $file) {
            echo "  Importing $file...\n";
            $rows = $this->loadJson($file);
            foreach ($rows as $r) {
                try {
                    // Extract primary and secondary pattern from component_benh_co
                    $components  = $r['component_benh_co'] ?? [];
                    $primaryCode = $components[0] ?? ($r['kiem_chung_code'] ?? null);
                    $secondCode  = $components[1] ?? null;

                    // Build caution list from warning + phap_tri_note
                    $cautionParts = array_filter([
                        $r['warning']      ?? null,
                        $r['phap_tri_note'] ?? null,
                    ]);
                    $cautionList = implode(' | ', $cautionParts) ?: null;

                    // clinical_note: combine prognosis + presentations
                    $clinicalNote = $r['prognosis_note'] ?? null;
                    if (!empty($r['common_presentations'])) {
                        $presStr      = implode(', ', $r['common_presentations']);
                        $clinicalNote = ($clinicalNote ? $clinicalNote . ' | ' : '') . $presStr;
                    }

                    $stmt->execute([
                        $primaryCode,
                        $secondCode,
                        $r['phap_tri_combined'] ?? $r['typical_herb_formula'] ?? null,
                        $cautionList,
                        $r['prevalence'] ?? null,
                        $clinicalNote,
                    ]);
                    $this->inserted++;
                } catch (Exception $e) {
                    echo "  [ERROR] {$r['rule_id']}: {$e->getMessage()}\n";
                    $this->errors++;
                }
            }
        }
    }
}
