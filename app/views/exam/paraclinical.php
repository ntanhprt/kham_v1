<?php
/**
 * Exam Step 4 — Cận lâm sàng (Paraclinical Tests)
 *
 * Variables from controller:
 *   $hints        — array grouped by category_vi => [ test_code => hint_data ]
 *   $top_patterns — array of top 5 candidate patterns with scores
 *   $session      — current exam session
 */
$hints       = $hints ?? [];
$topPatterns = $top_patterns ?? [];
$stepLabels  = ['Mô tả', 'Làm rõ', 'Triệu chứng', 'Câu hỏi', 'Cận lâm sàng', 'Kết quả'];
$hasHints    = !empty($hints);
?>
<style>
.pcl-wrap {
    max-width: 720px;
    margin: 0 auto;
    padding: 1rem 0 3rem;
}
.pcl-header {
    background: linear-gradient(135deg, #E3F2FD, #E8EAF6);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
    border-left: 4px solid #1565C0;
}
.pcl-header h2 { margin:0 0 .25rem; font-size:1.15rem; color:#1565C0; }
.pcl-header p  { margin:0; color:#455A64; font-size:.9rem; }

/* Top patterns badge row */
.pattern-badges { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1.5rem; }
.pat-badge {
    background:#fff;
    border:1.5px solid #90CAF9;
    border-radius:20px;
    padding:.25rem .85rem;
    font-size:.82rem;
    color:#1565C0;
    display:flex; align-items:center; gap:.4rem;
}
.pat-badge .score { font-weight:700; }

/* Category section */
.cat-section { margin-bottom:1.5rem; }
.cat-title {
    font-size:.8rem;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.05em;
    color:#78909C;
    padding:.5rem 0 .25rem;
    border-bottom:2px solid #E3F2FD;
    margin-bottom:.75rem;
    display:flex; align-items:center; gap:.5rem;
}
.cat-icon { font-size:1rem; }

/* Test card */
.test-card {
    background:#fff;
    border:1.5px solid #E8EAF6;
    border-radius:12px;
    padding:1rem 1.25rem;
    margin-bottom:.75rem;
    transition:border-color .2s, box-shadow .2s;
}
.test-card.has-result { border-color:#1565C0; box-shadow:0 2px 8px rgba(21,101,192,.08); }
.test-name { font-weight:600; color:#1A237E; margin-bottom:.15rem; font-size:.95rem; }
.test-note { font-size:.82rem; color:#607D8B; margin-bottom:.75rem; }
.test-suggested { font-size:.78rem; color:#9E9E9E; margin-bottom:.75rem; }

/* Radio group */
.result-radios { display:flex; gap:.6rem; flex-wrap:wrap; margin-bottom:.5rem; }
.result-radio { display:none; }
.result-radio + label {
    border:1.5px solid #CFD8DC;
    border-radius:8px;
    padding:.3rem .85rem;
    font-size:.85rem;
    cursor:pointer;
    color:#546E7A;
    transition:all .15s;
    user-select:none;
}
.result-radio:checked + label { font-weight:600; }
.result-radio[value="normal"]:checked + label   { border-color:#2E7D32; color:#2E7D32; background:#E8F5E9; }
.result-radio[value="abnormal"]:checked + label { border-color:#C62828; color:#C62828; background:#FFEBEE; }
.result-radio[value="not_done"]:checked + label { border-color:#78909C; color:#78909C; background:#ECEFF1; }

/* Abnormal details */
.abnormal-details { display:none; margin-top:.5rem; }
.abnormal-details.visible { display:block; }
.direction-select {
    border:1.5px solid #CFD8DC; border-radius:8px;
    padding:.35rem .75rem; font-size:.85rem; margin-bottom:.5rem; width:100%;
    color:#37474F;
}
.findings-input {
    border:1.5px solid #CFD8DC; border-radius:8px;
    padding:.4rem .75rem; font-size:.85rem; width:100%;
    color:#37474F; resize:vertical; min-height:58px;
}
.findings-input:focus, .direction-select:focus {
    outline:none; border-color:#1565C0;
    box-shadow:0 0 0 3px rgba(21,101,192,.08);
}

/* Empty state */
.pcl-empty {
    text-align:center; padding:2.5rem 1rem;
    color:#90A4AE; font-size:.95rem;
}
.pcl-empty .icon { font-size:2.5rem; margin-bottom:.75rem; display:block; }

/* Action bar */
.pcl-actions {
    display:flex; gap:1rem; justify-content:space-between;
    align-items:center; margin-top:2rem;
    padding-top:1rem; border-top:1px solid #E8EAF6;
}
.btn-primary {
    background:#1565C0; color:#fff; border:none; border-radius:10px;
    padding:.7rem 2rem; font-size:.95rem; font-weight:600; cursor:pointer;
    transition:background .15s;
}
.btn-primary:hover { background:#0D47A1; }
.btn-skip {
    color:#90A4AE; font-size:.85rem; text-decoration:none;
    padding:.4rem .75rem; border-radius:8px;
    transition:color .15s;
}
.btn-skip:hover { color:#607D8B; }
</style>

<?php
// Progress bar
$currentStep = 5; // step index (1-based: Mô tả=1 … Kết quả=6)
?>
<div class="pcl-wrap">

    <!-- Progress bar -->
    <div style="margin-bottom:1.5rem;">
        <div style="display:flex;gap:0;border-radius:8px;overflow:hidden;height:6px;background:#E8EAF6;">
            <?php foreach ($stepLabels as $i => $label): ?>
                <div style="flex:1;background:<?= ($i + 1 <= $currentStep) ? '#1565C0' : '#E8EAF6' ?>;"></div>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;margin-top:.4rem;">
            <?php foreach ($stepLabels as $i => $label): ?>
                <div style="flex:1;text-align:center;font-size:.7rem;color:<?= ($i + 1 == $currentStep) ? '#1565C0' : (($i + 1 < $currentStep) ? '#78909C' : '#B0BEC5') ?>;font-weight:<?= ($i + 1 == $currentStep) ? '700' : '400' ?>;">
                    <?= htmlspecialchars($label) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Header -->
    <div class="pcl-header">
        <h2>🔬 Kết quả cận lâm sàng</h2>
        <p>Nếu bạn đã có kết quả xét nghiệm/chẩn đoán hình ảnh, hãy điền vào để tăng độ chính xác chẩn đoán. Bạn có thể bỏ qua nếu chưa làm.</p>
    </div>

    <!-- Top candidate patterns -->
    <?php if (!empty($topPatterns)): ?>
    <div style="margin-bottom:1rem;">
        <div style="font-size:.8rem;color:#78909C;margin-bottom:.5rem;">Gợi ý xét nghiệm dựa trên các chứng hội hàng đầu:</div>
        <div class="pattern-badges">
            <?php foreach ($topPatterns as $p): ?>
            <div class="pat-badge">
                🌿 <?= htmlspecialchars($p['name_vi'] ?? $p['chung_code'] ?? '') ?>
                <span class="score"><?= round(($p['score'] ?? 0) * 100) ?>%</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Test form -->
    <form method="POST" action="<?= BASE_URL ?>exam/paraclinical" id="pcl-form">
        <?php if ($hasHints): ?>
            <?php
            $catIcons = [
                'Xét nghiệm máu'        => '🩸',
                'Xét nghiệm nước tiểu'  => '🧪',
                'Chẩn đoán hình ảnh'    => '📷',
                'Thăm dò chức năng'     => '📊',
                'Xét nghiệm vi sinh'    => '🦠',
                'Xét nghiệm sinh sản'   => '🧬',
                'Xét nghiệm phụ khoa'   => '🩺',
                'Khác'                  => '📋',
            ];
            ?>
            <?php foreach ($hints as $category => $tests): ?>
            <div class="cat-section">
                <div class="cat-title">
                    <span class="cat-icon"><?= $catIcons[$category] ?? '📋' ?></span>
                    <?= htmlspecialchars($category) ?>
                </div>
                <?php foreach ($tests as $testCode => $hint): ?>
                <div class="test-card" id="card-<?= htmlspecialchars($testCode) ?>">
                    <!-- Hidden fields -->
                    <input type="hidden" name="tests[<?= htmlspecialchars($testCode) ?>][test_name_vi]" value="<?= htmlspecialchars($hint['test_name_vi'] ?? '') ?>">
                    <input type="hidden" name="tests[<?= htmlspecialchars($testCode) ?>][pattern_code]" value="<?= htmlspecialchars($hint['suggested_by_code'] ?? '') ?>">

                    <div class="test-name"><?= htmlspecialchars($hint['test_name_vi'] ?? $testCode) ?></div>
                    <div class="test-note"><?= htmlspecialchars($hint['note_vi'] ?? '') ?></div>
                    <?php if (!empty($hint['suggested_by_name'])): ?>
                    <div class="test-suggested">💡 Gợi ý bởi: <?= htmlspecialchars($hint['suggested_by_name']) ?></div>
                    <?php endif; ?>

                    <!-- Status radios -->
                    <div class="result-radios">
                        <input type="radio" class="result-radio" name="tests[<?= htmlspecialchars($testCode) ?>][status]"
                               id="<?= htmlspecialchars($testCode) ?>_notdone" value="not_done" checked
                               onchange="toggleDetails('<?= htmlspecialchars($testCode) ?>', this.value)">
                        <label for="<?= htmlspecialchars($testCode) ?>_notdone">Chưa làm</label>

                        <input type="radio" class="result-radio" name="tests[<?= htmlspecialchars($testCode) ?>][status]"
                               id="<?= htmlspecialchars($testCode) ?>_normal" value="normal"
                               onchange="toggleDetails('<?= htmlspecialchars($testCode) ?>', this.value)">
                        <label for="<?= htmlspecialchars($testCode) ?>_normal">✓ Bình thường</label>

                        <input type="radio" class="result-radio" name="tests[<?= htmlspecialchars($testCode) ?>][status]"
                               id="<?= htmlspecialchars($testCode) ?>_abnormal" value="abnormal"
                               onchange="toggleDetails('<?= htmlspecialchars($testCode) ?>', this.value)">
                        <label for="<?= htmlspecialchars($testCode) ?>_abnormal">⚠ Bất thường</label>
                    </div>

                    <!-- Abnormal detail fields (shown only when abnormal selected) -->
                    <div class="abnormal-details" id="details-<?= htmlspecialchars($testCode) ?>">
                        <select class="direction-select" name="tests[<?= htmlspecialchars($testCode) ?>][direction]">
                            <option value="any">— Mức độ bất thường —</option>
                            <option value="elevated">Tăng cao</option>
                            <option value="decreased">Giảm thấp</option>
                            <option value="present">Dương tính / Có mặt</option>
                            <option value="absent">Âm tính / Vắng mặt</option>
                            <option value="any">Bất thường (không xác định hướng)</option>
                        </select>
                        <textarea class="findings-input"
                                  name="tests[<?= htmlspecialchars($testCode) ?>][findings]"
                                  placeholder="Mô tả kết quả cụ thể (ví dụ: Eosinophil 12%, IgE 450 IU/mL...)"></textarea>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="pcl-empty">
                <span class="icon">🔬</span>
                Không có gợi ý xét nghiệm cụ thể cho tình trạng này.<br>
                Bạn có thể bỏ qua và xem kết quả ngay.
            </div>
        <?php endif; ?>

        <!-- Action bar -->
        <div class="pcl-actions">
            <a href="<?= BASE_URL ?>exam/result-skip" class="btn-skip"
               onclick="return skipToResult()">⏭ Bỏ qua, xem kết quả ngay</a>
            <button type="submit" class="btn-primary" id="submit-btn">
                Lưu kết quả &amp; Xem chẩn đoán →
            </button>
        </div>
    </form>
</div>

<script>
function toggleDetails(testCode, status) {
    const details = document.getElementById('details-' + testCode);
    const card    = document.getElementById('card-' + testCode);
    if (status === 'abnormal') {
        details.classList.add('visible');
        card.classList.add('has-result');
    } else {
        details.classList.remove('visible');
        card.classList.toggle('has-result', status === 'normal');
    }
}

function skipToResult() {
    // Submit form with all tests as not_done — engine will just use preliminary result
    const form = document.getElementById('pcl-form');
    // Add hidden field to signal skip
    const inp = document.createElement('input');
    inp.type = 'hidden'; inp.name = 'skip'; inp.value = '1';
    form.appendChild(inp);
    form.submit();
    return false;
}

// Auto-mark card when normal selected
document.querySelectorAll('.result-radio').forEach(r => {
    if (r.value === 'normal') {
        r.addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('card-' + this.name.match(/tests\[(.+?)\]/)[1])
                    ?.classList.add('has-result');
            }
        });
    }
});
</script>
