<?php 
session_start();
include_once("includes/configuration.php");

if (isset($_SESSION['user_token'])) {
    $token_user = $_SESSION['user_token'];
    
    $stmt = $conn->prepare("SELECT id, balance, email, name, token, mobile, image, profile_pic FROM users WHERE token = ?");
    $stmt->bind_param("s", $token_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id = $row["id"];
        $balance = $row["balance"];
        $email = $row["email"];
        $name = $row["name"];
        $token = $row["token"];
        $phone = $row["mobile"];
        $user_image = !empty($row["image"]) ? $row["image"] : (!empty($row["profile_pic"]) ? $row["profile_pic"] : "");
    } else {
        unset($_SESSION['user_token']);
        unset($_SESSION['user_id']);
        header("Location: index.php");
        exit();
    }
    $stmt->close();
} else {
    header("Location: index.php");
    exit();
}

$status_map = [];

$q_loc = $conn->query("SELECT service_key, status FROM location_services");
if ($q_loc) { while ($r = $q_loc->fetch_assoc()) { $status_map['loc_' . $r['service_key']] = $r['status']; } }

$q_call = $conn->query("SELECT service_key, status FROM call_sms_services");
if ($q_call) { while ($r = $q_call->fetch_assoc()) { $status_map['call_' . $r['service_key']] = $r['status']; } }

$q_imei = $conn->query("SELECT service_key, status FROM imei_services");
if ($q_imei) { while ($r = $q_imei->fetch_assoc()) { $status_map['imei_' . $r['service_key']] = $r['status']; } }

$q_mfs = $conn->query("SELECT service_key, status FROM mfs_services");
if ($q_mfs) { while ($r = $q_mfs->fetch_assoc()) { $status_map['mfs_' . $r['service_key']] = $r['status']; } }

