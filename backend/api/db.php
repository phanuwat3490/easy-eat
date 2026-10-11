<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Bangkok');

$allowedOrigins = array_filter(array_map('trim', explode(',', getenv('APP_ORIGINS') ?: 'http://localhost:8080,http://localhost:5173')));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
session_start();

$conn = new mysqli(
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASSWORD') ?: '',
    getenv('DB_NAME') ?: 'easy_eat'
);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    jsonResponse(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้'], 500);
}
$conn->query("SET time_zone = '+07:00'");

function jsonResponse(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function requestBody(): array {
    $body = json_decode(file_get_contents('php://input'), true);
    return is_array($body) ? $body : [];
}

function requireLogin(): array {
    if (empty($_SESSION['user'])) {
        jsonResponse(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], 401);
    }
    return $_SESSION['user'];
}

function requireAdmin(): array {
    $user = requireLogin();
    if (($user['role'] ?? '') !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ดำเนินการ'], 403);
    }
    return $user;
}

function isValidServiceDateTime(string $value): bool {
    $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $value);
    return $dateTime !== false && $dateTime->format('Y-m-d H:i:s') === $value;
}
