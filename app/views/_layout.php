<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Hệ thống hỗ trợ chẩn đoán Y Học Cổ Truyền Việt Nam - YHCT First">
    <title><?= htmlspecialchars($title ?? APP_NAME) ?></title>

    <!-- Bootstrap 5 CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
          crossorigin="anonymous">

    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts: Be Vietnam Pro -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- App CSS -->
    <link rel="stylesheet" href="/y/kham/public/css/app.css">

    <style>
        :root {
            --yhct-primary:   #2E7D32;
            --yhct-secondary: #81C784;
            --yhct-accent:    #FF8F00;
            --yhct-danger:    #C62828;
            --yhct-text:      #1A1A1A;
            --yhct-bg:        #F9F7F4;
            --yhct-card-bg:   #FFFFFF;
            --sidebar-width:  240px;
        }
        body {
            font-family: 'Be Vietnam Pro', sans-serif;
            background-color: var(--yhct-bg);
            color: var(--yhct-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        /* Navbar */
        .navbar-yhct {
            background: linear-gradient(135deg, var(--yhct-primary), #1B5E20);
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }
        .navbar-yhct .navbar-brand {
            font-weight: 700;
            font-size: 1.2rem;
            color: #fff !important;
            letter-spacing: -0.3px;
        }
        .navbar-yhct .nav-link {
            color: rgba(255,255,255,0.85) !important;
            font-weight: 500;
            transition: color 0.2s;
        }
        .navbar-yhct .nav-link:hover,
        .navbar-yhct .nav-link.active {
            color: #fff !important;
        }
        .navbar-yhct .nav-link.active {
            border-bottom: 2px solid var(--yhct-secondary);
        }
        /* Main content */
        main {
            flex: 1;
        }
        /* Flash messages */
        .flash-container {
            position: fixed;
            top: 70px;
            right: 16px;
            z-index: 9999;
            max-width: 400px;
        }
        .flash-alert {
            animation: slideInRight 0.3s ease;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        @keyframes slideInRight {
            from { transform: translateX(120%); opacity: 0; }
            to   { transform: translateX(0);   opacity: 1; }
        }
        /* Footer */
        .site-footer {
            background: #1A2820;
            color: rgba(255,255,255,0.75);
            padding: 1.5rem 0;
            margin-top: auto;
            font-size: 0.875rem;
        }
        .site-footer .disclaimer {
            background: rgba(255, 143, 0, 0.15);
            border-left: 3px solid var(--yhct-accent);
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            color: #FFE082;
            font-size: 0.8rem;
        }
        /* Role badge */
        .role-badge-admin   { background: #7B1FA2; }
        .role-badge-doctor  { background: #1565C0; }
        .role-badge-patient { background: var(--yhct-primary); }
        .role-badge {
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            color: #fff;
            font-weight: 600;
        }
        /* Sidebar for admin/doctor */
        @media (min-width: 992px) {
            .has-sidebar .sidebar-nav {
                position: sticky;
                top: 70px;
                min-height: calc(100vh - 70px);
            }
        }
        /* Card styles */
        .card-yhct {
            border: 1px solid #E8F5E9;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(46,125,50,0.06);
        }
        /* Progress bar */
        .progress-steps {
            display: flex;
            align-items: center;
            gap: 0;
        }
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }
        .progress-step .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            border: 2px solid #ccc;
            background: #fff;
            color: #999;
            transition: all 0.3s;
        }
        .progress-step.active .step-circle {
            background: var(--yhct-primary);
            border-color: var(--yhct-primary);
            color: #fff;
        }
        .progress-step.done .step-circle {
            background: var(--yhct-secondary);
            border-color: var(--yhct-secondary);
            color: #fff;
        }
        .progress-step .step-label {
            font-size: 0.7rem;
            color: #999;
            margin-top: 4px;
            text-align: center;
        }
        .progress-step.active .step-label { color: var(--yhct-primary); font-weight: 600; }
        .progress-step.done .step-label   { color: var(--yhct-secondary); }
        .step-line {
            flex: 1;
            height: 2px;
            background: #ddd;
            margin-bottom: 20px;
        }
        .step-line.done { background: var(--yhct-secondary); }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-yhct sticky-top">
    <div class="container-fluid px-3">
        <a class="navbar-brand" href="<?= BASE_URL ?>">
            <i class="bi bi-heart-pulse-fill me-2"></i>Khám Bệnh YHCT
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarMain" aria-controls="navbarMain"
                aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list text-white fs-5"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= ($active_nav ?? '') === 'home' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>">
                        <i class="bi bi-house me-1"></i>Trang chủ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($active_nav ?? '') === 'exam' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>exam/start">
                        <i class="bi bi-clipboard2-pulse me-1"></i>Khám bệnh
                    </a>
                </li>
                <?php if (Auth::isDoctor()): ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($active_nav ?? '') === 'doctor' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>doctor/dashboard">
                        <i class="bi bi-person-badge me-1"></i>Bảng bác sĩ
                    </a>
                </li>
                <?php endif; ?>
                <?php if (Auth::isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($active_nav ?? '') === 'admin' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>admin/dashboard">
                        <i class="bi bi-gear me-1"></i>Admin
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                <?php if (Auth::isLoggedIn()): ?>
                    <?php $authUser = Auth::getUser(); ?>
                    <li class="nav-item me-2">
                        <span class="text-white-50 small">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= htmlspecialchars($authUser['display_name'] ?? $authUser['username'] ?? 'Người dùng') ?>
                            <span class="role-badge ms-1
                                <?= Auth::isAdmin() ? 'role-badge-admin' : (Auth::isDoctor() ? 'role-badge-doctor' : 'role-badge-patient') ?>">
                                <?= Auth::isAdmin() ? 'Admin' : (Auth::isDoctor() ? 'BS' : 'BN') ?>
                            </span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>auth/logout">
                            <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>auth/login">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash Messages -->
<div class="flash-container">
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible flash-alert mb-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible flash-alert mb-2" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_info'])): ?>
        <div class="alert alert-info alert-dismissible flash-alert mb-2" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i>
            <?= htmlspecialchars($_SESSION['flash_info']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_info']); ?>
    <?php endif; ?>
</div>

<!-- Main Content -->
<main>
    <?php include $content_template; ?>
</main>

<!-- Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="disclaimer">
                    <i class="bi bi-shield-exclamation me-1"></i>
                    <strong>Lưu ý:</strong> Đây là công cụ hỗ trợ tham khảo, <strong>không thay thế</strong> việc thăm khám bác sĩ.
                    Trong trường hợp khẩn cấp, hãy gọi <strong>115</strong>.
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-1">
                    <i class="bi bi-heart-pulse text-danger me-1"></i>
                    <?= APP_NAME ?> v<?= APP_VERSION ?>
                </p>
                <p class="mb-0 text-white-50" style="font-size:0.75rem;">
                    Áp dụng nguyên lý Y Học Cổ Truyền Việt Nam &bull;
                    Biện chứng luận trị &bull; YHCT-First
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>

<!-- Semantic Search (browser-side embedding + Hamming search, used on symptom picker) -->
<script src="/y/kham/public/js/semantic-search.js"></script>

<!-- App JS -->
<script src="/y/kham/public/js/app.js"></script>

<!-- Auto-dismiss flash messages -->
<script>
setTimeout(function() {
    document.querySelectorAll('.flash-alert').forEach(function(el) {
        var bs = bootstrap.Alert.getOrCreateInstance(el);
        if (bs) bs.close();
    });
}, 5000);
</script>

</body>
</html>
