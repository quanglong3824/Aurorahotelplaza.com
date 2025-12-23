// Booking Form JavaScript

let currentStep = 1;
let isInquiryMode = false;
let currentBookingType = 'standard'; // 'standard' or 'short_stay'
let extraGuests = []; // Array to store extra guests with heights
let suggestionDismissed = false; // Track if user dismissed suggestion

// ========== PRICING CONSTANTS (must match backend) ==========
const EXTRA_BED_PRICE = 650000; // 650,000đ/đêm
const EXTRA_GUEST_FEES = {
    under1m: 0,       // Dưới 1m: Miễn phí (bao gồm ăn sáng)
    '1m_1m3': 200000, // 1m - 1m3: 200,000đ/đêm (bao gồm ăn sáng)
    over1m3: 400000   // Trên 1m3: 400,000đ/đêm (bao gồm ăn sáng)
};

// ========== SMART SUGGESTION ALGORITHM ==========
/**
 * Thuật toán gợi ý phụ thu thông minh
 * 
 * LOGIC:
 * 1. Lấy thông tin phòng: max_adults, max_children, category
 * 2. Lấy số khách hiện tại: num_adults, num_children
 * 3. Tính tổng khách: total_guests = num_adults + num_children
 * 4. So sánh với giới hạn phòng và đưa ra gợi ý:
 * 
 * CASES:
 * - Case A: num_adults >= max_adults && num_children >= 1
 *   → Gợi ý: "Bạn có trẻ em đi cùng. Vui lòng khai báo chiều cao để tính phụ thu."
 *   → Action: Mở form thêm khách với số lượng = num_children
 * 
 * - Case B: num_adults >= max_adults && num_children >= 2
 *   → Gợi ý: "Bạn có nhiều trẻ em. Có thể cần thêm giường phụ."
 *   → Action: Gợi ý thêm giường + mở form thêm khách
 * 
 * - Case C: total_guests > max_occupancy
 *   → Gợi ý: "Số khách vượt quá sức chứa phòng. Vui lòng khai báo phụ thu."
 *   → Action: Mở form thêm khách
 * 
 * - Case D: num_children > 0 && extraGuests.length === 0
 *   → Gợi ý nhẹ: "Bạn có trẻ em đi cùng. Nhớ khai báo chiều cao nếu cần phụ thu."
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
    
    // Update border color based on type
    box.classList.remove('border-amber-500/30', 'border-red-500/30', 'border-blue-500/30');
    box.classList.remove('bg-amber-500/10', 'bg-red-500/10', 'bg-blue-500/10');
    
    if (suggestion.type === 'warning') {
        box.classList.add('border-red-500/30', 'bg-red-500/10');
    } else if (suggestion.type === 'hint') {
        box.classList.add('border-blue-500/30', 'bg-blue-500/10');
    } else {
        box.classList.add('border-amber-500/30', 'bg-amber-500/10');
    }
    
    // Build action buttons
    actions.innerHTML = suggestion.actions.map(act => {
        if (act.action === 'dismiss') {
            return `<button type="button" onclick="dismissSuggestion()" 
                class="px-3 py-1.5 text-xs bg-gray-600/50 hover:bg-gray-600 text-white rounded-lg transition-colors">
                ${act.label}
            </button>`;
        }
        return `<button type="button" onclick="handleSuggestionAction('${act.action}', ${act.count || 0})" 
            class="px-3 py-1.5 text-xs bg-amber-500/80 hover:bg-amber-500 text-white rounded-lg transition-colors font-medium">
            <span class="material-symbols-outlined text-sm align-middle mr-1">${act.action === 'addBed' ? 'single_bed' : 'person_add'}</span>
            ${act.label}
        </button>`;
    }).join('');
    
    box.classList.remove('hidden');
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
        numAdultsInput.addEventListener('change', calculateTotal);
    }
    if (numChildrenInput) {
        numChildrenInput.addEventListener('change', calculateTotal);
    }
    if (extraBedsInput) {
        extraBedsInput.addEventListener('change', calculateTotal);
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

    let value = parseInt(input.value) || 0;
    let min = parseInt(input.min) || 0;
    let max = parseInt(input.max) || 99;

    value += delta;

    if (value < min) value = min;
    if (value > max) value = max;

    input.value = value;

    // Update total guests hidden field
    updateTotalGuests();

    // Reset suggestion dismissed when user changes values
    if (fieldId === 'num_adults' || fieldId === 'num_children') {
        suggestionDismissed = false;
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

// Add extra guest entry
function addExtraGuest() {
    const id = Date.now();
    extraGuests.push({ id, height: 1.3, type: 'over1m3' });
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

// Update extra guest height
function updateExtraGuestHeight(id, height) {
    const guest = extraGuests.find(g => g.id === id);
    if (guest) {
        guest.height = parseFloat(height);
        // Mapping chiều cao sang loại phí (phải khớp với backend)
        if (guest.height < 1.0) {
            guest.type = 'under1m';      // Dưới 1m: Miễn phí
        } else if (guest.height >= 1.0 && guest.height < 1.3) {
            guest.type = '1m_1m3';       // 1m - dưới 1m3: 200,000đ/đêm
        } else {
            guest.type = 'over1m3';      // Từ 1m3 trở lên: 400,000đ/đêm
        }
    }
    updateExtraGuestsData();
    calculateTotal();
}

// Render extra guests list
function renderExtraGuests() {
    const list = document.getElementById('extra_guests_list');
    if (!list) return;

    list.innerHTML = extraGuests.map((guest, index) => `
        <div class="flex items-center gap-3 p-3 bg-gray-700/50 rounded-lg">
            <span class="text-gray-400 text-sm">${index + 1}.</span>
            <div class="flex-1">
                <label class="text-xs text-gray-400 mb-1 block">Chiều cao (m)</label>
                <select onchange="updateExtraGuestHeight(${guest.id}, this.value)" 
                    class="form-input text-sm py-1">
                    <option value="0.5" ${guest.height < 1.0 ? 'selected' : ''}>Dưới 1m (Miễn phí)</option>
                    <option value="1.15" ${guest.height >= 1.0 && guest.height < 1.3 ? 'selected' : ''}>1m - dưới 1m3 (200.000đ/đêm)</option>
                    <option value="1.5" ${guest.height >= 1.3 ? 'selected' : ''}>Từ 1m3 trở lên (400.000đ/đêm)</option>
                </select>
            </div>
            <div class="text-right">
                <span class="text-sm font-semibold ${guest.type === 'under1m' ? 'text-green-400' : guest.type === '1m_1m3' ? 'text-yellow-400' : 'text-orange-400'}">
                    ${guest.type === 'under1m' ? 'Miễn phí' : guest.type === '1m_1m3' ? '200.000đ/đêm' : '400.000đ/đêm'}
                </span>
            </div>
            <button type="button" onclick="removeExtraGuest(${guest.id})" 
                class="text-red-400 hover:text-red-300 p-1">
                <span class="material-symbols-outlined text-sm">delete</span>
            </button>
        </div>
    `).join('');

    // Add "Add more" button
    list.innerHTML += `
        <button type="button" onclick="addExtraGuest()" 
            class="w-full p-2 border border-dashed border-blue-500/50 rounded-lg text-blue-400 text-sm flex items-center justify-center gap-1 hover:bg-blue-500/10 transition-colors">
            <span class="material-symbols-outlined text-sm">add</span>
            Thêm khách
        </button>
    `;

    updateExtraGuestsData();
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
    if (step1Title) step1Title.textContent = 'Chọn căn hộ & thời gian cư trú';

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
    if (submitBtnText) submitBtnText.textContent = 'Gửi yêu cầu tư vấn';
    if (submitBtnIcon) submitBtnIcon.textContent = 'send';

    // Update Step 3 Title
    const step3Title = document.getElementById('step3_title');
    if (step3Title) step3Title.textContent = 'Xác nhận thông tin';
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
    if (step1Title) step1Title.textContent = 'Chọn phòng & ngày';

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
    if (submitBtnText) submitBtnText.textContent = 'Xác nhận đặt phòng';
    if (submitBtnIcon) submitBtnIcon.textContent = 'lock';

    // Update Step 3 Title
    const step3Title = document.getElementById('step3_title');
    if (step3Title) step3Title.textContent = 'Xác nhận thanh toán';
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
                nightsElement.textContent = diffDays + ' đêm';
            }
            return diffDays;
        }
    }

    const nightsElement = document.getElementById('num_nights');
    if (nightsElement) {
        nightsElement.textContent = '0 đêm';
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
        roomPriceDisplay.textContent = '0 VNĐ';
        estimatedTotal.value = '0';
        if (estimatedTotalDisplay) estimatedTotalDisplay.textContent = '0 VNĐ';
        if (originalPriceDisplay) originalPriceDisplay.classList.add('hidden');
        if (roomSubtotalDisplay) roomSubtotalDisplay.textContent = '0 VNĐ';
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
    let priceLabel = 'Giá 2 người';
    let priceType = 'double';
    let originalPrice = parseFloat(selectedOption.dataset.pricePublished) || 0;

    // Check for short stay
    if (currentBookingType === 'short_stay' && category === 'room') {
        const shortStayPrice = parseFloat(selectedOption.dataset.priceShortStay) || 0;
        if (shortStayPrice > 0) {
            price = shortStayPrice;
            priceLabel = 'Giá nghỉ ngắn hạn';
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
            priceLabel = 'Giá 1 người';
            priceType = 'single';
        } else {
            price = priceDouble || parseFloat(selectedOption.dataset.price) || 0;
            priceLabel = 'Giá 2 người';
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
                priceLabel = 'Giá tuần (1 người)';
                priceType = 'weekly';
            } else if (priceAvgWeeklyDouble > 0) {
                price = priceAvgWeeklyDouble;
                priceLabel = 'Giá tuần (2 người)';
                priceType = 'weekly';
            } else {
                price = parseFloat(selectedOption.dataset.price) || 0;
                priceLabel = 'Giá theo ngày';
                priceType = 'daily';
            }
        } else {
            if (numAdults === 1 && priceDailySingle > 0) {
                price = priceDailySingle;
                priceLabel = 'Giá ngày (1 người)';
                priceType = 'daily';
            } else if (priceDailyDouble > 0) {
                price = priceDailyDouble;
                priceLabel = 'Giá ngày (2 người)';
                priceType = 'daily';
            } else {
                price = parseFloat(selectedOption.dataset.price) || 0;
                priceLabel = 'Giá theo ngày';
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
    let extraGuestFee = 0;
    extraGuests.forEach(guest => {
        extraGuestFee += EXTRA_GUEST_FEES[guest.type] || 0;
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
        nightsElement.textContent = 'Nghỉ ngắn hạn (dưới 4h)';
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
        return '0 VNĐ';
    }

    try {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    } catch (error) {
        console.error('Currency formatting error:', error);
        return new Intl.NumberFormat('vi-VN').format(amount) + ' VNĐ';
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

    // Format as dd/mm/yyyy for display
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
        durationDisplay.textContent = months + ' tháng';

        // Get calculated end date
        const calculatedEndDate = document.getElementById('calculated_end_date')?.value;
        endDateDisplay.textContent = calculatedEndDate || '--';
    } else {
        const days = document.getElementById('duration_days')?.value;
        const manualEndDate = document.getElementById('manual_end_date')?.value;

        if (days) {
            durationDisplay.textContent = days + ' ngày';
        } else if (manualEndDate && preferredCheckIn) {
            const startDate = new Date(preferredCheckIn);
            const endDate = new Date(manualEndDate);
            const diffDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
            durationDisplay.textContent = diffDays + ' ngày';
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
function nextStep(step) {
    // Validate current step
    if (!validateStep(currentStep)) {
        return;
    }

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
            alert('Vui lòng chọn loại phòng/căn hộ');
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
                alert('Vui lòng chọn ngày dự kiến nhận phòng');
                return false;
            }

            if (preferredCheckIn < todayStr) {
                alert('Ngày dự kiến nhận phòng không được nhỏ hơn ngày hiện tại');
                return false;
            }

            if (!numAdults || numAdults < 1) {
                alert('Số người lớn phải ít nhất là 1');
                return false;
            }
        } else {
            // ========== ROOM VALIDATION ==========
            const checkin = document.getElementById('check_in_date').value;
            const checkout = document.getElementById('check_out_date').value;
            const numAdults = document.getElementById('num_adults')?.value || document.getElementById('num_guests').value;

            if (!checkin) {
                alert('Vui lòng chọn ngày nhận phòng');
                return false;
            }

            if (checkin < todayStr) {
                alert('Ngày nhận phòng không được nhỏ hơn ngày hiện tại');
                return false;
            }

            // Only validate checkout for standard bookings
            if (currentBookingType !== 'short_stay') {
                if (!checkout) {
                    alert('Vui lòng chọn ngày trả phòng');
                    return false;
                }

                if (new Date(checkout) <= new Date(checkin)) {
                    alert('Ngày trả phòng phải sau ngày nhận phòng');
                    return false;
                }

                if (checkout <= todayStr) {
                    alert('Ngày trả phòng phải là ngày trong tương lai');
                    return false;
                }
            }

            if (!numAdults || numAdults < 1) {
                alert('Vui lòng nhập số khách hợp lệ');
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
            alert('Vui lòng nhập đầy đủ thông tin bắt buộc');
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
        let guestText = numAdults + ' người lớn';
        if (numChildren > 0) {
            guestText += ', ' + numChildren + ' trẻ em';
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
        let guestText = numAdults + ' người lớn';
        if (numChildren > 0) {
            guestText += ', ' + numChildren + ' trẻ em';
        }
        if (extraGuests.length > 0) {
            guestText += ' + ' + extraGuests.length + ' khách thêm';
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

// Format date
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN', {
        weekday: 'short',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Handle form submission
async function handleSubmit(e) {
    e.preventDefault();

    // Validate terms
    if (!document.getElementById('agree_terms').checked) {
        alert('Vui lòng đồng ý với điều khoản và điều kiện');
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

    // Disable submit button
    submitBtn.disabled = true;
    submitBtnText.textContent = 'Đang xử lý...';

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
                    window.location.href = './confirmation.php?booking_code=' + result.booking_code;
                }
            }
        } else {
            alert('Có lỗi xảy ra: ' + result.message);
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
                <span>Khách vãng lai không thể sử dụng mã giảm giá. Vui lòng <a href="../auth/login.php" class="underline font-bold hover:text-yellow-400">đăng nhập</a> để hưởng ưu đãi.</span>
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
