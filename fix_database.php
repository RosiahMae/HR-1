<?php
require_once 'db.php';

try {
    // Add the missing password_hash column
    $sql = "ALTER TABLE applicants ADD COLUMN password_hash VARCHAR(255) NULL AFTER status";
    $pdo->exec($sql);
    echo "<h1>Success!</h1><p>The 'password_hash' column has been added to your database.</p>";
    echo "<p>You can now delete this file and try logging in/registering again.</p>";
    
} catch (PDOException $e) {
    echo "<h1>Database Status</h1>";
    echo "<p>Message: " . $e->getMessage() . "</p>";
}
?>