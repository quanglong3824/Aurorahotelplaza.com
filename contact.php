<?php
session_start();
require_once 'config/database.php';
require_once 'config/environment.php';
require_once 'helpers/language.php';
initLanguage();

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $user_email = $user_phone = '';

if ($is_logged_in) {
    try {
        $db = getDB();
        if ($db) {
            $stmt = $db->prepare("SELECT full_name, email, phone FROM users WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $user_name = $user['full_name'] ?? '';
                $user_email = $user['email'] ?? '';
                $user_phone = $user['phone'] ?? '';
            }
        }
    } catch (Exception $e) {
        $user_name = $_SESSION['user_name'] ?? '';
        $user_email = $_SESSION['user_email'] ?? '';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="<?php echo getLang(); ?>">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
<title><?php _e('contact_page.title'); ?></title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/liquid-glass.css">
<style>
/* ========== CONTACT PAGE - MODERN GLASS STYLE ========== */

/* Hero Section */
.contact-hero {
    position: relative;
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(17, 24, 39, 0.85), rgba(17, 24, 39, 0.7)), 
                url('assets/img/hero-banner/aurora-hotel-bien-hoa-3.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    padding: 120px 20px 80px;
}

.contact-hero::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 120px;
    background: linear-gradient(to top, var(--background-light), transparent);
    z-index: 1;
}

.dark .contact-hero::after {
    background: linear-gradient(to top, var(--background-dark), transparent);
}

/* Hero Glass Card */
.hero-glass-content {
    position: relative;
    z-index: 10;
    max-width: 700px;
    text-align: center;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 2rem;
    padding: 3rem;
}

/* Contact Grid */
.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 3rem;
    align-items: start;
}

/* Info Cards - Liquid Glass Style */
.info-card-glass {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0.06) 50%, rgba(255, 255, 255, 0.03) 100%);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 2rem;
    padding: 2.5rem;
    position: relative;
    overflow: hidden;
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.12),
        0 2px 8px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.25);
}

.dark .info-card-glass {
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.5) 0%, rgba(30, 41, 59, 0.6) 100%);
    border-color: rgba(255, 255, 255, 0.1);
}

.info-card-glass::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.08), transparent);
}

.info-card-glass::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -30%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(212, 175, 55, 0.08) 0%, transparent 70%);
    border-radius: 50%;
    animation: shimmer 8s ease-in-out infinite;
}

@keyframes shimmer {
    0%, 100% { opacity: 0.5; transform: translate(0, 0); }
    50% { opacity: 1; transform: translate(-10px, 10px); }
}

.info-item-glass {
    display: flex;
    gap: 1rem;
    padding: 1.25rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    position: relative;
    z-index: 1;
}

.dark .info-item-glass {
    border-bottom-color: rgba(255, 255, 255, 0.08);
}

.info-item-glass:last-child {
    border-bottom: none;
}

.info-icon-glass {
    width: 3.5rem;
    height: 3.5rem;
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.12) 0%, rgba(212, 175, 55, 0.06) 50%, rgba(212, 175, 55, 0.03) 100%);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(212, 175, 55, 0.18);
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.info-item-glass:hover .info-icon-glass {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(212, 175, 55, 0.15);
}

.info-icon-glass .material-symbols-outlined {
    font-size: 1.5rem;
    color: #d4af37;
}

.info-title-glass {
    font-weight: 700;
    font-size: 1rem;
    margin-bottom: 0.375rem;
    color: var(--text-primary-light);
}

.dark .info-title-glass {
    color: var(--text-primary-dark);
}

.info-text-glass {
    font-size: 0.9375rem;
    color: var(--text-secondary-light);
    line-height: 1.6;
}

.dark .info-text-glass {
    color: var(--text-secondary-dark);
}

.info-text-glass a {
    color: var(--text-primary-light);
    transition: color 0.3s;
}

.dark .info-text-glass a {
    color: var(--text-primary-dark);
}

.info-text-glass a:hover {
    color: #d4af37;
}

/* Social Links */
.social-glass {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.social-btn-glass {
    width: 3rem;
    height: 3rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    transition: all 0.3s;
}

.social-btn-glass:hover {
    background: #d4af37;
    border-color: #d4af37;
    transform: translateY(-3px);
}

.social-btn-glass svg {
    width: 1.25rem;
    height: 1.25rem;
}

/* Form Card */
.form-card-glass {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    border-radius: 2rem;
    padding: 2.5rem;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
}

.dark .form-card-glass {
    background: rgba(30, 41, 59, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
}

.form-title-glass {
    font-family: 'Playfair Display', serif;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary-light);
    margin-bottom: 1.5rem;
}

.dark .form-title-glass {
    color: var(--text-primary-dark);
}

.logged-notice-glass {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.2);
    border-radius: 0.75rem;
    color: #10b981;
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
}

.form-row-glass {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group-glass {
    margin-bottom: 1rem;
}

.form-label-glass {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary-light);
    margin-bottom: 0.5rem;
}

.dark .form-label-glass {
    color: var(--text-primary-dark);
}

