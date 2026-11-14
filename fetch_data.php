<?php
session_start();
  if (!isset($_SESSION['user_id'])) {
    // Redirect to logout page or perform logout actions
    header("Location: ../");
    exit();
}
$user = base64_decode($_SESSION['user_id']);
// Database connection
include_once("includes/configuration.php");
require_once __DIR__ . '/includes/server_copy_settings.php';
$settings = sheba_server_copy_settings($conn);
header('Content-Type: application/json; charset=utf-8');
if ($settings['server_copy_enabled'] !== '1' || $settings['server_copy_download_list_enabled'] !== '1') { echo json_encode([]); exit; }

$stmt = $conn->prepare('SELECT * FROM server_copy WHERE user_mail = ? ORDER BY id DESC');
$stmt->bind_param('s', $user);
$stmt->execute();
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) { $data[] = $row; }
$stmt->close();

$conn->close();

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode($data);
?>
