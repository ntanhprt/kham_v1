<?php
/**
 * Safety Filter - Final output safety check
 *
 * Applies herb-drug interactions, pregnancy filters,
 * pediatric filters, and K08 combined treatment validation.
 *
 * This runs AFTER the main diagnosis engine and modifies the result
 * to add warnings or suppress unsafe recommendations.
 */
class SafetyFilter
{
    // K07 herb-drug interaction rules
    private array $k07Rules = [];

    // Special context rules (pregnancy, children, elderly)
    private array $specialRules = [];

    // K08 combined treatment validation rules
    private array $k08Rules = [];

    // Severity levels for interactions
    private const SEVERITY_HIGH     = 'high';
    private const SEVERITY_MEDIUM   = 'medium';
    private const SEVERITY_LOW      = 'low';

    // Known contraindicated herb codes for pregnancy (fallback if DB empty)
    private const PREGNANCY_CONTRA_HERBS = [
        'HB_001', 'HB_002', 'HB_003', // Placeholder codes
        // Add real herb codes: Ngải cứu (Artemisia), Đại hoàng, Tam lăng, Nga truật
    ];

    /**
     * Constructor - loads K07 and K08 rules from database
     */
    public function __construct()
    {
        $this->loadK07Rules();
        $this->loadK08Rules();
    }

