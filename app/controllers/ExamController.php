<?php
/**
 * ExamController - Main exam flow controller
 *
 * Flow:
 *   Phase 0: start()     → Chief complaint input
 *   Phase 1: symptoms()  → Symptom picker (3 groups)
 *   Phase 2: questions() → Quick questions (onset, trajectory, tongue, meds, emotion)
 *   Phase 3: result()    → Full diagnosis result
 *
 * API endpoints (JSON):
 *   api_rank()              → Re-rank symptoms
 *   api_next_question()     → Get best differentiating question
 *   api_check_contradiction() → Check for contradictory selections
 */
class ExamController extends Controller
{
    private ExamSessionModel $sessionModel;
    private YHCTEngine       $engine;

    // Maximum symptoms the user should select (soft warning)
    private const MAX_SYMPTOMS_SOFT = 15;
    // Minimum symptoms before showing Group 3
    private const MIN_FOR_GROUP3    = 3;

    public function __construct()
    {
        parent::__construct();
        $this->sessionModel = new ExamSessionModel();
    }

    /**
     * Lazy-init the engine (heavy - only when needed)
     */
    private function getEngine(): YHCTEngine
    {
        if (!isset($this->engine)) {
            $this->engine = new YHCTEngine();
        }
        return $this->engine;
    }

    // =========================================================================
    // STEP 1: START (Phase 0 → Chief Complaint)
    // =========================================================================

    /**
     * GET /exam/start  - Show chief complaint form
     * POST /exam/start - Process chief complaint, create session
     */
    public function start(): void
    {
        // Ensure user has some identity (logged in or guest)
        $this->ensureIdentity();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processStart();
            return;
        }

