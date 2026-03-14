<?php
$symptomCount  = $stats['symptom_count']  ?? 0;
$patternCount  = $stats['pattern_count']  ?? 0;
$redFlagCount  = $stats['red_flag_count'] ?? 0;
$clusterCount  = $stats['cluster_count']  ?? 0;
?>
<style>
.hero-section {
    background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 50%, #388E3C 100%);
    color: #fff;
    padding: 5rem 0 4rem;
    position: relative;
    overflow: hidden;
}
.hero-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 600px;
    height: 600px;
    border-radius: 50%;
    background: rgba(255,255,255,0.04);
}
.hero-section::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -5%;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    background: rgba(255,255,255,0.03);
}
.hero-badge {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.5px;
}
.hero-title {
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    font-weight: 700;
    line-height: 1.2;
}
.hero-subtitle {
    font-size: 1.1rem;
    opacity: 0.85;
    max-width: 520px;
}
.btn-start {
    background: #FF8F00;
    border: none;
    color: #fff;
    padding: 14px 36px;
    font-size: 1.1rem;
    font-weight: 700;
    border-radius: 50px;
    transition: all 0.25s;
    box-shadow: 0 4px 20px rgba(255,143,0,0.4);
}
.btn-start:hover {
    background: #F57F00;
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(255,143,0,0.5);
    color: #fff;
}
.btn-start-outline {
    border: 2px solid rgba(255,255,255,0.5);
    color: #fff;
    padding: 12px 28px;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 50px;
    background: transparent;
    transition: all 0.25s;
}
.btn-start-outline:hover {
    background: rgba(255,255,255,0.1);
    border-color: #fff;
    color: #fff;
}
.hero-stat {
    background: rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 12px 20px;
    text-align: center;
    backdrop-filter: blur(4px);
}
.hero-stat .stat-number {
    font-size: 1.8rem;
    font-weight: 700;
    display: block;
}
.hero-stat .stat-label {
    font-size: 0.75rem;
    opacity: 0.8;
}
/* How it works */
.section-title {
    font-size: 1.6rem;
    font-weight: 700;
    color: #1B5E20;
}
.step-card {
    background: #fff;
    border-radius: 16px;
    padding: 2rem 1.5rem;
    text-align: center;
    border: 1px solid #E8F5E9;
    transition: all 0.3s;
    height: 100%;
    position: relative;
}
.step-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(46,125,50,0.12);
    border-color: #A5D6A7;
}
.step-number {
    width: 52px;
    height: 52px;
    background: linear-gradient(135deg, #2E7D32, #4CAF50);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 auto 1rem;
}
.step-icon {
    font-size: 2.5rem;
    color: #2E7D32;
    margin-bottom: 0.75rem;
}
.step-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1B5E20;
    margin-bottom: 0.5rem;
}
.step-desc {
    font-size: 0.875rem;
    color: #666;
    line-height: 1.5;
}
.step-arrow {
    position: absolute;
    right: -18px;
    top: 50%;
    transform: translateY(-50%);
    color: #A5D6A7;
    font-size: 1.5rem;
    z-index: 1;
}
/* Feature cards */
.feature-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #E8F5E9;
    height: 100%;
}
.feature-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    margin-bottom: 1rem;
}
/* YHCT Notice */
.yhct-notice {
    background: linear-gradient(135deg, #E8F5E9, #F1F8E9);
    border: 1px solid #A5D6A7;
    border-radius: 16px;
    padding: 2rem;
}
.tang-badge {
    display: inline-flex;
    align-items: center;
    background: rgba(46,125,50,0.1);
    border: 1px solid #A5D6A7;
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 0.8rem;
    color: #2E7D32;
    font-weight: 600;
    margin: 3px;
}
/* Safety box */
.safety-box {
    background: #FFF8E1;
    border-left: 4px solid #FF8F00;
    border-radius: 8px;
    padding: 1rem 1.25rem;
}
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container position-relative" style="z-index:1;">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="hero-badge mb-3 d-inline-block">
                    <i class="bi bi-star-fill me-1"></i> YHCT-First Diagnostic System
                </span>
                <h1 class="hero-title mb-3">
                    Khám Bệnh Thông Minh<br>theo Y Học Cổ Truyền
                </h1>
                <p class="hero-subtitle mb-4">
                    Hệ thống biện chứng luận trị theo nguyên lý YHCT Việt Nam.
                    Phân tích triệu chứng, xác định chứng bệnh và đề xuất pháp trị cá thể hóa.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="<?= BASE_URL ?>exam/start" class="btn btn-start">
                        <i class="bi bi-clipboard2-pulse me-2"></i>Bắt đầu khám bệnh
                    </a>
                    <?php if (!Auth::isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>auth/login" class="btn btn-start-outline">
                        <i class="bi bi-person me-1"></i>Đăng nhập
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Stats row -->
                <div class="row g-3">
                    <div class="col-auto">
                        <div class="hero-stat">
                            <span class="stat-number"><?= $symptomCount > 0 ? number_format($symptomCount) : '200+' ?></span>
                            <span class="stat-label">Triệu chứng YHCT</span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="hero-stat">
                            <span class="stat-number"><?= $patternCount > 0 ? number_format($patternCount) : '80+' ?></span>
                            <span class="stat-label">Chứng bệnh</span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="hero-stat">
                            <span class="stat-number"><?= $redFlagCount > 0 ? number_format($redFlagCount) : '50+' ?></span>
                            <span class="stat-label">Quy tắc an toàn</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center">
                <!-- Visual decoration -->
                <div style="font-size:9rem; opacity:0.15; position:absolute; right:0; top:50%; transform:translateY(-50%);">
                    <i class="bi bi-yin-yang"></i>
                </div>
                <div class="position-relative" style="z-index:1;">
                    <div class="bg-white bg-opacity-10 rounded-3 p-4 d-inline-block">
                        <div style="font-size:5rem;">🌿</div>
                        <div class="text-white-75 small mt-2">Biện Chứng Luận Trị</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="py-5" style="background:#F9F7F4;">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Cách hoạt động</h2>
            <p class="text-muted">4 bước đơn giản để nhận phân tích YHCT</p>
        </div>

        <div class="row g-4 position-relative">
            <div class="col-6 col-md-3">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-icon"><i class="bi bi-chat-text"></i></div>
                    <div class="step-title">Mô tả triệu chứng</div>
                    <div class="step-desc">Nhập mô tả triệu chứng bằng ngôn ngữ tự nhiên tiếng Việt</div>
                    <span class="step-arrow d-none d-md-block"><i class="bi bi-chevron-right"></i></span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-icon"><i class="bi bi-ui-checks"></i></div>
                    <div class="step-title">Chọn triệu chứng</div>
                    <div class="step-desc">Chọn từ danh sách triệu chứng YHCT đã được gợi ý thông minh</div>
                    <span class="step-arrow d-none d-md-block"><i class="bi bi-chevron-right"></i></span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-icon"><i class="bi bi-question-circle"></i></div>
                    <div class="step-title">Trả lời câu hỏi</div>
                    <div class="step-desc">5 câu hỏi nhanh để làm rõ bệnh cơ: lưỡi, khởi phát, cảm xúc...</div>
                    <span class="step-arrow d-none d-md-block"><i class="bi bi-chevron-right"></i></span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="step-card">
                    <div class="step-number">4</div>
                    <div class="step-icon"><i class="bi bi-clipboard2-data"></i></div>
                    <div class="step-title">Xem kết quả</div>
                    <div class="step-desc">Nhận phân tích chứng bệnh, bát cương, pháp trị và khuyến cáo</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- YHCT Approach -->
<section class="py-5" style="background:#fff;">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="yhct-notice">
                    <div class="d-flex align-items-center mb-3">
                        <div style="font-size:2.5rem; margin-right:12px;">🌿</div>
                        <div>
                            <h3 class="mb-0 fw-bold text-success">YHCT-First Approach</h3>
                            <small class="text-muted">Biện chứng luận trị theo Y Học Cổ Truyền</small>
                        </div>
                    </div>
                    <p class="mb-3">
                        Hệ thống ưu tiên <strong>biện chứng luận trị theo YHCT</strong>,
                        phân tích tạng phủ, khí huyết, âm dương, và xác định chứng bệnh
                        trước khi bổ sung thêm góc nhìn y học hiện đại.
                    </p>
                    <div class="mb-3">
                        <strong class="text-success d-block mb-2">Ngũ Tạng:</strong>
                        <?php foreach ([
                            ['🌿', 'Can (Gan)'],
                            ['❤️', 'Tâm (Tim)'],
                            ['🌾', 'Tỳ (Lách)'],
                            ['🍃', 'Phế (Phổi)'],
                            ['💧', 'Thận'],
                        ] as [$icon, $name]): ?>
                        <span class="tang-badge"><?= $icon ?> <?= $name ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <strong class="text-success d-block mb-2">Bát Cương:</strong>
                        <?php foreach (['Âm/Dương', 'Lý/Biểu', 'Hàn/Nhiệt', 'Hư/Thực'] as $bc): ?>
                        <span class="tang-badge">⚖️ <?= $bc ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <h2 class="section-title mb-4">Tính năng chính</h2>
                <div class="row g-3">
                    <?php
                    $features = [
                        ['icon' => 'bi-graph-up-arrow', 'color' => '#E8F5E9', 'icolor' => '#2E7D32',
                         'title' => 'Xếp hạng thông minh',
                         'desc'  => 'Triệu chứng được gợi ý và xếp hạng theo mức độ liên quan dựa trên AI và YHCT'],
                        ['icon' => 'bi-shield-check', 'color' => '#FFEBEE', 'icolor' => '#C62828',
                         'title' => 'Phát hiện nguy hiểm (Red Flag)',
                         'desc'  => 'Tự động cảnh báo các dấu hiệu cấp cứu (L1/L2) và chuyển hướng kịp thời'],
                        ['icon' => 'bi-diagram-3', 'color' => '#E3F2FD', 'icolor' => '#1565C0',
                         'title' => 'Phân biệt chứng',
                         'desc'  => 'Đặt câu hỏi phân biệt khi nhiều chứng bệnh có thể xảy ra'],
                        ['icon' => 'bi-capsule', 'color' => '#FFF3E0', 'icolor' => '#E65100',
                         'title' => 'Kiểm tra tương tác thuốc',
                         'desc'  => 'Cảnh báo tương tác thuốc nam - thuốc tây, thai kỳ và trẻ em'],
                        ['icon' => 'bi-person-check', 'color' => '#F3E5F5', 'icolor' => '#7B1FA2',
                         'title' => 'Cá thể hóa',
                         'desc'  => 'Pháp trị và khuyến cáo phù hợp với thể trạng và bối cảnh cụ thể'],
                        ['icon' => 'bi-lock', 'color' => '#E0F2F1', 'icolor' => '#00695C',
                         'title' => 'Kết quả tất định',
                         'desc'  => 'Cùng dữ liệu đầu vào luôn cho cùng kết quả - minh bạch và nhất quán'],
                    ];
                    foreach ($features as $f):
                    ?>
                    <div class="col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon" style="background:<?= $f['color'] ?>">
                                <i class="bi <?= $f['icon'] ?>" style="color:<?= $f['icolor'] ?>"></i>
                            </div>
                            <h6 class="fw-bold mb-1"><?= $f['title'] ?></h6>
                            <p class="text-muted mb-0" style="font-size:0.8rem;"><?= $f['desc'] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Safety Disclaimer -->
<section class="py-4" style="background:#F9F7F4;">
    <div class="container">
        <div class="safety-box">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="fw-bold mb-1">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                        Lưu ý quan trọng
                    </h5>
                    <p class="mb-0 text-muted" style="font-size:0.9rem;">
                        Hệ thống này chỉ là công cụ <strong>hỗ trợ tham khảo</strong>,
                        không thay thế thăm khám trực tiếp với bác sĩ hay thầy thuốc YHCT.
                        Với triệu chứng nghiêm trọng hoặc kéo dài, vui lòng đến cơ sở y tế.
                        <strong>Trong trường hợp khẩn cấp, hãy gọi 115 ngay.</strong>
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="<?= BASE_URL ?>exam/start" class="btn btn-success btn-lg px-4">
                        <i class="bi bi-clipboard2-pulse me-2"></i>Bắt đầu ngay
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
