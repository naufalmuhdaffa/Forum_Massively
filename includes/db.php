<?php
// includes/db.php
// ---------------------------------
// Koneksi PDO ke database `forum_massively`

$env = parse_ini_file('.env');

if (!$env || !isset($env['KEY_DB'])) {
    die("Gagal membaca file .env atau KEY_DB tidak ditemukan.");
}

$key = $env['KEY_DB'];

$DB_HOST = 'localhost';
$DB_NAME = 'hertahub';
$DB_USER = 'nada';
$DB_PASS = $key;

try {
    $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Bila koneksi gagal, tampilkan pesan
    exit("Database connection failed: " . $e->getMessage());
}
