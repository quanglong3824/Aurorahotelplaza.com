// Booking Form JavaScript

let currentStep = 1;

// Test function to verify elements exist
function testElements() {
    console.log('Testing elements...');
    const checkin = document.getElementById('check_in_date');
    const checkout = document.getElementById('check_out_date');
    const roomRows = document.querySelectorAll('.room-row');

    console.log('Dates:', { checkin, checkout });
    console.log('Room rows found:', roomRows.length);

    return { checkin, checkout, roomRows };
}

// Initialize
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM loaded, initializing booking form...');

    // Test elements
    testElements();

    // Set minimum date to today
    const checkInInput = document.getElementById('check_in_date');
    const checkOutInput = document.getElementById('check_out_date');

    if (checkInInput) {
        const today = new Date().toISOString().split('T')[0];
        checkInInput.min = today;

        // Set default check-in to today if empty
        if (!checkInInput.value) {
            checkInInput.value = today;

            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            if (checkOutInput) {
                checkOutInput.value = tomorrow.toISOString().split('T')[0];
            }
        }
    }

    // Update checkout min
    updateCheckoutMin();

    // Initialize Room Rows Logic
    initRoomLogic();

    // Initialize Guest Capacity Listeners (filter rooms by guest count)
    setupGuestCapacityListeners();

    // Calculate initial values
    calculateTotal();

    // Global Date Listeners
    if (checkInInput) {
        checkInInput.addEventListener('change', function () {
            updateCheckoutMin();
            calculateTotal();
        });
    }
    if (checkOutInput) {
        checkOutInput.addEventListener('change', calculateTotal);
    }

    // Form submission
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', handleSubmit);
    }
});

// Initialize Room Row Logic (Add/Remove/Change)
function initRoomLogic() {
    const container = document.getElementById('room-list-container');
    const addBtn = document.getElementById('add-room-btn');

    // Initial Setup for existing rows
    setupRowListeners();

    // Add Room Button
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            const rows = container.querySelectorAll('.room-row');
            if (rows.length >= 5) {
                alert('Tối đa 5 phòng cho mỗi lần đặt');
                return;
            }

            const newIndex = rows.length;
            const newRow = rows[0].cloneNode(true);

            // Reset values
            newRow.setAttribute('data-index', newIndex);
            newRow.querySelector('h4').textContent = `Phòng ${newIndex + 1}`;
            newRow.querySelector('select').selectedIndex = 0;
            newRow.querySelector('select').removeAttribute('data-preselected'); // Clear preselection

            // Add delete button if not exists
            const header = newRow.querySelector('.flex.justify-between');
            if (!header.querySelector('.remove-room-btn')) {
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'text-red-400 hover:text-red-300 remove-room-btn';
                removeBtn.innerHTML = '<span class="material-symbols-outlined">delete</span>';
                header.appendChild(removeBtn);
            }

            container.appendChild(newRow);

            // Re-attach listeners for ALL rows (simpler than selective attach)
            setupRowListeners();
            calculateTotal();
        });
    }
}

function setupRowListeners() {
    const roomSelects = document.querySelectorAll('.room-select');
    roomSelects.forEach(select => {
        // Remove old listeners to avoid duplicates (cloneNode copies listeners? No, usually not, but safest to be sure)
        select.removeEventListener('change', calculateTotal);
        select.addEventListener('change', calculateTotal);

        // Add room capacity handler
        select.addEventListener('change', function () {
            handleRoomCapacity(this);
        });

        // Initial capacity check for pre-selected rooms
        if (select.value) {
            handleRoomCapacity(select);
        }
    });

    const removeBtns = document.querySelectorAll('.remove-room-btn');
    removeBtns.forEach(btn => {
        btn.onclick = function () {
            const row = this.closest('.room-row');
            if (document.querySelectorAll('.room-row').length > 1) {
                row.remove();
                updateRoomIndices();
                calculateTotal();
            }
        };
    });
}

