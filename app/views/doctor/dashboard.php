<?php
$recentSessions = $recent_sessions ?? [];
$todayCount     = $today_count     ?? 0;
$pendingCount   = $pending_count   ?? 0;
$doctorUser     = Auth::getUser();
?>
<style>
.doctor-stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    border: 1px solid #E8F5E9;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.stat-icon-circle {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}
.level-badge-L1 { background: #FFEBEE; color: #C62828; border: 1px solid #FFCDD2; }
.level-badge-L2 { background: #FFF8E1; color: #E65100; border: 1px solid #FFE0B2; }
.level-badge-L3 { background: #E3F2FD; color: #1565C0; border: 1px solid #BBDEFB; }
</style>

<div class="py-4" style="background:#F9F7F4; min-height:calc(100vh - 120px);">
    <div class="container">

        <!-- Welcome -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0" style="color:#1B5E20;">
                    <i class="bi bi-person-badge me-2"></i>Bảng điều khiển Bác sĩ
                </h4>
                <p class="text-muted mb-0 small">
                    Xin chào, <?= htmlspecialchars($doctorUser['display_name'] ?? $doctorUser['username'] ?? 'Bác sĩ') ?>
                    &bull; <?= date('d/m/Y') ?>
                </p>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4">
                <div class="doctor-stat-card">
                    <div class="stat-icon-circle" style="background:#E8F5E9;">
                        <i class="bi bi-calendar-day text-success"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4"><?= number_format($todayCount) ?></div>
                        <div class="text-muted small">Phiên khám hôm nay</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4">
                <div class="doctor-stat-card">
                    <div class="stat-icon-circle" style="background:#FFF3E0;">
                        <i class="bi bi-clock-history text-warning"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4"><?= number_format($pendingCount) ?></div>
                        <div class="text-muted small">Chờ xem xét</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4">
                <div class="doctor-stat-card">
                    <div class="stat-icon-circle" style="background:#E3F2FD;">
                        <i class="bi bi-collection text-primary"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4"><?= count($recentSessions) ?></div>
                        <div class="text-muted small">Phiên gần đây</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Sessions Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-list-check me-2 text-success"></i>Phiên khám gần đây
                </h6>
                <a href="<?= BASE_URL ?>admin/exam_sessions" class="btn btn-outline-success btn-sm">
                    Xem tất cả <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentSessions)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                    Chưa có phiên khám nào.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="font-size:0.8rem;" class="fw-semibold">ID</th>
                                <th style="font-size:0.8rem;" class="fw-semibold">Ngày</th>
                                <th style="font-size:0.8rem;" class="fw-semibold">Người khám</th>
                                <th style="font-size:0.8rem;" class="fw-semibold">Triệu chứng chính</th>
                                <th style="font-size:0.8rem;" class="fw-semibold">Trạng thái</th>
                                <th style="font-size:0.8rem;" class="fw-semibold">Cảnh báo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSessions as $s): ?>
                            <?php
                            $rd         = is_string($s['result_data'] ?? null)
                                          ? (json_decode($s['result_data'], true) ?? [])
                                          : ($s['result_data'] ?? []);
                            $triage     = $rd['triage_level'] ?? null;
                            $patName    = $rd['primary_pattern']['name_vi'] ?? '-';
                            $shortId    = substr($s['session_id'] ?? '', 0, 8);
                            ?>
                            <tr>
                                <td>
                                    <code style="font-size:0.75rem;" class="text-muted"><?= $shortId ?>...</code>
                                </td>
                                <td style="font-size:0.8rem;">
                                    <?= date('d/m H:i', strtotime($s['created_at'] ?? 'now')) ?>
                                </td>
                                <td style="font-size:0.8rem;">
                                    <?= htmlspecialchars($s['username'] ?? ('Khách #' . $s['user_id'])) ?>
                                </td>
                                <td style="max-width:200px;">
                                    <div style="font-size:0.8rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                        <?= htmlspecialchars($s['chief_complaint'] ?? '-') ?>
                                    </div>
                                    <div style="font-size:0.72rem; color:#2E7D32; font-weight:500;">
                                        <?= htmlspecialchars($patName) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $statusMap = ['active'=>['Đang khám','warning'],
                                                  'completed'=>['Hoàn tất','success'],
                                                  'abandoned'=>['Bỏ dở','secondary']];
                                    [$stLabel, $stColor] = $statusMap[$s['status'] ?? ''] ?? ['?','secondary'];
                                    ?>
                                    <span class="badge bg-<?= $stColor ?>"><?= $stLabel ?></span>
                                </td>
                                <td>
                                    <?php if ($triage === 'L1_emergency'): ?>
                                    <span class="badge level-badge-L1">🚨 Cấp cứu</span>
                                    <?php elseif ($triage === 'L2_urgent'): ?>
                                    <span class="badge level-badge-L2">⚠️ Khẩn</span>
                                    <?php elseif ($triage === 'L3_watch'): ?>
                                    <span class="badge level-badge-L3">ℹ️ Theo dõi</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>doctor/session/<?= urlencode($s['session_id'] ?? '') ?>"
                                       class="btn btn-outline-success btn-sm">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
