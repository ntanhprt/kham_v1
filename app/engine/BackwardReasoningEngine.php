<?php
/**
 * BackwardReasoningEngine — Disease Graph Traversal
 *
 * Given selected symptoms, finds probable underlying diseases (bệnh nền)
 * and complication risks by traversing disease_nodes → disease_edges.
 *
 * Algorithm:
 *   1. Map selected symptom codes → disease_nodes via symptom_disease_map
 *   2. Score each disease: sum(specificity × sensitivity) for matched symptoms
 *   3. Boost by prevalence_vn (high=1.2, medium=1.0, low=0.8, rare=0.5)
 *   4. Traverse edges: find upstream "caused_by" nodes (root causes)
 *   5. Traverse edges: find downstream "causes" nodes (complications)
 *   6. Apply context boosts (elderly → age-related, bedridden → pressure ulcer, etc.)
 *   7. Return top 3 underlying + top 3 complications + probing questions
 */
class BackwardReasoningEngine
{
    private array $diseaseNodes   = [];  // disease_code => row
    private array $edgesBySource  = [];  // source_disease => [edges]
    private array $edgesByTarget  = [];  // target_disease => [edges]
    private array $symptomMap     = [];  // symptom_code => [disease_code => {spec, sens}]

    private const PREVALENCE_BOOST = [
        'high'   => 1.25,
        'medium' => 1.00,
        'low'    => 0.75,
        'rare'   => 0.40,
    ];

    public function __construct()
    {
        $this->load();
    }

    private function load(): void
    {
        try {
            $db = Database::get();

            // Load disease nodes
            $rows = $db->query("SELECT * FROM disease_nodes WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $this->diseaseNodes[$row['disease_code']] = $row;
            }

            // Load edges indexed by source and target
            $edges = $db->query("SELECT * FROM disease_edges")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($edges as $edge) {
                $this->edgesBySource[$edge['source_disease']][] = $edge;
                $this->edgesByTarget[$edge['target_disease']][] = $edge;
            }

            // Load symptom → disease map
            $maps = $db->query("SELECT * FROM symptom_disease_map")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($maps as $m) {
                $this->symptomMap[$m['symptom_code']][$m['disease_code']] = [
                    'specificity'      => (float)$m['specificity'],
                    'sensitivity'      => (float)$m['sensitivity'],
                    'is_pathognomonic' => !empty($m['is_pathognomonic']),
                ];
            }
        } catch (\Exception $e) {
            // Graceful degradation — empty data means no backward reasoning
        }
    }

    /**
     * Main entry point
     *
     * @param array $selectedCodes  K02 symptom codes the patient selected
     * @param array $contextFlags   ['is_elderly', 'is_bedridden', 'is_pregnant', ...]
     * @param array $patternResult  Top pattern match from YHCTEngine (optional, for correlation)
     * @return array {
     *   underlying: [{disease_code, name_vi, score, confidence, yhct_correlate, probing_questions}],
     *   complications: [{disease_code, name_vi, relation, from_disease, strength}],
     *   probing_questions: [string]
     * }
     */
    public function reason(array $selectedCodes, array $contextFlags = [], array $patternResult = []): array
    {
        if (empty($this->diseaseNodes) || empty($selectedCodes)) {
            return ['underlying' => [], 'complications' => [], 'probing_questions' => []];
        }

        // Step 1+2: Score diseases by symptom overlap
        $scores = $this->scoreDiseases($selectedCodes);

        // Step 3: Apply context boosts
        $scores = $this->applyContextBoosts($scores, $contextFlags);

        // Sort and take top candidates
        arsort($scores);
        $topDiseases = array_slice(array_keys($scores), 0, 6);

        // Step 4: Build underlying disease list (non-complications)
        $underlying = [];
        foreach ($topDiseases as $code) {
            if ($scores[$code] < 0.1) continue;
            $node = $this->diseaseNodes[$code] ?? null;
            if (!$node) continue;

            // Skip if this is clearly a downstream complication of another top disease
            if ($this->isComplicationOf($code, $topDiseases)) continue;

            $underlying[] = [
                'disease_code'      => $code,
                'name_vi'           => $node['name_vi'],
                'name_en'           => $node['name_en'] ?? '',
                'category'          => $node['category'] ?? '',
                'score'             => round($scores[$code], 3),
                'confidence'        => $this->scoreToConfidence($scores[$code]),
                'yhct_correlate'    => $node['yhct_correlate'] ?? '',
                'prevalence_vn'     => $node['prevalence_vn'] ?? 'medium',
                'probing_questions' => $this->getProbingQuestions($code, $selectedCodes),
            ];

            if (count($underlying) >= 3) break;
        }

        // Step 5: Find complications from top underlying diseases
        $complications = $this->findComplications($topDiseases, $scores);

        // Step 6: Build combined probing question list
        $probing = $this->buildProbingQuestions($underlying, $complications);

        return [
            'underlying'        => $underlying,
            'complications'     => $complications,
            'probing_questions' => $probing,
        ];
    }