        $this->render('exam/start', [
            'title'      => 'Mô tả triệu chứng - ' . APP_NAME,
            'active_nav' => 'exam',
            'step'       => 1,
        ]);
    }

    /**
     * Process POST /exam/start
     */
    private function processStart(): void
    {
        $complaint = trim($_POST['chief_complaint'] ?? '');

        if (mb_strlen($complaint, 'UTF-8') < 5) {
            $_SESSION['flash_error'] = 'Vui lòng mô tả triệu chứng (ít nhất 5 ký tự).';
            $this->redirect('exam/start');
            return;
        }

        if (mb_strlen($complaint, 'UTF-8') > 1000) {
            $_SESSION['flash_error'] = 'Mô tả quá dài. Vui lòng tóm tắt trong 1000 ký tự.';
            $this->redirect('exam/start');
            return;
        }

        // Create exam session (null for guests — user_id is nullable)
        $isGuest   = Auth::getSessionValue('is_guest', false);
        $userId    = (Auth::isLoggedIn() && !$isGuest) ? Auth::getUserId() : null;
        $sessionId = $this->generateSessionId();

        $this->sessionModel->createSession($userId, $sessionId);

        // Parse chief complaint with engine
        $engine   = $this->getEngine();
        $parsed   = $engine->parseChiefComplaint($complaint);

        // Store session ID in PHP session for tracking
        $_SESSION['exam_session_id'] = $sessionId;

        // Update DB session with chief complaint + parsed data
        $this->sessionModel->updateSession($sessionId, [
            'chief_complaint' => $complaint,
            'phase'           => ExamSessionModel::PHASE_CHIEF,
            'context_flags'   => array_merge(
                $parsed['context_flags'],
                ['parsed_codes' => $parsed['matched_codes']]
            ),
            'selected_codes'  => [], // Start empty — matched_codes only used as context triggers for ranking
        ]);

        // Store context triggers in session for ranking
        $_SESSION['context_triggers'] = $parsed['matched_codes'];
        $_SESSION['parsed_phrases']   = $parsed['extracted_phrases'];
        $_SESSION['drug_mentions']    = $parsed['drug_mentions'];

        // Go to clarification step before symptom picker
        $this->redirect('exam/clarify');
    }

    // =========================================================================
    // STEP 1.5: CLARIFY (Làm rõ triệu chứng)
    // =========================================================================

    /**
     * GET /exam/clarify  — Show clarifying questions based on chief complaint
     * POST /exam/clarify — Store answers, redirect to symptom picker
     */
    public function clarify(): void
    {
        $this->ensureIdentity();
        $session = $this->loadActiveSession();
        if (!$session) { $this->redirect('exam/start'); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processClarify($session);
            return;
        }

        $complaint = $session['chief_complaint'] ?? '';
        $lower     = mb_strtolower($complaint, 'UTF-8');

        // Build keyword-driven question set
        $questions = $this->buildClarifyQuestions($lower);

        $this->render('exam/clarify', [
            'title'          => 'Làm rõ triệu chứng - ' . APP_NAME,
            'active_nav'     => 'exam',
            'chief_complaint'=> $complaint,
            'questions'      => $questions,
        ]);
    }

    private function processClarify(array $session): void
    {
        // Collect and sanitize answers
        $answers = [];
        foreach ($_POST as $k => $v) {
            if (str_starts_with($k, 'q_')) {
                $key           = preg_replace('/[^a-z0-9_]/', '', $k);
                $answers[$key] = $this->sanitizeAnswer(is_array($v) ? implode(', ', $v) : (string)$v);
            }
        }

        // Persist clarification answers in session
        $sessionId = $session['session_id'] ?? ($_SESSION['exam_session_id'] ?? null);
        if ($sessionId) {
            $existing = $session['context_flags'] ?? [];
            $existing['clarification'] = $answers;
            $this->sessionModel->updateSession($sessionId, ['context_flags' => $existing]);
        }
        $_SESSION['clarification_answers'] = $answers;

        $this->redirect('exam/symptoms');
    }

    /**
     * Generate contextual clarifying questions based on chief complaint keywords.
     * Returns array of question definitions for the view.
     */
    private function buildClarifyQuestions(string $lower): array
    {
        $questions = [];

        // Q1: Duration — always shown
        $questions[] = [
            'id'      => 'duration',
            'label'   => 'Triệu chứng xuất hiện từ bao lâu rồi?',
            'type'    => 'radio',
            'options' => [
                'lt1w'  => 'Dưới 1 tuần',
                '1_4w'  => '1 – 4 tuần',
                '1_3m'  => '1 – 3 tháng',
                'gt3m'  => 'Hơn 3 tháng (mạn tính)',
            ],
        ];

        // Q2: Severity — always shown
        $questions[] = [
            'id'      => 'severity',
            'label'   => 'Mức độ ảnh hưởng đến sinh hoạt hằng ngày?',
            'type'    => 'radio',
            'options' => [
                'mild'     => 'Nhẹ — khó chịu nhưng vẫn sinh hoạt bình thường',
                'moderate' => 'Vừa — hạn chế một số hoạt động',
                'severe'   => 'Nặng — ảnh hưởng nhiều đến công việc / giấc ngủ',
            ],
        ];

        // Q3: Pattern — always shown
        $questions[] = [
            'id'      => 'pattern',
            'label'   => 'Triệu chứng xuất hiện theo kiểu nào?',
            'type'    => 'radio',
            'options' => [
                'constant'  => 'Liên tục, không ngừng',
                'episodic'  => 'Từng đợt, giữa các đợt bình thường',
                'seasonal'  => 'Theo mùa / định kỳ hàng năm',
                'triggered' => 'Có yếu tố kích phát rõ ràng',
            ],
        ];

        // Q4: Location — if itch, pain, or other location-relevant complaints
        $needsLocation = preg_match('/ngứa|đau|nhức|tê|phù|sưng|nóng|đỏ|viêm/u', $lower);
        if ($needsLocation) {
            $questions[] = [
                'id'          => 'location',
                'label'       => 'Vị trí / bộ phận bị ảnh hưởng nhiều nhất?',
                'type'        => 'text',
                'placeholder' => 'VD: chân trái, kẽ ngón chân, toàn thân, ...',
            ];
        }

        // Q5: Itch-specific — skin changes
        if (mb_strpos($lower, 'ngứa', 0, 'UTF-8') !== false) {
            $questions[] = [
                'id'      => 'skin_changes',
                'label'   => 'Vùng ngứa có thay đổi trên da không?',
                'type'    => 'checkbox',
                'options' => [
                    'rash'      => 'Nổi mẩn đỏ / phát ban',
                    'scaling'   => 'Bong vảy / da khô',
                    'blisters'  => 'Mụn nước / phồng rộp',
                    'discharge' => 'Chảy dịch / rỉ nước',
                    'darkening' => 'Da sậm màu / dày da',
                    'none'      => 'Không thay đổi gì, da bình thường',
                ],
            ];
            $questions[] = [
                'id'      => 'itch_timing',
                'label'   => 'Ngứa nặng hơn khi nào?',
                'type'    => 'checkbox',
                'options' => [
                    'night'   => 'Ban đêm',
                    'heat'    => 'Khi nóng / ra mồ hôi',
                    'cold'    => 'Khi lạnh / thời tiết khô',
                    'wet'     => 'Khi ẩm / tiếp xúc nước',
                    'stress'  => 'Khi căng thẳng',
                    'anytime' => 'Bất kỳ lúc nào',
                ],
            ];
        }

        // Q_cough: cough-specific
        if (mb_strpos($lower, ' ho', 0, 'UTF-8') !== false || mb_strpos($lower, 'ho ', 0, 'UTF-8') !== false
                || mb_strpos($lower, 'bị ho', 0, 'UTF-8') !== false) {
            $questions[] = [
                'id'      => 'cough_type',
                'label'   => 'Tính chất ho?',
                'type'    => 'radio',
                'options' => [
                    'dry'         => 'Ho khan (không có đờm)',
                    'productive'  => 'Ho có đờm (trắng/vàng/xanh)',
                    'bloody'      => 'Ho ra máu / đờm lẫn máu',
                    'nocturnal'   => 'Ho nhiều về đêm / sáng sớm',
                ],
            ];
        }

        // Q_headache: headache-specific
        if (preg_match('/đau đầu|nhức đầu|đầu đau/u', $lower)) {
            $questions[] = [
                'id'      => 'headache_type',
                'label'   => 'Đặc điểm cơn đau đầu?',
                'type'    => 'radio',
                'options' => [
                    'throbbing' => 'Đau nhói theo mạch đập / một bên',
                    'pressure'  => 'Đau âm ỉ / cảm giác đè nặng',
                    'tension'   => 'Đau cứng đầu cổ / căng cơ',
                    'stabbing'  => 'Đau dữ dội đột ngột',
                ],
            ];
        }

        return $questions;
    }

    // =========================================================================
    // STEP 2: SYMPTOMS (Phase 1 → Symptom Picker)
    // =========================================================================

    /**
     * GET /exam/symptoms  - Show symptom picker
     * POST /exam/symptoms - Save selected codes, go to questions
     */
    public function symptoms(): void
    {
        $this->ensureIdentity();
        $session = $this->loadActiveSession();
        if (!$session) {
            $this->redirect('exam/start');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processSymptoms($session);
            return;
        }

        // GET: build symptom groups for picker
        $engine        = $this->getEngine();
        $selectedCodes = $session['selected_codes'] ?? [];
        $contextFlags  = $session['context_flags']  ?? [];
        $ctxTriggers   = $_SESSION['context_triggers'] ?? [];

        // Build symptom matrix
        $engine->buildCoOccurrenceMatrix();
        $ranked = $engine->rankSymptoms($selectedCodes, $contextFlags, $ctxTriggers);

        // Also run a quick pattern check for current hypothesis display
        $k03Scores   = [];
        $hypotheses  = [];
        if (!empty($selectedCodes)) {
            $k03Scores  = $this->runK03Quick($selectedCodes, $contextFlags);
            $patternMatches = $engine->matchPatterns($selectedCodes, $k03Scores);
            $hypoEngine     = new HypothesisEngine();
            $hypotheses     = $hypoEngine->getCompetingHypotheses(['chung_ranked' => $patternMatches]);
        }

        // Group symptoms into 3 groups
        $groups = $this->buildSymptomGroups($ranked, $selectedCodes, $hypotheses, $engine);

        // Load full symptom data for selected codes
        $selectedSymptoms = [];
        $allSymptoms      = $engine->getSymptoms();
        foreach ($selectedCodes as $code) {
            if (isset($allSymptoms[$code])) {
                $selectedSymptoms[$code] = $allSymptoms[$code];
            }
        }

        $this->render('exam/symptoms', [
            'title'            => 'Chọn triệu chứng - ' . APP_NAME,
            'active_nav'       => 'exam',
            'step'             => 2,
            'session'          => $session,
            'chief_complaint'  => $session['chief_complaint'] ?? '',
            'selected_codes'   => $selectedCodes,
            'selected_symptoms'=> $selectedSymptoms,
            'groups'           => $groups,
            'hypotheses'       => $hypotheses,
            'max_symptoms'     => self::MAX_SYMPTOMS_SOFT,
            'min_for_group3'   => self::MIN_FOR_GROUP3,
        ]);
    }

    /**
     * Process POST /exam/symptoms
     */
    private function processSymptoms(array $session): void
    {
        $rawCodes      = $_POST['selected_codes'] ?? [];
        $selectedCodes = [];

        // Sanitize: only allow valid codes (alphanumeric + underscore)
        foreach ((array)$rawCodes as $code) {
            $code = trim($code);
            if (preg_match('/^[A-Za-z0-9_]+$/', $code)) {
                $selectedCodes[] = $code;
            }
        }

        $selectedCodes = array_unique($selectedCodes);

        if (empty($selectedCodes)) {
            $_SESSION['flash_error'] = 'Vui lòng chọn ít nhất 1 triệu chứng.';
            $this->redirect('exam/symptoms');
            return;
        }

        // Save to DB
        $this->sessionModel->updateSession($session['session_id'], [
            'selected_codes' => $selectedCodes,
            'phase'          => ExamSessionModel::PHASE_SYMPTOMS,
        ]);

        $this->redirect('exam/questions');
    }

    // =========================================================================
    // STEP 3: QUESTIONS (Phase 2 → Quick Questions)
    // =========================================================================

    /**
     * GET /exam/questions  - Show 5 quick questions
     * POST /exam/questions - Save answers, run engine, redirect to result
     */
    public function questions(): void
    {
        $this->ensureIdentity();
        $session = $this->loadActiveSession();
        if (!$session) {
            $this->redirect('exam/start');
            return;
        }

        // Must have selected some symptoms
        if (empty($session['selected_codes'])) {
            $this->redirect('exam/symptoms');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processQuestions($session);
            return;
        }

        $this->render('exam/symptoms', [
            'title'      => 'Câu hỏi nhanh - ' . APP_NAME,
            'active_nav' => 'exam',
            'step'       => 3,
            'session'    => $session,
            'is_questions_page' => true,
        ]);
    }

    /**
     * Process POST /exam/questions - save answers and run full analysis
     */
    private function processQuestions(array $session): void
    {
        // Sanitize quick answers
        $quickAnswers = [
            'onset'       => $this->sanitizeAnswer($_POST['onset']       ?? ''),
            'trajectory'  => $this->sanitizeAnswer($_POST['trajectory']  ?? ''),
            'tongue'      => $this->sanitizeAnswer($_POST['tongue']      ?? ''),
            'medications' => $this->sanitizeAnswer($_POST['medications'] ?? ''),
            'emotion'     => $this->sanitizeAnswer($_POST['emotion']     ?? ''),
            'age'         => (int)($_POST['age'] ?? 0),
            'sex'         => in_array($_POST['sex'] ?? '', ['M', 'F', 'other'], true)
                              ? $_POST['sex'] : '',
            'pregnancy'   => in_array($_POST['pregnancy'] ?? '', ['yes', 'no', 'unknown'], true)
                              ? $_POST['pregnancy'] : 'unknown',
        ];

        // Update session with quick answers
        $this->sessionModel->updateSession($session['session_id'], [
            'quick_answers' => $quickAnswers,
        ]);

        // Reload session with new data
        $session['quick_answers'] = $quickAnswers;

        // Run preliminary analysis (no clinical data yet)
        $engine     = $this->getEngine();
        $resultData = $engine->analyze($session);

        // Apply safety filter
        $safetyFilter = new SafetyFilter();
        $resultData   = $safetyFilter->filter($resultData, $session);

        // Apply cluster detection
        $clusterEngine  = new ClusterEngine();
        $clusterMatches = $clusterEngine->match(
            $session['selected_codes'] ?? [],
            $session['context_flags']  ?? [],
            $quickAnswers
        );
        $resultData['clusters'] = $clusterMatches;

        // Save preliminary result; keep session active for paraclinical step
        $this->sessionModel->updateSession($session['session_id'], [
            'result_data' => json_encode($resultData, JSON_UNESCAPED_UNICODE),
        ]);

        $this->redirect('exam/paraclinical');
    }

    // =========================================================================
    // STEP 4: PARACLINICAL — Cận lâm sàng
    // =========================================================================

    /**
     * GET  /exam/paraclinical – Form nhập kết quả cận lâm sàng
     * POST /exam/paraclinical – Lưu kết quả, áp dụng điều chỉnh, redirect result
     */
    public function paraclinical(): void
    {
        $this->ensureIdentity();
        $session = $this->loadActiveSession();
        if (!$session) {
            $this->redirect('exam/start');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processClinical($session);
            return;
        }

        $resultData = $session['result_data'] ?? [];
        if (is_string($resultData)) {
            $resultData = json_decode($resultData, true) ?? [];
        }

        $hints = $this->buildParaclinicalHints($session);

        $this->render('exam/paraclinical', [
            'title'        => 'Cận lâm sàng - ' . APP_NAME,
            'active_nav'   => 'exam',
            'step'         => 5,
            'session'      => $session,
            'hints'        => $hints,
            'top_patterns' => array_slice($resultData['chung_ranked'] ?? [], 0, 5),
        ]);
    }

    /**
     * Process POST /exam/paraclinical — lưu kết quả, điều chỉnh score, complete session
     */
    private function processClinical(array $session): void
    {
        $submittedTests  = $_POST['tests'] ?? [];
        $clinicalResults = ['tests' => [], 'submitted_at' => date('Y-m-d H:i:s')];

        foreach ($submittedTests as $testCode => $entry) {
            $status = $entry['status'] ?? 'not_done';
            if (!in_array($status, ['normal', 'abnormal', 'not_done'], true)) {
                $status = 'not_done';
            }
            if ($status === 'not_done') continue;

            $clinicalResults['tests'][] = [
                'test_code'    => preg_replace('/[^a-z0-9_]/', '', $testCode),
                'test_name_vi' => mb_substr(strip_tags($entry['test_name_vi'] ?? $testCode), 0, 100, 'UTF-8'),
                'status'       => $status,
                'direction'    => $entry['direction'] ?? 'any',
                'findings'     => mb_substr(strip_tags($entry['findings'] ?? ''), 0, 500, 'UTF-8'),
                'pattern_code' => $entry['pattern_code'] ?? '',
            ];
        }

        // Load preliminary result_data
        $resultData = $session['result_data'] ?? [];
        if (is_string($resultData)) {
            $resultData = json_decode($resultData, true) ?? [];
        }

        // Apply clinical score adjustments to chung_ranked
        if (!empty($clinicalResults['tests'])) {
            $engine = $this->getEngine();
            $engine->applyClinicalResults($resultData, $clinicalResults);
        }

        $resultData['paraclinical'] = $clinicalResults;

        // Mark complete with final result
        $this->sessionModel->markComplete($session['session_id'], $resultData);

        $this->redirect('exam/result');
    }

    /**
     * Build grouped hint list from top candidate patterns' paraclinical_hints.
     *
     * @return array [ 'Xét nghiệm máu' => [ test_code => hint, ... ], ... ]
     */
    private function buildParaclinicalHints(array $session): array
    {
        $resultData = $session['result_data'] ?? [];
        if (is_string($resultData)) {
            $resultData = json_decode($resultData, true) ?? [];
        }

        $topPatterns = array_slice($resultData['chung_ranked'] ?? [], 0, 5);
        if (empty($topPatterns)) return [];

        $codes        = array_column($topPatterns, 'chung_code');
        $db           = Database::get();
        $placeholders = implode(',', array_fill(0, count($codes), '?'));

        $stmt = $db->prepare(
            "SELECT chung_code, name_vi, paraclinical_hints FROM kb_patterns
             WHERE chung_code IN ($placeholders) AND paraclinical_hints IS NOT NULL"
        );
        $stmt->execute($codes);
        $patterns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Merge hints; keep highest weight per test_code
        $merged = [];
        foreach ($patterns as $pat) {
            $hints = json_decode($pat['paraclinical_hints'] ?? '[]', true) ?: [];
            foreach ($hints as $h) {
                $tc = $h['test_code'];
                if (!isset($merged[$tc]) || $h['weight'] > $merged[$tc]['weight']) {
                    $h['suggested_by_code'] = $pat['chung_code'];
                    $h['suggested_by_name'] = $pat['name_vi'];
                    $merged[$tc] = $h;
                }
            }
        }

        // Group by category_vi
        $grouped = [];
        foreach ($merged as $tc => $h) {
            $cat = $h['category_vi'] ?? 'Khác';
            $grouped[$cat][$tc] = $h;
        }

        return $grouped;
    }

    // =========================================================================
    // STEP 5: RESULT
    // =========================================================================

    /**
     * GET /exam/result - Display diagnosis result
     */
    public function result(): void
    {
        $this->ensureIdentity();
        $session = $this->loadSessionForResult();

        if (!$session) {
            $_SESSION['flash_error'] = 'Không tìm thấy kết quả khám. Vui lòng bắt đầu khám lại.';
            $this->redirect('exam/start');
            return;
        }

        $resultData = $session['result_data'] ?? [];

        // Safety: if result_data is still a string, decode it
        if (is_string($resultData)) {
            $resultData = json_decode($resultData, true) ?? [];
        }

        $triageLevel    = $resultData['triage_level']    ?? null;
        $primaryPattern = $resultData['primary_pattern'] ?? null;
        $yhctSuppressed = $resultData['yhct_suppressed'] ?? false;

        $this->render('exam/result', [
            'title'          => 'Kết quả khám - ' . APP_NAME,
            'active_nav'     => 'exam',
            'step'           => 5,
            'session'        => $session,
            'result'         => $resultData,
            'triage_level'   => $triageLevel,
            'primary_pattern'=> $primaryPattern,
            'yhct_suppressed'=> $yhctSuppressed,
            'chief_complaint'=> $session['chief_complaint'] ?? '',
        ]);
    }

    // =========================================================================
    // API ENDPOINTS
    // =========================================================================

    /**
     * POST /exam/api_rank - Re-rank symptoms based on current selections
     * Returns JSON
     */
    public function api_rank(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $input         = json_decode(file_get_contents('php://input'), true) ?? [];
        $selectedCodes = array_filter((array)($input['selected_codes'] ?? []), fn($c) => preg_match('/^[A-Za-z0-9_]+$/', $c));
        $contextFlags  = (array)($input['context_flags']  ?? []);
        $ctxTriggers   = (array)($input['context_triggers'] ?? []);

        try {
            $engine = $this->getEngine();
            $engine->buildCoOccurrenceMatrix();
            $ranked = $engine->rankSymptoms(
                array_values($selectedCodes),
                $contextFlags,
                $ctxTriggers
            );

            // Return top 50 for performance
            echo json_encode([
                'success' => true,
                'ranked'  => array_slice($ranked, 0, 50),
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Lỗi xử lý. Vui lòng thử lại.']);
        }
        exit;
    }

    /**
     * GET /exam/api_next_question - Get best differentiating question
     * Returns JSON
     */
    public function api_next_question(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $session = $this->loadActiveSession();
        if (!$session) {
            echo json_encode(['success' => false, 'question' => null]);
            exit;
        }

        try {
            $engine   = $this->getEngine();
            $question = $engine->getNextBestQuestion(
                $session['selected_codes'] ?? [],
                $session
            );
            echo json_encode(['success' => true, 'question' => $question], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'question' => null]);
        }
        exit;
    }

    /**
     * POST /exam/api_check_contradiction - Detect contradictory selections
     * Returns JSON
     */
    public function api_check_contradiction(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $input         = json_decode(file_get_contents('php://input'), true) ?? [];
        $selectedCodes = array_filter((array)($input['selected_codes'] ?? []), fn($c) => preg_match('/^[A-Za-z0-9_]+$/', $c));

        // Known contradictions (YHCT opposites)
        $contradictions = [
            // Heat vs Cold pairs
            ['S_NHIET_001', 'S_HAN_001', 'Nhiệt chứng và Hàn chứng mâu thuẫn nhau'],
            // Deficiency vs Excess
            ['S_HU_001', 'S_THUC_001', 'Hư chứng và Thực chứng cần xem lại'],
            // Interior vs Exterior
            ['S_LY_001', 'S_BIEU_001', 'Lý chứng và Biểu chứng không thường cùng xuất hiện'],
        ];

        $found = [];
        foreach ($contradictions as [$codeA, $codeB, $msg]) {
            if (in_array($codeA, $selectedCodes, true) && in_array($codeB, $selectedCodes, true)) {
                $found[] = [
                    'code_a'  => $codeA,
                    'code_b'  => $codeB,
                    'message' => $msg,
                ];
            }
        }

        // Also check via K04 patterns: if two selected codes appear in exclude lists of each other's patterns
        // (complex - simplified version here)
        try {
            $engine     = $this->getEngine();
            $patterns   = $engine->getPatterns();
            $codesArray = array_values($selectedCodes);

            foreach ($patterns as $pattern) {
                $excludes  = $pattern['exclude_codes_arr'] ?? [];
                $required  = $pattern['required_codes_arr'] ?? [];
                $patHasReq = !empty(array_intersect($codesArray, $required));
                if (!$patHasReq) {
                    continue;
                }
                $conflictCodes = array_intersect($codesArray, $excludes);
                if (!empty($conflictCodes)) {
                    foreach ($conflictCodes as $cc) {
                        $found[] = [
                            'code_a'  => reset($required),
                            'code_b'  => $cc,
                            'message' => 'Hai triệu chứng này thường không xuất hiện cùng nhau trong YHCT.',
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Silent
        }

        echo json_encode([
            'success'         => true,
            'contradictions'  => array_slice($found, 0, 5),
            'has_contradiction'=> !empty($found),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Ensure user has some identity; if not, allow guest access
     */
    private function ensureIdentity(): void
    {
        if (!Auth::isLoggedIn()) {
            // Auto-create guest session
            $guestId = -1 * abs(crc32(session_id()));
            Auth::login($guestId, 'patient', [
                'username'     => 'Khách',
                'display_name' => 'Khách vãng lai',
                'is_guest'     => true,
            ]);
        }
    }

    /**
     * Load active exam session from PHP session → DB
     */
    private function loadActiveSession(): ?array
    {
        $sessionId = $_SESSION['exam_session_id'] ?? null;
        if (!$sessionId) {
            return null;
        }
        $session = $this->sessionModel->getSession($sessionId);
        if (!$session || $session['status'] === ExamSessionModel::STATUS_ABANDONED) {
            return null;
        }
        return $session;
    }

    /**
     * Load session for result display (allow completed sessions)
     */
    private function loadSessionForResult(): ?array
    {
        $sessionId = $_SESSION['exam_session_id'] ?? null;
        if (!$sessionId) {
            return null;
        }
        return $this->sessionModel->getSession($sessionId);
    }

    /**
     * Build 3 symptom groups for the picker
     *
     * @param array $ranked       All ranked symptoms from engine
     * @param array $selected     Currently selected codes
     * @param array $hypotheses   Current top hypotheses
     * @param YHCTEngine $engine
     * @return array ['group1'=>[], 'group2'=>[], 'group3'=>[]]
     */
    private function buildSymptomGroups(
        array $ranked,
        array $selected,
        array $hypotheses,
        YHCTEngine $engine
    ): array {
        // Group 1: High-score symptoms (anchor + directly related)
        // Top 20 by score, exclude already selected
        $group1 = array_slice(
            array_filter($ranked, fn($r) => $r['score'] >= 0.25),
            0, 20
        );

        // Group 2: Organize by organ system (tang)
        $byTang = [];
        foreach ($ranked as $r) {
            $tang = $r['tang'] ?? 'khac';
            if (!empty($tang) && !in_array($r['code'], $selected, true)) {
                $byTang[$tang][] = $r;
            }
        }
        // Sort each group by score DESC
        foreach ($byTang as &$tangGroup) {
            usort($tangGroup, fn($a, $b) => $b['score'] <=> $a['score']);
            $tangGroup = array_slice($tangGroup, 0, 10);
        }
        unset($tangGroup);

        // Group 3: Differentiating symptoms (only after MIN_FOR_GROUP3 selected)
        $group3 = [];
        if (count($selected) >= self::MIN_FOR_GROUP3 && count($hypotheses) >= 2) {
            $hypoEngine = new HypothesisEngine();
            $k04        = $engine->getPatterns();
            $diffSymptoms = $hypoEngine->getDifferentiatingSymptoms($hypotheses, $k04);
            $allSymptoms  = $engine->getSymptoms();

            foreach (array_slice($diffSymptoms, 0, 10) as $ds) {
                $code = $ds['symptom_code'];
                if (in_array($code, $selected, true)) {
                    continue;
                }
                if (!isset($allSymptoms[$code])) {
                    continue;
                }
                $group3[] = [
                    'code'          => $code,
                    'symptom'       => $allSymptoms[$code],
                    'score'         => $ds['discrim_score'],
                    'present_in'    => $ds['present_in'],
                    'absent_in'     => $ds['absent_in'],
                    'tang'          => $allSymptoms[$code]['tang'] ?? null,
                    'is_differentiating' => true,
                ];
            }
        }

        // Tang display names
        $tangNames = [
            'can'          => 'Can (Gan)',
            'tam'          => 'Tâm (Tim)',
            'ty'           => 'Tỳ (Lách)',
            'phe'          => 'Phế (Phổi)',
            'than'         => 'Thận',
            'vi'           => 'Vị (Dạ Dày)',
            'dan'          => 'Đởm (Mật)',
            'tieu_truong'  => 'Tiểu Trường',
            'dai_truong'   => 'Đại Trường',
            'bang_quang'   => 'Bàng Quang',
            'khac'         => 'Khác',
        ];

        return [
            'group1'    => array_values($group1),
            'group2'    => $byTang,
            'group3'    => $group3,
            'tang_names'=> $tangNames,
        ];
    }

    /**
     * Quick K03 scoring for hypothesis display during symptom picking
     */
    private function runK03Quick(array $selectedCodes, array $contextFlags): array
    {
        try {
            $db   = Database::get();
            $rows = $db->query("SELECT * FROM kb_pathogenesis_rules")->fetchAll(PDO::FETCH_ASSOC);

            $scores = [];
            foreach ($rows as $row) {
                $reqJson   = $row['required_symptoms'] ?? '[]';
                $ruleCodes = json_decode($reqJson, true) ?? [];
                if (!is_array($ruleCodes) || empty($ruleCodes)) {
                    continue;
                }
                $matched = count(array_intersect($selectedCodes, $ruleCodes));
                if ($matched > 0) {
                    $scores[$row['rule_code']] = round($matched / count($ruleCodes), 4);
                }
            }
            arsort($scores);
            return $scores;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Generate a unique exam session ID (UUID v4)
     */
    private function generateSessionId(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Sanitize a free-text answer (strip tags, limit length)
     */
    private function sanitizeAnswer(string $value): string
    {
        $value = strip_tags(trim($value));
        return mb_substr($value, 0, 500, 'UTF-8');
    }
}
