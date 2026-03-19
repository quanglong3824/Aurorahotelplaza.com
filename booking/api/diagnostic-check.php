<?php
/**
 * ============================================================================
 * AURORA HOTEL PLAZA - COMPREHENSIVE BOOKING DIAGNOSTIC TOOL
 * ============================================================================
 * 
 * This diagnostic tool performs a complete health check of the booking system:
 * - Database connection and permissions
 * - Table structure validation
 * - Column existence checks (all 27+ fields)
 * - Sample booking test with transaction rollback
 * - PHP error detection
 * - Session validation
 * - File dependency checks
 * - Performance timing
 * 
 * Usage: Access via browser at /booking/api/diagnostic-check.php
 * Output: JSON response with detailed diagnostic information
 * 
 * @author Aurora Development Team
 * @version 1.0.0
 * @date 2026-03-19
 * ============================================================================
 */

// Disable output buffering for real-time output
while (ob_get_level()) {
    ob_end_clean();
}

// Set headers for JSON response and no caching
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Expires: 0');
header('Pragma: no-cache');

// Start output buffering to capture all output
ob_start();

// Start timing
$startTime = microtime(true);

/**
 * Diagnostic Result Collector
 */
class DiagnosticResult {
    public $success = true;
    public $timestamp;
    public $duration_ms;
    public $checks = [];
    public $errors = [];
    public $warnings = [];
    public $info = [];
    public $database = [];
    public $tables = [];
    public $columns = [];
    public $sample_booking_test = [];
    public $php_environment = [];
    public $session_data = [];
    public $file_checks = [];
    public $permissions = [];
    
    public function __construct() {
        $this->timestamp = date('Y-m-d H:i:s');
    }
    
    public function addCheck($name, $passed, $message = '', $details = []) {
        $this->checks[] = [
            'name' => $name,
            'passed' => $passed,
            'message' => $message,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if (!$passed) {
            $this->success = false;
            $this->errors[] = $message;
        }
    }
    
    public function addWarning($message) {
        $this->warnings[] = $message;
    }
    
    public function addInfo($message) {
        $this->info[] = $message;
    }
    
    public function getSummary() {
        $totalChecks = count($this->checks);
        $passedChecks = count(array_filter($this->checks, fn($c) => $c['passed']));
        $failedChecks = $totalChecks - $passedChecks;
        
        return [
            'total_checks' => $totalChecks,
            'passed' => $passedChecks,
            'failed' => $failedChecks,
            'success_rate' => $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100, 2) : 0,
            'errors_count' => count($this->errors),
            'warnings_count' => count($this->warnings)
        ];
    }
}

$result = new DiagnosticResult();

/**
 * Helper function to log to both response and error_log
 */
function logDiagnostic($message, $level = 'INFO') {
    error_log("[DIAGNOSTIC][$level] $message");
}

/**
 * SECTION 1: PHP ENVIRONMENT CHECKS
 */
logDiagnostic('Starting PHP Environment Checks...', 'INFO');

