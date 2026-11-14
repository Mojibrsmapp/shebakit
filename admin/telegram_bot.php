<?php
// admin/telegram_bot.php
include_once("../includes/configuration.php");

$botToken = "6260586366:AAFlUeC0t6ua9GZDvywnfL5JSBEPCflura8";
$adminChatId = "1875687264"; // শুধুমাত্র এই আইডি থেকেই কমান্ড কাজ করবে

// টেলিগ্রাম থেকে আসা ডাটা রিসিভ করা
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit("No data.");
}

$message = $update["message"] ?? null;
if (!$message) exit();

$chatId = $message["chat"]["id"];
$text = trim($message["text"] ?? '');

// নিরাপত্তা চেক: অন্য কেউ কমান্ড দিলে ইগনোর করবে
if ($chatId != $adminChatId) {
    sendMessage($chatId, "⛔ আপনি এই বট ব্যবহার করার অনুমতি রাখেন না।");
    exit();
}

// কমান্ড প্রসেসিং
switch ($text) {
    case '/start':
        $welcome = "👋 <b>স্বাগতম এডমিন!</b>\n\n";
        $welcome .= "আপনি এখন টেলিগ্রাম থেকেই সাইট কন্ট্রোল করতে পারবেন।\n\n";
        $welcome .= "📌 <b>কমান্ড লিস্ট:</b>\n";
        $welcome .= "/pending - পেন্ডিং অর্ডার ও রিচার্জ দেখুন\n";
        $welcome .= "/stats - সাইটের মোট স্ট্যাটস দেখুন\n";
        $welcome .= "/balance [email] [amount] - ইউজারকে ব্যালেন্স দিন\n";
        sendMessage($chatId, $welcome);
        break;

    case '/stats':
        $users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
        $orders = $conn->query("SELECT COUNT(*) as c FROM order_list")->fetch_assoc()['c'] + $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
        $pending = $conn->query("SELECT COUNT(*) as c FROM order_list WHERE status='pending'")->fetch_assoc()['c'];
        
        $msg = "📊 <b>সাইট স্ট্যাটস</b>\n\n";
        $msg .= "👥 মোট ইউজার: <b>{$users} জন</b>\n";
        $msg .= "📦 মোট অর্ডার: <b>{$orders} টি</b>\n";
        $msg .= "⏳ পেন্ডিং অর্ডার: <b>{$pending} টি</b>\n";
        sendMessage($chatId, $msg);
        break;

    case '/pending':
        $q = $conn->query("SELECT * FROM order_list WHERE status='pending' ORDER BY id DESC LIMIT 5");
        $msg = "⏳ <b>সর্বশেষ ৫টি পেন্ডিং অর্ডার:</b>\n\n";
        if ($q->num_rows > 0) {
            while($row = $q->fetch_assoc()) {
                $msg .= "🆔 <b>ID:</b> #{$row['id']}\n";
                $msg .= "📧 <b>ইউজার:</b> {$row['email']}\n";
                $msg .= "📝 <b>টাইপ:</b> {$row['order_type']}\n";
                $msg .= "💰 <b>দাম:</b> {$row['price']} ৳\n";
                $msg .= "-------------------------\n";
            }
        } else {
            $msg .= "কোনো পেন্ডিং অর্ডার নেই।";
        }
        sendMessage($chatId, $msg);
        break;

    default:
        // /balance email amount কমান্ডের জন্য
        if (preg_match('/^\/balance\s+(\S+)\s+(\d+(\.\d+)?)$/', $text, $matches)) {
            $email = $matches[1];
            $amount = floatval($matches[2]);

            // ইউজার চেক
            $stmt = $conn->prepare("SELECT id, balance FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res->num_rows > 0) {
                $user = $res->fetch_assoc();
                $new_balance = $user['balance'] + $amount;
                
                // ব্যালেন্স আপডেট
                $upd = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
                $upd->bind_param("di", $new_balance, $user['id']);
                $upd->execute();
                
                $msg = "✅ <b>সফলভাবে ব্যালেন্স যোগ করা হয়েছে!</b>\n\n";
                $msg .= "👤 ইউজার: {$email}\n";
                $msg .= "💵 নতুন ব্যালেন্স: <b>{$new_balance} ৳</b>";
                sendMessage($chatId, $msg);
            } else {
                sendMessage($chatId, "❌ এই ইমেইলে কোনো ইউজার পাওয়া যায়নি।");
            }
        } else {
            sendMessage($chatId, "❓ অজানা কমান্ড। /start দিয়ে কমান্ড লিস্ট দেখুন।");
        }
        break;
}

// মেসেজ পাঠানোর হেল্পার ফাংশন
function sendMessage($chatId, $text) {
    global $botToken;
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    @file_get_contents($url . "?" . http_build_query($data));
}
?>