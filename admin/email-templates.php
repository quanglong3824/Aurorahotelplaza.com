<?php
$page_title = 'Email Templates';
$page_subtitle = 'Preview system email templates';

require_once __DIR__ . '/includes/admin-header.php';

$templates = [
    [
        'id' => 'booking-customer',
        'name' => 'Booking Confirmation (To Customer)',
        'desc' => 'Email sent to customer after successful booking',
        'file' => 'booking-confirmation.php',
        'type' => 'booking'
    ],
    [
        'id' => 'booking-customer-noprice',
        'name' => 'Booking Confirmation - No Price (To Customer)',
        'desc' => 'Email to customer - no pricing shown, only confirms request submitted',
        'file' => 'booking-confirmation-noprice.php',
        'type' => 'booking'
    ],
    [
        'id' => 'contact-customer',
        'name' => 'Contact Confirmation (To Customer)',
        'desc' => 'Confirmation email sent to customer when they submit contact form',
        'file' => 'contact-templates.php',
        'type' => 'contact'
    ],
    [
        'id' => 'contact-hotel',
        'name' => 'Contact Notification (To Hotel)',
        'desc' => 'Notification email sent to staff when there is a new contact',
        'file' => 'contact-templates.php',
        'type' => 'contact'
    ],
    [
        'id' => 'welcome',
        'name' => 'Welcome Member',
        'desc' => 'Email sent to customer when they register a new account',
        'file' => 'email-templates.php',
        'type' => 'auth'
    ],
    [
        'id' => 'password-reset',
        'name' => 'Reset Password',
        'desc' => 'Email with password reset link',
        'file' => 'email-templates.php',
        'type' => 'auth'
    ],
    [
        'id' => 'temp-password',
        'name' => 'Temporary Password',
        'desc' => 'Email with temporary password',
        'file' => 'email-templates.php',
        'type' => 'auth'
    ],
];

$selected = $_GET['template'] ?? 'booking-customer-noprice';
?>

