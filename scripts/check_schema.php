<?php
$db = new PDO('sqlite:' . __DIR__ . '/../app/storage/kham.db');
$sql = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='exam_sessions'")->fetchColumn();
echo "=== exam_sessions DDL ===\n" . $sql . "\n";
