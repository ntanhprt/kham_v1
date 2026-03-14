<?php
/**
 * Red Flag Engine - Safety filter (P0 priority)
 *
 * Checks K05 rules against selected symptoms.
 * Must NEVER miss L1 emergencies.
 *
 * Level definitions:
 *   L1_emergency - Cấp cứu ngay: gọi 115, vào viện
 *   L2_urgent    - Khẩn cấp: khám trong 24h
 *   L3_watch     - Theo dõi: tái khám sớm nếu nặng hơn
 */
class RedFlagEngine
{
    // All K05 rules indexed by code
    private array $rules = [];

    // Satellite symptom cache: rule_code => [satellite codes]
    private array $satelliteCache = [];

    /**
     * Constructor - loads K05 rules from database
     */
    public function __construct()
    {
        $this->loadRules();
    }

    /**
     * Load all K05 red flag rules from database
     */
    private function loadRules(): void
    {
        try {
            $db   = Database::get();
            $rows = $db->query("SELECT * FROM kb_red_flags ORDER BY level DESC, rule_code ASC")
                       ->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                // Parse trigger_symptoms JSON array
                $row['symptom_codes_arr'] = json_decode($row['trigger_symptoms'] ?? '[]', true) ?: [];

                // Context triggers not in current schema — empty
                $row['context_triggers_arr'] = [];

                // Parse satellite_symptoms JSON array
                $row['satellite_codes_arr'] = json_decode($row['satellite_symptoms'] ?? '[]', true) ?: [];

                // Compatibility aliases
                $row['code']        = $row['rule_code'];
                $row['description'] = $row['message_vi'] ?? '';
                $row['action']      = $row['action_vi']  ?? '';

                $this->rules[$row['rule_code']] = $row;

                // Pre-cache satellite symptoms
                $this->satelliteCache[$row['rule_code']] = array_merge(
                    $row['symptom_codes_arr'],
                    $row['satellite_codes_arr']
                );
            }
        } catch (\Exception $e) {
            // Graceful degradation: empty rules means no red flags triggered
            $this->rules = [];
        }
    }

    /**
     * Check all K05 rules against selected symptoms and context
     *
     * @param array $selectedCodes Selected symptom codes
     * @param array $contextFlags  Context flags (has_emotion, has_drug, etc.)
     * @param array $quickAnswers  Quick question answers (onset, trajectory, etc.)
     * @return array Triggered flags sorted by severity (L1 first)
     */
    public function check(array $selectedCodes, array $contextFlags = [], array $quickAnswers = []): array
    {
        $triggered = [];

        foreach ($this->rules as $ruleCode => $rule) {
            $triggered_by = [];

            // --- Primary check: direct symptom code match ---
            $codeMatches = array_values(array_intersect($selectedCodes, $rule['symptom_codes_arr']));
            if (!empty($codeMatches)) {
                $triggered_by[] = ['type' => 'symptom_code', 'matches' => $codeMatches];
            }

            // --- Context trigger check ---
            foreach ($rule['context_triggers_arr'] as $trigger) {
                $flagKey = 'has_' . $trigger;
                if (!empty($contextFlags[$flagKey])) {
                    $triggered_by[] = ['type' => 'context', 'trigger' => $trigger];
                }
            }

            // --- Quick answer checks ---
            // Sudden/abrupt onset is a red flag booster
            if (!empty($quickAnswers['onset'])) {
                $onset = mb_strtolower($quickAnswers['onset'], 'UTF-8');
                if (mb_strpos($onset, 'đột ngột', 0, 'UTF-8') !== false
                 || mb_strpos($onset, 'cấp tính', 0, 'UTF-8') !== false) {
                    // Only relevant for L1 rules
                    if ($rule['level'] === 'L1_emergency' && !empty($codeMatches)) {
                        $triggered_by[] = ['type' => 'quick_answer', 'key' => 'onset', 'value' => $onset];
                    }
                }
            }

            // Worsening trajectory boosts L2 rules
            if (!empty($quickAnswers['trajectory'])) {
                $traj = mb_strtolower($quickAnswers['trajectory'], 'UTF-8');
                if ((mb_strpos($traj, 'nặng hơn', 0, 'UTF-8') !== false
                  || mb_strpos($traj, 'tăng dần', 0, 'UTF-8') !== false)
                  && $rule['level'] === 'L2_urgent'
                  && !empty($codeMatches)) {
                    $triggered_by[] = ['type' => 'quick_answer', 'key' => 'trajectory', 'value' => $traj];
                }
            }

            if (empty($triggered_by)) {
                continue;
            }

            // Count satellite symptom matches (strengthen confidence)
            $satelliteMatches = array_values(array_intersect($selectedCodes, $rule['satellite_codes_arr']));

            $triggered[] = [
                'code'                 => $ruleCode,
                'level'                => $rule['level']          ?? 'L3_watch',
                'name_vi'              => $rule['name_vi']        ?? $ruleCode,
                'description'          => $rule['description']    ?? '',
                'action'               => $rule['action']         ?? 'Vui lòng tham khảo ý kiến bác sĩ.',
                'suppress_yhct_output' => !empty($rule['suppress_yhct_output']),
                'triggered_by'         => $triggered_by,
                'matched_codes'        => $codeMatches,
                'satellite_matches'    => $satelliteMatches,
                'confidence'           => $this->computeConfidence(
                    $codeMatches,
                    $satelliteMatches,
                    $rule,
                    $triggered_by
                ),
            ];
        }

        // Sort: L1_emergency → L2_urgent → L3_watch, then by confidence DESC
        $levelOrder = ['L1_emergency' => 0, 'L2_urgent' => 1, 'L3_watch' => 2];
        usort($triggered, function ($a, $b) use ($levelOrder) {
            $la = $levelOrder[$a['level']] ?? 3;
            $lb = $levelOrder[$b['level']] ?? 3;
            if ($la !== $lb) {
                return $la <=> $lb;
            }
            return $b['confidence'] <=> $a['confidence'];
        });

        return $triggered;
    }

    /**
     * Compute a confidence score for a triggered flag
     *
     * @param array $codeMatches     Direct symptom matches
     * @param array $satelliteMatch  Satellite symptom matches
     * @param array $rule            Rule definition
     * @param array $triggeredBy     Trigger sources
     * @return float Confidence [0.0, 1.0]
     */
    private function computeConfidence(
        array $codeMatches,
        array $satelliteMatch,
        array $rule,
        array $triggeredBy
    ): float {
        $totalCodes    = max(1, count($rule['symptom_codes_arr']));
        $matchFraction = count($codeMatches) / $totalCodes;

        // Satellite bonus: up to 0.2 extra
        $satBonus = min(0.2, count($satelliteMatch) * 0.05);

        // Context/quick-answer bonus: 0.1 per additional trigger type
        $contextBonus = 0.0;
        foreach ($triggeredBy as $tb) {
            if ($tb['type'] !== 'symptom_code') {
                $contextBonus += 0.1;
            }
        }

        return min(1.0, round($matchFraction + $satBonus + $contextBonus, 3));
    }

    /**
     * Get the highest triage level from a set of triggered flags
     *
     * @param array $triggeredFlags
     * @return string|null Highest level or null if none
     */
    public function getLevel(array $triggeredFlags): ?string
    {
        if (empty($triggeredFlags)) {
            return null;
        }
        $levels    = array_column($triggeredFlags, 'level');
        $levelOrder = ['L1_emergency' => 0, 'L2_urgent' => 1, 'L3_watch' => 2];
        $min        = PHP_INT_MAX;
        $minLevel   = null;
        foreach ($levels as $level) {
            $order = $levelOrder[$level] ?? 99;
            if ($order < $min) {
                $min      = $order;
                $minLevel = $level;
            }
        }
        return $minLevel;
    }

    /**
     * Get satellite symptoms for a specific rule
     *
     * @param string $ruleCode
     * @return array
     */
    public function getSatelliteSymptoms(string $ruleCode): array
    {
        return $this->satelliteCache[$ruleCode] ?? [];
    }

    /**
     * Determine whether YHCT content should be suppressed given triggered flags
     *
     * Suppresses YHCT when:
     *   - Any L1_emergency flag is triggered
     *   - Multiple L2_urgent flags are triggered with high confidence
     *
     * @param array $triggeredFlags
     * @return bool
     */
    public function shouldSuppressYHCT(array $triggeredFlags): bool
    {
        if (empty($triggeredFlags)) {
            return false;
        }

        // Always suppress for L1
        foreach ($triggeredFlags as $flag) {
            if ($flag['level'] === 'L1_emergency') {
                return true;
            }
        }

        // Suppress for any L2 flag with explicit suppress_yhct_output = true
        foreach ($triggeredFlags as $flag) {
            if ($flag['level'] === 'L2_urgent' && !empty($flag['suppress_yhct_output'])) {
                return true;
            }
        }

        // Suppress for multiple high-confidence L2 flags
        $highConfidenceL2 = array_filter(
            $triggeredFlags,
            fn($f) => $f['level'] === 'L2_urgent' && $f['confidence'] >= 0.75
        );

        return count($highConfidenceL2) >= 2;
    }

    /**
     * Get emergency instruction text for a triggered flag
     *
     * @param array $flag
     * @return string
     */
    public function getEmergencyInstructions(array $flag): string
    {
        $level = $flag['level'] ?? 'L3_watch';

        return match ($level) {
            'L1_emergency' => '🚨 GỌI NGAY 115 hoặc đến phòng cấp cứu gần nhất. '
                . 'Không tự điều trị. Không dùng thuốc YHCT khi chưa được đánh giá y tế. '
                . ($flag['action'] ?? ''),
            'L2_urgent'    => '⚠️ Cần khám bác sĩ trong vòng 24 giờ. '
                . ($flag['action'] ?? 'Đến cơ sở y tế gần nhất nếu triệu chứng nặng hơn.'),
            'L3_watch'     => 'ℹ️ Theo dõi triệu chứng. '
                . ($flag['action'] ?? 'Tái khám nếu triệu chứng không cải thiện sau 3-5 ngày.'),
            default        => $flag['action'] ?? 'Tham khảo ý kiến bác sĩ.',
        };
    }

    /**
     * Get all rules (for testing/debug)
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}