// Handle room capacity based on bed type (Twin = 2:2, Single = 2:1)
function handleRoomCapacity(selectElement) {
    const row = selectElement.closest('.room-row');
    if (!row) return;

    const option = selectElement.options[selectElement.selectedIndex];
    if (!option || !option.value) return;

    const maxAdults = parseInt(option.dataset.maxAdults) || 2;
    const maxChildren = parseInt(option.dataset.maxChildren) || 1;
    const isTwin = parseInt(option.dataset.isTwin) || 0;

    // Find capacity indicator (already in HTML)
    let indicator = row.querySelector('.capacity-indicator');

    if (indicator) {
        // Update capacity indicator
        const bedIcon = isTwin ? '🛏️🛏️' : '🛏️';
        indicator.innerHTML = `${bedIcon} Max: ${maxAdults} người lớn + ${maxChildren} trẻ em`;
        indicator.classList.remove('hidden');
    }

    // Update children dropdown max value based on room capacity
    const childrenSelect = row.querySelector('.children-input');
    if (childrenSelect) {
        const currentChildren = parseInt(childrenSelect.value) || 0;

        // Rebuild options based on max
        childrenSelect.innerHTML = '';
        for (let c = 0; c <= maxChildren; c++) {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c + ' trẻ em';
            if (c === Math.min(currentChildren, maxChildren)) opt.selected = true;
            childrenSelect.appendChild(opt);
        }
    }
}

// Check guest count and filter room options accordingly
function setupGuestCapacityListeners() {
    document.querySelectorAll('.room-row').forEach(row => {
        const adultsSelect = row.querySelector('.adults-input');
        const childrenSelect = row.querySelector('.children-input');
        const roomSelect = row.querySelector('.room-select');

        if (!adultsSelect || !childrenSelect || !roomSelect) return;

        const updateRoomOptions = () => {
            const adults = parseInt(adultsSelect.value) || 1;
            const children = parseInt(childrenSelect.value) || 0;

            // Update room options: disable rooms that can't fit these guests
            // TEMPORARILY DISABLED for testing - uncomment after DB columns added
            /*
            roomSelect.querySelectorAll('option').forEach(opt => {
                if (!opt.value) return; // Skip placeholder

                // Fallback: assume most rooms can hold 2 adults + 2 children until DB columns are added
                const maxAdults = parseInt(opt.dataset.maxAdults) || 2;
                const maxChildren = parseInt(opt.dataset.maxChildren) || 2; // Changed from 1 to 2

                // Check if room can accommodate
                const canFit = adults <= maxAdults && children <= maxChildren;

                // Mark unsuitable rooms
                if (!canFit) {
                    opt.disabled = true;
                    if (!opt.textContent.includes('❌')) {
                        opt.textContent = opt.textContent.replace(' (Het phong)', '') + ' ❌';
                    }
                } else {
                    // Re-enable if was only disabled by capacity (not availability)
                    if (opt.textContent.includes('❌')) {
                        opt.disabled = false;
                        opt.textContent = opt.textContent.replace(' ❌', '');
                    }
                }
            });
            */
        };

        adultsSelect.addEventListener('change', updateRoomOptions);
        childrenSelect.addEventListener('change', updateRoomOptions);

        // Initial check
        updateRoomOptions();
    });
}

function updateRoomIndices() {
    const rows = document.querySelectorAll('.room-row');
    rows.forEach((row, index) => {
        row.setAttribute('data-index', index);
        row.querySelector('h4').textContent = `Phòng ${index + 1}`;
    });
}

// Update checkout minimum date
function updateCheckoutMin() {
    const checkinDate = document.getElementById('check_in_date').value;
    if (checkinDate) {
        const minCheckout = new Date(checkinDate);
        minCheckout.setDate(minCheckout.getDate() + 1);
        const checkoutInput = document.getElementById('check_out_date');
        if (checkoutInput) {
            checkoutInput.min = minCheckout.toISOString().split('T')[0];

            const checkoutDate = checkoutInput.value;
            if (checkoutDate && new Date(checkoutDate) <= new Date(checkinDate)) {
                checkoutInput.value = minCheckout.toISOString().split('T')[0];
            }
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
            if (nightsElement) nightsElement.textContent = diffDays + ' đêm';
            return diffDays;
        }
    }

    const nightsElement = document.getElementById('num_nights');
    if (nightsElement) nightsElement.textContent = '0 đêm';
    return 0;
}

