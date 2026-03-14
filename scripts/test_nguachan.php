<?php
define('APP_ROOT', __DIR__ . '/../app');
require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/engine/YHCTEngine.php';

$engine = new YHCTEngine();
$parsed = $engine->parseChiefComplaint('ngứa chân');

echo "Tokens: " . implode(', ', $parsed['tokens']) . "\n";
echo "Matched codes: " . implode(', ', $parsed['matched_codes']) . "\n";
echo "Count: " . count($parsed['matched_codes']) . "\n";

if (!empty($parsed['matched_codes'])) {
    echo "\nSample matched symptoms:\n";
    $ranked = $engine->rankSymptoms([], $parsed['context_flags'], $parsed['matched_codes']);
    $shown = array_filter($ranked, fn($r) => $r['score'] >= 0.25);
    foreach (array_slice($shown, 0, 15) as $r) {
        echo sprintf("  [%.2f] %s | %s\n", $r['score'], $r['code'], $r['symptom']['name_vi'] ?? '');
    }
    echo "\nTotal shown (score>=0.25): " . count($shown) . "\n";
}
