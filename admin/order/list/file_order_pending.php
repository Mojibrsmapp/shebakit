<?php
session_start();
$email = base64_decode($_SESSION["user_id"]);
include_once("../../includes/configuration.php");

// Fetch data from the order_list table
// REMOVED: order_type NOT IN ('sign_copy', 'NID PDF') to include sign orders
$sql = "SELECT id, email, order_type, user_type, nid, bio_totto, status, received, opinion, order_time 
        FROM order_list 
        WHERE user_type = 'file' 
        AND status = 'pending'";

$result = $conn->query($sql);

$orders = array();

if ($result->num_rows > 0) {
    // Fetch all rows into an array
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Return data as JSON
header('Content-Type: application/json'); // Ensure correct JSON header
echo json_encode($orders);

// Close the connection
$conn->close();
?>