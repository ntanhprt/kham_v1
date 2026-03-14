<?php
require_once __DIR__ . '/base_importer.php';

class ImportK01 extends BaseImporter
{
    public function import(): void
    {
        $files = [
            'k01_observable_phrases/batch_01_tieu_hoa.json',
            'k01_observable_phrases/batch_02_dau_than_kinh.json',
            'k01_observable_phrases/batch_03_tim_ho_hap.json',
            'k01_observable_phrases/batch_04_co_xuong_khop.json',
            'k01_observable_phrases/batch_05_toan_than.json',
            'k01_observable_phrases/batch_06_tiet_nieu_phu_khoa_da.json',
        ];

        // Maps to actual kb_observable_phrases schema:
        // phrase_id, phrase_vi, variants_vi, disambiguation_options,
        // linked_symptom_codes, organ_hint, status
        $sql = <<<SQL
            INSERT OR REPLACE INTO kb_observable_phrases
                (phrase_id, phrase_vi, variants_vi, disambiguation_options,
                 linked_symptom_codes, organ_hint, status)
            VALUES (?,?,?,?,?,?,?)
        SQL;
        $stmt = $this->pdo->prepare($sql);

        foreach ($files as $file) {
            echo "  Importing $file...\n";
            $rows = $this->loadJson($file);
            foreach ($rows as $r) {
                try {
                    // Collect all symptom_codes linked from all options
                    $linkedCodes = [];
                    $options = $r['options'] ?? [];
                    foreach ($options as $opt) {
                        foreach ($opt['symptom_codes'] ?? [] as $code) {
                            if (!in_array($code, $linkedCodes)) {
                                $linkedCodes[] = $code;
                            }
                        }
                    }

                    // Store full options including disambiguation_question
                    $disambigData = [
                        'question'              => $r['disambiguation_question'] ?? null,
                        'requires_disambiguation'=> $r['requires_disambiguation'] ?? false,
                        'options'               => $options,
                    ];

                    // Extract organ hint from notes or first option's yhct_hint
                    $organHint = $r['notes'] ?? null;
                    if (!$organHint && !empty($options[0]['yhct_hint'])) {
                        $organHint = $options[0]['yhct_hint'];
                    }

                    $stmt->execute([
                        $r['phrase_id'],
                        $r['phrase_vi'],
                        $this->encode($r['variants_vi'] ?? []),
                        $this->encode($disambigData),
                        $this->encode($linkedCodes),
                        $organHint,
                        'active',
                    ]);
                    $this->inserted++;
                } catch (Exception $e) {
                    echo "  [ERROR] {$r['phrase_id']}: {$e->getMessage()}\n";
                    $this->errors++;
                }
            }
        }
    }
}
