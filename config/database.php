<?php
declare(strict_types=1);

// ============================================================
// KONFIGURASI DATABASE
// Ganti nilai di bawah sesuai hosting yang digunakan
// ============================================================

// Jika menggunakan environment variable (Railway, Render, dll):
// $host   = getenv('DB_HOST')   ?: 'localhost';
// $dbname = getenv('DB_NAME')   ?: 'db_peminjaman_multimedia3';
// $dbuser = getenv('DB_USER')   ?: 'root';
// $dbpass = getenv('DB_PASS')   ?: '';
// $dbport = getenv('DB_PORT')   ?: '3306';

// Untuk InfinityFree / 000webhost — isi manual di sini:
$host   = 'localhost';
$dbname = 'db_peminjaman_multimedia3';  // <-- ganti dengan nama DB dari panel hosting
$dbuser = 'root';                        // <-- ganti dengan user DB dari panel hosting
$dbpass = 'Raihan110123041';             // <-- ganti dengan password DB dari panel hosting
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
