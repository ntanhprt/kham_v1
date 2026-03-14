<?php
/**
 * Build binary embedding index files
 *
 * Reads all embedded documents from embedding_documents and writes:
 *   public/embeddings/{doc_type}.bin        — 48 bytes × N (binary sign-hash vectors)
 *   public/embeddings/{doc_type}_meta.json  — [{id, src, preview}, ...]
 *
 * Usage:
 *   php scripts/build_index.php
 *   php scripts/build_index.php --json        (JSON summary output)
 *   php scripts/build_index.php k02_symptom   (rebuild only one type)
 */

define('APP_ROOT', __DIR__ . '/../app');
define('DB_PATH',  APP_ROOT . '/storage/kham.db');
require_once APP_ROOT . '/core/Database.php';

$asJson   = in_array('--json', $argv ?? []);
$typeOnly = null;
foreach ($argv ?? [] as $arg) {
    if (!str_starts_with($arg, '--') && $arg !== basename(__FILE__)) {
        $typeOnly = preg_replace('/[^a-z0-9_]/', '', $arg);
    }
}

$outputDir = __DIR__ . '/../public/embeddings';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$pdo = Database::get();

// Discover which doc_types have embedded documents
$typeQuery = $typeOnly
    ? "SELECT DISTINCT doc_type FROM embedding_documents WHERE doc_type=? AND embedding IS NOT NULL"
    : "SELECT DISTINCT doc_type FROM embedding_documents WHERE embedding IS NOT NULL";

$typeStmt = $pdo->prepare($typeQuery);
$typeStmt->execute($typeOnly ? [$typeOnly] : []);
$docTypes = $typeStmt->fetchAll(PDO::FETCH_COLUMN);

$summary = ['types' => [], 'total_vectors' => 0, 'output_dir' => $outputDir];

foreach ($docTypes as $docType) {
    $rows = $pdo->prepare(
        "SELECT id, source_id, text, embedding
         FROM embedding_documents
         WHERE doc_type = ? AND embedding IS NOT NULL
         ORDER BY id"
    );
    $rows->execute([$docType]);
    $docs = $rows->fetchAll(PDO::FETCH_ASSOC);

    if (empty($docs)) {
        continue;
    }

    $binFile  = $outputDir . '/' . $docType . '.bin';
    $metaFile = $outputDir . '/' . $docType . '_meta.json';

    $fp   = fopen($binFile, 'wb');
    $meta = [];

    foreach ($docs as $doc) {
        $blob = $doc['embedding'];

        // Ensure exactly 48 bytes (pad/truncate if needed)
        $len = strlen($blob);
        if ($len < 48) {
            $blob .= str_repeat("\0", 48 - $len);
        } elseif ($len > 48) {
            $blob = substr($blob, 0, 48);
        }

        fwrite($fp, $blob);
        $meta[] = [
            'id'      => (int)$doc['id'],
            'src'     => $doc['source_id'],
            'preview' => mb_substr($doc['text'], 0, 80, 'UTF-8'),
        ];
    }

    fclose($fp);
    file_put_contents($metaFile, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    $count = count($docs);
    $summary['types'][$docType] = [
        'count'    => $count,
        'bin_size' => filesize($binFile),
        'bin_file' => basename($binFile),
    ];
    $summary['total_vectors'] += $count;

    if (!$asJson) {
        echo "Built {$docType}.bin: {$count} vectors (" . round(filesize($binFile) / 1024, 1) . " KB)\n";
    }
}

if (!$asJson) {
    echo "\nTotal: {$summary['total_vectors']} vectors written to {$outputDir}\n";
} else {
    echo json_encode($summary, JSON_UNESCAPED_UNICODE);
}
