<?php
// Determine if we're on questions page or symptoms picker
$isQuestionsPage = $is_questions_page ?? false;

$stepLabels      = ['Mô tả', 'Làm rõ', 'Triệu chứng', 'Câu hỏi', 'Cận lâm sàng', 'Kết quả'];
$currentStep     = $isQuestionsPage ? 4 : 3;
$chiefComplaint  = $chief_complaint ?? '';
$selectedCodes   = $selected_codes  ?? [];
$selectedSymptoms= $selected_symptoms ?? [];
$groups          = $groups ?? ['group1'=>[], 'group2'=>[], 'group3'=>[], 'tang_names'=>[]];
$hypotheses      = $hypotheses ?? [];
$maxSymptoms     = $max_symptoms ?? 15;
$minForGroup3    = $min_for_group3 ?? 3;

// Tang icons
$tangIcons = [
    'can'  => '🌿', 'tam' => '❤️', 'ty' => '🌾', 'phe' => '🍃', 'than' => '💧',
    'vi'   => '🫁', 'dan' => '💛', 'tieu_truong' => '🔴', 'dai_truong' => '🟤',
    'bang_quang' => '🔵',
];
?>
<style>
.symptoms-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 1.5rem;
    min-height: calc(100vh - 130px);
}
@media (max-width: 991px) {
    .symptoms-layout {
        grid-template-columns: 1fr;
    }
    .sidebar-panel { display: none; }
}
.sidebar-panel {
    position: sticky;
    top: 70px;
    height: fit-content;
    max-height: calc(100vh - 90px);
    overflow-y: auto;
}
.symptom-card {
    border: 2px solid #E8F5E9;
    border-radius: 12px;
    padding: 10px 14px;
    cursor: pointer;
    transition: all 0.2s;
    background: #fff;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
}
.symptom-card:hover {
    border-color: #2E7D32;
    background: #F1F8E9;
}
.symptom-card.selected {
    border-color: #2E7D32;
    background: #E8F5E9;
}
.symptom-card.selected .symptom-checkbox { accent-color: #2E7D32; }
.symptom-card .symptom-checkbox {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    accent-color: #2E7D32;
    cursor: pointer;
}
.symptom-card.red-flag-sat {
    border-color: #FFCDD2;
}
.symptom-card.red-flag-sat:hover,
.symptom-card.red-flag-sat.selected {
    border-color: #EF5350;
    background: #FFF5F5;
}
.symptom-name {
    flex: 1;
    font-size: 0.875rem;
    font-weight: 500;
    color: #333;
    line-height: 1.3;
}
.symptom-tang-badge {
    font-size: 0.7rem;
    padding: 2px 7px;
    border-radius: 10px;
    background: #E8F5E9;
    color: #2E7D32;
    font-weight: 600;
    flex-shrink: 0;
}
.group-header {
    font-size: 0.9rem;
    font-weight: 700;
    color: #2E7D32;
    padding: 8px 0 6px;
    border-bottom: 2px solid #E8F5E9;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.section-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #E8F5E9;
    padding: 1.25rem;
    margin-bottom: 1rem;
}
.sticky-bottom-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    border-top: 2px solid #E8F5E9;
    padding: 12px 20px;
    z-index: 100;
    box-shadow: 0 -4px 16px rgba(0,0,0,0.08);
}
.selected-counter {
    font-size: 0.85rem;
    color: #666;
}
.selected-counter strong { color: #2E7D32; }
.hypothesis-card {
    background: linear-gradient(135deg, #E8F5E9, #F1F8E9);
    border: 1px solid #A5D6A7;
    border-radius: 12px;
    padding: 12px 14px;
    margin-bottom: 8px;
}
.hypothesis-name {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1B5E20;
    margin-bottom: 4px;
}
.hypothesis-bar {
    height: 6px;
    background: #C8E6C9;
    border-radius: 3px;
    overflow: hidden;
}
.hypothesis-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #2E7D32, #4CAF50);
    border-radius: 3px;
    transition: width 0.5s ease;
}
.selected-tags-area {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    min-height: 32px;
    padding: 8px;
    background: #F9F9F9;
    border-radius: 8px;
    border: 1px solid #eee;
}
.selected-tag {
    background: #2E7D32;
    color: #fff;
    border-radius: 12px;
    padding: 3px 10px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}
.selected-tag .remove-tag {
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.2s;
}
.selected-tag .remove-tag:hover { opacity: 1; }
.over-limit { border-color: #FF8F00 !important; }
.contradiction-alert {
    background: #FFF3E0;
    border: 1px solid #FFB300;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 0.8rem;
    display: none;
}
/* Questions page styles */
.question-card {
    background: #fff;
    border-radius: 16px;
    border: 2px solid #E8F5E9;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: border-color 0.2s;
}
.question-card:focus-within {
    border-color: #2E7D32;
    box-shadow: 0 0 0 3px rgba(46,125,50,0.08);
}
.question-label {
    font-size: 1rem;
    font-weight: 700;
    color: #1B5E20;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.option-btn {
    border: 2px solid #E8F5E9;
    background: #fff;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 0.85rem;
    color: #444;
    cursor: pointer;
    transition: all 0.15s;
    text-align: left;
    width: 100%;
    margin-bottom: 6px;
}
.option-btn:hover { border-color: #2E7D32; background: #F1F8E9; }
.option-btn.selected,
.option-btn:has(input[type="radio"]:checked) { border-color: #2E7D32; background: #E8F5E9; color: #1B5E20; font-weight: 600; }
</style>

<div class="py-3" style="background:#F9F7F4; padding-bottom: 80px !important;">
    <div class="container-fluid px-3 px-md-4">

        <!-- Progress Bar -->
        <div class="mb-3" style="max-width:600px; margin:0 auto;">
            <div class="progress-steps">
                <?php foreach ($stepLabels as $i => $label):
                    $stepNum = $i + 1;
                    $isDone  = $stepNum < $currentStep;
                    $isActive = $stepNum === $currentStep;
                ?>
                    <?php if ($i > 0): ?><div class="step-line <?= $isDone ? 'done' : '' ?>"></div><?php endif; ?>
                    <div class="progress-step <?= $isActive ? 'active' : ($isDone ? 'done' : '') ?>">
                        <div class="step-circle">
                            <?= $isDone ? '<i class="bi bi-check-lg"></i>' : $stepNum ?>
                        </div>
                        <div class="step-label"><?= $label ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ================================================================ -->
        <!-- SYMPTOMS PICKER -->
        <!-- ================================================================ -->
        <?php if (!$isQuestionsPage): ?>

        <form id="symptomsForm" method="POST" action="<?= BASE_URL ?>exam/symptoms">
            <div class="symptoms-layout">

                <!-- Sidebar -->
                <aside class="sidebar-panel">
                    <!-- Chief Complaint Summary -->
                    <div class="section-card mb-3">
                        <div class="group-header">
                            <i class="bi bi-chat-text"></i>Triệu chứng mô tả
                        </div>
                        <p class="text-muted small mb-0">
                            "<?= htmlspecialchars(mb_substr($chiefComplaint, 0, 120, 'UTF-8')) ?><?= mb_strlen($chiefComplaint, 'UTF-8') > 120 ? '...' : '' ?>"
                        </p>
                    </div>

                    <!-- Clarification answers summary -->
                    <?php
                    $clarify = $_SESSION['clarification_answers'] ?? [];
                    $labelMap = [
                        'q_duration'     => 'Thời gian',
                        'q_severity'     => 'Mức độ',
                        'q_pattern'      => 'Kiểu xuất hiện',
                        'q_location'     => 'Vị trí',
                        'q_skin_changes' => 'Thay đổi da',
                        'q_itch_timing'  => 'Ngứa nặng khi',
                        'q_cough_type'   => 'Tính chất ho',
                        'q_headache_type'=> 'Đặc điểm đau đầu',
                    ];
                    $durationMap = [
                        'lt1w'=>'< 1 tuần','1_4w'=>'1–4 tuần','1_3m'=>'1–3 tháng','gt3m'=>'> 3 tháng (mạn tính)',
                    ];
                    $severityMap = ['mild'=>'Nhẹ','moderate'=>'Vừa','severe'=>'Nặng'];
                    $patternMap  = ['constant'=>'Liên tục','episodic'=>'Từng đợt','seasonal'=>'Theo mùa','triggered'=>'Có kích phát'];
                    if (!empty($clarify)):
                    ?>
                    <div class="section-card mb-3" style="border-color:#C8E6C9;">
                        <div class="group-header">
                            <i class="bi bi-clipboard-check"></i>Thông tin đã khai báo
                            <a href="<?= BASE_URL ?>exam/clarify" class="ms-auto text-muted" style="font-size:.7rem; text-decoration:none;" title="Sửa lại">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                        <?php foreach ($clarify as $key => $val):
                            if (empty($val)) continue;
                            $label = $labelMap[$key] ?? $key;
                            // Human-readable value
                            $display = $val;
                            if ($key === 'q_duration') $display = $durationMap[$val] ?? $val;
                            if ($key === 'q_severity')  $display = $severityMap[$val] ?? $val;
                            if ($key === 'q_pattern')   $display = $patternMap[$val] ?? $val;
                        ?>
                        <div style="font-size:.78rem; margin-bottom:.3rem;">
                            <span class="text-muted"><?= htmlspecialchars($label) ?>:</span>
                            <span class="fw-semibold"> <?= htmlspecialchars($display) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Current Hypotheses -->
                    <?php if (!empty($hypotheses)): ?>
                    <div class="section-card mb-3">
                        <div class="group-header">
                            <i class="bi bi-graph-up"></i>Hướng chẩn đoán
                        </div>
                        <?php foreach (array_slice($hypotheses, 0, 3) as $hypo): ?>
                        <div class="hypothesis-card">
                            <div class="hypothesis-name">
                                <?= htmlspecialchars($hypo['pattern']['name_vi'] ?? 'Chứng không xác định') ?>
                                <span class="badge bg-<?= $hypo['confidence'] === 'high' ? 'success' : ($hypo['confidence'] === 'medium' ? 'warning' : 'secondary') ?> ms-1" style="font-size:0.65rem;">
                                    <?= $hypo['confidence'] === 'high' ? 'Cao' : ($hypo['confidence'] === 'medium' ? 'TB' : 'Thấp') ?>
                                </span>
                            </div>
                            <div class="hypothesis-bar">
                                <div class="hypothesis-bar-fill" style="width:<?= round($hypo['score'] * 100) ?>%"></div>
                            </div>
                            <div class="text-muted" style="font-size:0.7rem; margin-top:2px;">
                                <?= round($hypo['score'] * 100) ?>% khớp
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Selected tags -->
                    <div class="section-card">
                        <div class="group-header">
                            <i class="bi bi-check2-square"></i>Đã chọn (<span id="sidebarCount">0</span>)
                        </div>
                        <div class="selected-tags-area" id="selectedTagsArea">
                            <span class="text-muted small" id="noSelectionText">Chưa chọn triệu chứng nào</span>
                        </div>
                    </div>
                </aside>

                <!-- Main Area -->
                <div class="main-symptoms-area">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0" style="color:#1B5E20;">
                            <i class="bi bi-ui-checks me-2"></i>Chọn triệu chứng bạn đang gặp
                        </h5>
                        <span class="badge bg-light text-muted border" style="font-size:0.75rem;">
                            <span id="headerCount">0</span> đã chọn
                        </span>
                    </div>

                    <!-- Contradiction Alert -->
                    <div class="contradiction-alert mb-3" id="contradictionAlert">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                        <span id="contradictionText">Một số triệu chứng đã chọn có thể mâu thuẫn với nhau.</span>
                    </div>

                    <!-- Over-limit Warning -->
                    <div class="alert alert-warning py-2 d-none" id="overlimitAlert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Đã chọn hơn <?= $maxSymptoms ?> triệu chứng. Nhiều triệu chứng có thể làm giảm độ chính xác của kết quả.
                    </div>

                    <!-- Symptom Search -->
                    <div class="mb-3" id="symptomSearchWrap">
                        <div class="input-group">
                            <span class="input-group-text" style="background:#F1F8E9; border-color:#C8E6C9;">
                                <i class="bi bi-search text-success"></i>
                            </span>
                            <input type="text"
                                   id="symptom-search"
                                   class="form-control"
                                   placeholder="Tìm triệu chứng... (VD: đau đầu, mất ngủ)"
                                   style="border-color:#C8E6C9; border-left:none;">
                            <span class="input-group-text" style="background:#F1F8E9; border-color:#C8E6C9;">
                                <span id="semModelIndicator" class="badge bg-success" style="font-size:0.65rem; display:none;">
                                    <i class="bi bi-cpu me-1"></i>AI
                                </span>
                            </span>
                        </div>
                        <span id="semSearchBadge" class="badge bg-success mt-1" style="display:none; font-size:0.7rem;">
                            <i class="bi bi-stars me-1"></i>Kết quả AI đang hiển thị
                        </span>
                    </div>

                    <!-- ===== GROUP 1: Direct / Anchor ===== -->
                    <?php if (!empty($groups['group1'])): ?>
                    <div class="section-card">
                        <div class="group-header">
                            <i class="bi bi-star-fill text-warning"></i>
                            Nhóm 1: Triệu chứng liên quan trực tiếp
                            <span class="badge bg-warning text-dark ms-auto"><?= count($groups['group1']) ?> triệu chứng</span>
                        </div>
                        <div class="row g-2">
                            <?php foreach ($groups['group1'] as $item): ?>
                            <?php $code = $item['code']; $sym = $item['symptom']; ?>
                            <div class="col-12 col-md-6">
                                <label class="symptom-card <?= in_array($code, $selectedCodes, true) ? 'selected' : '' ?> <?= $item['is_red_flag_satellite'] ?? false ? 'red-flag-sat' : '' ?>"
                                       data-code="<?= htmlspecialchars($code) ?>"
                                       data-name="<?= htmlspecialchars($sym['name_vi'] ?? $code) ?>">
                                    <input type="checkbox" name="selected_codes[]" value="<?= htmlspecialchars($code) ?>" class="symptom-checkbox" <?= in_array($code, $selectedCodes, true) ? 'checked' : '' ?>>
                                    <span class="symptom-name"><?= htmlspecialchars($sym['name_vi'] ?? $code) ?></span>
                                    <?php if (!empty($sym['tang'])): ?>
                                    <span class="symptom-tang-badge">
                                        <?= $tangIcons[$sym['tang']] ?? '🔵' ?> <?= htmlspecialchars($groups['tang_names'][$sym['tang']] ?? $sym['tang']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($item['is_red_flag_satellite'] ?? false): ?>
                                    <span class="badge bg-danger ms-1" style="font-size:0.65rem;" title="Triệu chứng cảnh báo">⚠️</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ===== GROUP 2: By Organ System ===== -->
                    <?php if (!empty($groups['group2'])): ?>
                    <div class="section-card">
                        <div class="group-header">
                            <i class="bi bi-grid-3x3-gap"></i>
                            Nhóm 2: Triệu chứng theo tạng phủ
                        </div>

                        <!-- Organ Tabs -->
                        <ul class="nav nav-pills mb-3 flex-wrap gap-1" id="tangTabs" role="tablist">
                            <?php $firstTang = true; foreach ($groups['group2'] as $tang => $items): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-1 px-2 <?= $firstTang ? 'active' : '' ?>"
                                        style="font-size:0.8rem;"
                                        id="tab-<?= $tang ?>"
                                        data-bs-toggle="pill"
                                        data-bs-target="#panel-<?= $tang ?>"
                                        type="button">
                                    <?= $tangIcons[$tang] ?? '🔵' ?>
                                    <?= htmlspecialchars($groups['tang_names'][$tang] ?? $tang) ?>
                                    <span class="badge bg-light text-dark ms-1"><?= count($items) ?></span>
                                </button>
                            </li>
                            <?php $firstTang = false; endforeach; ?>
                        </ul>

                        <div class="tab-content" id="tangTabContent">
                            <?php $firstTang = true; foreach ($groups['group2'] as $tang => $items): ?>
                            <div class="tab-pane fade <?= $firstTang ? 'show active' : '' ?>"
                                 id="panel-<?= $tang ?>"
                                 role="tabpanel">
                                <div class="row g-2">
                                    <?php foreach ($items as $item): ?>
                                    <?php $code = $item['code']; $sym = $item['symptom']; ?>
                                    <div class="col-12 col-md-6">
                                        <label class="symptom-card <?= in_array($code, $selectedCodes, true) ? 'selected' : '' ?>"
                                               data-code="<?= htmlspecialchars($code) ?>"
                                               data-name="<?= htmlspecialchars($sym['name_vi'] ?? $code) ?>">
                                            <input type="checkbox" name="selected_codes[]" value="<?= htmlspecialchars($code) ?>" class="symptom-checkbox" <?= in_array($code, $selectedCodes, true) ? 'checked' : '' ?>>
                                            <span class="symptom-name"><?= htmlspecialchars($sym['name_vi'] ?? $code) ?></span>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php $firstTang = false; endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ===== GROUP 3: Differentiating (only after MIN_FOR_GROUP3) ===== -->
                    <div id="group3Section" class="<?= count($selectedCodes) >= $minForGroup3 ? '' : 'd-none' ?>">
                        <?php if (!empty($groups['group3'])): ?>
                        <div class="section-card">
                            <div class="group-header">
                                <i class="bi bi-search"></i>
                                Nhóm 3: Câu hỏi phân biệt
                                <span class="badge bg-info text-dark ms-1" style="font-size:0.7rem;">
                                    Giúp phân biệt chứng bệnh
                                </span>
                            </div>
                            <p class="text-muted small mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Những triệu chứng này giúp phân biệt chính xác giữa các hướng chẩn đoán.
                            </p>
                            <div class="row g-2">
                                <?php foreach ($groups['group3'] as $item): ?>
                                <?php $code = $item['code']; $sym = $item['symptom']; ?>
                                <div class="col-12 col-md-6">
                                    <label class="symptom-card <?= in_array($code, $selectedCodes, true) ? 'selected' : '' ?>"
                                           data-code="<?= htmlspecialchars($code) ?>"
                                           data-name="<?= htmlspecialchars($sym['name_vi'] ?? $code) ?>"
                                           style="border-style:dashed;">
                                        <input type="checkbox" name="selected_codes[]" value="<?= htmlspecialchars($code) ?>" class="symptom-checkbox" <?= in_array($code, $selectedCodes, true) ? 'checked' : '' ?>>
                                        <span class="symptom-name">
                                            <?= htmlspecialchars($sym['name_vi'] ?? $code) ?>
                                            <?php if (!empty($item['present_in'])): ?>
                                            <small class="d-block text-muted" style="font-size:0.7rem;">
                                                Gợi ý: <?= htmlspecialchars(implode(', ', array_slice($item['present_in'], 0, 2))) ?>
                                            </small>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </form>

        <!-- ================================================================ -->
        <!-- QUICK QUESTIONS PAGE -->
        <!-- ================================================================ -->
        <?php else: ?>

        <div style="max-width:640px; margin:0 auto;">
            <div class="text-center mb-4">
                <h4 class="fw-bold" style="color:#1B5E20;">
                    <i class="bi bi-question-circle me-2"></i>5 Câu Hỏi Nhanh
                </h4>
                <p class="text-muted small">Trả lời để giúp hệ thống biện chứng chính xác hơn</p>
            </div>

            <form id="questionsForm" method="POST" action="<?= BASE_URL ?>exam/questions">
                <!-- Q1: Onset -->
                <div class="question-card">
                    <div class="question-label">
                        <span class="badge bg-success rounded-circle">1</span>
                        Triệu chứng bắt đầu từ khi nào và khởi phát như thế nào?
                    </div>
                    <div class="row g-2">
                        <?php foreach (['Đột ngột (cấp tính)','Dần dần (từ từ)','Tái đi tái lại','Mãn tính (lâu dài)'] as $opt): ?>
                        <div class="col-6 col-md-6">
                            <label class="option-btn">
                                <input type="radio" name="onset" value="<?= htmlspecialchars($opt) ?>" style="position:absolute;opacity:0;pointer-events:none;">
                                <?= htmlspecialchars($opt) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Q2: Trajectory -->
                <div class="question-card">
                    <div class="question-label">
                        <span class="badge bg-success rounded-circle">2</span>
                        Xu hướng thay đổi của triệu chứng gần đây?
                    </div>
                    <div class="row g-2">
                        <?php foreach (['Nặng hơn dần','Ổn định / không đổi','Nhẹ hơn dần','Lúc nặng lúc nhẹ'] as $opt): ?>
                        <div class="col-6">
                            <label class="option-btn">
                                <input type="radio" name="trajectory" value="<?= htmlspecialchars($opt) ?>" style="position:absolute;opacity:0;pointer-events:none;">
                                <?= htmlspecialchars($opt) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Q3: Tongue -->
                <div class="question-card">
                    <div class="question-label">
                        <span class="badge bg-success rounded-circle">3</span>
                        Quan sát lưỡi của bạn (nếu có thể)
                        <a href="#" data-bs-toggle="collapse" data-bs-target="#tongueHelp"
                           style="font-size:0.75rem; margin-left:6px;">
                            <i class="bi bi-question-circle"></i> Hướng dẫn
                        </a>
                    </div>
                    <div class="collapse mb-2" id="tongueHelp">
                        <div class="alert alert-light py-2 small">
                            <strong>Cách quan sát lưỡi:</strong> Ra ngoài lưỡi tự nhiên dưới ánh sáng tốt.
                            Nhìn màu thân lưỡi và rêu lưỡi.
                        </div>
                    </div>
                    <div class="row g-2">
                        <?php foreach ([
                            'Lưỡi nhợt, rêu trắng mỏng',
                            'Lưỡi đỏ, rêu vàng',
                            'Lưỡi bệu (dấu răng), rêu dày',
                            'Lưỡi đỏ không rêu (hoặc rêu ít)',
                            'Không chắc / không quan sát được',
                        ] as $opt): ?>
                        <div class="col-12 col-md-6">
                            <label class="option-btn">
                                <input type="radio" name="tongue" value="<?= htmlspecialchars($opt) ?>" style="position:absolute;opacity:0;pointer-events:none;">
                                <?= htmlspecialchars($opt) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Q4: Medications -->
                <div class="question-card">
                    <div class="question-label">
                        <span class="badge bg-success rounded-circle">4</span>
                        Bạn đang dùng thuốc gì không? (để kiểm tra tương tác)
                    </div>
                    <input type="text"
                           name="medications"
                           id="medications"
                           class="form-control"
                           placeholder="Ví dụ: aspirin, huyết áp, metformin, kháng sinh... hoặc để trống nếu không"
                           style="border-radius:8px; border:2px solid #E8F5E9;">
                    <small class="text-muted">Viết tên thuốc hoặc tên bệnh đang điều trị. Để trống nếu không dùng thuốc.</small>
                </div>

                <!-- Q5: Emotion -->
                <div class="question-card">
                    <div class="question-label">
                        <span class="badge bg-success rounded-circle">5</span>
                        Trong thời gian gần đây, cảm xúc và tinh thần của bạn?
                    </div>
                    <div class="row g-2">
                        <?php foreach ([
                            'Bình thường, không thay đổi',
                            'Nhiều stress, lo lắng',
                            'Hay cáu giận, tức giận',
                            'Buồn bã, ít nói',
                            'Sợ hãi, hồi hộp',
                        ] as $opt): ?>
                        <div class="col-12 col-md-6">
                            <label class="option-btn">
                                <input type="radio" name="emotion" value="<?= htmlspecialchars($opt) ?>" style="position:absolute;opacity:0;pointer-events:none;">
                                <?= htmlspecialchars($opt) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Age / Sex / Pregnancy -->
                <div class="question-card">
                    <div class="question-label">
                        <span class="badge bg-secondary rounded-circle">+</span>
                        Thông tin thêm (tùy chọn)
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tuổi</label>
                            <input type="number" name="age" min="1" max="120" class="form-control"
                                   style="border-radius:8px;" placeholder="ví dụ: 35">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Giới tính</label>
                            <select name="sex" class="form-select" style="border-radius:8px;">
                                <option value="">Không chỉ định</option>
                                <option value="M">Nam</option>
                                <option value="F">Nữ</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Thai kỳ</label>
                            <select name="pregnancy" class="form-select" style="border-radius:8px;">
                                <option value="unknown">Không rõ</option>
                                <option value="no">Không mang thai</option>
                                <option value="yes">Đang mang thai</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success btn-lg px-5 rounded-pill" id="btnSubmitQuestions">
                        <i class="bi bi-cpu me-2"></i>Xem kết quả phân tích
                    </button>
                    <div class="mt-2">
                        <a href="<?= BASE_URL ?>exam/symptoms" class="text-muted small">
                            <i class="bi bi-arrow-left me-1"></i>Quay lại chọn triệu chứng
                        </a>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Sticky Bottom Bar (symptoms picker only) -->
<?php if (!$isQuestionsPage): ?>
<div class="sticky-bottom-bar d-flex align-items-center justify-content-between">
    <div>
        <div class="selected-counter">
            Đã chọn: <strong id="bottomCount">0</strong> triệu chứng
            <span class="text-muted">(tối thiểu 1, khuyến khích 3-10)</span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>exam/start" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="bi bi-arrow-left me-1"></i>Quay lại
        </a>
        <button type="button" class="btn btn-success rounded-pill" id="btnContinue" disabled>
            <i class="bi bi-arrow-right me-1"></i>Tiếp tục
            <span class="badge bg-white text-success ms-1" id="btnCountBadge">0</span>
        </button>
    </div>
</div>
<?php endif; ?>

<script>
// ============================================================
// SYMPTOM PICKER LOGIC
// ============================================================
var selectedCodes = <?= json_encode($selectedCodes, JSON_UNESCAPED_UNICODE) ?>;

// Wire up checkbox change events — label click triggers this natively
document.querySelectorAll('.symptom-checkbox').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var label = this.closest('.symptom-card');
        if (!label) return;
        var code = label.dataset.code;
        var idx  = selectedCodes.indexOf(code);
        if (this.checked && idx === -1) {
            selectedCodes.push(code);
        } else if (!this.checked && idx !== -1) {
            selectedCodes.splice(idx, 1);
        }
        label.classList.toggle('selected', this.checked);
        updateUI();
        debouncedRank();
        debouncedContraCheck();
    });
});

function updateUI() {
    var count = selectedCodes.length;

    // Update counters
    document.querySelectorAll('#sidebarCount,#headerCount,#bottomCount').forEach(function(el) {
        el.textContent = count;
    });
    var badge = document.getElementById('btnCountBadge');
    if (badge) badge.textContent = count;

    // Enable/disable continue button
    var btn = document.getElementById('btnContinue');
    if (btn) btn.disabled = count === 0;

    // Over-limit warning
    var overlimit = document.getElementById('overlimitAlert');
    if (overlimit) overlimit.classList.toggle('d-none', count <= <?= $maxSymptoms ?>);

    // Show group3 after min threshold
    var g3 = document.getElementById('group3Section');
    if (g3) g3.classList.toggle('d-none', count < <?= $minForGroup3 ?>);

    // Update sidebar tags
    updateSidebarTags();

    // Sync card states
    document.querySelectorAll('.symptom-card').forEach(function(c) {
        var code = c.dataset.code;
        var sel  = selectedCodes.indexOf(code) !== -1;
        c.classList.toggle('selected', sel);
        var cb = c.querySelector('.symptom-checkbox');
        if (cb) cb.checked = sel;
    });
}

function updateSidebarTags() {
    var area   = document.getElementById('selectedTagsArea');
    var noText = document.getElementById('noSelectionText');
    if (!area) return;

    if (selectedCodes.length === 0) {
        area.innerHTML = '<span class="text-muted small" id="noSelectionText">Chưa chọn triệu chứng nào</span>';
        return;
    }

    var html = '';
    selectedCodes.forEach(function(code) {
        var card = document.querySelector('[data-code="' + code + '"]');
        var name = card ? card.dataset.name : code;
        html += '<span class="selected-tag">' + escapeHtml(name) +
                ' <span class="remove-tag" onclick="removeTag(\'' + escapeHtml(code) + '\')">×</span></span>';
    });
    area.innerHTML = html;
}

function removeTag(code) {
    var label = document.querySelector('[data-code="' + code + '"]');
    if (!label) return;
    var cb = label.querySelector('.symptom-checkbox');
    if (cb && cb.checked) {
        cb.checked = false;
        cb.dispatchEvent(new Event('change'));
    }
}

function escapeHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function submitSymptoms() {
    if (selectedCodes.length === 0) return;
    document.getElementById('symptomsForm').submit();
}

// Debounced re-ranking via AJAX
var rankTimer = null;
function debouncedRank() {
    clearTimeout(rankTimer);
    rankTimer = setTimeout(doRank, 800);
}

function doRank() {
    if (selectedCodes.length === 0) return;

    fetch('<?= BASE_URL ?>exam/api_rank', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            selected_codes: selectedCodes,
            context_flags: {},
            context_triggers: []
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        // Could update scores/ordering here
        // For now, just log
    })
    .catch(function() {});
}

// Debounced contradiction check
var contraTimer = null;
function debouncedContraCheck() {
    clearTimeout(contraTimer);
    contraTimer = setTimeout(doContraCheck, 1200);
}

function doContraCheck() {
    if (selectedCodes.length < 2) return;

    fetch('<?= BASE_URL ?>exam/api_check_contradiction', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({selected_codes: selectedCodes})
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var alert = document.getElementById('contradictionAlert');
        if (data.has_contradiction && data.contradictions.length > 0) {
            var text = data.contradictions.map(function(c) { return c.message; }).join('; ');
            document.getElementById('contradictionText').textContent = text;
            alert.style.display = 'block';
        } else {
            alert.style.display = 'none';
        }
    })
    .catch(function() {});
}

// Init
updateUI();


// Also wire up the "Tiếp tục" button
var btnContinue = document.getElementById('btnContinue');
if (btnContinue) {
    btnContinue.addEventListener('click', submitSymptoms);
}

// Questions form submit handler
var qForm = document.getElementById('questionsForm');
if (qForm) {
    qForm.addEventListener('submit', function() {
        var btn = document.getElementById('btnSubmitQuestions');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang phân tích...';
        }
    });
}
</script>
