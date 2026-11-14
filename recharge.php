<?php 
include_once("header.php");

// Database theke direct value load (kono hardcoded default value chara)
$bkash_number = get_site_setting($conn, "bkash_number", "");
$nagad_number = get_site_setting($conn, "nagad_number", "");
$support_wa   = get_site_setting($conn, "support_whatsapp_num", "");
$tg_link      = get_site_setting($conn, "telegram_link", "");
$wa_group     = get_site_setting($conn, "whatsapp_group", "");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_deposit'])) {
    $amount = floatval($_POST['amount'] ?? 0);
    $method = trim($_POST['method'] ?? '');
    $trx_id = trim($_POST['trx_id'] ?? '');
    
    if ($amount < 50) {
        $_SESSION['Error'] = "সর্বনিম্ন ৫০ টাকা রিচার্জ করতে হবে।";
    } elseif (empty($method) || !in_array($method, ['bkash', 'nagad'])) {
        $_SESSION['Error'] = "সঠিক পেমেন্ট গেটওয়ে নির্বাচন করুন।";
    } elseif (empty($trx_id)) {
        $_SESSION['Error'] = "সঠিক ট্রানজেকশন আইডি (TrxID) দিন।";
    } else {
        $checkStmt = $conn->prepare("SELECT id FROM deposit_requests WHERE trx_id = ?");
        $checkStmt->bind_param("s", $trx_id);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();

        if ($checkRes->num_rows > 0) {
            $_SESSION['Error'] = "এই ট্রানজেকশন আইডিটি ইতোমধ্যে ব্যবহার করা হয়েছে!";
        } else {
            $screenshotPath = null;
            if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === 0) {
                $uploadDir = "uploads/deposits/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $ext = strtolower(pathinfo($_FILES["screenshot"]["name"], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($ext, $allowed)) {
                    $newFilename = "dep_" . $id . "_" . time() . "." . $ext;
                    $targetFile = $uploadDir . $newFilename;
                    if (move_uploaded_file($_FILES["screenshot"]["tmp_name"], $targetFile)) {
                        $screenshotPath = $targetFile;
                    }
                }
            }

            $insStmt = $conn->prepare("INSERT INTO deposit_requests (user_id, gateway, method, trx_id, amount, screenshot, status) VALUES (?, 'manual', ?, ?, ?, ?, 'pending')");
            $insStmt->bind_param("issds", $id, $method, $trx_id, $amount, $screenshotPath);
            
            // ডাটাবেস থেকে ইউজারের ইমেইল বের করা
            $userEmail = "Unknown User";
            $userQuery = $conn->query("SELECT email FROM users WHERE id = '$id' LIMIT 1");
            if ($userQuery && $userRow = $userQuery->fetch_assoc()) {
                $userEmail = $userRow['email'];
            }

            // এখন সঠিক ইমেইলসহ নোটিফিকেশন পাঠানো হবে
            send_tg_instant('recharge', $userEmail, $amount, $method, $trx_id);
            
            if ($insStmt->execute()) {
                $_SESSION['Success'] = "পেমেন্ট সফলভাবে সাবমিট হয়েছে! কিছুক্ষণের মধ্যে ওয়ালেটে ব্যালেন্স যুক্ত হবে।";
            } else {
                $_SESSION['Error'] = "সার্ভারে ত্রুটি হয়েছে। পুনরায় চেষ্টা করুন।";
            }
            $insStmt->close();
        }
        $checkStmt->close();
    }

    echo "<script>window.location.href='recharge.php';</script>";
    exit();
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="shortcut icon" type="image/jpeg" href="/assets/logo.jpg">
<link rel="icon" type="image/jpeg" href="/assets/logo.jpg">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Hind+Siliguri:wght@500;600;700&display=swap" rel="stylesheet">

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
    }

    /* header.php theke asha shob duplicate floating elements ebong cut-off elements shompurno bondho kora */
    .content-wrapper > .fixed,
    div.fixed.bottom-5.right-5,
    div.fixed.bottom-20.right-5,
    a.bg-emerald-500.hover\:bg-emerald-600,
    a.bg-\[\#10B981\],
    a[href*="whatsapp"]:not(.single-clean-floating *):not(.sup-badge) {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }

    body {
        background-color: var(--bg-main) !important;
        color: var(--heading-dark);
        font-family: 'Hind Siliguri', 'Plus Jakarta Sans', sans-serif !important;
        margin: 0;
        padding: 0;
    }

    .content-wrapper {
        padding-top: 0px !important;
        margin-top: 0px !important;
    }

    .recharge-page-wrapper {
        max-width: 480px;
        margin: 0 auto;
        padding: 6px 12px 85px; /* Navbar er jonno clean padding */
    }

    .brand-recharge-card {
        background: var(--card-bg);
        border-radius: 20px;
        border: 1.5px solid var(--border-soft);
        box-shadow: 0 8px 24px rgba(18, 59, 112, 0.05);
        padding: 16px 14px;
        margin-bottom: 12px;
        position: relative;
        overflow: hidden;
    }
    .brand-recharge-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-blue), var(--secondary-cyan));
    }

    .steps-tracker {
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        margin-bottom: 14px;
    }
    .steps-tracker::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 25%;
        right: 25%;
        height: 2px;
        background: var(--border-soft);
        z-index: 1;
        transform: translateY(-50%);
    }
    .step-item {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 5px;
        background: var(--card-bg);
        padding: 4px 10px;
        border-radius: 30px;
        border: 1.5px solid var(--border-soft);
        font-size: 11.5px;
        font-weight: 700;
        color: var(--body-muted);
    }
    .step-item .circle {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        background: var(--border-soft);
        color: var(--heading-dark);
    }
    .step-item.active {
        border-color: var(--primary-blue);
        color: var(--primary-blue);
        box-shadow: 0 2px 6px rgba(8, 102, 217, 0.12);
    }
    .step-item.active .circle {
        background: linear-gradient(135deg, var(--primary-blue), var(--secondary-cyan));
        color: #ffffff;
    }
    .step-item.done {
        border-color: var(--success);
        color: var(--success);
    }
    .step-item.done .circle {
        background: var(--success);
        color: #ffffff;
    }

    .input-amount-custom {
        background: #F8FAFD !important;
        border: 2px solid var(--border-soft) !important;
        border-radius: 12px !important;
        color: var(--heading-dark) !important;
        font-size: 20px !important;
        font-weight: 900 !important;
        text-align: center;
        padding: 8px !important;
    }
    .input-amount-custom:focus {
        border-color: var(--primary-blue) !important;
        background: #ffffff !important;
        box-shadow: 0 0 0 3px rgba(8, 102, 217, 0.12) !important;
    }

    .chips-group {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
        margin-top: 6px;
    }
    .amount-chip {
        background: #F8FAFD;
        border: 1px solid var(--border-soft);
        border-radius: 8px;
        padding: 5px 2px;
        text-align: center;
        font-size: 11.5px;
        font-weight: 800;
        color: var(--heading-dark);
        cursor: pointer;
        transition: 0.2s;
    }
    .amount-chip:hover {
        background: #E5F0FF;
        border-color: var(--primary-blue);
        color: var(--primary-blue);
    }

    .methods-wrapper {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin: 12px 0;
    }
    .method-choice {
        background: #F8FAFD;
        border: 1.5px solid var(--border-soft);
        border-radius: 14px;
        padding: 8px 6px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }
    .method-choice:hover {
        border-color: var(--secondary-cyan);
    }
    .method-choice.selected {
        border-color: var(--primary-blue);
        background: rgba(8, 102, 217, 0.05);
        box-shadow: 0 2px 10px rgba(8, 102, 217, 0.1);
    }
    .method-choice.selected::after {
        content: '\f058';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        top: 4px;
        right: 6px;
        color: var(--primary-blue);
        font-size: 11px;
    }
    .method-choice img {
        height: 24px !important;
        max-width: 80px;
        object-fit: contain;
    }
    .method-choice .method-title {
        font-size: 11px !important;
        font-weight: 700;
        color: var(--heading-dark);
        line-height: 1.1;
        text-align: center;
    }

    .btn-shebakit-main {
        background: linear-gradient(135deg, var(--primary-blue) 0%, #0353b7 100%);
        border: none;
        color: #ffffff;
        font-size: 13.5px;
        font-weight: 800;
        padding: 10px;
        border-radius: 12px;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        box-shadow: 0 4px 12px rgba(8, 102, 217, 0.2);
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-shebakit-back {
        background: #F8FAFD;
        border: 1px solid var(--border-soft);
        color: var(--body-muted);
        padding: 10px;
        border-radius: 12px;
        width: 100%;
        font-weight: 700;
        font-size: 12px;
        cursor: pointer;
    }

    .number-copy-strip {
        background: rgba(8, 184, 208, 0.08);
        border: 1.5px dashed var(--secondary-cyan);
        border-radius: 12px;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 10px 0;
    }

    .copy-pill-btn {
        background: var(--secondary-cyan);
        color: #ffffff;
        border: none;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 800;
        cursor: pointer;
    }

    .history-brand-card {
        background: var(--card-bg);
        border: 1px solid var(--border-soft);
        border-radius: 16px;
        padding: 14px 12px;
        box-shadow: 0 3px 10px rgba(18, 59, 112, 0.03);
    }
    .history-table-modern {
        width: 100%;
        border-collapse: collapse;
    }
    .history-table-modern thead th {
        background: #F8FAFD;
        color: var(--heading-dark);
        font-size: 11px;
        font-weight: 800;
        padding: 8px;
        border-bottom: 1.5px solid var(--border-soft);
    }
    .history-table-modern tbody td {
        padding: 8px 6px;
        font-size: 11.5px;
        vertical-align: middle;
        color: var(--body-muted);
        border-bottom: 1px solid var(--border-soft);
    }

    .badge-status {
        padding: 2px 7px;
        border-radius: 16px;
        font-size: 10px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 2px;
    }
    .badge-status.pending { background: #FEF3C7; color: #B45309; }
    .badge-status.completed { background: #DCFCE7; color: var(--success); }
    .badge-status.failed { background: #FEE2E2; color: #EF4444; }

    /* Single Clean Floating WhatsApp Button above Bottom Nav */
    .single-clean-floating {
        position: fixed;
        bottom: 64px;
        right: 14px;
        z-index: 45;
    }
    .btn-clean-join {
        background: #10B981;
        color: #ffffff !important;
        font-weight: 700;
        font-size: 11.5px;
        padding: 7px 14px;
        border-radius: 30px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
        text-decoration: none !important;
        transition: transform 0.2s;
    }
    .btn-clean-join:hover {
        transform: scale(1.04);
    }

    /* Fixed Clean Bottom Navigation Bar */
    .pro-bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        background: #FFFFFF;
        border-top: 1px solid var(--border-soft);
        padding: 6px 12px 5px;
        display: flex;
        align-items: center;
        justify-content: space-around;
        z-index: 50;
        box-shadow: 0 -3px 15px rgba(18, 59, 112, 0.05);
    }
    .nav-tab-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none !important;
        color: var(--body-muted);
        padding: 2px 6px;
        min-width: 55px;
        transition: color 0.15s;
    }
    .nav-tab-item i {
        font-size: 16px;
        margin-bottom: 2px;
    }
    .nav-tab-item span {
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }
    .nav-tab-item:hover, .nav-tab-item.active {
        color: var(--primary-blue);
    }
</style>

<div class="recharge-page-wrapper">

    <?php if(isset($_SESSION['Error'])): ?>
        <div class="alert alert-danger text-center fw-bold rounded-3 shadow-sm mb-2 border-0 bg-red-100 text-red-700 py-1.5 text-xs" role="alert">
            <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo $_SESSION['Error']; unset($_SESSION['Error']); ?>
        </div>
    <?php elseif(isset($_SESSION['Success'])): ?>
        <div class="alert alert-success text-center fw-bold rounded-3 shadow-sm mb-2 border-0 bg-emerald-100 text-emerald-800 py-1.5 text-xs" role="alert">
            <i class="fa-solid fa-circle-check me-1"></i> <?php echo $_SESSION['Success']; unset($_SESSION['Success']); ?>
        </div>
    <?php endif; ?>

    <!-- Recharge Box Card -->
    <div class="brand-recharge-card">
        <div class="text-center mb-2">
            <div class="inline-flex items-center justify-center gap-1.5 mb-0.5">
                <i class="fa-solid fa-bolt text-[#0866D9] text-sm"></i>
                <h4 class="font-extrabold text-[#123B70] text-sm sm:text-base">ShebaKit ব্যালেন্স রিচার্জ</h4>
            </div>
            <p class="text-[#52657D] text-[11px]">বিকাশ ও নগদ এর মাধ্যমে দ্রুত ব্যালেন্স অ্যাড করুন (২৪/৭)</p>
        </div>

        <div class="steps-tracker">
            <div class="step-item active" id="node1">
                <div class="circle">১</div>
                <span>পরিমাণ</span>
            </div>
            <div class="step-item" id="node2">
                <div class="circle">২</div>
                <span id="labelStep2">পেমেন্ট তথ্য</span>
            </div>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" id="depositForm">
            <input type="hidden" name="method" id="selectedMethodInput" value="bkash">

            <!-- Step 1 -->
            <div id="tab1">
                <div class="mb-2">
                    <label class="text-[11px] text-[#123B70] font-bold mb-1 block">টাকার পরিমাণ লিখুন (সর্বনিম্ন ৫০ টাকা)</label>
                    <input type="number" min="50" step="1" class="form-control input-amount-custom" id="amount" name="amount" placeholder="৳ ৫০" required>
                    
                    <div class="chips-group">
                        <div class="amount-chip" onclick="setAmount(50)">৳ ৫০</div>
                        <div class="amount-chip" onclick="setAmount(100)">৳ ১০০</div>
                        <div class="amount-chip" onclick="setAmount(500)">৳ ৫০০</div>
                        <div class="amount-chip" onclick="setAmount(1000)">৳ ১,০০০</div>
                    </div>
                </div>

                <label class="text-[11px] text-[#123B70] font-bold mb-1 block">পেমেন্ট মেথড নির্বাচন করুন</label>
                <div class="methods-wrapper">
                    <!-- bKash Tile with compact logo & text -->
                    <div class="method-choice selected" onclick="selectMethod('bkash', '<?php echo htmlspecialchars($bkash_number); ?>')">
                        <img src="lib/img/bkash.svg" alt="bKash" onerror="this.src='assets/logo.jpg'">
                        <span class="method-title">বিকাশ (Send Money)</span>
                    </div>

                    <!-- Nagad Tile with compact logo & text -->
                    <div class="method-choice" onclick="selectMethod('nagad', '<?php echo htmlspecialchars($nagad_number); ?>')">
                        <img src="lib/img/nagad.png" alt="Nagad" onerror="this.src='assets/logo.jpg'">
                        <span class="method-title">নগদ (Send Money)</span>
                    </div>
                </div>

                <button type="button" class="btn-shebakit-main" onclick="goToStep2()">
                    পরবর্তী ধাপ <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </div>

            <!-- Step 2 -->
            <div id="tab2" style="display: none;">
                <div class="number-copy-strip">
                    <div>
                        <span class="text-[10px] text-[#08B8D0] font-bold block" id="methodNameText">বিকাশ পার্সোনাল নম্বর:</span>
                        <span class="font-black text-[#123B70] text-sm tracking-wider" id="targetNumber"><?php echo htmlspecialchars($bkash_number); ?></span>
                    </div>
                    <button type="button" class="copy-pill-btn" onclick="copyNumber()">
                        <i class="fa-solid fa-copy me-1"></i> কপি
                    </button>
                </div>

                <div class="bg-[#F3F8FF] border border-[#DCE8F5] rounded-xl p-2 mb-2 text-[10.5px] text-[#123B70] font-medium flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-info text-[#0866D9] text-xs"></i>
                    <span>উপরের নাম্বারে <b id="displayPayAmount" class="text-[#0866D9] font-bold">৳ ৫০</b> সেন্ড মানি করে TrxID দিন।</span>
                </div>

                <div class="mb-2">
                    <label for="trx_id" class="text-[11px] text-[#123B70] font-bold mb-1 block">ট্রানজেকশন আইডি (TrxID)</label>
                    <input type="text" class="form-control input-amount-custom text-xs uppercase tracking-widest !py-2" id="trx_id" name="trx_id" placeholder="যেমন: BK105953 / 7A8B9C">
                </div>

                <div class="mb-2.5">
                    <label for="screenshot" class="text-[11px] text-[#123B70] font-bold mb-1 block">পেমেন্ট স্ক্রিনশট (ঐচ্ছিক)</label>
                    <input type="file" class="form-control rounded-xl text-xs py-1.5 bg-white border-[#DCE8F5]" id="screenshot" name="screenshot" accept="image/*">
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <button type="button" class="btn-shebakit-back col-span-1" onclick="goToStep1()">
                        <i class="fa-solid fa-arrow-left me-1"></i> ব্যাকে
                    </button>
                    <button type="submit" name="submit_deposit" class="btn-shebakit-main col-span-2">
                        <i class="fa-solid fa-circle-check me-1"></i> কনফার্ম করুন
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- History Table Card -->
    <div class="history-brand-card">
        <h5 class="font-extrabold text-[#123B70] text-xs mb-2 flex items-center gap-1.5">
            <i class="fa-solid fa-clock-rotate-left text-[#0866D9]"></i> পূর্ববর্তী রিচার্জ হিস্টোরি
        </h5>
        
        <div class="overflow-x-auto">
            <table class="history-table-modern">
                <thead>
                    <tr>
                        <th>আইডি</th>
                        <th>মেথড ও TrxID</th>
                        <th>সময়</th>
                        <th>পরিমাণ</th>
                        <th>স্ট্যাটাস</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $history = [];
                    if (!empty($id)) {
                        $histStmt = $conn->prepare("SELECT id, method, trx_id, amount, status, created_at FROM deposit_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
                        $histStmt->bind_param("i", $id);
                        $histStmt->execute();
                        $histRes = $histStmt->get_result();
                        while ($row = $histRes->fetch_assoc()) {
                            $history[] = $row;
                        }
                        $histStmt->close();
                    }

                    if (empty($history)):
                    ?>
                    <tr>
                        <td colspan="5" class="text-center text-slate-400 py-3 font-bold text-xs">কোনো লেনদেন পাওয়া যায়নি</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($history as $row):
                        $statusClass = 'pending';
                        $statusIcon = 'fa-clock';
                        $statusText = 'প্রসেসিং';

                        if ($row['status'] === 'completed') {
                            $statusClass = 'completed';
                            $statusIcon = 'fa-check';
                            $statusText = 'সফল';
                        } elseif (in_array($row['status'], ['failed', 'cancelled'])) {
                            $statusClass = 'failed';
                            $statusIcon = 'fa-xmark';
                            $statusText = 'বাতিল';
                        }
                    ?>
                    <tr>
                        <td class="font-bold text-[#123B70]">#<?php echo htmlspecialchars($row['id']); ?></td>
                        <td>
                            <span class="text-[9.5px] font-bold uppercase text-[#0866D9] bg-[#F3F8FF] px-1.5 py-0.5 rounded border border-[#DCE8F5]">
                                <?php echo htmlspecialchars($row['method']); ?>
                            </span>
                            <div class="font-mono text-[10.5px] text-[#52657D] mt-0.5"><?php echo htmlspecialchars($row['trx_id']); ?></div>
                        </td>
                        <td class="text-[10.5px] text-[#52657D]"><?php echo htmlspecialchars(date('d M, h:i A', strtotime($row['created_at']))); ?></td>
                        <td class="font-black text-[#123B70] text-xs">৳ <?php echo htmlspecialchars($row['amount']); ?></td>
                        <td>
                            <span class="badge-status <?php echo $statusClass; ?>">
                                <i class="fa-solid <?php echo $statusIcon; ?>"></i> <?php echo $statusText; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Only One Single Clean Floating WhatsApp Button -->
<?php if (!empty($wa_group)): ?>
<div class="single-clean-floating">
    <a href="<?php echo htmlspecialchars($wa_group); ?>" target="_blank" class="btn-clean-join">
        <i class="fa-brands fa-whatsapp text-sm"></i> Join Group
    </a>
</div>
<?php endif; ?>

<!-- Clean & Beautiful Bottom Navigation Bar (No Overlapping Text) -->
<nav class="pro-bottom-nav">
    <a href="dashboard.php" class="nav-tab-item">
        <i class="fa-solid fa-house"></i>
        <span>হোম</span>
    </a>
    <a href="serverCopy.php" class="nav-tab-item">
        <i class="fa-solid fa-layer-group"></i>
        <span>সেবা</span>
    </a>
    <a href="work_history.php" class="nav-tab-item">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span>হিস্টোরি</span>
    </a>
    <a href="profile.php" class="nav-tab-item">
        <i class="fa-solid fa-user-gear"></i>
        <span>প্রোফাইল</span>
    </a>
</nav>

<script>
let currentNumber = "<?php echo htmlspecialchars($bkash_number); ?>";

function selectMethod(method, number) {
    $('.method-choice').removeClass('selected');
    event.currentTarget.classList.add('selected');
    
    $('#selectedMethodInput').val(method);
    currentNumber = number;
    $('#targetNumber').text(number);
    
    if (method === 'bkash') {
        $('#methodNameText').text('বিকাশ পার্সোনাল নম্বর:');
    } else {
        $('#methodNameText').text('নগদ পার্সোনাল নম্বর:');
    }
}

function setAmount(val) {
    $('#amount').val(val).focus();
}

function copyNumber() {
    if (!currentNumber) return;
    navigator.clipboard.writeText(currentNumber).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'নম্বর কপি সফল!',
            text: currentNumber,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500
        });
    });
}

function goToStep2() {
    let amount = parseFloat($('#amount').val());
    if (isNaN(amount) || amount < 50) {
        Swal.fire({
            icon: 'warning',
            title: 'সঠিক অ্যামাউন্ট দিন',
            text: 'অনুগ্রহ করে সর্বনিম্ন ৫০ টাকা বা তার বেশি লিখুন।'
        });
        return;
    }

    $('#displayPayAmount').text('৳ ' + amount);
    
    $('#tab1').fadeOut(120, function() {
        $('#tab2').fadeIn(120);
        $('#node1').removeClass('active').addClass('done');
        $('#node2').addClass('active');
        $('#labelStep2').css('color', '#0866D9');
    });
}

function goToStep1() {
    $('#tab2').fadeOut(120, function() {
        $('#tab1').fadeIn(120);
        $('#node2').removeClass('active');
        $('#node1').removeClass('done').addClass('active');
        $('#labelStep2').css('color', '#52657D');
    });
}
</script>

<?php include_once("footer.php"); ?>