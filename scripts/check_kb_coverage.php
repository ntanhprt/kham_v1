<?php
$db = new SQLite3(__DIR__ . '/../app/storage/kham.db');

echo "=== KB PATTERNS (chứng/thể bệnh YHCT) ===\n";
$r = $db->query("SELECT SUBSTR(pattern_id,1,10) as pid, name_vi, organ_system FROM kb_patterns ORDER BY organ_system, pattern_id LIMIT 200");
$byOrgan = [];
while($row = $r->fetchArray(SQLITE3_ASSOC)) {
    $org = $row['organ_system'] ?: 'other';
    $byOrgan[$org][] = $row['name_vi'];
}
foreach($byOrgan as $org => $names) {
    echo "\n[$org] (".count($names)." chứng)\n";
    foreach($names as $n) echo "  - $n\n";
}

echo "\n\n=== YHHD CORRELATES (bệnh Tây Y trong KB) ===\n";
$r = $db->query("SELECT DISTINCT value FROM (SELECT json_each.value FROM kb_patterns, json_each(kb_patterns.yhhd_correlates) WHERE kb_patterns.yhhd_correlates != '[]' AND kb_patterns.yhhd_correlates IS NOT NULL) ORDER BY value");
$diseases = [];
while($row = $r->fetchArray(SQLITE3_ASSOC)) $diseases[] = $row['value'];
echo implode(', ', $diseases) . "\n";
echo "\nTotal: " . count($diseases) . " bệnh Tây Y\n";
