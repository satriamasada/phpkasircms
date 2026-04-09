<?php
$host = 'db.fr-pari1.bengt.wasmernet.com';
$dbname = 'dbWzmHve8Ya73EDUJVHTkE7v';
$username = '548afaf97bd58000f6bb97eae248';
$password = '069d548a-faf9-7c80-8000-7df17bc9c9b3';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
