<?php
session_start();
// Database connection
include_once("includes/configuration.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$feedback = ""; // Feedback message
$registrationSuccessful = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if all required fields are present
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
            // Hash password using MD5 (Not Recommended for Production)
            $hashed_password = md5($password);

            // Generate a random token
            $token = bin2hex(random_bytes(8));

            // Insert into database
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

$conn->close();
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - ShebaKit</title>
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" type="image/jpeg" href="https://shebakit.xyz/assets/logo.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; -webkit-font-smoothing: antialiased; }
        
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
          inset: 0; 
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

        .bg-orb { 
          position: fixed; 
          border-radius: 50%; 
          filter: blur(90px); 
          opacity: .5; 
          animation: orbFloat 10s ease-in-out infinite alternate; 
          z-index: -1; 
        }

        .orb-1 { top: 10%; left: 10%; width: 320px; height: 320px; background: linear-gradient(135deg, #10b981, #06b6d4); }
        .orb-2 { bottom: 10%; right: 10%; width: 360px; height: 360px; background: linear-gradient(135deg, #3b82f6, #047857); animation-delay: -5s; }

        @keyframes orbFloat { 
          0% { transform: translate(0, 0) scale(1); } 
          100% { transform: translate(30px, -40px) scale(1.08); } 
        }

        .auth-container { width: 100%; max-width: 500px; padding: 20px; margin: auto; }
        
        .glass-card { 
          background: rgba(9, 20, 35, 0.72); 
          backdrop-filter: blur(25px) saturate(180%); 
          -webkit-backdrop-filter: blur(25px) saturate(180%); 
          border: 1px solid rgba(52, 211, 153, 0.3); 
          border-radius: 24px; 
          padding: 38px; 
          box-shadow: 0 25px 60px rgba(0, 0, 0, 0.85), 
                      0 0 35px rgba(16, 185, 129, 0.2); 
          transition: all .4s cubic-bezier(.16, 1, .3, 1); 
        }

        .glass-card:hover { 
          border-color: rgba(52, 211, 153, 0.5); 
          box-shadow: 0 30px 70px rgba(0, 0, 0, 0.9), 
                      0 0 45px rgba(6, 182, 212, 0.3); 
        }

        .logo-container { width: 76px; height: 76px; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(255, 255, 255, .15), rgba(255, 255, 255, .04)); border: 1px solid rgba(52, 211, 153, 0.3); border-radius: 22px; box-shadow: 0 12px 30px rgba(0, 0, 0, .4), inset 0 1px 1px rgba(255, 255, 255, .4); animation: smoothFloat 4s ease-in-out infinite; }
        .logo-container img { width: 44px; height: auto; filter: drop-shadow(0 6px 12px rgba(0, 0, 0, .4)); }
        @keyframes smoothFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

        .title-text { margin-bottom: 6px; background: linear-gradient(135deg, #fff 0%, #e2e8f0 50%, #34d399 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 26px; font-weight: 800; letter-spacing: -.02em; }
        .subtitle-text { margin-bottom: 0; color: #94a3b8; font-size: 13px; line-height: 1.6; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
        .form-group-custom { margin-bottom: 18px; }
        .form-group-custom.full-width { grid-column: 1 / -1; }
        
        .form-label-custom { display: block; margin-bottom: 8px; color: #cbd5e1; font-size: 13px; font-weight: 600; }
        .input-wrapper { position: relative; display: flex; align-items: center; }
        
        .left-icon { position: absolute; left: 16px; z-index: 2; color: #34d399; font-size: 16px; transition: color .3s ease; }
        
        .smooth-input { width: 100%; padding: 14px 44px 14px 48px; border: 1px solid rgba(52, 211, 153, 0.25); border-radius: 14px; outline: none; background: rgba(4, 11, 20, 0.85); color: #f8fafc; font-size: 14px; font-weight: 500; transition: all .3s cubic-bezier(.16, 1, .3, 1); }
        .smooth-input::placeholder { color: #64748b; font-weight: 400; }
        .smooth-input:focus { border-color: #34d399; background: rgba(4, 11, 20, 0.95); box-shadow: 0 0 20px rgba(52, 211, 153, .3); }
        .input-wrapper:focus-within .left-icon { color: #06b6d4; }
        
        .toggle-pwd { position: absolute; right: 16px; z-index: 2; color: #34d399; cursor: pointer; font-size: 15px; transition: color .3s ease; }
        .toggle-pwd:hover { color: #fff; }
        
        .btn-smooth-submit { width: 100%; padding: 15px; border: 0; border-radius: 14px; background: linear-gradient(135deg, #059669 0%, #0d9488 50%, #0284c7 100%); background-size: 200% 200%; color: #fff; font-size: 15px; font-weight: 700; cursor: pointer; box-shadow: 0 10px 25px rgba(5, 150, 105, .45); transition: all .4s cubic-bezier(.16, 1, .3, 1); display: flex; align-items: center; justify-content: center; gap: 10px; animation: btnGlow 4s ease-in-out infinite alternate; }
        @keyframes btnGlow { 0% { background-position: 0% 50%; box-shadow: 0 10px 25px rgba(5, 150, 105, .4); } 100% { background-position: 100% 50%; box-shadow: 0 10px 30px rgba(6, 182, 212, .6); } }
        .btn-smooth-submit:hover { transform: translateY(-2px); box-shadow: 0 15px 35px rgba(6, 182, 212, .7); }
        .btn-smooth-submit:disabled { opacity: .7; cursor: wait; transform: none; }
        
        .btn-smooth-login { width: 100%; margin-top: 14px; padding: 13px; border: 1px solid rgba(52, 211, 153, 0.25); border-radius: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; background: rgba(255, 255, 255, .04); color: #34d399; font-size: 14px; font-weight: 600; text-decoration: none; transition: all .3s cubic-bezier(.16, 1, .3, 1); }
        .btn-smooth-login:hover { border-color: rgba(52, 211, 153, .5); background: rgba(255, 255, 255, .1); color: #fff; text-decoration: none; transform: translateY(-2px); }
        
        @media (max-width: 575px) { body { align-items: flex-start; } .auth-container { padding: 14px; } .glass-card { padding: 28px 20px; } .form-grid { grid-template-columns: 1fr; } .form-group-custom.full-width { grid-column: auto; } }
    </style>
</head>
<body>
    <div class="smooth-bg-mesh"></div>
    <div class="bg-orb orb-1"></div>
    <div class="bg-orb orb-2"></div>

    <div class="auth-container">
        <div class="glass-card">
            <div class="text-center mb-4">
                <div class="logo-container">
                    <img src="assets/images/logo-mini.svg" alt="logo">
                </div>
                <h2 class="title-text">ShebaKit</h2>
                <p class="subtitle-text">আপনার সঠিক ও সচল WhatsApp নাম্বার দিয়ে নতুন অ্যাকাউন্ট তৈরি করুন</p>
            </div>

            <form method="POST" action="" id="registerForm">
                <div class="form-grid">
                    <div class="form-group-custom">
                        <label for="name" class="form-label-custom"><i class="fa-solid fa-user me-1" style="color:#34d399;"></i> আপনার নাম</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-signature left-icon"></i>
                            <input type="text" name="name" id="name" class="smooth-input" placeholder="সম্পূর্ণ নাম" value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="name">
                        </div>
                    </div>

                    <div class="form-group-custom">
                        <label for="number" class="form-label-custom"><i class="fa-brands fa-whatsapp me-1" style="color:#22c55e;"></i> WhatsApp নাম্বার</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-phone left-icon"></i>
                            <input type="tel" name="number" id="number" class="smooth-input" placeholder="01XXXXXXXXX" value="<?php echo htmlspecialchars($_POST['number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="tel" inputmode="numeric">
                        </div>
                    </div>

                    <div class="form-group-custom full-width">
                        <label for="email" class="form-label-custom"><i class="fa-solid fa-envelope me-1" style="color:#06b6d4;"></i> ইমেইল ঠিকানা</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-at left-icon"></i>
                            <input type="email" name="email" id="email" class="smooth-input" placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="email">
                        </div>
                    </div>

                    <div class="form-group-custom full-width">
                        <label for="password" class="form-label-custom"><i class="fa-solid fa-lock me-1" style="color:#f43f5e;"></i> পাসওয়ার্ড</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-key left-icon"></i>
                            <input type="password" name="password" id="password" class="smooth-input" placeholder="••••••••" required autocomplete="new-password">
                            <i class="fa-solid fa-eye toggle-pwd" id="togglePassword"></i>
                        </div>
                    </div>
                </div>

                <button type="submit" id="registerBtn" class="btn-smooth-submit">
                    <span>রেজিস্টার করুন</span>
                    <i class="fa-solid fa-user-plus"></i>
                </button>
                <a href="index.php" class="btn-smooth-login">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    <span>ইতোমধ্যে অ্যাকাউন্ট আছে? লগইন করুন</span>
                </a>
            </form>
        </div>
    </div>

    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');

        togglePassword.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            this.classList.toggle('fa-eye', !isPassword);
            this.classList.toggle('fa-eye-slash', isPassword);
        });

        registerForm.addEventListener('submit', function () {
            registerBtn.disabled = true;
            registerBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>প্রসেস হচ্ছে...</span>';
        });

        <?php if ($registrationSuccessful): ?>
        Swal.fire({
            icon: 'success',
            title: 'রেজিস্ট্রেশন সফল হয়েছে!',
            text: 'আপনাকে স্বয়ংক্রিয়ভাবে ড্যাশবোর্ডে নেওয়া হচ্ছে...',
            showConfirmButton: false,
            timer: 2200,
            timerProgressBar: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            background: '#091423',
            color: '#f8fafc'
        }).then(function () {
            window.location.replace('user Dashboad.php');
        });
        <?php elseif ($feedback !== ''): ?>
        Swal.fire({
            icon: 'error',
            title: 'রেজিস্ট্রেশন ব্যর্থ!',
            text: <?php echo json_encode($feedback, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>,
            confirmButtonColor: '#ef4444',
            background: '#091423',
            color: '#f8fafc'
        });
        <?php endif; ?>
    </script>
</body>
</html>