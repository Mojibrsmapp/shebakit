<?php
include_once __DIR__ . '/header.php';
$permissionLabels = [
    'orders' => 'অর্ডার',
    'history' => 'হিস্টোরি',
    'recharge' => 'রিচার্জ ও ডিপোজিট',
    'services' => 'সার্ভিস তালিকা',
    'users' => 'ইউজার তালিকা',
];
$record = sheba_admin_role_record($conn, (string)($_SESSION['admin_token'] ?? ''));
$permissions = $record['permissions'] ?? sheba_default_permissions();
$notice = (string)($_SESSION['admin_access_notice'] ?? '');
unset($_SESSION['admin_access_notice']);
?>
<div class="space-y-5">
    <section class="rounded-3xl bg-white p-6 border border-slate-200 shadow-sm">
        <span class="text-xs font-bold uppercase tracking-wide text-indigo-500">Moderator panel</span>
        <h2 class="mt-1 text-xl font-extrabold text-slate-800">স্বাগতম, <?= htmlspecialchars($admin_name, ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="mt-2 text-sm text-slate-500">অ্যাডমিন আপনার জন্য যে অংশগুলো চালু করেছেন সেগুলো এখানে দেখা যাবে। এই অনুমতিগুলো শুধু দেখার জন্য—কোনো তথ্য পরিবর্তন, approve/reject বা delete করা যাবে না।</p>
    </section>
    <?php if ($notice !== ''): ?><div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
    <?php foreach ($permissionLabels as $key => $label): if (empty($permissions[$key])) { continue; } ?>
        <a href="moderator_view.php?section=<?= urlencode($key) ?>" class="rounded-2xl bg-white border border-slate-200 p-5 hover:border-indigo-300 hover:shadow-md transition">
            <div class="font-extrabold text-slate-800"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-xs text-slate-500 mt-1">শুধু দেখার অনুমতি</div>
        </a>
    <?php endforeach; ?>
    <?php if (!array_filter($permissions)): ?><div class="sm:col-span-2 xl:col-span-3 rounded-2xl bg-slate-50 border border-slate-200 p-5 text-sm text-slate-600">এখনো কোনো অংশের অনুমতি চালু করা হয়নি। অ্যাডমিনের সঙ্গে যোগাযোগ করুন।</div><?php endif; ?>
    </section>
</div>
<?php include_once __DIR__ . '/footer.php'; ?>
