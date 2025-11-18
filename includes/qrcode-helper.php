<?php
/**
 * QR Code Helper Functions
 * Generates QR codes for bookings
 */

/**
 * Generate QR code data for booking
 */
function generateBookingQRData($booking) {
    $data = [
        'booking_code' => $booking['booking_code'],
        'booking_id' => $booking['booking_id'],
        'guest_name' => $booking['guest_name'],
        'guest_email' => $booking['guest_email'],
        'guest_phone' => $booking['guest_phone'],
        'room_type' => $booking['type_name'],
        'check_in_date' => $booking['check_in_date'],
        'check_out_date' => $booking['check_out_date'],
        'num_adults' => $booking['num_adults'],
        'num_children' => $booking['num_children'] ?? 0,
        'total_nights' => $booking['total_nights'],
        'total_amount' => $booking['total_amount'],
        'status' => $booking['status'],
        'hotel' => 'Aurora Hotel Plaza'
    ];
    
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}

/**
 * Generate QR code image using Google Charts API
 */
function generateQRCodeImage($data, $size = 300) {
    $encoded_data = urlencode($data);
    $url = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encoded_data}&choe=UTF-8";
    return $url;
}

/**
 * Generate QR code using PHP QR Code library (if available)
 * Falls back to Google Charts API if library is not available
 */
function generateQRCodeFile($booking, $output_path = null) {
    $qr_data = generateBookingQRData($booking);
    
    // Check if PHP QR Code library is available
    if (file_exists(__DIR__ . '/../vendor/phpqrcode/qrlib.php')) {
        require_once __DIR__ . '/../vendor/phpqrcode/qrlib.php';
        
        if ($output_path === null) {
            $output_path = __DIR__ . '/../uploads/qrcodes/' . $booking['booking_code'] . '.png';
        }
        
        // Create directory if it doesn't exist
        $dir = dirname($output_path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Generate QR code
        QRcode::png($qr_data, $output_path, QR_ECLEVEL_L, 10, 2);
        
        return [
            'success' => true,
            'path' => $output_path,
            'url' => str_replace(__DIR__ . '/..', '', $output_path)
        ];
    }
    
    // Fallback to Google Charts API
    return [
        'success' => true,
        'url' => generateQRCodeImage($qr_data, 400),
        'external' => true
    ];
}

/**
 * Generate QR code as base64 data URL
 */
function generateQRCodeBase64($booking) {
    $qr_data = generateBookingQRData($booking);
    
    // Try to use PHP QR Code library
    if (file_exists(__DIR__ . '/../vendor/phpqrcode/qrlib.php')) {
        require_once __DIR__ . '/../vendor/phpqrcode/qrlib.php';
        
        ob_start();
        QRcode::png($qr_data, null, QR_ECLEVEL_L, 10, 2);
        $image_data = ob_get_contents();
        ob_end_clean();
        
        return 'data:image/png;base64,' . base64_encode($image_data);
    }
    
    // Fallback: fetch from Google Charts API
    $url = generateQRCodeImage($qr_data, 400);
    $image_data = @file_get_contents($url);
    
    if ($image_data !== false) {
        return 'data:image/png;base64,' . base64_encode($image_data);
    }
    
    return null;
}

/**
 * Simple QR Code generator without external libraries
 * Uses a simple matrix-based approach
 */
function generateSimpleQRCode($data, $size = 300) {
    // For simplicity, we'll use Google Charts API as fallback
    // In production, you should use a proper QR code library
    return generateQRCodeImage($data, $size);
}

/**
 * Verify QR code data
 */
function verifyQRCodeData($qr_data) {
    try {
        $data = json_decode($qr_data, true);
        
        if (!$data || !isset($data['booking_code']) || !isset($data['booking_id'])) {
            return ['valid' => false, 'message' => 'Invalid QR code data'];
        }
        
        return ['valid' => true, 'data' => $data];
    } catch (Exception $e) {
        return ['valid' => false, 'message' => 'Error parsing QR code data'];
    }
}

/**
 * Save QR code to booking record
 */
function saveQRCodeToBooking($db, $booking_id, $qr_code_path) {
    try {
        $stmt = $db->prepare("UPDATE bookings SET qr_code = ? WHERE booking_id = ?");
        $stmt->execute([$qr_code_path, $booking_id]);
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Error saving QR code to booking: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>
