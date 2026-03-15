<div class="max-w-3xl mx-auto">
    <!-- Warning Alert -->
    <div class="bg-red-50 border-2 border-red-500 rounded-xl p-6 mb-6">
        <div class="flex items-start gap-4">
            <span class="material-symbols-outlined text-red-600 text-4xl">warning</span>
            <div>
                <h3 class="text-xl font-bold text-red-600 mb-2">⚠️ CẢNH BÁO QUAN TRỌNG</h3>
                <p class="text-red-700 mb-2">Trang này dùng để xóa dữ liệu hệ thống. Hãy cân nhắc kỹ trước khi thực hiện.</p>
                <p class="text-red-700 font-bold">✅ Luôn giữ lại: Tài khoản ADMIN và Cấu hình hệ thống (System Settings)</p>
                <p class="text-red-700 mt-2">🔴 Hành động này <strong>KHÔNG THỂ HOÀN TÁC</strong>!</p>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-xl mb-6">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined">check_circle</span>
                <span><?php echo $message; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-xl mb-6">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined">error</span>
                <span><?php echo $error; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Reset Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-red-600">delete_forever</span>
                Tùy chọn Dọn dẹp
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" onsubmit="return confirmReset()">
                <div class="space-y-6">
                    <!-- Mode Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="relative flex p-4 cursor-pointer rounded-lg border-2 border-green-200 hover:border-green-500 bg-green-50 has-[:checked]:border-green-600 has-[:checked]:bg-green-100 transition-all">
                            <input type="radio" name="reset_mode" value="transactions_only" class="sr-only" checked>
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="material-symbols-outlined text-green-600">cleaning_services</span>
                                    <span class="font-bold text-green-800">Dọn dẹp Booking & Khách</span>
                                </div>
                                <p class="text-sm text-green-700">
                                    Chỉ xóa dữ liệu Đặt phòng, Thanh toán, Khách hàng, Đánh giá, Logs.
                                    <br><strong>TỰ ĐỘNG:</strong> Đưa tất cả phòng về trạng thái "Còn trống".
                                    <br><span class="font-semibold">GIỮ LẠI:</span> Phòng, Loại phòng, Dịch vụ, Bài viết, Ảnh, Cấu hình giá.
                                </p>
                                <div class="mt-2 text-xs font-semibold text-green-600 uppercase tracking-wider">Khuyên dùng cho lên Product</div>
                            </div>
                        </label>

                        <label class="relative flex p-4 cursor-pointer rounded-lg border-2 border-red-200 hover:border-red-500 bg-red-50 has-[:checked]:border-red-600 has-[:checked]:bg-red-100 transition-all">
                            <input type="radio" name="reset_mode" value="full" class="sr-only">
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="material-symbols-outlined text-red-600">delete_sweep</span>
                                    <span class="font-bold text-red-800">Reset Toàn Bộ (Xóa Hết)</span>
                                </div>
                                <p class="text-sm text-red-700">
                                    Xóa TẤT CẢ mọi dữ liệu về trạng thái ban đầu của database rỗng.
                                    <br>Phòng, Dịch vụ, Bài viết cũng sẽ bị xóa.
                                </p>
                                <div class="mt-2 text-xs font-semibold text-red-600 uppercase tracking-wider">Cẩn thận</div>
                            </div>
                        </label>
                    </div>

                    <div class="border-t pt-4">
                        <p class="text-gray-700 mb-4">Để xác nhận, vui lòng nhập chính xác văn bản sau:</p>
                        <div class="bg-gray-100 dark:bg-slate-800 p-4 rounded-lg mb-4 text-center">
                            <code class="text-lg font-mono font-bold text-red-600">RESET DATABASE</code>
                        </div>
                        <div class="form-group max-w-md mx-auto">
                            <input type="text" name="confirmation" id="confirmation" class="form-input font-mono text-center border-red-300 focus:border-red-500 focus:ring-red-500" placeholder="Nhập RESET DATABASE vào đây" required autocomplete="off">
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <span class="material-symbols-outlined text-sm">arrow_back</span>
                            Quay lại Dashboard
                        </a>
                        <button type="submit" name="confirm_reset" class="btn btn-danger w-full md:w-auto">
                            <span class="material-symbols-outlined text-sm">run_circle</span>
                            Thực hiện Reset
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Card -->
    <div class="card mt-6">
        <div class="card-header">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <span class="material-symbols-outlined">info</span>
                Thông tin
            </h3>
        </div>
        <div class="card-body">
            <div class="space-y-3 text-sm">
                <p><strong>Mục đích:</strong> Dùng để reset database về trạng thái ban đầu khi cần test hoặc bắt đầu lại từ đầu.</p>
                <p><strong>Thời gian:</strong> Quá trình reset mất khoảng 5-10 giây.</p>
                <p><strong>Backup:</strong> Nên backup database trước khi thực hiện nếu cần giữ lại dữ liệu.</p>
                <p><strong>Alternative:</strong> Có thể chạy file SQL trực tiếp: <code class="bg-gray-100 px-2 py-1 rounded">docs/RESET_DATABASE_KEEP_ADMIN.sql</code></p>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmReset() {
        const confirmation = document.getElementById('confirmation').value;
        if (confirmation !== 'RESET DATABASE') {
            alert('Vui lòng nhập chính xác "RESET DATABASE" để xác nhận.');
            return false;
        }
        return confirm(
            '⚠️ XÁC NHẬN LẦN CUỐI ⚠️\n\n' +
            'Bạn có CHẮC CHẮN muốn xóa TOÀN BỘ dữ liệu?\n\n' +
            'Hành động này KHÔNG THỂ HOÀN TÁC!\n\n' +
            'Nhấn OK để tiếp tục, Cancel để hủy.'
        );
    }
</script>

<style>
    .btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
    .btn-danger:hover {
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
        transform: translateY(-2px);
    }
</style>
