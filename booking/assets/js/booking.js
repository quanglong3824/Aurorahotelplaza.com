// Booking Form JavaScript

let currentStep = 1;
let isInquiryMode = false;
let currentBookingType = 'standard'; // 'standard' or 'short_stay'
let extraGuests = []; // Array to store extra guests with heights
let suggestionDismissed = false; // Track if user dismissed suggestion
let extraBedLocked = false; // Track if extra bed is locked (for 3 adults case)

// ========== PRICING CONSTANTS (must match backend) ==========
const EXTRA_BED_PRICE = 650000; // 650,000 VND/đêm
const EXTRA_GUEST_FEES = {
    under1m: 0,       // Dưới 1m: Miễn phí (bao gồm ăn sáng)
    '1m_1m3': 200000, // 1m - 1m3: 200,000 VND/đêm (bao gồm ăn sáng)
    over1m3: 400000   // Trên 1m3: 400,000 VND/đêm (bao gồm ăn sáng)
};

// ========== ROOM CONFIGURATION ==========
// 4 loại phòng: Deluxe, Double Deluxe, Aurora Studio, Twin
const ROOM_CONFIG = {
    maxAdults: 3,
    maxChildren: 1,
    maxOccupancy: 3, // 3 người lớn HOẶC 2 lớn + 1 nhỏ
    extraBedFor3Adults: 1 // Bắt buộc 1 giường phụ khi có 3 người lớn
};

// ========== SMART SUGGESTION ALGORITHM ==========
/**
 * Thuật toán tính toán booking mới (2025)
 * 
 * QUY TẮC:
 * 1. Tối đa 3 người lớn, tối đa 2 lớn + 1 nhỏ
 * 2. Nếu 3 người lớn:
 *    - Disable thêm trẻ em (mặc định = 0)
 *    - Tự động +1 phụ thu người lớn thứ 3
 *    - Tự động +1 giường phụ (locked, không thể thay đổi)
 * 3. Nếu 2 lớn + 1 nhỏ:
 *    - Cho phép chọn chiều cao trẻ em
 *    - Giường phụ mặc định = 0, có thể chọn thêm
 *    - Nếu height >= 1.3m: PHẢI chọn giường phụ
 * 4. Phụ thu trẻ em theo chiều cao:
 *    - Dưới 1m: Miễn phí
 *    - 1m - 1.3m: 200,000 VND/đêm
 *    - Trên 1.3m: 400,000 VND/đêm
 */
function checkAndShowSuggestion() {
    if (isInquiryMode || suggestionDismissed) return;
    
    const roomSelect = document.getElementById('room_type_id');
    const selectedOption = roomSelect?.options[roomSelect.selectedIndex];
    
    if (!selectedOption || !selectedOption.value) {
        hideSuggestion();
        return;
    }
    
    const category = selectedOption.dataset.category || 'room';
    const maxAdults = parseInt(selectedOption.dataset.maxAdults) || 2;
    const maxChildren = parseInt(selectedOption.dataset.maxChildren) || 1;
    const maxOccupancy = parseInt(selectedOption.dataset.maxGuests) || 3;
    
    const numAdults = parseInt(document.getElementById('num_adults')?.value) || 2;
    const numChildren = parseInt(document.getElementById('num_children')?.value) || 0;
    const totalGuests = numAdults + numChildren;
    const numExtraBeds = parseInt(document.getElementById('extra_beds')?.value) || 0;
    
    // Số khách thêm đã khai báo
    const declaredExtraGuests = extraGuests.length;
    
    let suggestion = null;
    
    // Case A: Đã đạt max adults và có trẻ em nhưng chưa khai báo
    if (numAdults >= maxAdults && numChildren >= 1 && declaredExtraGuests < numChildren) {
        const undeclared = numChildren - declaredExtraGuests;
        
        if (numChildren >= 2) {
            // Case B: Nhiều trẻ em - gợi ý thêm giường
            suggestion = {
                type: 'warning',
                message: `Bạn có ${numChildren} trẻ em đi cùng ${numAdults} người lớn. ` +
                         `Vui lòng khai báo chiều cao trẻ em để tính phụ thu chính xác. ` +
                         `${category === 'room' ? 'Bạn cũng có thể cần thêm giường phụ.' : ''}`,
                actions: [
                    { label: `Khai báo ${undeclared} trẻ em`, action: 'declareChildren', count: undeclared },
                    ...(category === 'room' && numExtraBeds === 0 ? [{ label: 'Thêm giường phụ', action: 'addBed' }] : [])
                ]
            };
        } else {
            // Case A: 1 trẻ em
            suggestion = {
                type: 'info',
                message: `Bạn có ${numChildren} trẻ em đi cùng. Vui lòng khai báo chiều cao để tính phụ thu (nếu có).`,
                actions: [
                    { label: 'Khai báo chiều cao', action: 'declareChildren', count: undeclared }
                ]
            };
        }
    }
    // Case C: Tổng khách vượt quá sức chứa
    else if (totalGuests > maxOccupancy && declaredExtraGuests < (totalGuests - maxOccupancy)) {
        const extraNeeded = totalGuests - maxOccupancy - declaredExtraGuests;
        suggestion = {
            type: 'warning',
            message: `Số khách (${totalGuests} người) vượt quá sức chứa tiêu chuẩn của phòng (${maxOccupancy} người). ` +
                     `Vui lòng khai báo ${extraNeeded} khách thêm để tính phụ thu.`,
            actions: [
                { label: `Khai báo ${extraNeeded} khách thêm`, action: 'declareExtra', count: extraNeeded }
            ]
        };
    }
    // Case D: Có trẻ em nhưng chưa khai báo (gợi ý nhẹ)
    else if (numChildren > 0 && declaredExtraGuests === 0) {
        suggestion = {
            type: 'hint',
            message: `Lưu ý: Nếu trẻ em cao từ 1m trở lên sẽ có phụ thu. Bạn có muốn khai báo chiều cao không?`,
            actions: [
                { label: 'Khai báo ngay', action: 'declareChildren', count: numChildren },
                { label: 'Bỏ qua', action: 'dismiss' }
            ]
        };
    }
    
    if (suggestion) {
        showSuggestion(suggestion);
    } else {
        hideSuggestion();
    }
}

