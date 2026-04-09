<?php
/**
 * Database Connection
 * Loads credentials from .env for security and portability
 */

// Simple .env Loader
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $_ENV[trim($parts[0])] = trim($parts[1], ' "');
        }
    }
}

$host     = $_ENV['DB_HOST'] ?? 'localhost';
$dbname   = $_ENV['DB_NAME'] ?? 'belajarphpkasir';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

$pdo = null;
$pdo_error = null;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if ($e->getCode() == 1049) { // Unknown database
        $pdo_error = "Database '$dbname' tidak ditemukan. Silakan buat database terlebih dahulu di Panel Hosting/Laragon Anda.";
    } else {
        $pdo_error = "Database Connection failed: " . $e->getMessage();
    }
}
?>