<?php
// Admin access uses the database session; no external status endpoint is required.
session_start();
include_once("includes/configuration.php");

if (isset($_SESSION['admin_token'])) {
    $token_user =$_SESSION['admin_token'];

    $stmt =$conn->prepare("SELECT * FROM admin_user WHERE token = ? LIMIT 1");
    if ($stmt) {$stmt->bind_param("s", $token_user);$stmt->execute();
        $result =$stmt->get_result();

        if ($result &&$result->num_rows > 0) {
            $row =$result->fetch_assoc();
            $admin_id =$row["id"];
            $admin_balance =$row["balance"] ?? 0.00;
            $admin_email =$row["email"] ?? '';
            $admin_name =$row["name"] ?? 'Admin';
            $admin_token =$row["token"] ?? '';
            $admin_role = strtolower((string)($row["role"] ?? $_SESSION['admin_role'] ?? 'admin'));
            if (!in_array($admin_role, ['admin', 'moderator'], true) || (int)($row['status'] ?? 0) !== 1) {
                unset($_SESSION['admin_token'], $_SESSION['admin_role']);
                header("Location: index.php");
                exit();
            }
            $_SESSION['admin_role'] = $admin_role;
            $admin_record = sheba_admin_role_record($conn, (string)$admin_token);
            $admin_permissions = $admin_record['permissions'] ?? sheba_default_permissions();
        } else {
            unset($_SESSION['admin_token']);
            header("Location: index.php");
            exit();
        }
        $stmt->close();
    } else {
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}

$pending_deposits = 0;
$dep_c =$conn->query("SELECT COUNT(*) as c FROM deposit_requests WHERE status = 'pending'");
if ($dep_c) {
    $pending_deposits = intval($dep_c->fetch_assoc()['c'] ?? 0);
}

$pending_orders = 0;
$ord_c =$conn->query("SELECT COUNT(*) as c FROM orders WHERE status = 'pending'");
if ($ord_c) {
    $pending_orders = intval($ord_c->fetch_assoc()['c'] ?? 0);
}

$current_page = basename($_SERVER['PHP_SELF']);
$adminLogoSrc = '/assets/logo_nav.png';
$adminLogoFallback = '/assets/logo.jpg';
$documentRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
$logoFile = realpath(dirname(__DIR__) . '/assets/logo_nav.png');
$logoFallbackFile = realpath(dirname(__DIR__) . '/assets/logo.jpg');
if ($documentRoot && $logoFile && strpos($logoFile, rtrim($documentRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) === 0) {
    $adminLogoSrc = str_replace(DIRECTORY_SEPARATOR, '/', substr($logoFile, strlen(rtrim($documentRoot, DIRECTORY_SEPARATOR))));
}
if ($documentRoot && $logoFallbackFile && strpos($logoFallbackFile, rtrim($documentRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) === 0) {
    $adminLogoFallback = str_replace(DIRECTORY_SEPARATOR, '/', substr($logoFallbackFile, strlen(rtrim($documentRoot, DIRECTORY_SEPARATOR))));
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ShebaKit - Enterprise Control Hub</title>

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
            background: #090514;
            color: #E2E8F0;
            min-height: 100vh;
        }

        .admin-topbar {
            background: rgba(14, 9, 29, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(168, 85, 247, 0.15);
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .admin-sidebar {
            width: 255px;
            background: #0B061A;
            border-right: 1px solid rgba(168, 85, 247, 0.12);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 50;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            overflow-y: auto;
        }

        @media (max-width: 1024px) {
            .admin-sidebar { left: -265px; }
            .admin-sidebar.open { left: 0 !important; }
            .main-content-layout { margin-left: 0 !important; }
        }

        @media (min-width: 1025px) {
            .main-content-layout { margin-left: 255px; }
        }

        .nav-category-header {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #7E69AB;
            padding: 16px 20px 6px;
        }

        .sidebar-menu-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            margin: 3px 12px;
            border-radius: 12px;
            color: #94A3B8;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .sidebar-menu-item:hover {
            background: rgba(168, 85, 247, 0.08);
            color: #F8FAFC;
            border-color: rgba(168, 85, 247, 0.15);
        }

        .sidebar-menu-item.active {
            background: linear-gradient(135deg, #7C3AED 0%, #9333EA 100%);
            color: #FFFFFF;
            box-shadow: 0 4px 18px rgba(124, 58, 237, 0.4);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .sidebar-menu-item i {
            width: 22px;
            font-size: 14px;
            text-align: center;
        }

        .counter-pill-sm {
            font-size: 10px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 20px;
            line-height: 1;
        }

        .admin-sidebar::-webkit-scrollbar { width: 3px; }
        .admin-sidebar::-webkit-scrollbar-thumb {
            background: rgba(168, 85, 247, 0.2);
            border-radius: 4px;
        }

        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(5, 2, 12, 0.7);
            backdrop-filter: blur(5px);
            z-index: 45;
            display: none;
        }
    </style>
</head>
<body>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <aside class="admin-sidebar" id="adminSidebar">

        <div class="p-4 border-b border-purple-900/20 flex items-center justify-between">
            <a href="dashboard.php" class="flex items-center gap-2.5 no-underline">
                <img src="<?php echo htmlspecialchars($adminLogoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="ShebaKit Logo" class="h-7 w-auto" onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($adminLogoFallback, ENT_QUOTES, 'UTF-8'); ?>';">
                <div>
                    <span class="text-[11px] font-black tracking-wider block bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">MASTER ENGINE</span>
                    <span class="text-[9.5px] text-purple-300/60 font-semibold block">v2.4 Control Hub</span>
                </div>
            </a>
            <button type="button" id="closeSidebarBtn" class="lg:hidden text-slate-400 hover:text-white text-base">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <?php if (($admin_role ?? 'admin') === 'moderator'): ?>
        <div class="py-2">
            <div class="nav-category-header">মডারেটর প্যানেল</div>
            <a href="moderator_panel.php" class="sidebar-menu-item <?php echo ($current_page === 'moderator_panel.php') ? 'active' : ''; ?>"><div class="flex items-center gap-2.5"><i class="fa-solid fa-layer-group text-purple-400"></i><span>আমার অনুমতি</span></div></a>
            <?php $modLabels = ['orders' => ['অর্ডার', 'fa-boxes-stacked'], 'history' => ['হিস্টোরি', 'fa-clock-rotate-left'], 'recharge' => ['রিচার্জ', 'fa-money-bill-transfer'], 'services' => ['সার্ভিস তালিকা', 'fa-list-check'], 'users' => ['ইউজার তালিকা', 'fa-users']]; ?>
            <?php foreach ($modLabels as $modKey => $modLabel): if (empty($admin_permissions[$modKey])) { continue; } ?>
                <a href="moderator_view.php?section=<?php echo urlencode($modKey); ?>" class="sidebar-menu-item <?php echo (($_GET['section'] ?? '') === $modKey) ? 'active' : ''; ?>"><div class="flex items-center gap-2.5"><i class="fa-solid <?php echo htmlspecialchars($modLabel[1]); ?> text-sky-400"></i><span><?php echo htmlspecialchars($modLabel[0]); ?></span></div></a>
            <?php endforeach; ?>
            <a href="profile.php" class="sidebar-menu-item <?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>"><div class="flex items-center gap-2.5"><i class="fa-solid fa-user text-slate-400"></i><span>নিজের পাসওয়ার্ড</span></div></a>
            <a href="logout.php" class="sidebar-menu-item text-rose-400/90"><div class="flex items-center gap-2.5"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>লগআউট</span></div></a>
        </div>
        <?php else: ?>
        <div class="py-2">

            <div class="nav-category-header">ওভারভিউ</div>
            <a href="dashboard.php" class="sidebar-menu-item <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-layer-group text-purple-400"></i>
                    <span>ড্যাশবোর্ড</span>
                </div>
            </a>

            <div class="nav-category-header">অর্ডার ও লেনদেন</div>
            <a href="all_orders_history.php" class="sidebar-menu-item <?php echo ($current_page === 'all_orders_history.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-boxes-stacked text-sky-400"></i>
                    <span>সকল অর্ডার ও লগ</span>
                </div>
                <?php if ($pending_orders > 0): ?>
                    <span class="counter-pill-sm bg-rose-500 text-white"><?php echo $pending_orders; ?></span>
                <?php endif; ?>
            </a>

            <a href="manage_deposits.php" class="sidebar-menu-item <?php echo ($current_page === 'manage_deposits.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-money-bill-transfer text-amber-400"></i>
                    <span>পেন্ডিং রিচার্জ</span>
                </div>
                <?php if ($pending_deposits > 0): ?>
                    <span class="counter-pill-sm bg-amber-500 text-white"><?php echo $pending_deposits; ?></span>
                <?php endif; ?>
            </a>

            <a href="work_history.php" class="sidebar-menu-item <?php echo ($current_page === 'work_history.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-clock-rotate-left text-violet-400"></i>
                    <span>কাজের হিস্টোরি</span>
                </div>
            </a>

            <div class="nav-category-header">সার্ভিস ও প্রাইসিং কন্ট্রোল</div>
            <a href="manage_services.php" class="sidebar-menu-item <?php echo ($current_page === 'manage_services.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-power-off text-rose-400"></i>
                    <span>সার্ভিস সুইচ</span>
                </div>
            </a>

            <a href="all_pricing.php" class="sidebar-menu-item <?php echo ($current_page === 'all_pricing.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-coins text-yellow-400"></i>
                    <span>মাস্টার প্রাইসিং</span>
                </div>
            </a>

            <a href="amount_auto.php" class="sidebar-menu-item <?php echo ($current_page === 'amount_auto.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-server text-cyan-400"></i>
                    <span>অটো সার্ভার কপি মূল্য</span>
                </div>
            </a>

            <a href="server_copy_settings.php" class="sidebar-menu-item <?php echo ($current_page === 'server_copy_settings.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-sliders text-sky-400"></i>
                    <span>Server Copy API ও কন্ট্রোল</span>
                </div>
            </a>

            <a href="add_service.php" class="sidebar-menu-item <?php echo ($current_page === 'add_service.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-plus text-emerald-400"></i>
                    <span>নতুন সার্ভিস</span>
                </div>
            </a>

            <div class="nav-category-header">ইউজার ও ফান্ডিং</div>
            <a href="user_list.php" class="sidebar-menu-item <?php echo ($current_page === 'user_list.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-users text-teal-400"></i>
                    <span>ইউজার তালিকা</span>
                </div>
            </a>

            <a href="balance_add_remove.php" class="sidebar-menu-item <?php echo ($current_page === 'balance_add_remove.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-wallet text-indigo-400"></i>
                    <span>ব্যালেন্স মডিফাই</span>
                </div>
            </a>

            <a href="createLink.php" class="sidebar-menu-item <?php echo ($current_page === 'createLink.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-link text-cyan-400"></i>
                    <span>ইনভাইট লিংক</span>
                </div>
            </a>

            <a href="moderators.php" class="sidebar-menu-item <?php echo ($current_page === 'moderators.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-user-shield text-fuchsia-400"></i>
                    <span>মডারেটর প্যানেল</span>
                </div>
            </a>

            <div class="nav-category-header">সিস্টেম ও কনফিগ</div>
            <a href="settings.php" class="sidebar-menu-item <?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-sliders text-purple-400"></i>
                    <span>সাইট সেটিংস</span>
                </div>
            </a>

            <a href="update_marquee.php" class="sidebar-menu-item <?php echo ($current_page === 'update_marquee.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-bullhorn text-pink-400"></i>
                    <span>নোটিশ মডিউল</span>
                </div>
            </a>

            <a href="profile.php" class="sidebar-menu-item <?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-user-shield text-slate-400"></i>
                    <span>প্রোফাইল</span>
                </div>
            </a>

            <a href="logout.php" class="sidebar-menu-item text-rose-400/90 hover:bg-rose-500/10 hover:text-rose-300">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>লগআউট</span>
                </div>
            </a>

        </div>
        <?php endif; ?>

    </aside>

    <div class="main-content-layout min-h-screen flex flex-col">

        <header class="admin-topbar px-4 py-2.5 flex items-center justify-between">

            <div class="flex items-center gap-3">
                <button type="button" id="toggleSidebarBtn" class="lg:hidden text-purple-300 hover:text-white text-base px-2.5 py-1.5 rounded-xl border border-purple-500/20 bg-purple-950/40 transition">
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
                <div>
                    <h4 class="font-extrabold text-sm text-white leading-tight">ShebaKit <span class="text-purple-400 font-normal">| Control Hub</span></h4>
                    <p class="text-[10.5px] text-purple-300/60 font-medium"><?php echo date('d M Y, l'); ?></p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">

                <?php if (($admin_role ?? 'admin') === 'admin'): ?>
                <a href="manage_deposits.php" class="relative bg-purple-950/40 hover:bg-purple-900/40 border border-purple-500/20 text-purple-200 w-8 h-8 rounded-xl flex items-center justify-center text-xs transition" title="পেন্ডিং রিচার্জ">
                    <i class="fa-solid fa-bell"></i>
                    <?php if ($pending_deposits > 0): ?>
                        <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-rose-500 text-white rounded-full text-[8.5px] font-black flex items-center justify-center">
                            <?php echo $pending_deposits; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>

                <?php if (($admin_role ?? 'admin') === 'admin'): ?>
                <div class="bg-gradient-to-r from-purple-950/60 to-indigo-950/60 border border-purple-500/25 px-3 py-1 rounded-xl flex items-center gap-2">
                    <i class="fa-solid fa-wallet text-purple-400 text-xs"></i>
                    <span class="text-xs font-bold text-white">৳ <?php echo number_format($admin_balance, 2); ?></span>
                </div>
                <?php else: ?>
                <span class="px-3 py-1 rounded-xl border border-purple-500/25 text-xs text-purple-200">Moderator</span>
                <?php endif; ?>

                <div class="relative">
                    <button type="button" id="adminProfileMenuBtn" class="flex items-center gap-2 focus:outline-none p-0.5 rounded-xl hover:ring-2 hover:ring-purple-400/40 transition">
                        <img src="<?php echo htmlspecialchars($adminLogoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Admin account" class="w-8 h-8 rounded-xl border border-purple-500/30 object-cover pointer-events-none" onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($adminLogoFallback, ENT_QUOTES, 'UTF-8'); ?>';">
                    </button>

                    <div id="adminDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-[#0E091D] rounded-2xl shadow-2xl border border-purple-500/20 py-2 z-50">
                        <div class="px-3.5 py-2 border-b border-purple-500/15">
                            <p class="text-xs font-bold text-white truncate"><?php echo htmlspecialchars($admin_name); ?></p>
                            <p class="text-[10px] text-purple-300/60 truncate"><?php echo htmlspecialchars($admin_email); ?></p>
                        </div>
                        <a href="profile.php" class="flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-purple-200 hover:bg-purple-900/30">
                            <i class="fa-solid fa-user-gear text-purple-400 w-4"></i> প্রোফাইল
                        </a>
                        <?php if (($admin_role ?? 'admin') === 'admin'): ?>
                        <a href="settings.php" class="flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-purple-200 hover:bg-purple-900/30">
                            <i class="fa-solid fa-sliders text-pink-400 w-4"></i> সাইট সেটিংস
                        </a>
                        <?php endif; ?>
                        <a href="logout.php" class="flex items-center gap-2 px-3.5 py-2 text-xs font-bold text-rose-400 hover:bg-rose-500/10">
                            <i class="fa-solid fa-power-off w-4"></i> লগআউট
                        </a>
                    </div>
                </div>

            </div>

        </header>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const sidebar = document.getElementById('adminSidebar');
                const backdrop = document.getElementById('sidebarBackdrop');
                const toggleBtn = document.getElementById('toggleSidebarBtn');
                const closeBtn = document.getElementById('closeSidebarBtn');
                const profileBtn = document.getElementById('adminProfileMenuBtn');
                const dropdownMenu = document.getElementById('adminDropdownMenu');

                if (toggleBtn) {
                    toggleBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        if (sidebar) sidebar.classList.add('open');
                        if (backdrop) backdrop.style.display = 'block';
                    });
                }

                if (closeBtn) {
                    closeBtn.addEventListener('click', function () {
                        if (sidebar) sidebar.classList.remove('open');
                        if (backdrop) backdrop.style.display = 'none';
                    });
                }

                if (backdrop) {
                    backdrop.addEventListener('click', function () {
                        if (sidebar) sidebar.classList.remove('open');
                        backdrop.style.display = 'none';
                    });
                }

                if (profileBtn && dropdownMenu) {
                    profileBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        dropdownMenu.classList.toggle('hidden');
                    });

                    document.addEventListener('click', function (e) {
                        if (!profileBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                            dropdownMenu.classList.add('hidden');
                        }
                    });
                }
            });
        </script>

        <main class="flex-1 p-3.5 md:p-5">
