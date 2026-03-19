/**
 * Aurora Hotel Plaza - Booking Page Scripts
 * Separated from index.php and booking.js
 */

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
const ROOM_CONFIG = {
    maxAdults: 3,
    maxChildren: 2,
    maxOccupancy: 4, // 3 lớn + 1 nhỏ HOẶC 2 lớn + 2 nhỏ
    extraBedFor3Adults: 1 // Bắt buộc 1 giường phụ khi có 3 người lớn
};

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
    
    const declaredExtraGuests = extraGuests.length;
    
    let suggestion = null;
    
    if (numAdults >= maxAdults && numChildren >= 1 && declaredExtraGuests < numChildren) {
        const undeclared = numChildren - declaredExtraGuests;
        if (numChildren >= 2) {
            suggestion = {
                type: 'warning',
                message: `Bạn có ${numChildren} trẻ em đi cùng ${numAdults} người lớn. Vui lòng khai báo chiều cao trẻ em để tính phụ thu chính xác. ${category === 'room' ? 'Bạn cũng có thể cần thêm giường phụ.' : ''}`,
                actions: [
                    { label: `Khai báo ${undeclared} trẻ em`, action: 'declareChildren', count: undeclared },
                    ...(category === 'room' && numExtraBeds === 0 ? [{ label: 'Thêm giường phụ', action: 'addBed' }] : [])
                ]
            };
        } else {
            suggestion = {
                type: 'info',
                message: `Bạn có ${numChildren} trẻ em đi cùng. Vui lòng khai báo chiều cao để tính phụ thu (nếu có).`,
                actions: [
                    { label: 'Khai báo chiều cao', action: 'declareChildren', count: undeclared }
                ]
            };
        }
    }
    else if (totalGuests > maxOccupancy && declaredExtraGuests < (totalGuests - maxOccupancy)) {
        const extraNeeded = totalGuests - maxOccupancy - declaredExtraGuests;
        suggestion = {
            type: 'warning',
            message: `Số khách (${totalGuests} người) vượt quá sức chứa tiêu chuẩn của phòng (${maxOccupancy} người). Vui lòng khai báo ${extraNeeded} khách thêm để tính phụ thu.`,
            actions: [
                { label: `Khai báo ${extraNeeded} khách thêm`, action: 'declareExtra', count: extraNeeded }
            ]
        };
    }
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
    
    message.textContent = suggestion.message;
    box.classList.remove('border-amber-500/30', 'border-red-500/30', 'border-blue-500/30', 'bg-amber-500/10', 'bg-red-500/10', 'bg-blue-500/10');
    
    if (suggestion.type === 'warning') {
        box.classList.add('border-red-500/30', 'bg-red-500/10');
    } else if (suggestion.type === 'hint') {
        box.classList.add('border-blue-500/30', 'bg-blue-500/10');
    } else {
        box.classList.add('border-amber-500/30', 'bg-amber-500/10');
    }
    
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
        const list = document.getElementById('extra_guests_list');
        const btn = document.getElementById('toggle_extra_guests_btn');
        if (list.classList.contains('hidden')) {
            list.classList.remove('hidden');
            btn.innerHTML = '<span class="material-symbols-outlined text-sm">remove_circle</span> Ẩn';
        }
        for (let i = 0; i < count; i++) {
            addExtraGuest();
        }
        document.getElementById('extra_guests_section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } 
    else if (action === 'addBed') {
        const extraBedsInput = document.getElementById('extra_beds');
        if (extraBedsInput) {
            extraBedsInput.value = Math.min(parseInt(extraBedsInput.value || 0) + 1, 2);
            calculateTotal();
        }
        document.getElementById('extra_bed_section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    hideSuggestion();
    calculateTotal();
}

function initBookingTypeSelection() {
    const bookingTypeOptions = document.querySelectorAll('.booking-type-option');
    bookingTypeOptions.forEach(option => {
        option.addEventListener('click', function () {
            const input = this.querySelector('input[type="radio"]');
            if (input && !input.disabled) {
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
        shortStayOption.classList.remove('opacity-50', 'cursor-not-allowed');
        if (input) input.disabled = false;
        if (div) div.classList.remove('opacity-50');
    } else {
        shortStayOption.classList.add('opacity-50', 'cursor-not-allowed');
        if (input) input.disabled = true;
        if (div) div.classList.add('opacity-50');

        if (currentBookingType === 'short_stay') {
            const standardOption = document.querySelector('.booking-type-option[data-type="standard"]');
            if (standardOption) standardOption.click();
        }
    }

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

function adjustValue(fieldId, delta) {
    const input = document.getElementById(fieldId);
    if (!input || input.disabled) return;
    
    let value = parseInt(input.value) || 0;
    let min = parseInt(input.min) || 0;
    let max = parseInt(input.max) || 99;
    
    value += delta;
    if (value < min) value = min;
    if (value > max) value = max;

    input.value = value;

    if (fieldId === 'num_adults' || fieldId === 'num_children') {
        updateTotalGuests();
        suggestionDismissed = false;
        if (fieldId === 'num_adults') handleAdultsChange();
        else if (fieldId === 'num_children') handleChildrenChange();
    } else if (fieldId === 'extra_beds' && extraBedLocked) {
        input.value = 1;
    }
    checkAndShowSuggestion();
    calculateTotal();
}

function updateTotalGuests() {
    const numAdults = parseInt(document.getElementById('num_adults')?.value) || 1;
    const numGuests = document.getElementById('num_guests');
    if (numGuests) numGuests.value = numAdults;
}

function toggleExtraGuests() {
    const list = document.getElementById('extra_guests_list');
    const btn = document.getElementById('toggle_extra_guests_btn');

    if (list.classList.contains('hidden')) {
        list.classList.remove('hidden');
        btn.innerHTML = '<span class="material-symbols-outlined text-sm">remove_circle</span> Ẩn';
        if (extraGuests.length === 0) addExtraGuest();
    } else {
        list.classList.add('hidden');
        btn.innerHTML = '<span class="material-symbols-outlined text-sm">add_circle</span> Thêm khách';
    }
}

function handleAdultsChange() {
    const numAdultsInput = document.getElementById('num_adults');
    const numChildrenInput = document.getElementById('num_children');
    const extraBedsInput = document.getElementById('extra_beds');
    const toggleExtraGuestsBtn = document.getElementById('toggle_extra_guests_btn');

    const btnMinusChild = numChildrenInput?.parentElement?.querySelector('button:first-child');
    const btnPlusChild = numChildrenInput?.parentElement?.querySelector('button:last-child');
    const btnMinusBed = extraBedsInput?.parentElement?.querySelector('button:first-child');
    const btnPlusBed = extraBedsInput?.parentElement?.querySelector('button:last-child');

    if (!numAdultsInput || !numChildrenInput) return;

    const numAdults = parseInt(numAdultsInput.value) || 1;
    let numChildren = parseInt(numChildrenInput.value) || 0;

    if (numAdults >= 3) {
        numChildrenInput.value = 0;
        numChildrenInput.disabled = true;
        if (btnMinusChild) btnMinusChild.classList.add('opacity-50', 'cursor-not-allowed');
        if (btnPlusChild) btnPlusChild.classList.add('opacity-50', 'cursor-not-allowed');
        numChildren = 0;
        extraGuests = [{ id: 999, height: 1.7, type: 'over1m3', isAdult: true, isLocked: true }];
        if (extraBedsInput) {
            extraBedsInput.value = 1;
            extraBedsInput.disabled = true;
            extraBedLocked = true;
            if (btnMinusBed) btnMinusBed.classList.add('opacity-50', 'cursor-not-allowed');
            if (btnPlusBed) btnPlusBed.classList.add('opacity-50', 'cursor-not-allowed');
        }
        document.getElementById('extra_guests_section')?.classList.add('hidden');
        if (toggleExtraGuestsBtn) toggleExtraGuestsBtn.classList.add('hidden');
    } else {
        numChildrenInput.disabled = false;
        if (btnMinusChild) btnMinusChild.classList.remove('opacity-50', 'cursor-not-allowed');
        if (btnPlusChild) btnPlusChild.classList.remove('opacity-50', 'cursor-not-allowed');
        extraBedLocked = false;
        if (extraBedsInput) {
            extraBedsInput.disabled = false;
            if (numAdults === 2 && numChildren === 2) {
                extraBedsInput.value = 1;
                extraBedLocked = true;
            } else if (numAdults < 2 && extraBedsInput.value == 1 && numChildren == 0) {
                extraBedsInput.value = 0;
            }
            if (btnMinusBed) btnMinusBed.classList.remove('opacity-50', 'cursor-not-allowed');
            if (btnPlusBed) btnPlusBed.classList.remove('opacity-50', 'cursor-not-allowed');
        }
        document.getElementById('extra_guests_section')?.classList.remove('hidden');
        if (toggleExtraGuestsBtn) toggleExtraGuestsBtn.classList.remove('hidden');
        extraGuests = extraGuests.filter(g => !g.isAdult);
        if (numAdults + numChildren > ROOM_CONFIG.maxOccupancy) {
            numChildren = ROOM_CONFIG.maxOccupancy - numAdults;
            numChildrenInput.value = numChildren;
        }
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
}

function handleChildrenChange() {
    const numAdultsInput = document.getElementById('num_adults');
    const numChildrenInput = document.getElementById('num_children');
    const extraBedsInput = document.getElementById('extra_beds');
    const toggleExtraGuestsBtn = document.getElementById('toggle_extra_guests_btn');

    if (!numChildrenInput || !numAdultsInput) return;

    const numAdults = parseInt(numAdultsInput.value) || 1;
    let numChildren = parseInt(numChildrenInput.value) || 0;

    if (numAdults === 2 && numChildren > 2) {
        numChildren = 2;
        numChildrenInput.value = 2;
    }
    if (numAdults + numChildren > ROOM_CONFIG.maxOccupancy) {
        numChildren = ROOM_CONFIG.maxOccupancy - numAdults;
        numChildrenInput.value = numChildren;
    }

    extraGuests = extraGuests.filter(g => !g.isAdult);
    if (numChildren > 0) {
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

    if (numAdults === 2 && numChildren === 2 && extraBedsInput) {
        if (parseInt(extraBedsInput.value) === 0) {
            extraBedsInput.value = 1;
            extraBedLocked = true;
            showToast('Đã tự động thêm 1 giường phụ cho 2 trẻ em', 'info');
        }
    }

    const needsExtraBed = extraGuests.some(g => g.height >= 1.3 && !g.isAdult);
    const bedWarning = document.getElementById('extra_bed_warning');
    if (needsExtraBed && extraBedsInput && !extraBedsInput.disabled && parseInt(extraBedsInput.value) === 0) {
        if (bedWarning) {
            bedWarning.classList.remove('hidden');
            bedWarning.innerHTML = '<span class="material-symbols-outlined text-sm text-amber-400">warning</span> Trẻ em cao từ 1.3m nên sử dụng giường phụ (650,000 VND/đêm)';
        }
    } else if(bedWarning) bedWarning.classList.add('hidden');

    renderExtraGuests();
}

function addExtraGuest() {
    const activeGuests = extraGuests.filter(g => !g.isAdult);
    if (activeGuests.length >= 2) {
        showToast('Tối đa chỉ được thêm 2 khách phụ thu', 'error');
        return;
    }
    const numAdults = parseInt(document.getElementById('num_adults')?.value) || 1;
    const numChildren = parseInt(document.getElementById('num_children')?.value) || 0;
    if (numAdults + numChildren >= ROOM_CONFIG.maxOccupancy) {
        showToast('Không thể thêm khách vượt quá sức chứa phòng', 'error');
        return;
    }
    const id = Date.now();
    extraGuests.push({ id, height: 1.0, type: '1m_1m3', isAdult: false, isLocked: false });
    renderExtraGuests();
    calculateTotal();
}

function removeExtraGuest(id) {
    extraGuests = extraGuests.filter(g => g.id !== id);
    renderExtraGuests();
    calculateTotal();
    if (extraGuests.length === 0) {
        document.getElementById('extra_guests_list')?.classList.add('hidden');
        document.getElementById('toggle_extra_guests_btn').innerHTML = '<span class="material-symbols-outlined text-sm">add_circle</span> Thêm khách';
    }
    suggestionDismissed = false;
    checkAndShowSuggestion();
}

function renderExtraGuests() {
    const list = document.getElementById('extra_guests_list');
    if (!list) return;
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
                    <button type="button" onclick="setExtraGuestHeight(${guest.id}, 0.5, 'under1m')" class="height-btn py-3 px-2 rounded-lg border-2 transition-all text-center ${guest.type === 'under1m' ? 'bg-green-500/20 border-green-500 text-green-400' : 'bg-slate-600/50 border-slate-600 text-gray-400 hover:border-green-500/50'}">
                        <div class="font-bold text-sm mb-0.5">Dưới 1m</div>
                        <div class="text-xs opacity-75">Miễn phí</div>
                    </button>
                    <button type="button" onclick="setExtraGuestHeight(${guest.id}, 1.0, '1m_1m3')" class="height-btn py-3 px-2 rounded-lg border-2 transition-all text-center ${guest.type === '1m_1m3' ? 'bg-yellow-500/20 border-yellow-500 text-yellow-400' : 'bg-slate-600/50 border-slate-600 text-gray-400 hover:border-yellow-500/50'}">
                        <div class="font-bold text-sm mb-0.5">1m - 1m3</div>
                        <div class="text-xs opacity-75">200k/đêm</div>
                    </button>
                    <button type="button" onclick="setExtraGuestHeight(${guest.id}, 1.5, 'over1m3')" class="height-btn py-3 px-2 rounded-lg border-2 transition-all text-center ${guest.type === 'over1m3' ? 'bg-orange-500/20 border-orange-500 text-orange-400' : 'bg-slate-600/50 border-slate-600 text-gray-400 hover:border-orange-500/50'}">
                        <div class="font-bold text-sm mb-0.5">Trên 1m3</div>
                        <div class="text-xs opacity-75">400k/đêm</div>
                    </button>
                </div>
            </div>
            <div class="flex items-center justify-between pt-3 border-t border-slate-600">
                <div class="text-sm text-gray-400">
                    <span class="material-symbols-outlined text-xs align-middle">calculate</span>
                    Phụ thu: <span class="font-bold ${guest.type === 'under1m' ? 'text-green-400' : guest.type === '1m_1m3' ? 'text-yellow-400' : 'text-orange-400'}">${guest.type === 'under1m' ? 'Miễn phí' : guest.type === '1m_1m3' ? '200.000 VND' : '400.000 VND'}</span>
                </div>
                ${!guest.isLocked ? `<button type="button" onclick="removeExtraGuest(${guest.id})" class="text-red-400 hover:text-red-300 p-2 rounded-lg hover:bg-red-500/10 transition-colors"><span class="material-symbols-outlined text-sm">delete</span></button>` : ''}
            </div>
        </div>
    `).join('');
    
    const addBtn = document.getElementById('toggle_extra_guests_btn');
    if (addBtn) {
        if (activeGuests.length >= 2) {
            addBtn.disabled = true;
            addBtn.classList.add('opacity-50', 'cursor-not-allowed');
            addBtn.innerHTML = '<span class="material-symbols-outlined text-sm">block</span> Đã đạt tối đa 2 trẻ em';
        } else {
            addBtn.disabled = false;
            addBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            addBtn.innerHTML = `<span class="material-symbols-outlined text-sm">add_circle</span> Thêm trẻ em (${activeGuests.length}/2)`;
        }
    }
    updateExtraGuestsData();
}

function setExtraGuestHeight(id, height, type) {
    const guest = extraGuests.find(g => g.id === id);
    if (guest && !guest.isLocked) {
        guest.height = height;
        guest.type = type;
        renderExtraGuests();
        updateExtraGuestsData();
        calculateTotal();
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

function updateExtraGuestsData() {
    const dataField = document.getElementById('extra_guests_data');
    if (dataField) dataField.value = JSON.stringify(extraGuests);
}

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

function updateUIForInquiry() {
    document.getElementById('room_booking_fields')?.classList.add('hidden');
    document.getElementById('apartment_inquiry_fields')?.classList.remove('hidden');
    const step1Title = document.getElementById('step1_title');
    if (step1Title) step1Title.textContent = translations.booking_form.checkin_title_apt;
    const roomSelect = document.getElementById('room_type_id');
    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const apartmentNameEl = document.getElementById('inquiry_apartment_name');
    if (apartmentNameEl && selectedOption) apartmentNameEl.textContent = selectedOption.text.split(' - ')[0] || '--';
    const preferredCheckIn = document.getElementById('preferred_check_in');
    if (preferredCheckIn) {
        const today = new Date().toISOString().split('T')[0];
        preferredCheckIn.min = today;
        if (!preferredCheckIn.value) preferredCheckIn.value = today;
    }
    document.getElementById('inquiry_fields')?.classList.remove('hidden');
    document.getElementById('inquiry_confirm_section')?.classList.remove('hidden');
    document.getElementById('booking_payment_section')?.classList.add('hidden');
    document.getElementById('payment_summary_rows')?.classList.add('hidden');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnIcon = document.getElementById('submitBtnIcon');
    if (submitBtnText) submitBtnText.textContent = translations.booking_form.submit_btn_apt;
    if (submitBtnIcon) submitBtnIcon.textContent = 'send';
    const step3Title = document.getElementById('step3_title');
    if (step3Title) step3Title.textContent = translations.booking_form.confirm_title_apt;
}

function updateUIForBooking() {
    document.getElementById('room_booking_fields')?.classList.remove('hidden');
    document.getElementById('apartment_inquiry_fields')?.classList.add('hidden');
    const step1Title = document.getElementById('step1_title');
    if (step1Title) step1Title.textContent = translations.booking_form.checkin_title_room;
    document.getElementById('inquiry_fields')?.classList.add('hidden');
    document.getElementById('inquiry_confirm_section')?.classList.add('hidden');
    document.getElementById('booking_payment_section')?.classList.remove('hidden');
    document.getElementById('payment_summary_rows')?.classList.remove('hidden');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnIcon = document.getElementById('submitBtnIcon');
    if (submitBtnText) submitBtnText.textContent = translations.booking_form.submit_btn_room;
    if (submitBtnIcon) submitBtnIcon.textContent = 'lock';
    const step3Title = document.getElementById('step3_title');
    if (step3Title) step3Title.textContent = translations.booking_form.confirm_title_room;
}

function updateCheckoutMin() {
    const checkinDate = document.getElementById('check_in_date').value;
    if (checkinDate) {
        const parts = checkinDate.split('-');
        const nextDay = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]) + 1);
        const minCheckoutStr = `${nextDay.getFullYear()}-${String(nextDay.getMonth() + 1).padStart(2, '0')}-${String(nextDay.getDate()).padStart(2, '0')}`;
        document.getElementById('check_out_date').min = minCheckoutStr;
        if (document.getElementById('check_out_date').value < minCheckoutStr) document.getElementById('check_out_date').value = minCheckoutStr;
    }
}

function calculateNights() {
    const checkin = document.getElementById('check_in_date').value;
    const checkout = document.getElementById('check_out_date').value;
    if (checkin && checkout) {
        const diffDays = Math.ceil((new Date(checkout) - new Date(checkin)) / (1000 * 60 * 60 * 24));
        if (diffDays > 0) {
            const nightsElement = document.getElementById('num_nights');
            if (nightsElement) nightsElement.textContent = diffDays + ' ' + translations.common.nights;
            return diffDays;
        }
    }
    const nightsElement = document.getElementById('num_nights');
    if (nightsElement) nightsElement.textContent = '0 ' + translations.common.nights;
    return 0;
}

function calculateTotal() {
    if (isInquiryMode) return;
    const roomSelect = document.getElementById('room_type_id');
    const roomPriceDisplay = document.getElementById('room_price_display');
    const roomSubtotalDisplay = document.getElementById('room_subtotal_display');
    const estimatedTotal = document.getElementById('estimated_total');
    const estimatedTotalDisplay = document.getElementById('estimated_total_display');
    if (!roomSelect || !roomPriceDisplay || !estimatedTotal) return 0;
    if (!roomSelect.value) {
        roomPriceDisplay.textContent = '0 ' + translations.common.currency;
        estimatedTotal.value = '0';
        if (estimatedTotalDisplay) estimatedTotalDisplay.textContent = '0 ' + translations.common.currency;
        return 0;
    }
    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const category = selectedOption.dataset.category || 'room';
    const numAdults = parseInt(document.getElementById('num_adults')?.value) || 2;
    const nights = currentBookingType === 'short_stay' ? 1 : calculateNights();
    const numExtraBeds = parseInt(document.getElementById('extra_beds')?.value) || 0;
    updateTotalGuests();
    let price = 0, priceLabel = translations.booking_form.price_for_2, priceType = 'double';
    if (currentBookingType === 'short_stay' && category === 'room') {
        price = parseFloat(selectedOption.dataset.priceShortStay) || 0;
        priceLabel = translations.booking_form.price_short_stay;
        priceType = 'short_stay';
    } else if (category === 'room') {
        const priceSingle = parseFloat(selectedOption.dataset.priceSingle) || 0;
        const priceDouble = parseFloat(selectedOption.dataset.priceDouble) || 0;
        if (numAdults === 1 && priceSingle > 0) { price = priceSingle; priceLabel = translations.booking_form.price_single; priceType = 'single'; }
        else { price = priceDouble || parseFloat(selectedOption.dataset.price) || 0; }
    } else {
        const pds = parseFloat(selectedOption.dataset.priceDailySingle) || 0;
        const pdd = parseFloat(selectedOption.dataset.priceDailyDouble) || 0;
        const paws = parseFloat(selectedOption.dataset.priceAvgWeeklySingle) || 0;
        const pawd = parseFloat(selectedOption.dataset.priceAvgWeeklyDouble) || 0;
        if (nights >= 7) {
            if (numAdults === 1 && paws > 0) { price = paws; priceLabel = translations.booking_form.price_weekly_1; priceType = 'weekly'; }
            else if (pawd > 0) { price = pawd; priceLabel = translations.booking_form.price_weekly_2; priceType = 'weekly'; }
            else { price = parseFloat(selectedOption.dataset.price) || 0; priceLabel = translations.booking_form.price_daily; priceType = 'daily'; }
        } else {
            if (numAdults === 1 && pds > 0) { price = pds; priceLabel = translations.booking_form.price_daily_1; priceType = 'daily'; }
            else if (pdd > 0) { price = pdd; priceLabel = translations.booking_form.price_daily_2; priceType = 'daily'; }
            else { price = parseFloat(selectedOption.dataset.price) || 0; priceLabel = translations.booking_form.price_daily; priceType = 'daily'; }
        }
    }
    const priceTypeLabel = document.getElementById('price_type_label');
    if (priceTypeLabel) priceTypeLabel.textContent = priceLabel;
    const priceTypeUsed = document.getElementById('price_type_used');
    if (priceTypeUsed) priceTypeUsed.value = priceType;
    roomPriceDisplay.textContent = formatCurrency(price);
    const effectiveNights = currentBookingType === 'short_stay' ? 1 : (nights > 0 ? nights : 0);
    const roomSubtotal = price * effectiveNights;
    if (roomSubtotalDisplay) roomSubtotalDisplay.textContent = formatCurrency(roomSubtotal);
    let extraGuestFee = 0;
    if (numAdults >= 3) extraGuestFee += EXTRA_GUEST_FEES.over1m3;
    extraGuests.forEach(guest => { if (!guest.isAdult) extraGuestFee += EXTRA_GUEST_FEES[guest.type] || 0; });
    extraGuestFee *= effectiveNights;
    const egfr = document.getElementById('extra_guest_fee_row');
    if (egfr) {
        if (extraGuestFee > 0) {
            egfr.classList.remove('hidden');
            const egfd = document.getElementById('extra_guest_fee_display');
            if (egfd) egfd.textContent = '+' + formatCurrency(extraGuestFee);
        } else egfr.classList.add('hidden');
    }
    document.getElementById('extra_guest_fee').value = extraGuestFee;
    let extraBedFee = 0;
    if (category === 'room' && numExtraBeds > 0) extraBedFee = numExtraBeds * EXTRA_BED_PRICE * effectiveNights;
    const ebfr = document.getElementById('extra_bed_fee_row');
    if (ebfr) {
        if (extraBedFee > 0) {
            ebfr.classList.remove('hidden');
            const ebfd = document.getElementById('extra_bed_fee_display');
            if (ebfd) ebfd.textContent = '+' + formatCurrency(extraBedFee);
        } else ebfr.classList.add('hidden');
    }
    document.getElementById('extra_bed_fee').value = extraBedFee;
    const total = roomSubtotal + extraGuestFee + extraBedFee;
    estimatedTotal.value = total;
    if (estimatedTotalDisplay) estimatedTotalDisplay.textContent = formatCurrency(total);
    const nightsElement = document.getElementById('num_nights');
    if (nightsElement && currentBookingType === 'short_stay') nightsElement.textContent = translations.booking_form.short_stay_label;
    roomSelect.setAttribute('data-calculated-total', total);
    roomSelect.setAttribute('data-calculated-nights', effectiveNights);
    roomSelect.setAttribute('data-room-price', price);
    roomSelect.setAttribute('data-price-type', priceType);
    roomSelect.setAttribute('data-room-subtotal', roomSubtotal);
    roomSelect.setAttribute('data-extra-guest-fee', extraGuestFee);
    roomSelect.setAttribute('data-extra-bed-fee', extraBedFee);
    return total;
}

function formatCurrency(amount) {
    if (isNaN(amount) || amount === null || amount === undefined) return '0 ' + translations.common.currency;
    try {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
    } catch (error) {
        return new Intl.NumberFormat('vi-VN').format(amount) + ' ' + translations.common.currency;
    }
}

function toggleRentMode() {
    const mode = document.getElementById('rent_mode').value;
    if (mode === 'by_month') {
        document.getElementById('rent_by_month')?.classList.remove('hidden');
        document.getElementById('rent_by_date')?.classList.add('hidden');
        calculateEndDate();
    } else {
        document.getElementById('rent_by_month')?.classList.add('hidden');
        document.getElementById('rent_by_date')?.classList.remove('hidden');
        const pci = document.getElementById('preferred_check_in').value;
        if (pci) document.getElementById('manual_end_date').min = pci;
    }
    updateDurationType();
    updateInquirySummary();
}

function calculateEndDate() {
    const pci = document.getElementById('preferred_check_in').value;
    if (!pci) return;
    const months = parseInt(document.getElementById('duration_months')?.value) || 1;
    const endDate = new Date(pci);
    endDate.setMonth(endDate.getMonth() + months);
    const day = String(endDate.getDate()).padStart(2, '0'), month = String(endDate.getMonth() + 1).padStart(2, '0'), year = endDate.getFullYear();
    if (document.getElementById('calculated_end_date')) document.getElementById('calculated_end_date').value = `${day}/${month}/${year}`;
    updateDurationType();
    updateInquirySummary();
}

function calculateEndDateFromDays() {
    const pci = document.getElementById('preferred_check_in').value;
    const days = parseInt(document.getElementById('duration_days')?.value) || 0;
    if (!pci || days <= 0) return;
    const endDate = new Date(pci);
    endDate.setDate(endDate.getDate() + days);
    const year = endDate.getFullYear(), month = String(endDate.getMonth() + 1).padStart(2, '0'), day = String(endDate.getDate()).padStart(2, '0');
    if (document.getElementById('manual_end_date')) document.getElementById('manual_end_date').value = `${year}-${month}-${day}`;
    updateDurationType();
    updateInquirySummary();
}

function calculateDaysFromEndDate() {
    const pci = document.getElementById('preferred_check_in').value;
    const med = document.getElementById('manual_end_date').value;
    if (!pci || !med) return;
    const diffDays = Math.ceil((new Date(med) - new Date(pci)) / (1000 * 60 * 60 * 24));
    if (diffDays > 0 && document.getElementById('duration_days')) document.getElementById('duration_days').value = diffDays;
    updateDurationType();
    updateInquirySummary();
}

function updateDurationType() {
    const mode = document.getElementById('rent_mode')?.value || 'by_month';
    const dtf = document.getElementById('duration_type');
    if (mode === 'by_month') {
        const months = document.getElementById('duration_months')?.value || '1';
        dtf.value = months + '_month' + (months > 1 ? 's' : '');
    } else {
        const pci = document.getElementById('preferred_check_in').value;
        const med = document.getElementById('manual_end_date').value;
        if (pci && med) {
            const diffDays = Math.ceil((new Date(med) - new Date(pci)) / (1000 * 60 * 60 * 24));
            dtf.value = 'custom_' + diffDays + '_days';
        } else dtf.value = 'custom';
    }
}

function updateInquirySummary() {
    const mode = document.getElementById('rent_mode')?.value || 'by_month';
    const dd = document.getElementById('inquiry_duration_display');
    const edd = document.getElementById('inquiry_end_date_display');
    if (!dd || !edd) return;
    if (mode === 'by_month') {
        const months = document.getElementById('duration_months')?.value || '1';
        dd.textContent = months + ' ' + translations.common.month;
        edd.textContent = document.getElementById('calculated_end_date')?.value || '--';
    } else {
        const days = document.getElementById('duration_days')?.value;
        const med = document.getElementById('manual_end_date')?.value;
        if (days) dd.textContent = days + ' ' + translations.common.day;
        else if (med && document.getElementById('preferred_check_in').value) {
            const diffDays = Math.ceil((new Date(med) - new Date(document.getElementById('preferred_check_in').value)) / (1000 * 60 * 60 * 24));
            dd.textContent = diffDays + ' ' + translations.common.day;
        } else dd.textContent = '--';
        if (med) {
            const d = new Date(med);
            edd.textContent = `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
        } else edd.textContent = '--';
    }
}

async function nextStep(step) {
    if (!validateStep(currentStep)) return;
    if (step === 3 && !isInquiryMode) {
        const continueBtn = event?.target;
        const originalText = continueBtn?.innerHTML;
        if (continueBtn) { continueBtn.disabled = true; continueBtn.innerHTML = '<span class="material-symbols-outlined animate-spin">progress_activity</span> Đang kiểm tra...'; }
        try {
            const vr = await fetch('./api/validate-booking.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ check_in_date: document.getElementById('check_in_date')?.value, check_out_date: document.getElementById('check_out_date')?.value }) });
            const validation = await vr.json();
            if (!validation.allowed) { showBookingConflictModal(validation); if (continueBtn) { continueBtn.disabled = false; continueBtn.innerHTML = originalText; } return; }
        } catch (error) { console.error('Pre-validation error:', error); } finally { if (continueBtn) { continueBtn.disabled = false; continueBtn.innerHTML = originalText; } }
    }
    document.getElementById('step' + currentStep).classList.remove('active');
    document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.remove('active');
    document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.add('completed');
    const cc = document.querySelector(`.step-connector[data-from="${currentStep}"]`);
    if (cc) { cc.classList.remove('active'); cc.classList.add('completed'); }
    currentStep = step;
    document.getElementById('step' + currentStep).classList.add('active');
    document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.add('active');
    const ac = document.querySelector(`.step-connector[data-from="${currentStep}"]`);
    if (ac) ac.classList.add('active');
    if (step === 3) updateSummary();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function prevStep(step) {
    document.getElementById('step' + currentStep).classList.remove('active');
    document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.remove('active');
    const pc = document.querySelector(`.step-connector[data-to="${currentStep}"]`);
    if (pc) { pc.classList.remove('completed'); pc.classList.add('active'); }
    const sc = document.querySelector(`.step-connector[data-from="${currentStep}"]`);
    if (sc) sc.classList.remove('active', 'completed');
    currentStep = step;
    document.getElementById('step' + currentStep).classList.add('active');
    document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.add('active');
    document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.remove('completed');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateStep(step) {
    if (step === 1) {
        if (!document.getElementById('room_type_id').value) { alert(translations.booking_form.select_room_or_apt); return false; }
        const todayStr = new Date().toISOString().split('T')[0];
        if (isInquiryMode) {
            const pci = document.getElementById('preferred_check_in').value;
            const na = document.getElementById('inquiry_num_adults').value;
            if (!pci) { alert(translations.booking_form.select_est_checkin); return false; }
            if (pci < todayStr) { alert(translations.booking_form.checkin_not_past); return false; }
            if (!na || na < 1) { alert(translations.booking_form.min_adults); return false; }
        } else {
            const ci = document.getElementById('check_in_date').value, co = document.getElementById('check_out_date').value, na = document.getElementById('num_adults')?.value || document.getElementById('num_guests').value;
            if (!ci) { alert(translations.booking_form.select_checkin_date); return false; }
            if (ci < todayStr) { alert(translations.booking_form.checkin_not_past); return false; }
            if (currentBookingType !== 'short_stay') {
                if (!co) { alert(translations.booking_form.select_checkout_date); return false; }
                if (new Date(co) <= new Date(ci)) { alert(translations.booking_form.checkout_after_checkin); return false; }
                if (co <= todayStr) { alert(translations.booking_form.checkout_future || 'Ngày trả phòng phải ở tương lai.'); return false; }
                if (Math.ceil((new Date(co) - new Date(ci)) / (1000 * 60 * 60 * 24)) > 30) { alert('Số đêm lưu trú tối đa là 30 đêm theo cấu hình hệ thống, vui lòng chọn lại ngày trả phòng!'); return false; }
            }
            if (!na || na < 1) { alert(translations.booking_form.invalid_guests); return false; }
        }
        return true;
    }
    if (step === 2) {
        const name = document.getElementById('guest_name').value.trim(), phone = document.getElementById('guest_phone').value.trim(), email = document.getElementById('guest_email').value.trim();
        if (!name || !phone || !email) { alert(translations.booking_form.fill_required || 'Vui lòng điền đầy đủ thông tin bắt buộc.'); return false; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { alert('Vui lòng nhập đúng định dạng Email hợp lệ!'); return false; }
        const pn = phone.replace(/[^0-9]/g, '');
        if (pn.length < 9 || pn.length > 15) { alert('Số điện thoại không hợp lệ (yêu cầu từ 9 đến 15 chữ số thuần túy)!'); return false; }
        return true;
    }
    return true;
}

function updateSummary() {
    const roomSelect = document.getElementById('room_type_id');
    document.getElementById('summary_room_type').textContent = roomSelect.options[roomSelect.selectedIndex].text.split(' - ')[0];
    document.getElementById('summary_name').textContent = document.getElementById('guest_name').value;
    document.getElementById('summary_email').textContent = document.getElementById('guest_email').value;
    document.getElementById('summary_phone').textContent = document.getElementById('guest_phone').value;
    if (isInquiryMode) {
        const na = document.getElementById('inquiry_num_adults').value || 1, nc = document.getElementById('inquiry_num_children').value || 0, pci = document.getElementById('preferred_check_in').value, rm = document.getElementById('rent_mode')?.value || 'by_month';
        let gt = na + ' ' + (na > 1 ? translations.common.adults : translations.common.adult);
        if (nc > 0) gt += ', ' + nc + ' ' + (nc > 1 ? translations.common.children : translations.common.child);
        document.getElementById('summary_guests').textContent = gt;
        document.getElementById('summary_checkin_label').textContent = 'Ngày dự kiến:';
        document.getElementById('summary_checkin').textContent = formatDate(pci);
        document.getElementById('summary_checkout_label').textContent = 'Thời gian thuê:';
        if (rm === 'by_month') document.getElementById('summary_checkout').textContent = (document.getElementById('duration_months')?.value || '1') + ' tháng';
        else {
            const days = document.getElementById('duration_days')?.value, med = document.getElementById('manual_end_date')?.value;
            if (days) document.getElementById('summary_checkout').textContent = days + ' ngày';
            else if (med && pci) document.getElementById('summary_checkout').textContent = Math.ceil((new Date(med) - new Date(pci)) / (1000 * 60 * 60 * 24)) + ' ngày';
            else document.getElementById('summary_checkout').textContent = '--';
        }
        document.getElementById('summary_nights_row').style.display = 'none';
    } else {
        const na = document.getElementById('num_adults')?.value || document.getElementById('num_guests')?.value || 2, nc = document.getElementById('num_children')?.value || 0;
        let gt = na + ' ' + (na > 1 ? translations.common.adults : translations.common.adult);
        if (nc > 0) gt += ', ' + nc + ' ' + (nc > 1 ? translations.common.children : translations.common.child);
        if (extraGuests.length > 0) gt += ' + ' + extraGuests.length + ' ' + (extraGuests.length > 1 ? translations.common.guests : translations.common.guest_add);
        document.getElementById('summary_guests').textContent = gt;
        document.getElementById('summary_checkin_label').textContent = 'Nhận phòng:';
        document.getElementById('summary_checkout_label').textContent = currentBookingType === 'short_stay' ? 'Loại hình:' : 'Trả phòng:';
        document.getElementById('summary_nights_label').textContent = 'Số đêm:';
        document.getElementById('summary_checkin').textContent = formatDate(document.getElementById('check_in_date').value);
        if (currentBookingType === 'short_stay') { document.getElementById('summary_checkout').textContent = 'Nghỉ ngắn hạn (dưới 4h)'; document.getElementById('summary_nights').textContent = '1 lượt'; }
        else { document.getElementById('summary_checkout').textContent = formatDate(document.getElementById('check_out_date').value); document.getElementById('summary_nights').textContent = document.getElementById('num_nights').textContent; }
        document.getElementById('summary_nights_row').style.display = 'flex';
        const rs = document.getElementById('room_type_id'), rst = parseFloat(rs.getAttribute('data-room-subtotal')) || 0, egf = parseFloat(rs.getAttribute('data-extra-guest-fee')) || 0, ebf = parseFloat(rs.getAttribute('data-extra-bed-fee')) || 0, total = parseFloat(rs.getAttribute('data-calculated-total')) || 0;
        document.getElementById('summary_subtotal').textContent = formatCurrency(rst);
        if (document.getElementById('summary_extra_guest_row')) {
            if (egf > 0) { document.getElementById('summary_extra_guest_row').style.display = 'flex'; document.getElementById('summary_extra_guest_fee').textContent = '+' + formatCurrency(egf); }
            else document.getElementById('summary_extra_guest_row').style.display = 'none';
        }
        if (document.getElementById('summary_extra_bed_row')) {
            if (ebf > 0) { document.getElementById('summary_extra_bed_row').style.display = 'flex'; document.getElementById('summary_extra_bed_fee').textContent = '+' + formatCurrency(ebf); }
            else document.getElementById('summary_extra_bed_row').style.display = 'none';
        }
        const pd = document.getElementById('discount_amount_input').value;
        let ft = total; if (pd && pd > 0) { ft = total - pd; document.getElementById('summary_discount').textContent = '-' + formatCurrency(pd); }
        document.getElementById('summary_total').textContent = formatCurrency(ft);
    }
}

function formatDate(ds) { if (!ds) return ''; return new Date(ds).toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' }); }

async function handleSubmit(e) {
    e.preventDefault();
    if (!document.getElementById('agree_terms').checked) { alert(translations.booking_form.agree_terms_alert); return; }
    const formData = new FormData(e.target);
    const fo = Object.fromEntries(formData);
    let data = { ...fo, booking_type: isInquiryMode ? 'inquiry' : 'instant' };
    if (isInquiryMode) {
        const pci = document.getElementById('preferred_check_in').value, dt = document.getElementById('duration_type').value, na = document.getElementById('inquiry_num_adults').value, nc = document.getElementById('inquiry_num_children').value, im = document.getElementById('inquiry_message')?.value || '', rm = document.getElementById('rent_mode')?.value || 'by_month';
        let co = pci; if (pci) {
            const sd = new Date(pci); if (rm === 'by_month') { const m = parseInt(document.getElementById('duration_months')?.value) || 1; sd.setMonth(sd.getMonth() + m); co = sd.toISOString().split('T')[0]; }
            else { const med = document.getElementById('manual_end_date')?.value; if (med) co = med; else { const d = parseInt(document.getElementById('duration_days')?.value) || 30; sd.setDate(sd.getDate() + d); co = sd.toISOString().split('T')[0]; } }
        }
        data = { ...data, room_type_id: fo.room_type_id, check_in_date: pci, check_out_date: co, num_guests: na, num_adults: na, num_children: nc, duration_type: dt, message: im, num_nights: 0 };
    } else {
        const rs = document.getElementById('room_type_id'), na = parseInt(document.getElementById('num_adults')?.value) || 2, nc = parseInt(document.getElementById('num_children')?.value) || 0, neb = parseInt(document.getElementById('extra_beds')?.value) || 0, nn = currentBookingType === 'short_stay' ? 1 : calculateNights(), rp = parseFloat(rs.getAttribute('data-room-price')) || 0, ct = parseFloat(rs.getAttribute('data-calculated-total')) || 0, pt = rs.getAttribute('data-price-type') || 'double', rst = parseFloat(rs.getAttribute('data-room-subtotal')) || 0, egf = parseFloat(rs.getAttribute('data-extra-guest-fee')) || 0, ebf = parseFloat(rs.getAttribute('data-extra-bed-fee')) || 0;
        data.booking_type = currentBookingType; data.num_adults = na; data.num_children = nc; data.num_guests = na; data.num_nights = nn; data.calculated_nights = nn; data.room_price = rp; data.room_subtotal = rst; data.calculated_total = ct; data.price_type_used = pt; data.extra_beds = neb; data.extra_bed_fee = ebf; data.extra_guest_fee = egf; data.extra_guests_data = JSON.stringify(extraGuests);
    }
    const sb = document.getElementById('submitBtn'), sbt = document.getElementById('submitBtnText'), ot = sbt.textContent;
    try {
        const vr = await fetch('./api/validate-booking.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ check_in_date: data.check_in_date, check_out_date: data.check_out_date }) });
        const validation = await vr.json();
        if (!validation.allowed) { showBookingConflictModal(validation); return; }
    } catch (error) { console.error('Pre-validation error:', error); }
    sb.disabled = true; sbt.textContent = translations.common.processing;
    try {
        const response = await fetch('./api/create_booking.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        let result;
        const responseText = await response.text();
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('Phản hồi từ server không phải JSON:', responseText);
            alert('Lỗi hệ thống (Server Error): ' + (responseText.substring(0, 200) || 'Phản hồi trống'));
            sb.disabled = false; sbt.textContent = ot;
            return;
        }

        if (result.success) {
            if (result.booking_type === 'inquiry') { alert(result.message || 'Yêu cầu tư vấn của bạn đã được gửi thành công!'); window.location.href = '../index.php'; }
            else { if (data.payment_method === 'vnpay' && result.payment_url) window.location.href = result.payment_url; else window.location.href = result.is_guest ? './confirmation.php?booking_code=' + result.booking_code : '../profile/bookings.php'; }
        } else {
            if (result.existing_bookings || result.overlapping_bookings) showBookingConflictModal(result);
            else if (result.retry_after) showToast(`Vui lòng đợi ${result.retry_after} giây trước khi đặt tiếp`, 'error');
            else if (result.message) alert('Lỗi đặt phòng: ' + result.message);
            else alert('Có lỗi xảy ra. Vui lòng thử lại.');
            sb.disabled = false; sbt.textContent = ot;
        }
    } catch (error) { 
        console.error('Error:', error); 
        alert('Lỗi kết nối hoặc thực thi: ' + error.message); 
        sb.disabled = false; sbt.textContent = ot; 
    }
}

function showBookingConflictModal(result) {
    closeBookingConflictModal();
    const modal = document.createElement('div');
    modal.id = 'bookingConflictModal';
    modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm';
    modal.setAttribute('role', 'dialog'); modal.setAttribute('aria-modal', 'true');
    modal.innerHTML = `
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg border border-red-500/30 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-red-600 to-red-700 sticky top-0 rounded-t-2xl">
                <h3 class="font-bold text-lg text-white flex items-center gap-2"><span class="material-symbols-outlined">warning</span>Không thể đặt phòng</h3>
                <button onclick="closeBookingConflictModal()" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg transition-colors"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-6">
                <div class="mb-4"><p class="text-gray-700 dark:text-gray-300 mb-4">${result.message || 'Bạn đang có đặt phòng chưa hoàn tất.'}</p></div>
                ${result.pending_bookings && result.pending_bookings.length > 0 ? `
                    <div class="mb-4">
                        <h4 class="font-semibold text-red-600 mb-2 flex items-center gap-2"><span class="material-symbols-outlined text-sm">list</span>Đặt phòng chưa hoàn tất (${result.pending_bookings.length}):</h4>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            ${result.pending_bookings.map(booking => `
                                <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="font-semibold text-sm text-blue-600 dark:text-blue-400">Mã: ${booking.booking_code}</span>
                                        <span class="text-xs px-2 py-1 rounded ${booking.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : booking.status === 'confirmed' ? 'bg-blue-100 text-blue-800' : booking.status === 'checked_in' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">${booking.status === 'pending' ? 'Chờ xác nhận' : booking.status === 'confirmed' ? 'Đã xác nhận' : booking.status === 'checked_in' ? 'Đang ở' : booking.status}</span>
                                    </div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1"><div>📅 ${booking.check_in_date} → ${booking.check_out_date}</div><div>💰 ${parseInt(booking.total_amount).toLocaleString()} VND</div></div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
                ${result.overlapping_bookings && result.overlapping_bookings.length > 0 ? `
                    <div class="mb-4"><h4 class="font-semibold text-orange-600 mb-2 flex items-center gap-2"><span class="material-symbols-outlined text-sm">event_busy</span>Trùng lịch sử đặt (${result.overlapping_bookings.length}):</h4><div class="space-y-2">${result.overlapping_bookings.map(booking => `<div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3 border border-orange-200 dark:orange-800"><div class="text-sm text-orange-900 dark:text-orange-100"><strong>Mã ${booking.booking_code}</strong>: ${booking.check_in_date} → ${booking.check_out_date}</div></div>`).join('')}</div></div>
                ` : ''}
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800"><h5 class="font-semibold text-blue-800 dark:text-blue-300 mb-2 text-sm">Bạn cần làm gì?</h5><ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1 list-disc list-inside"><li>Hoàn tất thanh toán cho đặt phòng cũ (nếu chưa thanh toán)</li><li>Hủy đặt phòng cũ qua trang Quản lý đặt phòng</li><li>Liên hệ lễ tân: <strong>(0251) 391.8888</strong> để được hỗ trợ</li><li>Chọn ngày khác không trùng với đặt phòng hiện có</li></ul></div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3 sticky bottom-0 bg-white dark:bg-gray-800 rounded-b-2xl">
                <button onclick="closeBookingConflictModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">Đóng</button>
                <a href="../profile/bookings.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2"><span class="material-symbols-outlined text-sm">list</span>Xem đặt phòng của tôi</a>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    modal.addEventListener('click', (e) => { if (e.target === modal) closeBookingConflictModal(); });
    const handleEsc = (e) => { if (e.key === 'Escape') closeBookingConflictModal(); };
    document.addEventListener('keydown', handleEsc);
    modal.cleanup = () => document.removeEventListener('keydown', handleEsc);
}

function closeBookingConflictModal() {
    const modal = document.getElementById('bookingConflictModal');
    if (modal) { if (modal.cleanup) modal.cleanup(); modal.remove(); document.body.style.overflow = ''; }
}

let appliedPromotion = null;
async function applyPromoCode() {
    if (isInquiryMode) return;
    if (typeof IS_LOGGED_IN !== 'undefined' && !IS_LOGGED_IN) {
        document.getElementById('promo_message').innerHTML = `
            <div class="text-yellow-500 flex items-start gap-2 text-sm mt-2"><span class="material-symbols-outlined text-base mt-0.5">lock</span><span>${translations.booking_form.guest_promo_lock} <a href="../auth/login.php" class="underline font-bold hover:text-yellow-400">${translations.auth.login}</a> ${translations.booking_form.guest_promo_lock_end}</span></div>
        `;
        return;
    }
    const promoCode = document.getElementById('promo_code').value.trim().toUpperCase(), messageDiv = document.getElementById('promo_message');
    if (!promoCode) { messageDiv.innerHTML = '<span class="text-red-600">Vui lòng nhập mã giảm giá</span>'; return; }
    const totalAmount = parseFloat(document.getElementById('estimated_total').value) || 0, roomTypeId = document.getElementById('room_type_id').value;
    if (totalAmount <= 0) { messageDiv.innerHTML = '<span class="text-red-600">Vui lòng chọn ngày trước khi áp dụng mã</span>'; return; }
    messageDiv.innerHTML = '<span class="text-gray-600">Đang kiểm tra...</span>';
    try {
        const formData = new FormData(); formData.append('promo_code', promoCode); formData.append('total_amount', totalAmount); formData.append('room_type_id', roomTypeId);
        const response = await fetch('./api/apply-promotion.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            appliedPromotion = result;
            document.getElementById('summary_subtotal').textContent = formatCurrency(result.original_amount);
            document.getElementById('summary_discount').textContent = '-' + formatCurrency(result.discount_amount);
            document.getElementById('summary_total').textContent = formatCurrency(result.final_amount);
            document.getElementById('discount_row').style.display = 'flex';
            document.getElementById('promotion_code_input').value = result.promotion.code;
            document.getElementById('discount_amount_input').value = result.discount_amount;
            messageDiv.innerHTML = `<div class="flex items-center gap-2 text-green-600"><span class="material-symbols-outlined">check_circle</span><span>${result.message} Giảm ${formatCurrency(result.discount_amount)}</span></div>`;
            document.getElementById('promo_code').disabled = true;
        } else { messageDiv.innerHTML = `<span class="text-red-600">${result.message}</span>`; removePromotion(); }
    } catch (error) { console.error('Error:', error); messageDiv.innerHTML = '<span class="text-red-600">Có lỗi xảy ra khi áp dụng mã</span>'; }
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

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-[9999] transition-all duration-300 transform translate-x-0 ${type === 'success' ? 'bg-green-600 text-white' : type === 'error' ? 'bg-red-600 text-white' : 'bg-blue-600 text-white'}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => { toast.classList.add('opacity-0', 'translate-x-full'); setTimeout(() => toast.remove(), 300); }, 3000);
}
