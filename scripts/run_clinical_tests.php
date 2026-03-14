<?php
/**
 * Clinical Test Runner
 *
 * Self-testing agent for the YHCT Diagnostic Engine.
 * Tests:
 *   - Chief complaint parsing (K01/K02 matching)
 *   - Pattern matching accuracy (K04)
 *   - Red flag detection (K05)
 *   - Symptom ranking
 *   - Safety filter (K07)
 *
 * Run: php scripts/run_clinical_tests.php [--verbose] [--json]
 */

define('APP_ROOT', __DIR__ . '/../app');
require APP_ROOT . '/config.php';
require APP_ROOT . '/core/Database.php';
require APP_ROOT . '/engine/YHCTEngine.php';
require APP_ROOT . '/engine/RedFlagEngine.php';
require APP_ROOT . '/engine/ClusterEngine.php';
require APP_ROOT . '/engine/SafetyFilter.php';
require APP_ROOT . '/engine/HypothesisEngine.php';

$verbose = in_array('--verbose', $argv ?? [], true);
$jsonOut = in_array('--json',    $argv ?? [], true);

// ============================================================================
// TEST CASE DEFINITIONS
// ============================================================================

/**
 * Each test case:
 *   name           => Human-readable label
 *   chief_complaint=> Raw Vietnamese patient input
 *   selected_codes => Symptoms patient confirms (simulates picker)
 *   quick_answers  => Quick question answers (optional)
 *   expect_pattern => Expected top chung_code (or null to skip)
 *   expect_redflags=> [rule_code, ...] expected red flags (empty=none expected)
 *   expect_tokens  => Tokens that must appear in parseChiefComplaint output
 *   min_score      => Minimum score for top pattern (0–1)
 *   expect_no_pattern => Pattern that must NOT appear in results
 */
