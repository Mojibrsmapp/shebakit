<?php
session_start();
require_once __DIR__ . '/includes/configuration.php';

if (empty($_SESSION['user_token'])) {
    header('Location: index.php');
    exit;
}

$token = (string)$_SESSION['user_token'];
$userStmt = $conn->prepare('SELECT id, name, balance FROM users WHERE token = ? LIMIT 1');
$userStmt->bind_param('s', $token);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();
if (!$user) {
    unset($_SESSION['user_token'], $_SESSION['user_id']);
    header('Location: index.php');
    exit;
}
$userId = (int)$user['id'];
$serviceId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($serviceId < 1) {
    header('Location: all_services.php');
    exit;
}

if (empty($_SESSION['price_text_csrf'])) {
    $_SESSION['price_text_csrf'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    $value = trim((string)($_POST['text_data'] ?? ''));
    $phone = trim((string)($_POST['contact_phone'] ?? ''));
    $error = '';
    if (!hash_equals($_SESSION['price_text_csrf'], $csrf)) {
        $error = 'সেশন যাচাই করা যায়নি। পেজটি রিফ্রেশ করে আবার চেষ্টা করুন।';
    } elseif ($value === '') {
        $error = 'প্রয়োজনীয় তথ্য লিখুন।';
    } else {
        $conn->begin_transaction();
        try {
            $priceStmt = $conn->prepare("SELECT COALESCE(NULLIF(name, ''), orderName) AS title, price FROM prices WHERE id = ? AND typeOforder = 'text' AND work_status = 1 LIMIT 1 FOR UPDATE");
            $priceStmt->bind_param('i', $serviceId);
            $priceStmt->execute();
            $service = $priceStmt->get_result()->fetch_assoc();
            $priceStmt->close();
            if (!$service) {
                throw new RuntimeException('এই সার্ভিসটি পাওয়া যাচ্ছে না বা বর্তমানে বন্ধ আছে।');
            }
            $cost = (float)$service['price'];
            if ($cost < 0) {
                throw new RuntimeException('সার্ভিসের মূল্য সঠিক নয়।');
            }
            if ($cost > 0) {
                $balanceStmt = $conn->prepare('UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?');
                $balanceStmt->bind_param('did', $cost, $userId, $cost);
                $balanceStmt->execute();
                $charged = $balanceStmt->affected_rows === 1;
                $balanceStmt->close();
                if (!$charged) {
                    throw new RuntimeException('আপনার অ্যাকাউন্টে পর্যাপ্ত ব্যালেন্স নেই।');
                }
            }
            $orderType = '(Text) ' . $service['title'];
            $input = $value . ($phone !== '' ? ' (ফোন: ' . $phone . ')' : '');
            $orderStmt = $conn->prepare("INSERT INTO orders (user_id, order_type, input_data, price, status) VALUES (?, ?, ?, ?, 'pending')");
            $orderStmt->bind_param('issd', $userId, $orderType, $input, $cost);
            $orderStmt->execute();
            $orderStmt->close();
            $conn->commit();
            $_SESSION['price_text_flash'] = ['type' => 'success', 'message' => 'অর্ডার সফলভাবে জমা হয়েছে।'];
        } catch (Throwable $e) {
            $conn->rollback();
            $_SESSION['price_text_flash'] = ['type' => 'error', 'message' => $e instanceof RuntimeException ? $e->getMessage() : 'অর্ডার জমা দিতে সমস্যা হয়েছে।'];
        }
        header('Location: price_text_order.php?id=' . $serviceId);
        exit;
    }
    $_SESSION['price_text_flash'] = ['type' => 'error', 'message' => $error];
    header('Location: price_text_order.php?id=' . $serviceId);
    exit;
}

$priceStmt = $conn->prepare("SELECT COALESCE(NULLIF(name, ''), orderName) AS title, price, field_placeholder FROM prices WHERE id = ? AND typeOforder = 'text' AND work_status = 1 LIMIT 1");
$priceStmt->bind_param('i', $serviceId);
$priceStmt->execute();
$service = $priceStmt->get_result()->fetch_assoc();
$priceStmt->close();
if (!$service) {
    header('Location: all_services.php');
    exit;
}
$flash = $_SESSION['price_text_flash'] ?? null;
unset($_SESSION['price_text_flash']);
$escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $escape($service['title']) ?> - ShebaKit</title>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}body{margin:0;background:#f1f5f9;color:#172554;font-family:'Hind Siliguri',sans-serif}.wrap{max-width:680px;margin:40px auto;padding:20px}.card{background:white;border:1px solid #dbeafe;border-radius:20px;padding:26px;box-shadow:0 12px 35px #0f172a12}a{color:#075fca;text-decoration:none}.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}.muted{color:#64748b}.field{display:block;width:100%;padding:13px 14px;border:1px solid #cbd5e1;border-radius:12px;font:inherit;margin:8px 0 16px}.submit{border:0;border-radius:12px;padding:13px 20px;background:#0866d9;color:white;font:700 16px inherit;cursor:pointer;width:100%}.notice{padding:12px;border-radius:12px;margin:0 0 16px;background:#ecfdf5;color:#047857}.notice.error{background:#fff1f2;color:#be123c}
</style>
</head>
<body><main class="wrap"><div class="top"><a href="all_services.php">← সকল সেবা</a><a href="recharge.php">ব্যালেন্স: ৳<?= number_format((float)$user['balance'], 2) ?></a></div>
<section class="card"><h1><?= $escape($service['title']) ?></h1><p class="muted">সার্ভিস চার্জ: ৳<?= number_format((float)$service['price'], 2) ?></p>
<?php if ($flash): ?><div class="notice <?= $flash['type'] === 'error' ? 'error' : '' ?>"><?= $escape($flash['message']) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['price_text_csrf']) ?>">
<label for="text_data">প্রয়োজনীয় তথ্য</label><textarea class="field" id="text_data" name="text_data" rows="5" placeholder="<?= $escape($service['field_placeholder'] ?: 'তথ্য লিখুন') ?>" required></textarea>
<label for="contact_phone">যোগাযোগ নম্বর (ঐচ্ছিক)</label><input class="field" id="contact_phone" name="contact_phone" type="tel" maxlength="30">
<button class="submit" type="submit">অর্ডার জমা দিন</button></form></section></main></body></html>
