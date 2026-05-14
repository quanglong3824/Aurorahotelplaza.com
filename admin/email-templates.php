<?php
$page_title = 'Template Email';
$page_subtitle = 'Xem trước các mẫu email hệ thống';

require_once __DIR__ . '/includes/admin-header.php';

$templates = [
    [
        'id' => 'booking-customer',
        'name' => 'Xác nhận đặt phòng (Gửi khách)',
        'desc' => 'Email gửi cho khách hàng sau khi đặt phòng thành công',
        'file' => 'booking-confirmation.php',
        'type' => 'booking'
    ],
    [
        'id' => 'booking-customer-noprice',
        'name' => 'Xác nhận đặt phòng - Ẩn giá (Gửi khách)',
        'desc' => 'Email gửi khách - không hiển thị giá, chỉ xác nhận đã gửi yêu cầu',
        'file' => 'booking-confirmation-noprice.php',
        'type' => 'booking'
    ],
    [
        'id' => 'contact-customer',
        'name' => 'Xác nhận liên hệ (Gửi khách)',
        'desc' => 'Email xác nhận gửi cho khách hàng khi họ gửi form liên hệ',
        'file' => 'contact-templates.php',
        'type' => 'contact'
    ],
    [
        'id' => 'contact-hotel',
        'name' => 'Thông báo liên hệ (Gửi khách sạn)',
        'desc' => 'Email thông báo gửi cho nhân viên khi có liên hệ mới',
        'file' => 'contact-templates.php',
        'type' => 'contact'
    ],
    [
        'id' => 'welcome',
        'name' => 'Chào mừng thành viên',
        'desc' => 'Email gửi cho khách hàng khi đăng ký tài khoản mới',
        'file' => 'email-templates.php',
        'type' => 'auth'
    ],
    [
        'id' => 'password-reset',
        'name' => 'Đặt lại mật khẩu',
        'desc' => 'Email gửi link đặt lại mật khẩu',
        'file' => 'email-templates.php',
        'type' => 'auth'
    ],
    [
        'id' => 'temp-password',
        'name' => 'Mật khẩu tạm thời',
        'desc' => 'Email gửi mật khẩu tạm thời',
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
                        Danh sách template
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">Click để xem trước</p>
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
                            Xem trước email
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
                        'guest_name' => 'Nguyễn Văn A',
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
                        'special_requests' => 'Phòng tầng cao, view thành phố',
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $hotel_info = [
                        'name' => 'Aurora Hotel Plaza',
                        'address' => 'KP2, Phường Tân Hiệp, Thủ Đông Nai',
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
                                    'name' => 'Nguyễn Văn A',
                                    'email' => 'nguyenvana@email.com',
                                    'phone' => '0901234567',
                                    'subject' => 'Đặt phòng',
                                    'message' => 'Tôi muốn đặt phòng Deluxe cho 2 người vào cuối tuần này.',
                                    'submission_id' => 'CT-2025-001',
                                    'created_at' => date('d/m/Y H:i'),
                                    'user_id' => 1
                                ]);
                                break;

                            case 'contact-hotel':
                                require_once __DIR__ . '/../includes/email-templates/contact-templates.php';
                                $preview_html = ContactEmailTemplates::getHotelNotificationTemplate([
                                    'name' => 'Nguyễn Văn A',
                                    'email' => 'nguyenvana@email.com',
                                    'phone' => '0901234567',
                                    'subject' => 'Đặt phòng',
                                    'message' => 'Tôi muốn đặt phòng Deluxe cho 2 người vào cuối tuần này.',
                                    'submission_id' => 'CT-2025-001',
                                    'created_at' => date('d/m/Y H:i'),
                                    'user_id' => 1
                                ]);
                                break;

                            case 'welcome':
                                require_once __DIR__ . '/../helpers/email-templates.php';
                                $preview_html = EmailTemplates::getWelcomeTemplate('Nguyễn Văn A', 'nguyenvana@email.com', 1);
                                break;

                            case 'password-reset':
                                require_once __DIR__ . '/../helpers/email-templates.php';
                                $preview_html = EmailTemplates::getPasswordResetTemplate('Nguyễn Văn A', 'https://aurorahotelplaza.com/auth/reset-password.php?token=sample_token_123');
                                break;

                            case 'temp-password':
                                require_once __DIR__ . '/../helpers/email-templates.php';
                                $preview_html = EmailTemplates::getTemporaryPasswordTemplate('Nguyễn Văn A', 'TEMP-ABC123');
                                break;

                            default:
                                $preview_html = '<div class="text-center p-8 text-gray-500">Chọn template để xem trước</div>';
                        }
                    } catch (Exception $e) {
                        $preview_html = '<div class="text-center p-8 text-red-500">Lỗi render template: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                    ?>
                    <div class="bg-gray-100 dark:bg-slate-800 rounded-xl overflow-hidden">
                        <div class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-slate-700">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400"></div>
                            <span class="ml-3 text-xs text-gray-500 dark:text-gray-400">Email Preview</span>
                        </div>
                        <div style="max-height: 700px; overflow-y: auto;">
                            <?php echo $preview_html; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-6 p-6">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-amber-500 text-2xl">info</span>
                    <div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-2">Lưu ý về Template Email</h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1.5">
                            <li>Template <strong>"Ẩn giá"</strong> được gửi cho khách hàng — không tiết lộ bất kỳ thông tin giá cả nào.</li>
                            <li>Template đầy đủ (có giá) chỉ được gửi cho nội bộ khách sạn để xử lý booking.</li>
                            <li>Để chỉnh sửa template, vui lòng liên hệ bộ phận IT hoặc chỉnh trực tiếp file PHP.</li>
                            <li>Thư mục template: <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-slate-700 rounded text-xs">includes/email-templates/</code> và <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-slate-700 rounded text-xs">helpers/email-templates.php</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
