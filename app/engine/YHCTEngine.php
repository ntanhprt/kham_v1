<?php
/**
 * YHCT Engine - Core diagnostic engine
 *
 * Implements the full YHCT diagnostic pipeline:
 * Phase 0: Parse chief complaint (tokenize, extract context)
 * Phase 1: Score and rank symptoms (SymptomRanker)
 * Phase 2: Apply pathogenesis rules (K03)
 * Phase 3: Match diagnostic patterns (K04)
 * Phase 4: Generate treatment plan
 *
 * All results are deterministic: same input = same output
 */
class YHCTEngine
{
    // Loaded KB data
    private array $k02Symptoms    = [];
    private array $k03Rules       = [];
    private array $k04Patterns    = [];
    private array $k05RedFlags    = [];

    // Co-occurrence matrix (cached)
    private array $coOccurrenceMatrix = [];
    private bool  $matrixBuilt        = false;

    // Scoring weights for RelevanceScore
    private const W_ANCHOR          = 0.30;
    private const W_ORGAN           = 0.15;
    private const W_COOCCURRENCE    = 0.20;
    private const W_CATEGORY        = 0.10;
    private const W_REDFLAG_SAT     = 0.15;
    private const W_CONTEXT_TRIGGER = 0.10;

    // Organ system keys (Tạng Phủ)
    private const TANG_LIST = ['can', 'tam', 'ty', 'phe', 'than', 'vi', 'dan', 'tieu_truong', 'dai_truong', 'bang_quang'];

    // Vietnamese stop words for tokenization
    private const STOP_WORDS = [
        'tôi', 'bị', 'bị bị', 'và', 'hoặc', 'nhưng', 'vì', 'do', 'khi', 'sau',
        'trước', 'đang', 'đã', 'sẽ', 'rất', 'hay', 'có', 'không', 'thì', 'mà',
        'cũng', 'đều', 'để', 'được', 'vào', 'ra', 'lên', 'xuống', 'đến', 'từ',
        'về', 'trong', 'ngoài', 'trên', 'dưới', 'với', 'cho', 'của', 'là', 'này',
        'đó', 'nó', 'họ', 'mình', 'em', 'anh', 'chị', 'ông', 'bà', 'cháu',
    ];

    // Context trigger patterns (Vietnamese)
    private const CONTEXT_PATTERNS = [
        // Causal patterns
        'causal'   => ['do', 'vì', 'bởi', 'sau khi', 'nguyên nhân', 'vì vậy'],
        // Temporal patterns
        'temporal' => ['sáng', 'chiều', 'tối', 'đêm', 'ngày', 'tuần', 'tháng', 'năm',
                       'hàng ngày', 'mỗi ngày', 'thỉnh thoảng', 'thường xuyên',
                       'liên tục', 'đột ngột', 'dần dần', 'từ từ'],
        // Proxy patterns (body location)
        'proxy'    => ['đầu', 'ngực', 'bụng', 'lưng', 'cổ', 'vai', 'tay', 'chân',
                       'mắt', 'tai', 'mũi', 'họng', 'tim', 'gan', 'thận', 'dạ dày'],
        // Negation
        'negation' => ['không', 'chưa', 'chẳng', 'không có', 'không bị', 'không thấy',
                       'hết', 'khỏi', 'không còn'],
        // Drug mentions
        'drug'     => ['thuốc', 'uống thuốc', 'tiêm', 'thuốc tây', 'kháng sinh',
                       'aspirin', 'paracetamol', 'ibuprofen', 'thuốc hạ áp',
                       'thuốc tiểu đường', 'warfarin', 'thuốc loãng máu'],
    ];

    // Emotion / psychological triggers
    private const EMOTION_PATTERNS = ['stress', 'lo lắng', 'căng thẳng', 'buồn', 'tức giận',
                                       'tức', 'giận', 'sợ', 'hồi hộp', 'trầm cảm'];

    /**
     * Constructor - loads all KB data from database
     */
    public function __construct()
    {
        $db = Database::get();
        $this->loadK02($db);
        $this->loadK03($db);
        $this->loadK04($db);
        $this->loadK05($db);
    }

    /**
     * Load K02 symptoms (actual schema: kb_symptoms + symptom_aliases)
     */
    private function loadK02(PDO $db): void
    {
        $rows = $db->query("SELECT * FROM kb_symptoms WHERE status = 'active' ORDER BY symptom_code")
                   ->fetchAll(PDO::FETCH_ASSOC);
        // Load aliases
        $aliasRows = $db->query("SELECT symptom_code, GROUP_CONCAT(alias, ',') as aliases FROM symptom_aliases GROUP BY symptom_code")
                        ->fetchAll(PDO::FETCH_ASSOC);
        $aliasMap = [];
        foreach ($aliasRows as $ar) {
            $aliasMap[$ar['symptom_code']] = $ar['aliases'];
        }
        foreach ($rows as $row) {
            $row['aliases_arr'] = !empty($aliasMap[$row['symptom_code']])
                ? array_map('trim', explode(',', $aliasMap[$row['symptom_code']]))
                : [];
            // Parse JSON fields
            $row['bat_cuong_weights'] = json_decode($row['bat_cuong_weights'] ?? '{}', true) ?: [];
            $row['lay_descriptions_vi'] = json_decode($row['lay_descriptions_vi'] ?? '[]', true) ?: [];
            // Alias for engine compatibility
            $row['tang'] = $row['organ_system'] ?? null;
            $this->k02Symptoms[$row['symptom_code']] = $row;
        }
    }

