<?php
/**
 * Hypothesis Engine - Differential Diagnosis
 *
 * Finds competing diagnoses and differentiating questions.
 * Used to generate the "Nhóm 3" (differentiating) section
 * of the symptom picker and to drive the disambiguation flow.
 *
 * All ranking is deterministic: same input produces same output.
 */
class HypothesisEngine
{
    // Competing hypotheses are patterns within this fraction of the top score
    private const COMPETING_THRESHOLD = 0.20; // 20%

    // Minimum score to be considered a hypothesis
    private const MIN_HYPOTHESIS_SCORE = 0.15;

    /**
     * Get competing hypotheses (patterns within 20% of top score)
     *
     * @param array $engineResult Full result from YHCTEngine::analyze()
     * @param int   $topN         Maximum number of competing hypotheses to return
     * @return array Competing pattern matches
     */
    public function getCompetingHypotheses(array $engineResult, int $topN = 3): array
    {
        $ranked = $engineResult['chung_ranked'] ?? [];

        if (empty($ranked)) {
            return [];
        }

        $topScore = $ranked[0]['score'];

        if ($topScore < self::MIN_HYPOTHESIS_SCORE) {
            return [];
        }

        // Cutoff: within 20% of top score
        $cutoff = $topScore * (1.0 - self::COMPETING_THRESHOLD);

        $competing = array_filter(
            $ranked,
            fn($m) => $m['score'] >= $cutoff
        );

        return array_slice(array_values($competing), 0, $topN);
    }

    /**
     * Get symptoms that differentiate between competing hypotheses
     *
     * A differentiating symptom is one that:
     * - Appears in SOME but not ALL of the candidate patterns
     * - Is not already selected
     *
     * @param array $candidates    Competing pattern matches (from getCompetingHypotheses)
     * @param array $k04Patterns   Full K04 pattern map (pattern_code => pattern_data)
     * @return array Symptom codes with discrimination scores
     */
    public function getDifferentiatingSymptoms(array $candidates, array $k04Patterns): array
    {
        if (count($candidates) < 2) {
            return [];
        }

        // Collect all symptom codes across candidates, with presence per pattern
        $symptomPresence = []; // symptom_code => [pattern_code => bool]

        foreach ($candidates as $candidate) {
            $patCode  = $candidate['pattern']['pattern_code'] ?? null;
            $pattern  = $candidate['pattern'];

            if (!$patCode) {
                continue;
            }

            $allCodes = array_merge(
                $pattern['required_codes_arr'] ?? [],
                $pattern['optional_codes_arr'] ?? []
            );

            foreach ($allCodes as $code) {
                if (!isset($symptomPresence[$code])) {
                    $symptomPresence[$code] = [];
                }
                $symptomPresence[$code][$patCode] = true;
            }
        }

        $totalCandidates = count($candidates);
        $results         = [];

        foreach ($symptomPresence as $symptomCode => $patternPresence) {
            $presentCount = count($patternPresence);

            // Skip symptoms that are in ALL patterns (not differentiating)
            // Skip symptoms that are in NO other patterns (not relevant)
            if ($presentCount === $totalCandidates || $presentCount === 0) {
                continue;
            }

            // Discrimination score: maximized when symptom is in ~50% of patterns
            $fraction        = $presentCount / $totalCandidates;
            $discriminScore  = 1.0 - abs(($fraction * 2) - 1.0); // Peak at 0.5

            // Weight by the score difference it creates
            $scoreWith    = 0.0;
            $scoreWithout = 0.0;
            foreach ($candidates as $c) {
                $patCode = $c['pattern']['pattern_code'];
                $s       = $c['score'];
                if (!empty($patternPresence[$patCode])) {
                    $scoreWith += $s;
                } else {
                    $scoreWithout += $s;
                }
            }
            $scoreDiff = abs($scoreWith - $scoreWithout) / max(0.001, $scoreWith + $scoreWithout);

            $finalScore = ($discriminScore * 0.60) + ($scoreDiff * 0.40);

            $results[$symptomCode] = [
                'symptom_code'   => $symptomCode,
                'discrim_score'  => round($finalScore, 4),
                'present_in'     => array_keys($patternPresence),
                'absent_in'      => [], // Computed below
                'fraction'       => round($fraction, 3),
            ];
        }

        // Compute absent_in properly
        $allPatternCodes = array_map(fn($c) => $c['pattern']['pattern_code'], $candidates);
        foreach ($results as &$r) {
            $r['absent_in'] = array_values(array_diff($allPatternCodes, $r['present_in']));
        }
        unset($r);

        // Sort by discrimination score DESC, then code ASC
        uasort($results, function ($a, $b) {
            if (abs($a['discrim_score'] - $b['discrim_score']) < 0.0001) {
                return strcmp($a['symptom_code'], $b['symptom_code']);
            }
            return $b['discrim_score'] <=> $a['discrim_score'];
        });

        return array_values($results);
    }

