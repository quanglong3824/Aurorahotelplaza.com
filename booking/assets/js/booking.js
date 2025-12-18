// Booking Form JavaScript

let currentStep = 1;
let isInquiryMode = false;

// Initialize
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM loaded, initializing booking form...');

    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('check_in_date').min = today;

    // Set default check-in to today and check-out to tomorrow (only if not pre-filled from URL)
    if (!document.getElementById('check_in_date').value) {
        document.getElementById('check_in_date').value = today;

        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('check_out_date').value = tomorrow.toISOString().split('T')[0];
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
    // Hide Price Summary
    const priceBox = document.getElementById('price_summary_box');
    if (priceBox) priceBox.classList.add('hidden');

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
    // Show Price Summary
    const priceBox = document.getElementById('price_summary_box');
    if (priceBox) priceBox.classList.remove('hidden');

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
        const minCheckout = new Date(checkinDate);
        minCheckout.setDate(minCheckout.getDate() + 1);
        document.getElementById('check_out_date').min = minCheckout.toISOString().split('T')[0];

        // If checkout date is before the new minimum, update it
        const checkoutDate = document.getElementById('check_out_date').value;
        if (checkoutDate && new Date(checkoutDate) <= new Date(checkinDate)) {
            document.getElementById('check_out_date').value = minCheckout.toISOString().split('T')[0];
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
        const checkin = document.getElementById('check_in_date').value;
        const checkout = document.getElementById('check_out_date').value;
        const guests = document.getElementById('num_guests').value;

        if (!roomType) {
            alert('Vui lòng chọn loại phòng');
            return false;
        }

        if (!checkin || !checkout) {
            alert('Vui lòng chọn ngày nhận và trả phòng');
            return false;
        }

        if (new Date(checkout) <= new Date(checkin)) {
            alert('Ngày trả phòng phải sau ngày nhận phòng');
            return false;
        }

        if (!guests || guests < 1) {
            alert('Vui lòng nhập số khách hợp lệ');
            return false;
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

        // Validate duration for inquiry
        if (isInquiryMode) {
            // Can add more validation here if needed
        }

        return true;
    }

    return true;
}

// Update summary
function updateSummary() {
    const roomSelect = document.getElementById('room_type_id');
    const roomName = roomSelect.options[roomSelect.selectedIndex].text.split(' - ')[0];

    document.getElementById('conf_room_type').textContent = roomName;
    document.getElementById('conf_guest_name').textContent = document.getElementById('guest_name').value;
    document.getElementById('conf_guest_phone').textContent = document.getElementById('guest_phone').value;

    // Checkin/Checkout
    document.getElementById('conf_check_in').textContent = formatDate(document.getElementById('check_in_date').value);
    document.getElementById('conf_check_out').textContent = formatDate(document.getElementById('check_out_date').value);

    if (!isInquiryMode) {
        // Payment summaries
        document.getElementById('conf_subtotal').textContent = document.getElementById('estimated_total_display').textContent;
        const promoDiscount = document.getElementById('discount_amount_input').value;
        const total = document.getElementById('estimated_total').value;

        let finalTotal = total;
        if (promoDiscount && promoDiscount > 0) {
            finalTotal = total - promoDiscount;
            document.getElementById('conf_discount').textContent = '-' + formatCurrency(promoDiscount);
        }
        document.getElementById('conf_total').textContent = formatCurrency(finalTotal);
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

    // Add additional fields via JSON
    const data = {
        ...formObject,
        num_nights: calculateNights() // ensure we have nights count
    };

    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const originalText = submitBtnText.textContent;

    // Disable submit button
    submitBtn.disabled = true;
    submitBtnText.textContent = 'Đang xử lý...';

    try {
        let apiUrl = './api/create_booking.php';

        // Check if Inquiry Mode
        if (isInquiryMode) {
            apiUrl = './api/create_inquiry.php';
        }

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
            if (isInquiryMode) {
                // Success for inquiry - Show alert and redirect home or clear form
                alert(result.message);
                window.location.href = '../index.php';
            } else {
                // Success for booking
                if (data.payment_method === 'vnpay') {
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

    document.getElementById('conf_subtotal').textContent = formatCurrency(totalAmount);
    document.getElementById('conf_total').textContent = formatCurrency(totalAmount);
    document.getElementById('discount_row').style.display = 'none';

    document.getElementById('promotion_code_input').value = '';
    document.getElementById('discount_amount_input').value = '0';
    document.getElementById('promo_code').disabled = false;
}
