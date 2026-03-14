<?php
$db = new SQLite3(__DIR__ . '/../app/storage/kham.db');

echo "=== Symptoms with ngua/itch/skin ===\n";
$r = $db->query("SELECT symptom_code, name_vi, name_en FROM kb_symptoms WHERE name_vi LIKE '%ngứa%' OR name_en LIKE '%itch%' OR name_en LIKE '%pruritus%' OR name_en LIKE '%skin%' LIMIT 30");
while($row = $r->fetchArray(SQLITE3_ASSOC)) echo $row['symptom_code'].' | '.$row['name_vi'].' | '.$row['name_en']."\n";

echo "\n=== Aliases with ngứa/chân ===\n";
$r = $db->query("SELECT symptom_code, alias FROM symptom_aliases WHERE alias LIKE '%ngứa%' OR alias LIKE '%chân%' LIMIT 20");
while($row = $r->fetchArray(SQLITE3_ASSOC)) echo $row['symptom_code'].' | '.$row['alias']."\n";

echo "\n=== Total symptoms: ".$db->querySingle("SELECT COUNT(*) FROM kb_symptoms")."\n";

echo "\n=== Categories of symptom codes ===\n";
$r = $db->query("SELECT SUBSTR(symptom_code,1,3) as prefix, COUNT(*) as cnt FROM kb_symptoms GROUP BY prefix ORDER BY prefix");
while($row = $r->fetchArray(SQLITE3_ASSOC)) echo $row['prefix'].' => '.$row['cnt']."\n";

echo "\n=== Sample S06 batch symptoms ===\n";
$r = $db->query("SELECT symptom_code, name_vi, name_en FROM kb_symptoms WHERE symptom_code LIKE 'S06%' LIMIT 20");
while($row = $r->fetchArray(SQLITE3_ASSOC)) echo $row['symptom_code'].' | '.$row['name_vi'].' | '.$row['name_en']."\n";

echo "\n=== Patterns covering skin/dermatology ===\n";
$r = $db->query("SELECT pattern_code, name_vi, organ_system FROM kb_patterns WHERE organ_system LIKE '%skin%' OR organ_system LIKE '%da%' OR name_vi LIKE '%da%' LIMIT 20");
while($row = $r->fetchArray(SQLITE3_ASSOC)) echo $row['pattern_code'].' | '.$row['name_vi'].' | '.$row['organ_system']."\n";