    /**
     * Load K07 herb-drug interaction rules
     */
    private function loadK07Rules(): void
    {
        try {
            $db   = Database::get();
            $rows = $db->query("SELECT * FROM kb_herb_drug ORDER BY severity DESC, id ASC")
                       ->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                // Support both old column names (herb_code) and new (herb_codes)
                $rawHerbs = $row['herb_codes'] ?? $row['herb_code'] ?? '';
                $row['herb_codes_arr'] = !empty($rawHerbs)
                    ? array_map('trim', explode(',', $rawHerbs))
                    : [];
                $rawDrugs = $row['drug_classes'] ?? $row['drug_class'] ?? '';
                $row['drug_classes_arr'] = !empty($rawDrugs)
                    ? array_map('trim', explode(',', $rawDrugs))
                    : [];
                // Normalize description/warning fields
                $row['description'] = $row['message_to_patient'] ?? $row['mechanism_vi'] ?? $row['description'] ?? '';
                $row['warning']     = $row['message_to_patient'] ?? $row['warning'] ?? '';
                $row['action']      = $row['message_to_doctor']  ?? $row['action']  ?? '';
                $row['herb_code']   = $rawHerbs;
                $row['drug_class']  = $rawDrugs;

                $context = $row['context'] ?? 'general';

                if ($context === 'pregnancy' || $context === 'children' || $context === 'elderly') {
                    $this->specialRules[$context][] = $row;
                } else {
                    $this->k07Rules[] = $row;
                }
            }
        } catch (\Exception $e) {
            $this->k07Rules   = [];
            $this->specialRules = [];
        }
    }

    /**
     * Load K08 combined treatment validation rules
     */
    private function loadK08Rules(): void
    {
        try {
            $db   = Database::get();
            $rows = $db->query("SELECT * FROM kb_kiem_chung ORDER BY confidence DESC")
                       ->fetchAll(PDO::FETCH_ASSOC);
            $this->k08Rules = $rows;
        } catch (\Exception $e) {
            $this->k08Rules = [];
        }
    }

    /**
     * Apply all safety filters to the engine result
     *
     * Modifies the result array in place:
     *   - Adds drug_warnings
     *   - May suppress certain phap_tri items
     *   - Adds pregnancy/pediatric notes
     *   - Applies K08 kiem_chung
     *
     * @param array $result      Engine result from YHCTEngine::buildResult()
     * @param array $sessionData Full session data
     * @return array Modified result
     */
    public function filter(array $result, array $sessionData): array
    {
        $quickAnswers  = $sessionData['quick_answers']  ?? [];
        $contextFlags  = $sessionData['context_flags']  ?? [];
        $patternMatches = $result['chung_ranked']        ?? [];

        // 1. Extract phap_tri herb codes from result
        $phapTriCodes  = $this->extractPhapTriCodes($result);

        // 2. Extract drug classes from quick answers
        $drugClasses   = $this->extractDrugClasses($quickAnswers);

        // 3. Herb-drug interaction check
        $herbDrugWarnings = $this->checkHerbDrug($phapTriCodes, $drugClasses);
        if (!empty($herbDrugWarnings)) {
            $existing = $result['drug_warnings'] ?? [];
            $result['drug_warnings'] = array_merge($existing, $herbDrugWarnings);
        }

        // 4. Pregnancy check
        $isPregnant = !empty($contextFlags['is_pregnant'])
                   || (!empty($quickAnswers['pregnancy']) && $quickAnswers['pregnancy'] !== 'no');
        if ($isPregnant) {
            $result = $this->applyPregnancyFilter($result, $phapTriCodes);
        }

        // 5. Pediatric check
        $isChild = !empty($contextFlags['is_child'])
                || (!empty($quickAnswers['age']) && (int)$quickAnswers['age'] < 12);
        if ($isChild) {
            $result = $this->applyPediatricFilter($result, $phapTriCodes, $quickAnswers);
        }

        // 6. Elderly check
        $isElderly = !empty($contextFlags['is_elderly'])
                  || (!empty($quickAnswers['age']) && (int)$quickAnswers['age'] >= 65);
        if ($isElderly) {
            $result = $this->applyElderlyFilter($result, $phapTriCodes);
        }

        // 7. K08 kiêm chứng application
        if (!empty($patternMatches)) {
            $k08Result = $this->applyK08KiemChung($patternMatches);
            if (!empty($k08Result)) {
                $result['kiem_chung'] = array_merge($result['kiem_chung'] ?? [], $k08Result);
            }
        }

        // 8. Deduplicate drug_warnings by class
        $result['drug_warnings'] = $this->deduplicateWarnings($result['drug_warnings'] ?? []);

        return $result;
    }

    /**
     * Check herb-drug interactions
     *
     * @param array $phapTriCodes  Herb/formula codes from pháp trị
     * @param array $drugClasses   Drug class names from quick answers
     * @return array Warning records
     */
    public function checkHerbDrug(array $phapTriCodes, array $drugClasses): array
    {
        if (empty($phapTriCodes) || empty($drugClasses)) {
            return [];
        }

        $warnings = [];

        foreach ($this->k07Rules as $rule) {
            // Check herb code overlap
            $herbMatch = !empty(array_intersect($phapTriCodes, $rule['herb_codes_arr']));

            // Check drug class overlap
            $drugMatch = false;
            foreach ($drugClasses as $dc) {
                foreach ($rule['drug_classes_arr'] as $rdc) {
                    if (stripos($dc, $rdc) !== false || stripos($rdc, $dc) !== false) {
                        $drugMatch = true;
                        break 2;
                    }
                }
            }

            if (!$herbMatch && !$drugMatch) {
                continue;
            }

            // Both herb AND drug need to match for a valid interaction
            // If only one side matches, it's a partial warning
            if ($herbMatch && $drugMatch) {
                $warnings[] = [
                    'type'        => 'herb_drug_interaction',
                    'severity'    => $rule['severity']    ?? self::SEVERITY_MEDIUM,
                    'herb_code'   => $rule['herb_code']   ?? '',
                    'drug_class'  => $rule['drug_class']  ?? '',
                    'warning'     => $rule['description'] ?? $rule['warning'] ?? 'Tương tác tiềm năng với thuốc tây.',
                    'action'      => $rule['action']      ?? 'Tham khảo bác sĩ trước khi dùng.',
                    'reference'   => $rule['reference']   ?? '',
                ];
            } elseif ($drugMatch) {
                // Drug class mentioned but no specific herb match - general warning
                $warnings[] = [
                    'type'       => 'drug_class_caution',
                    'severity'   => self::SEVERITY_LOW,
                    'drug_class' => $rule['drug_class'] ?? '',
                    'warning'    => 'Đang dùng ' . ($rule['drug_class'] ?? 'thuốc') . ': tham khảo bác sĩ trước khi dùng thuốc YHCT.',
                    'action'     => 'Thông báo cho bác sĩ điều trị về kế hoạch dùng thuốc YHCT.',
                ];
            }
        }

        return $warnings;
    }

    /**
     * Filter contraindicated herbs for pregnancy
     *
     * @param array $result       Full result array
     * @param array $phapTriCodes Herb/formula codes
     * @return array Modified result
     */
    public function checkPregnancy(array $phapTriCodes, bool $isPregnant): array
    {
        if (!$isPregnant) {
            return [];
        }

        $warnings = [];

        // Check against K07 pregnancy special rules
        $pregnancyRules = $this->specialRules['pregnancy'] ?? [];
        foreach ($pregnancyRules as $rule) {
            $herbMatch = !empty(array_intersect($phapTriCodes, $rule['herb_codes_arr']));
            if ($herbMatch || $rule['herb_code'] === '*') {
                $warnings[] = [
                    'type'     => 'pregnancy_contraindication',
                    'severity' => $rule['severity'] ?? self::SEVERITY_HIGH,
                    'warning'  => $rule['description'] ?? 'Không dùng thuốc này khi mang thai.',
                    'action'   => $rule['action'] ?? 'Tham khảo bác sĩ sản khoa.',
                    'herbs'    => $rule['herb_code'] ?? '',
                ];
            }
        }

        // Fallback hardcoded check
        if (empty($warnings)) {
            $contra = array_intersect($phapTriCodes, self::PREGNANCY_CONTRA_HERBS);
            if (!empty($contra)) {
                $warnings[] = [
                    'type'     => 'pregnancy_contraindication',
                    'severity' => self::SEVERITY_HIGH,
                    'warning'  => 'Một số thuốc trong phác đồ có thể không an toàn cho phụ nữ mang thai.',
                    'action'   => 'Vui lòng tham khảo bác sĩ sản khoa và thầy thuốc YHCT.',
                    'herbs'    => implode(', ', $contra),
                ];
            }
        }

        return $warnings;
    }

    /**
     * Apply pregnancy filter to result
     */
    private function applyPregnancyFilter(array $result, array $phapTriCodes): array
    {
        $warnings = $this->checkPregnancy($phapTriCodes, true);

        // Always add a general pregnancy note
        $warnings[] = [
            'type'     => 'pregnancy_note',
            'severity' => self::SEVERITY_MEDIUM,
            'warning'  => '⚠️ Phụ nữ mang thai: cần thận trọng đặc biệt khi dùng thuốc YHCT.',
            'action'   => 'Hỏi ý kiến bác sĩ sản khoa hoặc thầy thuốc YHCT có kinh nghiệm về thai kỳ.',
        ];

        $existing                = $result['drug_warnings'] ?? [];
        $result['drug_warnings'] = array_merge($existing, $warnings);
        $result['pregnancy_flag'] = true;

        return $result;
    }

    /**
     * Apply pediatric filter to result
     */
    private function applyPediatricFilter(array $result, array $phapTriCodes, array $quickAnswers): array
    {
        $age = (int)($quickAnswers['age'] ?? 0);

        $pediatricRules = $this->specialRules['children'] ?? [];
        $warnings       = [];

        foreach ($pediatricRules as $rule) {
            $herbMatch = !empty(array_intersect($phapTriCodes, $rule['herb_codes_arr']));
            if ($herbMatch || $rule['herb_code'] === '*') {
                $warnings[] = [
                    'type'     => 'pediatric_caution',
                    'severity' => $rule['severity'] ?? self::SEVERITY_MEDIUM,
                    'warning'  => $rule['description'] ?? 'Thận trọng khi dùng cho trẻ em.',
                    'action'   => $rule['action']      ?? 'Điều chỉnh liều theo cân nặng của trẻ.',
                ];
            }
        }

        // General pediatric warning
        $warnings[] = [
            'type'     => 'pediatric_note',
            'severity' => self::SEVERITY_MEDIUM,
            'warning'  => '⚠️ Trẻ em: liều lượng thuốc YHCT cần được điều chỉnh theo tuổi và cân nặng.',
            'action'   => 'Tham khảo bác sĩ Nhi hoặc thầy thuốc YHCT chuyên về nhi khoa.',
        ];

        if ($age < 6) {
            $warnings[] = [
                'type'     => 'pediatric_high_risk',
                'severity' => self::SEVERITY_HIGH,
                'warning'  => '⚠️ Trẻ dưới 6 tuổi: rất nhiều thuốc YHCT chưa được nghiên cứu ở nhóm tuổi này.',
                'action'   => 'Không tự ý dùng thuốc YHCT cho trẻ dưới 6 tuổi mà không có hướng dẫn của thầy thuốc.',
            ];
        }

        $existing                = $result['drug_warnings'] ?? [];
        $result['drug_warnings'] = array_merge($existing, $warnings);
        $result['pediatric_flag'] = true;

        return $result;
    }

    /**
     * Apply elderly filter to result
     */
    private function applyElderlyFilter(array $result, array $phapTriCodes): array
    {
        $elderlyRules = $this->specialRules['elderly'] ?? [];
        $warnings     = [];

        foreach ($elderlyRules as $rule) {
            $herbMatch = !empty(array_intersect($phapTriCodes, $rule['herb_codes_arr']));
            if ($herbMatch || $rule['herb_code'] === '*') {
                $warnings[] = [
                    'type'     => 'elderly_caution',
                    'severity' => $rule['severity'] ?? self::SEVERITY_MEDIUM,
                    'warning'  => $rule['description'] ?? 'Thận trọng ở người cao tuổi.',
                    'action'   => $rule['action']      ?? 'Bắt đầu với liều thấp hơn bình thường.',
                ];
            }
        }

        $warnings[] = [
            'type'     => 'elderly_note',
            'severity' => self::SEVERITY_LOW,
            'warning'  => 'ℹ️ Người cao tuổi: chức năng gan thận có thể suy giảm, ảnh hưởng chuyển hóa thuốc.',
            'action'   => 'Theo dõi chặt chẽ và tái khám thường xuyên.',
        ];

        $existing                = $result['drug_warnings'] ?? [];
        $result['drug_warnings'] = array_merge($existing, $warnings);

        return $result;
    }

    /**
     * Apply K08 kiêm chứng (combined treatment) validation
     *
     * Checks if primary + secondary patterns have documented combined treatment
     *
     * @param array $patternMatches Pattern match results from engine
     * @return array K08 validation records
     */
    public function applyK08KiemChung(array $patternMatches): array
    {
        if (empty($patternMatches) || empty($this->k08Rules)) {
            return [];
        }

        $result     = [];
        $topCode    = $patternMatches[0]['pattern']['pattern_code'] ?? null;

        foreach (array_slice($patternMatches, 1, 3) as $match) {
            $secondCode = $match['pattern']['pattern_code'] ?? null;
            if (!$secondCode || !$topCode) {
                continue;
            }

            foreach ($this->k08Rules as $rule) {
                // Support both old column names and new schema
                $pCode = $rule['primary_pattern']   ?? $rule['primary_code']   ?? '';
                $sCode = $rule['secondary_pattern']  ?? $rule['secondary_code'] ?? '';

                if (($pCode === $topCode && $sCode === $secondCode)
                 || ($pCode === $secondCode && $sCode === $topCode)) {
                    $result[] = [
                        'primary_code'   => $pCode,
                        'secondary_code' => $sCode,
                        'compatibility'  => $rule['compatibility']  ?? 'compatible',
                        'combined_note'  => $rule['phap_tri_combined_vi'] ?? $rule['clinical_note'] ?? $rule['combined_note'] ?? '',
                        'confidence'     => $rule['confidence']      ?? 0.5,
                    ];
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Extract herb/formula codes from the phap_tri section of a result
     */
    private function extractPhapTriCodes(array $result): array
    {
        $codes    = [];
        $phapTri  = $result['phap_tri'] ?? [];

        if (is_array($phapTri)) {
            // If phap_tri has a 'herb_codes' or 'formula_codes' key
            if (!empty($phapTri['herb_codes'])) {
                $codes = array_merge($codes, (array)$phapTri['herb_codes']);
            }
            if (!empty($phapTri['formula_codes'])) {
                $codes = array_merge($codes, (array)$phapTri['formula_codes']);
            }
        }

        // Also extract from primary pattern
        $primary = $result['primary_pattern'] ?? [];
        if (!empty($primary['herb_codes'])) {
            $codes = array_merge($codes, array_map('trim', explode(',', $primary['herb_codes'])));
        }

        return array_unique(array_filter($codes));
    }

    /**
     * Extract drug classes from quick answers
     */
    private function extractDrugClasses(array $quickAnswers): array
    {
        $classes = [];
        $meds    = $quickAnswers['medications'] ?? '';

        if (empty($meds)) {
            return $classes;
        }

        $lower = mb_strtolower($meds, 'UTF-8');

        // Map of keywords to drug classes
        $drugMap = [
            'warfarin'         => 'anticoagulant',
            'heparin'          => 'anticoagulant',
            'aspirin'          => 'nsaid',
            'ibuprofen'        => 'nsaid',
            'naproxen'         => 'nsaid',
            'paracetamol'      => 'analgesic',
            'acetaminophen'    => 'analgesic',
            'metformin'        => 'antidiabetic',
            'insulin'          => 'antidiabetic',
            'đái tháo đường'   => 'antidiabetic',
            'tiểu đường'       => 'antidiabetic',
            'huyết áp'         => 'antihypertensive',
            'amlodipine'       => 'antihypertensive',
            'lisinopril'       => 'antihypertensive',
            'atorvastatin'     => 'statin',
            'simvastatin'      => 'statin',
            'omeprazole'       => 'proton_pump_inhibitor',
            'pantoprazole'     => 'proton_pump_inhibitor',
            'levothyroxine'    => 'thyroid',
            'digoxin'          => 'cardiac_glycoside',
            'phenytoin'        => 'anticonvulsant',
            'carbamazepine'    => 'anticonvulsant',
            'kháng sinh'       => 'antibiotic',
            'amoxicillin'      => 'antibiotic',
            'ciprofloxacin'    => 'antibiotic',
            'fluoxetine'       => 'antidepressant',
            'sertraline'       => 'antidepressant',
            'trầm cảm'         => 'antidepressant',
            'cyclosporine'     => 'immunosuppressant',
            'tacrolimus'       => 'immunosuppressant',
        ];

        foreach ($drugMap as $keyword => $class) {
            if (mb_strpos($lower, $keyword, 0, 'UTF-8') !== false) {
                $classes[] = $class;
            }
        }

        return array_unique($classes);
    }

    /**
     * Deduplicate warnings by (type + drug_class/herb_code) key
     */
    private function deduplicateWarnings(array $warnings): array
    {
        $seen   = [];
        $result = [];
        foreach ($warnings as $w) {
            $key = ($w['type'] ?? '') . '|' . ($w['drug_class'] ?? '') . '|' . ($w['herb_code'] ?? '');
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $result[]   = $w;
            }
        }
        return $result;
    }

    /**
     * Get severity badge HTML class for display
     */
    public static function getSeverityClass(string $severity): string
    {
        return match ($severity) {
            'high'   => 'danger',
            'medium' => 'warning',
            'low'    => 'info',
            default  => 'secondary',
        };
    }
}
