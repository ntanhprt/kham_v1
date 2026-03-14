<?php
/**
 * sync_k04_symptoms.php
 *
 * Creates kb_symptoms entries for all symptom codes referenced in K04 patterns
 * and K03 pathogenesis rules that are missing from the current K02 import.
 *
 * Run from project root:
 *   php scripts/sync_k04_symptoms.php
 */

require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/core/Database.php';

$db      = Database::get();
$dataDir = __DIR__ . '/../data';

// ── 1. Load existing K02 symptom codes ───────────────────────────────────────
$existing = $db->query("SELECT symptom_code FROM kb_symptoms")
               ->fetchAll(PDO::FETCH_COLUMN);
$existingSet = array_flip($existing);

// ── 2. Collect all codes from K04 patterns ────────────────────────────────────
$k04Files = glob($dataDir . '/k04_patterns/*.json');
$k03Files = glob($dataDir . '/k03_pathogenesis/*.json');

$codeInfo = []; // code => ['organ'=>..., 'bat_cuong'=>..., 'category'=>...]

foreach ($k04Files as $file) {
    $rows = json_decode(file_get_contents($file), true) ?? [];
    foreach ($rows as $r) {
        $zangfu  = $r['zangfu_primary'] ?? null;
        $batC    = $r['bat_cuong_summary'] ?? [];
        $dc      = $r['diagnostic_criteria'] ?? [];
        $reqCodes = array_merge(
            $dc['required']      ?? [],
            $dc['two_or_more_of'] ?? []
        );
        foreach ($reqCodes as $code) {
            if (!isset($codeInfo[$code])) {
                $codeInfo[$code] = ['organ' => $zangfu, 'bat_cuong' => $batC, 'category' => inferCategory($zangfu)];
            }
        }
    }
}

foreach ($k03Files as $file) {
    $rows = json_decode(file_get_contents($file), true) ?? [];
    foreach ($rows as $r) {
        $zangfu  = $r['zangfu_primary'] ?? null;
        $batC    = $r['bat_cuong_profile'] ?? [];
        foreach (array_merge($r['required_symptoms'] ?? [], $r['supporting_symptoms'] ?? []) as $code) {
            if (!isset($codeInfo[$code])) {
                $codeInfo[$code] = ['organ' => $zangfu, 'bat_cuong' => $batC, 'category' => inferCategory($zangfu)];
            }
        }
    }
}

// ── 3. Find missing codes ─────────────────────────────────────────────────────
$missing = array_diff_key($codeInfo, $existingSet);
echo "Total unique codes in K04/K03: " . count($codeInfo) . "\n";
echo "Already in K02: " . count($existingSet) . "\n";
echo "Missing (to create): " . count($missing) . "\n\n";

