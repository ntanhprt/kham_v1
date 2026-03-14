<?php
define('APP_ROOT', __DIR__ . '/../app');
require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/core/Database.php';
$db = Database::get();

echo "=== ALL TABLES ===\n";
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
foreach($tables as $t) {
    $cnt = $db->query("SELECT COUNT(*) FROM \"$t\"")->fetchColumn();
    echo "  $t: $cnt rows\n";
}

echo "\n=== Embedding-related tables ===\n";
foreach($tables as $t) {
    if(stripos($t,'embed') !== false || stripos($t,'vector') !== false || stripos($t,'doc') !== false) {
        echo "\n[$t] columns:\n";
        $cols = $db->query("PRAGMA table_info(\"$t\")")->fetchAll(PDO::FETCH_ASSOC);
        foreach($cols as $c) echo "  ".$c['name']." (".$c['type'].")\n";
    }
}
