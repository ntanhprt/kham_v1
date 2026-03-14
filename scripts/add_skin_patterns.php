<?php
/**
 * Add K04 skin/dermatology patterns to kb_patterns
 * These cover common skin conditions seen in YHCT practice.
 */
define('APP_ROOT', __DIR__ . '/../app');
define('DB_PATH',  APP_ROOT . '/storage/kham.db');
require_once APP_ROOT . '/core/Database.php';

$db = Database::get();
$db->exec("PRAGMA journal_mode=WAL");

// Verify which optional symptom codes actually exist
$checkCodes = [
    'dry_hair_skin_nails','dry_skin','dry_scaly_skin','dry_itchy_skin_or_eczema_chronic',
    'skin_eczema_or_itching','skin_eczema_weeping','skin_rash_maculopapular',
    'generalized_pruritus_no_rash','skin_scaling','skin_darkening','skin_dryness',
    'pale_dull_complexion_or_pale_lips_nails','post_exertion_fatigue','poor_appetite',
    'loose_stool_or_diarrhea','abdominal_distension','severe_thirst_polyuria',
    'fatigue_with_hot_flushes','constipation_dry_hard_stool','dark_yellow_urine',
    'low_grade_persistent_fever','afternoon_low_grade_fever','high_fever_continuous',
    'night_sweats','dry_eyes_or_blurred_vision','blurred_vision_or_dry_eyes_or_floaters',
    'red_face_and_eyes','irritability_emotional_lability','yellow_greasy_tongue_coating',
    'itchy_throat_triggering_cough','pruritus','skin_nodules','skin_petechiae_or_ecchymosis',
];
$existingCodes = [];
foreach ($checkCodes as $c) {
    $r = $db->query("SELECT symptom_code FROM kb_symptoms WHERE symptom_code = " . $db->quote($c))->fetchColumn();
    if ($r) $existingCodes[] = $c;
}
echo "Existing symptom codes (" . count($existingCodes) . "/" . count($checkCodes) . "):\n";
foreach ($existingCodes as $c) echo "  ✓ $c\n";

$missing = array_diff($checkCodes, $existingCodes);
echo "\nMissing codes (" . count($missing) . "):\n";
foreach ($missing as $c) echo "  ✗ $c\n";