$q_num = $conn->query("SELECT operator_key, status FROM number_to_server_services");
if ($q_num) { while ($r = $q_num->fetch_assoc()) { $status_map['num_' . $r['operator_key']] = $r['status']; } }
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Dashboard - ICONIC ONLINE FAST SERVICE</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="shortcut icon" type="image/jpeg" href="https://shebakit.xyz/assets/logo.jpg">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Hind Siliguri', 'Plus Jakarta Sans', sans-serif;
            background: #F1F5F9;
            color: #1E293B;
            min-height: 100vh;
            padding-bottom: 20px;
        }

        .top-navbar {
            background: #FFFFFF;
            padding: 10px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .header-logo {
            height: 38px;
            width: auto;
            max-width: 160px;
            object-fit: contain;
        }

        .balance-badge-top {
            background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);
            color: #FFFFFF;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
            white-space: nowrap;
        }

        .dashboard-container {
            max-width: 1180px;
            margin: 0 auto;
            padding: 16px 14px;
        }

        .profile-strip {
            background: #FFFFFF;
            border-radius: 18px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            margin-bottom: 16px;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        .profile-strip .avatar {
            width: 48px;
            height: 48px;
            background: #5B21B6;
            color: #FFFFFF;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 22px;
            text-transform: uppercase;
            flex-shrink: 0;
        }
        .profile-strip .profile-img {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            object-fit: cover;
            border: 2px solid #E2E8F0;
            flex-shrink: 0;
        }

        .wallet-card {
            background: linear-gradient(140deg, #5B21B6 0%, #6D28D9 45%, #7C3AED 100%);
            border-radius: 22px;
            padding: 22px 20px;
            color: #FFFFFF;
            box-shadow: 0 10px 25px rgba(109, 40, 217, 0.28);
            position: relative;
            overflow: hidden;
            margin-bottom: 22px;
        }
        .wallet-card::after {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 160px;
            height: 160px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
        }

        .recharge-btn-pill {
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #FFFFFF;
            border-radius: 25px;
            padding: 9px 20px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 700;
            font-size: 14px;
            margin: 14px 0 16px;
            transition: all 0.2s ease;
        }
        .recharge-btn-pill:hover {
            background: rgba(255, 255, 255, 0.28);
        }

        .stat-subcard {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(6px);
            border-radius: 16px;
            padding: 12px 10px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .category-banner {
            background: linear-gradient(90deg, #4F46E5 0%, #6366F1 60%, #818CF8 100%);
            color: #FFFFFF;
            border-radius: 25px;
            padding: 8px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 22px 0 14px;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.18);
        }
        .category-banner.banner-free {
            background: linear-gradient(90deg, #059669 0%, #10B981 60%, #34D399 100%);
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.2);
        }
        .category-banner.banner-auto {
            background: linear-gradient(90deg, #7C3AED 0%, #8B5CF6 60%, #A78BFA 100%);
            box-shadow: 0 4px 14px rgba(139, 92, 246, 0.2);
        }
        .category-banner .count-pill {
            background: #FFFFFF;
            color: #4F46E5;
            font-size: 11px;
            font-weight: 800;
            padding: 2px 10px;
            border-radius: 14px;
        }
        .banner-free .count-pill { color: #059669; }
        .banner-auto .count-pill { color: #7C3AED; }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 12px;
            margin-bottom: 14px;
        }

        @media (max-width: 480px) {
            .service-grid { gap: 8px; }
        }

        .service-item-card {
            background: #FFFFFF;
            border-radius: 18px;
            padding: 10px 8px 12px;
            text-align: center;
            border: 1.5px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.02);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            min-height: 145px;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        .service-item-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(79, 70, 229, 0.12);
            border-color: #818CF8;
        }

        .service-card-top {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 4px;
        }

        .badge-live {
            font-size: 10px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        .badge-live.status-on {
            background: #DCFCE7;
            border: 1px solid #BBF7D0;
            color: #15803D;
        }
        .badge-live.status-off {
            background: #FFE4E6;
            border: 1px solid #FECDD3;
            color: #BE123C;
        }
        .badge-live.status-free {
            background: #FEF3C7;
            border: 1px solid #FDE68A;
            color: #B45309;
        }
        .dot-live {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }
        .dot-green { background: #16A34A; }
        .dot-red { background: #E11D48; }
        .dot-amber { background: #F59E0B; }

        .service-icon-tile {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin: 4px 0 6px;
        }

        .service-order-pill {
            background: #F1F5F9;
            color: #64748B;
            font-size: 10.5px;
            font-weight: 700;
            padding: 2px 14px;
            border-radius: 12px;
            margin-top: 4px;
        }

        .service-title-text {
            font-size: 11.5px;
            font-weight: 700;
            color: #1E293B;
            line-height: 1.25;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            min-height: 28px;
        }

        @media (max-width: 480px) {
            .service-title-text { font-size: 10.5px; min-height: 26px; }
            .service-icon-tile { width: 36px; height: 36px; font-size: 16px; }
            .badge-live { font-size: 9px; padding: 1px 4px; }
            .service-order-pill { font-size: 9.5px; padding: 2px 10px; }
        }

        .floating-social {
            position: fixed;
            bottom: 78px;
            right: 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 40;
        }
        .fab-btn {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
            font-size: 22px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s ease;
            text-decoration: none;
        }
        .fab-btn:hover { transform: scale(1.08); }
        .fab-whatsapp { background: #25D366; }
        .fab-telegram { background: #0088CC; }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #FFFFFF;
            box-shadow: 0 -3px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding: 8px 0;
            z-index: 50;
            border-top: 1px solid #F1F5F9;
        }
        .nav-item-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #64748B;
            text-decoration: none;
            font-size: 11px;
            font-weight: 600;
            gap: 2px;
        }
        .nav-item-btn i { font-size: 18px; }
        .nav-item-btn.active { color: #4F46E5; }

        @media (min-width: 769px) {
            .bottom-nav { display: none !important; }
            .floating-social { bottom: 24px; right: 24px; }
            body { padding-bottom: 20px; }
        }
        @media (max-width: 768px) {
            body { padding-bottom: 85px; }
        }

        .drawer-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            z-index: 90;
            display: none;
        }
        .drawer-menu {
            position: fixed;
            top: 0;
            right: -320px;
            width: 300px;
            height: 100%;
            background: #FFFFFF;
            box-shadow: -4px 0 25px rgba(0, 0, 0, 0.15);
            z-index: 100;
            transition: right 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .drawer-menu.open { right: 0; }
        .drawer-header {
            padding: 18px;
            background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .drawer-body { flex: 1; overflow-y: auto; padding: 10px 0; }
        .drawer-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 18px;
            color: #334155;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            border-bottom: 1px solid #F8FAFC;
        }
        .drawer-link:hover { background: #F1F5F9; color: #4F46E5; }
        .drawer-link i { width: 22px; font-size: 16px; text-align: center; }
    </style>
</head>
<body>

    <header class="top-navbar">
        <div class="flex items-center gap-3">
            <a href="user Dashboad.php" class="flex items-center">
                <img src="https://shebakitonline.xyz/assets/logo_nav.png" alt="ShebaKit Logo" class="header-logo">
            </a>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            <div class="balance-badge-top cursor-pointer" onclick="location.href='recharge.php'">
                <span id="balance-display"><?php echo htmlspecialchars($balance); ?></span> টাকা
            </div>
            <button type="button" id="openDrawerBtn" class="text-slate-700 text-xl px-1 hover:text-indigo-600">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </header>

    <div class="dashboard-container">
        
        <div class="profile-strip">
            <?php if (!empty($user_image)): ?>
                <img src="<?php echo htmlspecialchars($user_image); ?>" alt="Profile" class="profile-img" onerror="this.style.display='none'; document.getElementById('fallbackAvatar').style.display='flex';">
                <div id="fallbackAvatar" class="avatar" style="display: none;">
                    <?php echo strtoupper(mb_substr($name, 0, 1, "UTF-8")); ?>
                </div>
            <?php else: ?>
                <div class="avatar">
                    <?php echo strtoupper(mb_substr($name, 0, 1, "UTF-8")); ?>
                </div>
            <?php endif; ?>

            <div class="flex-1 overflow-hidden">
                <div class="flex items-center gap-1.5 text-xs text-amber-600 font-bold mb-0.5">
                    <i class="fa-solid fa-star text-[10px]"></i> ICONIC VIP
                </div>
                <h4 class="font-extrabold text-slate-800 text-base truncate"><?php echo htmlspecialchars($name); ?></h4>
                <p class="text-xs text-slate-500 truncate"><?php echo htmlspecialchars($email); ?></p>
            </div>
        </div>

        <div class="wallet-card">
            <p class="text-xs text-purple-200 font-semibold mb-1">বর্তমান ব্যালেন্স</p>
            <div class="text-3xl font-extrabold flex items-center gap-1">
                ৳ <span id="userBalance"><?php echo htmlspecialchars($balance); ?></span>
            </div>

            <button onclick="location.href='recharge.php'" class="recharge-btn-pill">
                <i class="fa-solid fa-plus text-xs"></i> রিচার্জ
            </button>

            <div class="grid grid-cols-3 gap-2">
                <div class="stat-subcard">
                    <div class="w-8 h-8 rounded-lg bg-white/20 text-white flex items-center justify-center mx-auto mb-1 text-sm"><i class="fa-solid fa-layer-group"></i></div>
                    <p class="text-[10px] text-purple-200">সর্বমোট কাজ</p>
                    <h5 class="font-bold text-sm mt-0.5" id="totalOrders">0 টি</h5>
                </div>
                <div class="stat-subcard">
                    <div class="w-8 h-8 rounded-lg bg-emerald-400/25 text-emerald-300 flex items-center justify-center mx-auto mb-1 text-sm"><i class="fa-solid fa-calendar-check"></i></div>
                    <p class="text-[10px] text-purple-200">আজকের কাজ</p>
                    <h5 class="font-bold text-sm mt-0.5" id="completedOrders">0 টি</h5>
                </div>
                <div class="stat-subcard">
                    <div class="w-8 h-8 rounded-lg bg-cyan-400/25 text-cyan-300 flex items-center justify-center mx-auto mb-1 text-sm"><i class="fa-solid fa-chart-simple"></i></div>
                    <p class="text-[10px] text-purple-200">স্ট্যাটাস</p>
                    <h5 class="font-bold text-sm mt-0.5 text-emerald-300">সক্রিয়</h5>
                </div>
            </div>
        </div>

        <!-- 🌟 ফ্রি সার্ভিসসমূহ -->
        <div class="category-banner banner-free">
            <div class="flex items-center gap-2 font-bold text-sm">
                <i class="fa-solid fa-gift"></i> ফ্রি সার্ভিসসমূহ (FREE SERVICES)
            </div>
            <span class="count-pill">৩টি</span>
        </div>

        <div class="service-grid">
            <div class="service-item-card" onclick="location.href='free_service.php?type=biometric'">
                <div class="service-card-top"><span class="badge-live status-free"><span class="dot-live dot-amber"></span> ফ্রি</span></div>
                <div class="service-icon-tile bg-emerald-100 text-emerald-600"><i class="fa-solid fa-fingerprint"></i></div>
                <h5 class="service-title-text">ফ্রি বায়োমেট্রিক চেক</h5>
                <span class="service-order-pill">ক্লিক করুন</span>
            </div>
            <div class="service-item-card" onclick="location.href='free_service.php?type=server_copy'">
                <div class="service-card-top"><span class="badge-live status-free"><span class="dot-live dot-amber"></span> ফ্রি</span></div>
                <div class="service-icon-tile bg-teal-100 text-teal-600"><i class="fa-solid fa-file-lines"></i></div>
                <h5 class="service-title-text">ফ্রি সার্ভার কপি</h5>
                <span class="service-order-pill">ক্লিক করুন</span>
            </div>
            <div class="service-item-card" onclick="location.href='free_service.php?type=make'">
                <div class="service-card-top"><span class="badge-live status-free"><span class="dot-live dot-amber"></span> ফ্রি</span></div>
                <div class="service-icon-tile bg-cyan-100 text-cyan-600"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                <h5 class="service-title-text">ফ্রি মেক</h5>
                <span class="service-order-pill">ক্লিক করুন</span>
            </div>
        </div>

        <!-- ১. সাইন কপি (৩টি) -->
        <div class="category-banner">
            <div class="flex items-center gap-2 font-bold text-sm">
                <i class="fa-solid fa-truck-fast"></i> সাইন কপি
            </div>
            <span class="count-pill">৩টি</span>
        </div>

        <div class="service-grid">
            <div class="service-item-card" onclick="location.href='sign_order.php?type=10_12_17'">
                <div class="service-card-top"><span class="badge-live status-on"><span class="dot-live dot-green"></span> চালু</span></div>
                <div class="service-icon-tile bg-blue-100 text-blue-600"><i class="fa-solid fa-truck-fast"></i></div>
                <h5 class="service-title-text">১০/১২/১৭ দিয়ে সাইন</h5>
                <span class="service-order-pill">অর্ডার</span>
            </div>
            <div class="service-item-card" onclick="location.href='sign_order.php?type=13_form'">
                <div class="service-card-top"><span class="badge-live status-on"><span class="dot-live dot-green"></span> চালু</span></div>
                <div class="service-icon-tile bg-purple-100 text-purple-600"><i class="fa-solid fa-truck-fast"></i></div>
                <h5 class="service-title-text">১৩ সংখ্যা/ফরম/জন্মনিবন্ধন দিয়ে সাইন</h5>
                <span class="service-order-pill">অর্ডার</span>
            </div>
            <div class="service-item-card" onclick="location.href='sign_order.php?type=10_12_17'">
                <div class="service-card-top"><span class="badge-live status-on"><span class="dot-live dot-green"></span> চালু</span></div>
                <div class="service-icon-tile bg-teal-100 text-teal-600"><i class="fa-solid fa-id-card"></i></div>
                <h5 class="service-title-text">এনআইডি অর্ডার</h5>
                <span class="service-order-pill">অর্ডার</span>
            </div>
        </div>

        <!-- ২. সার্ভারকপি সংক্রান্ত সার্ভিস (৩টি) -->
        <div class="category-banner">
            <div class="flex items-center gap-2 font-bold text-sm">
                <i class="fa-solid fa-truck-fast"></i> সার্ভারকপি সংক্রান্ত সার্ভিস
            </div>
            <span class="count-pill">৩টি</span>
        </div>

        <div class="service-grid">
            <div class="service-item-card" onclick="location.href='serverCopy.php'">
                <div class="service-card-top"><span class="badge-live status-on"><span class="dot-live dot-green"></span> চালু</span></div>
                <div class="service-icon-tile bg-orange-100 text-orange-600"><i class="fa-solid fa-server"></i></div>
                <h5 class="service-title-text">অটো সার্ভার কপি</h5>
                <span class="service-order-pill">অর্ডার</span>
            </div>
            <?php 
                $st_robi = ($status_map['num_robi'] ?? 'active') === 'active';
                $st_bl = ($status_map['num_bl'] ?? 'active') === 'active';
            ?>
            <div class="service-item-card" onclick="location.href='numbertobio.php'">
                <div class="service-card-top">
                    <span class="badge-live <?php echo $st_robi ? 'status-on' : 'status-off'; ?>">
                        <span class="dot-live <?php echo $st_robi ? 'dot-green' : 'dot-red'; ?>"></span>
                        <?php echo $st_robi ? 'চালু' : 'বন্ধ'; ?>
                    </span>
                </div>
                <div class="service-icon-tile bg-cyan-100 text-cyan-600"><i class="fa-solid fa-sim-card"></i></div>
                <h5 class="service-title-text">রবি/এয়ারটেল নাম্বার টু সার্ভার কপি</h5>
                <span class="service-order-pill">অর্ডার</span>
            </div>
            <div class="service-item-card" onclick="location.href='numbertobio.php'">
                <div class="service-card-top">
                    <span class="badge-live <?php echo $st_bl ? 'status-on' : 'status-off'; ?>">
                        <span class="dot-live <?php echo $st_bl ? 'dot-green' : 'dot-red'; ?>"></span>
                        <?php echo $st_bl ? 'চালু' : 'বন্ধ'; ?>
                    </span>
                </div>
                <div class="service-icon-tile bg-red-100 text-red-600"><i class="fa-solid fa-sim-card"></i></div>
                <h5 class="service-title-text">বাংলালিংক/গ্রামীণ নাম্বার টু সার্ভার কপি</h5>
                <span class="service-order-pill">অর্ডার</span>
            </div>
        </div>

        <!-- 🌟 AUTO SERVICE (৩টি) -->
        <div class="category-banner banner-auto">
            <div class="flex items-center gap-2 font-bold text-sm">
                <i class="fa-solid fa-robot"></i> অটো সার্ভিসসমূহ (AUTO SERVICE)
            </div>
            <span class="count-pill">৩টি</span>
        </div>

        <div class="service-grid">
            <div class="service-item-card" onclick="location.href='auto_service.php?type=nid'">
                <div class="service-card-top"><span class="badge-live status-on"><span class="dot-live dot-green"></span> অটো</span></div>
                <div class="service-icon-tile bg-purple-100 text-purple-600"><i class="fa-solid fa-address-card"></i></div>
                <h5 class="service-title-text">AUTO NID</h5>
                <span class="service-order-pill">অর্ডার</span>
            </div>
            <div class="service-item-card" onclick="location.href='auto_service.php?type=password'">
                <div class="service-card-top"><span class="badge-live status-on"><span class="dot-live dot-green"></span> অটো</span></div>
                <div class="service-icon-tile bg-indigo-100 text-indigo-600"><i class="fa-solid fa-key"></i></div>
                <h5 class="service-title-text">AUTO USER PASSWORD</h5>
                <span class="service-order-pill">অর্ডার</span>
            </div>
            <div class="service-item-card" onclick="location.href='auto_service.php?type=ministry'">
                <div class="service-card-top"><span class="badge-live status-on"><span class="dot-live dot-green"></span> অটো</span></div>
                <div class="service-icon-tile bg-pink-100 text-pink-600"><i class="fa-solid fa-building-columns"></i></div>
                <h5 class="service-title-text">AUTO MINISTRY</h5>
                <span class="service-order-pill">অর্ডার</span>
            </div>
        </div>

        <!-- ৩. টিন ও ফাইল সার্ভিস (৩টি) -->
        <div class="category-banner">
            <div class="flex items-center gap-2 font-bold text-sm">
                <i class="fa-solid fa-folder-open"></i> টিন ও ফাইল সার্ভিস
            </div>
            <span class="count-pill">৩টি</span>
        </div>

        <div class="service-grid">
            <div class="service-item-card" onclick="location.href='auto_tin.php'">
                <div class="service-card-top"><span class="badge-live status-on"><span class="dot-live dot-green"></span> চালু</span></div>
                <div class="service-icon-tile bg-rose-100 text-rose-600"><i class="fa-solid fa-file-invoice"></i></div>
                <h5 class="service-title-text">অটো টিন</h5>
                <span class="service-order-pill">অর্ডার</span>
            </div>
            <div class="service-item-card" onclick="location.href='order_file.php'">
                <div class="service-card-top"><span class="badge-live status-on"><span class="dot-live dot-green"></span> চালু</span></div>
                <div class="service-icon-tile bg-emerald-100 text-emerald-600"><i class="fa-solid fa-file-arrow-up"></i></div>
                <h5 class="service-title-text">অর্ডার ফাইল</h5>
                <span class="service-order-pill">অর্ডার</span>
            </div>
            <div class="service-item-card" onclick="location.href='order_text.php'">
                <div class="service-card-top"><span class="badge-live status-on"><span class="dot-live dot-green"></span> চালু</span></div>
                <div class="service-icon-tile bg-indigo-100 text-indigo-600"><i class="fa-solid fa-pen-to-square"></i></div>
                <h5 class="service-title-text">অর্ডার টেক্সট</h5>
                <span class="service-order-pill">অর্ডার</span>
            </div>
        </div>

        <!-- ৪. লোকেশন ট্র্যাকিং সার্ভিস (৬টি) -->
        <div class="category-banner">
            <div class="flex items-center gap-2 font-bold text-sm">
                <i class="fa-solid fa-truck-fast"></i> ( Location ) লোকেশন ট্র্যাকিং সার্ভিস
            </div>
            <span class="count-pill">৬টি</span>
        </div>

        <div class="service-grid">
            <?php 
                $loc_items_def = [
                    ['key' => 'sim_on_radio', 'title' => 'সিম অন রেডিও লোকেশন', 'bg' => 'bg-pink-100', 'col' => 'text-pink-600', 'icon' => 'fa-location-dot'],
                    ['key' => 'sim_off_tower', 'title' => 'সিম অফ লাস্ট টাওয়ার লোকেশন', 'bg' => 'bg-amber-100', 'col' => 'text-amber-600', 'icon' => 'fa-tower-cell'],
                    ['key' => 'sim_on_pinpoint', 'title' => 'সিম অন PIN POINT লোকেশন', 'bg' => 'bg-indigo-100', 'col' => 'text-indigo-600', 'icon' => 'fa-crosshairs'],
                    ['key' => 'sim_off_pinpoint', 'title' => 'সিম অফ PIN POINT লোকেশন', 'bg' => 'bg-violet-100', 'col' => 'text-violet-600', 'icon' => 'fa-crosshairs'],
                    ['key' => 'cid_sim_on', 'title' => 'CID লোকেশন সিম অন', 'bg' => 'bg-teal-100', 'col' => 'text-teal-600', 'icon' => 'fa-satellite-dish'],
                    ['key' => 'cid_sim_off', 'title' => 'CID লোকেশন সিম বন্ধ', 'bg' => 'bg-sky-100', 'col' => 'text-sky-600', 'icon' => 'fa-satellite-dish']
                ];
                foreach ($loc_items_def as $li): 
                    $is_act = ($status_map['loc_' . $li['key']] ?? 'active') === 'active';
            ?>
            <div class="service-item-card" onclick="location.href='location_service.php?type=<?php echo $li['key']; ?>'">
                <div class="service-card-top">
                    <span class="badge-live <?php echo $is_act ? 'status-on' : 'status-off'; ?>">
                        <span class="dot-live <?php echo $is_act ? 'dot-green' : 'dot-red'; ?>"></span>
                        <?php echo $is_act ? 'চালু' : 'বন্ধ'; ?>
                    </span>
                </div>
                <div class="service-icon-tile <?php echo $li['bg']; ?> <?php echo $li['col']; ?>"><i class="fa-solid <?php echo $li['icon']; ?>"></i></div>
                <h5 class="service-title-text"><?php echo $li['title']; ?></h5>
                <span class="service-order-pill">অর্ডার</span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ৫. কল ও এসএমএস হিস্টোরি (৬টি) -->
        <div class="category-banner">
            <div class="flex items-center gap-2 font-bold text-sm">
                <i class="fa-solid fa-truck-fast"></i> কল ও এসএমএস হিস্টোরি
            </div>
            <span class="count-pill">৬টি</span>
        </div>

        <div class="service-grid">
            <?php 
                $call_items_def = [
                    ['key' => 'call_3m', 'title' => '৩ মাসের কল লিস্ট', 'bg' => 'bg-pink-100', 'col' => 'text-pink-600', 'icon' => 'fa-phone-volume'],
                    ['key' => 'call_6m', 'title' => '৬ মাসের কল লিস্ট', 'bg' => 'bg-amber-100', 'col' => 'text-amber-600', 'icon' => 'fa-phone-volume'],
                    ['key' => 'call_1y', 'title' => '১ বছরের কল লিস্ট', 'bg' => 'bg-sky-100', 'col' => 'text-sky-600', 'icon' => 'fa-phone-volume'],
                    ['key' => 'sms_3m', 'title' => 'এস এম লিস্ট ৩ মাস', 'bg' => 'bg-indigo-100', 'col' => 'text-indigo-600', 'icon' => 'fa-message'],
                    ['key' => 'sms_6m', 'title' => 'এস এম লিস্ট ৬ মাস', 'bg' => 'bg-violet-100', 'col' => 'text-violet-600', 'icon' => 'fa-message'],
                    ['key' => 'sms_1y', 'title' => 'এস এম লিস্ট ১ বছর', 'bg' => 'bg-rose-100', 'col' => 'text-rose-600', 'icon' => 'fa-message']
                ];
                foreach ($call_items_def as $ci): 
                    $is_act = ($status_map['call_' . $ci['key']] ?? 'active') === 'active';
            ?>
            <div class="service-item-card" onclick="location.href='call_sms_list.php?type=<?php echo $ci['key']; ?>'">
                <div class="service-card-top">
                    <span class="badge-live <?php echo $is_act ? 'status-on' : 'status-off'; ?>">
                        <span class="dot-live <?php echo $is_act ? 'dot-green' : 'dot-red'; ?>"></span>
                        <?php echo $is_act ? 'চালু' : 'বন্ধ'; ?>
                    </span>
                </div>
                <div class="service-icon-tile <?php echo $ci['bg']; ?> <?php echo $ci['col']; ?>"><i class="fa-solid <?php echo $ci['icon']; ?>"></i></div>
                <h5 class="service-title-text"><?php echo $ci['title']; ?></h5>
                <span class="service-order-pill">অর্ডার</span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ৬. IMEI সার্ভিস (৩টি) -->
        <div class="category-banner">
            <div class="flex items-center gap-2 font-bold text-sm">
                <i class="fa-solid fa-truck-fast"></i> IMEI / মোবাইল ট্র্যাকিং সার্ভিস
            </div>
            <span class="count-pill">৩টি</span>
        </div>

        <div class="service-grid">
            <?php 
                $imei_items_def = [
                    ['key' => 'num_to_imei', 'title' => 'মোবাইল নাম্বার দিয়ে ফোনের IMEI বের করুন', 'bg' => 'bg-teal-100', 'col' => 'text-teal-600', 'icon' => 'fa-mobile-screen'],
                    ['key' => 'imei_to_num', 'title' => 'IMEI দিয়ে ফোনের একটিভ নাম্বার বের করুন', 'bg' => 'bg-sky-100', 'col' => 'text-sky-600', 'icon' => 'fa-mobile-screen'],
                    ['key' => 'imei_all_num', 'title' => 'IMEI দিয়ে ফোনে ব্যবহার করা সকল নাম্বার', 'bg' => 'bg-amber-100', 'col' => 'text-amber-600', 'icon' => 'fa-mobile-screen']
                ];
                foreach ($imei_items_def as $ii): 
                    $is_act = ($status_map['imei_' . $ii['key']] ?? 'active') === 'active';
            ?>
            <div class="service-item-card" onclick="location.href='imei_service.php?type=<?php echo $ii['key']; ?>'">
                <div class="service-card-top">
                    <span class="badge-live <?php echo $is_act ? 'status-on' : 'status-off'; ?>">
                        <span class="dot-live <?php echo $is_act ? 'dot-green' : 'dot-red'; ?>"></span>
                        <?php echo $is_act ? 'চালু' : 'বন্ধ'; ?>
                    </span>
                </div>
                <div class="service-icon-tile <?php echo $ii['bg']; ?> <?php echo $ii['col']; ?>"><i class="fa-solid <?php echo $ii['icon']; ?>"></i></div>
                <h5 class="service-title-text"><?php echo $ii['title']; ?></h5>
                <span class="service-order-pill">অর্ডার</span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ৭. বিকাশ / নগদ / রকেট সার্ভিস (৩টি) -->
        <div class="category-banner">
            <div class="flex items-center gap-2 font-bold text-sm">
                <i class="fa-solid fa-truck-fast"></i> বিকাশ / নগদ / রকেট সার্ভিস
            </div>
            <span class="count-pill">৩টি</span>
        </div>

        <div class="service-grid">
            <?php 
                $mfs_items_def = [
                    ['key' => 'bkash_personal', 'title' => 'বিকাশ পারসোনাল NID ইনফরমেশন', 'bg' => 'bg-pink-100', 'col' => 'text-pink-600'],
                    ['key' => 'nagad_personal', 'title' => 'নগদ পারসোনাল NID ইনফরমেশন', 'bg' => 'bg-orange-100', 'col' => 'text-orange-600'],
                    ['key' => 'rocket_personal', 'title' => 'রকেট পারসোনাল NID ইনফরমেশন', 'bg' => 'bg-purple-100', 'col' => 'text-purple-600']
                ];
                foreach ($mfs_items_def as $mi): 
                    $is_act = ($status_map['mfs_' . $mi['key']] ?? 'active') === 'active';
            ?>
            <div class="service-item-card" onclick="location.href='mfs_service.php?type=<?php echo $mi['key']; ?>'">
                <div class="service-card-top">
                    <span class="badge-live <?php echo $is_act ? 'status-on' : 'status-off'; ?>">
                        <span class="dot-live <?php echo $is_act ? 'dot-green' : 'dot-red'; ?>"></span>
                        <?php echo $is_act ? 'চালু' : 'বন্ধ'; ?>
                    </span>
                </div>
                <div class="service-icon-tile <?php echo $mi['bg']; ?> <?php echo $mi['col']; ?>"><i class="fa-solid fa-wallet"></i></div>
                <h5 class="service-title-text"><?php echo $mi['title']; ?></h5>
                <span class="service-order-pill">অর্ডার</span>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <div class="floating-social">
        <a href="https://chat.whatsapp.com/IKEmCVrXgwp5a87Lng1AGX" target="_blank" class="fab-btn fab-whatsapp">
            <i class="fa-brands fa-whatsapp"></i>
        </a>
        <a href="https://t.me/+uA2ZXDwdZotjYWM1" target="_blank" class="fab-btn fab-telegram">
            <i class="fa-brands fa-telegram"></i>
        </a>
    </div>

    <nav class="bottom-nav">
        <a href="user Dashboad.php" class="nav-item-btn active">
            <i class="fa-solid fa-house"></i>
            <span>হোম</span>
        </a>
        <a href="work_history.php" class="nav-item-btn">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>হিস্টোরি</span>
        </a>
        <a href="recharge.php" class="nav-item-btn">
            <i class="fa-solid fa-wallet"></i>
            <span>রিচার্জ</span>
        </a>
        <a href="https://chat.whatsapp.com/IKEmCVrXgwp5a87Lng1AGX" target="_blank" class="nav-item-btn">
            <i class="fa-solid fa-paper-plane"></i>
            <span>সাপোর্ট</span>
        </a>
        <a href="profile.php" class="nav-item-btn">
            <i class="fa-solid fa-circle-user"></i>
            <span>প্রোফাইল</span>
        </a>
    </nav>

    <div class="drawer-overlay" id="drawerOverlay"></div>
    <div class="drawer-menu" id="drawerMenu">
        <div class="drawer-header">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-grid-2"></i>
                <h5 class="font-bold text-sm">সকল সার্ভিস তালিকা</h5>
            </div>
            <button id="closeDrawerBtn" class="text-white text-lg"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="drawer-body">
            <a href="user Dashboad.php" class="drawer-link"><i class="fa-solid fa-house text-indigo-500"></i> হোম ড্যাশবোর্ড</a>
            <a href="serverCopy.php" class="drawer-link"><i class="fa-solid fa-server text-sky-500"></i> অটো সার্ভার কপি</a>
            <a href="auto_tin.php" class="drawer-link"><i class="fa-solid fa-file-invoice text-rose-500"></i> অটো টিন সার্টিফিকেট</a>
            <a href="order_file.php" class="drawer-link"><i class="fa-solid fa-file-arrow-up text-emerald-500"></i> অর্ডার ফাইল</a>
            <a href="order_text.php" class="drawer-link"><i class="fa-solid fa-pen-to-square text-indigo-500"></i> অর্ডার টেক্সট</a>
            <a href="numbertobio.php" class="drawer-link"><i class="fa-solid fa-sim-card text-emerald-500"></i> নাম্বার টু বায়োমেট্রিক</a>
            <a href="call_sms_list.php" class="drawer-link"><i class="fa-solid fa-phone-volume text-pink-500"></i> কল ও এসএমএস হিস্ট্রি</a>
            <a href="location_service.php" class="drawer-link"><i class="fa-solid fa-location-dot text-indigo-500"></i> লোকেশন ট্র্যাকিং</a>
            <a href="imei_service.php" class="drawer-link"><i class="fa-solid fa-mobile-screen text-amber-500"></i> IMEI সার্ভিস</a>
            <a href="mfs_service.php" class="drawer-link"><i class="fa-solid fa-wallet text-purple-500"></i> MFS NID সার্ভিস</a>
            <a href="recharge.php" class="drawer-link"><i class="fa-solid fa-wallet text-emerald-600"></i> ব্যালেন্স রিচার্জ</a>
            <a href="work_history.php" class="drawer-link"><i class="fa-solid fa-clock-rotate-left text-violet-500"></i> অর্ডার হিস্টোরি</a>
            <a href="profile.php" class="drawer-link"><i class="fa-solid fa-user-gear text-slate-600"></i> আমার প্রোফাইল</a>
            <a href="logout.php" class="drawer-link text-red-600"><i class="fa-solid fa-arrow-right-from-bracket text-red-600"></i> লগআউট</a>
        </div>
    </div>

    <script>
        $('#openDrawerBtn').click(function() {
            $('#drawerOverlay').fadeIn(200);
            $('#drawerMenu').addClass('open');
        });

        $('#closeDrawerBtn, #drawerOverlay').click(function() {
            $('#drawerOverlay').fadeOut(200);
            $('#drawerMenu').removeClass('open');
        });

        function loadStats() {
            $.ajax({
                url: 'api/get_user_stats.php',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data && data.success) {
                        $('#balance-display').text(data.user_balance || '0.00');
                        $('#userBalance').text(data.user_balance || '0.00');
                        $('#totalOrders').text((data.total_orders || 0) + ' টি');
                        $('#completedOrders').text((data.completed_orders || 0) + ' টি');
                    }
                }
            });
        }

        $(document).ready(function() {
            loadStats();
            setInterval(loadStats, 10000);
        });
    </script>
</body>
</html>