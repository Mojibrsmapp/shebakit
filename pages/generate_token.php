<?php
session_start();
if (!empty($_SESSION["admin_token"])) {
// Include the database connection
include('../includes/configuration.php');

// Check if the email parameter is set
if (isset($_GET['token'])) {
    $adminToken = (string)$_SESSION["admin_token"];
    $adminStmt = $conn->prepare("SELECT id FROM admin_user WHERE token = ? LIMIT 1");
    $adminStmt->bind_param("s", $adminToken);
    $adminStmt->execute();
    $adminRow = $adminStmt->get_result()->fetch_assoc();
    $adminStmt->close();
    if (!$adminRow) { header("Location: ../admin/index.php"); exit; }

    // Generate a new token (you can change this to any method of token generation)
    $newToken = bin2hex(random_bytes(5)); // Generates a 32-character token

    // Update the user's token in the database
    $sql = "UPDATE admin_user SET reg_token = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $adminId = (int)$adminRow['id'];
    $stmt->bind_param("si", $newToken, $adminId);

    if ($stmt->execute()) {
        // Success - redirect to the edit page with the email parameter
        header("Location: ../admin/createLink.php?msg=success&text=New+link+generated");
        exit;
    } else {
        // Handle error
        header("Location: ../index.php");
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    // Handle the case where the email is not provided
    echo "Email not provided.";
}
} else {
    header("Location: ../index.php");
}
?>
