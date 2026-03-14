<?php
/**
 * Base class for all KB importers
 */
abstract class BaseImporter
{
    protected PDO $pdo;
    protected string $dataDir;
    protected int $inserted = 0;
    protected int $skipped  = 0;
    protected int $errors   = 0;

    public function __construct(PDO $pdo, string $dataDir)
    {
        $this->pdo     = $pdo;
        $this->dataDir = rtrim($dataDir, '/\\');
    }

    abstract public function import(): void;

    protected function loadJson(string $file): array
    {
        $path = $this->dataDir . DIRECTORY_SEPARATOR . $file;
        if (!file_exists($path)) {
            echo "  [SKIP] File not found: $path\n";
            return [];
        }
        $data = json_decode(file_get_contents($path), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "  [ERROR] JSON parse error in $file: " . json_last_error_msg() . "\n";
            return [];
        }
        return is_array($data) ? $data : [];
    }

    protected function encode(mixed $value): ?string
    {
        if ($value === null) return null;
        if (is_array($value) || is_object($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);
        return (string)$value;
    }

    protected function bool(mixed $value): int
    {
        return $value ? 1 : 0;
    }

    public function report(): void
    {
        $class = static::class;
        echo "[$class] inserted={$this->inserted}, skipped={$this->skipped}, errors={$this->errors}\n";
    }
}
