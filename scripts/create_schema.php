<?php
/**
 * Create SQLite schema for Khám Tổng Quán
 * Run: C:\xampp\php\php.exe scripts/create_schema.php
 */

$dbPath = __DIR__ . '/../app/storage/kham.db';
$dir = dirname($dbPath);
if (!is_dir($dir)) mkdir($dir, 0755, true);

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA journal_mode=WAL');
$pdo->exec('PRAGMA foreign_keys=ON');

$sql = <<<'SQL'

-- =============================================
-- KNOWLEDGE BASE TABLES (imported from JSON)
-- =============================================

CREATE TABLE IF NOT EXISTS kb_observable_phrases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    phrase_id TEXT UNIQUE NOT NULL,
    phrase_vi TEXT NOT NULL,
    variants_vi TEXT,           -- JSON array
    regions TEXT,               -- JSON array
    requires_disambiguation INTEGER DEFAULT 0,
    disambiguation_question TEXT,
    options TEXT,               -- JSON array of option objects
    red_flag_trigger TEXT,      -- JSON array of option codes
    notes TEXT,
    status TEXT DEFAULT 'active',
    created_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_obs_status ON kb_observable_phrases(status);

CREATE TABLE IF NOT EXISTS kb_symptoms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symptom_code TEXT UNIQUE NOT NULL,
    name_vi TEXT NOT NULL,
    name_en TEXT,
    category TEXT,
    icd11_approximate TEXT,
    severity_weight REAL DEFAULT 0.5,
    time_sensitivity TEXT,
    bat_cuong_weights TEXT,     -- JSON {cold,heat,deficiency,excess,exterior,interior}
    zangfu_weights TEXT,        -- JSON {liver,heart,spleen,lung,kidney}
    red_flag_level TEXT,
    cluster_contribution TEXT,  -- JSON array
    yhct_clinical_note TEXT,
    yhhd_clinical_note TEXT,
    status TEXT DEFAULT 'active',
    created_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_sym_code ON kb_symptoms(symptom_code);
CREATE INDEX IF NOT EXISTS idx_sym_red_flag ON kb_symptoms(red_flag_level);

CREATE TABLE IF NOT EXISTS kb_pathogenesis (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rule_id TEXT UNIQUE NOT NULL,
    benh_co_code TEXT NOT NULL,
    benh_co_vi TEXT NOT NULL,
    benh_co_en TEXT,
    zangfu_primary TEXT,
    zangfu_secondary TEXT,      -- JSON array
    bat_cuong_profile TEXT,     -- JSON object
    required_symptoms TEXT,     -- JSON array
    supporting_symptoms TEXT,   -- JSON array
    contradicting_symptoms TEXT,-- JSON array
    differentiates_from TEXT,   -- JSON array
    derived_chung_codes TEXT,   -- JSON array
    phap_tri_direction TEXT,
    typical_herb_formula TEXT,
    clinical_notes TEXT,
    veto_rule TEXT,             -- JSON object or null
    prevalence TEXT,
    status TEXT DEFAULT 'active',
    created_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_path_zangfu ON kb_pathogenesis(zangfu_primary);

CREATE TABLE IF NOT EXISTS kb_patterns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pattern_id TEXT UNIQUE NOT NULL,
    chung_code TEXT NOT NULL,
    name_vi TEXT NOT NULL,
    name_en TEXT,
    zangfu_primary TEXT,
    pathogenesis_code TEXT,
    bat_cuong_summary TEXT,     -- JSON object
    diagnostic_criteria TEXT,   -- JSON object
    key_distinguishing_features TEXT,
    differential_diagnosis TEXT,-- JSON array
    phap_tri TEXT,              -- JSON object
    yhhd_correlations TEXT,     -- JSON array
    safety_notes TEXT,
    follow_up_timing TEXT,
    red_flag_override INTEGER DEFAULT 0,
    prevalence_vietnam TEXT,
    common_demographics TEXT,
    status TEXT DEFAULT 'active',
    created_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_pat_chung ON kb_patterns(chung_code);
CREATE INDEX IF NOT EXISTS idx_pat_zangfu ON kb_patterns(zangfu_primary);

CREATE TABLE IF NOT EXISTS kb_red_flags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rule_id TEXT UNIQUE NOT NULL,
    red_flag_code TEXT NOT NULL,
    name_vi TEXT NOT NULL,
    name_en TEXT,
    triage_level TEXT NOT NULL, -- L1_emergency, L2_urgent, L3_watch
    trigger_logic TEXT,         -- JSON object
    alternative_trigger TEXT,   -- JSON object
    display_message_vi TEXT,
    suppress_yhct_output INTEGER DEFAULT 0,
    show_emergency_only INTEGER DEFAULT 0,
    emergency_action TEXT,
    special_instructions TEXT,
    yhct_warning TEXT,
    vietnam_notes TEXT,
    status TEXT DEFAULT 'active',
    created_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_rf_triage ON kb_red_flags(triage_level);

CREATE TABLE IF NOT EXISTS kb_clusters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rule_id TEXT UNIQUE NOT NULL,
    cluster_code TEXT,
    name_vi TEXT NOT NULL,
    cluster_type TEXT,
    conditions TEXT,            -- JSON array
    required_any TEXT,          -- JSON array
    probability_formula TEXT,   -- JSON object
    threshold REAL DEFAULT 0.6,
    recommendation_vi TEXT,
    vietnam_context TEXT,
    status TEXT DEFAULT 'active',
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS kb_herb_drug (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    interaction_id TEXT UNIQUE NOT NULL,
    herb_vi TEXT NOT NULL,
    herb_en TEXT,
    herb_category TEXT,
    drug_name_vi TEXT,
    drug_class TEXT,
    drug_generic_names TEXT,    -- JSON array
    severity TEXT NOT NULL,     -- contraindicated, major_avoid, moderate_monitor, minor_caution
    clinical_effect_vi TEXT,
    action_required TEXT,
    warning_message_vi TEXT,
    evidence_level TEXT,
    vietnam_relevance REAL DEFAULT 0.5,
    phap_tri_affected TEXT,     -- JSON array
    status TEXT DEFAULT 'active',
    created_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_hd_severity ON kb_herb_drug(severity);
CREATE INDEX IF NOT EXISTS idx_hd_herb ON kb_herb_drug(herb_vi);

CREATE TABLE IF NOT EXISTS kb_kiem_chung (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rule_id TEXT UNIQUE NOT NULL,
    kiem_chung_code TEXT NOT NULL,
    name_vi TEXT NOT NULL,
    name_en TEXT,
    component_benh_co TEXT,     -- JSON array
    trigger_logic TEXT,         -- JSON object
    combined_required_symptoms TEXT, -- JSON array
    phap_tri_combined TEXT,     -- JSON object
    phap_tri_note TEXT,
    typical_herb_formula TEXT,
    formula_modifications TEXT,
    prognosis_note TEXT,
    prevalence TEXT,
    common_presentations TEXT,  -- JSON array
    warning TEXT,
    status TEXT DEFAULT 'active',
    created_at TEXT DEFAULT (datetime('now'))
);

-- =============================================
-- APPLICATION TABLES
-- =============================================

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('doctor','admin','super_admin')),
    display_name TEXT,
    phone TEXT,
    license_number TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now')),
    last_login TEXT
);

