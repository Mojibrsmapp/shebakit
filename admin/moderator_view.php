<?php
include_once __DIR__ . '/header.php';
$section = (string)($_GET['section'] ?? '');
$labels = ['orders' => 'অর্ডার তালিকা', 'history' => 'অর্ডার ও কাজের হিস্টোরি', 'recharge' => 'রিচার্জ ও ডিপোজিট', 'services' => 'সার্ভিস তালিকা', 'users' => 'ইউজার তালিকা'];
if (!isset($labels[$section]) || !sheba_moderator_has_permission($conn, $section)) { http_response_code(403); exit('এই অংশ দেখার অনুমতি নেই।'); }
$rows = [];
$error = '';
try {
    if ($section === 'orders') {
        $result = $conn->query("SELECT o.id, o.order_type, o.input_data, o.price, o.status, o.created_at, u.name AS user_name, u.mobile AS user_phone FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 200");
        if ($result) { while ($row = $result->fetch_assoc()) { $rows[] = $row; } }
    } elseif ($section === 'history') {
        $result = $conn->query("SELECT id, email, order_type, price, current_balance, balance_after_cut, order_time FROM history_work ORDER BY id DESC LIMIT 200");
        if ($result) { while ($row = $result->fetch_assoc()) { $rows[] = $row; } }
    } elseif ($section === 'recharge') {
        $result = $conn->query("SELECT d.id, d.user_id, d.method, d.trx_id, d.amount, d.screenshot, d.status, d.created_at, u.name AS user_name, u.mobile AS user_phone FROM deposit_requests d LEFT JOIN users u ON u.id = d.user_id ORDER BY d.id DESC LIMIT 200");
        if ($result) { while ($row = $result->fetch_assoc()) { $rows[] = $row; } }
    } elseif ($section === 'services') {
        $serviceQueries = [
            "SELECT service_name AS title, status, price, 'লোকেশন সার্ভিস' AS category FROM location_services",
            "SELECT service_name AS title, status, price, 'কল ও এসএমএস' AS category FROM call_sms_services",
            "SELECT service_name AS title, status, price, 'IMEI সার্ভিস' AS category FROM imei_services",
            "SELECT service_name AS title, status, price, 'বিকাশ/নগদ/রকেট' AS category FROM mfs_services",
            "SELECT operator_name AS title, status, price, 'নাম্বার টু বায়ো' AS category FROM number_to_server_services",
            "SELECT service_title AS title, status, price, 'অর্ডার টেক্সট' AS category FROM order_text_services",
            "SELECT service_title AS title, status, price, 'অর্ডার ফাইল' AS category FROM order_file_services",
            "SELECT service_name AS title, status, price, 'কাস্টম সার্ভিস' AS category FROM custom_services",
            "SELECT COALESCE(name, orderName) AS title, IF(work_status=1, 'active', 'inactive') AS status, price, 'রেগুলার সার্ভিস' AS category FROM prices",
        ];
        foreach ($serviceQueries as $sql) {
            try { $result = $conn->query($sql); if ($result) { while ($row = $result->fetch_assoc()) { $rows[] = $row; } } } catch (Throwable $ignored) {}
        }
    } elseif ($section === 'users') {
        $result = $conn->query("SELECT id, name, email, mobile, balance, status FROM users ORDER BY id DESC LIMIT 300");
        if ($result) { while ($row = $result->fetch_assoc()) { $rows[] = $row; } }
    }
} catch (Throwable $e) { $error = 'তথ্য লোড করা যায়নি। ডাটাবেজ টেবিল/কলাম যাচাই করুন।'; }
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
require_once __DIR__ . '/includes/deposit_proof.php';
?>
<div class="space-y-4">
    <div class="rounded-2xl bg-white border border-slate-200 p-5 flex items-center justify-between gap-3">
        <div><h2 class="text-xl font-extrabold text-slate-800"><?= $e($labels[$section]) ?></h2><p class="text-xs text-slate-500 mt-1">শুধু দেখা যাবে—এখানে কোনো পরিবর্তন করা যাবে না।</p></div>
        <a href="moderator_panel.php" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700">প্যানেলে ফিরুন</a>
    </div>
    <?php if ($error !== ''): ?><div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-700 p-4 text-sm"><?= $e($error) ?></div><?php endif; ?>
    <div class="rounded-2xl bg-white border border-slate-200 overflow-x-auto">
        <?php if (!$rows && $error === ''): ?><p class="p-5 text-sm text-slate-500">দেখানোর মতো তথ্য নেই।</p><?php else: ?>
        <table class="w-full min-w-[760px] text-sm text-left">
            <thead class="bg-slate-50 text-slate-600"><tr>
                <?php if ($section === 'orders'): ?><th class="p-3">#</th><th class="p-3">ইউজার</th><th class="p-3">অর্ডার</th><th class="p-3">ইনপুট</th><th class="p-3">মূল্য</th><th class="p-3">স্ট্যাটাস</th><th class="p-3">সময়</th>
                <?php elseif ($section === 'history'): ?><th class="p-3">#</th><th class="p-3">ইমেইল</th><th class="p-3">সার্ভিস</th><th class="p-3">মূল্য</th><th class="p-3">আগের ব্যালেন্স</th><th class="p-3">পরের ব্যালেন্স</th><th class="p-3">সময়</th>
                <?php elseif ($section === 'recharge'): ?><th class="p-3">#</th><th class="p-3">ইউজার</th><th class="p-3">মেথড</th><th class="p-3">TrxID</th><th class="p-3">পরিমাণ</th><th class="p-3">প্রমাণ</th><th class="p-3">স্ট্যাটাস</th><th class="p-3">সময়</th>
                <?php elseif ($section === 'services'): ?><th class="p-3">ক্যাটাগরি</th><th class="p-3">সার্ভিস</th><th class="p-3">মূল্য</th><th class="p-3">স্ট্যাটাস</th>
                <?php else: ?><th class="p-3">#</th><th class="p-3">নাম</th><th class="p-3">ইমেইল</th><th class="p-3">মোবাইল</th><th class="p-3">ব্যালেন্স</th><th class="p-3">স্ট্যাটাস</th><?php endif; ?>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            <?php foreach ($rows as $row): ?>
                <tr>
                <?php if ($section === 'orders'): ?><td class="p-3">#<?= (int)$row['id'] ?></td><td class="p-3"><?= $e(($row['user_name'] ?? '') . ' ' . ($row['user_phone'] ?? '')) ?></td><td class="p-3"><?= $e($row['order_type'] ?? '') ?></td><td class="p-3 max-w-xs truncate"><?= $e($row['input_data'] ?? '') ?></td><td class="p-3">৳<?= $e($row['price'] ?? '') ?></td><td class="p-3"><?= $e($row['status'] ?? '') ?></td><td class="p-3"><?= $e($row['created_at'] ?? '') ?></td>
                <?php elseif ($section === 'history'): ?><td class="p-3">#<?= (int)$row['id'] ?></td><td class="p-3"><?= $e($row['email'] ?? '') ?></td><td class="p-3"><?= $e($row['order_type'] ?? '') ?></td><td class="p-3">৳<?= $e($row['price'] ?? '') ?></td><td class="p-3">৳<?= $e($row['current_balance'] ?? '') ?></td><td class="p-3">৳<?= $e($row['balance_after_cut'] ?? '') ?></td><td class="p-3"><?= $e($row['order_time'] ?? '') ?></td>
                <?php elseif ($section === 'recharge'): ?><td class="p-3">#<?= (int)$row['id'] ?></td><td class="p-3"><?= $e(($row['user_name'] ?? '') . ' ' . ($row['user_phone'] ?? '')) ?></td><td class="p-3"><?= $e($row['method'] ?? '') ?></td><td class="p-3"><?= $e($row['trx_id'] ?? '') ?></td><td class="p-3">৳<?= $e($row['amount'] ?? '') ?></td><td class="p-3"><?php $proof = sheba_deposit_proof_url($row['screenshot'] ?? ''); ?><?= $proof !== '' ? '<a target="_blank" rel="noopener" class="text-indigo-600 underline" href="' . $e($proof) . '">দেখুন</a>' : '—' ?></td><td class="p-3"><?= $e($row['status'] ?? '') ?></td><td class="p-3"><?= $e($row['created_at'] ?? '') ?></td>
                <?php elseif ($section === 'services'): ?><td class="p-3"><?= $e($row['category'] ?? '') ?></td><td class="p-3"><?= $e($row['title'] ?? '') ?></td><td class="p-3">৳<?= $e($row['price'] ?? '') ?></td><td class="p-3"><?= $e($row['status'] ?? '') ?></td>
                <?php else: ?><td class="p-3">#<?= (int)$row['id'] ?></td><td class="p-3"><?= $e($row['name'] ?? '') ?></td><td class="p-3"><?= $e($row['email'] ?? '') ?></td><td class="p-3"><?= $e($row['mobile'] ?? '') ?></td><td class="p-3">৳<?= $e($row['balance'] ?? '') ?></td><td class="p-3"><?= (int)($row['status'] ?? 0) === 1 ? 'সক্রিয়' : 'বন্ধ' ?></td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php include_once __DIR__ . '/footer.php'; ?>
