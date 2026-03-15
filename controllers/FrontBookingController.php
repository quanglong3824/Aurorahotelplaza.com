<?php

class FrontBookingController {
    public function getData() {
        try {
            $db = getDB();

            // Get user information if logged in
            $user_info = null;
            if (isset($_SESSION['user_id'])) {
                try {
                    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user_info = $stmt->fetch();
                } catch (Exception $e) {
                    error_log("Error fetching user info: " . $e->getMessage());
                }
            }

            // ========== ANTI-SPAM: Server-side validation ==========
            $spam_check_passed = true;
            $booking_block_message = '';
            $booking_block_bookings = [];

            if (isset($_SESSION['user_id'])) {
                // User đã đăng ký: check theo user_id
                $booking_spam_check = checkBookingSpam($_SESSION['user_id'], null, null);

                if (!$booking_spam_check['allowed']) {
                    $spam_check_passed = false;
                    $booking_block_message = $booking_spam_check['message'];
                    $booking_block_bookings = $booking_spam_check['pending_bookings'];
                    
                    // Set session to show modal
                    $_SESSION['booking_block_message'] = $booking_block_message;
                    $_SESSION['booking_block_bookings'] = $booking_block_bookings;
                }
            }
            // ========== END ANTI-SPAM ==========

            // Get room types for selection
            $stmt = $db->prepare("
                SELECT 
                    rt.*,
                    COALESCE(total.total_rooms, 0) as total_rooms,
                    COALESCE(available.available_rooms, 0) as available_rooms
                FROM room_types rt
                LEFT JOIN (
                    SELECT room_type_id, COUNT(*) as total_rooms 
                    FROM rooms 
                    GROUP BY room_type_id
                ) total ON rt.room_type_id = total.room_type_id
                LEFT JOIN (
                    SELECT room_type_id, COUNT(*) as available_rooms 
                    FROM rooms 
                    WHERE status = 'available' 
                    GROUP BY room_type_id
                ) available ON rt.room_type_id = available.room_type_id
                WHERE rt.status = 'active' 
                ORDER BY rt.sort_order, rt.base_price
            ");
            $stmt->execute();
            $room_types = $stmt->fetchAll();

            // Get pre-selected room type from URL
            $selected_room_type_id = null;
            $selected_room_slug = null;
            if (isset($_GET['room_type'])) {
                $room_type_param = trim($_GET['room_type']);
                if (is_numeric($room_type_param)) {
                    $selected_room_type_id = (int) $room_type_param;
                } else {
                    $selected_room_slug = $room_type_param;
                    foreach ($room_types as $room) {
                        if ($room['slug'] === $room_type_param) {
                            $selected_room_type_id = $room['room_type_id'];
                            break;
                        }
                    }
                }
            }

            // Other pre-filled data
            $selected_room_id = isset($_GET['selected_room_id']) ? (int) $_GET['selected_room_id'] : null;
            $selected_room_number = isset($_GET['selected_room_number']) ? htmlspecialchars($_GET['selected_room_number']) : null;
            $prefilled_check_in = isset($_GET['check_in']) ? htmlspecialchars($_GET['check_in']) : '';
            $prefilled_check_out = isset($_GET['check_out']) ? htmlspecialchars($_GET['check_out']) : '';
            $prefilled_guests = isset($_GET['guests']) ? (int) $_GET['guests'] : 2;

            return [
                'user_info' => $user_info,
                'spam_check_passed' => $spam_check_passed,
                'booking_block_message' => $booking_block_message,
                'booking_block_bookings' => $booking_block_bookings,
                'room_types' => $room_types,
                'selected_room_type_id' => $selected_room_type_id,
                'selected_room_slug' => $selected_room_slug,
                'selected_room_id' => $selected_room_id,
                'selected_room_number' => $selected_room_number,
                'prefilled_check_in' => $prefilled_check_in,
                'prefilled_check_out' => $prefilled_check_out,
                'prefilled_guests' => $prefilled_guests
            ];
        } catch (Exception $e) {
            error_log("Booking page controller error: " . $e->getMessage());
            return [
                'room_types' => []
            ];
        }
    }
}
