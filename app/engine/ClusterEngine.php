<?php
/**
 * Cluster Engine - K06 Symptom Clusters
 *
 * Detects syndrome clusters with context support.
 * A cluster is a named group of symptoms that together suggest
 * a specific YHCT condition or differential pattern.
 *
 * Cluster structure in kb_clusters:
 *   cluster_code, name_vi, symptom_codes (CSV), context_modifiers (JSON),
 *   min_match (int), threshold (float), pattern_codes (CSV - linked patterns),
 *   description, status
 */
class ClusterEngine
{
    // All K06 clusters indexed by cluster_code
    private array $clusters = [];

    // Default minimum match threshold (fraction of required codes)
    private const DEFAULT_THRESHOLD = 0.60;

    /**
     * Constructor - loads K06 cluster data from database
     */
    public function __construct()
    {
        $this->loadClusters();
    }

    /**
     * Load all active clusters from database
     */
    private function loadClusters(): void
    {
        try {
            $db   = Database::get();
            $rows = $db->query("SELECT * FROM kb_clusters WHERE status = 'active' ORDER BY cluster_code ASC")
                       ->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                // Parse CSV symptom codes (stored in required_codes column)
                $rawCodes = $row['required_codes'] ?? $row['symptom_codes'] ?? '';
                $row['symptom_codes_arr'] = !empty($rawCodes)
                    ? array_map('trim', explode(',', $rawCodes))
                    : [];

                // Parse linked pattern codes
                $row['pattern_codes_arr'] = !empty($row['pattern_codes'])
                    ? array_map('trim', explode(',', $row['pattern_codes']))
                    : [];

                // Parse context modifiers (JSON)
                $row['context_modifiers_arr'] = !empty($row['context_modifiers'])
                    ? (json_decode($row['context_modifiers'], true) ?? [])
                    : [];

                // Determine threshold
                $row['effective_threshold'] = isset($row['threshold']) && (float)$row['threshold'] > 0
                    ? (float)$row['threshold']
                    : self::DEFAULT_THRESHOLD;

                // Determine min_match (absolute count)
                $row['effective_min_match'] = isset($row['min_match']) && (int)$row['min_match'] > 0
                    ? (int)$row['min_match']
                    : max(1, (int)ceil(count($row['symptom_codes_arr']) * $row['effective_threshold']));

                $this->clusters[$row['cluster_code']] = $row;
            }
        } catch (\Exception $e) {
            $this->clusters = [];
        }
    }

    /**
     * Check all clusters against selected codes + context
     *
     * @param array $selectedCodes  Selected symptom codes
     * @param array $contextFlags   Context flags from chief complaint parsing
     * @param array $quickAnswers   Quick question answers
     * @return array Triggered clusters sorted by score DESC
     */
    public function match(array $selectedCodes, array $contextFlags = [], array $quickAnswers = []): array
    {
        $results = [];

        foreach ($this->clusters as $clusterCode => $cluster) {
            $score = $this->scoreCluster($cluster, $selectedCodes);

            // Apply context modifiers
            $score = $this->applyContextModifiers($score, $cluster, $contextFlags, $quickAnswers);

            if ($score < $cluster['effective_threshold']) {
                continue;
            }

            $matchedCodes = array_values(array_intersect($selectedCodes, $cluster['symptom_codes_arr']));

            $results[] = [
                'cluster_code'     => $clusterCode,
                'name_vi'          => $cluster['name_vi']       ?? $clusterCode,
                'description'      => $cluster['description']   ?? '',
                'score'            => round($score, 4),
                'matched_codes'    => $matchedCodes,
                'match_count'      => count($matchedCodes),
                'total_codes'      => count($cluster['symptom_codes_arr']),
                'pattern_codes'    => $cluster['pattern_codes_arr'],
                'context_boosted'  => $score > $this->scoreCluster($cluster, $selectedCodes),
            ];
        }

        // Sort by score DESC, then cluster_code ASC for determinism
        usort($results, function ($a, $b) {
            if (abs($a['score'] - $b['score']) < 0.0001) {
                return strcmp($a['cluster_code'], $b['cluster_code']);
            }
            return $b['score'] <=> $a['score'];
        });

        return $results;
    }

    /**
     * Calculate raw match score for a single cluster
     *
     * Score = matched_count / total_cluster_codes
     * Bonus if min_match threshold is exceeded
     *
     * @param array $cluster       Cluster definition with parsed fields
     * @param array $selectedCodes Currently selected codes
     * @return float Score in [0.0, 1.0]
     */
    public function scoreCluster(array $cluster, array $selectedCodes): float
    {
        $clusterCodes = $cluster['symptom_codes_arr'];
        $total        = count($clusterCodes);

        if ($total === 0) {
            return 0.0;
        }

        $matchedCount = count(array_intersect($selectedCodes, $clusterCodes));

        if ($matchedCount === 0) {
            return 0.0;
        }

        // Base score: proportion matched
        $baseScore = $matchedCount / $total;

        // Bonus if we meet or exceed the minimum match requirement
        $minMatch = $cluster['effective_min_match'];
        if ($matchedCount >= $minMatch) {
            // Small bonus for exceeding min_match, max +0.1
            $excessBonus = min(0.10, ($matchedCount - $minMatch) * 0.02);
            $baseScore   = min(1.0, $baseScore + $excessBonus);
        }

        return round($baseScore, 4);
    }

    /**
     * Apply context modifiers to a cluster score
     *
     * Context modifiers can boost or reduce score based on context flags
     * Format: [{'type': 'boost', 'condition': 'has_emotion', 'value': 0.15}, ...]
     *
     * @param float $baseScore      Base score to modify
     * @param array $cluster        Cluster with context_modifiers_arr
     * @param array $contextFlags   Context flags
     * @param array $quickAnswers   Quick answers
     * @return float Modified score
     */
    private function applyContextModifiers(
        float $baseScore,
        array $cluster,
        array $contextFlags,
        array $quickAnswers
    ): float {
        $score = $baseScore;
        $mods  = $cluster['context_modifiers_arr'];

        if (empty($mods)) {
            return $score;
        }

        foreach ($mods as $mod) {
            if (!is_array($mod)) {
                continue;
            }
            $condition = $mod['condition'] ?? null;
            $type      = $mod['type']      ?? 'boost';
            $value     = (float)($mod['value'] ?? 0.0);

            if (!$condition) {
                continue;
            }

            // Check context flag match
            $conditionMet = false;
            if (isset($contextFlags[$condition])) {
                $conditionMet = (bool)$contextFlags[$condition];
            } elseif (isset($quickAnswers[$condition])) {
                $qv           = $quickAnswers[$condition];
                $conditionMet = !empty($qv) && $qv !== 'no' && $qv !== 'không';
            }

            if (!$conditionMet) {
                continue;
            }

            if ($type === 'boost') {
                $score += $value;
            } elseif ($type === 'reduce') {
                $score -= $value;
            } elseif ($type === 'multiply') {
                $score *= $value;
            }
        }

        return min(1.0, max(0.0, round($score, 4)));
    }

    /**
     * Get all matching clusters above the default threshold
     *
     * @param array $selectedCodes
     * @param array $contextFlags
     * @return array
     */
    public function getMatchingClusters(array $selectedCodes, array $contextFlags = []): array
    {
        return $this->match($selectedCodes, $contextFlags);
    }

    /**
     * Get clusters that overlap with a specific pattern code
     *
     * @param string $patternCode
     * @return array
     */
    public function getClustersByPattern(string $patternCode): array
    {
        $result = [];
        foreach ($this->clusters as $cluster) {
            if (in_array($patternCode, $cluster['pattern_codes_arr'], true)) {
                $result[] = $cluster;
            }
        }
        return $result;
    }

    /**
     * Get symptom codes that define a cluster
     *
     * @param string $clusterCode
     * @return array
     */
    public function getClusterSymptomCodes(string $clusterCode): array
    {
        return $this->clusters[$clusterCode]['symptom_codes_arr'] ?? [];
    }

    /**
     * Get all clusters that contain a specific symptom code
     *
     * @param string $symptomCode
     * @return array
     */
    public function getClustersForSymptom(string $symptomCode): array
    {
        $result = [];
        foreach ($this->clusters as $cluster) {
            if (in_array($symptomCode, $cluster['symptom_codes_arr'], true)) {
                $result[] = $cluster;
            }
        }
        return $result;
    }

    /**
     * Get all loaded clusters
     */
    public function getClusters(): array
    {
        return $this->clusters;
    }

    /**
     * Get statistics about cluster coverage
     *
     * @param array $selectedCodes
     * @return array ['total_clusters'=>int, 'matched'=>int, 'coverage_pct'=>float]
     */
    public function getCoverageStats(array $selectedCodes): array
    {
        $total   = count($this->clusters);
        $matched = count($this->getMatchingClusters($selectedCodes));

        return [
            'total_clusters' => $total,
            'matched'        => $matched,
            'coverage_pct'   => $total > 0 ? round($matched / $total * 100, 1) : 0.0,
        ];
    }
}
