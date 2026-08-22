<?php
// FILE: get_student_details.php

// Pastikan script ini hanya bisa diakses via AJAX atau dari admin
// Anda bisa menambahkan cek session admin di sini juga untuk keamanan ekstra
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah session admin ada
if (!isset($_SESSION['admin'])) {
    // Kirim respons error JSON jika tidak login
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login sebagai admin.']));
}

// Set header untuk respons JSON
header('Content-Type: application/json; charset=utf-8');

// Include file konfigurasi database
if (file_exists('config.php')) {
    include 'config.php';
} else {
    // Jika file config tidak ditemukan, kirim respons error
    exit(json_encode(['success' => false, 'message' => 'File konfigurasi database tidak ditemukan.']));
}

// Pastikan koneksi database berhasil
if (!$conn) {
     exit(json_encode(['success' => false, 'message' => 'Koneksi database gagal.']));
}

// Ambil ID pendaftar dari parameter GET
$student_id = $_GET['id'] ?? null; // Gunakan null coalescing operator untuk PHP 7+

// Validasi ID
if ($student_id === null || !is_numeric($student_id)) {
    mysqli_close($conn); // Tutup koneksi sebelum keluar
    exit(json_encode(['success' => false, 'message' => 'ID pendaftar tidak valid.']));
}

// Konversi ID ke integer untuk memastikan (tidak mutlak perlu jika pakai prepared statement 'i', tapi praktik yang baik)
$student_id = (int)$student_id;

$response = ['success' => false, 'message' => 'Data pendaftar tidak ditemukan atau terjadi kesalahan.'];

// Query database menggunakan Prepared Statement
// Mengambil SEMUA kolom karena di index_admin.php JS modal mengharapkan banyak kolom
$query = "SELECT * FROM students WHERE id = ?";

// Persiapkan statement
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    // Bind parameter 'i' untuk integer
    mysqli_stmt_bind_param($stmt, 'i', $student_id);

    // Eksekusi statement
    mysqli_stmt_execute($stmt);

    // Ambil hasil query
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        // Cek apakah ada baris yang ditemukan
        if (mysqli_num_rows($result) > 0) {
            // Ambil data sebagai associative array
            $student_data = mysqli_fetch_assoc($result);
            // Data ditemukan, set respons sukses
            $response = ['success' => true, 'data' => $student_data];
            mysqli_free_result($result); // Bebaskan memori hasil query
        } else {
            // Tidak ada pendaftar dengan ID tersebut
            $response['message'] = 'Pendaftar dengan ID tersebut tidak ditemukan.';
        }
    } else {
        // Error saat menjalankan query
        error_log("Failed to get result from statement: " . mysqli_stmt_error($stmt)); // Log error
        $response['message'] = 'Terjadi kesalahan saat mengambil data dari database.';
    }

    // Tutup statement
    mysqli_stmt_close($stmt);

} else {
    // Error saat mempersiapkan statement
    error_log("Failed to prepare statement: " . mysqli_error($conn)); // Log error
    $response['message'] = 'Terjadi kesalahan internal saat mempersiapkan data.';
}

// Tutup koneksi database
mysqli_close($conn);

// Kirim respons dalam format JSON
echo json_encode($response);

?>