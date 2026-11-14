<?php
include_once("../../includes/configuration.php");

// সাইন কপির পেন্ডিং অর্ডার কাউন্ট করার কুয়েরি
// যেহেতু sign_order.php থেকে user_type='file' এবং order_type='sign_copy' হিসেবে সেভ হয়
$sql_sign_count = "SELECT COUNT(*) AS count FROM order_list WHERE order_type = 'sign_copy' AND status = 'pending'";
$result_sign_count = $conn->query($sql_sign_count);

$counts = [
    'sign_pending' => 0
];

// ডাটা ফেচ করা
if ($result_sign_count && $result_sign_count->num_rows > 0) {
    $row = $result_sign_count->fetch_assoc();
    $counts['sign_pending'] = (int)$row['count'];
}

// ডাটাবেস কানেকশন ক্লোজ করা
$conn->close();

// JSON আকারে রেসপন্স রিটার্ন করা
header('Content-Type: application/json');
echo json_encode($counts);
?>