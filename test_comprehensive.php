<?php
/**
 * Comprehensive Test Suite for Aurora Hotel Plaza
 * This script tests various components of the application
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = [];
$testsPassed = 0;
$testsFailed = 0;

function testResult($name, $passed, $message = '') {
    global $testsPassed, $testsFailed;
    if ($passed) {
        $testsPassed++;
        echo "✓ PASS: $name\n";
    } else {
        $testsFailed++;
        echo "✗ FAIL: $name" . ($message ? " - $message" : "") . "\n";
    }
}

echo "========================================\n";
echo "AURORA HOTEL PLAZA - COMPREHENSIVE TEST\n";
echo "========================================\n\n";

// 1. PHP Version Check
echo "--- PHP Environment Tests ---\n";
testResult("PHP Version >= 7.4", version_compare(PHP_VERSION, '7.4.0', '>='), "Current: " . PHP_VERSION);

// 2. Required Extensions
$requiredExtensions = ['mysqli', 'json', 'session', 'gd', 'curl', 'openssl'];
foreach ($requiredExtensions as $ext) {
    testResult("Extension: $ext", extension_loaded($ext));
}

// 3. Configuration Files Check
echo "\n--- Configuration Files ---\n";
$configFiles = [
    'config/environment.php',
    'config/email.php',
    'config/performance.php',
    'config/router.php'
];
foreach ($configFiles as $file) {
    testResult("Config file exists: $file", file_exists($file));
}

// 4. Environment File Check
// Cấu trúc production:
// (root)/
// ├── public_html/  (chứa code - tương đương thư mục hiện tại)
// └── config/
//     └── .env      (file .env ngoài public)
$envLocations = [
    'config/.env',                    // Local development (trong public_html)
    __DIR__ . '/../../config/.env',   // Production: public_html/../config/.env
    __DIR__ . '/../config/.env',      // Alternative path
];
$envExists = false;
$foundPath = '';
foreach ($envLocations as $loc) {
    if (file_exists($loc)) {
        $envExists = true;
        $foundPath = $loc;
        break;
    }
}
testResult(".env file exists", $envExists, $envExists ? "Found: $foundPath" : "Checked: config/.env, ../../config/.env");

// 5. Database Connection Test
echo "\n--- Database Connection ---\n";
try {
    if (file_exists('config/environment.php')) {
        include_once 'config/environment.php';
        testResult("Environment config loaded", true);
    } else {
        testResult("Environment config loaded", false, "File not found");
    }
} catch (Exception $e) {
    testResult("Environment config loaded", false, $e->getMessage());
}

// 6. Helper Files Syntax Check
echo "\n--- Helper Files Syntax ---\n";
$helperFiles = glob('helpers/*.php');
foreach ($helperFiles as $file) {
    $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    $passed = strpos($output, 'No syntax errors') !== false;
    testResult("Syntax: " . basename($file), $passed);
}

// 7. Model Files Syntax Check
echo "\n--- Model Files Syntax ---\n";
$modelFiles = glob('models/*.php');
foreach ($modelFiles as $file) {
    $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    $passed = strpos($output, 'No syntax errors') !== false;
    testResult("Syntax: " . basename($file), $passed);
}

// 8. Main Pages Syntax Check
echo "\n--- Main Pages Syntax ---\n";
$mainPages = ['index.php', 'rooms.php', 'apartments.php', 'contact.php', 'about.php'];
foreach ($mainPages as $page) {
    $output = shell_exec("php -l " . escapeshellarg($page) . " 2>&1");
    $passed = strpos($output, 'No syntax errors') !== false;
    testResult("Syntax: $page", $passed);
}

// 9. Admin Pages Sample Check
echo "\n--- Admin Pages Syntax (Sample) ---\n";
$adminPages = ['admin/dashboard.php', 'admin/bookings.php', 'admin/rooms.php'];
foreach ($adminPages as $page) {
    $output = shell_exec("php -l " . escapeshellarg($page) . " 2>&1");
    $passed = strpos($output, 'No syntax errors') !== false;
    testResult("Syntax: $page", $passed);
}

// 10. API Files Check
echo "\n--- API Files Syntax ---\n";
$apiFiles = glob('api/*.php');
foreach ($apiFiles as $file) {
    $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    $passed = strpos($output, 'No syntax errors') !== false;
    testResult("Syntax: " . basename($file), $passed);
}

// 11. Payment Files Check
echo "\n--- Payment Files Syntax ---\n";
$paymentFiles = glob('payment/*.php');
foreach ($paymentFiles as $file) {
    $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    $passed = strpos($output, 'No syntax errors') !== false;
    testResult("Syntax: " . basename($file), $passed);
}

// 12. Auth Files Check
echo "\n--- Auth Files Syntax ---\n";
$authFiles = glob('auth/*.php');
foreach ($authFiles as $file) {
    $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    $passed = strpos($output, 'No syntax errors') !== false;
    testResult("Syntax: " . basename($file), $passed);
}

// 13. Booking Files Check
echo "\n--- Booking Files Syntax ---\n";
$bookingFiles = glob('booking/*.php');
foreach ($bookingFiles as $file) {
    $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    $passed = strpos($output, 'No syntax errors') !== false;
    testResult("Syntax: " . basename($file), $passed);
}

// 14. Profile Files Check
echo "\n--- Profile Files Syntax ---\n";
$profileFiles = glob('profile/*.php');
foreach ($profileFiles as $file) {
    $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    $passed = strpos($output, 'No syntax errors') !== false;
    testResult("Syntax: " . basename($file), $passed);
}

// 15. Include Files Check
echo "\n--- Include Files Syntax ---\n";
$includeFiles = glob('includes/*.php');
foreach ($includeFiles as $file) {
    $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    $passed = strpos($output, 'No syntax errors') !== false;
    testResult("Syntax: " . basename($file), $passed);
}

// 16. Language Files Check
echo "\n--- Language Files ---\n";
$langFiles = glob('lang/*.php');
foreach ($langFiles as $file) {
    $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    $passed = strpos($output, 'No syntax errors') !== false;
    testResult("Syntax: " . basename($file), $passed);
    if ($passed) {
        include $file;
        testResult("Lang array loaded: " . basename($file), isset($lang) && is_array($lang));
    }
}

// 17. Check for Required Directories
echo "\n--- Required Directories ---\n";
$requiredDirs = ['assets', 'config', 'helpers', 'includes', 'models', 'api', 'admin', 'auth', 'booking', 'profile', 'payment'];
foreach ($requiredDirs as $dir) {
    testResult("Directory exists: $dir", is_dir($dir));
}

// 18. Check Write Permissions
echo "\n--- Write Permissions ---\n";
$writableDirs = ['assets', 'config'];
foreach ($writableDirs as $dir) {
    testResult("Writable: $dir", is_writable($dir));
}

// 19. Composer Dependencies
echo "\n--- Composer ---\n";
testResult("composer.json exists", file_exists('composer.json'));
if (file_exists('composer.json')) {
    $composer = json_decode(file_get_contents('composer.json'), true);
    testResult("composer.json valid", $composer !== null);
    if ($composer) {
        testResult("Has dependencies", isset($composer['require']) && count($composer['require']) > 0);
    }
}

// 20. Check for Vendor Directory
testResult("vendor directory exists", is_dir('vendor'));

// Summary
echo "\n========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n";
echo "Passed: $testsPassed\n";
echo "Failed: $testsFailed\n";
echo "Total:  " . ($testsPassed + $testsFailed) . "\n";
echo "========================================\n";

if ($testsFailed > 0) {
    echo "\n⚠ Some tests failed. Please review the output above.\n";
    exit(1);
} else {
    echo "\n✓ All tests passed!\n";
    exit(0);
}
?>