<style>
.login-wrapper {
    min-height: calc(100vh - 120px);
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #F1F8E9 0%, #E8F5E9 100%);
}
.login-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(46,125,50,0.1);
    overflow: hidden;
    max-width: 440px;
    width: 100%;
}
.login-header {
    background: linear-gradient(135deg, #2E7D32, #388E3C);
    padding: 2rem 2rem 1.5rem;
    text-align: center;
    color: #fff;
}
.login-header .brand-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}
.login-body {
    padding: 2rem;
}
.form-control-yhct {
    border: 2px solid #E8F5E9;
    border-radius: 10px;
    padding: 0.65rem 1rem;
    transition: border-color 0.2s;
    font-size: 0.95rem;
}
.form-control-yhct:focus {
    border-color: #2E7D32;
    box-shadow: 0 0 0 3px rgba(46,125,50,0.1);
}
.input-group-yhct .input-group-text {
    background: #F1F8E9;
    border: 2px solid #E8F5E9;
    border-right: none;
    border-radius: 10px 0 0 10px;
    color: #2E7D32;
}
.input-group-yhct .form-control {
    border-left: none !important;
    border-radius: 0 10px 10px 0 !important;
}
.input-group-yhct .form-control:focus {
    border-color: #2E7D32;
    box-shadow: none;
}
.input-group-yhct:focus-within .input-group-text {
    border-color: #2E7D32;
}
.btn-login {
    background: linear-gradient(135deg, #2E7D32, #388E3C);
    border: none;
    border-radius: 10px;
    color: #fff;
    font-weight: 700;
    padding: 0.75rem;
    font-size: 1rem;
    width: 100%;
    transition: all 0.25s;
}
.btn-login:hover {
    background: linear-gradient(135deg, #1B5E20, #2E7D32);
    transform: translateY(-1px);
    color: #fff;
}
.btn-guest {
    background: transparent;
    border: 2px solid #A5D6A7;
    border-radius: 10px;
    color: #2E7D32;
    font-weight: 600;
    padding: 0.65rem;
    font-size: 0.9rem;
    width: 100%;
    transition: all 0.2s;
}
.btn-guest:hover {
    background: #E8F5E9;
    border-color: #2E7D32;
    color: #1B5E20;
}
.divider-text {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #aaa;
    font-size: 0.8rem;
}
.divider-text::before,
.divider-text::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e0e0e0;
}
</style>

<div class="login-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                <div class="login-card mx-auto">

                    <!-- Header -->
                    <div class="login-header">
                        <div class="brand-icon">🌿</div>
                        <h4 class="fw-bold mb-1">Khám Bệnh YHCT</h4>
                        <p class="mb-0 opacity-75 small">Đăng nhập để lưu kết quả khám</p>
                    </div>

                    <!-- Body -->
                    <div class="login-body">

                        <!-- Flash error from layout already shown; also handle inline -->
                        <?php if (!empty($_SESSION['flash_error'])): ?>
                        <div class="alert alert-danger rounded-3 py-2 mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?= htmlspecialchars($_SESSION['flash_error']) ?>
                        </div>
                        <?php unset($_SESSION['flash_error']); ?>
                        <?php endif; ?>

                        <form method="POST" action="<?= BASE_URL ?>auth/login" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

                            <!-- Username -->
                            <div class="mb-3">
                                <label for="username" class="form-label fw-semibold small text-muted">
                                    Tên đăng nhập / Email
                                </label>
                                <div class="input-group input-group-yhct">
                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text"
                                           class="form-control form-control-yhct"
                                           id="username"
                                           name="username"
                                           placeholder="admin hoặc email@..."
                                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                           autocomplete="username"
                                           required>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold small text-muted">
                                    Mật khẩu
                                </label>
                                <div class="input-group input-group-yhct">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password"
                                           class="form-control form-control-yhct"
                                           id="password"
                                           name="password"
                                           placeholder="••••••••"
                                           autocomplete="current-password"
                                           required>
                                    <button class="btn btn-outline-secondary border-0 bg-transparent"
                                            type="button"
                                            id="togglePassword"
                                            style="position:absolute;right:10px;top:50%;transform:translateY(-50%);z-index:10;border:none;">
                                        <i class="bi bi-eye text-muted"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-login mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập
                            </button>
                        </form>

                        <div class="divider-text mb-3">hoặc</div>

                        <!-- Guest mode -->
                        <a href="<?= BASE_URL ?>auth/guest" class="btn btn-guest mb-3">
                            <i class="bi bi-incognito me-2"></i>Khám bệnh không đăng nhập
                        </a>

                        <p class="text-center text-muted mb-0" style="font-size:0.8rem;">
                            <i class="bi bi-info-circle me-1"></i>
                            Chế độ khách không lưu lịch sử khám.
                        </p>

                    </div>
                </div>

                <!-- Back link -->
                <div class="text-center mt-3">
                    <a href="<?= BASE_URL ?>" class="text-muted text-decoration-none small">
                        <i class="bi bi-arrow-left me-1"></i>Quay về trang chủ
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePassword')?.addEventListener('click', function() {
    var pwd  = document.getElementById('password');
    var icon = this.querySelector('i');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.className = 'bi bi-eye-slash text-muted';
    } else {
        pwd.type = 'password';
        icon.className = 'bi bi-eye text-muted';
    }
});
</script>