    /**
     * Build "Nhóm 3" differentiating groups for the symptom picker
     *
     * Returns a structured array for display in the UI:
     * [
     *   ['question' => 'Bạn có gặp...?', 'symptom_code' => 'S_CAN_001',
     *    'if_yes' => 'can_hoa_thuong_yem', 'if_no' => 'can_khi_uuluong']
     * ]
     *
     * @param array $candidates Competing pattern matches
     * @return array Probe groups for display
     */
    public function getDifferentiatingGroups(array $candidates): array
    {
        if (count($candidates) < 2) {
            return [];
        }

        // Get differentiating symptoms (need K04 patterns - use from candidates)
        $k04 = [];
        foreach ($candidates as $c) {
            $code       = $c['pattern']['pattern_code'] ?? null;
            if ($code) {
                $k04[$code] = $c['pattern'];
            }
        }

        $diffSymptoms = $this->getDifferentiatingSymptoms($candidates, $k04);
        $topDiff      = array_slice($diffSymptoms, 0, 8);

        $groups = [];
        foreach ($topDiff as $ds) {
            // Find which patterns include this symptom
            $ifYes = [];
            $ifNo  = [];
            foreach ($candidates as $c) {
                $patCode    = $c['pattern']['pattern_code'];
                $patNameVi  = $c['pattern']['name_vi'] ?? $patCode;
                if (in_array($patCode, $ds['present_in'], true)) {
                    $ifYes[] = $patNameVi;
                } else {
                    $ifNo[]  = $patNameVi;
                }
            }

            $groups[] = [
                'symptom_code'  => $ds['symptom_code'],
                'discrim_score' => $ds['discrim_score'],
                'if_yes'        => $ifYes,
                'if_no'         => $ifNo,
                'fraction'      => $ds['fraction'],
            ];
        }

        return $groups;
    }

    /**
     * Build probe questions for the pre-picker disambiguation phase
     *
     * These are yes/no questions shown BEFORE the symptom picker
     * when the chief complaint matches multiple plausible patterns.
     *
     * @param array $hypotheses   Top competing hypotheses
     * @param array $contextFlags Context flags from parseChiefComplaint
     * @return array Array of probe questions
     */
    public function buildProbeQuestions(array $hypotheses, array $contextFlags): array
    {
        if (empty($hypotheses)) {
            return [];
        }

        $questions = [];

        // --- Pattern-based differentiation questions ---
        $diffGroups = $this->getDifferentiatingGroups($hypotheses);
        foreach (array_slice($diffGroups, 0, 3) as $group) {
            $questions[] = [
                'type'         => 'symptom_probe',
                'symptom_code' => $group['symptom_code'],
                'if_yes'       => $group['if_yes'],
                'if_no'        => $group['if_no'],
                'priority'     => $group['discrim_score'],
            ];
        }

        // --- Contextual questions (onset, duration) ---
        if (empty($contextFlags['has_temporal'])) {
            $questions[] = [
                'type'     => 'context_probe',
                'key'      => 'duration',
                'question' => 'Triệu chứng này bắt đầu từ khi nào?',
                'options'  => [
                    'Vài giờ đến 1 ngày',
                    '2-7 ngày',
                    '1-4 tuần',
                    'Trên 1 tháng',
                    'Nhiều tháng / mãn tính',
                ],
                'priority' => 0.80,
            ];
        }

        // --- Organ-specific questions based on competing tang ---
        $tangs = [];
        foreach ($hypotheses as $h) {
            $tang = $h['pattern']['primary_tang'] ?? null;
            if ($tang && !isset($tangs[$tang])) {
                $tangs[$tang] = true;
            }
        }

        foreach (array_keys($tangs) as $tang) {
            $probes = $this->getTangProbeQuestions($tang);
            foreach ($probes as $probe) {
                $questions[] = $probe;
            }
        }

        // Sort by priority DESC, then by type for determinism
        usort($questions, function ($a, $b) {
            $pa = $a['priority'] ?? 0;
            $pb = $b['priority'] ?? 0;
            if (abs($pa - $pb) < 0.001) {
                return strcmp($a['type'] ?? '', $b['type'] ?? '');
            }
            return $pb <=> $pa;
        });

        return array_slice($questions, 0, 5); // Max 5 probe questions
    }

