<?php
// FILE: diagnosa_db.php
// Untuk mendiagnosis masalah koneksi atau query dasar ke tabel 'students'

// Aktifkan laporan error PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sertakan file konfigurasi database
if (file_exists('config.php')) {
    include_once 'config.php';
} else {
    die("<h3>Error Diagnosa: File konfigurasi database (config.php) tidak ditemukan.</h3>");
}

// Cek koneksi database
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    die("<h3>Error Diagnosa: Koneksi database gagal.</h3><p>Detail Error: " . htmlspecialchars($conn->connect_error) . "</p>");
}

echo "<h3>Diagnosa Database: Berhasil Terhubung!</h3>";

// Coba lakukan query sederhana ke tabel 'students'
// PASTIKAN nama tabel 'students' benar.
// Query ini hanya mencoba mengambil 1 baris untuk cek dasar.
$test_query = "SELECT id, name, email, phone FROM students LIMIT 1";
$result = mysqli_query($conn, $test_query);

if ($result === false) {
    echo "<h3>Error Diagnosa: Query ke tabel 'students' gagal.</h3>";
    echo "<p>Detail Error: " . htmlspecialchars(mysqli_error($conn)) . "</p>";
    echo "<p>Query yang Dicoba: <code>" . htmlspecialchars($test_query) . "</code></p>";
} else {
    $num_rows = mysqli_num_rows($result);
    echo "<h3>Diagnosa Database: Query ke tabel 'students' Berhasil!</h3>";
    echo "<p>Jumlah baris ditemukan (LIMIT 1): " . $num_rows . "</p>";
    if ($num_rows > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "<p>Contoh data dari baris pertama: <code>" . htmlspecialchars(json_encode($row)) . "</code></p>";
    }
    mysqli_free_result($result);
}

// Tutup koneksi database
mysqli_close($conn);

echo "<p>Diagnosa selesai.</p>";

?>