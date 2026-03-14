<?php
$stats = $stats ?? [];
$kb    = $stats['kb']             ?? [];
$userCounts   = $stats['user_counts']   ?? [];
$sessionStats = $stats['session_stats'] ?? [];
$recentSessions = $stats['recent_sessions'] ?? [];
?>
<style>
.admin-stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    border: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: box-shadow 0.2s;
}
.admin-stat-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}
.stat-icon-lg {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}
</style>

<div class="py-4" style="background:#F9F7F4; min-height:calc(100vh - 120px);">
    <div class="container-fluid px-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-speedometer2 me-2 text-success"></i>Admin Dashboard
                </h4>
                <p class="text-muted small mb-0"><?= APP_NAME ?> &bull; <?= date('d/m/Y H:i') ?></p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>admin/kb_review" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-database me-1"></i>KB Review
                </a>
                <a href="<?= BASE_URL ?>admin/clinical_tests" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-clipboard-check me-1"></i>Clinical Tests
                </a>
                <a href="<?= BASE_URL ?>admin/embeddings" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-database-gear me-1"></i>Embeddings
                </a>
                <a href="<?= BASE_URL ?>admin/users" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-people me-1"></i>Users
                </a>
            </div>
        </div>

        <!-- Users Stats -->
        <h6 class="text-muted fw-semibold small text-uppercase mb-2">Người dùng</h6>
        <div class="row g-3 mb-4">
            <?php
            $userCards = [
                ['Admin', $userCounts['admin'] ?? 0, '#7B1FA2', '#F3E5F5', 'bi-shield-lock'],
                ['Bác sĩ', $userCounts['doctor'] ?? 0, '#1565C0', '#E3F2FD', 'bi-person-badge'],
                ['Bệnh nhân', $userCounts['patient'] ?? 0, '#2E7D32', '#E8F5E9', 'bi-person-heart'],
                ['Tổng', $stats['total_users'] ?? 0, '#424242', '#F5F5F5', 'bi-people'],
            ];
            foreach ($userCards as [$label, $count, $color, $bg, $icon]):
            ?>
            <div class="col-6 col-md-3">
                <div class="admin-stat-card">
                    <div class="stat-icon-lg" style="background:<?= $bg ?>;">
                        <i class="bi <?= $icon ?>" style="color:<?= $color ?>;"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:1.5rem;"><?= number_format($count) ?></div>
                        <div class="text-muted small"><?= $label ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Session Stats -->
        <h6 class="text-muted fw-semibold small text-uppercase mb-2">Phiên khám</h6>
        <div class="row g-3 mb-4">
            <?php
            $sessionCards = [
                ['Đang khám', $sessionStats['active'] ?? 0, '#FF8F00', '#FFF3E0', 'bi-activity'],
                ['Hoàn tất', $sessionStats['completed'] ?? 0, '#2E7D32', '#E8F5E9', 'bi-check-circle'],
                ['Bỏ dở', $sessionStats['abandoned'] ?? 0, '#616161', '#F5F5F5', 'bi-x-circle'],
                ['Tổng', $stats['total_sessions'] ?? 0, '#1565C0', '#E3F2FD', 'bi-collection'],
            ];
            foreach ($sessionCards as [$label, $count, $color, $bg, $icon]):
            ?>
            <div class="col-6 col-md-3">
                <div class="admin-stat-card">
                    <div class="stat-icon-lg" style="background:<?= $bg ?>;">
                        <i class="bi <?= $icon ?>" style="color:<?= $color ?>;"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:1.5rem;"><?= number_format($count) ?></div>
                        <div class="text-muted small"><?= $label ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- KB Stats -->
        <h6 class="text-muted fw-semibold small text-uppercase mb-2">Cơ sở tri thức (KB)</h6>
        <div class="row g-3 mb-4">
            <?php
            $kbCards = [
                ['Triệu chứng', $kb['symptoms'] ?? 0, '#00796B', '#E0F2F1', 'bi-activity'],
                ['Chứng bệnh', $kb['patterns'] ?? 0, '#1565C0', '#E3F2FD', 'bi-diagram-3'],
                ['Cờ đỏ', $kb['red_flags'] ?? 0, '#C62828', '#FFEBEE', 'bi-exclamation-triangle'],
                ['Cụm bệnh', $kb['clusters'] ?? 0, '#E65100', '#FBE9E7', 'bi-collection'],
                ['Bệnh nguyên', $kb['pathogenesis'] ?? 0, '#4A148C', '#F3E5F5', 'bi-gear'],
                ['Tương tác thuốc', $kb['herb_drug'] ?? 0, '#BF360C', '#FBE9E7', 'bi-capsule'],
            ];
            foreach ($kbCards as [$label, $count, $color, $bg, $icon]):
            ?>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="admin-stat-card flex-column text-center" style="padding:1rem;">
                    <div class="stat-icon-lg mx-auto mb-2" style="background:<?= $bg ?>; width:44px; height:44px; font-size:1.2rem;">
                        <i class="bi <?= $icon ?>" style="color:<?= $color ?>;"></i>
                    </div>
                    <div class="fw-bold fs-5"><?= number_format($count) ?></div>
                    <div class="text-muted" style="font-size:0.75rem;"><?= $label ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Quick Links -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <a href="<?= BASE_URL ?>admin/kb_review" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon-lg" style="background:#E8F5E9; width:44px; height:44px; font-size:1.2rem;">
                                <i class="bi bi-database-check text-success"></i>
                            </div>
                            <div>
                                <div class="fw-bold">KB Review</div>
                                <div class="text-muted small">Xem xét và kiểm tra dữ liệu YHCT</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?= BASE_URL ?>admin/users" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon-lg" style="background:#E3F2FD; width:44px; height:44px; font-size:1.2rem;">
                                <i class="bi bi-people text-primary"></i>
                            </div>
                            <div>
                                <div class="fw-bold">Quản lý người dùng</div>
                                <div class="text-muted small">Tạo, chỉnh sửa, phân quyền</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?= BASE_URL ?>admin/exam_sessions" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="stat-icon-lg" style="background:#FFF3E0; width:44px; height:44px; font-size:1.2rem;">
                                <i class="bi bi-list-check text-warning"></i>
                            </div>
                            <div>
                                <div class="fw-bold">Phiên khám</div>
                                <div class="text-muted small">Xem lịch sử tất cả phiên khám</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Sessions -->
        <?php if (!empty($recentSessions)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-clock-history me-2 text-success"></i>Hoạt động gần đây
                </h6>
                <a href="<?= BASE_URL ?>admin/exam_sessions" class="btn btn-outline-secondary btn-sm">
                    Xem tất cả <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold small">ID</th>
                                <th class="fw-semibold small">Ngày</th>
                                <th class="fw-semibold small">User</th>
                                <th class="fw-semibold small">Triệu chứng</th>
                                <th class="fw-semibold small">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($recentSessions, 0, 10) as $s): ?>
                            <tr>
                                <td><code style="font-size:0.7rem;"><?= substr($s['session_id'] ?? '', 0, 8) ?>...</code></td>
                                <td style="font-size:0.78rem;"><?= date('d/m H:i', strtotime($s['created_at'] ?? 'now')) ?></td>
                                <td style="font-size:0.78rem;"><?= htmlspecialchars($s['username'] ?? 'Guest') ?></td>
                                <td style="max-width:200px; font-size:0.78rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?= htmlspecialchars($s['chief_complaint'] ?? '-') ?>
                                </td>
                                <td>
                                    <?php
                                    $sm = ['active'=>['Đang','warning'],'completed'=>['Xong','success'],'abandoned'=>['Bỏ','secondary']];
                                    [$sl, $sc] = $sm[$s['status'] ?? ''] ?? ['?','secondary'];
                                    ?>
                                    <span class="badge bg-<?= $sc ?>"><?= $sl ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
