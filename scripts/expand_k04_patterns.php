<?php
/**
 * Expand K04 Patterns - Add 10 clinically important YHCT patterns
 * Uses only symptoms already in K02 (kb_symptoms)
 *
 * Run: php scripts/expand_k04_patterns.php
 */

define('APP_ROOT', __DIR__ . '/../app');
require APP_ROOT . '/config.php';
require APP_ROOT . '/core/Database.php';

$db = Database::get();
$db->exec('PRAGMA journal_mode=WAL');

function j(array $v): string {
    return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

$newPatterns = [

    // =========================================================================
    // 1. Kidney Essence Deficiency (Thận Tinh Hư)
    // =========================================================================
    [
        'chung_code'   => 'kidney_essence_deficiency',
        'name_vi'      => 'Thận tinh hư',
        'name_en'      => 'Kidney Essence Deficiency',
        'organ_system' => 'than',
        'required_symptoms'                 => j(['lower_back_pain', 'poor_memory']),
        'two_or_more_of'                    => j(['premature_aging', 'weak_knees', 'tinnitus_low', 'spermatorrhea_impotence']),
        'supporting_symptoms'               => j(['frequent_urination_night', 'fatigue_general', 'dizziness_vertigo']),
        'differentiating_symptoms_positive' => j(['premature_aging', 'poor_memory', 'tinnitus_low']),
        'differentiating_symptoms_negative' => j(['cold_extremities', 'edema_lower_limbs']),
        'differentiates_from'               => j(['kidney_yin_deficiency', 'kidney_yang_deficiency']),
        'phap_tri_code' => 'bo_than_ich_tinh',
        'phap_tri_vi'   => 'Bổ thận ích tinh, tư dưỡng tinh huyết',
        'phuong_thuoc'  => j(['Lục vị địa hoàng thang (Liu Wei Di Huang Wan)', 'Tả quy thang (Zuo Gui Wan)', 'Hà thủ ô thang bổ thận']),
        'huyet_vi'      => j(['Thận Du BL-23', 'Thái Khê KI-3', 'Tam Âm Giao SP-6', 'Chí Thất BL-52', 'Huyền Chung GB-39']),
        'life_advice_vi'  => 'Nghỉ ngơi đầy đủ, ăn thực phẩm bổ thận như óc heo, hạt đen, quả thận. Tránh quan hệ tình dục quá độ.',
        'clinical_note'   => 'Thận tinh hư là dạng suy thận sâu nhất, ảnh hưởng đến sinh trưởng, phát dục và sinh sản. Phân biệt với thận âm hư (có nhiệt tướng).',
        'yhhd_correlates' => 'Suy giảm trí nhớ, lão hóa sớm, vô sinh.',
        'key_questions'   => j([
            ['question_vi' => 'Bạn có hay quên và trí nhớ suy giảm không?', 'symptom_code' => 'poor_memory', 'importance' => 'high'],
            ['question_vi' => 'Bạn có tóc bạc sớm hoặc rụng tóc nhiều không?', 'symptom_code' => 'premature_aging', 'importance' => 'medium'],
        ]),
        'prevalence_vn' => 'medium',
        'status'        => 'active',
    ],

    // =========================================================================
    // 2. Heart Yin Deficiency (Tâm Âm Hư)
    // =========================================================================
    [
        'chung_code'   => 'heart_yin_deficiency',
        'name_vi'      => 'Tâm âm hư',
        'name_en'      => 'Heart Yin Deficiency',
        'organ_system' => 'tam',
        'required_symptoms'                 => j(['insomnia', 'palpitations_irregular']),
        'two_or_more_of'                    => j(['night_sweats', 'hot_palms_soles', 'anxiety_worry', 'dry_mouth_throat']),
        'supporting_symptoms'               => j(['dream_disturbed_sleep', 'poor_memory', 'dizziness_vertigo']),
        'differentiating_symptoms_positive' => j(['insomnia', 'night_sweats', 'hot_palms_soles']),
        'differentiating_symptoms_negative' => j(['pale_complexion', 'cold_extremities', 'fatigue_general']),
        'differentiates_from'               => j(['heart_blood_deficiency', 'heart_fire_excess']),
        'phap_tri_code' => 'tu_am_duong_tam',
        'phap_tri_vi'   => 'Tư âm dưỡng tâm, an thần thanh nhiệt',
        'phuong_thuoc'  => j(['Thiên vương bổ tâm đan (Tian Wang Bu Xin Dan)', 'Bá tử dưỡng tâm thang', 'Toan táo nhân thang (Suan Zao Ren Tang)']),
        'huyet_vi'      => j(['Thần Môn HT-7', 'Âm Khích HT-6', 'Tam Âm Giao SP-6', 'Thái Khê KI-3', 'Nội Quan PC-6']),
        'life_advice_vi'  => 'Giảm căng thẳng, thiền định hàng ngày. Ăn thức ăn bổ âm như bách hợp, ngân nhĩ, hà thủ ô.',
        'clinical_note'   => 'Tâm âm hư là dạng âm hư có liên quan đến tim, phân biệt với tâm huyết hư (không có nhiệt tướng) và tâm hỏa vượng (nhiệt tướng nặng hơn).',
        'yhhd_correlates' => 'Rối loạn thần kinh thực vật, loạn nhịp tim chức năng, lo âu.',
        'key_questions'   => j([
            ['question_vi' => 'Bạn có đổ mồ hôi trộm về đêm không?', 'symptom_code' => 'night_sweats', 'importance' => 'high'],
            ['question_vi' => 'Lòng bàn tay chân có cảm giác nóng không?', 'symptom_code' => 'hot_palms_soles', 'importance' => 'high'],
        ]),
        'prevalence_vn' => 'medium',
        'status'        => 'active',
    ],

    // =========================================================================
    // 3. Lung Yin Deficiency (Phế Âm Hư)
    // =========================================================================
    [
        'chung_code'   => 'lung_yin_deficiency',
        'name_vi'      => 'Phế âm hư',
        'name_en'      => 'Lung Yin Deficiency',
        'organ_system' => 'phe',
        'required_symptoms'                 => j(['cough_dry', 'dry_mouth_throat']),
        'two_or_more_of'                    => j(['night_sweats', 'hot_palms_soles', 'shortness_of_breath', 'hoarse_voice']),
        'supporting_symptoms'               => j(['fatigue_general', 'pale_complexion', 'sore_throat']),
        'differentiating_symptoms_positive' => j(['cough_dry', 'dry_mouth_throat', 'night_sweats']),
        'differentiating_symptoms_negative' => j(['cough_with_phlegm', 'nasal_congestion', 'high_fever']),
        'differentiates_from'               => j(['lung_wind_cold', 'lung_phlegm_damp', 'lung_qi_deficiency']),
        'phap_tri_code' => 'tu_am_run_phe',
        'phap_tri_vi'   => 'Tư âm nhuận phế, thanh nhiệt chỉ khái',
        'phuong_thuoc'  => j(['Bách hợp cố kim thang (Bai He Gu Jin Tang)', 'Sa sâm mạch đông thang', 'Dưỡng âm thanh phế thang']),
        'huyet_vi'      => j(['Phế Du BL-13', 'Liệt Khuyết LU-7', 'Thái Uyên LU-9', 'Chiếu Hải KI-6', 'Tam Âm Giao SP-6']),
        'life_advice_vi'  => 'Tránh khói thuốc, không khí ô nhiễm. Ăn lê hấp mật ong, ngân nhĩ, thịt vịt.',
        'clinical_note'   => 'Phế âm hư thường gặp sau bệnh phổi mãn tính, lao phổi. Ho khan về chiều tối, ít đờm hoặc đờm có máu.',
        'yhhd_correlates' => 'Lao phổi, viêm phổi mãn tính, khô họng mãn tính.',
        'key_questions'   => j([
            ['question_vi' => 'Bạn có ho khan không có đờm không?', 'symptom_code' => 'cough_dry', 'importance' => 'high'],
            ['question_vi' => 'Miệng và họng có khô không?', 'symptom_code' => 'dry_mouth_throat', 'importance' => 'high'],
        ]),
        'prevalence_vn' => 'medium',
        'status'        => 'active',
    ],

    // =========================================================================
    // 4. Wind-Heat Exterior Syndrome (Phong Nhiệt Biểu Chứng)
    // =========================================================================
    [
        'chung_code'   => 'wind_heat_exterior',
        'name_vi'      => 'Phong nhiệt biểu chứng',
        'name_en'      => 'Wind-Heat Exterior Syndrome',
        'organ_system' => 'phe',
        'required_symptoms'                 => j(['high_fever', 'sore_throat']),
        'two_or_more_of'                    => j(['cough_with_phlegm', 'nasal_congestion', 'dry_mouth_throat', 'red_eyes']),
        'supporting_symptoms'               => j(['irritability_emotional_lability', 'headache']),
        'differentiating_symptoms_positive' => j(['high_fever', 'sore_throat', 'dry_mouth_throat']),
        'differentiating_symptoms_negative' => j(['cold_extremities', 'nasal_congestion']),
        'differentiates_from'               => j(['lung_wind_cold', 'lung_phlegm_damp']),
        'phap_tri_code' => 'so_phong_thanh_nhiet',
        'phap_tri_vi'   => 'Sơ phong thanh nhiệt, tuyên phế chỉ khái',
        'phuong_thuoc'  => j(['Ngân kiều tán (Yin Qiao San)', 'Tang cúc ẩm (Sang Ju Yin)', 'Ma hạnh thạch cam thang']),
        'huyet_vi'      => j(['Hợp Cốc LI-4', 'Ngoại Quan SJ-5', 'Phong Trì GB-20', 'Đại Chùy DU-14', 'Thiếu Thương LU-11']),
        'life_advice_vi'  => 'Uống nhiều nước, nghỉ ngơi, ăn thức ăn nhẹ. Tránh đồ cay nóng, có thể uống trà gừng sả nhẹ.',
        'clinical_note'   => 'Phong nhiệt là dạng cảm cúm do nhiệt, sốt nổi bật hơn ớn lạnh. Thường kèm đau họng rõ.',
        'yhhd_correlates' => 'Viêm họng, cúm, viêm amidan cấp.',
        'key_questions'   => j([
            ['question_vi' => 'Bạn có sốt cao và đau họng không?', 'symptom_code' => 'high_fever', 'importance' => 'high'],
            ['question_vi' => 'Họng có đỏ và đau nhiều không?', 'symptom_code' => 'sore_throat', 'importance' => 'high'],
        ]),
        'prevalence_vn' => 'high',
        'status'        => 'active',
    ],

    // =========================================================================
    // 5. Spleen-Kidney Yang Deficiency (Tỳ Thận Dương Hư)
    // =========================================================================
    [
        'chung_code'   => 'spleen_kidney_yang_deficiency',
        'name_vi'      => 'Tỳ thận dương hư',
        'name_en'      => 'Spleen-Kidney Yang Deficiency',
        'organ_system' => 'than',
        'required_symptoms'                 => j(['cold_extremities', 'fatigue_general']),
        'two_or_more_of'                    => j(['edema_lower_limbs', 'loose_stools', 'lower_back_pain', 'poor_appetite_loss']),
        'supporting_symptoms'               => j(['frequent_urination_night', 'cold_lower_body', 'pale_complexion']),
        'differentiating_symptoms_positive' => j(['cold_extremities', 'edema_lower_limbs', 'loose_stools']),
        'differentiating_symptoms_negative' => j(['night_sweats', 'hot_palms_soles', 'red_eyes']),
        'differentiates_from'               => j(['kidney_yang_deficiency', 'spleen_qi_deficiency']),
        'phap_tri_code' => 'wen_bo_ty_than',
        'phap_tri_vi'   => 'Ôn bổ tỳ thận, ích hỏa trợ dương',
        'phuong_thuoc'  => j(['Phụ tử lý trung thang', 'Thận khí thang (Shen Qi Wan)', 'Chân vũ thang (Zhen Wu Tang)']),
        'huyet_vi'      => j(['Thận Du BL-23', 'Tỳ Du BL-20', 'Quan Nguyên CV-4', 'Mệnh Môn DU-4', 'Tam Âm Giao SP-6']),
        'life_advice_vi'  => 'Ăn ấm, tránh lạnh. Thực phẩm bổ dương: nhung hươu, quế chi, gừng, hẹ, thịt dê. Không tắm nước lạnh.',
        'clinical_note'   => 'Tỳ thận dương hư là tình trạng dương hư nặng ảnh hưởng cả hai tạng, thường ở người cao tuổi hoặc bệnh mãn tính.',
        'yhhd_correlates' => 'Suy thận mãn, viêm đại tràng mãn, suy giáp.',
        'key_questions'   => j([
            ['question_vi' => 'Tay chân có lạnh và phù không?', 'symptom_code' => 'cold_extremities', 'importance' => 'high'],
            ['question_vi' => 'Phân có lỏng và mệt mỏi nhiều không?', 'symptom_code' => 'loose_stools', 'importance' => 'high'],
        ]),
        'prevalence_vn' => 'medium',
        'status'        => 'active',
    ],

    // =========================================================================
    // 6. Blood Deficiency (Huyết Hư)
    // =========================================================================
    [
        'chung_code'   => 'blood_deficiency',
        'name_vi'      => 'Huyết hư',
        'name_en'      => 'Blood Deficiency',
        'organ_system' => 'can',
        'required_symptoms'                 => j(['pale_complexion', 'fatigue_general']),
        'two_or_more_of'                    => j(['dizziness_vertigo', 'insomnia', 'poor_memory', 'palpitations_irregular']),
        'supporting_symptoms'               => j(['dry_mouth_throat', 'cold_extremities', 'depression_mood']),
        'differentiating_symptoms_positive' => j(['pale_complexion', 'dizziness_vertigo', 'poor_memory']),
        'differentiating_symptoms_negative' => j(['night_sweats', 'hot_palms_soles', 'red_eyes']),
        'differentiates_from'               => j(['liver_blood_deficiency', 'heart_blood_deficiency', 'qi_blood_deficiency']),
        'phap_tri_code' => 'bo_huyet_ich_khi',
        'phap_tri_vi'   => 'Bổ huyết ích khí, dưỡng tâm an thần',
        'phuong_thuoc'  => j(['Tứ vật thang (Si Wu Tang)', 'Đương quy bổ huyết thang', 'Nhân sâm dưỡng vinh thang']),
        'huyet_vi'      => j(['Huyết Hải SP-10', 'Cách Du BL-17', 'Tam Âm Giao SP-6', 'Túc Tam Lý ST-36', 'Khúc Trì LI-11']),
        'life_advice_vi'  => 'Ăn thực phẩm bổ huyết: đương quy, đỏ (quả táo đỏ, long nhãn), thịt bò, gan. Tránh thức khuya.',
        'clinical_note'   => 'Huyết hư tổng quát, thường do dinh dưỡng kém, mất máu, hoặc suy tỳ vị. Phân biệt với từng tạng cụ thể.',
        'yhhd_correlates' => 'Thiếu máu (anemia), suy dinh dưỡng, sau sinh.',
        'key_questions'   => j([
            ['question_vi' => 'Da mặt có nhợt nhạt, xanh xao không?', 'symptom_code' => 'pale_complexion', 'importance' => 'high'],
            ['question_vi' => 'Bạn có hay chóng mặt khi đứng dậy không?', 'symptom_code' => 'dizziness_vertigo', 'importance' => 'high'],
        ]),
        'prevalence_vn' => 'high',
        'status'        => 'active',
    ],

    // =========================================================================
    // 7. Phlegm Misting Heart Orifices (Đàm Mê Tâm Khiếu)
    // =========================================================================
    [
        'chung_code'   => 'phlegm_misting_heart',
        'name_vi'      => 'Đàm mê tâm khiếu',
        'name_en'      => 'Phlegm Misting Heart Orifices',
        'organ_system' => 'tam',
        'required_symptoms'                 => j(['insomnia', 'cough_with_phlegm']),
        'two_or_more_of'                    => j(['palpitations_irregular', 'nausea_vomiting', 'bloating_fullness', 'heavy_dull_headache']),
        'supporting_symptoms'               => j(['anxiety_worry', 'poor_memory', 'fatigue_general']),
        'differentiating_symptoms_positive' => j(['cough_with_phlegm', 'nausea_vomiting', 'heavy_dull_headache']),
        'differentiating_symptoms_negative' => j(['night_sweats', 'hot_palms_soles']),
        'differentiates_from'               => j(['heart_blood_deficiency', 'heart_yin_deficiency', 'lung_phlegm_damp']),
        'phap_tri_code' => 'hoa_dam_khai_khieu',
        'phap_tri_vi'   => 'Hóa đàm khai khiếu, an thần định trí',
        'phuong_thuoc'  => j(['Ôn đởm thang (Wen Dan Tang)', 'Hoàng liên ôn đởm thang', 'Định khiếu thang']),
        'huyet_vi'      => j(['Phong Long ST-40', 'Thần Môn HT-7', 'Nội Quan PC-6', 'Trung Quản CV-12', 'Bách Hội DU-20']),
        'life_advice_vi'  => 'Tránh thức ăn béo ngậy, đồ ngọt nhiều. Thiền định, tập thở để thanh thản tâm trí.',
        'clinical_note'   => 'Đàm mê tâm khiếu có thể biểu hiện từ mơ hồ tinh thần đến mê sảng. Thường gặp trong rối loạn tâm thần, động kinh.',
        'yhhd_correlates' => 'Rối loạn lo âu, trầm cảm kèm đầy bụng, rối loạn tâm thần.',
        'key_questions'   => j([
            ['question_vi' => 'Bạn có thường xuyên buồn nôn và có đờm nhiều không?', 'symptom_code' => 'nausea_vomiting', 'importance' => 'high'],
            ['question_vi' => 'Đầu có cảm giác nặng nề, mơ hồ không?', 'symptom_code' => 'heavy_dull_headache', 'importance' => 'medium'],
        ]),
        'prevalence_vn' => 'medium',
        'status'        => 'active',
    ],

    // =========================================================================
    // 8. Liver-Stomach Disharmony (Can Vị Bất Hòa)
    // =========================================================================
    [
        'chung_code'   => 'liver_stomach_disharmony',
        'name_vi'      => 'Can vị bất hòa',
        'name_en'      => 'Liver-Stomach Disharmony',
        'organ_system' => 'ty_vi',
        'required_symptoms'                 => j(['epigastric_pain', 'right_hypochondrial_pain']),
        'two_or_more_of'                    => j(['nausea_vomiting', 'bloating_fullness', 'irritability_emotional_lability', 'sighing_dyspnea']),
        'supporting_symptoms'               => j(['poor_appetite_loss', 'bitter_taste_mouth', 'depression_mood']),
        'differentiating_symptoms_positive' => j(['epigastric_pain', 'right_hypochondrial_pain', 'irritability_emotional_lability']),
        'differentiating_symptoms_negative' => j(['cold_extremities', 'loose_stools', 'pale_complexion']),
        'differentiates_from'               => j(['liver_qi_stagnation', 'stomach_cold_excess', 'spleen_qi_deficiency']),
        'phap_tri_code' => 'shu_can_he_vi',
        'phap_tri_vi'   => 'Sơ can hòa vị, giáng nghịch chỉ thống',
        'phuong_thuoc'  => j(['Tứ nghịch tán (Si Ni San)', 'Thư can hòa vị thang', 'Sài hồ sơ can thang']),
        'huyet_vi'      => j(['Thái Xung LR-3', 'Nội Quan PC-6', 'Túc Tam Lý ST-36', 'Trung Quản CV-12', 'Hợp Cốc LI-4']),
        'life_advice_vi'  => 'Tránh ăn khi quá căng thẳng. Thư giãn trước bữa ăn. Tránh đồ cay, chua. Tập thể dục nhẹ nhàng.',
        'clinical_note'   => 'Can vị bất hòa thường gặp ở người có stress ảnh hưởng đến tiêu hóa. Đau dạ dày tăng khi xúc động.',
        'yhhd_correlates' => 'Viêm loét dạ dày tá tràng do stress, hội chứng ruột kích thích.',
        'key_questions'   => j([
            ['question_vi' => 'Đau dạ dày có tăng lên khi bạn căng thẳng hoặc lo lắng không?', 'symptom_code' => 'epigastric_pain', 'importance' => 'high'],
            ['question_vi' => 'Bạn có hay thở dài hoặc đau vùng sườn không?', 'symptom_code' => 'right_hypochondrial_pain', 'importance' => 'high'],
        ]),
        'prevalence_vn' => 'high',
        'status'        => 'active',
    ],

    // =========================================================================
    // 9. Lung Qi Deficiency (Phế Khí Hư)
    // =========================================================================
    [
        'chung_code'   => 'lung_qi_deficiency',
        'name_vi'      => 'Phế khí hư',
        'name_en'      => 'Lung Qi Deficiency',
        'organ_system' => 'phe',
        'required_symptoms'                 => j(['shortness_of_breath', 'fatigue_general']),
        'two_or_more_of'                    => j(['cough_dry', 'nasal_congestion', 'pale_complexion', 'cold_extremities']),
        'supporting_symptoms'               => j(['anxiety_worry', 'poor_appetite_loss', 'sighing_dyspnea']),
        'differentiating_symptoms_positive' => j(['shortness_of_breath', 'fatigue_general', 'pale_complexion']),
        'differentiating_symptoms_negative' => j(['high_fever', 'night_sweats', 'cough_with_phlegm']),
        'differentiates_from'               => j(['lung_wind_cold', 'lung_yin_deficiency', 'qi_blood_deficiency']),
        'phap_tri_code' => 'bo_phe_ich_khi',
        'phap_tri_vi'   => 'Bổ phế ích khí, cố biểu chỉ hản',
        'phuong_thuoc'  => j(['Bổ phế thang (Bu Fei Tang)', 'Ngọc bình phong tán (Yu Ping Feng San)', 'Nhân sâm cáp giới tán']),
        'huyet_vi'      => j(['Phế Du BL-13', 'Thái Uyên LU-9', 'Túc Tam Lý ST-36', 'Khí Hải CV-6', 'Phong Môn BL-12']),
        'life_advice_vi'  => 'Tránh khí lạnh, gió. Tập thở khí công. Ăn các thức bổ phế như hạt thông, bạch quả (ginkgo), táo đỏ.',
        'clinical_note'   => 'Phế khí hư thường gặp ở người hay ốm vặt, dễ cảm lạnh. Đặc trưng là mệt mỏi + hụt hơi không có triệu chứng nhiệt.',
        'yhhd_correlates' => 'COPD nhẹ, hen suyễn ổn định, suy giảm miễn dịch.',
        'key_questions'   => j([
            ['question_vi' => 'Bạn có hay mệt mỏi và khó thở khi gắng sức không?', 'symptom_code' => 'shortness_of_breath', 'importance' => 'high'],
            ['question_vi' => 'Bạn có hay bị cảm lạnh hơn người bình thường không?', 'symptom_code' => 'nasal_congestion', 'importance' => 'medium'],
        ]),
        'prevalence_vn' => 'high',
        'status'        => 'active',
    ],

    // =========================================================================
    // 10. Damp-Heat in Spleen-Stomach (Tỳ Vị Thấp Nhiệt extended)
    // =========================================================================
    [
        'chung_code'   => 'liver_qi_blood_stasis',
        'name_vi'      => 'Can khí uất kiêm huyết ứ',
        'name_en'      => 'Liver Qi Stagnation with Blood Stasis',
        'organ_system' => 'can',
        'required_symptoms'                 => j(['right_hypochondrial_pain', 'depression_mood']),
        'two_or_more_of'                    => j(['sighing_dyspnea', 'irritability_emotional_lability', 'chest_pain_stabbing', 'night_sweats']),
        'supporting_symptoms'               => j(['bitter_taste_mouth', 'insomnia', 'poor_memory']),
        'differentiating_symptoms_positive' => j(['right_hypochondrial_pain', 'chest_pain_stabbing', 'depression_mood']),
        'differentiating_symptoms_negative' => j(['cold_extremities', 'edema_lower_limbs', 'loose_stools']),
        'differentiates_from'               => j(['liver_qi_stagnation', 'heart_blood_deficiency']),
        'phap_tri_code' => 'shu_can_hoa_huyet',
        'phap_tri_vi'   => 'Sơ can lý khí, hoạt huyết hóa ứ',
        'phuong_thuoc'  => j(['Huyết phủ trục ứ thang (Xue Fu Zhu Yu Tang)', 'Sài hồ sơ can thang gia giảm', 'Cách hạ trục ứ thang']),
        'huyet_vi'      => j(['Thái Xung LR-3', 'Huyết Hải SP-10', 'Cách Du BL-17', 'Nội Quan PC-6', 'Tam Âm Giao SP-6']),
        'life_advice_vi'  => 'Tập thể dục đều đặn để vận chuyển khí huyết. Tránh ngồi lâu. Ăn nghệ, hoa rum, đan sâm.',
        'clinical_note'   => 'Can khí uất kiêm huyết ứ thường do can khí uất lâu ngày gây ứ huyết. Đau cố định, đau sườn kèm uất ức.',
        'yhhd_correlates' => 'Hội chứng đau mạn tính, u xơ tử cung, gan nhiễm mỡ.',
        'key_questions'   => j([
            ['question_vi' => 'Đau vùng sườn hoặc ngực có tính chất cố định, nhói không?', 'symptom_code' => 'chest_pain_stabbing', 'importance' => 'high'],
            ['question_vi' => 'Bạn có cảm giác trầm uất, u ám trong người không?', 'symptom_code' => 'depression_mood', 'importance' => 'high'],
        ]),
        'prevalence_vn' => 'medium',
        'status'        => 'active',
    ],
];

// Insert patterns
$inserted = 0;
$skipped  = 0;

foreach ($newPatterns as $p) {
    // Check if already exists
    $exists = $db->prepare("SELECT COUNT(*) FROM kb_patterns WHERE chung_code = ?");
    $exists->execute([$p['chung_code']]);
    if ($exists->fetchColumn() > 0) {
        echo "SKIP (exists): {$p['chung_code']}\n";
        $skipped++;
        continue;
    }

    $cols = implode(', ', array_keys($p));
    $phs  = implode(', ', array_fill(0, count($p), '?'));
    $stmt = $db->prepare("INSERT INTO kb_patterns ($cols) VALUES ($phs)");
    $stmt->execute(array_values($p));
    echo "INSERT: {$p['chung_code']} ({$p['name_vi']})\n";
    $inserted++;
}

$total = $db->query("SELECT COUNT(*) FROM kb_patterns")->fetchColumn();
echo "\n=== Done: {$inserted} inserted, {$skipped} skipped, {$total} total patterns ===\n";
