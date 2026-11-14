<?php
error_reporting(E_ALL);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
session_start();
if (isset($_SESSION['user_id'])) {
    header("location: dashboard.php");
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

$support_wa = get_site_setting($conn, "support_whatsapp_num", "01964706489");
$tg_link    = get_site_setting($conn, "telegram_link", "https://t.me/shebakit");
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Login - ShebaKit</title>

  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="shortcut icon" type="image/jpeg" href="https://shebakit.xyz/assets/logo.jpg">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Hind+Siliguri:wght@500;600;700&display=swap" rel="stylesheet">

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
      --danger: #EF4444;
    }

    * {
      box-sizing: border-box;
      font-family: 'Hind Siliguri', 'Plus Jakarta Sans', sans-serif;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    /* FontAwesome icon protection */
    .fa, .fas, .far, .fal, .fad, .fab, .fa-solid, .fa-regular, .fa-brands {
      font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important;
    }

    body {
      margin: 0;
      padding: 20px 12px;
      min-height: 100vh;
      background-color: var(--bg-main);
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow-x: hidden;
    }

    /* Background Ambient Waves */
    .bg-shape-1 {
      position: fixed;
      top: -100px;
      right: -100px;
      width: 450px;
      height: 450px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(8, 184, 208, 0.15) 0%, rgba(243, 248, 255, 0) 70%);
      z-index: -1;
      animation: floatSlow 12s ease-in-out infinite alternate;
    }

    .bg-shape-2 {
      position: fixed;
      bottom: -120px;
      left: -100px;
      width: 500px;
      height: 500px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(8, 102, 217, 0.12) 0%, rgba(243, 248, 255, 0) 70%);
      z-index: -1;
      animation: floatSlow 14s ease-in-out infinite alternate-reverse;
    }

    @keyframes floatSlow {
      0% { transform: translate(0, 0) scale(1); }
      100% { transform: translate(30px, 30px) scale(1.08); }
    }

    .auth-container {
      width: 100%;
      max-width: 430px;
      margin: auto;
      animation: entranceAnim 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes entranceAnim {
      0% { opacity: 0; transform: translateY(20px) scale(0.97); }
      100% { opacity: 1; transform: translateY(0) scale(1); }
    }

    .login-card {
      background: var(--card-bg);
      border-radius: 24px;
      padding: 36px 30px;
      border: 1.5px solid var(--border-soft);
      box-shadow: 0 16px 40px -10px rgba(18, 59, 112, 0.08), 0 0 1px rgba(18, 59, 112, 0.1);
      position: relative;
    }

    .login-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: linear-gradient(90deg, var(--primary-blue), var(--secondary-cyan));
      border-top-left-radius: 24px;
      border-top-right-radius: 24px;
    }

    .brand-header {
      text-align: center;
      margin-bottom: 26px;
    }

    .brand-logo-wrap {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 12px;
    }

    .brand-logo-wrap img {
      height: 48px;
      width: auto;
      object-fit: contain;
    }

    .brand-header h2 {
      margin: 0;
      font-size: 22px;
      font-weight: 800;
      color: var(--heading-dark);
      letter-spacing: -0.01em;
    }

    .brand-header p {
      margin: 5px 0 0 0;
      color: var(--body-muted);
      font-size: 13px;
      font-weight: 500;
    }

    .form-group-custom {
      margin-bottom: 18px;
    }

    .form-group-custom label {
      display: block;
      font-size: 12.5px;
      font-weight: 700;
      color: var(--heading-dark);
      margin-bottom: 6px;
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-wrapper i.left-icon {
      position: absolute;
      left: 16px;
      color: var(--body-muted);
      font-size: 15px;
      z-index: 2;
      transition: color 0.25s ease;
    }

    .smooth-input {
      width: 100%;
      padding: 13px 45px 13px 46px;
      border-radius: 14px;
      border: 1.5px solid var(--border-soft);
      background: #F8FAFD;
      color: var(--heading-dark);
      font-size: 14px;
      font-weight: 600;
      outline: none;
      transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .smooth-input::placeholder {
      color: #94A3B8;
      font-weight: 400;
    }

    .smooth-input:focus {
      border-color: var(--primary-blue);
      background: #FFFFFF;
      box-shadow: 0 0 0 3.5px rgba(8, 102, 217, 0.12);
    }

    .smooth-input:focus ~ i.left-icon {
      color: var(--primary-blue);
    }

    .toggle-pwd {
      position: absolute;
      right: 16px;
      color: var(--body-muted);
      cursor: pointer;
      font-size: 15px;
      z-index: 2;
      transition: color 0.2s ease;
      padding: 4px;
    }

    .toggle-pwd:hover {
      color: var(--primary-blue);
    }

    .caps-lock-warning {
      display: none;
      font-size: 11.5px;
      color: #D97706;
      font-weight: 700;
      margin-top: 5px;
      align-items: center;
      gap: 5px;
    }

    /* Remember & Forgot Row */
    .utility-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
      font-size: 12.5px;
    }

    .remember-label {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      color: var(--body-muted);
      cursor: pointer;
      font-weight: 600;
      user-select: none;
    }

    .remember-label input[type="checkbox"] {
      width: 15px;
      height: 15px;
      accent-color: var(--primary-blue);
      cursor: pointer;
    }

    .forgot-link {
      color: var(--primary-blue);
      text-decoration: none;
      font-weight: 700;
      transition: color 0.2s;
    }

    .forgot-link:hover {
      color: #0654b3;
      text-decoration: underline;
    }

    /* Primary Submit Button */
    .btn-login-submit {
      width: 100%;
      padding: 14px;
      border-radius: 14px;
      border: none;
      background: linear-gradient(135deg, var(--primary-blue) 0%, #0353b7 100%);
      color: #FFFFFF;
      font-size: 15px;
      font-weight: 800;
      letter-spacing: 0.01em;
      cursor: pointer;
      box-shadow: 0 8px 20px rgba(8, 102, 217, 0.25);
      transition: all 0.25s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-login-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 24px rgba(8, 102, 217, 0.35);
      color: #FFFFFF;
    }

    .btn-login-submit:active {
      transform: translateY(0);
    }

    .register-footer {
      text-align: center;
      margin-top: 22px;
      padding-top: 18px;
      border-top: 1.5px solid var(--border-soft);
      font-size: 13.5px;
      color: var(--body-muted);
    }

    .register-footer a {
      color: var(--primary-blue);
      text-decoration: none;
      font-weight: 800;
      margin-left: 5px;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: color 0.2s;
    }

    .register-footer a:hover {
      color: var(--secondary-cyan);
    }

    /* Support Strip */
    .help-strip {
      margin-top: 18px;
      background: #F8FAFD;
      border: 1px solid var(--border-soft);
      border-radius: 14px;
      padding: 10px 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 12px;
    }

    .help-strip span {
      color: var(--body-muted);
      font-weight: 600;
    }

    .help-strip .help-links {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .help-icon-btn {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #FFFFFF;
      font-size: 13px;
      text-decoration: none;
      transition: transform 0.2s;
    }

    .help-icon-btn:hover {
      transform: scale(1.1);
      color: #FFFFFF;
    }

    .help-icon-btn.wa { background: var(--success); }
    .help-icon-btn.tg { background: #0088CC; }

    /* SweetAlert Theme */
    .custom-swal-popup {
      background: #FFFFFF !important;
      border: 1.5px solid var(--border-soft) !important;
      border-radius: 20px !important;
      box-shadow: 0 20px 40px rgba(18, 59, 112, 0.12) !important;
    }

    .custom-swal-title {
      color: var(--heading-dark) !important;
      font-weight: 800 !important;
    }

    .custom-swal-text {
      color: var(--body-muted) !important;
      font-weight: 600 !important;
    }
  </style>
</head>
<body>

  <div class="bg-shape-1"></div>
  <div class="bg-shape-2"></div>

  <div class="auth-container">
    <div class="login-card">
      
      <!-- Brand & Header -->
      <div class="brand-header">
        <a href="index.php" class="brand-logo-wrap">
          <img src="assets/logo_nav.png" alt="ShebaKit" onerror="this.src='assets/logo.jpg'">
        </a>
        <h2>স্বাগতম ShebaKit-এ</h2>
        <p>আপনার ইউজারনেম ও পাসওয়ার্ড দিয়ে প্রবেশ করুন</p>
      </div>

      <!-- Login Form -->
      <form id="loginForm">
        
        <!-- Username Input -->
        <div class="form-group-custom">
          <label for="email">ইউজারনেম / ইমেইল</label>
          <div class="input-wrapper">
            <i class="fa-solid fa-user left-icon"></i>
            <input type="text" class="smooth-input" id="email" placeholder="ইউজারনেম লিখুন" required autocomplete="username">
          </div>
        </div>

        <!-- Password Input -->
        <div class="form-group-custom">
          <label for="password">পাসওয়ার্ড</label>
          <div class="input-wrapper">
            <i class="fa-solid fa-lock left-icon"></i>
            <input type="password" class="smooth-input" id="password" placeholder="পাসওয়ার্ড লিখুন" required autocomplete="current-password">
            <i class="fa-solid fa-eye toggle-pwd" id="togglePassword" title="পাসওয়ার্ড দেখুন"></i>
          </div>
          <div id="capsWarning" class="caps-lock-warning">
            <i class="fa-solid fa-triangle-exclamation"></i> <span>ক্যাপস লক চালু রয়েছে!</span>
          </div>
          <input type="hidden" id="token" value="<?php echo htmlspecialchars($reg_token, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <!-- Utility: Remember Me & Forgot Password -->
        <div class="utility-row">
          <label class="remember-label">
            <input type="checkbox" id="rememberMe">
            <span>মনে রাখুন</span>
          </label>
          <a href="<?php echo htmlspecialchars($tg_link); ?>" target="_blank" class="forgot-link">পাসওয়ার্ড ভুলে গেছেন?</a>
        </div>

        <!-- Submit Button -->
        <div>
          <button type="submit" id="loginBtn" class="btn-login-submit">
            <i class="fa-solid fa-arrow-right-to-bracket"></i>
            <span>সাইন ইন করুন</span>
          </button>
        </div>
      </form>

      <!-- Quick Helpline Action -->
      <div class="help-strip">
        <span>লগইন করতে সমস্যা হচ্ছে?</span>
        <div class="help-links">
          <a href="https://wa.me/88<?php echo htmlspecialchars($support_wa); ?>" target="_blank" class="help-icon-btn wa" title="WhatsApp Support">
            <i class="fa-brands fa-whatsapp"></i>
          </a>
          <a href="<?php echo htmlspecialchars($tg_link); ?>" target="_blank" class="help-icon-btn tg" title="Telegram Channel">
            <i class="fa-brands fa-telegram"></i>
          </a>
        </div>
      </div>

      <!-- Registration Footer -->
      <div class="register-footer">
        <span>নতুন ইউজার? অ্যাকাউন্ট নেই?</span>
        <a href="register_user.php">
          <span>রেজিস্টার করুন</span>
          <i class="fa-solid fa-arrow-right text-xs"></i>
        </a>
      </div>

    </div>
  </div>

  <script src="assets/vendors/js/vendor.bundle.base.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const emailInput = document.getElementById('email');
    const rememberMeCheckbox = document.getElementById('rememberMe');
    const capsWarning = document.getElementById('capsWarning');

    // Auto-load remembered username
    if (localStorage.getItem('shebakit_saved_username')) {
      emailInput.value = localStorage.getItem('shebakit_saved_username');
      rememberMeCheckbox.checked = true;
    }

    // Toggle Password Visibility
    togglePassword.addEventListener('click', function () {
      const isPwd = passwordInput.getAttribute('type') === 'password';
      passwordInput.setAttribute('type', isPwd ? 'text' : 'password');
      this.classList.toggle('fa-eye', !isPwd);
      this.classList.toggle('fa-eye-slash', isPwd);
    });

    // Detect Caps Lock Key
    passwordInput.addEventListener('keyup', function (e) {
      if (e.getModifierState && e.getModifierState('CapsLock')) {
        capsWarning.style.display = 'flex';
      } else {
        capsWarning.style.display = 'none';
      }
    });

    // AJAX Login Execution
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      e.preventDefault();

      var email = emailInput.value.trim();
      var password = passwordInput.value.trim();
      var token = document.getElementById('token').value;
      var loginBtn = document.getElementById('loginBtn');

      if (!email || !password) {
        Swal.fire({
          icon: 'warning',
          title: 'তথ্য দিন',
          text: 'অনুগ্রহ করে আপনার ইউজারনেম এবং পাসওয়ার্ড প্রদান করুন।',
          confirmButtonColor: '#0866D9',
          customClass: {
            popup: 'custom-swal-popup',
            title: 'custom-swal-title',
            htmlContainer: 'custom-swal-text'
          }
        });
        return;
      }

      // Handle Remember Me Storage
      if (rememberMeCheckbox.checked) {
        localStorage.setItem('shebakit_saved_username', email);
      } else {
        localStorage.removeItem('shebakit_saved_username');
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
                loginBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i> <span>লগইন সফল!</span>';
                Swal.fire({
                  icon: 'success',
                  title: 'লগইন সফল হয়েছে!',
                  text: 'আপনাকে ড্যাশবোর্ডে নেওয়া হচ্ছে...',
                  showConfirmButton: false,
                  timer: 1500,
                  timerProgressBar: true,
                  customClass: {
                    popup: 'custom-swal-popup',
                    title: 'custom-swal-title',
                    htmlContainer: 'custom-swal-text'
                  }
                }).then(() => {
                  window.location.replace('dashboard.php');
                });
              } else {
                Swal.fire({
                  icon: 'error',
                  title: 'লগইন ব্যর্থ!',
                  text: response.message || 'ইউজারনেম বা পাসওয়ার্ড ভুল হয়েছে।',
                  confirmButtonColor: '#EF4444',
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
                text: 'সার্ভার থেকে সঠিক রেসপন্স পাওয়া যায়নি।',
                confirmButtonColor: '#EF4444',
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
              text: 'সার্ভারের সাথে সংযোগ স্থাপন করা যায়নি।',
              confirmButtonColor: '#EF4444',
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