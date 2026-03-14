<?php
/** @var string $title */
/** @var array  $results  [['name'=>..., 'pass'=>bool, 'details'=>[...]], ...] */
/** @var array  $summary  ['total'=>int, 'passed'=>int, 'failed'=>int, 'pct'=>int] */
// Layout included automatically by View::render()
?>

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold mb-0">Clinical Test Suite</h2>
            <p class="text-muted mb-0">Kiểm tra chất lượng động cơ chẩn đoán YHCT</p>
        </div>
        <a href="?refresh=1" class="btn btn-outline-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise me-1" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
            </svg>
            Chạy lại
        </a>
    </div>

    <?php if (empty($results)): ?>
        <div class="alert alert-warning">
            Không thể chạy test. Kiểm tra file <code>scripts/run_clinical_tests.php</code>.
        </div>
    <?php else: ?>

    <!-- Summary Card -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-4">
                    <div class="display-4 fw-bold <?= $summary['failed'] == 0 ? 'text-success' : 'text-danger' ?>">
                        <?= $summary['pct'] ?>%
                    </div>
                    <div class="text-muted">Pass Rate</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-4">
                    <div class="display-4 fw-bold text-success"><?= $summary['passed'] ?></div>
                    <div class="text-muted">Passed</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-4">
                    <div class="display-4 fw-bold <?= $summary['failed'] > 0 ? 'text-danger' : 'text-muted' ?>">
                        <?= $summary['failed'] ?>
                    </div>
                    <div class="text-muted">Failed</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-4">
                    <div class="display-4 fw-bold text-primary"><?= $summary['total'] ?></div>
                    <div class="text-muted">Total Tests</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress bar -->
    <div class="mb-4">
        <div class="progress" style="height: 12px;">
            <div class="progress-bar bg-success" style="width: <?= $summary['pct'] ?>%"></div>
            <?php if ($summary['failed'] > 0): ?>
            <div class="progress-bar bg-danger" style="width: <?= 100 - $summary['pct'] ?>%"></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Test Results Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary active" onclick="filterTests('all', this)">Tất cả</button>
                <button class="btn btn-sm btn-outline-danger" onclick="filterTests('fail', this)">Thất bại</button>
                <button class="btn btn-sm btn-outline-success" onclick="filterTests('pass', this)">Đạt</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="testTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px">#</th>
                            <th style="width:80px">Kết quả</th>
                            <th>Tên test</th>
                            <th>Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $i => $r): ?>
                        <tr class="test-row <?= $r['pass'] ? 'pass' : 'fail' ?>">
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td>
                                <?php if ($r['pass']): ?>
                                    <span class="badge bg-success-subtle text-success fw-normal">✓ PASS</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger fw-normal">✗ FAIL</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code class="small text-dark"><?= htmlspecialchars($r['name']) ?></code>
                            </td>
                            <td>
                                <?php foreach ($r['details'] as $d): ?>
                                    <div class="small <?= str_starts_with($d, 'OK:') ? 'text-success' : (str_starts_with($d, 'FAIL:') ? 'text-danger' : 'text-muted') ?>">
                                        <?= htmlspecialchars($d) ?>
                                    </div>
                                <?php endforeach; ?>
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

<script>
function filterTests(type, btn) {
    document.querySelectorAll('.test-row').forEach(row => {
        if (type === 'all') row.style.display = '';
        else if (type === 'pass') row.style.display = row.classList.contains('pass') ? '' : 'none';
        else if (type === 'fail') row.style.display = row.classList.contains('fail') ? '' : 'none';
    });
    document.querySelectorAll('[onclick^="filterTests"]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
</script>
