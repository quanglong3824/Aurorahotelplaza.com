<?php
/**
 * Fix Booking Trigger Script
 * 
 * This script fixes the database trigger that incorrectly references 'service_charges'
 * instead of 'service_fee' column in the bookings table.
 */

session_start();
require_once '../config/database.php';
require_once '../helpers/session-helper.php';

// Check admin access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'sale', 'receptionist'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Verify user still exists
if (!verifyUserExists('../auth/login.php')) {
    exit;
}

$page_title = 'Fix Booking Trigger';
$page_subtitle = 'Fix database trigger error for service_charges column';
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
                }
            }
        }
        
        // Step 2: Verify bookings table structure
        $results[] = "\n=== Verifying bookings table structure ===";
        
        $stmt = $db->query("DESCRIBE bookings");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hasServiceFee = false;
        foreach ($columns as $col) {
            if ($col['Field'] === 'service_fee') {
                $hasServiceFee = true;
                $results[] = "✓ Column 'service_fee' exists (Type: " . $col['Type'] . ")";
            }
        }
        
        // Step 3: Final verification
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
<?php include 'includes/admin-header.php'; ?>

            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 mb-6">
                <div class="flex items-start gap-2">
                    <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400">warning</span>
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">Problem:</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300">SQLSTATE[42S22]: Column not found: 1054 Unknown column 'service_charges' in 'NEW'</p>
                        <p class="text-sm mt-2 text-gray-700 dark:text-gray-300"><strong>Cause:</strong> A database trigger is referencing 'service_charges' but the actual column is 'service_fee'</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
            <?php if (!$success && empty($results)): ?>
                <form method="POST" class="space-y-4">
                    <p class="text-gray-600 dark:text-gray-400">This script will:</p>
                    <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 space-y-1">
                        <li>Check for triggers on the bookings table</li>
                        <li>Find and fix any trigger that references 'service_charges'</li>
                        <li>Verify the database structure</li>
                    </ul>
                    
                    <button type="submit" name="fix_trigger" value="1" 
                        class="btn btn-primary flex items-center gap-2 mt-6">
                        <span class="material-symbols-outlined">play_arrow</span>
                        Run Fix Script
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if (!empty($results)): ?>
                <div class="bg-gray-900 dark:bg-slate-800 text-green-400 p-4 rounded-lg font-mono text-sm mb-4 overflow-x-auto max-h-96">
                    <pre><?php echo htmlspecialchars(implode("\n", $results)); ?></pre>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 mb-4">
                    <p class="font-semibold text-red-700 dark:text-red-400">Errors:</p>
                    <ul class="list-disc list-inside text-red-600 dark:text-red-400">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-green-600 dark:text-green-400">check_circle</span>
                        <span class="font-semibold text-green-700 dark:text-green-400">Trigger issue fixed successfully!</span>
                    </div>
                    <p class="text-sm text-green-600 dark:text-green-400 mt-2">You can now create bookings without the trigger error.</p>
                </div>
                
                <a href="bookings.php" class="inline-flex items-center gap-2 text-accent hover:underline">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Back to Bookings
                </a>
            <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-6">
                <div class="card-body">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">info</span>
                        Note
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        The booking price calculation in Aurora Hotel Plaza is handled by PHP code in 
                        <code class="bg-gray-100 dark:bg-slate-700 px-2 py-1 rounded text-xs">booking/api/create_booking.php</code>, 
                        not by database triggers. This is the recommended approach for maintainability.
                    </p>
                </div>
            </div>

<?php include 'includes/admin-footer.php' ?? ''; ?>
