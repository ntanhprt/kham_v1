<?php
/**
 * Seed K01 Observable Phrases
 *
 * Maps colloquial Vietnamese patient expressions → standardized symptom codes (K02)
 * Each entry covers common ways a patient might describe a symptom.
 */

$db = new PDO('sqlite:' . __DIR__ . '/../app/storage/kham.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('PRAGMA journal_mode=WAL');

// Clear existing
$db->exec("DELETE FROM kb_observable_phrases");
echo "Cleared kb_observable_phrases\n";

// phrase_id, phrase_vi, variants_vi, linked_symptom_codes, organ_hint
// Code mapping: K01 codes → actual K02 symptom_code values
// K02 uses: throbbing_headache, heavy_dull_headache, dizziness_vertigo, tinnitus,
//   irritability_emotional_lability, right_hypochondrial_pain, bitter_taste_mouth,
//   dry_mouth_throat, red_eyes, night_sweats, cold_extremities, hot_palms_soles,
//   sighing_dyspnea, depression_mood, lower_back_pain, weak_knees,
//   frequent_urination_night, poor_memory, premature_aging, tinnitus_low,
//   edema_lower_limbs, spermatorrhea_impotence, poor_appetite_loss, bloating_fullness,
//   loose_stools, fatigue_general, pale_complexion, nausea_vomiting, epigastric_pain,
//   constipation_hard_stools, palpitations_irregular, insomnia, anxiety_worry,
//   dream_disturbed_sleep, poor_memory, chest_pain_stabbing, cough_dry, cough_with_phlegm,
//   shortness_of_breath, sore_throat, nasal_congestion, high_fever, facial_droop_weakness

$phrases = [

    // =========================================================================
    // CAN (Gan/Liver) system
    // =========================================================================
    ['p_head_throb',        'đau đầu nhịp đập',         'đau nửa đầu,đau đầu như búa bổ,nhức đầu giật giật,đầu thốn,nhức một bên đầu,đau đầu,nhức đầu,đầu đau',  'throbbing_headache',                                  'can'],
    ['p_head_heavy',        'đau đầu nặng nề',           'đầu nặng,đầu ong ong,đau âm ỉ trong đầu,đầu căng tức',                                                  'heavy_dull_headache',                                 'can'],
    ['p_dizzy',             'chóng mặt',                 'hoa mắt,xây xẩm mặt mày,quay đầu,chóng mặt tiền đình,mắt mờ khi đứng dậy,đứng dậy thấy tối sầm',       'dizziness_vertigo',                                   'can'],
    ['p_tinnitus',          'ù tai',                     'tai kêu,tiếng kêu trong tai,ve kêu trong tai,tai ù,nghe tiếng ve,tiếng vù trong tai',                   'tinnitus',                                            'can'],
    ['p_irritable',         'hay cáu giận',              'dễ nổi nóng,tính tình nóng nảy,hay cáu kỉnh,dễ bực bội,nóng tính,cáu gắt vô cớ,tức giận dễ dàng',     'irritability_emotional_lability',                     'can'],
    ['p_rib_pain',          'đau hông sườn',             'đau mạng sườn,đau vùng gan,đau bên phải bụng trên,tức ngực sườn,căng tức sườn phải',                    'right_hypochondrial_pain',                            'can'],
    ['p_bitter',            'miệng đắng',                'đắng miệng,miệng đắng khi ngủ dậy,vị đắng trong miệng,miệng đắng buổi sáng',                            'bitter_taste_mouth',                                  'can'],
    ['p_dry_mouth',         'khô miệng',                 'khô họng,miệng khô,họng khô,khát nước,uống nước không hết khô,miệng họng khô khát',                     'dry_mouth_throat',                                    'can'],
    ['p_red_eyes',          'mắt đỏ',                    'mắt đỏ sung,mắt sung húp,mắt nóng rát,mắt đỏ ngầu,mắt cộm,mắt khó chịu',                               'red_eyes',                                            'can'],
    ['p_night_sweat',       'mồ hôi trộm',               'đổ mồ hôi ban đêm,toát mồ hôi lúc ngủ,mồ hôi đêm,ra mồ hôi ban đêm dù không nóng',                    'night_sweats',                                        'can'],
    ['p_cold_limbs',        'tay chân lạnh',             'chân tay lạnh ngắt,lạnh tay,tứ chi lạnh,tay chân lạnh dù trời không lạnh,bàn tay bàn chân lạnh',       'cold_extremities',                                    'can'],
    ['p_hot_palms',         'lòng bàn tay chân nóng',   'bàn chân nóng,nóng trong lòng bàn tay,gan bàn tay nóng,năm tâm phiền nhiệt,bàn tay bàn chân nóng hừng','hot_palms_soles',                                     'can'],
    ['p_sighing',           'hay thở dài',               'thở dài thườn thượt,thở nặng nề,hay thở dài não ruột,khó thở dài,thở dài tức ngực',                    'sighing_dyspnea',                                     'can'],
    ['p_depressed',         'tâm trạng trầm uất',        'buồn bã,trầm cảm,uể oải,chán nản,không muốn làm gì,tâm trạng xấu kéo dài,tinh thần suy sụp',          'depression_mood',                                     'can'],

    // =========================================================================
    // THAN (Thận/Kidney) system — using actual K02 codes
    // =========================================================================
    ['p_back_pain',         'đau lưng dưới',             'đau thắt lưng,mỏi lưng,đau lưng,đau hông lưng,lưng đau âm ỉ,thắt lưng đau mỏi',                        'lower_back_pain',                                     'than'],
    ['p_knee_weak',         'đầu gối mỏi',               'khớp gối đau,đầu gối yếu,gối mỏi,đầu gối lỏng lẻo,gối đau khi leo cầu thang',                          'weak_knees',                                          'than'],
    ['p_frequent_urine',    'tiểu nhiều lần',            'đi tiểu đêm,tiểu đêm nhiều,đái nhiều,tiểu lắt nhắt,đi tiểu đêm nhiều lần',                              'frequent_urination_night',                            'than'],
    ['p_poor_memory',       'hay quên',                  'trí nhớ kém,quên nhiều,đãng trí,hay nhầm lẫn,trí nhớ suy giảm,không nhớ được',                          'poor_memory',                                         'than'],
    ['p_hair_loss',         'rụng tóc',                  'tóc rụng nhiều,tóc thưa dần,tóc dễ gãy,tóc không chắc,rụng tóc nhiều hơn bình thường,tóc bạc sớm',     'premature_aging',                                     'than'],
    ['p_tinnitus_low',      'ù tai tiếng thấp',          'ù tai tiếng thấp liên tục,tai ù âm thanh thấp,ù tai kiểu than âm',                                       'tinnitus_low',                                        'than'],
    ['p_edema',             'phù chân',                  'phù nề,chân phù,mu bàn chân phù,phù chân buổi chiều,sưng phù chân',                                      'edema_lower_limbs',                                   'than'],
    ['p_impotence',         'yếu sinh lý',               'liệt dương,sinh lý yếu,xuất tinh sớm,yếu chức năng tình dục,rối loạn cương dương,mộng tinh',            'spermatorrhea_impotence',                             'than'],

    // =========================================================================
    // TY VI (Tỳ Vị / Spleen-Stomach) system — using actual K02 codes
    // =========================================================================
    ['p_poor_appetite',     'ăn không ngon',             'chán ăn,kém ăn,ăn ít,ăn không thấy ngon,không muốn ăn,mất cảm giác ngon miệng,biếng ăn',               'poor_appetite_loss',                                  'ty_vi'],
    ['p_bloating',          'bụng đầy trướng',           'đầy hơi,trướng bụng,bụng trướng,bụng căng,ăn vào đầy bụng ngay,bụng phình to sau ăn',                   'bloating_fullness',                                   'ty_vi'],
    ['p_loose_stool',       'phân lỏng',                 'tiêu chảy,đi cầu lỏng,phân nát,đại tiện lỏng,đi ngoài nhiều lần,bụng lỏng',                             'loose_stools',                                        'ty_vi'],
    ['p_fatigue',           'mệt mỏi',                   'uể oải,lười biếng,không có sức,kiệt sức,mệt không rõ nguyên nhân,người mệt mỏi toàn thân',              'fatigue_general',                                     'ty_vi'],
    ['p_pale_face',         'mặt xanh xao',              'da mặt nhợt nhạt,da vàng,xanh xao,mặt trắng bệch,da không hồng hào,thiếu máu mặt',                      'pale_complexion',                                     'ty_vi'],
    ['p_nausea',            'buồn nôn',                  'nôn nao,muốn nôn,buồn nôn khi ăn,hay nôn,ói mửa,nôn sau khi ăn',                                        'nausea_vomiting',                                     'ty_vi'],
    ['p_stomach_pain',      'đau dạ dày',                'đau vùng thượng vị,đau bụng trên,đau dạ dày khi đói,đau thượng vị,bụng trên đau,đau dạ dày sau ăn',    'epigastric_pain',                                     'ty_vi'],
    ['p_constipation',      'táo bón',                   'đại tiện khó,đi cầu ít,phân cứng,khó đi cầu,mấy ngày không đi cầu,bón',                                 'constipation_hard_stools',                            'ty_vi'],
    ['p_belching',          'ợ hơi',                     'hay ợ,ợ chua,ợ nóng,ợ hơi nhiều,trào ngược,acid trào lên,ợ chua sau ăn,ợ hơi ợ chua',                   'bloating_fullness',                                   'ty_vi'],

    // =========================================================================
    // TAM (Tâm / Heart) system — using actual K02 codes
    // =========================================================================
    ['p_palpitation',       'hồi hộp',                   'tim đập nhanh,trống ngực,tim đập mạnh,đánh trống ngực,tim đập loạn nhịp,tim hồi hộp',                   'palpitations_irregular',                              'tam'],
    ['p_insomnia',          'mất ngủ',                   'khó ngủ,ngủ không sâu,trằn trọc,ngủ hay giật mình,ngủ ít,thức giấc giữa đêm,ngủ không ngon',            'insomnia',                                            'tam'],
    ['p_anxiety',           'lo lắng',                   'hay lo âu,hồi hộp lo lắng,căng thẳng,dễ sợ hãi,lo nghĩ nhiều,bất an trong lòng,lo lắng thái quá',       'anxiety_worry',                                       'tam'],
    ['p_dream',             'hay mơ',                    'ngủ nhiều mộng,mộng mị,hay chiêm bao,ngủ hay nằm mơ,giấc ngủ không yên giấc',                           'dream_disturbed_sleep',                               'tam'],
    ['p_forgetful',         'hay quên việc',             'mau quên,đãng trí,không tập trung,mất tập trung,trí nhớ ngắn hạn kém',                                   'poor_memory',                                         'tam'],
    ['p_chest_pain',        'đau ngực',                  'đau tức ngực,ngực đau nhói,đau ngực bên trái,nặng ngực,tức ngực',                                        'chest_pain_stabbing',                                 'tam'],

    // =========================================================================
    // PHE (Phế / Lung) system — using actual K02 codes
    // =========================================================================
    ['p_cough_dry',         'ho khan',                   'ho không có đờm,ho khan liên tục,ho khô họng,ho khan về đêm',                                            'cough_dry',                                           'phe'],
    ['p_cough_phlegm',      'ho có đàm',                 'ho khạc đàm,ho ra đàm,ho nhiều đàm,ho đờm,ho có đờm xanh vàng',                                         'cough_with_phlegm',                                   'phe'],
    ['p_breath_short',      'khó thở',                   'hụt hơi,thở nông,thở không sâu được,thở gấp,khó thở khi gắng sức,hơi thở yếu',                         'shortness_of_breath',                                 'phe'],
    ['p_sore_throat',       'đau họng',                  'họng đau,viêm họng,cổ họng đau,nuốt đau,họng sưng đỏ,cổ họng rát',                                       'sore_throat',                                         'phe'],
    ['p_nasal',             'chảy nước mũi',             'ngạt mũi,sổ mũi,nghẹt mũi,nước mũi chảy,mũi tắc,viêm mũi,mũi chảy dịch',                               'nasal_congestion',                                    'phe'],
    ['p_fever',             'sốt',                       'bị sốt,sốt cao,sốt nhẹ,cơ thể nóng,thân nhiệt cao,nóng sốt,sốt bất thường',                             'high_fever',                                          'phe'],

    // =========================================================================
    // Symptom combinations — using actual K02 codes
    // =========================================================================
    ['p_head_dizzy',        'đau đầu chóng mặt',         'nhức đầu hoa mắt,đau đầu kèm chóng mặt,đầu đau mắt mờ',                                                 'throbbing_headache,dizziness_vertigo',                'can'],
    ['p_tired_back',        'mệt mỏi đau lưng',          'người mệt kèm đau lưng,mỏi lưng mệt mỏi',                                                                'fatigue_general,lower_back_pain',                     'than'],
    ['p_sleep_heart',       'mất ngủ hồi hộp',           'khó ngủ tim đập nhanh,mất ngủ lo lắng,ngủ không được vì hồi hộp',                                        'insomnia,palpitations_irregular',                     'tam'],
    ['p_digest_full',       'đầy bụng chán ăn',          'ăn không ngon bụng trướng,bụng đầy không ăn được',                                                        'poor_appetite_loss,bloating_fullness',                'ty_vi'],
    ['p_cold_fatigue',      'lạnh tay chân mệt',         'tay chân lạnh người mệt mỏi,lạnh và mệt mỏi',                                                             'cold_extremities,fatigue_general',                    'can'],
    ['p_back_urine',        'đau lưng tiểu đêm',         'đau thắt lưng đi tiểu đêm,lưng đau kèm tiểu nhiều đêm',                                                   'lower_back_pain,frequent_urination_night',             'than'],
    ['p_bitter_irritable',  'miệng đắng cáu giận',       'đắng miệng hay cáu,tức giận miệng đắng',                                                                  'bitter_taste_mouth,irritability_emotional_lability',  'can'],
    ['p_cough_breath',      'ho khó thở',                'ho kèm khó thở,khó thở có ho,ho và thở không được',                                                       'cough_with_phlegm,shortness_of_breath',               'phe'],
    ['p_insomnia_dizzy',    'mất ngủ chóng mặt',         'khó ngủ chóng mặt,chóng mặt không ngủ được',                                                              'insomnia,dizziness_vertigo',                          'can'],

    // =========================================================================
    // Common patient chief complaints (full sentence patterns)
    // =========================================================================
    ['p_cc_headache',       'tôi bị đau đầu',            'đau đầu mấy ngày nay,đau đầu không dứt,đầu tôi đau lắm',                                                 'throbbing_headache',                                  'can'],
    ['p_cc_cant_sleep',     'tôi không ngủ được',        'tôi bị mất ngủ,đêm nào cũng trằn trọc,ngủ rất khó,khó vào giấc ngủ',                                     'insomnia',                                            'tam'],
    ['p_cc_stomachache',    'tôi đau bụng',              'đau bụng trên,bụng tôi hay đau,hay đau bụng sau ăn',                                                       'epigastric_pain',                                     'ty_vi'],
    ['p_cc_back',           'tôi đau lưng',              'lưng tôi đau mãi,lưng đau không khỏi,đau lưng từ lâu',                                                    'lower_back_pain',                                     'than'],
    ['p_cc_tired',          'tôi hay mệt',               'tôi mệt mỏi hoài,người lúc nào cũng uể oải,hay cảm thấy kiệt sức',                                        'fatigue_general',                                     'ty_vi'],
    ['p_cc_breath',         'tôi khó thở',               'tôi thở không được,hay hụt hơi,thở không đủ hơi',                                                         'shortness_of_breath',                                 'phe'],
    ['p_cc_palpitation',    'tim tôi đập nhanh',         'tim hay hồi hộp,tim đập mạnh bất thường,trống ngực khó chịu',                                             'palpitations_irregular',                              'tam'],
    ['p_cc_dizzy',          'tôi hay chóng mặt',         'chóng mặt thường xuyên,hay xây xẩm,đứng lên là chóng mặt',                                                'dizziness_vertigo',                                   'can'],
    ['p_cc_cough',          'tôi hay ho',                'ho hoài không dứt,ho mãn tính,ho kéo dài nhiều tuần',                                                     'cough_dry',                                           'phe'],
    ['p_cc_knee',           'gối tôi đau',               'đầu gối đau,gối yếu đau khi đi,khớp gối hay đau',                                                         'weak_knees',                                          'than'],
];

$stmt = $db->prepare("
    INSERT INTO kb_observable_phrases
        (phrase_id, phrase_vi, variants_vi, linked_symptom_codes, organ_hint, status, created_at)
    VALUES
        (:pid, :pvi, :var, :codes, :organ, 'active', datetime('now'))
");

$count = 0;
foreach ($phrases as $p) {
    $stmt->execute([
        ':pid'   => $p[0],
        ':pvi'   => $p[1],
        ':var'   => $p[2],
        ':codes' => $p[3],
        ':organ' => $p[4],
    ]);
    $count++;
}

echo "Seeded {$count} K01 observable phrases\n";

// Also add more symptom aliases to improve matching
// Additional aliases using actual K02 codes
$additionalAliases = [
    // Tỳ Vị symptoms
    ['poor_appetite_loss',    'không muốn ăn',      'colloquial'],
    ['poor_appetite_loss',    'ăn kém',             'colloquial'],
    ['poor_appetite_loss',    'biếng ăn',           'colloquial'],
    ['poor_appetite_loss',    'chán ăn',            'colloquial'],
    ['bloating_fullness',     'đầy hơi',            'colloquial'],
    ['bloating_fullness',     'trướng bụng',        'colloquial'],
    ['bloating_fullness',     'bụng căng',          'colloquial'],
    ['bloating_fullness',     'ợ chua',             'colloquial'],
    ['bloating_fullness',     'trào ngược',         'colloquial'],
    ['loose_stools',          'tiêu chảy',          'colloquial'],
    ['loose_stools',          'phân lỏng',          'colloquial'],
    ['nausea_vomiting',       'buồn nôn',           'colloquial'],
    ['nausea_vomiting',       'muốn nôn',           'colloquial'],
    ['nausea_vomiting',       'ói mửa',             'colloquial'],
    ['epigastric_pain',       'đau thượng vị',      'formal'],
    ['epigastric_pain',       'đau dạ dày',         'colloquial'],
    ['constipation_hard_stools','táo bón',          'colloquial'],
    ['constipation_hard_stools','khó đi cầu',       'colloquial'],
    ['constipation_hard_stools','phân cứng',        'colloquial'],
    ['fatigue_general',       'uể oải',             'colloquial'],
    ['fatigue_general',       'không có sức',       'colloquial'],
    ['pale_complexion',       'xanh xao',           'colloquial'],
    ['pale_complexion',       'da nhợt',            'colloquial'],
    // Tâm symptoms
    ['palpitations_irregular','tim hồi hộp',        'colloquial'],
    ['palpitations_irregular','trống ngực',         'colloquial'],
    ['palpitations_irregular','tim đập nhanh',      'colloquial'],
    ['palpitations_irregular','đánh trống ngực',    'colloquial'],
    ['anxiety_worry',         'lo âu',              'colloquial'],
    ['anxiety_worry',         'hay sợ',             'colloquial'],
    ['anxiety_worry',         'bất an',             'colloquial'],
    ['dream_disturbed_sleep', 'mộng mị',            'colloquial'],
    ['dream_disturbed_sleep', 'nhiều mộng',         'colloquial'],
    ['dream_disturbed_sleep', 'ngủ hay giật mình',  'colloquial'],
    ['chest_pain_stabbing',   'đau tức ngực',       'colloquial'],
    ['chest_pain_stabbing',   'nặng ngực',          'colloquial'],
    // Phế symptoms
    ['cough_dry',             'ho khạc',            'colloquial'],
    ['cough_dry',             'ho khan',            'colloquial'],
    ['cough_with_phlegm',     'ho có đàm',          'colloquial'],
    ['cough_with_phlegm',     'đàm nhiều',          'colloquial'],
    ['cough_with_phlegm',     'đờm đặc',            'colloquial'],
    ['shortness_of_breath',   'hụt hơi',            'colloquial'],
    ['sore_throat',           'họng sưng đau',      'colloquial'],
    ['nasal_congestion',      'sổ mũi',             'colloquial'],
    ['nasal_congestion',      'nghẹt mũi',          'colloquial'],
    ['nasal_congestion',      'chảy nước mũi',      'colloquial'],
    // Thận symptoms
    ['weak_knees',            'gối mỏi',            'colloquial'],
    ['weak_knees',            'đầu gối yếu',        'colloquial'],
    ['weak_knees',            'gối đau',            'colloquial'],
    ['frequent_urination_night','tiểu đêm',         'colloquial'],
    ['frequent_urination_night','đái đêm',          'colloquial'],
    ['frequent_urination_night','tiểu lắt nhắt',    'colloquial'],
    ['poor_memory',           'hay quên',           'colloquial'],
    ['poor_memory',           'trí nhớ kém',        'colloquial'],
    ['poor_memory',           'đãng trí',           'colloquial'],
    ['premature_aging',       'tóc rụng',           'colloquial'],
    ['premature_aging',       'tóc bạc sớm',        'colloquial'],
    ['premature_aging',       'lão hóa sớm',        'colloquial'],
    ['edema_lower_limbs',     'phù chân',           'colloquial'],
    ['edema_lower_limbs',     'sưng chân',          'colloquial'],
    ['spermatorrhea_impotence','yếu sinh lý',       'colloquial'],
    ['spermatorrhea_impotence','liệt dương',        'colloquial'],
    // Can symptoms
    ['high_fever',            'sốt cao',            'colloquial'],
    ['high_fever',            'nóng sốt',           'colloquial'],
    ['cold_lower_body',       'sợ lạnh',            'colloquial'],
    ['cold_lower_body',       'ớn lạnh',            'colloquial'],
    ['cold_lower_body',       'lạnh chân',          'colloquial'],
];

// Only add aliases for symptoms that exist in kb_symptoms
$existingSymptoms = $db->query("SELECT symptom_code FROM kb_symptoms")->fetchAll(PDO::FETCH_COLUMN);
$existingSet = array_flip($existingSymptoms);

$aliasStmt = $db->prepare("
    INSERT OR IGNORE INTO symptom_aliases (symptom_code, alias, alias_type)
    SELECT :code, :alias, :type
    WHERE NOT EXISTS (
        SELECT 1 FROM symptom_aliases WHERE symptom_code=:code AND alias=:alias
    )
");

$aliasCount = 0;
foreach ($additionalAliases as $a) {
    if (isset($existingSet[$a[0]])) {
        $aliasStmt->execute([':code' => $a[0], ':alias' => $a[1], ':type' => $a[2]]);
        if ($aliasStmt->rowCount()) $aliasCount++;
    }
}
echo "Added {$aliasCount} additional aliases\n";

// Summary
$total = $db->query("SELECT COUNT(*) FROM kb_observable_phrases")->fetchColumn();
$totalAlias = $db->query("SELECT COUNT(*) FROM symptom_aliases")->fetchColumn();
echo "Total K01 phrases: {$total}\n";
echo "Total aliases: {$totalAlias}\n";
echo "Done!\n";
