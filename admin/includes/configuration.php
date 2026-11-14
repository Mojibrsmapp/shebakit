<?php
$servername = "localhost";
$username = "sheb_nid";
$password = "sheb_nid";
$dbname = "sheb_nid";

$conn = new mysqli($servername, $username, $password, $dbname);
mysqli_set_charset($conn, "utf8mb4");

if (!function_exists('get_site_setting')) {
    function get_site_setting($conn, $key, $default = '') {
        $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $key);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                $val = trim($row['setting_value']);
                $stmt->close();
                return !empty($val) ? $val : $default;
            }
            $stmt->close();
        }
        return $default;
    }
}

// ==========================================
// Telegram Instant Notification Function
// ==========================================
if (!function_exists('send_tg_instant')) {
    function send_tg_instant($type, $userEmail, $amount, $method = '', $trxId = '') {
        $botToken = "6260586366:AAFlUeC0t6ua9GZDvywnfL5JSBEPCflura8";
        $chatId   = "1875687264";
        $siteName = "ShebaKit";
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
        $date = date('d M Y, h:i A');

        if ($type == 'recharge') {
            $msg = "💰 <b>নতুন রিচার্জ রিকোয়েস্ট!</b>\n\n";
            $msg .= "🌐 <b>সাইট:</b> {$siteName}\n";
            $msg .= "👤 <b>ইউজার:</b> {$userEmail}\n";
            $msg .= "💵 <b>পরিমাণ:</b> {$amount} ৳\n";
            $msg .= "🏦 <b>মেথড:</b> " . strtoupper($method) . "\n";
            $msg .= "🔢 <b>TrxID:</b> {$trxId}\n";
            $msg .= "🌍 <b>আইপি:</b> {$ip}\n";
            $msg .= "⏰ <b>সময়:</b> {$date}";
        } elseif ($type == 'order') {
            $msg = "🔔 <b>নতুন অর্ডার এসেছে!</b>\n\n";
            $msg .= "🌐 <b>সাইট:</b> {$siteName}\n";
            $msg .= "📧 <b>ইউজার:</b> {$userEmail}\n";
            $msg .= "💰 <b>মূল্য:</b> {$amount} ৳\n";
            $msg .= "📝 <b>বিবরণ:</b> {$method}\n";
            $msg .= "🌍 <b>আইপি:</b> {$ip}\n";
            $msg .= "⏰ <b>সময়:</b> {$date}";
        } else {
            return; // অজানা টাইপ হলে কিছুই করবে না
        }

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $data = ['chat_id' => $chatId, 'text' => $msg, 'parse_mode' => 'HTML'];
        
        // cURL ব্যবহার করা হয়েছে যাতে সব সার্ভারে কাজ করে
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_exec($ch);
            curl_close($ch);
        } else {
            @file_get_contents($url . "?" . http_build_query($data));
        }
    }
}
require_once __DIR__ . '/admin_roles.php';
sheba_ensure_admin_role_column($conn);
sheba_enforce_moderator_scope($conn);
