<?php
/**
 * Admin — Embedding Management (content fragment, layout auto-included)
 * $stats = [['doc_type'=>..., 'total'=>int, 'embedded'=>int], ...]
 */
$stats = $stats ?? [];

$totalAll    = array_sum(array_column($stats, 'total'));
$embeddedAll = array_sum(array_column($stats, 'embedded'));
$pendingAll  = $totalAll - $embeddedAll;
$pctAll      = $totalAll > 0 ? round($embeddedAll / $totalAll * 100) : 0;

$labelMap = [
    'k01_phrase'   => 'K01 — Cụm từ quan sát',
    'k02_symptom'  => 'K02 — Triệu chứng',
    'k04_pattern'  => 'K04 — Chứng bệnh',
    'k05_red_flag' => 'K05 — Cờ đỏ',
    'disease_node' => 'Disease Graph — Bệnh nền',
];
?>
<style>
.embed-provider-card {
    border: 2px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: .9rem 1.1rem;
    cursor: pointer;
    transition: var(--transition);
    background: #fff;
    margin-bottom: .5rem;
}
.embed-provider-card:hover { border-color: var(--color-secondary); }
.embed-provider-card.active {
    border-color: var(--color-primary);
    background: #f0faf4;
}
.embed-provider-name { font-weight: 700; font-size: .92rem; }
.embed-provider-desc { font-size: .78rem; color: var(--color-text-muted); }
.embed-stat-card {
    border-radius: var(--radius-md);
    padding: 1rem 1.25rem;
    border: 1px solid var(--color-border-light);
    background: #fff;
    text-align: center;
}
.embed-stat-num { font-size: 1.6rem; font-weight: 800; color: var(--color-primary); line-height: 1; }
.embed-stat-label { font-size: .75rem; color: var(--color-text-muted); margin-top: .25rem; }
.embed-doc-preview {
    font-size: .8rem;
    color: var(--color-text-muted);
    background: var(--color-bg);
    border-radius: var(--radius-sm);
    padding: .5rem .75rem;
    min-height: 2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: .5rem;
}
</style>

