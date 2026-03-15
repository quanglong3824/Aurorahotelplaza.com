<?php
/**
 * Email Configuration for Aurora Hotel
 * Strictly loads from environment variables
 */

require_once __DIR__ . '/load_env.php';

// Email debug mode
define('EMAIL_DEBUG', env('EMAIL_DEBUG', false));
define('MAIL_DEBUG', env('MAIL_DEBUG', 0)); 

// SMTP Configuration
define('SMTP_HOST', env('SMTP_HOST', ''));
define('SMTP_PORT', env('SMTP_PORT', 587));
define('SMTP_USERNAME', env('SMTP_USERNAME', ''));
define('SMTP_PASSWORD', env('SMTP_PASSWORD', '')); 
define('SMTP_ENCRYPTION', env('SMTP_ENCRYPTION', 'tls')); 
define('SMTP_SECURE', env('SMTP_SECURE', 'tls')); 
define('SMTP_AUTH', env('SMTP_AUTH', true)); 

// Email settings
define('FROM_EMAIL', env('FROM_EMAIL', '')); 
define('FROM_NAME', env('FROM_NAME', 'Aurora Hotel Plaza'));
define('REPLY_TO_EMAIL', env('REPLY_TO_EMAIL', ''));

// Mailer class constants
define('MAIL_FROM_EMAIL', FROM_EMAIL);
define('MAIL_FROM_NAME', FROM_NAME);
define('MAIL_REPLY_TO', REPLY_TO_EMAIL);
define('MAIL_REPLY_NAME', FROM_NAME);
define('MAIL_CHARSET', 'UTF-8');

// Email templates directory
define('EMAIL_TEMPLATES_DIR', __DIR__ . '/../templates/email/');

// Enable/disable email sending
define('EMAIL_ENABLED', env('EMAIL_ENABLED', true));
?>