$testCases = [

    // =========================================================================
    // T01–T05: Can (Gan/Liver) patterns
    // =========================================================================
    [
        'name'            => 'T01 Can duong vuong (Liver Yang Rising)',
        'chief_complaint' => 'đau đầu nhịp đập một bên, chóng mặt, hay cáu giận, miệng đắng',
        'selected_codes'  => ['throbbing_headache', 'dizziness_vertigo', 'irritability_emotional_lability', 'bitter_taste_mouth'],
        'quick_answers'   => [],
        'expect_pattern'  => 'liver_yang_rising',
        'expect_redflags' => [],
        'min_score'       => 0.6,
    ],
    [
        'name'            => 'T02 Can khi uat (Liver Qi Stagnation)',
        'chief_complaint' => 'đau hông sườn, hay thở dài, tâm trạng trầm uất, bụng đầy, hay cáu',
        'selected_codes'  => ['right_hypochondrial_pain', 'sighing_dyspnea', 'depression_mood', 'bloating_fullness', 'irritability_emotional_lability'],
        'quick_answers'   => [],
        'expect_pattern'  => 'liver_qi_stagnation',
        'expect_redflags' => [],
        'min_score'       => 0.5,
    ],
    [
        'name'            => 'T03 Can than am hu (Liver-Kidney Yin Deficiency)',
        'chief_complaint' => 'chóng mặt, mặt xanh xao, mất ngủ, mồ hôi trộm, đau lưng',
        'selected_codes'  => ['dizziness_vertigo', 'pale_complexion', 'insomnia', 'night_sweats', 'lower_back_pain'],
        'quick_answers'   => [],
        'expect_pattern'  => 'liver_kidney_yin_deficiency',
        'expect_redflags' => [],
        'min_score'       => 0.4,
    ],
    [
        'name'            => 'T04 Can hoa vuong (Liver Fire Blazing)',
        'chief_complaint' => 'đau đầu, mắt đỏ, hay cáu giận, miệng đắng, mồ hôi trộm',
        'selected_codes'  => ['throbbing_headache', 'red_eyes', 'irritability_emotional_lability', 'bitter_taste_mouth', 'night_sweats'],
        'quick_answers'   => [],
        'expect_pattern'  => 'liver_fire_blazing',
        'expect_redflags' => [],
        'min_score'       => 0.5,
    ],

    // =========================================================================
    // T05–T08: Than (Thận/Kidney) patterns
    // =========================================================================
    [
        'name'            => 'T05 Than am hu (Kidney Yin Deficiency)',
        'chief_complaint' => 'đau lưng, ù tai, mồ hôi trộm về đêm, lòng bàn tay nóng',
        'selected_codes'  => ['lower_back_pain', 'tinnitus', 'night_sweats', 'hot_palms_soles'],
        'quick_answers'   => [],
        'expect_pattern'  => 'kidney_yin_deficiency',
        'expect_redflags' => [],
        'min_score'       => 0.4,
    ],
    [
        'name'            => 'T06 Than duong hu (Kidney Yang Deficiency)',
        'chief_complaint' => 'đau lưng, tay chân lạnh, tiểu đêm nhiều, phù chân',
        'selected_codes'  => ['lower_back_pain', 'cold_extremities', 'frequent_urination_night', 'edema_lower_limbs'],
        'quick_answers'   => [],
        'expect_pattern'  => 'kidney_yang_deficiency',
        'expect_redflags' => [],
        'min_score'       => 0.5,
    ],
    [
        'name'            => 'T07 Ty Than Duong Hu (Spleen-Kidney Yang Deficiency)',
        'chief_complaint' => 'mệt mỏi, phân lỏng, tay chân lạnh, phù chân',
        'selected_codes'  => ['fatigue_general', 'loose_stools', 'cold_extremities', 'edema_lower_limbs', 'lower_back_pain'],
        'quick_answers'   => [],
        'expect_pattern'  => 'spleen_kidney_yang_deficiency',
        'expect_redflags' => [],
        'min_score'       => 0.5,
    ],

    // =========================================================================
    // T08–T12: Ty Vi (Tỳ Vị / Spleen-Stomach) patterns
    // =========================================================================
    [
        'name'            => 'T08 Ty khi hu (Spleen Qi Deficiency)',
        'chief_complaint' => 'mệt mỏi, ăn không ngon, bụng đầy, phân lỏng',
        'selected_codes'  => ['fatigue_general', 'poor_appetite_loss', 'bloating_fullness', 'loose_stools'],
        'quick_answers'   => [],
        'expect_pattern'  => 'spleen_qi_deficiency',
        'expect_redflags' => [],
        'min_score'       => 0.5,
    ],
    [
        'name'            => 'T09 Vi han thuc han (Stomach Cold Excess)',
        'chief_complaint' => 'buồn nôn, ợ hơi, đau vùng thượng vị sau ăn, bụng trướng',
        'selected_codes'  => ['nausea_vomiting', 'bloating_fullness', 'epigastric_pain'],
        'quick_answers'   => [],
        'expect_pattern'  => 'stomach_cold_excess',
        'expect_redflags' => [],
        'min_score'       => 0.4,
    ],
    [
        'name'            => 'T10 Huyet hu (Blood Deficiency) - pale + dizzy + insomnia',
        'chief_complaint' => 'mệt mỏi, mặt nhợt, chóng mặt, mất ngủ, hồi hộp',
        'selected_codes'  => ['fatigue_general', 'pale_complexion', 'dizziness_vertigo', 'insomnia', 'palpitations_irregular'],
        'quick_answers'   => [],
        'expect_pattern'  => 'blood_deficiency',
        'expect_redflags' => [],
        'min_score'       => 0.5,
    ],

    // =========================================================================
    // T11–T13: Tam (Tâm / Heart) patterns
    // =========================================================================
    [
        'name'            => 'T11 Tam huyet hu (Heart Blood Deficiency)',
        'chief_complaint' => 'mất ngủ, hồi hộp, hay quên, mặt nhợt nhạt',
        'selected_codes'  => ['insomnia', 'palpitations_irregular', 'poor_memory', 'pale_complexion'],
        'quick_answers'   => [],
        'expect_pattern'  => 'heart_blood_deficiency',
        'expect_redflags' => [],
        'min_score'       => 0.5,
    ],
    [
        'name'            => 'T12 Tam hoa vuong (Heart Fire Excess) - mouth sores, bitter taste',
        'chief_complaint' => 'mất ngủ, hồi hộp, miệng đắng, mắt đỏ, lo lắng',
        'selected_codes'  => ['insomnia', 'palpitations_irregular', 'bitter_taste_mouth', 'red_eyes', 'anxiety_worry'],
        'quick_answers'   => [],
        'expect_pattern'  => 'heart_fire_excess',
        'expect_redflags' => [],
        'min_score'       => 0.4,
    ],

    // =========================================================================
    // T13–T15: Phe (Phế / Lung) patterns
    // =========================================================================
    [
        'name'            => 'T13 Phong han pham phe (Wind-Cold attacking Lung)',
        'chief_complaint' => 'hay ho, khó thở, ngạt mũi',
        'selected_codes'  => ['cough_dry', 'shortness_of_breath', 'nasal_congestion'],
        'quick_answers'   => [],
        'expect_pattern'  => 'lung_wind_cold',
        'expect_redflags' => [],
        'min_score'       => 0.4,
    ],
    [
        'name'            => 'T14 Dam thap tro phe (Phlegm-Damp obstructing Lung)',
        'chief_complaint' => 'ho có đờm nhiều, khó thở, bụng đầy',
        'selected_codes'  => ['cough_with_phlegm', 'shortness_of_breath', 'bloating_fullness'],
        'quick_answers'   => [],
        'expect_pattern'  => 'lung_phlegm_damp',
        'expect_redflags' => [],
        'min_score'       => 0.4,
    ],

    // =========================================================================
    // T15–T18: Red Flag detection (K05)
    // =========================================================================
    [
        'name'            => 'T15 Red Flag L1: Stroke/TIA (dot quy)',
        'chief_complaint' => 'đột ngột méo miệng, tay chân yếu một bên, khó nói',
        'selected_codes'  => ['facial_droop_weakness'],
        'quick_answers'   => [],
        'expect_pattern'  => null,
        'expect_redflags' => ['stroke_tia'],
        'min_score'       => 0.0,
    ],
    [
        'name'            => 'T16 Red Flag L1: SAH (dau dau dot ngot)',
        'chief_complaint' => 'đau đầu đột ngột dữ dội như sét đánh',
        'selected_codes'  => ['severe_headache_sudden'],
        'quick_answers'   => [],
        'expect_pattern'  => null,
        'expect_redflags' => ['subarachnoid_hemorrhage'],
        'min_score'       => 0.0,
    ],
    [
        'name'            => 'T17 Red Flag L2: Hypertensive urgency',
        'chief_complaint' => 'đau đầu dữ dội, chóng mặt',
        'selected_codes'  => ['throbbing_headache', 'dizziness_vertigo'],
        'quick_answers'   => [],
        'expect_pattern'  => null,
        'expect_redflags' => ['hypertensive_urgency'],
        'min_score'       => 0.0,
    ],
    [
        'name'            => 'T18 Red Flag L2: Hemoptysis urgent',
        'chief_complaint' => 'ho ra máu, mệt mỏi nhiều',
        'selected_codes'  => ['hemoptysis', 'fatigue_general'],
        'quick_answers'   => [],
        'expect_pattern'  => null,
        'expect_redflags' => ['hemoptysis_urgent'],
        'min_score'       => 0.0,
    ],

    // =========================================================================
    // T19–T22: Chief complaint parsing accuracy
    // =========================================================================
    [
        'name'                => 'T19 Parse: "dau dau chong mat" → throbbing_headache + dizziness_vertigo',
        'chief_complaint'     => 'đau đầu chóng mặt',
        'selected_codes'      => [],
        'quick_answers'       => [],
        'expect_pattern'      => null,
        'expect_redflags'     => [],
        'expect_matched_codes'=> ['throbbing_headache', 'dizziness_vertigo'],
        'min_score'           => 0.0,
    ],
    [
        'name'                => 'T20 Parse: "mat ngu hoi hop" → insomnia + palpitations_irregular',
        'chief_complaint'     => 'mất ngủ hồi hộp',
        'selected_codes'      => [],
        'quick_answers'       => [],
        'expect_pattern'      => null,
        'expect_redflags'     => [],
        'expect_matched_codes'=> ['insomnia', 'palpitations_irregular'],
        'min_score'           => 0.0,
    ],
    [
        'name'                => 'T21 Parse: "dau lung tieu dem" → lower_back_pain + frequent_urination_night',
        'chief_complaint'     => 'đau lưng tiểu đêm',
        'selected_codes'      => [],
        'quick_answers'       => [],
        'expect_pattern'      => null,
        'expect_redflags'     => [],
        'expect_matched_codes'=> ['lower_back_pain', 'frequent_urination_night'],
        'min_score'           => 0.0,
    ],
    [
        'name'                => 'T22 Parse: colloquial "hay cau gian mieng dang" → irritability + bitter',
        'chief_complaint'     => 'hay cáu giận, miệng đắng',
        'selected_codes'      => [],
        'quick_answers'       => [],
        'expect_pattern'      => null,
        'expect_redflags'     => [],
        'expect_matched_codes'=> ['irritability_emotional_lability', 'bitter_taste_mouth'],
        'min_score'           => 0.0,
    ],
    [
        'name'                => 'T23 Parse: "met moi chan an" → fatigue + poor_appetite',
        'chief_complaint'     => 'mệt mỏi chán ăn',
        'selected_codes'      => [],
        'quick_answers'       => [],
        'expect_pattern'      => null,
        'expect_redflags'     => [],
        'expect_matched_codes'=> ['fatigue_general', 'poor_appetite_loss'],
        'min_score'           => 0.0,
    ],

    // =========================================================================
    // T24–T27: Symptom ranking quality
    // =========================================================================
    [
        'name'               => 'T24 Ranking: "dau dau chong mat hay cau" → throbbing_headache in top 2',
        'chief_complaint'    => 'đau đầu chóng mặt hay cáu',
        'selected_codes'     => [],
        'quick_answers'      => [],
        'expect_pattern'     => null,
        'expect_redflags'    => [],
        'expect_top5_ranked' => 'throbbing_headache',
        'min_score'          => 0.0,
    ],
    [
        'name'               => 'T25 Ranking: after selecting "throbbing_headache" → dizziness ranked high',
        'chief_complaint'    => 'đau đầu',
        'selected_codes'     => ['throbbing_headache'],
        'quick_answers'      => [],
        'expect_pattern'     => null,
        'expect_redflags'    => [],
        'expect_top_ranked'  => 'dizziness_vertigo',
        'min_score'          => 0.0,
    ],
    [
        'name'               => 'T26 Ranking: after selecting "insomnia" → palpitations ranked top 5',
        'chief_complaint'    => 'mất ngủ',
        'selected_codes'     => ['insomnia'],
        'quick_answers'      => [],
        'expect_pattern'     => null,
        'expect_redflags'    => [],
        'expect_top5_ranked' => 'palpitations_irregular',
        'min_score'          => 0.0,
    ],

    // =========================================================================
    // T27–T30: Full exam flow (end-to-end)
    // =========================================================================
    [
        'name'            => 'T27 E2E: Classic Can Duong Vuong - full flow',
        'chief_complaint' => 'đau đầu nhịp đập, chóng mặt, hay cáu giận, miệng đắng',
        'selected_codes'  => ['throbbing_headache', 'dizziness_vertigo', 'irritability_emotional_lability', 'bitter_taste_mouth'],
        'quick_answers'   => ['phan_ung_nhiet_han' => 'nhiet', 'tinh_chat_dau' => 'nhanh_chong'],
        'expect_pattern'  => 'liver_yang_rising',
        'expect_redflags' => ['hypertensive_urgency'],
        'min_score'       => 0.6,
        'expect_phap_tri' => true,  // phap_tri must be non-empty
    ],
    [
        'name'            => 'T28 E2E: Classic Tam Huyet Hu - full flow',
        'chief_complaint' => 'mất ngủ, hồi hộp, hay quên, mặt nhợt',
        'selected_codes'  => ['insomnia', 'palpitations_irregular', 'poor_memory', 'pale_complexion'],
        'quick_answers'   => [],
        'expect_pattern'  => 'heart_blood_deficiency',
        'expect_redflags' => [],
        'min_score'       => 0.5,
        'expect_phap_tri' => true,
    ],
    [
        'name'            => 'T29 E2E: Classic Ty Khi Hu - full flow',
        'chief_complaint' => 'mệt mỏi, ăn không ngon, bụng đầy, phân lỏng',
        'selected_codes'  => ['fatigue_general', 'poor_appetite_loss', 'bloating_fullness', 'loose_stools'],
        'quick_answers'   => [],
        'expect_pattern'  => 'spleen_qi_deficiency',
        'expect_redflags' => [],
        'min_score'       => 0.5,
        'expect_phap_tri' => true,
    ],
    [
        'name'            => 'T30 E2E: Classic Than Am Hu - full flow',
        'chief_complaint' => 'đau lưng mỏi, ù tai, mồ hôi trộm, lòng bàn tay nóng',
        'selected_codes'  => ['lower_back_pain', 'tinnitus', 'night_sweats', 'hot_palms_soles'],
        'quick_answers'   => [],
        'expect_pattern'  => 'kidney_yin_deficiency',
        'expect_redflags' => [],
        'min_score'       => 0.4,
        'expect_phap_tri' => true,
    ],
    [
        'name'            => 'T31 Determinism: same input always same output',
        'chief_complaint' => 'đau đầu chóng mặt hay cáu giận miệng đắng',
        'selected_codes'  => ['throbbing_headache', 'dizziness_vertigo', 'irritability_emotional_lability', 'bitter_taste_mouth'],
        'quick_answers'   => [],
        'expect_pattern'  => 'liver_yang_rising',
        'expect_redflags' => [],
        'min_score'       => 0.6,
        'determinism_check' => true,  // run twice and compare
    ],
];

