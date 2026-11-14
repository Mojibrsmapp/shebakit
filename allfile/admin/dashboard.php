<?php include_once("header.php"); ?>

<div class="space-y-6">

    <!-- Top Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm relative overflow-hidden group hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 block">মোট ব্যবহারকারী</span>
                    <h3 class="text-2xl font-black text-slate-800 mt-1" id="totalUsers">0</h3>
                    <span class="text-[11px] text-slate-500 font-semibold mt-0.5 block">সর্বমোট নিবন্ধিত একাউন্ট</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl group-hover:scale-110 transition">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 to-indigo-300"></div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm relative overflow-hidden group hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 block">ইউজার মোট ব্যালেন্স</span>
                    <h3 class="text-2xl font-black text-emerald-600 mt-1">৳ <span id="totalBalance">0.00</span></h3>
                    <span class="text-[11px] text-slate-500 font-semibold mt-0.5 block">সকল ইউজারের কারেন্ট ফান্ড</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl group-hover:scale-110 transition">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 to-emerald-300"></div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm relative overflow-hidden group hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 block">শূন্য ব্যালেন্স ইউজার</span>
                    <h3 class="text-2xl font-black text-rose-600 mt-1" id="zeroBalanceUsers">0</h3>
                    <span class="text-[11px] text-slate-500 font-semibold mt-0.5 block">ব্যালেন্সহীন একাউন্ট সংখ্যা</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl group-hover:scale-110 transition">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-rose-500 to-rose-300"></div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm relative overflow-hidden group hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 block">পেন্ডিং রিচার্জ রিকোয়েস্ট</span>
                    <h3 class="text-2xl font-black text-amber-500 mt-1"><?php echo isset($pending_deposits) ?$pending_deposits : 0; ?> টি</h3>
                    <span class="text-[11px] text-slate-500 font-semibold mt-0.5 block">অপেক্ষমান ডিপোজিট</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl group-hover:scale-110 transition">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 to-amber-300"></div>
        </div>

    </div>

    <!-- Quick Actions & Control Shortcuts -->
    <div class="bg-white p-5 md:p-6 rounded-3xl border border-slate-200/90 shadow-sm">
        
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <div>
                    <h4 class="text-sm md:text-base font-extrabold text-slate-800">কুইক একশন ও সার্ভিস শর্টকাট</h4>
                    <p class="text-[11px] text-slate-400 font-medium">প্রয়োজনীয় কন্ট্রোল প্যানেলে দ্রুত প্রবেশ করুন</p>
                </div>
            </div>
            <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full border border-indigo-100 hidden sm:inline-block">Master Control</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            
            <a href="manage_services.php" class="bg-slate-50 hover:bg-rose-50/60 border border-slate-200 hover:border-rose-300 rounded-2xl p-3.5 flex flex-col items-center justify-center gap-2 text-center transition group no-underline">
                <div class="w-10 h-10 rounded-xl bg-white text-rose-500 flex items-center justify-center text-base shadow-sm group-hover:scale-110 transition">
                    <i class="fa-solid fa-power-off"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 group-hover:text-rose-600">সার্ভিস অন/অফ</span>
                <span class="text-[9.5px] font-bold bg-white text-rose-600 border px-2 py-0.5 rounded-md">Switch</span>
            </a>

            <a href="all_pricing.php" class="bg-slate-50 hover:bg-amber-50/60 border border-slate-200 hover:border-amber-300 rounded-2xl p-3.5 flex flex-col items-center justify-center gap-2 text-center transition group no-underline">
                <div class="w-10 h-10 rounded-xl bg-white text-amber-500 flex items-center justify-center text-base shadow-sm group-hover:scale-110 transition">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 group-hover:text-amber-600">মাস্টার প্রাইসিং</span>
                <span class="text-[9.5px] font-bold bg-white text-amber-600 border px-2 py-0.5 rounded-md">Rates</span>
            </a>

            <a href="all_orders_history.php" class="bg-slate-50 hover:bg-sky-50/60 border border-slate-200 hover:border-sky-300 rounded-2xl p-3.5 flex flex-col items-center justify-center gap-2 text-center transition group no-underline">
                <div class="w-10 h-10 rounded-xl bg-white text-sky-600 flex items-center justify-center text-base shadow-sm group-hover:scale-110 transition">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 group-hover:text-sky-600">সকল অর্ডার ও লগ</span>
                <span class="text-[9.5px] font-bold bg-white text-sky-600 border px-2 py-0.5 rounded-md">Orders</span>
            </a>

            <a href="manage_deposits.php" class="bg-slate-50 hover:bg-amber-50/60 border border-slate-200 hover:border-amber-300 rounded-2xl p-3.5 flex flex-col items-center justify-center gap-2 text-center transition group no-underline">
                <div class="w-10 h-10 rounded-xl bg-white text-amber-600 flex items-center justify-center text-base shadow-sm group-hover:scale-110 transition">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 group-hover:text-amber-600">পেন্ডিং রিচার্জ</span>
                <span class="text-[9.5px] font-bold bg-white text-amber-600 border px-2 py-0.5 rounded-md">Deposit</span>
            </a>

            <a href="work_history.php" class="bg-slate-50 hover:bg-violet-50/60 border border-slate-200 hover:border-violet-300 rounded-2xl p-3.5 flex flex-col items-center justify-center gap-2 text-center transition group no-underline">
                <div class="w-10 h-10 rounded-xl bg-white text-violet-600 flex items-center justify-center text-base shadow-sm group-hover:scale-110 transition">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 group-hover:text-violet-600">কাজের হিস্টোরি</span>
                <span class="text-[9.5px] font-bold bg-white text-violet-600 border px-2 py-0.5 rounded-md">History</span>
            </a>

            <a href="add_service.php" class="bg-slate-50 hover:bg-emerald-50/60 border border-slate-200 hover:border-emerald-300 rounded-2xl p-3.5 flex flex-col items-center justify-center gap-2 text-center transition group no-underline">
                <div class="w-10 h-10 rounded-xl bg-white text-emerald-600 flex items-center justify-center text-base shadow-sm group-hover:scale-110 transition">
                    <i class="fa-solid fa-circle-plus"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 group-hover:text-emerald-600">নতুন সার্ভিস যোগ</span>
                <span class="text-[9.5px] font-bold bg-white text-emerald-600 border px-2 py-0.5 rounded-md">New</span>
            </a>

            <a href="user_list.php" class="bg-slate-50 hover:bg-teal-50/60 border border-slate-200 hover:border-teal-300 rounded-2xl p-3.5 flex flex-col items-center justify-center gap-2 text-center transition group no-underline">
                <div class="w-10 h-10 rounded-xl bg-white text-teal-600 flex items-center justify-center text-base shadow-sm group-hover:scale-110 transition">
                    <i class="fa-solid fa-users-gear"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 group-hover:text-teal-600">ইউজার তালিকা</span>
                <span class="text-[9.5px] font-bold bg-white text-teal-600 border px-2 py-0.5 rounded-md">Users</span>
            </a>

            <a href="balance_add_remove.php" class="bg-slate-50 hover:bg-indigo-50/60 border border-slate-200 hover:border-indigo-300 rounded-2xl p-3.5 flex flex-col items-center justify-center gap-2 text-center transition group no-underline">
                <div class="w-10 h-10 rounded-xl bg-white text-indigo-600 flex items-center justify-center text-base shadow-sm group-hover:scale-110 transition">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 group-hover:text-indigo-600">ব্যালেন্স যোগ/বাদ</span>
                <span class="text-[9.5px] font-bold bg-white text-indigo-600 border px-2 py-0.5 rounded-md">Fund</span>
            </a>

            <a href="createLink.php" class="bg-slate-50 hover:bg-cyan-50/60 border border-slate-200 hover:border-cyan-300 rounded-2xl p-3.5 flex flex-col items-center justify-center gap-2 text-center transition group no-underline">
                <div class="w-10 h-10 rounded-xl bg-white text-cyan-600 flex items-center justify-center text-base shadow-sm group-hover:scale-110 transition">
                    <i class="fa-solid fa-link"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 group-hover:text-cyan-600">রেজিস্ট্রেশন লিংক</span>
                <span class="text-[9.5px] font-bold bg-white text-cyan-600 border px-2 py-0.5 rounded-md">Invite</span>
            </a>

            <a href="update_marquee.php" class="bg-slate-50 hover:bg-pink-50/60 border border-slate-200 hover:border-pink-300 rounded-2xl p-3.5 flex flex-col items-center justify-center gap-2 text-center transition group no-underline">
                <div class="w-10 h-10 rounded-xl bg-white text-pink-600 flex items-center justify-center text-base shadow-sm group-hover:scale-110 transition">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 group-hover:text-pink-600">নোটিশ আপডেট</span>
                <span class="text-[9.5px] font-bold bg-white text-pink-600 border px-2 py-0.5 rounded-md">Notice</span>
            </a>

            <a href="profile.php" class="bg-slate-50 hover:bg-purple-50/60 border border-slate-200 hover:border-purple-300 rounded-2xl p-3.5 flex flex-col items-center justify-center gap-2 text-center transition group no-underline">
                <div class="w-10 h-10 rounded-xl bg-white text-purple-600 flex items-center justify-center text-base shadow-sm group-hover:scale-110 transition">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <span class="text-xs font-bold text-slate-800 group-hover:text-purple-600">এডমিন প্রোফাইল</span>
                <span class="text-[9.5px] font-bold bg-white text-purple-600 border px-2 py-0.5 rounded-md">Profile</span>
            </a>

            <button onclick="location.reload()" class="bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-2xl p-3.5 flex flex-col items-center justify-center gap-2 text-center transition group">
                <div class="w-10 h-10 rounded-xl bg-white text-slate-600 flex items-center justify-center text-base shadow-sm group-hover:rotate-180 transition duration-300">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </div>
                <span class="text-xs font-bold text-slate-800">রিলোড ড্যাশবোর্ড</span>
                <span class="text-[9.5px] font-bold bg-white text-slate-600 border px-2 py-0.5 rounded-md">Sync</span>
            </button>

        </div>

    </div>

    <!-- Analytics & Zero Balance Users Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Zero Balance Users Column (Left 4 cols) -->
        <div class="lg:col-span-4 bg-white p-5 rounded-3xl border border-slate-200/90 shadow-sm flex flex-col">
            
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-sm"></i>
                    <h5 class="font-extrabold text-sm text-slate-800">শূন্য ব্যালেন্স ইউজার</h5>
                </div>
                <span class="text-xs font-black bg-rose-50 text-rose-600 border border-rose-200 px-2.5 py-0.5 rounded-full" id="zeroBalanceCount">0</span>
            </div>

            <div id="zeroBalanceList" class="flex-1 overflow-y-auto space-y-2 max-h-[500px] pr-1">
                <div class="text-center py-8 text-slate-400 text-xs font-medium">লোড হচ্ছে...</div>
            </div>

        </div>

        <!-- Date Analytics Column (Right 8 cols) -->
        <div class="lg:col-span-8 bg-white p-5 md:p-6 rounded-3xl border border-slate-200/90 shadow-sm space-y-5">
            
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h5 class="font-extrabold text-sm md:text-base text-slate-800" id="selectedDateTitle">তারিখ অনুযায়ী কাজের হিসাব</h5>
                </div>
                <div>
                    <input type="date" id="start_date" name="start_date" class="border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 bg-slate-50 outline-none focus:border-indigo-500 transition">
                </div>
            </div>

            <!-- Table 1: Work Summary -->
            <div>
                <h6 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">কাজের সামগ্রিক সারাংশ</h6>
                <div class="overflow-x-auto rounded-2xl border border-slate-100">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10.5px]">
                            <tr>
                                <th class="p-3">#</th>
                                <th class="p-3">কাজের ধরন</th>
                                <th class="p-3 text-center">সংখ্যা</th>
                                <th class="p-3 text-right">মোট চার্জ</th>
                            </tr>
                        </thead>
                        <tbody id="historyWorkSummary" class="divide-y divide-slate-100 text-slate-700">
                            <tr><td colspan="4" class="p-4 text-center text-slate-400">ডাটা লোড হচ্ছে...</td></tr>
                        </tbody>
                        <tfoot class="bg-slate-50 font-black text-slate-800 border-t border-slate-200">
                            <tr>
                                <th colspan="2" class="p-3">সর্বমোট</th>
                                <th class="p-3 text-center" id="totalHistoryOrders">0 টি</th>
                                <th class="p-3 text-right text-emerald-600">৳ <span id="totalHistoryPrice">0.00</span></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Table 2: Status Breakdown -->
            <div>
                <h6 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">সার্ভিস অর্ডার স্ট্যাটাস ও কাউন্ট</h6>
                <div class="overflow-x-auto rounded-2xl border border-slate-100">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10.5px]">
                            <tr>
                                <th class="p-3">#</th>
                                <th class="p-3">সার্ভিস নাম</th>
                                <th class="p-3 text-center text-amber-600">পেন্ডিং</th>
                                <th class="p-3 text-center text-emerald-600">সফল</th>
                                <th class="p-3 text-center text-rose-600">অন্যান্য</th>
                            </tr>
                        </thead>
                        <tbody id="orderListCountsSummary" class="divide-y divide-slate-100 text-slate-700">
                            <tr><td colspan="5" class="p-4 text-center text-slate-400">ডাটা লোড হচ্ছে...</td></tr>
                        </tbody>
                        <tfoot class="bg-slate-50 font-black text-slate-800 border-t border-slate-200">
                            <tr>
                                <th colspan="2" class="p-3">মোট কাউন্ট</th>
                                <th class="p-3 text-center text-amber-600" id="totalPendingCount">0</th>
                                <th class="p-3 text-center text-emerald-600" id="totalSuccessCount">0</th>
                                <th class="p-3 text-center text-rose-600" id="totalOtherCount">0</th>
                            </tr>
                            <tr>
                                <th colspan="2" class="p-3">মোট চার্জ</th>
                                <th class="p-3 text-center text-amber-600">৳ <span id="totalPendingPrice">0.00</span></th>
                                <th class="p-3 text-center text-emerald-600">৳ <span id="totalSuccessPrice">0.00</span></th>
                                <th class="p-3 text-center text-rose-600">৳ <span id="totalOtherPrice">0.00</span></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>

    </div>

