<?php
require_once __DIR__ . '/base_importer.php';

class ImportK06 extends BaseImporter
{
    public function import(): void
    {
        $files = [
            'k06_clusters/batch_01_cum_benh.json',
            'k06_clusters/batch_02_cum_benh_2.json',
        ];

        // Maps to actual kb_clusters schema:
        // cluster_code, name_vi, required_codes, optional_codes, min_optional,
        // threshold, context_required, message_vi, action_base, urgency, status
        $sql = <<<SQL
            INSERT OR REPLACE INTO kb_clusters
                (cluster_code, name_vi, required_codes, optional_codes, min_optional,
                 threshold, context_required, message_vi, action_base, urgency, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        SQL;
        $stmt = $this->pdo->prepare($sql);

        foreach ($files as $file) {
            echo "  Importing $file...\n";
            $rows = $this->loadJson($file);
            foreach ($rows as $r) {
                try {
                    // Extract required and optional symptom codes from trigger_symptoms
                    $triggerSymptoms = $r['trigger_symptoms'] ?? [];
                    $requiredCodes   = [];
                    $optionalCodes   = [];
                    foreach ($triggerSymptoms as $ts) {
                        $code = is_array($ts) ? ($ts['symptom_code'] ?? null) : $ts;
                        if (!$code) continue;
                        $isRequired = is_array($ts) ? ($ts['required'] ?? false) : true;
                        if ($isRequired) $requiredCodes[] = $code;
                        else             $optionalCodes[] = $code;
                    }
                    // Store all codes as CSV (required first for ClusterEngine)
                    $allCodes    = array_merge($requiredCodes, $optionalCodes);
                    $allCsv      = implode(',', $allCodes);
                    $optionalCsv = implode(',', $optionalCodes);
                    $minOptional = !empty($optionalCodes)
                        ? max(1, (int)ceil(count($optionalCodes) * 0.4))
                        : 0;

                    $urgency = $r['triage_level'] ?? 'L3_watch';
                    if (str_starts_with($urgency, 'L1')) $urgency = 'L1_emergency';
                    elseif (str_starts_with($urgency, 'L2')) $urgency = 'L2_urgent';

                    $contextReq = isset($r['time_gate'])
                        ? json_encode($r['time_gate'], JSON_UNESCAPED_UNICODE)
                        : null;
                    $messageVi  = $r['alert_body_vi'] ?? $r['alert_title_vi'] ?? $r['name_vi'];
                    $actionBase = $r['action_required'] ?? null;

                    $stmt->execute([
                        $r['cluster_code'],
                        $r['name_vi'],
                        $allCsv,
                        $optionalCsv,
                        $minOptional,
                        $r['probability_threshold'] ?? 0.6,
                        $contextReq,
                        $messageVi,
                        $actionBase,
                        $urgency,
                        'active',
                    ]);
                    $this->inserted++;
                } catch (Exception $e) {
                    echo "  [ERROR] {$r['cluster_code']}: {$e->getMessage()}\n";
                    $this->errors++;
                }
            }
        }
    }
}
