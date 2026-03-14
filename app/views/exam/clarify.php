<?php
/**
 * Exam Step 1.5 — Làm rõ triệu chứng (Clarification)
 */
$complaint = $chief_complaint ?? '';
$questions = $questions ?? [];
$stepLabels = ['Mô tả', 'Làm rõ', 'Triệu chứng', 'Câu hỏi', 'Cận lâm sàng', 'Kết quả'];
?>
<style>
.clarify-wrap {
    max-width: 640px;
    margin: 0 auto;
    padding: 1rem 0 3rem;
}
.clarify-header {
    background: linear-gradient(135deg, #E8F5E9, #F1F8E9);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
    border-left: 4px solid #2E7D32;
}
.clarify-header .complaint-text {
    font-style: italic;
    color: #2E7D32;
    font-weight: 600;
    font-size: 1rem;
}
.question-card {
    background: #fff;
    border-radius: 12px;
    border: 1.5px solid #E8F5E9;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1rem;
    transition: border-color 0.2s;
}
.question-card:focus-within {
    border-color: #2E7D32;
    box-shadow: 0 0 0 3px rgba(46,125,50,0.08);
}
.question-label {
    font-weight: 600;
    color: #1B5E20;
    font-size: 0.95rem;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.option-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}
@media (max-width: 500px) { .option-grid { grid-template-columns: 1fr; } }
.option-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.55rem 0.85rem;
    border-radius: 8px;
    border: 1.5px solid #E0E0E0;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.15s;
    background: #fafafa;
    user-select: none;
}
.option-label:hover {
    border-color: #66BB6A;
    background: #F1F8E9;
}
.option-label input[type="radio"]:checked + span,
.option-label input[type="checkbox"]:checked + span {
    color: #1B5E20;
    font-weight: 600;
}
.option-label:has(input:checked) {
    border-color: #2E7D32;
    background: #E8F5E9;
}
.option-label input { accent-color: #2E7D32; flex-shrink: 0; }
.skip-link {
    color: #9E9E9E;
    font-size: 0.8rem;
    text-decoration: none;
    cursor: pointer;
}
.skip-link:hover { color: #616161; text-decoration: underline; }
.qnum-badge {
    background: #2E7D32;
    color: #fff;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    font-size: 0.7rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
</style>

<div class="py-4" style="background:#F8FDF8; min-height:calc(100vh - 110px);">
<div class="container-fluid px-3">

    <!-- Progress steps -->
    <div class="mb-4" style="max-width:640px; margin:0 auto;">
        <div class="progress-steps">
            <?php foreach ($stepLabels as $i => $label):
                $sn = $i + 1;
                $isDone = $sn < 2;
                $isActive = $sn === 2;
            ?>
                <?php if ($i > 0): ?><div class="step-line <?= $isDone ? 'done' : '' ?>"></div><?php endif; ?>
                <div class="progress-step <?= $isActive ? 'active' : ($isDone ? 'done' : '') ?>">
                    <div class="step-circle">
                        <?= $isDone ? '<i class="bi bi-check-lg"></i>' : $sn ?>
                    </div>
                    <div class="step-label"><?= htmlspecialchars($label) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="clarify-wrap">

        <!-- Header -->
        <div class="clarify-header mb-4">
            <div class="text-muted small mb-1">
                <i class="bi bi-chat-text me-1"></i>Triệu chứng bạn mô tả:
            </div>
            <div class="complaint-text">"<?= htmlspecialchars(mb_substr($complaint, 0, 200, 'UTF-8')) ?>"</div>
            <div class="text-muted small mt-2">
                <i class="bi bi-info-circle me-1"></i>Trả lời vài câu hỏi ngắn để hệ thống gợi ý triệu chứng chính xác hơn.
            </div>
        </div>

        <?php if (empty($questions)): ?>
        <div class="text-center text-muted py-4">
            <p>Không cần câu hỏi thêm.</p>
            <a href="<?= BASE_URL ?>exam/symptoms" class="btn btn-success">
                <i class="bi bi-arrow-right me-1"></i>Tiếp tục chọn triệu chứng
            </a>
        </div>
        <?php else: ?>

        <form method="POST" action="<?= BASE_URL ?>exam/clarify" id="clarifyForm">

            <?php foreach ($questions as $i => $q):
                $qid = 'q_' . htmlspecialchars($q['id']);
                $num = $i + 1;
            ?>
            <div class="question-card" id="qcard-<?= $q['id'] ?>">
                <div class="question-label">
                    <span class="qnum-badge"><?= $num ?></span>
                    <?= htmlspecialchars($q['label']) ?>
                </div>

                <?php if ($q['type'] === 'radio'): ?>
                    <div class="option-grid">
                    <?php foreach ($q['options'] as $val => $text): ?>
                        <label class="option-label">
                            <input type="radio" name="<?= $qid ?>" value="<?= htmlspecialchars($val) ?>">
                            <span><?= htmlspecialchars($text) ?></span>
                        </label>
                    <?php endforeach; ?>
                    </div>

                <?php elseif ($q['type'] === 'checkbox'): ?>
                    <div class="option-grid">
                    <?php foreach ($q['options'] as $val => $text): ?>
                        <label class="option-label">
                            <input type="checkbox" name="<?= $qid ?>[]" value="<?= htmlspecialchars($val) ?>">
                            <span><?= htmlspecialchars($text) ?></span>
                        </label>
                    <?php endforeach; ?>
                    </div>

                <?php elseif ($q['type'] === 'text'): ?>
                    <input type="text"
                           name="<?= $qid ?>"
                           class="form-control"
                           placeholder="<?= htmlspecialchars($q['placeholder'] ?? '') ?>"
                           style="border-color:#C8E6C9;"
                           autocomplete="off">
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <div class="d-flex gap-3 align-items-center mt-3">
                <button type="submit" class="btn btn-success btn-lg flex-grow-1" style="font-weight:600;">
                    <i class="bi bi-arrow-right me-2"></i>Tiếp tục chọn triệu chứng
                </button>
            </div>
            <div class="text-center mt-2">
                <a href="<?= BASE_URL ?>exam/symptoms" class="skip-link">
                    <i class="bi bi-skip-forward me-1"></i>Bỏ qua, chuyển thẳng đến danh sách triệu chứng
                </a>
            </div>

        </form>
        <?php endif; ?>

    </div><!-- /clarify-wrap -->
</div>
</div>
