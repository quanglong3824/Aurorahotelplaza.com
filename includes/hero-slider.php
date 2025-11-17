<!-- Hero Slider Section -->
<section class="hero-slider relative flex min-h-screen w-full items-center justify-center">
    <!-- Slider Images -->
    <div class="hero-slide active" style="background-image: url('assets/img/classical family apartment/classical-family-apartment6.jpg');"></div>
    <div class="hero-slide" style="background-image: url('assets/img/classical premium apartment/classical-premium-apartment-2.jpg');"></div>
    <div class="hero-slide" style="background-image: url('assets/img/indochine family apartment/indochine-family-apartment-12.jpg');"></div>
    <div class="hero-slide" style="background-image: url('assets/img/indochine studio apartment/indochine-studio-apartment-3.jpg');"></div>
    <div class="hero-slide" style="background-image: url('assets/img/modern premium apartment/modern-premium-apartment-4.jpg');"></div>
    <div class="hero-slide" style="background-image: url('assets/img/modern studio apartment/modern-studio-apartment-5.jpg');"></div>
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
            <h1 class="font-display text-4xl font-black leading-tight tracking-tight md:text-6xl">Aurora Hotel Plaza</h1>
            <p class="text-lg font-light text-white/90">Trải nghiệm đẳng cấp sang trọng tại trung tâm Biên Hòa</p>
        </div>
        <div class="mt-4 w-full max-w-4xl rounded-xl bg-white/10 p-4 backdrop-blur-md">
            <form class="grid grid-cols-1 items-end gap-3 md:grid-cols-4">
                <div class="flex flex-col text-left">
                    <label class="mb-1 text-sm font-medium" for="checkin">Ngày nhận phòng</label>
                    <input class="h-12 rounded-lg border-0 bg-white/90 px-3 text-gray-800 shadow-sm" id="checkin" type="date"/>
                </div>
                <div class="flex flex-col text-left">
                    <label class="mb-1 text-sm font-medium" for="checkout">Ngày trả phòng</label>
                    <input class="h-12 rounded-lg border-0 bg-white/90 px-3 text-gray-800 shadow-sm" id="checkout" type="date"/>
                </div>
                <div class="flex flex-col text-left">
                    <label class="mb-1 text-sm font-medium" for="guests">Số khách</label>
                    <select class="h-12 rounded-lg border-0 bg-white/90 px-3 text-gray-800 shadow-sm" id="guests">
                        <option>1 Người lớn</option>
                        <option selected="">2 Người lớn</option>
                        <option>2 Người lớn, 1 Trẻ em</option>
                        <option>2 Người lớn, 2 Trẻ em</option>
                    </select>
                </div>
                <button class="col-span-1 flex h-12 w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg bg-accent text-base font-bold text-white transition-opacity hover:opacity-90">
                    Kiểm tra phòng trống
                </button>
            </form>
        </div>
    </div>

    <!-- Slider Navigation Dots -->
    <div class="slider-dots" id="slider-dots-container">
        <!-- Dots will be generated dynamically by JavaScript -->
    </div>
</section>
