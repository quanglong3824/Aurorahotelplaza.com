<?php
// Determine base path based on current directory (if not already set)
if (!isset($base_path)) {
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
    $base_path = ($current_dir == 'room-details' || $current_dir == 'apartment-details') ? '../' : '';
}
?>
<!-- Footer -->
<footer class="w-full bg-surface-dark text-white/80">
    <div class="mx-auto max-w-7xl px-4 py-20">
        <div class="grid grid-cols-1 gap-12 md:grid-cols-2 lg:grid-cols-5">
            <!-- Logo & Description -->
            <div class="lg:col-span-2">
                <img src="<?php echo $base_path; ?>assets/img/src/logo/logo-dark-ui.png" alt="Aurora Hotel Plaza Logo" class="h-14 w-auto mb-6">
                <p class="mt-2 text-base text-white/70 leading-relaxed">
                    Aurora Hotel Plaza - Khách sạn sang trọng tại trung tâm Biên Hòa, Đồng Nai. 
                    Chúng tôi mang đến trải nghiệm nghỉ dưỡng đẳng cấp với dịch vụ hoàn hảo và tiện nghi hiện đại.
                </p>
                <div class="mt-6">
                    <h4 class="font-bold text-white mb-4">Theo dõi chúng tôi</h4>
                    <div class="flex gap-4">
                        <a class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white/60 hover:bg-accent hover:text-white transition-all" href="#" aria-label="Facebook">
                            <svg aria-hidden="true" class="h-5 w-5" fill="currentColor" viewbox="0 0 24 24"><path clip-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" fill-rule="evenodd"></path></svg>
                        </a>
                        <a class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white/60 hover:bg-accent hover:text-white transition-all" href="#" aria-label="Instagram">
                            <svg aria-hidden="true" class="h-5 w-5" fill="currentColor" viewbox="0 0 24 24"><path clip-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.024.06 1.378.06 3.808s-.012 2.784-.06 3.808c-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.024.048-1.378.06-3.808.06s-2.784-.013-3.808-.06c-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.048-1.024-.06-1.378-.06-3.808s.012-2.784.06-3.808c.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 016.345 2.525c.636-.247 1.363-.416 2.427-.465C9.793 2.013 10.147 2 12.315 2zm-1.161 1.545a.972.972 0 01.972.972c0 .537-.435.972-.972.972s-.972-.435-.972-.972c0-.537.435-.972.972-.972zM12 7.163c-2.673 0-4.837 2.164-4.837 4.837s2.164 4.837 4.837 4.837 4.837-2.164 4.837-4.837-2.164-4.837-4.837-4.837zm0 7.828a2.99 2.99 0 110-5.98 2.99 2.99 0 010 5.98z" fill-rule="evenodd"></path></svg>
                        </a>
                        <a class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white/60 hover:bg-accent hover:text-white transition-all" href="#" aria-label="YouTube">
                            <svg aria-hidden="true" class="h-5 w-5" fill="currentColor" viewbox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"></path></svg>
                        </a>
                        <a class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white/60 hover:bg-accent hover:text-white transition-all" href="#" aria-label="TripAdvisor">
                            <svg aria-hidden="true" class="h-5 w-5" fill="currentColor" viewbox="0 0 24 24"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8zm0-14c-3.309 0-6 2.691-6 6s2.691 6 6 6 6-2.691 6-6-2.691-6-6-6zm0 10c-2.206 0-4-1.794-4-4s1.794-4 4-4 4 1.794 4 4-1.794 4-4 4z"></path></svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-span-1">
                <h4 class="font-bold text-white text-lg mb-4">Liên kết nhanh</h4>
                <ul class="space-y-3 text-sm">
                    <li><a class="text-white/70 hover:text-accent transition-colors" href="#about">Về chúng tôi</a></li>
                    <li><a class="text-white/70 hover:text-accent transition-colors" href="#rooms">Phòng &amp; Suite</a></li>
                    <li><a class="text-white/70 hover:text-accent transition-colors" href="#services">Dịch vụ</a></li>
                    <li><a class="text-white/70 hover:text-accent transition-colors" href="#dining">Nhà hàng</a></li>
                    <li><a class="text-white/70 hover:text-accent transition-colors" href="#gallery">Thư viện ảnh</a></li>
                    <li><a class="text-white/70 hover:text-accent transition-colors" href="#events">Sự kiện</a></li>
                    <li><a class="text-white/70 hover:text-accent transition-colors" href="#contact">Liên hệ</a></li>
                </ul>
            </div>

            <!-- Services -->
            <div class="col-span-1">
                <h4 class="font-bold text-white text-lg mb-4">Dịch vụ</h4>
                <ul class="space-y-3 text-sm">
                    <li class="text-white/70">Đặt phòng trực tuyến</li>
                    <li class="text-white/70">Nhà hàng &amp; Bar</li>
                    <li class="text-white/70">Hội nghị &amp; Sự kiện</li>
                    <li class="text-white/70">Spa &amp; Massage</li>
                    <li class="text-white/70">Hồ bơi</li>
                    <li class="text-white/70">Phòng gym</li>
                    <li class="text-white/70">Dịch vụ đưa đón</li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-span-1">
                <h4 class="font-bold text-white text-lg mb-4">Liên hệ</h4>
                <ul class="space-y-4 text-sm">
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined mt-0.5 text-accent text-xl">location_on</span>
                        <span class="text-white/70">Số 253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, Tỉnh Đồng Nai</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined mt-0.5 text-accent text-xl">phone</span>
                        <a href="tel:+842513918888" class="text-white/70 hover:text-accent transition-colors">(+84-251) 391.8888</a>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined mt-0.5 text-accent text-xl">email</span>
                        <div class="flex flex-col gap-1">
                            <a href="mailto:info@aurorahotelplaza.com" class="text-white/70 hover:text-accent transition-colors">info@aurorahotelplaza.com</a>
                            <a href="mailto:booking@aurorahotelplaza.com" class="text-white/70 hover:text-accent transition-colors">booking@aurorahotelplaza.com</a>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined mt-0.5 text-accent text-xl">schedule</span>
                        <span class="text-white/70">Lễ tân 24/7</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="mt-12 pt-8 border-t border-white/20">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-sm text-white/60 text-center md:text-left">
                    © 2025 Aurora Hotel Plaza. Bản quyền thuộc về Aurora Hotel Plaza.
                </p>
                <div class="flex gap-6 text-sm">
                    <a href="#" class="text-white/60 hover:text-accent transition-colors">Chính sách bảo mật</a>
                    <a href="#" class="text-white/60 hover:text-accent transition-colors">Điều khoản sử dụng</a>
                    <a href="#" class="text-white/60 hover:text-accent transition-colors">Chính sách hủy phòng</a>
                </div>
            </div>
        </div>
    </div>
</footer>
