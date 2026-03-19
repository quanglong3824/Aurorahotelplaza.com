<?php
/**
 * Email Configuration for Aurora Hotel
 */

// Email debug mode - set to true during development
define('EMAIL_DEBUG', true);
define('MAIL_DEBUG', 0); // 0 = off, 1 = client, 2 = client and server

// SMTP Configuration (Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'long.lequang308@gmail.com');
define('SMTP_PASSWORD', 'zbzp bize hoca zjqv'); // Gmail App Password
define('SMTP_ENCRYPTION', 'tls'); // tls or ssl
define('SMTP_SECURE', 'tls'); // Alias for SMTP_ENCRYPTION
define('SMTP_AUTH', true); // Set to true if using authentication

// Email settings
define('FROM_EMAIL', 'long.lequang308@gmail.com'); // Must match SMTP_USERNAME for Gmail
define('FROM_NAME', 'Aurora Hotel Plaza');
define('REPLY_TO_EMAIL', 'long.lequang308@gmail.com');

// Mailer class constants
define('MAIL_FROM_EMAIL', FROM_EMAIL);
define('MAIL_FROM_NAME', FROM_NAME);
define('MAIL_REPLY_TO', REPLY_TO_EMAIL);
define('MAIL_REPLY_NAME', FROM_NAME);
define('MAIL_CHARSET', 'UTF-8');

// Email templates directory
define('EMAIL_TEMPLATES_DIR', __DIR__ . '/../templates/email/');

// Enable/disable email sending
define('EMAIL_ENABLED', true);
?>