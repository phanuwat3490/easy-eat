<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['name']) || !isset($data['price'])) {
    echo json_encode(["success" => false, "message" => "กรุณากรอกข้อมูลให้ครบถ้วน"]);
    exit();
}

$id = $data['id'] ?? '';
$name = $data['name'] ?? '';
$category = $data['category'] ?? 'ทั่วไป';
$price = $data['price'] ?? 0;
$image = $data['image'] ?? '';
$is_active = isset($data['is_active']) && $data['is_active'] ? 'available' : 'unavailable';

// จัดการแปลง Base64 หากมีการอัปโหลดรูปภาพใหม่
if (!empty($image) && strpos($image, 'data:image/') === 0) {
    $imageParts = explode(',', $image);
    if (count($imageParts) > 1) {
        $decodedImage = base64_decode($imageParts[1]);
        if ($decodedImage !== false) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!file_exists($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $filename = 'uploads/menu_' . time() . '_' . mt_rand(1000, 9999) . '.png';
            $filePath = __DIR__ . '/' . $filename;
            if (@file_put_contents($filePath, $decodedImage)) {
                $image = $filename;
            }
        }
    }
} else if ($image === '' && !empty($id)) {
    // กรณีผู้ใช้กดลบรูปภาพออก ให้เซ็ตค่าเป็นค่าว่างจริง ๆ
    $image = '';
} else if (empty($image) && !empty($id)) {
    $resOld = $conn->query("SELECT image FROM menu WHERE menu_id = '$id'");
    if ($resOld && $rowOld = $resOld->fetch_assoc()) {
        $image = $rowOld['image'];
    }
}

if (!empty($id)) {
    $sql = "UPDATE menu SET menu_name = ?, category = ?, price = ?, image = ?, menu_status = ? WHERE menu_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdsss", $name, $category, $price, $image, $is_active, $id);
} else {
    $resId = $conn->query("SELECT menu_id FROM menu ORDER BY menu_id DESC LIMIT 1");
    $nextId = "MN001";
    if ($resId && $rowId = $resId->fetch_assoc()) {
        $num = (int)str_replace("MN", "", $rowId['menu_id']) + 1;
        $nextId = "MN" . sprintf("%03d", $num);
    }

    $sql = "INSERT INTO menu (menu_id, menu_name, category, price, image, menu_status) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdss", $nextId, $name, $category, $price, $image, $is_active);
}

if ($stmt && $stmt->execute()) {
    echo json_encode(["success" => true, "message" => "บันทึกข้อมูลเมนูสำเร็จ"]);
} else {
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดฐานข้อมูล: " . ($conn->error ?: 'Unknown error')]);
}

if (isset($stmt)) $stmt->close();
$conn->close();
?>