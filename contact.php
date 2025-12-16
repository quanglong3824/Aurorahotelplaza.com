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
    
    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://www.google.com" crossorigin>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="assets/css/fonts.css" as="style">
    <link rel="preload" href="assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg" as="image">
    
    <!-- Critical CSS inline for faster FCP -->
    <style>
        /* Critical CSS - Same pattern as auth pages for smooth scroll */
        body.glass-page{position:relative;background-image:url('assets/img/hero-banner/aurora-hotel-bien-hoa-1.jpg');background-size:cover;background-position:center;background-repeat:no-repeat;background-attachment:fixed}
        body.glass-page::before{content:'';position:fixed;inset:0;background:linear-gradient(135deg,rgba(17,24,39,.88),rgba(17,24,39,.75));z-index:1;pointer-events:none}
        body.glass-page>div{position:relative;z-index:2}
        .page-hero-glass{position:relative;min-height:60vh;display:flex;align-items:center;justify-content:center;padding:180px 20px 80px}
    </style>
    
    <!-- Tailwind CSS -->
    <script src="assets/js/tailwindcss-cdn.js"></script>
    <link href="assets/css/fonts.css" rel="stylesheet"/>
    <script src="assets/js/tailwind-config.js"></script>
    
    <!-- Main stylesheets with versioning -->
    <?php $css_version = '1.0.7'; ?>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo $css_version; ?>">
    <link rel="stylesheet" href="assets/css/pages-glass.css?v=<?php echo $css_version; ?>">
</head>