<div class="py-4" style="background:var(--color-bg); min-height:calc(100vh - 120px);">
<div class="container-fluid px-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">
                <i class="bi bi-database-gear me-2 text-success"></i>Quản lý Nhúng Vector (Embeddings)
            </h4>
            <p class="text-muted small mb-0">Tạo vector ngữ nghĩa cho tìm kiếm triệu chứng chính xác hơn.</p>
        </div>
        <a href="<?= BASE_URL ?>admin/dashboard" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Dashboard
        </a>
    </div>

    <div class="row g-4">

        <!-- LEFT: Status & Progress -->
        <div class="col-lg-8">

            <!-- Summary stats -->
            <div class="row g-3 mb-4">
                <?php
                $summaryCards = [
                    ['val' => $totalAll,    'label' => 'Tổng tài liệu',  'color' => 'var(--color-text)'],
                    ['val' => $embeddedAll, 'label' => 'Đã nhúng',       'color' => 'var(--color-primary)'],
                    ['val' => $pendingAll,  'label' => 'Chờ xử lý',      'color' => $pendingAll > 0 ? 'var(--color-accent)' : 'var(--color-text-muted)'],
                    ['val' => $pctAll.'%',  'label' => 'Hoàn thành',     'color' => $pctAll >= 80 ? 'var(--color-primary)' : 'var(--color-accent)'],
                ];
                foreach ($summaryCards as $card):
                ?>
                <div class="col-6 col-md-3">
                    <div class="embed-stat-card">
                        <div class="embed-stat-num" style="color:<?= $card['color'] ?>"><?= h($card['val']) ?></div>
                        <div class="embed-stat-label"><?= $card['label'] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Progress bar -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="fw-semibold">Tiến độ tổng thể</small>
                        <small class="text-muted"><?= $embeddedAll ?>/<?= $totalAll ?></small>
                    </div>
                    <div class="embed-progress-wrap" style="height:20px;">
                        <div class="embed-progress-bar" style="width:<?= $pctAll ?>%; min-width:<?= $pctAll > 0 ? '20px' : '0' ?>;">
                            <span class="embed-progress-label"><?= $pctAll ?>%</span>
                        </div>
                    </div>
                    <?php if ($pendingAll > 0): ?>
                    <small class="text-muted d-block mt-2">Còn <?= $pendingAll ?> tài liệu chưa được nhúng vector.</small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Per-type status table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold border-bottom">
                    <i class="bi bi-table me-2"></i>Trạng thái theo loại tài liệu
                </div>
                <div class="card-body p-0">
                    <table class="admin-table" style="min-width:500px;">
                        <thead>
                            <tr>
                                <th>Loại tài liệu</th>
                                <th style="text-align:center;">Tổng</th>
                                <th style="text-align:center;">Đã nhúng</th>
                                <th style="text-align:center;">Chờ xử lý</th>
                                <th style="text-align:center;">Tiến độ</th>
                                <th style="text-align:center;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($stats)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">
                                Chưa có dữ liệu. Chạy <code>php scripts/seed_embedding_documents.php</code>
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($stats as $row):
                                $pct     = $row['total'] > 0 ? round($row['embedded'] / $row['total'] * 100) : 0;
                                $pending = $row['total'] - $row['embedded'];
                                $dot     = $pct === 100 ? 'complete' : ($pct > 0 ? 'partial' : 'empty');
                                $label   = $labelMap[$row['doc_type']] ?? $row['doc_type'];
                            ?>
                            <tr>
                                <td>
                                    <span class="status-dot <?= $dot ?>"></span>
                                    <strong><?= h($label) ?></strong>
                                </td>
                                <td style="text-align:center;"><?= $row['total'] ?></td>
                                <td style="text-align:center; color:var(--color-primary); font-weight:600;"><?= $row['embedded'] ?></td>
                                <td style="text-align:center; color:<?= $pending > 0 ? 'var(--color-accent)' : 'var(--color-text-muted)' ?>; font-weight:600;"><?= $pending ?></td>
                                <td style="min-width:120px;">
                                    <div class="embed-progress-wrap" style="height:14px;">
                                        <div class="embed-progress-bar" style="width:<?= $pct ?>%; min-width:<?= $pct > 0 ? '12px' : '0' ?>;">
                                            <span class="embed-progress-label" style="font-size:.6rem;"><?= $pct ?>%</span>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align:center;">
                                    <div class="d-flex gap-1 justify-content-center flex-wrap">
                                    <?php if ($pending > 0): ?>
                                        <button class="btn btn-sm btn-success"
                                                style="font-size:.75rem; padding:.25rem .6rem;"
                                                onclick="generateEmbeddings('<?= h($row['doc_type']) ?>')">
                                            <i class="bi bi-play-fill me-1"></i>Nhúng
                                        </button>
                                    <?php else: ?>
                                        <span class="badge bg-success" style="font-size:.7rem; line-height:1.8;">✓ Xong</span>
                                    <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-secondary"
                                                style="font-size:.75rem; padding:.25rem .6rem;"
                                                onclick="showDetail('<?= h($row['doc_type']) ?>', '<?= h($labelMap[$row['doc_type']] ?? $row['doc_type']) ?>')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Detail view panel -->
            <div class="card border-0 shadow-sm mt-3" id="detail-panel" style="display:none;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                    <span class="fw-semibold small"><i class="bi bi-list-ul me-1"></i>Chi tiết tài liệu — <span id="detail-doctype-label"></span></span>
                    <button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('detail-panel').style.display='none'">✕</button>
                </div>
                <div class="card-body p-0" style="max-height:320px; overflow-y:auto;">
                    <table class="admin-table" style="font-size:.78rem;">
                        <thead>
                            <tr>
                                <th>source_id</th>
                                <th style="text-align:center;">Embedded</th>
                                <th>Cập nhật</th>
                                <th>Nội dung (100 ký tự)</th>
                            </tr>
                        </thead>
                        <tbody id="detail-tbody">
                            <tr><td colspan="4" class="text-center text-muted py-3">Đang tải...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center" style="font-size:.78rem;">
                    <span id="detail-pager-info" class="text-muted"></span>
                    <div class="d-flex gap-1">
                        <button class="btn btn-xs btn-outline-secondary" id="detail-prev" onclick="loadDetail(-1)">‹ Trước</button>
                        <button class="btn btn-xs btn-outline-secondary" id="detail-next" onclick="loadDetail(1)">Tiếp ›</button>
                    </div>
                </div>
            </div>

            <!-- Live progress panel -->
            <div class="card border-0 shadow-sm mt-3" id="embed-progress-panel" style="display:none;">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-semibold small"><i class="bi bi-gear-fill me-1 text-success"></i>Đang xử lý...</span>
                        <button class="btn btn-sm btn-outline-danger" id="cancel-embed-btn" onclick="EmbeddingAdmin.cancelled=true;">✕ Hủy</button>
                    </div>
                    <div id="embed-progress-wrap" style="display:none;">
                        <div class="embed-progress-wrap mb-2" style="height:20px;">
                            <div class="embed-progress-bar striped" id="embed-progress-bar" style="width:0%;">
                                <span class="embed-progress-label">0%</span>
                            </div>
                        </div>
                    </div>
                    <div class="embed-doc-preview" id="doc-preview">—</div>
                </div>
            </div>

        </div><!-- /col-lg-8 -->

        <!-- RIGHT: Settings -->
        <div class="col-lg-4">

            <!-- Model status -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="fw-semibold mb-2"><i class="bi bi-robot me-2"></i>Trạng thái mô hình</div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="text-muted small">Trạng thái:</span>
                        <span class="badge bg-secondary" id="model-status">Chưa tải</span>
                    </div>
                    <button class="btn btn-outline-success btn-sm w-100 mb-2" id="load-model-btn"
                            onclick="EmbeddingAdmin.loadModel()">
                        <i class="bi bi-download me-1"></i>Tải mô hình AI vào trình duyệt
                    </button>
                    <p class="text-muted small mb-0">
                        Chạy trong trình duyệt (WASM). Lần đầu ~25–120 MB, sau đó cache tự động.
                    </p>
                </div>
            </div>

            <!-- Provider selector -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="fw-semibold mb-2"><i class="bi bi-lightning me-2"></i>Chọn nhà cung cấp</div>

                    <label class="embed-provider-card active" data-provider="browser_minilm">
                        <input type="radio" name="embed_provider" id="embed-provider" value="browser_minilm" checked class="d-none">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="embed-provider-name">Browser — MiniLM-L6</div>
                                <div class="embed-provider-desc">Nhỏ, nhanh, tiếng Anh / đa ngôn ngữ cơ bản. Offline.</div>
                            </div>
                            <span class="badge bg-success align-self-start">Mặc định</span>
                        </div>
                    </label>

                    <label class="embed-provider-card" data-provider="browser_vietnamese">
                        <input type="radio" name="embed_provider" value="browser_vietnamese" class="d-none">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="embed-provider-name">Browser — Multilingual-L12</div>
                                <div class="embed-provider-desc">Tốt hơn cho tiếng Việt (~120 MB). Offline.</div>
                            </div>
                            <span class="badge bg-info align-self-start">Tốt hơn</span>
                        </div>
                    </label>

                    <label class="embed-provider-card" data-provider="openai_3small">
                        <input type="radio" name="embed_provider" value="openai_3small" class="d-none">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="embed-provider-name">OpenAI — text-embedding-3-small</div>
                                <div class="embed-provider-desc">Chất lượng cao. Cần API key + internet.</div>
                            </div>
                            <span class="badge bg-warning text-dark align-self-start">Cần API key</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Build index -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="fw-semibold mb-2"><i class="bi bi-folder-check me-2"></i>Xây dựng chỉ mục</div>
                    <p class="text-muted small mb-2">
                        Sau khi nhúng xong, tạo file <code>.bin</code> tại <code>public/embeddings/</code>
                        để tìm kiếm vector nhanh.
                    </p>
                    <button class="btn btn-success btn-sm w-100" id="build-index-btn"
                            onclick="EmbeddingAdmin.buildIndex()">
                        <i class="bi bi-folder-check me-1"></i>Xây dựng chỉ mục
                    </button>
                    <?php if ($pendingAll > 0): ?>
                    <div class="alert alert-warning py-1 mt-2 small">
                        ⚠ Còn <?= $pendingAll ?> tài liệu chưa nhúng — chỉ mục sẽ không đầy đủ.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reset embeddings -->
            <div class="card border-0 shadow-sm mb-3" style="border-color:#f5c6cb !important;">
                <div class="card-body">
                    <div class="fw-semibold mb-2 text-danger"><i class="bi bi-arrow-counterclockwise me-2"></i>Reset & Nhúng lại</div>
                    <p class="text-muted small mb-2">
                        Xóa toàn bộ vector đã lưu để nhúng lại từ đầu. Cần thiết khi KB thay đổi nhiều hoặc muốn đổi mô hình.
                    </p>
                    <div class="d-flex gap-2 mb-2">
                        <select class="form-select form-select-sm" id="reset-doctype-select" style="font-size:.8rem;">
                            <option value="">— Toàn bộ —</option>
                            <?php foreach ($stats as $row): ?>
                            <option value="<?= h($row['doc_type']) ?>"><?= h($labelMap[$row['doc_type']] ?? $row['doc_type']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-danger btn-sm" style="white-space:nowrap;"
                                onclick="resetEmbeddings()">
                            <i class="bi bi-trash me-1"></i>Reset
                        </button>
                    </div>
                    <button class="btn btn-outline-secondary btn-sm w-100" onclick="reseedDocuments()">
                        <i class="bi bi-database-add me-1"></i>Seed lại tài liệu (sync KB mới)
                    </button>
                </div>
            </div>

            <!-- Guide -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold mb-2"><i class="bi bi-info-circle me-2"></i>Hướng dẫn</div>
                    <ol class="small text-muted mb-0" style="padding-left:1.2rem; line-height:1.8;">
                        <li>Chọn nhà cung cấp mô hình phù hợp.</li>
                        <li>Nhấn <strong>Tải mô hình AI</strong> (chỉ cần 1 lần, sau đó cache).</li>
                        <li>Nhấn <strong>Nhúng</strong> cho từng loại tài liệu.</li>
                        <li>Sau khi xong, nhấn <strong>Xây dựng chỉ mục</strong>.</li>
                        <li>Tìm kiếm ngữ nghĩa hoạt động ngay.</li>
                    </ol>
                    <hr>
                    <small class="text-muted">
                        <strong>Checkpoint:</strong> Tiến trình lưu trong localStorage — nếu bị gián đoạn, nhấn Nhúng để tiếp tục từ điểm dừng.
                    </small>
                </div>
            </div>

        </div><!-- /col-lg-4 -->
    </div><!-- /row -->

</div>
</div>

<script>
// Provider card click handler
document.querySelectorAll('.embed-provider-card').forEach(function(card) {
    card.addEventListener('click', function() {
        document.querySelectorAll('.embed-provider-card').forEach(function(c) { c.classList.remove('active'); });
        this.classList.add('active');
        var radio = this.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
        // Sync hidden input used by EmbeddingAdmin
        var providerInput = document.getElementById('embed-provider');
        if (providerInput) providerInput.value = this.querySelector('input').value;
    });
});

// Show progress panel when embedding starts
var _origGenerate = EmbeddingAdmin.generateEmbeddings.bind(EmbeddingAdmin);
EmbeddingAdmin.generateEmbeddings = function(docType) {
    document.getElementById('embed-progress-panel').style.display = 'block';
    document.getElementById('embed-progress-wrap').style.display = 'block';
    return _origGenerate(docType);
};

// After embedding completes: refresh stats row in-place (no page reload, model stays loaded)
EmbeddingAdmin.onComplete = function(docType, processedCount) {
    // Fetch fresh stats from server for this doc_type
    App.get('admin/api/docs-pending?doc_type=' + encodeURIComponent(docType) + '&offset=0')
        .then(function(resp) {
            var pending = resp.total || 0;
            _refreshTableRow(docType, pending, processedCount);
        })
        .catch(function() {
            // Fallback: assume all embedded
            _refreshTableRow(docType, 0, processedCount);
        });

    // Hide progress panel
    var panel = document.getElementById('embed-progress-panel');
    if (panel) panel.style.display = 'none';
};

function _refreshTableRow(docType, remaining, processed) {
    // Find the row by looking for a button with data-doc-type or the Nhúng button's onclick
    var rows = document.querySelectorAll('tbody tr');
    rows.forEach(function(row) {
        var btn = row.querySelector('button[onclick]');
        if (!btn) return;
        if (btn.getAttribute('onclick').indexOf("'" + docType + "'") === -1) return;

        var cells = row.querySelectorAll('td');
        if (cells.length < 6) return;

        var totalCell    = cells[1]; // Tổng
        var embeddedCell = cells[2]; // Đã nhúng
        var pendingCell  = cells[3]; // Chờ xử lý
        var progressCell = cells[4]; // Tiến độ
        var actionCell   = cells[5]; // Thao tác

        var total    = parseInt(totalCell.textContent.trim(), 10) || (processed + remaining);
        var embedded = total - remaining;
        var pct      = total > 0 ? Math.round(embedded / total * 100) : 100;

        // Update dot
        var dot = row.querySelector('.status-dot');
        if (dot) {
            dot.className = 'status-dot ' + (pct === 100 ? 'complete' : pct > 0 ? 'partial' : 'empty');
        }

        embeddedCell.textContent = embedded;
        embeddedCell.style.color = 'var(--color-primary)';
        pendingCell.textContent  = remaining;
        pendingCell.style.color  = remaining > 0 ? 'var(--color-accent)' : 'var(--color-text-muted)';

        // Progress bar
        var bar = progressCell.querySelector('.embed-progress-bar');
        if (bar) {
            bar.style.width = pct + '%';
            var label = bar.querySelector('.embed-progress-label');
            if (label) label.textContent = pct + '%';
        }

        // Action cell
        if (remaining === 0) {
            actionCell.innerHTML = '<span class="badge bg-success" style="font-size:.7rem;">✓ Hoàn thành</span>';
        }
    });

    // Update summary cards (total embedded + pct)
    _refreshSummaryCards();
}

function _refreshSummaryCards() {
    var rows     = document.querySelectorAll('tbody tr');
    var totalAll = 0, embeddedAll = 0;

    rows.forEach(function(row) {
        var cells = row.querySelectorAll('td');
        if (cells.length < 4) return;
        totalAll    += parseInt(cells[1].textContent.trim(), 10) || 0;
        embeddedAll += parseInt(cells[2].textContent.trim(), 10) || 0;
    });

    var pendingAll = totalAll - embeddedAll;
    var pctAll     = totalAll > 0 ? Math.round(embeddedAll / totalAll * 100) : 0;

    // Update the 4 summary stat cards
    var statNums = document.querySelectorAll('.embed-stat-num');
    if (statNums.length >= 4) {
        statNums[1].textContent = embeddedAll;
        statNums[2].textContent = pendingAll;
        statNums[2].style.color = pendingAll > 0 ? 'var(--color-accent)' : 'var(--color-text-muted)';
        statNums[3].textContent = pctAll + '%';
        statNums[3].style.color = pctAll >= 80 ? 'var(--color-primary)' : 'var(--color-accent)';
    }

    // Update overall progress bar
    var overallBar = document.querySelector('.embed-progress-wrap .embed-progress-bar');
    if (overallBar) {
        overallBar.style.width = pctAll + '%';
        var label = overallBar.querySelector('.embed-progress-label');
        if (label) label.textContent = pctAll + '% (' + embeddedAll + '/' + totalAll + ')';
    }
}

// ── Detail viewer ─────────────────────────────────────────────────────────────

var _detailDocType = '';
var _detailPage    = 0;
var _detailTotal   = 0;

function showDetail(docType, label) {
    _detailDocType = docType;
    _detailPage    = 0;
    document.getElementById('detail-doctype-label').textContent = label;
    document.getElementById('detail-panel').style.display = 'block';
    document.getElementById('detail-panel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    _fetchDetail();
}

function loadDetail(dir) {
    _detailPage = Math.max(0, _detailPage + dir);
    _fetchDetail();
}

function _fetchDetail() {
    var tbody = document.getElementById('detail-tbody');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Đang tải...</td></tr>';

    App.get('admin/api/docs-detail?doc_type=' + encodeURIComponent(_detailDocType) + '&page=' + _detailPage)
        .then(function(resp) {
            var docs  = resp.docs  || [];
            var total = resp.total || 0;
            var limit = resp.limit || 50;
            _detailTotal = total;

            var html = '';
            if (!docs.length) {
                html = '<tr><td colspan="4" class="text-center text-muted py-3">Không có dữ liệu.</td></tr>';
            } else {
                docs.forEach(function(d) {
                    var icon  = d.has_embedding ? '<span class="text-success">✓</span>' : '<span class="text-danger">✗</span>';
                    var ts    = d.embedding_updated_at ? d.embedding_updated_at.replace('T',' ') : '—';
                    var prev  = (d.preview || '').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                    html += '<tr>'
                          + '<td style="font-size:.72rem; font-family:monospace;">' + (d.source_id||'') + '</td>'
                          + '<td style="text-align:center;">' + icon + '</td>'
                          + '<td style="font-size:.72rem; white-space:nowrap;">' + ts + '</td>'
                          + '<td style="font-size:.72rem; color:var(--color-text-muted); max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="' + prev + '">' + prev + '</td>'
                          + '</tr>';
                });
            }
            tbody.innerHTML = html;

            var pages    = Math.ceil(total / limit);
            var curPage  = _detailPage + 1;
            document.getElementById('detail-pager-info').textContent =
                'Trang ' + curPage + '/' + pages + ' — ' + total + ' tài liệu';
            document.getElementById('detail-prev').disabled = (_detailPage === 0);
            document.getElementById('detail-next').disabled = (curPage >= pages);
        })
        .catch(function(err) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-danger text-center py-3">Lỗi: ' + err.message + '</td></tr>';
        });
}

// ── Reset embeddings ──────────────────────────────────────────────────────────

function resetEmbeddings() {
    var docType = document.getElementById('reset-doctype-select').value;
    var label   = docType
        ? document.getElementById('reset-doctype-select').selectedOptions[0].text
        : 'TOÀN BỘ dữ liệu';

    if (!confirm('Xác nhận reset embedding của ' + label + '?\nHành động này không thể hoàn tác.')) return;

    App.post('admin/api/reset-embeddings', { doc_type: docType })
        .then(function(resp) {
            App.showAlert('Đã reset ' + resp.reset + ' tài liệu. File xóa: ' + (resp.removed_files || []).join(', '), 'warning');
            // Clear localStorage checkpoints
            if (docType) {
                localStorage.removeItem('yhct_embed_' + docType);
            } else {
                Object.keys(localStorage).filter(k => k.startsWith('yhct_embed_')).forEach(k => localStorage.removeItem(k));
            }
            setTimeout(() => location.reload(), 1500);
        })
        .catch(function(err) {
            App.showAlert('Lỗi reset: ' + err.message, 'danger');
        });
}

// ── Reseed documents ──────────────────────────────────────────────────────────

function reseedDocuments() {
    if (!confirm('Chạy lại seed_embedding_documents.php?\nCác embedding hợp lệ (nội dung không đổi) sẽ được giữ nguyên.')) return;
    App.showLoading('Đang seed lại tài liệu...');
    App.post('admin/api/reseed', {})
        .then(function(resp) {
            App.hideLoading();
            App.showAlert(resp.message || 'Đã seed xong.', 'success');
            setTimeout(() => location.reload(), 1500);
        })
        .catch(function(err) {
            App.hideLoading();
            App.showAlert('Lỗi seed: ' + err.message, 'danger');
        });
}
</script>