    /**
     * Load K03 pathogenesis rules (actual schema: kb_pathogenesis_rules)
     */
    private function loadK03(PDO $db): void
    {
        try {
            $rows = $db->query("SELECT * FROM kb_pathogenesis_rules ORDER BY rule_code")
                       ->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                // required_symptoms and supporting_symptoms are JSON arrays
                $row['required_arr']   = json_decode($row['required_symptoms'] ?? '[]', true) ?: [];
                $row['supporting_arr'] = json_decode($row['supporting_symptoms'] ?? '[]', true) ?: [];
                $row['symptom_codes_arr'] = array_merge($row['required_arr'], $row['supporting_arr']);
                $row['tang'] = $row['organ_system'] ?? null;
                $this->k03Rules[$row['rule_code']] = $row;
            }
        } catch (\Exception $e) {
            $this->k03Rules = [];
        }
    }

    /**
     * Load K04 patterns (actual schema: kb_patterns with chung_code, required_symptoms, two_or_more_of)
     */
    private function loadK04(PDO $db): void
    {
        try {
            $rows = $db->query("SELECT * FROM kb_patterns WHERE status = 'active' ORDER BY chung_code")
                       ->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                // Parse JSON symptom lists using actual column names
                $row['required_codes_arr'] = json_decode($row['required_symptoms'] ?? '[]', true) ?: [];
                $row['optional_codes_arr'] = array_merge(
                    json_decode($row['two_or_more_of'] ?? '[]', true) ?: [],
                    json_decode($row['supporting_symptoms'] ?? '[]', true) ?: []
                );
                $row['exclude_codes_arr'] = json_decode($row['differentiating_symptoms_negative'] ?? '[]', true) ?: [];
                // pattern_code alias for compatibility
                $row['pattern_code'] = $row['chung_code'];
                $row['name_vi']      = $row['name_vi'] ?? $row['chung_code'];
                $row['primary_tang'] = $row['organ_system'] ?? null;
                // Parse phuong_thuoc: may be JSON array OR pipe-separated string
                $rawPT = $row['phuong_thuoc'] ?? '';
                $ptArr = json_decode($rawPT, true);
                if (!is_array($ptArr)) {
                    $ptArr = !empty($rawPT)
                        ? array_values(array_filter(array_map('trim', explode('|', $rawPT))))
                        : [];
                }
                $row['phuong_thuoc_arr']  = $ptArr;
                // Parse huyet_vi JSON array
                $rawHV = $row['huyet_vi'] ?? '[]';
                $hvArr = json_decode($rawHV, true);
                if (!is_array($hvArr)) {
                    $hvArr = !empty($rawHV) ? array_map('trim', explode(',', $rawHV)) : [];
                }
                $row['huyet_vi_arr']      = array_values(array_filter($hvArr));
                $row['key_questions_arr'] = json_decode($row['key_questions'] ?? '[]', true) ?: [];
                // Parse yhhd_correlates
                $rawYHHD = $row['yhhd_correlates'] ?? '[]';
                $yhhdArr = json_decode($rawYHHD, true);
                $row['yhhd_arr'] = is_array($yhhdArr) ? $yhhdArr : [];
                $this->k04Patterns[$row['chung_code']] = $row;
            }
        } catch (\Exception $e) {
            $this->k04Patterns = [];
        }
    }

    /**
     * Load K05 red flags (actual schema: rule_code, trigger_symptoms, satellite_symptoms)
     */
    private function loadK05(PDO $db): void
    {
        try {
            $rows = $db->query("SELECT * FROM kb_red_flags ORDER BY level DESC, rule_code")
                       ->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                // Parse JSON arrays (actual schema column names)
                $row['symptom_codes_arr']    = json_decode($row['trigger_symptoms'] ?? '[]', true) ?: [];
                $row['satellite_codes_arr']  = json_decode($row['satellite_symptoms'] ?? '[]', true) ?: [];
                $row['combination_arr']      = json_decode($row['trigger_combination'] ?? '[]', true) ?: [];
                $row['context_triggers_arr'] = []; // Not used in current schema
                // Compatibility aliases
                $row['code']        = $row['rule_code'];
                $row['action']      = $row['action_vi'] ?? '';
                $row['description'] = $row['message_vi'] ?? '';
                $this->k05RedFlags[$row['rule_code']] = $row;
            }
        } catch (\Exception $e) {
            $this->k05RedFlags = [];
        }
    }

    // =========================================================================
    // MAIN ENTRY POINT
    // =========================================================================

    /**
     * Main analysis method - takes session data, returns full diagnostic result
     *
     * @param array $session Full session array from ExamSessionModel
     * @return array Result array
     */
    public function analyze(array $session): array
    {
        $selectedCodes = $session['selected_codes'] ?? [];
        $contextFlags  = $session['context_flags']  ?? [];
        $quickAnswers  = $session['quick_answers']   ?? [];

        // Ensure selectedCodes is flat array of strings
        if (!is_array($selectedCodes)) {
            $selectedCodes = [];
        }
        $selectedCodes = array_values(array_filter(array_unique($selectedCodes)));

        // Step 1: Check red flags (P0 - must run first)
        $redFlags    = $this->checkRedFlags($selectedCodes, $contextFlags);
        $triageLevel = $this->computeTriageLevel($redFlags);

        // Step 2: Apply K03 pathogenesis rules
        $k03Scores = $this->applyK03Rules($selectedCodes, $contextFlags, $quickAnswers);

        // Step 3: Match K04 patterns
        $patternMatches = $this->matchPatterns($selectedCodes, $k03Scores);

        // Step 4: Build bat_cuong (Bát Cương) balance
        $batCuong = $this->buildBatCuong($patternMatches, $selectedCodes);

        // Step 5: Compute organ system involvement
        $organSystems = $this->computeOrganSystems($patternMatches, $selectedCodes);

        // Step 6: Build full result
        $result = $this->buildResult($patternMatches, $selectedCodes, $quickAnswers);

        // Attach computed data
        $result['triage_level']  = $triageLevel;
        $result['red_flags']     = $redFlags;
        $result['bat_cuong']     = $batCuong;
        $result['organ_systems'] = $organSystems;

        // Step 7: Apply drug/context warnings from quick answers
        $result = $this->applyQuickAnswerContext($result, $quickAnswers, $contextFlags);

        // Step 8: Backward reasoning — find underlying diseases and complications
        if (class_exists('BackwardReasoningEngine')) {
            try {
                $backward = new BackwardReasoningEngine();
                if ($backward->hasData()) {
                    $backwardResult = $backward->reason($selectedCodes, $contextFlags, $patternMatches);
                    $result['underlying_diseases'] = $backwardResult['underlying']    ?? [];
                    $result['complication_risks']  = $backwardResult['complications'] ?? [];
                    $result['probing_questions']   = $backwardResult['probing_questions'] ?? [];
                }
            } catch (\Exception $e) {
                // Non-critical — degraded gracefully
            }
        }

        return $result;
    }

    // =========================================================================
    // PHASE 0: PARSE CHIEF COMPLAINT
    // =========================================================================

    /**
     * Tokenize Vietnamese chief complaint, extract causal/temporal/proxy/negation/drug patterns
     *
     * @param string $input Raw chief complaint text
     * @return array ['tokens', 'context_flags', 'extracted_phrases', 'drug_mentions']
     */
    public function parseChiefComplaint(string $input): array
    {
        $input  = trim($input);
        $lower  = mb_strtolower($input, 'UTF-8');

        // 1. Tokenize: split on spaces and punctuation, remove stop words
        $rawTokens = preg_split('/[\s,;.!?\/]+/u', $lower, -1, PREG_SPLIT_NO_EMPTY);
        $tokens    = array_values(array_filter($rawTokens, function ($t) {
            return mb_strlen($t, 'UTF-8') >= 2 && !in_array($t, self::STOP_WORDS, true);
        }));

        // 2. Extract context flags
        $contextFlags = [
            'has_negation' => false,
            'has_causal'   => false,
            'has_temporal' => false,
            'has_emotion'  => false,
            'has_drug'     => false,
            'temporal_ref' => null,
            'location_ref' => null,
        ];

        // Check negation
        foreach (self::CONTEXT_PATTERNS['negation'] as $pat) {
            if (mb_strpos($lower, $pat, 0, 'UTF-8') !== false) {
                $contextFlags['has_negation'] = true;
                break;
            }
        }
        // Check causal
        foreach (self::CONTEXT_PATTERNS['causal'] as $pat) {
            if (mb_strpos($lower, $pat, 0, 'UTF-8') !== false) {
                $contextFlags['has_causal'] = true;
                break;
            }
        }
        // Check temporal and capture first match
        foreach (self::CONTEXT_PATTERNS['temporal'] as $pat) {
            if (mb_strpos($lower, $pat, 0, 'UTF-8') !== false) {
                $contextFlags['has_temporal'] = true;
                if ($contextFlags['temporal_ref'] === null) {
                    $contextFlags['temporal_ref'] = $pat;
                }
            }
        }
        // Check proxy (body location)
        foreach (self::CONTEXT_PATTERNS['proxy'] as $pat) {
            if (mb_strpos($lower, $pat, 0, 'UTF-8') !== false) {
                if ($contextFlags['location_ref'] === null) {
                    $contextFlags['location_ref'] = $pat;
                }
            }
        }
        // Check emotions
        foreach (self::EMOTION_PATTERNS as $pat) {
            if (mb_strpos($lower, $pat, 0, 'UTF-8') !== false) {
                $contextFlags['has_emotion'] = true;
                break;
            }
        }

        // 3. Extract drug mentions
        $drugMentions = [];
        foreach (self::CONTEXT_PATTERNS['drug'] as $pat) {
            if (mb_strpos($lower, $pat, 0, 'UTF-8') !== false) {
                $contextFlags['has_drug'] = true;
                $drugMentions[] = $pat;
            }
        }
        $drugMentions = array_unique($drugMentions);

        // 4. Match tokens against K01 observable phrases (alias matching)
        $extractedPhrases = $this->matchObservablePhrases($tokens, $lower);

        // 5. Determine matched symptom codes from K02 (alias matching)
        $matchedCodes = $this->matchSymptomCodesFromTokens($tokens, $lower);

        return [
            'tokens'            => $tokens,
            'context_flags'     => $contextFlags,
            'extracted_phrases' => $extractedPhrases,
            'drug_mentions'     => array_values($drugMentions),
            'matched_codes'     => $matchedCodes,
            'raw_input'         => $input,
        ];
    }

    /**
     * Match tokens against KB observable phrases
     */
    private function matchObservablePhrases(array $tokens, string $lowerInput): array
    {
        $matched = [];
        try {
            $db   = Database::get();
            $rows = $db->query("SELECT * FROM kb_observable_phrases WHERE status = 'active'")
                       ->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                // Actual schema: phrase_id, phrase_vi, variants_vi, linked_symptom_codes
                $phraseCode    = $row['phrase_id']            ?? $row['phrase_code']   ?? '';
                $phraseVi      = $row['phrase_vi']            ?? '';
                $variants      = $row['variants_vi']          ?? $row['aliases']       ?? '';
                $symptomCodes  = $row['linked_symptom_codes'] ?? $row['symptom_codes'] ?? '';

                $phrase = mb_strtolower($phraseVi, 'UTF-8');
                if ($phrase && mb_strpos($lowerInput, $phrase, 0, 'UTF-8') !== false) {
                    $matched[] = [
                        'code'          => $phraseCode,
                        'phrase_vi'     => $phraseVi,
                        'symptom_codes' => $symptomCodes,
                    ];
                    continue;
                }
                // Also check variants
                if (!empty($variants)) {
                    foreach (explode(',', $variants) as $alias) {
                        $alias = mb_strtolower(trim($alias), 'UTF-8');
                        if ($alias && mb_strpos($lowerInput, $alias, 0, 'UTF-8') !== false) {
                            $matched[] = [
                                'code'          => $phraseCode,
                                'phrase_vi'     => $phraseVi,
                                'symptom_codes' => $symptomCodes,
                            ];
                            break;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Graceful degradation
        }
        // Deduplicate
        $seen   = [];
        $result = [];
        foreach ($matched as $m) {
            if (!isset($seen[$m['code']])) {
                $seen[$m['code']] = true;
                $result[]         = $m;
            }
        }
        return $result;
    }

    /**
     * Match symptom codes from tokens/input via alias lookup in K02
     */
    private function matchSymptomCodesFromTokens(array $tokens, string $lowerInput): array
    {
        $matched = [];

        // Pass 1: forward matching — input contains a symptom name or alias (original logic)
        foreach ($this->k02Symptoms as $code => $sym) {
            $nameLower = mb_strtolower($sym['name_vi'] ?? '', 'UTF-8');
            if ($nameLower && mb_strpos($lowerInput, $nameLower, 0, 'UTF-8') !== false) {
                $matched[$code] = true;
                continue;
            }
            foreach ($sym['aliases_arr'] as $alias) {
                $aliasLower = mb_strtolower(trim($alias), 'UTF-8');
                if ($aliasLower && mb_strpos($lowerInput, $aliasLower, 0, 'UTF-8') !== false) {
                    $matched[$code] = true;
                    break;
                }
            }
        }

        // Pass 2: reverse matching — only when forward pass found nothing (short/novel input)
        // A token must be >= 4 chars to avoid matching generic roots like "đau", "mất"
        if (empty($matched)) {
            $specificTokens = array_filter($tokens, fn($t) => mb_strlen($t, 'UTF-8') >= 4);
            foreach ($this->k02Symptoms as $code => $sym) {
                $nameLower = mb_strtolower($sym['name_vi'] ?? '', 'UTF-8');
                if ($nameLower) {
                    foreach ($specificTokens as $tok) {
                        if (mb_strpos($nameLower, $tok, 0, 'UTF-8') !== false) {
                            $matched[$code] = true;
                            continue 2;
                        }
                    }
                }
                foreach ($sym['aliases_arr'] as $alias) {
                    $aliasLower = mb_strtolower(trim($alias), 'UTF-8');
                    if (!$aliasLower) {
                        continue;
                    }
                    foreach ($specificTokens as $tok) {
                        if (mb_strpos($aliasLower, $tok, 0, 'UTF-8') !== false) {
                            $matched[$code] = true;
                            break 2;
                        }
                    }
                }
            }
        }

        return array_keys($matched);
    }

    // =========================================================================
    // PHASE 1: SYMPTOM RANKING
    // =========================================================================

    /**
     * Score and rank symptoms for picker display
     *
     * RelevanceScore = W1×AnchorScore + W2×OrganScore + W3×CoOccurrenceScore
     *                + W4×CategoryScore + W5×RedFlagSatelliteScore + W6×ContextTriggerScore
     *
     * @param array $selectedCodes   Currently selected symptom codes
     * @param array $contextFlags    Context flags from parseChiefComplaint
     * @param array $contextTriggers Matched codes from chief complaint
     * @return array Scored symptom list sorted by score DESC
     */
    public function rankSymptoms(
        array $selectedCodes,
        array $contextFlags,
        array $contextTriggers = []
    ): array {
        if (!$this->matrixBuilt) {
            $this->buildCoOccurrenceMatrix();
        }

        $scored = [];

        foreach ($this->k02Symptoms as $code => $sym) {
            // Skip already selected
            if (in_array($code, $selectedCodes, true)) {
                continue;
            }

            $score = 0.0;

            // W1 - Anchor Score: is this code in contextTriggers / chief complaint match?
            $anchorScore = in_array($code, $contextTriggers, true) ? 1.0 : 0.0;

            // W2 - Organ Score: does this share organ (tang) with selected symptoms?
            $organScore = $this->computeOrganScore($code, $selectedCodes);

            // W3 - Co-occurrence Score: how often do these appear together in patterns?
            $coOccurrenceScore = $this->computeCoOccurrenceScore($code, $selectedCodes);

            // W4 - Category Score: same category as selected symptoms
            $categoryScore = $this->computeCategoryScore($code, $selectedCodes);

            // W5 - Red Flag Satellite Score: is this a satellite symptom of a red flag?
            $redFlagSatScore = $this->computeRedFlagSatelliteScore($code, $selectedCodes);

            // W6 - Context Trigger Score: matches temporal/causal/location context
            $contextTriggerScore = $this->computeContextTriggerScore($code, $contextFlags);

            $score = (self::W_ANCHOR          * $anchorScore)
                   + (self::W_ORGAN           * $organScore)
                   + (self::W_COOCCURRENCE    * $coOccurrenceScore)
                   + (self::W_CATEGORY        * $categoryScore)
                   + (self::W_REDFLAG_SAT     * $redFlagSatScore)
                   + (self::W_CONTEXT_TRIGGER * $contextTriggerScore);

            $scored[] = [
                'code'     => $code,
                'symptom'  => $sym,
                'score'    => round($score, 4),
                'scores'   => [
                    'anchor'       => $anchorScore,
                    'organ'        => $organScore,
                    'cooccurrence' => $coOccurrenceScore,
                    'category'     => $categoryScore,
                    'redflag_sat'  => $redFlagSatScore,
                    'context'      => $contextTriggerScore,
                ],
                'group'    => $sym['category'] ?? 'other',
                'tang'     => $sym['tang'] ?? null,
                'is_red_flag_satellite' => $redFlagSatScore > 0,
            ];
        }

        // Sort by score DESC, then by symptom_code ASC for determinism
        usort($scored, function ($a, $b) {
            if (abs($a['score'] - $b['score']) < 0.0001) {
                return strcmp($a['code'], $b['code']);
            }
            return $b['score'] <=> $a['score'];
        });

        return $scored;
    }

    /**
     * Compute organ overlap score between candidate and selected codes
     */
    private function computeOrganScore(string $candidate, array $selectedCodes): float
    {
        if (empty($selectedCodes)) {
            return 0.0;
        }
        $candTang = $this->k02Symptoms[$candidate]['tang'] ?? null;
        if (!$candTang) {
            return 0.0;
        }
        $matches = 0;
        foreach ($selectedCodes as $sc) {
            if (($this->k02Symptoms[$sc]['tang'] ?? null) === $candTang) {
                $matches++;
            }
        }
        return min(1.0, $matches / max(1, count($selectedCodes)));
    }

    /**
     * Compute co-occurrence score using pre-built matrix
     */
    private function computeCoOccurrenceScore(string $candidate, array $selectedCodes): float
    {
        if (empty($selectedCodes) || empty($this->coOccurrenceMatrix[$candidate])) {
            return 0.0;
        }
        $total = 0.0;
        foreach ($selectedCodes as $sc) {
            $total += $this->coOccurrenceMatrix[$candidate][$sc] ?? 0.0;
        }
        return min(1.0, $total / count($selectedCodes));
    }

    /**
     * Compute category overlap score
     */
    private function computeCategoryScore(string $candidate, array $selectedCodes): float
    {
        if (empty($selectedCodes)) {
            return 0.0;
        }
        $candCat = $this->k02Symptoms[$candidate]['category'] ?? null;
        if (!$candCat) {
            return 0.0;
        }
        $matches = 0;
        foreach ($selectedCodes as $sc) {
            if (($this->k02Symptoms[$sc]['category'] ?? null) === $candCat) {
                $matches++;
            }
        }
        return min(1.0, $matches / max(1, count($selectedCodes)));
    }

    /**
     * Compute red flag satellite score - does this symptom orbit a triggered red flag?
     */
    private function computeRedFlagSatelliteScore(string $candidate, array $selectedCodes): float
    {
        foreach ($this->k05RedFlags as $rf) {
            // Is candidate in the satellite codes of this red flag?
            if (!in_array($candidate, $rf['symptom_codes_arr'], true)) {
                continue;
            }
            // Is at least one selected code also in this red flag?
            foreach ($selectedCodes as $sc) {
                if (in_array($sc, $rf['symptom_codes_arr'], true)) {
                    return 1.0;
                }
            }
        }
        return 0.0;
    }

    /**
     * Compute context trigger score (temporal/location/emotion match)
     */
    private function computeContextTriggerScore(string $candidate, array $contextFlags): float
    {
        $sym   = $this->k02Symptoms[$candidate] ?? [];
        $score = 0.0;

        // Emotion-related symptoms score higher when emotional context detected
        if (!empty($contextFlags['has_emotion'])) {
            $cat = $sym['category'] ?? '';
            if (in_array($cat, ['than_kinh', 'tam_than', 'ngu', 'can_khi'], true)) {
                $score += 0.5;
            }
        }

        // Location match
        if (!empty($contextFlags['location_ref'])) {
            $loc     = $contextFlags['location_ref'];
            $aliases = implode(' ', $sym['aliases_arr']);
            $namevi  = mb_strtolower($sym['name_vi'] ?? '', 'UTF-8');
            if (mb_strpos($namevi, $loc, 0, 'UTF-8') !== false
             || mb_strpos(mb_strtolower($aliases, 'UTF-8'), $loc, 0, 'UTF-8') !== false) {
                $score += 0.5;
            }
        }

        return min(1.0, $score);
    }

    // =========================================================================
    // CO-OCCURRENCE MATRIX
    // =========================================================================

    /**
     * Build static/cached co-occurrence matrix from K03+K04 data
     */
    public function buildCoOccurrenceMatrix(): void
    {
        $this->coOccurrenceMatrix = [];
        $allCoSets = [];

        // From K03 rules: symptoms that share a pathogenesis rule
        foreach ($this->k03Rules as $rule) {
            $codes = $rule['symptom_codes_arr'] ?? [];
            if (count($codes) >= 2) {
                $allCoSets[] = $codes;
            }
        }

        // From K04 patterns: required + optional codes per pattern
        foreach ($this->k04Patterns as $pattern) {
            $codes = array_merge(
                $pattern['required_codes_arr'] ?? [],
                $pattern['optional_codes_arr'] ?? []
            );
            $codes = array_unique(array_filter($codes));
            if (count($codes) >= 2) {
                $allCoSets[] = $codes;
            }
        }

        // Build pairwise co-occurrence counts (normalized per set size)
        foreach ($allCoSets as $set) {
            $n = count($set);
            if ($n < 2) {
                continue;
            }
            $weight = 1.0 / ($n - 1); // Smaller sets = stronger signal
            for ($i = 0; $i < $n; $i++) {
                for ($j = 0; $j < $n; $j++) {
                    if ($i === $j) {
                        continue;
                    }
                    $a = $set[$i];
                    $b = $set[$j];
                    if (!isset($this->coOccurrenceMatrix[$a])) {
                        $this->coOccurrenceMatrix[$a] = [];
                    }
                    $this->coOccurrenceMatrix[$a][$b] = ($this->coOccurrenceMatrix[$a][$b] ?? 0) + $weight;
                }
            }
        }

        // Normalize: scale each row to [0,1]
        foreach ($this->coOccurrenceMatrix as $code => &$row) {
            $max = max($row);
            if ($max > 0) {
                foreach ($row as &$val) {
                    $val = round($val / $max, 4);
                }
            }
        }
        unset($row, $val);

        $this->matrixBuilt = true;
    }

    // =========================================================================
    // K03 PATHOGENESIS APPLICATION
    // =========================================================================

    /**
     * Apply K03 pathogenesis rules to build organ/mechanism scores
     *
     * @param array $selectedCodes
     * @param array $contextFlags
     * @param array $quickAnswers
     * @return array ['rule_code' => score, ...]
     */
    private function applyK03Rules(array $selectedCodes, array $contextFlags, array $quickAnswers): array
    {
        $ruleScores = [];

        foreach ($this->k03Rules as $ruleCode => $rule) {
            $ruleCodes = $rule['symptom_codes_arr'];
            if (empty($ruleCodes)) {
                continue;
            }

            $matched = array_intersect($selectedCodes, $ruleCodes);
            if (empty($matched)) {
                continue;
            }

            // Base score: proportion of rule's symptoms that are selected
            $score = count($matched) / count($ruleCodes);

            // Boost if quick answers align with this rule's tang
            $ruleTang = $rule['tang'] ?? null;
            if ($ruleTang && !empty($quickAnswers['tongue'])) {
                $tongueHints = $this->interpretTongue($quickAnswers['tongue'], $ruleTang);
                $score       *= (1.0 + $tongueHints);
            }

            // Boost if emotional context matches Gan (Can)
            if ($ruleTang === 'can' && !empty($contextFlags['has_emotion'])) {
                $score *= 1.2;
            }

            $ruleScores[$ruleCode] = min(1.0, round($score, 4));
        }

        // Sort descending for determinism
        arsort($ruleScores);
        return $ruleScores;
    }

    /**
     * Interpret tongue signs in context of a specific tang
     * Returns a boost multiplier (0.0 to 0.3)
     */
    private function interpretTongue(string $tongueDesc, string $tang): float
    {
        $lower = mb_strtolower($tongueDesc, 'UTF-8');
        $boost = 0.0;

        $hintMap = [
            'can' => ['đỏ bên', 'rêu vàng', 'run lưỡi'],
            'than' => ['đỏ không rêu', 'lưỡi gầy', 'rêu ít'],
            'ty'  => ['bệu', 'có dấu răng', 'rêu dày', 'rêu trắng'],
            'phe' => ['rêu trắng mỏng', 'nhợt', 'khô'],
            'tam' => ['đỏ tip lưỡi', 'đỏ', 'loét lưỡi'],
        ];

        $hints = $hintMap[$tang] ?? [];
        foreach ($hints as $hint) {
            if (mb_strpos($lower, $hint, 0, 'UTF-8') !== false) {
                $boost += 0.15;
            }
        }

        return min(0.3, $boost);
    }

    // =========================================================================
    // PHASE 3: PATTERN MATCHING
    // =========================================================================

    /**
     * Match K04 patterns against selected codes and K03 scores
     *
     * @param array $selectedCodes
     * @param array $k03Scores ['rule_code' => float]
     * @return array Sorted pattern matches
     */
    public function matchPatterns(array $selectedCodes, array $k03Scores = []): array
    {
        $matches = [];

        foreach ($this->k04Patterns as $code => $pattern) {
            $required = $pattern['required_codes_arr'];
            $optional = $pattern['optional_codes_arr'];
            $exclude  = $pattern['exclude_codes_arr'];

            // Hard exclusion: if any excluded code is present, skip this pattern
            foreach ($exclude as $ex) {
                if (in_array($ex, $selectedCodes, true)) {
                    continue 2;
                }
            }

            // Must have at least one required code (if any required defined)
            if (!empty($required)) {
                $hasRequired = false;
                foreach ($required as $req) {
                    if (in_array($req, $selectedCodes, true)) {
                        $hasRequired = true;
                        break;
                    }
                }
                if (!$hasRequired) {
                    continue;
                }
            }

            // Count required matches
            $requiredMatched = !empty($required)
                ? count(array_intersect($selectedCodes, $required))
                : 0;
            $requiredTotal   = max(1, count($required));

            // Count optional matches
            $optionalMatched = !empty($optional)
                ? count(array_intersect($selectedCodes, $optional))
                : 0;
            $optionalTotal   = max(1, count($optional));

            // Base score formula:
            // 60% from required coverage + 40% from optional coverage
            $baseScore = (0.60 * ($requiredMatched / $requiredTotal))
                       + (0.40 * ($optionalMatched / max(1, count($optional) ?: 1)));

            // K03 boost: if this pattern's primary_tang matches a high K03 rule
            $k03Boost = 0.0;
            $primaryTang = $pattern['primary_tang'] ?? null;
            if ($primaryTang) {
                foreach ($k03Scores as $ruleCode => $ruleScore) {
                    $ruleTang = ($this->k03Rules[$ruleCode]['tang'] ?? null);
                    if ($ruleTang === $primaryTang) {
                        $k03Boost = max($k03Boost, $ruleScore * 0.15);
                    }
                }
            }

            $totalScore = min(1.0, $baseScore + $k03Boost);

            if ($totalScore < 0.10) {
                continue; // Skip very low scores
            }

            // Determine confidence
            if ($totalScore >= 0.70) {
                $confidence = 'high';
            } elseif ($totalScore >= 0.45) {
                $confidence = 'medium';
            } else {
                $confidence = 'low';
            }

            $matches[] = [
                'pattern'         => $pattern,
                'score'           => round($totalScore, 4),
                'confidence'      => $confidence,
                'required_hit'    => $requiredMatched,
                'required_total'  => $requiredTotal,
                'optional_hit'    => $optionalMatched,
                'optional_total'  => count($optional),
                'k03_boost'       => round($k03Boost, 4),
            ];
        }

        // Sort: score DESC, then pattern_code ASC for determinism
        usort($matches, function ($a, $b) {
            if (abs($a['score'] - $b['score']) < 0.0001) {
                return strcmp($a['pattern']['pattern_code'], $b['pattern']['pattern_code']);
            }
            return $b['score'] <=> $a['score'];
        });

        return $matches;
    }

    // =========================================================================
    // BAT CUONG (Eight Principles)
    // =========================================================================

    /**
     * Build Bát Cương balance from pattern matches and selected codes
     *
     * @param array $patternMatches
     * @param array $selectedCodes
     * @return array ['yin'=>float, 'yang'=>float, 'interior'=>float, 'exterior'=>float,
     *               'cold'=>float, 'heat'=>float, 'deficiency'=>float, 'excess'=>float]
     */
    private function buildBatCuong(array $patternMatches, array $selectedCodes): array
    {
        $bc = [
            'yin'        => 0.0,
            'yang'       => 0.0,
            'interior'   => 0.0,
            'exterior'   => 0.0,
            'cold'       => 0.0,
            'heat'       => 0.0,
            'deficiency' => 0.0,
            'excess'     => 0.0,
        ];

        // Aggregate bat_cuong_weights from selected K02 symptoms (primary source)
        $count = 0;
        foreach ($selectedCodes as $code) {
            $w = $this->k02Symptoms[$code]['bat_cuong_weights'] ?? [];
            if (empty($w)) {
                continue;
            }
            foreach (array_keys($bc) as $key) {
                $bc[$key] += (float)($w[$key] ?? 0.0);
            }
            $count++;
        }

        // Fallback: if no symptom weights, try top-3 pattern bat_cuong_arr
        if ($count === 0 && !empty($patternMatches)) {
            $weightSum = 0.0;
            foreach (array_slice($patternMatches, 0, 3) as $match) {
                $w  = $match['score'];
                $ba = $match['pattern']['bat_cuong_arr'] ?? [];
                if (empty($ba)) {
                    continue;
                }
                foreach (array_keys($bc) as $key) {
                    $bc[$key] += ($ba[$key] ?? 0.0) * $w;
                }
                $weightSum += $w;
            }
            if ($weightSum > 0) {
                foreach ($bc as &$val) {
                    $val /= $weightSum;
                }
                unset($val);
            }
        } elseif ($count > 0) {
            // Average over symptom count
            foreach ($bc as &$val) {
                $val = $val / $count;
            }
            unset($val);
        }

        // Normalize opposing pairs so each pair sums to 1.0
        $pairs = [['yin', 'yang'], ['interior', 'exterior'], ['cold', 'heat'], ['deficiency', 'excess']];
        foreach ($pairs as [$a, $b]) {
            $sum = $bc[$a] + $bc[$b];
            if ($sum > 0.001) {
                $bc[$a] = round($bc[$a] / $sum, 3);
                $bc[$b] = round($bc[$b] / $sum, 3);
            } else {
                $bc[$a] = 0.5;
                $bc[$b] = 0.5;
            }
        }

        return $bc;
    }

    // =========================================================================
    // ORGAN SYSTEMS
    // =========================================================================

    /**
     * Compute organ system involvement scores
     */
    private function computeOrganSystems(array $patternMatches, array $selectedCodes): array
    {
        $systems = array_fill_keys(self::TANG_LIST, 0.0);

        // English organ_system → Vietnamese tang code mapping
        $engToViet = [
            'liver'          => 'can',
            'gallbladder'    => 'dan',
            'heart'          => 'tam',
            'spleen'         => 'ty',
            'stomach'        => 'vi',
            'lung'           => 'phe',
            'kidney'         => 'than',
            'large_intestine'=> 'dai_truong',
            'small_intestine'=> 'tieu_truong',
            'bladder'        => 'bang_quang',
            'liver_kidney'   => 'can',   // dual — map primary
            'uterus'         => 'ty',    // map to spleen/kidney context
        ];

        // Normalize a tang value to Vietnamese key
        $normTang = function (?string $t) use ($engToViet): ?string {
            if (!$t) return null;
            return $engToViet[$t] ?? $t;
        };

        // From pattern matches
        foreach (array_slice($patternMatches, 0, 5) as $match) {
            $tang = $normTang($match['pattern']['primary_tang'] ?? null);
            if ($tang && isset($systems[$tang])) {
                $systems[$tang] += $match['score'];
            }
            // liver_kidney dual → also boost than
            $rawTang = $match['pattern']['primary_tang'] ?? null;
            if ($rawTang === 'liver_kidney' && isset($systems['than'])) {
                $systems['than'] += $match['score'] * 0.6;
            }
            // Secondary tang
            $secTang = $normTang($match['pattern']['secondary_tang'] ?? null);
            if ($secTang && isset($systems[$secTang])) {
                $systems[$secTang] += $match['score'] * 0.5;
            }
        }

        // From selected codes
        foreach ($selectedCodes as $code) {
            $tang = $normTang($this->k02Symptoms[$code]['tang'] ?? null);
            if ($tang && isset($systems[$tang])) {
                $systems[$tang] += 0.1;
            }
        }

        // Normalize to [0,1]
        $max = max($systems) ?: 1.0;
        foreach ($systems as &$v) {
            $v = round(min(1.0, $v / $max), 3);
        }

        // Remove zero-score organs
        return array_filter($systems, fn($v) => $v > 0);
    }

    // =========================================================================
    // RED FLAGS
    // =========================================================================

    /**
     * Check K05 red flags against selected codes and context flags
     *
     * @param array $selectedCodes
     * @param array $contextFlags
     * @return array Triggered red flags sorted by severity
     */
    public function checkRedFlags(array $selectedCodes, array $contextFlags): array
    {
        $triggered = [];

        foreach ($this->k05RedFlags as $ruleCode => $rule) {
            $ruleCodes   = $rule['symptom_codes_arr'];
            $ctxTriggers = $rule['context_triggers_arr'];

            $codeMatch    = !empty(array_intersect($selectedCodes, $ruleCodes));
            $contextMatch = false;

            if (!empty($ctxTriggers)) {
                foreach ($ctxTriggers as $trigger) {
                    $triggerKey = 'has_' . $trigger;
                    if (!empty($contextFlags[$triggerKey])) {
                        $contextMatch = true;
                        break;
                    }
                }
            }

            if (!$codeMatch && !$contextMatch) {
                continue;
            }

            // Compute how many codes are matched
            $matchedCodes = array_values(array_intersect($selectedCodes, $ruleCodes));

            $triggered[] = [
                'code'                => $ruleCode,
                'level'               => $rule['level'] ?? 'L3_watch',
                'name_vi'             => $rule['name_vi'] ?? '',
                'description'         => $rule['description'] ?? '',
                'action'              => $rule['action'] ?? '',
                'matched_codes'       => $matchedCodes,
                'match_count'         => count($matchedCodes),
                'suppress_yhct_output' => !empty($rule['suppress_yhct_output']),
            ];
        }

        // Sort: L1 first, then L2, then L3
        $levelOrder = ['L1_emergency' => 0, 'L2_urgent' => 1, 'L3_watch' => 2];
        usort($triggered, function ($a, $b) use ($levelOrder) {
            $la = $levelOrder[$a['level']] ?? 3;
            $lb = $levelOrder[$b['level']] ?? 3;
            if ($la !== $lb) {
                return $la <=> $lb;
            }
            return $b['match_count'] <=> $a['match_count'];
        });

        return $triggered;
    }

    /**
     * Compute triage level from triggered red flags
     */
    private function computeTriageLevel(array $redFlags): ?string
    {
        if (empty($redFlags)) {
            return null;
        }
        $levels = array_column($redFlags, 'level');
        if (in_array('L1_emergency', $levels, true)) {
            return 'L1_emergency';
        }
        if (in_array('L2_urgent', $levels, true)) {
            return 'L2_urgent';
        }
        if (in_array('L3_watch', $levels, true)) {
            return 'L3_watch';
        }
        return null;
    }

    // =========================================================================
    // ENTROPY / NEXT BEST QUESTION
    // =========================================================================

    /**
     * Calculate entropy / information gain for a symptom code given current hypotheses
     *
     * @param string $symptomCode Candidate symptom
     * @param array  $hypotheses  Current pattern hypotheses with scores
     * @return float Information gain score
     */
    public function calculateEntropyScore(string $symptomCode, array $hypotheses): float
    {
        if (empty($hypotheses)) {
            return 0.0;
        }

        // Count how many hypotheses include vs. exclude this symptom
        $withCode    = 0;
        $withoutCode = 0;
        $totalScore  = 0.0;

        foreach ($hypotheses as $h) {
            $codes = array_merge(
                $h['pattern']['required_codes_arr'] ?? [],
                $h['pattern']['optional_codes_arr'] ?? []
            );
            $s = $h['score'];
            $totalScore += $s;
            if (in_array($symptomCode, $codes, true)) {
                $withCode += $s;
            } else {
                $withoutCode += $s;
            }
        }

        if ($totalScore <= 0) {
            return 0.0;
        }

        // Information gain: maximize discrimination
        $p1 = $withCode / $totalScore;
        $p2 = $withoutCode / $totalScore;

        // Highest entropy (most discriminating) when p1 ≈ 0.5
        if ($p1 <= 0 || $p1 >= 1.0) {
            return 0.0;
        }

        // Shannon entropy
        $entropy = -($p1 * log($p1, 2)) - ($p2 * log($p2, 2));
        return round($entropy, 4);
    }

    /**
     * Get the next best differentiating question
     *
     * @param array $selectedCodes Currently selected codes
     * @param array $sessionData   Full session data
     * @return array|null Best candidate symptom info or null
     */
    public function getNextBestQuestion(array $selectedCodes, array $sessionData): ?array
    {
        $contextFlags = $sessionData['context_flags'] ?? [];
        $k03Scores    = $this->applyK03Rules($selectedCodes, $contextFlags, $sessionData['quick_answers'] ?? []);
        $topPatterns  = array_slice($this->matchPatterns($selectedCodes, $k03Scores), 0, 5);

        if (count($topPatterns) < 2) {
            return null; // Not enough competing hypotheses
        }

        // Find candidate codes in top patterns that are NOT yet selected
        $candidateCodes = [];
        foreach ($topPatterns as $p) {
            foreach (array_merge($p['pattern']['required_codes_arr'], $p['pattern']['optional_codes_arr']) as $c) {
                if (!in_array($c, $selectedCodes, true) && isset($this->k02Symptoms[$c])) {
                    $candidateCodes[$c] = true;
                }
            }
        }

        $best      = null;
        $bestScore = -1.0;

        foreach (array_keys($candidateCodes) as $candidate) {
            $entropy = $this->calculateEntropyScore($candidate, $topPatterns);
            if ($entropy > $bestScore) {
                $bestScore = $entropy;
                $best      = [
                    'code'    => $candidate,
                    'symptom' => $this->k02Symptoms[$candidate],
                    'entropy' => $entropy,
                ];
            }
        }

        return $best;
    }

    // =========================================================================
    // BUILD RESULT
    // =========================================================================

    /**
     * Build final result array from pattern matches
     *
     * @param array $patternMatches Sorted pattern match array
     * @param array $selectedCodes  Selected symptom codes
     * @param array $quickAnswers   Quick question answers
     * @return array Full result
     */
    public function buildResult(array $patternMatches, array $selectedCodes, array $quickAnswers): array
    {
        $primaryPattern = !empty($patternMatches) ? $patternMatches[0]['pattern'] : null;

        // Separate primary and kiêm chứng (co-existing patterns)
        $kiemChung  = [];
        $chungRanked = $patternMatches;
        if (count($patternMatches) > 1) {
            // Patterns within 20% of top score are competing; rest are kiêm chứng
            $topScore = $patternMatches[0]['score'];
            $kiemChung = array_filter(
                array_slice($patternMatches, 1),
                fn($m) => $m['score'] < $topScore * 0.80
            );
            $kiemChung = array_values($kiemChung);
        }

        // Build pháp trị (treatment principles)
        $phapTri = [];
        if ($primaryPattern) {
            $phapTri = $this->extractPhapTri($primaryPattern, $quickAnswers);
        }

        // Build drug warnings placeholder
        $drugWarnings = $this->buildDrugWarnings($quickAnswers);

        // Kiêm chứng cross-validation (K08)
        $kiemChungResult = $this->runKiemChung($patternMatches);

        // Build follow-up advice
        $followUp = $this->buildFollowUp($primaryPattern, $patternMatches);

        // Disclaimer
        $disclaimer = 'Kết quả này chỉ mang tính tham khảo. '
                    . 'Vui lòng tham khảo ý kiến bác sĩ chuyên khoa Y học cổ truyền '
                    . 'để được chẩn đoán và điều trị chính xác.';

        // Build Western medicine correlations from top patterns
        $yhhdResult = $this->buildYhhdResult($patternMatches, $selectedCodes);

        return [
            'triage_level'    => null,   // Will be set by analyze()
            'red_flags'       => [],     // Will be set by analyze()
            'chung_ranked'    => $chungRanked,
            'primary_pattern' => $primaryPattern,
            'bat_cuong'       => [],     // Will be set by analyze()
            'organ_systems'   => [],     // Will be set by analyze()
            'kiem_chung'      => $kiemChungResult,
            'drug_warnings'   => $drugWarnings,
            'phap_tri'        => $phapTri,
            'yhct_suppressed' => false,
            'yhct_disclaimer' => $disclaimer,
            'follow_up'       => $followUp,
            'yhhd'            => $yhhdResult,
            'selected_codes'     => $selectedCodes,
            'quick_answers'      => $quickAnswers,
            'generated_at'       => date('Y-m-d H:i:s'),
            'underlying_diseases'=> [],  // Set by BackwardReasoningEngine in analyze()
            'complication_risks' => [],  // Set by BackwardReasoningEngine in analyze()
            'probing_questions'  => [],  // Set by BackwardReasoningEngine in analyze()
        ];
    }

    /**
     * Extract pháp trị (treatment principles) from a pattern
     */
    private function extractPhapTri(array $pattern, array $quickAnswers): array
    {
        // phuong_thuoc may be JSON array or pipe-separated string
        $phuongThuoc = $pattern['phuong_thuoc_arr'] ?? [];
        if (empty($phuongThuoc)) {
            $raw = $pattern['phuong_thuoc'] ?? '';
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $phuongThuoc = $decoded;
            } elseif (!empty($raw)) {
                $phuongThuoc = array_values(array_filter(array_map('trim', explode('|', $raw))));
            }
        }

        $huyetVi = $pattern['huyet_vi_arr'] ?? [];
        if (empty($huyetVi)) {
            $raw = $pattern['huyet_vi'] ?? '';
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $huyetVi = $decoded;
            } elseif (!empty($raw)) {
                $huyetVi = array_values(array_filter(array_map('trim', explode(',', $raw))));
            }
        }

        return [
            'principle'       => $pattern['phap_tri_vi'] ?? $pattern['phap_tri_code'] ?? 'Tham khảo bác sĩ YHCT',
            'principle_en'    => $pattern['phap_tri_code'] ?? null,
            'phuong_thuoc'    => $phuongThuoc,
            'huyet_vi'        => $huyetVi,
            'life_advice'     => $pattern['life_advice_vi'] ?? '',
            'clinical_note'   => $pattern['clinical_note'] ?? '',
            'key_questions'   => $pattern['key_questions_arr'] ?? [],
            'follow_up_timing'=> $pattern['follow_up_timing'] ?? null,
        ];
    }

    /**
     * Build Western medicine (YHHD) result section from matched patterns and symptoms
     *
     * @param array $patternMatches   Ranked pattern matches from matchPatterns()
     * @param array $selectedCodes    Selected symptom codes
     * @return array YHHD result with correlates, notes, differential
     */
    private function buildYhhdResult(array $patternMatches, array $selectedCodes): array
    {
        // Collect YHHD correlations from top 3 matched patterns
        $correlates = [];
        $differential = [];
        foreach (array_slice($patternMatches, 0, 3) as $idx => $match) {
            $p    = $match['pattern'];
            $yhhdArr = $p['yhhd_arr'] ?? [];
            foreach ($yhhdArr as $corr) {
                if (!in_array($corr, $correlates)) {
                    $correlates[] = $corr;
                }
            }
            if ($idx > 0) {
                $differential[] = [
                    'pattern_vi' => $p['name_vi'] ?? $p['chung_code'],
                    'score'      => round($match['score'], 2),
                    'yhhd'       => array_slice($yhhdArr, 0, 3),
                ];
            }
        }

        // Collect YHHD clinical notes from selected symptoms
        $symptomNotes = [];
        foreach ($selectedCodes as $code) {
            $sym = $this->k02Symptoms[$code] ?? null;
            if ($sym && !empty($sym['yhhd_clinical_note'])) {
                $symptomNotes[] = [
                    'symptom'   => $sym['name_vi'] ?? $code,
                    'yhhd_note' => $sym['yhhd_clinical_note'],
                ];
            }
        }

        // Build red flag Western medicine interpretations
        $redFlagWestern = [];
        foreach ($this->k05RedFlags as $rule) {
            if (!empty(array_intersect($selectedCodes, $rule['symptom_codes_arr'] ?? []))) {
                $redFlagWestern[] = [
                    'name'   => $rule['name_vi'] ?? $rule['rule_code'],
                    'level'  => $rule['level']   ?? 'L3_watch',
                    'action' => $rule['action_vi'] ?? $rule['description'] ?? '',
                ];
            }
        }

        return [
            'correlates'      => array_slice($correlates, 0, 10),
            'differential'    => $differential,
            'symptom_notes'   => array_slice($symptomNotes, 0, 5),
            'red_flag_western'=> $redFlagWestern,
            'disclaimer'      => 'Các chẩn đoán Tây y chỉ mang tính gợi ý dựa trên tương quan lâm sàng. '
                               . 'Cần xét nghiệm và khám Tây y để xác nhận.',
        ];
    }

    /**
     * Build drug warnings from quick answers (drug class mentions)
     */
    private function buildDrugWarnings(array $quickAnswers): array
    {
        $warnings = [];
        $meds = $quickAnswers['medications'] ?? '';
        if (empty($meds)) {
            return $warnings;
        }

        // Simple keyword matching for common drug classes
        $drugMap = [
            'warfarin'   => ['class' => 'anticoagulant', 'warning' => 'Nhiều vị thuốc YHCT tương tác với warfarin (tăng/giảm INR)'],
            'aspirin'    => ['class' => 'nsaid',          'warning' => 'Một số thảo dược có thể tăng nguy cơ chảy máu khi dùng cùng aspirin'],
            'metformin'  => ['class' => 'diabetes',       'warning' => 'Một số thảo dược hạ đường huyết cần thận trọng khi dùng cùng metformin'],
            'digoxin'    => ['class' => 'cardiac',        'warning' => 'Cây thảo dược ảnh hưởng tim mạch cần thận trọng khi dùng cùng digoxin'],
            'huyết áp'   => ['class' => 'antihypertensive', 'warning' => 'Một số thảo dược YHCT có thể ảnh hưởng huyết áp'],
            'kháng sinh' => ['class' => 'antibiotic',     'warning' => 'Kháng sinh và một số thảo dược có thể tương tác'],
        ];

        $medsLower = mb_strtolower($meds, 'UTF-8');
        foreach ($drugMap as $keyword => $info) {
            if (mb_strpos($medsLower, $keyword, 0, 'UTF-8') !== false) {
                $warnings[] = $info;
            }
        }

        return $warnings;
    }

    /**
     * Run K08 kiêm chứng cross-validation
     */
    private function runKiemChung(array $patternMatches): array
    {
        if (count($patternMatches) < 2) {
            return [];
        }

        $result = [];
        $topCode    = $patternMatches[0]['pattern']['pattern_code'] ?? null;
        $secondCode = $patternMatches[1]['pattern']['pattern_code'] ?? null;

        if (!$topCode || !$secondCode) {
            return [];
        }

        try {
            $db  = Database::get();
            $row = $db->prepare(
                "SELECT * FROM kb_kiem_chung
                 WHERE (primary_code = ? AND secondary_code = ?)
                    OR (primary_code = ? AND secondary_code = ?)
                 LIMIT 1"
            );
            $row->execute([$topCode, $secondCode, $secondCode, $topCode]);
            $kc = $row->fetch(PDO::FETCH_ASSOC);
            if ($kc) {
                $result[] = $kc;
            }
        } catch (\Exception $e) {
            // Graceful degradation
        }

        return $result;
    }

    /**
     * Build follow-up advice based on primary pattern
     */
    private function buildFollowUp(array|null $primaryPattern, array $allMatches): array
    {
        $advice = [];

        if ($primaryPattern) {
            $tang = $primaryPattern['primary_tang'] ?? '';
            $advice[] = 'Nếu sau 7 ngày áp dụng pháp trị mà triệu chứng không thuyên giảm, hãy tái khám.';

            if ($tang === 'can') {
                $advice[] = 'Nên giảm stress, ngủ đủ giấc (23h-1h là giờ Can vận hành).';
                $advice[] = 'Tránh rượu bia, đồ ăn cay nóng.';
            } elseif ($tang === 'than') {
                $advice[] = 'Nên giữ ấm vùng thắt lưng, tránh quan hệ quá độ.';
                $advice[] = 'Ăn thực phẩm bổ Thận như hạt mè đen, quả óc chó.';
            } elseif ($tang === 'ty') {
                $advice[] = 'Ăn uống điều độ, tránh đồ ngọt béo ngậy.';
                $advice[] = 'Không ăn quá no hoặc quá đói; ăn đúng giờ.';
            } elseif ($tang === 'phe') {
                $advice[] = 'Giữ ấm vùng ngực, cổ khi thời tiết thay đổi.';
                $advice[] = 'Tránh môi trường có khói bụi, lạnh ẩm.';
            } elseif ($tang === 'tam') {
                $advice[] = 'Giữ tâm thần thoải mái, hạn chế lo âu thái quá.';
                $advice[] = 'Thực hành thiền hoặc yoga nhẹ nhàng.';
            }
        }

        if (count($allMatches) > 3) {
            $advice[] = 'Chứng phức tạp - nên gặp thầy thuốc YHCT để biện chứng trực tiếp.';
        }

        return $advice;
    }

    /**
     * Apply context from quick answers to the result
     */
    private function applyQuickAnswerContext(array $result, array $quickAnswers, array $contextFlags): array
    {
        // If medications mentioned and drug_warnings empty, try to build from drug_mentions
        if (empty($result['drug_warnings']) && !empty($quickAnswers['medications'])) {
            $result['drug_warnings'] = $this->buildDrugWarnings($quickAnswers);
        }

        // Suppress YHCT for L1 emergencies
        if ($result['triage_level'] === 'L1_emergency') {
            $result['yhct_suppressed'] = true;
            $result['yhct_disclaimer'] = 'KHẨN CẤP: Đây là tình trạng cấp cứu. '
                . 'Vui lòng gọi 115 hoặc đến cơ sở y tế ngay lập tức. '
                . 'Không áp dụng thuốc YHCT khi chưa có đánh giá y tế.';
        }

        // Suppress YHCT for L2 flags that explicitly require it (suppress_yhct_output = true)
        if (!$result['yhct_suppressed'] && !empty($result['red_flags'])) {
            foreach ($result['red_flags'] as $flag) {
                if (!empty($flag['suppress_yhct_output'])) {
                    $result['yhct_suppressed'] = true;
                    $result['yhct_disclaimer'] = '⚠️ Tình trạng này cần được đánh giá bởi bác sĩ trước khi áp dụng thuốc YHCT. '
                        . 'Vui lòng khám bác sĩ trong vòng 24 giờ.';
                    break;
                }
            }
        }

        return $result;
    }

    // =========================================================================
    // PUBLIC ACCESSORS
    // =========================================================================

    /**
     * Get all loaded K02 symptoms
     */
    public function getSymptoms(): array
    {
        return $this->k02Symptoms;
    }

    /**
     * Get all loaded K04 patterns
     */
    public function getPatterns(): array
    {
        return $this->k04Patterns;
    }

    /**
     * Get co-occurrence matrix (build if not yet built)
     */
    public function getCoOccurrenceMatrix(): array
    {
        if (!$this->matrixBuilt) {
            $this->buildCoOccurrenceMatrix();
        }
        return $this->coOccurrenceMatrix;
    }
}
