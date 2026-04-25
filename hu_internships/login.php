<?php
session_start();
require_once 'includes/db_connect.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $u_id = isset($_POST['u_id']) ? $_POST['u_id'] : ''; 
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE u_id = :u_id AND role = :role LIMIT 1");
    $stmt->execute(['u_id' => $u_id, 'role' => $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $password === $user['password']) {
        $_SESSION['u_id'] = $user['u_id'];
        $_SESSION['role'] = $user['role'];
        
        // เพิ่มเงื่อนไขแยกหน้าตาม Role ให้ครบ
        if ($user['role'] == 'student') {
            header("Location: student/view_status.php");
        } 
        elseif ($user['role'] == 'teacher') {
            // ถ้าเป็นอาจารย์ ให้ส่งไปโฟลเดอร์ teacher
            header("Location: teacher/view_all.php"); 
        } 
        elseif ($user['role'] == 'admin') {
            // ถ้าเป็นเจ้าหน้าที่ ให้ส่งไปโฟลเดอร์ staff
            header("Location: staff/s_view_all.php");
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>Login - IS.SWU</title>
    <style>
        /* ตั้งค่าพื้นหลังสีชมพูอ่อนตามรูป */
        body {
            background-color: #FCE4EC;
            font-family: 'Prompt', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        select, input, .btn-login, label {
        font-family: 'Prompt', sans-serif;
        }

        /* กล่องหลักที่แบ่งซ้าย-ขวา */
        .main-container {
            display: flex;
            align-items: center;
            gap: 50px; /* ระยะห่างระหว่างฟอร์มกับรูปคอม */
        }

        /* ฝั่งซ้าย: การ์ด Login */
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 40px; /* ขอบมนมากตามรูป */
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            width: 380px;
        }

        .header h2 { 
            font-family: 'Kanit', sans-serif;
            margin: 0; 
            font-size: 28px; 
            color: #333; 
        }

        .header p { 
            font-family: 'Kanit', sans-serif;
            color: #888; 
            font-size: 14px; 
            margin-bottom: 30px; 
        }

        /* การตกแต่ง Input และ Select */
        .form-group { 
            margin-bottom: 20px; 
        }
        label { 
            display: block; 
            margin-bottom: 8px; 
            color: #A58E8E; 
            font-size: 14px; }
        
        select, input {
            width: 100%;
            padding: 12px 20px;
            border: none;
            border-radius: 20px; /* ขอบมน */
            background-color: #F1F1F1; /* สีเทาอ่อนตามรูป */
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
        }

        /* ปุ่ม Login สีเขียว */
        .btn-login {
            width: 50%; /* ปุ่มขนาดเล็กลงหน่อยตามรูป */
            padding: 12px;
            border: none;
            border-radius: 10px;
            background-color: #69F0AE;
            color: #333;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            display: block;
            margin: 20px auto 0; /* จัดกลางปุ่ม */
        }

        .forgot {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #FF5252;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
        }

        /* ฝั่งขวา: พื้นที่ใส่รูปคอมพิวเตอร์ */
        .image-section {
            width: 450px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .image-section img {
            width: 100%;
            height: auto;
            /* คุณสามารถใส่รูปคอมพิวเตอร์ 3D ตรงนี้ */
        }
        
        .imageppw-section {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .error-msg { color: red; text-align: center; font-size: 13px; margin-bottom: 10px; }
    </style>
</head>
<body>

    <div class="main-container">
        
        <div class="login-card">
            <div class="header">
                <h2>LOG IN</h2>
                <p>WELCOME TO IS.SWU</p>
            </div>

            <?php if($error) echo "<p class='error-msg'>$error</p>"; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <select name="role" required>
                        <option value="" disabled selected>-- สถานะผู้ใช้งาน --</option>
                        <option value="student">นิสิต (Student)</option>
                        <option value="teacher">อาจารย์ (Teacher)</option>
                        <option value="admin">เจ้าหน้าที่ (Admin)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>User ID :</label>
                    <input type="text" name="u_id" required>
                </div>

                <div class="form-group">
                    <label>Passwords :</label>
                    <input type="password" name="password" required>
                </div>

                <button type="submit" class="btn-login">LOG IN</button>
                <a href="#" class="forgot">Forget Passwords.?</a>
            </form>
        </div>

        <div class="image-combined-section" style="display: flex; flex-direction: column; align-items: center;">
            <img src="homepage_pic/picturc/login_pic.png" alt="Computer Icon" style="width: 100%; max-width: 400px; height: auto;">
        </div>
    </div>

</body>
</html>