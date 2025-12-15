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
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php _e('contact_page.title'); ?></title>
<script src="assets/js/tailwindcss-cdn.js"></script>
<link href="assets/css/fonts.css" rel="stylesheet"/>
<script src="assets/js/tailwind-config.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/liquid-glass.css">
<style>
/* ========== FULL PAGE BACKGROUND WITH GLASS BLOCKS ========== */
.page-wrapper { position: relative; min-height: 100vh; }
.page-bg {
    position: fixed; inset: 0; z-index: -2;
    background: url('assets/img/hero banner/AURORA-HOTEL-BIEN-HOA-1.jpg');
    background-size: cover; background-position: center; background-attachment: fixed;
}
.page-overlay { position: fixed; inset: 0; z-index: -1; background: rgba(0, 0, 0, 0.55); }

/* Hero Section */
.hero-section {
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 120px 1rem 60px;
}

.hero-glass-card {
    max-width: 700px;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 2rem;
    padding: 3rem;
    text-align: center;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: rgba(212, 175, 55, 0.2);
    border: 1px solid rgba(212, 175, 55, 0.4);
    border-radius: 3rem;
    color: #d4af37;
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
}

.hero-title {
    font-family: 'Playfair Display', serif;
    font-size: 3rem;
    font-weight: 700;
    color: white;
    margin-bottom: 1rem;
}

.hero-subtitle {
    font-size: 1.125rem;
    color: rgba(255, 255, 255, 0.85);
    margin-bottom: 2rem;
    line-height: 1.7;
}

/* Content Section */
.content-section { padding: 3rem 1rem 5rem; }
.section-container { max-width: 1200px; margin: 0 auto; }

/* Glass Block */
.glass-block {
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1.5rem;
    padding: 2.5rem;
}

/* Contact Grid */
.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 2rem;
}

/* Info Section */
.info-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.5rem;
}

.info-desc {
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 2rem;
    font-size: 0.9375rem;
}

.info-item {
    display: flex;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.info-item:last-of-type { border-bottom: none; }

.info-icon {
    width: 3rem;
    height: 3rem;
    background: rgba(212, 175, 55, 0.15);
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.info-icon .material-symbols-outlined {
    font-size: 1.25rem;
    color: #d4af37;
}

.info-label {
    font-weight: 700;
    color: white;
    font-size: 0.9375rem;
    margin-bottom: 0.25rem;
}

.info-text {
    color: rgba(255, 255, 255, 0.75);
    font-size: 0.875rem;
    line-height: 1.5;
}

.info-text a {
    color: rgba(255, 255, 255, 0.85);
    transition: color 0.3s;
}

.info-text a:hover { color: #d4af37; }

/* Social Links */
.social-links {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.social-btn {
    width: 2.75rem;
    height: 2.75rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    transition: all 0.3s;
}

.social-btn:hover {
    background: #d4af37;
    border-color: #d4af37;
    transform: translateY(-3px);
}

.social-btn svg { width: 1.125rem; height: 1.125rem; }

/* Form Section */
.form-section {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1.25rem;
    padding: 2rem;
}

.form-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    margin-bottom: 1.