<!-- Hero Slider Section -->
<section class="hero-slider relative flex min-h-screen w-full items-center justify-center">
    <!-- Slider Images -->
    <div class="hero-slide active" style="background-image: url('assets/img/classical-family-apartment/classical-family-apartment6.jpg');"></div>
    <div class="hero-slide" style="background-image: url('assets/img/classical-premium-apartment/classical-premium-apartment-2.jpg');"></div>
    <div class="hero-slide" style="background-image: url('assets/img/indochine-family-apartment/indochine-family-apartment-12.jpg');"></div>
    <div class="hero-slide" style="background-image: url('assets/img/indochine-studio-apartment/indochine-studio-apartment-3.jpg');"></div>
    <div class="hero-slide" style="background-image: url('assets/img/modern-premium-apartment/modern-premium-apartment-4.jpg');"></div>
    <div class="hero-slide" style="background-image: url('assets/img/modern-studio-apartment/modern-studio-apartment-5.jpg');"></div>
    <div class="hero-slide" style="background-image: url('assets/img/restaurant/NHA-HANG-AURORA-HOTEL-4.jpg');"></div>
    <div class="hero-slide" style="background-image: url('assets/img/restaurant/NHA-HANG-AURORA-HOTEL-6.jpg');"></div>
    <div class="hero-slide" style="background-image: url('assets/img/post/wedding/Tiec-cuoi-tai-aurora-5.jpg');"></div>
    <div class="hero-slide" style="background-image: url('assets/img/src/ui/horizontal/sanh-khach-san-aurora.jpg');"></div>

    <!-- Previous Arrow -->
    <div class="slider-arrow prev">
        <span class="material-symbols-outlined arrow-icon">chevron_left</span>
    </div>

    <!-- Next Arrow -->
    <div class="slider-arrow next">
        <span class="material-symbols-outlined arrow-icon">chevron_right</span>
    </div>

    <!-- Hero Content -->
    <div class="relative z-10 flex flex-col items-center gap-8 text-center text-white px-4">
        <div class="flex flex-col gap-4">
            <span class="glass-badge-accent mx-auto">
                <span class="text-accent">★★★★★</span>
                Khách sạn 5 sao tại Biên Hòa
            </span>
            <h1 class="font-display text-4xl font-black leading-tight tracking-tight md:text-6xl">Aurora Hotel Plaza</h1>
            <p class="text-xl font-light text-white/90 max-w-2xl">Trải nghiệm đẳng cấp sang trọng với dịch vụ hoàn hảo tại trung tâm thành phố Biên Hòa, Đồng Nai</p>
        </div>
        
        <!-- Quick Booking Form - Liquid Glass -->
        <div class="mt-4 w-full max-w-4xl glass-booking-form">
            <form action="booking/index.php" method="GET" class="grid grid-cols-1 items-end gap-4 md:grid-cols-5">
                <div class="flex flex-col text-left">
                    <label class="mb-2 text-sm font-medium flex items-center gap-1" for="checkin">
                        <span class="material-symbols-outlined text-sm">calendar_today</span>
                        Ngày nhận phòng
                    </label>
                    <input class="glass-input-solid h-12" 
                           id="checkin" name="check_in" type="date" 
                           min="<?php echo date('Y-m-d'); ?>" 
                           value="<?php echo date('Y-m-d'); ?>"/>
                </div>
                <div class="flex flex-col text-left">
                    <label class="mb-2 text-sm font-medium flex items-center gap-1" for="checkout">
                        <span class="material-symbols-outlined text-sm">event</span>
                        Ngày trả phòng
                    </label>
                    <input class="glass-input-solid h-12" 
                           id="checkout" name="check_out" type="date" 
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                           value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"/>
                </div>
                <div class="flex flex-col text-left">
                    <label class="mb-2 text-sm font-medium flex items-center gap-1" for="adults">
                        <span class="material-symbols-outlined text-sm">person</span>
                        Người lớn
                    </label>
                    <select class="glass-input-solid glass-select h-12" id="adults" name="adults">
                        <option value="1">1 người</option>
                        <option value="2" selected>2 người</option>
                        <option value="3">3 người</option>
                        <option value="4">4 người</option>
                    </select>
                </div>
                <div class="flex flex-col text-left">
                    <label class="mb-2 text-sm font-medium flex items-center gap-1" for="children">
                        <span class="material-symbols-outlined text-sm">child_care</span>
                        Trẻ em
                    </label>
                    <select class="glass-input-solid glass-select h-12" id="children" name="children">
                        <option value="0" selected>0 trẻ em</option>
                        <option value="1">1 trẻ em</option>
                        <option value="2">2 trẻ em</option>
                        <option value="3">3 trẻ em</option>
                    </select>
                </div>
                <button type="submit" class="btn-glass-primary h-12 w-full">
                    <span class="material-symbols-outlined">search</span>
                    Tìm phòng
                </button>
            </form>
        </div>

        <!-- Trust Badges - Liquid Glass -->
        <div class="flex flex-wrap justify-center gap-4 mt-6">
            <div class="glass-trust-badge">
                <span class="material-symbols-outlined">verified</span>
                <span>Giá tốt nhất đảm bảo</span>
            </div>
            <div class="glass-trust-badge">
                <span class="material-symbols-outlined">credit_card_off</span>
                <span>Không cần thanh toán trước</span>
            </div>
            <div class="glass-trust-badge">
                <span class="material-symbols-outlined">event_available</span>
                <span>Hủy miễn phí trong 24h</span>
            </div>
        </div>
    </div>

    <!-- Slider Navigation Dots -->
    <div class="slider-dots" id="slider-dots-container">
        <!-- Dots will be generated dynamically by JavaScript -->
    </div>

    <!-- Scroll Down Indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
        <a href="#about" class="flex flex-col items-center gap-2 text-white/70 hover:text-white transition-colors">
            <span class="text-sm">Khám phá thêm</span>
            <span class="material-symbols-outlined text-2xl">keyboard_arrow_down</span>
        </a>
    </div>
</section>

<script>
// Set minimum dates for booking form
document.addEventListener('DOMContentLoaded', function() {
    const checkinInput = document.getElementById('checkin');
    const checkoutInput = document.getElementById('checkout');
    
    if (checkinInput && checkoutInput) {
        checkinInput.addEventListener('change', function() {
            const checkinDate = new Date(this.value);
            checkinDate.setDate(checkinDate.getDate() + 1);
            const minCheckout = checkinDate.toISOString().split('T')[0];
            checkoutInput.min = minCheckout;
            
            if (checkoutInput.value && checkoutInput.value <= this.value) {
                checkoutInput.value = minCheckout;
            }
        });
    }
});
</script>
