<?php
/**
 * Test Authentication System
 */

require_once '../config/database.php';

echo "<h2>Testing Authentication System</h2>";

// Test connection
$result = checkDBConnection(true);

if ($result['success']) {
    echo "<h3>✅ Database connected!</h3>";
    
    $db = getDB();
    
    // Create test user
    echo "<h3>Creating test user...</h3>";
    
    $test_email = 'test@aurorahotel.com';
    $test_password = 'test123';
    
    try {
        // Check if test user exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$test_email]);
        
        if ($stmt->fetch()) {
            echo "⏭️ Test user already exists<br>";
        } else {
            // Create test user
            $username = 'testuser_' . time();
            $password_hash = password_hash($test_password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users (username, email, password_hash, full_name, phone, role, status)
                VALUES (?, ?, ?, 'Test User', '0912345678', 'customer', 'active')
            ");
            $stmt->execute([$username, $test_email, $password_hash]);
            
            echo "✅ Test user created successfully!<br>";
            echo "Email: {$test_email}<br>";
            echo "Password: {$test_password}<br>";
        }
        
        // Create admin user
        echo "<h3>Creating admin user...</h3>";
        
        $admin_email = 'admin@aurorahotel.com';
        $admin_password = 'admin123';
        
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$admin_email]);
        
        if ($stmt->fetch()) {
            echo "⏭️ Admin user already exists<br>";
        } else {
            $username = 'admin_' . time();
            $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users (username, email, password_hash, full_name, phone, role, status)
                VALUES (?, ?, ?, 'Administrator', '0987654321', 'admin', 'active')
            ");
            $stmt->execute([$username, $admin_email, $password_hash]);
            
            echo "✅ Admin user created successfully!<br>";
            echo "Email: {$admin_email}<br>";
            echo "Password: {$admin_password}<br>";
        }
        
        echo "<h3>✅ Setup completed!</h3>";
        echo "<div style='margin-top: 20px;'>";
        echo "<h4>Test Accounts:</h4>";
        echo "<p><strong>Customer Account:</strong><br>";
        echo "Email: {$test_email}<br>";
        echo "Password: {$test_password}</p>";
        echo "<p><strong>Admin Account:</strong><br>";
        echo "Email: {$admin_email}<br>";
        echo "Password: {$admin_password}</p>";
        echo "<p><a href='./login.php' style='color: #d4af37; font-weight: bold;'>Go to Login Page</a></p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
    
} else {
    echo "<h3>❌ Database connection failed!</h3>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background: #f5f5f5;
}
h2, h3 {
    color: #333;
}
</style>
