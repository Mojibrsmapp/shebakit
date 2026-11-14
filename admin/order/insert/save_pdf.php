<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once("../../includes/configuration.php");
require_once __DIR__ . "/../../../includes/upload_validation.php";

$adminToken = (string)($_SESSION['admin_token'] ?? '');
$auth = $conn->prepare('SELECT id FROM admin_user WHERE token = ? LIMIT 1');
$auth->bind_param('s', $adminToken);
$auth->execute();
$isAdmin = (bool)$auth->get_result()->fetch_assoc();
$auth->close();
if ($adminToken === '' || !$isAdmin) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id']) && isset($_FILES['pdfFile']) && shebakit_valid_upload($_FILES['pdfFile'], 20971520) && strtolower(pathinfo($_FILES['pdfFile']['name'], PATHINFO_EXTENSION)) === 'pdf') {
        $orderId = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        if (!$orderId || $orderId < 1) { echo json_encode(['status' => 'error', 'message' => 'Invalid order id.']); exit; }
        $uploadDir = dirname(__DIR__, 3) . '/pdf/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
        $fileTmpPath = $_FILES['pdfFile']['tmp_name'];

        // Generate a unique random file name
        $randomString = bin2hex(random_bytes(8)); // 16 characters
        $fileExtension = pathinfo($_FILES['pdfFile']['name'], PATHINFO_EXTENSION);
        $fileName = $randomString . '.' . $fileExtension; // Unique file name
        $insertFile = 'pdf/' . $fileName; // Path for database insertion
        $destinationPath = $uploadDir . $fileName; // Complete destination path

        // Attempt to move the uploaded file to the destination folder
        if (move_uploaded_file($fileTmpPath, $destinationPath)) {
            // Update the database with the file path and status
			$query = "UPDATE order_list SET file_one = ?, status = 'success', complete_time = NOW() WHERE id = ?";
            $updateStmt = $conn->prepare($query);
            $updateStmt->bind_param('si', $insertFile, $orderId);
            if ($updateStmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'File uploaded successfully!', 'fileName' => $fileName]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
            }
            $updateStmt->close();
        } else {
            // Debugging potential issues with file upload
            $errorMessage = 'Failed to move uploaded file. ';

            // Check if the directory is writable
            if (!is_writable($uploadDir)) {
                $errorMessage .= 'Upload directory is not writable. ';
            }

            // Check if the temporary file exists
            elseif (!file_exists($fileTmpPath)) {
                $errorMessage .= 'Temporary file is missing. ';
            }

            // Add more details about the file and directory
            $errorMessage .= 'Directory: ' . $uploadDir . '. ';
            $errorMessage .= 'Temp file: ' . $fileTmpPath . '. ';
            if (file_exists($fileTmpPath)) {
                $errorMessage .= 'Temp file size: ' . filesize($fileTmpPath) . '. ';
            } else {
                $errorMessage .= 'Temp file does not exist. ';
            }

            echo json_encode(['status' => 'error', 'message' => $errorMessage]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No file uploaded or there was an error.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
