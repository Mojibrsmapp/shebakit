<?php
// ১. ডাটাবেজ সংযোগ নিশ্চিতকরণ
if (file_exists("includes/connection.php")) {
    include_once("includes/connection.php");
} elseif (file_exists("../includes/connection.php")) {
    include_once("../includes/connection.php");
} elseif (file_exists("includes/configuration.php")) {
    include_once("includes/configuration.php");
}

include_once("header.php");

if (!isset($conn) && isset($db)) {
    $conn = $db;
}

// ২. পেন্ডিং রিচার্জ কাউন্ট
$pending_deposits = 0;
if (isset($conn) && $conn) {
    $dep_q = $conn->query("SELECT COUNT(*) AS total FROM deposit_requests WHERE status = 'pending'");
    if ($dep_q && $row_dep = $dep_q->fetch_assoc()) {
        $pending_deposits = intval($row_dep['total']);
    }
}

// ৩. অর্ডার মেট্রিক্স (orders + order_list)
$total_orders = 0;
$today_orders = 0;
$pending_orders = 0;
$success_orders = 0;
$pending_file_orders = 0;

$today_date = date('Y-m-d');

if (isset($conn) && $conn) {
    // A. orders টেবিল
    $q1 = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN LOWER(TRIM(status)) = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN LOWER(TRIM(status)) IN ('success', 'completed') THEN 1 ELSE 0 END) as success,
        SUM(CASE WHEN DATE(created_at) = '$today_date' THEN 1 ELSE 0 END) as today
        FROM orders");
    if ($q1 && $r1 = $q1->fetch_assoc()) {
        $total_orders   += intval($r1['total']);
        $pending_orders += intval($r1['pending']);
        $success_orders += intval($r1['success']);
        $today_orders   += intval($r1['today']);
    }

    // B. order_list টেবিল (সাইন কপি ও ফাইল অর্ডার)
    $q2 = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN LOWER(TRIM(status)) = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN LOWER(TRIM(status)) IN ('success', 'completed') THEN 1 ELSE 0 END) as success,
        SUM(CASE WHEN DATE(order_time) = '$today_date' THEN 1 ELSE 0 END) as today
        FROM order_list");
    if ($q2 && $r2 = $q2->fetch_assoc()) {
        $total_orders        += intval($r2['total']);
        $pending_orders      += intval($r2['pending']);
        $pending_file_orders += intval($r2['pending']);
        $success_orders      += intval($r2['success']);
        $today_orders        += intval($r2['today']);
    }
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Hind+Siliguri:wght@500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --bg-main: #F3F8FF;
        --card-bg: #FFFFFF;
        --primary-blue: #0866D9;
        --secondary-cyan: #08B8D0;
        --heading-dark: #123B70;
        --body-muted: #52657D;
        --border-soft: #DCE8F5;
        --success: #10B981;
        --warning: #F59E0B;
        --danger: #EF4444;
    }

    body {
        background-color: var(--bg-main) !important;
        color: var(--heading-dark);
        font-family: 'Hind Siliguri', 'Plus Jakarta Sans', sans-serif !important;
        margin: 0;
        padding: 0;
    }

    .admin-dashboard-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 12px 14px 40px;
    }

    .metric-card {
        background: var(--card-bg);
        border: 1.5px solid var(--border-soft);
        border-radius: 18px;
        padding: 14px;
        box-shadow: 0 4px 16px rgba(18, 59, 112, 0.04);
        position: relative;
        overflow: hidden;
        transition: all 0.2s ease;
    }
    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(18, 59, 112, 0.08);
        border-color: var(--primary-blue);
    }
    .metric-card .icon-chip {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }

    .panel-card {
        background: var(--card-bg);
        border: 1.5px solid var(--border-soft);
        border-radius: 20px;
        padding: 18px 16px;
        box-shadow: 0 6px 20px rgba(18, 59, 112, 0.04);
        margin-bottom: 20px;
    }

    .action-pill-tile {
        background: #F8FAFD;
        border: 1.5px solid var(--border-soft);
        border-radius: 14px;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none !important;
        transition: all 0.2s ease;
    }
    .action-pill-tile:hover {
        background: #FFFFFF;
        border-color: var(--primary-blue);
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(8, 102, 217, 0.12);
    }
    .action-pill-tile .tile-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
    }
    .admin-table thead th {
        background: #F8FAFD;
        color: var(--heading-dark);
        font-weight: 800;
        padding: 9px 10px;
        border-bottom: 1.5px solid var(--border-soft);
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .admin-table tbody td {
        padding: 9px 10px;
        border-bottom: 1px solid var(--border-soft);
        color: var(--body-muted);
        vertical-align: middle;
    }
    .admin-table tfoot th {
        padding: 10px;
        background: #F8FAFD;
        color: var(--heading-dark);
        font-weight: 900;
        border-top: 1.5px solid var(--border-soft);
    }

    .zero-user-row {
        background: #F8FAFD;
        border: 1px solid var(--border-soft);
        border-radius: 12px;
        padding: 8px 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 6px;
        transition: all 0.15s ease;
    }
    .zero-user-row:hover {
        background: #FFFFFF;
        border-color: var(--danger);
    }
</style>

<div class="admin-dashboard-wrapper space-y-4">

    <!-- Row 1: ফান্ড, ব্যালেন্স ও মূল স্ট্যাটাস -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
        
        <div class="metric-card flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-[#52657D]">মোট ইউজার</span>
                <div class="icon-chip bg-[#EFF6FF] text-[#0866D9] border border-[#BFDBFE]">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl md:text-3xl font-black text-[#123B70] tracking-tight" id="totalUsers">...</h3>
                <span class="text-[10.5px] text-[#52657D] block mt-0.5">নিবন্ধিত কাস্টমার</span>
            </div>
        </div>

        <div class="metric-card flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-[#52657D]">ইউজার ব্যালেন্স</span>
                <div class="icon-chip bg-[#DCFCE7] text-[#15803D] border border-[#BBF7D0]">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl md:text-3xl font-black text-[#15803D] tracking-tight">৳ <span id="totalBalance">0.00</span></h3>
                <span class="text-[10.5px] text-[#52657D] block mt-0.5">গ্রাহকদের মোট ফান্ড</span>
            </div>
        </div>

        <div class="metric-card flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-[#52657D]">পেন্ডিং রিচার্জ</span>
                <div class="icon-chip bg-[#FEF3C7] text-[#B45309] border border-[#FDE68A]">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl md:text-3xl font-black text-[#B45309] tracking-tight" id="pendingDepositsCount"><?php echo $pending_deposits; ?> টি</h3>
                <span class="text-[10.5px] text-[#52657D] block mt-0.5">অনুমোদন প্রয়োজন</span>
            </div>
        </div>

        <a href="all_orders_history.php" class="metric-card flex flex-col justify-between border-rose-200 bg-rose-50/20 text-decoration-none">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-rose-700">পেন্ডিং অর্ডার</span>
                <div class="icon-chip bg-[#FEE2E2] text-[#DC2626] border border-[#FECACA]">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl md:text-3xl font-black text-[#DC2626] tracking-tight" id="pendingOrdersCount">
                    <?php echo $pending_orders; ?> টি
                </h3>
                <span class="text-[10.5px] text-rose-600 font-bold block mt-0.5">সব অর্ডার পর্যবেক্ষণ &rarr;</span>
            </div>
        </a>

    </div>

    <!-- Row 2: অর্ডারের সারসংক্ষেপ ও ফাইল শর্টকাট -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
        
        <div class="metric-card flex flex-col justify-between">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold text-[#52657D]">মোট অর্ডার</span>
                <div class="icon-chip bg-sky-50 text-sky-600 border border-sky-200">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
            <div>
                <h3 class="text-xl md:text-2xl font-black text-[#123B70] tracking-tight" id="totalOrdersCount">
                    <?php echo $total_orders; ?> টি
                </h3>
                <span class="text-[10px] text-[#52657D] block">সর্বমোট সাবমিটকৃত অর্ডার</span>
            </div>
        </div>

        <div class="metric-card flex flex-col justify-between">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold text-[#52657D]">আজকের অর্ডার</span>
                <div class="icon-chip bg-indigo-50 text-indigo-600 border border-indigo-200">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
            </div>
            <div>
                <h3 class="text-xl md:text-2xl font-black text-indigo-600 tracking-tight" id="todayOrdersCount">
                    <?php echo $today_orders; ?> টি
                </h3>
                <span class="text-[10px] text-[#52657D] block">আজকের নতুন কাজ</span>
            </div>
        </div>

        <div class="metric-card flex flex-col justify-between">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold text-[#52657D]">সফল অর্ডার</span>
                <div class="icon-chip bg-emerald-50 text-emerald-600 border border-emerald-200">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div>
                <h3 class="text-xl md:text-2xl font-black text-emerald-600 tracking-tight" id="successOrdersCount">
                    <?php echo $success_orders; ?> টি
                </h3>
                <span class="text-[10px] text-[#52657D] block">ডেলিভারি সম্পন্ন হয়েছে</span>
            </div>
        </div>

        <a href="all_orders_history.php" class="metric-card flex flex-col justify-between text-decoration-none group hover:border-[#0866D9]">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold text-[#0866D9]">সাইন/ফাইল পেন্ডিং</span>
                <div class="icon-chip bg-[#EFF6FF] text-[#0866D9] border border-[#BFDBFE] group-hover:bg-[#0866D9] group-hover:text-white transition">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
            </div>
            <div>
                <h3 class="text-xl md:text-2xl font-black text-rose-600 tracking-tight" id="pendingFileSignBadge">
                    <?php echo $pending_file_orders; ?> টি পেন্ডিং
                </h3>
                <span class="text-[10px] text-[#0866D9] font-bold block">ফাইল অর্ডার সমাধান করুন &rarr;</span>
            </div>
        </a>

    </div>

    <!-- Master Management Quick Grid -->
    <div class="panel-card">
        <div class="flex items-center justify-between mb-3.5 pb-2.5 border-b border-[#DCE8F5]">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-[#EFF6FF] text-[#0866D9] border border-[#BFDBFE] flex items-center justify-center text-xs">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <h4 class="text-xs md:text-sm font-extrabold text-[#123B70] m-0">মাস্টার সার্ভিস ও ম্যানেজমেন্ট কন্ট্রোল</h4>
            </div>
            <span class="text-[10.5px] font-bold text-[#0866D9] bg-[#EFF6FF] border border-[#BFDBFE] px-2.5 py-0.5 rounded-full">কন্ট্রোল প্যানেল</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
            
            <a href="all_orders_history.php" class="action-pill-tile">
                <div class="tile-icon bg-sky-50 text-sky-600 border border-sky-200"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div class="min-w-0">
                    <span class="text-xs font-bold text-[#123B70] block truncate">সকল অর্ডার</span>
                    <span class="text-[10px] text-[#52657D] block">লগ ও ডেলিভারি</span>
                </div>
            </a>

            <a href="manage_services.php" class="action-pill-tile">
                <div class="tile-icon bg-rose-50 text-rose-600 border border-rose-200"><i class="fa-solid fa-power-off"></i></div>
                <div class="min-w-0">
                    <span class="text-xs font-bold text-[#123B70] block truncate">সার্ভিস সুইচ</span>
                    <span class="text-[10px] text-[#52657D] block">অন / অফ</span>
                </div>
            </a>

            <a href="all_pricing.php" class="action-pill-tile">
                <div class="tile-icon bg-amber-50 text-amber-600 border border-amber-200"><i class="fa-solid fa-coins"></i></div>
                <div class="min-w-0">
                    <span class="text-xs font-bold text-[#123B70] block truncate">মাস্টার রেট</span>
                    <span class="text-[10px] text-[#52657D] block">প্রাইসিং তালিকা</span>
                </div>
            </a>

            <a href="manage_deposits.php" class="action-pill-tile">
                <div class="tile-icon bg-emerald-50 text-emerald-600 border border-emerald-200"><i class="fa-solid fa-money-bill-transfer"></i></div>
                <div class="min-w-0">
                    <span class="text-xs font-bold text-[#123B70] block truncate">পেন্ডিং ফান্ড</span>
                    <span class="text-[10px] text-[#52657D] block">রিচার্জ অনুমোদন</span>
                </div>
            </a>

            <a href="settings.php" class="action-pill-tile">
                <div class="tile-icon bg-indigo-50 text-indigo-600 border border-indigo-200"><i class="fa-solid fa-sliders"></i></div>
                <div class="min-w-0">
                    <span class="text-xs font-bold text-[#123B70] block truncate">সাইট সেটিংস</span>
                    <span class="text-[10px] text-[#52657D] block">লিংক ও নম্বর</span>
                </div>
            </a>

            <a href="work_history.php" class="action-pill-tile">
                <div class="tile-icon bg-purple-50 text-purple-600 border border-purple-200"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <div class="min-w-0">
                    <span class="text-xs font-bold text-[#123B70] block truncate">কাজের হিস্টোরি</span>
                    <span class="text-[10px] text-[#52657D] block">ইউজার সামারি</span>
                </div>
            </a>

            <a href="add_service.php" class="action-pill-tile">
                <div class="tile-icon bg-teal-50 text-teal-600 border border-teal-200"><i class="fa-solid fa-circle-plus"></i></div>
                <div class="min-w-0">
                    <span class="text-xs font-bold text-[#123B70] block truncate">নতুন সার্ভিস</span>
                    <span class="text-[10px] text-[#52657D] block">সার্ভিস তৈরি</span>
                </div>
            </a>

            <a href="user_list.php" class="action-pill-tile">
                <div class="tile-icon bg-cyan-50 text-cyan-600 border border-cyan-200"><i class="fa-solid fa-users-gear"></i></div>
                <div class="min-w-0">
                    <span class="text-xs font-bold text-[#123B70] block truncate">ইউজার তালিকা</span>
                    <span class="text-[10px] text-[#52657D] block">ম্যানেজ একাউন্ট</span>
                </div>
            </a>

            <a href="balance_add_remove.php" class="action-pill-tile">
                <div class="tile-icon bg-pink-50 text-pink-600 border border-pink-200"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div class="min-w-0">
                    <span class="text-xs font-bold text-[#123B70] block truncate">ব্যালেন্স এড/কাট</span>
                    <span class="text-[10px] text-[#52657D] block">ইউজার ফান্ড</span>
                </div>
            </a>

            <a href="createLink.php" class="action-pill-tile">
                <div class="tile-icon bg-blue-50 text-blue-600 border border-blue-200"><i class="fa-solid fa-link"></i></div>
                <div class="min-w-0">
                    <span class="text-xs font-bold text-[#123B70] block truncate">ইনভাইট লিংক</span>
                    <span class="text-[10px] text-[#52657D] block">রেজিস্ট্রেশন URL</span>
                </div>
            </a>

            <a href="update_marquee.php" class="action-pill-tile">
                <div class="tile-icon bg-orange-50 text-orange-600 border border-orange-200"><i class="fa-solid fa-bullhorn"></i></div>
                <div class="min-w-0">
                    <span class="text-xs font-bold text-[#123B70] block truncate">নোটিশ মডিউল</span>
                    <span class="text-[10px] text-[#52657D] block">মারকুই টেক্সট</span>
                </div>
            </a>

            <a href="profile.php" class="action-pill-tile">
                <div class="tile-icon bg-emerald-50 text-emerald-600 border border-emerald-200"><i class="fa-solid fa-user-shield"></i></div>
                <div class="min-w-0">
                    <span class="text-xs font-bold text-[#123B70] block truncate">এডমিন প্রোফাইল</span>
                    <span class="text-[10px] text-[#52657D] block">সিকিউরিটি ও লগ</span>
                </div>
            </a>

        </div>
    </div>

    <!-- Analytics & Activity Section -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

        <!-- Zero Balance Users (4 cols) -->
        <div class="lg:col-span-4 panel-card flex flex-col mb-0">
            <div class="flex items-center justify-between pb-3 mb-2.5 border-b border-[#DCE8F5]">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-[#EF4444] text-xs"></i>
                    <h5 class="font-extrabold text-xs md:text-sm text-[#123B70] m-0">শূন্য ব্যালেন্স ইউজার</h5>
                </div>
                <span class="text-[10.5px] font-black bg-[#FEE2E2] text-[#DC2626] border border-[#FECACA] px-2 py-0.5 rounded-full" id="zeroBalanceCount">0</span>
            </div>

            <div id="zeroBalanceList" class="flex-1 overflow-y-auto max-h-[440px] pr-1">
                <div class="text-center py-8 text-[#52657D] text-xs font-medium">লোড হচ্ছে...</div>
            </div>
        </div>

        <!-- Work Summary & Daily Orders (8 cols) -->
        <div class="lg:col-span-8 panel-card space-y-4 mb-0">
            
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2.5 pb-2.5 border-b border-[#DCE8F5]">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#10B981] animate-pulse"></span>
                    <h5 class="font-extrabold text-xs md:text-sm text-[#123B70] m-0" id="selectedDateTitle">তারিখ অনুযায়ী কাজের হিসাব</h5>
                </div>
                <div>
                    <input type="date" id="start_date" name="start_date" class="border border-[#DCE8F5] rounded-xl px-2.5 py-1 text-xs font-bold text-[#123B70] bg-[#F8FAFD] outline-none focus:border-[#0866D9] transition">
                </div>
            </div>

            <!-- Summary Table 1 -->
            <div>
                <h6 class="text-[10.5px] font-extrabold uppercase tracking-wider text-[#52657D] mb-2">কাজের সামগ্রিক সারাংশ</h6>
                <div class="overflow-x-auto rounded-xl border border-[#DCE8F5]">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>কাজের ধরন</th>
                                <th class="text-center">অর্ডার সংখ্যা</th>
                                <th class="text-right">মোট চার্জ</th>
                            </tr>
                        </thead>
                        <tbody id="historyWorkSummary">
                            <tr><td colspan="4" class="p-3 text-center text-slate-400">ডাটা লোড হচ্ছে...</td></tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2">সর্বমোট</th>
                                <th class="text-center" id="totalHistoryOrders">0 টি</th>
                                <th class="text-right text-[#10B981]">৳ <span id="totalHistoryPrice">0.00</span></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Summary Table 2 -->
            <div>
                <h6 class="text-[10.5px] font-extrabold uppercase tracking-wider text-[#52657D] mb-2">সার্ভিস অর্ডার স্ট্যাটাস ও কাউন্ট</h6>
                <div class="overflow-x-auto rounded-xl border border-[#DCE8F5]">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>সার্ভিস নাম</th>
                                <th class="text-center text-amber-600">পেন্ডিং</th>
                                <th class="text-center text-emerald-600">সফল</th>
                                <th class="text-center text-rose-600">অন্যান্য</th>
                            </tr>
                        </thead>
                        <tbody id="orderListCountsSummary">
                            <tr><td colspan="5" class="p-3 text-center text-slate-400">ডাটা লোড হচ্ছে...</td></tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2">মোট কাউন্ট</th>
                                <th class="text-center text-amber-600" id="totalPendingCount">0</th>
                                <th class="text-center text-emerald-600" id="totalSuccessCount">0</th>
                                <th class="text-center text-rose-600" id="totalOtherCount">0</th>
                            </tr>
                            <tr>
                                <th colspan="2">মোট চার্জ</th>
                                <th class="text-center text-amber-600">৳ <span id="totalPendingPrice">0.00</span></th>
                                <th class="text-center text-emerald-600">৳ <span id="totalSuccessPrice">0.00</span></th>
                                <th class="text-center text-rose-600">৳ <span id="totalOtherPrice">0.00</span></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>

    </div>

</div>

<?php include_once("footer.php"); ?>

<script>
    $(document).ready(function() {
        fetchUserData();
        fetchZeroBalanceUsers();
        syncFilePendingOrders();

        // 10 সেকেন্ড পর পর লাইভ পোলিং
        setInterval(function() {
            syncFilePendingOrders();
        }, 10000);

        let today = new Date().toISOString().split('T')[0];
        $('#start_date').val(today);
        fetchData(today);

        $('#start_date').change(function() {
            let startDate = $(this).val();
            fetchData(startDate);
        });
    });

    // file_order_pending.php থেকে লাইভ পেন্ডিং ডেটা রিড
    function syncFilePendingOrders() {
        $.ajax({
            url: 'order/list/file_order_pending.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (Array.isArray(response)) {
                    let filePending = response.length;
                    $('#pendingOrdersCount').text(filePending + " টি");
                    $('#pendingFileSignBadge').text(filePending + " টি পেন্ডিং");
                }
            },
            error: function() {
                // PHP এর রেন্ডার করা কাউন্ট অক্ষত থাকবে
            }
        });
    }

    function fetchData(startDate) {
        let formattedDate = new Date(startDate).toLocaleDateString('bn-BD', {
            weekday: 'short', year: 'numeric', month: 'short', day: 'numeric'
        });
        $('#selectedDateTitle').text("কাজের হিসাব: " + formattedDate);

        $.ajax({
            url: 'order/list/fetch_total_order_summery.php',
            type: 'GET',
            data: { start_date: startDate },
            dataType: 'json',
            success: function(response) {
                let historyWorkSummaryHtml = '';
                let orderListCountsHtml = '';
                let serial = 1;

                let totalPendingCount = 0, totalSuccessCount = 0, totalOtherCount = 0;
                let totalPendingPrice = 0, totalSuccessPrice = 0, totalOtherPrice = 0;
                let totalHistoryOrders = 0, totalHistoryPrice = 0;

                if (response.historyWorkSummary && response.historyWorkSummary.length > 0) {
                    response.historyWorkSummary.forEach(order => {
                        historyWorkSummaryHtml += `
                            <tr class="hover:bg-slate-50 transition">
                                <td class="font-bold text-[#0866D9]">${serial}</td>
                                <td class="font-bold text-[#123B70]">${order.order_type}</td>
                                <td class="text-center"><span class="bg-[#EFF6FF] text-[#0866D9] border border-[#BFDBFE] font-bold px-2 py-0.5 rounded-full text-[10.5px]">${order.totalOrders} টি</span></td>
                                <td class="text-right font-black text-[#123B70]">৳ ${parseFloat(order.totalPrice).toFixed(2)}</td>
                            </tr>
                        `;
                        totalHistoryOrders += parseInt(order.totalOrders) || 0;
                        totalHistoryPrice += parseFloat(order.totalPrice) || 0;
                        serial++;
                    });
                } else {
                    historyWorkSummaryHtml = '<tr><td colspan="4" class="p-3 text-center text-slate-400 text-xs">কোনো রেকর্ড পাওয়া যায়নি</td></tr>';
                }

                let orderTypeSerial = 1;
                if (response.orderListCounts) {
                    for (let orderType in response.orderListCounts) {
                        let order = response.orderListCounts[orderType];
                        orderListCountsHtml += `
                            <tr class="hover:bg-slate-50 transition">
                                <td class="font-bold text-[#0866D9]">${orderTypeSerial}</td>
                                <td class="font-bold text-[#123B70]">${orderType}</td>
                                <td class="text-center"><span class="text-amber-600 font-bold">${order.pending_count}</span> <span class="text-[9.5px] text-slate-400">(৳${order.pending_price})</span></td>
                                <td class="text-center"><span class="text-emerald-600 font-bold">${order.success_count}</span> <span class="text-[9.5px] text-slate-400">(৳${order.success_price})</span></td>
                                <td class="text-center"><span class="text-rose-600 font-bold">${order.other_count}</span> <span class="text-[9.5px] text-slate-400">(৳${order.other_price})</span></td>
                            </tr>
                        `;
                        totalPendingCount += parseInt(order.pending_count) || 0;
                        totalSuccessCount += parseInt(order.success_count) || 0;
                        totalOtherCount += parseInt(order.other_count) || 0;

                        totalPendingPrice += parseFloat(order.pending_price) || 0;
                        totalSuccessPrice += parseFloat(order.success_price) || 0;
                        totalOtherPrice += parseFloat(order.other_price) || 0;

                        orderTypeSerial++;
                    }
                } else {
                    orderListCountsHtml = '<tr><td colspan="5" class="p-3 text-center text-slate-400 text-xs">কোনো অর্ডার পাওয়া যায়নি</td></tr>';
                }

                $('#historyWorkSummary').html(historyWorkSummaryHtml);
                $('#orderListCountsSummary').html(orderListCountsHtml);

                $('#totalHistoryOrders').text(totalHistoryOrders + ' টি');
                $('#totalHistoryPrice').text(totalHistoryPrice.toFixed(2));

                $('#totalPendingCount').text(totalPendingCount);
                $('#totalSuccessCount').text(totalSuccessCount);
                $('#totalOtherCount').text(totalOtherCount);

                $('#totalPendingPrice').text(totalPendingPrice.toFixed(2));
                $('#totalSuccessPrice').text(totalSuccessPrice.toFixed(2));
                $('#totalOtherPrice').text(totalOtherPrice.toFixed(2));
            },
            error: function() {
                $('#historyWorkSummary').html('<tr><td colspan="4" class="p-3 text-center text-rose-500 text-xs">ডাটা লোড করতে সমস্যা হয়েছে</td></tr>');
                $('#orderListCountsSummary').html('<tr><td colspan="5" class="p-3 text-center text-rose-500 text-xs">ডাটা লোড করতে সমস্যা হয়েছে</td></tr>');
            }
        });
    }

    function fetchUserData() {
        $.ajax({
            url: 'order/list/fetch_user_data.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#totalUsers').text(response.totalUsers || 0);
                $('#totalBalance').text(response.totalBalance || '0.00');
            },
            error: function() {
                $('#totalUsers').text("0");
                $('#totalBalance').text("0.00");
            }
        });
    }

    function fetchZeroBalanceUsers() {
        $.ajax({
            url: 'order/list/fetch_zero_balance_users.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                let zeroBalanceHtml = '';
                let zeroBalanceCount = 0;

                if (response.zeroBalanceUsers && response.zeroBalanceUsers.length > 0) {
                    zeroBalanceCount = response.zeroBalanceUsers.length;
                    response.zeroBalanceUsers.forEach((user) => {
                        zeroBalanceHtml += `
                            <div class="zero-user-row">
                                <div class="flex-1 min-w-0">
                                    <h6 class="font-bold text-xs text-[#123B70] truncate m-0">${user.name || 'অজানা ইউজার'}</h6>
                                    <p class="text-[9.5px] text-slate-400 font-mono truncate m-0">#${user.id || ''} | ${user.phone || 'ফোন নেই'}</p>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="text-[10px] font-black text-rose-600 bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded-lg">৳0.00</span>
                                    <button class="bg-[#0866D9] hover:bg-[#0654b3] text-white rounded-lg px-2 py-1 text-xs font-bold transition flex items-center gap-1" onclick="addBalanceToUser('${user.id}')">
                                        <i class="fa-solid fa-plus text-[9px]"></i> ফান্ড
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    zeroBalanceHtml = '<div class="text-center py-8 text-emerald-600 font-bold text-xs"><i class="fa-solid fa-circle-check text-base block mb-1"></i>কোনো শূন্য ব্যালেন্স ইউজার নেই</div>';
                }

                $('#zeroBalanceList').html(zeroBalanceHtml);
                $('#zeroBalanceCount').text(zeroBalanceCount);
            },
            error: function() {
                $('#zeroBalanceList').html('<div class="text-center py-6 text-rose-500 text-xs">লোড করা সম্ভব হয়নি</div>');
            }
        });
    }

    function addBalanceToUser(userId) {
        window.location.href = 'balance_add_remove.php?user_id=' + encodeURIComponent(userId);
    }
</script>