    /**
     * Score each disease based on symptom matches
     */
    private function scoreDiseases(array $selectedCodes): array
    {
        $scores = [];

        foreach ($selectedCodes as $symptomCode) {
            $diseaseLinks = $this->symptomMap[$symptomCode] ?? [];
            foreach ($diseaseLinks as $diseaseCode => $link) {
                if (!isset($this->diseaseNodes[$diseaseCode])) continue;

                // Pathognomonic symptoms get a big boost
                $weight = $link['specificity'] * $link['sensitivity'];
                if ($link['is_pathognomonic']) {
                    $weight *= 2.0;
                }

                $scores[$diseaseCode] = ($scores[$diseaseCode] ?? 0) + $weight;
            }
        }

        // Apply prevalence boost
        foreach ($scores as $code => $score) {
            $node = $this->diseaseNodes[$code] ?? null;
            $prevalence = $node['prevalence_vn'] ?? 'medium';
            $boost = self::PREVALENCE_BOOST[$prevalence] ?? 1.0;
            $scores[$code] = $score * $boost;
        }

        return $scores;
    }

    /**
     * Apply context-based score adjustments
     */
    private function applyContextBoosts(array $scores, array $contextFlags): array
    {
        // Elderly → boost age-related diseases
        if (!empty($contextFlags['is_elderly'])) {
            $ageRelated = [
                'hypertension', 'diabetes_mellitus_t2', 'osteoporosis', 'dementia_alzheimer',
                'parkinson_disease', 'ischemic_heart_disease', 'copd', 'chronic_kidney_disease',
                'stroke_ischemic', 'cataracts', 'hip_fracture',
            ];
            foreach ($ageRelated as $code) {
                if (isset($scores[$code])) {
                    $scores[$code] *= 1.5;
                }
            }
        }

        // Bedridden → boost pressure ulcer, pneumonia, DVT
        if (!empty($contextFlags['is_bedridden'])) {
            $bedriddenRisks = ['pressure_ulcer', 'pneumonia_aspiration', 'dvt'];
            foreach ($bedriddenRisks as $code) {
                if (isset($scores[$code])) {
                    $scores[$code] *= 2.0;
                }
            }
        }

        // Pregnant → suppress non-pregnancy-relevant diseases
        if (!empty($contextFlags['is_pregnant'])) {
            $pregnancy = ['ectopic_pregnancy', 'gestational_diabetes', 'preeclampsia'];
            foreach ($pregnancy as $code) {
                if (isset($scores[$code])) {
                    $scores[$code] *= 2.0;
                }
            }
        }

        return $scores;
    }

