<?php
$activeTab = $active_tab ?? 'symptoms';
$data      = $data      ?? ['items' => [], 'total' => 0];
$items     = $data['items'] ?? [];
$total     = $data['total'] ?? 0;
$error     = $data['error'] ?? null;

$tabs = [
    'symptoms'  => ['label' => 'Triệu chứng', 'icon' => 'bi-activity'],
    'patterns'  => ['label' => 'Chứng bệnh', 'icon' => 'bi-diagram-3'],
    'red_flags' => ['label' => 'Cờ đỏ',      'icon' => 'bi-exclamation-triangle'],
    'clusters'  => ['label' => 'Cụm bệnh',   'icon' => 'bi-collection'],
];
?>
<div class="py-4" style="background:#F9F7F4; min-height:calc(100vh - 120px);">
    <div class="container-fluid px-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-database-check me-2 text-success"></i>KB Review
                </h4>
                <p class="text-muted small mb-0">Xem xét cơ sở tri thức Y Học Cổ Truyền</p>
            </div>
            <a href="<?= BASE_URL ?>admin/dashboard" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Dashboard
            </a>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-pills mb-4 gap-1">
            <?php foreach ($tabs as $tabKey => $tabInfo): ?>
            <li class="nav-item">
                <a class="nav-link <?= $activeTab === $tabKey ? 'active' : '' ?>"
                   href="?tab=<?= $tabKey ?>">
                    <i class="bi <?= $tabInfo['icon'] ?> me-1"></i>
                    <?= $tabInfo['label'] ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Tab header with total -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">
                <?= htmlspecialchars($tabs[$activeTab]['label'] ?? $activeTab) ?>
                <span class="badge bg-secondary ms-2"><?= number_format($total) ?> bản ghi</span>
            </h6>
            <span class="text-muted small">Hiển thị 100 bản ghi đầu tiên</span>
        </div>

        <!-- Data Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($items)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                    Không có dữ liệu.
                </div>

                <?php elseif ($activeTab === 'symptoms'): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold small">Mã</th>
                                <th class="fw-semibold small">Tên Việt</th>
                                <th class="fw-semibold small">Tên Anh</th>
                                <th class="fw-semibold small">Tạng</th>
                                <th class="fw-semibold small">Danh mục</th>
                                <th class="fw-semibold small">Bí danh</th>
                                <th class="fw-semibold small">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $row): ?>
                            <tr>
                                <td><code style="font-size:0.72rem;"><?= htmlspecialchars($row['symptom_code'] ?? '') ?></code></td>
                                <td style="font-size:0.82rem; font-weight:500;"><?= htmlspecialchars($row['name_vi'] ?? '') ?></td>
                                <td style="font-size:0.78rem; color:#666;"><?= htmlspecialchars($row['name_en'] ?? '') ?></td>
                                <td>
                                    <?php if ($row['tang'] ?? ''): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success" style="font-size:0.7rem;">
                                        <?= htmlspecialchars($row['tang']) ?>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:0.75rem; color:#888;"><?= htmlspecialchars($row['category'] ?? '') ?></td>
                                <td style="font-size:0.72rem; color:#aaa; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?= htmlspecialchars($row['aliases'] ?? '') ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= ($row['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>">
                                        <?= $row['status'] ?? 'unknown' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php elseif ($activeTab === 'patterns'): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold small">Mã</th>
                                <th class="fw-semibold small">Tên Việt</th>
                                <th class="fw-semibold small">Tạng chủ</th>
                                <th class="fw-semibold small">Triệu chứng bắt buộc</th>
                                <th class="fw-semibold small">Triệu chứng tuỳ chọn</th>
                                <th class="fw-semibold small">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $row): ?>
                            <tr>
                                <td><code style="font-size:0.72rem;"><?= htmlspecialchars($row['pattern_code'] ?? '') ?></code></td>
                                <td style="font-size:0.82rem; font-weight:500;"><?= htmlspecialchars($row['name_vi'] ?? '') ?></td>
                                <td>
                                    <?php if ($row['primary_tang'] ?? ''): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success" style="font-size:0.7rem;">
                                        <?= htmlspecialchars($row['primary_tang']) ?>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:0.72rem; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($row['required_codes'] ?? '') ?>">
                                    <?= htmlspecialchars($row['required_codes'] ?? '-') ?>
                                </td>
                                <td style="font-size:0.72rem; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($row['optional_codes'] ?? '') ?>">
                                    <?= htmlspecialchars($row['optional_codes'] ?? '-') ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= ($row['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>">
                                        <?= $row['status'] ?? 'unknown' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php elseif ($activeTab === 'red_flags'): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold small">Mã</th>
                                <th class="fw-semibold small">Tên</th>
                                <th class="fw-semibold small">Mức độ</th>
                                <th class="fw-semibold small">Mô tả</th>
                                <th class="fw-semibold small">Triệu chứng</th>
                                <th class="fw-semibold small">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $row): ?>
                            <?php
                            $levelClass = match($row['level'] ?? '') {
                                'L1_emergency' => 'danger',
                                'L2_urgent'    => 'warning',
                                'L3_watch'     => 'info',
                                default        => 'secondary',
                            };
                            $levelLabel = match($row['level'] ?? '') {
                                'L1_emergency' => '🚨 Cấp cứu',
                                'L2_urgent'    => '⚠️ Khẩn cấp',
                                'L3_watch'     => 'ℹ️ Theo dõi',
                                default        => ($row['level'] ?? '-'),
                            };
                            ?>
                            <tr>
                                <td><code style="font-size:0.72rem;"><?= htmlspecialchars($row['code'] ?? '') ?></code></td>
                                <td style="font-size:0.82rem; font-weight:500;"><?= htmlspecialchars($row['name_vi'] ?? '') ?></td>
                                <td>
                                    <span class="badge bg-<?= $levelClass ?>"><?= $levelLabel ?></span>
                                </td>
                                <td style="font-size:0.78rem; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                                    title="<?= htmlspecialchars($row['description'] ?? '') ?>">
                                    <?= htmlspecialchars($row['description'] ?? '-') ?>
                                </td>
                                <td style="font-size:0.7rem; color:#666; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?= htmlspecialchars($row['symptom_codes'] ?? '-') ?>
                                </td>
                                <td style="font-size:0.75rem; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?= htmlspecialchars($row['action'] ?? '-') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php elseif ($activeTab === 'clusters'): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold small">Mã</th>
                                <th class="fw-semibold small">Tên</th>
                                <th class="fw-semibold small">Triệu chứng</th>
                                <th class="fw-semibold small">Ngưỡng</th>
                                <th class="fw-semibold small">Chứng liên quan</th>
                                <th class="fw-semibold small">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $row): ?>
                            <tr>
                                <td><code style="font-size:0.72rem;"><?= htmlspecialchars($row['cluster_code'] ?? '') ?></code></td>
                                <td style="font-size:0.82rem; font-weight:500;"><?= htmlspecialchars($row['name_vi'] ?? '') ?></td>
                                <td style="font-size:0.7rem; color:#666; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?= htmlspecialchars($row['symptom_codes'] ?? '-') ?>
                                </td>
                                <td style="font-size:0.8rem;"><?= htmlspecialchars((string)($row['threshold'] ?? '0.6')) ?></td>
                                <td style="font-size:0.72rem; color:#888; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?= htmlspecialchars($row['pattern_codes'] ?? '-') ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= ($row['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>">
                                        <?= $row['status'] ?? 'unknown' ?>
                                    </span>
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
