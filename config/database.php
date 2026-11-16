<?php
declare(strict_types=1);

// KONFIGURASI DATABASE — IKP KOMINFO SUMUT
// Sesuaikan nilai di bawah dengan kredensial hosting Anda

$host   = 'localhost';
$dbname = 'db_peminjaman_multimedia3';
$dbuser = 'root';
$dbpass = 'Raihan110123041';
$dbport = '3306';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$dbport;dbname=$dbname;charset=utf8mb4",
        $dbuser,
        $dbpass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi dan pastikan MySQL aktif.');
}