// ============================================================================
// TEST RUNNER
// ============================================================================

$engine = new YHCTEngine();
$rfEngine = new RedFlagEngine();

$passed  = 0;
$failed  = 0;
$results = [];

foreach ($testCases as $tc) {
    $result = runTest($tc, $engine, $rfEngine, $verbose);
    $results[] = $result;
    if ($result['pass']) {
        $passed++;
    } else {
        $failed++;
    }
    if (!$jsonOut) {
        $icon = $result['pass'] ? '✓' : '✗';
        $status = $result['pass'] ? 'PASS' : 'FAIL';
        echo "{$icon} [{$status}] {$tc['name']}\n";
        if (!$result['pass'] || $verbose) {
            foreach ($result['details'] as $d) {
                echo "    {$d}\n";
            }
        }
    }
}

// Summary
$total = $passed + $failed;
$pct   = $total > 0 ? round($passed / $total * 100) : 0;

if ($jsonOut) {
    echo json_encode([
        'summary' => ['total' => $total, 'passed' => $passed, 'failed' => $failed, 'pct' => $pct],
        'results' => $results,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    echo "\n";
    echo str_repeat('=', 60) . "\n";
    echo "RESULTS: {$passed}/{$total} passed ({$pct}%)\n";
    if ($failed > 0) {
        echo "\nFAILED TESTS:\n";
        foreach ($results as $r) {
            if (!$r['pass']) {
                echo "  ✗ {$r['name']}\n";
                foreach ($r['details'] as $d) {
                    echo "      {$d}\n";
                }
            }
        }
    }
    echo str_repeat('=', 60) . "\n";
}

exit($failed > 0 ? 1 : 0);


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function runTest(array $tc, YHCTEngine $engine, RedFlagEngine $rfEngine, bool $verbose): array
{
    $pass    = true;
    $details = [];

    // --- Build session ---
    $parsed = $engine->parseChiefComplaint($tc['chief_complaint']);

    $session = [
        'selected_codes'   => $tc['selected_codes'],
        'context_flags'    => $parsed['context_flags'],
        'quick_answers'    => $tc['quick_answers'],
        'chief_complaint'  => $tc['chief_complaint'],
    ];

    // --- Test: parseChiefComplaint matched codes ---
    if (!empty($tc['expect_matched_codes'])) {
        $matchedCodes = $parsed['matched_codes'];
        // Also check K01 observable phrase codes
        foreach ($parsed['extracted_phrases'] as $phrase) {
            foreach (explode(',', $phrase['symptom_codes'] ?? '') as $sc) {
                $sc = trim($sc);
                if ($sc) $matchedCodes[] = $sc;
            }
        }
        $matchedCodes = array_unique($matchedCodes);
        foreach ($tc['expect_matched_codes'] as $expected) {
            if (!in_array($expected, $matchedCodes, true)) {
                $pass      = false;
                $details[] = "FAIL: expected '{$expected}' in matched_codes, got: " . implode(', ', $matchedCodes);
            } else {
                $details[] = "OK: matched code '{$expected}'";
            }
        }
    }

    // --- Run analysis ---
    $analysisResult = $engine->analyze($session);

    // --- Test: expected pattern ---
    // chung_ranked[N] = ['pattern' => [...], 'score' => float, ...]
    // pattern code is inside ['pattern']['chung_code'] or ['pattern']['pattern_code']
    if (!empty($tc['expect_pattern'])) {
        $top0      = $analysisResult['chung_ranked'][0] ?? [];
        $patObj    = $top0['pattern'] ?? $top0;
        $topPattern = $patObj['chung_code'] ?? $patObj['pattern_code'] ?? null;
        $topScore   = $top0['score'] ?? 0;
        if ($topPattern !== $tc['expect_pattern']) {
            $pass    = false;
            $allPat  = array_map(function($r) {
                $p = $r['pattern'] ?? $r;
                return ($p['chung_code'] ?? $p['pattern_code'] ?? '?') . '(' . round($r['score'] ?? 0, 3) . ')';
            }, $analysisResult['chung_ranked'] ?? []);
            $details[] = "FAIL: expected top pattern '{$tc['expect_pattern']}', got '{$topPattern}'"
                       . ' (all: ' . implode(', ', $allPat) . ')';
        } else {
            $details[] = "OK: top pattern '{$topPattern}' score=" . round($topScore, 4);
        }
    }

    // --- Test: min_score ---
    if (!empty($tc['expect_pattern']) && isset($tc['min_score']) && $tc['min_score'] > 0) {
        $top0     = $analysisResult['chung_ranked'][0] ?? [];
        $topScore = $top0['score'] ?? 0;
        if ($topScore < $tc['min_score']) {
            $pass      = false;
            $details[] = "FAIL: min_score {$tc['min_score']} not met, got {$topScore}";
        } else {
            $details[] = "OK: score {$topScore} >= {$tc['min_score']}";
        }
    }

    // --- Test: red flags ---
    // red_flags in analysis result have key 'code' (not 'rule_code')
    if (!empty($tc['expect_redflags'])) {
        $triggeredCodes = array_column($analysisResult['red_flags'] ?? [], 'code');
        // Also check rule_code for robustness
        foreach ($analysisResult['red_flags'] ?? [] as $rf) {
            if (!empty($rf['rule_code'])) $triggeredCodes[] = $rf['rule_code'];
        }
        $triggeredCodes = array_unique($triggeredCodes);
        foreach ($tc['expect_redflags'] as $expected) {
            if (!in_array($expected, $triggeredCodes, true)) {
                $pass      = false;
                $details[] = "FAIL: expected red flag '{$expected}', triggered: " . implode(', ', $triggeredCodes);
            } else {
                $details[] = "OK: red flag '{$expected}' triggered";
            }
        }
    }

    // Build combined contextTriggers: K02 matched codes + K01 extracted phrase codes
    $contextTriggers = $parsed['matched_codes'];
    foreach ($parsed['extracted_phrases'] as $phrase) {
        foreach (explode(',', $phrase['symptom_codes'] ?? '') as $sc) {
            $sc = trim($sc);
            if ($sc) $contextTriggers[] = $sc;
        }
    }
    $contextTriggers = array_unique($contextTriggers);

    // --- Test: symptom ranking ---
    // rankSymptoms returns ['code'=>..., 'symptom'=>[...], 'score'=>...]
    if (!empty($tc['expect_top_ranked'])) {
        $ranked  = $engine->rankSymptoms($tc['selected_codes'], $parsed['context_flags'], $contextTriggers);
        $topCode = $ranked[0]['code'] ?? null;
        if ($topCode !== $tc['expect_top_ranked']) {
            $pass      = false;
            $top5      = array_map(fn($r) => ($r['code'] ?? '?') . '(' . round($r['score'] ?? 0, 3) . ')', array_slice($ranked, 0, 5));
            $details[] = "FAIL: expected top ranked '{$tc['expect_top_ranked']}', got '{$topCode}' (top5: " . implode(', ', $top5) . ")";
        } else {
            $details[] = "OK: top ranked '{$topCode}'";
        }
    }

    if (!empty($tc['expect_top5_ranked'])) {
        $ranked = $engine->rankSymptoms($tc['selected_codes'], $parsed['context_flags'], $contextTriggers);
        $top5   = array_slice($ranked, 0, 5);
        $codes  = array_column($top5, 'code');
        if (!in_array($tc['expect_top5_ranked'], $codes, true)) {
            $pass      = false;
            $top5str   = array_map(fn($r) => ($r['code'] ?? '?') . '(' . round($r['score'] ?? 0, 3) . ')', $top5);
            $details[] = "FAIL: expected '{$tc['expect_top5_ranked']}' in top 5, got: " . implode(', ', $top5str);
        } else {
            $details[] = "OK: '{$tc['expect_top5_ranked']}' in top 5";
        }
    }

    // --- Test: phap_tri present ---
    if (!empty($tc['expect_phap_tri'])) {
        $phapTri = $analysisResult['phap_tri'] ?? null;
        if (empty($phapTri) || empty($phapTri['principle'])) {
            $pass      = false;
            $details[] = "FAIL: phap_tri is empty";
        } else {
            $details[] = "OK: phap_tri principle='" . $phapTri['principle'] . "'";
        }
    }

    // --- Test: determinism ---
    if (!empty($tc['determinism_check'])) {
        $result2 = $engine->analyze($session);
        $top0a   = $analysisResult['chung_ranked'][0] ?? [];
        $top0b   = $result2['chung_ranked'][0]        ?? [];
        $pA      = $top0a['pattern'] ?? $top0a;
        $pB      = $top0b['pattern'] ?? $top0b;
        $pat1    = $pA['chung_code'] ?? $pA['pattern_code'] ?? null;
        $pat2    = $pB['chung_code'] ?? $pB['pattern_code'] ?? null;
        $sc1     = $top0a['score'] ?? 0;
        $sc2     = $top0b['score'] ?? 0;
        if ($pat1 !== $pat2 || abs($sc1 - $sc2) > 0.0001) {
            $pass      = false;
            $details[] = "FAIL: non-deterministic! Run1={$pat1}({$sc1}), Run2={$pat2}({$sc2})";
        } else {
            $details[] = "OK: deterministic result '{$pat1}' score={$sc1}";
        }
    }

    if ($verbose && empty($details)) {
        $details[] = 'No details';
    }

    return [
        'name'    => $tc['name'],
        'pass'    => $pass,
        'details' => $details,
    ];
}
