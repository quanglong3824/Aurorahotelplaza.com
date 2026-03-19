<?php
/**
 * Aurora Hotel Plaza - Booking Page
 * Clean Entry Point (Refactored)
 */

session_start();
require_once '../config/database.php';
require_once '../helpers/language.php';
require_once '../helpers/booking-validator.php';
require_once '../controllers/FrontBookingController.php';

initLanguage();

// Initialize Controller
$controller = new FrontBookingController();
$data = $controller->getData();

// Expose data to view
extract($data);
?>
<!DOCTYPE html>
<html translate="no" class="light" lang="<?php echo getLang(); ?>">

<head>
    <meta name="google" content="notranslate" />
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <title><?php _e('booking_page.title'); ?></title>

    <!-- Core CSS -->
    <link href="../assets/css/tailwind-output.css" rel="stylesheet" />
    <link href="../assets/css/fonts.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/liquid-glass.css">
    
    <!-- Page Specific CSS -->
    <link rel="stylesheet" href="../assets/css/pages/booking-page.css">
</head>

<body class="booking-page">
    
    <!-- Main Content -->
    <?php include '../views/front-booking.view.php'; ?>

    <!-- JavaScript Infrastructure -->
    <script>
        // Expose translations to JS
        const translations = {
            booking_form: {
                checkin_title_room: "<?php echo addslashes(__('booking_form.checkin_title_room')); ?>",
                checkin_title_apt: "<?php echo addslashes(__('booking_form.checkin_title_apt')); ?>",
                confirm_title_room: "<?php echo addslashes(__('booking_form.confirm_title_room')); ?>",
                confirm_title_apt: "<?php echo addslashes(__('booking_form.confirm_title_apt')); ?>",
                submit_btn_room: "<?php echo addslashes(__('booking_form.submit_btn_room')); ?>",
                submit_btn_apt: "<?php echo addslashes(__('booking_form.submit_btn_apt')); ?>",
                select_room_or_apt: "<?php echo addslashes(__('booking_form.select_room_or_apt')); ?>",
                select_checkin_date: "<?php echo addslashes(__('booking_form.select_checkin_date')); ?>",
                checkin_not_past: "<?php echo addslashes(__('booking_form.checkin_not_past')); ?>",
                select_checkout_date: "<?php echo addslashes(__('booking_form.select_checkout_date')); ?>",
                checkout_after_checkin: "<?php echo addslashes(__('booking_form.checkout_after_checkin')); ?>",
                checkout_future: "<?php echo addslashes(__('booking_form.checkout_future')); ?>",
                invalid_guests: "<?php echo addslashes(__('booking_form.invalid_guests')); ?>",
                fill_required: "<?php echo addslashes(__('booking_form.fill_required')); ?>",
                select_est_checkin: "<?php echo addslashes(__('booking_form.select_est_checkin')); ?>",
                min_adults: "<?php echo addslashes(__('booking_form.min_adults')); ?>",
                price_for_2: "<?php echo addslashes(__('booking_form.price_for_2')); ?>",
                price_short_stay: "<?php echo addslashes(__('booking_form.price_short_stay')); ?>",
                price_single: "<?php echo addslashes(__('booking_form.price_single')); ?>",
                price_weekly_1: "<?php echo addslashes(__('booking_form.price_weekly_1')); ?>",
                price_weekly_2: "<?php echo addslashes(__('booking_form.price_weekly_2')); ?>",
                price_daily: "<?php echo addslashes(__('booking_form.price_daily')); ?>",
                price_daily_1: "<?php echo addslashes(__('booking_form.price_daily_1')); ?>",
                price_daily_2: "<?php echo addslashes(__('booking_form.price_daily_2')); ?>",
                short_stay_label: "<?php echo addslashes(__('booking_form.short_stay_label')); ?>",
                agree_terms_alert: "<?php echo addslashes(__('booking_form.agree_terms_alert')); ?>",
                guest_promo_lock: "<?php echo addslashes(__('booking_form.guest_promo_lock')); ?>",
                guest_promo_lock_end: "<?php echo addslashes(__('booking_form.guest_promo_lock_end')); ?>",
                long_stay_title: "<?php echo addslashes(__('booking_form.long_stay_title')); ?>",
                long_stay_msg: "<?php echo addslashes(__('booking_form.long_stay_msg')); ?>",
                switch_to_inquiry: "<?php echo addslashes(__('booking_form.switch_to_inquiry')); ?>",
                pay_at_hotel_desc: "<?php echo addslashes(__('booking_form.pay_at_hotel_desc')); ?>"
            },
            booking_page: {
                pay_at_hotel_desc: "<?php echo addslashes(__('booking_form.pay_at_hotel_desc')); ?>"
            },
            common: {
                night: "<?php echo addslashes(__('common.night')); ?>",
                nights: "<?php echo addslashes(__('common.nights')); ?>",
                adult: "<?php echo addslashes(__('common.adult')); ?>",
                adults: "<?php echo addslashes(__('common.adults')); ?>",
                child: "<?php echo addslashes(__('common.child')); ?>",
                children: "<?php echo addslashes(__('common.children')); ?>",
                month: "<?php echo addslashes(__('booking_form.month')); ?>",
                day: "<?php echo addslashes(__('common.day')); ?>",
                currency: "<?php echo addslashes(__('common.currency')); ?>",
                processing: "<?php echo addslashes(__('common.processing')); ?>",
                guest: "<?php echo addslashes(__('common.guest')); ?>",
                guests: "<?php echo addslashes(__('common.guests')); ?>",
                guest_add: "<?php echo addslashes(__('common.guest_add')); ?>"
            },
            auth: {
                login: "<?php echo addslashes(__('auth.login')); ?>"
            }
        };
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/pages/booking-page.js"></script>
    <script src="./assets/js/booking-diagnostic.js"></script>

</body>

</html>
