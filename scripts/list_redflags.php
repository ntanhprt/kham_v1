<?php
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/core/Database.php';
$db = Database::get();
$rows = $db->query("SELECT rule_code, level, trigger_symptoms, name_vi FROM kb_red_flags ORDER BY level")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $triggers = json_decode($r['trigger_symptoms'] ?? '[]', true) ?: [];
    echo $r['rule_code'] . ' | ' . $r['level'] . ' | triggers: ' . implode(', ', $triggers) . "\n";
}
