<?php
// Establish PHP session after successful API authentication

require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload || !isset($payload['user']) || !is_array($payload['user'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit;
}

$user = $payload['user'];
$userId = $user['id'] ?? null;
$username = $user['username'] ?? null;
$role = $user['role'] ?? 'user';

if ($userId === null || $username === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing user data']);
    exit;
}

$_SESSION['user_id'] = $userId;
$_SESSION['username'] = $username;
$_SESSION['user_role'] = $role;

if (isset($user['email'])) {
    $_SESSION['email'] = $user['email'];
}

if (isset($user['last_login'])) {
    $_SESSION['last_login'] = $user['last_login'];
}

echo json_encode(['success' => true]);