    /**
     * Check if a disease is a known complication of any other disease in the list
     */
    private function isComplicationOf(string $diseaseCode, array $topDiseases): bool
    {
        $edges = $this->edgesByTarget[$diseaseCode] ?? [];
        foreach ($edges as $edge) {
            if ($edge['relation_type'] === 'causes'
             && in_array($edge['source_disease'], $topDiseases, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Find complications (downstream diseases) from a set of source diseases
     */
    private function findComplications(array $sourceDiseases, array $allScores): array
    {
        $complications = [];
        $seen = [];

        foreach ($sourceDiseases as $sourceCode) {
            $edges = $this->edgesBySource[$sourceCode] ?? [];
            foreach ($edges as $edge) {
                if (!in_array($edge['relation_type'], ['causes', 'risk_factor'], true)) continue;
                $targetCode = $edge['target_disease'];
                if (isset($seen[$targetCode])) continue;

                $node = $this->diseaseNodes[$targetCode] ?? null;
                if (!$node) continue;

                $seen[$targetCode] = true;
                $complications[] = [
                    'disease_code' => $targetCode,
                    'name_vi'      => $node['name_vi'],
                    'name_en'      => $node['name_en'] ?? '',
                    'relation'     => $edge['relation_type'],
                    'from_disease' => $sourceCode,
                    'from_name_vi' => $this->diseaseNodes[$sourceCode]['name_vi'] ?? $sourceCode,
                    'strength'     => (float)$edge['strength'],
                    'clinical_note'=> $edge['clinical_note'] ?? '',
                ];
            }
        }

        // Sort by edge strength DESC
        usort($complications, fn($a, $b) => $b['strength'] <=> $a['strength']);
        return array_slice($complications, 0, 3);
    }

    /**
     * Generate probing questions to confirm a suspected disease
     */
    private function getProbingQuestions(string $diseaseCode, array $alreadySelected): array
    {
        // Static question bank per disease category/code
        $questionBank = [
            'diabetes_mellitus_t2'  => ['Bạn có thường xuyên khát nước nhiều không?', 'Bạn có tiền sử đái tháo đường trong gia đình không?', 'Gần đây bạn có xét nghiệm đường huyết không?'],
            'hypertension'          => ['Bạn có biết huyết áp của mình không?', 'Bạn có hay bị đau đầu buổi sáng khi vừa thức dậy không?'],
            'ischemic_heart_disease'=> ['Bạn có bị đau ngực khi gắng sức hoặc leo cầu thang không?', 'Cơn đau có lan lên vai trái hoặc hàm không?'],
            'copd'                  => ['Bạn có hút thuốc lá không? Nếu có, bao nhiêu năm?', 'Bạn có khó thở khi đi bộ bình thường không?'],
            'chronic_kidney_disease'=> ['Bạn có bị phù mặt vào buổi sáng không?', 'Nước tiểu có bọt hoặc màu bất thường không?'],
            'tuberculosis'          => ['Bạn có tiếp xúc với người bị lao không?', 'Ho này kéo dài bao lâu rồi?', 'Bạn có đổ mồ hôi đêm nhiều không?'],
            'stroke_ischemic'       => ['Bạn có tay chân yếu một bên, nói khó, hoặc méo miệng không?', 'Triệu chứng xuất hiện đột ngột hay từ từ?'],
            'depression'            => ['Tình trạng này kéo dài bao nhiêu tuần?', 'Bạn có mất hứng thú với các hoạt động từng yêu thích không?'],
            'anxiety_disorder'      => ['Lo lắng này ảnh hưởng đến cuộc sống hàng ngày không?', 'Bạn có hay bị tim đập nhanh hoặc khó thở khi lo lắng không?'],
            'cirrhosis'             => ['Bạn có uống rượu bia thường xuyên không?', 'Bạn có tiền sử viêm gan không?'],
            'gastric_ulcer'         => ['Đau bụng có liên quan đến bữa ăn không (trước hay sau ăn)?', 'Bạn có uống aspirin hoặc thuốc giảm đau thường xuyên không?'],
            'hypothyroidism'        => ['Bạn có tăng cân gần đây mà không rõ lý do không?', 'Bạn có thường xuyên cảm thấy lạnh hơn người khác không?'],
            'hyperthyroidism'       => ['Bạn có bị giảm cân dù ăn nhiều không?', 'Bạn có hay ra mồ hôi và tim đập nhanh không?'],
            'osteoporosis'          => ['Bạn có gãy xương sau va chạm nhẹ không?', 'Bạn có uống sữa hoặc bổ sung canxi không?'],
        ];

        return $questionBank[$diseaseCode] ?? [];
    }

    /**
     * Build a deduplicated list of probing questions from underlying + complication lists
     */
    private function buildProbingQuestions(array $underlying, array $complications): array
    {
        $questions = [];
        foreach ($underlying as $u) {
            foreach ($u['probing_questions'] as $q) {
                if (!in_array($q, $questions, true)) {
                    $questions[] = $q;
                }
            }
        }
        return array_slice($questions, 0, 5);
    }

    /**
     * Convert raw score to a human-readable confidence level
     */
    private function scoreToConfidence(float $score): string
    {
        if ($score >= 0.6) return 'high';
        if ($score >= 0.3) return 'medium';
        return 'low';
    }

    /**
     * True if the engine has any data loaded
     */
    public function hasData(): bool
    {
        return !empty($this->diseaseNodes);
    }
}
