<?php
$host = 'localhost';
$db   = 'dnd_project';
$user = 'root';
$pass = ''; // XAMPP default
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;charset=$charset"; // Connect without DB first to create it if needed
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Ensure DB exists and select it
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db`");
    $pdo->exec("USE `$db`");
    
} catch (\PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
    exit;
}
?>
