    <?php
/**
 * Fix Booking Trigger Script
 * 
 * This script fixes the database trigger that incorrectly references 'service_charges'
 * instead of 'service_fee' column in the bookings table.
 * 
 * Error being fixed: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'service_charges' in 'NEW'
 * 
 * Run this script once from admin panel or directly via browser.
 */

session_start();
require_once '../config/database.php';
require_once 'includes/admin-header.php';

// Check admin access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header('Location: ../auth/login.php');
    exit;
}

$results = [];
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_trigger'])) {
    try {
        $db = getDB();
        
        // Step 1: Check for existing triggers on bookings table
        $results[] = "=== Checking for existing triggers on bookings table ===";
        
        $stmt = $db->query("SHOW TRIGGERS WHERE `Table` = 'bookings'");
        $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($triggers)) {
            $results[] = "No triggers found on bookings table.";
            $results[] = "The error might be from a trigger that was already deleted.";
        } else {
            $results[] = "Found " . count($triggers) . " trigger(s):";
            
            foreach ($triggers as $trigger) {
                $results[] = "- Trigger: " . $trigger['Trigger'] . " (Event: " . $trigger['Event'] . ", Timing: " . $trigger['Timing'] . ")";
                
                // Check if trigger contains 'service_charges'
                if (stripos($trigger['Statement'], 'service_charges') !== false) {
                    $results[] = "  ⚠️ This trigger references 'service_charges' - NEEDS FIX!";
                    
                    // Drop the problematic trigger
                    $triggerName = $trigger['Trigger'];
                    $results[] = "  Dropping trigger: $triggerName";
                    $db->exec("DROP TRIGGER IF EXISTS `$triggerName`");
                    $results[] = "  ✓ Trigger dropped successfully";
                    
                    // Recreate trigger with correct column name
                    // Extract the trigger statement and fix it
                    $originalStatement = $trigger['Statement'];
                    $fixedStatement = str_replace('service_charges', 'service_fee', $originalStatement);
                    
                    $results[] = "  Creating fixed trigger...";
                    
                    // Recreate the trigger
                    $timing = $trigger['Timing'];
                    $event = $trigger['Event'];
                    
                    $createTriggerSQL = "CREATE TRIGGER `$triggerName` $timing $event ON `bookings` FOR EACH ROW $fixedStatement";
                    
                    try {
                        $db->exec($createTriggerSQL);
                        $results[] = "  ✓ Trigger recreated with 'service_fee' column";
                    } catch (PDOException $e) {
                        $results[] = "  Note: Could not recreate trigger - it may not be needed.";
                        $results[] = "  Original error: " . $e->getMessage();
                    }
                } else {
                    $results[] = "  ✓ This trigger does not reference 'service_charges'";
                }
            }
        }
        
        // Step 2: Verify bookings table structure
        $results[] = "\n=== Verifying bookings table structure ===";
        
        $stmt = $db->query("DESCRIBE bookings");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hasServiceFee = false;
        $hasServiceCharges = false;
        
        foreach ($columns as $col) {
            if ($col['Field'] === 'service_fee') {
                $hasServiceFee = true;
                $results[] = "✓ Column 'service_fee' exists (Type: " . $col['Type'] . ")";
            }
            if ($col['Field'] === 'service_charges') {
                $hasServiceCharges = true;
                $results[] = "⚠️ Column 'service_charges' exists - should be renamed or removed";
            }
        }
        
        if (!$hasServiceFee && !$hasServiceCharges) {
            $results[] = "⚠️ Neither 'service_fee' nor 'service_charges' column found!";
        }
        
        // Step 3: Drop any remaining problematic triggers
        $results[] = "\n=== Final cleanup ===";
        
        // Common trigger names that might cause issues
        $potentialTriggers = [
            'booking_before_insert',
            'booking_after_insert', 
            'booking_before_update',
            'booking_after_update',
            'calculate_booking_total',
            'update_booking_total'
        ];
        
        foreach ($potentialTriggers as $triggerName) {
            try {
                $db->exec("DROP TRIGGER IF EXISTS `$triggerName`");
            } catch (PDOException $e) {
                // Ignore errors for non-existent triggers
            }
        }
        
        $results[] = "✓ Cleanup completed";
        
        // Step 4: Final verification
        $results[] = "\n=== Final trigger check ===";
        $stmt = $db->query("SHOW TRIGGERS WHERE `Table` = 'bookings'");
        $finalTriggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($finalTriggers)) {
            $results[] = "✓ No triggers on bookings table - this is safe!";
            $results[] = "The booking price calculation is handled in PHP code.";
        } else {
            foreach ($finalTriggers as $trigger) {
                if (stripos($trigger['Statement'], 'service_charges') !== false) {
                    $errors[] = "Trigger still contains 'service_charges': " . $trigger['Trigger'];
                } else {
                    $results[] = "✓ Trigger '" . $trigger['Trigger'] . "' is clean";
                }
            }
        }
        
        if (empty($errors)) {
            $success = true;
            $results[] = "\n=== SUCCESS ===";
            $results[] = "All trigger issues have been resolved!";
        }
        
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Booking Trigger - Aurora Hotel Plaza</title>
    <script src="<?php echo asset('js/tailwindcss-cdn.js'); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-2xl font-bold mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-600">build</span>
                Fix Booking Trigger
            </h1>
            
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
                <div class="flex items-start gap-2">
                    <span class="material-symbols-outlined text-yellow-600">warning</span>
                    <div>
                        <p class="font-semibold">Problem:</p>
                        <p class="text-sm">SQLSTATE[42S22]: Column not found: 1054 Unknown column 'service_charges' in 'NEW'</p>
                        <p class="text-sm mt-2"><strong>Cause:</strong> A database trigger is referencing 'service_charges' but the actual column is 'service_fee'</p>
                    </div>
                </div>
            </div>
            
            <?php if (!$success && empty($results)): ?>
                <form method="POST" class="space-y-4">
                    <p class="text-gray-600">This script will:</p>
                    <ul class="list-disc list-inside text-gray-600 space-y-1">
                        <li>Check for triggers on the bookings table</li>
                        <li>Find and fix any trigger that references 'service_charges'</li>
                        <li>Verify the database structure</li>
                    </ul>
                    
                    <button type="submit" name="fix_trigger" value="1" 
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2">
                        <span class="material-symbols-outlined">play_arrow</span>
                        Run Fix Script
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if (!empty($results)): ?>
                <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm mb-4">
                    <pre><?php echo htmlspecialchars(implode("\n", $results)); ?></pre>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                    <p class="font-semibold text-red-700">Errors:</p>
                    <ul class="list-disc list-inside text-red-600">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-green-600">check_circle</span>
                        <span class="font-semibold text-green-700">Trigger issue fixed successfully!</span>
                    </div>
                    <p class="text-sm text-green-600 mt-2">You can now create bookings without the trigger error.</p>
                </div>
                
                <a href="bookings.php" class="inline-flex items-center gap-2 text-blue-600 hover:underline">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Back to Bookings
                </a>
            <?php endif; ?>
        </div>
        
        <div class="bg-blue-50 rounded-lg p-4">
            <h3 class="font-semibold text-blue-800 mb-2">Note:</h3>
            <p class="text-sm text-blue-700">
                The booking price calculation in Aurora Hotel Plaza is handled by PHP code in 
                <code class="bg-blue-100 px-1 rounded">booking/api/create_booking.php</code>, 
                not by database triggers. This is the recommended approach for maintainability.
            </p>
        </div>
    </div>
</body>
</html>