// ── 4. Vietnamese name dictionary ─────────────────────────────────────────────
$viDict = [
    // Anatomy
    'hypochondrial'   => 'hạ sườn',
    'hepatic'         => 'gan',
    'epigastric'      => 'thượng vị',
    'abdominal'       => 'bụng',
    'abdomen'         => 'bụng',
    'lumbar'          => 'thắt lưng',
    'knee'            => 'đầu gối',
    'back'            => 'lưng',
    'chest'           => 'ngực',
    'throat'          => 'họng',
    'breast'          => 'vú',
    'temporal'        => 'thái dương',
    'vertex'          => 'đỉnh đầu',
    'occipital'       => 'chẩm đầu',
    'frontal'         => 'trán',
    'facial'          => 'mặt',
    'face'            => 'mặt',
    'eye'             => 'mắt',
    'eyes'            => 'mắt',
    'ear'             => 'tai',
    'finger'          => 'ngón tay',
    'joint'           => 'khớp',
    'muscle'          => 'cơ',
    'tendon'          => 'gân',
    'skin'            => 'da',
    'scalp'           => 'da đầu',
    'tongue'          => 'lưỡi',
    'uterine'         => 'tử cung',
    'vaginal'         => 'âm đạo',
    'menstrual'       => 'kinh nguyệt',
    'cardiac'         => 'tim',
    'pericardial'     => 'màng tim',
    'pulmonary'       => 'phổi',
    'renal'           => 'thận',
    'spleen'          => 'tỳ',
    'liver'           => 'can/gan',
    'bladder'         => 'bàng quang',
    'intestinal'      => 'ruột',
    'nasal'           => 'mũi',
    'urinary'         => 'tiết niệu',
    'rectal'          => 'hậu môn',
    // Symptoms
    'headache'        => 'đau đầu',
    'pain'            => 'đau',
    'distension'      => 'trướng/đầy',
    'distending'      => 'trướng',
    'fullness'        => 'đầy bụng',
    'discomfort'      => 'khó chịu',
    'tightness'       => 'tức',
    'heaviness'       => 'nặng nề',
    'throbbing'       => 'đau theo nhịp đập',
    'burning'         => 'nóng rát',
    'stabbing'        => 'đau như dao đâm',
    'cramping'        => 'co thắt',
    'colicky'         => 'đau quặn',
    'dull'            => 'âm ỉ',
    'aching'          => 'nhức mỏi',
    'sharp'           => 'đau sắc bén',
    'dizziness'       => 'chóng mặt',
    'vertigo'         => 'chóng mặt quay cuồng',
    'nausea'          => 'buồn nôn',
    'vomiting'        => 'nôn mửa',
    'belching'        => 'ợ hơi',
    'hiccup'          => 'nấc cụt',
    'bloating'        => 'bụng đầy hơi',
    'constipation'    => 'táo bón',
    'diarrhea'        => 'tiêu chảy',
    'stool'           => 'phân',
    'loose'           => 'phân lỏng',
    'bloody'          => 'có máu',
    'mucus'           => 'nhầy',
    'insomnia'        => 'mất ngủ',
    'sleep'           => 'giấc ngủ',
    'fatigue'         => 'mệt mỏi',
    'weakness'        => 'yếu ớt',
    'lethargy'        => 'uể oải',
    'sweating'        => 'đổ mồ hôi',
    'sweat'           => 'mồ hôi',
    'fever'           => 'sốt',
    'chills'          => 'sợ lạnh',
    'cold'            => 'lạnh',
    'hot'             => 'nóng',
    'heat'            => 'nhiệt',
    'flushing'        => 'bừng đỏ',
    'flushed'         => 'đỏ mặt',
    'redness'         => 'đỏ',
    'red'             => 'đỏ',
    'pale'            => 'nhợt nhạt',
    'pallor'          => 'sắc mặt nhợt',
    'yellow'          => 'vàng',
    'dark'            => 'sẫm màu',
    'cough'           => 'ho',
    'dyspnea'         => 'khó thở',
    'breathlessness'  => 'khó thở',
    'palpitation'     => 'hồi hộp/đánh trống ngực',
    'tinnitus'        => 'ù tai',
    'deafness'        => 'nghe kém',
    'hearing'         => 'thính giác',
    'blurred'         => 'mờ',
    'vision'          => 'thị lực',
    'dry'             => 'khô',
    'thirst'          => 'khát',
    'appetite'        => 'cảm giác thèm ăn',
    'poor'            => 'kém',
    'reduced'         => 'giảm',
    'loss'            => 'mất',
    'excessive'       => 'quá nhiều',
    'increased'       => 'tăng',
    'frequent'        => 'thường xuyên',
    'urgency'         => 'mót',
    'incontinence'    => 'tiểu không tự chủ',
    'retention'       => 'bí',
    'hematuria'       => 'đái ra máu',
    'proteinuria'     => 'protein niệu',
    'edema'           => 'phù',
    'swelling'        => 'sưng',
    'numbness'        => 'tê',
    'tingling'        => 'tê bì',
    'tremor'          => 'run rẩy',
    'spasm'           => 'co thắt',
    'cramps'          => 'chuột rút',
    'convulsion'      => 'co giật',
    'paralysis'       => 'liệt',
    'stiffness'       => 'cứng',
    'soreness'        => 'đau nhức',
    'rash'            => 'phát ban',
    'itching'         => 'ngứa',
    'itchy'           => 'ngứa',
    'bleeding'        => 'chảy máu',
    'hematemesis'     => 'nôn ra máu',
    'epistaxis'       => 'chảy máu mũi',
    'bruising'        => 'bầm tím',
    'discharge'       => 'tiết dịch',
    'leucorrhea'      => 'khí hư',
    'menorrhagia'     => 'kinh nhiều',
    'amenorrhea'      => 'vô kinh',
    'dysmenorrhea'    => 'đau bụng kinh',
    'irregular'       => 'không đều',
    'delayed'         => 'trễ',
    'early'           => 'sớm',
    'spotting'        => 'ra ít máu',
    // Emotions/mental
    'emotional'       => 'tình chí',
    'depression'      => 'trầm uất',
    'irritability'    => 'dễ cáu kỉnh',
    'irritable'       => 'cáu kỉnh',
    'anxiety'         => 'lo âu',
    'anger'           => 'tức giận',
    'rage'            => 'nổi giận',
    'fear'            => 'sợ hãi',
    'fright'          => 'hoảng sợ',
    'sadness'         => 'buồn bã',
    'grief'           => 'bi thương',
    'mood'            => 'tâm trạng',
    'stress'          => 'căng thẳng',
    'restless'        => 'bất an',
    'restlessness'    => 'bất an/bồn chồn',
    'confusion'       => 'lú lẫn',
    'forgetful'       => 'hay quên',
    'memory'          => 'trí nhớ',
    'concentration'   => 'tập trung',
    // Modifiers
    'acute'           => 'cấp tính',
    'chronic'         => 'mạn tính',
    'intermittent'    => 'từng đợt',
    'persistent'      => 'dai dẳng',
    'recurrent'       => 'tái phát',
    'sudden'          => 'đột ngột',
    'gradual'         => 'từ từ',
    'progressive'     => 'tiến triển',
    'bilateral'       => 'hai bên',
    'unilateral'      => 'một bên',
    'generalized'     => 'toàn thân',
    'localized'       => 'cục bộ',
    'mild'            => 'nhẹ',
    'moderate'        => 'vừa',
    'severe'          => 'nặng',
    'worse'           => 'nặng hơn khi',
    'better'          => 'nhẹ hơn khi',
    'relieved'        => 'giảm khi',
    'aggravated'      => 'nặng hơn khi',
    // Misc
    'plum'            => 'hòn mai',
    'pit'             => 'cảm giác vướng',
    'sensation'       => 'cảm giác',
    'swings'          => 'thay đổi',
    'sighing'         => 'thở dài thở ngắn',
    'sigh'            => 'thở dài',
    'tenderness'      => 'đau khi ấn',
    'onset'           => 'khởi phát',
    'dream'           => 'mơ nhiều',
    'disturbed'       => 'không yên',
    'overactive'      => 'hoạt động quá mức',
    'deficient'       => 'hư',
    'excess'          => 'thực',
    'stagnation'      => 'uất trệ',
    'stasis'          => 'ứ trệ',
    'deficiency'      => 'hư tổn',
    'uprising'        => 'bốc lên',
    'descending'      => 'đi xuống',
    'rising'          => 'dâng lên',
    'sinking'         => 'sa',
    'pattern'         => 'chứng',
    'syndrome'        => 'hội chứng',
    'condition'       => 'tình trạng',
    'cases'           => '',
    'case'            => '',
    'or'              => 'hoặc',
    'and'             => 'và',
    'with'            => 'kèm',
    'without'         => 'không',
    'due'             => 'do',
    'after'           => 'sau khi',
    'before'          => 'trước khi',
    'during'          => 'trong khi',
    'morning'         => 'sáng',
    'evening'         => 'tối',
    'night'           => 'ban đêm',
    'alternating'     => 'luân phiên',
    'mixed'           => 'hỗn hợp',
    'complex'         => 'phức tạp',
    'combined'        => 'kết hợp',
    'soreness'        => 'nhức mỏi',
    'weakness'        => 'yếu',
    'lassitude'       => 'mệt mỏi uể oải',
    'prolapse'        => 'sa',
    'spontaneous'     => 'tự nhiên',
    'nocturnal'       => 'ban đêm',
    'diurnal'         => 'ban ngày',
    'exertional'      => 'khi gắng sức',
    'postprandial'    => 'sau khi ăn',
    'hunger'          => 'đói',
    'fullness'        => 'no/đầy',
    'indigestion'     => 'khó tiêu',
    'reflux'          => 'trào ngược',
    'acid'            => 'acid/chua',
    'regurgitation'   => 'trớ/ợ chua',
    'hiccups'         => 'nấc cụt',
    'borborygmus'     => 'sôi bụng',
    'flatulence'      => 'hơi',
    'tenesmus'        => 'mót rặn',
    'incomplete'      => 'không hết',
    'evacuation'      => 'đại tiện',
    'defecation'      => 'đại tiện',
    'urination'       => 'tiểu tiện',
    'cloudy'          => 'đục',
    'turbid'          => 'đục',
    'foamy'           => 'bọt',
    'concentrated'    => 'đậm đặc',
    'polyuria'        => 'đái nhiều',
    'oliguria'        => 'đái ít',
    'scanty'          => 'ít',
    'profuse'         => 'nhiều',
    'copious'         => 'nhiều',
    'watery'          => 'loãng như nước',
    'blood'           => 'máu',
    'clots'           => 'cục máu',
    'purple'          => 'tím',
    'blue'            => 'xanh',
    'green'           => 'xanh lá',
    'white'           => 'trắng',
    'grey'            => 'xám',
    'black'           => 'đen',
    'coating'         => 'rêu lưỡi',
    'greasy'          => 'nhầy nhớt',
    'thin'            => 'mỏng',
    'thick'           => 'dày',
    'peeled'          => 'bong',
    'cracked'         => 'nứt',
    'swollen'         => 'to/phù',
    'teethmarks'      => 'có dấu răng',
    'wiry'            => 'huyền',
    'slippery'        => 'hoạt',
    'rapid'           => 'sác',
    'slow'            => 'trì',
    'weak'            => 'nhược',
    'strong'          => 'mạnh',
    'deep'            => 'trầm',
    'superficial'     => 'phù',
    'pulse'           => 'mạch',
    'tongue'          => 'lưỡi',
];

