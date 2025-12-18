// Booking Form JavaScript

let currentStep = 1;

// Test function to verify elements exist
function testElements() {
    const elements = {
        'room_type_id': document.getElementById('room_type_id'),
        'check_in_date': document.getElementById('check_in_date'),
        'check_out_date': document.getElementById('check_out_date'),
        'num_nights': document.getElementById('num_nights'),
        'room_price_display': document.getElementById('room_price_display'),
        'estimated_total': document.getElementById('estimated_total')
    };

    console.log('Element check:', elements);

    for (const [name, element] of Object.entries(elements)) {
        if (!element) {
            console.error(`Element ${name} not found!`);
        }
    }

    return elements;
}

// Initialize
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM loaded, initializing booking form...');

    // Test elements
    testElements();
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

    // Debug: Log preselected values from PHP
    console.log('DEBUG - PHP preselected ID:', roomSelect.dataset.preselected);
    console.log('DEBUG - PHP slug:', roomSelect.dataset.slug);
    console.log('DEBUG - Current selectedIndex:', roomSelect.selectedIndex);
    console.log('DEBUG - Current value:', roomSelect.value);

    // Check if a room is pre-selected from PHP (via data attribute or selected attribute)
    const preselectedId = roomSelect.dataset.preselected;
    const hasPreselection = preselectedId && preselectedId !== 'null' && preselectedId !== '';

    if (hasPreselection) {
        // Find and select the option with matching room_type_id
        for (let i = 0; i < roomSelect.options.length; i++) {
            if (roomSelect.options[i].value === preselectedId) {
                roomSelect.selectedIndex = i;
                console.log('Room pre-selected from URL (index: ' + i + ', id: ' + preselectedId + ')');
                break;
            }
        }
        // Calculate only if room is pre-selected from URL
        calculateTotal();
    } else {
        // Keep default placeholder selected - do not auto-select first room
        console.log('No room pre-selected, keeping placeholder option...');
        roomSelect.selectedIndex = 0;
    }

    // Event listeners
    document.getElementById('check_in_date').addEventListener('change', function () {
        updateCheckoutMin();
        calculateTotal();
    });
    document.getElementById('check_out_date').addEventListener('change', calculateTotal);
    document.getElementById('room_type_id').addEventListener('change', calculateTotal);

    // Calculate initial values if room type is pre-selected
    if (document.getElementById('room_type_id').value) {
        calculateTotal();
    }

    // Form submission
    document.getElementById('bookingForm').addEventListener('submit', handleSubmit);
});

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

    console.log('calculateNights:', { checkin, checkout });

    if (checkin && checkout) {
        const date1 = new Date(checkin);
        const date2 = new Date(checkout);
        const diffTime = date2 - date1;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        console.log('Date calculation:', { date1, date2, diffTime, diffDays });

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
    console.log('calculateTotal() called'); // Debug log

    const roomSelect = document.getElementById('room_type_id');
    const roomPriceDisplay = document.getElementById('room_price_display');
    const estimatedTotal = document.getElementById('estimated_total');
    const estimatedTotalDisplay = document.getElementById('estimated_total_display');

    if (!roomSelect || !roomPriceDisplay || !estimatedTotal) {
        console.error('Required elements not found');
        return 0;
    }

    if (!roomSelect.value) {
        roomPriceDisplay.textContent = '0 VNĐ';
        estimatedTotal.value = '0';
        if (estimatedTotalDisplay) estimatedTotalDisplay.textContent = '0 VNĐ';
        console.log('No room selected');
        return 0;
    }

    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const price = parseFloat(selectedOption.dataset.price) || 0;
    const nights = calculateNights();

    console.log('Calculation data:', {
        selectedIndex: roomSelect.selectedIndex,
        price: price,
        nights: nights,
        dataPrice: selectedOption.dataset.price
    });

    // Update room price display
    roomPriceDisplay.textContent = formatCurrency(price);

    if (nights <= 0) {
        estimatedTotal.value = '0';
        if (estimatedTotalDisplay) estimatedTotalDisplay.textContent = '0 VNĐ';
        console.log('No nights calculated');
        return 0;
    }

    const total = price * nights;
    estimatedTotal.value = total; // Store numeric value
    if (estimatedTotalDisplay) estimatedTotalDisplay.textContent = formatCurrency(total); // Display formatted

    // Store values for form submission
    roomSelect.setAttribute('data-calculated-total', total);
    roomSelect.setAttribute('data-calculated-nights', nights);
    roomSelect.setAttribute('data-room-price', price);

    console.log('Final calculation:', { price, nights, total, formattedTotal: formatCurrency(total) });

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

        // Check max guests
        const roomSelect = document.getElementById('room_type_id');
        const selectedOption = roomSelect.options[roomSelect.selectedIndex];
        const maxGuests = parseInt(selectedOption.dataset.maxGuests) || 2;

        if (guests > maxGuests) {
            alert(`Loại phòng này chỉ phù hợp cho tối đa ${maxGuests} khách`);
            return false;
        }

        return true;
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

        // Validate phone format (Vietnamese) - more flexible
        const cleanPhone = phone.replace(/[\s\-\.]/g, '');
        const phoneRegex = /^(0|\+84|84)[1-9][0-9]{8,9}$/;
        if (!phoneRegex.test(cleanPhone)) {
            alert('Số điện thoại không hợp lệ. Vui lòng nhập số điện thoại Việt Nam (VD: 0901234567)');
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

    document.getElementById('summary_room_type').textContent = roomName;
    document.getElementById('summary_guests').textContent = document.getElementById('num_guests').value + ' khách';
    document.getElementById('summary_checkin').textContent = formatDate(document.getElementById('check_in_date').value);
    document.getElementById('summary_checkout').textContent = formatDate(document.getElementById('check_out_date').value);
    document.getElementById('summary_nights').textContent = document.getElementById('num_nights').textContent;
    document.getElementById('summary_name').textContent = document.getElementById('guest_name').value;
    document.getElementById('summary_email').textContent = document.getElementById('guest_email').value;
    document.getElementById('summary_phone').textContent = document.getElementById('guest_phone').value;
    document.getElementById('summary_total').textContent = document.getElementById('estimated_total').textContent;
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
    const roomSelect = document.getElementById('room_type_id');
    const calculatedTotal = roomSelect.getAttribute('data-calculated-total') || '0';
    const calculatedNights = roomSelect.getAttribute('data-calculated-nights') || '0';
    const roomPrice = roomSelect.getAttribute('data-room-price') || '0';

    formData.append('calculated_total', calculatedTotal);
    formData.append('calculated_nights', calculatedNights);
    formData.append('room_price', roomPrice);

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

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}
