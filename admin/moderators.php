<?php
ob_start();
include_once __DIR__ . '/header.php';
if (($admin_role ?? 'admin') !== 'admin') { http_response_code(403); exit('এই পেজটি শুধু পূর্ণ অ্যাডমিনের জন্য।'); }
$schemaReady = sheba_ensure_admin_role_column($conn);
if (empty($_SESSION['moderator_csrf'])) { $_SESSION['moderator_csrf'] = bin2hex(random_bytes(32)); }

// Live lookup in the create-moderator form helps distinguish existing site users.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['lookup_user']) && $schemaReady) {
    $lookupEmail = strtolower(trim((string)$_GET['lookup_user']));
    $lookupResponse = ['found' => false];
    if (filter_var($lookupEmail, FILTER_VALIDATE_EMAIL)) {
        $lookupStmt = $conn->prepare('SELECT id, name, email, status FROM users WHERE email = ? LIMIT 1');
        if ($lookupStmt) {
            $lookupStmt->bind_param('s', $lookupEmail);
            $lookupStmt->execute();
            $lookupUser = $lookupStmt->get_result()->fetch_assoc();
            $lookupStmt->close();
            if ($lookupUser) {
                $roleStmt = $conn->prepare('SELECT `role` FROM admin_user WHERE email = ? LIMIT 1');
                $currentRole = '';
                if ($roleStmt) {
                    $roleStmt->bind_param('s', $lookupEmail);
                    $roleStmt->execute();
                    $roleRow = $roleStmt->get_result()->fetch_assoc();
                    $currentRole = strtolower((string)($roleRow['role'] ?? ''));
                    $roleStmt->close();
                }
                $lookupResponse = [
                    'found' => true,
                    'id' => (int)$lookupUser['id'],
                    'name' => (string)($lookupUser['name'] ?? ''),
                    'email' => (string)$lookupUser['email'],
                    'status' => (int)($lookupUser['status'] ?? 0),
                    'role' => $currentRole,
                ];
            }
        }
    }
    if (ob_get_level() > 0) { ob_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($lookupResponse);
    exit;
}

