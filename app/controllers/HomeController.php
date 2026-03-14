<?php
/**
 * HomeController - Trang chủ
 */
class HomeController extends Controller
{
    /**
     * GET / - Trang chủ
     */
    public function index(): void
    {
        // Load statistics from KB for display
        $stats = $this->loadStats();

        $this->render('home/index', [
            'title'         => 'Khám Bệnh Thông Minh theo Y Học Cổ Truyền',
            'stats'         => $stats,
            'active_nav'    => 'home',
        ]);
    }

    /**
     * GET /about - Giới thiệu hệ thống
     */
    public function about(): void
    {
        $this->render('home/index', [
            'title'      => 'Giới thiệu - ' . APP_NAME,
            'active_nav' => 'about',
            'stats'      => $this->loadStats(),
        ]);
    }

    /**
     * Load statistics from the knowledge base for homepage display
     */
    private function loadStats(): array
    {
        $stats = [
            'symptom_count'  => 0,
            'pattern_count'  => 0,
            'red_flag_count' => 0,
            'cluster_count'  => 0,
        ];

        try {
            $db = Database::get();

            $stats['symptom_count']  = (int)$db->query("SELECT COUNT(*) FROM kb_symptoms WHERE status='active'")->fetchColumn();
            $stats['pattern_count']  = (int)$db->query("SELECT COUNT(*) FROM kb_patterns WHERE status='active'")->fetchColumn();
            $stats['red_flag_count'] = (int)$db->query("SELECT COUNT(*) FROM kb_red_flags")->fetchColumn();
            $stats['cluster_count']  = (int)$db->query("SELECT COUNT(*) FROM kb_clusters WHERE status='active'")->fetchColumn();
        } catch (\Exception $e) {
            // Return defaults silently
        }

        return $stats;
    }
}