<div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <div class="lg:col-span-1">
            <div class="card p-0">
                <div class="p-4 border-b border-gray-200 dark:border-slate-700">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#d4af37]">mail</span>
                        Template List
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">Click to preview</p>
                </div>
                <div class="p-2">
                    <?php foreach ($templates as $tpl): ?>
                        <a href="?p=<?php echo Security::hashAdminPage('email-templates'); ?>&template=<?php echo $tpl['id']; ?>"
                           class="flex items-start gap-3 p-3 rounded-xl transition-all <?php echo $selected === $tpl['id'] ? 'bg-[#d4af37]/10 border border-[#d4af37]/30' : 'hover:bg-gray-50 dark:hover:bg-slate-800'; ?>">
                            <span class="material-symbols-outlined text-lg mt-0.5 <?php echo $selected === $tpl['id'] ? 'text-[#d4af37]' : 'text-gray-400'; ?>">
                                <?php
                                $icons = [
                                    'booking' => 'book_online',
                                    'contact' => 'contact_mail',
                                    'auth' => 'lock'
                                ];
                                echo $icons[$tpl['type']] ?? 'mail';
                                ?>
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold <?php echo $selected === $tpl['id'] ? 'text-[#b8941f]' : 'text-gray-900 dark:text-white'; ?>">
                                    <?php echo $tpl['name']; ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2"><?php echo $tpl['desc']; ?></p>
                            </div>
                            <?php if ($selected === $tpl['id']): ?>
                                <span class="material-symbols-outlined text-[#d4af37] text-sm">check_circle</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="lg:col-span-3">
            <div class="card p-0">
                <div class="p-4 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="material-symbols-outlined text-[#d4af37]">preview</span>
                            Email Preview
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            <?php foreach ($templates as $tpl): ?>
                                <?php if ($tpl['id'] === $selected): ?>
                                    <?php echo $tpl['name']; ?> — <?php echo $tpl['desc']; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                        Readonly Preview
                    </span>
                </div>
                <div class="p-4">
                    <?php
                    $preview_html = '';
                    $sample_booking = [
                        'booking_code' => 'AUR-2025-ABC123',
                        'guest_name' => 'Nguyen Van A',
                        'guest_email' => 'nguyenvana@email.com',
                        'guest_phone' => '0901234567',
                        'type_name' => 'Deluxe Double Room',
                        'room_type_name' => 'Deluxe Double Room',
                        'check_in_date' => date('Y-m-d', strtotime('+3 days')),
                        'check_out_date' => date('Y-m-d', strtotime('+5 days')),
                        'total_nights' => 2,
                        'num_nights' => 2,
                        'num_adults' => 2,
                        'total_amount' => 3500000,
                        'total_amount_formatted' => '3.500.000',
                        'special_requests' => 'High floor room, city view',
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $hotel_info = [
                        'name' => 'Aurora Hotel Plaza',
                        'address' => '253 Phạm Văn Thuận, KP2, Tam Hiệp, TP. Đồng Nai',
                        'phone' => '(+84-251) 391 8888',
                        'email' => 'info@aurorahotelplaza.com',
                        'website' => 'https://aurorahotelplaza.com'
                    ];

                    try {
                        switch ($selected) {
                            case 'booking-customer':
                                require_once __DIR__ . '/../includes/email-templates/booking-confirmation.php';
                                $preview_html = getBookingConfirmationEmailHTML($sample_booking, $hotel_info);
                                break;

                            case 'booking-customer-noprice':
                                require_once __DIR__ . '/../includes/email-templates/booking-confirmation-noprice.php';
                                $preview_html = getBookingConfirmationNoPriceEmailHTML($sample_booking, $hotel_info);
                                break;

                            case 'contact-customer':
                                require_once __DIR__ . '/../includes/email-templates/contact-templates.php';
                                $preview_html = ContactEmailTemplates::getCustomerConfirmationTemplate([
                                    'name' => 'Nguyen Van A',
                                    'email' => 'nguyenvana@email.com',
                                    'phone' => '0901234567',
                                    'subject' => 'Booking',
                                    'message' => 'I would like to book a Deluxe room for 2 people this weekend.',
                                    'submission_id' => 'CT-2025-001',
                                    'created_at' => date('d/m/Y H:i'),
                                    'user_id' => 1
                                ]);
                                break;

                            case 'contact-hotel':
                                require_once __DIR__ . '/../includes/email-templates/contact-templates.php';
                                $preview_html = ContactEmailTemplates::getHotelNotificationTemplate([
                                    'name' => 'Nguyen Van A',
                                    'email' => 'nguyenvana@email.com',
                                    'phone' => '0901234567',
                                    'subject' => 'Booking',
                                    'message' => 'I would like to book a Deluxe room for 2 people this weekend.',
                                    'submission_id' => 'CT-2025-001',
                                    'created_at' => date('d/m/Y H:i'),
                                    'user_id' => 1
                                ]);
                                break;

                            case 'welcome':
                                require_once __DIR__ . '/../helpers/email-templates.php';
                                $preview_html = EmailTemplates::getWelcomeTemplate('Nguyen Van A', 'nguyenvana@email.com', 1);
                                break;

                            case 'password-reset':
                                require_once __DIR__ . '/../helpers/email-templates.php';
                                $preview_html = EmailTemplates::getPasswordResetTemplate('Nguyen Van A', 'https://aurorahotelplaza.com/auth/reset-password.php?token=sample_token_123');
                                break;

                            case 'temp-password':
                                require_once __DIR__ . '/../helpers/email-templates.php';
                                $preview_html = EmailTemplates::getTemporaryPasswordTemplate('Nguyen Van A', 'TEMP-ABC123');
                                break;

                            default:
                                $preview_html = '<div class="text-center p-8 text-gray-500">Select a template to preview</div>';
                        }
                    } catch (Exception $e) {
                        $preview_html = '<div class="text-center p-8 text-red-500">Template render error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                    ?>
                    <div class="bg-gray-100 dark:bg-slate-800 rounded-xl overflow-hidden">
                        <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-200 dark:bg-slate-700 border-b border-gray-300 dark:border-slate-600">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400"></div>
                            <span class="ml-3 text-xs text-gray-500 dark:text-gray-400 font-medium">Email Preview</span>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-slate-900">
                            <iframe
                                id="email-preview-frame"
                                srcdoc="<?php echo htmlspecialchars($preview_html, ENT_QUOTES, 'UTF-8'); ?>"
                                style="width: 100%; min-height: 900px; border: none; display: block; background: #fff; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);"
                                sandbox="allow-same-origin"
                                onload="resizePreviewIframe(this);"
                            ></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function resizePreviewIframe(iframe) {
    try {
        const height = iframe.contentWindow.document.body.scrollHeight;
        iframe.style.minHeight = Math.max(height + 60, 900) + 'px';
    } catch(e) {
        iframe.style.minHeight = '900px';
    }
}
</script>
                        <iframe
                            id="email-preview-frame"
                            srcdoc="<?php echo htmlspecialchars($preview_html, ENT_QUOTES, 'UTF-8'); ?>"
                            style="width: 100%; min-height: 700px; border: none; display: block; background: #fff;"
                            sandbox="allow-same-origin"
                            onload="this.style.minHeight = (this.contentWindow.document.body.scrollHeight + 40) + 'px';"
                        ></iframe>
                    </div>
                </div>
            </div>

            <div class="card mt-6 p-6">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-amber-500 text-2xl">info</span>
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-2">Email Template Notes</h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1.5">
                            <li>The <strong>"No Price"</strong> template is sent to customers — it does not reveal any pricing information.</li>
                            <li>The full template (with pricing) is only sent internally to the hotel for booking processing.</li>
                            <li>To edit templates, please contact the IT department or edit the PHP files directly.</li>
                            <li>Template directory: <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-slate-700 rounded text-xs">includes/email-templates/</code> and <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-slate-700 rounded text-xs">helpers/email-templates.php</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
