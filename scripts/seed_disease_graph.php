<?php
/**
 * Seed Disease Graph — disease_nodes, disease_edges, symptom_disease_map
 *
 * Seeds 30 clinically important diseases with:
 *   - disease_nodes: metadata per disease
 *   - disease_edges: causal/risk relationships between diseases
 *   - symptom_disease_map: maps K02 symptom codes to diseases with specificity/sensitivity
 *
 * Usage: php scripts/seed_disease_graph.php [--clear]
 *   --clear : Delete all existing disease graph rows before seeding
 */

define('APP_ROOT', __DIR__ . '/../app');
define('DB_PATH',  APP_ROOT . '/storage/kham.db');
require_once APP_ROOT . '/core/Database.php';

$clear = in_array('--clear', $argv ?? []);
$db    = Database::get();
$db->exec("PRAGMA journal_mode=WAL");

if ($clear) {
    $db->exec("DELETE FROM symptom_disease_map");
    $db->exec("DELETE FROM disease_edges");
    $db->exec("DELETE FROM disease_nodes");
    echo "Cleared existing disease graph data.\n";
}

// ── Helper ──────────────────────────────────────────────────────────────────

$nodeStmt = $db->prepare("
    INSERT INTO disease_nodes (disease_code, name_vi, name_en, category, icd11_code, is_primary, prevalence_vn, yhct_correlate)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ON CONFLICT(disease_code) DO UPDATE
        SET name_vi=excluded.name_vi, name_en=excluded.name_en,
            category=excluded.category, prevalence_vn=excluded.prevalence_vn,
            yhct_correlate=excluded.yhct_correlate
");

$edgeStmt = $db->prepare("
    INSERT OR IGNORE INTO disease_edges (source_disease, target_disease, relation_type, strength, clinical_note, evidence_level)
    VALUES (?, ?, ?, ?, ?, ?)
");

$mapStmt = $db->prepare("
    INSERT INTO symptom_disease_map (symptom_code, disease_code, specificity, sensitivity, is_pathognomonic)
    VALUES (?, ?, ?, ?, ?)
    ON CONFLICT(symptom_code, disease_code) DO UPDATE
        SET specificity=excluded.specificity, sensitivity=excluded.sensitivity
");

function node(PDO $db, $stmt, string $code, string $nameVi, string $nameEn, string $cat, string $icd, bool $isPrimary, string $prev, string $yhct): void
{
    $stmt->execute([$code, $nameVi, $nameEn, $cat, $icd, $isPrimary ? 1 : 0, $prev, $yhct]);
}

function edge(PDO $db, $stmt, string $src, string $tgt, string $rel, float $str, string $note, string $ev): void
{
    $stmt->execute([$src, $tgt, $rel, $str, $note, $ev]);
}

function map(PDO $db, $stmt, string $symptom, string $disease, float $spec, float $sens, bool $path = false): void
{
    $stmt->execute([$symptom, $disease, $spec, $sens, $path ? 1 : 0]);
}

// ── DISEASE NODES ───────────────────────────────────────────────────────────

echo "\n[1] Seeding disease_nodes...\n";

// Nhóm A — Bệnh nền phổ biến
node($db, $nodeStmt, 'diabetes_mellitus_t2',  'Đái tháo đường type 2',       'Diabetes Mellitus Type 2',       'endocrine',      '5A11',  true, 'high',   'Tỳ hư, Thận hư, tiêu khát');
node($db, $nodeStmt, 'hypertension',           'Tăng huyết áp',               'Hypertension',                   'cardiovascular', 'BA00',  true, 'high',   'Can dương thượng kháng, Thận âm hư');
node($db, $nodeStmt, 'ischemic_heart_disease', 'Bệnh tim mạch vành',          'Ischemic Heart Disease',         'cardiovascular', 'BA80',  true, 'high',   'Tâm huyết ứ, Tâm khí hư');
node($db, $nodeStmt, 'copd',                   'Bệnh phổi tắc nghẽn mạn tính','COPD',                           'respiratory',    'CA22',  true, 'high',   'Phế thận lưỡng hư');
node($db, $nodeStmt, 'chronic_kidney_disease', 'Suy thận mạn',                'Chronic Kidney Disease',         'nephrology',     'GB61',  true, 'medium', 'Thận hư, Tỳ thận lưỡng hư');
node($db, $nodeStmt, 'cirrhosis',              'Xơ gan',                      'Liver Cirrhosis',                'hepatology',     'DB93',  true, 'medium', 'Can khí uất, Can huyết ứ');
node($db, $nodeStmt, 'hypothyroidism',         'Suy giáp',                    'Hypothyroidism',                 'endocrine',      '5A00',  true, 'medium', 'Tỳ thận dương hư');
node($db, $nodeStmt, 'hyperthyroidism',        'Cường giáp',                  'Hyperthyroidism',                'endocrine',      '5A01',  true, 'medium', 'Can hỏa vượng, Tâm âm hư');
node($db, $nodeStmt, 'osteoporosis',           'Loãng xương',                 'Osteoporosis',                   'orthopedic',     'FB83',  true, 'high',   'Thận tinh bất túc');
node($db, $nodeStmt, 'depression',             'Trầm cảm',                    'Major Depressive Disorder',      'mental',         '6A70',  true, 'high',   'Tâm Tỳ lưỡng hư, Can khí uất kết');

// Nhóm B — Bệnh gây immobility / nằm liệt
node($db, $nodeStmt, 'stroke_ischemic',        'Đột quỵ thiếu máu não',       'Ischemic Stroke',                'neurological',   '8B11',  true, 'high',   'Khí hư huyết ứ, Đàm trọc bế trở');
node($db, $nodeStmt, 'stroke_hemorrhagic',     'Xuất huyết não',              'Hemorrhagic Stroke',             'neurological',   '8B00',  true, 'medium', 'Can dương hóa phong, khí nghịch huyết ứ');
node($db, $nodeStmt, 'parkinson_disease',      'Bệnh Parkinson',              'Parkinson Disease',               'neurological',   '8A00',  true, 'medium', 'Can thận hư, nội phong');
node($db, $nodeStmt, 'dementia_alzheimer',     'Sa sút trí tuệ Alzheimer',    'Alzheimer Dementia',             'neurological',   '8A20',  true, 'medium', 'Thận tinh suy kiệt, Tâm huyết hư');
node($db, $nodeStmt, 'hip_fracture',           'Gãy cổ xương đùi',            'Hip Fracture',                   'orthopedic',     'NC63',  false,'medium', 'Thận hư cốt nhược');

// Nhóm C — Cấp cứu khẩn
node($db, $nodeStmt, 'acute_coronary_syndrome','Hội chứng vành cấp',          'Acute Coronary Syndrome',        'cardiovascular', 'BA41',  false,'high',   'Tâm huyết ứ trệ cấp tính');
node($db, $nodeStmt, 'pulmonary_embolism',     'Thuyên tắc phổi',             'Pulmonary Embolism',             'respiratory',    'BB21',  false,'low',    'Phế khí bế tắc');
node($db, $nodeStmt, 'meningitis_bacterial',   'Viêm màng não mủ',            'Bacterial Meningitis',           'infectious',     '1C00',  false,'low',    'Tà nhiệt xâm não');
node($db, $nodeStmt, 'subarachnoid_hemorrhage','Xuất huyết dưới nhện',        'Subarachnoid Hemorrhage',        'neurological',   '8B01',  false,'rare',   'Can dương bạo thăng');
node($db, $nodeStmt, 'aortic_dissection',      'Tách thành động mạch chủ',    'Aortic Dissection',              'cardiovascular', 'BD50',  false,'rare',   'Khí huyết nghịch loạn cấp tính');

// Nhóm D — Bệnh nhiệt đới / phổ biến VN
node($db, $nodeStmt, 'dengue_fever',           'Sốt xuất huyết dengue',       'Dengue Fever',                   'infectious',     '1D2Z',  false,'high',   'Ôn bệnh, thấp nhiệt');
node($db, $nodeStmt, 'tuberculosis',           'Lao phổi',                    'Pulmonary Tuberculosis',         'infectious',     '1B10',  false,'medium', 'Phế âm hư, Hư lao');
node($db, $nodeStmt, 'chronic_hepatitis_b',    'Viêm gan B mạn tính',         'Chronic Hepatitis B',            'hepatology',     'DB92',  false,'high',   'Can khí uất, Thấp nhiệt uất kết');

// Nhóm E — Bệnh tiêu hóa / tâm thần phổ biến
node($db, $nodeStmt, 'gastric_ulcer',          'Loét dạ dày tá tràng',        'Peptic Ulcer Disease',           'gastroenterology','DA60',true, 'high',   'Can khí phạm Vị, Tỳ Vị hư hàn');
node($db, $nodeStmt, 'anxiety_disorder',       'Rối loạn lo âu',              'Generalized Anxiety Disorder',   'mental',         '6B00',  true, 'high',   'Tâm Đởm hư kiếp, Tâm hỏa vượng');
node($db, $nodeStmt, 'iron_deficiency_anemia', 'Thiếu máu thiếu sắt',         'Iron Deficiency Anemia',         'hematology',     '3A00',  true, 'high',   'Tâm Tỳ lưỡng hư, huyết hư');
node($db, $nodeStmt, 'gerd',                   'Trào ngược dạ dày thực quản', 'GERD',                           'gastroenterology','DA22',true, 'high',   'Can khí phạm Vị, Vị khí thượng nghịch');
node($db, $nodeStmt, 'pressure_ulcer',         'Loét da do tỳ đè',            'Pressure Ulcer',                 'dermatology',    'EH90',  false,'medium', 'Khí huyết hư, bì phu thất dưỡng');
node($db, $nodeStmt, 'dvt',                    'Huyết khối tĩnh mạch sâu',    'Deep Vein Thrombosis',           'cardiovascular', 'BD50',  false,'low',    'Huyết ứ, kinh lạc bế trở');
node($db, $nodeStmt, 'hyperlipidemia',         'Rối loạn lipid máu',          'Hyperlipidemia',                 'endocrine',      '5C80',  true, 'high',   'Đàm thấp nội uẩn');

echo "  Nodes seeded.\n";

// ── DISEASE EDGES ───────────────────────────────────────────────────────────

echo "[2] Seeding disease_edges...\n";

// hypertension is a risk factor for many conditions
edge($db, $edgeStmt, 'hypertension', 'stroke_ischemic',        'risk_factor',    0.80, 'Tăng huyết áp là yếu tố nguy cơ số 1 của đột quỵ',                   'strong');
edge($db, $edgeStmt, 'hypertension', 'stroke_hemorrhagic',     'risk_factor',    0.75, 'THA làm tăng nguy cơ xuất huyết não',                                'strong');
edge($db, $edgeStmt, 'hypertension', 'ischemic_heart_disease', 'risk_factor',    0.80, 'THA tăng gánh nặng lên cơ tim',                                      'strong');
edge($db, $edgeStmt, 'hypertension', 'chronic_kidney_disease', 'risk_factor',    0.70, 'THA mạn tính gây tổn thương cầu thận',                               'strong');
edge($db, $edgeStmt, 'hypertension', 'aortic_dissection',      'risk_factor',    0.60, 'THA là yếu tố nguy cơ chính của tách thành ĐMC',                     'strong');

// diabetes causes/risk factor
edge($db, $edgeStmt, 'diabetes_mellitus_t2', 'chronic_kidney_disease', 'causes',     0.75, 'Biến chứng thận do tiểu đường (diabetic nephropathy)',          'strong');
edge($db, $edgeStmt, 'diabetes_mellitus_t2', 'ischemic_heart_disease', 'risk_factor',0.80, 'Đái tháo đường tăng nguy cơ xơ vữa động mạch',                'strong');
edge($db, $edgeStmt, 'diabetes_mellitus_t2', 'stroke_ischemic',        'risk_factor',0.65, 'Đái tháo đường tăng nguy cơ đột quỵ 2-4 lần',                 'strong');
edge($db, $edgeStmt, 'diabetes_mellitus_t2', 'hyperlipidemia',         'associated_with', 0.70, 'Hay đi kèm với rối loạn lipid máu',                      'moderate');

// ischemic heart disease
edge($db, $edgeStmt, 'ischemic_heart_disease', 'acute_coronary_syndrome', 'causes',  0.90, 'Mảng xơ vữa vỡ → nhồi máu cơ tim cấp',                        'strong');

// cirrhosis
edge($db, $edgeStmt, 'chronic_hepatitis_b', 'cirrhosis', 'causes', 0.60, 'Viêm gan B mạn tính là nguyên nhân hàng đầu gây xơ gan',                        'strong');

// osteoporosis
edge($db, $edgeStmt, 'osteoporosis', 'hip_fracture', 'causes', 0.70, 'Loãng xương làm tăng nguy cơ gãy cổ xương đùi sau chấn thương nhẹ',                  'strong');

// stroke complications
edge($db, $edgeStmt, 'stroke_ischemic',    'pressure_ulcer', 'causes', 0.60, 'Nằm lâu sau đột quỵ dẫn đến loét da',                                        'strong');
edge($db, $edgeStmt, 'stroke_ischemic',    'dvt',            'causes', 0.55, 'Bất động sau đột quỵ tăng nguy cơ DVT',                                       'moderate');
edge($db, $edgeStmt, 'stroke_hemorrhagic', 'pressure_ulcer', 'causes', 0.60, 'Nằm lâu sau xuất huyết não dẫn đến loét da',                                  'strong');
edge($db, $edgeStmt, 'hip_fracture',       'pressure_ulcer', 'causes', 0.65, 'Bất động sau gãy xương đùi dễ gây loét da',                                   'strong');
edge($db, $edgeStmt, 'hip_fracture',       'dvt',            'causes', 0.70, 'Gãy xương đùi + bất động tăng cao nguy cơ DVT',                               'strong');

// DVT
edge($db, $edgeStmt, 'dvt', 'pulmonary_embolism', 'causes', 0.80, 'Cục huyết khối từ DVT di chuyển lên phổi gây thuyên tắc',                               'strong');

// hyperlipidemia
edge($db, $edgeStmt, 'hyperlipidemia', 'ischemic_heart_disease', 'risk_factor', 0.75, 'LDL cao là yếu tố nguy cơ xơ vữa động mạch vành',                    'strong');
edge($db, $edgeStmt, 'hyperlipidemia', 'stroke_ischemic',        'risk_factor', 0.60, 'Rối loạn lipid tăng nguy cơ đột quỵ',                                'moderate');

// COPD
edge($db, $edgeStmt, 'copd', 'pulmonary_embolism', 'risk_factor', 0.45, 'COPD tăng nguy cơ DVT và thuyên tắc phổi',                                        'moderate');

// Depression
edge($db, $edgeStmt, 'depression', 'anxiety_disorder', 'associated_with', 0.65, 'Trầm cảm và lo âu thường đồng mắc',                                       'strong');
edge($db, $edgeStmt, 'ischemic_heart_disease', 'depression', 'associated_with', 0.50, 'Bệnh tim mạch thường kèm theo trầm cảm',                             'moderate');

echo "  Edges seeded.\n";

// ── SYMPTOM → DISEASE MAP ───────────────────────────────────────────────────

echo "[3] Seeding symptom_disease_map...\n";

// Format: map($db, $mapStmt, symptom_code, disease_code, specificity, sensitivity, is_pathognomonic)

// ── Đái tháo đường type 2
map($db, $mapStmt, 'frequent_urination_night', 'diabetes_mellitus_t2', 0.60, 0.70);
map($db, $mapStmt, 'fatigue_general',          'diabetes_mellitus_t2', 0.30, 0.75);
map($db, $mapStmt, 'weight_loss_unexplained',  'diabetes_mellitus_t2', 0.45, 0.55);
map($db, $mapStmt, 'poor_appetite_loss',       'diabetes_mellitus_t2', 0.25, 0.40);
map($db, $mapStmt, 'dry_mouth_throat',         'diabetes_mellitus_t2', 0.35, 0.60);

// ── Tăng huyết áp
map($db, $mapStmt, 'throbbing_headache',       'hypertension', 0.40, 0.55);
map($db, $mapStmt, 'dizziness_vertigo',        'hypertension', 0.35, 0.60);
map($db, $mapStmt, 'palpitations_irregular',   'hypertension', 0.30, 0.45);
map($db, $mapStmt, 'tinnitus',                 'hypertension', 0.30, 0.40);
map($db, $mapStmt, 'tinnitus_low',             'hypertension', 0.30, 0.40);

// ── Bệnh tim mạch vành
map($db, $mapStmt, 'chest_pain_stabbing',      'ischemic_heart_disease', 0.65, 0.70);
map($db, $mapStmt, 'shortness_of_breath',      'ischemic_heart_disease', 0.45, 0.65);
map($db, $mapStmt, 'palpitations_irregular',   'ischemic_heart_disease', 0.40, 0.55);
map($db, $mapStmt, 'fatigue_general',          'ischemic_heart_disease', 0.25, 0.60);

// ── Hội chứng vành cấp
map($db, $mapStmt, 'chest_pain_stabbing',      'acute_coronary_syndrome', 0.80, 0.90, true);
map($db, $mapStmt, 'shortness_of_breath',      'acute_coronary_syndrome', 0.55, 0.65);
map($db, $mapStmt, 'nausea_vomiting',          'acute_coronary_syndrome', 0.35, 0.40);
map($db, $mapStmt, 'palpitations_irregular',   'acute_coronary_syndrome', 0.45, 0.55);

// ── COPD
map($db, $mapStmt, 'cough_with_phlegm',        'copd', 0.55, 0.80);
map($db, $mapStmt, 'shortness_of_breath',      'copd', 0.50, 0.85);
map($db, $mapStmt, 'cough_dry',                'copd', 0.30, 0.50);
map($db, $mapStmt, 'fatigue_general',          'copd', 0.20, 0.60);

// ── Suy thận mạn
map($db, $mapStmt, 'edema_lower_limbs',        'chronic_kidney_disease', 0.55, 0.65);
map($db, $mapStmt, 'fatigue_general',          'chronic_kidney_disease', 0.30, 0.70);
map($db, $mapStmt, 'frequent_urination_night', 'chronic_kidney_disease', 0.40, 0.60);
map($db, $mapStmt, 'lower_back_pain',          'chronic_kidney_disease', 0.25, 0.45);
map($db, $mapStmt, 'pale_complexion',          'chronic_kidney_disease', 0.40, 0.55);
map($db, $mapStmt, 'nausea_vomiting',          'chronic_kidney_disease', 0.35, 0.45);

// ── Xơ gan
map($db, $mapStmt, 'right_hypochondrial_pain', 'cirrhosis', 0.60, 0.65);
map($db, $mapStmt, 'bloating_fullness',        'cirrhosis', 0.40, 0.60);
map($db, $mapStmt, 'nausea_vomiting',          'cirrhosis', 0.35, 0.55);
map($db, $mapStmt, 'fatigue_general',          'cirrhosis', 0.25, 0.75);
map($db, $mapStmt, 'edema_lower_limbs',        'cirrhosis', 0.50, 0.50);

// ── Suy giáp
map($db, $mapStmt, 'fatigue_general',          'hypothyroidism', 0.35, 0.80);
map($db, $mapStmt, 'cold_extremities',         'hypothyroidism', 0.50, 0.70);
map($db, $mapStmt, 'poor_appetite_loss',       'hypothyroidism', 0.30, 0.45);
map($db, $mapStmt, 'constipation_hard_stools', 'hypothyroidism', 0.40, 0.55);
map($db, $mapStmt, 'depression_mood',          'hypothyroidism', 0.30, 0.40);
map($db, $mapStmt, 'cold_lower_body',          'hypothyroidism', 0.45, 0.60);

// ── Cường giáp
map($db, $mapStmt, 'palpitations_irregular',   'hyperthyroidism', 0.60, 0.80);
map($db, $mapStmt, 'anxiety_worry',            'hyperthyroidism', 0.50, 0.70);
map($db, $mapStmt, 'weight_loss_unexplained',  'hyperthyroidism', 0.65, 0.75);
map($db, $mapStmt, 'irritability_emotional_lability', 'hyperthyroidism', 0.45, 0.65);

// ── Loãng xương
map($db, $mapStmt, 'lower_back_pain',          'osteoporosis', 0.35, 0.60);
map($db, $mapStmt, 'weak_knees',               'osteoporosis', 0.30, 0.50);

// ── Trầm cảm
map($db, $mapStmt, 'depression_mood',          'depression', 0.70, 0.90, true);
map($db, $mapStmt, 'insomnia',                 'depression', 0.40, 0.75);
map($db, $mapStmt, 'fatigue_general',          'depression', 0.30, 0.70);
map($db, $mapStmt, 'poor_appetite_loss',       'depression', 0.35, 0.60);
map($db, $mapStmt, 'poor_memory',              'depression', 0.35, 0.50);
map($db, $mapStmt, 'sighing_dyspnea',          'depression', 0.40, 0.55);

// ── Đột quỵ thiếu máu não
map($db, $mapStmt, 'facial_droop_weakness',    'stroke_ischemic', 0.90, 0.85, true);
map($db, $mapStmt, 'dizziness_vertigo',        'stroke_ischemic', 0.40, 0.55);
map($db, $mapStmt, 'severe_headache_sudden',   'stroke_ischemic', 0.45, 0.35);

// ── Xuất huyết não
map($db, $mapStmt, 'severe_headache_sudden',   'stroke_hemorrhagic', 0.70, 0.80);
map($db, $mapStmt, 'facial_droop_weakness',    'stroke_hemorrhagic', 0.85, 0.75, true);
map($db, $mapStmt, 'neck_stiffness',           'stroke_hemorrhagic', 0.40, 0.30);

// ── Parkinson
map($db, $mapStmt, 'muscle_weakness',          'parkinson_disease', 0.40, 0.60);
map($db, $mapStmt, 'fatigue_general',          'parkinson_disease', 0.20, 0.55);
map($db, $mapStmt, 'depression_mood',          'parkinson_disease', 0.30, 0.45);
map($db, $mapStmt, 'constipation_hard_stools', 'parkinson_disease', 0.30, 0.50);

// ── Alzheimer
map($db, $mapStmt, 'poor_memory',              'dementia_alzheimer', 0.75, 0.90, true);
map($db, $mapStmt, 'depression_mood',          'dementia_alzheimer', 0.30, 0.50);
map($db, $mapStmt, 'insomnia',                 'dementia_alzheimer', 0.30, 0.45);

// ── Gãy cổ xương đùi
map($db, $mapStmt, 'lower_back_pain',          'hip_fracture', 0.20, 0.40);
map($db, $mapStmt, 'pressure_ulcer_risk',      'hip_fracture', 0.55, 0.50);
map($db, $mapStmt, 'muscle_weakness',          'hip_fracture', 0.25, 0.40);

// ── Xuất huyết dưới nhện
map($db, $mapStmt, 'severe_headache_sudden',   'subarachnoid_hemorrhage', 0.85, 0.95, true);
map($db, $mapStmt, 'neck_stiffness',           'subarachnoid_hemorrhage', 0.70, 0.65);

// ── Viêm màng não mủ
map($db, $mapStmt, 'neck_stiffness',           'meningitis_bacterial', 0.80, 0.75, true);
map($db, $mapStmt, 'high_fever',               'meningitis_bacterial', 0.45, 0.90);
map($db, $mapStmt, 'severe_headache_sudden',   'meningitis_bacterial', 0.55, 0.70);

// ── Thuyên tắc phổi
map($db, $mapStmt, 'chest_pain_stabbing',      'pulmonary_embolism', 0.50, 0.60);
map($db, $mapStmt, 'shortness_of_breath',      'pulmonary_embolism', 0.55, 0.85);
map($db, $mapStmt, 'hemoptysis',               'pulmonary_embolism', 0.60, 0.30);

// ── Sốt xuất huyết
map($db, $mapStmt, 'fever_4_7_days',           'dengue_fever', 0.65, 0.85, true);
map($db, $mapStmt, 'high_fever',               'dengue_fever', 0.35, 0.80);

// ── Lao phổi
map($db, $mapStmt, 'cough_with_phlegm',        'tuberculosis', 0.40, 0.75);
map($db, $mapStmt, 'hemoptysis',               'tuberculosis', 0.70, 0.50);
map($db, $mapStmt, 'weight_loss_unexplained',  'tuberculosis', 0.55, 0.70);
map($db, $mapStmt, 'high_fever',               'tuberculosis', 0.30, 0.55);
map($db, $mapStmt, 'night_sweats',             'tuberculosis', 0.55, 0.65);

// ── Viêm gan B mạn
map($db, $mapStmt, 'fatigue_general',          'chronic_hepatitis_b', 0.30, 0.70);
map($db, $mapStmt, 'right_hypochondrial_pain', 'chronic_hepatitis_b', 0.55, 0.55);
map($db, $mapStmt, 'nausea_vomiting',          'chronic_hepatitis_b', 0.30, 0.45);

// ── Loét dạ dày tá tràng
map($db, $mapStmt, 'epigastric_pain',          'gastric_ulcer', 0.60, 0.80, true);
map($db, $mapStmt, 'nausea_vomiting',          'gastric_ulcer', 0.35, 0.55);
map($db, $mapStmt, 'bloating_fullness',        'gastric_ulcer', 0.35, 0.60);
map($db, $mapStmt, 'poor_appetite_loss',       'gastric_ulcer', 0.30, 0.55);

// ── Rối loạn lo âu
map($db, $mapStmt, 'anxiety_worry',            'anxiety_disorder', 0.75, 0.90, true);
map($db, $mapStmt, 'insomnia',                 'anxiety_disorder', 0.45, 0.70);
map($db, $mapStmt, 'palpitations_irregular',   'anxiety_disorder', 0.40, 0.65);
map($db, $mapStmt, 'poor_appetite_loss',       'anxiety_disorder', 0.25, 0.40);

// ── Thiếu máu thiếu sắt
map($db, $mapStmt, 'pale_complexion',          'iron_deficiency_anemia', 0.65, 0.80, true);
map($db, $mapStmt, 'fatigue_general',          'iron_deficiency_anemia', 0.35, 0.80);
map($db, $mapStmt, 'poor_appetite_loss',       'iron_deficiency_anemia', 0.30, 0.50);
map($db, $mapStmt, 'cold_extremities',         'iron_deficiency_anemia', 0.35, 0.55);

// ── GERD
map($db, $mapStmt, 'epigastric_pain',          'gerd', 0.50, 0.70);
map($db, $mapStmt, 'sore_throat',              'gerd', 0.40, 0.45);
map($db, $mapStmt, 'nausea_vomiting',          'gerd', 0.30, 0.50);
map($db, $mapStmt, 'bloating_fullness',        'gerd', 0.35, 0.55);

// ── Loét da do tỳ đè
map($db, $mapStmt, 'pressure_ulcer_risk',      'pressure_ulcer', 0.90, 0.95, true);

// ── Rối loạn lipid
map($db, $mapStmt, 'fatigue_general',          'hyperlipidemia', 0.15, 0.30);
map($db, $mapStmt, 'heavy_dull_headache',      'hyperlipidemia', 0.20, 0.25);

echo "  Symptom-disease maps seeded.\n";

// ── Summary ──────────────────────────────────────────────────────────────────

$nNodes = $db->query("SELECT COUNT(*) FROM disease_nodes")->fetchColumn();
$nEdges = $db->query("SELECT COUNT(*) FROM disease_edges")->fetchColumn();
$nMaps  = $db->query("SELECT COUNT(*) FROM symptom_disease_map")->fetchColumn();

echo "\nDone:\n";
echo "  disease_nodes:       $nNodes\n";
echo "  disease_edges:       $nEdges\n";
echo "  symptom_disease_map: $nMaps\n";
