<?php
// db.php
$host = 'localhost';
$dbname = 'gweens_hr_db'; // The name of the database we just created
$username = 'root'; // Change this if your local server uses a different username (e.g., in XAMPP it's usually 'root')
$password = ''; // Change this if you have a password set

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Set PDO to throw exceptions on errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // If connection fails, output error and kill the script
    die(json_encode([
        "status" => "error", 
        "message" => "Database connection failed: " . $e->getMessage()
    ]));
}
?>