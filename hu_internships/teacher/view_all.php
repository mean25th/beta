<?php
session_start();
require_once '../includes/db_connect.php';

// 1. ตรวจสอบสิทธิ์
if (!isset($_SESSION['u_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$u_id = $_SESSION['u_id'];

// 2. ดึงข้อมูลอาจารย์ (ดึงมาทั้งชื่อและนามสกุล)
$stmt = $conn->prepare("SELECT * FROM teachers WHERE u_id = :u_id LIMIT 1");
$stmt->execute(['u_id' => $u_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

$t_id = $teacher['teacher_id']; // รหัสอาจารย์ เช่น T001
$f_name = $teacher['first_name']; // ชื่อ
$l_name = $teacher['last_name'];  // นามสกุล

// 3. ดึงสถิติ (แก้ไขให้ค้นหาแม่นยำขึ้น ทั้งจากรหัสและชื่อ)
$stmt_p = $conn->prepare("SELECT COUNT(*) FROM internship_requests ir JOIN students s ON ir.student_id = s.student_id WHERE (s.advisor = :t_id OR s.advisor = :f_name) AND ir.status = 'รับคำขอ'");
$stmt_p->execute(['t_id' => $t_id, 'f_name' => $f_name]);
$count_pending = $stmt_p->fetchColumn();

$stmt_inc = $conn->prepare("SELECT COUNT(*) FROM internship_requests ir JOIN students s ON ir.student_id = s.student_id WHERE (s.advisor = :t_id OR s.advisor = :f_name) AND ir.status = 'เอกสารไม่ครบ'");
$stmt_inc->execute(['t_id' => $t_id, 'f_name' => $f_name]);
$count_incomplete = $stmt_inc->fetchColumn();

$stmt_a = $conn->prepare("SELECT COUNT(*) FROM internship_requests ir JOIN students s ON ir.student_id = s.student_id WHERE (s.advisor = :t_id OR s.advisor = :f_name) AND (ir.status = 'อนุมัติ' OR ir.status = 'อนุมัติแล้ว')");
$stmt_a->execute(['t_id' => $t_id, 'f_name' => $f_name]);
$count_approved = $stmt_a->fetchColumn();

// 4. ดึงรายชื่อนิสิต
$sql = "SELECT s.*, ir.request_id, ir.status, ir.remark, ir.position, ir.pdf_path, c.name AS company_name 
        FROM students s 
        LEFT JOIN internship_requests ir ON s.student_id = ir.student_id 
        LEFT JOIN companies c ON ir.company_id = c.company_id 
        WHERE s.advisor = :t_id OR s.advisor = :f_name"; 
$stmt_s = $conn->prepare($sql);
$stmt_s->execute(['t_id' => $t_id, 'f_name' => $f_name]);
$students_list = $stmt_s->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>Teacher Dashboard - IS.SWU</title>

    <style>
    /* --- 1. การตั้งค่าพื้นฐาน --- */
    body { 
        font-family: 'Prompt', sans-serif; 
        background-color: #fdf2f2; 
        margin: 0; 
        padding: 20px; 
    }
    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 5%;
        background-color: #fff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
        border-radius: 10px;
    }
    .logo-img { height: 50px; }
    .nav-links { display: flex; gap: 20px; }
    .nav-links a { text-decoration: none; color: #333; font-weight: 600; }
    .logout-btn { background-color: #FF3D3D; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer; transition: 0.3s; }
    .logout-btn:hover { background-color: #e63535; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }

    /* --- 2. Profile Card --- */
    .profile-card { 
        background-color: #C5E1A5; /* สีเขียวอ่อนแบบในรูป */
        border-radius: 20px; 
        padding: 40px; 
        display: flex; 
        align-items: center; 
        gap: 40px; 
        max-width: 900px; 
        margin: 0 auto 40px; 
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .cat-img { width: 140px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .info-group { display: flex; flex-direction: column; gap: 20px; flex-grow: 1; }
    .form-row { display: flex; align-items: center; gap: 15px; }
    .data-box { 
        background-color: #E0E0E0; 
        padding: 12px 25px; 
        border-radius: 15px; 
        font-size: 18px; 
        flex-grow: 1;
        display: block;
        color: #333;
    }
    .profile-title {
        font-family: 'Kanit';
        font-size: 28px;
        font-weight: 600;
        margin-bottom: 15px;
    }

    /* --- 3. Stat Cards (การ์ดสถิติ 3 ช่อง) --- */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        max-width: 900px;
        margin: 0 auto 40px;
    }
    .stat-card {
        padding: 30px 20px;
        border-radius: 20px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transition: transform 0.3s;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-title { font-size: 18px; font-weight: 500; margin-bottom: 15px; color: #333; }
    .stat-num { font-size: 45px; font-weight: 600; color: #111; font-family: 'Kanit'; }
    
    .bg-orange { background-color: #FFCC80; }
    .bg-blue { background-color: #90CAF9; }
    .bg-green { background-color: #69F0AE; }

    /* --- 4. ตารางรายชื่อนิสิต --- */
    .table-container {
        max-width: 1000px;
        margin: 0 auto 50px;
    }
    .table-title {
        text-align: center;
        font-family: 'Kanit';
        font-size: 24px;
        margin-bottom: 20px;
        color: #333;
    }
    .subject-table { 
        width: 100%; 
        border-collapse: collapse; 
        background-color: #EFE6E6; 
        border-radius: 15px; 
        overflow: hidden; 
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .subject-table th, .subject-table td { 
        padding: 18px 15px; 
        border: 1px solid #dcdcdc; 
        text-align: center; 
        vertical-align: middle;
    }
    .subject-table th { background-color: #E2D3D3; font-family: 'Kanit'; font-weight: 600; color: #333; }
    .subject-table tbody tr:hover { background-color: rgba(255,255,255,0.5); }
    
    .status-badge {
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
        display: inline-block;
    }

    /* --- 5. Pagination Buttons --- */
    .pagination-buttons {
        display: flex;
        justify-content: space-between;
        max-width: 1000px;
        margin: 0 auto 50px;
    }
    .btn-page {
        background-color: #8C9EFF;
        color: white;
        padding: 12px 30px;
        border-radius: 10px;
        border: none;
        font-weight: bold;
        cursor: pointer;
    }

    /* --- 6. Modal Review --- */
    .modal {
        display: none; 
        position: fixed; 
        z-index: 2000; 
        left: 0; top: 0;
        width: 100%; height: 100%; 
        background-color: rgba(0,0,0,0.6);
        backdrop-filter: blur(8px); 
        overflow-y: auto; 
        padding: 20px 0;
    }
    .modal-content {
        background-color: #F8E4E4;
        margin: 2% auto;
        padding: 35px;
        border-radius: 25px;
        width: 90%;
        max-width: 780px; 
        position: relative;
        box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        border: 2px solid #F8BBD0;
        animation: modalFadeIn 0.4s; 
    }
    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .modal-header {
        background-color: #F8BBD0;
        padding: 20px;
        border-radius: 18px;
        text-align: center;
        margin-bottom: 30px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    }
    .modal-header h2 { margin: 0; font-family: 'Kanit'; color: #333; letter-spacing: 1px; }
    .close {
        position: absolute;
        right: 25px; top: 20px;
        font-size: 32px; font-weight: bold; cursor: pointer; color: #d81b60; transition: 0.3s;
    }
    .close:hover { color: #f44336; transform: scale(1.1); }

    fieldset {
        border: 2px solid #F8BBD0;
        border-radius: 18px;
        padding: 25px;
        margin-bottom: 25px;
        background: rgba(255, 255, 255, 0.6); 
    }
    legend { 
        font-weight: bold; 
        color: #D81B60; 
        padding: 0 15px; 
        font-size: 18px; 
        background: #F8E4E4; 
        border-radius: 10px;
    }
    .info-grid { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 18px 35px; 
    }
    .info-grid p { margin: 0; font-size: 16px; color: #333; }
    .info-grid b { color: #555; }
    
    .modal select, .modal textarea {
        width: 100%; 
        padding: 12px 15px; 
        border-radius: 12px; 
        border: 1px solid #ddd;
        box-sizing: border-box;
        font-size: 15px;
        margin-top: 8px;
    }
    .btn-submit {
        width: 100%; 
        padding: 16px; 
        background: #28a745; 
        color: white; 
        border: none; 
        border-radius: 15px; 
        cursor: pointer; 
        font-size: 19px;
        font-weight: bold;
        margin-top: 15px;
        transition: 0.3s;
        font-family: 'Kanit';
    }
    .btn-submit:hover { background: #218838; transform: translateY(-2px); }
    .btn-review {
        background: #4A90E2; color: white; border: none; padding: 8px 15px; border-radius: 10px; cursor: pointer; transition: 0.3s;
    }
    .btn-review:hover { background: #357ABD; }
    </style>
</head>

<body>
    <header>
        <div class="logo">
            <a href="https://swu.ac.th/" target="_blank">
                <img src="../homepage_pic/SWU_Logo_TH_Color.png" alt="SWU" class="logo-img">
            </a>
        </div>
        <div class="nav-links">
            <a href="#home">หน้าแรก</a>
            <a href="#news">ประชาสัมพันธ์</a>
            <a href="#teacher">บุคลากร</a>
        </div>
        <a href="../logout.php"><button class="logout-btn">LOG OUT</button></a>
    </header>

    <div class="profile-card">
        <div style="text-align: center;">
            <img src="../homepage_pic/student_pic.png" class="cat-img" alt="profile">
            <p style="margin-top: 10px;"><b>ยินดีต้อนรับนะคะ</b></p>
        </div>
        <div class="info-group">
            <div class="profile-title">สวัสดีนะคะ อาจารย์</div>
            <div class="form-row">
                <span style="min-width:100px; font-weight: 500;">ชื่อ - สกุล :</span> 
                <span class="data-box"><?= htmlspecialchars($f_name . " " . $l_name) ?></span>
            </div>
            <div class="form-row">
                <span style="min-width:140px; font-weight: 500;">อาจารย์ที่ปรึกษาชั้นปี :</span> 
                <span class="data-box">4</span>
            </div>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card bg-orange">
            <div class="stat-title">รออนุมัติฝึกงาน</div>
            <div class="stat-num"><?= $count_pending ?></div>
        </div>
        <div class="stat-card bg-blue">
            <div class="stat-title">เอกสารไม่ครบ</div>
            <div class="stat-num"><?= $count_incomplete ?></div>
        </div>
        <div class="stat-card bg-green">
            <div class="stat-title">อนุมัติแล้ว</div>
            <div class="stat-num"><?= $count_approved ?></div>
        </div>
    </div>

    <div class="table-container">
        <div class="table-title">รายชื่อนิสิตที่ปรึกษา</div>
        <table class="subject-table">
            <thead>
                <tr>
                    <th>รหัสนิสิต</th>
                    <th>ชื่อ - สกุล</th>
                    <th>✅ สถานะฝึกงาน</th>
                    <th>📄 เอกสารที่ส่ง</th>
                    <th>✍️ หมายเหตุ</th>
                    <th>ตรวจสอบ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students_list as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['student_id']) ?></td>
                        <td><?= htmlspecialchars($student['full_name']) ?></td>
                        <td><?= htmlspecialchars($student['company_name'] ?? '-') ?></td>
                        <td>
                            <?php if($student['status'] == 'รับคำขอ'): ?>
                                <span style="color: orange; font-weight: bold;">รอตรวจสอบ</span>
                            <?php elseif($student['status'] == 'อนุมัติ'): ?>
                                <span style="color: green; font-weight: bold;">อนุมัติแล้ว</span>
                            <?php else: ?>
                                <span><?= htmlspecialchars($student['status'] ?? 'ยังไม่ส่งคำขอ') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($student['request_id'])): ?>
                                <button type="button" 
                                        style="padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;"
                                        onclick='openReview(<?= json_encode($student, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)'>
                                    🔍 ตรวจสอบ / อนุมัติ
                                </button>
                            <?php else: ?>
                                <span style="color: #ccc;">ยังไม่มีข้อมูล</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-buttons">
        <button class="btn-page">หน้าก่อนหน้า</button>
        <button class="btn-page">หน้าถัดไป</button>
    </div>

    <div id="reviewModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 style="margin-top:0;">🔍 ตรวจสอบใบคำขอฝึกงาน</h2>
        <hr>
        <div class="modal-body">
            <p><strong>ชื่อ-นามสกุล:</strong> <span id="m_name"></span></p>
            <p><strong>รหัสนิสิต:</strong> <span id="m_sid"></span></p>
            <p><strong>บริษัท:</strong> <span id="m_company"></span></p>
            <p><strong>ตำแหน่ง:</strong> <span id="m_pos"></span></p>
            
            <p><strong>เอกสาร PDF:</strong> 
                <a id="m_pdf" href="#" target="_blank" class="btn-pdf" style="display:none; background:#17a2b8; color:white; padding:5px 12px; border-radius:4px; text-decoration:none;">
                    📂 คลิกเพื่อดูไฟล์ PDF
                </a>
                <span id="m_no_pdf" style="color:red; display:none;">(นิสิตยังไม่ได้แนบไฟล์)</span>
            </p>

            <form action="update_status.php" method="POST">
                <input type="hidden" name="request_id" id="m_request_id">
                
                <div style="margin-top:15px;">
                    <label><strong>สถานะการอนุมัติ:</strong></label>
                    <select name="status" id="m_status" class="form-control" style="width:100%; padding:8px; margin-top:5px;" required>
                        <option value="รับคำขอ">รอการตรวจสอบ (รับคำขอ)</option>
                        <option value="อนุมัติ">✅ อนุมัติ</option>
                        <option value="เอกสารไม่ครบ">❌ เอกสารไม่ครบ / ให้แก้ไขใหม่</option>
                    </select>
                </div>

                <div style="margin-top:15px;">
                    <label><strong>หมายเหตุ (พิมพ์ได้ที่นี่):</strong></label>
                    <textarea name="remark" id="m_remark" class="form-control" rows="3" style="width:100%; padding:8px; margin-top:5px;" placeholder="ใส่เหตุผลกรณีไม่อนุมัติ หรือคำแนะนำเพิ่มเติม..."></textarea>
                </div>

                <div style="margin-top:20px; text-align:right;">
                    <button type="button" onclick="closeModal()" style="padding:10px 15px; background:#6c757d; color:white; border:none; border-radius:4px; cursor:pointer;">ยกเลิก</button>
                    <button type="submit" style="padding:10px 20px; background:#28a745; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">💾 บันทึกและอนุมัติ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// --- ส่วนของ JavaScript (หรือที่เรียกว่า JS/Java) ---
function openReview(data) {
    // 1. นำข้อมูลใส่ลงใน Modal
    document.getElementById('m_request_id').value = data.request_id || '';
    document.getElementById('m_name').innerText = data.full_name || '';
    document.getElementById('m_sid').innerText = data.student_id || '';
    document.getElementById('m_company').innerText = data.company_name || '-';
    document.getElementById('m_pos').innerText = data.position || '-';
    
    // 2. เคลียร์หรือดึงข้อความหมายเหตุมาแสดง
    document.getElementById('m_remark').value = data.remark || '';
    
    // 3. ตั้งค่า Dropdown สถานะให้ตรงกับของเดิม
    document.getElementById('m_status').value = data.status || 'รับคำขอ';

    // 4. จัดการปุ่มดู PDF (เช็คว่ามีไฟล์ไหม)
    const pdfBtn = document.getElementById('m_pdf');
    const noPdfText = document.getElementById('m_no_pdf');
    
    if(data.pdf_path && data.pdf_path.trim() !== "") {
        pdfBtn.href = "../uploads/" + data.pdf_path;
        pdfBtn.style.display = "inline-block";
        noPdfText.style.display = "none";
    } else {
        pdfBtn.style.display = "none";
        noPdfText.style.display = "inline-block";
    }

    // 5. เปิดหน้าต่าง Modal ขึ้นมา
    document.getElementById('reviewModal').style.display = "block";
}

// ฟังก์ชันปิดหน้าต่าง Modal
function closeModal() { 
    document.getElementById('reviewModal').style.display = "none"; 
}

// กดพื้นที่ว่างข้างนอกเพื่อปิด Modal ได้
window.onclick = function(event) {
    const modal = document.getElementById('reviewModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>
</body>
</html>