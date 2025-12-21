// Booking Form JavaScript

let currentStep = 1;
let isInquiryMode = false;

// Initialize
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM loaded, initializing booking form...');

    // Set minimum date to today
    // Set minimum date to today (Local Time)
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const today = `${year}-${month}-${day}`;

    document.getElementById('check_in_date').min = today;

    // Set default check-in to today and check-out to tomorrow (only if not pre-filled from URL)
    if (!document.getElementById('check_in_date').value) {
        document.getElementById('check_in_date').value = today;

        const tomorrowDate = new Date();
        tomorrowDate.setDate(tomorrowDate.getDate() + 1);
        const tYear = tomorrowDate.getFullYear();
        const tMonth = String(tomorrowDate.getMonth() + 1).padStart(2, '0');
        const tDay = String(tomorrowDate.getDate()).padStart(2, '0');

        document.getElementById('check_out_date').value = `${tYear}-${tMonth}-${tDay}`;
    }

    // Always update checkout min based on check-in value
    updateCheckoutMin();

    // Initial calculation with default dates
    // Only auto-select first room if no room is pre-selected from URL
    const roomSelect = document.getElementById('room_type_id');

    // Check if a room is pre-selected from PHP (via data attribute or selected attribute)
    const preselectedId = roomSelect.dataset.preselected;
    const hasPreselection = preselectedId && preselectedId !== 'null' && preselectedId !== '';

    if (hasPreselection) {
        // Find and select the option with matching room_type_id
        for (let i = 0; i < roomSelect.options.length; i++) {
            if (roomSelect.options[i].value === preselectedId) {
                roomSelect.selectedIndex = i;
                break;
            }
        }
    } else {
        // Keep default placeholder selected - do not auto-select first room
        roomSelect.selectedIndex = 0;
    }

    // Check initial mode and calculate
    checkBookingMode();
    calculateTotal();

    // Event listeners
    document.getElementById('check_in_date').addEventListener('change', function () {
        updateCheckoutMin();
        calculateTotal();
    });
    document.getElementById('check_out_date').addEventListener('change', calculateTotal);
    document.getElementById('room_type_id').addEventListener('change', function () {
        checkBookingMode();
        calculateTotal();
    });

    // Form submission
    const form = document.getElementById('bookingForm');
    if (form) {
        form.addEventListener('submit', handleSubmit);
    }
});

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

// Calculate total price
function calculateTotal() {
    if (isInquiryMode) return; // Skip calculation for inquiry

    const roomSelect = document.getElementById('room_type_id');
    const roomPriceDisplay = document.getElementById('room_price_display');
    const estimatedTotal = document.getElementById('estimated_total');
    const estimatedTotalDisplay = document.getElementById('estimated_total_display');

    if (!roomSelect || !roomPriceDisplay || !estimatedTotal) return 0;

    if (!roomSelect.value) {
        roomPriceDisplay.textContent = '0 VNĐ';
        estimatedTotal.value = '0';
        if (estimatedTotalDisplay) estimatedTotalDisplay.textContent = '0 VNĐ';
        return 0;
    }

    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const price = parseFloat(selectedOption.dataset.price) || 0;
    const nights = calculateNights();

    // Update room price display
    roomPriceDisplay.textContent = formatCurrency(price);

    if (nights <= 0) {
        estimatedTotal.value = '0';
        if (estimatedTotalDisplay) estimatedTotalDisplay.textContent = '0 VNĐ';
        return 0;
    }

    const total = price * nights;
    estimatedTotal.value = total;
    if (estimatedTotalDisplay) estimatedTotalDisplay.textContent = formatCurrency(total);

    // Store values for form submission
    roomSelect.setAttribute('data-calculated-total', total);
    roomSelect.setAttribute('data-calculated-nights', nights);
    roomSelect.setAttribute('data-room-price', price);

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
            const guests = document.getElementById('num_guests').value;

            if (!checkin || !checkout) {
                alert('Vui lòng chọn ngày nhận và trả phòng');
                return false;
            }

            if (new Date(checkout) <= new Date(checkin)) {
                alert('Ngày trả phòng phải sau ngày nhận phòng');
                return false;
            }

            if (checkin < todayStr) {
                alert('Ngày nhận phòng không được nhỏ hơn ngày hiện tại');
                return false;
            }

            if (checkout <= todayStr) {
                alert('Ngày trả phòng phải là ngày trong tương lai');
                return false;
            }

            if (!guests || guests < 1) {
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
        document.getElementById('summary_guests').textContent = document.getElementById('num_guests').value + ' khách';

        // Reset labels
        document.getElementById('summary_checkin_label').textContent = 'Nhận phòng:';
        document.getElementById('summary_checkout_label').textContent = 'Trả phòng:';
        document.getElementById('summary_nights_label').textContent = 'Số đêm:';

        // Checkin/Checkout/Nights
        document.getElementById('summary_checkin').textContent = formatDate(document.getElementById('check_in_date').value);
        document.getElementById('summary_checkout').textContent = formatDate(document.getElementById('check_out_date').value);
        document.getElementById('summary_nights').textContent = document.getElementById('num_nights').textContent;

        // Show nights row
        document.getElementById('summary_nights_row').style.display = 'flex';

        // Payment summaries
        const subtotal = document.getElementById('estimated_total_display').textContent;
        document.getElementById('summary_subtotal').textContent = subtotal;

        const promoDiscount = document.getElementById('discount_amount_input').value;
        const total = parseFloat(document.getElementById('estimated_total').value) || 0;

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
        data.num_nights = calculateNights();
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
