<?php
// File: config.php

// 1. Pengaturan Error Reporting (opsional, baik untuk development)
// Matikan ini di server produksi jika tidak ingin menampilkan error ke pengguna
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// 2. Definisikan Konstanta untuk Detail Database
// Menggunakan define() adalah praktik yang baik untuk menjaga agar nilai ini tidak mudah berubah.
define('DB_SERVER', 'localhost');         // Biasanya 'localhost' jika database di server yang sama
define('DB_USERNAME', 'smap9589_sma_ibnu_aqil'); // Ganti dengan username database Anda
define('DB_PASSWORD', 'ibnuaqil');         // Ganti dengan password database Anda
define('DB_NAME', 'smap9589_sma_ibnu_aqil');     // Ganti dengan nama database Anda
define('DB_CHARSET', 'utf8mb4');           // Direkomendasikan

// 3. Membuat Koneksi MySQLi
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// 4. Memeriksa Koneksi MySQLi
if ($conn === false) { // Bisa juga dicek dengan: if (!$conn) atau if (mysqli_connect_errno())
    // Catat error ke log server (lebih aman daripada menampilkan detail ke pengguna)
    error_log("Koneksi Database MySQLi Gagal: " . mysqli_connect_error());
    // Tampilkan pesan umum ke pengguna
    die("Maaf, sistem sedang mengalami gangguan teknis. Silakan coba beberapa saat lagi. [Error Code: CFG-MYSQLI]");
}

// 5. Mengatur Karakter Set untuk Koneksi MySQLi (PENTING!)
if (!mysqli_set_charset($conn, DB_CHARSET)) {
    error_log("Error saat mengatur karakter set MySQLi " . DB_CHARSET . ": " . mysqli_error($conn));
    // Anda bisa memilih untuk die() di sini juga jika karakter set sangat krusial
    // die("Terjadi kesalahan konfigurasi karakter set.");
}

// Opsional: Jika Anda juga tetap ingin menggunakan PDO di proyek Anda untuk bagian lain
// Anda bisa menyertakan bagian koneksi PDO di sini juga, mirip dengan yang sudah Anda miliki.
/*
$dsn_pdo = "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options_pdo = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn_pdo, DB_USERNAME, DB_PASSWORD, $options_pdo);
} catch (\PDOException $e) {
    error_log("Koneksi Database PDO Gagal: " . $e->getMessage());
    die("Maaf, sistem sedang mengalami gangguan teknis. Silakan coba beberapa saat lagi. [Error Code: CFG-PDO]");
}
*/

// Pesan ini bisa dihapus setelah Anda yakin koneksi berfungsi
// echo "Config.php berhasil di-load dan koneksi MySQLi ($conn) siap digunakan.<br>";

?>