</div>

<?php include_once("footer.php"); ?>

<script>
    $(document).ready(function() {
        fetchUserData();
        fetchZeroBalanceUsers();

        let today = new Date().toISOString().split('T')[0];
        $('#start_date').val(today);
        fetchData(today);

        $('#start_date').change(function() {
            let startDate = $(this).val();
            fetchData(startDate);
        });
    });

    function fetchData(startDate) {
        let formattedDate = new Date(startDate).toLocaleDateString('bn-BD', {
            weekday: 'short', year: 'numeric', month: 'short', day: 'numeric'
        });
        $('#selectedDateTitle').text("কাজের হিসাব: " + formattedDate);

        $.ajax({
            url: 'order/list/fetch_total_order_summery.php',
            type: 'GET',
            data: { start_date: startDate },
            dataType: 'json',
            success: function(response) {
                let historyWorkSummaryHtml = '';
                let orderListCountsHtml = '';
                let serial = 1;

                let totalPendingCount = 0, totalSuccessCount = 0, totalOtherCount = 0;
                let totalPendingPrice = 0, totalSuccessPrice = 0, totalOtherPrice = 0;
                let totalHistoryOrders = 0, totalHistoryPrice = 0;

                if (response.historyWorkSummary && response.historyWorkSummary.length > 0) {
                    response.historyWorkSummary.forEach(order => {
                        historyWorkSummaryHtml += `
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="p-3 font-bold text-slate-400">${serial}</td>
                                <td class="p-3 font-bold text-slate-800">${order.order_type}</td>
                                <td class="p-3 text-center"><span class="bg-slate-100 text-slate-700 font-bold px-2.5 py-0.5 rounded-full text-[11px]">${order.totalOrders} টি</span></td>
                                <td class="p-3 text-right font-black text-slate-800">৳ ${parseFloat(order.totalPrice).toFixed(2)}</td>
                            </tr>
                        `;
                        totalHistoryOrders += parseInt(order.totalOrders) || 0;
                        totalHistoryPrice += parseFloat(order.totalPrice) || 0;
                        serial++;
                    });
                } else {
                    historyWorkSummaryHtml = '<tr><td colspan="4" class="p-4 text-center text-slate-400 text-xs">কোনো রেকর্ড পাওয়া যায়নি</td></tr>';
                }

                let orderTypeSerial = 1;
                if (response.orderListCounts) {
                    for (let orderType in response.orderListCounts) {
                        let order = response.orderListCounts[orderType];
                        orderListCountsHtml += `
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="p-3 font-bold text-slate-400">${orderTypeSerial}</td>
                                <td class="p-3 font-bold text-slate-800">${orderType}</td>
                                <td class="p-3 text-center"><span class="text-amber-600 font-bold">${order.pending_count}</span> <span class="text-[10px] text-slate-400">(৳${order.pending_price})</span></td>
                                <td class="p-3 text-center"><span class="text-emerald-600 font-bold">${order.success_count}</span> <span class="text-[10px] text-slate-400">(৳${order.success_price})</span></td>
                                <td class="p-3 text-center"><span class="text-rose-600 font-bold">${order.other_count}</span> <span class="text-[10px] text-slate-400">(৳${order.other_price})</span></td>
                            </tr>
                        `;
                        totalPendingCount += parseInt(order.pending_count) || 0;
                        totalSuccessCount += parseInt(order.success_count) || 0;
                        totalOtherCount += parseInt(order.other_count) || 0;

                        totalPendingPrice += parseFloat(order.pending_price) || 0;
                        totalSuccessPrice += parseFloat(order.success_price) || 0;
                        totalOtherPrice += parseFloat(order.other_price) || 0;

                        orderTypeSerial++;
                    }
                } else {
                    orderListCountsHtml = '<tr><td colspan="5" class="p-4 text-center text-slate-400 text-xs">কোনো অর্ডার পাওয়া যায়নি</td></tr>';
                }

                $('#historyWorkSummary').html(historyWorkSummaryHtml);
                $('#orderListCountsSummary').html(orderListCountsHtml);

                $('#totalHistoryOrders').text(totalHistoryOrders + ' টি');
                $('#totalHistoryPrice').text(totalHistoryPrice.toFixed(2));

                $('#totalPendingCount').text(totalPendingCount);
                $('#totalSuccessCount').text(totalSuccessCount);
                $('#totalOtherCount').text(totalOtherCount);

                $('#totalPendingPrice').text(totalPendingPrice.toFixed(2));
                $('#totalSuccessPrice').text(totalSuccessPrice.toFixed(2));
                $('#totalOtherPrice').text(totalOtherPrice.toFixed(2));
            },
            error: function() {
                $('#historyWorkSummary').html('<tr><td colspan="4" class="p-4 text-center text-rose-500 text-xs">ডাটা লোড করতে সমস্যা হয়েছে</td></tr>');
                $('#orderListCountsSummary').html('<tr><td colspan="5" class="p-4 text-center text-rose-500 text-xs">ডাটা লোড করতে সমস্যা হয়েছে</td></tr>');
            }
        });
    }

    function fetchUserData() {
        $.ajax({
            url: 'order/list/fetch_user_data.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#totalUsers').text(response.totalUsers || 0);
                $('#totalBalance').text(response.totalBalance || '0.00');
            },
            error: function() {
                $('#totalUsers').text("0");
                $('#totalBalance').text("0.00");
            }
        });
    }

    function fetchZeroBalanceUsers() {
        $.ajax({
            url: 'order/list/fetch_zero_balance_users.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                let zeroBalanceHtml = '';
                let zeroBalanceCount = 0;

                if (response.zeroBalanceUsers && response.zeroBalanceUsers.length > 0) {
                    zeroBalanceCount = response.zeroBalanceUsers.length;
                    response.zeroBalanceUsers.forEach((user) => {
                        zeroBalanceHtml += `
                            <div class="bg-slate-50 border border-slate-200/90 rounded-2xl p-3 flex items-center justify-between gap-2 hover:bg-slate-100 transition">
                                <div class="flex-1 truncate">
                                    <h6 class="font-bold text-xs text-slate-800 truncate">${user.name || 'অজানা ইউজার'}</h6>
                                    <p class="text-[10px] text-slate-400 font-mono truncate">#${user.id || ''} | ${user.phone || 'ফোন নেই'}</p>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="text-[11px] font-black text-rose-600 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-lg">৳0.00</span>
                                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-2.5 py-1 text-xs font-bold transition flex items-center gap-1" onclick="addBalanceToUser('${user.id}')">
                                        <i class="fa-solid fa-plus text-[10px]"></i> যোগ
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    zeroBalanceHtml = '<div class="text-center py-8 text-emerald-600 font-bold text-xs"><i class="fa-solid fa-circle-check text-base block mb-1"></i>কোনো শূন্য ব্যালেন্স ইউজার নেই</div>';
                }

                $('#zeroBalanceList').html(zeroBalanceHtml);
                $('#zeroBalanceUsers').text(zeroBalanceCount);
                $('#zeroBalanceCount').text(zeroBalanceCount);
            },
            error: function() {
                $('#zeroBalanceList').html('<div class="text-center py-6 text-rose-500 text-xs">লোড করা সম্ভব হয়নি</div>');
            }
        });
    }

    function addBalanceToUser(userId) {
        window.location.href = 'balance_add_remove.php?user_id=' + encodeURIComponent(userId);
    }
</script>