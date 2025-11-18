/**
 * Auth Pages JavaScript - Modern Enhanced UX
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize all features
    initPasswordToggle();
    initPasswordStrength();
    initPasswordMatch();
    initFormValidation();
    initFormSubmit();
    initSocialLogin();
    autoDismissAlerts();
    initAnimations();
    
});

/**
 * Password Visibility Toggle
 */
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const toggle = input.parentElement.querySelector('.password-toggle');
    
    if (input.type === 'password') {
        input.type = 'text';
        toggle.innerHTML = '<span class="material-symbols-outlined">visibility_off</span>';
    } else {
        input.type = 'password';
        toggle.innerHTML = '<span class="material-symbols-outlined">visibility</span>';
    }
}

function initPasswordToggle() {
    // Password toggle functionality is handled by onclick in HTML
    // This function can be used for additional initialization if needed
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
                    setInvalid(confirmPassword);
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
        setInvalid(input);
        return false;
    }
    
    // Email validation
    if (type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            setInvalid(input);
            return false;
        }
    }
    
    // Phone validation (Vietnamese)
    if (name === 'phone' && value) {
        const phoneRegex = /^(0|\+84)[0-9]{9,10}$/;
        if (!phoneRegex.test(value.replace(/\s/g, ''))) {
            setInvalid(input);
            return false;
        }
    }
    
    // Password length
    if (type === 'password' && value && value.length < 6) {
        setInvalid(input);
        return false;
    }
    
    setValid(input);
    return true;
}

/**
 * Set Invalid State - Only visual feedback, no text message
 */
function setInvalid(input, message) {
    input.classList.add('is-invalid');
    input.classList.remove('is-valid');
    
    // Remove any existing error message - only show visual feedback
    removeError(input);
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
 * Enhanced Form Submit with Loading State
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
                let firstInvalidInput = null;
                
                inputs.forEach(input => {
                    if (!validateInput(input)) {
                        isValid = false;
                        if (!firstInvalidInput) {
                            firstInvalidInput = input;
                        }
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    // Focus on first invalid input
                    if (firstInvalidInput) {
                        firstInvalidInput.focus();
                        firstInvalidInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return;
                }
                
                // Add loading state
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                
                const btnText = submitBtn.querySelector('.btn-text');
                const btnIcon = submitBtn.querySelector('.btn-icon .material-symbols-outlined');
                const originalText = btnText.textContent;
                
                btnText.textContent = 'Đang xử lý...';
                if (btnIcon) {
                    btnIcon.textContent = 'hourglass_empty';
                    btnIcon.style.animation = 'spin 1s linear infinite';
                }
                
                // Reset after 15 seconds (fallback)
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');
                    btnText.textContent = originalText;
                    if (btnIcon) {
                        btnIcon.textContent = 'arrow_forward';
                        btnIcon.style.animation = '';
                    }
                }, 15000);
            }
        });
    });
}

/**
 * Auto Dismiss Alerts with Enhanced Animation
 */
function autoDismissAlerts() {
    const alerts = document.querySelectorAll('.alert-success');
    
    alerts.forEach(alert => {
        // Add close button
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '<span class="material-symbols-outlined">close</span>';
        closeBtn.className = 'alert-close';
        closeBtn.style.cssText = `
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s ease;
            padding: 0.25rem;
            border-radius: 0.25rem;
        `;
        
        closeBtn.addEventListener('mouseenter', () => closeBtn.style.opacity = '1');
        closeBtn.addEventListener('mouseleave', () => closeBtn.style.opacity = '0.7');
        
        alert.style.position = 'relative';
        alert.appendChild(closeBtn);
        
        function dismissAlert() {
            alert.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-15px) scale(0.95)';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 400);
        }
        
        // Auto dismiss after 6 seconds
        setTimeout(dismissAlert, 6000);
        
        // Manual dismiss
        closeBtn.addEventListener('click', dismissAlert);
    });
}

/**
 * Password Strength Indicator
 */
function initPasswordStrength() {
    const passwordInput = document.getElementById('password');
    if (!passwordInput) return;
    
    const strengthIndicator = document.getElementById('passwordStrength');
    if (!strengthIndicator) return;
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        updatePasswordStrength(strengthIndicator, strength, password.length > 0);
    });
}

function calculatePasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 6) score += 1;
    if (password.length >= 10) score += 1;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score += 1;
    if (/[0-9]/.test(password)) score += 1;
    if (/[^a-zA-Z0-9]/.test(password)) score += 1;
    
    return Math.min(score, 4);
}

