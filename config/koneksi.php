<?php
// Konfigurasi database + konstanta aplikasi LaporKampus
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'laporkampus');
define('DB_USER', 'root');
define('DB_PASS', '');

define('BASE', '/laporkampus');   // path web aplikasi di dalam htdocs

// Produksi: jangan pernah tampilkan error PHP ke pengunjung
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('Asia/Jakarta');
define('APP_NAME', 'LaporKampus');
define('APP_SUB', 'Universitas Jenderal Soedirman');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    error_log('[LaporKampus] koneksi DB gagal: ' . $e->getMessage());
    exit('Layanan sedang tidak tersedia. Silakan coba beberapa saat lagi.');
}
