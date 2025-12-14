/**
 * Contact Form Handler
 * Aurora Hotel Plaza
 */

document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (!contactForm) return;
    
    contactForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Disable button and show loading
        setLoading(true);
        
        // Get form data
        const formData = new FormData(contactForm);
        
        // Validate
        const name = formData.get('name').trim();
        const email = formData.get('email').trim();
        const phone = formData.get('phone').trim();
        const message = formData.get('message').trim();
        
        if (!name || !email || !phone || !message) {
            showToast('Vui lòng điền đầy đủ thông tin bắt buộc', 'error');
            setLoading(false);
            return;
        }
        
        if (!isValidEmail(email)) {
            showToast('Email không hợp lệ', 'error');
            setLoading(false);
            return;
        }
        
        if (!isValidPhone(phone)) {
            showToast('Số điện thoại không hợp lệ', 'error');
            setLoading(false);
            return;
        }
        
        if (message.length < 10) {
            showToast('Nội dung tin nhắn quá ngắn (tối thiểu 10 ký tự)', 'error');
            setLoading(false);
            return;
        }
        
        try {
            const response = await fetch('api/contact.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                
                // Reset form (chỉ reset message và subject)
                contactForm.querySelector('textarea[name="message"]').value = '';
                contactForm.querySelector('select[name="subject"]').selectedIndex = 0;
                
                // Show success modal
                showSuccessModal(data.submission_id);
            } else {
                showToast(data.message || 'Có lỗi xảy ra', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Có lỗi xảy ra khi gửi liên hệ. Vui lòng thử lại sau.', 'error');
        } finally {
            setLoading(false);
        }
    });
    
    // Helper functions
    function setLoading(loading) {
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoading = submitBtn.querySelector('.btn-loading');
        
        if (loading) {
            submitBtn.disabled = true;
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');
        } else {
            submitBtn.disabled = false;
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
        }
    }
    
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    function isValidPhone(phone) {
        const cleaned = phone.replace(/[^0-9]/g, '');
        return cleaned.length >= 10 && cleaned.length <= 11;
    }
    
    function showSuccessModal(submissionId) {
        // Create modal with liquid glass style
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="fixed inset-0 bg-black/60 backdrop-blur-md" onclick="this.parentElement.remove()"></div>
            <div class="relative bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl rounded-3xl shadow-2xl max-w-md w-full p-10 text-center animate-scale-in border border-white/20">
                <div class="w-24 h-24 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center mx-auto mb-8 shadow-lg shadow-green-500/30">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Gửi thành công</h3>
                <p class="text-gray-600 dark:text-gray-300 mb-6">Cảm ơn bạn đã liên hệ với Aurora Hotel Plaza</p>
                <div class="bg-gradient-to-br from-amber-50 to-amber-100/80 dark:from-amber-900/30 dark:to-amber-800/20 backdrop-blur-sm rounded-2xl p-6 mb-6 border border-amber-200/50 dark:border-amber-700/30">
                    <p class="text-sm text-amber-700 dark:text-amber-300 mb-2 uppercase tracking-wider font-semibold">Mã liên hệ của bạn</p>
                    <p class="text-3xl font-bold text-amber-600 dark:text-amber-400 tracking-widest font-mono">${submissionId}</p>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-8 leading-relaxed">
                    Chúng tôi đã gửi email xác nhận đến địa chỉ email của bạn. Vui lòng kiểm tra hộp thư.
                </p>
                <button onclick="this.closest('.fixed').remove()" class="w-full bg-gradient-to-r from-accent to-amber-500 text-white rounded-xl px-6 py-4 font-bold hover:shadow-lg hover:shadow-accent/30 transition-all duration-300 hover:-translate-y-0.5">
                    Đóng
                </button>
            </div>
        `;
        document.body.appendChild(modal);
    }
});

/**
 * Toast notification function
 */
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    
    const icons = {
        success: 'check_circle',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };
    
    const toast = document.createElement('div');
    toast.className = `${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 animate-slide-in-right max-w-sm`;
    toast.innerHTML = `
        <span class="material-symbols-outlined">${icons[type]}</span>
        <span class="flex-1">${message}</span>
        <button onclick="this.parentElement.remove()" class="hover:opacity-70">
            <span class="material-symbols-outlined text-sm">close</span>
        </button>
    `;
    
    container.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.classList.add('animate-slide-out-right');
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}
