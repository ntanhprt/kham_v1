<?php
require_once __DIR__ . '/base_importer.php';

class ImportK05 extends BaseImporter
{
    public function import(): void
    {
        $files = [
            'k05_red_flags/batch_01_cap_cuu.json',
            'k05_red_flags/batch_02_than_trong.json',
        ];

        // Maps to actual kb_red_flags schema:
        // rule_code, level, trigger_symptoms, trigger_combination,
        // satellite_symptoms, name_vi, message_vi, action_vi,
        // suppress_yhct_output, status
        $sql = <<<SQL
            INSERT OR REPLACE INTO kb_red_flags
                (rule_code, level, trigger_symptoms, trigger_combination,
                 satellite_symptoms, name_vi, message_vi, action_vi,
                 suppress_yhct_output, status)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        SQL;
        $stmt = $this->pdo->prepare($sql);

        foreach ($files as $file) {
            echo "  Importing $file...\n";
            $rows = $this->loadJson($file);
            foreach ($rows as $r) {
                try {
                    // Extract trigger symptom codes from trigger_logic
                    $triggerLogic = $r['trigger_logic'] ?? [];
                    $triggerCodes = $this->extractSymptomCodes($triggerLogic);

                    // Extract satellite codes from alternative_trigger
                    $altTrigger    = $r['alternative_trigger'] ?? [];
                    $satelliteCodes = $this->extractSymptomCodes($altTrigger);

                    // Map triage_level to engine-expected format
                    $level = $r['triage_level'] ?? 'L3_watch';
                    // Normalize: L1_emergency, L2_urgent, L3_watch
                    if (str_starts_with($level, 'L1')) $level = 'L1_emergency';
                    elseif (str_starts_with($level, 'L2')) $level = 'L2_urgent';
                    else $level = 'L3_watch';

                    // Build action_vi
                    $actionParts = array_filter([
                        $r['emergency_action']     ?? null,
                        $r['special_instructions'] ?? null,
                    ]);
                    $actionVi = implode(' | ', $actionParts) ?: null;

                    // message_vi: use display_message_vi, fallback to yhct_warning
                    $messageVi = $r['display_message_vi'] ?? $r['yhct_warning'] ?? $r['name_vi'];

                    $stmt->execute([
                        $r['red_flag_code'],
                        $level,
                        $this->encode($triggerCodes),
                        $this->encode($triggerLogic),
                        $this->encode($satelliteCodes),
                        $r['name_vi'],
                        $messageVi,
                        $actionVi,
                        $this->bool($r['suppress_yhct_output'] ?? false),
                        'active',
                    ]);
                    $this->inserted++;
                } catch (Exception $e) {
                    echo "  [ERROR] {$r['red_flag_code']}: {$e->getMessage()}\n";
                    $this->errors++;
                }
            }
        }
    }

    /** Recursively extract symptom_code values from a trigger logic tree */
    private function extractSymptomCodes(array $logic): array
    {
        $codes = [];
        if (isset($logic['symptom_code'])) {
            $codes[] = $logic['symptom_code'];
        }
        foreach ($logic['conditions'] ?? [] as $cond) {
            $codes = array_merge($codes, $this->extractSymptomCodes($cond));
        }
        return array_values(array_unique($codes));
    }
}
