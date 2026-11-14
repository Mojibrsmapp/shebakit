<?php 
session_start();

include_once("includes/configuration.php");

if (isset($_SESSION['user_token'])) {
    $token_user = $_SESSION['user_token'];
    
    $stmt = $conn->prepare("SELECT id, balance, email, name, token, mobile, image FROM users WHERE token = ?");
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
        $user_image = (!empty($row["image"])) ? $row["image"] : 'assets/images/faces/face8.jpg';
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

$current_page = basename($_SERVER['PHP_SELF']);

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
$user_ip = getUserIP();

$whatsapp_group_link = get_site_setting($conn, 'whatsapp_group', '#');
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ShebaKit - ড্যাশবোর্ড</title>
    
    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <link rel="shortcut icon" type="image/jpeg" href="/assets/logo.jpg">
    <link href="https://fonts.maateen.me/solaiman-lipi/font.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/app_core.js"></script>

    <style>
        :root {
            --bg-main: #F3F8FF;
            --navbar-bg: #FFFFFF;
            --sidebar-bg: #FFFFFF;
            --primary-blue: #0866D9;
            --secondary-cyan: #08B8D0;
            --heading-color: #123B70;
            --body-text: #52657D;
            --border-color: #DCE8F5;
            --success-color: #10B981;
            --danger-color: #EF4444;
        }

        html, body {
            background: var(--bg-main) !important;
            color: var(--body-text);
            font-family: 'SolaimanLipi', 'Plus Jakarta Sans', Arial, sans-serif !important;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .container-scroller { background: transparent !important; }
        .content-wrapper { background: var(--bg-main) !important; }

        /* Navbar Styling */
        .navbar, .navbar.default-layout {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            background: var(--navbar-bg) !important;
            border-bottom: 1px solid var(--border-color) !important;
            box-shadow: 0 2px 12px rgba(18, 59, 112, 0.05) !important;
            height: 65px !important;
            min-height: 65px !important;
            padding: 0 16px !important;
            position: fixed !important;
            top: 0 !important;
            right: 0 !important;
            left: 0 !important;
            z-index: 1030 !important;
        }

        .navbar .navbar-brand-wrapper {
            display: flex !important;
            align-items: center !important;
            background: transparent !important;
            border: none !important;
            width: auto !important;
            height: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .navbar .navbar-brand-wrapper .navbar-brand {
            display: flex !important;
            align-items: center !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .navbar .navbar-brand-wrapper .navbar-brand img {
            height: 38px !important;
            width: auto !important;
            max-width: 155px !important;
            object-fit: contain !important;
            border: none !important;
        }

        .navbar .navbar-menu-wrapper {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            background: transparent !important;
            border: none !important;
            height: 100% !important;
            width: auto !important;
            padding: 0 !important;
            margin: 0 !important;
            flex-grow: 1 !important;
        }

        .navbar .navbar-nav {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            margin: 0 !important;
            padding: 0 !important;
            gap: 12px !important;
        }

        /* Topbar Balance Badge */
        .balance-badge {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-cyan) 100%) !important;
            color: #FFFFFF !important;
            font-weight: 800;
            padding: 8px 18px;
            border-radius: 20px;
            box-shadow: 0 4px 14px rgba(8, 102, 217, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13.5px;
            white-space: nowrap;
            border: none !important;
            transition: all 0.25s ease;
            cursor: pointer;
        }

        .balance-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(8, 102, 217, 0.35);
            color: #FFFFFF !important;
        }

        /* Sidebar Styling */
        .sidebar {
            background: var(--sidebar-bg) !important;
            border-right: 1px solid var(--border-color);
            box-shadow: 2px 0 15px rgba(18, 59, 112, 0.04);
        }

        .sidebar .nav {
            padding-top: 15px;
        }

        .sidebar .nav .nav-item .nav-link {
            color: var(--body-text) !important;
            padding: 11px 18px;
            border-radius: 12px;
            margin: 4px 12px;
            display: flex;
            align-items: center;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .sidebar .nav .nav-item .nav-link:hover {
            background: var(--bg-main) !important;
            color: var(--primary-blue) !important;
            transform: translateX(3px);
        }

        .sidebar .nav .nav-item.active > .nav-link {
            background: var(--primary-blue) !important;
            color: #FFFFFF !important;
            box-shadow: 0 4px 14px rgba(8, 102, 217, 0.28);
        }

        .sidebar .nav .nav-item.active > .nav-link i,
        .sidebar .nav .nav-item.active > .nav-link span {
            color: #FFFFFF !important;
        }

        .sidebar .nav .nav-item .menu-title {
            font-size: 14.5px !important;
            margin-left: 12px;
        }

        .sidebar .nav .nav-item .menu-icon {
            font-size: 17px;
        }

        /* User Dropdown */
        .dropdown-menu {
            background: #FFFFFF !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 16px !important;
            box-shadow: 0 12px 30px rgba(18, 59, 112, 0.08) !important;
            padding: 10px !important;
        }

        .dropdown-menu .dropdown-item {
            color: var(--body-text) !important;
            border-radius: 10px;
            padding: 9px 14px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .dropdown-menu .dropdown-item:hover {
            background: var(--bg-main) !important;
            color: var(--primary-blue) !important;
        }

        .dropdown-header {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 12px;
            margin-bottom: 8px;
        }

        @media (max-width: 768px) {
            .navbar, .navbar.default-layout {
                height: 58px !important;
                min-height: 58px !important;
                padding: 0 10px !important;
            }
            .navbar .navbar-brand-wrapper .navbar-brand img {
                height: 32px !important;
                max-width: 125px !important;
            }
            .balance-badge {
                padding: 6px 12px !important;
                font-size: 11.5px !important;
            }
        }
    </style>
</head>
<body class="with-welcome-text">
    <div class="container-scroller">
      
      <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
        <div class="navbar-brand-wrapper d-flex align-items-center justify-content-start">
          <div class="me-2">
            <button class="navbar-toggler navbar-toggler align-self-center p-1 border-0 bg-transparent" type="button" data-bs-toggle="minimize">
              <i class="fa-solid fa-bars-staggered" style="color: #0866D9; font-size: 18px;"></i>
            </button>
          </div>
          <div>
            <a class="navbar-brand brand-logo" href="dashboard.php">
              <img src="/assets/logo_nav.png" alt="ShebaKit" onerror="this.src='assets/logo.jpg'" />
            </a>
          </div>
        </div>

        <div class="navbar-menu-wrapper d-flex align-items-center">
          <ul class="navbar-nav ms-auto flex-row align-items-center"> 
            <li class="nav-item">
              <button class="balance-badge" onclick="location.href='recharge.php'" type="button">
                <i class="fa-solid fa-wallet"></i>
                <span id="balance-display"><?php echo htmlspecialchars($balance); ?></span> ৳
              </button>
            </li>
            
            <li class="nav-item dropdown user-dropdown ms-2">
              <a class="nav-link p-0" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <img class="rounded-circle" src="<?php echo htmlspecialchars($user_image); ?>" alt="Profile" style="width: 38px; height: 38px; object-fit: cover; border: 2px solid #0866D9;"> 
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                <div class="dropdown-header text-center">
                  <img class="rounded-circle mb-2" src="<?php echo htmlspecialchars($user_image); ?>" alt="Profile" style="width: 44px; height: 44px; object-fit: cover; border: 2px solid #08B8D0;">
                  <p class="mb-0 fw-bold" style="color: #123B70; font-size: 14px;"><?php echo htmlspecialchars($name); ?></p>
                  <p class="mb-0 small" style="color: #52657D;"><?php echo htmlspecialchars($email); ?></p>
                </div>
                <a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user-gear me-2" style="color: #0866D9;"></i> প্রোফাইল</a>
                <a class="dropdown-item" href="logout.php"><i class="fa-solid fa-power-off me-2 text-danger"></i> লগআউট</a>
              </div>
            </li>
          </ul>
        </div>
      </nav>

      <div class="container-fluid page-body-wrapper">
        <nav class="sidebar sidebar-offcanvas" id="sidebar">
          <ul class="nav">
            <li class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
              <a class="nav-link" href="dashboard.php"><i class="fa-solid fa-house-chimney menu-icon" style="color: #0866D9;"></i><span class="menu-title">ড্যাশবোর্ড</span></a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'serverCopy.php') ? 'active' : ''; ?>">
              <a class="nav-link" href="serverCopy.php"><i class="fa-solid fa-server menu-icon" style="color: #08B8D0;"></i><span class="menu-title">অটো সার্ভার কপি</span></a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'nid.php') ? 'active' : ''; ?>">
              <a class="nav-link" href="nid.php"><i class="fa-solid fa-id-card menu-icon" style="color: #0284c7;"></i><span class="menu-title">এনআইডি মেইক</span></a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'recharge.php') ? 'active' : ''; ?>">
              <a class="nav-link" href="recharge.php"><i class="fa-solid fa-wallet menu-icon" style="color: #10B981;"></i><span class="menu-title">রিচার্জ করুন</span></a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'work_history.php') ? 'active' : ''; ?>">
              <a class="nav-link" href="work_history.php"><i class="fa-solid fa-clock-rotate-left menu-icon" style="color: #f59e0b;"></i><span class="menu-title">কাজের হিস্টোরি</span></a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
              <a class="nav-link" href="profile.php"><i class="fa-solid fa-user-shield menu-icon" style="color: #6366f1;"></i><span class="menu-title">প্রোফাইল</span></a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket menu-icon text-danger"></i><span class="menu-title text-danger">লগআউট</span></a>
            </li>
          </ul>
        </nav>

        <div class="main-panel">
          <div class="content-wrapper pt-0" style="margin-top: 65px;">