$permissionLabels = [
    'orders' => 'অর্ডার দেখা',
    'history' => 'হিস্টোরি দেখা',
    'recharge' => 'রিচার্জ/ডিপোজিট দেখা',
    'services' => 'সার্ভিস তালিকা দেখা',
    'users' => 'ইউজার তালিকা দেখা',
];
$flash = $_SESSION['moderator_flash'] ?? null;
unset($_SESSION['moderator_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $schemaReady) {
    $validCsrf = hash_equals($_SESSION['moderator_csrf'], (string)($_POST['csrf_token'] ?? ''));
    $action = (string)($_POST['action'] ?? '');
    if (!$validCsrf) {
        $_SESSION['moderator_flash'] = ['type' => 'error', 'message' => 'নিরাপত্তা যাচাই ব্যর্থ হয়েছে। আবার চেষ্টা করুন।'];
    } elseif ($action === 'create' || $action === 'permissions') {
        $permissions = sheba_default_permissions();
        foreach (array_keys($permissions) as $key) { $permissions[$key] = isset($_POST['permissions'][$key]); }
        if ($action === 'create') {
            $name = trim((string)($_POST['name'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['moderator_flash'] = ['type' => 'error', 'message' => 'নাম ও সঠিক ইমেইল দিন।'];
            } else {
                $check = $conn->prepare('SELECT id FROM admin_user WHERE email = ? LIMIT 1');
                $check->bind_param('s', $email);
                $check->execute();
                $exists = (bool)$check->get_result()->fetch_assoc();
                $check->close();
                if ($exists) {
                    $_SESSION['moderator_flash'] = ['type' => 'error', 'message' => 'এই ইমেইল দিয়ে অ্যাডমিন অ্যাকাউন্ট আগে থেকেই আছে।'];
                } else {
                    $oneTimePassword = rtrim(strtr(base64_encode(random_bytes(12)), '+/', '-_'), '=');
                    $moderatorToken = bin2hex(random_bytes(32));
                    $passwordHash = md5($oneTimePassword); // Legacy admin login compatibility.
                    $permissionJson = json_encode($permissions);
                    $status = 1;
                    $stmt = $conn->prepare("INSERT INTO admin_user (name, email, status, password, balance, token, `role`, moderator_permissions) VALUES (?, ?, ?, ?, 0, ?, 'moderator', ?)");
                    if ($stmt) {
                        $stmt->bind_param('ssisss', $name, $email, $status, $passwordHash, $moderatorToken, $permissionJson);
                        if ($stmt->execute()) {
                            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                            $loginUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/admin/index.php?admin_token=' . rawurlencode($moderatorToken);
                            $_SESSION['moderator_flash'] = ['type' => 'success', 'message' => 'মডারেটর অ্যাকাউন্ট তৈরি হয়েছে। লগইন তথ্যটি নিরাপদে কপি করে দিন।', 'email' => $email, 'password' => $oneTimePassword, 'login_url' => $loginUrl];
                        } else {
                            $_SESSION['moderator_flash'] = ['type' => 'error', 'message' => 'অ্যাকাউন্ট তৈরি করা যায়নি।'];
                        }
                        $stmt->close();
                    } else {
                        $_SESSION['moderator_flash'] = ['type' => 'error', 'message' => 'মডারেটর ডাটাবেজ কলাম তৈরি হয়নি। admin_user-এ role ও moderator_permissions কলাম যোগ করুন।'];
                    }
                }
            }
        } else {
            $moderatorId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$moderatorId) {
                $_SESSION['moderator_flash'] = ['type' => 'error', 'message' => 'মডারেটর অ্যাকাউন্টটি পাওয়া যায়নি।'];
            } else {
                $permissionJson = json_encode($permissions);
                $stmt = $conn->prepare("UPDATE admin_user SET moderator_permissions = ? WHERE id = ? AND `role` = 'moderator'");
                $stmt->bind_param('si', $permissionJson, $moderatorId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['moderator_flash'] = ['type' => 'success', 'message' => 'মডারেটরের দেখার অনুমতি আপডেট হয়েছে।'];
            }
        }
    } elseif ($action === 'assign_existing') {
        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        $targetRole = strtolower(trim((string)($_POST['target_role'] ?? '')));
        if (!$userId || !in_array($targetRole, ['admin', 'moderator'], true)) {
            $_SESSION['moderator_flash'] = ['type' => 'error', 'message' => 'সঠিক ইউজার ও admin/moderator role নির্বাচন করুন।'];
        } else {
            $userStmt = $conn->prepare('SELECT id, name, email, status, password FROM users WHERE id = ? LIMIT 1');
            $userStmt->bind_param('i', $userId);
            $userStmt->execute();
            $siteUser = $userStmt->get_result()->fetch_assoc();
            $userStmt->close();
            if (!$siteUser) {
                $_SESSION['moderator_flash'] = ['type' => 'error', 'message' => 'সাইট ইউজার অ্যাকাউন্টটি পাওয়া যায়নি।'];
            } else {
                $email = strtolower(trim((string)$siteUser['email']));
                $accountStmt = $conn->prepare('SELECT id, `role`, token, moderator_permissions FROM admin_user WHERE email = ? LIMIT 1');
                $accountStmt->bind_param('s', $email);
                $accountStmt->execute();
                $existingAccount = $accountStmt->get_result()->fetch_assoc();
                $accountStmt->close();
                $isAdminDemotion = $existingAccount && strtolower((string)($existingAccount['role'] ?? '')) === 'admin' && $targetRole === 'moderator';
                $isSelfDemotion = $isAdminDemotion && (int)$existingAccount['id'] === (int)($admin_id ?? 0);
                $lastAdminDemotion = false;
                if ($isAdminDemotion) {
                    $adminCountResult = $conn->query("SELECT COUNT(*) AS total FROM admin_user WHERE `role` = 'admin' AND status = 1");
                    $lastAdminDemotion = !$adminCountResult || (int)($adminCountResult->fetch_assoc()['total'] ?? 0) <= 1;
                    if ($adminCountResult) { $adminCountResult->free(); }
                }
                if ($isSelfDemotion || $lastAdminDemotion) {
                    $_SESSION['moderator_flash'] = ['type' => 'error', 'message' => $isSelfDemotion ? 'নিজের admin role নিজে থেকে moderator করা যাবে না।' : 'এটি সর্বশেষ সক্রিয় admin; অন্তত একজন admin রাখতে role পরিবর্তন করা যাবে না।'];
                } else {
                    $token = trim((string)($existingAccount['token'] ?? ''));
                    if ($token === '') { $token = bin2hex(random_bytes(32)); }
                    if ($targetRole === 'moderator') {
                        $currentPermissions = json_decode((string)($existingAccount['moderator_permissions'] ?? ''), true);
                        $permissions = (($existingAccount['role'] ?? '') === 'moderator' && is_array($currentPermissions))
                            ? $currentPermissions
                            : sheba_default_permissions();
                    } else {
                        $permissions = sheba_default_permissions();
                    }
                    $permissionJson = json_encode($permissions);
                    if ($existingAccount) {
                        $accountId = (int)$existingAccount['id'];
                        $update = $conn->prepare('UPDATE admin_user SET `role` = ?, moderator_permissions = ?, token = ? WHERE id = ?');
                        $update->bind_param('sssi', $targetRole, $permissionJson, $token, $accountId);
                        $ok = $update->execute();
                        $update->close();
                    } else {
                        $name = trim((string)($siteUser['name'] ?? '')) ?: $email;
                        $status = (int)($siteUser['status'] ?? 0) === 1 ? 1 : 0;
                        $passwordHash = (string)$siteUser['password'];
                        $insert = $conn->prepare('INSERT INTO admin_user (name, email, status, password, balance, token, `role`, moderator_permissions) VALUES (?, ?, ?, ?, 0, ?, ?, ?)');
                        $insert->bind_param('ssissss', $name, $email, $status, $passwordHash, $token, $targetRole, $permissionJson);
                        $ok = $insert->execute();
                        $insert->close();
                    }
                    if ($ok) {
                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $loginUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/admin/index.php?admin_token=' . rawurlencode($token);
                        $_SESSION['moderator_flash'] = ['type' => 'success', 'message' => 'বর্তমান ইউজারের admin panel role আপডেট হয়েছে। বিদ্যমান user email/password দিয়েই লগইন করবেন।', 'email' => $email, 'login_url' => $loginUrl];
                    } else {
                        $_SESSION['moderator_flash'] = ['type' => 'error', 'message' => 'ইউজারের admin panel role আপডেট করা যায়নি।'];
                    }
                }
            }
        }
    } elseif ($action === 'toggle') {
        $moderatorId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $newStatus = ((int)($_POST['status'] ?? 0) === 1) ? 1 : 0;
        if (!$moderatorId) {
            $_SESSION['moderator_flash'] = ['type' => 'error', 'message' => 'মডারেটর অ্যাকাউন্টটি পাওয়া যায়নি।'];
        } else {
            $stmt = $conn->prepare("UPDATE admin_user SET status = ? WHERE id = ? AND `role` = 'moderator'");
            if ($stmt) {
                $stmt->bind_param('ii', $newStatus, $moderatorId);
                $ok = $stmt->execute();
                $stmt->close();
            } else { $ok = false; }
            $_SESSION['moderator_flash'] = ['type' => $ok ? 'success' : 'error', 'message' => $newStatus ? 'মডারেটর পুনরায় সক্রিয় করা হয়েছে।' : 'মডারেটরের প্রবেশ বন্ধ করা হয়েছে।'];
        }
    } elseif ($action === 'delete') {
        $moderatorId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$moderatorId) {
            $_SESSION['moderator_flash'] = ['type' => 'error', 'message' => 'মডারেটর অ্যাকাউন্টটি পাওয়া যায়নি।'];
        } else {
            $stmt = $conn->prepare("DELETE FROM admin_user WHERE id = ? AND `role` = 'moderator'");
            if ($stmt) {
                $stmt->bind_param('i', $moderatorId);
                $ok = $stmt->execute() && $stmt->affected_rows > 0;
                $stmt->close();
            } else { $ok = false; }
            $_SESSION['moderator_flash'] = [
                'type' => $ok ? 'success' : 'error',
                'message' => $ok ? 'মডারেটরের admin panel access মুছে দেওয়া হয়েছে; সাধারণ user account অক্ষত আছে।' : 'মডারেটর অ্যাকাউন্টটি মুছে ফেলা যায়নি।',
            ];
        }
    }
    if (ob_get_level() > 0) { ob_clean(); }
    header('Location: moderators.php', true, 303);
    exit;
}

$existingUsers = [];
$userSearch = trim((string)($_GET['user_q'] ?? ''));
if ($schemaReady) {
    if ($userSearch !== '') {
        $userStmt = $conn->prepare("SELECT u.id, u.name, u.email, u.status, a.`role` AS admin_role FROM users u LEFT JOIN admin_user a ON a.email = u.email WHERE u.email LIKE ? OR u.name LIKE ? ORDER BY u.id DESC LIMIT 100");
        $like = '%' . $userSearch . '%';
        $userStmt->bind_param('ss', $like, $like);
    } else {
        $userStmt = $conn->prepare("SELECT u.id, u.name, u.email, u.status, a.`role` AS admin_role FROM users u LEFT JOIN admin_user a ON a.email = u.email ORDER BY u.id DESC LIMIT 50");
    }
    if ($userStmt) {
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        while ($row = $userResult->fetch_assoc()) { $existingUsers[] = $row; }
        $userStmt->close();
    }
}

$moderators = [];
$result = $schemaReady ? $conn->query("SELECT id, name, email, status, moderator_permissions FROM admin_user WHERE `role` = 'moderator' ORDER BY id DESC") : false;
if (!$schemaReady && !$flash) { $flash = ['type' => 'error', 'message' => 'মডারেটর ফিচারের ডাটাবেজ কলাম যোগ হয়নি। SQL migration চালান: admin/migrations/001_admin_role.sql']; }
if ($result) { while ($row = $result->fetch_assoc()) { $moderators[] = $row; } $result->free(); }
$escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$renderPermissions = static function (array $permissions, callable $escape) use ($permissionLabels) {
    foreach ($permissionLabels as $key => $label) {
        $checked = !empty($permissions[$key]) ? 'checked' : '';
        echo '<label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-2 text-xs text-slate-700"><input type="checkbox" name="permissions[' . $key . ']" value="1" ' . $checked . '> ' . $escape($label) . '</label>';
    }
};
?>
<style>
    .moderator-users-table { color: #0f172a !important; background: #ffffff !important; }
    .moderator-users-table thead th { color: #0f172a !important; background: #e2e8f0 !important; border-color: #cbd5e1 !important; }
    .moderator-users-table tbody td { color: #334155 !important; background: #ffffff !important; border-color: #cbd5e1 !important; }
    .moderator-users-table tbody tr:nth-child(even) td { background: #f1f5f9 !important; }
    .moderator-users-table tbody td .text-slate-400,
    .moderator-users-table tbody td .text-slate-500 { color: #475569 !important; }
    .moderator-users-table select,
    .moderator-users-table select option { color: #0f172a !important; background-color: #ffffff !important; }
    .moderator-user-search input { color: #0f172a !important; background-color: #ffffff !important; border-color: #94a3b8 !important; }
    .moderator-user-search input::placeholder { color: #64748b !important; opacity: 1; }
</style>
<div class="space-y-5">
    <section class="bg-white p-5 md:p-6 rounded-3xl border border-slate-200 shadow-sm">
        <h2 class="text-xl font-extrabold text-slate-800">মডারেটর প্যানেল</h2>
        <p class="text-sm text-slate-500 mt-1">প্রতিটি অনুমতি শুধু দেখার জন্য। অর্ডার/রিচার্জ পরিবর্তন, ব্যালেন্স, মূল্য, সার্ভিস কন্ট্রোল ও সাইট সেটিংস মডারেটরের জন্য বন্ধ থাকবে।</p>
    </section>
    <?php if ($flash): ?><div class="rounded-xl border px-4 py-3 text-sm <?= ($flash['type'] ?? '') === 'error' ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700' ?>">
        <?= $escape($flash['message'] ?? '') ?>
        <?php if (!empty($flash['email'])): ?><div class="mt-3 grid gap-2 font-mono text-xs break-all"><div>ইমেইল: <strong><?= $escape($flash['email']) ?></strong></div><?php if (!empty($flash['password'])): ?><div>একবারের পাসওয়ার্ড: <strong><?= $escape($flash['password']) ?></strong></div><?php else: ?><div>বিদ্যমান user password ব্যবহার হবে।</div><?php endif; ?><div>লগইন লিংক: <a class="underline" href="<?= $escape($flash['login_url'] ?? '') ?>"><?= $escape($flash['login_url'] ?? '') ?></a></div></div><?php endif; ?>
    </div><?php endif; ?>
    <section class="bg-white p-5 md:p-6 rounded-3xl border border-slate-200 shadow-sm">
        <h3 class="font-bold text-slate-800 mb-4">নতুন মডারেটর যোগ করুন ও দেখার অনুমতি দিন</h3>
        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['moderator_csrf']) ?>"><input type="hidden" name="action" value="create">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3"><label class="text-sm text-slate-600">নাম<input id="newModeratorName" class="mt-1 block w-full rounded-xl border border-slate-300 p-3" name="name" maxlength="255" required></label><label class="text-sm text-slate-600">ইমেইল<input id="newModeratorEmail" class="mt-1 block w-full rounded-xl border border-slate-300 p-3" type="email" name="email" maxlength="255" required></label></div>
            <div id="existingUserLookup" class="text-xs" aria-live="polite"></div>
            <div class="flex flex-wrap gap-2"><?php $createPermissions = array_fill_keys(array_keys(sheba_default_permissions()), true); $renderPermissions($createPermissions, $escape); ?></div>
            <button class="rounded-xl bg-indigo-600 text-white font-bold px-5 py-3 hover:bg-indigo-700" type="submit">মডারেটর তৈরি করুন</button>
        </form>
    </section>
    <section class="bg-white p-5 md:p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
        <div>
            <h3 class="font-bold text-slate-800">বর্তমান ইউজারকে Admin / Moderator করুন</h3>
            <p class="text-xs text-slate-500 mt-1">সাইটের বিদ্যমান ইউজারের email ও password admin panel-এও ব্যবহার হবে। নতুন Moderator-এর কোনো বিভাগ শুরুতে অনুমোদিত থাকবে না; নিচের Moderator অনুমতি অংশ থেকে Admin প্রয়োজনীয় view-only বিভাগ চালু করবেন।</p>
        </div>
        <form method="get" class="moderator-user-search flex flex-wrap gap-2">
            <input class="min-w-[240px] flex-1 rounded-xl border border-slate-300 p-3" type="search" name="user_q" value="<?= $escape($userSearch) ?>" placeholder="নাম বা ইমেইল দিয়ে খুঁজুন">
            <button class="rounded-xl bg-slate-800 px-4 py-2 font-bold text-white" type="submit">খুঁজুন</button>
        </form>
        <div class="overflow-x-auto"><table class="moderator-users-table w-full min-w-[720px] text-sm text-left">
            <thead><tr class="border-b text-slate-500"><th class="p-3">ইউজার</th><th class="p-3">ইমেইল</th><th class="p-3">বর্তমান Role</th><th class="p-3">অ্যাকশন</th></tr></thead>
            <tbody>
            <?php if (!$existingUsers): ?><tr><td class="p-3 text-slate-500" colspan="4">ইউজার পাওয়া যায়নি। নাম বা ইমেইল দিয়ে খুঁজুন।</td></tr><?php endif; ?>
            <?php foreach ($existingUsers as $siteUser): $assignedRole = strtolower((string)($siteUser['admin_role'] ?? '')); ?>
                <tr class="border-b border-slate-100"><td class="p-3"><?= $escape($siteUser['name'] ?? '') ?> <span class="text-xs text-slate-400">#<?= (int)$siteUser['id'] ?></span></td><td class="p-3"><?= $escape($siteUser['email']) ?></td><td class="p-3"><?= in_array($assignedRole, ['admin', 'moderator'], true) ? $escape($assignedRole) : 'সাধারণ ইউজার' ?> · <?= (int)$siteUser['status'] === 1 ? 'সক্রিয়' : 'বন্ধ' ?></td>
                    <td class="p-3"><form method="post" class="flex items-center gap-2"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['moderator_csrf']) ?>"><input type="hidden" name="action" value="assign_existing"><input type="hidden" name="user_id" value="<?= (int)$siteUser['id'] ?>"><select name="target_role" class="rounded-lg border border-slate-300 p-2"><option value="admin" <?= $assignedRole === 'admin' ? 'selected' : '' ?>>Admin</option><option value="moderator" <?= $assignedRole !== 'admin' ? 'selected' : '' ?>>Moderator</option></select><button class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white" type="submit">Role দিন/আপডেট</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </section>
    <section class="bg-white p-5 md:p-6 rounded-3xl border border-slate-200 shadow-sm overflow-x-auto">
        <h3 class="font-bold text-slate-800 mb-4">মডারেটর অ্যাকাউন্ট ও অনুমতি</h3>
        <?php if (!$moderators): ?><p class="text-sm text-slate-500">এখনো কোনো মডারেটর তৈরি হয়নি।</p><?php endif; ?>
        <div class="space-y-4">
        <?php foreach ($moderators as $moderator): $permissions = json_decode((string)($moderator['moderator_permissions'] ?? ''), true); if (!is_array($permissions)) { $permissions = sheba_default_permissions(); } ?>
            <article class="rounded-2xl border border-slate-200 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3"><div><strong class="text-slate-800"><?= $escape($moderator['name']) ?></strong><div class="text-xs text-slate-500"><?= $escape($moderator['email']) ?> · <?= (int)$moderator['status'] === 1 ? 'সক্রিয়' : 'বন্ধ' ?></div></div>
                    <div class="flex items-center gap-2">
                        <form method="post"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['moderator_csrf']) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$moderator['id'] ?>"><input type="hidden" name="status" value="<?= (int)$moderator['status'] === 1 ? 0 : 1 ?>"><button class="rounded-lg px-3 py-2 font-bold <?= (int)$moderator['status'] === 1 ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' ?>" type="submit"><?= (int)$moderator['status'] === 1 ? 'বন্ধ করুন' : 'সক্রিয় করুন' ?></button></form>
                        <form method="post" onsubmit="return confirm('মডারেটরের admin panel access মুছে দিতে চান? সাধারণ user account মুছবে না।');"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['moderator_csrf']) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$moderator['id'] ?>"><button class="rounded-lg bg-rose-600 px-3 py-2 font-bold text-white hover:bg-rose-700" type="submit">মুছুন</button></form>
                    </div>
                </div>
                <form method="post" class="space-y-3"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['moderator_csrf']) ?>"><input type="hidden" name="action" value="permissions"><input type="hidden" name="id" value="<?= (int)$moderator['id'] ?>"><div class="flex flex-wrap gap-2"><?php $renderPermissions($permissions, $escape); ?></div><button class="rounded-lg bg-slate-800 text-white text-xs font-bold px-4 py-2" type="submit">অনুমতি সংরক্ষণ</button></form>
            </article>
        <?php endforeach; ?>
        </div>
    </section>
</div>
<script>
(() => {
    const emailInput = document.getElementById('newModeratorEmail');
    const nameInput = document.getElementById('newModeratorName');
    const lookupBox = document.getElementById('existingUserLookup');
    if (!emailInput || !lookupBox) return;

    const csrfToken = <?= json_encode((string)$_SESSION['moderator_csrf']) ?>;
    let timer = null;
    let controller = null;
    const showMessage = (text, color = 'text-slate-500') => {
        lookupBox.replaceChildren();
        const message = document.createElement('p');
        message.className = color;
        message.textContent = text;
        lookupBox.appendChild(message);
    };

    emailInput.addEventListener('input', () => {
        clearTimeout(timer);
        if (controller) controller.abort();
        const email = emailInput.value.trim();
        lookupBox.replaceChildren();
        if (!email || !emailInput.checkValidity()) return;

        timer = setTimeout(async () => {
            controller = new AbortController();
            showMessage('এই ইমেইল দিয়ে ইউজার খোঁজা হচ্ছে…');
            try {
                const response = await fetch('moderators.php?lookup_user=' + encodeURIComponent(email), {
                    credentials: 'same-origin',
                    signal: controller.signal,
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('lookup failed');
                const user = await response.json();
                if (emailInput.value.trim().toLowerCase() !== email.toLowerCase()) return;
                lookupBox.replaceChildren();
                if (!user.found) {
                    showMessage('এই ইমেইলে কোনো বর্তমান সাইট ইউজার পাওয়া যায়নি। নতুন মডারেটর তৈরি করতে নাম পূরণ করে নিচের বোতাম চাপুন।', 'text-amber-700');
                    return;
                }

                if (nameInput && !nameInput.value.trim()) nameInput.value = user.name || '';
                const details = document.createElement('p');
                details.className = 'mb-2 font-semibold text-emerald-700';
                details.textContent = 'বর্তমান ইউজার পাওয়া গেছে: ' + (user.name || user.email) + ' · বর্তমান Role: ' + (user.role || 'সাধারণ ইউজার');
                lookupBox.appendChild(details);

                const form = document.createElement('form');
                form.method = 'post';
                form.className = 'flex flex-wrap items-center gap-2 rounded-xl bg-indigo-50 p-3';
                const hiddenFields = { csrf_token: csrfToken, action: 'assign_existing', user_id: String(user.id) };
                Object.entries(hiddenFields).forEach(([key, value]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                });
                const roleLabel = document.createElement('label');
                roleLabel.className = 'text-slate-700';
                roleLabel.textContent = 'এই ইউজারকে করুন: ';
                const roleSelect = document.createElement('select');
                roleSelect.name = 'target_role';
                roleSelect.className = 'ml-1 rounded-lg border border-slate-300 bg-white p-2';
                [['moderator', 'Moderator'], ['admin', 'Admin']].forEach(([value, label]) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = label;
                    if (user.role === value || (!user.role && value === 'moderator')) option.selected = true;
                    roleSelect.appendChild(option);
                });
                roleLabel.appendChild(roleSelect);
                form.appendChild(roleLabel);
                const button = document.createElement('button');
                button.type = 'submit';
                button.className = 'rounded-lg bg-indigo-600 px-4 py-2 font-bold text-white hover:bg-indigo-700';
                button.textContent = 'Role আপডেট করুন';
                form.appendChild(button);
                lookupBox.appendChild(form);
                const note = document.createElement('p');
                note.className = 'mt-2 text-slate-500';
                note.textContent = 'বিদ্যমান ইউজারের পাসওয়ার্ডই থাকবে; নতুন অ্যাকাউন্ট তৈরি হবে না।';
                lookupBox.appendChild(note);
            } catch (error) {
                if (error.name !== 'AbortError') showMessage('ইউজার খোঁজা যায়নি। আবার চেষ্টা করুন।', 'text-rose-700');
            }
        }, 350);
    });
})();
</script>
<?php include_once __DIR__ . '/footer.php'; ?>
