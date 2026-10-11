<?php
declare(strict_types=1);
require 'db.php';

$user = requireLogin();
if ($user['role'] !== 'member') {
    jsonResponse(['success' => false, 'message' => 'บัญชีผู้ดูแลไม่สามารถจองคิวลูกค้าได้'], 403);
}

$data = requestBody();
$date = trim((string)($data['date'] ?? ''));
$time = trim((string)($data['time'] ?? ''));
$people = filter_var($data['people'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 20]]);
$serviceDateTime = "$date $time:00";

if (!$people || !isValidServiceDateTime($serviceDateTime) || $serviceDateTime < date('Y-m-d H:i:s')) {
    jsonResponse(['success' => false, 'message' => 'ข้อมูลวัน เวลา หรือจำนวนผู้เข้ารับบริการไม่ถูกต้อง'], 422);
}

$allowedTimes = ['10:00', '10:15', '10:30', '10:45', '11:00', '11:15', '11:30', '11:45', '12:00', '12:15', '12:30', '12:45', '13:00', '13:15', '13:30', '13:45', '14:00', '14:15', '14:30', '14:45', '15:00', '15:15', '15:30', '15:45', '16:00', '16:15', '16:30', '16:45', '17:00', '17:15', '17:30', '17:45', '18:00', '18:15', '18:30', '18:45', '19:00', '19:15', '19:30', '19:45', '20:00', '20:15', '20:30', '20:45', '21:00', '21:15', '21:30', '21:45', '22:00'];
if (!in_array($time, $allowedTimes, true)) {
    jsonResponse(['success' => false, 'message' => 'เวลาจองอยู่นอกช่วงให้บริการ'], 422);
}

$conn->begin_transaction();
try {
    $active = $conn->prepare("SELECT queue_id FROM queue WHERE member_id = ? AND status_id IN ('S1', 'S2', 'S5') FOR UPDATE");
    $active->bind_param('s', $user['id']);
    $active->execute();
    if ($active->get_result()->num_rows > 0) {
        throw new RuntimeException('คุณมีคิวที่กำลังใช้งานอยู่แล้ว');
    }
    $active->close();

    $sequence = $conn->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(queue_number, 2) AS UNSIGNED)), 0) + 1 AS next_number FROM queue WHERE DATE(service_datetime) = ? FOR UPDATE");
    $sequence->bind_param('s', $date);
    $sequence->execute();
    $nextNumber = (int)$sequence->get_result()->fetch_assoc()['next_number'];
    $sequence->close();

    $idResult = $conn->query("SELECT COALESCE(MAX(CAST(SUBSTRING(queue_id, 2) AS UNSIGNED)), 0) + 1 AS next_id FROM queue FOR UPDATE");
    $nextId = (int)$idResult->fetch_assoc()['next_id'];
    $queueId = 'Q' . str_pad((string)$nextId, 3, '0', STR_PAD_LEFT);
    $queueNumber = 'A' . str_pad((string)$nextNumber, 3, '0', STR_PAD_LEFT);
    $createdAt = date('Y-m-d H:i:s');
    $insert = $conn->prepare('INSERT INTO queue (queue_id, queue_number, member_id, number_of_people, booking_datetime, service_datetime, status_id) VALUES (?, ?, ?, ?, ?, ?, \'S1\')');
    $insert->bind_param('sssiss', $queueId, $queueNumber, $user['id'], $people, $createdAt, $serviceDateTime);
    $insert->execute();
    $insert->close();

    $conn->commit();
    jsonResponse(['success' => true, 'id' => $queueId, 'queue_number' => $queueNumber]);
} catch (RuntimeException $error) {
    $conn->rollback();
    jsonResponse(['success' => false, 'message' => $error->getMessage()], 422);
} catch (Throwable $error) {
    $conn->rollback();
    error_log('booking: ' . $error->getMessage());
    jsonResponse(['success' => false, 'message' => 'ไม่สามารถจองคิวได้'], 500);
}