<div class="fixed bottom-5 right-5 z-50">
    <a href="<?php echo htmlspecialchars($whatsapp_group_link); ?>" target="_blank" class="bg-[#10B981] hover:bg-[#059669] text-white font-bold px-4 py-2.5 rounded-full shadow-lg flex items-center gap-2 text-xs transition transform hover:scale-105">
        <i class="fa-brands fa-whatsapp text-base"></i> Join Group
    </a>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let previousBalance = null;

    function fetchBalance() {
        $.ajax({
            url: 'pages/fetch_balance.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response && response.balance !== undefined) {
                    const currentBalance = response.balance;
                    if (currentBalance !== previousBalance) {
                        $('#balance-display').text(currentBalance);
                        previousBalance = currentBalance;
                    }
                }
            }
        });
    }

    $(document).ready(function() {
        fetchBalance();
        setInterval(fetchBalance, 3000);
    });
</script>

<?php if (isset($_GET['msg']) && isset($_GET['text'])): ?>
<script>
    Swal.fire({
        icon: '<?php echo ($_GET['msg'] === 'success') ? 'success' : 'error'; ?>',
        title: '<?php echo ($_GET['msg'] === 'success') ? 'সফল হয়েছে!' : 'ত্রুটি!'; ?>',
        text: '<?php echo htmlspecialchars(urldecode($_GET['text'])); ?>',
        confirmButtonText: 'ঠিক আছে',
        background: '#FFFFFF',
        color: '#123B70',
        confirmButtonColor: '#0866D9'
    }).then(() => {
        if (history.pushState) {
            var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.pushState({ path: newUrl }, '', newUrl);
        }
    });
</script>
<?php endif; ?>