<?php
require_once __DIR__ . '/base_importer.php';

class ImportK03 extends BaseImporter
{
    public function import(): void
    {
        $files = [
            'k03_pathogenesis/batch_01_can.json',
            'k03_pathogenesis/batch_02_than.json',
            'k03_pathogenesis/batch_03_ty_vi.json',
            'k03_pathogenesis/batch_04_tam.json',
            'k03_pathogenesis/batch_05_phe.json',
            'k03_pathogenesis/batch_06_lien_tang.json',
        ];

        // Maps to actual kb_pathogenesis_rules schema:
        // rule_code, organ_system, pathogenesis, pathogenesis_vi,
        // required_symptoms, supporting_symptoms, weight, status
        $sql = <<<SQL
            INSERT OR REPLACE INTO kb_pathogenesis_rules
                (rule_code, organ_system, pathogenesis, pathogenesis_vi,
                 required_symptoms, supporting_symptoms, weight, status)
            VALUES (?,?,?,?,?,?,?,?)
        SQL;
        $stmt = $this->pdo->prepare($sql);

        $prevalenceMap = [
            'very_common' => 0.9,
            'common'      => 0.7,
            'uncommon'    => 0.4,
            'rare'        => 0.2,
        ];

        foreach ($files as $file) {
            echo "  Importing $file...\n";
            $rows = $this->loadJson($file);
            foreach ($rows as $r) {
                try {
                    $prevStr = $r['prevalence'] ?? 'common';
                    $weight  = $prevalenceMap[$prevStr] ?? 0.5;

                    $stmt->execute([
                        $r['benh_co_code'],
                        $r['zangfu_primary'] ?? null,
                        $r['benh_co_en']     ?? $r['benh_co_vi'],
                        $r['benh_co_vi'],
                        $this->encode($r['required_symptoms']  ?? []),
                        $this->encode($r['supporting_symptoms'] ?? []),
                        $weight,
                        'active',
                    ]);
                    $this->inserted++;
                } catch (Exception $e) {
                    echo "  [ERROR] {$r['benh_co_code']}: {$e->getMessage()}\n";
                    $this->errors++;
                }
            }
        }
    }
}