    /**
     * Get organ-specific probe questions for differential diagnosis
     *
     * @param string $tang Organ system code (can, than, ty, phe, tam, etc.)
     * @return array Probe question arrays
     */
    private function getTangProbeQuestions(string $tang): array
    {
        $probes = [
            'can' => [
                [
                    'type'     => 'context_probe',
                    'key'      => 'anger_stress',
                    'question' => 'Triệu chứng có liên quan đến tức giận, stress hoặc cảm xúc tiêu cực không?',
                    'options'  => ['Có, rõ ràng', 'Có, chút ít', 'Không liên quan'],
                    'priority' => 0.75,
                    'tang'     => 'can',
                ],
                [
                    'type'     => 'context_probe',
                    'key'      => 'sleep_11pm',
                    'question' => 'Bạn có thường ngủ sau 23 giờ không?',
                    'options'  => ['Thường xuyên', 'Thỉnh thoảng', 'Ít khi'],
                    'priority' => 0.60,
                    'tang'     => 'can',
                ],
            ],
            'than' => [
                [
                    'type'     => 'context_probe',
                    'key'      => 'low_back_knees',
                    'question' => 'Có tình trạng đau mỏi thắt lưng hoặc đầu gối không?',
                    'options'  => ['Có thường xuyên', 'Thỉnh thoảng', 'Không'],
                    'priority' => 0.78,
                    'tang'     => 'than',
                ],
            ],
            'ty' => [
                [
                    'type'     => 'context_probe',
                    'key'      => 'appetite_digestion',
                    'question' => 'Bạn có thường ăn không ngon miệng hoặc tiêu hóa kém không?',
                    'options'  => ['Có thường xuyên', 'Thỉnh thoảng', 'Không'],
                    'priority' => 0.72,
                    'tang'     => 'ty',
                ],
            ],
            'phe' => [
                [
                    'type'     => 'context_probe',
                    'key'      => 'cold_weather_worse',
                    'question' => 'Triệu chứng có nặng hơn khi thời tiết lạnh hoặc ẩm không?',
                    'options'  => ['Rõ ràng', 'Đôi khi', 'Không liên quan'],
                    'priority' => 0.70,
                    'tang'     => 'phe',
                ],
            ],
            'tam' => [
                [
                    'type'     => 'context_probe',
                    'key'      => 'palpitations',
                    'question' => 'Bạn có cảm giác hồi hộp, đánh trống ngực không?',
                    'options'  => ['Có', 'Thỉnh thoảng', 'Không'],
                    'priority' => 0.80,
                    'tang'     => 'tam',
                ],
            ],
        ];

        return $probes[$tang] ?? [];
    }

    /**
     * Score how well a symptom differentiates between two specific patterns
     *
     * @param string $symptomCode
     * @param array  $patternA    First pattern definition
     * @param array  $patternB    Second pattern definition
     * @return float Score [0.0 = identical coverage, 1.0 = perfect discrimination]
     */
    public function getDifferentiationScore(string $symptomCode, array $patternA, array $patternB): float
    {
        $codesA = array_merge(
            $patternA['required_codes_arr'] ?? [],
            $patternA['optional_codes_arr'] ?? []
        );
        $codesB = array_merge(
            $patternB['required_codes_arr'] ?? [],
            $patternB['optional_codes_arr'] ?? []
        );

        $inA = in_array($symptomCode, $codesA, true);
        $inB = in_array($symptomCode, $codesB, true);

        if ($inA === $inB) {
            // Same presence = not discriminating
            return 0.0;
        }

        // Is it required in one pattern?
        $requiredA = in_array($symptomCode, $patternA['required_codes_arr'] ?? [], true);
        $requiredB = in_array($symptomCode, $patternB['required_codes_arr'] ?? [], true);

        if ($requiredA || $requiredB) {
            return 1.0; // Required in one but not other = perfectly discriminating
        }

        return 0.70; // Optional in one but not other
    }

    /**
     * Generate a human-readable summary of competing hypotheses
     *
     * @param array $hypotheses
     * @return string Vietnamese summary text
     */
    public function buildHypothesisSummary(array $hypotheses): string
    {
        if (empty($hypotheses)) {
            return 'Chưa đủ dữ liệu để xác định hướng chẩn đoán.';
        }

        if (count($hypotheses) === 1) {
            $name = $hypotheses[0]['pattern']['name_vi'] ?? 'chứng không xác định';
            $conf = $hypotheses[0]['confidence'];
            return "Hướng chính: {$name} (độ tin cậy: {$conf}).";
        }

        $names = array_map(
            fn($h) => ($h['pattern']['name_vi'] ?? 'N/A') . ' (' . round($h['score'] * 100) . '%)',
            $hypotheses
        );

        return 'Đang phân biệt giữa: ' . implode(', ', $names) . '. '
             . 'Chọn thêm triệu chứng để làm rõ chẩn đoán.';
    }
}