function insertPattern(PDO $db, array $p): void {
    static $stmt = null;
    if (!$stmt) {
        $stmt = $db->prepare("
            INSERT OR REPLACE INTO kb_patterns
            (chung_code, name_vi, name_en, organ_system,
             required_symptoms, two_or_more_of, supporting_symptoms,
             differentiating_symptoms_positive, differentiating_symptoms_negative,
             phap_tri_vi, phuong_thuoc, huyet_vi,
             clinical_note, yhhd_correlates, key_questions,
             prevalence_vn, status)
            VALUES
            (:chung_code, :name_vi, :name_en, :organ_system,
             :required, :two_or_more, :supporting,
             :diff_pos, :diff_neg,
             :phap_tri_vi, :phuong_thuoc, :huyet_vi,
             :clinical_note, :yhhd, :key_questions,
             :prevalence, 'active')
        ");
    }
    $stmt->execute([
        ':chung_code'   => $p['code'],
        ':name_vi'      => $p['name_vi'],
        ':name_en'      => $p['name_en'],
        ':organ_system' => $p['organ'],
        ':required'     => json_encode($p['required'],      JSON_UNESCAPED_UNICODE),
        ':two_or_more'  => json_encode($p['two_or_more'] ?? [], JSON_UNESCAPED_UNICODE),
        ':supporting'   => json_encode($p['supporting'] ?? [],  JSON_UNESCAPED_UNICODE),
        ':diff_pos'     => json_encode($p['diff_pos'] ?? [],    JSON_UNESCAPED_UNICODE),
        ':diff_neg'     => json_encode($p['diff_neg'] ?? [],    JSON_UNESCAPED_UNICODE),
        ':phap_tri_vi'  => $p['phap_tri_vi'],
        ':phuong_thuoc' => json_encode($p['phuong_thuoc'] ?? [], JSON_UNESCAPED_UNICODE),
        ':huyet_vi'     => json_encode($p['huyet_vi'] ?? [],    JSON_UNESCAPED_UNICODE),
        ':clinical_note'=> $p['clinical_note'] ?? '',
        ':yhhd'         => json_encode($p['yhhd'] ?? [],        JSON_UNESCAPED_UNICODE),
        ':key_questions'=> json_encode($p['key_questions'] ?? [], JSON_UNESCAPED_UNICODE),
        ':prevalence'   => $p['prevalence'] ?? 'moderate',
    ]);
    echo "Inserted: " . $p['code'] . " — " . $p['name_vi'] . "\n";
}

// ──────────────────────────────────────────────────────────────────────────────
// SKIN PATTERNS
// ──────────────────────────────────────────────────────────────────────────────

$patterns = [

    // ── 1. Huyết nhiệt sinh phong ─────────────────────────────────────────────
    [
        'code'    => 'blood_heat_wind_itch',
        'name_vi' => 'Huyết nhiệt sinh phong (Ngứa do huyết nhiệt)',
        'name_en' => 'Blood-Heat generating Wind — Pruritus',
        'organ'   => 'heart',
        'required'=> ['generalized_pruritus_no_rash'],
        'two_or_more' => [
            'skin_rash_maculopapular',
            'fatigue_with_hot_flushes',
            'red_face_and_eyes',
            'irritability_emotional_lability',
            'dark_yellow_urine',
        ],
        'supporting' => [
            'constipation_dry_hard_stool',
            'severe_thirst_polyuria',
            'skin_darkening',
        ],
        'diff_pos' => ['skin_rash_maculopapular','fatigue_with_hot_flushes'],
        'diff_neg' => ['dry_itchy_skin_or_eczema_chronic','pale_dull_complexion_or_pale_lips_nails'],
        'phap_tri_vi'  => 'Lương huyết giải độc, khu phong chỉ dưỡng (Thanh nhiệt lương huyết, trừ phong giảm ngứa)',
        'phuong_thuoc' => ['Tiêu phong tán (消風散)', 'Tứ vật thang gia giảm — thêm Bạch tiên bì, Địa phu tử', 'Lương huyết giải độc thang'],
        'huyet_vi'     => ['Huyết hải (SP10)', 'Khúc trì (LI11)', 'Hợp cốc (LI4)', 'Phong trì (GB20)', 'Túc tam lý (ST36)', 'Tam âm giao (SP6)'],
        'clinical_note'=> 'Ngứa dữ dội, tăng về đêm hoặc khi nóng. Lưỡi đỏ hoặc hồng đậm, rêu vàng mỏng. Mạch huyền sác. Gặp trong mày đay cấp, dị ứng thuốc, ngứa toàn thân do nhiệt độc.',
        'key_questions'=> ['tongue' => 'Lưỡi đỏ hoặc hồng đậm, rêu vàng mỏng', 'pulse' => 'Mạch huyền sác (cung căng và nhanh)'],
        'yhhd'   => ['Urticaria (acute)', 'Drug hypersensitivity reaction', 'Prurigo', 'Psoriasis (hot type)', 'Pityriasis rosea'],
        'prevalence' => 'high',
    ],

    // ── 2. Huyết hư phong táo ─────────────────────────────────────────────────
    [
        'code'    => 'blood_deficiency_wind_dryness_itch',
        'name_vi' => 'Huyết hư phong táo (Ngứa do huyết hư sinh phong)',
        'name_en' => 'Blood Deficiency with Wind-Dryness — Chronic Pruritus',
        'organ'   => 'liver',
        'required'=> ['dry_itchy_skin_or_eczema_chronic'],
        'two_or_more' => [
            'pale_dull_complexion_or_pale_lips_nails',
            'dry_hair_skin_nails',
            'post_exertion_fatigue',
            'night_sweats',
            'blurred_vision_or_dry_eyes_or_floaters',
        ],
        'supporting' => [
            'skin_scaling',
            'skin_dryness',
            'dry_skin',
            'dry_scaly_skin',
        ],
        'diff_pos' => ['dry_hair_skin_nails','pale_dull_complexion_or_pale_lips_nails'],
        'diff_neg' => ['skin_eczema_weeping','high_fever_continuous','skin_rash_maculopapular'],
        'phap_tri_vi'  => 'Dưỡng huyết nhuận táo, khu phong chỉ dưỡng (Bổ huyết nhuận da, trừ phong giảm ngứa)',
        'phuong_thuoc' => ['Đương quy ẩm tử (當歸飲子)', 'Tứ vật thang gia Thủ ô, Bạch tiên bì, Phòng phong', 'Nhị địa thang'],
        'huyet_vi'     => ['Huyết hải (SP10)', 'Tam âm giao (SP6)', 'Túc tam lý (ST36)', 'Phong trì (GB20)', 'Tỳ du (BL20)', 'Can du (BL18)'],
        'clinical_note'=> 'Ngứa mạn tính, da khô bong vảy, tăng về đêm hoặc mùa đông. Không có mẩn đỏ rõ ràng. Lưỡi nhạt hoặc hồng nhạt, rêu ít. Mạch tế (nhỏ). Thường gặp ở người cao tuổi, sau sinh, thiếu máu.',
        'key_questions'=> ['tongue' => 'Lưỡi nhạt hoặc hồng nhạt, rêu mỏng trắng hoặc ít rêu', 'pulse' => 'Mạch tế (nhỏ) hoặc tế sác'],
        'yhhd'   => ['Atopic dermatitis (chronic)', 'Senile pruritus', 'Ichthyosis', 'Xerosis cutis', 'Lichen simplex chronicus', 'Iron deficiency anemia with pruritus'],
        'prevalence' => 'high',
    ],

    // ── 3. Thấp nhiệt xâm bì ─────────────────────────────────────────────────
    [
        'code'    => 'damp_heat_skin',
        'name_vi' => 'Thấp nhiệt xâm bì (Eczema / Hắc lào do thấp nhiệt)',
        'name_en' => 'Damp-Heat invading Skin — Weeping Eczema / Tinea',
        'organ'   => 'spleen',
        'required'=> ['skin_eczema_weeping'],
        'two_or_more' => [
            'skin_rash_maculopapular',
            'generalized_pruritus_no_rash',
            'low_grade_persistent_fever',
            'dark_yellow_urine',
        ],
        'supporting' => [
            'skin_eczema_or_itching',
            'fatigue_with_hot_flushes',
            'poor_appetite',
        ],
        'diff_pos' => ['skin_eczema_weeping','yellow_greasy_tongue_coating'],
        'diff_neg' => ['dry_itchy_skin_or_eczema_chronic','pale_dull_complexion_or_pale_lips_nails'],
        'phap_tri_vi'  => 'Thanh nhiệt lợi thấp, khu phong chỉ dưỡng (Trừ thấp, thanh nhiệt, giảm ngứa)',
        'phuong_thuoc' => ['Long đởm tả can thang (龍膽瀉肝湯) gia giảm', 'Tứ diệu tán hợp Tiêu phong tán', 'Khổ sâm thang (bôi ngoài)'],
        'huyet_vi'     => ['Âm lăng tuyền (SP9)', 'Khúc trì (LI11)', 'Huyết hải (SP10)', 'Thái xung (LV3)', 'Tam âm giao (SP6)', 'Nội đình (ST44)'],
        'clinical_note'=> 'Da nổi mụn nước, rỉ dịch vàng, đóng vảy vàng nâu, ngứa nhiều. Thường kèm cảm giác nặng nề chi dưới, đại tiện dính nhờn hoặc lỏng, tiểu vàng đậm. Lưỡi đỏ rêu vàng nhờn. Mạch huyền hoạt sác. Gặp trong eczema cấp, hắc lào (tinea), viêm da tiếp xúc bội nhiễm.',
        'key_questions'=> ['tongue' => 'Lưỡi đỏ, rêu vàng nhờn (dày)', 'pulse' => 'Mạch hoạt sác (trơn và nhanh) hoặc huyền hoạt'],
        'yhhd'   => ['Eczema (acute/subacute)', 'Tinea (hắc lào)', 'Contact dermatitis', 'Intertrigo', 'Pompholyx', 'Scabies with secondary infection'],
        'prevalence' => 'high',
    ],

    // ── 4. Tỳ hư thấp trệ bì phu ─────────────────────────────────────────────
    [
        'code'    => 'spleen_deficiency_damp_skin',
        'name_vi' => 'Tỳ hư thấp trệ bì phu (Eczema mạn tính do Tỳ hư)',
        'name_en' => 'Spleen Deficiency with Damp Stagnation — Chronic Eczema',
        'organ'   => 'spleen',
        'required'=> ['skin_eczema_or_itching'],
        'two_or_more' => [
            'poor_appetite',
            'post_exertion_fatigue',
            'abdominal_distension',
            'loose_stool_or_diarrhea',
            'generalized_pruritus_no_rash',
        ],
        'supporting' => [
            'dry_itchy_skin_or_eczema_chronic',
            'skin_scaling',
            'skin_dryness',
        ],
        'diff_pos' => ['poor_appetite','loose_stool_or_diarrhea'],
        'diff_neg' => ['skin_eczema_weeping','high_fever_continuous'],
        'phap_tri_vi'  => 'Kiện tỳ hóa thấp, khu phong chỉ dưỡng (Bổ Tỳ, trừ thấp, giảm ngứa)',
        'phuong_thuoc' => ['Sâm linh bạch truật tán (參苓白朮散) gia Địa phu tử, Bạch tiên bì', 'Trừ thấp vị linh thang', 'Kiện tỳ trừ thấp thang'],
        'huyet_vi'     => ['Túc tam lý (ST36)', 'Tỳ du (BL20)', 'Âm lăng tuyền (SP9)', 'Huyết hải (SP10)', 'Trung quản (CV12)', 'Tam âm giao (SP6)'],
        'clinical_note'=> 'Eczema mạn tính tái phát, da dày, ít rỉ dịch. Bệnh nhân thường mệt mỏi, ăn kém, bụng đầy. Lưỡi nhạt hoặc bệu, có hằn răng, rêu trắng nhờn. Mạch hoãn nhược. Cần điều trị lâu dài.',
        'key_questions'=> ['tongue' => 'Lưỡi nhạt hoặc bệu phì, có dấu răng, rêu trắng nhờn', 'pulse' => 'Mạch hoãn nhược (chậm và yếu)'],
        'yhhd'   => ['Atopic dermatitis (chronic)', 'Seborrheic dermatitis', 'Lichen planus', 'Neurodermatitis', 'Irritable bowel syndrome with eczema'],
        'prevalence' => 'moderate',
    ],

    // ── 5. Phong thấp nhiệt ──────────────────────────────────────────────────
    [
        'code'    => 'wind_damp_heat_skin',
        'name_vi' => 'Phong thấp nhiệt (Mày đay / Ngứa cấp tính do phong nhiệt)',
        'name_en' => 'Wind-Damp-Heat — Acute Urticaria / Tinea Pedis',
        'organ'   => 'lung',
        'required'=> ['skin_rash_maculopapular'],
        'two_or_more' => [
            'generalized_pruritus_no_rash',
            'skin_eczema_or_itching',
            'low_grade_persistent_fever',
            'fatigue_with_hot_flushes',
            'severe_thirst_polyuria',
        ],
        'supporting' => [
            'skin_eczema_weeping',
            'itchy_throat_triggering_cough',
            'dark_yellow_urine',
        ],
        'diff_pos' => ['skin_rash_maculopapular','fatigue_with_hot_flushes'],
        'diff_neg' => ['dry_itchy_skin_or_eczema_chronic','loose_stool_or_diarrhea'],
        'phap_tri_vi'  => 'Sơ phong thanh nhiệt thắng thấp, chỉ dưỡng (Tán phong, thanh nhiệt, trừ thấp, giảm ngứa)',
        'phuong_thuoc' => ['Tiêu phong tán (消風散) — bài thuốc kinh điển', 'Phòng phong thông thánh tán gia giảm', 'Ma hoàng liên kiều xích tiểu đậu thang'],
        'huyet_vi'     => ['Hợp cốc (LI4)', 'Khúc trì (LI11)', 'Phong trì (GB20)', 'Đại trùy (GV14)', 'Huyết hải (SP10)', 'Tam âm giao (SP6)'],
        'clinical_note'=> 'Khởi phát cấp tính: nổi mẩn đỏ nhanh, ngứa dữ dội, có thể kèm sốt nhẹ, đau đầu, họng đau. Lưỡi hồng nhạt hoặc đỏ, rêu trắng mỏng hoặc vàng mỏng. Mạch phù sác (nổi nhanh). Gặp trong mày đay cấp, tinea pedis, dị ứng thức ăn/thời tiết.',
        'key_questions'=> ['tongue' => 'Lưỡi hồng nhạt hoặc đỏ nhẹ, rêu trắng mỏng hoặc vàng mỏng', 'pulse' => 'Mạch phù sác (nổi trên da và nhanh)'],
        'yhhd'   => ['Urticaria (acute)', 'Tinea pedis (hắc lào chân)', 'Angioedema', 'Drug rash', 'Food allergy rash', 'Prickly heat (rôm sảy)'],
        'prevalence' => 'high',
    ],

];

echo "\n=== Inserting skin patterns ===\n";
foreach ($patterns as $p) {
    insertPattern($db, $p);
}

echo "\nDone. Total kb_patterns: " . $db->query("SELECT COUNT(*) FROM kb_patterns")->fetchColumn() . "\n";
echo "Skin patterns: " . $db->query("SELECT COUNT(*) FROM kb_patterns WHERE chung_code IN ('blood_heat_wind_itch','blood_deficiency_wind_dryness_itch','damp_heat_skin','spleen_deficiency_damp_skin','wind_damp_heat_skin')")->fetchColumn() . "\n";
