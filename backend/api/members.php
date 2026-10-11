<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db.php';

// ดึงฟิลด์ profile_pic และตรวจสอบเผื่อกรณีฐานข้อมูลใช้ชื่อคอลัมน์ image
$sql = "SELECT member_id AS id, firstname, lastname, email, phone, profile_pic, is_active FROM member WHERE member_id NOT LIKE 'W%' ORDER BY member_id DESC";
$result = $conn->query($sql);

$members = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['is_active'] = (bool)$row['is_active'];
        // รองรับทั้งฟิลด์ profile_pic และ image
        if (empty($row['profile_pic']) && isset($row['image'])) {
            $row['profile_pic'] = $row['image'];
        }
        $members[] = $row;
    }
}

echo json_encode($members);
$conn->close();
?>