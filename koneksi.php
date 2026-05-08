<?php
// ============================================
// koneksi.php — Konfigurasi Koneksi Database
// ============================================

define('DB_HOST',   'localhost');
define('DB_USER',   'root');        // Ganti sesuai user MySQL Anda
define('DB_PASS',   '');            // Ganti sesuai password MySQL Anda
define('DB_NAME',   'db_mahasiswa');
define('DB_CHARSET','utf8mb4');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        'status'  => 'error',
        'message' => 'Koneksi database gagal: ' . $conn->connect_error,
    ]));
}

$conn->set_charset(DB_CHARSET);

// Konstanta upload
define('UPLOAD_DIR',      __DIR__ . '/uploads/');
define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024); // 2 MB
define('ALLOWED_TYPES',   ['image/jpeg', 'image/jpg', 'image/png']);
define('ALLOWED_EXTS',    ['jpg', 'jpeg', 'png']);