function showSuggestion(suggestion) {
    const box = document.getElementById('smart_suggestion_box');
    const message = document.getElementById('suggestion_message');
    const actions = document.getElementById('suggestion_actions');
    
    if (!box || !message || !actions) return;
    
    // Update message
    message.textContent = suggestion.message;
    
    // Update border color and icon based on type
    box.classList.remove('border-amber-500/30', 'border-red-500/30', 'border-blue-500/30', 'bg-amber-500/10', 'bg-red-500/10', 'bg-blue-500/10');
    
    const icon = document.getElementById('suggestion_icon');
    if (icon) {
        icon.classList.remove('text-amber-400', 'text-red-400', 'text-blue-400');
    }
    
    if (suggestion.type === 'warning') {
        box.classList.add('border-red-500/30', 'bg-red-500/10');
        if (icon) icon.classList.add('text-red-400');
        if (icon) icon.textContent = 'warning';
    } else if (suggestion.type === 'hint') {
        box.classList.add('border-blue-500/30', 'bg-blue-500/10');
        if (icon) icon.classList.add('text-blue-400');
        if (icon) icon.textContent = 'lightbulb';
    } else {
        box.classList.add('border-amber-500/30', 'bg-amber-500/10');
        if (icon) icon.classList.add('text-amber-400');
        if (icon) icon.textContent = 'info';
    }
    
    // Build action buttons with enhanced styling
    actions.innerHTML = suggestion.actions.map(act => {
        if (act.action === 'dismiss') {
            return `<button type="button" onclick="dismissSuggestion()" 
                class="px-3 py-1.5 text-xs bg-gray-600/50 hover:bg-gray-600 text-white rounded-lg transition-colors">
                ${act.label}
            </button>`;
        }
        const iconClass = act.action === 'addBed' ? 'single_bed' : 'person_add';
        const bgColor = act.action === 'addBed' ? 'bg-orange-600 hover:bg-orange-500' : 'bg-blue-600 hover:bg-blue-500';
        return `<button type="button" onclick="handleSuggestionAction('${act.action}', ${act.count || 0})" 
            class="px-3 py-1.5 text-xs ${bgColor} text-white rounded-lg transition-all font-medium shadow-md hover:shadow-lg flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">${iconClass}</span>
            ${act.label}
        </button>`;
    }).join('');
    
    box.classList.remove('hidden');
    
    // Auto-scroll to suggestion if needed
    setTimeout(() => {
        box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 100);
}

function hideSuggestion() {
    const box = document.getElementById('smart_suggestion_box');
    if (box) box.classList.add('hidden');
}

function dismissSuggestion() {
    suggestionDismissed = true;
    hideSuggestion();
}

function handleSuggestionAction(action, count) {
    if (action === 'declareChildren' || action === 'declareExtra') {
        // Mở form thêm khách và thêm số lượng cần thiết
        const list = document.getElementById('extra_guests_list');
        const btn = document.getElementById('toggle_extra_guests_btn');
        
        if (list.classList.contains('hidden')) {
            list.classList.remove('hidden');
            btn.innerHTML = '<span class="material-symbols-outlined text-sm">remove_circle</span> Ẩn';
        }
        
        // Thêm số khách cần khai báo
        for (let i = 0; i < count; i++) {
            addExtraGuest();
        }
        
        // Scroll to extra guests section
        document.getElementById('extra_guests_section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } 
    else if (action === 'addBed') {
        // Thêm 1 giường phụ
        const extraBedsInput = document.getElementById('extra_beds');
        if (extraBedsInput) {
            extraBedsInput.value = Math.min(parseInt(extraBedsInput.value || 0) + 1, 2);
            calculateTotal();
        }
        
        // Scroll to extra bed section
        document.getElementById('extra_bed_section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    // Ẩn gợi ý sau khi thực hiện action
    hideSuggestion();
    calculateTotal();
}

// Initialize
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM loaded, initializing booking form...');

    // Set minimum date to today
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const today = `${year}-${month}-${day}`;

    const checkInDate = document.getElementById('check_in_date');
    const checkOutDate = document.getElementById('check_out_date');

    if (checkInDate) checkInDate.min = today;

    // Set default check-in to today and check-out to tomorrow
    if (checkInDate && !checkInDate.value) {
        checkInDate.value = today;

        const tomorrowDate = new Date();
        tomorrowDate.setDate(tomorrowDate.getDate() + 1);
        const tYear = tomorrowDate.getFullYear();
        const tMonth = String(tomorrowDate.getMonth() + 1).padStart(2, '0');
        const tDay = String(tomorrowDate.getDate()).padStart(2, '0');

        if (checkOutDate) checkOutDate.value = `${tYear}-${tMonth}-${tDay}`;
    }

    // Always update checkout min based on check-in value
    updateCheckoutMin();

    // Room type selection
    const roomSelect = document.getElementById('room_type_id');
    const preselectedId = roomSelect?.dataset.preselected;
    const hasPreselection = preselectedId && preselectedId !== 'null' && preselectedId !== '';

    if (hasPreselection && roomSelect) {
        for (let i = 0; i < roomSelect.options.length; i++) {
            if (roomSelect.options[i].value === preselectedId) {
                roomSelect.selectedIndex = i;
                break;
            }
        }
    } else if (roomSelect) {
        roomSelect.selectedIndex = 0;
    }

    // Initialize booking type selection
    initBookingTypeSelection();

    // Check initial mode and calculate
    checkBookingMode();
    calculateTotal();

    // Event listeners
    if (checkInDate) {
        checkInDate.addEventListener('change', function () {
            updateCheckoutMin();
            calculateTotal();
        });
    }
    if (checkOutDate) {
        checkOutDate.addEventListener('change', calculateTotal);
    }
    if (roomSelect) {
        roomSelect.addEventListener('change', function () {
            checkBookingMode();
            updateShortStayAvailability();
            calculateTotal();
        });
    }

    // Recalculate price when adults/children changes
    const numAdultsInput = document.getElementById('num_adults');
    const numChildrenInput = document.getElementById('num_children');
    const extraBedsInput = document.getElementById('extra_beds');

    if (numAdultsInput) {
        numAdultsInput.addEventListener('change', function() {
            handleAdultsChange();
            calculateTotal();
        });
    }
    if (numChildrenInput) {
        numChildrenInput.addEventListener('change', function() {
            handleChildrenChange();
            calculateTotal();
        });
    }
    if (extraBedsInput) {
        extraBedsInput.addEventListener('change', function() {
            // If locked (3 adults case), prevent change
            if (extraBedLocked) {
                this.value = 1;
                return;
            }
            calculateTotal();
        });
    }

    // Form submission
    const form = document.getElementById('bookingForm');
    if (form) {
        form.addEventListener('submit', handleSubmit);
    }

    // Update short stay availability based on selected room
    updateShortStayAvailability();
});

// Initialize booking type selection (standard vs short_stay)
function initBookingTypeSelection() {
    const bookingTypeOptions = document.querySelectorAll('.booking-type-option');
    bookingTypeOptions.forEach(option => {
        option.addEventListener('click', function () {
            const input = this.querySelector('input[type="radio"]');
            if (input && !input.disabled) {
                // Update visual state
                bookingTypeOptions.forEach(opt => {
                    opt.classList.remove('selected');
                    const div = opt.querySelector('div');
                    if (div) {
                        div.classList.remove('border-amber-500', 'bg-amber-500/10');
                        div.classList.add('border-gray-600', 'bg-gray-700/30');
                    }
                });

                this.classList.add('selected');
                const div = this.querySelector('div');
                if (div) {
                    div.classList.remove('border-gray-600', 'bg-gray-700/30');
                    div.classList.add('border-amber-500', 'bg-amber-500/10');
                }

                input.checked = true;
                currentBookingType = input.value;

                // Show/hide short stay note
                const shortStayNote = document.getElementById('short_stay_note');
                const checkoutGroup = document.getElementById('checkout_group');

                if (currentBookingType === 'short_stay') {
                    if (shortStayNote) shortStayNote.classList.remove('hidden');
                    if (checkoutGroup) checkoutGroup.classList.add('hidden');
                } else {
                    if (shortStayNote) shortStayNote.classList.add('hidden');
                    if (checkoutGroup) checkoutGroup.classList.remove('hidden');
                }

                calculateTotal();
            }
        });
    });
}

// Update short stay availability based on room type
function updateShortStayAvailability() {
    const roomSelect = document.getElementById('room_type_id');
    const shortStayOption = document.getElementById('short_stay_option');

    if (!roomSelect || !shortStayOption) return;

    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const shortStayPrice = parseFloat(selectedOption?.dataset.priceShortStay) || 0;
    const category = selectedOption?.dataset.category || 'room';

    const input = shortStayOption.querySelector('input');
    const div = shortStayOption.querySelector('div');

    if (shortStayPrice > 0 && category === 'room') {
        // Enable short stay
        shortStayOption.classList.remove('opacity-50', 'cursor-not-allowed');
        if (input) input.disabled = false;
        if (div) div.classList.remove('opacity-50');
    } else {
        // Disable short stay
        shortStayOption.classList.add('opacity-50', 'cursor-not-allowed');
        if (input) input.disabled = true;
        if (div) div.classList.add('opacity-50');

        // Reset to standard if short_stay was selected
        if (currentBookingType === 'short_stay') {
            const standardOption = document.querySelector('.booking-type-option[data-type="standard"]');
            if (standardOption) standardOption.click();
        }
    }

    // Show/hide extra bed section for apartments
    const extraBedSection = document.getElementById('extra_bed_section');
    const extraBedWarning = document.getElementById('extra_bed_warning');

    if (category === 'apartment') {
        if (extraBedSection) extraBedSection.classList.add('hidden');
        if (extraBedWarning) extraBedWarning.classList.remove('hidden');
    } else {
        if (extraBedSection) extraBedSection.classList.remove('hidden');
        if (extraBedWarning) extraBedWarning.classList.add('hidden');
    }
}

// Adjust numeric input values with +/- buttons
function adjustValue(fieldId, delta) {
    const input = document.getElementById(fieldId);
    if (!input) return;

    // Ngăn điều chỉnh nếu đang bị khóa
    if (input.disabled) return;

    let value = parseInt(input.value) || 0;
    let min = parseInt(input.min) || 0;
    let max = parseInt(input.max) || 99;

    value += delta;

    if (value < min) value = min;
    if (value > max) value = max;

    input.value = value;

    // Update total guests hidden field
    if (fieldId === 'num_adults' || fieldId === 'num_children') {
        updateTotalGuests();
        suggestionDismissed = false; // Reset suggestion dismissed
        
        // CẬP NHẬT LOGIC KHÓA THEO SỐ LƯỢNG KHI CLICK BUTTON +/-
        if (fieldId === 'num_adults') {
            handleAdultsChange();
        } else if (fieldId === 'num_children') {
            handleChildrenChange();
        }
    } else if (fieldId === 'extra_beds') {
        // Prevent changing locked bed
        if (extraBedLocked) {
           input.value = 1;
        }
    }

    // Check and show smart suggestion
    checkAndShowSuggestion();

    // Recalculate
    calculateTotal();
}

// Update the hidden num_guests field
function updateTotalGuests() {
    const numAdults = parseInt(document.getElementById('num_adults')?.value) || 1;
    const numGuests = document.getElementById('num_guests');
    if (numGuests) {
        numGuests.value = numAdults;
    }
}

// Toggle extra guests form visibility
function toggleExtraGuests() {
    const list = document.getElementById('extra_guests_list');
    const btn = document.getElementById('toggle_extra_guests_btn');

    if (list.classList.contains('hidden')) {
        list.classList.remove('hidden');
        btn.innerHTML = '<span class="material-symbols-outlined text-sm">remove_circle</span> Ẩn';
        // Add first guest if list is empty
        if (extraGuests.length === 0) {
            addExtraGuest();
        }
    } else {
        list.classList.add('hidden');
        btn.innerHTML = '<span class="material-symbols-outlined text-sm">add_circle</span> Thêm khách';
    }
}

// ========== NEW LOGIC: Handle Adults Change ==========
function handleAdultsChange() {
    const numAdultsInput = document.getElementById('num_adults');
    const numChildrenInput = document.getElementById('num_children');
    const extraBedsInput = document.getElementById('extra_beds');
    const toggleExtraGuestsBtn = document.getElementById('toggle_extra_guests_btn');
    
    // Nút +/- của trẻ em
    const btnMinusChild = numChildrenInput?.parentElement?.querySelector('button:first-child');
    const btnPlusChild = numChildrenInput?.parentElement?.querySelector('button:last-child');
    
    // Nút +/- của giường phụ
    const btnMinusBed = extraBedsInput?.parentElement?.querySelector('button:first-child');
    const btnPlusBed = extraBedsInput?.parentElement?.querySelector('button:last-child');
    
    if (!numAdultsInput || !numChildrenInput) return;
    
    const numAdults = parseInt(numAdultsInput.value) || 1;
    let numChildren = parseInt(numChildrenInput.value) || 0;
    
    // Case 1: 3 người lớn
    if (numAdults >= 3) {
        // Tắt khả năng thêm trẻ em hoàn toàn
        numChildrenInput.value = 0;
        numChildrenInput.disabled = true;
        if (btnMinusChild) btnMinusChild.classList.add('opacity-50', 'cursor-not-allowed');
        if (btnPlusChild) btnPlusChild.classList.add('opacity-50', 'cursor-not-allowed');
        
        numChildren = 0;
        
        // Auto-add 1 extra guest ẩn cho Backend tính tiền 400k (không hiện trên UI extra guests)
        extraGuests = [{ id: 999, height: 1.7, type: 'over1m3', isAdult: true, isLocked: true }];
        
        // Auto-add 1 extra bed (bắt buộc)
        if (extraBedsInput) {
            extraBedsInput.value = 1;
            extraBedsInput.disabled = true;
            extraBedLocked = true;
            if (btnMinusBed) btnMinusBed.classList.add('opacity-50', 'cursor-not-allowed');
            if (btnPlusBed) btnPlusBed.classList.add('opacity-50', 'cursor-not-allowed');
        }
        
        // Ẩn UI Khách thêm 
        const extraGuestsSection = document.getElementById('extra_guests_section');
        if (extraGuestsSection) extraGuestsSection.classList.add('hidden');
        if (toggleExtraGuestsBtn) toggleExtraGuestsBtn.classList.add('hidden');
        
    } else {
        // Case 2: 1-2 người lớn -> Cho phép thêm trẻ em
        numChildrenInput.disabled = false;
        if (btnMinusChild) btnMinusChild.classList.remove('opacity-50', 'cursor-not-allowed');
        if (btnPlusChild) btnPlusChild.classList.remove('opacity-50', 'cursor-not-allowed');
        
        // Release Khóa của Giường phụ
        extraBedLocked = false;
        if (extraBedsInput) {
            extraBedsInput.disabled = false;
            // Nếu giảm xuống 1 người lớn thì chưa chắc cần giường phụ, thiết lập về 0 nếu chưa muốn
            if (numAdults < 2 && extraBedsInput.value == 1 && numChildren == 0) {
                extraBedsInput.value = 0;
            }
            if (btnMinusBed) btnMinusBed.classList.remove('opacity-50', 'cursor-not-allowed');
            if (btnPlusBed) btnPlusBed.classList.remove('opacity-50', 'cursor-not-allowed');
        }
        
        // Khôi phục UI Khách thêm
        const extraGuestsSection = document.getElementById('extra_guests_section');
        if (extraGuestsSection) extraGuestsSection.classList.remove('hidden');
        if (toggleExtraGuestsBtn) toggleExtraGuestsBtn.classList.remove('hidden');
        
        // Filter bỏ đi người lớn thứ 3 đang bị khóa trong list
        extraGuests = extraGuests.filter(g => !g.isAdult);
        
        // Nếu số lượng khách vượt quá maxOccupancy (ví dụ 3) 
        if (numAdults + numChildren > ROOM_CONFIG.maxOccupancy) {
            numChildren = ROOM_CONFIG.maxOccupancy - numAdults;
            numChildrenInput.value = numChildren;
        }
        
        // Tái đồng bộ dữ liệu extraGuests (thêm form nếu có trẻ nhỏ, ẩn form nếu chưa có)
        if (numChildren > 0 && extraGuests.length === 0) {
            for (let i = 0; i < numChildren; i++) {
                extraGuests.push({ id: Date.now() + i, height: 1.0, type: '1m_1m3', isAdult: false, isLocked: false });
            }
            if (toggleExtraGuestsBtn) toggleExtraGuestsBtn.innerHTML = '<span class="material-symbols-outlined text-sm">remove_circle</span> Ẩn';
            document.getElementById('extra_guests_list')?.classList.remove('hidden');
        } else if (numChildren < extraGuests.length) {
            extraGuests = extraGuests.slice(0, numChildren);
        } else if (numChildren === 0) {
            extraGuests = [];
            if (toggleExtraGuestsBtn) toggleExtraGuestsBtn.innerHTML = '<span class="material-symbols-outlined text-sm">add_circle</span> Thêm khách';
            document.getElementById('extra_guests_list')?.classList.add('hidden');
        }
    }
    
    renderExtraGuests();
    // calculateTotal(); sẽ được gọi sau handleAdultsChange ở nơi gọi nó
}

// ========== NEW LOGIC: Handle Children Change ==========
function handleChildrenChange() {
    const numAdultsInput = document.getElementById('num_adults');
    const numChildrenInput = document.getElementById('num_children');
    const extraBedsInput = document.getElementById('extra_beds');
    const toggleExtraGuestsBtn = document.getElementById('toggle_extra_guests_btn');
    
    if (!numChildrenInput || !numAdultsInput) return;
    
    const numAdults = parseInt(numAdultsInput.value) || 1;
    let numChildren = parseInt(numChildrenInput.value) || 0;
    
    // Ép giới hạn: (Max = Tổng số người - Người lớn)
    if (numAdults >= 2 && numChildren > 1) {
        numChildren = 1;
        numChildrenInput.value = 1;
    }
    
    if (numAdults + numChildren > ROOM_CONFIG.maxOccupancy) {
        numChildren = ROOM_CONFIG.maxOccupancy - numAdults;
        numChildrenInput.value = numChildren;
    }
    
    // Lọc lại mảng nếu có người lớn bị lỗi kẹt trong extraGuests (đề phòng)
    extraGuests = extraGuests.filter(g => !g.isAdult);

    // Không quan tâm mảng cũ, build lại danh sách khách theo số lượng mới (xóa data cũ để UI ko mâu thuẫn)
    if (numChildren > 0) {
        // Chỉ thêm mới entry trẻ con nếu mảng thiếu
        if (extraGuests.length < numChildren) {
             for (let i = extraGuests.length; i < numChildren; i++) {
                 extraGuests.push({ id: Date.now() + i, height: 1.0, type: '1m_1m3', isAdult: false, isLocked: false });
             }
        } else if (extraGuests.length > numChildren) {
             extraGuests = extraGuests.slice(0, numChildren);
        }
        
        document.getElementById('extra_guests_list')?.classList.remove('hidden');
        if(toggleExtraGuestsBtn) toggleExtraGuestsBtn.innerHTML = '<span class="material-symbols-outlined text-sm">remove_circle</span> Ẩn';
    } else {
        extraGuests = [];
        document.getElementById('extra_guests_list')?.classList.add('hidden');
        if(toggleExtraGuestsBtn) toggleExtraGuestsBtn.innerHTML = '<span class="material-symbols-outlined text-sm">add_circle</span> Thêm khách';
    }
    
    // Gợi ý giường phụ nếu có em bé nhưng vẫn chưa đủ 3 người lớn
    const needsExtraBed = extraGuests.some(g => g.height >= 1.3 && !g.isAdult);
    const bedWarning = document.getElementById('extra_bed_warning');
    if (needsExtraBed && extraBedsInput && !extraBedsInput.disabled && parseInt(extraBedsInput.value) === 0) {
        if (bedWarning) {
            bedWarning.classList.remove('hidden');
            bedWarning.innerHTML = '<span class="material-symbols-outlined text-sm text-amber-400">warning</span> Trẻ em cao từ 1.3m nên sử dụng giường phụ (650,000 VND/đêm)';
        }
    } else {
        if(bedWarning) bedWarning.classList.add('hidden');
    }
    
    renderExtraGuests();
    // calculateTotal(); sẽ được gọi sau ở ngoài
}

// Add extra guest entry
function addExtraGuest() {
    // Check if at max occupancy
    const numAdults = parseInt(document.getElementById('num_adults')?.value) || 1;
    const numChildren = parseInt(document.getElementById('num_children')?.value) || 0;
    
    if (numAdults + numChildren >= ROOM_CONFIG.maxOccupancy) {
        showToast('Không thể thêm khách vượt quá sức chứa phòng (tối đa 3 người)', 'error');
        return;
    }
    
    const id = Date.now();
    extraGuests.push({ id, height: 1.0, type: '1m_1m3', isAdult: false, isLocked: false });
    renderExtraGuests();
    calculateTotal();
}

// Remove extra guest
function removeExtraGuest(id) {
    extraGuests = extraGuests.filter(g => g.id !== id);
    renderExtraGuests();
    calculateTotal();

    // Hide list if empty
    if (extraGuests.length === 0) {
        const list = document.getElementById('extra_guests_list');
        const btn = document.getElementById('toggle_extra_guests_btn');
        list.classList.add('hidden');
        btn.innerHTML = '<span class="material-symbols-outlined text-sm">add_circle</span> Thêm khách';
    }

    // Re-check suggestion after removing guest
    suggestionDismissed = false;
    checkAndShowSuggestion();
}

// Update extra guest height (kept for backward compatibility, but not used)
// Now using setExtraGuestHeight() with button clicks instead
function updateExtraGuestHeight(id, height) {
    // Deprecated - use setExtraGuestHeight instead
    console.warn('updateExtraGuestHeight is deprecated, use setExtraGuestHeight');
    setExtraGuestHeight(id, parseFloat(height), height < 1.0 ? 'under1m' : height < 1.3 ? '1m_1m3' : 'over1m3');
}

// Render extra guests list with height buttons
function renderExtraGuests() {
    const list = document.getElementById('extra_guests_list');
    if (!list) return;

    // If no extra guests or only locked adult entry, hide list
    const activeGuests = extraGuests.filter(g => !g.isAdult);
    if (activeGuests.length === 0) {
        list.classList.add('hidden');
        return;
    }

    list.innerHTML = activeGuests.map((guest, index) => `
        <div class="bg-slate-700/50 border border-slate-600 rounded-xl p-5 transition-all duration-300 hover:border-blue-500/50 shadow-sm hover:shadow-md">
            <div class="flex items-center gap-3 mb-3">
                <span class="material-symbols-outlined text-blue-400 text-lg">child_friendly</span>
                <span class="text-gray-300 font-semibold">Trẻ em #${index + 1}</span>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-400 mb-2 block">Chiều cao trẻ em (chọn 1 trong 3 mức)</label>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" 
                        onclick="setExtraGuestHeight(${guest.id}, 0.5, 'under1m')"
                        class="height-btn py-3 px-2 rounded-lg border-2 transition-all text-center ${guest.type === 'under1m' ? 'bg-green-500/20 border-green-500 text-green-400' : 'bg-slate-600/50 border-slate-600 text-gray-400 hover:border-green-500/50'}">
                        <div class="font-bold text-sm mb-0.5">Dưới 1m</div>
                        <div class="text-xs opacity-75">Miễn phí</div>
                    </button>
                    <button type="button" 
                        onclick="setExtraGuestHeight(${guest.id}, 1.0, '1m_1m3')"
                        class="height-btn py-3 px-2 rounded-lg border-2 transition-all text-center ${guest.type === '1m_1m3' ? 'bg-yellow-500/20 border-yellow-500 text-yellow-400' : 'bg-slate-600/50 border-slate-600 text-gray-400 hover:border-yellow-500/50'}">
                        <div class="font-bold text-sm mb-0.5">1m - 1m3</div>
                        <div class="text-xs opacity-75">200k/đêm</div>
                    </button>
                    <button type="button" 
                        onclick="setExtraGuestHeight(${guest.id}, 1.5, 'over1m3')"
                        class="height-btn py-3 px-2 rounded-lg border-2 transition-all text-center ${guest.type === 'over1m3' ? 'bg-orange-500/20 border-orange-500 text-orange-400' : 'bg-slate-600/50 border-slate-600 text-gray-400 hover:border-orange-500/50'}">
                        <div class="font-bold text-sm mb-0.5">Trên 1m3</div>
                        <div class="text-xs opacity-75">400k/đêm</div>
                    </button>
                </div>
            </div>
            
            <div class="flex items-center justify-between pt-3 border-t border-slate-600">
                <div class="text-sm text-gray-400">
                    <span class="material-symbols-outlined text-xs align-middle">calculate</span>
                    Phụ thu: <span class="font-bold ${guest.type === 'under1m' ? 'text-green-400' : guest.type === '1m_1m3' ? 'text-yellow-400' : 'text-orange-400'}">
                        ${guest.type === 'under1m' ? 'Miễn phí' : guest.type === '1m_1m3' ? '200.000 VND' : '400.000 VND'}
                    </span>
                </div>
                ${!guest.isLocked ? `
                <button type="button" onclick="removeExtraGuest(${guest.id})"
                    class="text-red-400 hover:text-red-300 p-2 rounded-lg hover:bg-red-500/10 transition-colors" title="Xóa trẻ em">
                    <span class="material-symbols-outlined text-sm">delete</span>
                </button>
                ` : ''}
            </div>
        </div>
    `).join('');

    // Add "Add more" button only if not at max
    const numAdults = parseInt(document.getElementById('num_adults')?.value) || 1;
    const numChildren = parseInt(document.getElementById('num_children')?.value) || 0;
    const totalGuests = numAdults + numChildren;
    
    if (totalGuests < ROOM_CONFIG.maxOccupancy) {
        list.innerHTML += `
            <button type="button" onclick="addExtraGuest()"
                class="w-full p-3 border border-dashed border-blue-500/50 rounded-lg text-blue-400 text-sm flex items-center justify-center gap-2 hover:bg-blue-500/10 transition-colors">
                <span class="material-symbols-outlined text-sm">add_circle</span>
                Thêm trẻ em
            </button>
        `;
    }

    updateExtraGuestsData();
}

// Set extra guest height from button click
function setExtraGuestHeight(id, height, type) {
    const guest = extraGuests.find(g => g.id === id);
    if (guest && !guest.isLocked) {
        guest.height = height;
        guest.type = type;
        renderExtraGuests();
        updateExtraGuestsData();
        calculateTotal();
        
        // Show warning if height >= 1.3m and no extra bed
        if (height >= 1.3) {
            const extraBedsInput = document.getElementById('extra_beds');
            if (extraBedsInput && parseInt(extraBedsInput.value) === 0 && !extraBedLocked) {
                const bedWarning = document.getElementById('extra_bed_warning');
                if (bedWarning) {
                    bedWarning.classList.remove('hidden');
                    bedWarning.innerHTML = '<span class="material-symbols-outlined text-sm text-amber-400">warning</span> Trẻ em cao từ 1.3m nên sử dụng giường phụ (650,000 VND/đêm)';
                }
            }
        }
    }
}

// Update hidden field with extra guests data
function updateExtraGuestsData() {
    const dataField = document.getElementById('extra_guests_data');
    if (dataField) {
        dataField.value = JSON.stringify(extraGuests);
    }
}

// Check booking mode based on selected room
function checkBookingMode() {
    const roomSelect = document.getElementById('room_type_id');
    const selectedOption = roomSelect.options[roomSelect.selectedIndex];

    if (selectedOption && selectedOption.dataset.bookingType === 'inquiry') {
        isInquiryMode = true;
        updateUIForInquiry();
    } else {
        isInquiryMode = false;
        updateUIForBooking();
    }
}

// Update UI for Inquiry Mode
function updateUIForInquiry() {
    // ========== STEP 1 FIELDS ==========
    // Hide Room Booking Fields
    const roomFields = document.getElementById('room_booking_fields');
    if (roomFields) roomFields.classList.add('hidden');

    // Show Apartment Inquiry Fields
    const apartmentFields = document.getElementById('apartment_inquiry_fields');
    if (apartmentFields) apartmentFields.classList.remove('hidden');

    // Update Step 1 Title
    const step1Title = document.getElementById('step1_title');
    if (step1Title) step1Title.textContent = translations.booking_form.checkin_title_apt;

    // Update Apartment Name in Summary
    const roomSelect = document.getElementById('room_type_id');
    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const apartmentNameEl = document.getElementById('inquiry_apartment_name');
    if (apartmentNameEl && selectedOption) {
        apartmentNameEl.textContent = selectedOption.text.split(' - ')[0] || '--';
    }

    // Set minimum date for preferred check-in
    const preferredCheckIn = document.getElementById('preferred_check_in');
    if (preferredCheckIn) {
        const today = new Date();
        preferredCheckIn.min = today.toISOString().split('T')[0];
        if (!preferredCheckIn.value) {
            preferredCheckIn.value = today.toISOString().split('T')[0];
        }
    }

    // ========== STEP 2 & 3 FIELDS ==========
    // Show Inquiry Fields in Step 2
    const inquiryFields = document.getElementById('inquiry_fields');
    if (inquiryFields) inquiryFields.classList.remove('hidden');

    // Show Inquiry Confirm Section in Step 3
    const inquiryConfirm = document.getElementById('inquiry_confirm_section');
    if (inquiryConfirm) inquiryConfirm.classList.remove('hidden');

    // Hide Payment Section in Step 3
    const paymentSection = document.getElementById('booking_payment_section');
    if (paymentSection) paymentSection.classList.add('hidden');

    // Hide Payment Summary Rows
    const paymentSummary = document.getElementById('payment_summary_rows');
    if (paymentSummary) paymentSummary.classList.add('hidden');

    // Update Submit Button
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnIcon = document.getElementById('submitBtnIcon');
    if (submitBtnText) submitBtnText.textContent = translations.booking_form.submit_btn_apt;
    if (submitBtnIcon) submitBtnIcon.textContent = 'send';

    // Update Step 3 Title
    const step3Title = document.getElementById('step3_title');
    if (step3Title) step3Title.textContent = translations.booking_form.confirm_title_apt;
}

// Update UI for Standard Booking
function updateUIForBooking() {
    // ========== STEP 1 FIELDS ==========
    // Show Room Booking Fields
    const roomFields = document.getElementById('room_booking_fields');
    if (roomFields) roomFields.classList.remove('hidden');

    // Hide Apartment Inquiry Fields
    const apartmentFields = document.getElementById('apartment_inquiry_fields');
    if (apartmentFields) apartmentFields.classList.add('hidden');

    // Update Step 1 Title
    const step1Title = document.getElementById('step1_title');
    if (step1Title) step1Title.textContent = translations.booking_form.checkin_title_room;

    // ========== STEP 2 & 3 FIELDS ==========
    // Hide Inquiry Fields
    const inquiryFields = document.getElementById('inquiry_fields');
    if (inquiryFields) inquiryFields.classList.add('hidden');

    // Hide Inquiry Confirm Section
    const inquiryConfirm = document.getElementById('inquiry_confirm_section');
    if (inquiryConfirm) inquiryConfirm.classList.add('hidden');

    // Show Payment Section
    const paymentSection = document.getElementById('booking_payment_section');
    if (paymentSection) paymentSection.classList.remove('hidden');

    // Show Payment Summary Rows
    const paymentSummary = document.getElementById('payment_summary_rows');
    if (paymentSummary) paymentSummary.classList.remove('hidden');

    // Update Submit Button
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnIcon = document.getElementById('submitBtnIcon');
    if (submitBtnText) submitBtnText.textContent = translations.booking_form.submit_btn_room;
    if (submitBtnIcon) submitBtnIcon.textContent = 'lock';

    // Update Step 3 Title
    const step3Title = document.getElementById('step3_title');
    if (step3Title) step3Title.textContent = translations.booking_form.confirm_title_room;
}

// Update checkout minimum date
function updateCheckoutMin() {
    const checkinDate = document.getElementById('check_in_date').value;
    if (checkinDate) {
        // Create date from input string (YYYY-MM-DD)
        // Be careful: new Date('2025-12-21') is UTC. 
        // We want simply "Next Day" string.
        const parts = checkinDate.split('-');
        const year = parseInt(parts[0]);
        const month = parseInt(parts[1]) - 1; // 0-indexed
        const day = parseInt(parts[2]);

        const nextDay = new Date(year, month, day + 1);

        const ndYear = nextDay.getFullYear();
        const ndMonth = String(nextDay.getMonth() + 1).padStart(2, '0');
        const ndDay = String(nextDay.getDate()).padStart(2, '0');
        const minCheckoutStr = `${ndYear}-${ndMonth}-${ndDay}`;

        document.getElementById('check_out_date').min = minCheckoutStr;

        // If checkout date is before the new minimum, update it
        const checkoutDate = document.getElementById('check_out_date').value;
        if (checkoutDate && checkoutDate < minCheckoutStr) {
            document.getElementById('check_out_date').value = minCheckoutStr;
        }
    }
}

// Calculate number of nights
function calculateNights() {
    const checkin = document.getElementById('check_in_date').value;
    const checkout = document.getElementById('check_out_date').value;

    if (checkin && checkout) {
        const date1 = new Date(checkin);
        const date2 = new Date(checkout);
        const diffTime = date2 - date1;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays > 0) {
            const nightsElement = document.getElementById('num_nights');
            if (nightsElement) {
                nightsElement.textContent = diffDays + ' ' + translations.common.nights;
            }
            return diffDays;
        }
    }

    const nightsElement = document.getElementById('num_nights');
    if (nightsElement) {
        nightsElement.textContent = '0 ' + translations.common.nights;
    }
    return 0;
}

// Calculate total price - Enhanced with new pricing structure
function calculateTotal() {
    if (isInquiryMode) return; // Skip calculation for inquiry

    const roomSelect = document.getElementById('room_type_id');
    const roomPriceDisplay = document.getElementById('room_price_display');
    const roomSubtotalDisplay = document.getElementById('room_subtotal_display');
    const estimatedTotal = document.getElementById('estimated_total');
    const estimatedTotalDisplay = document.getElementById('estimated_total_display');
    const priceTypeLabel = document.getElementById('price_type_label');
    const originalPriceDisplay = document.getElementById('original_price_display');
    const priceTypeUsed = document.getElementById('price_type_used');
    const extraGuestFeeRow = document.getElementById('extra_guest_fee_row');
    const extraGuestFeeDisplay = document.getElementById('extra_guest_fee_display');
    const extraBedFeeRow = document.getElementById('extra_bed_fee_row');
    const extraBedFeeDisplay = document.getElementById('extra_bed_fee_display');
    const extraGuestFeeInput = document.getElementById('extra_guest_fee');
    const extraBedFeeInput = document.getElementById('extra_bed_fee');

    if (!roomSelect || !roomPriceDisplay || !estimatedTotal) return 0;

    if (!roomSelect.value) {
        roomPriceDisplay.textContent = '0 ' + translations.common.currency;
        estimatedTotal.value = '0';
        if (estimatedTotalDisplay) estimatedTotalDisplay.textContent = '0 ' + translations.common.currency;
        if (originalPriceDisplay) originalPriceDisplay.classList.add('hidden');
        if (roomSubtotalDisplay) roomSubtotalDisplay.textContent = '0 ' + translations.common.currency;
        if (extraGuestFeeRow) extraGuestFeeRow.classList.add('hidden');
        if (extraBedFeeRow) extraBedFeeRow.classList.add('hidden');
        return 0;
    }

    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const category = selectedOption.dataset.category || 'room';
    const numAdults = parseInt(document.getElementById('num_adults')?.value) || 2;
    const nights = currentBookingType === 'short_stay' ? 1 : calculateNights();
    const numExtraBeds = parseInt(document.getElementById('extra_beds')?.value) || 0;

    // Update num_guests hidden field
    updateTotalGuests();

    // Get prices based on room category, booking type, and number of guests
    let price = 0;
    let priceLabel = translations.booking_form.price_for_2;
    let priceType = 'double';
    let originalPrice = parseFloat(selectedOption.dataset.pricePublished) || 0;

    // Check for short stay
    if (currentBookingType === 'short_stay' && category === 'room') {
        const shortStayPrice = parseFloat(selectedOption.dataset.priceShortStay) || 0;
        if (shortStayPrice > 0) {
            price = shortStayPrice;
            priceLabel = translations.booking_form.price_short_stay;
            priceType = 'short_stay';
            originalPrice = 0;
        }
    }
    // Standard pricing
    else if (category === 'room') {
        // Hotel Room pricing
        const priceSingle = parseFloat(selectedOption.dataset.priceSingle) || 0;
        const priceDouble = parseFloat(selectedOption.dataset.priceDouble) || 0;

        if (numAdults === 1 && priceSingle > 0) {
            price = priceSingle;
            priceLabel = translations.booking_form.price_single;
            priceType = 'single';
        } else {
            price = priceDouble || parseFloat(selectedOption.dataset.price) || 0;
            priceLabel = translations.booking_form.price_for_2;
            priceType = 'double';
        }
    } else {
        // Apartment pricing
        const priceDailySingle = parseFloat(selectedOption.dataset.priceDailySingle) || 0;
        const priceDailyDouble = parseFloat(selectedOption.dataset.priceDailyDouble) || 0;
        const priceAvgWeeklySingle = parseFloat(selectedOption.dataset.priceAvgWeeklySingle) || 0;
        const priceAvgWeeklyDouble = parseFloat(selectedOption.dataset.priceAvgWeeklyDouble) || 0;

        // Check if weekly rate applies (7+ nights)
        if (nights >= 7) {
            if (numAdults === 1 && priceAvgWeeklySingle > 0) {
                price = priceAvgWeeklySingle;
                priceLabel = translations.booking_form.price_weekly_1;
                priceType = 'weekly';
            } else if (priceAvgWeeklyDouble > 0) {
                price = priceAvgWeeklyDouble;
                priceLabel = translations.booking_form.price_weekly_2;
                priceType = 'weekly';
            } else {
                price = parseFloat(selectedOption.dataset.price) || 0;
                priceLabel = translations.booking_form.price_daily;
                priceType = 'daily';
            }
        } else {
            if (numAdults === 1 && priceDailySingle > 0) {
                price = priceDailySingle;
                priceLabel = translations.booking_form.price_daily_1;
                priceType = 'daily';
            } else if (priceDailyDouble > 0) {
                price = priceDailyDouble;
                priceLabel = translations.booking_form.price_daily_2;
                priceType = 'daily';
            } else {
                price = parseFloat(selectedOption.dataset.price) || 0;
                priceLabel = translations.booking_form.price_daily;
                priceType = 'daily';
            }
        }
        originalPrice = 0; // Apartments don't have published price
    }

    // Update price type label and badge
    if (priceTypeLabel) priceTypeLabel.textContent = priceLabel;
    if (priceTypeUsed) priceTypeUsed.value = priceType;

    // Show/hide original price if there's a discount
    if (originalPriceDisplay && originalPrice > 0 && originalPrice > price) {
        originalPriceDisplay.textContent = formatCurrency(originalPrice);
        originalPriceDisplay.classList.remove('hidden');
    } else if (originalPriceDisplay) {
        originalPriceDisplay.classList.add('hidden');
    }

    // Update room price display
    roomPriceDisplay.textContent = formatCurrency(price);

    // Calculate room subtotal
    const effectiveNights = currentBookingType === 'short_stay' ? 1 : (nights > 0 ? nights : 0);
    const roomSubtotal = price * effectiveNights;
    if (roomSubtotalDisplay) roomSubtotalDisplay.textContent = formatCurrency(roomSubtotal);

    // Calculate extra guest fees
    // LƯU Ý: Tính phụ thu cho người lớn thứ 3 và trẻ em
    let extraGuestFee = 0;
    const has3Adults = numAdults >= 3;
    
    // Nếu có 3 người lớn: tính phụ thu cho người thứ 3 (400,000 VND/đêm - như over1m3)
    if (has3Adults) {
        extraGuestFee += EXTRA_GUEST_FEES.over1m3; // 400,000 VND/đêm cho người lớn thứ 3
    }
    
    // Tính phụ thu trẻ em (nếu có)
    extraGuests.forEach(guest => {
        // Chỉ tính phí nếu là trẻ em (isAdult = false hoặc undefined)
        if (!guest.isAdult) {
            extraGuestFee += EXTRA_GUEST_FEES[guest.type] || 0;
        }
    });
    
    // Extra guest fee is per night (or per stay for short stay)
    extraGuestFee = extraGuestFee * effectiveNights;

    // Show/hide extra guest fee row
    if (extraGuestFeeRow) {
        if (extraGuestFee > 0) {
            extraGuestFeeRow.classList.remove('hidden');
            if (extraGuestFeeDisplay) extraGuestFeeDisplay.textContent = '+' + formatCurrency(extraGuestFee);
        } else {
            extraGuestFeeRow.classList.add('hidden');
        }
    }
    if (extraGuestFeeInput) extraGuestFeeInput.value = extraGuestFee;

    // Calculate extra bed fees (only for rooms, not apartments)
    let extraBedFee = 0;
    if (category === 'room' && numExtraBeds > 0) {
        extraBedFee = numExtraBeds * EXTRA_BED_PRICE * effectiveNights;
    }

    // Show/hide extra bed fee row
    if (extraBedFeeRow) {
        if (extraBedFee > 0) {
            extraBedFeeRow.classList.remove('hidden');
            if (extraBedFeeDisplay) extraBedFeeDisplay.textContent = '+' + formatCurrency(extraBedFee);
        } else {
            extraBedFeeRow.classList.add('hidden');
        }
    }
    if (extraBedFeeInput) extraBedFeeInput.value = extraBedFee;

    // Calculate total
    const total = roomSubtotal + extraGuestFee + extraBedFee;
    
    // Debug logging
    console.log('=== CALCULATE TOTAL DEBUG ===');
    console.log('Room Subtotal:', roomSubtotal);
    console.log('Extra Guest Fee:', extraGuestFee);
    console.log('Extra Bed Fee:', extraBedFee);
    console.log('TOTAL:', total);
    console.log('Formatted:', formatCurrency(total));
    
    estimatedTotal.value = total;
    if (estimatedTotalDisplay) {
        estimatedTotalDisplay.textContent = formatCurrency(total);
        console.log('Updated estimatedTotalDisplay to:', estimatedTotalDisplay.textContent);
    }

    // Update num_nights display for short stay
    const nightsElement = document.getElementById('num_nights');
    if (nightsElement && currentBookingType === 'short_stay') {
        nightsElement.textContent = translations.booking_form.short_stay_label;
    }

    // Store values for form submission
    roomSelect.setAttribute('data-calculated-total', total);
    roomSelect.setAttribute('data-calculated-nights', effectiveNights);
    roomSelect.setAttribute('data-room-price', price);
    roomSelect.setAttribute('data-price-type', priceType);
    roomSelect.setAttribute('data-room-subtotal', roomSubtotal);
    roomSelect.setAttribute('data-extra-guest-fee', extraGuestFee);
    roomSelect.setAttribute('data-extra-bed-fee', extraBedFee);

    return total;
}

// Format currency
function formatCurrency(amount) {
    if (isNaN(amount) || amount === null || amount === undefined) {
        return '0 ' + translations.common.currency;
    }

    try {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    } catch (error) {
        console.error('Currency formatting error:', error);
        return new Intl.NumberFormat('vi-VN').format(amount) + ' ' + translations.common.currency;
    }
}

// ========== APARTMENT RENT MODE FUNCTIONS ==========

// Toggle between rent by month and rent by date
function toggleRentMode() {
    const mode = document.getElementById('rent_mode').value;
    const byMonthSection = document.getElementById('rent_by_month');
    const byDateSection = document.getElementById('rent_by_date');

    if (mode === 'by_month') {
        byMonthSection.classList.remove('hidden');
        byDateSection.classList.add('hidden');
        calculateEndDate(); // Recalculate when switching
    } else {
        byMonthSection.classList.add('hidden');
        byDateSection.classList.remove('hidden');
        // Clear and set minimum for manual end date
        const preferredCheckIn = document.getElementById('preferred_check_in').value;
        if (preferredCheckIn) {
            document.getElementById('manual_end_date').min = preferredCheckIn;
        }
    }
    updateDurationType();
    updateInquirySummary();
}

// Calculate end date from months
function calculateEndDate() {
    const preferredCheckIn = document.getElementById('preferred_check_in').value;
    const monthsSelect = document.getElementById('duration_months');
    const calculatedEndDate = document.getElementById('calculated_end_date');

    if (!preferredCheckIn || !monthsSelect) return;

    const months = parseInt(monthsSelect.value) || 1;
    const startDate = new Date(preferredCheckIn);
    const endDate = new Date(startDate);
    endDate.setMonth(endDate.getMonth() + months);

    // Format as mm/dd/yyyy for display
    const day = String(endDate.getDate()).padStart(2, '0');
    const month = String(endDate.getMonth() + 1).padStart(2, '0');
    const year = endDate.getFullYear();

    if (calculatedEndDate) {
        calculatedEndDate.value = `${day}/${month}/${year}`;
    }

    updateDurationType();
    updateInquirySummary();
}

// Calculate end date from number of days
function calculateEndDateFromDays() {
    const preferredCheckIn = document.getElementById('preferred_check_in').value;
    const daysInput = document.getElementById('duration_days');
    const manualEndDate = document.getElementById('manual_end_date');

    if (!preferredCheckIn || !daysInput.value) return;

    const days = parseInt(daysInput.value) || 0;
    if (days <= 0) return;

    const startDate = new Date(preferredCheckIn);
    const endDate = new Date(startDate);
    endDate.setDate(endDate.getDate() + days);

    // Set the manual end date field
    const year = endDate.getFullYear();
    const month = String(endDate.getMonth() + 1).padStart(2, '0');
    const day = String(endDate.getDate()).padStart(2, '0');

    if (manualEndDate) {
        manualEndDate.value = `${year}-${month}-${day}`;
    }

    updateDurationType();
    updateInquirySummary();
}

// Calculate days from selected end date
function calculateDaysFromEndDate() {
    const preferredCheckIn = document.getElementById('preferred_check_in').value;
    const manualEndDate = document.getElementById('manual_end_date').value;
    const daysInput = document.getElementById('duration_days');

    if (!preferredCheckIn || !manualEndDate) return;

    const startDate = new Date(preferredCheckIn);
    const endDate = new Date(manualEndDate);
    const diffTime = endDate - startDate;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays > 0 && daysInput) {
        daysInput.value = diffDays;
    }

    updateDurationType();
    updateInquirySummary();
}

// Update hidden duration_type field based on current selection
function updateDurationType() {
    const mode = document.getElementById('rent_mode')?.value || 'by_month';
    const durationTypeField = document.getElementById('duration_type');

    if (mode === 'by_month') {
        const months = document.getElementById('duration_months')?.value || '1';
        durationTypeField.value = months + '_month' + (months > 1 ? 's' : '');
    } else {
        // For by_date mode, calculate approximate months or use 'custom'
        const preferredCheckIn = document.getElementById('preferred_check_in').value;
        const manualEndDate = document.getElementById('manual_end_date').value;

        if (preferredCheckIn && manualEndDate) {
            const startDate = new Date(preferredCheckIn);
            const endDate = new Date(manualEndDate);
            const diffTime = endDate - startDate;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            durationTypeField.value = 'custom_' + diffDays + '_days';
        } else {
            durationTypeField.value = 'custom';
        }
    }
}

// Update inquiry summary display
function updateInquirySummary() {
    const mode = document.getElementById('rent_mode')?.value || 'by_month';
    const durationDisplay = document.getElementById('inquiry_duration_display');
    const endDateDisplay = document.getElementById('inquiry_end_date_display');
    const preferredCheckIn = document.getElementById('preferred_check_in').value;

    if (!durationDisplay || !endDateDisplay) return;

    if (mode === 'by_month') {
        const months = document.getElementById('duration_months')?.value || '1';
        durationDisplay.textContent = months + ' ' + translations.common.month;

        // Get calculated end date
        const calculatedEndDate = document.getElementById('calculated_end_date')?.value;
        endDateDisplay.textContent = calculatedEndDate || '--';
    } else {
        const days = document.getElementById('duration_days')?.value;
        const manualEndDate = document.getElementById('manual_end_date')?.value;

        if (days) {
            durationDisplay.textContent = days + ' ' + translations.common.day;
        } else if (manualEndDate && preferredCheckIn) {
            const startDate = new Date(preferredCheckIn);
            const endDate = new Date(manualEndDate);
            const diffDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
            durationDisplay.textContent = diffDays + ' ' + translations.common.day;
        } else {
            durationDisplay.textContent = '--';
        }

        if (manualEndDate) {
            const d = new Date(manualEndDate);
            endDateDisplay.textContent = `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
        } else {
            endDateDisplay.textContent = '--';
        }
    }
}

// Update apartment name in summary
document.addEventListener('change', function (e) {
    if (e.target.id === 'preferred_check_in') {
        calculateEndDate();
        // Set min for manual end date
        const manualEndDate = document.getElementById('manual_end_date');
        if (manualEndDate) {
            manualEndDate.min = e.target.value;
        }
    }
});

// Navigate to next step
async function nextStep(step) {
    // Validate current step
    if (!validateStep(currentStep)) {
        return;
    }

    // ========== ANTI-SPAM: Check before step 3 ==========
    if (step === 3 && !isInquiryMode) {
        // Show loading
        const continueBtn = event?.target;
        const originalText = continueBtn?.innerHTML;
        if (continueBtn) {
            continueBtn.disabled = true;
            continueBtn.innerHTML = '<span class="material-symbols-outlined animate-spin">progress_activity</span> Đang kiểm tra...';
        }

        try {
            const validationResponse = await fetch('./api/validate-booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    check_in_date: document.getElementById('check_in_date')?.value,
                    check_out_date: document.getElementById('check_out_date')?.value
                })
            });
            
            const validation = await validationResponse.json();
            
            if (!validation.allowed) {
                // Show conflict modal and stop navigation
                showBookingConflictModal(validation);
                if (continueBtn) {
                    continueBtn.disabled = false;
                    continueBtn.innerHTML = originalText;
                }
                return; // Stop - don't go to step 3
            }
        } catch (error) {
            console.error('Pre-validation error:', error);
            // Continue if validation API fails (don't block legitimate users)
        } finally {
            if (continueBtn) {
                continueBtn.disabled = false;
                continueBtn.innerHTML = originalText;
            }
        }
    }
    // ========== END ANTI-SPAM ==========

    // Hide current step
    document.getElementById('step' + currentStep).classList.remove('active');
    document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.remove('active');
    document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.add('completed');

    // Update connector for completed step
    const completedConnector = document.querySelector(`.step-connector[data-from="${currentStep}"]`);
    if (completedConnector) {
        completedConnector.classList.remove('active');
        completedConnector.classList.add('completed');
    }

    // Show next step
    currentStep = step;
    document.getElementById('step' + currentStep).classList.add('active');
    document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.add('active');

    // Update connector for active step (if not last step)
    const activeConnector = document.querySelector(`.step-connector[data-from="${currentStep}"]`);
    if (activeConnector) {
        activeConnector.classList.add('active');
    }

    // Update summary if going to step 3
    if (step === 3) {
        updateSummary();
    }

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Navigate to previous step
function prevStep(step) {
    // Hide current step
    document.getElementById('step' + currentStep).classList.remove('active');
    document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.remove('active');

    // Reset connector from previous step to current
    const prevConnector = document.querySelector(`.step-connector[data-to="${currentStep}"]`);
    if (prevConnector) {
        prevConnector.classList.remove('completed');
        prevConnector.classList.add('active');
    }

    // Reset connector from current step (if any)
    const currentConnector = document.querySelector(`.step-connector[data-from="${currentStep}"]`);
    if (currentConnector) {
        currentConnector.classList.remove('active', 'completed');
    }

    // Show previous step
    currentStep = step;
    document.getElementById('step' + currentStep).classList.add('active');
    document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.add('active');
    document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.remove('completed');

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Validate step
function validateStep(step) {
    if (step === 1) {
        const roomType = document.getElementById('room_type_id').value;

        if (!roomType) {
            alert(translations.booking_form.select_room_or_apt);
            return false;
        }

        // Get today's date string for comparison
        const d = new Date();
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const todayStr = `${year}-${month}-${day}`;

        if (isInquiryMode) {
            // ========== APARTMENT VALIDATION ==========
            const preferredCheckIn = document.getElementById('preferred_check_in').value;
            const numAdults = document.getElementById('inquiry_num_adults').value;

            if (!preferredCheckIn) {
                alert(translations.booking_form.select_est_checkin);
                return false;
            }

            if (preferredCheckIn < todayStr) {
                alert(translations.booking_form.checkin_not_past);
                return false;
            }

            if (!numAdults || numAdults < 1) {
                alert(translations.booking_form.min_adults);
                return false;
            }
        } else {
            // ========== ROOM VALIDATION ==========
            const checkin = document.getElementById('check_in_date').value;
            const checkout = document.getElementById('check_out_date').value;
            const numAdults = document.getElementById('num_adults')?.value || document.getElementById('num_guests').value;

            if (!checkin) {
                alert(translations.booking_form.select_checkin_date);
                return false;
            }

            if (checkin < todayStr) {
                alert(translations.booking_form.checkin_not_past);
                return false;
            }

            // Only validate checkout for standard bookings
            if (currentBookingType !== 'short_stay') {
                if (!checkout) {
                    alert(translations.booking_form.select_checkout_date);
                    return false;
                }

                if (new Date(checkout) <= new Date(checkin)) {
                    alert(translations.booking_form.checkout_after_checkin);
                    return false;
                }

                if (checkout <= todayStr) {
                    alert(translations.booking_form.checkout_future);
                    return false;
                }
            }

            if (!numAdults || numAdults < 1) {
                alert(translations.booking_form.invalid_guests);
                return false;
            }
        }

        return true;
    }

    if (step === 2) {
        const name = document.getElementById('guest_name').value.trim();
        const phone = document.getElementById('guest_phone').value.trim();
        const email = document.getElementById('guest_email').value.trim();

        if (!name || !phone || !email) {
            alert(translations.booking_form.fill_required);
            return false;
        }

        return true;
    }

    return true;
}

// Update summary
function updateSummary() {
    const roomSelect = document.getElementById('room_type_id');
    const roomName = roomSelect.options[roomSelect.selectedIndex].text.split(' - ')[0];

    // IDs must match index.php
    document.getElementById('summary_room_type').textContent = roomName;
    document.getElementById('summary_name').textContent = document.getElementById('guest_name').value;
    document.getElementById('summary_email').textContent = document.getElementById('guest_email').value;
    document.getElementById('summary_phone').textContent = document.getElementById('guest_phone').value;

    if (isInquiryMode) {
        // ========== APARTMENT INQUIRY SUMMARY ==========
        const numAdults = document.getElementById('inquiry_num_adults').value || 1;
        const numChildren = document.getElementById('inquiry_num_children').value || 0;
        const preferredCheckIn = document.getElementById('preferred_check_in').value;
        const rentMode = document.getElementById('rent_mode')?.value || 'by_month';

        // Update guest count
        let guestText = numAdults + ' ' + (numAdults > 1 ? translations.common.adults : translations.common.adult);
        if (numChildren > 0) {
            guestText += ', ' + numChildren + ' ' + (numChildren > 1 ? translations.common.children : translations.common.child);
        }
        document.getElementById('summary_guests').textContent = guestText;

        // Update labels for apartment
        document.getElementById('summary_checkin_label').textContent = 'Ngày dự kiến:';
        document.getElementById('summary_checkin').textContent = formatDate(preferredCheckIn);

        // Show duration based on rent mode
        document.getElementById('summary_checkout_label').textContent = 'Thời gian thuê:';

        if (rentMode === 'by_month') {
            const months = document.getElementById('duration_months')?.value || '1';
            document.getElementById('summary_checkout').textContent = months + ' tháng';
        } else {
            const days = document.getElementById('duration_days')?.value;
            const manualEndDate = document.getElementById('manual_end_date')?.value;

            if (days) {
                document.getElementById('summary_checkout').textContent = days + ' ngày';
            } else if (manualEndDate && preferredCheckIn) {
                const startDate = new Date(preferredCheckIn);
                const endDate = new Date(manualEndDate);
                const diffDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
                document.getElementById('summary_checkout').textContent = diffDays + ' ngày';
            } else {
                document.getElementById('summary_checkout').textContent = '--';
            }
        }

        // Hide nights row for inquiry
        document.getElementById('summary_nights_row').style.display = 'none';

    } else {
        // ========== ROOM BOOKING SUMMARY ==========
        const numAdults = document.getElementById('num_adults')?.value || document.getElementById('num_guests')?.value || 2;
        const numChildren = document.getElementById('num_children')?.value || 0;
        let guestText = numAdults + ' ' + (numAdults > 1 ? translations.common.adults : translations.common.adult);
        if (numChildren > 0) {
            guestText += ', ' + numChildren + ' ' + (numChildren > 1 ? translations.common.children : translations.common.child);
        }
        if (extraGuests.length > 0) {
            guestText += ' + ' + extraGuests.length + ' ' + (extraGuests.length > 1 ? translations.common.guests : translations.common.guest_add);
        }
        document.getElementById('summary_guests').textContent = guestText;

        // Reset labels
        document.getElementById('summary_checkin_label').textContent = 'Nhận phòng:';
        document.getElementById('summary_checkout_label').textContent = currentBookingType === 'short_stay' ? 'Loại hình:' : 'Trả phòng:';
        document.getElementById('summary_nights_label').textContent = 'Số đêm:';

        // Checkin/Checkout/Nights
        document.getElementById('summary_checkin').textContent = formatDate(document.getElementById('check_in_date').value);

        if (currentBookingType === 'short_stay') {
            document.getElementById('summary_checkout').textContent = 'Nghỉ ngắn hạn (dưới 4h)';
            document.getElementById('summary_nights').textContent = '1 lượt';
        } else {
            document.getElementById('summary_checkout').textContent = formatDate(document.getElementById('check_out_date').value);
            document.getElementById('summary_nights').textContent = document.getElementById('num_nights').textContent;
        }

        // Show nights row
        document.getElementById('summary_nights_row').style.display = 'flex';

        // Payment summaries - get from calculated values
        const roomSelect = document.getElementById('room_type_id');
        const roomSubtotal = parseFloat(roomSelect.getAttribute('data-room-subtotal')) || 0;
        const extraGuestFee = parseFloat(roomSelect.getAttribute('data-extra-guest-fee')) || 0;
        const extraBedFee = parseFloat(roomSelect.getAttribute('data-extra-bed-fee')) || 0;
        const total = parseFloat(roomSelect.getAttribute('data-calculated-total')) || 0;

        document.getElementById('summary_subtotal').textContent = formatCurrency(roomSubtotal);

        // Show/hide extra guest fee row
        const extraGuestRow = document.getElementById('summary_extra_guest_row');
        if (extraGuestRow) {
            if (extraGuestFee > 0) {
                extraGuestRow.style.display = 'flex';
                document.getElementById('summary_extra_guest_fee').textContent = '+' + formatCurrency(extraGuestFee);
            } else {
                extraGuestRow.style.display = 'none';
            }
        }

        // Show/hide extra bed fee row
        const extraBedRow = document.getElementById('summary_extra_bed_row');
        if (extraBedRow) {
            if (extraBedFee > 0) {
                extraBedRow.style.display = 'flex';
                document.getElementById('summary_extra_bed_fee').textContent = '+' + formatCurrency(extraBedFee);
            } else {
                extraBedRow.style.display = 'none';
            }
        }

        const promoDiscount = document.getElementById('discount_amount_input').value;

        let finalTotal = total;
        if (promoDiscount && promoDiscount > 0) {
            finalTotal = total - promoDiscount;
            document.getElementById('summary_discount').textContent = '-' + formatCurrency(promoDiscount);
        }
        document.getElementById('summary_total').textContent = formatCurrency(finalTotal);
    }
}

// Format date - mm/dd/yyyy format
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

// Handle form submission
async function handleSubmit(e) {
    e.preventDefault();

    // Validate terms
    if (!document.getElementById('agree_terms').checked) {
        alert(translations.booking_form.agree_terms_alert);
        return;
    }

    // Get form data
    const formData = new FormData(e.target);
    const formObject = Object.fromEntries(formData);

    // Build data object based on booking type
    let data = {
        ...formObject,
        booking_type: isInquiryMode ? 'inquiry' : 'instant'
    };

    if (isInquiryMode) {
        // ========== APARTMENT INQUIRY DATA ==========
        const preferredCheckIn = document.getElementById('preferred_check_in').value;
        const durationType = document.getElementById('duration_type').value;
        const numAdults = document.getElementById('inquiry_num_adults').value;
        const numChildren = document.getElementById('inquiry_num_children').value;
        const inquiryMsg = document.getElementById('inquiry_message')?.value || '';
        const rentMode = document.getElementById('rent_mode')?.value || 'by_month';

        // Calculate check-out date based on rent mode
        let checkOutDate = preferredCheckIn;
        if (preferredCheckIn) {
            const startDate = new Date(preferredCheckIn);

            if (rentMode === 'by_month') {
                // By month: use duration_months select
                const months = parseInt(document.getElementById('duration_months')?.value) || 1;
                const endDate = new Date(startDate);
                endDate.setMonth(endDate.getMonth() + months);
                checkOutDate = endDate.toISOString().split('T')[0];
            } else {
                // By date: use manual_end_date or calculate from days
                const manualEndDate = document.getElementById('manual_end_date')?.value;
                if (manualEndDate) {
                    checkOutDate = manualEndDate;
                } else {
                    const days = parseInt(document.getElementById('duration_days')?.value) || 30;
                    const endDate = new Date(startDate);
                    endDate.setDate(endDate.getDate() + days);
                    checkOutDate = endDate.toISOString().split('T')[0];
                }
            }
        }

        data = {
            ...data,
            room_type_id: formObject.room_type_id,
            check_in_date: preferredCheckIn,
            check_out_date: checkOutDate,
            num_guests: numAdults,
            num_adults: numAdults,
            num_children: numChildren,
            duration_type: durationType,
            message: inquiryMsg,
            num_nights: 0 // Not applicable for inquiry
        };
    } else {
        // ========== ROOM BOOKING DATA ==========
        const roomSelect = document.getElementById('room_type_id');
        const numAdults = parseInt(document.getElementById('num_adults')?.value) || 2;
        const numChildren = parseInt(document.getElementById('num_children')?.value) || 0;
        const numExtraBeds = parseInt(document.getElementById('extra_beds')?.value) || 0;
        const numNights = currentBookingType === 'short_stay' ? 1 : calculateNights();
        const roomPrice = parseFloat(roomSelect.getAttribute('data-room-price')) || 0;
        const calculatedTotal = parseFloat(roomSelect.getAttribute('data-calculated-total')) || 0;
        const priceType = roomSelect.getAttribute('data-price-type') || 'double';
        const roomSubtotal = parseFloat(roomSelect.getAttribute('data-room-subtotal')) || 0;
        const extraGuestFee = parseFloat(roomSelect.getAttribute('data-extra-guest-fee')) || 0;
        const extraBedFee = parseFloat(roomSelect.getAttribute('data-extra-bed-fee')) || 0;

        data.booking_type = currentBookingType; // 'standard' or 'short_stay'
        data.num_adults = numAdults;
        data.num_children = numChildren;
        data.num_guests = numAdults;
        data.num_nights = numNights;
        data.calculated_nights = numNights;
        data.room_price = roomPrice;
        data.room_subtotal = roomSubtotal;
        data.calculated_total = calculatedTotal;
        data.price_type_used = priceType;
        data.extra_beds = numExtraBeds;
        data.extra_bed_fee = extraBedFee;
        data.extra_guest_fee = extraGuestFee;
        data.extra_guests_data = JSON.stringify(extraGuests);
    }

    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const originalText = submitBtnText.textContent;

    // ========== PRE-SUBMIT VALIDATION (Anti-spam) ==========
    // Check if user has existing bookings before submitting
    let validation;
    try {
        const validationResponse = await Promise.race([
            fetch('./api/validate-booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    check_in_date: data.check_in_date,
                    check_out_date: data.check_out_date
                })
            }),
            // Timeout after 5 seconds
            new Promise((_, reject) => 
                setTimeout(() => reject(new Error('Validation timeout')), 5000)
            )
        ]);

        const validationData = await validationResponse.json();
        validation = validationData;

        if (!validation.allowed) {
            // Show error modal with existing bookings
            showBookingConflictModal(validation);
            submitBtn.disabled = false;
            submitBtnText.textContent = originalText;
            return; // Stop submission
        }
    } catch (error) {
        console.error('Pre-validation error:', error);
        // Continue with booking if validation API fails or times out (don't block legitimate users)
        showToast('Không thể kiểm tra đặt phòng. Tiếp tục xử lý...', 'info');
    }
    // ========== END PRE-SUBMIT VALIDATION ==========

    // Disable submit button
    submitBtn.disabled = true;
    submitBtnText.textContent = translations.common.processing;

    try {
        // Always use create_booking.php - it handles both instant and inquiry bookings
        const apiUrl = './api/create_booking.php';

        // Send request
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            // Check booking type from response (more reliable than front-end state)
            if (result.booking_type === 'inquiry') {
                // Success for inquiry - Show alert and redirect home
                alert(result.message || 'Yêu cầu tư vấn của bạn đã được gửi thành công!');
                window.location.href = '../index.php';
            } else {
                // Success for instant booking
                if (data.payment_method === 'vnpay' && result.payment_url) {
                    window.location.href = result.payment_url;
                } else {
                    if (result.is_guest) {
                        window.location.href = './confirmation.php?booking_code=' + result.booking_code;
                    } else {
                        window.location.href = '../profile/bookings.php';
                    }
                }
            }
        } else {
            // Handle specific error types from backend
            if (result.existing_bookings || result.overlapping_bookings) {
                showBookingConflictModal(result);
            } else if (result.retry_after) {
                showToast(`Vui lòng đợi ${result.retry_after} giây trước khi đặt tiếp`, 'error');
            } else if (result.message) {
                showToast(result.message, 'error');
            } else {
                alert('Có lỗi xảy ra. Vui lòng thử lại.');
            }
            submitBtn.disabled = false;
            submitBtnText.textContent = originalText;
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại.');
        submitBtn.disabled = false;
        submitBtnText.textContent = originalText;
    }
}

// Show Booking Conflict Modal (Anti-spam)
function showBookingConflictModal(result) {
    // Close any existing modal first
    closeBookingConflictModal();
    
    const modal = document.createElement('div');
    modal.id = 'bookingConflictModal';
    modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg border border-red-500/30 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-red-600 to-red-700 sticky top-0 rounded-t-2xl">
                <h3 class="font-bold text-lg text-white flex items-center gap-2">
                    <span class="material-symbols-outlined">warning</span>
                    Không thể đặt phòng
                </h3>
                <button onclick="closeBookingConflictModal()" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg transition-colors" aria-label="Đóng">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <p class="text-gray-700 dark:text-gray-300 mb-4">${result.message || 'Bạn đang có đặt phòng chưa hoàn tất.'}</p>
                </div>
                
                ${result.pending_bookings && result.pending_bookings.length > 0 ? `
                    <div class="mb-4">
                        <h4 class="font-semibold text-red-600 mb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">list</span>
                            Đặt phòng chưa hoàn tất (${result.pending_bookings.length}):
                        </h4>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            ${result.pending_bookings.map(booking => `
                                <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="font-semibold text-sm text-blue-600 dark:text-blue-400">Mã: ${booking.booking_code}</span>
                                        <span class="text-xs px-2 py-1 rounded ${
                                            booking.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                            booking.status === 'confirmed' ? 'bg-blue-100 text-blue-800' : 
                                            booking.status === 'checked_in' ? 'bg-green-100 text-green-800' :
                                            'bg-gray-100 text-gray-800'
                                        }">${
                                            booking.status === 'pending' ? 'Chờ xác nhận' : 
                                            booking.status === 'confirmed' ? 'Đã xác nhận' : 
                                            booking.status === 'checked_in' ? 'Đang ở' :
                                            booking.status
                                        }</span>
                                    </div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                        <div>📅 ${booking.check_in_date} → ${booking.check_out_date}</div>
                                        <div>💰 ${parseInt(booking.total_amount).toLocaleString()} VND</div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
                
                ${result.overlapping_bookings && result.overlapping_bookings.length > 0 ? `
                    <div class="mb-4">
                        <h4 class="font-semibold text-orange-600 mb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">event_busy</span>
                            Trùng lịch sử đặt (${result.overlapping_bookings.length}):
                        </h4>
                        <div class="space-y-2">
                            ${result.overlapping_bookings.map(booking => `
                                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3 border border-orange-200 dark:border-orange-800">
                                    <div class="text-sm text-orange-900 dark:text-orange-100">
                                        <strong>Mã ${booking.booking_code}</strong>: 
                                        ${booking.check_in_date} → ${booking.check_out_date}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
                
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h5 class="font-semibold text-blue-800 dark:text-blue-300 mb-2 text-sm">Bạn cần làm gì?</h5>
                    <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1 list-disc list-inside">
                        <li>Hoàn tất thanh toán cho đặt phòng cũ (nếu chưa thanh toán)</li>
                        <li>Hủy đặt phòng cũ qua trang Quản lý đặt phòng</li>
                        <li>Liên hệ lễ tân: <strong>(0251) 391.8888</strong> để được hỗ trợ</li>
                        <li>Chọn ngày khác không trùng với đặt phòng hiện có</li>
                    </ul>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3 sticky bottom-0 bg-white dark:bg-gray-800 rounded-b-2xl">
                <button onclick="closeBookingConflictModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    Đóng
                </button>
                <a href="../profile/bookings.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">list</span>
                    Xem đặt phòng của tôi
                </a>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden'; // Prevent scrolling
    
    // Close modal when clicking outside
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeBookingConflictModal();
        }
    });
    
    // Handle ESC key
    const handleEsc = (e) => {
        if (e.key === 'Escape') {
            closeBookingConflictModal();
        }
    };
    document.addEventListener('keydown', handleEsc);
    
    // Store cleanup function
    modal.cleanup = () => {
        document.removeEventListener('keydown', handleEsc);
    };
}

function closeBookingConflictModal() {
    const modal = document.getElementById('bookingConflictModal');
    if (modal) {
        if (modal.cleanup) {
            modal.cleanup();
        }
        modal.remove();
        document.body.style.overflow = '';
    }
}

// Apply Promotion Code
let appliedPromotion = null;

async function applyPromoCode() {
    // Only for booking mode
    if (isInquiryMode) return;

    // Guest Restriction
    if (typeof IS_LOGGED_IN !== 'undefined' && !IS_LOGGED_IN) {
        const messageDiv = document.getElementById('promo_message');
        messageDiv.innerHTML = `
            <div class="text-yellow-500 flex items-start gap-2 text-sm mt-2">
                <span class="material-symbols-outlined text-base mt-0.5">lock</span>
                <span>${translations.booking_form.guest_promo_lock} <a href="../auth/login.php" class="underline font-bold hover:text-yellow-400">${translations.auth.login}</a> ${translations.booking_form.guest_promo_lock_end}</span>
            </div>
        `;
        return;
    }

    const promoCode = document.getElementById('promo_code').value.trim().toUpperCase();
    const messageDiv = document.getElementById('promo_message');

    if (!promoCode) {
        messageDiv.innerHTML = '<span class="text-red-600">Vui lòng nhập mã giảm giá</span>';
        return;
    }

    // Get current total
    const totalAmount = parseFloat(document.getElementById('estimated_total').value) || 0;
    const roomTypeId = document.getElementById('room_type_id').value;

    if (totalAmount <= 0) {
        messageDiv.innerHTML = '<span class="text-red-600">Vui lòng chọn ngày trước khi áp dụng mã</span>';
        return;
    }

    messageDiv.innerHTML = '<span class="text-gray-600">Đang kiểm tra...</span>';

    try {
        const formData = new FormData();
        formData.append('promo_code', promoCode);
        formData.append('total_amount', totalAmount);
        formData.append('room_type_id', roomTypeId);

        const response = await fetch('./api/apply-promotion.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            appliedPromotion = result;

            // Update display
            document.getElementById('summary_subtotal').textContent = formatCurrency(result.original_amount);
            document.getElementById('summary_discount').textContent = '-' + formatCurrency(result.discount_amount);
            document.getElementById('summary_total').textContent = formatCurrency(result.final_amount);
            document.getElementById('discount_row').style.display = 'flex';

            // Update hidden inputs
            document.getElementById('promotion_code_input').value = result.promotion.code;
            document.getElementById('discount_amount_input').value = result.discount_amount;

            // Show success message
            messageDiv.innerHTML = `
                <div class="flex items-center gap-2 text-green-600">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span>${result.message} Giảm ${formatCurrency(result.discount_amount)}</span>
                </div>
            `;

            // Disable input
            document.getElementById('promo_code').disabled = true;

        } else {
            messageDiv.innerHTML = `<span class="text-red-600">${result.message}</span>`;
            removePromotion();
        }

    } catch (error) {
        console.error('Error:', error);
        messageDiv.innerHTML = '<span class="text-red-600">Có lỗi xảy ra khi áp dụng mã</span>';
    }
}

function removePromotion() {
    appliedPromotion = null;

    const totalAmount = parseFloat(document.getElementById('estimated_total').value) || 0;

    document.getElementById('summary_subtotal').textContent = formatCurrency(totalAmount);
    document.getElementById('summary_total').textContent = formatCurrency(totalAmount);
    document.getElementById('discount_row').style.display = 'none';

    document.getElementById('promotion_code_input').value = '';
    document.getElementById('discount_amount_input').value = '0';
    document.getElementById('promo_code').disabled = false;
}
// Update toggle button text based on guest count
function updateToggleButtonText() {
    const btn = document.getElementById('toggle_extra_guests_btn');
    if (!btn) return;
    
    if (extraGuests.length > 0) {
        btn.innerHTML = `
            <span class="material-symbols-outlined text-base">remove</span>
            Ẩn (${extraGuests.length})
        `;
    } else {
        btn.innerHTML = `
            <span class="material-symbols-outlined text-base">add</span>
            Thêm khách
        `;
    }
}

// Show toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-[9999] transition-all duration-300 transform translate-x-0 ${
        type === 'success' ? 'bg-green-600 text-white' :
        type === 'error' ? 'bg-red-600 text-white' :
        'bg-blue-600 text-white'
    }`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}