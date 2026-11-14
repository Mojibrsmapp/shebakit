<?php
ob_start();
include_once __DIR__ . '/header.php';
require_once __DIR__ . '/../includes/server_copy_settings.php';
sheba_require_admin_role($conn, ['admin']);

if (empty($_SESSION['server_copy_settings_csrf'])) { $_SESSION['server_copy_settings_csrf'] = bin2hex(random_bytes(32)); }
$notice = $_SESSION['server_copy_settings_notice'] ?? null;
unset($_SESSION['server_copy_settings_notice']);
$settings = sheba_server_copy_settings($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['server_copy_settings_csrf'], (string)($_POST['csrf_token'] ?? ''))) {
        $notice = ['ok' => false, 'text' => 'নিরাপত্তা যাচাই ব্যর্থ হয়েছে। পেজ রিফ্রেশ করে আবার চেষ্টা করুন।'];
    } else {
        $title = trim((string)($_POST['server_copy_title'] ?? ''));
        $groupUrl = trim((string)($_POST['server_copy_group_url'] ?? ''));
        $apiUrls = [];
        $urlError = '';
        foreach ([1, 2] as $version) {
            $field = 'api' . $version . '_url';
            $url = trim((string)($_POST[$field] ?? ''));
            if ($url !== '') {
                if (strlen($url) > 2000 || strpos($url, '{nid}') === false || strpos($url, '{dob}') === false) {
                    $urlError = "API {$version} URL-এ {nid} ও {dob} placeholder থাকা আবশ্যক।";
                    break;
                }
                $sample = str_replace(['{nid}', '{dob}', '{key}'], ['1234567890', '2000-01-01', 'samplekey'], $url);
                $parts = parse_url($sample);
                if (!filter_var($sample, FILTER_VALIDATE_URL) || strtolower((string)($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])) {
                    $urlError = "API {$version} URL অবশ্যই সঠিক HTTPS URL হতে হবে।";
                    break;
                }
            }
            $apiUrls[$version] = $url;
        }
        if ($title === '' || mb_strlen($title, 'UTF-8') > 100) {
            $notice = ['ok' => false, 'text' => 'পেজের শিরোনাম ১–১০০ অক্ষরের মধ্যে দিন।'];
        } elseif ($urlError === '' && isset($_POST['server_copy_group_enabled']) && ($groupUrl === '' || !filter_var($groupUrl, FILTER_VALIDATE_URL) || strtolower((string)(parse_url($groupUrl, PHP_URL_SCHEME) ?? '')) !== 'https')) {
            $notice = ['ok' => false, 'text' => 'Join Group লিংক সঠিক HTTPS URL হতে হবে।'];
        } elseif ($urlError !== '') {
            $notice = ['ok' => false, 'text' => $urlError];
        } else {
            $values = [
                'server_copy_enabled' => isset($_POST['server_copy_enabled']) ? '1' : '0',
                'server_copy_api1_enabled' => isset($_POST['server_copy_api1_enabled']) ? '1' : '0',
                'server_copy_api2_enabled' => isset($_POST['server_copy_api2_enabled']) ? '1' : '0',
                'server_copy_api1_url' => $apiUrls[1] ?? '',
                'server_copy_api2_url' => $apiUrls[2] ?? '',
                'server_copy_smart_paste_enabled' => isset($_POST['server_copy_smart_paste_enabled']) ? '1' : '0',
                'server_copy_download_list_enabled' => isset($_POST['server_copy_download_list_enabled']) ? '1' : '0',
                'server_copy_download_v1_enabled' => isset($_POST['server_copy_download_v1_enabled']) ? '1' : '0',
                'server_copy_download_v2_enabled' => isset($_POST['server_copy_download_v2_enabled']) ? '1' : '0',
                'server_copy_download_nid_enabled' => isset($_POST['server_copy_download_nid_enabled']) ? '1' : '0',
                'server_copy_group_enabled' => isset($_POST['server_copy_group_enabled']) ? '1' : '0',
                'server_copy_group_url' => $groupUrl,
                'server_copy_title' => $title,
            ];
            foreach ([1, 2] as $version) {
                $keyName = 'api' . $version . '_key';
                $clear = !empty($_POST['clear_' . $keyName]);
                $newKey = trim((string)($_POST[$keyName] ?? ''));
                if ($clear) { $values['server_copy_' . $keyName] = ''; }
                elseif ($newKey !== '') {
                    if (strlen($newKey) > 500) { $urlError = "API {$version} key অনেক বড়।"; break; }
                    $values['server_copy_' . $keyName] = $newKey;
                }
            }
            if ($urlError === '') {
                foreach ([1, 2] as $version) {
                    $keyName = 'api' . $version . '_key';
                    $configuredKey = isset($values['server_copy_' . $keyName])
                        ? $values['server_copy_' . $keyName]
                        : (string)($settings['server_copy_' . $keyName] ?? '');
                    if (strpos((string)($apiUrls[$version] ?? ''), '{key}') !== false && $configuredKey === '') {
                        $urlError = "API {$version} URL-এ {key} আছে, তাই API key দিন।";
                        break;
                    }
                }
            }
            if ($urlError !== '') {
                $notice = ['ok' => false, 'text' => $urlError];
            } else {
                $stmt = $conn->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
                if (!$stmt) {
                    $notice = ['ok' => false, 'text' => 'সেটিংস সংরক্ষণ করা যায়নি। site_settings টেবিল যাচাই করুন।'];
                } else {
                    $conn->begin_transaction();
                    $ok = true;
                    foreach ($values as $key => $value) {
                        $stmt->bind_param('ss', $key, $value);
                        if (!$stmt->execute()) { $ok = false; break; }
                    }
                    $stmt->close();
                    if ($ok) { $conn->commit(); $_SESSION['server_copy_settings_notice'] = ['ok' => true, 'text' => 'Server Copy পেজ ও API সেটিংস সংরক্ষণ হয়েছে।']; }
                    else { $conn->rollback(); $_SESSION['server_copy_settings_notice'] = ['ok' => false, 'text' => 'কিছু সেটিংস সংরক্ষণ করা যায়নি।']; }
                    if (ob_get_level() > 0) { ob_clean(); }
                    header('Location: server_copy_settings.php', true, 303);
                    exit;
                }
            }
        }
    }
}
$settings = sheba_server_copy_settings($conn);
$escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$keyConfigured = static function (string $name) use ($settings): bool { return trim((string)($settings[$name] ?? '')) !== ''; };
?>
<div class="max-w-5xl mx-auto space-y-5 text-slate-800">
    <section class="rounded-3xl border border-slate-200 bg-white p-5 md:p-6 shadow-sm">
        <h2 class="text-xl font-extrabold">Server Copy — API ও পেজ কন্ট্রোল</h2>
        <p class="mt-1 text-sm text-slate-500">এখান থেকে ইউজার পেজ চালু/বন্ধ, API নির্বাচন, API endpoint ও দৃশ্যমান ফিচার নিয়ন্ত্রণ করুন। মূল্য আগের pricing ব্যবস্থাতেই থাকবে; এখানে মূল্য পরিবর্তন করা হয় না।</p>
    </section>
    <?php if ($notice): ?><div class="rounded-xl border px-4 py-3 text-sm <?= !empty($notice['ok']) ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700' ?>"><?= $escape($notice['text'] ?? '') ?></div><?php endif; ?>
    <form method="post" class="space-y-5">
        <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['server_copy_settings_csrf']) ?>">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 md:p-6 shadow-sm space-y-4">
            <h3 class="font-extrabold">পেজ ও API চালু/বন্ধ</h3>
            <label class="flex items-center gap-3 rounded-xl bg-slate-50 p-3"><input type="checkbox" name="server_copy_enabled" value="1" <?= $settings['server_copy_enabled'] === '1' ? 'checked' : '' ?>><span><strong>Server Copy পেজ চালু</strong><small class="block text-slate-500">বন্ধ করলে ইউজাররা পেজ ও API ব্যবহার করতে পারবে না।</small></span></label>
            <div class="grid md:grid-cols-2 gap-3">
                <?php foreach ([1, 2] as $version): $apiKey = 'server_copy_api' . $version . '_enabled'; ?>
                <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"><input type="checkbox" name="<?= $apiKey ?>" value="1" <?= $settings[$apiKey] === '1' ? 'checked' : '' ?>><span><strong>API <?= $version ?> নির্বাচন চালু</strong><small class="block text-slate-500">ইউজার ফর্মে API <?= $version ?> দেখাবে।</small></span></label>
                <?php endforeach; ?>
            </div>
            <label class="block text-sm font-bold">Server Copy পেজের শিরোনাম<input class="mt-1 block w-full rounded-xl border border-slate-300 p-3" name="server_copy_title" maxlength="100" value="<?= $escape($settings['server_copy_title']) ?>" required></label>
        </section>
        <section class="rounded-3xl border border-slate-200 bg-white p-5 md:p-6 shadow-sm space-y-5">
            <div><h3 class="font-extrabold">API endpoint সেট করুন</h3><p class="text-xs text-slate-500 mt-1">URL template-এ <code>{nid}</code>, <code>{dob}</code> এবং প্রয়োজন হলে <code>{key}</code> লিখুন। URL অবশ্যই HTTPS হতে হবে। API key ফাঁকা রাখলে আগের সংরক্ষিত key অপরিবর্তিত থাকবে। Provider-এর JSON response-ও সংশ্লিষ্ট API 1/2-এর বর্তমান response format-এর সঙ্গে সামঞ্জস্যপূর্ণ হতে হবে।</p></div>
            <?php foreach ([1, 2] as $version): $urlName = 'server_copy_api' . $version . '_url'; $keyName = 'server_copy_api' . $version . '_key'; ?>
            <div class="rounded-2xl border border-slate-200 p-4 space-y-3">
                <div class="flex items-center justify-between gap-2"><h4 class="font-bold">API <?= $version ?></h4><span class="text-xs rounded-full px-2.5 py-1 <?= $settings[$urlName] !== '' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' ?>"><?= $settings[$urlName] !== '' ? 'Custom endpoint' : 'বর্তমান built-in endpoint' ?></span></div>
                <label class="block text-xs font-bold text-slate-600">URL template
                    <input class="mt-1 block w-full rounded-xl border border-slate-300 p-3 font-mono text-xs" type="text" inputmode="url" name="api<?= $version ?>_url" value="<?= $escape($settings[$urlName]) ?>" placeholder="https://provider.example/api?key={key}&amp;nid={nid}&amp;dob={dob}" autocomplete="off">
                </label>
                <label class="block text-xs font-bold text-slate-600">API key <span class="font-normal text-slate-400">(বর্তমান অবস্থা: <?= $keyConfigured($keyName) ? 'অ্যাডমিন-সেট করা key' : 'built-in key' ?>; নিরাপত্তার জন্য key দেখানো হয় না)</span>
                    <input class="mt-1 block w-full rounded-xl border border-slate-300 p-3" type="password" name="api<?= $version ?>_key" value="" placeholder="নতুন key দিলে বদলাবে; ফাঁকা রাখলে আগেরটাই থাকবে" autocomplete="new-password">
                </label>
                <?php if ($keyConfigured($keyName)): ?><label class="inline-flex items-center gap-2 text-xs text-rose-700"><input type="checkbox" name="clear_api<?= $version ?>_key" value="1"> সংরক্ষিত API key মুছুন</label><?php endif; ?>
                <p class="text-[11px] text-slate-500">URL ফাঁকা রাখলে বর্তমান built-in endpoint ব্যবহার হবে। <?php if ($version === 1): ?>Built-in: <code>https://api1.totthohub.com/api/v1?key={key}&amp;nid={nid}&amp;dob={dob}</code><?php else: ?>Built-in: <code>https://api.totthohub.com/servercopy[API key hidden]&amp;nid={nid}&amp;dob={dob}</code><?php endif; ?> NID ও জন্মতারিখ placeholder বসিয়ে সার্ভার থেকে request পাঠানো হবে।
            </div>
            <?php endforeach; ?>
        </section>
        <section class="rounded-3xl border border-slate-200 bg-white p-5 md:p-6 shadow-sm space-y-3">
            <h3 class="font-extrabold">ServerCopy.php-এর দৃশ্যমান ফিচার</h3>
            <?php
            $toggles = [
                'server_copy_smart_paste_enabled' => 'স্মার্ট অটো-ফিল বক্স',
                'server_copy_download_list_enabled' => 'ডাউনলোড লিস্ট দেখানো',
                'server_copy_download_v1_enabled' => 'ডাউনলোড লিস্টে V1 বাটন',
                'server_copy_download_v2_enabled' => 'ডাউনলোড লিস্টে V2 বাটন',
                'server_copy_download_nid_enabled' => 'ডাউনলোড লিস্টে NID বাটন',
                'server_copy_group_enabled' => 'Join Group বাটন দেখানো',
            ];
            foreach ($toggles as $key => $label): ?>
                <label class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 text-sm"><input type="checkbox" name="<?= $key ?>" value="1" <?= $settings[$key] === '1' ? 'checked' : '' ?>><span><?= $escape($label) ?></span></label>
            <?php endforeach; ?>
            <label class="block text-xs font-bold text-slate-600">Join Group URL
                <input class="mt-1 block w-full rounded-xl border border-slate-300 p-3" type="text" inputmode="url" name="server_copy_group_url" value="<?= $escape($settings['server_copy_group_url']) ?>" placeholder="https://chat.whatsapp.com/…">
            </label>
        </section>
        <button type="submit" class="rounded-xl bg-indigo-600 hover:bg-indigo-700 px-6 py-3 font-bold text-white">Server Copy সেটিংস সংরক্ষণ</button>
    </form>
</div>
<?php include_once __DIR__ . '/footer.php'; ?>
