<div style="min-height:calc(100vh - 120px); display:flex; align-items:center; background:linear-gradient(135deg,#FFF8E1,#F9F7F4);">
    <div class="container text-center py-5">
        <div style="font-size:6rem; line-height:1; margin-bottom:1rem; opacity:0.3;">
            <i class="bi bi-shield-x"></i>
        </div>
        <h1 class="fw-bold mb-2" style="font-size:4rem; color:#E65100;">403</h1>
        <h2 class="fw-semibold mb-3" style="color:#333;">Không có quyền truy cập</h2>
        <p class="text-muted mb-4" style="max-width:440px; margin:0 auto;">
            Bạn không có quyền truy cập vào trang này.
            Vui lòng đăng nhập bằng tài khoản có quyền phù hợp.
        </p>
        <div class="d-flex gap-3 justify-content-center">
            <a href="<?= BASE_URL ?>" class="btn btn-warning rounded-pill px-4">
                <i class="bi bi-house me-2"></i>Về trang chủ
            </a>
            <?php if (!Auth::isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>auth/login" class="btn btn-outline-warning rounded-pill px-4">
                <i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập
            </a>
            <?php else: ?>
            <a href="<?= BASE_URL ?>auth/logout" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="bi bi-box-arrow-right me-2"></i>Đổi tài khoản
            </a>
            <?php endif; ?>
        </div>
        <div class="mt-4">
            <div class="alert alert-warning d-inline-block py-2 px-4">
                <i class="bi bi-info-circle me-2"></i>
                <?php if (Auth::isLoggedIn()): ?>
                Bạn đang đăng nhập với quyền:
                <strong><?= match(Auth::getRole()) {
                    'patient' => 'Bệnh nhân',
                    'doctor'  => 'Bác sĩ',
                    'admin'   => 'Admin',
                    default   => Auth::getRole(),
                } ?></strong>
                <?php else: ?>
                Bạn chưa đăng nhập.
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-3 text-muted small">
            <i class="bi bi-arrow-left me-1"></i>
            <a href="javascript:history.back()" class="text-muted">Quay lại trang trước</a>
        </div>
    </div>
</div>
