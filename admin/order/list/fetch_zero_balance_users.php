<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/configuration.php';

$adminToken = (string)($_SESSION['admin_token'] ?? '');
if ($adminToken === '') {
    http_response_code(401);
    echo json_encode(['zeroBalanceUsers' => []]);
    exit;
}
$adminCheck = $conn->prepare('SELECT id FROM admin_user WHERE token = ? LIMIT 1');
$adminCheck->bind_param('s', $adminToken);
$adminCheck->execute();
$isAdmin = (bool)$adminCheck->get_result()->fetch_assoc();
$adminCheck->close();
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['zeroBalanceUsers' => []]);
    exit;
}

$users = [];
$result = $conn->query('SELECT id, name, mobile AS phone FROM users WHERE balance <= 0 ORDER BY id DESC LIMIT 50');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = ['id' => (int)$row['id'], 'name' => (string)$row['name'], 'phone' => (string)($row['phone'] ?? '')];
    }
    $result->free();
}
echo json_encode(['zeroBalanceUsers' => $users], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
