<?php
$session        = $session         ?? [];
$result         = $result          ?? [];
$symptomDetails = $symptom_details ?? [];

$selectedCodes  = $session['selected_codes']  ?? [];
$quickAnswers   = $session['quick_answers']   ?? [];
$contextFlags   = $session['context_flags']   ?? [];

$primaryPattern = $result['primary_pattern'] ?? null;
$triageLevel    = $result['triage_level']    ?? null;
$batCuong       = $result['bat_cuong']       ?? [];
$chungRanked    = $result['chung_ranked']    ?? [];
$doctorReview   = $result['doctor_review']   ?? null;

$shortId = substr($session['session_id'] ?? '', 0, 8);
?>
<div class="py-4" style="background:#F9F7F4; min-height:calc(100vh - 120px);">
    <div class="container" style="max-width:900px;">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= BASE_URL ?>doctor/dashboard" class="text-success">Bảng điều khiển</a>
                </li>
                <li class="breadcrumb-item active">Phiên <?= htmlspecialchars($shortId) ?>...</li>
            </ol>
        </nav>

        <!-- Session Header -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="fw-bold mb-1">
                            <i class="bi bi-clipboard2-pulse me-2 text-success"></i>
                            Chi tiết phiên khám
                            <code class="text-muted small ms-2"><?= htmlspecialchars($session['session_id'] ?? '') ?></code>
                        </h5>
                        <p class="text-muted small mb-1">
                            Ngày: <?= date('d/m/Y H:i', strtotime($session['created_at'] ?? 'now')) ?>
                            <?php if (!empty($session['completed_at'])): ?>
                            &bull; Hoàn tất: <?= date('H:i', strtotime($session['completed_at'])) ?>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($session['chief_complaint'])): ?>
                        <div class="alert alert-light py-2 mb-0 mt-2" style="font-size:0.9rem;">
                            <i class="bi bi-chat-quote me-2"></i>
                            "<?= htmlspecialchars($session['chief_complaint']) ?>"
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <?php
                        $statusMap = ['active'=>['Đang khám','warning'],
                                      'completed'=>['Hoàn tất','success'],
                                      'abandoned'=>['Bỏ dở','secondary']];
                        [$stLabel, $stColor] = $statusMap[$session['status'] ?? ''] ?? ['?','secondary'];
                        ?>
                        <span class="badge bg-<?= $stColor ?> fs-6 d-block mb-2"><?= $stLabel ?></span>
                        <?php if ($triageLevel): ?>
                        <span class="badge bg-<?= $triageLevel === 'L1_emergency' ? 'danger' : ($triageLevel === 'L2_urgent' ? 'warning' : 'info') ?> d-block">
                            <?= $triageLevel === 'L1_emergency' ? '🚨 Cấp cứu' : ($triageLevel === 'L2_urgent' ? '⚠️ Khẩn' : 'ℹ️ Theo dõi') ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">

            <!-- Left: Session Data -->
            <div class="col-md-7">

                <!-- Selected Symptoms -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom py-2">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-check2-square me-2 text-success"></i>
                            Triệu chứng đã chọn (<?= count($selectedCodes) ?>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($selectedCodes)): ?>
                        <p class="text-muted small mb-0">Không có triệu chứng được chọn.</p>
                        <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($selectedCodes as $code): ?>
                            <?php $sym = $symptomDetails[$code] ?? null; ?>
                            <span class="badge rounded-pill"
                                  style="background:#E8F5E9; color:#1B5E20; font-size:0.8rem; font-weight:500; border:1px solid #A5D6A7;">
                                <?= htmlspecialchars($sym ? $sym['name_vi'] : $code) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Answers -->
                <?php if (!empty($quickAnswers)): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom py-2">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-question-circle me-2 text-info"></i>
                            Câu hỏi nhanh
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <?php
                            $qaLabels = [
                                'onset'       => 'Khởi phát',
                                'trajectory'  => 'Xu hướng',
                                'tongue'      => 'Lưỡi',
                                'medications' => 'Thuốc đang dùng',
                                'emotion'     => 'Cảm xúc',
                                'age'         => 'Tuổi',
                                'sex'         => 'Giới tính',
                                'pregnancy'   => 'Thai kỳ',
                            ];
                            foreach ($quickAnswers as $key => $val):
                                if (empty($val) || !isset($qaLabels[$key])) continue;
                            ?>
                            <tr>
                                <td class="text-muted fw-semibold" style="font-size:0.8rem; width:130px; padding:8px 12px;">
                                    <?= $qaLabels[$key] ?>
                                </td>
                                <td style="font-size:0.85rem; padding:8px 12px;">
                                    <?= htmlspecialchars((string)$val) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Engine Result Summary -->
                <?php if (!empty($result)): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom py-2">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-cpu me-2 text-success"></i>
                            Kết quả phân tích
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if ($primaryPattern): ?>
                        <div class="mb-3 p-3 rounded" style="background:linear-gradient(135deg,#E8F5E9,#F1F8E9);">
                            <div class="fw-bold text-success">
                                <?= htmlspecialchars($primaryPattern['name_vi'] ?? '-') ?>
                            </div>
                            <?php if (!empty($primaryPattern['primary_tang'])): ?>
                            <div class="small text-muted">Tạng chủ: <?= htmlspecialchars($primaryPattern['primary_tang']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($chungRanked[0]['score'])): ?>
                            <div class="small text-success">Điểm: <?= round($chungRanked[0]['score'] * 100) ?>%</div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Top 3 differential -->
                        <?php if (count($chungRanked) > 1): ?>
                        <div class="small text-muted mb-2 fw-semibold">Chẩn đoán phân biệt:</div>
                        <?php foreach (array_slice($chungRanked, 1, 3) as $m): ?>
                        <div class="d-flex justify-content-between small mb-1">
                            <span><?= htmlspecialchars($m['pattern']['name_vi'] ?? '-') ?></span>
                            <span class="text-muted"><?= round($m['score'] * 100) ?>%</span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Right: Doctor Review -->
            <div class="col-md-5">

                <!-- Previous review -->
                <?php if ($doctorReview): ?>
                <div class="card border-success mb-3">
                    <div class="card-header bg-success text-white py-2">
                        <h6 class="mb-0">
                            <i class="bi bi-person-check me-2"></i>Ghi chú bác sĩ (đã có)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="small">
                            <div class="text-muted">Bác sĩ: <?= htmlspecialchars($doctorReview['doctor_username'] ?? '-') ?></div>
                            <div class="text-muted mb-2">Lúc: <?= htmlspecialchars($doctorReview['reviewed_at'] ?? '-') ?></div>
                            <?php if (!empty($doctorReview['confirmed_pattern'])): ?>
                            <div class="mb-1"><strong>Chứng xác nhận:</strong> <?= htmlspecialchars($doctorReview['confirmed_pattern']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($doctorReview['notes'])): ?>
                            <div class="mb-1"><strong>Ghi chú:</strong><br><?= nl2br(htmlspecialchars($doctorReview['notes'])) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($doctorReview['action'])): ?>
                            <div><strong>Xử lý:</strong> <?= htmlspecialchars($doctorReview['action']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Doctor Notes Form -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-2">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-pencil-square me-2 text-primary"></i>
                            <?= $doctorReview ? 'Cập nhật' : 'Thêm' ?> ghi chú bác sĩ
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= BASE_URL ?>doctor/session/<?= htmlspecialchars($session['session_id'] ?? '') ?>">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Chứng bệnh xác nhận</label>
                                <input type="text" name="confirmed_pattern" class="form-control form-control-sm"
                                       placeholder="Ví dụ: Can khí uất kết..."
                                       value="<?= htmlspecialchars($doctorReview['confirmed_pattern'] ?? ($primaryPattern['name_vi'] ?? '')) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Ghi chú lâm sàng</label>
                                <textarea name="doctor_notes" class="form-control" rows="5"
                                          placeholder="Nhận xét lâm sàng, điều chỉnh biện chứng, kế hoạch điều trị..."><?= htmlspecialchars($doctorReview['notes'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Xử lý / Hành động</label>
                                <select name="doctor_action" class="form-select form-select-sm">
                                    <option value="">-- Chọn --</option>
                                    <option value="confirmed">Xác nhận kết quả AI</option>
                                    <option value="corrected">Chỉnh sửa kết quả</option>
                                    <option value="refer">Chuyển viện</option>
                                    <option value="followup">Hẹn tái khám</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 rounded-pill">
                                <i class="bi bi-save me-2"></i>Lưu ghi chú
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Back button -->
                <div class="mt-3 text-center">
                    <a href="<?= BASE_URL ?>doctor/dashboard" class="text-muted text-decoration-none small">
                        <i class="bi bi-arrow-left me-1"></i>Quay lại bảng điều khiển
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
