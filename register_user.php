<?php
session_start();
include_once("includes/configuration.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$feedback = "";
$registrationSuccessful = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['name'], $_POST['email'], $_POST['password'], $_POST['number'])) {
        $name = trim($_POST['name']);
        $email = strtolower(trim($_POST['email']));
        $password = trim($_POST['password']);
        $number = trim($_POST['number']);

        // Check for duplicate email
        $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $checkEmail->store_result();

        if ($checkEmail->num_rows > 0) {
            $feedback = "এই ইমেইলটি ইতোমধ্যে ব্যবহৃত হয়েছে।";
        } else {
            $hashed_password = md5($password);
            $token = bin2hex(random_bytes(8));

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, token, mobile) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $hashed_password, $token, $number);

            if ($stmt->execute()) {
                session_regenerate_id(true);
                $_SESSION['user_token'] = $token;
                $_SESSION['user_id'] = base64_encode($email);
                $registrationSuccessful = true;
            } else {
                $feedback = "রেজিস্ট্রেশন ব্যর্থ হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।";
            }
            $stmt->close();
        }
        $checkEmail->close();
    } else {
        $feedback = "অনুগ্রহ করে সব ঘর পূরণ করুন।";
    }
}

$support_wa = get_site_setting($conn, "support_whatsapp_num", "01964706489");
$conn->close();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Register - ShebaKit</title>
    
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" type="image/jpeg" href="/assets/logo.jpg">
    <link rel="icon" type="image/jpeg" href="/assets/logo.jpg">
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
        }

        .fa, .fas, .far, .fal, .fad, .fab, .fa-solid, .fa-regular, .fa-brands {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important;
        }

        body { 
            margin: 0; 
            padding: 24px 12px; 
            min-height: 100vh; 
            overflow-x: hidden; 
            background-color: var(--bg-main); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            position: relative; 
        }

        /* Ambient Glow Shapes */
        .bg-shape-1 {
            position: fixed;
            top: -120px;
            right: -100px;
            width: 480px;
            height: 480px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(8, 184, 208, 0.12) 0%, rgba(243, 248, 255, 0) 70%);
            z-index: -1;
            animation: orbFloat 12s ease-in-out infinite alternate;
        }

        .bg-shape-2 {
            position: fixed;
            bottom: -150px;
            left: -120px;
            width: 520px;
            height: 520px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(8, 102, 217, 0.1) 0%, rgba(243, 248, 255, 0) 70%);
            z-index: -1;
            animation: orbFloat 14s ease-in-out infinite alternate-reverse;
        }

        @keyframes orbFloat {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, -30px) scale(1.08); }
        }

        .auth-container { 
            width: 100%; 
            max-width: 520px; 
            margin: auto; 
            animation: cardIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; 
        }

        @keyframes cardIn {
            0% { opacity: 0; transform: translateY(20px) scale(0.97); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .register-card { 
            background: var(--card-bg); 
            border: 1.5px solid var(--border-soft); 
            border-radius: 24px; 
            padding: 34px 28px; 
            box-shadow: 0 16px 40px -10px rgba(18, 59, 112, 0.08); 
            position: relative; 
        }

        .register-card::before {
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
            margin-bottom: 24px;
        }

        .logo-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .logo-wrap img {
            height: 48px;
            width: auto;
            object-fit: contain;
        }

        .title-text { 
            margin: 0; 
            color: var(--heading-dark); 
            font-size: 22px; 
            font-weight: 800; 
        }

        .subtitle-text { 
            margin: 4px 0 0; 
            color: var(--body-muted); 
            font-size: 13px; 
            font-weight: 500; 
        }
        
        .form-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 14px 16px; 
        }

        .form-group-custom { 
            margin-bottom: 0; 
        }

        .form-group-custom.full-width { 
            grid-column: 1 / -1; 
        }
        
        .form-label-custom { 
            display: block; 
            margin-bottom: 6px; 
            color: var(--heading-dark); 
            font-size: 12.5px; 
            font-weight: 700; 
        }

        .input-wrapper { 
            position: relative; 
            display: flex; 
            align-items: center; 
        }
        
        .left-icon { 
            position: absolute; 
            left: 14px; 
            z-index: 2; 
            color: var(--body-muted); 
            font-size: 14px; 
            transition: color .2s ease; 
        }
        
        .smooth-input { 
            width: 100%; 
            padding: 12px 38px 12px 42px; 
            border: 1.5px solid var(--border-soft); 
            border-radius: 14px; 
            outline: none; 
            background: #F8FAFD; 
            color: var(--heading-dark); 
            font-size: 13.5px; 
            font-weight: 600; 
            transition: all .2s cubic-bezier(.16, 1, .3, 1); 
        }

        .smooth-input::placeholder { 
            color: #94A3B8; 
            font-weight: 400; 
        }

        .smooth-input:focus { 
            border-color: var(--primary-blue); 
            background: #FFFFFF; 
            box-shadow: 0 0 0 3px rgba(8, 102, 217, 0.12); 
        }

        .input-wrapper:focus-within .left-icon { 
            color: var(--primary-blue); 
        }
        
        .toggle-pwd { 
            position: absolute; 
            right: 14px; 
            z-index: 2; 
            color: var(--body-muted); 
            cursor: pointer; 
            font-size: 14px; 
            transition: color .2s ease; 
        }

        .toggle-pwd:hover { 
            color: var(--primary-blue); 
        }

        /* Password Strength Bar */
        .strength-meter {
            height: 4px;
            background: #E2E8F0;
            border-radius: 4px;
            margin-top: 6px;
            overflow: hidden;
            display: flex;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }

        .terms-row {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-top: 14px;
            margin-bottom: 18px;
            font-size: 12px;
            color: var(--body-muted);
        }

        .terms-row input[type="checkbox"] {
            margin-top: 3px;
            accent-color: var(--primary-blue);
            cursor: pointer;
        }
        
        .btn-register-submit { 
            width: 100%; 
            padding: 13.5px; 
            border: 0; 
            border-radius: 14px; 
            background: linear-gradient(135deg, var(--primary-blue) 0%, #0353b7 100%); 
            color: #FFFFFF; 
            font-size: 15px; 
            font-weight: 800; 
            cursor: pointer; 
            box-shadow: 0 6px 18px rgba(8, 102, 217, 0.25); 
            transition: all .25s ease; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 8px; 
        }

        .btn-register-submit:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 10px 24px rgba(8, 102, 217, 0.35); 
            color: #FFFFFF; 
        }

        .btn-register-submit:disabled { 
            opacity: .75; 
            cursor: wait; 
            transform: none; 
        }
        
        .btn-login-redirect { 
            width: 100%; 
            margin-top: 12px; 
            padding: 12px; 
            border: 1.5px solid var(--border-soft); 
            border-radius: 14px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 8px; 
            background: #F8FAFD; 
            color: var(--heading-dark); 
            font-size: 13.5px; 
            font-weight: 700; 
            text-decoration: none; 
            transition: all .2s ease; 
        }

        .btn-login-redirect:hover { 
            border-color: var(--primary-blue); 
            background: #FFFFFF; 
            color: var(--primary-blue); 
            transform: translateY(-1px); 
        }

        .help-support-bar {
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px dashed var(--border-soft);
            text-align: center;
            font-size: 12px;
            color: var(--body-muted);
        }

        .help-support-bar a {
            color: var(--primary-blue);
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .help-support-bar a:hover {
            text-decoration: underline;
        }

        /* Custom SweetAlert */
        .custom-swal-popup {
            background: #FFFFFF !important;
            border: 1.5px solid var(--border-soft) !important;
            border-radius: 20px !important;
            box-shadow: 0 20px 40px rgba(18, 59, 112, 0.12) !important;
        }
        
        @media (max-width: 575px) { 
            body { padding: 14px 10px; } 
            .register-card { padding: 26px 18px; } 
            .form-grid { grid-template-columns: 1fr; gap: 12px 0; } 
            .form-group-custom.full-width { grid-column: auto; } 
        }
    </style>
</head>
<body>
    <div class="bg-shape-1"></div>
    <div class="bg-shape-2"></div>

    <div class="auth-container">
        <div class="register-card">
            
            <div class="brand-header">
                <a href="index.php" class="logo-wrap">
                    <img src="assets/logo_nav.png" alt="ShebaKit" onerror="this.src='assets/logo.jpg'">
                </a>
                <h2 class="title-text">নতুন অ্যাকাউন্ট তৈরি করুন</h2>
                <p class="subtitle-text">ShebaKit পোর্টালে যুক্ত হয়ে সকল সার্ভিস ব্যবহার করুন</p>
            </div>

            <form method="POST" action="" id="registerForm">
                <div class="form-grid">
                    
                    <!-- Name Field -->
                    <div class="form-group-custom">
                        <label for="name" class="form-label-custom">আপনার পূর্ণ নাম</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-user left-icon"></i>
                            <input type="text" name="name" id="name" class="smooth-input" placeholder="আপনার নাম লিখুন" value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="name">
                        </div>
                    </div>

                    <!-- WhatsApp / Mobile Field -->
                    <div class="form-group-custom">
                        <label for="number" class="form-label-custom">WhatsApp / মোবাইল নম্বর</label>
                        <div class="input-wrapper">
                            <i class="fa-brands fa-whatsapp left-icon" style="color:#22c55e;"></i>
                            <input type="tel" name="number" id="number" class="smooth-input" placeholder="01XXXXXXXXX" value="<?php echo htmlspecialchars($_POST['number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="tel" inputmode="numeric" maxlength="11">
                        </div>
                    </div>

                    <!-- Email Field -->
                    <div class="form-group-custom full-width">
                        <label for="email" class="form-label-custom">ইমেইল ঠিকানা</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-envelope left-icon"></i>
                            <input type="email" name="email" id="email" class="smooth-input" placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="email">
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group-custom">
                        <label for="password" class="form-label-custom">পাসওয়ার্ড</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock left-icon"></i>
                            <input type="password" name="password" id="password" class="smooth-input" placeholder="কমপক্ষে ৬ অক্ষর" required autocomplete="new-password">
                            <i class="fa-solid fa-eye toggle-pwd" id="togglePassword"></i>
                        </div>
                        <div class="strength-meter">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="form-group-custom">
                        <label for="confirmPassword" class="form-label-custom">কনফার্ম পাসওয়ার্ড</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-shield-halved left-icon"></i>
                            <input type="password" id="confirmPassword" class="smooth-input" placeholder="পুনরায় পাসওয়ার্ড দিন" required autocomplete="new-password">
                        </div>
                    </div>

                </div>

                <!-- Terms and Privacy Check -->
                <div class="terms-row">
                    <input type="checkbox" id="agreeTerms" required checked>
                    <label for="agreeTerms">আমি ShebaKit-এর ব্যবহার বিধি ও গোপনীয়তা নীতির সাথে একমত পোষণ করছি।</label>
                </div>

                <button type="submit" id="registerBtn" class="btn-register-submit">
                    <span>রেজিস্ট্রেশন সম্পন্ন করুন</span>
                    <i class="fa-solid fa-arrow-right-long text-xs"></i>
                </button>
                
                <a href="index.php" class="btn-login-redirect">
                    <i class="fa-solid fa-arrow-right-to-bracket text-[#0866D9]"></i>
                    <span>ইতোমধ্যে অ্যাকাউন্ট আছে? সাইন ইন করুন</span>
                </a>

                <div class="help-support-bar">
                    <span>রেজিস্ট্রেশনে কোনো সমস্যা হচ্ছে?</span>
                    <a href="https://wa.me/88<?php echo htmlspecialchars($support_wa); ?>" target="_blank">
                        <i class="fa-brands fa-whatsapp text-emerald-600"></i> হেল্পলাইনে যোগাযোগ করুন
                    </a>
                </div>
            </form>

        </div>
    </div>

    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');
        const strengthBar = document.getElementById('strengthBar');
        const numberInput = document.getElementById('number');

        // Toggle Password Visibility
        togglePassword.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            confirmPasswordInput.type = isPassword ? 'text' : 'password';
            this.classList.toggle('fa-eye', !isPassword);
            this.classList.toggle('fa-eye-slash', isPassword);
        });

        // Realtime Password Strength Bar
        passwordInput.addEventListener('input', function() {
            const val = this.value;
            let score = 0;
            if (val.length >= 6) score += 33;
            if (/[0-9]/.test(val)) score += 33;
            if (/[A-Z]/.test(val) || /[^A-Za-z0-9]/.test(val)) score += 34;

            strengthBar.style.width = score + '%';
            if (score <= 33) {
                strengthBar.style.backgroundColor = '#EF4444';
            } else if (score <= 66) {
                strengthBar.style.backgroundColor = '#F59E0B';
            } else {
                strengthBar.style.backgroundColor = '#10B981';
            }
        });

        // Numeric phone input validation
        numberInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Form Submission & Confirmation Check
        registerForm.addEventListener('submit', function (e) {
            const pwd = passwordInput.value.trim();
            const cpwd = confirmPasswordInput.value.trim();
            const phone = numberInput.value.trim();

            if (phone.length !== 11 || !phone.startsWith('01')) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'সঠিক নম্বর দিন',
                    text: 'অনুগ্রহ করে ১১ ডিজিটের সঠিক মোবাইল নম্বর লিখুন (যেমন: 01XXXXXXXXX)।',
                    confirmButtonColor: '#0866D9',
                    customClass: { popup: 'custom-swal-popup' }
                });
                return;
            }

            if (pwd !== cpwd) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'পাসওয়ার্ড মিলেনি',
                    text: 'উভয় ঘরে একই পাসওয়ার্ড প্রদান করুন।',
                    confirmButtonColor: '#0866D9',
                    customClass: { popup: 'custom-swal-popup' }
                });
                return;
            }

            registerBtn.disabled = true;
            registerBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>প্রসেস হচ্ছে...</span>';
        });

        <?php if ($registrationSuccessful): ?>
        Swal.fire({
            icon: 'success',
            title: 'রেজিস্ট্রেশন সফল হয়েছে!',
            text: 'আপনাকে ড্যাশবোর্ডে নেওয়া হচ্ছে...',
            showConfirmButton: false,
            timer: 1600,
            timerProgressBar: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            customClass: { popup: 'custom-swal-popup' }
        }).then(function () {
            window.location.replace('dashboard.php');
        });
        <?php elseif ($feedback !== ''): ?>
        Swal.fire({
            icon: 'error',
            title: 'রেজিস্ট্রেশন ব্যর্থ!',
            text: <?php echo json_encode($feedback, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>,
            confirmButtonColor: '#EF4444',
            customClass: { popup: 'custom-swal-popup' }
        });
        <?php endif; ?>
    </script>
</body>
</html>