CREATE TABLE IF NOT EXISTS exam_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_token TEXT UNIQUE NOT NULL,
    patient_name TEXT,
    patient_age INTEGER,
    patient_gender TEXT CHECK(patient_gender IN ('male','female','other','unknown')),
    patient_phone TEXT,
    assigned_doctor_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    status TEXT DEFAULT 'in_progress' CHECK(status IN ('in_progress','completed','reviewed','archived')),
    chief_complaint TEXT,
    collected_symptoms TEXT,    -- JSON array of symptom codes
    disambiguation_answers TEXT,-- JSON object
    red_flag_triggered TEXT,    -- JSON object or null
    yhct_result TEXT,           -- JSON object (full reasoning output)
    yhhd_notes TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    completed_at TEXT,
    ip_address TEXT
);
CREATE INDEX IF NOT EXISTS idx_sess_token ON exam_sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_sess_doctor ON exam_sessions(assigned_doctor_id);
CREATE INDEX IF NOT EXISTS idx_sess_status ON exam_sessions(status);

CREATE TABLE IF NOT EXISTS exam_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id INTEGER NOT NULL REFERENCES exam_sessions(id) ON DELETE CASCADE,
    step TEXT NOT NULL,         -- complaint, disambiguation, symptom_checklist, demographics
    question_key TEXT,
    answer TEXT,                -- JSON or plain text
    created_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_ans_session ON exam_answers(session_id);

CREATE TABLE IF NOT EXISTS doctor_session_access (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    doctor_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_id INTEGER NOT NULL REFERENCES exam_sessions(id) ON DELETE CASCADE,
    access_type TEXT DEFAULT 'assigned' CHECK(access_type IN ('assigned','patient_selected')),
    granted_at TEXT DEFAULT (datetime('now')),
    UNIQUE(doctor_id, session_id)
);

SQL;

$pdo->exec($sql);

// Insert default super admin
$hash = password_hash('admin123', PASSWORD_BCRYPT);
$stmt = $pdo->prepare("INSERT OR IGNORE INTO users (username, email, password_hash, role, display_name) VALUES (?, ?, ?, 'super_admin', 'Super Admin')");
$stmt->execute(['admin', 'admin@kham.local', $hash]);

echo "Schema created successfully.\n";
echo "DB: {$dbPath}\n";
echo "Default login: admin / admin123\n";
