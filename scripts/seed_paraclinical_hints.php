<?php
/**
 * Seed paraclinical_hints vào kb_patterns.
 *
 * Cấu trúc mỗi hint:
 * {
 *   "test_code":   string,   // unique key, used by engine
 *   "test_name_vi": string,  // tên hiển thị cho user
 *   "category":    string,   // blood | urine | imaging | other
 *   "category_vi": string,   // Xét nghiệm máu | Xét nghiệm nước tiểu | Chẩn đoán hình ảnh | Khác
 *   "relevance":   "confirms" | "excludes",
 *   "weight":      float,    // 0.10–0.30 (score boost/penalty multiplier)
 *   "abnormal_direction": "elevated"|"decreased"|"present"|"absent"|"any",
 *   "note_vi":     string    // giải thích lâm sàng
 * }
 */
define('APP_ROOT', __DIR__ . '/../app');
define('DB_PATH',  APP_ROOT . '/storage/kham.db');
$db = new PDO('sqlite:' . DB_PATH);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("PRAGMA journal_mode=WAL;");

// ────────────────────────────────────────────────────────────────────────────
// MASTER TEST CATALOGUE  (dùng chung, tham chiếu bởi nhiều pattern)
// ────────────────────────────────────────────────────────────────────────────
// Mỗi entry = một loại xét nghiệm có thể xuất hiện trong nhiều pattern
// Seed thực tế gán hint trực tiếp vào từng pattern

// ────────────────────────────────────────────────────────────────────────────
// MAP: chung_code => [hints]
// ────────────────────────────────────────────────────────────────────────────

$hintMap = [];

// ══════════════════════════════════════════════════════════════════
//  I. DA LIỄU (Skin)
// ══════════════════════════════════════════════════════════════════

