<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

include_once("../includes/configuration.php");

if (!isset($_SESSION['user_token'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$token_user = $_SESSION['user_token'];

// ইউজারের id, balance ও email বের করা
$stmt = $conn->prepare("SELECT id, balance, email FROM users WHERE token = ?");
$stmt->bind_param("s", $token_user);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user = $res->fetch_assoc();
$user_id = intval($user['id']);
$user_email = trim($user['email']);
$user_balance = number_format(floatval($user['balance']), 2, '.', '');
$stmt->close();

$today_date = date('Y-m-d');

/* ========================================================
   ১. orders টেবিল থেকে কাউন্ট (user_id দিয়ে)
   ======================================================== */
// মোট অর্ডার
$stmt1 = $conn->prepare("SELECT COUNT(*) AS total FROM orders WHERE user_id = ?");
$stmt1->bind_param("i", $user_id);
$stmt1->execute();
$count_orders = $stmt1->get_result()->fetch_assoc()['total'] ?? 0;
$stmt1->close();

// আজকের সম্পন্ন অর্ডার (status = 'success' অথবা 'completed')
$stmt2 = $conn->prepare("SELECT COUNT(*) AS today_comp FROM orders WHERE user_id = ? AND (status = 'success' OR status = 'completed') AND DATE(created_at) = ?");
$stmt2->bind_param("is", $user_id, $today_date);
$stmt2->execute();
$count_today_orders = $stmt2->get_result()->fetch_assoc()['today_comp'] ?? 0;
$stmt2->close();

/* ========================================================
   ২. order_list টেবিল থেকে কাউন্ট (email দিয়ে)
   ======================================================== */
$stmt3 = $conn->prepare("SELECT COUNT(*) AS total FROM order_list WHERE email = ?");
$stmt3->bind_param("s", $user_email);
$stmt3->execute();
$count_order_list = $stmt3->get_result()->fetch_assoc()['total'] ?? 0;
$stmt3->close();

$stmt4 = $conn->prepare("SELECT COUNT(*) AS today_comp FROM order_list WHERE email = ? AND (status = 'success' OR status = 'completed') AND DATE(order_time) = ?");
$stmt4->bind_param("ss", $user_email, $today_date);
$stmt4->execute();
$count_today_order_list = $stmt4->get_result()->fetch_assoc()['today_comp'] ?? 0;
$stmt4->close();

/* ========================================================
   ৩. দুই টেবিল মিলিয়ে সর্বমোট কাউন্ট প্রস্তুত
   ======================================================== */
$total_all_orders = intval($count_orders) + intval($count_order_list);
$today_completed_all = intval($count_today_orders) + intval($count_today_order_list);

$conn->close();

echo json_encode([
    'success'          => true,
    'user_balance'     => $user_balance,
    'total_orders'     => $total_all_orders,
    'completed_orders' => $today_completed_all
], JSON_UNESCAPED_UNICODE);
exit();