.form-input-glass {
    width: 100%;
    padding: 0.875rem 1rem;
    background: rgba(0, 0, 0, 0.03);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 0.75rem;
    font-size: 0.9375rem;
    color: var(--text-primary-light);
    transition: all 0.3s;
}

.dark .form-input-glass {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
    color: var(--text-primary-dark);
}

.form-input-glass:focus {
    outline: none;
    border-color: #d4af37;
    box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
}

.form-input-glass.readonly {
    background: rgba(0, 0, 0, 0.06);
    cursor: not-allowed;
}

.form-textarea-glass {
    min-height: 150px;
    resize: vertical;
}

.btn-submit-glass {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: white;
    border: none;
    border-radius: 0.75rem;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
}

.btn-submit-glass:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
}

/* Map Section */
.map-glass-container {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 2rem;
    padding: 1rem;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.dark .map-glass-container {
    background: rgba(30, 41, 59, 0.9);
    border-color: rgba(255, 255, 255, 0.1);
}

.map-glass-container iframe {
    border-radius: 1.5rem;
    width: 100%;
    height: 400px;
}

/* Responsive */
@media (max-width: 1024px) {
    .contact-grid {
        grid-template-columns: 1fr;
    }
    
    .info-card-glass {
        order: 2;
    }
    
    .form-card-glass {
        order: 1;
    }
}

@media (max-width: 768px) {
    .contact-hero {
        min-height: 50vh;
        padding: 100px 16px 60px;
    }
    
    .hero-glass-content {
        padding: 2rem;
    }
    
    .form-row-glass {
        grid-template-columns: 1fr;
    }
    
    .info-card-glass, .form-card-glass {
        padding: 1.5rem;
        border-radius: 1.5rem;
    }
}

@media (max-width: 480px) {
    .hero-glass-content {
        padding: 1.5rem;
        border-radius: 1.25rem;
    }
    
    .info-item-glass {
        flex-direction: column;
        text-align: center;
    }
    
    .info-icon-glass {
        margin: 0 auto;
    }
}
</style>
</head>

<body class="bg-background-light dark:bg-background-dark font-body text-text-primary-light dark:text-text-primary-dark">
<div class="relative flex min-h-screen w-full flex-col">
<?php include 'includes/header.php'; ?>

<main class="flex h-full grow flex-col">
    <!-- Hero Section -->
    <section class="contact-hero">
        <div class="hero-glass-content">
            <div class="glass-badge mb-4 inline-flex">
                <span class="material-symbols-outlined text-accent text-sm">support_agent</span>
                <?php _e('contact_page.support_24_7'); ?>
            </div>
            <h1 class="font-display text-3xl md:text-4xl font-bold text-white mb-4">
                <?php _e('contact_page.page_title'); ?>
            </h1>
            <p class="text-white/85 text-lg mb-6">
                <?php _e('contact_page.page_subtitle'); ?>
            </p>
            <div class="flex flex-wrap gap-3 justify-center">
                <a href="tel:+842513918888" class="btn-glass-primary">
                    <span class="material-symbols-outlined">phone</span>
                    <?php _e('contact_page.call_now'); ?>
                </a>
                <a href="#contact-form" class="btn-glass-secondary">
                    <span class="material-symbols-outlined">arrow_downward</span>
                    <?php _e('contact_page.send_message'); ?>
                </a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact-form" class="py-16 md:py-24">
        <div class="max-w-7xl mx-auto px-4">
            <div class="contact-grid">
                <!-- Info Card -->
                <div class="info-card-glass">
                    <h2 class="font-display text-2xl font-bold mb-6"><?php _e('contact_page.contact_info'); ?></h2>
                    <p class="text-white/80 mb-6"><?php _e('contact_page.contact_info_desc'); ?></p>
                    
                    <div class="info-item-glass">
                        <div class="info-icon-glass">
                            <span class="material-symbols-outlined">location_on</span>
                        </div>
                        <div>
                            <h3 class="info-title-glass"><?php _e('contact_page.address'); ?></h3>
                            <p class="info-text-glass">Số 253, Phạm Văn Thuận, KP2<br>Phường Tam Hiệp, Tỉnh Đồng Nai</p>
                        </div>
                    </div>
                    
                    <div class="info-item-glass">
                        <div class="info-icon-glass">
                            <span class="material-symbols-outlined">phone</span>
                        </div>
                        <div>
                            <h3 class="info-title-glass"><?php _e('contact_page.phone'); ?></h3>
                            <p class="info-text-glass"><a href="tel:+842513918888">(+84-251) 391.8888</a></p>
                        </div>
                    </div>
                    
                    <div class="info-item-glass">
                        <div class="info-icon-glass">
                            <span class="material-symbols-outlined">email</span>
                        </div>
                        <div>
                            <h3 class="info-title-glass"><?php _e('contact_page.email'); ?></h3>
                            <p class="info-text-glass">
                                <a href="mailto:info@aurorahotelplaza.com">info@aurorahotelplaza.com</a><br>
                                <a href="mailto:booking@aurorahotelplaza.com">booking@aurorahotelplaza.com</a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="info-item-glass">
                        <div class="info-icon-glass">
                            <span class="material-symbols-outlined">schedule</span>
                        </div>
                        <div>
                            <h3 class="info-title-glass"><?php _e('contact_page.working_hours'); ?></h3>
                            <p class="info-text-glass"><?php _e('contact_page.reception'); ?>: 24/7<br><?php _e('contact_page.restaurant'); ?>: 6:00 - 22:00</p>
                        </div>
                    </div>
                    
                    <!-- Social Links -->
                    <div class="social-glass">
                        <a href="#" class="social-btn-glass">
                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="social-btn-glass">
                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                        <a href="#" class="social-btn-glass">
                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="form-card-glass">
                    <h2 class="form-title-glass"><?php _e('contact_page.send_us_message'); ?></h2>
                    
                    <?php if ($is_logged_in): ?>
                    <div class="logged-notice-glass">
                        <span class="material-symbols-outlined">verified_user</span>
                        <span><?php _e('contact_page.logged_in_as'); ?> <strong><?php echo htmlspecialchars($user_email); ?></strong></span>
                    </div>
                    <?php endif; ?>
                    
                    <form id="contactForm">
                        <div class="form-row-glass">
                            <div class="form-group-glass">
                                <label class="form-label-glass"><?php _e('contact_page.full_name'); ?> <span class="text-red-500">*</span></label>
                                <input type="text" name="name" class="form-input-glass <?php echo $is_logged_in && $user_name ? 'readonly' : ''; ?>" 
                                       placeholder="<?php _e('contact_page.enter_name'); ?>" 
                                       value="<?php echo htmlspecialchars($user_name); ?>"
                                       <?php echo $is_logged_in && $user_name ? 'readonly' : ''; ?> required>
                            </div>
                            <div class="form-group-glass">
                                <label class="form-label-glass"><?php _e('contact_page.email'); ?> <span class="text-red-500">*</span></label>
                                <input type="email" name="email" class="form-input-glass <?php echo $is_logged_in && $user_email ? 'readonly' : ''; ?>" 
                                       placeholder="<?php _e('contact_page.enter_email'); ?>" 
                                       value="<?php echo htmlspecialchars($user_email); ?>"
                                       <?php echo $is_logged_in && $user_email ? 'readonly' : ''; ?> required>
                            </div>
                        </div>
                        
                        <div class="form-row-glass">
                            <div class="form-group-glass">
                                <label class="form-label-glass"><?php _e('contact_page.phone_number'); ?> <span class="text-red-500">*</span></label>
                                <input type="tel" name="phone" class="form-input-glass <?php echo $is_logged_in && $user_phone ? 'readonly' : ''; ?>" 
                                       placeholder="<?php _e('contact_page.enter_phone'); ?>" 
                                       value="<?php echo htmlspecialchars($user_phone); ?>"
                                       <?php echo $is_logged_in && $user_phone ? 'readonly' : ''; ?> required>
                            </div>
                            <div class="form-group-glass">
                                <label class="form-label-glass"><?php _e('contact_page.subject'); ?></label>
                                <select name="subject" class="form-input-glass">
                                    <option value="<?php _e('contact_page.subject_booking'); ?>"><?php _e('contact_page.subject_booking'); ?></option>
                                    <option value="<?php _e('contact_page.subject_event'); ?>"><?php _e('contact_page.subject_event'); ?></option>
                                    <option value="<?php _e('contact_page.subject_other'); ?>"><?php _e('contact_page.subject_other'); ?></option>
                                    <option value="<?php _e('contact_page.subject_feedback'); ?>"><?php _e('contact_page.subject_feedback'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group-glass">
                            <label class="form-label-glass"><?php _e('contact_page.message'); ?> <span class="text-red-500">*</span></label>
                            <textarea name="message" class="form-input-glass form-textarea-glass" rows="5" 
                                      placeholder="<?php _e('contact_page.enter_message'); ?>" required minlength="10"></textarea>
                        </div>
                        
                        <button type="submit" class="btn-submit-glass" id="submitBtn">
                            <span class="material-symbols-outlined">send</span>
                            <?php _e('contact_page.send_message'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="py-16 md:py-24 bg-surface-light dark:bg-surface-dark">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <span class="text-accent font-semibold text-sm uppercase tracking-wider">Vị trí</span>
                <h2 class="font-display text-3xl md:text-4xl font-bold mt-2 mb-4">Tìm đường đến Aurora</h2>
            </div>
            <div class="map-glass-container">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3917.0824374942376!2d106.84213347514152!3d10.957145355834111!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dc27705d362d%3A0xc1fb19ec2c2b1806!2zS2jDoWNoIHPhuqFuIEF1cm9yYQ!5e0!3m2!1svi!2s!4v1765630076897!5m2!1svi!2s"
                    allowfullscreen="" loading="lazy">
                </iframe>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
</div>

<div id="toast-container" class="fixed top-24 right-4 z-50 flex flex-col gap-2"></div>
<script src="assets/js/main.js"></script>
<script src="assets/js/contact.js"></script>
</body>
</html>