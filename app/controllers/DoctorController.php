<?php
/**
 * DoctorController - Doctor's review panel
 *
 * Doctors can:
 * - View their assigned/recent exam sessions
 * - Review session details with full YHCT analysis
 * - Add clinical notes and confirm/correct patterns
 */
class DoctorController extends Controller
{
    private ExamSessionModel $sessionModel;

    public function __construct()
    {
        parent::__construct();
        $this->sessionModel = new ExamSessionModel();
    }

    /**
     * GET /doctor/dashboard - Doctor's dashboard
     */
    public function dashboard(): void
    {
        $this->requireDoctor();

        $userId       = Auth::getUserId();
        $recentSessions = $this->sessionModel->getRecentSessions(20);

        // Stats
        $db    = Database::get();
        $today = date('Y-m-d');
        try {
            $todayCount   = (int)$db->prepare(
                "SELECT COUNT(*) FROM exam_sessions WHERE DATE(created_at) = ?"
            )->execute([$today]) ? $db->query(
                "SELECT COUNT(*) FROM exam_sessions WHERE DATE(created_at) = '$today'"
            )->fetchColumn() : 0;

            $pendingCount = (int)$db->query(
                "SELECT COUNT(*) FROM exam_sessions WHERE status = 'completed' AND result_data IS NOT NULL"
            )->fetchColumn();
        } catch (\Exception $e) {
            $todayCount   = 0;
            $pendingCount = 0;
        }

        $this->render('doctor/dashboard', [
            'title'           => 'Bảng điều khiển Bác sĩ - ' . APP_NAME,
            'active_nav'      => 'doctor',
            'recent_sessions' => $recentSessions,
            'today_count'     => $todayCount,
            'pending_count'   => $pendingCount,
        ]);
    }

    /**
     * GET /doctor/session/{sessionId} - View a specific exam session
     *
     * @param string $sessionId UUID of the exam session
     */
    public function session(string $sessionId = ''): void
    {
        $this->requireDoctor();

        if (empty($sessionId)) {
            $this->redirect('doctor/dashboard');
            return;
        }

        // Sanitize sessionId
        $sessionId = preg_replace('/[^a-f0-9\-]/', '', $sessionId);
        $session   = $this->sessionModel->getSession($sessionId);

        if (!$session) {
            $_SESSION['flash_error'] = 'Không tìm thấy phiên khám.';
            $this->redirect('doctor/dashboard');
            return;
        }

        $resultData = $session['result_data'] ?? [];
        if (is_string($resultData)) {
            $resultData = json_decode($resultData, true) ?? [];
        }

        // Handle POST: save doctor notes
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveDoctorNotes($sessionId, $session);
            return;
        }

        // Load symptom details
        $selectedCodes    = $session['selected_codes'] ?? [];
        $symptomDetails   = [];
        if (!empty($selectedCodes)) {
            try {
                $db          = Database::get();
                $placeholders = implode(',', array_fill(0, count($selectedCodes), '?'));
                $rows         = $db->prepare(
                    "SELECT * FROM kb_symptoms WHERE symptom_code IN ({$placeholders})"
                );
                $rows->execute(array_values($selectedCodes));
                foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $symptomDetails[$row['symptom_code']] = $row;
                }
            } catch (\Exception $e) {
                // Graceful
            }
        }

        $this->render('doctor/session', [
            'title'           => 'Chi tiết phiên khám #' . substr($sessionId, 0, 8) . ' - ' . APP_NAME,
            'active_nav'      => 'doctor',
            'session'         => $session,
            'result'          => $resultData,
            'symptom_details' => $symptomDetails,
        ]);
    }

    /**
     * Save doctor notes for a session
     */
    private function saveDoctorNotes(string $sessionId, array $session): void
    {
        $notes           = strip_tags(trim($_POST['doctor_notes'] ?? ''));
        $confirmedPattern = strip_tags(trim($_POST['confirmed_pattern'] ?? ''));
        $doctorAction    = strip_tags(trim($_POST['doctor_action'] ?? ''));

        $notes = mb_substr($notes, 0, 2000, 'UTF-8');

        // Load existing result_data
        $resultData = $session['result_data'] ?? [];
        if (is_string($resultData)) {
            $resultData = json_decode($resultData, true) ?? [];
        }

        // Append doctor review
        $resultData['doctor_review'] = [
            'doctor_id'         => Auth::getUserId(),
            'doctor_username'   => Auth::getSessionValue('username'),
            'notes'             => $notes,
            'confirmed_pattern' => $confirmedPattern,
            'action'            => $doctorAction,
            'reviewed_at'       => date('Y-m-d H:i:s'),
        ];

        // Save back
        try {
            $db = Database::get();
            $stmt = $db->prepare(
                "UPDATE exam_sessions SET result_data = ? WHERE session_id = ?"
            );
            $stmt->execute([
                json_encode($resultData, JSON_UNESCAPED_UNICODE),
                $sessionId,
            ]);
            $_SESSION['flash_success'] = 'Đã lưu ghi chú bác sĩ thành công.';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Lỗi khi lưu ghi chú. Vui lòng thử lại.';
        }

        $this->redirect('doctor/session/' . $sessionId);
    }
}
