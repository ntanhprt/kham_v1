<?php
define('APP_ROOT', __DIR__ . '/../app');
require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/core/Database.php';
$db = Database::get();

// Check bat_cuong_weights for itch symptoms
$codes = ['generalized_pruritus_no_rash','skin_eczema_or_itching','dry_itchy_skin_or_eczema_chronic',
          'skin_eczema_weeping','skin_rash_maculopapular','vulvar_pruritus'];
echo "=== bat_cuong_weights for itch symptoms ===\n";
foreach ($codes as $c) {
    $r = $db->query("SELECT symptom_code, bat_cuong_weights FROM kb_symptoms WHERE symptom_code = '$c'")->fetch(PDO::FETCH_ASSOC);
    if ($r) echo $r['symptom_code'] . ": " . ($r['bat_cuong_weights'] ?? 'NULL') . "\n";
}

// Check how many total symptoms have bat_cuong_weights
$cnt = $db->query("SELECT COUNT(*) FROM kb_symptoms WHERE bat_cuong_weights IS NOT NULL AND bat_cuong_weights != '{}' AND bat_cuong_weights != ''")->fetchColumn();
$tot = $db->query("SELECT COUNT(*) FROM kb_symptoms")->fetchColumn();
echo "\n=== Symptoms WITH bat_cuong_weights: $cnt / $tot ===\n";

// Sample some that DO have weights
$r = $db->query("SELECT symptom_code, bat_cuong_weights FROM kb_symptoms WHERE bat_cuong_weights IS NOT NULL AND bat_cuong_weights NOT IN ('{}','') LIMIT 5");
foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['symptom_code'] . ": " . $row['bat_cuong_weights'] . "\n";
}

// Check what patterns exist with skin-related required symptoms
echo "\n=== Patterns that use any skin/itch symptoms ===\n";
$r = $db->query("SELECT chung_code, name_vi, required_symptoms, two_or_more_of, supporting_symptoms FROM kb_patterns");
$skinCodes = ['generalized_pruritus','pruritus','skin_eczema','dry_itchy','vulvar_pruritus','skin_rash',
              'bleeding_tendency','skin_petechiae','skin_nodules','skin_scaling','dry_skin','skin_darkening'];
$found = 0;
foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $pat) {
    $allSyms = array_merge(
        json_decode($pat['required_symptoms'] ?? '[]', true) ?: [],
        json_decode($pat['two_or_more_of'] ?? '[]', true) ?: [],
        json_decode($pat['supporting_symptoms'] ?? '[]', true) ?: []
    );
    foreach ($skinCodes as $sc) {
        foreach ($allSyms as $s) {
            if (str_contains($s, 'skin') || str_contains($s, 'itch') || str_contains($s, 'pruritus') || str_contains($s, 'rash') || str_contains($s, 'eczema')) {
                echo "  " . $pat['chung_code'] . " | " . $pat['name_vi'] . "\n";
                $found++;
                break 2;
            }
        }
    }
}
echo "Total patterns with skin symptoms: $found\n";
