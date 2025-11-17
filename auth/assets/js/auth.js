/**
 * Auth Pages JavaScript - Enhanced UX
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Password visibility toggle
    initPasswordToggle();
    
    // Form validation
    initFormValidation();
    
    // Loading state on submit
    initFormSubmit();
    
    // Auto-dismiss alerts
    autoDismissAlerts();
    
});

/**
 * Password Visibility Toggle
 */
function initPasswordToggle() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        // Create wrapper if not exists
        if (!input.parentElement.classList.contains('password-wrapper')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'password-wrapper';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
        }
        
        // Create toggle button
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'password-toggle';
        toggleBtn.innerHTML = '<span class="material-symbols-outlined">visibility</span>';
        toggleBtn.setAttribute('aria-label', 'Toggle password visibility');
        
        // Insert after input
        input.parentElement.appendChild(toggleBtn);
        
        // Toggle functionality
        toggleBtn.addEventListener('click', function() {
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            
            const icon = type === 'password' ? 'visibility' : 'visibility_off';
            toggleBtn.innerHTML = `<span class="material-symbols-outlined">${icon}</span>`;
        });
    });
}

/**
 * Form Validation
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input[required]');
        
        inputs.forEach(input => {
            // Real-time validation
            input.addEventListener('blur', function() {
                validateInput(input);
            });
            
            input.addEventListener('input', function() {
                if (input.classList.contains('is-invalid')) {
                    validateInput(input);
                }
            });
        });
        
        // Password match validation
        const password = form.querySelector('input[name="password"]');
        const confirmPassword = form.querySelector('input[name="confirm_password"]');
        
        if (password && confirmPassword) {
            confirmPassword.addEventListener('input', function() {
                if (confirmPassword.value !== password.value) {
                    setInvalid(confirmPassword, 'Mật khẩu không khớp');
                } else {
                    setValid(confirmPassword);
                }
            });
        }
    });
}

/**
 * Validate Input
 */
function validateInput(input) {
    const value = input.value.trim();
    const type = input.type;
    const name = input.name;
    
    // Remove previous error
    removeError(input);
    
    // Required check
    if (input.hasAttribute('required') && !value) {
        setInvalid(input, 'Trường này là bắt buộc');
        return false;
    }
    
    // Email validation
    if (type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            setInvalid(input, 'Email không hợp lệ');
            return false;
        }
    }
    
    // Phone validation (Vietnamese)
    if (name === 'phone' && value) {
        const phoneRegex = /^(0|\+84)[0-9]{9,10}$/;
        if (!phoneRegex.test(value.replace(/\s/g, ''))) {
            setInvalid(input, 'Số điện thoại không hợp lệ');
            return false;
        }
    }
    
    // Password length
    if (type === 'password' && value && value.length < 6) {
        setInvalid(input, 'Mật khẩu phải có ít nhất 6 ký tự');
        return false;
    }
    
    setValid(input);
    return true;
}

/**
 * Set Invalid State
 */
function setInvalid(input, message) {
    input.classList.add('is-invalid');
    input.classList.remove('is-valid');
    
    // Add error message
    let errorDiv = input.parentElement.querySelector('.error-message');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'error-message text-xs text-red-600 dark:text-red-400 mt-1';
        input.parentElement.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

/**
 * Set Valid State
 */
function setValid(input) {
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
    removeError(input);
}

/**
 * Remove Error
 */
function removeError(input) {
    const errorDiv = input.parentElement.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * Form Submit with Loading State
 */
function initFormSubmit() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            
            if (submitBtn) {
                // Validate all inputs
                const inputs = form.querySelectorAll('input[required]');
                let isValid = true;
                
                inputs.forEach(input => {
                    if (!validateInput(input)) {
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    return;
                }
                
                // Add loading state
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span>Đang xử lý...</span>';
                
                // Reset after 10 seconds (fallback)
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');
                    submitBtn.innerHTML = originalText;
                }, 10000);
            }
        });
    });
}

/**
 * Auto Dismiss Alerts
 */
function autoDismissAlerts() {
    const alerts = document.querySelectorAll('.alert-success');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
}

/**
 * Show Password Strength
 */
function showPasswordStrength(input) {
    const password = input.value;
    let strength = 0;
    
    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    const strengthTexts = ['Rất yếu', 'Yếu', 'Trung bình', 'Mạnh', 'Rất mạnh'];
    const strengthColors = ['#ef4444', '#f59e0b', '#eab308', '#84cc16', '#22c55e'];
    
    let strengthDiv = input.parentElement.querySelector('.password-strength');
    if (!strengthDiv) {
        strengthDiv = document.createElement('div');
        strengthDiv.className = 'password-strength text-xs mt-1';
        input.parentElement.appendChild(strengthDiv);
    }
    
    if (password.length > 0) {
        strengthDiv.innerHTML = `
            <div class="flex items-center gap-2">
                <div class="flex-1 h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-300" 
                         style="width: ${(strength / 5) * 100}%; background: ${strengthColors[strength - 1]}"></div>
                </div>
                <span style="color: ${strengthColors[strength - 1]}">${strengthTexts[strength - 1]}</span>
            </div>
        `;
    } else {
        strengthDiv.innerHTML = '';
    }
}

// Add password strength indicator to password inputs
document.addEventListener('DOMContentLoaded', function() {
    const passwordInputs = document.querySelectorAll('input[name="password"]');
    passwordInputs.forEach(input => {
        input.addEventListener('input', function() {
            showPasswordStrength(input);
        });
    });
});
