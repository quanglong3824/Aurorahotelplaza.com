const fs = require('fs');
const path = require('path');

const files = [
    'about.php', 'apartments.php', 'blog-detail.php', 'blog.php', 'cancellation-policy.php',
    'contact.php', 'explore.php', 'gallery.php', 'privacy.php', 'rooms.php',
    'service-detail.php', 'services.php', 'terms.php', 'index.php', 'profile.php',
    'room-map-user.php',
    'admin/includes/admin-header.php',
    'apartment-details/classical-family.php', 'apartment-details/classical-premium.php',
    'apartment-details/family-apartment.php', 'apartment-details/indochine-family.php',
    'apartment-details/indochine-studio.php', 'apartment-details/modern-premium.php',
    'apartment-details/modern-studio.php', 'apartment-details/premium-apartment.php',
    'apartment-details/studio-apartment.php',
    'auth/change-password.php', 'auth/forgot-password.php', 'auth/login.php',
    'auth/logout-confirm.php', 'auth/register.php', 'auth/reset-password.php',
    'booking/confirmation.php', 'booking/index.php', 'booking/vnpay_return.php',
    'payment/index.php', 'payment/vnpay_pay.php', 'payment/vnpay_querydr.php',
    'payment/vnpay_refund.php', 'payment/vnpay_return.php',
    'profile/booking-detail.php', 'profile/bookings.php', 'profile/edit.php',
    'profile/index.php', 'profile/loyalty.php', 'profile/view-qrcode.php',
    'room-details/deluxe.php', 'room-details/premium-deluxe.php',
    'room-details/premium-twin.php', 'room-details/vip-suite.php'
];

files.forEach(file => {
    const filePath = path.join(__dirname, file);
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        let modified = false;

        // 1. Add translate="no" to html tag
        if (content.includes('<html') && !content.includes('translate="no"')) {
            content = content.replace('<html', '<html translate="no"');
            modified = true;
        }

        // 2. Add meta tag to head
        if (content.includes('<head>') && !content.includes('name="google" content="notranslate"')) {
            const metaTag = '\n    <meta name="google" content="notranslate" />';
            content = content.replace('<head>', '<head>' + metaTag);
            modified = true;
        }

        if (modified) {
            fs.writeFileSync(filePath, content, 'utf8');
            console.log(`Applied to: ${file}`);
        }
    }
});
