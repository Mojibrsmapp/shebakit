<?php 
ob_start();
session_start();
include_once("includes/configuration.php");

if (isset($_SESSION['user_token'])) {
    $token_user = $_SESSION['user_token'];
    
    $stmt = $conn->prepare("SELECT id, balance, email, name, token, mobile, image FROM users WHERE token = ?");
    if ($stmt) {
        $stmt->bind_param("s", $token_user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $id = (int)$row["id"];
            $balance = $row["balance"];
            $email = $row["email"];
            $name = $row["name"];
            $token = $row["token"];
            $phone = $row["mobile"];
            $user_image = !empty($row["image"]) ? $row["image"] : "";
        } else {
            unset($_SESSION['user_token'], $_SESSION['user_id']);
            header("Location: index.php");
            exit();
        }
        $stmt->close();
    }
} else {
    header("Location: index.php");
    exit();
}

$support_num = '01849207118';
$whatsapp_link = 'https://wa.me/8801849207118';
$telegram_link = 'https://t.me/Sheba_kit';

// Dynamic Slider
$slider_images = ['/assets/slider/1.jpg', '/assets/slider/2.jpg'];
$slider_path = __DIR__ . '/assets/slider';
if (is_dir($slider_path)) {
    $files = @scandir($slider_path);
    if ($files) {
        $temp_images = [];
        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $temp_images[] = '/assets/slider/' . $file;
            }
        }
        if (!empty($temp_images)) { $slider_images = $temp_images; }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>সকল সেবা - সব কাজ, এক প্ল্যাটফর্মে</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/maateen/bangla-fonts/Nikosh/stylesheet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="shortcut icon" type="image/jpeg" href="https://shebakit.xyz/assets/logo.jpg">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body, input, button, select, textarea, p, h1, h2, h3, h4, h5, h6, span, a, label, li {
            font-family: 'Nikosh', 'SolaimanLipi', sans-serif !important;
            -webkit-font-smoothing: antialiased;
        }

        /* Icon Font Protection */
        .fa, .fas, .far, .fal, .fad, .fab, .fa-solid, .fa-regular, .fa-brands {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important;
        }

        body { 
            background: #F3F4F6; 
            color: #1E293B; 
            min-height: 100vh; 
            padding-bottom: 74px; 
        }

        /* 2-Column Mobile Compact Category Cards */
        .category-box {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #E5E7EB;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
        }
        .category-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 14px rgba(0, 0, 0, 0.07);
        }

        .category-header {
            padding: 7px 10px;
            color: #ffffff;
            font-size: 13.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .category-list {
            padding: 8px 10px;
            font-size: 11.5px;
            font-weight: 600;
            color: #475569;
            line-height: 1.45;
        }

        /* Slider Design */
        .sokol-slider-box {
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid #A7F3D0;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.08);
            background: #ECFDF5;
        }
        .sokol-slider-box .swiper-slide img {
            width: 100%;
            height: auto;
            max-height: 230px;
            object-fit: cover;
            display: block;
        }
        .slider-dots .swiper-pagination-bullet {
            width: 8px;
            height: 8px;
            background: #D1D5DB;
            opacity: 1;
            margin: 0 3px !important;
            transition: 0.3s;
        }
        .slider-dots .swiper-pagination-bullet-active {
            background: #059669 !important;
            transform: scale(1.2);
        }
    </style>
</head>
<body>

    <!-- 1. Top Header (Boro Logo Shaha) -->
    <header class="bg-white sticky top-0 z-40 border-b border-slate-100 shadow-sm">
        <div class="max-w-6xl mx-auto px-3.5 sm:px-6 py-2 flex items-center justify-between gap-2.5">
            
            <!-- Logo Section - Size Boro Kora Hoyeche -->
            <a href="dashboard.php" class="flex items-center gap-2.5 shrink-0">
                <img src="/assets/logo.jpg" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'" alt="Logo" class="w-14 h-14 sm:w-16 sm:h-16 rounded-full object-cover border-2 border-green-600 p-0.5 shadow-sm">
                <div>
                    <div class="flex items-center gap-1 leading-none">
                        <span class="text-2xl sm:text-3xl font-black text-green-700 tracking-tight">সকল</span>
                        <span class="text-2xl sm:text-3xl font-black text-red-600 tracking-tight">সেবা</span>
                    </div>
                    <p class="text-[11px] sm:text-xs font-bold text-slate-500 mt-1">সব কাজ, এক প্ল্যাটফর্মে</p>
                </div>
            </a>

            <!-- Right Wallet, Bell, Menu -->
            <div class="flex items-center gap-2">
                <button onclick="location.href='recharge.php'" class="flex items-center gap-1.5 bg-[#059669] hover:bg-emerald-700 text-white px-3 py-1.5 rounded-xl text-xs font-bold shadow-sm transition">
                    <i class="fa-solid fa-wallet text-sm"></i>
                    <div class="text-left leading-tight">
                        <span class="text-[9px] opacity-90 block">ওয়ালেট</span>
                        <span class="text-xs font-black">৳ <?php echo htmlspecialchars($balance); ?></span>
                    </div>
                </button>

                <button onclick="Swal.fire('জরুরী নোটিশ', 'সকল কাজ চালু আছে, অযথা এডমিনকে ফোন করে কেউ বিরক্ত করবেন না। ধন্যবাদ সবাইকে', 'info')" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 flex items-center justify-center relative border border-slate-200 transition">
                    <i class="fa-regular fa-bell text-base text-slate-600"></i>
                    <span class="w-2.5 h-2.5 bg-red-500 rounded-full absolute top-2 right-2 border border-white"></span>
                </button>

                <button id="openDrawerBtn" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-800 flex items-center justify-center border border-slate-200 transition">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="max-w-6xl mx-auto px-3 sm:px-5 py-3.5 space-y-3.5">

        <!-- 2. Search Bar -->
        <div class="bg-white p-1 rounded-full shadow-sm border border-slate-200 flex items-center">
            <div class="pl-3.5 pr-2 text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs sm:text-sm"></i>
            </div>
            <input type="text" id="serviceSearchInput" placeholder="যে কোন সার্ভিস সার্চ করুন..." class="w-full py-1 text-xs sm:text-sm focus:outline-none text-slate-700 bg-transparent">
            <button class="bg-[#156839] hover:bg-green-800 text-white font-bold text-xs sm:text-sm px-5 sm:px-6 py-1.5 sm:py-2 rounded-full transition shrink-0 shadow-sm">
                খুঁজুন
            </button>
        </div>

        <!-- 3. Slider Banner -->
        <div>
            <div class="swiper sokol-slider-box">
                <div class="swiper-wrapper">
                    <?php foreach ($slider_images as $idx => $img_url): ?>
                        <div class="swiper-slide cursor-pointer" onclick="location.href='order_file.php'">
                            <img src="<?php echo htmlspecialchars($img_url); ?>" alt="Banner">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="slider-dots flex justify-center items-center mt-2"></div>
        </div>

        <!-- 4. Title Header -->
        <div class="flex items-center justify-between pt-0.5">
            <div class="flex items-center gap-1.5">
                <div class="grid grid-cols-2 gap-0.5 text-green-700 text-xs">
                    <i class="fa-solid fa-square"></i><i class="fa-solid fa-square"></i>
                    <i class="fa-solid fa-square"></i><i class="fa-solid fa-circle text-emerald-500"></i>
                </div>
                <h3 class="text-base sm:text-lg font-extrabold text-slate-800">সকল সেবা বিভাগ</h3>
            </div>
            <a href="order_file.php" class="text-xs font-bold text-green-700 hover:underline flex items-center gap-1">
                সব বিভাগ দেখুন <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <!-- 5. 2 Columns in Mobile Grid (grid-cols-2 md:grid-cols-3) -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2.5 sm:gap-3.5" id="servicesContainer">
            
            <!-- 1. NID Seba -->
            <div class="category-box">
                <div>
                    <div class="category-header bg-[#107C41]">
                        <span class="flex items-center gap-1.5 truncate">
                            <i class="fa-solid fa-id-card text-xs"></i> NID সেবা
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] opacity-80 shrink-0"></i>
                    </div>
                    <ul class="category-list space-y-1">
                        <li onclick="location.href='sign_order.php?type=10_12_17'" class="hover:text-green-700 cursor-pointer truncate">• সাইন কপি</li>
                        <li onclick="location.href='auto_service.php?type=nid'" class="hover:text-green-700 cursor-pointer truncate">• আইডি কার্ড</li>
                        <li onclick="location.href='serverCopy.php'" class="hover:text-green-700 cursor-pointer truncate">• সার্ভার কপি ১</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-green-700 cursor-pointer truncate">• স্মার্ট কার্ড পিডিএফ</li>
                        <li onclick="location.href='free_service.php?type=make'" class="hover:text-green-700 cursor-pointer truncate">• অটো NID মেক</li>
                    </ul>
                </div>
                <div class="px-2.5 py-1.5 border-t border-slate-100">
                    <a href="order_file.php" class="text-[11px] font-bold text-[#107C41] hover:underline">আরও দেখুন...</a>
                </div>
            </div>

            <!-- 2. Passport Seba -->
            <div class="category-box">
                <div>
                    <div class="category-header bg-[#0078D4]">
                        <span class="flex items-center gap-1.5 truncate">
                            <i class="fa-solid fa-passport text-xs"></i> পাসপোর্ট সেবা
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] opacity-80 shrink-0"></i>
                    </div>
                    <ul class="category-list space-y-1">
                        <li onclick="location.href='order_file.php'" class="hover:text-blue-700 cursor-pointer truncate">• পাসপোর্ট স্লিপ PDF</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-blue-700 cursor-pointer truncate">• প্রথম পাতা PDF</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-blue-700 cursor-pointer truncate">• NID দিয়ে পাসপোর্ট</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-blue-700 cursor-pointer truncate">• ভিসা যাচাই</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-blue-700 cursor-pointer truncate">• পুলিশ ক্লিয়ারেন্স</li>
                    </ul>
                </div>
                <div class="px-2.5 py-1.5 border-t border-slate-100">
                    <a href="order_file.php" class="text-[11px] font-bold text-[#0078D4] hover:underline">আরও দেখুন...</a>
                </div>
            </div>

            <!-- 3. Jonmo Nibondhon Seba -->
            <div class="category-box">
                <div>
                    <div class="category-header bg-[#7B1FA2]">
                        <span class="flex items-center gap-1.5 truncate">
                            <i class="fa-solid fa-file-lines text-xs"></i> জন্ম নিবন্ধন সেবা
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] opacity-80 shrink-0"></i>
                    </div>
                    <ul class="category-list space-y-1">
                        <li onclick="location.href='order_file.php'" class="hover:text-purple-700 cursor-pointer truncate">• জন্ম নিবন্ধন পিডিএফ</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-purple-700 cursor-pointer truncate">• ক্লোন কপি</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-purple-700 cursor-pointer truncate">• নতুন জন্ম নিবন্ধন</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-purple-700 cursor-pointer truncate">• হারানো উত্তোলন</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-purple-700 cursor-pointer truncate">• Server Copy</li>
                    </ul>
                </div>
                <div class="px-2.5 py-1.5 border-t border-slate-100">
                    <a href="order_file.php" class="text-[11px] font-bold text-[#7B1FA2] hover:underline">আরও দেখুন...</a>
                </div>
            </div>

            <!-- 4. bKash Nagad Rocket -->
            <div class="category-box">
                <div>
                    <div class="category-header bg-[#D81B60]">
                        <span class="flex items-center gap-1.5 truncate">
                            <i class="fa-solid fa-mobile-retro text-xs"></i> বিকাশ-নগদ-রকেট
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] opacity-80 shrink-0"></i>
                    </div>
                    <ul class="category-list space-y-1">
                        <li onclick="location.href='mfs_service.php'" class="hover:text-pink-600 cursor-pointer truncate">• বিকাশ ইনফো</li>
                        <li onclick="location.href='mfs_service.php'" class="hover:text-pink-600 cursor-pointer truncate">• নগদ ইনফো</li>
                        <li onclick="location.href='mfs_service.php'" class="hover:text-pink-600 cursor-pointer truncate">• রকেট ইনফো</li>
                        <li onclick="location.href='mfs_service.php'" class="hover:text-pink-600 cursor-pointer truncate">• উপায় ইনফো</li>
                        <li onclick="location.href='mfs_service.php'" class="hover:text-pink-600 cursor-pointer truncate">• স্টেটমেন্ট তথ্য</li>
                    </ul>
                </div>
                <div class="px-2.5 py-1.5 border-t border-slate-100">
                    <a href="mfs_service.php" class="text-[11px] font-bold text-[#D81B60] hover:underline">আরও দেখুন...</a>
                </div>
            </div>

            <!-- 5. Sim Biometric -->
            <div class="category-box">
                <div>
                    <div class="category-header bg-[#E65100]">
                        <span class="flex items-center gap-1.5 truncate">
                            <i class="fa-solid fa-sim-card text-xs"></i> সিম বায়োমেট্রিক
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] opacity-80 shrink-0"></i>
                    </div>
                    <ul class="category-list space-y-1">
                        <li onclick="location.href='order_file.php'" class="hover:text-orange-600 cursor-pointer truncate">• NID থেকে সিম বাতিল</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-orange-600 cursor-pointer truncate">• সকল সিম বাতিল</li>
                        <li onclick="location.href='numbertobio.php'" class="hover:text-orange-600 cursor-pointer truncate">• রবি বায়োমেট্রিক</li>
                        <li onclick="location.href='numbertobio.php'" class="hover:text-orange-600 cursor-pointer truncate">• এয়ারটেল বায়োমেট্রিক</li>
                        <li onclick="location.href='numbertobio.php'" class="hover:text-orange-600 cursor-pointer truncate">• গ্রামীণ বায়োমেট্রিক</li>
                    </ul>
                </div>
                <div class="px-2.5 py-1.5 border-t border-slate-100">
                    <a href="numbertobio.php" class="text-[11px] font-bold text-[#E65100] hover:underline">আরও দেখুন...</a>
                </div>
            </div>

            <!-- 6. Location Track -->
            <div class="category-box">
                <div>
                    <div class="category-header bg-[#00897B]">
                        <span class="flex items-center gap-1.5 truncate">
                            <i class="fa-solid fa-location-dot text-xs"></i> লোকেশন ট্র্যাক
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] opacity-80 shrink-0"></i>
                    </div>
                    <ul class="category-list space-y-1">
                        <li onclick="location.href='location_service.php'" class="hover:text-teal-700 cursor-pointer truncate">• সকল সিমের লোকেশন</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-teal-700 cursor-pointer truncate">• এনআইডি টু নাম্বার</li>
                        <li onclick="location.href='location_service.php'" class="hover:text-teal-700 cursor-pointer truncate">• পিন পয়েন্ট লোকেশন</li>
                        <li onclick="location.href='location_service.php'" class="hover:text-teal-700 cursor-pointer truncate">• জিও লোকেশন</li>
                        <li onclick="location.href='imei_service.php'" class="hover:text-teal-700 cursor-pointer truncate">• IMEI টু নাম্বার</li>
                    </ul>
                </div>
                <div class="px-2.5 py-1.5 border-t border-slate-100">
                    <a href="location_service.php" class="text-[11px] font-bold text-[#00897B] hover:underline">আরও দেখুন...</a>
                </div>
            </div>

            <!-- 7. TIN Certificate -->
            <div class="category-box">
                <div>
                    <div class="category-header bg-[#F57F17]">
                        <span class="flex items-center gap-1.5 truncate">
                            <i class="fa-solid fa-file-invoice text-xs"></i> টিন সার্টিফিকেট
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] opacity-80 shrink-0"></i>
                    </div>
                    <ul class="category-list space-y-1">
                        <li onclick="location.href='auto_tin.php'" class="hover:text-amber-700 cursor-pointer truncate">• নতুন টিন আবেদন</li>
                        <li onclick="location.href='order_text.php'" class="hover:text-amber-700 cursor-pointer truncate">• হারানো সার্টিফিকেট</li>
                        <li onclick="location.href='order_text.php'" class="hover:text-amber-700 cursor-pointer truncate">• নিজ নাম সংশোধন</li>
                        <li onclick="location.href='order_text.php'" class="hover:text-amber-700 cursor-pointer truncate">• অল নাম সংশোধন</li>
                        <li onclick="location.href='auto_tin.php'" class="hover:text-amber-700 cursor-pointer truncate">• অটো টিন জেনারেট</li>
                    </ul>
                </div>
                <div class="px-2.5 py-1.5 border-t border-slate-100">
                    <a href="auto_tin.php" class="text-[11px] font-bold text-[#F57F17] hover:underline">আরও দেখুন...</a>
                </div>
            </div>

            <!-- 8. Trade License -->
            <div class="category-box">
                <div>
                    <div class="category-header bg-[#5E35B1]">
                        <span class="flex items-center gap-1.5 truncate">
                            <i class="fa-solid fa-store text-xs"></i> ট্রেড লাইসেন্স
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] opacity-80 shrink-0"></i>
                    </div>
                    <ul class="category-list space-y-1">
                        <li onclick="location.href='order_file.php'" class="hover:text-purple-700 cursor-pointer truncate">• লাইসেন্স আবেদন</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-purple-700 cursor-pointer truncate">• হারানো উত্তোলন</li>
                        <li onclick="location.href='order_text.php'" class="hover:text-purple-700 cursor-pointer truncate">• জিরো রিটার্ন</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-purple-700 cursor-pointer truncate">• নবায়ন আবেদন</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-purple-700 cursor-pointer truncate">• লাইসেন্স সংশোধন</li>
                    </ul>
                </div>
                <div class="px-2.5 py-1.5 border-t border-slate-100">
                    <a href="order_file.php" class="text-[11px] font-bold text-[#5E35B1] hover:underline">আরও দেখুন...</a>
                </div>
            </div>

            <!-- 9. Bhumi Seba -->
            <div class="category-box">
                <div>
                    <div class="category-header bg-[#0288D1]">
                        <span class="flex items-center gap-1.5 truncate">
                            <i class="fa-solid fa-mountain-sun text-xs"></i> ভূমি সেবা
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] opacity-80 shrink-0"></i>
                    </div>
                    <ul class="category-list space-y-1">
                        <li onclick="location.href='order_file.php'" class="hover:text-sky-700 cursor-pointer truncate">• মৌজা ম্যাপ</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-sky-700 cursor-pointer truncate">• খাজনা অনুমোদন</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-sky-700 cursor-pointer truncate">• সার্ভে খতিয়ান কপি</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-sky-700 cursor-pointer truncate">• নামজারি খতিয়ান কপি</li>
                        <li onclick="location.href='order_file.php'" class="hover:text-sky-700 cursor-pointer truncate">• খাজনা ডিসকাউন্ট</li>
                    </ul>
                </div>
                <div class="px-2.5 py-1.5 border-t border-slate-100">
                    <a href="order_file.php" class="text-[11px] font-bold text-[#0288D1] hover:underline">আরও দেখুন...</a>
                </div>
            </div>

        </div>

        <!-- 6. All Application Services Banner -->
        <div class="rounded-2xl bg-gradient-to-r from-[#D81B60] via-[#E91E63] to-[#C2185B] text-white p-3 shadow-sm flex items-center justify-between gap-2.5 cursor-pointer" onclick="location.href='order_file.php'">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-white text-rose-600 flex items-center justify-center text-lg shrink-0 shadow-sm">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div class="min-w-0">
                    <h4 class="text-xs sm:text-sm font-black truncate">সকল প্রকার আবেদন সেবা</h4>
                    <p class="text-[10px] sm:text-[11px] text-pink-100 truncate">নতুন NID | পাসপোর্ট | জন্ম নিবন্ধন | বিদ্যুৎ মিটার</p>
                </div>
            </div>
            <span class="bg-[#FBC02D] text-slate-900 font-extrabold text-[10px] sm:text-[11px] px-2.5 sm:px-3 py-1 rounded-full shrink-0 shadow-sm">
                বিস্তারিত দেখুন →
            </span>
        </div>

        <!-- 7. Helpline / WhatsApp Strip -->
        <div class="rounded-2xl bg-[#0F5132] text-white p-3 shadow-sm flex items-center justify-between gap-2.5">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-full bg-white text-green-700 flex items-center justify-center text-2xl shrink-0 shadow-sm">
                    <i class="fa-brands fa-whatsapp"></i>
                </div>
                <div>
                    <span class="text-[10px] text-green-200 block">যেকোনো প্রয়োজনে WhatsApp করুন</span>
                    <h4 class="text-sm sm:text-base font-black text-white"><?php echo htmlspecialchars($support_num); ?></h4>
                </div>
            </div>
            <a href="<?php echo htmlspecialchars($whatsapp_link); ?>" target="_blank" class="bg-[#0D6EFD] hover:bg-blue-600 text-white font-extrabold text-xs px-3.5 py-1.5 rounded-xl shrink-0 transition flex items-center gap-1 shadow">
                <i class="fa-solid fa-headset"></i> আমরা আছি
            </a>
        </div>

        <!-- 8. Bottom Notice Marquee -->
        <div class="bg-white border border-slate-200 rounded-xl px-3 py-2 flex items-center gap-2 text-xs font-bold text-slate-700 shadow-sm">
            <i class="fa-solid fa-bullhorn text-green-700 text-xs shrink-0"></i>
            <span class="truncate text-[11px] sm:text-xs">বিঃদ্রঃ- সকল কাজ চালু আছে, অযথা এডমিনকে ফোন করে কেউ বিরক্ত করবেন না। ধন্যবাদ সবাইকে।</span>
        </div>

    </main>

    <!-- 9. Bottom 5-Tab Bar (Exact Match) -->
    <nav class="fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 py-1.5 px-3 flex items-center justify-around z-50 shadow-[0_-4px_15px_rgba(0,0,0,0.04)]">
        <a href="dashboard.php" class="flex flex-col items-center gap-0.5 text-[#107C41] text-[10.5px] font-extrabold">
            <i class="fa-solid fa-house text-base sm:text-lg"></i>
            <span>হোম</span>
        </a>
        <a href="services.php" class="flex flex-col items-center gap-0.5 text-slate-500 hover:text-green-700 text-[10.5px] font-semibold">
            <i class="fa-solid fa-table-cells-large text-base sm:text-lg"></i>
            <span>সেবা</span>
        </a>
        <a href="recharge.php" class="flex flex-col items-center gap-0.5 text-slate-500 hover:text-green-700 text-[10.5px] font-semibold">
            <i class="fa-solid fa-wallet text-base sm:text-lg"></i>
            <span>ওয়ালেট</span>
        </a>
        <a href="work_history.php" class="flex flex-col items-center gap-0.5 text-slate-500 hover:text-green-700 text-[10.5px] font-semibold">
            <i class="fa-solid fa-file-invoice text-base sm:text-lg"></i>
            <span>অর্ডার</span>
        </a>
        <a href="profile.php" class="flex flex-col items-center gap-0.5 text-slate-500 hover:text-green-700 text-[10.5px] font-semibold">
            <i class="fa-regular fa-user text-base sm:text-lg"></i>
            <span>প্রোফাইল</span>
        </a>
    </nav>

    <!-- Side Drawer Menu -->
    <div class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm z-50 hidden" id="drawerOverlay"></div>
    <div class="fixed top-0 right-[-100%] w-72 h-full bg-white shadow-2xl z-50 transition-all duration-300 flex flex-col" id="drawerMenu">
        <div class="p-4 bg-[#107C41] text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-layer-group text-emerald-200"></i>
                <h5 class="font-bold text-sm">সকল সেবা মেনু</h5>
            </div>
            <button id="closeDrawerBtn" class="w-8 h-8 rounded-lg bg-white/10 text-white flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
