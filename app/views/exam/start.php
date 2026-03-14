<?php
$stepLabels = ['Mô tả', 'Triệu chứng', 'Câu hỏi', 'Kết quả'];
?>
<style>
.exam-container {
    max-width: 720px;
    margin: 0 auto;
}
.exam-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 4px 24px rgba(46,125,50,0.08);
    border: 1px solid #E8F5E9;
    padding: 2.5rem;
}
.chief-complaint-area {
    border: 2px solid #A5D6A7;
    border-radius: 14px;
    padding: 1rem 1.25rem;
    font-size: 1rem;
    line-height: 1.6;
    width: 100%;
    resize: vertical;
    min-height: 120px;
    transition: border-color 0.2s, box-shadow 0.2s;
    font-family: inherit;
    color: #1A1A1A;
}
.chief-complaint-area:focus {
    border-color: #2E7D32;
    box-shadow: 0 0 0 3px rgba(46,125,50,0.12);
    outline: none;
}
.char-counter {
    font-size: 0.75rem;
    color: #aaa;
    text-align: right;
}
.char-counter.warning { color: #FF8F00; }
.char-counter.danger  { color: #C62828; }
.example-chip {
    display: inline-block;
    background: #E8F5E9;
    border: 1px solid #A5D6A7;
    border-radius: 16px;
    padding: 5px 14px;
    font-size: 0.8rem;
    color: #2E7D32;
    cursor: pointer;
    margin: 3px;
    transition: all 0.2s;
}
.example-chip:hover {
    background: #2E7D32;
    color: #fff;
    border-color: #2E7D32;
}
.btn-analyze {
    background: linear-gradient(135deg, #2E7D32, #43A047);
    border: none;
    color: #fff;
    font-weight: 700;
    font-size: 1.05rem;
    padding: 14px 40px;
    border-radius: 50px;
    transition: all 0.25s;
    box-shadow: 0 4px 16px rgba(46,125,50,0.3);
}
.btn-analyze:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(46,125,50,0.4);
    color: #fff;
}
.btn-analyze:disabled {
    opacity: 0.6;
    transform: none;
}
.tip-card {
    background: #F1F8E9;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    border: 1px solid #C8E6C9;
}
.tip-card .tip-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 6px;
    font-size: 0.875rem;
    color: #444;
}
.tip-card .tip-item:last-child { margin-bottom: 0; }
.tip-icon { color: #2E7D32; margin-top: 2px; flex-shrink: 0; }
</style>

<div class="py-4 py-md-5" style="background: linear-gradient(180deg, #F1F8E9 0%, #F9F7F4 100%); min-height: calc(100vh - 120px);">
    <div class="container exam-container">

        <!-- Progress Steps -->
        <div class="mb-4">
            <div class="progress-steps">
                <?php foreach ($stepLabels as $i => $label):
                    $stepNum = $i + 1;
                    $isDone  = $stepNum < ($step ?? 1);
                    $isActive = $stepNum === ($step ?? 1);
                ?>
                    <?php if ($i > 0): ?>
                    <div class="step-line <?= $isDone ? 'done' : '' ?>"></div>
                    <?php endif; ?>
                    <div class="progress-step <?= $isActive ? 'active' : ($isDone ? 'done' : '') ?>">
                        <div class="step-circle">
                            <?php if ($isDone): ?>
                            <i class="bi bi-check-lg"></i>
                            <?php else: ?>
                            <?= $stepNum ?>
                            <?php endif; ?>
                        </div>
                        <div class="step-label"><?= $label ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main Card -->
        <div class="exam-card">
            <div class="text-center mb-4">
                <div style="font-size:2.5rem; margin-bottom:0.5rem;">💬</div>
                <h2 class="fw-bold mb-1" style="font-size:1.5rem; color:#1B5E20;">
                    Bạn đang gặp triệu chứng gì?
                </h2>
                <p class="text-muted mb-0" style="font-size:0.9rem;">
                    Mô tả triệu chứng bằng ngôn ngữ tự nhiên - hệ thống sẽ tự phân tích
                </p>
            </div>

            <form id="startForm" method="POST" action="<?= BASE_URL ?>exam/start">
                <!-- Complaint textarea -->
                <div class="mb-2">
                    <textarea
                        id="chiefComplaint"
                        name="chief_complaint"
                        class="chief-complaint-area"
                        placeholder="Ví dụ: Tôi bị đau đầu kèm chóng mặt từ 3 ngày nay, hay đau hơn vào buổi chiều, kèm theo mệt mỏi và ngủ không ngon..."
                        maxlength="1000"
                        required
                        autofocus><?= htmlspecialchars($_POST['chief_complaint'] ?? '') ?></textarea>
                    <div class="char-counter" id="charCounter">0 / 1000 ký tự</div>
                </div>

                <!-- Example chips -->
                <div class="mb-4">
                    <p class="text-muted small mb-2">
                        <i class="bi bi-lightbulb me-1"></i>Ví dụ nhanh (click để điền):
                    </p>
                    <?php
                    $examples = [
                        'đau đầu kèm chóng mặt, hay quên',
                        'mệt mỏi toàn thân, chán ăn, ngủ không ngon',
                        'đau lưng mỏi gối, tiểu đêm nhiều',
                        'tức ngực khó thở, hồi hộp đánh trống ngực',
                        'đau bụng tiêu chảy, buồn nôn sau ăn',
                        'mất ngủ, hay lo lắng, tim đập nhanh',
                        'ho khan không đờm, khô họng, ra mồ hôi đêm',
                        'đau khớp gối, tê chân tay, sợ lạnh',
                    ];
                    foreach ($examples as $ex):
                    ?>
                    <span class="example-chip" onclick="fillExample(this)"><?= htmlspecialchars($ex) ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-center">
                    <button type="submit" class="btn btn-analyze" id="btnAnalyze" disabled>
                        <i class="bi bi-search me-2"></i>Phân tích triệu chứng
                    </button>
                </div>
            </form>
        </div>

        <!-- Tips -->
        <div class="tip-card mt-4">
            <h6 class="fw-bold text-success mb-3">
                <i class="bi bi-info-circle me-2"></i>Để có kết quả tốt nhất:
            </h6>
            <div class="tip-item">
                <i class="bi bi-check-circle-fill tip-icon"></i>
                <span>Mô tả <strong>triệu chứng chính</strong> trước (đau, mệt mỏi, chóng mặt...)</span>
            </div>
            <div class="tip-item">
                <i class="bi bi-check-circle-fill tip-icon"></i>
                <span>Nêu <strong>thời gian</strong> triệu chứng xuất hiện (hôm nay, 1 tuần, mãn tính...)</span>
            </div>
            <div class="tip-item">
                <i class="bi bi-check-circle-fill tip-icon"></i>
                <span>Thêm <strong>yếu tố làm nặng/nhẹ</strong> nếu biết (sau ăn, khi lạnh, lúc stress...)</span>
            </div>
            <div class="tip-item">
                <i class="bi bi-check-circle-fill tip-icon"></i>
                <span>Đề cập <strong>thuốc đang dùng</strong> nếu có để kiểm tra tương tác</span>
            </div>
            <div class="tip-item">
                <i class="bi bi-x-circle-fill" style="color:#C62828;margin-top:2px;flex-shrink:0;"></i>
                <span class="text-danger"><strong>Không dùng công cụ này</strong> khi có triệu chứng khẩn cấp (đau ngực dữ dội, khó thở đột ngột, liệt nửa người) - Hãy gọi 115 ngay!</span>
            </div>
        </div>

    </div>
</div>

<script>
var textarea   = document.getElementById('chiefComplaint');
var counter    = document.getElementById('charCounter');
var btnAnalyze = document.getElementById('btnAnalyze');

function updateUI() {
    var len = textarea.value.trim().length;
    var raw = textarea.value.length;
    counter.textContent = raw + ' / 1000 ký tự';
    counter.className   = 'char-counter' + (raw > 800 ? ' warning' : '') + (raw > 950 ? ' danger' : '');
    btnAnalyze.disabled = len < 5;
}

textarea.addEventListener('input', updateUI);
updateUI();

function fillExample(el) {
    textarea.value = el.textContent;
    textarea.focus();
    updateUI();
}

document.getElementById('startForm').addEventListener('submit', function() {
    btnAnalyze.disabled = true;
    btnAnalyze.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang phân tích...';
});
</script>
