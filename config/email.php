<?php
/**
 * Email Configuration for Aurora Hotel
 */

// Email debug mode - set to true during development
define('EMAIL_DEBUG', true);

// SMTP Configuration (for production)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls'); // tls or ssl

// Email settings
define('FROM_EMAIL', 'noreply@aurorahotel.com');
define('FROM_NAME', 'Aurora Hotel Plaza');
define('REPLY_TO_EMAIL', 'info@aurorahotel.com');

// Email templates directory
define('EMAIL_TEMPLATES_DIR', __DIR__ . '/../templates/email/');

// Enable/disable email sending
define('EMAIL_ENABLED', true);
?>