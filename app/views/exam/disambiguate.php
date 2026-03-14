<?php
$topResults = $top_results ?? [];
$sessionId  = $session_id  ?? '';
?>
<style>
.disambig-card {
    max-width: 640px;
    margin: 0 auto;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 4px 24px rgba(46,125,50,0.08);
    border: 1px solid #E8F5E9;
    overflow: hidden;
}
.disambig-header {
    background: linear-gradient(135deg, #2E7D32, #388E3C);
    color: #fff;
    padding: 1.5rem 2rem;
}
.disambig-body {
    padding: 2rem;
}
.disambig-option {
    border: 2px solid #E8F5E9;
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 12px;
}
.disambig-option:hover {
    border-color: #2E7D32;
    background: #F1F8E9;
}
.disambig-option.selected {
    border-color: #2E7D32;
    background: #E8F5E9;
}
.disambig-option .opt-check {
    width: 22px;
    height: 22px;
    border: 2px solid #ccc;
    border-radius: 6px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2E7D32;
    transition: all 0.2s;
}
.disambig-option.selected .opt-check {
    border-color: #2E7D32;
    background: #2E7D32;
    color: #fff;
}
.none-option {
    border-style: dashed;
    border-color: #ccc;
    color: #888;
}
.none-option:hover {
    border-color: #888;
    background: #f9f9f9;
    color: #444;
}
</style>

<div class="py-5" style="background:linear-gradient(180deg,#F1F8E9 0%,#F9F7F4 100%); min-height:calc(100vh - 120px);">
    <div class="container">
        <div class="disambig-card">
            <div class="disambig-header">
                <div class="d-flex align-items-center gap-3">
                    <div style="font-size:2rem;">🔍</div>
                    <div>
                        <h4 class="mb-1 fw-bold">Làm rõ triệu chứng</h4>
                        <p class="mb-0 opacity-75 small">
                            Chọn mô tả phù hợp nhất với tình trạng của bạn
                        </p>
                    </div>
                </div>
            </div>

            <div class="disambig-body">
                <p class="text-muted mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    Dựa trên mô tả của bạn, chúng tôi tìm thấy một số hướng triệu chứng.
                    Bạn đang gặp vấn đề về:
                </p>

                <form method="POST" action="<?= BASE_URL ?>exam/disambiguate">
                    <input type="hidden" name="session_id" value="<?= htmlspecialchars($sessionId) ?>">

                    <?php if (!empty($topResults)): ?>
                        <?php foreach ($topResults as $result): ?>
                        <div class="disambig-option"
                             onclick="toggleOption(this)"
                             data-value="<?= htmlspecialchars($result['code'] ?? '') ?>">
                            <div class="opt-check">
                                <i class="bi bi-check-lg" style="font-size:0.75rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">
                                    <?= htmlspecialchars($result['name'] ?? $result['phrase_vi'] ?? $result['code'] ?? '') ?>
                                </div>
                                <?php if (!empty($result['description'])): ?>
                                <div class="text-muted small mt-1">
                                    <?= htmlspecialchars($result['description']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($result['score'])): ?>
                            <span class="badge bg-light text-muted border">
                                <?= round($result['score'] * 100) ?>%
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Không tìm thấy kết quả gần khớp. Vui lòng mô tả lại.
                        </div>
                    <?php endif; ?>

                    <!-- None option -->
                    <div class="disambig-option none-option" onclick="selectNone(this)">
                        <div class="opt-check" id="noneCheck">
                            <i class="bi bi-x-lg" style="font-size:0.75rem;"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">Không có cái nào phù hợp</div>
                            <div class="small">Tôi muốn mô tả lại triệu chứng</div>
                        </div>
                    </div>

                    <input type="hidden" name="selected_options" id="selectedOptionsInput" value="">
                    <input type="hidden" name="none_selected" id="noneSelectedInput" value="0">

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-success flex-grow-1 rounded-pill" id="btnConfirm">
                            <i class="bi bi-check-lg me-2"></i>Xác nhận và tiếp tục
                        </button>
                        <a href="<?= BASE_URL ?>exam/start" class="btn btn-outline-secondary rounded-pill">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
var selectedOptions = [];
var noneSelected    = false;

function toggleOption(el) {
    if (noneSelected) { clearNone(); }

    var val = el.dataset.value;
    var idx = selectedOptions.indexOf(val);
    if (idx === -1) {
        selectedOptions.push(val);
        el.classList.add('selected');
    } else {
        selectedOptions.splice(idx, 1);
        el.classList.remove('selected');
    }
    updateHiddenInputs();
}

function selectNone(el) {
    // Deselect all others
    document.querySelectorAll('.disambig-option:not(.none-option)').forEach(function(o) {
        o.classList.remove('selected');
    });
    selectedOptions = [];

    noneSelected = !noneSelected;
    if (noneSelected) {
        el.classList.add('selected');
        el.querySelector('.opt-check').style.background = '#666';
        el.querySelector('.opt-check').style.borderColor = '#666';
    } else {
        clearNone(el);
    }
    updateHiddenInputs();
}

function clearNone() {
    noneSelected = false;
    var noneEl = document.querySelector('.none-option');
    if (noneEl) {
        noneEl.classList.remove('selected');
        noneEl.querySelector('.opt-check').style.background = '';
        noneEl.querySelector('.opt-check').style.borderColor = '';
    }
}

function updateHiddenInputs() {
    document.getElementById('selectedOptionsInput').value = selectedOptions.join(',');
    document.getElementById('noneSelectedInput').value    = noneSelected ? '1' : '0';
    document.getElementById('btnConfirm').disabled        = selectedOptions.length === 0 && !noneSelected;
}

updateHiddenInputs();
</script>
