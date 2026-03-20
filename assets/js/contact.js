/**
 * Contact Form Handler
 * Aurora Hotel Plaza
 */

document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const submitBtn = document.getElementById('submitBtn');
    const lookupForm = document.getElementById('lookupForm');
    const lookupBtn = document.getElementById('lookupBtn');
    const lookupResult = document.getElementById('lookupResult');
    
    // --- Contact Form Submission ---
    if (contactForm) {
        contactForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Disable button and show loading immediately
            setLoading(submitBtn, true, 'send');
            
            // Get form data
            const formData = new FormData(contactForm);
            
            // Validate basic inputs
            const name = formData.get('name').trim();
            const email = formData.get('email').trim();
            const phone = formData.get('phone').trim();
            const message = formData.get('message').trim();
            
            if (!name || !email || !phone || !message) {
                showToast('Vui lòng điền đầy đủ thông tin bắt buộc.', 'warning');
                setLoading(submitBtn, false, 'send');
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
                    
                    // Reset message only, keep info
                    contactForm.querySelector('textarea[name="message"]').value = '';
                    
                    // Show success modal with the ID
                    showSuccessModal(data.submission_id);
                } else {
                    showToast(data.message || 'Có lỗi xảy ra', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Không thể gửi liên hệ. Vui lòng kiểm tra kết nối mạng.', 'error');
            } finally {
                setLoading(submitBtn, false, 'send');
            }
        });
    }

    // --- Status Lookup Handler ---
    if (lookupForm) {
        lookupForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const code = document.getElementById('lookupCode').value.trim();
            if (!code) return;
            
            setLoading(lookupBtn, true, 'search');
            lookupResult.classList.add('hidden');
            
            try {
                const response = await fetch(`api/contact-status.php?code=${encodeURIComponent(code)}`);
                const data = await response.json();
                
                if (data.success) {
                    const status = data.data;
                    let responseHtml = '';
                    
                    if (status.has_response) {
                        responseHtml = `
                            <div class="mt-4 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
                                <p class="text-[10px] text-emerald-400 uppercase font-bold mb-2">Phản hồi từ Aurora:</p>
                                <p class="text-sm text-white/90 italic">"${status.response}"</p>
                                <p class="text-[10px] text-white/40 mt-2">Ngày phản hồi: ${status.responded_at}</p>
                            </div>
                        `;
                    }

                    lookupResult.innerHTML = `
                        <div class="p-5 rounded-2xl bg-white/5 border border-white/10 animate-fade-in">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-[10px] text-white/40 uppercase font-bold tracking-widest">Kết quả tra cứu</span>
                                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-${status.color}-500/20 text-${status.color}-400 text-[10px] font-bold border border-${status.color}-500/30">
                                    <span class="material-symbols-outlined text-[14px]">${status.icon}</span>
                                    ${status.status}
                                </div>
                            </div>
                            <h4 class="text-white font-bold mb-1">${status.subject}</h4>
                            <p class="text-xs text-white/60 mb-3">Gửi bởi: ${status.name} • ${status.created_at}</p>
                            ${responseHtml}
                        </div>
                    `;
                    lookupResult.classList.remove('hidden');
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                showToast('Lỗi khi tra cứu trạng thái.', 'error');
            } finally {
                setLoading(lookupBtn, false, 'search');
            }
        });
    }
    
    // Global Helper: setLoading
    function setLoading(btn, loading, originalIcon = 'send') {
        if (!btn) return;
        const icon = btn.querySelector('.material-symbols-outlined');
        
        if (loading) {
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            if (icon) {
                icon.textContent = 'hourglass_empty';
                icon.classList.add('animate-spin');
            }
        } else {
            btn.disabled = false;
            btn.classList.remove('opacity-70', 'cursor-not-allowed');
            if (icon) {
                icon.textContent = originalIcon;
                icon.classList.remove('animate-spin');
            }
        }
    }
    
    function showSuccessModal(submissionId) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="absolute inset-0 bg-black/80 backdrop-blur-md" onclick="this.parentElement.remove(); document.body.style.overflow = '';"></div>
            <div class="relative z-10 bg-slate-900 border border-white/10 rounded-3xl shadow-2xl max-w-md w-full p-8 text-center animate-scale-in">
                <div class="w-20 h-20 bg-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-emerald-500/20">
                    <span class="material-symbols-outlined text-4xl text-white">check</span>
                </div>
                <h3 class="text-2xl font-bold text-white mb-2">Gửi thành công!</h3>
                <p class="text-white/60 mb-6">Cảm ơn bạn đã liên hệ. Chúng tôi đã nhận được tin nhắn.</p>
                <div class="bg-white/5 border border-white/10 rounded-2xl p-4 mb-6">
                    <p class="text-[10px] text-white/40 uppercase font-bold tracking-widest mb-1">Mã tra cứu của bạn</p>
                    <p class="text-2xl font-mono font-bold text-accent tracking-widest">${submissionId}</p>
                </div>
                <button onclick="this.closest('.fixed').remove(); document.body.style.overflow = '';" class="btn-glass-gold w-full justify-center">Đóng</button>
            </div>
        `;
        document.body.appendChild(modal);
        document.body.style.overflow = 'hidden';
    }
});

/**
 * Toast notification function
 */
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const colors = {
        success: 'bg-emerald-600',
        error: 'bg-red-600',
        warning: 'bg-amber-600',
        info: 'bg-blue-600'
    };
    
    const icons = {
        success: 'check_circle',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };
    
    const toast = document.createElement('div');
    toast.className = `${colors[type]} text-white px-5 py-3.5 rounded-xl shadow-2xl flex items-center gap-3 animate-slide-in-right max-w-xs border border-white/10`;
    toast.innerHTML = `
        <span class="material-symbols-outlined text-xl">${icons[type]}</span>
        <span class="text-sm font-medium flex-1">${message}</span>
    `;
    
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-x-full');
        toast.style.transition = 'all 0.5s ease';
        setTimeout(() => toast.remove(), 500);
    }, 4000);
}
