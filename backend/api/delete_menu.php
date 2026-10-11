<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? '';

if (empty($id)) {
    echo json_encode(["success" => false, "message" => "ไม่พบรหัสเมนูที่ต้องการลบ"]);
    exit();
}

// ตรวจสอบและดึงไฟล์รูปภาพเก่ามาลบออกจากเซิร์ฟเวอร์เพื่อความสะอาด
$resOld = $conn->query("SELECT image FROM menu WHERE menu_id = '$id'");
if ($resOld && $rowOld = $resOld->fetch_assoc()) {
    $oldImage = $rowOld['image'];
    if (!empty($oldImage) && file_exists(__DIR__ . '/' . $oldImage)) {
        @unlink(__DIR__ . '/' . $oldImage);
    }
}

// ลบข้อมูลเมนูออกจากฐานข้อมูล
$sql = "DELETE FROM menu WHERE menu_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);

if ($stmt && $stmt->execute()) {
    echo json_encode(["success" => true, "message" => "ลบเมนูอาหารสำเร็จ"]);
} else {
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการลบข้อมูล: " . $conn->error]);
}

if (isset($stmt)) $stmt->close();
$conn->close();
?>