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
$session = [
    'selected_codes' => ['throbbing_headache', 'dizziness_vertigo', 'irritability_emotional_lability', 'bitter_taste_mouth'],
    'context_flags'  => [],
    'quick_answers'  => [],
];
$result = $engine->analyze($session);

echo "chung_ranked count: " . count($result['chung_ranked'] ?? []) . "\n";
if (!empty($result['chung_ranked'])) {
    $top = $result['chung_ranked'][0];
    echo "Top keys: " . implode(', ', array_keys($top)) . "\n";
    echo "Top values:\n";
    foreach ($top as $k => $v) {
        if (!is_array($v)) echo "  $k = $v\n";
    }
}

echo "\n--- Red flags in result ---\n";
echo "red_flags count: " . count($result['red_flags'] ?? []) . "\n";
foreach ($result['red_flags'] ?? [] as $rf) {
    echo "  keys: " . implode(', ', array_keys($rf)) . "\n";
    foreach ($rf as $k => $v) {
        if (!is_array($v)) echo "    $k = $v\n";
    }
    break;
}

echo "\n--- Red flags in DB ---\n";
$db = Database::get();
$rows = $db->query("SELECT rule_code, level FROM kb_red_flags")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) echo "  " . $r['rule_code'] . " | " . $r['level'] . "\n";

echo "\n--- Ranking sample ---\n";
$ranked = $engine->rankSymptoms([], [], []);
echo "Ranked count: " . count($ranked) . "\n";
if (!empty($ranked)) {
    $top = $ranked[0];
    echo "Top keys: " . implode(', ', array_keys($top)) . "\n";
    echo "  code=" . ($top['code'] ?? $top['symptom_code'] ?? '?') . " score=" . ($top['score'] ?? '?') . "\n";
}
