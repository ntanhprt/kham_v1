<?php
require_once APP_ROOT . '/core/disease_vi.php';

$result         = $result         ?? [];
$triageLevel    = $triage_level   ?? ($result['triage_level'] ?? null);
$primaryPattern = $primary_pattern ?? ($result['primary_pattern'] ?? null);
$yhctSuppressed = $yhct_suppressed ?? ($result['yhct_suppressed'] ?? false);
$chungRanked    = $result['chung_ranked']    ?? [];
$batCuong       = $result['bat_cuong']       ?? [];
$organSystems   = $result['organ_systems']   ?? [];
$redFlags       = $result['red_flags']       ?? [];
$drugWarnings   = $result['drug_warnings']   ?? [];
$phapTri        = $result['phap_tri']        ?? [];
$kiemChung      = $result['kiem_chung']      ?? [];
$followUp       = $result['follow_up']       ?? [];
$clusters       = $result['clusters']        ?? [];
$paraclinical   = $result['paraclinical']    ?? [];
$clinicalAdj    = $result['clinical_adjustments'] ?? [];
$disclaimer     = $result['yhct_disclaimer'] ?? '';
$chiefComplaint = $chief_complaint ?? '';

// Confidence badge
$confidenceMap = ['high' => ['Cao','success'], 'medium' => ['Trung bình','warning'], 'low' => ['Thấp','secondary']];
$topConf       = !empty($chungRanked) ? ($chungRanked[0]['confidence'] ?? 'low') : 'low';

