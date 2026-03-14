<?php
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/core/Database.php';
$db = Database::get();
$rows = $db->query("SELECT chung_code, name_vi, organ_system FROM kb_patterns WHERE status='active' ORDER BY organ_system, chung_code")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['organ_system'] . ' | ' . $r['chung_code'] . ' | ' . $r['name_vi'] . "\n";
}
echo "Total: " . count($rows) . "\n";
