// Booking Form JavaScript

let currentStep = 1;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('check_in_date').min = today;
    
    // Event listeners
    document.getElementById('check_in_date').addEventListener('change', updateCheckoutMin);
    document.getElementById('check_out_date').addEventListener('change', calculateNights);
    document.getElementById('room_type_id').addEventListener('change', calculateTotal);
    
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
        calculateNights();
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
            document.getElementById('num_nights').textContent = diffDays + ' đêm';
            calculateTotal();
            return diffDays;
        }
    }
    
    document.getElementById('num_nights').textContent = '0 đêm';
    return 0;
}

// Calculate total price
function calculateTotal() {
    const roomSelect = document.getElementById('room_type_id');
    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const price = parseFloat(selectedOption.dataset.price) || 0;
    const nights = calculateNights();
    
    const total = price * nights;
    document.getElementById('estimated_total').textContent = formatCurrency(total);
    
    return total;
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
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
