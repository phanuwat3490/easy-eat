<?php
header("Access-Control-Allow-Origin: http://localhost:8080");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$phone = trim($data['phone'] ?? '');
$current_id = trim($data['member_id'] ?? ($data['admin_id'] ?? ''));

if (empty($phone)) {
    echo json_encode(["success" => false, "message" => "กรุณากรอกเบอร์โทรศัพท์"]);
    exit();
}

$exists = false;

// 1. ตรวจสอบว่าเบอร์นี้ถูกใช้งานโดยสมาชิกคนอื่นหรือไม่
$stmtMember = $conn->prepare("SELECT member_id FROM member WHERE phone = ? AND member_id != ?");
$stmtMember->bind_param("ss", $phone, $current_id);
$stmtMember->execute();
if ($stmtMember->get_result()->num_rows > 0) {
    $exists = true;
}
$stmtMember->close();

// 2. ถ้ายังไม่พบในสมาชิก ให้ตรวจสอบว่าเบอร์นี้ถูกใช้งานโดยแอดมินคนอื่นหรือไม่
if (!$exists) {
    $stmtAdmin = $conn->prepare("SELECT admin_id FROM admin WHERE phone = ? AND admin_id != ?");
    $stmtAdmin->bind_param("ss", $phone, $current_id);
    $stmtAdmin->execute();
    if ($stmtAdmin->get_result()->num_rows > 0) {
        $exists = true;
    }
    $stmtAdmin->close();
}

if ($exists) {
    echo json_encode(["success" => false, "exists" => true, "message" => "เบอร์โทรศัพท์นี้ถูกใช้งานในระบบแล้ว"]);
} else {
    echo json_encode(["success" => true, "exists" => false]);
}

$conn->close();
?>