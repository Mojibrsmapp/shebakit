<?php
// Report all PHP errors
error_reporting(E_ALL);

// Force errors to be displayed on the screen
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
session_start();
if (isset($_SESSION['user_id'])) {
    header("location: user Dashboad.php");
    exit();
}

include_once("includes/configuration.php");

$query = "SELECT whatsapp_group FROM admin_user WHERE id = 1";
$result = mysqli_query($conn, $query);
$reg_token = "";

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $reg_token = $row['whatsapp_group'];
}
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - ShebaKit</title>

  <!-- CSS Vendors -->
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="shortcut icon" type="image/jpeg" href="https://shebakit.xyz/assets/logo.jpg">

  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Advanced Smooth Font: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    * {
      box-sizing: border-box;
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      text-rendering: optimizeLegibility;
    }

    body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      overflow-x: hidden;
      background-color: #040d12;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

    /* Animated Dynamic Mesh Background with Emerald & Cyber Glow */
    .smooth-bg-mesh {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: radial-gradient(circle at 10% 20%, rgba(16, 185, 129, 0.2) 0%, transparent 45%),
                  radial-gradient(circle at 90% 80%, rgba(6, 182, 212, 0.22) 0%, transparent 45%),
                  radial-gradient(circle at 50% 50%, rgba(59, 130, 246, 0.15) 0%, transparent 55%),
                  linear-gradient(135deg, #040d12 0%, #0b192c 50%, #040d12 100%);
      z-index: -2;
      animation: meshPulse 12s ease-in-out infinite alternate;
    }

    @keyframes meshPulse {
      0% { transform: scale(1); filter: hue-rotate(0deg); }
      100% { transform: scale(1.05); filter: hue-rotate(25deg); }
    }

    /* Floating Animated Glowing Orbs with Smooth Animation */
    .bg-orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(90px);
      opacity: 0.5;
      animation: orbFloat 10s ease-in-out infinite alternate;
      z-index: -1;
    }

    .orb-1 {
      top: 10%;
      left: 10%;
      width: 320px;
      height: 320px;
      background: linear-gradient(135deg, #10b981, #06b6d4);
    }

    .orb-2 {
      bottom: 10%;
      right: 10%;
      width: 360px;
      height: 360px;
      background: linear-gradient(135deg, #3b82f6, #047857);
      animation-delay: -5s;
    }

    @keyframes orbFloat {
      0% { transform: translate(0, 0) scale(1); }
      100% { transform: translate(30px, -40px) scale(1.08); }
    }

    .auth-container {
      width: 100%;
      max-width: 410px;
      padding: 20px;
      margin: auto;
      animation: cardEntrance 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes cardEntrance {
      0% { opacity: 0; transform: translateY(30px) scale(0.95); }
      100% { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Premium Glassmorphism Card with Emerald Glow Border */
    .glass-card {
      background: rgba(9, 20, 35, 0.72);
      backdrop-filter: blur(25px) saturate(180%);
      -webkit-backdrop-filter: blur(25px) saturate(180%);
      border-radius: 24px;
      padding: 40px 32px;
      border: 1px solid rgba(52, 211, 153, 0.3);
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.85),
                  0 0 35px rgba(16, 185, 129, 0.2);
      transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .glass-card:hover {
      border-color: rgba(52, 211, 153, 0.5);
      box-shadow: 0 30px 70px rgba(0, 0, 0, 0.9),
                  0 0 45px rgba(6, 182, 212, 0.3);
      transform: translateY(-3px);
    }

    /* Form Fields Styling */
    .form-group-custom {
      margin-bottom: 22px;
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-wrapper i.left-icon {
      position: absolute;
      left: 18px;
      color: #34d399;
      font-size: 16px;
      z-index: 2;
      transition: color 0.3s ease;
    }

    .smooth-input {
      width: 100%;
      padding: 16px 16px 16px 52px;
      border-radius: 14px;
      border: 1px solid rgba(52, 211, 153, 0.25);
      background: rgba(4, 11, 20, 0.85);
      color: #f8fafc;
      font-size: 15px;
      font-weight: 500;
      outline: none;
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .smooth-input::placeholder {
      color: #64748b;
      font-weight: 400;
    }

    .smooth-input:focus {
      border-color: #34d399;
      background: rgba(4, 11, 20, 0.95);
      box-shadow: 0 0 20px rgba(52, 211, 153, 0.3);
    }

    .smooth-input:focus ~ i.left-icon {
      color: #06b6d4;
    }

    .toggle-pwd {
      position: absolute;
      right: 18px;
      color: #34d399;
      cursor: pointer;
      font-size: 16px;
      z-index: 2;
      transition: color 0.3s ease;
    }

    .toggle-pwd:hover {
      color: #ffffff;
    }

    /* High-End Emerald/Cyan Gradient Action Button with Glow Animation */
    .btn-smooth-submit {
      width: 100%;
      padding: 16px;
      border-radius: 14px;
      border: none;
      background: linear-gradient(135deg, #059669 0%, #0d9488 50%, #0284c7 100%);
      background-size: 200% 200%;
      color: #ffffff;
      font-size: 17px;
      font-weight: 700;
      letter-spacing: 0.02em;
      cursor: pointer;
      box-shadow: 0 10px 25px rgba(5, 150, 105, 0.45);
      transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-top: 10px;
      animation: btnGlow 4s ease-in-out infinite alternate;
    }

    @keyframes btnGlow {
      0% { background-position: 0% 50%; box-shadow: 0 10px 25px rgba(5, 150, 105, 0.4); }
      100% { background-position: 100% 50%; box-shadow: 0 10px 30px rgba(6, 182, 212, 0.6); }
    }

    .btn-smooth-submit:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 35px rgba(6, 182, 212, 0.7);
    }

    .btn-smooth-submit:active {
      transform: translateY(-1px);
    }

    /* Custom Futuristic SweetAlert Styling */
    .custom-swal-popup {
      background: rgba(9, 20, 35, 0.95) !important;
      backdrop-filter: blur(20px);
      border: 1px solid rgba(52, 211, 153, 0.4) !important;
      border-radius: 20px !important;
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.9), 0 0 30px rgba(16, 185, 129, 0.25) !important;
    }

    .custom-swal-title {
      color: #f8fafc !important;
      font-family: 'Plus Jakarta Sans', sans-serif !important;
      font-weight: 700 !important;
    }

    .custom-swal-text {
      color: #94a3b8 !important;
      font-family: 'Plus Jakarta Sans', sans-serif !important;
    }
  </style>
</head>
<body>

  <!-- Smooth Mesh & Floating Orbs Background -->
  <div class="smooth-bg-mesh"></div>
  <div class="bg-orb orb-1"></div>
  <div class="bg-orb orb-2"></div>

  <!-- Main Container -->
  <div class="auth-container">
    <div class="glass-card">
      
      <!-- Login Form -->
      <form id="loginForm">
        <!-- Email / Username Input -->
        <div class="form-group-custom">
          <div class="input-wrapper">
            <i class="fa-solid fa-user left-icon"></i>
            <input type="text" class="smooth-input" id="email" placeholder="ইউজারনেম" required autocomplete="username">
          </div>
        </div>

        <!-- Password Input -->
        <div class="form-group-custom">
          <div class="input-wrapper">
            <i class="fa-solid fa-lock left-icon"></i>
            <input type="password" class="smooth-input" id="password" placeholder="পাসওয়ার্ড" required autocomplete="current-password">
            <i class="fa-solid fa-eye toggle-pwd" id="togglePassword"></i>
          </div>
          <input type="hidden" id="token" value="<?php echo htmlspecialchars($reg_token, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <!-- Submit Button Only -->
        <div>
          <button type="submit" id="loginBtn" class="btn-smooth-submit">
            <i class="fa-solid fa-arrow-right-to-bracket"></i>
            <span>সাইন ইন করুন</span>
          </button>
        </div>
      </form>

    </div>
  </div>

  <!-- Vendors & Scripts -->
  <script src="assets/vendors/js/vendor.bundle.base.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    // Password Visibility Toggle
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', function () {
      const isPwd = passwordInput.getAttribute('type') === 'password';
      passwordInput.setAttribute('type', isPwd ? 'text' : 'password');
      this.classList.toggle('fa-eye', !isPwd);
      this.classList.toggle('fa-eye-slash', isPwd);
    });

    // AJAX Login Logic
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      e.preventDefault();

      var email = document.getElementById('email').value.trim();
      var password = document.getElementById('password').value.trim();
      var token = document.getElementById('token').value;
      var loginBtn = document.getElementById('loginBtn');

      if (!email || !password) {
        Swal.fire({
          icon: 'warning',
          title: 'তথ্য দিন',
          text: 'অনুগ্রহ করে আপনার ইউজারনেম এবং পাসওয়ার্ড প্রদান করুন।',
          confirmButtonColor: '#059669',
          customClass: {
            popup: 'custom-swal-popup',
            title: 'custom-swal-title',
            htmlContainer: 'custom-swal-text'
          }
        });
        return;
      }

      loginBtn.disabled = true;
      loginBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>প্রসেস হচ্ছে...</span>';

      var xhr = new XMLHttpRequest();
      xhr.open('POST', 'pages/login.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

      xhr.onreadystatechange = function () {
        if (xhr.readyState == 4) {
          loginBtn.disabled = false;
          loginBtn.innerHTML = '<i class="fa-solid fa-arrow-right-to-bracket"></i> <span>সাইন ইন করুন</span>';

          if (xhr.status == 200) {
            try {
              var response = JSON.parse(xhr.responseText);
              
              if (response.status === 'success') {
                loginBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i> <span>লগইন সফল</span>';
                Swal.fire({
                  icon: 'success',
                  title: 'লগইন সফল হয়েছে!',
                  text: 'আপনাকে ড্যাশবোর্ডে নেওয়া হচ্ছে...',
                  showConfirmButton: false,
                  timer: 2000,
                  timerProgressBar: true,
                  customClass: {
                    popup: 'custom-swal-popup',
                    title: 'custom-swal-title',
                    htmlContainer: 'custom-swal-text'
                  }
                }).then(() => {
                  window.location.replace('user Dashboad.php');
                });
              } else {
                Swal.fire({
                  icon: 'error',
                  title: 'লগইন ব্যর্থ!',
                  text: response.message,
                  confirmButtonColor: '#ef4444',
                  customClass: {
                    popup: 'custom-swal-popup',
                    title: 'custom-swal-title',
                    htmlContainer: 'custom-swal-text'
                  }
                });
              }
            } catch (err) {
              Swal.fire({
                icon: 'error',
                title: 'সার্ভার রেসপন্স ত্রুটি',
                text: 'সার্ভার থেকে রেসপন্স প্রসেস করা যায়নি।',
                confirmButtonColor: '#ef4444',
                customClass: {
                  popup: 'custom-swal-popup',
                  title: 'custom-swal-title',
                  htmlContainer: 'custom-swal-text'
                }
              });
            }
          } else {
            Swal.fire({
              icon: 'error',
              title: 'সার্ভার ত্রুটি',
              text: 'সার্ভারের সাথে সংযোগ স্থাপন করা সম্ভব হয়নি।',
              confirmButtonColor: '#ef4444',
              customClass: {
                popup: 'custom-swal-popup',
                title: 'custom-swal-title',
                htmlContainer: 'custom-swal-text'
              }
            });
          }
        }
      };

      xhr.send('email=' + encodeURIComponent(email) + '&password=' + encodeURIComponent(password) + '&token=' + encodeURIComponent(token));
    });
  </script>
</body>
</html>