function updatePasswordStrength(indicator, strength, show) {
    const strengthClasses = ['strength-weak', 'strength-fair', 'strength-good', 'strength-strong'];
    const strengthTexts = ['Yếu', 'Trung bình', 'Tốt', 'Mạnh'];
    
    // Remove all strength classes
    strengthClasses.forEach(cls => indicator.classList.remove(cls));
    
    if (show && strength > 0) {
        indicator.classList.add('show');
        indicator.classList.add(strengthClasses[strength - 1]);
        indicator.querySelector('.strength-text').textContent = `Độ mạnh: ${strengthTexts[strength - 1]}`;
    } else {
        indicator.classList.remove('show');
    }
}

/**
 * Password Match Indicator
 */
function initPasswordMatch() {
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirmPassword');
    const matchIndicator = document.getElementById('passwordMatch');
    
    if (!passwordInput || !confirmInput || !matchIndicator) return;
    
    function checkPasswordMatch() {
        const password = passwordInput.value;
        const confirm = confirmInput.value;
        
        if (confirm.length === 0) {
            matchIndicator.classList.remove('show');
            return;
        }
        
        matchIndicator.classList.add('show');
        
        if (password === confirm) {
            matchIndicator.classList.remove('no-match');
            matchIndicator.classList.add('match');
            matchIndicator.textContent = '✓ Mật khẩu khớp';
        } else {
            matchIndicator.classList.remove('match');
            matchIndicator.classList.add('no-match');
            matchIndicator.textContent = '✗ Mật khẩu không khớp';
        }
    }
    
    confirmInput.addEventListener('input', checkPasswordMatch);
    passwordInput.addEventListener('input', checkPasswordMatch);
}

/**
 * Social Login Handlers
 */
function initSocialLogin() {
    const googleBtn = document.getElementById('googleLoginBtn');
    const facebookBtn = document.querySelector('.facebook-btn');
    
    if (googleBtn) {
        googleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Add loading state
            const originalText = this.textContent;
            this.disabled = true;
            this.innerHTML = `
                <svg viewBox="0 0 24 24" width="20" height="20" style="animation: spin 1s linear infinite;">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/>
                    <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                Đang chuyển hướng...
            `;
            
            // Redirect to Google OAuth
            setTimeout(() => {
                window.location.href = './login-google.php';
            }, 500);
        });
    }
    
    if (facebookBtn) {
        facebookBtn.addEventListener('click', function() {
            // Placeholder for Facebook OAuth integration
            console.log('Facebook login clicked');
            // window.location.href = '/auth/facebook';
        });
    }
}

/**
 * Enhanced Animations
 */
function initAnimations() {
    // Stagger animation for form fields
    const formFields = document.querySelectorAll('.form-group');
    formFields.forEach((field, index) => {
        field.style.animationDelay = `${index * 0.1}s`;
        field.classList.add('animate-fade-in-up');
    });
    
    // Floating label effect
    const inputs = document.querySelectorAll('.form-input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
        
        // Check if input has value on load
        if (input.value) {
            input.parentElement.classList.add('focused');
        }
    });
}

/**
 * Enhanced Form Validation with Better UX
 */
function validateInput(input) {
    const value = input.value.trim();
    const type = input.type;
    const name = input.name;
    
    // Remove previous validation states
    input.classList.remove('is-invalid', 'is-valid');
    removeError(input);
    
    // Required check
    if (input.hasAttribute('required') && !value) {
        setInvalid(input);
        return false;
    }
    
    // Email validation
    if (type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            setInvalid(input);
            return false;
        }
    }
    
    // Phone validation (Vietnamese format)
    if (name === 'phone' && value) {
        const phoneRegex = /^(0|\+84)[0-9]{9,10}$/;
        if (!phoneRegex.test(value.replace(/\s/g, ''))) {
            setInvalid(input);
            return false;
        }
    }
    
    // Password validation
    if (name === 'password' && value) {
        if (value.length < 6) {
            setInvalid(input);
            return false;
        }
    }
    
    // Confirm password validation
    if (name === 'confirm_password' && value) {
        const passwordInput = document.querySelector('input[name="password"]');
        if (passwordInput && value !== passwordInput.value) {
            setInvalid(input);
            return false;
        }
    }
    
    // Full name validation
    if (name === 'full_name' && value) {
        if (value.length < 2) {
            setInvalid(input);
            return false;
        }
    }
    
    setValid(input);
    return true;
}
