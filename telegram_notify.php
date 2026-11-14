<?php
// telegram_notify.php

function sendTelegramNotification($message) {
    $botToken = "6260586366:AAFlUeC0t6ua9GZDvywnfL5JSBEPCflura8";
    $chatId = "1875687264"; // আপনার চ্যাট আইডি
    
    $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 5 // ৫ সেকেন্ড টাইমআউট
        ]
    ];
    
    $context  = stream_context_create($options);
    // সার্ভার স্লো হওয়া এড়াতে @ দিয়ে এরর সাপ্রেস করা হলো
    @file_get_contents($url, false, $context);
}

// নতুন অর্ডার বা রিচার্জ নোটিফিকেশন পাঠানোর ফাংশন
function notifyNewOrder($type, $details, $userInfo, $amount) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    $siteName = "ShebaKit";
    $date = date('d M Y, h:i A');
    
    $msg = "🔔 <b>নতুন {$type} এসেছে!</b>\n\n";
    $msg .= "🌐 <b>সাইট:</b> {$siteName}\n";
    $msg .= "👤 <b>ইউজার:</b> {$userInfo}\n";
    $msg .= "💰 <b>পরিমাণ:</b> {$amount} ৳\n";
    $msg .= "📝 <b>বিবরণ:</b> {$details}\n";
    $msg .= "🌍 <b>আইপি:</b> {$ip}\n";
    $msg .= "⏰ <b>সময়:</b> {$date}\n\n";
    $msg .= "👉 <i>বিস্তারিত দেখতে ড্যাশবোর্ডে যান।</i>";
    
    sendTelegramNotification($msg);
}
?>