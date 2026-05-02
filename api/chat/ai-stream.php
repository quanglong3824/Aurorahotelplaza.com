<?php
/**
 * API: Stream AI Reply (SSE)
 * GET /api/chat/ai-stream.php?conversation_id=5&message=...
 */

session_start();
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

require_once '../../config/database.php';
require_once '../../helpers/ai-helper.php';

// Auth check
if (!isset($_SESSION['user_id']) && !isset($_SESSION['chat_guest_id'])) {
    echo "data: " . json_encode(["error" => "Unauthorized"]) . "\n\n";
    exit;
}

$conv_id = (int) ($_GET['conversation_id'] ?? 0);
$user_msg_id = (int) ($_GET['user_message_id'] ?? 0);

if (!$conv_id || !$user_msg_id) {
    echo "data: " . json_encode(["error" => "Missing parameters"]) . "\n\n";
    exit;
}

$db = getDB();

// Lấy nội dung tin nhắn của khách
$stmt = $db->prepare("SELECT message FROM chat_messages WHERE message_id = ? AND conversation_id = ?");
$stmt->execute([$user_msg_id, $conv_id]);
$message = $stmt->fetchColumn();

if (!$message) {
    echo "data: " . json_encode(["error" => "Message not found"]) . "\n\n";
    exit;
}

require_once '../../config/database.php';
require_once '../../helpers/ai-helper.php';
require_once '../../helpers/mailer.php';
require_once '../../helpers/pricing_calculator.php';
require_once '../../models/Booking.php';

// Auth check
...
// Gọi stream từ AI (Tự động định tuyến Gemini hoặc Opencode)
$full_reply = stream_ai_reply($message, $db, $conv_id);

// Sau khi stream xong, xử lý các tag đặc biệt và lưu vào DB
$new_msg_id = 0;
if (!empty($full_reply)) {
    try {
        // 1. Xử lý [SAVE_CONTACT: name=xxx, phone=xxx, msg=xxx]
        if (preg_match('/\[SAVE_CONTACT:\s*name=(.*?),?\s*phone=(.*?),?\s*msg=(.*?)\]/i', $full_reply, $matches)) {
            $name = trim($matches[1]);
            $phone = trim($matches[2]);
            $msg = trim($matches[3]);

            $stmtC = $db->prepare("
                INSERT INTO contact_submissions (name, email, phone, subject, message, status, created_at)
                VALUES (:name, :email, :phone, 'AI Lead/Support Request', :msg, 'new', NOW())
            ");
            $stmtC->execute([
                ':name' => $name,
                ':email' => 'ai_collected@aurorahotelplaza.com',
                ':phone' => $phone,
                ':msg' => $msg
            ]);
        }

        // 2. Xử lý [EXECUTE_BOOKING: {json}]
        if (preg_match('/\[EXECUTE_BOOKING:\s*(\{.*?\})\]/is', $full_reply, $matches)) {
            $jsonData = json_decode($matches[1], true);
            if ($jsonData) {
                $bookingModel = new Booking($db);
                $check_in = $jsonData['check_in'];
                $check_out = $jsonData['check_out'];
                $room_type_id = (int)$jsonData['room_type_id'];

                // 2.1 Kiểm tra phòng trống
                $room = $bookingModel->getAvailableRoom($room_type_id, $check_in, $check_out);
                if ($room) {
                    // 2.2 Tính toán tiền
                    $nights = Booking::calculateNights($check_in, $check_out);

                    $stmtRT = $db->prepare("SELECT * FROM room_types WHERE room_type_id = ?");
                    $stmtRT->execute([$room_type_id]);
                    $room_type = $stmtRT->fetch(PDO::FETCH_ASSOC);

                    $basePrice = (float)$room_type['base_price'];
                    $total_amount = $basePrice * $nights;

                    // 2.3 Tạo đơn đặt phòng
                    $bookingData = [
                        'booking_code' => Booking::generateBookingCode(),
                        'user_id' => $_SESSION['user_id'] ?? null,
                        'room_id' => $room['room_id'],
                        'room_type_id' => $room_type_id,
                        'check_in_date' => $check_in,
                        'check_out_date' => $check_out,
                        'num_guests' => 1,
                        'num_nights' => $nights,
                        'room_price' => $basePrice,
                        'total_amount' => $total_amount,
                        'guest_name' => $jsonData['name'],
                        'guest_email' => $jsonData['email'],
                        'guest_phone' => $jsonData['phone'],
                        'status' => 'confirmed',
                        'payment_status' => 'unpaid'
                    ];

                    $booking_id = $bookingModel->create($bookingData);

                    if ($booking_id) {
                        // 2.4 Gửi email xác nhận
                        $mailer = getMailer();
                        $fullBooking = $bookingModel->getById($booking_id);
                        $mailer->sendBookingConfirmation($jsonData['email'], $fullBooking);

                        // Cập nhật câu trả lời của AI để báo thành công kèm link
                        $success_tag = "[BOOK_NOW_BTN_SUCCESS: booking_code={$bookingData['booking_code']}, booking_id={$booking_id}]";
                        $full_reply .= "\n\n✅ [HỆ THỐNG]: Đã đặt phòng thành công! " . $success_tag;
                    }
                } else {
                    $full_reply .= "\n\n❌ [HỆ THỐNG]: Rất tiếc, loại phòng này vừa hết chỗ trong khoảng thời gian sếp chọn. Sếp vui lòng chọn loại phòng khác nhé.";
                }
            }
        }

        $stmt = $db->prepare("
            INSERT INTO chat_messages
...
                (conversation_id, sender_id, sender_type, message, message_type, is_internal, is_read, created_at)
            VALUES
                (:cid, 0, 'bot', :msg, 'text', 0, 0, NOW())
        ");
        $stmt->execute([
            ':cid' => $conv_id,
            ':msg' => $full_reply
        ]);
        $new_msg_id = $db->lastInsertId();

        $db->prepare("
            UPDATE chat_conversations
            SET unread_customer = unread_customer + 1,
                unread_staff = 0,
                last_message_at = NOW(),
                last_message_preview = :preview,
                updated_at = NOW()
            WHERE conversation_id = :cid
        ")->execute([
                    ':preview' => mb_substr($full_reply, 0, 100),
                    ':cid' => $conv_id
                ]);
    } catch (Exception $e) {
        error_log("Failed to save AI reply or lead: " . $e->getMessage());
    }
}

echo "data: " . json_encode(["done" => true, "message_id" => $new_msg_id]) . "\n\n";
if (ob_get_level() > 0)
    ob_flush();
flush();
