<?php
$users     = $users      ?? [];
$csrfToken = $csrf_token ?? '';
?>
<style>
.role-badge-admin   { background: #7B1FA2; color: #fff; }
.role-badge-doctor  { background: #1565C0; color: #fff; }
.role-badge-patient { background: #2E7D32; color: #fff; }
</style>

<div class="py-4" style="background:#F9F7F4; min-height:calc(100vh - 120px);">
    <div class="container-fluid px-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0">
                    <i class="bi bi-people me-2 text-success"></i>Quản lý người dùng
                </h4>
                <p class="text-muted small mb-0"><?= count($users) ?> tài khoản</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="bi bi-person-plus me-1"></i>Tạo người dùng
                </button>
                <a href="<?= BASE_URL ?>admin/dashboard" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Dashboard
                </a>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($users)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                    Chưa có người dùng nào.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold small">ID</th>
                                <th class="fw-semibold small">Tên đăng nhập</th>
                                <th class="fw-semibold small">Email</th>
                                <th class="fw-semibold small">Vai trò</th>
                                <th class="fw-semibold small">Trạng thái</th>
                                <th class="fw-semibold small">Ngày tạo</th>
                                <th class="fw-semibold small">Đăng nhập cuối</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr class="<?= $user['status'] === 'inactive' ? 'table-secondary' : '' ?>">
                                <td class="text-muted small"><?= $user['id'] ?></td>
                                <td>
                                    <div class="fw-semibold small">
                                        <?= htmlspecialchars($user['username']) ?>
                                        <?php if ((int)$user['id'] === Auth::getUserId()): ?>
                                        <span class="badge bg-light text-success border border-success ms-1" style="font-size:0.65rem;">Bạn</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="font-size:0.82rem; color:#666;">
                                    <?= htmlspecialchars($user['email'] ?? '') ?>
                                </td>
                                <td>
                                    <span class="badge role-badge-<?= htmlspecialchars($user['role'] ?? '') ?> rounded-pill">
                                        <?= match($user['role'] ?? '') {
                                            'admin'   => 'Admin',
                                            'doctor'  => 'Bác sĩ',
                                            'patient' => 'Bệnh nhân',
                                            default   => htmlspecialchars($user['role'] ?? ''),
                                        } ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusMap = [
                                        'active'   => ['Hoạt động','success'],
                                        'inactive' => ['Vô hiệu','secondary'],
                                        'banned'   => ['Bị cấm','danger'],
                                    ];
                                    [$stLabel, $stColor] = $statusMap[$user['status'] ?? ''] ?? ['?','secondary'];
                                    ?>
                                    <span class="badge bg-<?= $stColor ?>"><?= $stLabel ?></span>
                                </td>
                                <td style="font-size:0.78rem; white-space:nowrap;">
                                    <?= !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '-' ?>
                                </td>
                                <td style="font-size:0.78rem; white-space:nowrap; color:#888;">
                                    <?= !empty($user['last_login_at']) ? date('d/m H:i', strtotime($user['last_login_at'])) : 'Chưa' ?>
                                </td>
                                <td>
                                    <?php if ((int)$user['id'] !== Auth::getUserId()): ?>
                                    <form method="POST"
                                          action="<?= BASE_URL ?>admin/users/toggle/<?= $user['id'] ?>"
                                          class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <button type="submit"
                                                class="btn btn-<?= $user['status'] === 'active' ? 'outline-secondary' : 'outline-success' ?> btn-sm py-0"
                                                onclick="return confirm('Bạn có chắc muốn thay đổi trạng thái tài khoản này?')">
                                            <?= $user['status'] === 'active' ? '<i class="bi bi-toggle-on"></i>' : '<i class="bi bi-toggle-off"></i>' ?>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus me-2"></i>Tạo người dùng mới
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>admin/users/create">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required
                               pattern="[a-zA-Z0-9_]{3,30}"
                               placeholder="Chỉ chữ cái, số và gạch dưới (3-30 ký tự)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="email@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required
                               minlength="6" placeholder="Ít nhất 6 ký tự">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Vai trò</label>
                        <select name="role" class="form-select">
                            <option value="patient">Bệnh nhân</option>
                            <option value="doctor">Bác sĩ</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-person-plus me-1"></i>Tạo tài khoản
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
