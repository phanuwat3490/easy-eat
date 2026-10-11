<?php
require_once __DIR__ . '/cors.php';
header("Content-Type: application/json; charset=UTF-8");

include 'db.php';

$member_id = $_GET['member_id'] ?? '';

if ($member_id === '') {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ไม่พบรหัสสมาชิก"]);
    exit();
}

$stmt = $conn->prepare("SELECT is_active FROM member WHERE member_id = ?");
$stmt->bind_param("s", $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "success"   => true,
        "is_active" => (int)$row['is_active'] === 1,
    ]);
} else {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "ไม่พบข้อมูลผู้ใช้"]);
}

$stmt->close();
$conn->close();