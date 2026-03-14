<?php
/**
 * Master import script — runs all K01–K08 importers
 * Run from project root:
 *   C:\xampp\php\php.exe scripts/import_all.php
 *
 * Options:
 *   --fresh   Drop and recreate all KB tables before import
 *   --only=k01,k02   Import only specified jobs (comma-separated)
 */

$opts   = getopt('', ['fresh', 'only::']);
$fresh  = isset($opts['fresh']);
$only   = isset($opts['only']) ? explode(',', strtolower($opts['only'])) : null;

$root   = dirname(__DIR__);
$dbPath = $root . '/app/storage/kham.db';
$dataDir= $root . '/data';

// ── Ensure DB exists ─────────────────────────────────────────────────────────
if (!file_exists($dbPath)) {
    echo "Database not found. Run: php scripts/create_schema.php first.\n";
    exit(1);
}

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA journal_mode=WAL');
$pdo->exec('PRAGMA foreign_keys=OFF');

// ── Optional: wipe KB tables before re-import ─────────────────────────────────
if ($fresh) {
    $kbTables = ['kb_observable_phrases','kb_symptoms','kb_pathogenesis_rules',
                 'kb_patterns','kb_red_flags','kb_clusters','kb_herb_drug','kb_kiem_chung',
                 'symptom_aliases'];
    foreach ($kbTables as $t) {
        $pdo->exec("DELETE FROM $t");
    }
    echo "[fresh] Cleared all KB tables.\n";
}

// ── Load importers ────────────────────────────────────────────────────────────
$jobs = [
    'k01' => 'ImportK01',
    'k02' => 'ImportK02',
    'k03' => 'ImportK03',
    'k04' => 'ImportK04',
    'k05' => 'ImportK05',
    'k06' => 'ImportK06',
    'k07' => 'ImportK07',
    'k08' => 'ImportK08',
];

foreach ($jobs as $key => $class) {
    if ($only && !in_array($key, $only)) continue;

    require_once __DIR__ . "/import/import_{$key}.php";
    echo "\n=== {$class} ===\n";
    $pdo->beginTransaction();
    try {
        $importer = new $class($pdo, $dataDir);
        $importer->import();
        $pdo->commit();
        $importer->report();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "[FATAL] {$class}: {$e->getMessage()}\n";
    }
}

echo "\n=== DONE ===\n";

// Print counts
$tables = [
    'kb_observable_phrases' => 'K01 Phrases',
    'kb_symptoms'           => 'K02 Symptoms',
    'kb_pathogenesis_rules' => 'K03 Pathogenesis',
    'kb_patterns'           => 'K04 Patterns',
    'kb_red_flags'          => 'K05 Red Flags',
    'kb_clusters'           => 'K06 Clusters',
    'kb_herb_drug'          => 'K07 Herb-Drug',
    'kb_kiem_chung'         => 'K08 Kiêm Chứng',
];
foreach ($tables as $tbl => $label) {
    $n = $pdo->query("SELECT COUNT(*) FROM $tbl")->fetchColumn();
    printf("  %-22s %4d records\n", $label, $n);
}
