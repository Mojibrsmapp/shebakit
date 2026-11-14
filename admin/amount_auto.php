<?php
ob_start();
include_once("header.php");
require_once __DIR__ . "/includes/admin_roles.php";
sheba_require_admin_role($conn, ['admin']);

$filePaths = [
    'server_copy' => '../api_key/deduction_amount.txt',
    'server_copy_two' => '../api_key/v3_deduction_amount.txt',
    'auto_tin' => '../api_key/auto_tin.txt',
    'card_make' => '../api_key/card_make.txt',
    'recharge' => '../api_key/recharge.txt'
];
$labels = [
    'server_copy' => 'অটো সার্ভার কপি — API 1',
    'server_copy_two' => 'অটো সার্ভার কপি — API 2',
    'auto_tin' => 'অটো TIN',
    'card_make' => 'কার্ড তৈরি',
    'recharge' => 'রিচার্জ'
];
if (empty($_SESSION['auto_price_csrf'])) {
    $_SESSION['auto_price_csrf'] = bin2hex(random_bytes(32));
}
$notice = $_SESSION['auto_price_notice'] ?? null;
unset($_SESSION['auto_price_notice']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['auto_price_csrf'], (string)($_POST['csrf_token'] ?? ''))) {
        $notice = ['ok' => false, 'text' => 'নিরাপত্তা যাচাই ব্যর্থ হয়েছে। আবার চেষ্টা করুন।'];
    } else {
        $valid = true;
        $values = [];
        foreach ($filePaths as $name => $relativePath) {
            $raw = trim((string)($_POST[$name] ?? ''));
            if ($raw === '' || !is_numeric($raw) || !is_finite((float)$raw) || (float)$raw < 0 || (float)$raw > 1000000) {
                $valid = false;
                break;
            }
            $values[$name] = number_format((float)$raw, 2, '.', '');
        }
        if (!$valid) {
            $notice = ['ok' => false, 'text' => 'সব মূল্য ০ থেকে ১,০০০,০০০-এর মধ্যে সংখ্যায় দিন।'];
        } else {
            $written = [];
            foreach ($values as $name => $value) {
                $absolutePath = __DIR__ . '/' . $filePaths[$name];
                $written[$name] = file_put_contents($absolutePath, $value . "\n", LOCK_EX) !== false;
            }
            $allOk = !in_array(false, $written, true);
            $_SESSION['auto_price_notice'] = [
                'ok' => $allOk,
                'text' => $allOk ? 'সার্ভার কপি ও অন্যান্য অটো সার্ভিসের মূল্য আপডেট হয়েছে।' : 'কিছু মূল্য সংরক্ষণ করা যায়নি; সার্ভারের ফাইল অনুমতি যাচাই করুন।'
            ];
            if (ob_get_level() > 0) { ob_clean(); }
            header('Location: amount_auto.php', true, 303);
            exit;
        }
    }
}
$currentValues = [];
foreach ($filePaths as $name => $relativePath) {
    $file = __DIR__ . '/' . $relativePath;
    $currentValues[$name] = is_file($file) ? trim((string)file_get_contents($file)) : '';
}
$escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>

<div class="max-w-3xl mx-auto bg-white text-slate-800 p-5 md:p-7 rounded-3xl border border-slate-200 shadow-sm">
    <h2 class="text-xl font-extrabold mb-2">অটো সার্ভিসের মূল্য</h2>
    <p class="text-sm text-slate-500 mb-5">সার্ভার কপি পেজে API 1 বা API 2 নির্বাচন করলে এই মূল্য ইউজারের ব্যালেন্স থেকে কাটা হবে।</p>
    <?php if ($notice): ?><div class="mb-4 p-3 rounded-xl <?php echo !empty($notice['ok']) ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'; ?>"><?php echo $escape($notice['text']); ?></div><?php endif; ?>
    <form action="" method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['auto_price_csrf']); ?>">
        <?php foreach ($filePaths as $inputName => $filePath): ?>
            <label class="block text-sm font-bold" for="<?php echo $inputName; ?>"><?php echo $escape($labels[$inputName]); ?> (৳)
                <input class="form-control mt-1 block w-full rounded-xl border border-slate-300 p-3" type="number" min="0" max="1000000" step="0.01" id="<?php echo $inputName; ?>" name="<?php echo $inputName; ?>" value="<?php echo $escape($currentValues[$inputName]); ?>" required>
            </label>
        <?php endforeach; ?>
        <button type="submit" class="rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-5 py-3">মূল্য সংরক্ষণ করুন</button>
    </form>
</div>

<?php
include_once("footer.php");
?>