<!-- হাফ-উইডথ সাইড ড্রয়ার মেনু (স্ক্রিনের অর্ধেক মাপ) -->
    <div class="fixed inset-0 bg-slate-950/40 backdrop-blur-[2px] z-50 hidden transition-opacity" id="drawerOverlay"></div>
    <div class="fixed top-0 right-[-100%] w-[58%] sm:w-56 h-full bg-white shadow-2xl z-50 transition-all duration-300 flex flex-col border-l border-slate-200" id="drawerMenu">
        
        <!-- ড্রয়ার হেডার -->
        <div class="px-3.5 py-3 bg-[#107C41] text-white flex items-center justify-between">
            <div class="flex items-center gap-1.5 min-w-0">
                <i class="fa-solid fa-layer-group text-emerald-200 text-xs"></i>
                <h5 class="font-extrabold text-xs truncate">সকল সেবা মেনু</h5>
            </div>
            <button id="closeDrawerBtn" class="w-6 h-6 rounded-md bg-white/15 hover:bg-white/25 text-white flex items-center justify-center text-xs transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- ড্রয়ার আইটেম তালিকা (কমপ্যাক্ট ও সাইজ হাফ) -->
        <div class="flex-1 overflow-y-auto p-2 space-y-0.5 text-[11.5px] font-bold">
            <a href="dashboard.php" class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-green-50 text-green-800">
                <i class="fa-solid fa-house w-4 text-green-700 text-xs"></i>
                <span class="truncate">হোম ড্যাশবোর্ড</span>
            </a>
            <a href="serverCopy.php" class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-slate-700 hover:bg-slate-50 transition">
                <i class="fa-solid fa-server w-4 text-purple-600 text-xs"></i>
                <span class="truncate">অটো সার্ভার কপি</span>
            </a>
            <a href="sign_order.php?type=10_12_17" class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-slate-700 hover:bg-slate-50 transition">
                <i class="fa-solid fa-file-signature w-4 text-indigo-600 text-xs"></i>
                <span class="truncate">সাইন কপি অর্ডার</span>
            </a>
            <a href="auto_tin.php" class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-slate-700 hover:bg-slate-50 transition">
                <i class="fa-solid fa-file-invoice w-4 text-amber-600 text-xs"></i>
                <span class="truncate">অটো টিন সার্টিফিকেট</span>
            </a>
            <a href="numbertobio.php" class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-slate-700 hover:bg-slate-50 transition">
                <i class="fa-solid fa-sim-card w-4 text-orange-600 text-xs"></i>
                <span class="truncate">সিম বায়োমেট্রিক</span>
            </a>
            <a href="location_service.php" class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-slate-700 hover:bg-slate-50 transition">
                <i class="fa-solid fa-location-dot w-4 text-teal-600 text-xs"></i>
                <span class="truncate">লোকেশন ট্র্যাকিং</span>
            </a>
            <a href="mfs_service.php" class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-slate-700 hover:bg-slate-50 transition">
                <i class="fa-solid fa-wallet w-4 text-rose-600 text-xs"></i>
                <span class="truncate">বিকাশ-নগদ-রকেট</span>
            </a>
            <a href="order_file.php" class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-slate-700 hover:bg-slate-50 transition">
                <i class="fa-solid fa-file-arrow-up w-4 text-emerald-600 text-xs"></i>
                <span class="truncate">কাস্টম ও ফাইল অর্ডার</span>
            </a>

            <div class="my-1 border-t border-slate-100"></div>

            <a href="recharge.php" class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-slate-700 hover:bg-slate-50 transition">
                <i class="fa-solid fa-circle-plus w-4 text-green-600 text-xs"></i>
                <span class="truncate">ব্যালেন্স রিচার্জ</span>
            </a>
            <a href="work_history.php" class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-slate-700 hover:bg-slate-50 transition">
                <i class="fa-solid fa-clock-rotate-left w-4 text-violet-600 text-xs"></i>
                <span class="truncate">অর্ডার হিস্টোরি</span>
            </a>
            <a href="profile.php" class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-slate-700 hover:bg-slate-50 transition">
                <i class="fa-solid fa-user-gear w-4 text-slate-600 text-xs"></i>
                <span class="truncate">আমার প্রোফাইল</span>
            </a>
            <a href="logout.php" class="flex items-center gap-2 px-2.5 py-2 rounded-lg text-red-600 hover:bg-red-50 transition">
                <i class="fa-solid fa-arrow-right-from-bracket w-4 text-xs"></i>
                <span class="truncate">লগআউট</span>
            </a>
        </div>
    </div>

    <!-- Swiper JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        var swiper = new Swiper(".sokol-slider-box", {
            loop: true,
            autoplay: { delay: 3500, disableOnInteraction: false },
            pagination: { el: ".slider-dots", clickable: true },
            speed: 600,
        });

        $('#openDrawerBtn').click(function() {
            $('#drawerOverlay').fadeIn(200);
            $('#drawerMenu').css('right', '0');
        });
        $('#closeDrawerBtn, #drawerOverlay').click(function() {
            $('#drawerOverlay').fadeOut(200);
            $('#drawerMenu').css('right', '-100%');
        });

        $('#serviceSearchInput').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $("#servicesContainer .category-box").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>