<?php
/**
 * Database connection test script
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>\n";

$db_path = __DIR__ . '/anime_admin_project/db.sqlite3';

echo "<p><strong>Database Path:</strong> $db_path</p>\n";
echo "<p><strong>File Exists:</strong> " . (file_exists($db_path) ? 'Yes' : 'No') . "</p>\n";

if (!file_exists($db_path)) {
    die("<p style='color:red;'>Database file not found!</p>");
}

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green;'><strong>✓ Database Connected Successfully!</strong></p>\n";
    
    // Check if table exists
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='admin_panel_siteuser'");
    $table = $result->fetch(PDO::FETCH_ASSOC);
    
    if ($table) {
        echo "<p style='color:green;'><strong>✓ Table 'admin_panel_siteuser' exists!</strong></p>\n";
        
        // Get table structure
        echo "<h3>Table Structure:</h3>\n";
        $pragma = $db->query("PRAGMA table_info(admin_panel_siteuser)");
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Column</th><th>Type</th><th>Not Null</th><th>Default</th><th>PK</th></tr>\n";
        while ($row = $pragma->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['type']) . "</td>";
            echo "<td>" . ($row['notnull'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . htmlspecialchars($row['dflt_value']) . "</td>";
            echo "<td>" . ($row['pk'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // Count users
        $count_result = $db->query("SELECT COUNT(*) as count FROM admin_panel_siteuser");
        $count = $count_result->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>Total Users:</strong> " . $count['count'] . "</p>\n";
        
        // Show users
        $users_result = $db->query("SELECT id, username, email, is_approved, is_active, created_at FROM admin_panel_siteuser ORDER BY id DESC LIMIT 10");
        $users = $users_result->fetchAll(PDO::FETCH_ASSOC);
        
        if ($users) {
            echo "<h3>Recent Users:</h3>\n";
            echo "<table border='1' cellpadding='5'>\n";
            echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Approved</th><th>Active</th><th>Created</th></tr>\n";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . ($user['is_approved'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . $user['created_at'] . "</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }
        
    } else {
        echo "<p style='color:red;'><strong>✗ Table 'admin_panel_siteuser' NOT found!</strong></p>\n";
        
        // List all tables
        echo "<h3>Available Tables:</h3>\n";
        $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        echo "<ul>\n";
        while ($table = $tables->fetch(PDO::FETCH_ASSOC)) {
            echo "<li>" . htmlspecialchars($table['name']) . "</li>\n";
        }
        echo "</ul>\n";
    }
    
} catch(PDOException $e) {
    echo "<p style='color:red;'><strong>✗ Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
