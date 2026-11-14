<?php
// telegram_cron_notifier.php
include_once("../includes/configuration.php"); // আপনার পাথ অনুযায়ী ঠিক করুন

$botToken = "6260586366:AAFlUeC0t6ua9GZDvywnfL5JSBEPCflura8";
$chatId = "1875687264"; // আপনার টেলিগ্রাম চ্যাট আইডি
$siteName = "ShebaKit";
$siteUrl = "https://shebakit.xyz";

// ফাংশন: টেলিগ্রামে মেসেজ পাঠানো
function sendTelegramMessage($msg) {
    global $botToken, $chatId;
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $msg,
        'parse_mode' => 'HTML'
    ];
    @file_get_contents($url . "?" . http_build_query($data));
}

// গত ২ মিনিটের সময় বের করা (সার্ভারের টাইমজোন অনুযায়ী)
$timeLimit = date('Y-m-d H:i:s', strtotime('-2 minutes'));

// --- ১. নতুন ফাইল/সাইন অর্ডার চেক করা (order_list টেবিল) ---
$q_orders = $conn->query("SELECT * FROM order_list WHERE order_time >= '$timeLimit' ORDER BY id ASC");
if ($q_orders && $q_orders->num_rows > 0) {
    while ($o = $q_orders->fetch_assoc()) {
        $msg = "🔔 <b>নতুন অর্ডার এসেছে!</b>\n\n";
        $msg .= "🌐 <b>সাইট:</b> {$siteName}\n";
        $msg .= "📧 <b>ইউজার:</b> {$o['email']}\n";
        $msg .= "📝 <b>সার্ভিস:</b> {$o['order_type']} ({$o['user_type']})\n";
        $msg .= "💰 <b>মূল্য:</b> {$o['price']} ৳\n";
        $msg .= "🆔 <b>অর্ডার ID:</b> #{$o['id']}\n";
        $msg .= "⏰ <b>সময়:</b> " . date('d M Y, h:i A', strtotime($o['order_time'])) . "\n";
        $msg .= "👉 <a href='{$siteUrl}/admin/all_orders_history.php'>এডমিন প্যানেলে দেখুন</a>";
        
        sendTelegramMessage($msg);
    }
}

// --- ২. নতুন রিচার্জ চেক করা (deposit_requests টেবিল) ---
$q_deposits = $conn->query("SELECT d.*, u.name, u.email, u.mobile FROM deposit_requests d LEFT JOIN users u ON d.user_id = u.id WHERE d.created_at >= '$timeLimit' ORDER BY d.id ASC");
if ($q_deposits && $q_deposits->num_rows > 0) {
    while ($d = $q_deposits->fetch_assoc()) {
        $userInfo = $d['email'] ?? $d['mobile'] ?? 'Unknown User';
        
        $msg = "💰 <b>নতুন রিচার্জ রিকোয়েস্ট!</b>\n\n";
        $msg .= "🌐 <b>সাইট:</b> {$siteName}\n";
        $msg .= "👤 <b>ইউজার:</b> {$userInfo}\n";
        $msg .= "💵 <b>পরিমাণ:</b> {$d['amount']} ৳\n";
        $msg .= "🏦 <b>মেথড:</b> " . strtoupper($d['method']) . "\n";
        $msg .= "🔢 <b>TrxID:</b> {$d['trx_id']}\n";
        $msg .= "⏰ <b>সময়:</b> " . date('d M Y, h:i A', strtotime($d['created_at'])) . "\n";
        $msg .= "👉 <a href='{$siteUrl}/admin/manage_deposits.php'>এখানে এপ্রুভ করুন</a>";
        
        sendTelegramMessage($msg);
    }
}

// --- ৩. নতুন সার্ভিস অর্ডার চেক করা (orders টেবিল) ---
$q_orders2 = $conn->query("SELECT o.*, u.email, u.mobile FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.created_at >= '$timeLimit' ORDER BY o.id ASC");
if ($q_orders2 && $q_orders2->num_rows > 0) {
    while ($o2 = $q_orders2->fetch_assoc()) {
        $userInfo = $o2['email'] ?? $o2['mobile'] ?? 'Unknown';
        
        $msg = "📦 <b>নতুন সার্ভিস অর্ডার!</b>\n\n";
        $msg .= "🌐 <b>সাইট:</b> {$siteName}\n";
        $msg .= "👤 <b>ইউজার:</b> {$userInfo}\n";
        $msg .= "📝 <b>সার্ভিস:</b> {$o2['order_type']}\n";
        $msg .= "💰 <b>মূল্য:</b> {$o2['price']} ৳\n";
        $msg .= "🆔 <b>অর্ডার ID:</b> #{$o2['id']}\n";
        $msg .= "⏰ <b>সময়:</b> " . date('d M Y, h:i A', strtotime($o2['created_at'])) . "\n";
        
        sendTelegramMessage($msg);
    }
}

// Cron Job এর জন্য সফল রেসপন্স
echo "Cron executed successfully at " . date('Y-m-d H:i:s');
?>