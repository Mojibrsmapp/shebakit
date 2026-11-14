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
require_once __DIR__ . '/includes/service_catalog.php';

$services = [
    ['title'=>'ফ্রি বায়োমেট্রিক','category'=>'কমপ্লিমেন্টারি সার্ভিস','url'=>'free_service.php?type=biometric'],
    ['title'=>'ফ্রি সার্ভার কপি','category'=>'কমপ্লিমেন্টারি সার্ভিস','url'=>'free_service.php?type=server_copy'],
    ['title'=>'ফ্রি মেক কপি','category'=>'কমপ্লিমেন্টারি সার্ভিস','url'=>'free_service.php?type=make'],
    ['title'=>'অটো সার্ভার কপি','category'=>'সার্ভার ও এনআইডি','url'=>'serverCopy.php'],
    ['title'=>'১০/১২/১৭ সাইন কপি','category'=>'সার্ভার ও এনআইডি','url'=>'sign_order.php?type=10_12_17'],
    ['title'=>'১৩ ডিজিট / ফরম সাইন','category'=>'সার্ভার ও এনআইডি','url'=>'sign_order.php?type=13_form'],
    ['title'=>'Auto NID মেক','category'=>'সার্ভার ও এনআইডি','url'=>'auto_service.php?type=nid'],
    ['title'=>'User Pass রিসেট','category'=>'সার্ভার ও এনআইডি','url'=>'auto_service.php?type=password'],
    ['title'=>'Ministry Copy','category'=>'সার্ভার ও এনআইডি','url'=>'auto_service.php?type=ministry'],
    ['title'=>'রবি / এয়ারটেল তথ্য','category'=>'সিম ও মোবাইল রেকর্ডস','url'=>'numbertobio.php'],
    ['title'=>'বাংলালিংক / জিপি তথ্য','category'=>'সিম ও মোবাইল রেকর্ডস','url'=>'numbertobio.php'],
    ['title'=>'কল হিস্টোরি','category'=>'সিম ও মোবাইল রেকর্ডস','url'=>'call_sms_list.php'],
    ['title'=>'এসএমএস হিস্টোরি','category'=>'সিম ও মোবাইল রেকর্ডস','url'=>'call_sms_list.php'],
    ['title'=>'সিম অন লোকেশন','category'=>'লোকেশন সার্ভিস','url'=>'location_service.php?type=sim_on_radio'],
    ['title'=>'সিম অফ লোকেশন','category'=>'লোকেশন সার্ভিস','url'=>'location_service.php?type=sim_off_tower'],
    ['title'=>'সিম অন পিনপয়েন্ট','category'=>'লোকেশন সার্ভিস','url'=>'location_service.php?type=sim_on_pinpoint'],
    ['title'=>'সিম অফ পিনপয়েন্ট','category'=>'লোকেশন সার্ভিস','url'=>'location_service.php?type=sim_off_pinpoint'],
    ['title'=>'CID সিম অন','category'=>'লোকেশন সার্ভিস','url'=>'location_service.php?type=cid_sim_on'],
    ['title'=>'CID সিম অফ','category'=>'লোকেশন সার্ভিস','url'=>'location_service.php?type=cid_sim_off'],
    ['title'=>'অটো TIN সার্টিফিকেট','category'=>'ইউটিলিটি','url'=>'auto_tin.php'],
    ['title'=>'IMEI সার্ভিস','category'=>'ইউটিলিটি','url'=>'imei_service.php'],
    ['title'=>'MFS NID তথ্য','category'=>'ইউটিলিটি','url'=>'mfs_service.php'],
    ['title'=>'সাইন কপি','category'=>'ইউটিলিটি','url'=>'sign_order.php'],
];
foreach (shebakit_service_catalog($conn) as $item) {
    $services[] = $item;
}
shebakit_service_sort($services);
$escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="bn" data-theme="light">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>সকল সেবা - ShebaKit</title>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{--bg:#f3f8ff;--card:#fff;--ink:#123b70;--muted:#52657d;--blue:#0866d9;--border:#dce8f5}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font-family:'Hind Siliguri',sans-serif;padding-bottom:85px}.top{position:sticky;top:0;background:var(--card);border-bottom:1px solid var(--border);padding:14px 18px;z-index:2;display:flex;align-items:center;justify-content:space-between}.top a{color:var(--blue);font-weight:700;text-decoration:none}.wrap{max-width:1000px;margin:auto;padding:22px 16px}.heading{display:flex;justify-content:space-between;align-items:end;gap:10px;margin-bottom:16px}.heading h1{font-size:24px;margin:0}.count{color:var(--muted);font-size:13px}.search{width:100%;padding:14px 16px;border:1px solid var(--border);border-radius:14px;background:var(--card);font:inherit;margin:4px 0 18px}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:12px}.item{min-height:112px;display:flex;align-items:center;gap:12px;padding:14px;border-radius:16px;background:var(--card);border:1px solid var(--border);box-shadow:0 4px 14px #123b700b;color:inherit;text-decoration:none;transition:.2s}.item:hover{transform:translateY(-2px);border-color:#86b7fe}.icon{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:#eaf3ff;color:var(--blue);flex:none}.service-image{width:42px;height:42px;object-fit:cover;border-radius:12px;flex:none}.title{font-weight:700;line-height:1.35}.meta{color:var(--muted);font-size:12px;margin-top:4px}.no-results{display:none;padding:28px;text-align:center;color:var(--muted)}.bottom{position:fixed;bottom:0;left:0;right:0;z-index:5;display:flex;justify-content:space-around;background:var(--card);border-top:1px solid var(--border);padding:9px 4px max(9px,env(safe-area-inset-bottom));box-shadow:0 -4px 18px #00000010}.bottom a{display:flex;flex-direction:column;align-items:center;gap:2px;color:var(--muted);font-size:11px;text-decoration:none;font-weight:600}.bottom a.active{color:var(--blue)}.bottom i{font-size:16px}@media(min-width:769px){.bottom{display:none}body{padding-bottom:24px}}
</style>
</head>
<body>
<header class="top"><a href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> হোম</a><span>ব্যালেন্স: ৳<?= number_format((float)$user['balance'], 2) ?></span></header>
<main class="wrap"><div class="heading"><h1><i class="fa-solid fa-layer-group"></i> সকল সেবা</h1><span class="count" id="serviceCount"><?= count($services) ?>টি সেবা</span></div>
<input class="search" id="serviceSearch" type="search" placeholder="সার্ভিসের নাম দিয়ে খুঁজুন…" aria-label="সার্ভিস খুঁজুন">
<div class="grid" id="serviceGrid">
<?php foreach ($services as $service): $image = (string)($service['image_url'] ?? ''); ?>
<a class="item" href="<?= $escape($service['url']) ?>" data-search="<?= $escape(($service['title'] ?? '') . ' ' . ($service['category'] ?? '') . ' ' . ($service['description'] ?? '')) ?>">
<?php if ($image !== ''): ?><img class="service-image" src="<?= $escape($image) ?>" alt=""><?php else: ?><span class="icon"><i class="fa-solid <?= !empty($service['kind']) && $service['kind'] === 'file' ? 'fa-file-arrow-up' : 'fa-bolt' ?>"></i></span><?php endif; ?>
<span><span class="title"><?= $escape($service['title'] ?? '') ?></span><span class="meta"><?= $escape($service['category'] ?? 'সার্ভিস') ?><?php if (isset($service['price'])): ?> · ৳<?= number_format((float)$service['price'], 2) ?><?php endif; ?></span></span>
</a>
<?php endforeach; ?>
</div><div class="no-results" id="noResults">কোনো সার্ভিস পাওয়া যায়নি।</div>
</main>
<nav class="bottom" aria-label="মোবাইল নেভিগেশন">
<a href="dashboard.php"><i class="fa-solid fa-house"></i><span>হোম</span></a>
<a href="work_history.php"><i class="fa-solid fa-clock-rotate-left"></i><span>হিস্টোরি</span></a>
<a href="all_services.php" class="active"><i class="fa-solid fa-layer-group"></i><span>সকল সেবা</span></a>
<a href="recharge.php"><i class="fa-solid fa-wallet"></i><span>রিচার্জ</span></a>
<a href="profile.php"><i class="fa-solid fa-user-gear"></i><span>প্রোফাইল</span></a>
</nav>
<script>
const search=document.getElementById('serviceSearch'), grid=document.getElementById('serviceGrid'), cards=[...grid.querySelectorAll('.item')], count=document.getElementById('serviceCount');
const bnCollator=new Intl.Collator('bn',{sensitivity:'base',numeric:true});
cards.sort((a,b)=>bnCollator.compare(a.querySelector('.title').textContent.trim(),b.querySelector('.title').textContent.trim())).forEach(card=>grid.appendChild(card));
search.addEventListener('input',()=>{const q=search.value.trim().toLocaleLowerCase();let visible=0;cards.forEach(card=>{const show=card.dataset.search.toLocaleLowerCase().includes(q);card.hidden=!show;if(show)visible++;});count.textContent=visible+'টি সেবা';document.getElementById('noResults').style.display=visible?'none':'block';});
</script>
</body></html>
