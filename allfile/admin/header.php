<?php 
session_start();
include_once("includes/configuration.php");

if (isset($_SESSION['admin_token'])) {
    $token_user = $_SESSION['admin_token'];
    
    $stmt = $conn->prepare("SELECT * FROM admin_user WHERE token = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $token_user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $admin_id = $row["id"];
            $admin_balance = $row["balance"] ?? 0.00;
            $admin_email = $row["email"] ?? '';
            $admin_name = $row["name"] ?? 'Admin';
            $admin_token = $row["token"] ?? '';
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
$dep_c = $conn->query("SELECT COUNT(*) as c FROM deposit_requests WHERE status = 'pending'");
if ($dep_c) {
    $pending_deposits = intval($dep_c->fetch_assoc()['c'] ?? 0);
}

$pending_orders = 0;
$ord_c = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status = 'pending'");
if ($ord_c) {
    $pending_orders = intval($ord_c->fetch_assoc()['c'] ?? 0);
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ICONIC Super Admin Panel</title>
    
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
        }

        .admin-topbar {
            background: #FFFFFF;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .admin-sidebar {
            width: 260px;
            background: #0F172A;
            color: #E2E8F0;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 50;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            overflow-y: auto;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        @media (max-width: 1024px) {
            .admin-sidebar {
                left: -270px;
            }
            .admin-sidebar.open {
                left: 0 !important;
            }
            .main-content-layout {
                margin-left: 0 !important;
            }
        }

        @media (min-width: 1025px) {
            .main-content-layout {
                margin-left: 260px;
            }
        }

        .nav-category-header {
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748B;
            padding: 16px 20px 6px;
        }

        .sidebar-menu-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 18px;
            margin: 2px 12px;
            border-radius: 12px;
            color: #94A3B8;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .sidebar-menu-item:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #FFFFFF;
        }

        .sidebar-menu-item.active {
            background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);
            color: #FFFFFF;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.35);
        }

        .sidebar-menu-item i {
            width: 22px;
            font-size: 15px;
            text-align: center;
        }

        .counter-pill-sm {
            font-size: 10px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 20px;
            line-height: 1;
        }

        .admin-sidebar::-webkit-scrollbar {
            width: 4px;
        }
        .admin-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
        }

        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 45;
            display: none;
        }
    </style>
