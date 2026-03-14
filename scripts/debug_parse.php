<?php
define('APP_ROOT', __DIR__ . '/../app');
require APP_ROOT . '/config.php';
require APP_ROOT . '/core/Database.php';
require APP_ROOT . '/engine/YHCTEngine.php';
require APP_ROOT . '/engine/RedFlagEngine.php';
require APP_ROOT . '/engine/ClusterEngine.php';
require APP_ROOT . '/engine/SafetyFilter.php';
require APP_ROOT . '/engine/HypothesisEngine.php';

$engine = new YHCTEngine();
$input = 'đau đầu chóng mặt hay cáu';
$parsed = $engine->parseChiefComplaint($input);
echo "Input: $input\n";
echo "tokens: " . implode(', ', $parsed['tokens']) . "\n";
echo "matched_codes (K02): " . implode(', ', $parsed['matched_codes']) . "\n";
echo "extracted_phrases (K01): " . count($parsed['extracted_phrases']) . " phrases\n";
foreach ($parsed['extracted_phrases'] as $p) {
    echo "  " . $p['code'] . " → codes: " . $p['symptom_codes'] . "\n";
}

// Build contextTriggers the right way - K02 + K01 codes
$contextTriggers = $parsed['matched_codes'];
foreach ($parsed['extracted_phrases'] as $phrase) {
    foreach (explode(',', $phrase['symptom_codes'] ?? '') as $sc) {
        $sc = trim($sc);
        if ($sc) $contextTriggers[] = $sc;
    }
}
$contextTriggers = array_unique($contextTriggers);
echo "contextTriggers (combined): " . implode(', ', $contextTriggers) . "\n";

// Rank with proper contextTriggers
$ranked = $engine->rankSymptoms([], $parsed['context_flags'], $contextTriggers);
echo "\nTop 5 ranked:\n";
foreach (array_slice($ranked, 0, 5) as $r) {
    echo "  " . $r['code'] . " score=" . $r['score'] . " (anchor=" . $r['scores']['anchor'] . ")\n";
}
