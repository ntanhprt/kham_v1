<?php
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/core/Database.php';
$db = Database::get();
$rows = $db->query('SELECT symptom_code, name_vi, organ_system FROM kb_symptoms ORDER BY organ_system, symptom_code')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['organ_system'] . ' | ' . $r['symptom_code'] . ' | ' . $r['name_vi'] . "\n";
}
echo 'Total: ' . count($rows) . "\n";