<body class="glass-page font-body text-white">
<div class="relative flex min-h-screen w-full flex-col">
    <?php include 'includes/header.php'; ?>

    <main class="flex h-full grow flex-col">
            <!-- Hero Section -->
            <section class="page-hero-glass">
                <div class="hero-glass-card">
                    <div class="glass-badge-pill mb-4 justify-center mx-auto">
                        <span class="material-symbols-outlined text-sm">support_agent</span>
                        <?php _e('contact_page.support_24_7'); ?>
                    </div>
                    
                    <h1 class="hero-title-glass">
                        <?php _e('contact_page.page_title'); ?>
                    </h1>
                    
                    <p class="hero-subtitle-glass">
                        <?php _e('contact_page.page_subtitle'); ?>
                    </p>
                    
                    <div class="flex flex-wrap gap-4 justify-center">
                        <a href="tel:+842513918888" class="btn-glass-gold">
                            <span class="material-symbols-outlined">phone</span>
                            <?php _e('contact_page.call_now'); ?>
                        </a>
                        <a href="#contact-form" class="btn-glass-outline">
                            <span class="material-symbols-outlined">arrow_downward</span>
                            <?php _e('contact_page.send_message'); ?>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Contact Form & Info Section -->
            <section id="contact-form" class="py-20 md:py-24 relative z-10">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="contact-grid-wrapper">
                        
                        <!-- Info Card (Left) -->
                        <div class="info-card-glass h-fit">
                            <h2 class="font-display text-2xl font-bold mb-6 text-white"><?php _e('contact_page.contact_info'); ?></h2>
                            <p class="text-white/80 mb-6 font-light"><?php _e('contact_page.contact_info_desc'); ?></p>
                            
                            <div class="space-y-2">
                                <div class="info-item">
                                    <div class="info-icon">
                                        <span class="material-symbols-outlined">location_on</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-white mb-1"><?php _e('contact_page.address'); ?></h3>
                                        <p class="text-white/70 text-sm">Số 253, Phạm Văn Thuận, KP2<br>Phường Tam Hiệp, Tỉnh Đồng Nai</p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <span class="material-symbols-outlined">phone</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-white mb-1"><?php _e('contact_page.phone'); ?></h3>
                                        <p class="text-white/70 text-sm"><a href="tel:+842513918888" class="hover:text-accent transition-colors">(+84-251) 391.8888</a></p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <span class="material-symbols-outlined">email</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-white mb-1"><?php _e('contact_page.email'); ?></h3>
                                        <p class="text-white/70 text-sm">
                                            <a href="mailto:info@aurorahotelplaza.com" class="hover:text-accent transition-colors block">info@aurorahotelplaza.com</a>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <span class="material-symbols-outlined">schedule</span>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-white mb-1"><?php _e('contact_page.working_hours'); ?></h3>
                                        <p class="text-white/70 text-sm"><?php _e('contact_page.reception'); ?>: 24/7<br><?php _e('contact_page.restaurant'); ?>: 6:00 - 22:00</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Social Links -->
                            <div class="flex gap-4 mt-8 pt-6 border-t border-white/10">
                                <a href="#" class="w-10 h-10 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-white hover:bg-accent hover:border-accent transition-all">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                                <a href="#" class="w-10 h-10 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-white hover:bg-accent hover:border-accent transition-all">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                </a>
                            </div>
                        </div>

                        <!-- Form Card (Right) -->
                        <div class="form-glass-card">
                            <h2 class="font-display text-2xl font-bold mb-6 text-white"><?php _e('contact_page.send_us_message'); ?></h2>
                            
                            <?php if ($is_logged_in): ?>
                                <div class="flex items-center gap-2 p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm mb-6">
                                    <span class="material-symbols-outlined text-lg">verified_user</span>
                                    <span><?php _e('contact_page.logged_in_as'); ?> <strong><?php echo htmlspecialchars($user_email); ?></strong></span>
                                </div>
                            <?php endif; ?>
                            
                            <form id="contactForm">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div class="form-group-glass">
                                        <label><?php _e('contact_page.full_name'); ?> <span class="text-red-500">*</span></label>
                                        <input type="text" name="name" class="form-input-glass <?php echo $is_logged_in && $user_name ? 'opacity-70 cursor-not-allowed' : ''; ?>" 
                                               placeholder="<?php _e('contact_page.enter_name'); ?>" 
                                               value="<?php echo htmlspecialchars($user_name); ?>"
                                               <?php echo $is_logged_in && $user_name ? 'readonly' : ''; ?> required>
                                    </div>
                                    <div class="form-group-glass">
                                        <label><?php _e('contact_page.email'); ?> <span class="text-red-500">*</span></label>
                                        <input type="email" name="email" class="form-input-glass <?php echo $is_logged_in && $user_email ? 'opacity-70 cursor-not-allowed' : ''; ?>" 
                                               placeholder="<?php _e('contact_page.enter_email'); ?>" 
                                               value="<?php echo htmlspecialchars($user_email); ?>"
                                               <?php echo $is_logged_in && $user_email ? 'readonly' : ''; ?> required>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div class="form-group-glass">
                                        <label><?php _e('contact_page.phone_number'); ?> <span class="text-red-500">*</span></label>
                                        <input type="tel" name="phone" class="form-input-glass <?php echo $is_logged_in && $user_phone ? 'opacity-70 cursor-not-allowed' : ''; ?>" 
                                               placeholder="<?php _e('contact_page.enter_phone'); ?>" 
                                               value="<?php echo htmlspecialchars($user_phone); ?>"
                                               <?php echo $is_logged_in && $user_phone ? 'readonly' : ''; ?> required>
                                    </div>
                                    <div class="form-group-glass">
                                        <label><?php _e('contact_page.subject'); ?></label>
                                        <div class="relative">
                                            <select name="subject" class="form-input-glass appearance-none">
                                                <option value="<?php _e('contact_page.subject_booking'); ?>" class="bg-slate-800"><?php _e('contact_page.subject_booking'); ?></option>
                                                <option value="<?php _e('contact_page.subject_event'); ?>" class="bg-slate-800"><?php _e('contact_page.subject_event'); ?></option>
                                                <option value="<?php _e('contact_page.subject_other'); ?>" class="bg-slate-800"><?php _e('contact_page.subject_other'); ?></option>
                                                <option value="<?php _e('contact_page.subject_feedback'); ?>" class="bg-slate-800"><?php _e('contact_page.subject_feedback'); ?></option>
                                            </select>
                                            <span class="material-symbols-outlined absolute right-3 top-3 text-white/50 pointer-events-none">expand_more</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group-glass mb-6">
                                    <label><?php _e('contact_page.message'); ?> <span class="text-red-500">*</span></label>
                                    <textarea name="message" class="form-input-glass h-32 resize-y" rows="5" 
                                              placeholder="<?php _e('contact_page.enter_message'); ?>" required minlength="10"></textarea>
                                </div>
                                
                                <button type="submit" class="btn-glass-gold w-full text-center justify-center text-lg py-3" id="submitBtn">
                                    <span class="material-symbols-outlined">send</span>
                                    <?php _e('contact_page.send_message'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Map Section - Lazy loaded -->
            <section class="py-20 relative z-10" id="map-section">
                <div class="max-w-7xl mx-auto px-4">
                    <div class="text-center mb-12">
                        <span class="text-accent font-semibold text-sm uppercase tracking-wider"><?php _e('contact_page.location'); ?></span>
                        <h2 class="font-display text-3xl md:text-4xl font-bold mt-2 mb-4 text-white"><?php _e('contact_page.find_us'); ?></h2>
                    </div>
                    <div class="map-glass-wrapper">
                        <!-- Lazy load map when visible -->
                        <div id="map-placeholder" class="w-full h-[450px] bg-slate-800/50 rounded-xl flex items-center justify-center cursor-pointer hover:bg-slate-800/70 transition-colors" onclick="loadMap()">
                            <div class="text-center">
                                <span class="material-symbols-outlined text-5xl text-accent mb-3 block">map</span>
                                <p class="text-white/70"><?php _e('contact_page.click_to_load_map'); ?></p>
                            </div>
                        </div>
                        <iframe 
                            id="google-map"
                            data-src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3917.0824374942376!2d106.84213347514152!3d10.957145355834111!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3174dc27705d362d%3A0xc1fb19ec2c2b1806!2zS2jDoWNoIHPhuqFuIEF1cm9yYQ!5e0!3m2!1svi!2s!4v1765630076897!5m2!1svi!2s"
                            allowfullscreen="" 
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            style="display:none;">
                        </iframe>
                    </div>
                </div>
            </section>
    </main>

    <?php include 'includes/footer.php'; ?>
</div>

<div id="toast-container" class="fixed top-24 right-4 z-50 flex flex-col gap-2"></div>

<!-- Scripts with defer for non-blocking load -->
<?php $js_version = '1.0.7'; ?>
<script src="assets/js/main.js?v=<?php echo $js_version; ?>" defer></script>
<script src="assets/js/contact.js?v=<?php echo $js_version; ?>" defer></script>
<script src="assets/js/lazy-map.js?v=<?php echo $js_version; ?>" defer></script>
</body>
</html>