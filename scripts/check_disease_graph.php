<?php
define('APP_ROOT', __DIR__ . '/../app');
define('DB_PATH',  APP_ROOT . '/storage/kham.db');
require_once APP_ROOT . '/core/Database.php';

$db = Database::get();

$counts = [
    'disease_nodes'      => $db->query('SELECT COUNT(*) FROM disease_nodes')->fetchColumn(),
    'disease_edges'      => $db->query('SELECT COUNT(*) FROM disease_edges')->fetchColumn(),
    'symptom_disease_map'=> $db->query('SELECT COUNT(*) FROM symptom_disease_map')->fetchColumn(),
];

foreach ($counts as $table => $count) {
    echo "$table: $count rows\n";
}

if ($counts['disease_nodes'] > 0) {
    echo "\nSample disease_nodes:\n";
    foreach ($db->query("SELECT disease_code, name_vi, category, prevalence_vn FROM disease_nodes LIMIT 5")->fetchAll(PDO::FETCH_ASSOC) as $r) {
        echo "  {$r['disease_code']} | {$r['name_vi']} | {$r['category']} | {$r['prevalence_vn']}\n";
    }
}

if ($counts['disease_edges'] > 0) {
    echo "\nSample disease_edges:\n";
    foreach ($db->query("SELECT source_disease, relation_type, target_disease, strength FROM disease_edges LIMIT 5")->fetchAll(PDO::FETCH_ASSOC) as $r) {
        echo "  {$r['source_disease']} --[{$r['relation_type']}({$r['strength']})]-> {$r['target_disease']}\n";
    }
}

if ($counts['symptom_disease_map'] > 0) {
    echo "\nSample symptom_disease_map:\n";
    foreach ($db->query("SELECT symptom_code, disease_code, specificity, sensitivity FROM symptom_disease_map LIMIT 5")->fetchAll(PDO::FETCH_ASSOC) as $r) {
        echo "  {$r['symptom_code']} → {$r['disease_code']} (spec={$r['specificity']}, sens={$r['sensitivity']})\n";
    }
}
