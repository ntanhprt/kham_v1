<?php
$sessions      = $sessions      ?? [];
$totalCount    = $total_count   ?? 0;
$currentPage   = $current_page  ?? 1;
$totalPages    = $total_pages   ?? 1;
$filterStatus  = $filter_status ?? 'all';
$filterDate    = $filter_date   ?? '';
?>
<div class="py-4" style="background:#F9F7F4; min-height:calc(100vh - 120px);">
    <div class="container-fluid px-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-list-check me-2 text-success"></i>Phiên khám
                </h4>
                <p class="text-muted small mb-0">
                    Tổng: <?= number_format($totalCount) ?> phiên
                </p>
            </div>
            <a href="<?= BASE_URL ?>admin/dashboard" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Dashboard
            </a>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label small mb-1">Trạng thái</label>
                        <select name="status" class="form-select form-select-sm" style="min-width:130px;">
                            <option value="all"       <?= $filterStatus==='all' ? 'selected' : '' ?>>Tất cả</option>
                            <option value="active"    <?= $filterStatus==='active' ? 'selected' : '' ?>>Đang khám</option>
                            <option value="completed" <?= $filterStatus==='completed' ? 'selected' : '' ?>>Hoàn tất</option>
                            <option value="abandoned" <?= $filterStatus==='abandoned' ? 'selected' : '' ?>>Bỏ dở</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small mb-1">Ngày</label>
                        <input type="date" name="date" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($filterDate) ?>">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-funnel me-1"></i>Lọc
                        </button>
                        <a href="<?= BASE_URL ?>admin/exam_sessions" class="btn btn-outline-secondary btn-sm ms-1">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($sessions)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                    Không tìm thấy phiên khám nào.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold small">ID</th>
                                <th class="fw-semibold small">Ngày tạo</th>
                                <th class="fw-semibold small">Người dùng</th>
                                <th class="fw-semibold small">Mô tả triệu chứng</th>
                                <th class="fw-semibold small">Chứng bệnh</th>
                                <th class="fw-semibold small">Cảnh báo</th>
                                <th class="fw-semibold small">Trạng thái</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $s): ?>
                            <?php
                            $statusMap = ['active'=>['Đang khám','warning'],
                                          'completed'=>['Hoàn tất','success'],
                                          'abandoned'=>['Bỏ dở','secondary']];
                            [$stLabel, $stColor] = $statusMap[$s['status'] ?? ''] ?? ['?','secondary'];
                            $triage = $s['triage_level'] ?? null;
                            ?>
                            <tr>
                                <td>
                                    <code style="font-size:0.72rem;" class="text-muted">
                                        <?= htmlspecialchars(substr($s['session_id'] ?? '', 0, 10)) ?>...
                                    </code>
                                </td>
                                <td style="font-size:0.8rem; white-space:nowrap;">
                                    <?= date('d/m/Y', strtotime($s['created_at'] ?? 'now')) ?><br>
                                    <span class="text-muted"><?= date('H:i', strtotime($s['created_at'] ?? 'now')) ?></span>
                                </td>
                                <td style="font-size:0.82rem;">
                                    <?= htmlspecialchars($s['username'] ?? ('ID:' . $s['user_id'])) ?>
                                </td>
                                <td style="max-width:220px;">
                                    <div style="font-size:0.8rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:220px;"
                                         title="<?= htmlspecialchars($s['chief_complaint'] ?? '') ?>">
                                        <?= htmlspecialchars($s['chief_complaint'] ?? '-') ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:0.8rem; color:#2E7D32; font-weight:500; max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                                         title="<?= htmlspecialchars($s['primary_pattern_name'] ?? '') ?>">
                                        <?= htmlspecialchars($s['primary_pattern_name'] ?? '-') ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($triage === 'L1_emergency'): ?>
                                    <span class="badge bg-danger">🚨 L1</span>
                                    <?php elseif ($triage === 'L2_urgent'): ?>
                                    <span class="badge bg-warning text-dark">⚠️ L2</span>
                                    <?php elseif ($triage === 'L3_watch'): ?>
                                    <span class="badge bg-info text-dark">ℹ️ L3</span>
                                    <?php else: ?>
                                    <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $stColor ?>"><?= $stLabel ?></span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>doctor/session/<?= urlencode($s['session_id'] ?? '') ?>"
                                       class="btn btn-outline-success btn-sm py-0">
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

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav class="mt-3">
            <ul class="pagination pagination-sm justify-content-center">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                    <a class="page-link"
                       href="?status=<?= urlencode($filterStatus) ?>&date=<?= urlencode($filterDate) ?>&page=<?= $p ?>">
                        <?= $p ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>

    </div>
</div>
