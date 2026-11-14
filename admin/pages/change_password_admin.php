<?php
session_start();
include_once("../includes/configuration.php");

// ইউজারের পাসওয়ার্ড পরিবর্তনের AJAX API; শুধু পূর্ণ অ্যাডমিনের অনুমতি।
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['token'], $_POST['new_password'])) {
    header('Content-Type: application/json; charset=utf-8');
    $adminToken = (string)($_SESSION['admin_token'] ?? '');
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if ($adminToken === '' || !hash_equals((string)($_SESSION['admin_csrf'] ?? ''), $csrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'সেশন যাচাই ব্যর্থ হয়েছে। পেজ রিফ্রেশ করে আবার চেষ্টা করুন।']);
        exit();
    }
    require_once __DIR__ . '/../includes/admin_roles.php';
    if (sheba_require_admin_role($conn, ['admin']) !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'এই কাজের অনুমতি আপনার নেই।']);
        exit();
    }
    $targetToken = trim((string)$_POST['token']);
    $newPassword = trim((string)$_POST['new_password']);
    if ($targetToken === '' || strlen($newPassword) < 6 || strlen($newPassword) > 255) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'পাসওয়ার্ড ৬ থেকে ২৫৫ অক্ষরের মধ্যে দিন।']);
        exit();
    }
    $lookup = $conn->prepare('SELECT id FROM users WHERE token = ? LIMIT 1');
    if (!$lookup) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'ডাটাবেজে অনুরোধ প্রস্তুত করা যায়নি।']);
        exit();
    }
    $lookup->bind_param('s', $targetToken);
    $lookup->execute();
    $target = $lookup->get_result()->fetch_assoc();
    $lookup->close();
    if (!$target) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'ইউজার অ্যাকাউন্ট পাওয়া যায়নি।']);
        exit();
    }
    $hash = md5($newPassword); // Keep compatibility with the existing user login.
    $update = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
    $userId = (int)$target['id'];
    $update->bind_param('si', $hash, $userId);
    $ok = $update->execute();
    $update->close();
    if (!$ok) { http_response_code(500); }
    echo json_encode(['success' => $ok, 'message' => $ok ? 'পাসওয়ার্ড পরিবর্তন হয়েছে।' : 'পাসওয়ার্ড আপডেট করা যায়নি।']);
    exit();
}

// এডমিন নিজের পাসওয়ার্ড পরিবর্তনের ফর্ম
if (!isset($_SESSION['admin_token'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token_user = $_SESSION['admin_token'];
    $currentPassword = trim($_POST['currentPassword'] ?? '');
    $newPassword = trim($_POST['newPassword'] ?? '');
    $confirmPassword = trim($_POST['confirmPassword'] ?? '');

    // ফাঁকা ফিল্ড যাচাই
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        header("Location: ../profile.php?msg=error&text=" . urlencode("সবগুলো তথ্য সঠিকভাবে পূরণ করুন!"));
        exit();
    }

    // নতুন পাসওয়ার্ড নিশ্চিতকরণ যাচাই
    if ($newPassword !== $confirmPassword) {
        header("Location: ../profile.php?msg=error&text=" . urlencode("নতুন পাসওয়ার্ড এবং কনফার্ম পাসওয়ার্ড মিলেনি!"));
        exit();
    }

    // এডমিন অ্যাকাউন্ট অনুসন্ধান
    $stmt = $conn->prepare("SELECT id, password FROM admin_user WHERE token = ? LIMIT 1");
    if (!$stmt) {
        header("Location: ../profile.php?msg=error&text=" . urlencode("ডাটাবেজ সংযোগে ত্রুটি!"));
        exit();
    }

    $stmt->bind_param("s", $token_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        $admin_id = $admin['id'];
        $db_password = $admin['password'];

        // MD5 হ্যাশ যাচাই
        $hashed_current = md5($currentPassword);

        // বর্তমান পাসওয়ার্ড সঠিক আছে কিনা পরীক্ষা
        if ($hashed_current !== $db_password && $currentPassword !== $db_password) {
            header("Location: ../profile.php?msg=error&text=" . urlencode("বর্তমান পাসওয়ার্ড সঠিক নয়!"));
            exit();
        }

        // নতুন পাসওয়ার্ড MD5 হ্যাশ করে আপডেট করা
        $hashed_new = md5($newPassword);
        $update_stmt = $conn->prepare("UPDATE admin_user SET password = ? WHERE id = ?");
        $update_stmt->bind_param("si", $hashed_new, $admin_id);

        if ($update_stmt->execute()) {
            $update_stmt->close();
            $stmt->close();
            header("Location: ../profile.php?msg=success&text=" . urlencode("পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে!"));
            exit();
        } else {
            $update_stmt->close();
            $stmt->close();
            header("Location: ../profile.php?msg=error&text=" . urlencode("পাসওয়ার্ড আপডেট করতে সমস্যা হয়েছে!"));
            exit();
        }

    } else {
        $stmt->close();
        header("Location: ../profile.php?msg=error&text=" . urlencode("এডমিন অ্যাকাউন্ট খুঁজে পাওয়া যায়নি!"));
        exit();
    }
} else {
    header("Location: ../profile.php");
    exit();
}