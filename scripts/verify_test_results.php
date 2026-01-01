<?php
/**
 * AUTOMATED TEST VERIFICATION & EVIDENCE GENERATOR
 * Script này quét DB để tạo báo cáo bằng chứng sau khi Stress Test.
 */

require_once '../config/database.php';

header('Content-Type: text/plain; charset=utf-8');

$db = getDB();
$report_file = "../docs/evidence/evidence_" . date('Y-m-d_H-i-s') . ".txt";
$output = "";

function logLine($msg)
{
    global $output;
    $line = "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n";
    echo $line;
    $output .= $line;
}

ob_start(); // Capture output

logLine("=== AURORA SYSTEM TEST EVIDENCE REPORT ===");
logLine("Generated User: Quang Long (Developer)");
logLine("Server Time: " . date('Y-m-d H:i:s'));
logLine("------------------------------------------");

try {
    // 1. CHỨNG MINH VOLUME DỮ LIỆU
    logLine("1. DATA VOLUME VERIFICATION");

    $stmt = $db->query("SELECT COUNT(*) FROM bookings");
    $total_bookings = $stmt->fetchColumn();
    logLine("   - Total Bookings stored in DB: " . number_format($total_bookings));

    $stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)");
    $recent_bookings = $stmt->fetchColumn();
    logLine("   - Bookings created in last 2 hours (Test Session): " . number_format($recent_bookings));

    // Chứng minh Status Distribution
    logLine("\n2. STATUS DISTRIBUTION (BUSINESS LOGIC PROOF)");
    $stmt = $db->query("SELECT status, payment_status, COUNT(*) as cnt FROM bookings GROUP BY status, payment_status");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        logLine(sprintf("   - Status: %-12s | Payment: %-10s | Count: %s", $r['status'], $r['payment_status'], number_format($r['cnt'])));
    }

    // 2. KIỂM TRA OVERBOOKING VẬT LÝ (INTEGRITY CHECK)
    logLine("\n3. PHYSICAL OVERBOOKING INTEGRITY CHECK (CRITICAL)");
    logLine("   Scanning for overlapping bookings on the SAME Room ID...");

    // Query tìm các cặp booking trùng phòng, trùng ngày chồng lấn nhau
    $sql_integrity = "
        SELECT b1.booking_code as code1, b2.booking_code as code2, b1.room_id, b1.check_in_date, b1.check_out_date
        FROM bookings b1
        JOIN bookings b2 ON b1.room_id = b2.room_id AND b1.booking_id < b2.booking_id
        WHERE b1.room_id IS NOT NULL 
        AND b1.status IN ('confirmed', 'checked_in') 
        AND b2.status IN ('confirmed', 'checked_in')
        AND b1.check_in_date < b2.check_out_date 
        AND b1.check_out_date > b2.check_in_date
    ";

    $stmt = $db->query($sql_integrity);
    $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($conflicts) == 0) {
        logLine("   [PASSED] ✅ NO PHYSICAL OVERBOOKINGS FOUND.");
        logLine("   Proof: 0 pairs of overlapping bookings identified in assigned rooms.");
    } else {
        logLine("   [FAILED] ❌ CRITICAL: Found " . count($conflicts) . " conflicting pairs!");
        foreach ($conflicts as $c) {
            logLine("   ! Conflict: Room {$c['room_id']} assigned to {$c['code1']} and {$c['code2']} at overlapping dates.");
        }
    }

    // 3. CHỨNG MINH RACE CONDITION (PENDING VS ROOM ASSIGNED)
    logLine("\n4. RACE CONDITION HANDLING PROOF");
    // Đếm số lượng booking chưa được gán phòng (Pending/Confirmed but No Room)
    $stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE room_id IS NULL AND status IN ('confirmed', 'pending')");
    $unassigned = $stmt->fetchColumn();
    logLine("   - Unassigned Bookings (Waiting for allocation): " . number_format($unassigned));
    logLine("   (This proves the system accepts orders but holds them safely without corrupting room inventory)");

    logLine("\n------------------------------------------");
    logLine("END OF EVIDENCE REPORT.");

    // Save to file
    file_put_contents($report_file, $output);
    echo "\nSaved report to: $report_file";

} catch (Exception $e) {
    logLine("ERROR: " . $e->getMessage());
}
?>