// Calculate total price (Sum of all rooms)
function calculateTotal() {
    console.log('calculateTotal() called');

    const nights = calculateNights();
    let totalRoomPricePerNight = 0;

    const roomRows = document.querySelectorAll('.room-row');

    roomRows.forEach(row => {
        const select = row.querySelector('.room-select');
        if (select && select.value) {
            const option = select.options[select.selectedIndex];
            const price = parseFloat(option.dataset.price) || 0;
            totalRoomPricePerNight += price;
        }
    });

    // Update Display
    const estimatedTotal = document.getElementById('estimated_total');
    const estimatedTotalDisplay = document.getElementById('estimated_total_display');

    if (nights <= 0) {
        if (estimatedTotal) estimatedTotal.value = '0';
        if (estimatedTotalDisplay) estimatedTotalDisplay.textContent = '0 VNĐ';
        return 0;
    }

    const grandTotal = totalRoomPricePerNight * nights;

    if (estimatedTotal) estimatedTotal.value = grandTotal;
    if (estimatedTotalDisplay) estimatedTotalDisplay.textContent = formatCurrency(grandTotal);

    console.log('Total calculated:', grandTotal);
    return grandTotal;
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

    // Show next step
    currentStep = step;
    document.getElementById('step' + currentStep).classList.add('active');
    document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.add('active');

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
    console.log('Validating step:', step);
    if (step === 1) {
        const checkin = document.getElementById('check_in_date').value;
        const checkout = document.getElementById('check_out_date').value;
        const roomRows = document.querySelectorAll('.room-row');

        if (!checkin || !checkout) {
            alert('Vui lòng chọn ngày nhận và trả phòng');
            return false;
        }

        if (new Date(checkout) <= new Date(checkin)) {
            alert('Ngày trả phòng phải sau ngày nhận phòng');
            return false;
        }

        if (roomRows.length === 0) {
            alert('Vui lòng thêm ít nhất 1 phòng');
            return false;
        }

        let isValid = true;
        roomRows.forEach((row, index) => {
            const select = row.querySelector('.room-select');
            const guestsInput = row.querySelector('.guests-input');
            const roomType = select.value;
            const guests = parseInt(guestsInput.value);

            if (!roomType) {
                alert(`Vui lòng chọn loại phòng cho Phòng ${index + 1}`);
                isValid = false;
                return; // Break callback
            }

            if (!guests || guests < 1) {
                alert(`Vui lòng nhập số khách hợp lệ cho Phòng ${index + 1}`);
                isValid = false;
                return;
            }

            // Check max guests
            const selectedOption = select.options[select.selectedIndex];
            const maxGuests = parseInt(selectedOption.dataset.maxGuests) || 2;
            if (guests > maxGuests) {
                alert(`Phòng ${index + 1} (${selectedOption.text.split(' - ')[0]}) chỉ phù hợp cho tối đa ${maxGuests} khách`);
                isValid = false;
                return;
            }
        });

        return isValid;
    }

    if (step === 2) {
        const name = document.getElementById('guest_name').value.trim();
        const phone = document.getElementById('guest_phone').value.trim();
        const email = document.getElementById('guest_email').value.trim();

        if (!name) {
            alert('Vui lòng nhập họ tên');
            return false;
        }
        if (!phone) {
            alert('Vui lòng nhập số điện thoại');
            return false;
        }
        if (!email) {
            alert('Vui lòng nhập email');
            return false;
        }

        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Email không hợp lệ');
            return false;
        }

        // Validate phone format (Vietnamese)
        const phoneRegex = /^(0|\+84)[0-9]{9,10}$/;
        if (!phoneRegex.test(phone.replace(/\s/g, ''))) {
            alert('Số điện thoại không hợp lệ');
            return false;
        }

        return true;
    }

    return true;
}