</head>
<body>

    <!-- Mobile Sidebar Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Sidebar Menu -->
    <aside class="admin-sidebar" id="adminSidebar">
        
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <a href="dashboard.php" class="flex items-center gap-2.5 text-white no-underline">
                <img src="https://shebakitonline.xyz/assets/logo_nav.png" alt="Logo" class="h-8 w-auto">
                <div>
                    <span class="text-xs font-black tracking-wider block text-indigo-400">ADMIN PANEL</span>
                    <span class="text-[10px] text-slate-500 font-bold block">Fast Service Engine</span>
                </div>
            </a>
            <button type="button" id="closeSidebarBtn" class="lg:hidden text-slate-400 hover:text-white text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="py-3">
            
            <div class="nav-category-header">ওভারভিউ</div>
            <a href="dashboard.php" class="sidebar-menu-item <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-gauge-high"></i>
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
                    <span>সার্ভিস অন/অফ</span>
                </div>
            </a>

            <a href="all_pricing.php" class="sidebar-menu-item <?php echo ($current_page === 'all_pricing.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-coins text-yellow-400"></i>
                    <span>মাস্টার প্রাইসিং</span>
                </div>
            </a>

            <a href="add_service.php" class="sidebar-menu-item <?php echo ($current_page === 'add_service.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-plus text-emerald-400"></i>
                    <span>নতুন সার্ভিস তৈরি</span>
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
                    <span>ব্যালেন্স যোগ / বাদ</span>
                </div>
            </a>

            <a href="createLink.php" class="sidebar-menu-item <?php echo ($current_page === 'createLink.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-user-plus text-sky-400"></i>
                    <span>অ্যাকাউন্ট তৈরির লিংক</span>
                </div>
            </a>

            <div class="nav-category-header">সিস্টেম ও কনফিগ</div>
            <a href="update_marquee.php" class="sidebar-menu-item <?php echo ($current_page === 'update_marquee.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-bullhorn text-pink-400"></i>
                    <span>নোটিশ আপডেট</span>
                </div>
            </a>

            <a href="profile.php" class="sidebar-menu-item <?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-user-shield text-slate-400"></i>
                    <span>এডমিন প্রোফাইল</span>
                </div>
            </a>

            <a href="logout.php" class="sidebar-menu-item text-rose-400 hover:bg-rose-500/10">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-arrow-right-from-bracket text-rose-400"></i>
                    <span>লগআউট</span>
                </div>
            </a>

        </div>

    </aside>

    <!-- Main Content Layout Wrapper -->
    <div class="main-content-layout min-h-screen flex flex-col">
        
        <!-- Top Navbar -->
        <header class="admin-topbar px-4 py-3 flex items-center justify-between">
            
            <div class="flex items-center gap-3">
                <button type="button" id="toggleSidebarBtn" class="lg:hidden text-slate-700 hover:text-indigo-600 text-xl px-2.5 py-1.5 rounded-xl border border-slate-200 bg-slate-50 transition active:scale-95">
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
                <div class="hidden sm:block">
                    <h4 class="font-extrabold text-sm text-slate-800 leading-tight">ICONIC এডমিন কন্ট্রোল</h4>
                    <p class="text-[11px] text-slate-400 font-medium"><?php echo date('d M Y, l'); ?></p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                
                <a href="manage_deposits.php" class="relative bg-slate-100 hover:bg-slate-200 text-slate-700 w-9 h-9 rounded-xl flex items-center justify-center text-sm transition" title="পেন্ডিং রিচার্জ">
                    <i class="fa-solid fa-bell"></i>
                    <?php if ($pending_deposits > 0): ?>
                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-rose-500 text-white rounded-full text-[9px] font-black flex items-center justify-center">
                            <?php echo $pending_deposits; ?>
                        </span>
                    <?php endif; ?>
                </a>

                <div class="bg-indigo-50 border border-indigo-100 px-3.5 py-1.5 rounded-xl flex items-center gap-2">
                    <i class="fa-solid fa-coins text-indigo-600 text-xs"></i>
                    <span class="text-xs font-black text-indigo-900" id="adminBalanceDisplay">৳ <?php echo number_format($admin_balance, 2); ?></span>
                </div>

                <div class="relative">
                    <button type="button" id="adminProfileMenuBtn" class="flex items-center gap-2 focus:outline-none p-0.5 rounded-xl hover:ring-2 hover:ring-indigo-300 transition">
                        <img src="https://shebakitonline.xyz/assets/logo.jpg" alt="Admin Avatar" class="w-9 h-9 rounded-xl border border-slate-200 object-cover pointer-events-none">
                    </button>
                    
                    <div id="adminDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50">
                        <div class="px-4 py-2 border-b border-slate-100">
                            <p class="text-xs font-bold text-slate-800 truncate"><?php echo htmlspecialchars($admin_name); ?></p>
                            <p class="text-[11px] text-slate-400 truncate"><?php echo htmlspecialchars($admin_email); ?></p>
                        </div>
                        <a href="profile.php" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-indigo-600">
                            <i class="fa-solid fa-user-gear"></i> প্রোফাইল
                        </a>
                        <a href="all_pricing.php" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-indigo-600">
                            <i class="fa-solid fa-coins"></i> প্রাইসিং
                        </a>
                        <a href="logout.php" class="flex items-center gap-2.5 px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> লগআউট
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

        <!-- Page Dynamic Body Container -->
        <main class="flex-1 p-3 md:p-6">