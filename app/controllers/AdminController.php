<?php
/**
 * AdminController - Admin panel
 *
 * Provides:
 * - Dashboard with system stats
 * - User management (create, toggle active)
 * - Exam session listing and filtering
 * - KB review (symptoms, patterns, red flags, clusters)
 */
class AdminController extends Controller
{
    private UserModel        $userModel;
    private ExamSessionModel $sessionModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel    = new UserModel();
        $this->sessionModel = new ExamSessionModel();
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    /**
     * GET /admin/dashboard - Admin dashboard with system stats
     */
    public function dashboard(): void
    {
        $this->requireAdmin();

        $db    = Database::get();
        $stats = [];

        try {
            // User stats
            $userCounts           = $this->userModel->getCountByRole();
            $stats['total_users'] = array_sum($userCounts);
            $stats['user_counts'] = $userCounts;

            // Session stats
            $sessionStats            = $this->sessionModel->getStatsByStatus();
            $stats['total_sessions'] = array_sum($sessionStats);
            $stats['session_stats']  = $sessionStats;

            // KB counts
            $stats['kb'] = [
                'symptoms'    => (int)$db->query("SELECT COUNT(*) FROM kb_symptoms WHERE status='active'")->fetchColumn(),
                'patterns'    => (int)$db->query("SELECT COUNT(*) FROM kb_patterns WHERE status='active'")->fetchColumn(),
                'red_flags'   => (int)$db->query("SELECT COUNT(*) FROM kb_red_flags")->fetchColumn(),
                'clusters'    => (int)$db->query("SELECT COUNT(*) FROM kb_clusters WHERE status='active'")->fetchColumn(),
                'pathogenesis'=> (int)$db->query("SELECT COUNT(*) FROM kb_pathogenesis_rules")->fetchColumn(),
                'herb_drug'   => (int)$db->query("SELECT COUNT(*) FROM kb_herb_drug")->fetchColumn(),
            ];

            // Recent activity
            $stats['recent_sessions'] = $this->sessionModel->getRecentSessions(10);

        } catch (\Exception $e) {
            $stats = ['error' => 'Không thể tải thống kê: ' . $e->getMessage()];
        }

        $this->render('admin/dashboard', [
            'title'      => 'Admin Dashboard - ' . APP_NAME,
            'active_nav' => 'admin',
            'stats'      => $stats,
        ]);
    }

    // =========================================================================
    // USER MANAGEMENT
    // =========================================================================

