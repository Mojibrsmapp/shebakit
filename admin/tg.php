<?php
// admin/tg.php
// এই ফাইলটি Cron Job এর মাধ্যমে প্রতি ১ মিনিট পর পর রান হবে।

// আপনার দেওয়া ডাটাবেস ইনফো
$servername = "localhost";
$username = "sheb_nid";
$password = "sheb_nid";
$dbname = "sheb_nid";

// ডাটাবেস কানেকশন
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// টেলিগ্রাম কনফিগারেশন
$botToken = "6260586366:AAFlUeC0t6ua9GZDvywnfL5JSBEPCflura8";
$chatId = "1875687264";
$siteName = "ShebaKit";

// টেলিগ্রামে মেসেজ পাঠানোর ফাংশন
function sendTelegramMsg($msg) {
    global $botToken, $chatId;
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $msg,
        'parse_mode' => 'HTML'
    ];
    // সার্ভার স্লো না হওয়ার জন্য @ ব্যবহার করা হয়েছে
    @file_get_contents($url . "?" . http_build_query($data));
}

// সময় জোন সেট করা (বাংলাদেশ সময়)
date_default_timezone_set('Asia/Dhaka');

// গত ২ মিনিটের ডাটা চেক করা হবে (যদি ক্রন জব ১ মিনিট পর পর রান হয়)
$timeLimit = date('Y-m-d H:i:s', strtotime('-2 minutes'));

// ==========================================
// ১. নতুন সাইন/ফাইল অর্ডার চেক করা (order_list টেবিল)
// ==========================================
$sql_order = "SELECT * FROM order_list WHERE order_time >= '$timeLimit'";
$result_order = $conn->query($sql_order);

if ($result_order && $result_order->num_rows > 0) {
    while ($row = $result_order->fetch_assoc()) {
        $msg = "🔔 <b>নতুন অর্ডার এসেছে!</b>\n\n";
        $msg .= "🌐 <b>সাইট:</b> {$siteName}\n";
        $msg .= "📧 <b>ইউজার:</b> {$row['email']}\n";
        $msg .= "📝 <b>সার্ভিস:</b> {$row['order_type']} ({$row['user_type']})\n";
        $msg .= "💰 <b>মূল্য:</b> {$row['price']} ৳\n";
        $msg .= "🆔 <b>ID:</b> #{$row['id']}\n";
        $msg .= "⏰ <b>সময়:</b> " . date('d M Y, h:i A', strtotime($row['order_time']));
        
        sendTelegramMsg($msg);
    }
}

// ==========================================
// ২. নতুন রিচার্জ চেক করা (deposit_requests টেবিল)
// ==========================================
$sql_dep = "SELECT d.*, u.email FROM deposit_requests d LEFT JOIN users u ON d.user_id = u.id WHERE d.created_at >= '$timeLimit'";
$result_dep = $conn->query($sql_dep);

if ($result_dep && $result_dep->num_rows > 0) {
    while ($row = $result_dep->fetch_assoc()) {
        $user_email = $row['email'] ?? 'Unknown User';
        
        $msg = "💰 <b>নতুন রিচার্জ রিকোয়েস্ট!</b>\n\n";
        $msg .= "🌐 <b>সাইট:</b> {$siteName}\n";
        $msg .= "👤 <b>ইউজার:</b> {$user_email}\n";
        $msg .= "💵 <b>পরিমাণ:</b> {$row['amount']} ৳\n";
        $msg .= "🏦 <b>মেথড:</b> " . strtoupper($row['method']) . "\n";
        $msg .= "🔢 <b>TrxID:</b> {$row['trx_id']}\n";
        $msg .= "⏰ <b>সময়:</b> " . date('d M Y, h:i A', strtotime($row['created_at']));
        
        sendTelegramMsg($msg);
    }
}

// ==========================================
// ৩. নতুন সার্ভিস অর্ডার চেক করা (orders টেবিল)
// ==========================================
$sql_orders2 = "SELECT o.*, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.created_at >= '$timeLimit'";
$result_orders2 = $conn->query($sql_orders2);

if ($result_orders2 && $result_orders2->num_rows > 0) {
    while ($row = $result_orders2->fetch_assoc()) {
        $user_email = $row['email'] ?? 'Unknown';
        
        $msg = "📦 <b>নতুন সার্ভিস অর্ডার!</b>\n\n";
        $msg .= "🌐 <b>সাইট:</b> {$siteName}\n";
        $msg .= "👤 <b>ইউজার:</b> {$user_email}\n";
        $msg .= "📝 <b>সার্ভিস:</b> {$row['order_type']}\n";
        $msg .= "💰 <b>মূল্য:</b> {$row['price']} ৳\n";
        $msg .= "🆔 <b>ID:</b> #{$row['id']}\n";
        $msg .= "⏰ <b>সময়:</b> " . date('d M Y, h:i A', strtotime($row['created_at']));
        
        sendTelegramMsg($msg);
    }
}

$conn->close();

// স্ক্রিপ্ট সফলভাবে রান হয়েছে কিনা তা জানানোর জন্য (ঐচ্ছিক)
echo "Scan completed at " . date('Y-m-d H:i:s');
?>