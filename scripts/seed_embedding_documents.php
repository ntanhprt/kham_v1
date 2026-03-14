<?php
/**
 * Seed embedding_documents table
 *
 * Populates text content from K01/K02/K04 for later embedding by the browser.
 * Embeddings (BLOB) are NULL until the admin runs the browser embedding generator.
 *
 * Usage: php scripts/seed_embedding_documents.php [--clear]
 *   --clear  : Delete all existing rows before seeding
 */

define('APP_ROOT', __DIR__ . '/../app');
define('DB_PATH',  APP_ROOT . '/storage/kham.db');
require_once APP_ROOT . '/core/Database.php';

$clear = in_array('--clear', $argv ?? []);

$pdo = Database::get();
$pdo->exec("PRAGMA journal_mode=WAL");

if ($clear) {
    $pdo->exec("DELETE FROM embedding_documents");
    echo "Cleared existing embedding_documents.\n";
}

$inserted = 0;
$skipped  = 0;

// ── Helper ────────────────────────────────────────────────────────────────────

function upsert(PDO $pdo, string $docType, string $sourceId, string $sourceTable, string $text): void {
    global $inserted, $skipped;
    if (trim($text) === '') { $skipped++; return; }

    $hash = md5($text);
    $stmt = $pdo->prepare("
        INSERT INTO embedding_documents (doc_type, source_id, source_table, text, content_hash)
        VALUES (?, ?, ?, ?, ?)
        ON CONFLICT(doc_type, source_id) DO UPDATE
            SET text = excluded.text,
                content_hash = excluded.content_hash,
                embedding = CASE WHEN content_hash != excluded.content_hash THEN NULL ELSE embedding END,
                embedding_updated_at = CASE WHEN content_hash != excluded.content_hash THEN NULL ELSE embedding_updated_at END
    ");
    $stmt->execute([$docType, $sourceId, $sourceTable, $text, $hash]);
    $inserted++;
}

// ── K02 Symptoms ──────────────────────────────────────────────────────────────

echo "\n[K02] Seeding symptoms...\n";

$symptoms = $pdo->query("
    SELECT symptom_code, name_vi, organ_system, yhct_clinical_note, yhhd_clinical_note,
           lay_descriptions_vi, context_summary_vi
    FROM kb_symptoms
    WHERE status = 'active'
    ORDER BY symptom_code
")->fetchAll(PDO::FETCH_ASSOC);

// Also load aliases per symptom
$aliasMap = [];
$aliasRows = $pdo->query("SELECT symptom_code, alias FROM symptom_aliases")->fetchAll(PDO::FETCH_ASSOC);
foreach ($aliasRows as $ar) {
    $aliasMap[$ar['symptom_code']][] = $ar['alias'];
}

foreach ($symptoms as $s) {
    $parts = array_filter([
        $s['name_vi'],
        implode(', ', $aliasMap[$s['symptom_code']] ?? []),
        $s['lay_descriptions_vi'] ?? '',
        $s['yhct_clinical_note'] ?? '',
        $s['yhhd_clinical_note'] ?? '',
        $s['context_summary_vi'] ?? '',
    ]);
    $text = implode(' — ', $parts);
    upsert($pdo, 'k02_symptom', $s['symptom_code'], 'kb_symptoms', $text);
}

echo "  Symptoms: {$inserted} upserted\n";
$cnt = $inserted; $inserted = 0;

// ── K04 Patterns ─────────────────────────────────────────────────────────────

echo "[K04] Seeding patterns...\n";

$patterns = $pdo->query("
    SELECT chung_code, name_vi, clinical_note, yhhd_correlates, phap_tri_vi, key_questions
    FROM kb_patterns
    WHERE status = 'active'
    ORDER BY chung_code
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($patterns as $p) {
    $parts = array_filter([
        $p['name_vi'] ?? '',
        $p['clinical_note'] ?? '',
        $p['yhhd_correlates'] ?? '',
        $p['phap_tri_vi'] ?? '',
        $p['key_questions'] ?? '',
    ]);
    $unique = array_unique($parts);
    $text = implode(' — ', $unique);
    upsert($pdo, 'k04_pattern', $p['chung_code'], 'kb_patterns', $text);
}

echo "  Patterns: {$inserted} upserted\n";
$cnt += $inserted; $inserted = 0;

// ── K01 Observable Phrases ────────────────────────────────────────────────────

echo "[K01] Seeding observable phrases...\n";

$phrases = $pdo->query("
    SELECT phrase_id, phrase_vi, variants_vi, organ_hint
    FROM kb_observable_phrases
    WHERE status = 'active'
    ORDER BY phrase_id
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($phrases as $ph) {
    $parts = array_filter([
        $ph['phrase_vi'] ?? '',
        $ph['variants_vi'] ?? '',
    ]);
    $text = implode(' — ', $parts);
    upsert($pdo, 'k01_phrase', (string)$ph['phrase_id'], 'kb_observable_phrases', $text);
}

echo "  Phrases: {$inserted} upserted\n";
$cnt += $inserted; $inserted = 0;

// ── K05 Red Flags ─────────────────────────────────────────────────────────────

echo "[K05] Seeding red flags...\n";

$redFlags = $pdo->query("
    SELECT rule_code, name_vi, message_vi, level
    FROM kb_red_flags
    ORDER BY rule_code
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($redFlags as $rf) {
    $parts = array_filter([
        $rf['name_vi'] ?? '',
        $rf['message_vi'] ?? '',
        "Mức độ: " . ($rf['level'] ?? ''),
    ]);
    $text = implode(' — ', $parts);
    upsert($pdo, 'k05_red_flag', $rf['rule_code'], 'kb_red_flags', $text);
}

echo "  Red flags: {$inserted} upserted\n";
$cnt += $inserted; $inserted = 0;

// ── Summary ───────────────────────────────────────────────────────────────────

echo "\nTotal seeded: " . ($cnt + $inserted) . " documents, {$skipped} skipped\n";

$totalPending = $pdo->query(
    "SELECT COUNT(*) FROM embedding_documents WHERE embedding IS NULL"
)->fetchColumn();

echo "Pending embedding: {$totalPending} documents\n";
echo "\nDone. Run the admin embedding generator at /admin/embeddings to create vectors.\n";