// Level colors
$levelClass = match($triageLevel) {
    'L1_emergency' => 'danger',
    'L2_urgent'    => 'warning',
    'L3_watch'     => 'info',
    default        => null,
};
?>
<style>
.result-layout {
    max-width: 840px;
    margin: 0 auto;
}
.result-section {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #E8F5E9;
    padding: 1.5rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 2px 8px rgba(46,125,50,0.05);
}
.result-section-title {
    font-size: 0.9rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #888;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}
.pattern-hero {
    background: linear-gradient(135deg, #1B5E20, #2E7D32);
    color: #fff;
    border-radius: 16px;
    padding: 1.75rem;
    margin-bottom: 1.25rem;
}
.pattern-name {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.2;
}
/* Bat Cuong bars */
.bc-bar-container {
    margin-bottom: 10px;
}
.bc-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 3px;
}
.bc-bar-track {
    height: 12px;
    background: #eee;
    border-radius: 6px;
    overflow: hidden;
    position: relative;
}
.bc-bar-left {
    position: absolute;
    right: 50%;
    height: 100%;
    border-radius: 6px 0 0 6px;
    transition: width 0.8s ease;
}
.bc-bar-right {
    position: absolute;
    left: 50%;
    height: 100%;
    border-radius: 0 6px 6px 0;
    transition: width 0.8s ease;
}
.bc-mid-line {
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #fff;
    z-index: 2;
}
/* Organ system */
.organ-bar {
    height: 8px;
    background: #E8F5E9;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 4px;
}
.organ-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #2E7D32, #66BB6A);
    border-radius: 4px;
    transition: width 0.8s ease;
}
/* Red flag alert */
.red-flag-emergency {
    background: #FFEBEE;
    border: 2px solid #EF5350;
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    margin-bottom: 1.25rem;
}
.red-flag-urgent {
    background: #FFF8E1;
    border: 2px solid #FFB300;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
}
/* Drug warning */
.drug-warning-card {
    border-left: 4px solid #FF8F00;
    background: #FFF3E0;
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 8px;
    font-size: 0.875rem;
}
.drug-warning-card.severity-high { border-color: #EF5350; background: #FFEBEE; }
.drug-warning-card.severity-medium { border-color: #FF8F00; background: #FFF3E0; }
.drug-warning-card.severity-low { border-color: #42A5F5; background: #E3F2FD; }
/* Cluster badge */
.cluster-badge {
    background: #E3F2FD;
    border: 1px solid #90CAF9;
    border-radius: 8px;
    padding: 8px 12px;
    margin-bottom: 6px;
    font-size: 0.85rem;
}
/* Print */
@media print {
    .no-print { display: none !important; }
    .result-section { break-inside: avoid; }
}
/* Confidence dots */
.conf-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 2px;
}
</style>

<div class="py-4" style="background:#F9F7F4; min-height:calc(100vh - 120px);">
    <div class="container result-layout">

        <!-- Progress Steps -->
        <div class="mb-4" style="max-width:500px; margin:0 auto 1.5rem;">
            <div class="progress-steps">
                <?php foreach (['Mô tả','Triệu chứng','Câu hỏi','Kết quả'] as $i => $lbl):
                    $n = $i + 1;
                ?>
                    <?php if ($i > 0): ?><div class="step-line done"></div><?php endif; ?>
                    <div class="progress-step done">
                        <div class="step-circle"><i class="bi bi-check-lg"></i></div>
                        <div class="step-label"><?= $lbl ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- L1 EMERGENCY: Full screen alert, suppress YHCT -->
        <!-- ============================================================ -->
        <?php if ($triageLevel === 'L1_emergency'): ?>
        <div class="red-flag-emergency">
            <div style="font-size:4rem; margin-bottom:0.5rem;">🚨</div>
            <h2 class="fw-bold text-danger mb-2">CẤP CỨU - GỌI 115 NGAY</h2>
            <p class="lead mb-3">
                Triệu chứng của bạn có dấu hiệu <strong>nguy hiểm</strong>.
                Đừng chờ đợi - hãy gọi <strong>115</strong> hoặc đến phòng cấp cứu ngay lập tức.
            </p>
            <?php foreach ($redFlags as $rf): if ($rf['level'] === 'L1_emergency'): ?>
            <div class="alert alert-danger text-start d-inline-block mb-2">
                <strong><?= htmlspecialchars($rf['name_vi']) ?>:</strong>
                <?= htmlspecialchars($rf['action'] ?: $rf['description']) ?>
            </div>
            <?php endif; endforeach; ?>
            <div class="mt-3">
                <a href="tel:115" class="btn btn-danger btn-lg me-2">
                    <i class="bi bi-telephone-fill me-2"></i>Gọi 115
                </a>
                <a href="<?= BASE_URL ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-house me-1"></i>Trang chủ
                </a>
            </div>
            <p class="text-muted small mt-3 mb-0">
                <?= htmlspecialchars($disclaimer) ?>
            </p>
        </div>
        <?php return; // Stop rendering for L1 ?>

        <!-- ============================================================ -->
        <!-- L2 URGENT: Warning banner, then show limited YHCT -->
        <!-- ============================================================ -->
        <?php elseif ($triageLevel === 'L2_urgent'): ?>
        <div class="red-flag-urgent">
            <div class="d-flex align-items-start gap-3">
                <div style="font-size:2rem;">⚠️</div>
                <div>
                    <h5 class="fw-bold text-warning mb-1">Cần khám bác sĩ sớm</h5>
                    <p class="mb-2">Một số triệu chứng cần được đánh giá y tế trong vòng 24 giờ.</p>
                    <?php foreach ($redFlags as $rf): if ($rf['level'] === 'L2_urgent'): ?>
                    <div class="small mb-1">
                        <strong class="text-warning"><?= htmlspecialchars($rf['name_vi']) ?>:</strong>
                        <?= htmlspecialchars($rf['action'] ?: $rf['description']) ?>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- L3 Watch flags (if any) -->
        <?php if (!empty($redFlags) && $triageLevel === 'L3_watch'): ?>
        <div class="alert alert-info d-flex gap-3 align-items-start mb-3">
            <i class="bi bi-info-circle-fill fs-5 mt-1"></i>
            <div>
                <strong>Lưu ý theo dõi:</strong>
                <?php foreach ($redFlags as $rf): ?>
                <div class="small mt-1"><?= htmlspecialchars($rf['name_vi']) ?>: <?= htmlspecialchars($rf['action'] ?? '') ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ① PRIMARY PATTERN SUMMARY -->
        <?php if ($primaryPattern): ?>
        <div class="pattern-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <div class="small text-white-50 mb-1">① Chứng bệnh chính</div>
                    <div class="pattern-name mb-1">
                        <?= htmlspecialchars($primaryPattern['name_vi'] ?? 'Chứng không xác định') ?>
                    </div>
                    <?php if (!empty($primaryPattern['name_en'])): ?>
                    <div class="small text-white-75 mb-2 fst-italic">
                        Tên khoa học: <?= htmlspecialchars($primaryPattern['name_en']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($primaryPattern['clinical_note'])): ?>
                    <p class="mb-0 small text-white-75"><?= htmlspecialchars($primaryPattern['clinical_note']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="text-end">
                    <?php
                    [$confLabel, $confColor] = $confidenceMap[$topConf] ?? ['Thấp','secondary'];
                    $score = !empty($chungRanked) ? round($chungRanked[0]['score'] * 100) : 0;
                    ?>
                    <div class="badge bg-<?= $confColor ?> fs-6 mb-1 d-block">
                        Độ tin cậy: <?= $confLabel ?>
                    </div>
                    <div class="text-white-75 small"><?= $score ?>% khớp</div>
                    <?php if (!empty($primaryPattern['primary_tang'])): ?>
                    <div class="mt-2 small text-white-50">
                        <i class="bi bi-heart me-1"></i>Tạng chủ: <?= htmlspecialchars($primaryPattern['primary_tang']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ② YHCT ANALYSIS -->
        <?php if (!$yhctSuppressed): ?>
        <div class="result-section">
            <div class="result-section-title">
                <i class="bi bi-clipboard2-data text-success"></i> ② Phân tích Y Học Cổ Truyền
            </div>

            <!-- Bát Cương -->
            <?php if (!empty($batCuong)): ?>
            <div class="mb-4">
                <h6 class="fw-bold text-success mb-3">
                    <i class="bi bi-yin-yang me-2"></i>Bát Cương (Tám cương lĩnh biện chứng)
                </h6>
                <?php
                $bcPairs = [
                    ['Âm', 'Dương', 'yin', 'yang',             '#3F51B5', '#FF5722'],
                    ['Lý (Nội)',  'Biểu (Ngoại)', 'interior', 'exterior', '#00796B', '#F57F17'],
                    ['Hàn (Lạnh)', 'Nhiệt (Nóng)', 'cold',   'heat',     '#1565C0', '#C62828'],
                    ['Hư', 'Thực', 'deficiency', 'excess',    '#6A1B9A', '#558B2F'],
                ];
                foreach ($bcPairs as [$labelA, $labelB, $keyA, $keyB, $colorA, $colorB]):
                    $valA = (float)($batCuong[$keyA] ?? 0.5);
                    $valB = (float)($batCuong[$keyB] ?? 0.5);
                    $pctA = round($valA * 50); // max 50%
                    $pctB = round($valB * 50);
                ?>
                <div class="bc-bar-container">
                    <div class="bc-labels">
                        <span style="color:<?= $colorA ?>">
                            <?= $labelA ?> (<?= round($valA * 100) ?>%)
                        </span>
                        <span style="color:<?= $colorB ?>">
                            <?= $labelB ?> (<?= round($valB * 100) ?>%)
                        </span>
                    </div>
                    <div class="bc-bar-track">
                        <div class="bc-mid-line"></div>
                        <div class="bc-bar-left" style="width:<?= $pctA ?>%; background:<?= $colorA ?>; opacity:0.7;"></div>
                        <div class="bc-bar-right" style="width:<?= $pctB ?>%; background:<?= $colorB ?>; opacity:0.7;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Pháp Trị -->
            <?php if (!empty($phapTri)): ?>
            <div class="mb-4">
                <h6 class="fw-bold text-success mb-2">
                    <i class="bi bi-journal-medical me-2"></i>Pháp trị (Nguyên tắc điều trị)
                </h6>
                <?php if (is_array($phapTri)): ?>
                    <?php if (!empty($phapTri['principle'])): ?>
                    <div class="p-3 rounded" style="background:#E8F5E9; font-size:0.95rem;">
                        <strong>Nguyên tắc:</strong> <?= htmlspecialchars($phapTri['principle']) ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($phapTri['phuong_thuoc'])): ?>
                    <div class="mt-3">
                        <strong class="text-success">Phương thuốc tham khảo:</strong>
                        <ul class="text-muted small mt-1 mb-0 ps-3">
                        <?php
                        $pt = is_array($phapTri['phuong_thuoc']) ? $phapTri['phuong_thuoc'] : [$phapTri['phuong_thuoc']];
                        foreach ($pt as $item): ?>
                            <li><?= htmlspecialchars($item) ?></li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($phapTri['huyet_vi'])): ?>
                    <div class="mt-3">
                        <strong class="text-success">Huyệt vị tham khảo:</strong>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                        <?php
                        $hv = is_array($phapTri['huyet_vi']) ? $phapTri['huyet_vi'] : [$phapTri['huyet_vi']];
                        foreach ($hv as $huyet): ?>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($huyet) ?></span>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($phapTri['key_questions'])): ?>
                    <?php
                    $kq = $phapTri['key_questions'];
                    $tongue = is_array($kq) ? ($kq['tongue'] ?? null) : null;
                    $pulse  = is_array($kq) ? ($kq['pulse']  ?? null) : null;
                    ?>
                    <?php if ($tongue || $pulse): ?>
                    <div class="mt-3 p-3 rounded" style="background:#F3E5F5; border-left:3px solid #9C27B0;">
                        <strong style="color:#6A1B9A;">Lưỡi &amp; Mạch đặc trưng (Vọng/Thiết chẩn):</strong>
                        <?php if ($tongue): ?>
                        <div class="small mt-2">
                            <i class="bi bi-circle-fill me-1" style="color:#9C27B0; font-size:0.5rem; vertical-align:middle;"></i>
                            <strong>Lưỡi:</strong> <?= htmlspecialchars($tongue) ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($pulse): ?>
                        <div class="small mt-1">
                            <i class="bi bi-circle-fill me-1" style="color:#9C27B0; font-size:0.5rem; vertical-align:middle;"></i>
                            <strong>Mạch:</strong> <?= htmlspecialchars($pulse) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($phapTri['clinical_note'])): ?>
                    <div class="mt-3 p-2 rounded" style="background:#F9FBE7; border-left:3px solid #C6E900; font-size:0.85rem; color:#555;">
                        <i class="bi bi-lightbulb me-1 text-warning"></i>
                        <strong>Lâm sàng:</strong> <?= htmlspecialchars($phapTri['clinical_note']) ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($phapTri['life_advice'])): ?>
                    <div class="mt-3 p-3 rounded" style="background:#FFF8E1; border-left:3px solid #FFB300;">
                        <strong>Lời khuyên sinh hoạt:</strong>
                        <p class="mb-0 small mt-1"><?= nl2br(htmlspecialchars($phapTri['life_advice'])) ?></p>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                <p class="text-muted small"><?= htmlspecialchars((string)$phapTri) ?></p>
                <?php endif; ?>
            </div>
            <?php elseif ($primaryPattern && !empty($primaryPattern['phap_tri'])): ?>
            <div class="mb-4">
                <h6 class="fw-bold text-success mb-2">
                    <i class="bi bi-journal-medical me-2"></i>Pháp trị
                </h6>
                <div class="p-3 rounded" style="background:#E8F5E9;">
                    <?= nl2br(htmlspecialchars($primaryPattern['phap_tri'])) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ③ TẠNG PHỦ (Organ Systems) -->
        <?php if (!empty($organSystems)): ?>
        <div class="result-section">
            <div class="result-section-title">
                <i class="bi bi-diagram-3 text-success"></i> ③ Tạng phủ liên quan
            </div>
            <?php
            $tangNames = [
                'can'=>'Can (Gan)','tam'=>'Tâm (Tim)','ty'=>'Tỳ (Lách)',
                'phe'=>'Phế (Phổi)','than'=>'Thận','vi'=>'Vị (Dạ Dày)',
                'dan'=>'Đởm (Mật)','tieu_truong'=>'Tiểu Trường',
                'dai_truong'=>'Đại Trường','bang_quang'=>'Bàng Quang',
            ];
            $tangIcons = [
                'can'=>'🌿','tam'=>'❤️','ty'=>'🌾','phe'=>'🍃','than'=>'💧',
                'vi'=>'🫁','dan'=>'💛','tieu_truong'=>'🔴','dai_truong'=>'🟤','bang_quang'=>'🔵',
            ];
            arsort($organSystems);
            foreach ($organSystems as $tang => $score): ?>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span style="width:130px; font-size:0.85rem; font-weight:500;">
                    <?= $tangIcons[$tang] ?? '🔵' ?> <?= htmlspecialchars($tangNames[$tang] ?? $tang) ?>
                </span>
                <div class="flex-grow-1">
                    <div class="organ-bar">
                        <div class="organ-bar-fill" style="width:<?= round($score * 100) ?>%"></div>
                    </div>
                </div>
                <span class="text-muted small" style="width:40px; text-align:right;"><?= round($score * 100) ?>%</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ④ KIÊM CHỨNG (Co-existing patterns) -->
        <?php if (count($chungRanked) > 1): ?>
        <div class="result-section">
            <div class="result-section-title">
                <i class="bi bi-layers text-success"></i> ④ Các chứng kèm theo
            </div>
            <?php foreach (array_slice($chungRanked, 1, 4) as $match): ?>
            <?php
            $pat  = $match['pattern'];
            $s    = round($match['score'] * 100);
            [$cl, $cc] = $confidenceMap[$match['confidence']] ?? ['Thấp','secondary'];
            ?>
            <div class="d-flex align-items-center gap-3 mb-2 p-2 rounded" style="background:#F9F9F9; border:1px solid #eee;">
                <div class="flex-grow-1">
                    <div class="fw-semibold small"><?= htmlspecialchars($pat['name_vi'] ?? 'Chứng không rõ') ?></div>
                    <?php if (!empty($pat['primary_tang'])): ?>
                    <div class="text-muted" style="font-size:0.72rem;">Tạng: <?= htmlspecialchars($pat['primary_tang']) ?></div>
                    <?php endif; ?>
                </div>
                <span class="badge bg-<?= $cc ?>"><?= $s ?>%</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- K08 Kiêm chứng validation -->
        <?php if (!empty($kiemChung)): ?>
        <div class="result-section">
            <div class="result-section-title">
                <i class="bi bi-check2-circle text-info"></i> Kiểm chứng kết hợp (K08)
            </div>
            <?php foreach ($kiemChung as $kc): ?>
            <div class="small mb-1">
                <span class="badge bg-<?= ($kc['compatibility'] ?? '') === 'compatible' ? 'success' : 'warning' ?> me-2">
                    <?= ($kc['compatibility'] ?? '') === 'compatible' ? 'Tương thích' : 'Cần xem lại' ?>
                </span>
                <?= htmlspecialchars($kc['combined_note'] ?? '') ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php endif; // end !yhctSuppressed ?>

        <!-- ⑤ DRUG WARNINGS -->
        <?php if (!empty($drugWarnings)): ?>
        <div class="result-section">
            <div class="result-section-title">
                <i class="bi bi-capsule text-warning"></i> ⑤ Cảnh báo thuốc
            </div>
            <?php foreach ($drugWarnings as $w): ?>
            <div class="drug-warning-card severity-<?= htmlspecialchars($w['severity'] ?? 'medium') ?>">
                <?php if (($w['severity'] ?? '') === 'high'): ?>
                <i class="bi bi-exclamation-octagon-fill text-danger me-2"></i>
                <?php elseif (($w['severity'] ?? '') === 'medium'): ?>
                <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                <?php else: ?>
                <i class="bi bi-info-circle-fill text-info me-2"></i>
                <?php endif; ?>
                <strong><?= htmlspecialchars($w['warning'] ?? '') ?></strong>
                <?php if (!empty($w['action'])): ?>
                <div class="text-muted small mt-1"><?= htmlspecialchars($w['action']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ⑥ CLUSTERS (Phát hiện đáng chú ý) -->
        <?php if (!empty($clusters)): ?>
        <div class="result-section">
            <div class="result-section-title">
                <i class="bi bi-collection text-info"></i> ⑥ Phát hiện đáng chú ý
            </div>
            <?php foreach (array_slice($clusters, 0, 5) as $cluster): ?>
            <div class="cluster-badge">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong class="small"><?= htmlspecialchars($cluster['name_vi']) ?></strong>
                        <?php if (!empty($cluster['description'])): ?>
                        <div class="text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($cluster['description']) ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="badge bg-info text-dark ms-2" style="font-size:0.7rem; white-space:nowrap;">
                        <?= $cluster['match_count'] ?>/<?= $cluster['total_codes'] ?> khớp
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ⑦ CẬN LÂM SÀNG (Paraclinical Results) -->
        <?php
        $pclTests   = $paraclinical['tests'] ?? [];
        $showPcl    = !empty($pclTests);
        ?>
        <?php if ($showPcl): ?>
        <div class="result-section" style="background:#E3F2FD; border-color:#90CAF9;">
            <div class="result-section-title">
                <i class="bi bi-flask text-primary"></i> ⑦ Kết quả cận lâm sàng đã nhập
            </div>
            <div style="display:flex;flex-direction:column;gap:.6rem;">
            <?php foreach ($pclTests as $t): ?>
                <?php
                $statusBadge = match($t['status']) {
                    'normal'   => ['✓ Bình thường', '#2E7D32', '#E8F5E9'],
                    'abnormal' => ['⚠ Bất thường',  '#C62828', '#FFEBEE'],
                    default    => ['Không rõ', '#607D8B', '#ECEFF1'],
                };
                // Score adjustment for this test's parent pattern
                $adjList = $clinicalAdj[$t['pattern_code']] ?? [];
                $thisAdj = array_filter($adjList, fn($a) => str_contains($a['test'] ?? '', substr($t['test_name_vi'], 0, 15)));
                ?>
                <div style="background:#fff;border-radius:10px;padding:.75rem 1rem;border:1.5px solid <?= $statusBadge[2] ?>;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;">
                        <div>
                            <strong style="font-size:.9rem;color:#1A237E;"><?= htmlspecialchars($t['test_name_vi']) ?></strong>
                            <?php if (!empty($t['findings'])): ?>
                            <div style="font-size:.82rem;color:#546E7A;margin-top:.2rem;"><?= htmlspecialchars($t['findings']) ?></div>
                            <?php endif; ?>
                        </div>
                        <span style="font-size:.78rem;font-weight:700;color:<?= $statusBadge[1] ?>;background:<?= $statusBadge[2] ?>;padding:.2rem .65rem;border-radius:12px;white-space:nowrap;">
                            <?= $statusBadge[0] ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php if (!empty($clinicalAdj)): ?>
            <div style="margin-top:1rem;padding:.75rem;background:#fff;border-radius:10px;font-size:.82rem;color:#546E7A;">
                <strong style="color:#1565C0;">📊 Điều chỉnh độ tin cậy dựa trên xét nghiệm:</strong>
                <?php foreach ($clinicalAdj as $pCode => $adjs): ?>
                    <?php if (empty($adjs)) continue; ?>
                    <div style="margin-top:.4rem;">
                        <span style="color:#1A237E;font-weight:600;"><?= htmlspecialchars($pCode) ?></span>:
                        <?php foreach ($adjs as $adj): ?>
                        <span style="margin-left:.5rem;"><?= htmlspecialchars($adj['test']) ?>
                            <strong style="color:<?= str_starts_with($adj['effect'], '+') ? '#2E7D32' : '#C62828' ?>;"><?= htmlspecialchars($adj['effect']) ?></strong>
                        </span>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ⑧ FOLLOW-UP ADVICE -->
        <?php if (!empty($followUp)): ?>
        <div class="result-section" style="background:#F1F8E9; border-color:#A5D6A7;">
            <div class="result-section-title">
                <i class="bi bi-calendar-check text-success"></i> ⑧ Lời khuyên theo dõi
            </div>
            <ul class="mb-0" style="font-size:0.9rem;">
                <?php foreach ($followUp as $advice): ?>
                <li class="mb-1"><?= htmlspecialchars($advice) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- ⑧ UNDERLYING DISEASES + COMPLICATIONS -->
        <?php
        $underlyingDiseases = $result['underlying_diseases'] ?? [];
        $complicationRisks  = $result['complication_risks']  ?? [];
        $probingQuestions   = $result['probing_questions']   ?? [];
        $showBackward = !empty($underlyingDiseases) || !empty($complicationRisks);
        ?>
        <?php if ($showBackward): ?>
        <div class="result-section" style="background:#EDE7F6; border-color:#B39DDB;">
            <div class="result-section-title">
                <i class="bi bi-diagram-3 text-purple" style="color:#6B3FA0;"></i>
                ⑨ Bệnh nền và biến chứng cần chú ý
            </div>

            <?php if (!empty($underlyingDiseases)): ?>
            <p class="small text-muted mb-2">
                <i class="bi bi-info-circle me-1"></i>
                Dựa trên triệu chứng đã chọn, các bệnh nền sau <strong>có thể</strong> liên quan.
                Đây chỉ là gợi ý — cần bác sĩ xác nhận.
            </p>
            <div class="mb-3">
                <?php
                $confColors = ['high' => '#4CAF50', 'medium' => '#FF9800', 'low' => '#9E9E9E'];
                $confLabels = ['high' => 'Khả năng cao', 'medium' => 'Có thể', 'low' => 'Thấp'];
                foreach ($underlyingDiseases as $ud):
                    $confColor = $confColors[$ud['confidence']] ?? '#9E9E9E';
                    $confLabel = $confLabels[$ud['confidence']] ?? $ud['confidence'];
                ?>
                <div class="d-flex align-items-start gap-2 mb-2 p-2" style="background:#fff; border-radius:8px; border-left:3px solid <?= $confColor ?>;">
                    <div style="min-width:80px;">
                        <span class="badge" style="background:<?= $confColor ?>; font-size:0.7rem;"><?= $confLabel ?></span>
                    </div>
                    <div>
                        <div style="font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($ud['name_vi']) ?></div>
                        <?php if (!empty($ud['name_en'])): ?>
                        <div class="text-muted" style="font-size:0.78rem;">Tên khoa học: <?= htmlspecialchars($ud['name_en']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($ud['yhct_correlate'])): ?>
                        <div class="text-muted" style="font-size:0.78rem;">
                            <i class="bi bi-yin-yang me-1"></i>YHCT: <?= htmlspecialchars($ud['yhct_correlate']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($complicationRisks)): ?>
            <div class="mb-2">
                <div class="fw-semibold small mb-2" style="color:#6B3FA0;">
                    <i class="bi bi-exclamation-triangle me-1"></i>Biến chứng cần đề phòng:
                </div>
                <?php foreach ($complicationRisks as $cr): ?>
                <div class="small mb-1" style="color:#555;">
                    <i class="bi bi-arrow-right-short text-danger"></i>
                    <strong><?= htmlspecialchars($cr['from_name_vi']) ?></strong>
                    → <span class="text-danger"><?= htmlspecialchars($cr['name_vi']) ?></span>
                    <?php if (!empty($cr['clinical_note'])): ?>
                    <span class="text-muted">(<?= htmlspecialchars($cr['clinical_note']) ?>)</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($probingQuestions)): ?>
            <div class="mt-2 p-2" style="background:#EDE7F6; border-radius:8px;">
                <div class="fw-semibold small mb-1" style="color:#6B3FA0;">
                    <i class="bi bi-question-circle me-1"></i>Câu hỏi gợi ý khi gặp bác sĩ:
                </div>
                <ul class="mb-0 small" style="color:#555;">
                    <?php foreach ($probingQuestions as $q): ?>
                    <li><?= htmlspecialchars($q) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ⑨ TÂY Y: WESTERN MEDICINE CORRELATIONS -->
        <?php
        $yhhd = $result['yhhd'] ?? [];
        $yhhdCorrelates   = $yhhd['correlates']    ?? [];
        $yhhdDiff         = $yhhd['differential']  ?? [];
        $yhhdSymNotes     = $yhhd['symptom_notes'] ?? [];
        $showYhhd = !empty($yhhdCorrelates) || !empty($yhhdSymNotes);
        ?>
        <?php if ($showYhhd && !$yhctSuppressed): ?>
        <div class="result-section" style="background:#E3F2FD; border-color:#90CAF9;">
            <div class="result-section-title">
                <i class="bi bi-hospital text-primary"></i> ⑩ Tương quan Tây Y (Y học hiện đại)
            </div>

            <?php if (!empty($yhhdCorrelates)): ?>
            <div class="mb-3">
                <div class="fw-semibold small mb-2" style="color:#1565C0;">
                    <i class="bi bi-clipboard2-pulse me-1"></i>Các bệnh Tây Y tương ứng với chứng YHCT:
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($yhhdCorrelates as $corr): ?>
                    <span class="badge" style="background:#1565C0; color:#fff; font-size:0.8rem; padding:0.4rem 0.7rem; border-radius:20px;">
                        <?= htmlspecialchars(displayDisease($corr)) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($yhhdDiff)): ?>
            <div class="mb-3">
                <div class="fw-semibold small mb-2" style="color:#1565C0;">
                    <i class="bi bi-list-ul me-1"></i>Chẩn đoán phân biệt (YHCT + Tây Y):
                </div>
                <?php foreach ($yhhdDiff as $d): ?>
                <div class="d-flex align-items-start gap-2 mb-2 p-2" style="background:#fff; border-radius:8px; border-left:3px solid #90CAF9;">
                    <div style="min-width:120px;">
                        <span class="text-muted small"><?= htmlspecialchars($d['pattern_vi']) ?></span>
                        <div style="font-size:0.7rem; color:#1565C0;">Độ phù hợp: <?= round($d['score'] * 100) ?>%</div>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach ($d['yhhd'] as $y): ?>
                        <span class="badge bg-light text-dark border" style="font-size:0.75rem;"><?= htmlspecialchars(displayDisease($y)) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($yhhdSymNotes)): ?>
            <div class="mb-2">
                <div class="fw-semibold small mb-2" style="color:#1565C0;">
                    <i class="bi bi-stethoscope me-1"></i>Gợi ý lâm sàng Tây Y theo từng triệu chứng:
                </div>
                <?php foreach ($yhhdSymNotes as $sn): ?>
                <div class="mb-2 p-2" style="background:#fff; border-radius:8px; border-left:3px solid #64B5F6;">
                    <div class="fw-semibold" style="font-size:0.85rem; color:#0D47A1;">
                        <?= htmlspecialchars($sn['symptom']) ?>
                    </div>
                    <div class="small mt-1" style="color:#444; font-style:italic;">
                        <i class="bi bi-info-circle me-1 text-info"></i>
                        <em>(Ghi chú lâm sàng tham khảo)</em>
                        <?= htmlspecialchars($sn['yhhd_note']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($yhhd['red_flag_western'])): ?>
            <div class="mb-2">
                <div class="fw-semibold small mb-1" style="color:#B71C1C;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Cảnh báo Tây y từ triệu chứng:
                </div>
                <?php foreach ($yhhd['red_flag_western'] as $rfw): ?>
                <div class="small mb-1 p-2" style="background:#FFEBEE; border-radius:6px; border-left:3px solid #EF5350;">
                    <strong><?= htmlspecialchars($rfw['name'] ?? '') ?></strong>
                    <?php if (!empty($rfw['action'])): ?>
                    — <span class="text-muted"><?= htmlspecialchars($rfw['action']) ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="small text-muted mt-2" style="font-size:0.75rem;">
                <i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($yhhd['disclaimer'] ?? '') ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ⑩ DISCLAIMER + ACTIONS -->
        <div class="result-section" style="background:#FFF8E1; border-color:#FFE082;">
            <div class="result-section-title">
                <i class="bi bi-shield-exclamation text-warning"></i> ⑪ Lưu ý quan trọng
            </div>
            <p class="small mb-3"><?= htmlspecialchars($disclaimer) ?></p>
            <div class="d-flex flex-wrap gap-2 no-print">
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-printer me-1"></i>In kết quả
                </button>
                <a href="<?= BASE_URL ?>exam/start" class="btn btn-success btn-sm">
                    <i class="bi bi-arrow-repeat me-1"></i>Khám lại
                </a>
                <a href="<?= BASE_URL ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-house me-1"></i>Trang chủ
                </a>
            </div>
        </div>

        <!-- Chief complaint reminder -->
        <?php if ($chiefComplaint): ?>
        <div class="text-muted small text-center mb-3">
            <i class="bi bi-chat-text me-1"></i>
            Triệu chứng mô tả: "<?= htmlspecialchars(mb_substr($chiefComplaint, 0, 120, 'UTF-8')) ?><?= mb_strlen($chiefComplaint, 'UTF-8') > 120 ? '...' : '' ?>"
        </div>
        <?php endif; ?>

    </div>
</div>
