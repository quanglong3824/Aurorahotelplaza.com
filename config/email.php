<?php
/**
 * Email Configuration
 * PHPMailer settings for Aurora Hotel Plaza
 */

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // tls or ssl
define('SMTP_AUTH', true);

// Email credentials
define('SMTP_USERNAME', 'long.lequang308@gmail.com');
define('SMTP_PASSWORD', 'gcnz vybl yfac xruk');

// Sender information
define('MAIL_FROM_EMAIL', 'long.lequang308@gmail.com');
define('MAIL_FROM_NAME', 'Aurora Hotel Plaza');

// Reply-to
define('MAIL_REPLY_TO', 'long.lequang308@gmail.com');
define('MAIL_REPLY_NAME', 'Aurora Hotel Plaza Support');

// Email settings
define('MAIL_CHARSET', 'UTF-8');
define('MAIL_DEBUG', 0); // 0 = off, 1 = client, 2 = server, 3 = connection, 4 = low-level
