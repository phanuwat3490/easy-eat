<?php
declare(strict_types=1);
require 'db.php';
requireAdmin();

$statusReport = [];
$statusResult = $conn->query("SELECT qs.status_id AS status, COUNT(q.queue_id) AS count
    FROM queue_status qs
    LEFT JOIN queue q ON q.status_id = qs.status_id
    GROUP BY qs.status_id
    ORDER BY qs.status_id");
if ($statusResult === false) {
    jsonResponse(['success' => false, 'message' => 'ไม่สามารถดึงสรุปสถานะคิวได้'], 500);
}
while ($row = $statusResult->fetch_assoc()) {
    $statusReport[] = ['status' => $row['status'], 'count' => (int)$row['count']];
}

$dailyReport = [];
$dailyResult = $conn->query('SELECT DATE(service_datetime) AS date, COUNT(*) AS count FROM queue GROUP BY DATE(service_datetime) ORDER BY date DESC LIMIT 7');
if ($dailyResult === false) {
    jsonResponse(['success' => false, 'message' => 'ไม่สามารถดึงสถิติคิวรายวันได้'], 500);
}
while ($row = $dailyResult->fetch_assoc()) {
    $dailyReport[] = ['date' => $row['date'], 'count' => (int)$row['count']];
}

jsonResponse(['success' => true, 'statusReport' => $statusReport, 'dailyReport' => $dailyReport]);
