<?php 
session_start();
include_once("includes/configuration.php");

if (isset($_SESSION['user_token'])) {
    $token_user = $_SESSION['user_token'];
    
    // image কলামসহ ডাটাবেজ থেকে ইউজারের তথ্য ফেচ করা হলো
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
        // ডাটাবেজে ছবি থাকলে সেটি লোড হবে, না থাকলে ডিফল্ট ছবি
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
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ShebaKit - Advanced Dashboard</title>
    
    <!-- Base CSS -->
    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon & Custom Fonts -->
    <link rel="shortcut icon" type="image/jpeg" href="https://shebakit.xyz/assets/logo.jpg">
    <link href="https://fonts.maateen.me/solaiman-lipi/font.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg-dark-main: #F0F4F8;
            --sidebar-bg: #ffffff;
            --navbar-bg: #ffffff;
            --text-color: #1E293B;
            --primary-blue: #0284c7;
        }

        html, body {
            background: #F0F4F8 !important;
            color: var(--text-color);
            font-family: 'SolaimanLipi', Arial, sans-serif !important;
            margin: 0;
            padding: 0;
        }

        .container-scroller { background: transparent !important; }
        .content-wrapper { background: #F0F4F8 !important; }

        /* Navbar Layout - Single straight row on all screens */
        .navbar, .navbar.default-layout {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            background: #FFFFFF !important;
            border: none !important;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.08) !important;
            height: 65px !important;
            min-height: 65px !important;
            padding: 0 12px !important;
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
            max-width: 150px !important;
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
            gap: 8px !important;
        }

        .navbar .navbar-nav .nav-item {
            margin: 0 !important;
            padding: 0 !important;
        }

        .balance-badge {
            background: linear-gradient(135deg, #FCD34D 0%, #F59E0B 100%) !important;
            color: #78350F !important;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(245, 158, 11, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            white-space: nowrap;
            border: none !important;
        }

        .sidebar {
            background: var(--sidebar-bg) !important;
            box-shadow: 4px 0 20px rgba(14, 165, 233, 0.05);
        }

        .sidebar .nav .nav-item .nav-link {
            color: #334155 !important;
            padding: 12px 18px;
            border-radius: 12px;
            margin: 4px 10px;
            display: flex;
            align-items: center;
        }

        .sidebar .nav .nav-item.active > .nav-link {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%) !important;
            color: #ffffff !important;
            font-weight: 700;
        }

        .sidebar .nav .nav-item.active > .nav-link i,
        .sidebar .nav .nav-item.active > .nav-link span {
            color: #ffffff !important;
        }

        .sidebar .nav .nav-item .menu-title {
            font-size: 15px !important;
            margin-left: 10px;
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
                padding: 4px 8px !important;
                font-size: 11px !important;
            }
        }
    </style>
</head>
<body class="with-welcome-text">
    <div class="container-scroller">
      
      <!-- Top Navbar Header -->
      <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
        <div class="navbar-brand-wrapper d-flex align-items-center justify-content-start">
          <div class="me-1">
            <button class="navbar-toggler navbar-toggler align-self-center p-1 border-0 bg-transparent" type="button" data-bs-toggle="minimize">
              <span class="icon-menu text-primary"></span>
            </button>
          </div>
          <div>
            <a class="navbar-brand brand-logo" href="user Dashboad.php">
              <img src="https://shebakitonline.xyz/assets/logo_nav.png" alt="logo" />
            </a>
          </div>
        </div>

        <div class="navbar-menu-wrapper d-flex align-items-center">
          <ul class="navbar-nav ms-auto flex-row align-items-center"> 
            <li class="nav-item">
              <button class="balance-badge" onclick="location.href='recharge.php'" type="button">
                <i class="mdi mdi-wallet-outline"></i>
                <span id="balance-display"><?php echo htmlspecialchars($balance); ?></span> tk
              </button>
            </li>
            
            <li class="nav-item dropdown user-dropdown ms-1">
              <a class="nav-link p-0" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <img class="rounded-circle border border-sky-400" src="<?php echo htmlspecialchars($user_image); ?>" alt="Profile" style="width: 32px; height: 32px; object-fit: cover;"> 
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                <div class="dropdown-header text-center">
                  <img class="rounded-circle border border-info" src="<?php echo htmlspecialchars($user_image); ?>" alt="Profile" style="width: 40px; height: 40px; object-fit: cover;">
                  <p class="mb-1 mt-2 fw-semibold text-slate-800"><?php echo htmlspecialchars($name); ?></p>
                  <p class="fw-light text-muted mb-0 small"><?php echo htmlspecialchars($email); ?></p>
                </div>
                <a class="dropdown-item" href="profile.php"><i class="mdi mdi-account-circle text-primary me-2"></i> প্রোফাইল</a>
                <a class="dropdown-item" href="logout.php"><i class="mdi mdi-power text-danger me-2"></i> লগআউট</a>
              </div>
            </li>
          </ul>
        </div>
      </nav>

      <!-- Sidebar Layout -->
      <div class="container-fluid page-body-wrapper">
        <nav class="sidebar sidebar-offcanvas" id="sidebar">
          <ul class="nav">
            <li class="nav-item <?php echo ($current_page == 'user Dashboad.php') ? 'active' : ''; ?>">
              <a class="nav-link" href="user Dashboad.php"><i class="menu-icon mdi mdi-view-dashboard" style="color: #0284c7;"></i><span class="menu-title">ড্যাশবোর্ড</span></a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'serverCopy.php') ? 'active' : ''; ?>">
              <a class="nav-link" href="serverCopy.php"><i class="menu-icon mdi mdi-server-network" style="color: #a855f7;"></i><span class="menu-title">অটো সার্ভার কপি</span></a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'nid.php') ? 'active' : ''; ?>">
              <a class="nav-link" href="nid.php"><i class="menu-icon mdi mdi-credit-card" style="color: #2dd4bf;"></i><span class="menu-title">এনআইডি মেইক করুন</span></a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
              <a class="nav-link" href="profile.php"><i class="menu-icon mdi mdi-account-circle" style="color: #38bdf8;"></i><span class="menu-title">প্রোফাইল</span></a>
            </li>
            <li class="nav-item <?php echo ($current_page == 'recharge.php') ? 'active' : ''; ?>">
              <a class="nav-link" href="recharge.php"><i class="menu-icon mdi mdi-wallet" style="color: #4ade80;"></i><span class="menu-title">রিচার্জ করুন</span></a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="logout.php"><i class="menu-icon mdi mdi-logout text-danger"></i><span class="menu-title text-danger">লগআউট</span></a>
            </li>
          </ul>
        </nav>

        <div class="main-panel">
          <div class="content-wrapper pt-0" style="margin-top: 65px;">

<!-- Balance & Alert Scripts -->
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
            },
            error: function() {
                console.error('Balance fetch issue.');
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
        confirmButtonText: 'ঠিক আছে'
    }).then(() => {
        if (history.pushState) {
            var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.pushState({ path: newUrl }, '', newUrl);
        }
    });
</script>
<?php endif; ?>