// ── 5. Helper: code → Vietnamese name ─────────────────────────────────────────
function codeToVietnamese(string $code, array $dict): string
{
    $words = explode('_', strtolower($code));
    $parts = [];
    foreach ($words as $w) {
        if ($w === '' || in_array($w, ['in','the','of','a','an','is','are','on','at','to','by','as'])) continue;
        if (isset($dict[$w]) && $dict[$w] !== '') {
            $parts[] = $dict[$w];
        } else {
            $parts[] = $w; // keep as-is if no translation
        }
    }
    $vi = implode(' ', $parts);
    return mb_strtoupper(mb_substr($vi, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($vi, 1, null, 'UTF-8');
}

function inferCategory(string $organ = null): string
{
    $map = [
        'liver' => 'constitutional', 'gallbladder' => 'constitutional',
        'heart' => 'cardiovascular', 'pericardium' => 'cardiovascular',
        'spleen' => 'digestive', 'stomach' => 'digestive',
        'lung' => 'respiratory', 'large_intestine' => 'digestive',
        'kidney' => 'urogenital', 'bladder' => 'urogenital',
        'uterus' => 'gynecological',
    ];
    return $map[$organ] ?? 'constitutional';
}

function inferBatCuong(array $batC): array
{
    // From K04 bat_cuong_summary: {dominant: [...], secondary: [...], absent: [...]}
    // Build a simple weights object
    $dominant   = $batC['dominant']   ?? [];
    $secondary  = $batC['secondary']  ?? [];
    $absent     = $batC['absent']     ?? [];
    $allDims    = ['cold','heat','deficiency','excess','exterior','interior'];
    $weights    = [];
    foreach ($allDims as $d) {
        if (in_array($d, $dominant))  $weights[$d] = 0.8;
        elseif (in_array($d, $secondary)) $weights[$d] = 0.5;
        elseif (in_array($d, $absent))    $weights[$d] = 0.1;
        else                               $weights[$d] = 0.3;
    }
    return $weights;
}

// ── 6. Insert missing symptoms ────────────────────────────────────────────────
$insStmt = $db->prepare("
    INSERT OR IGNORE INTO kb_symptoms
        (symptom_code, name_vi, name_en, category, organ_system,
         bat_cuong_weights, yhct_clinical_note, yhhd_clinical_note,
         lay_descriptions_vi, context_summary_vi, red_flag_level, status)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
");

$aliasStmt = $db->prepare("
    INSERT OR IGNORE INTO symptom_aliases (symptom_code, alias, alias_type)
    VALUES (?,?,?)
");

$inserted = 0;
$skipped  = 0;

$db->beginTransaction();

foreach ($missing as $code => $info) {
    $nameVi   = codeToVietnamese($code, $viDict);
    $nameEn   = ucwords(str_replace('_', ' ', $code));
    $category = $info['category'];
    $organ    = $info['organ'];
    $batCArr  = $info['bat_cuong'];
    $batCW    = inferBatCuong(is_array($batCArr) ? $batCArr : []);

    try {
        $insStmt->execute([
            $code,
            $nameVi,
            $nameEn,
            $category,
            $organ,
            json_encode($batCW, JSON_UNESCAPED_UNICODE),
            null,  // yhct_clinical_note — to be filled later
            null,  // yhhd_clinical_note
            null,
            null,
            null,
            'active',
        ]);
        $inserted++;

        // Add aliases
        $aliasStmt->execute([$code, mb_strtolower($nameVi, 'UTF-8'), 'generated']);
        $aliasStmt->execute([$code, mb_strtolower($nameEn, 'UTF-8'), 'generated']);
    } catch (\Exception $e) {
        echo "  [ERROR] $code: " . $e->getMessage() . "\n";
        $skipped++;
    }
}

$db->commit();

echo "Inserted: $inserted\n";
echo "Skipped:  $skipped\n\n";

// ── 7. Verify ─────────────────────────────────────────────────────────────────
$totalSyms  = $db->query("SELECT COUNT(*) FROM kb_symptoms")->fetchColumn();
$totalAlias = $db->query("SELECT COUNT(*) FROM symptom_aliases")->fetchColumn();
echo "kb_symptoms total: $totalSyms\n";
echo "symptom_aliases total: $totalAlias\n";
