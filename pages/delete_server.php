<?php
session_start();
require_once __DIR__ . '/../includes/configuration.php';
if (empty($_SESSION['user_id']) || empty($_SESSION['legacy_delete_csrf']) || !hash_equals($_SESSION['legacy_delete_csrf'], (string)($_GET['csrf'] ?? ''))) {
    http_response_code(403);
    exit('অনুমতি নেই');
}
$id = base64_decode((string)($_GET['id'] ?? ''), true);
if ($id === false || !ctype_digit($id)) {
    header('Location: ../signToserverList.php');
    exit;
}
$email = base64_decode((string)$_SESSION['user_id'], true);
if ($email === false || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../index.php');
    exit;
}
$stmt = $conn->prepare('DELETE FROM sign_to_server WHERE id = ? AND email = ?');
$recordId = (int)$id;
$stmt->bind_param('is', $recordId, $email);
$stmt->execute();
$stmt->close();
header('Location: ../signToserverList.php');
exit;
