<div style="min-height:calc(100vh - 120px); display:flex; align-items:center; background:linear-gradient(135deg,#F1F8E9,#F9F7F4);">
    <div class="container text-center py-5">
        <div style="font-size:6rem; line-height:1; margin-bottom:1rem; opacity:0.3;">
            <i class="bi bi-question-circle"></i>
        </div>
        <h1 class="fw-bold mb-2" style="font-size:4rem; color:#2E7D32;">404</h1>
        <h2 class="fw-semibold mb-3" style="color:#333;">Không tìm thấy trang</h2>
        <p class="text-muted mb-4" style="max-width:400px; margin:0 auto;">
            Trang bạn đang tìm kiếm không tồn tại hoặc đã được di chuyển đến địa chỉ khác.
        </p>
        <div class="d-flex gap-3 justify-content-center">
            <a href="<?= BASE_URL ?>" class="btn btn-success rounded-pill px-4">
                <i class="bi bi-house me-2"></i>Về trang chủ
            </a>
            <a href="<?= BASE_URL ?>exam/start" class="btn btn-outline-success rounded-pill px-4">
                <i class="bi bi-clipboard2-pulse me-2"></i>Bắt đầu khám
            </a>
        </div>
        <div class="mt-5 text-muted small">
            <i class="bi bi-arrow-left me-1"></i>
            <a href="javascript:history.back()" class="text-muted">Quay lại trang trước</a>
        </div>
    </div>
</div>