$result->php_environment = [
    'php_version' => phpversion(),
    'php_sapi' => php_sapi_name(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
    'current_working_dir' => getcwd(),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => error_reporting(),
    'timezone' => date_default_timezone_get(),
    'extensions_loaded' => get_loaded_extensions(),
    'pdo_drivers' => PDO::getAvailableDrivers(),
    'openssl_loaded' => extension_loaded('openssl'),
    'curl_loaded' => extension_loaded('curl'),
    'json_loaded' => extension_loaded('json'),
    'mbstring_loaded' => extension_loaded('mbstring'),
    'session_enabled' => ini_get('session.auto_start') || session_status() === PHP_SESSION_ACTIVE,
    'file_uploads' => ini_get('file_uploads'),
    'error_log_path' => ini_get('error_log') ?: 'Not configured'
];

// Check required PHP extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'session', 'mbstring', 'curl'];
$missingExtensions = [];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

$result->addCheck(
    'PHP Required Extensions',
    empty($missingExtensions),
    empty($missingExtensions) 
        ? 'All required PHP extensions are loaded' 
        : 'Missing extensions: ' . implode(', ', $missingExtensions),
    ['required' => $requiredExtensions, 'missing' => $missingExtensions]
);

// Check PHP version
$minPhpVersion = '7.4.0';
$phpVersionOk = version_compare(phpversion(), $minPhpVersion, '>=');
$result->addCheck(
    'PHP Version Check',
    $phpVersionOk,
    $phpVersionOk 
        ? "PHP version " . phpversion() . " meets minimum requirement ($minPhpVersion)" 
        : "PHP version " . phpversion() . " is below minimum ($minPhpVersion)",
    ['current' => phpversion(), 'minimum' => $minPhpVersion]
);

/**
 * SECTION 2: SESSION CHECKS
 */
logDiagnostic('Checking Session Data...', 'INFO');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$result->session_data = [
    'session_id' => session_id(),
    'session_name' => session_name(),
    'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive',
    'session_save_path' => ini_get('session.save_path'),
    'session_cookie_lifetime' => ini_get('session.cookie_lifetime'),
    'session_gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
    'session_data' => $_SESSION ?? [],
    'user_id' => $_SESSION['user_id'] ?? null,
    'user_email' => $_SESSION['user_email'] ?? null,
    'user_name' => $_SESSION['user_name'] ?? null,
    'language' => $_SESSION['language'] ?? null,
    'csrf_token' => $_SESSION['csrf_token'] ?? null
];

$result->addCheck(
    'Session Active',
    session_status() === PHP_SESSION_ACTIVE,
    session_status() === PHP_SESSION_ACTIVE ? 'Session is active' : 'Session failed to start',
    ['session_id' => session_id()]
);

/**
 * SECTION 3: FILE DEPENDENCY CHECKS
 */
logDiagnostic('Checking Required Files...', 'INFO');

$baseDir = dirname(dirname(dirname(__FILE__)));
$requiredFiles = [
    'config/database.php' => $baseDir . '/config/database.php',
    'config/load_env.php' => $baseDir . '/config/load_env.php',
    'helpers/functions.php' => $baseDir . '/helpers/functions.php',
    'helpers/language.php' => $baseDir . '/helpers/language.php',
    'helpers/booking-helper.php' => $baseDir . '/helpers/booking-helper.php',
    'src/Core/DTOs/GuestDTO.php' => $baseDir . '/src/Core/DTOs/GuestDTO.php',
    'src/Core/Repositories/RoomRepository.php' => $baseDir . '/src/Core/Repositories/RoomRepository.php',
    'src/Core/Repositories/BookingRepository.php' => $baseDir . '/src/Core/Repositories/BookingRepository.php',
    'src/Core/Services/PricingService.php' => $baseDir . '/src/Core/Services/PricingService.php',
    'src/Core/Services/BookingService.php' => $baseDir . '/src/Core/Services/BookingService.php'
];

$missingFiles = [];
$accessibleFiles = [];
foreach ($requiredFiles as $name => $path) {
    $exists = file_exists($path);
    $readable = is_readable($path);
    
    if ($exists && $readable) {
        $accessibleFiles[] = $name;
    } else {
        $missingFiles[] = [
            'file' => $name,
            'path' => $path,
            'exists' => $exists,
            'readable' => $readable
        ];
    }
    
    $result->file_checks[] = [
        'file' => $name,
        'path' => $path,
        'exists' => $exists,
        'readable' => $readable,
        'size_bytes' => $exists ? filesize($path) : 0
    ];
}

$result->addCheck(
    'Required Files Exist',
    empty($missingFiles),
    empty($missingFiles) 
        ? 'All required files exist and are readable' 
        : 'Missing/unreadable files: ' . count($missingFiles),
    ['missing' => $missingFiles, 'accessible' => $accessibleFiles]
);

/**
 * SECTION 4: DATABASE CONNECTION CHECK
 */
logDiagnostic('Checking Database Connection...', 'INFO');

try {
    // Load environment and database config
    $envFile = $baseDir . '/config/load_env.php';
    if (file_exists($envFile)) {
        require_once $envFile;
    }
    
    $dbConfigFile = $baseDir . '/config/database.php';
    if (file_exists($dbConfigFile)) {
        require_once $dbConfigFile;
    }
    
    // Try to get database connection
    $db = null;
    $connectionError = null;
    
    try {
        if (function_exists('getDB')) {
            $db = getDB();
        } elseif (class_exists('Database')) {
            $database = new Database();
            $db = $database->getConnection();
        }
    } catch (Throwable $e) {
        $connectionError = $e->getMessage();
    }
    
    $result->database = [
        'connection_successful' => $db !== null,
        'connection_error' => $connectionError,
        'db_name' => defined('DB_NAME') ? DB_NAME : 'Not defined',
        'db_host' => defined('DB_HOST') ? DB_HOST : 'Not defined',
        'db_user' => defined('DB_USER') ? DB_USER : 'Not defined',
        'db_charset' => defined('DB_CHARSET') ? DB_CHARSET : 'Not defined',
        'db_environment' => defined('DB_ENVIRONMENT') ? DB_ENVIRONMENT : 'Not defined',
        'pdo_available' => $db !== null,
        'pdo_error_info' => $db ? $db->errorInfo() : null,
        'server_version' => $db ? $db->getAttribute(PDO::ATTR_SERVER_VERSION) : null,
        'driver_name' => $db ? $db->getAttribute(PDO::ATTR_DRIVER_NAME) : null,
        'connection_time_ms' => 0
    ];
    
    $result->addCheck(
        'Database Connection',
        $db !== null,
        $db !== null 
            ? 'Successfully connected to database' 
            : 'Database connection failed: ' . ($connectionError ?? 'Unknown error'),
        $result->database
    );
    
    if ($db === null) {
        throw new Exception('Cannot proceed without database connection');
    }
    
    /**
     * SECTION 5: DATABASE PERMISSIONS CHECK
     */
    logDiagnostic('Checking Database Permissions...', 'INFO');
    
    $permissions = [
        'can_select' => false,
        'can_insert' => false,
        'can_update' => false,
        'can_delete' => false,
        'can_create_table' => false,
        'can_drop_table' => false
    ];
    
    try {
        // Test SELECT
        $stmt = $db->query("SELECT 1");
        $permissions['can_select'] = $stmt !== false;
    } catch (Throwable $e) {
        $permissions['select_error'] = $e->getMessage();
    }
    
    try {
        // Test INSERT (on a test table)
        $testTableName = '_diag_test_' . uniqid();
        $db->exec("CREATE TEMPORARY TABLE $testTableName (id INT)");
        $db->exec("INSERT INTO $testTableName VALUES (1)");
        $permissions['can_insert'] = true;
    } catch (Throwable $e) {
        $permissions['insert_error'] = $e->getMessage();
    }
    
    try {
        // Test UPDATE
        $db->exec("UPDATE _diag_test_" . explode('_', $_SERVER['REQUEST_TIME_FLOAT'] ?? time())[0] . " SET id = 2 WHERE id = 1");
        $permissions['can_update'] = true;
    } catch (Throwable $e) {
        $permissions['update_error'] = $e->getMessage();
    }
    
    $result->permissions = $permissions;
    
    $allPermissionsOk = $permissions['can_select'] && $permissions['can_insert'] && $permissions['can_update'];
    $result->addCheck(
        'Database Permissions',
        $allPermissionsOk,
        $allPermissionsOk 
            ? 'Database user has required SELECT, INSERT, UPDATE permissions' 
            : 'Missing database permissions',
        $permissions
    );
    
    /**
     * SECTION 6: TABLE EXISTENCE CHECK
     */
    logDiagnostic('Checking Required Tables...', 'INFO');
    
    $requiredTables = [
        'bookings',
        'booking_extra_guests',
        'booking_history',
        'room_types',
        'rooms',
        'users',
        'payments'
    ];
    
    $existingTables = [];
    $missingTables = [];
    
    try {
        $stmt = $db->query("SHOW TABLES");
        $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($requiredTables as $table) {
            $tableExists = in_array($table, $allTables);
            if ($tableExists) {
                $existingTables[] = $table;
                
                // Get table row count
                try {
                    $countStmt = $db->query("SELECT COUNT(*) as count FROM $table");
                    $rowCount = $countStmt->fetchColumn();
                } catch (Throwable $e) {
                    $rowCount = 'Error: ' . $e->getMessage();
                }
                
                $result->tables[$table] = [
                    'exists' => true,
                    'row_count' => $rowCount,
                    'in_database' => true
                ];
            } else {
                $missingTables[] = $table;
                $result->tables[$table] = [
                    'exists' => false,
                    'row_count' => 0,
                    'in_database' => false
                ];
            }
        }
        
        $result->database['all_tables'] = $allTables;
        $result->database['total_tables'] = count($allTables);
        
    } catch (Throwable $e) {
        $result->addWarning('Failed to retrieve table list: ' . $e->getMessage());
    }
    
    $result->addCheck(
        'Required Tables Exist',
        empty($missingTables),
        empty($missingTables) 
            ? 'All required tables exist' 
            : 'Missing tables: ' . implode(', ', $missingTables),
        ['existing' => $existingTables, 'missing' => $missingTables]
    );
    
    /**
     * SECTION 7: COLUMN EXISTENCE CHECK - BOOKINGS TABLE
     */
    logDiagnostic('Checking Bookings Table Columns...', 'INFO');
    
    // All expected columns in bookings table (27+ fields)
    $expectedBookingColumns = [
        'booking_id',
        'booking_code',
        'booking_type',
        'user_id',
        'guest_uuid',
        'room_type_id',
        'room_id',
        'check_in_date',
        'check_out_date',
        'num_adults',
        'num_children',
        'num_rooms',
        'total_nights',
        'room_price',
        'service_fee',
        'discount_amount',
        'points_used',
        'total_amount',
        'special_requests',
        'inquiry_message',
        'duration_type',
        'guest_name',
        'guest_email',
        'guest_phone',
        'guest_id_number',
        'status',
        'payment_status',
        'qr_code',
        'confirmation_sent',
        'checked_in_at',
        'checked_out_at',
        'checked_in_by',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'created_at',
        'updated_at',
        'occupancy_type',
        'extra_guest_fee',
        'extra_bed_fee',
        'extra_beds',
        'short_stay_hours',
        'expected_checkin_time',
        'expected_checkout_time',
        'price_type_used'
    ];
    
    $existingColumns = [];
    $missingColumns = [];
    $columnDetails = [];
    
    try {
        $stmt = $db->query("DESCRIBE bookings");
        $actualColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $actualColumnNames = array_column($actualColumns, 'Field');
        
        foreach ($expectedBookingColumns as $col) {
            $colExists = in_array($col, $actualColumnNames);
            if ($colExists) {
                $existingColumns[] = $col;
                
                // Find column details
                $colInfo = array_filter($actualColumns, fn($c) => $c['Field'] === $col);
                $columnDetails[$col] = !empty($colInfo) ? reset($colInfo) : null;
            } else {
                $missingColumns[] = $col;
            }
        }
        
        $result->columns['bookings'] = [
            'expected_count' => count($expectedBookingColumns),
            'existing_count' => count($existingColumns),
            'missing_count' => count($missingColumns),
            'existing' => $existingColumns,
            'missing' => $missingColumns,
            'details' => $columnDetails
        ];
        
    } catch (Throwable $e) {
        $result->addWarning('Failed to describe bookings table: ' . $e->getMessage());
    }
    
    $result->addCheck(
        'Bookings Table Columns',
        empty($missingColumns),
        empty($missingColumns) 
            ? 'All ' . count($expectedBookingColumns) . ' expected columns exist' 
            : 'Missing columns: ' . implode(', ', $missingColumns),
        ['expected' => count($expectedBookingColumns), 'existing' => count($existingColumns), 'missing' => $missingColumns]
    );
    
    /**
     * SECTION 8: COLUMN EXISTENCE CHECK - BOOKING_EXTRA_GUESTS TABLE
     */
    logDiagnostic('Checking Booking Extra Guests Table Columns...', 'INFO');
    
    $expectedExtraGuestColumns = [
        'id',
        'booking_id',
        'guest_type',
        'guest_name',
        'height_cm',
        'age',
        'fee',
        'includes_breakfast',
        'created_at'
    ];
    
    $existingExtraGuestColumns = [];
    $missingExtraGuestColumns = [];
    
    try {
        $stmt = $db->query("DESCRIBE booking_extra_guests");
        $actualColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $actualColumnNames = array_column($actualColumns, 'Field');
        
        foreach ($expectedExtraGuestColumns as $col) {
            if (in_array($col, $actualColumnNames)) {
                $existingExtraGuestColumns[] = $col;
            } else {
                $missingExtraGuestColumns[] = $col;
            }
        }
        
        $result->columns['booking_extra_guests'] = [
            'expected_count' => count($expectedExtraGuestColumns),
            'existing_count' => count($existingExtraGuestColumns),
            'missing_count' => count($missingExtraGuestColumns),
            'existing' => $existingExtraGuestColumns,
            'missing' => $missingExtraGuestColumns
        ];
        
    } catch (Throwable $e) {
        $result->addWarning('Failed to describe booking_extra_guests table: ' . $e->getMessage());
    }
    
    $result->addCheck(
        'Booking Extra Guests Table Columns',
        empty($missingExtraGuestColumns),
        empty($missingExtraGuestColumns) 
            ? 'All expected columns exist' 
            : 'Missing columns: ' . implode(', ', $missingExtraGuestColumns),
        ['missing' => $missingExtraGuestColumns]
    );
    
    /**
     * SECTION 9: COLUMN EXISTENCE CHECK - ROOM_TYPES TABLE
     */
    logDiagnostic('Checking Room Types Table Columns...', 'INFO');
    
    $expectedRoomTypeColumns = [
        'room_type_id',
        'type_name',
        'type_name_en',
        'slug',
        'category',
        'booking_type',
        'description',
        'description_en',
        'short_description',
        'short_description_en',
        'max_occupancy',
        'max_adults',
        'max_children',
        'is_twin',
        'size_sqm',
        'bed_type',
        'amenities',
        'images',
        'thumbnail',
        'base_price',
        'weekend_price',
        'holiday_price',
        'status',
        'sort_order',
        'created_at',
        'updated_at',
        'price_published',
        'price_single_occupancy',
        'price_double_occupancy',
        'price_short_stay',
        'short_stay_description',
        'view_type',
        'price_daily_single',
        'price_daily_double',
        'price_weekly_single',
        'price_weekly_double',
        'price_avg_weekly_single',
        'price_avg_weekly_double'
    ];
    
    $existingRoomTypeColumns = [];
    $missingRoomTypeColumns = [];
    
    try {
        $stmt = $db->query("DESCRIBE room_types");
        $actualColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $actualColumnNames = array_column($actualColumns, 'Field');
        
        foreach ($expectedRoomTypeColumns as $col) {
            if (in_array($col, $actualColumnNames)) {
                $existingRoomTypeColumns[] = $col;
            } else {
                $missingRoomTypeColumns[] = $col;
            }
        }
        
        $result->columns['room_types'] = [
            'expected_count' => count($expectedRoomTypeColumns),
            'existing_count' => count($existingRoomTypeColumns),
            'missing_count' => count($missingRoomTypeColumns),
            'existing' => $existingRoomTypeColumns,
            'missing' => $missingRoomTypeColumns
        ];
        
    } catch (Throwable $e) {
        $result->addWarning('Failed to describe room_types table: ' . $e->getMessage());
    }
    
    $result->addCheck(
        'Room Types Table Columns',
        empty($missingRoomTypeColumns),
        empty($missingRoomTypeColumns) 
            ? 'All expected columns exist' 
            : 'Missing columns: ' . implode(', ', $missingRoomTypeColumns),
        ['missing' => $missingRoomTypeColumns]
    );
    
    /**
     * SECTION 10: SAMPLE BOOKING TEST WITH ROLLBACK
     */
    logDiagnostic('Running Sample Booking Test...', 'INFO');
    
    $sampleBookingTest = [
        'started' => false,
        'transaction_started' => false,
        'insert_successful' => false,
        'rollback_successful' => false,
        'test_booking_id' => null,
        'test_booking_code' => null,
        'errors' => [],
        'duration_ms' => 0
    ];
    
    $sampleBookingStart = microtime(true);
    $sampleBookingTest['started'] = true;
    
    try {
        // Generate unique test data
        $testCode = 'DIAG' . strtoupper(substr(md5(uniqid('diag_test', true)), 0, 8));
        $testEmail = 'diagnostic_test_' . time() . '@aurorahotelplaza.test';
        
        // Get a valid room_type_id
        $roomTypeStmt = $db->query("SELECT room_type_id FROM room_types WHERE status = 'active' LIMIT 1");
        $roomTypeId = $roomTypeStmt->fetchColumn();
        
        if (!$roomTypeId) {
            // Try any room type
            $roomTypeStmt = $db->query("SELECT room_type_id FROM room_types LIMIT 1");
            $roomTypeId = $roomTypeStmt->fetchColumn();
        }
        
        $sampleBookingTest['test_room_type_id'] = $roomTypeId;
        
        if (!$roomTypeId) {
            throw new Exception('No room types available for testing');
        }
        
        // Start transaction
        $db->beginTransaction();
        $sampleBookingTest['transaction_started'] = true;
        
        // Insert test booking
        $insertStmt = $db->prepare("
            INSERT INTO bookings (
                booking_code, booking_type, user_id, guest_uuid, room_type_id, room_id,
                check_in_date, check_out_date, num_adults, num_children, num_rooms, total_nights,
                room_price, total_amount, guest_name, guest_email, guest_phone,
                status, payment_status, created_at, updated_at
            ) VALUES (
                :booking_code, :booking_type, :user_id, :guest_uuid, :room_type_id, :room_id,
                :check_in_date, :check_out_date, :num_adults, :num_children, :num_rooms, :total_nights,
                :room_price, :total_amount, :guest_name, :guest_email, :guest_phone,
                :status, :payment_status, NOW(), NOW()
            )
        ");
        
        $testCheckIn = date('Y-m-d', strtotime('+7 days'));
        $testCheckOut = date('Y-m-d', strtotime('+10 days'));
        
        $insertResult = $insertStmt->execute([
            ':booking_code' => $testCode,
            ':booking_type' => 'instant',
            ':user_id' => null,
            ':guest_uuid' => 'diag_test_' . uniqid(),
            ':room_type_id' => $roomTypeId,
            ':room_id' => null,
            ':check_in_date' => $testCheckIn,
            ':check_out_date' => $testCheckOut,
            ':num_adults' => 2,
            ':num_children' => 0,
            ':num_rooms' => 1,
            ':total_nights' => 3,
            ':room_price' => 1000000,
            ':total_amount' => 3000000,
            ':guest_name' => 'Diagnostic Test User',
            ':guest_email' => $testEmail,
            ':guest_phone' => '0123456789',
            ':status' => 'pending',
            ':payment_status' => 'unpaid'
        ]);
        
        if ($insertResult) {
            $sampleBookingTest['insert_successful'] = true;
            $sampleBookingTest['test_booking_id'] = (int)$db->lastInsertId();
            $sampleBookingTest['test_booking_code'] = $testCode;
            
            // Verify the insert
            $verifyStmt = $db->prepare("SELECT * FROM bookings WHERE booking_id = ?");
            $verifyStmt->execute([$sampleBookingTest['test_booking_id']]);
            $verifyResult = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            $sampleBookingTest['verify_result'] = $verifyResult !== false;
            $sampleBookingTest['verify_data'] = $verifyResult;
            
            if ($verifyResult === false) {
                throw new Exception('Inserted record could not be retrieved');
            }
        } else {
            throw new Exception('Insert statement failed');
        }
        
        // Rollback transaction (clean up test data)
        $db->rollBack();
        $sampleBookingTest['rollback_successful'] = true;
        
        // Verify rollback
        $verifyAfterRollback = $db->prepare("SELECT COUNT(*) FROM bookings WHERE booking_code = ?");
        $verifyAfterRollback->execute([$testCode]);
        $countAfterRollback = $verifyAfterRollback->fetchColumn();
        
        $sampleBookingTest['rollback_verified'] = ($countAfterRollback == 0);
        
        if (!$sampleBookingTest['rollback_verified']) {
            $sampleBookingTest['warnings'][] = 'Rollback may not have worked correctly - test record still exists';
        }
        
    } catch (Throwable $e) {
        $sampleBookingTest['errors'][] = $e->getMessage();
        $sampleBookingTest['error_trace'] = $e->getTraceAsString();
        
        // Try to rollback if transaction is still active
        try {
            if ($db->inTransaction()) {
                $db->rollBack();
                $sampleBookingTest['emergency_rollback'] = true;
            }
        } catch (Throwable $rollbackError) {
            $sampleBookingTest['errors'][] = 'Emergency rollback failed: ' . $rollbackError->getMessage();
        }
    }
    
    $sampleBookingTest['duration_ms'] = round((microtime(true) - $sampleBookingStart) * 1000, 2);
    $result->sample_booking_test = $sampleBookingTest;
    
    $sampleBookingPassed = $sampleBookingTest['insert_successful'] && $sampleBookingTest['rollback_successful'];
    
    $result->addCheck(
        'Sample Booking Test',
        $sampleBookingPassed,
        $sampleBookingPassed 
            ? 'Successfully created and rolled back test booking' 
            : 'Sample booking test failed: ' . implode('; ', $sampleBookingTest['errors']),
        $sampleBookingTest
    );
    
    /**
     * SECTION 11: CHECK FOR RECENT BOOKINGS (PRODUCTION DATA VALIDATION)
     */
    logDiagnostic('Checking Recent Bookings Data...', 'INFO');
    
    $recentBookingsCheck = [
        'total_bookings' => 0,
        'recent_bookings_24h' => 0,
        'recent_bookings_7d' => 0,
        'pending_bookings' => 0,
        'confirmed_bookings' => 0,
        'status_distribution' => [],
        'last_booking' => null
    ];
    
    try {
        // Total bookings
        $stmt = $db->query("SELECT COUNT(*) FROM bookings");
        $recentBookingsCheck['total_bookings'] = (int)$stmt->fetchColumn();
        
        // Bookings in last 24 hours
        $stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $recentBookingsCheck['recent_bookings_24h'] = (int)$stmt->fetchColumn();
        
        // Bookings in last 7 days
        $stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $recentBookingsCheck['recent_bookings_7d'] = (int)$stmt->fetchColumn();
        
        // Status distribution
        $stmt = $db->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status");
        $statusDist = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($statusDist as $s) {
            $recentBookingsCheck['status_distribution'][$s['status']] = (int)$s['count'];
        }
        
        // Last booking
        $stmt = $db->query("SELECT booking_code, status, created_at FROM bookings ORDER BY created_at DESC LIMIT 1");
        $recentBookingsCheck['last_booking'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Throwable $e) {
        $recentBookingsCheck['error'] = $e->getMessage();
    }
    
    $result->info['recent_bookings'] = $recentBookingsCheck;
    
    /**
     * SECTION 12: CHECK ERROR LOGS
     */
    logDiagnostic('Checking Error Logs...', 'INFO');
    
    $errorLogCheck = [
        'php_error_log' => null,
        'recent_errors' => [],
        'booking_related_errors' => []
    ];
    
    // Try to read PHP error log
    $errorLogPath = ini_get('error_log');
    if ($errorLogPath && file_exists($errorLogPath) && is_readable($errorLogPath)) {
        $logLines = file($errorLogPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $recentLines = array_slice($logLines, -100); // Last 100 lines
        
        $errorLogCheck['php_error_log'] = [
            'path' => $errorLogPath,
            'readable' => true,
            'total_lines' => count($logLines),
            'recent_lines_checked' => count($recentLines)
        ];
        
        foreach ($recentLines as $line) {
            if (stripos($line, 'error') !== false || stripos($line, 'fatal') !== false || stripos($line, 'warning') !== false) {
                $errorLogCheck['recent_errors'][] = $line;
            }
            if (stripos($line, 'booking') !== false) {
                $errorLogCheck['booking_related_errors'][] = $line;
            }
        }
    } else {
        $errorLogCheck['php_error_log'] = [
            'path' => $errorLogPath ?: 'Not configured',
            'readable' => false,
            'reason' => $errorLogPath ? 'File not found or not readable' : 'No error_log configured'
        ];
    }
    
    $result->info['error_logs'] = $errorLogCheck;
    
    /**
     * SECTION 13: CHECK OOP CLASSES LOADING
     */
    logDiagnostic('Checking OOP Class Loading...', 'INFO');
    
    $oopClassesCheck = [
        'classes' => [],
        'loadable' => [],
        'errors' => []
    ];
    
    $oopClasses = [
        'Aurora\\Core\\DTOs\\GuestDTO' => $baseDir . '/src/Core/DTOs/GuestDTO.php',
        'Aurora\\Core\\Repositories\\RoomRepository' => $baseDir . '/src/Core/Repositories/RoomRepository.php',
        'Aurora\\Core\\Repositories\\BookingRepository' => $baseDir . '/src/Core/Repositories/BookingRepository.php',
        'Aurora\\Core\\Services\\PricingService' => $baseDir . '/src/Core/Services/PricingService.php',
        'Aurora\\Core\\Services\\BookingService' => $baseDir . '/src/Core/Services/BookingService.php'
    ];
    
    foreach ($oopClasses as $className => $filePath) {
        $classInfo = [
            'class' => $className,
            'file' => $filePath,
            'file_exists' => file_exists($filePath),
            'class_loaded' => class_exists($className, false),
            'can_load' => false,
            'error' => null
        ];
        
        if (!$classInfo['class_loaded'] && $classInfo['file_exists']) {
            try {
                require_once $filePath;
                $classInfo['class_loaded'] = class_exists($className, false);
                $classInfo['can_load'] = $classInfo['class_loaded'];
            } catch (Throwable $e) {
                $classInfo['error'] = $e->getMessage();
                $oopClassesCheck['errors'][] = [
                    'class' => $className,
                    'error' => $e->getMessage()
                ];
            }
        } else {
            $classInfo['can_load'] = $classInfo['class_loaded'];
        }
        
        $oopClassesCheck['classes'][] = $classInfo;
        
        if ($classInfo['can_load']) {
            $oopClassesCheck['loadable'][] = $className;
        }
    }
    
    $allClassesLoadable = empty($oopClassesCheck['errors']);
    
    $result->info['oop_classes'] = $oopClassesCheck;
    
    $result->addCheck(
        'OOP Classes Loading',
        $allClassesLoadable,
        $allClassesLoadable 
            ? 'All OOP classes can be loaded successfully' 
            : 'Some OOP classes failed to load',
        ['errors' => $oopClassesCheck['errors']]
    );
    
} catch (Throwable $e) {
    $result->success = false;
    $result->addCheck(
        'Critical Error',
        false,
        'Critical error during diagnostics: ' . $e->getMessage(),
        [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    );
}

/**
 * FINALIZE RESULTS
 */

// Calculate total duration
$result->duration_ms = round((microtime(true) - $startTime) * 1000, 2);

// Add summary
$result->info['summary'] = $result->getSummary();

// Get output buffer content (if any errors were echoed)
$bufferContent = ob_get_clean();

// Prepare final response
$response = [
    'success' => $result->success,
    'timestamp' => $result->timestamp,
    'duration_ms' => $result->duration_ms,
    'summary' => $result->getSummary(),
    'checks' => $result->checks,
    'errors' => $result->errors,
    'warnings' => $result->warnings,
    'info' => $result->info,
    'database' => $result->database,
    'tables' => $result->tables,
    'columns' => $result->columns,
    'sample_booking_test' => $result->sample_booking_test,
    'php_environment' => $result->php_environment,
    'session_data' => $result->session_data,
    'file_checks' => $result->file_checks,
    'permissions' => $result->permissions,
    'buffer_output' => $bufferContent
];

// Log summary
logDiagnostic('Diagnostic completed in ' . $result->duration_ms . 'ms - Success: ' . ($result->success ? 'YES' : 'NO'), 'INFO');
logDiagnostic('Checks: ' . $result->getSummary()['passed'] . '/' . $result->getSummary()['total_checks'] . ' passed', 'INFO');

// Output JSON response
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