    /**
     * GET /admin/users - List all users
     */
    public function users(): void
    {
        $this->requireAdmin();

        $db  = Database::get();
        try {
            $users = $db->query(
                "SELECT * FROM users ORDER BY created_at DESC"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $users = [];
        }

        $this->render('admin/users', [
            'title'      => 'Quản lý người dùng - ' . APP_NAME,
            'active_nav' => 'admin',
            'users'      => $users,
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * POST /admin/users/create - Create a new user
     */
    public function users_create(): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/users');
            return;
        }

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'CSRF token không hợp lệ.';
            $this->redirect('admin/users');
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $role     = in_array($_POST['role'] ?? '', ['admin', 'doctor', 'patient'], true)
                    ? $_POST['role'] : 'patient';

        if (empty($username) || empty($email) || empty($password)) {
            $_SESSION['flash_error'] = 'Vui lòng điền đầy đủ thông tin.';
            $this->redirect('admin/users');
            return;
        }

        try {
            $this->userModel->createUser($username, $email, $password, $role);
            $_SESSION['flash_success'] = "Tạo tài khoản '{$username}' thành công.";
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        $this->redirect('admin/users');
    }

    /**
     * POST /admin/users/toggle/{userId} - Toggle user active/inactive
     */
    public function users_toggle(int $userId = 0): void
    {
        $this->requireAdmin();

        if ($userId <= 0) {
            $this->redirect('admin/users');
            return;
        }

        // Cannot deactivate self
        if ($userId === Auth::getUserId()) {
            $_SESSION['flash_error'] = 'Bạn không thể vô hiệu hóa tài khoản của chính mình.';
            $this->redirect('admin/users');
            return;
        }

        try {
            $db   = Database::get();
            $user = $db->prepare("SELECT status FROM users WHERE id = ?")->execute([$userId])
                    ? $db->query("SELECT status FROM users WHERE id = $userId")->fetch(PDO::FETCH_ASSOC)
                    : null;

            if ($user) {
                $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';
                $this->userModel->setStatus($userId, $newStatus);
                $_SESSION['flash_success'] = 'Đã cập nhật trạng thái tài khoản.';
            }
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Lỗi khi cập nhật trạng thái.';
        }

        $this->redirect('admin/users');
    }

    // =========================================================================
    // EXAM SESSIONS
    // =========================================================================

    /**
     * GET /admin/exam_sessions - List all exam sessions with filters
     */
    public function exam_sessions(): void
    {
        $this->requireAdmin();

        $filterStatus = $_GET['status'] ?? 'all';
        $filterDate   = $_GET['date']   ?? '';
        $page         = max(1, (int)($_GET['page'] ?? 1));
        $perPage      = 20;
        $offset       = ($page - 1) * $perPage;

        try {
            $db     = Database::get();
            $sql    = "SELECT es.*, u.username FROM exam_sessions es
                       LEFT JOIN users u ON es.user_id = u.id WHERE 1=1";
            $params = [];

            if ($filterStatus !== 'all') {
                $sql      .= " AND es.status = ?";
                $params[]  = $filterStatus;
            }
            if (!empty($filterDate)) {
                $sql      .= " AND DATE(es.created_at) = ?";
                $params[]  = $filterDate;
            }

            // Total count
            $countSql  = str_replace('es.*, u.username', 'COUNT(*)', $sql);
            $countStmt = $db->prepare($countSql);
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetchColumn();

            // Page data
            $sql      .= " ORDER BY es.created_at DESC LIMIT ? OFFSET ?";
            $params[]  = $perPage;
            $params[]  = $offset;

            $stmt     = $db->prepare($sql);
            $stmt->execute($params);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Decode JSON fields
            foreach ($sessions as &$s) {
                if (!empty($s['result_data']) && is_string($s['result_data'])) {
                    $rd = json_decode($s['result_data'], true);
                    $s['primary_pattern_name'] = $rd['primary_pattern']['name_vi'] ?? '-';
                    $s['triage_level']         = $rd['triage_level'] ?? null;
                } else {
                    $s['primary_pattern_name'] = '-';
                    $s['triage_level']         = null;
                }
            }
            unset($s);

        } catch (\Exception $e) {
            $sessions   = [];
            $totalCount = 0;
        }

        $totalPages = (int)ceil($totalCount / $perPage);

        $this->render('admin/exam_sessions', [
            'title'         => 'Phiên khám - ' . APP_NAME,
            'active_nav'    => 'admin',
            'sessions'      => $sessions,
            'total_count'   => $totalCount,
            'current_page'  => $page,
            'total_pages'   => $totalPages,
            'filter_status' => $filterStatus,
            'filter_date'   => $filterDate,
        ]);
    }

    // =========================================================================
    // KB REVIEW
    // =========================================================================

    /**
     * GET /admin/kb_review - KB review dashboard
     */
    public function kb_review(): void
    {
        $this->requireAdmin();

        $activeTab = $_GET['tab'] ?? 'symptoms';
        $db        = Database::get();

        $data = [];

        try {
            switch ($activeTab) {
                case 'symptoms':
                    $data['items'] = $db->query(
                        "SELECT * FROM kb_symptoms ORDER BY status DESC, symptom_code ASC LIMIT 100"
                    )->fetchAll(PDO::FETCH_ASSOC);
                    $data['total'] = $db->query("SELECT COUNT(*) FROM kb_symptoms")->fetchColumn();
                    break;

                case 'patterns':
                    $data['items'] = $db->query(
                        "SELECT * FROM kb_patterns ORDER BY status DESC, chung_code ASC LIMIT 100"
                    )->fetchAll(PDO::FETCH_ASSOC);
                    $data['total'] = $db->query("SELECT COUNT(*) FROM kb_patterns")->fetchColumn();
                    break;

                case 'red_flags':
                    $data['items'] = $db->query(
                        "SELECT * FROM kb_red_flags ORDER BY level DESC, rule_code ASC LIMIT 100"
                    )->fetchAll(PDO::FETCH_ASSOC);
                    $data['total'] = $db->query("SELECT COUNT(*) FROM kb_red_flags")->fetchColumn();
                    break;

                case 'clusters':
                    $data['items'] = $db->query(
                        "SELECT * FROM kb_clusters ORDER BY status DESC, cluster_code ASC LIMIT 100"
                    )->fetchAll(PDO::FETCH_ASSOC);
                    $data['total'] = $db->query("SELECT COUNT(*) FROM kb_clusters")->fetchColumn();
                    break;

                default:
                    $data['items'] = [];
                    $data['total'] = 0;
            }
        } catch (\Exception $e) {
            $data = ['items' => [], 'total' => 0, 'error' => $e->getMessage()];
        }

        $this->render('admin/kb_review', [
            'title'      => 'Xem xét KB - ' . APP_NAME,
            'active_nav' => 'admin',
            'active_tab' => $activeTab,
            'data'       => $data,
        ]);
    }

    // =========================================================================
    // CLINICAL TESTS
    // =========================================================================

    /**
     * GET /admin/clinical_tests - Run clinical test suite and show results
     */
    public function clinical_tests(): void
    {
        $this->requireAdmin();

        $scriptPath = APP_ROOT . '/../scripts/run_clinical_tests.php';
        $results    = [];
        $summary    = ['total' => 0, 'passed' => 0, 'failed' => 0, 'pct' => 0];

        if (file_exists($scriptPath)) {
            // Capture output as JSON
            $output = shell_exec('php ' . escapeshellarg($scriptPath) . ' --json 2>&1');
            if ($output) {
                $decoded = json_decode($output, true);
                if ($decoded) {
                    $results = $decoded['results'] ?? [];
                    $summary = $decoded['summary'] ?? $summary;
                }
            }
        }

        $this->render('admin/clinical_tests', [
            'title'      => 'Clinical Tests - ' . APP_NAME,
            'active_nav' => 'admin',
            'results'    => $results,
            'summary'    => $summary,
        ]);
    }

    // =========================================================================
    // EMBEDDINGS ADMIN
    // =========================================================================

    /**
     * GET /admin/embeddings - Embedding management UI
     */
    public function embeddings(): void
    {
        $this->requireAdmin();

        $db    = Database::get();
        $stats = [];

        try {
            $rows = $db->query("
                SELECT doc_type,
                       COUNT(*) AS total,
                       SUM(CASE WHEN embedding IS NOT NULL THEN 1 ELSE 0 END) AS embedded
                FROM embedding_documents
                GROUP BY doc_type
                ORDER BY doc_type
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $stats[] = [
                    'doc_type' => $row['doc_type'],
                    'total'    => (int)$row['total'],
                    'embedded' => (int)$row['embedded'],
                ];
            }
        } catch (\Exception $e) {
            $stats = [];
        }

        $this->render('admin/embeddings', [
            'title'      => 'Quản lý Embeddings - ' . APP_NAME,
            'active_nav' => 'admin',
            'stats'      => $stats,
        ]);
    }

    /**
     * Admin API dispatcher — /admin/api/{action}
     *
     * Actions:
     *   GET  docs-pending?doc_type=...&offset=...  — docs without embedding
     *   POST save-embeddings                        — store batch of float vectors
     *   POST build-index                            — trigger build_index.php
     *   POST openai-embed                           — proxy single text to OpenAI
     */
    public function api(string $action = ''): void
    {
        $this->requireAdmin();
        header('Content-Type: application/json; charset=UTF-8');

        $db = Database::get();

        try {
            switch ($action) {

                // ── GET pending docs ──────────────────────────────────────────
                case 'docs-pending': {
                    $docType = preg_replace('/[^a-z0-9_]/', '', $_GET['doc_type'] ?? '');
                    $offset  = max(0, (int)($_GET['offset'] ?? 0));
                    $limit   = 500;

                    $totalStmt = $db->prepare(
                        "SELECT COUNT(*) FROM embedding_documents WHERE doc_type=? AND embedding IS NULL"
                    );
                    $totalStmt->execute([$docType]);
                    $total = (int)$totalStmt->fetchColumn();

                    $stmt = $db->prepare(
                        "SELECT id, doc_type, source_id, text
                         FROM embedding_documents
                         WHERE doc_type=? AND embedding IS NULL
                         ORDER BY id
                         LIMIT ? OFFSET ?"
                    );
                    $stmt->execute([$docType, $limit, $offset]);
                    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    echo json_encode(['docs' => $docs, 'total' => $total], JSON_UNESCAPED_UNICODE);
                    break;
                }

                // ── POST save embeddings ──────────────────────────────────────
                case 'save-embeddings': {
                    $body       = json_decode(file_get_contents('php://input'), true) ?? [];
                    $embeddings = $body['embeddings'] ?? [];
                    $saved      = 0;

                    $stmt = $db->prepare(
                        "UPDATE embedding_documents
                         SET embedding = ?, embedding_updated_at = datetime('now')
                         WHERE id = ?"
                    );

                    foreach ($embeddings as $item) {
                        $id     = (int)($item['id'] ?? 0);
                        $vector = $item['vector'] ?? [];

                        if ($id <= 0 || empty($vector)) continue;

                        // Convert 384-float array → 48-byte binary (sign-bit hash)
                        $bits = array_fill(0, 48, 0);
                        foreach ($vector as $i => $v) {
                            if ($i >= 384) break;
                            if ($v > 0) $bits[(int)floor($i / 8)] |= (1 << (7 - ($i % 8)));
                        }
                        $blob = '';
                        foreach ($bits as $b) $blob .= chr($b);

                        $stmt->execute([$blob, $id]);
                        $saved++;
                    }

                    echo json_encode(['saved' => $saved]);
                    break;
                }

                // ── POST build index ──────────────────────────────────────────
                case 'build-index': {
                    $scriptPath = APP_ROOT . '/../scripts/build_index.php';
                    if (!file_exists($scriptPath)) {
                        http_response_code(500);
                        echo json_encode(['error' => 'build_index.php not found']);
                        break;
                    }
                    $output  = shell_exec('php ' . escapeshellarg($scriptPath) . ' --json 2>&1');
                    $decoded = json_decode($output, true);

                    if ($decoded) {
                        echo json_encode(array_merge(['message' => 'Chỉ mục đã được xây dựng.'], $decoded));
                    } else {
                        echo json_encode(['message' => 'Hoàn thành.', 'output' => trim((string)$output)]);
                    }
                    break;
                }

                // ── POST OpenAI embed proxy ───────────────────────────────────
                case 'openai-embed': {
                    $body = json_decode(file_get_contents('php://input'), true) ?? [];
                    $text = mb_substr(trim($body['text'] ?? ''), 0, 8192, 'UTF-8');

                    try {
                        $key = $db->query(
                            "SELECT value FROM system_settings WHERE key='openai_api_key'"
                        )->fetchColumn();
                    } catch (\Exception $e) {
                        $key = null;
                    }

                    if (empty($key)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'OpenAI API key not configured.']);
                        break;
                    }

                    $ctx = stream_context_create(['http' => [
                        'method'  => 'POST',
                        'header'  => "Content-Type: application/json\r\nAuthorization: Bearer {$key}\r\n",
                        'content' => json_encode(['model' => 'text-embedding-3-small', 'input' => $text]),
                        'timeout' => 15,
                    ]]);
                    $resp = @file_get_contents('https://api.openai.com/v1/embeddings', false, $ctx);

                    if (!$resp) {
                        http_response_code(502);
                        echo json_encode(['error' => 'OpenAI API request failed.']);
                        break;
                    }
                    $data   = json_decode($resp, true);
                    $vector = $data['data'][0]['embedding'] ?? [];
                    echo json_encode(['vector' => $vector]);
                    break;
                }

                // ── POST reset embeddings ─────────────────────────────────────
                case 'reset-embeddings': {
                    $body    = json_decode(file_get_contents('php://input'), true) ?? [];
                    $docType = preg_replace('/[^a-z0-9_]/', '', $body['doc_type'] ?? '');

                    if ($docType) {
                        $stmt = $db->prepare("UPDATE embedding_documents SET embedding = NULL, embedding_updated_at = NULL WHERE doc_type = ?");
                        $stmt->execute([$docType]);
                        $affected = $stmt->rowCount();
                    } else {
                        $affected = $db->exec("UPDATE embedding_documents SET embedding = NULL, embedding_updated_at = NULL");
                    }

                    // Also remove the .bin index file(s)
                    $outputDir = APP_ROOT . '/../public/embeddings';
                    $removed   = [];
                    if ($docType) {
                        foreach ([$docType . '.bin', $docType . '_meta.json'] as $f) {
                            $path = $outputDir . '/' . $f;
                            if (file_exists($path)) { unlink($path); $removed[] = $f; }
                        }
                    } else {
                        foreach (glob($outputDir . '/*.bin') + glob($outputDir . '/*_meta.json') as $f) {
                            unlink($f); $removed[] = basename($f);
                        }
                    }

                    echo json_encode(['reset' => $affected, 'removed_files' => $removed]);
                    break;
                }

                // ── GET docs-detail — list docs with embedding status ─────────
                case 'docs-detail': {
                    $docType = preg_replace('/[^a-z0-9_]/', '', $_GET['doc_type'] ?? '');
                    $page    = max(0, (int)($_GET['page'] ?? 0));
                    $limit   = 50;
                    $offset  = $page * $limit;

                    $where = $docType ? "WHERE doc_type = ?" : "";
                    $args  = $docType ? [$docType, $limit, $offset] : [$limit, $offset];

                    $cntStmt = $db->prepare("SELECT COUNT(*) FROM embedding_documents $where");
                    $cntStmt->execute($docType ? [$docType] : []);
                    $total = (int)$cntStmt->fetchColumn();

                    $stmt = $db->prepare(
                        "SELECT id, doc_type, source_id, source_table,
                                CASE WHEN embedding IS NOT NULL THEN 1 ELSE 0 END AS has_embedding,
                                embedding_updated_at,
                                content_hash,
                                SUBSTR(text, 1, 100) AS preview
                         FROM embedding_documents
                         $where
                         ORDER BY doc_type, source_id
                         LIMIT ? OFFSET ?"
                    );
                    $stmt->execute($args);
                    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    echo json_encode(['docs' => $docs, 'total' => $total, 'page' => $page, 'limit' => $limit], JSON_UNESCAPED_UNICODE);
                    break;
                }

                // ── POST reseed — run seed_embedding_documents.php ────────────
                case 'reseed': {
                    $scriptPath = APP_ROOT . '/../scripts/seed_embedding_documents.php';
                    if (!file_exists($scriptPath)) {
                        http_response_code(500);
                        echo json_encode(['error' => 'seed_embedding_documents.php not found']);
                        break;
                    }
                    $output = shell_exec('php ' . escapeshellarg($scriptPath) . ' 2>&1');
                    echo json_encode(['message' => 'Đã seed lại tài liệu.', 'output' => trim((string)$output)]);
                    break;
                }

                default:
                    http_response_code(404);
                    echo json_encode(['error' => "Unknown action: {$action}"]);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    private function validateCsrfToken(string $token): bool
    {
        $expected = $_SESSION['csrf_token'] ?? '';
        return !empty($expected) && hash_equals($expected, $token);
    }
}
