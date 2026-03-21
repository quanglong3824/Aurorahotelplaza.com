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
        if (loading) {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            const icon = submitBtn.querySelector('.material-symbols-outlined');
            if (icon) {
                icon.textContent = 'hourglass_empty';
                icon.classList.add('animate-pulse');
            }
        } else {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            const icon = submitBtn.querySelector('.material-symbols-outlined');
            if (icon) {
                icon.textContent = 'send';
                icon.classList.remove('animate-pulse');
            }
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
        // Scroll to top for better modal visibility
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Create modal with liquid glass style
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center p-4';
        modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0;';
        modal.innerHTML = `
            <div class="fixed inset-0 bg-black/60 backdrop-blur-md" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0;" onclick="this.parentElement.remove()"></div>
            <div class="relative bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl rounded-3xl shadow-2xl max-w-md w-full p-10 text-center animate-scale-in border border-white/20" style="position: relative; z-index: 10;">
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
        
        // Prevent body scroll when modal is open
        document.body.style.overflow = 'hidden';
        
        // Restore scroll when modal is closed
        modal.querySelector('button').addEventListener('click', () => {
            document.body.style.overflow = '';
        });
        modal.querySelector('.fixed.inset-0.bg-black\\/60').addEventListener('click', () => {
            document.body.style.overflow = '';
        });
    }
});

/**
 * Track Contact Submission Status
 */
async function trackContact() {
    const input = document.getElementById('contactTrackCode');
    if (!input) return;
    
    const code = input.value.trim();
    
    if (!code) {
        showToast('Vui lòng nhập mã liên hệ', 'warning');
        return;
    }
    
    try {
        const response = await fetch('api/contact-track.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ code: code })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showContactStatusModal(result.data);
            input.value = '';
        } else {
            showToast(result.message || 'Không tìm thấy liên hệ', 'error');
        }
    } catch (error) {
        console.error('Error tracking contact:', error);
        showToast('Có lỗi xảy ra khi tra cứu. Vui lòng thử lại sau.', 'error');
    }
}

/**
 * Show Contact Status Modal
 */
function showContactStatusModal(data) {
    const statusText = {
        'new': 'Mới nhận',
        'processing': 'Đang xử lý',
        'replied': 'Đã phản hồi',
        'closed': 'Đã đóng'
    };
    
    const statusColors = {
        'new': 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        'processing': 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        'replied': 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        'closed': 'bg-gray-500/20 text-gray-400 border-gray-500/30'
    };
    
    const statusIcons = {
        'new': 'mark_email_unread',
        'processing': 'sync',
        'replied': 'forward_to_inbox',
        'closed': 'task_alt'
    };

    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center p-4';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0;';
    modal.innerHTML = `
        <div class="fixed inset-0 bg-black/70 backdrop-blur-md" onclick="this.parentElement.remove(); document.body.style.overflow = '';"></div>
        <div class="relative bg-slate-900 border border-white/10 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-scale-in">
            <!-- Header -->
            <div class="p-6 bg-gradient-to-r from-slate-800 to-slate-900 border-b border-white/5 flex items-center justify-between">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-accent">info</span>
                    Chi tiết liên hệ
                </h3>
                <button onclick="this.closest('.fixed').remove(); document.body.style.overflow = '';" class="text-white/50 hover:text-white transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <!-- Body -->
            <div class="p-6 space-y-6">
                <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/5">
                    <div>
                        <p class="text-xs text-white/40 uppercase tracking-wider font-semibold mb-1">Mã liên hệ</p>
                        <p class="text-2xl font-mono font-bold text-accent tracking-widest">#${data.contact_code}</p>
                    </div>
                    <div class="px-4 py-2 rounded-xl border ${statusColors[data.status] || ''} flex flex-col items-center">
                        <span class="material-symbols-outlined text-2xl mb-1">${statusIcons[data.status] || 'info'}</span>
                        <span class="text-xs font-bold uppercase">${statusText[data.status] || data.status}</span>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-white/30">person</span>
                        <div>
                            <p class="text-xs text-white/40 mb-0.5">Người gửi</p>
                            <p class="text-white font-medium">${data.name}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-white/30">subject</span>
                        <div>
                            <p class="text-xs text-white/40 mb-0.5">Chủ đề</p>
                            <p class="text-white font-medium">${data.subject}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-white/30">event</span>
                        <div>
                            <p class="text-xs text-white/40 mb-0.5">Ngày gửi</p>
                            <p class="text-white font-medium">${data.created_at}</p>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-white/5 rounded-xl border border-white/5 italic text-sm text-white/60">
                        "${data.message_preview}"
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="p-6 bg-slate-800/50 border-t border-white/5">
                <button onclick="this.closest('.fixed').remove(); document.body.style.overflow = '';" class="w-full py-3 bg-white/10 hover:bg-white/20 text-white font-bold rounded-xl transition-all">
                    Đóng
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
}

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
