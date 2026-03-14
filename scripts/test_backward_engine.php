<?php
define('APP_ROOT', __DIR__ . '/../app');
define('DB_PATH',  APP_ROOT . '/storage/kham.db');
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/engine/BackwardReasoningEngine.php';

$e = new BackwardReasoningEngine();
echo "hasData: " . ($e->hasData() ? 'yes' : 'no') . PHP_EOL;

// Test 1: elderly patient with fatigue, lower back pain, frequent urination
$r = $e->reason(
    ['fatigue_general', 'lower_back_pain', 'frequent_urination_night', 'edema_lower_limbs'],
    ['is_elderly' => true]
);

echo "\nTest 1 (elderly, fatigue, lower_back_pain, frequent_urination, edema):\n";
echo "Underlying (" . count($r['underlying']) . "):\n";
foreach ($r['underlying'] as $u) {
    echo "  [{$u['confidence']}] {$u['disease_code']} — {$u['name_vi']} (score={$u['score']})\n";
}
echo "Complications (" . count($r['complications']) . "):\n";
foreach ($r['complications'] as $c) {
    echo "  {$c['from_name_vi']} → {$c['name_vi']} ({$c['relation']})\n";
}
echo "Probing questions:\n";
foreach ($r['probing_questions'] as $q) {
    echo "  - $q\n";
}

// Test 2: classic stroke presentation
$r2 = $e->reason(
    ['facial_droop_weakness', 'severe_headache_sudden', 'dizziness_vertigo'],
    []
);
echo "\nTest 2 (stroke symptoms):\n";
foreach ($r2['underlying'] as $u) {
    echo "  [{$u['confidence']}] {$u['disease_code']} — {$u['name_vi']}\n";
}

// Test 3: anxiety/depression
$r3 = $e->reason(
    ['anxiety_worry', 'insomnia', 'palpitations_irregular', 'depression_mood'],
    []
);
echo "\nTest 3 (anxiety/depression symptoms):\n";
foreach ($r3['underlying'] as $u) {
    echo "  [{$u['confidence']}] {$u['disease_code']} — {$u['name_vi']}\n";
}