// Update summary
function updateSummary() {
    // Room details summary
    const roomRows = document.querySelectorAll('.room-row');
    const roomSummaryList = [];
    roomRows.forEach((row, index) => {
        const select = row.querySelector('.room-select');
        if (select && select.value) {
            const roomName = select.options[select.selectedIndex].text.split(' - ')[0];
            roomSummaryList.push(`Phòng ${index + 1}: ${roomName}`);
        }
    });

    // Total guests
    let totalGuests = 0;
    document.querySelectorAll('.guests-input').forEach(input => {
        totalGuests += parseInt(input.value) || 0;
    });

    document.getElementById('summary_room_type').innerHTML = roomSummaryList.join('<br>');
    document.getElementById('summary_guests').textContent = totalGuests + ' khách';
    document.getElementById('summary_checkin').textContent = formatDate(document.getElementById('check_in_date').value);
    document.getElementById('summary_checkout').textContent = formatDate(document.getElementById('check_out_date').value);
    document.getElementById('summary_nights').textContent = document.getElementById('num_nights').textContent;
    document.getElementById('summary_name').textContent = document.getElementById('guest_name').value;
    document.getElementById('summary_email').textContent = document.getElementById('guest_email').value;
    document.getElementById('summary_phone').textContent = document.getElementById('guest_phone').value;
    document.getElementById('summary_total').textContent = document.getElementById('estimated_total_display').textContent;

    // Check if total is zero (shouldn't happen if validation passed)
    const totalAmount = document.getElementById('estimated_total').value;
    document.getElementById('summary_subtotal').textContent = formatCurrency(totalAmount);
}

// Format date
function formatDate(dateString) {
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

    // Add calculated values to form data
    const totalAmount = document.getElementById('estimated_total').value || '0';
    const numNights = parseInt(document.getElementById('num_nights').textContent) || 0;
    // Note: room_price is varied per room, we don't send single room_price anymore. 
    // Backend should calculate or we trust total (we trust backend calc).

    formData.append('calculated_total', totalAmount);
    formData.append('calculated_nights', numNights);

    const submitBtn = document.getElementById('submitBtn');

    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    submitBtn.innerHTML = '<span>Đang xử lý...</span>';

    try {
        // Send booking request
        const response = await fetch('./api/create_booking.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Redirect to payment or confirmation
            if (formData.get('payment_method') === 'vnpay') {
                window.location.href = result.payment_url;
            } else {
                window.location.href = './confirmation.php?booking_code=' + result.booking_code;
            }
        } else {
            alert('Có lỗi xảy ra: ' + result.message);
            submitBtn.disabled = false;
            submitBtn.classList.remove('loading');
            submitBtn.innerHTML = '<span class="material-symbols-outlined">lock</span> Xác nhận đặt phòng';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi đặt phòng. Vui lòng thử lại.');
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
        submitBtn.innerHTML = '<span class="material-symbols-outlined">lock</span> Xác nhận đặt phòng';
    }
}

// Apply Promotion Code
let appliedPromotion = null;

async function applyPromoCode() {
    const promoCode = document.getElementById('promo_code').value.trim().toUpperCase();
    const messageDiv = document.getElementById('promo_message');

    if (!promoCode) {
        messageDiv.innerHTML = '<span class="text-red-600">Vui lòng nhập mã giảm giá</span>';
        return;
    }

    // Get current total
    const totalAmount = parseFloat(document.getElementById('estimated_total').value) || 0;

    // Get ALL room type IDs? Promo logic usually applies to order or specific rooms.
    // For simplicity, let's pass the first room type or handle multi-room promo backend side.
    // Ideally pass "room_type_id[]" to backend or just pass total.
    // The current backend 'apply-promotion.php' likely expects 'room_type_id' scalar.
    // Workaround: Pass the first selected room type ID.
    const roomSelects = document.querySelectorAll('.room-select');
    const roomTypeId = roomSelects.length > 0 ? roomSelects[0].value : '';

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

