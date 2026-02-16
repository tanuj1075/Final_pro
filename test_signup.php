<?php
/**
 * Test signup functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_helper.php';

echo "<h2>Signup Test</h2>\n";

// Test with sample data
$test_username = "testuser" . rand(100, 999);
$test_email = "test" . rand(100, 999) . "@example.com";
$test_password = "testpass123";

echo "<p><strong>Testing with:</strong></p>\n";
echo "<ul>\n";
echo "<li>Username: $test_username</li>\n";
echo "<li>Email: $test_email</li>\n";
echo "<li>Password: $test_password</li>\n";
echo "</ul>\n";

try {
    $db = new DatabaseHelper();
    echo "<p style='color:green;'>✓ DatabaseHelper instantiated successfully</p>\n";
    
    $result = $db->registerUser($test_username, $test_email, $test_password);
    
    if ($result === true) {
        echo "<p style='color:green;'><strong>✓ User registered successfully!</strong></p>\n";
        
        // Verify the user was added
        $pdo = new PDO('sqlite:' . __DIR__ . '/anime_admin_project/db.sqlite3');
        $stmt = $pdo->prepare("SELECT * FROM admin_panel_siteuser WHERE username = :username");
        $stmt->execute(['username' => $test_username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p style='color:green;'>✓ User found in database!</p>\n";
            echo "<pre>";
            print_r($user);
            echo "</pre>";
        } else {
            echo "<p style='color:red;'>✗ User not found in database!</p>\n";
        }
    } else {
        echo "<p style='color:red;'><strong>✗ Registration failed:</strong> " . htmlspecialchars($result) . "</p>\n";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "<p style='color:red;'><strong>✗ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>
