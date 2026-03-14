<?php
/**
 * Migration: Add paraclinical support
 *
 * 1. exam_sessions: add paraclinical_results column
 * 2. kb_patterns: add paraclinical_hints column
 */
define('APP_ROOT', __DIR__ . '/../app');
define('DB_PATH',  APP_ROOT . '/storage/kham.db');
$db = new PDO('sqlite:' . DB_PATH);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("PRAGMA journal_mode=WAL; PRAGMA foreign_keys=OFF;");

// --- exam_sessions ---
$cols = array_column($db->query("PRAGMA table_info(exam_sessions)")->fetchAll(PDO::FETCH_ASSOC), 'name');
if (!in_array('paraclinical_results', $cols)) {
    $db->exec("ALTER TABLE exam_sessions ADD COLUMN paraclinical_results TEXT DEFAULT NULL");
    echo "✓ exam_sessions.paraclinical_results added\n";
} else {
    echo "- exam_sessions.paraclinical_results already exists\n";
}

// --- kb_patterns ---
$cols2 = array_column($db->query("PRAGMA table_info(kb_patterns)")->fetchAll(PDO::FETCH_ASSOC), 'name');
if (!in_array('paraclinical_hints', $cols2)) {
    $db->exec("ALTER TABLE kb_patterns ADD COLUMN paraclinical_hints TEXT DEFAULT NULL");
    echo "✓ kb_patterns.paraclinical_hints added\n";
} else {
    echo "- kb_patterns.paraclinical_hints already exists\n";
}

echo "\nDone. exam_sessions now has: paraclinical_results\n";
echo "kb_patterns now has: paraclinical_hints\n";
