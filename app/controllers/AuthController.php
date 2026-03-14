<?php
/**
 * AuthController - Quản lý xác thực người dùng
 *
 * Supports:
 * - Login / Logout
 * - Registration (if enabled)
 * - Anonymous (guest) exam mode: creates a temporary user session
 */
class AuthController extends Controller
{
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    // =========================================================================
    // LOGIN
    // =========================================================================

    /**
     * GET /auth/login - Show login form
     */
    public function login(): void
    {
        // Already logged in → redirect to home
        if (Auth::isLoggedIn()) {
            $this->redirect('');
            return;
        }

        // Handle POST submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processLogin();
            return;
        }

        $this->render('auth/login', [
            'title'        => 'Đăng nhập - ' . APP_NAME,
            'csrf_token'   => $this->generateCsrfToken(),
            'redirect_url' => $_SESSION['redirect_after_login'] ?? '',
        ]);
    }

    /**
     * Process POST /auth/login
     */
    private function processLogin(): void
    {
        // CSRF check
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Phiên làm việc hết hạn. Vui lòng thử lại.';
            $this->redirect('auth/login');
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Basic validation
        if (empty($username) || empty($password)) {
            $_SESSION['flash_error'] = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
            $this->redirect('auth/login');
            return;
        }

        // Find user by username or email
        $user = $this->userModel->findByUsername($username);
        if (!$user) {
            $user = $this->userModel->findByEmail($username);
        }

        // Validate credentials
        if (!$user || !$this->userModel->verifyPassword($user, $password)) {
            // Rate limiting: simple delay to slow brute force
            sleep(1);
            $_SESSION['flash_error'] = 'Tên đăng nhập hoặc mật khẩu không đúng.';
            $this->redirect('auth/login');
            return;
        }

        // Check account status
        if ($user['status'] !== 'active') {
            $_SESSION['flash_error'] = 'Tài khoản đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.';
            $this->redirect('auth/login');
            return;
        }

        // Successful login
        Auth::login((int)$user['id'], $user['role'], [
            'username'    => $user['username'],
            'email'       => $user['email'],
            'display_name'=> $user['username'],
        ]);

        // Update last login time
        $this->userModel->updateLastLogin((int)$user['id']);

        $_SESSION['flash_success'] = 'Đăng nhập thành công. Chào mừng ' . htmlspecialchars($user['username']) . '!';

        // Redirect to intended page or home
        $redirectUrl = $_SESSION['redirect_after_login'] ?? '';
        unset($_SESSION['redirect_after_login']);

        if (!empty($redirectUrl) && str_starts_with($redirectUrl, BASE_URL)) {
            header('Location: ' . $redirectUrl);
            exit;
        }

        // Role-based redirect
        if (Auth::isAdmin()) {
            $this->redirect('admin/dashboard');
        } elseif (Auth::isDoctor()) {
            $this->redirect('doctor/dashboard');
        } else {
            $this->redirect('');
        }
    }

    // =========================================================================
    // LOGOUT
    // =========================================================================

    /**
     * GET /auth/logout - Logout and redirect to home
     */
    public function logout(): void
    {
        Auth::logout();
        session_start(); // Restart for flash message
        $_SESSION['flash_success'] = 'Bạn đã đăng xuất thành công.';
        header('Location: ' . BASE_URL);
        exit;
    }

    // =========================================================================
    // REGISTER
    // =========================================================================

    /**
     * GET|POST /auth/register - Registration (if allowed)
     *
     * By default, registration is restricted.
     * Only allow self-registration for 'patient' role.
     */
    public function register(): void
    {
        // Already logged in
        if (Auth::isLoggedIn()) {
            $this->redirect('');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processRegister();
            return;
        }

        $this->render('auth/login', [
            'title'        => 'Đăng ký tài khoản - ' . APP_NAME,
            'show_register'=> true,
            'csrf_token'   => $this->generateCsrfToken(),
        ]);
    }

    /**
     * Process POST /auth/register
     */
    private function processRegister(): void
    {
        // CSRF check
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Phiên làm việc hết hạn. Vui lòng thử lại.';
            $this->redirect('auth/register');
            return;
        }

        $username  = trim($_POST['username']  ?? '');
        $email     = trim($_POST['email']     ?? '');
        $password  = $_POST['password']       ?? '';
        $password2 = $_POST['password_confirm'] ?? '';

        // Validation
        $errors = [];

        if (strlen($username) < 3 || strlen($username) > 30) {
            $errors[] = 'Tên đăng nhập phải từ 3-30 ký tự.';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Tên đăng nhập chỉ được chứa chữ cái, số và gạch dưới.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
        }
        if ($password !== $password2) {
            $errors[] = 'Mật khẩu xác nhận không khớp.';
        }

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode('<br>', $errors);
            $this->redirect('auth/register');
            return;
        }

        try {
            $userId = $this->userModel->createUser($username, $email, $password, UserModel::ROLE_PATIENT);

            // Auto-login after registration
            Auth::login($userId, UserModel::ROLE_PATIENT, [
                'username'    => $username,
                'email'       => $email,
                'display_name'=> $username,
            ]);

            $_SESSION['flash_success'] = 'Đăng ký thành công! Chào mừng ' . htmlspecialchars($username) . '.';
            $this->redirect('');
        } catch (\RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $this->redirect('auth/register');
        }
    }

    // =========================================================================
    // GUEST / ANONYMOUS MODE
    // =========================================================================

    /**
     * Start an anonymous exam session (guest mode)
     *
     * Creates a virtual guest user ID stored in session only.
     * Guest sessions are not persisted to the users table.
     */
    public function guest(): void
    {
        if (Auth::isLoggedIn()) {
            $this->redirect('exam/start');
            return;
        }

        // Create a transient guest identity in session
        $guestId = -1 * abs(crc32(session_id())); // Negative ID to distinguish guests

        Auth::login($guestId, 'patient', [
            'username'     => 'Khách',
            'email'        => '',
            'display_name' => 'Khách vãng lai',
            'is_guest'     => true,
        ]);

        $this->redirect('exam/start');
    }

    // =========================================================================
    // CSRF HELPERS
    // =========================================================================

    /**
     * Generate a CSRF token and store it in session
     */
    private function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    /**
     * Validate a CSRF token from POST data
     */
    private function validateCsrfToken(string $token): bool
    {
        $expected = $_SESSION['csrf_token'] ?? '';
        if (empty($expected) || empty($token)) {
            return false;
        }
        return hash_equals($expected, $token);
    }
}
