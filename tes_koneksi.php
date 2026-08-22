<?php
ini_set('display_errors', 1); // Tampilkan error untuk tes ini
error_reporting(E_ALL);

echo "Mencoba meng-include config.php...<br>";

if (file_exists('config.php')) {
    require_once 'config.php'; // Gunakan require_once agar skrip berhenti jika file tidak ada
    echo "File config.php berhasil di-include.<br>";
} else {
    die("ERROR FATAL: File config.php tidak ditemukan!");
}

// Setelah config.php di-include, variabel $conn seharusnya sudah ada
if (isset($conn) && $conn instanceof mysqli) {
    if ($conn->connect_error) {
        // Pesan ini seharusnya sudah ditangani oleh die() di dalam config.php
        // Tapi kita cek lagi untuk memastikan.
        echo "Koneksi GAGAL (dicek dari tes_koneksi.php). Error: " . htmlspecialchars($conn->connect_error) . "<br>";
    } else {
        echo "<strong style='color:green;'>SELAMAT! Koneksi ke database BERHASIL melalui config.php!</strong><br>";
        echo "Host info: " . htmlspecialchars($conn->host_info) . "<br>";
        // Anda bisa mencoba query sederhana di sini jika mau
        // $resultTest = $conn->query("SELECT DATABASE()");
        // $dbNameTest = $resultTest->fetch_row()[0];
        // echo "Nama database yang aktif: " . htmlspecialchars($dbNameTest) . "<br>";
        // $resultTest->free();
        mysqli_close($conn);
    }
} else {
    echo "<strong style='color:red;'>ERROR: Variabel \$conn tidak terdefinisi dengan benar setelah include config.php.</strong><br>";
    echo "Pastikan \$conn dibuat dengan benar di config.php dan tidak ada error sebelumnya.<br>";
    if (isset($conn)) {
       echo "Tipe variabel \$conn saat ini: " . gettype($conn);
    } else {
       echo "Variabel \$conn bahkan tidak ter-set.";
    }
}
?>