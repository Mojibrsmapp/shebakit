<?php 
include_once("header.php");

$user_email = !empty($email) ? $email : ($_SESSION['user_id'] ? base64_decode($_SESSION['user_id']) : '');
$user_id    = !empty($id) ? intval($id) : 0;
$wa_group   = get_site_setting($conn, "whatsapp_group", "");
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="shortcut icon" type="image/jpeg" href="https://shebakit.xyz/assets/logo.jpg">
<link rel="icon" type="image/jpeg" href="https://shebakit.xyz/assets/logo.jpg">
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
        --danger: #EF4444;
    }

    .content-wrapper > .fixed,
    div.fixed.bottom-5.right-5,
    div.fixed.bottom-20.right-5,
    a.bg-emerald-500.hover\:bg-emerald-600,
    a[href*="whatsapp"]:not(.single-clean-floating *):not(.sup-badge) {
        display: none !important;
        visibility: hidden !important;
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

    .history-page-wrapper {
        max-width: 480px;
        margin: 0 auto;
        padding: 4px 10px 90px;
    }

    /* --- Notice Marquee --- */
    .notice-marquee-card {
        background: #FFFFFF;
        border: 1.5px solid var(--border-soft);
        border-radius: 12px;
        padding: 6px 10px;
        margin-bottom: 10px;
        box-shadow: 0 3px 10px rgba(18, 59, 112, 0.03);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .notice-tag {
        background: #EFF6FF;
        color: var(--primary-blue);
        border: 1.5px solid var(--border-soft);
        font-size: 10.5px;
        font-weight: 800;
        padding: 2px 6px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* --- Main Card --- */
    .history-main-card {
        background: var(--card-bg);
        border: 1.5px solid var(--border-soft);
        border-radius: 18px;
        padding: 12px 10px;
        box-shadow: 0 6px 20px rgba(18, 59, 112, 0.04);
        position: relative;
        overflow: hidden;
    }
    .history-main-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3.5px;
        background: linear-gradient(90deg, var(--primary-blue), var(--secondary-cyan));
    }

    .card-header-clean {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
        gap: 6px;
    }
    .card-header-title {
        font-size: 13px;
        font-weight: 800;
        color: var(--heading-dark);
        display: flex;
        align-items: center;
        gap: 5px;
        margin: 0;
    }
    .live-badge {
        font-size: 10px;
        font-weight: 800;
        color: var(--primary-blue);
        background: #EFF6FF;
        padding: 2px 7px;
        border-radius: 16px;
        border: 1px solid var(--border-soft);
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* --- Search --- */
    .search-input-box {
        background: #F8FAFD;
        border: 1.5px solid var(--border-soft);
        border-radius: 10px;
        padding: 6px 10px;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 10px;
    }
    .search-input-box input {
        border: none;
        outline: none;
        background: transparent;
        font-size: 12px;
        color: var(--heading-dark);
        width: 100%;
    }

    /* --- History Item --- */
    .history-record-item {
        background: #F8FAFD;
        border: 1px solid var(--border-soft);
        border-radius: 12px;
        padding: 10px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: all 0.18s ease;
    }
    .history-record-item:hover {
        border-color: var(--primary-blue);
        background: #FFFFFF;
        box-shadow: 0 4px 12px rgba(8, 102, 217, 0.08);
    }

    .record-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 4px;
        padding-bottom: 5px;
        border-bottom: 1px dashed var(--border-soft);
        gap: 6px;
    }
    .record-title {
        font-size: 12px;
        font-weight: 800;
        color: var(--heading-dark);
        display: flex;
        align-items: center;
        gap: 5px;
        line-height: 1.25;
        word-break: break-word;
    }
    .record-cost {
        font-size: 12px;
        font-weight: 900;
        color: #DC2626;
        background: #FEE2E2;
        padding: 1px 6px;
        border-radius: 6px;
        white-space: nowrap;
    }

    .record-details-flex {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 11px;
        margin-top: 4px;
        gap: 6px;
    }
    .record-meta {
        color: var(--body-muted);
    }
    .record-meta b {
        color: var(--heading-dark);
    }

    .input-snippet {
        font-size: 11px;
        color: #475569;
        background: #FFFFFF;
        padding: 4px 8px;
        border-radius: 8px;
        border: 1px solid var(--border-soft);
        margin: 4px 0;
        font-family: monospace;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Force styles for buttons to prevent Tailwind override */
    .btn-smart-details {
        background: linear-gradient(135deg, #0866D9, #08B8D0) !important;
        color: #FFFFFF !important;
        font-size: 10.5px;
        font-weight: 800;
        padding: 3px 9px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 2px 6px rgba(8, 102, 217, 0.2);
        transition: transform 0.15s;
        border: none;
        cursor: pointer;
    }
    .btn-smart-details:hover {
        transform: scale(1.03);
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 10px;
        font-weight: 800;
        padding: 1.5px 6px;
        border-radius: 6px;
    }
    .status-pending { background: #FEF3C7; color: #B45309; border: 1px solid #FDE68A; }
    .status-completed, .status-success { background: #DCFCE7; color: #15803D; border: 1px solid #BBF7D0; }
    .status-cancel, .status-cancelled, .status-failed { background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA; }

    /* =========================================
       PREMIUM MODAL STYLES (FIXED)
       ========================================= */
    .custom-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(18, 59, 112, 0.6);
        backdrop-filter: blur(5px);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 12px;
    }
    .custom-modal-box {
        background: #FFFFFF;
        border-radius: 20px;
        width: 100%;
        max-width: 400px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 50px rgba(18, 59, 112, 0.3);
        border: 1px solid var(--border-soft);
        overflow: hidden;
        animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @keyframes modalIn {
        0% { opacity: 0; transform: scale(0.95) translateY(15px); }
        100% { opacity: 1; transform: scale(1) translateY(0); }
    }
    
    .modal-header-bar {
        background: linear-gradient(135deg, var(--primary-blue), var(--secondary-cyan));
        color: #FFFFFF;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }
    .modal-body-scrollable {
        padding: 18px;
        overflow-y: auto;
        flex-grow: 1;
        background: #F8FAFD;
    }
    .modal-close-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: #FFFFFF;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }
    .modal-close-btn:hover {
        background: rgba(255, 255, 255, 0.4);
    }

    /* Modal Inner Cards */
    .modal-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 10px;
        margin-bottom: 10px;
        border-bottom: 1px dashed var(--border-soft);
    }
    .modal-info-label {
        font-size: 11.5px;
        font-weight: 700;
        color: var(--body-muted);
    }
    .modal-info-value {
        font-size: 12px;
        font-weight: 800;
        color: var(--heading-dark);
    }
    .modal-info-value.price { color: #DC2626; font-size: 14px; }
    .modal-info-value.id { color: var(--primary-blue); font-family: monospace; font-size: 13px; }

    /* FORCED BUTTON STYLES (To fix invisible buttons) */
    .modal-action-btn {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
        padding: 12px !important;
        border-radius: 12px !important;
        font-size: 13px !important;
        font-weight: 800 !important;
        text-decoration: none !important;
        transition: all 0.2s ease !important;
        border: none !important;
        cursor: pointer !important;
        gap: 8px !important;
    }
    
    .btn-copy {
        background-color: #EFF6FF !important;
        color: #0866D9 !important;
        border: 1px solid #BFDBFE !important;
        margin-top: 8px !important;
    }
    .btn-copy:hover { background-color: #DBEAFE !important; }

    .btn-download {
        background: linear-gradient(135deg, #10B981, #059669) !important;
        color: #FFFFFF !important;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3) !important;
        margin-top: 10px !important;
    }
    .btn-download:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 15px rgba(16, 185, 129, 0.4) !important;
    }

    /* Floating WhatsApp */
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
        font-size: 11px;
        padding: 6px 12px;
        border-radius: 30px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
        text-decoration: none !important;
    }

    /* Bottom Nav */
    .pro-bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        background: #FFFFFF;
        border-top: 1px solid var(--border-soft);
        padding: 6px 10px 5px;
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
    }
    .nav-tab-item i { font-size: 15px; margin-bottom: 2px; }
    .nav-tab-item span { font-size: 10.5px; font-weight: 700; line-height: 1; }
    .nav-tab-item:hover, .nav-tab-item.active { color: var(--primary-blue); }
</style>

<div class="history-page-wrapper">

    <!-- Notice Card -->
    <div class="notice-marquee-card">
        <span class="notice-tag"><i class="fa-solid fa-bullhorn"></i> নোটিশ</span>
        <div class="flex-1 overflow-hidden">
            <?php
            include_once('includes/marquee.php');
            if (class_exists('Marquee')) {
                $marquee = new Marquee($conn, 2);
                $marquee->display();
            }
            ?>
        </div>
    </div>

    <!-- Main History Card -->
    <div class="history-main-card">
        
        <div class="card-header-clean">
            <h4 class="card-header-title">
                <i class="fa-solid fa-clock-rotate-left text-[#0866D9]"></i> কাজের হিস্টোরি ও অর্ডার
            </h4>
            <span class="live-badge">লাইভ রেকর্ডস</span>
        </div>

        <!-- Search Input -->
        <div class="search-input-box">
            <i class="fa-solid fa-magnifying-glass text-[#0866D9] text-xs"></i>
            <input type="text" id="recordSearch" placeholder="সার্ভিস বা নম্বর দিয়ে খুঁজুন...">
        </div>

        <div id="recordsContainer">
            <?php 
            $all_records = [];

            if ($user_id > 0) {
                $q1 = $conn->prepare("SELECT id, order_type, price, status, input_data, result_text, result_file, created_at FROM orders WHERE user_id = ? ORDER BY id DESC");
                $q1->bind_param("i", $user_id);
                $q1->execute();
                $res1 = $q1->get_result();
                while ($r1 = $res1->fetch_assoc()) {
                    $all_records[] = [
                        'source'      => 'orders',
                        'order_id'    => $r1['id'],
                        'type'        => !empty($r1['order_type']) ? $r1['order_type'] : 'সার্ভিস অর্ডার',
                        'price'       => floatval($r1['price']),
                        'before_bal'  => '',
                        'after_bal'   => '',
                        'input_data'  => $r1['input_data'],
                        'status'      => strtolower(trim($r1['status'])),
                        'result_text' => $r1['result_text'],
                        'result_file' => $r1['result_file'],
                        'time'        => $r1['created_at'],
                        'timestamp'   => strtotime($r1['created_at'])
                    ];
                }
                $q1->close();
            }

            if (!empty($user_email)) {
                $q2 = $conn->prepare("SELECT id, order_type, price, status, nid, user_name, opinion, file_one, order_time, complete_time FROM order_list WHERE email = ? ORDER BY id DESC");
                $q2->bind_param("s", $user_email);
                $q2->execute();
                $res2 = $q2->get_result();
                while ($r2 = $res2->fetch_assoc()) {
                    $all_records[] = [
                        'source'      => 'order_list',
                        'order_id'    => $r2['id'],
                        'type'        => !empty($r2['order_type']) ? $r2['order_type'] : 'ডকুমেন্ট অর্ডার',
                        'price'       => floatval($r2['price']),
                        'before_bal'  => '',
                        'after_bal'   => '',
                        'input_data'  => $r2['nid'],
                        'status'      => strtolower(trim($r2['status'])),
                        'result_text' => (!empty($r2['opinion']) && $r2['opinion'] != 'নেই') ? $r2['opinion'] : '',
                        'result_file' => $r2['file_one'],
                        'time'        => $r2['order_time'],
                        'timestamp'   => strtotime($r2['order_time'])
                    ];
                }
                $q2->close();
            }

            if (!empty($user_email)) {
                $q3 = $conn->prepare("SELECT id, order_type, price, current_balance, balance_after_cut, order_time FROM history_work WHERE email = ? ORDER BY id DESC");
                $q3->bind_param("s", $user_email);
                $q3->execute();
                $res3 = $q3->get_result();
                while ($r3 = $res3->fetch_assoc()) {
                    $all_records[] = [
                        'source'      => 'history_work',
                        'order_id'    => $r3['id'],
                        'type'        => !empty($r3['order_type']) ? $r3['order_type'] : 'ব্যালেন্স লেজার',
                        'price'       => floatval($r3['price']),
                        'before_bal'  => '৳ ' . intval($r3['current_balance']),
                        'after_bal'   => '৳ ' . intval($r3['balance_after_cut']),
                        'input_data'  => '',
                        'status'      => 'completed',
                        'result_text' => '',
                        'result_file' => '',
                        'time'        => $r3['order_time'],
                        'timestamp'   => strtotime($r3['order_time'])
                    ];
                }
                $q3->close();
            }

            usort($all_records, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });

            if (!empty($all_records)):
                foreach ($all_records as $row):
                    $orderType  = htmlspecialchars($row['type']);
                    $price      = $row['price'];
                    $timeStr    = date('d M, h:i A', $row['timestamp']);
                    $status     = $row['status'];
                    $resultText = trim($row['result_text'] ?? '');
                    $resultFile = trim($row['result_file'] ?? '');

                    $statusClass = 'status-pending';
                    $statusLabel = 'প্রসেসিং';
                    $statusIcon  = 'fa-hourglass-half';

                    if (in_array($status, ['success', 'completed'])) {
                        $statusClass = 'status-completed';
                        $statusLabel = 'সম্পন্ন';
                        $statusIcon  = 'fa-check';
                    } elseif (in_array($status, ['cancel', 'cancelled', 'failed'])) {
                        $statusClass = 'status-cancel';
                        $statusLabel = 'বাতিল';
                        $statusIcon  = 'fa-xmark';
                    }

                    $hasFile = (!empty($resultFile) && strtolower($resultFile) !== 'null' && strtolower($resultFile) !== 'undefined');
            ?>
            <div class="history-record-item record-item">
                <div class="record-header">
                    <span class="record-title">
                        <i class="fa-solid fa-file-invoice text-[#0866D9]"></i> <?php echo $orderType; ?>
                    </span>
                    <span class="record-cost">-৳ <?php echo $price; ?></span>
                </div>

                <?php if (!empty($row['input_data'])): ?>
                    <div class="input-snippet">
                        <b>ইনপুট:</b> <?php echo htmlspecialchars($row['input_data']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($row['before_bal'])): ?>
                    <div class="record-details-flex mb-1.5">
                        <span class="record-meta">পূর্ববর্তী: <b><?php echo $row['before_bal']; ?></b></span>
                        <span class="record-meta">পরবর্তী: <b class="text-[#10B981]"><?php echo $row['after_bal']; ?></b></span>
                    </div>
                <?php endif; ?>

                <div class="record-details-flex pt-1 border-t border-[var(--border-soft)]">
                    <span class="text-[10.5px] text-slate-400"><i class="fa-regular fa-clock"></i> <?php echo $timeStr; ?></span>
                    
                    <div class="flex items-center gap-2">
                        <button type="button" class="btn-smart-details details-btn" 
                                data-type="<?php echo $orderType; ?>"
                                data-id="<?php echo $row['order_id']; ?>"
                                data-status="<?php echo $statusLabel; ?>"
                                data-price="<?php echo $price; ?>"
                                data-time="<?php echo $timeStr; ?>"
                                data-input="<?php echo htmlspecialchars($row['input_data'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-result="<?php echo htmlspecialchars($resultText, ENT_QUOTES, 'UTF-8'); ?>"
                                data-file="<?php echo $hasFile ? htmlspecialchars($resultFile, ENT_QUOTES, 'UTF-8') : ''; ?>">
                            <i class="fa-solid fa-circle-info"></i> বিস্তারিত
                        </button>

                        <span class="status-pill <?php echo $statusClass; ?>">
                            <i class="fa-solid <?php echo $statusIcon; ?>"></i> <?php echo $statusLabel; ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php 
                endforeach;
            else:
            ?>
            <div class="text-center py-8 text-[#52657D]">
                <i class="fa-solid fa-box-open text-3xl text-slate-300 mb-2"></i>
                <p class="text-xs font-bold mb-0">এখনো কোনো কাজের লেনদেন পাওয়া যায়নি।</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Premium Interactive Details Modal -->
<div class="custom-modal-backdrop" id="detailsModal">
    <div class="custom-modal-box">
        <!-- Header -->
        <div class="modal-header-bar">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-file-shield text-base text-white"></i>
                <h5 class="font-extrabold text-xs m-0 text-white" id="modalTitle">অর্ডারের বিবরণ</h5>
            </div>
            <button class="modal-close-btn" onclick="closeDetailsModal()">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body-scrollable text-xs">
            
            <div class="modal-info-row">
                <span class="modal-info-label">অর্ডার ID:</span>
                <span class="modal-info-value id" id="modalOrderId">#000</span>
            </div>
            
            <div class="modal-info-row">
                <span class="modal-info-label">স্ট্যাটাস:</span>
                <span class="modal-info-value" id="modalStatus">Success</span>
            </div>
            
            <div class="modal-info-row">
                <span class="modal-info-label">চার্জ:</span>
                <span class="modal-info-value price" id="modalPrice">৳ 0.00</span>
            </div>
            
            <div class="modal-info-row" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                <span class="modal-info-label">তারিখ ও সময়:</span>
                <span class="modal-info-value" style="font-size: 11px; font-weight: 600; color: #52657D;" id="modalTime">--</span>
            </div>

            <!-- Input Box -->
            <div id="modalInputWrap" style="display:none; margin-top: 12px;">
                <span class="modal-info-label block mb-1">প্রদত্ত ইনপুট:</span>
                <div class="bg-[#EFF6FF] p-2.5 rounded-xl font-mono text-[11px] border border-[#BFDBFE] break-all text-[#1E3A8A]" id="modalInput"></div>
            </div>

            <!-- Result Box -->
            <div id="modalResultWrap" style="display:none; margin-top: 12px;">
                <span class="modal-info-label block mb-1">ডেলিভারি ফলাফল / নোট:</span>
                <div class="bg-[#F0FDF4] text-[#166534] p-2.5 rounded-xl font-mono text-[11px] border border-[#BBF7D0] whitespace-pre-wrap break-all max-h-40 overflow-y-auto" id="modalResult"></div>
                <button type="button" onclick="copyResultText()" class="modal-action-btn btn-copy">
                    <i class="fa-solid fa-copy"></i> ফলাফল কপি করুন
                </button>
            </div>

            <!-- File Download Button -->
            <div id="modalFileWrap" style="display:none; margin-top: 8px;">
                <a href="#" id="modalFileLink" target="_blank" rel="noopener noreferrer" class="modal-action-btn btn-download">
                    <i class="fa-solid fa-download"></i> ডেলিভারি ফাইল ডাউনলোড করুন
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Single Clean Floating WhatsApp Button -->
<?php if (!empty($wa_group)): ?>
<div class="single-clean-floating">
    <a href="<?php echo htmlspecialchars($wa_group); ?>" target="_blank" class="btn-clean-join">
        <i class="fa-brands fa-whatsapp text-sm"></i> Join Group
    </a>
</div>
<?php endif; ?>

<!-- Dashboard Style Bottom Navigation Bar -->
<nav class="pro-bottom-nav">
    <a href="dashboard.php" class="nav-tab-item">
        <i class="fa-solid fa-house"></i>
        <span>হোম</span>
    </a>
    <a href="serverCopy.php" class="nav-tab-item">
        <i class="fa-solid fa-layer-group"></i>
        <span>সেবা</span>
    </a>
    <a href="work_history.php" class="nav-tab-item active">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span>হিস্টোরি</span>
    </a>
    <a href="profile.php" class="nav-tab-item">
        <i class="fa-solid fa-user-gear"></i>
        <span>প্রোফাইল</span>
    </a>
</nav>

<script>
    document.querySelectorAll('.details-btn').forEach(button => {
        button.addEventListener('click', function() {
            const type   = this.getAttribute('data-type')   || '';
            const id     = this.getAttribute('data-id')     || '';
            const status = this.getAttribute('data-status') || '';
            const price  = this.getAttribute('data-price')  || '0';
            const time   = this.getAttribute('data-time')   || '';
            const input  = this.getAttribute('data-input')  || '';
            const result = this.getAttribute('data-result') || '';
            const file   = this.getAttribute('data-file')   || '';

            openDetailsModal(type, id, status, price, time, input, result, file);
        });
    });

    function openDetailsModal(type, id, status, price, time, input, result, file) {
        document.getElementById('modalTitle').textContent = 'সেবা: ' + type;
        document.getElementById('modalOrderId').textContent = '#' + id;
        document.getElementById('modalStatus').textContent = status;
        document.getElementById('modalPrice').textContent = '৳ ' + price;
        document.getElementById('modalTime').textContent = time;

        // Status Styling
        const statusEl = document.getElementById('modalStatus');
        statusEl.className = 'modal-info-value px-2.5 py-1 rounded-md text-[11px] inline-block'; 
        if (status === 'সম্পন্ন') {
            statusEl.classList.add('bg-emerald-100', 'text-emerald-800');
        } else if (status === 'বাতিল') {
            statusEl.classList.add('bg-rose-100', 'text-rose-800');
        } else {
            statusEl.classList.add('bg-amber-100', 'text-amber-800');
        }

        // Input Handling
        const inputWrap = document.getElementById('modalInputWrap');
        if (input && input.trim() !== '' && input !== 'undefined' && input !== 'null') {
            document.getElementById('modalInput').textContent = input;
            inputWrap.style.display = 'block';
        } else {
            inputWrap.style.display = 'none';
        }

        // Result Handling
        const resultWrap = document.getElementById('modalResultWrap');
        if (result && result.trim() !== '' && result !== 'undefined' && result !== 'null') {
            document.getElementById('modalResult').textContent = result;
            resultWrap.style.display = 'block';
        } else {
            resultWrap.style.display = 'none';
        }

        // File Handling
        const fileWrap = document.getElementById('modalFileWrap');
        const fileLink = document.getElementById('modalFileLink');
        
        if (file && file.trim() !== '' && file !== 'undefined' && file !== 'null') {
            fileLink.setAttribute('href', file);
            fileWrap.style.display = 'block';
        } else {
            fileLink.setAttribute('href', '#');
            fileWrap.style.display = 'none';
        }

        document.getElementById('detailsModal').style.display = 'flex';
    }

    function closeDetailsModal() {
        document.getElementById('detailsModal').style.display = 'none';
    }

    function copyResultText() {
        const text = document.getElementById('modalResult').textContent;
        if (!text) return;
        navigator.clipboard.writeText(text).then(() => {
            if (window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: 'কপি সফল!',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1200
                });
            } else {
                alert('কপি সফল হয়েছে!');
            }
        });
    }

    window.onclick = function(event) {
        const modal = document.getElementById('detailsModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }

    document.getElementById('recordSearch').addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const records = document.querySelectorAll('.record-item');

        records.forEach(function(item) {
            const text = item.textContent.toLowerCase();
            if (text.includes(query)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
</script>

<?php include_once("footer.php"); ?>