$hintMap['blood_heat_wind_itch'] = [
    ['test_code'=>'cbc_eosinophil','test_name_vi'=>'Công thức máu — Bạch cầu ái toan (Eosinophil)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'elevated','note_vi'=>'Tăng eosinophil > 5% gợi ý dị ứng, phản ứng dị ứng thuốc, ký sinh trùng'],
    ['test_code'=>'ige_total','test_name_vi'=>'IgE toàn phần (Total IgE)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'elevated','note_vi'=>'IgE tăng cao trong mày đay dị ứng, viêm da atopic'],
    ['test_code'=>'crp','test_name_vi'=>'CRP (Protein phản ứng C)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.10,'abnormal_direction'=>'elevated','note_vi'=>'CRP tăng gợi ý viêm/nhiễm kèm theo'],
];

$hintMap['blood_deficiency_wind_dryness_itch'] = [
    ['test_code'=>'cbc_hemoglobin','test_name_vi'=>'Công thức máu — Huyết sắc tố (Hemoglobin)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.25,'abnormal_direction'=>'decreased','note_vi'=>'Hb thấp xác nhận thiếu máu, nguyên nhân gây ngứa mạn do huyết hư'],
    ['test_code'=>'ferritin','test_name_vi'=>'Ferritin (dự trữ sắt)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'decreased','note_vi'=>'Ferritin thấp = thiếu sắt, thường gặp trong ngứa mạn tính do huyết hư'],
    ['test_code'=>'tsh','test_name_vi'=>'TSH (Hormone kích thích tuyến giáp)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.15,'abnormal_direction'=>'elevated','note_vi'=>'TSH tăng = suy giáp gây khô da, ngứa mạn tính'],
    ['test_code'=>'cbc_eosinophil','test_name_vi'=>'Công thức máu — Bạch cầu ái toan (Eosinophil)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'excludes','weight'=>0.15,'abnormal_direction'=>'elevated','note_vi'=>'Eosinophil tăng nhiều gợi ý dị ứng/huyết nhiệt hơn là huyết hư'],
];

$hintMap['damp_heat_skin'] = [
    ['test_code'=>'skin_koh','test_name_vi'=>'Soi tươi nấm da (KOH test)','category'=>'other','category_vi'=>'Xét nghiệm vi sinh','relevance'=>'confirms','weight'=>0.30,'abnormal_direction'=>'present','note_vi'=>'Dương tính KOH xác nhận nấm da (hắc lào/tinea) — thấp nhiệt tích tụ'],
    ['test_code'=>'cbc_neutrophil','test_name_vi'=>'Công thức máu — Bạch cầu trung tính (Neutrophil)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.15,'abnormal_direction'=>'elevated','note_vi'=>'Tăng neutrophil gợi ý nhiễm khuẩn thứ phát trên nền eczema'],
    ['test_code'=>'glucose_fasting','test_name_vi'=>'Đường huyết lúc đói (Glucose)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.15,'abnormal_direction'=>'elevated','note_vi'=>'Đái tháo đường làm nặng thêm eczema/nhiễm nấm da'],
    ['test_code'=>'crp','test_name_vi'=>'CRP (Protein phản ứng C)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.10,'abnormal_direction'=>'elevated','note_vi'=>'CRP tăng gợi ý viêm nhiễm'],
];

$hintMap['spleen_deficiency_damp_skin'] = [
    ['test_code'=>'albumin','test_name_vi'=>'Albumin máu','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'decreased','note_vi'=>'Albumin thấp gợi ý suy dinh dưỡng, Tỳ hư không vận hóa'],
    ['test_code'=>'cbc_hemoglobin','test_name_vi'=>'Công thức máu — Huyết sắc tố (Hemoglobin)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.15,'abnormal_direction'=>'decreased','note_vi'=>'Thiếu máu nhẹ thường đi kèm Tỳ hư'],
    ['test_code'=>'stool_parasite','test_name_vi'=>'Soi phân tìm ký sinh trùng','category'=>'other','category_vi'=>'Xét nghiệm vi sinh','relevance'=>'confirms','weight'=>0.15,'abnormal_direction'=>'present','note_vi'=>'Ký sinh trùng đường ruột gây suy giảm hấp thu, liên quan Tỳ hư thấp trệ'],
    ['test_code'=>'glucose_fasting','test_name_vi'=>'Đường huyết lúc đói','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.10,'abnormal_direction'=>'elevated','note_vi'=>'Đái tháo đường type 2 thường có nền Tỳ hư'],
];

$hintMap['wind_damp_heat_skin'] = [
    ['test_code'=>'cbc_eosinophil','test_name_vi'=>'Công thức máu — Bạch cầu ái toan (Eosinophil)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'elevated','note_vi'=>'Tăng eosinophil xác nhận phản ứng dị ứng phong nhiệt'],
    ['test_code'=>'ige_total','test_name_vi'=>'IgE toàn phần (Total IgE)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'elevated','note_vi'=>'IgE tăng trong mày đay cấp tính do phong nhiệt'],
    ['test_code'=>'skin_koh','test_name_vi'=>'Soi tươi nấm da (KOH test)','category'=>'other','category_vi'=>'Xét nghiệm vi sinh','relevance'=>'confirms','weight'=>0.25,'abnormal_direction'=>'present','note_vi'=>'KOH dương tính xác nhận tinea pedis/corporis'],
];

// ══════════════════════════════════════════════════════════════════
//  II. GAN MẬT (Liver/Gallbladder)
// ══════════════════════════════════════════════════════════════════

$liver_hints = [
    ['test_code'=>'alt_ast','test_name_vi'=>'Men gan ALT / AST','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.25,'abnormal_direction'=>'elevated','note_vi'=>'ALT/AST tăng xác nhận tổn thương tế bào gan'],
    ['test_code'=>'bilirubin_total','test_name_vi'=>'Bilirubin toàn phần','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'elevated','note_vi'=>'Bilirubin tăng trong ứ mật, vàng da'],
    ['test_code'=>'hbsag','test_name_vi'=>'HBsAg (Viêm gan B)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'present','note_vi'=>'HBsAg dương tính xác nhận viêm gan B mạn'],
    ['test_code'=>'abdominal_ultrasound','test_name_vi'=>'Siêu âm bụng tổng quát','category'=>'imaging','category_vi'=>'Chẩn đoán hình ảnh','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'any','note_vi'=>'Phát hiện gan to, gan nhiễm mỡ, sỏi mật, dịch ổ bụng'],
    ['test_code'=>'albumin','test_name_vi'=>'Albumin máu','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.15,'abnormal_direction'=>'decreased','note_vi'=>'Albumin thấp trong suy gan, xơ gan'],
];

// Gán cho tất cả pattern có organ_system = liver/gallbladder
// Ta sẽ query và gán theo organ_system

// ══════════════════════════════════════════════════════════════════
//  III. THẬN — BÀNG QUANG (Kidney/Bladder)
// ══════════════════════════════════════════════════════════════════

$kidney_hints = [
    ['test_code'=>'creatinine','test_name_vi'=>'Creatinine máu','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.25,'abnormal_direction'=>'elevated','note_vi'=>'Creatinine tăng gợi ý suy thận, thận hư'],
    ['test_code'=>'bun','test_name_vi'=>'BUN (Ure máu)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'elevated','note_vi'=>'BUN tăng xác nhận suy giảm chức năng thận'],
    ['test_code'=>'uric_acid','test_name_vi'=>'Acid uric máu','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'elevated','note_vi'=>'Acid uric tăng trong Thận hư, phong thấp nhiệt (gout)'],
    ['test_code'=>'urinalysis','test_name_vi'=>'Tổng phân tích nước tiểu (Urinalysis)','category'=>'urine','category_vi'=>'Xét nghiệm nước tiểu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'any','note_vi'=>'Protein, hồng cầu, bạch cầu niệu gợi ý bệnh thận, nhiễm trùng tiết niệu'],
    ['test_code'=>'microalbumin_urine','test_name_vi'=>'Microalbumin niệu (24h)','category'=>'urine','category_vi'=>'Xét nghiệm nước tiểu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'elevated','note_vi'=>'Microalbumin niệu tăng = tổn thương cầu thận sớm'],
    ['test_code'=>'kidney_ultrasound','test_name_vi'=>'Siêu âm thận — tiết niệu','category'=>'imaging','category_vi'=>'Chẩn đoán hình ảnh','relevance'=>'confirms','weight'=>0.15,'abnormal_direction'=>'any','note_vi'=>'Phát hiện sỏi thận, thận ứ nước, nang thận, teo thận'],
];

// ══════════════════════════════════════════════════════════════════
//  IV. TIM (Heart)
// ══════════════════════════════════════════════════════════════════

$heart_hints = [
    ['test_code'=>'ecg','test_name_vi'=>'Điện tâm đồ (ECG 12 chuyển đạo)','category'=>'other','category_vi'=>'Thăm dò chức năng','relevance'=>'confirms','weight'=>0.25,'abnormal_direction'=>'any','note_vi'=>'Rối loạn nhịp, thiếu máu cơ tim, phì đại thất trái'],
    ['test_code'=>'echocardiography','test_name_vi'=>'Siêu âm tim (Echocardiography)','category'=>'imaging','category_vi'=>'Chẩn đoán hình ảnh','relevance'=>'confirms','weight'=>0.25,'abnormal_direction'=>'any','note_vi'=>'Đánh giá chức năng tim, van tim, áp lực buồng tim'],
    ['test_code'=>'lipid_panel','test_name_vi'=>'Mỡ máu toàn phần (Lipid panel: TC, LDL, HDL, TG)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.15,'abnormal_direction'=>'elevated','note_vi'=>'Rối loạn lipid máu là yếu tố nguy cơ tim mạch'],
    ['test_code'=>'chest_xray','test_name_vi'=>'Chụp X-quang ngực thẳng','category'=>'imaging','category_vi'=>'Chẩn đoán hình ảnh','relevance'=>'confirms','weight'=>0.15,'abnormal_direction'=>'any','note_vi'=>'Đánh giá bóng tim, phù phổi, tràn dịch màng tim'],
];

// ══════════════════════════════════════════════════════════════════
//  V. PHỔI (Lung)
// ══════════════════════════════════════════════════════════════════

$lung_hints = [
    ['test_code'=>'chest_xray','test_name_vi'=>'Chụp X-quang ngực thẳng','category'=>'imaging','category_vi'=>'Chẩn đoán hình ảnh','relevance'=>'confirms','weight'=>0.25,'abnormal_direction'=>'any','note_vi'=>'Phát hiện viêm phổi, lao phổi, tràn dịch màng phổi, u phổi'],
    ['test_code'=>'spirometry','test_name_vi'=>'Đo chức năng hô hấp (Spirometry)','category'=>'other','category_vi'=>'Thăm dò chức năng','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'any','note_vi'=>'FEV1/FVC giảm trong COPD, hen phế quản (phế khí thũng YHCT)'],
    ['test_code'=>'sputum_culture','test_name_vi'=>'Cấy đờm + kháng sinh đồ','category'=>'other','category_vi'=>'Xét nghiệm vi sinh','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'present','note_vi'=>'Dương tính xác nhận nhiễm khuẩn phổi'],
    ['test_code'=>'cbc_neutrophil','test_name_vi'=>'Công thức máu — Bạch cầu trung tính','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.15,'abnormal_direction'=>'elevated','note_vi'=>'Tăng bạch cầu gợi ý nhiễm khuẩn hô hấp'],
    ['test_code'=>'ct_chest','test_name_vi'=>'CT ngực (Chest CT)','category'=>'imaging','category_vi'=>'Chẩn đoán hình ảnh','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'any','note_vi'=>'Chi tiết hơn X-quang: phát hiện tổn thương nhỏ, u phổi sớm, lao'],
];

// ══════════════════════════════════════════════════════════════════
//  VI. KHỚP XƯƠNG (Joint/Bone)
// ══════════════════════════════════════════════════════════════════

$joint_hints = [
    ['test_code'=>'uric_acid','test_name_vi'=>'Acid uric máu','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.25,'abnormal_direction'=>'elevated','note_vi'=>'Tăng acid uric trong gout (phong thấp nhiệt tích khớp)'],
    ['test_code'=>'esr','test_name_vi'=>'Tốc độ lắng hồng cầu (ESR / VS)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'elevated','note_vi'=>'ESR tăng trong viêm khớp dạng thấp, lupus, viêm khớp nhiễm khuẩn'],
    ['test_code'=>'crp','test_name_vi'=>'CRP (Protein phản ứng C)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'elevated','note_vi'=>'CRP tăng xác nhận viêm khớp cấp'],
    ['test_code'=>'rf_factor','test_name_vi'=>'Yếu tố dạng thấp (RF — Rheumatoid Factor)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'present','note_vi'=>'RF dương tính gợi ý viêm khớp dạng thấp (phong hàn thấp tý)'],
    ['test_code'=>'joint_xray','test_name_vi'=>'Chụp X-quang khớp','category'=>'imaging','category_vi'=>'Chẩn đoán hình ảnh','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'any','note_vi'=>'Phát hiện hẹp khe khớp, thoái hóa, bào mòn xương, gai xương'],
    ['test_code'=>'bone_density','test_name_vi'=>'Đo mật độ xương (DEXA Scan)','category'=>'other','category_vi'=>'Thăm dò chức năng','relevance'=>'confirms','weight'=>0.15,'abnormal_direction'=>'decreased','note_vi'=>'Loãng xương trong Thận hư, Tỳ hư mạn tính'],
];

// ══════════════════════════════════════════════════════════════════
//  VII. TIÊU HÓA (GI / Digestive)
// ══════════════════════════════════════════════════════════════════

$gi_hints = [
    ['test_code'=>'abdominal_ultrasound','test_name_vi'=>'Siêu âm bụng tổng quát','category'=>'imaging','category_vi'=>'Chẩn đoán hình ảnh','relevance'=>'confirms','weight'=>0.25,'abnormal_direction'=>'any','note_vi'=>'Phát hiện sỏi mật, gan nhiễm mỡ, polyp, nang, dịch ổ bụng'],
    ['test_code'=>'endoscopy_upper','test_name_vi'=>'Nội soi dạ dày — thực quản (ESOGD)','category'=>'other','category_vi'=>'Thăm dò chức năng','relevance'=>'confirms','weight'=>0.25,'abnormal_direction'=>'any','note_vi'=>'Viêm loét dạ dày, trào ngược, HP — liên quan Vị nhiệt, Can khí phạm Vị'],
    ['test_code'=>'h_pylori','test_name_vi'=>'Xét nghiệm HP (Helicobacter pylori)','category'=>'other','category_vi'=>'Xét nghiệm vi sinh','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'present','note_vi'=>'HP dương tính trong loét dạ dày — Vị nhiệt, ứ huyết'],
    ['test_code'=>'stool_parasite','test_name_vi'=>'Soi phân tìm ký sinh trùng + vi khuẩn','category'=>'other','category_vi'=>'Xét nghiệm vi sinh','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'present','note_vi'=>'Ký sinh trùng gây rối loạn tiêu hóa, Tỳ hư thấp'],
    ['test_code'=>'alt_ast','test_name_vi'=>'Men gan ALT / AST','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.15,'abnormal_direction'=>'elevated','note_vi'=>'Tổn thương gan ảnh hưởng tiêu hóa (Can phạm Tỳ Vị)'],
    ['test_code'=>'colonoscopy','test_name_vi'=>'Nội soi đại tràng (Colonoscopy)','category'=>'other','category_vi'=>'Thăm dò chức năng','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'any','note_vi'=>'Viêm đại tràng, polyp, ung thư đại tràng — Tỳ hư, Đại trường thấp nhiệt'],
];

// ══════════════════════════════════════════════════════════════════
//  VIII. NỘI TIẾT (Endocrine)
// ══════════════════════════════════════════════════════════════════

$endocrine_hints = [
    ['test_code'=>'glucose_fasting','test_name_vi'=>'Đường huyết lúc đói (Glucose)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.25,'abnormal_direction'=>'elevated','note_vi'=>'≥ 7.0 mmol/L = đái tháo đường — Phế Vị nhiệt, Thận âm hư'],
    ['test_code'=>'hba1c','test_name_vi'=>'HbA1c (Đường huyết trung bình 3 tháng)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.25,'abnormal_direction'=>'elevated','note_vi'=>'HbA1c ≥ 6.5% xác nhận đái tháo đường, kiểm soát đường kém'],
    ['test_code'=>'tsh','test_name_vi'=>'TSH — Hormone kích thích tuyến giáp','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.25,'abnormal_direction'=>'any','note_vi'=>'TSH tăng = suy giáp (Thận dương hư, Tỳ hư); TSH giảm = cường giáp (Âm hư hỏa vượng)'],
    ['test_code'=>'thyroid_ultrasound','test_name_vi'=>'Siêu âm tuyến giáp','category'=>'imaging','category_vi'=>'Chẩn đoán hình ảnh','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'any','note_vi'=>'Phát hiện bướu giáp, nhân giáp'],
    ['test_code'=>'lipid_panel','test_name_vi'=>'Mỡ máu toàn phần (Cholesterol, LDL, HDL, TG)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.15,'abnormal_direction'=>'elevated','note_vi'=>'Rối loạn lipid trong Tỳ hư đàm thấp, Can Thận hư'],
];

// ══════════════════════════════════════════════════════════════════
//  IX. SINH SẢN NAM (Male Reproductive)
// ══════════════════════════════════════════════════════════════════

$male_repro_hints = [
    ['test_code'=>'semen_analysis','test_name_vi'=>'Phân tích tinh dịch đồ (Semen Analysis)','category'=>'other','category_vi'=>'Xét nghiệm sinh sản','relevance'=>'confirms','weight'=>0.30,'abnormal_direction'=>'any','note_vi'=>'Số lượng, độ di động, hình thái tinh trùng — chẩn đoán vô sinh nam'],
    ['test_code'=>'testosterone','test_name_vi'=>'Testosterone toàn phần (Total Testosterone)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.25,'abnormal_direction'=>'decreased','note_vi'=>'Testosterone thấp = Thận dương hư, liệt dương, giảm ham muốn'],
    ['test_code'=>'fsh_lh_male','test_name_vi'=>'FSH / LH (nam)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'any','note_vi'=>'Đánh giá trục hạ đồi-tuyến yên-tinh hoàn'],
    ['test_code'=>'prostate_ultrasound','test_name_vi'=>'Siêu âm tuyến tiền liệt','category'=>'imaging','category_vi'=>'Chẩn đoán hình ảnh','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'any','note_vi'=>'Phì đại lành tính / ung thư tuyến tiền liệt — Thận hư, hạ tiêu thấp nhiệt'],
    ['test_code'=>'psa','test_name_vi'=>'PSA (Kháng nguyên đặc hiệu tuyến tiền liệt)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'elevated','note_vi'=>'PSA tăng gợi ý phì đại hoặc ung thư tuyến tiền liệt'],
];

// ══════════════════════════════════════════════════════════════════
//  X. SINH SẢN NỮ (Female Reproductive)
// ══════════════════════════════════════════════════════════════════

$female_repro_hints = [
    ['test_code'=>'pelvic_ultrasound','test_name_vi'=>'Siêu âm phụ khoa (Pelvic Ultrasound)','category'=>'imaging','category_vi'=>'Chẩn đoán hình ảnh','relevance'=>'confirms','weight'=>0.30,'abnormal_direction'=>'any','note_vi'=>'U xơ tử cung, u nang buồng trứng, lạc nội mạc, vô kinh cấu trúc'],
    ['test_code'=>'fsh_lh_female','test_name_vi'=>'FSH / LH / Estradiol (nữ)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.25,'abnormal_direction'=>'any','note_vi'=>'FSH tăng = suy buồng trứng sớm (Thận âm hư); LH/FSH 2:1 trong PCOS (Thận hư đàm thấp)'],
    ['test_code'=>'prolactin','test_name_vi'=>'Prolactin','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'elevated','note_vi'=>'Prolactin tăng gây rối loạn kinh nguyệt, vô kinh'],
    ['test_code'=>'pap_smear','test_name_vi'=>'Phết tế bào cổ tử cung (PAP Smear)','category'=>'other','category_vi'=>'Xét nghiệm phụ khoa','relevance'=>'confirms','weight'=>0.15,'abnormal_direction'=>'any','note_vi'=>'Sàng lọc ung thư cổ tử cung, nhiễm HPV'],
    ['test_code'=>'amh','test_name_vi'=>'AMH (Anti-Müllerian Hormone) — Dự trữ buồng trứng','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.20,'abnormal_direction'=>'decreased','note_vi'=>'AMH thấp = dự trữ buồng trứng kém, Thận tinh hư'],
];

// ══════════════════════════════════════════════════════════════════
//  Gán hints theo organ_system cho các pattern chưa có hints riêng
// ══════════════════════════════════════════════════════════════════

// Query all patterns grouped by organ_system
$patterns = $db->query("SELECT id, chung_code, organ_system, paraclinical_hints FROM kb_patterns WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);

$organMap = [
    'liver'      => $liver_hints,
    'gallbladder'=> $liver_hints,
    'kidney'     => $kidney_hints,
    'bladder'    => $kidney_hints,
    'heart'      => $heart_hints,
    'lung'       => $lung_hints,
    'joint'      => $joint_hints,
    'spleen'     => $gi_hints,     // Tỳ/Vị → GI
    'stomach'    => $gi_hints,
    'large_intestine' => $gi_hints,
    'small_intestine' => $gi_hints,
];

$stmt = $db->prepare("UPDATE kb_patterns SET paraclinical_hints = ? WHERE id = ?");

$updated = 0;
foreach ($patterns as $p) {
    $code   = $p['chung_code'];
    $organ  = $p['organ_system'] ?? '';

    // Priority: specific map first, then organ-based, skip if no match
    if (isset($hintMap[$code])) {
        $hints = $hintMap[$code];
    } elseif (isset($organMap[$organ])) {
        $hints = $organMap[$organ];
    } else {
        // Determine by pattern name keywords
        $name = strtolower($p['chung_code'] . ' ' . $organ);
        if (strpos($name, 'kidney') !== false || strpos($name, 'renal') !== false) {
            $hints = $kidney_hints;
        } elseif (strpos($name, 'heart') !== false || strpos($name, 'cardiac') !== false) {
            $hints = $heart_hints;
        } elseif (strpos($name, 'liver') !== false || strpos($name, 'hepat') !== false) {
            $hints = $liver_hints;
        } elseif (strpos($name, 'lung') !== false || strpos($name, 'pulmon') !== false) {
            $hints = $lung_hints;
        } elseif (strpos($name, 'joint') !== false || strpos($name, 'bone') !== false) {
            $hints = $joint_hints;
        } elseif (strpos($name, 'male') !== false || strpos($name, 'sperm') !== false || strpos($name, 'prostate') !== false) {
            $hints = $male_repro_hints;
        } elseif (strpos($name, 'uterus') !== false || strpos($name, 'ovary') !== false || strpos($name, 'female') !== false) {
            $hints = $female_repro_hints;
        } elseif (strpos($name, 'skin') !== false || strpos($name, 'itch') !== false || strpos($name, 'eczema') !== false) {
            $hints = [
                ['test_code'=>'cbc_eosinophil','test_name_vi'=>'Bạch cầu ái toan','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.15,'abnormal_direction'=>'elevated','note_vi'=>'Tăng trong phản ứng dị ứng da'],
                ['test_code'=>'ige_total','test_name_vi'=>'IgE toàn phần','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.15,'abnormal_direction'=>'elevated','note_vi'=>'IgE tăng trong dị ứng da'],
            ];
        } else {
            // Tổng quát — CBC + CRP
            $hints = [
                ['test_code'=>'cbc_general','test_name_vi'=>'Công thức máu toàn bộ (CBC)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.10,'abnormal_direction'=>'any','note_vi'=>'Đánh giá tình trạng nhiễm trùng, thiếu máu, rối loạn đông máu'],
                ['test_code'=>'crp','test_name_vi'=>'CRP (Protein phản ứng C)','category'=>'blood','category_vi'=>'Xét nghiệm máu','relevance'=>'confirms','weight'=>0.10,'abnormal_direction'=>'elevated','note_vi'=>'Viêm hệ thống'],
            ];
        }
    }

    // Add reproductive hints for relevant organ systems
    if (in_array($organ, ['kidney', 'liver', 'heart', 'lung', 'spleen', 'stomach'])) {
        // Already handled above
    }

    // Add diabetes hints to kidney/endocrine patterns
    if (strpos($code, 'diabetes') !== false || strpos($code, 'thirst') !== false || strpos($code, 'xiao_ke') !== false) {
        $hints = array_merge($hints ?? [], $endocrine_hints);
    }

    // Add semen analysis if pattern is about male fertility / kidney yang deficiency
    if (strpos($code, 'kidney_yang') !== false || strpos($code, 'yang_deficiency') !== false
        || strpos($code, 'shen_yang') !== false || strpos($code, 'impotence') !== false
        || strpos($code, 'infertility') !== false) {
        $hints = array_merge($hints ?? [], $male_repro_hints);
    }

    // Deduplicate by test_code
    $seen = [];
    $uniqueHints = [];
    foreach (($hints ?? []) as $h) {
        if (!isset($seen[$h['test_code']])) {
            $seen[$h['test_code']] = true;
            $uniqueHints[] = $h;
        }
    }

    if (empty($uniqueHints)) continue;

    $stmt->execute([json_encode($uniqueHints, JSON_UNESCAPED_UNICODE), $p['id']]);
    $updated++;
}

echo "Updated $updated / " . count($patterns) . " patterns with paraclinical_hints\n";

// Summary
$withHints = $db->query("SELECT COUNT(*) FROM kb_patterns WHERE paraclinical_hints IS NOT NULL AND paraclinical_hints != 'null'")->fetchColumn();
echo "Patterns with hints: $withHints / " . count($patterns) . "\n";

// Show sample
echo "\nSample (blood_heat_wind_itch):\n";
$sample = $db->query("SELECT paraclinical_hints FROM kb_patterns WHERE chung_code = 'blood_heat_wind_itch'")->fetchColumn();
$arr = json_decode($sample, true);
foreach ($arr as $h) {
    echo "  [{$h['category_vi']}] {$h['test_name_vi']} ({$h['relevance']}, weight={$h['weight']})\n";
}
