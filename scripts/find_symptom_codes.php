<?php
define('APP_ROOT', __DIR__ . '/../app');
require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/core/Database.php';
$db = Database::get();

$keywords = ['fatigue','poor_appetite','loose_stool','abdominal_dist','thirst','hot_flush',
             'dark.*urine','fever','night_sweat','irritab','sticky_stool'];
foreach ($keywords as $kw) {
    $r = $db->query("SELECT symptom_code, name_vi FROM kb_symptoms WHERE symptom_code LIKE '%" . str_replace('.*','%',$kw) . "%' LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        foreach($r as $row) echo $row['symptom_code'] . " | " . $row['name_vi'] . "\n";
    }
}
