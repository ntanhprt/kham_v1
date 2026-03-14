<?php
require_once __DIR__ . '/base_importer.php';

class ImportK02 extends BaseImporter
{
    public function import(): void
    {
        $files = [
            'k02_symptoms/batch_01_than_kinh.json',
            'k02_symptoms/batch_02_tim_ho_hap.json',
            'k02_symptoms/batch_03_tieu_hoa.json',
            'k02_symptoms/batch_04_co_xuong_khop.json',
            'k02_symptoms/batch_05_toan_than.json',
            'k02_symptoms/batch_06_tiet_nieu_da_tam_than.json',
            'k02_symptoms/batch_07_phu_khoa_sinh_san.json',
            'k02_symptoms/batch_08_cao_tuoi_tre_em.json',
        ];

        // Maps to actual kb_symptoms schema:
        // symptom_code, name_vi, name_en, category, organ_system,
        // bat_cuong_weights, yhct_clinical_note, yhhd_clinical_note,
        // lay_descriptions_vi, context_summary_vi, red_flag_level, status
        $sql = <<<SQL
            INSERT OR REPLACE INTO kb_symptoms
                (symptom_code, name_vi, name_en, category, organ_system,
                 bat_cuong_weights, yhct_clinical_note, yhhd_clinical_note,
                 lay_descriptions_vi, context_summary_vi, red_flag_level, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        SQL;
        $stmt = $this->pdo->prepare($sql);

        $aliasSQL  = "INSERT OR IGNORE INTO symptom_aliases (symptom_code, alias, alias_type) VALUES (?,?,?)";
        $aliasStmt = $this->pdo->prepare($aliasSQL);

        foreach ($files as $file) {
            echo "  Importing $file...\n";
            $rows = $this->loadJson($file);
            foreach ($rows as $r) {
                try {
                    // Determine primary organ_system from zangfu_weights (highest value)
                    $zangfu = $r['zangfu_weights'] ?? [];
                    $organSystem = null;
                    if (!empty($zangfu)) {
                        arsort($zangfu);
                        $organSystem = array_key_first($zangfu);
                    }

                    $stmt->execute([
                        $r['symptom_code'],
                        $r['name_vi'],
                        $r['name_en']            ?? null,
                        $r['category']           ?? null,
                        $organSystem,
                        $this->encode($r['bat_cuong_weights'] ?? null),
                        $r['yhct_clinical_note'] ?? null,
                        $r['yhhd_clinical_note'] ?? null,
                        null,  // lay_descriptions_vi
                        $r['time_sensitivity']   ?? null,  // context_summary_vi
                        $r['red_flag_level']     ?? null,
                        'active',
                    ]);
                    $this->inserted++;

                    // Insert aliases: name_vi and name_en
                    $code = $r['symptom_code'];
                    $aliasStmt->execute([$code, mb_strtolower($r['name_vi'], 'UTF-8'), 'formal']);
                    if (!empty($r['name_en'])) {
                        $aliasStmt->execute([$code, mb_strtolower($r['name_en'], 'UTF-8'), 'formal']);
                    }
                } catch (Exception $e) {
                    echo "  [ERROR] {$r['symptom_code']}: {$e->getMessage()}\n";
                    $this->errors++;
                }
            }
        }
    }
}
