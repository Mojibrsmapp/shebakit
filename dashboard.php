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

$whatsapp_group_link = get_site_setting($conn, 'whatsapp_group', '#');
$telegram_link = get_site_setting($conn, 'telegram_link', '#');
?>
<!DOCTYPE html>
<html lang="bn" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>ShebaKit Workspace - ড্যাশবোর্ড</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="shortcut icon" type="image/jpeg" href="https://shebakit.xyz/assets/logo.jpg">
    
    <style>
        /* Theme Switching Variables */
        :root[data-theme="light"] {
            --bg-main: #F3F8FF;
            --navbar-bg: #FFFFFF;
            --card-bg: #FFFFFF;
            --brand-dark: #123B70;
            --brand-blue: #0866D9;
            --brand-cyan: #08B8D0;
            --text-dark: #123B70;
            --text-muted: #52657D;
            --border-soft: #DCE8F5;
            --item-hover: #FAFCFF;
            --stat-box: #F3F8FF;
        }

        :root[data-theme="dark"] {
            --bg-main: #0B1120;
            --navbar-bg: #111827;
            --card-bg: #1F2937;
            --brand-dark: #F3F8FF;
            --brand-blue: #38BDF8;
            --brand-cyan: #2DD4BF;
            --text-dark: #F8FAFC;
            --text-muted: #94A3B8;
            --border-soft: #374151;
            --item-hover: #1E293B;
            --stat-box: #111827;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
            transition: background-color 0.25s ease, border-color 0.25s ease, color 0.25s ease;
        }

        body {
            font-family: 'Hind Siliguri', 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-dark);
            min-height: 100vh;
            padding-bottom: 30px;
        }

        .workspace-navbar {
            background: var(--navbar-bg);
            border-bottom: 1.5px solid var(--border-soft);
            box-shadow: 0 4px 20px -8px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        /* Dark/Light Mode Switch Button */
        .theme-toggle-btn {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: var(--stat-box);
            border: 1.5px solid var(--border-soft);
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .theme-toggle-btn:hover {
            transform: scale(1.05);
            border-color: var(--brand-blue);
        }

        .user-identity-card {
            background: var(--card-bg);
            border: 1.5px solid var(--border-soft);
            border-radius: 20px;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        .user-identity-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 6px;
            background: linear-gradient(180deg, var(--brand-blue), var(--brand-cyan));
        }

        .search-pill-box {
            background: var(--card-bg);
            border: 1.5px solid var(--border-soft);
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        }
        .search-pill-box:focus-within {
            border-color: var(--brand-blue);
            box-shadow: 0 0 0 4px rgba(8, 102, 217, 0.15);
        }

        .service-hub-item {
            background: var(--card-bg);
            border: 1.5px solid var(--border-soft);
            border-radius: 18px;
            padding: 14px 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .service-hub-item:hover {
            transform: translateY(-2px);
            border-color: var(--brand-blue);
            box-shadow: 0 10px 24px -6px rgba(0, 0, 0, 0.2);
            background: var(--item-hover);
        }

        .icon-bubble {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .free-tag {
            background: #064E3B;
            color: #34D399;
            font-size: 10px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 6px;
        }
        :root[data-theme="light"] .free-tag {
            background: #DCFCE7;
            color: #10B981;
        }

        .section-label {
            font-size: 14px;
            font-weight: 800;
            color: var(--brand-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-label::before {
            content: '';
            width: 4px;
            height: 16px;
            background: var(--brand-blue);
            border-radius: 4px;
            display: inline-block;
        }

        .mobile-bottom-bar {
            background: var(--navbar-bg);
            border-top: 1.5px solid var(--border-soft);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
        }

        @media (min-width: 769px) {
            .mobile-bottom-bar { display: none !important; }
        }
        @media (max-width: 768px) {
            body { padding-bottom: 85px; }
        }
    </style>
</head>
<body>

    <!-- Header Navbar -->
    <header class="workspace-navbar px-4 py-3">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="dashboard.php" class="flex items-center">
                    <img src="assets/logo_nav.png" alt="ShebaKit" onerror="this.src='assets/logo.jpg'" class="h-9 w-auto object-contain">
                </a>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                
                <!-- Dark / Light Mode Switch Button -->
                <button id="themeToggleBtn" class="theme-toggle-btn" title="ডার্ক/হোয়াইট মোড পরিবর্তন করুন" type="button">
                    <i class="fa-solid fa-moon text-sm" id="themeIcon"></i>
                </button>

                <!-- Balance Badge -->
                <button onclick="location.href='recharge.php'" class="flex items-center gap-2 bg-[var(--stat-box)] hover:opacity-90 text-[var(--brand-blue)] px-3.5 py-1.5 rounded-full text-xs font-black border border-[var(--border-soft)] transition shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-[#10B981] animate-pulse"></span>
                    <span id="balance-display"><?php echo htmlspecialchars($balance); ?></span> ৳
                    <i class="fa-solid fa-plus-circle text-[var(--brand-cyan)] ml-0.5"></i>
                </button>

                <!-- Menu Toggle Button -->
                <button id="openDrawerBtn" class="w-9 h-9 rounded-xl bg-[var(--stat-box)] text-[var(--brand-dark)] flex items-center justify-center border border-[var(--border-soft)] transition">
                    <i class="fa-solid fa-bars-staggered text-sm"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Workspace -->
    <main class="max-w-6xl mx-auto px-4 py-5 space-y-6">

        <!-- User Identity Card -->
        <div class="user-identity-card p-4 sm:p-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                
                <div class="flex items-center gap-3.5">
                    <div class="relative shrink-0">
                        <?php if (!empty($user_image)): ?>
                            <img src="<?php echo htmlspecialchars($user_image); ?>" alt="Profile" class="w-12 h-12 rounded-xl object-cover ring-2 ring-[var(--brand-blue)]/20 shadow-sm">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-[var(--brand-blue)] to-[var(--brand-cyan)] text-white flex items-center justify-center font-bold text-lg shadow-sm">
                                <?php echo strtoupper(mb_substr($name, 0, 1, "UTF-8")); ?>
                            </div>
                        <?php endif; ?>
                        <span class="w-3 h-3 bg-[#10B981] border-2 border-[var(--card-bg)] rounded-full absolute -bottom-0.5 -right-0.5"></span>
                    </div>

                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base sm:text-lg font-bold text-[var(--brand-dark)] leading-tight"><?php echo htmlspecialchars($name); ?></h3>
                            <span class="bg-[var(--stat-box)] text-[var(--brand-blue)] border border-[var(--border-soft)] text-[10px] font-extrabold px-2 py-0.5 rounded-full">ACTIVE USER</span>
                        </div>
                        <p class="text-xs text-[var(--text-muted)] mt-0.5"><?php echo htmlspecialchars($email); ?></p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <div class="bg-[var(--stat-box)] border border-[var(--border-soft)] px-3.5 py-2 rounded-xl text-center min-w-[85px]">
                        <span class="text-[10px] text-[var(--text-muted)] font-bold block">TOTAL ORDER</span>
                        <span class="text-sm font-black text-[var(--brand-dark)]" id="totalOrders">0 টি</span>
                    </div>

                    <div class="bg-[var(--stat-box)] border border-[var(--border-soft)] px-3.5 py-2 rounded-xl text-center min-w-[85px]">
                        <span class="text-[10px] text-[var(--text-muted)] font-bold block">TODAY COMPLETED</span>
                        <span class="text-sm font-black text-[#10B981]" id="completedOrders">0 টি</span>
                    </div>

                    <button onclick="location.href='recharge.php'" class="bg-[#0866D9] hover:bg-[#0654b3] text-white text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-1.5 shadow-md transition shrink-0">
                        <i class="fa-solid fa-wallet"></i> <span>রিচার্জ</span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Realtime Search Bar -->
        <div class="search-pill-box p-1.5 flex items-center gap-3">
            <div class="pl-3 text-[var(--brand-blue)]">
                <i class="fa-solid fa-magnifying-glass text-sm"></i>
            </div>
            <input type="text" id="dashboardSearchInput" placeholder="যেকোনো সার্ভিস খুঁজুন (যেমন: সাইন কপি, বায়োমেট্রিক, লোকেশন)..." class="w-full bg-transparent text-xs sm:text-sm font-medium text-[var(--text-dark)] outline-none placeholder-[var(--text-muted)]/60 py-1">
        </div>

        <!-- Section 1: Complimentary Quick Tools -->
        <div class="space-y-3 service-group">
            <div class="flex items-center justify-between">
                <span class="section-label">কমপ্লিমেন্টারি সার্ভিস</span>
                <span class="text-xs font-semibold text-[var(--text-muted)]">ফ্রি টুলস</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div onclick="location.href='free_service.php?type=biometric'" class="service-hub-item">
                    <div class="icon-bubble bg-[#DCFCE7] text-[#10B981]">
                        <i class="fa-solid fa-fingerprint"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1">
                            <h4 class="text-xs sm:text-sm font-bold text-[var(--brand-dark)] truncate">ফ্রি বায়োমেট্রিক</h4>
                            <span class="free-tag">FREE</span>
                        </div>
                        <p class="text-[11px] text-[var(--text-muted)] truncate mt-0.5">তাত্ক্ষণিক ভেরিফিকেশন</p>
                    </div>
                </div>

                <div onclick="location.href='free_service.php?type=server_copy'" class="service-hub-item">
                    <div class="icon-bubble bg-[#E0F2FE] text-[#0866D9]">
                        <i class="fa-solid fa-file-shield"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1">
                            <h4 class="text-xs sm:text-sm font-bold text-[var(--brand-dark)] truncate">ফ্রি সার্ভার কপি</h4>
                            <span class="free-tag">FREE</span>
                        </div>
                        <p class="text-[11px] text-[var(--text-muted)] truncate mt-0.5">বেসিক তথ্য প্রিভিউ</p>
                    </div>
                </div>

                <div onclick="location.href='free_service.php?type=make'" class="service-hub-item">
                    <div class="icon-bubble bg-[#ECFEFF] text-[#08B8D0]">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1">
                            <h4 class="text-xs sm:text-sm font-bold text-[var(--brand-dark)] truncate">ফ্রি মেক কপি</h4>
                            <span class="free-tag">FREE</span>
                        </div>
                        <p class="text-[11px] text-[var(--text-muted)] truncate mt-0.5">সহজ ফাইল জেনারেটর</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Server & NID Operations -->
        <div class="space-y-3 service-group">
            <div class="flex items-center justify-between">
                <span class="section-label">সার্ভার ও এনআইডি সার্ভিস</span>
                <span class="text-xs font-semibold text-[var(--text-muted)]">অটোমেশন ও সাইন</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
                <div onclick="location.href='serverCopy.php'" class="service-hub-item !flex-col !text-center !p-3">
                    <div class="icon-bubble bg-[#E0F2FE] text-[#0866D9] mx-auto mb-1">
                        <i class="fa-solid fa-server"></i>
                    </div>
                    <h4 class="text-xs font-bold text-[var(--brand-dark)] truncate w-full">অটো সার্ভার কপি</h4>
                    <span class="text-[10px] text-[var(--text-muted)]">তাত্ক্ষণিক ডেলিভারি</span>
                </div>

                <div onclick="location.href='sign_order.php?type=10_12_17'" class="service-hub-item !flex-col !text-center !p-3">
                    <div class="icon-bubble bg-[#EFF6FF] text-[#1D4ED8] mx-auto mb-1">
                        <i class="fa-solid fa-file-signature"></i>
                    </div>
                    <h4 class="text-xs font-bold text-[var(--brand-dark)] truncate w-full">১০/১২/১৭ সাইন</h4>
                    <span class="text-[10px] text-[var(--text-muted)]">সাইন কপি অর্ডার</span>
                </div>

                <div onclick="location.href='sign_order.php?type=13_form'" class="service-hub-item !flex-col !text-center !p-3">
                    <div class="icon-bubble bg-[#F0FDF4] text-[#15803D] mx-auto mb-1">
                        <i class="fa-solid fa-id-card-clip"></i>
                    </div>
                    <h4 class="text-xs font-bold text-[var(--brand-dark)] truncate w-full">১৩ ডিজিট / ফরম</h4>
                    <span class="text-[10px] text-[var(--text-muted)]">ফরম নম্বর সাইন</span>
                </div>

                <div onclick="location.href='auto_service.php?type=nid'" class="service-hub-item !flex-col !text-center !p-3">
                    <div class="icon-bubble bg-[#FDF2F8] text-[#DB2777] mx-auto mb-1">
                        <i class="fa-solid fa-address-card"></i>
                    </div>
                    <h4 class="text-xs font-bold text-[var(--brand-dark)] truncate w-full">Auto NID মেক</h4>
                    <span class="text-[10px] text-[var(--text-muted)]">স্মার্ট কার্ড মেকার</span>
                </div>

                <div onclick="location.href='auto_service.php?type=password'" class="service-hub-item !flex-col !text-center !p-3">
                    <div class="icon-bubble bg-[#ECFEFF] text-[#08B8D0] mx-auto mb-1">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <h4 class="text-xs font-bold text-[var(--brand-dark)] truncate w-full">User Pass</h4>
                    <span class="text-[10px] text-[var(--text-muted)]">অটো রিসেট</span>
                </div>

                <div onclick="location.href='auto_service.php?type=ministry'" class="service-hub-item !flex-col !text-center !p-3">
                    <div class="icon-bubble bg-[#FFF1F2] text-[#E11D48] mx-auto mb-1">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <h4 class="text-xs font-bold text-[var(--brand-dark)] truncate w-full">Ministry Copy</h4>
                    <span class="text-[10px] text-[var(--text-muted)]">মন্ত্রণালয় রেকর্ড</span>
                </div>
            </div>
        </div>

        <!-- Section 3: Telecom & CDR Hub -->
        <div class="space-y-3 service-group">
            <div class="flex items-center justify-between">
                <span class="section-label">সিম ও মোবাইল রেকর্ডস</span>
                <span class="text-xs font-semibold text-[var(--text-muted)]">সিডিআর ও বায়োমেট্রিক</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div onclick="location.href='numbertobio.php'" class="service-hub-item">
                    <div class="icon-bubble bg-[#ECFEFF] text-[#08B8D0]">
                        <i class="fa-solid fa-sim-card"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs sm:text-sm font-bold text-[var(--brand-dark)] truncate">রবি / এয়ারটেল তথ্য</h4>
                        <p class="text-[11px] text-[var(--text-muted)] truncate mt-0.5">নাম্বার টু সার্ভার কপি</p>
                    </div>
                </div>

                <div onclick="location.href='numbertobio.php'" class="service-hub-item">
                    <div class="icon-bubble bg-[#FFF1F2] text-[#E11D48]">
                        <i class="fa-solid fa-sim-card"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs sm:text-sm font-bold text-[var(--brand-dark)] truncate">বাংলালিংক / জিপি তথ্য</h4>
                        <p class="text-[11px] text-[var(--text-muted)] truncate mt-0.5">বায়োমেট্রিক ইনফো</p>
                    </div>
                </div>

                <div onclick="location.href='call_sms_list.php'" class="service-hub-item">
                    <div class="icon-bubble bg-[#EFF6FF] text-[#0866D9]">
                        <i class="fa-solid fa-phone-volume"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs sm:text-sm font-bold text-[var(--brand-dark)] truncate">কল লিস্ট হিস্টোরি</h4>
                        <p class="text-[11px] text-[var(--text-muted)] truncate mt-0.5">৩ মাস / ৬ মাস / ১ বছর</p>
                    </div>
                </div>

                <div onclick="location.href='call_sms_list.php'" class="service-hub-item">
                    <div class="icon-bubble bg-[#DCFCE7] text-[#10B981]">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs sm:text-sm font-bold text-[var(--brand-dark)] truncate">এসএমএস রেকর্ডস</h4>
                        <p class="text-[11px] text-[var(--text-muted)] truncate mt-0.5">ইনকামিং ও আউটগোয়িং লগ</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Telecom Live Location Tracking -->
        <div class="space-y-3 service-group">
            <div class="flex items-center justify-between">
                <span class="section-label">লোকেশন ট্র্যাকিং সার্ভিস</span>
                <span class="text-xs font-semibold text-[var(--text-muted)]">রিয়েলটাইম রেডিও / টাওয়ার</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
                <div onclick="location.href='location_service.php?type=sim_on_radio'" class="service-hub-item !flex-col !text-center !p-3">
                    <div class="icon-bubble bg-[#FFFBEB] text-[#D97706] mx-auto mb-1">
                        <i class="fa-solid fa-tower-broadcast"></i>
                    </div>
                    <h4 class="text-xs font-bold text-[var(--brand-dark)] truncate w-full">অন রেডিও লোকেশন</h4>
                    <span class="text-[10px] text-[var(--text-muted)]">অ্যাক্টিভ রেডিও</span>
                </div>

                <div onclick="location.href='location_service.php?type=sim_off_tower'" class="service-hub-item !flex-col !text-center !p-3">
                    <div class="icon-bubble bg-[#F1F5F9] text-[#475569] mx-auto mb-1">
                        <i class="fa-solid fa-tower-cell"></i>
                    </div>
                    <h4 class="text-xs font-bold text-[var(--brand-dark)] truncate w-full">অফ টাওয়ার লোকেশন</h4>
                    <span class="text-[10px] text-[var(--text-muted)]">লাস্ট টাওয়ার লগ</span>
                </div>

                <div onclick="location.href='location_service.php?type=sim_on_pinpoint'" class="service-hub-item !flex-col !text-center !p-3">
                    <div class="icon-bubble bg-[#EFF6FF] text-[#0866D9] mx-auto mb-1">
                        <i class="fa-solid fa-crosshairs"></i>
                    </div>
                    <h4 class="text-xs font-bold text-[var(--brand-dark)] truncate w-full">অন পিনপয়েন্ট</h4>
                    <span class="text-[10px] text-[var(--text-muted)]">লাইভ জিপিএস স্থানাঙ্ক</span>
                </div>

                <div onclick="location.href='location_service.php?type=sim_off_pinpoint'" class="service-hub-item !flex-col !text-center !p-3">
                    <div class="icon-bubble bg-[#ECFEFF] text-[#08B8D0] mx-auto mb-1">
                        <i class="fa-solid fa-location-crosshairs"></i>
                    </div>
                    <h4 class="text-xs font-bold text-[var(--brand-dark)] truncate w-full">অফ পিনপয়েন্ট</h4>
                    <span class="text-[10px] text-[var(--text-muted)]">সর্বশেষ অফলাইন পয়েন্ট</span>
                </div>

                <div onclick="location.href='location_service.php?type=cid_sim_on'" class="service-hub-item !flex-col !text-center !p-3">
                    <div class="icon-bubble bg-[#F0FDF4] text-[#16A34A] mx-auto mb-1">
                        <i class="fa-solid fa-satellite-dish"></i>
                    </div>
                    <h4 class="text-xs font-bold text-[var(--brand-dark)] truncate w-full">CID অন লোকেশন</h4>
                    <span class="text-[10px] text-[var(--text-muted)]">সেল আইডি ট্র্যাকিং</span>
                </div>

                <div onclick="location.href='location_service.php?type=cid_sim_off'" class="service-hub-item !flex-col !text-center !p-3">
                    <div class="icon-bubble bg-[#F0FDF4] text-[#15803D] mx-auto mb-1">
                        <i class="fa-solid fa-satellite"></i>
                    </div>
                    <h4 class="text-xs font-bold text-[var(--brand-dark)] truncate w-full">CID অফ লোকেশন</h4>
                    <span class="text-[10px] text-[var(--text-muted)]">অফলাইন সেল আইডি</span>
                </div>
            </div>
        </div>

        <!-- Section 5: Utility & Documents -->
        <div class="space-y-3 service-group">
            <div class="flex items-center justify-between">
                <span class="section-label">ইউটিলিটি ও প্রয়োজনীয় টুলস</span>
                <span class="text-xs font-semibold text-[var(--text-muted)]">টিন ও এমএফএস তথ্য</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div onclick="location.href='auto_tin.php'" class="service-hub-item">
                    <div class="icon-bubble bg-[#FFF1F2] text-[#E11D48]">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs sm:text-sm font-bold text-[var(--brand-dark)] truncate">অটো টিন সার্টিফিকেট</h4>
                        <p class="text-[11px] text-[var(--text-muted)] truncate mt-0.5">মুহূর্তেই টিন সার্টিফিকেট ডাউনলোড</p>
                    </div>
                </div>

                <div onclick="location.href='imei_service.php'" class="service-hub-item">
                    <div class="icon-bubble bg-[#E0F2FE] text-[#0866D9]">
                        <i class="fa-solid fa-mobile-screen"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs sm:text-sm font-bold text-[var(--brand-dark)] truncate">IMEI ট্র্যাকিং</h4>
                        <p class="text-[11px] text-[var(--text-muted)] truncate mt-0.5">হ্যান্ডসেট ট্র্যাকিং ও সিম হিস্টোরি</p>
                    </div>
                </div>

                <div onclick="location.href='mfs_service.php'" class="service-hub-item">
                    <div class="icon-bubble bg-[#FDF2F8] text-[#DB2777]">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs sm:text-sm font-bold text-[var(--brand-dark)] truncate">MFS ইনফরমেশন</h4>
                        <p class="text-[11px] text-[var(--text-muted)] truncate mt-0.5">বিকাশ / নগদ / রকেট ভেরিফিকেশন</p>
                    </div>
                </div>

                <div onclick="location.href='order_file.php'" class="service-hub-item">
                    <div class="icon-bubble bg-[#DCFCE7] text-[#10B981]">
                        <i class="fa-solid fa-file-arrow-up"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs sm:text-sm font-bold text-[var(--brand-dark)] truncate">কাস্টম ফাইল অর্ডার</h4>
                        <p class="text-[11px] text-[var(--text-muted)] truncate mt-0.5">অন্যান্য সকল ডকুমেন্ট প্রসেসিং</p>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- Floating Help Group -->
    <div class="fixed bottom-20 right-5 flex flex-col gap-2.5 z-40 md:bottom-7 md:right-7">
        <a href="<?php echo htmlspecialchars($whatsapp_group_link); ?>" target="_blank" class="w-11 h-11 rounded-full bg-[#10B981] hover:bg-[#059669] text-white flex items-center justify-center text-xl shadow-lg transition transform hover:scale-105">
            <i class="fa-brands fa-whatsapp"></i>
        </a>
        <a href="<?php echo htmlspecialchars($telegram_link); ?>" target="_blank" class="w-11 h-11 rounded-full bg-[#0866D9] hover:bg-[#0654b3] text-white flex items-center justify-center text-xl shadow-lg transition transform hover:scale-105">
            <i class="fa-brands fa-telegram"></i>
        </a>
    </div>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-bar fixed bottom-0 inset-x-0 py-2.5 flex items-center justify-around z-50">
        <a href="dashboard.php" class="flex flex-col items-center gap-1 text-[var(--brand-blue)] text-[11px] font-bold">
            <i class="fa-solid fa-house text-base"></i>
            <span>হোম</span>
        </a>
        <a href="work_history.php" class="flex flex-col items-center gap-1 text-[var(--text-muted)] text-[11px] font-semibold">
            <i class="fa-solid fa-clock-rotate-left text-base"></i>
            <span>হিস্টোরি</span>
        </a>
        <a href="recharge.php" class="flex flex-col items-center gap-1 text-[var(--text-muted)] text-[11px] font-semibold">
            <i class="fa-solid fa-wallet text-base"></i>
            <span>রিচার্জ</span>
        </a>
        <a href="profile.php" class="flex flex-col items-center gap-1 text-[var(--text-muted)] text-[11px] font-semibold">
            <i class="fa-solid fa-user-gear text-base"></i>
            <span>প্রোফাইল</span>
        </a>
    </nav>

    <!-- Control Panel Side Drawer -->
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden" id="drawerOverlay"></div>
    <div class="fixed top-0 right-[-320px] w-80 h-full bg-[var(--navbar-bg)] shadow-2xl z-50 transition-all duration-300 flex flex-col border-l border-[var(--border-soft)]" id="drawerMenu">
        <div class="p-5 bg-gradient-to-r from-[#123B70] to-[#0866D9] text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-layer-group text-[#08B8D0]"></i>
                <h5 class="font-bold text-sm">ShebaKit কন্ট্রোল প্যানেল</h5>
            </div>
            <button id="closeDrawerBtn" class="text-white text-lg"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="flex-1 overflow-y-auto p-2.5 space-y-1">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[var(--brand-blue)] bg-[var(--stat-box)] font-bold text-xs"><i class="fa-solid fa-house text-[var(--brand-blue)] w-5"></i> হোম ড্যাশবোর্ড</a>
            <a href="serverCopy.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[var(--text-dark)] hover:bg-[var(--stat-box)] hover:text-[var(--brand-blue)] font-bold text-xs transition"><i class="fa-solid fa-server text-[#08B8D0] w-5"></i> অটো সার্ভার কপি</a>
            <a href="auto_tin.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[var(--text-dark)] hover:bg-[var(--stat-box)] hover:text-[var(--brand-blue)] font-bold text-xs transition"><i class="fa-solid fa-file-invoice text-rose-600 w-5"></i> অটো টিন সার্টিফিকেট</a>
            <a href="order_file.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[var(--text-dark)] hover:bg-[var(--stat-box)] hover:text-[var(--brand-blue)] font-bold text-xs transition"><i class="fa-solid fa-file-arrow-up text-[#10B981] w-5"></i> অর্ডার ফাইল</a>
            <a href="order_text.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[var(--text-dark)] hover:bg-[var(--stat-box)] hover:text-[var(--brand-blue)] font-bold text-xs transition"><i class="fa-solid fa-pen-to-square text-[var(--brand-blue)] w-5"></i> অর্ডার টেক্সট</a>
            <a href="numbertobio.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[var(--text-dark)] hover:bg-[var(--stat-box)] hover:text-[var(--brand-blue)] font-bold text-xs transition"><i class="fa-solid fa-sim-card text-[#08B8D0] w-5"></i> নাম্বার টু বায়োমেট্রিক</a>
            <a href="call_sms_list.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[var(--text-dark)] hover:bg-[var(--stat-box)] hover:text-[var(--brand-blue)] font-bold text-xs transition"><i class="fa-solid fa-phone-volume text-pink-600 w-5"></i> কল ও এসএমএস হিস্ট্রি</a>
            <a href="location_service.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[var(--text-dark)] hover:bg-[var(--stat-box)] hover:text-[var(--brand-blue)] font-bold text-xs transition"><i class="fa-solid fa-location-dot text-[var(--brand-blue)] w-5"></i> লোকেশন ট্র্যাকিং</a>
            <a href="imei_service.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[var(--text-dark)] hover:bg-[var(--stat-box)] hover:text-[var(--brand-blue)] font-bold text-xs transition"><i class="fa-solid fa-mobile-screen text-amber-500 w-5"></i> IMEI সার্ভিস</a>
            <a href="mfs_service.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[var(--text-dark)] hover:bg-[var(--stat-box)] hover:text-[var(--brand-blue)] font-bold text-xs transition"><i class="fa-solid fa-wallet text-[#08B8D0] w-5"></i> MFS NID সার্ভিস</a>
            <a href="recharge.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[var(--text-dark)] hover:bg-[var(--stat-box)] hover:text-[var(--brand-blue)] font-bold text-xs transition"><i class="fa-solid fa-circle-plus text-[#10B981] w-5"></i> ব্যালেন্স রিচার্জ</a>
            <a href="work_history.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[var(--text-dark)] hover:bg-[var(--stat-box)] hover:text-[var(--brand-blue)] font-bold text-xs transition"><i class="fa-solid fa-clock-rotate-left text-[var(--brand-blue)] w-5"></i> অর্ডার হিস্টোরি</a>
            <a href="profile.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[var(--text-dark)] hover:bg-[var(--stat-box)] hover:text-[var(--brand-blue)] font-bold text-xs transition"><i class="fa-solid fa-user-gear text-slate-500 w-5"></i> আমার প্রোফাইল</a>
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-[#EF4444] hover:bg-red-500/10 font-bold text-xs transition"><i class="fa-solid fa-arrow-right-from-bracket w-5"></i> লগআউট</a>
        </div>
    </div>

    <script>
        // Theme Persistence and Toggle Logic
        const currentTheme = localStorage.getItem('shebakit_theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
        updateThemeIcon(currentTheme);

        $('#themeToggleBtn').click(function() {
            const activeTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = activeTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('shebakit_theme', newTheme);
            updateThemeIcon(newTheme);
        });

        function updateThemeIcon(theme) {
            if (theme === 'dark') {
                $('#themeIcon').removeClass('fa-moon').addClass('fa-sun text-amber-400');
            } else {
                $('#themeIcon').removeClass('fa-sun text-amber-400').addClass('fa-moon text-[var(--brand-dark)]');
            }
        }

        // Side Drawer
        $('#openDrawerBtn').click(function() {
            $('#drawerOverlay').fadeIn(200);
            $('#drawerMenu').css('right', '0');
        });

        $('#closeDrawerBtn, #drawerOverlay').click(function() {
            $('#drawerOverlay').fadeOut(200);
            $('#drawerMenu').css('right', '-320px');
        });

        // Realtime Service Search Filtering
        $('#dashboardSearchInput').on('keyup', function() {
            var value = $(this).val().toLowerCase().trim();
            $('.service-hub-item').each(function() {
                var text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(value) > -1);
            });

            $('.service-group').each(function() {
                var visibleCards = $(this).find('.service-hub-item:visible').length;
                $(this).toggle(visibleCards > 0);
            });
        });

        function loadStats() {
            $.ajax({
                url: 'api/get_user_stats.php',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data && data.success) {
                        $('#balance-display').text(data.user_balance || '0.00');
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