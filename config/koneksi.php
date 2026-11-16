<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Konfigurasi Koneksi Database MySQL dengan PDO
 * File: config/koneksi.php
 */

$host = 'localhost';
$port = '3306';
$dbname = 'db_supermarket';
$username = 'root';
$password = '';

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("
    <div style='font-family: sans-serif; padding: 20px; background: #fff3f3; color: #b71c1c; border: 1px solid #ffcdd2; border-radius: 8px; margin: 30px auto; max-width: 600px;'>
        <h3 style='margin-top: 0;'>Koneksi Database Gagal!</h3>
        <p>Pastikan MySQL server di Laragon sudah dalam keadaan <strong>Start</strong> dan database <code>{$dbname}</code> sudah diimpor.</p>
        <p><small>Pesan Error: " . htmlspecialchars($e->getMessage()) . "</small></p>
    </div>
    ");
}
