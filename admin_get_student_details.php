<?php
// FILE: admin_get_student_details.php

// Selalu mulai dengan pengaturan error dan sesi
ini_set('display_errors', 1); // Aktifkan untuk development, matikan (0) di produksi
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set header output sebagai JSON
header('Content-Type: application/json; charset=utf-8');

// Keamanan dasar: Hanya admin yang boleh akse
if (!isset($_SESSION['admin'])) { // Sesuaikan dengan variabel session login admin Anda
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Anda harus login sebagai admin.']);
    exit;
}

// Include config dan cek koneksi
if (file_exists('config.php')) {
    include_once 'config.php';
} else {
    error_log("CRITICAL: config.php tidak ditemukan di admin_get_student_details.php");
    echo json_encode(['success' => false, 'message' => 'Error konfigurasi server (config).']);
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $ajax_db_err = isset($conn) && $conn instanceof mysqli ? $conn->connect_error : 'Objek koneksi DB tidak terdefinisi.';
    error_log("CRITICAL: Koneksi DB gagal di admin_get_student_details.php: " . $ajax_db_err);
    echo json_encode(['success' => false, 'message' => 'Error koneksi database.']);
    exit;
}

$response = ['success' => false, 'message' => 'Data pendaftar tidak ditemukan atau ID tidak valid.'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $student_id = (int)$_GET['id'];

    // Mengambil semua kolom dari tabel students. Sesuaikan jika perlu.
    $query = "SELECT * FROM students WHERE id = ?"; 
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($student_data = mysqli_fetch_assoc($result)) {
                // Membersihkan output untuk keamanan sebelum dikirim sebagai JSON
                foreach ($student_data as $key => $value) {
                    $student_data[$key] = ($value !== null) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : null;
                }
                $response = ['success' => true, 'data' => $student_data];
            } else {
                $response['message'] = "Tidak ada data pendaftar ditemukan untuk ID: " . $student_id;
            }
            mysqli_free_result($result);
        } else {
            $response['message'] = "Gagal menjalankan query detail pendaftar: " . htmlspecialchars(mysqli_stmt_error($stmt));
            error_log("Execute failed for get_student_details ID {$student_id}: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = "Gagal menyiapkan query detail pendaftar: " . htmlspecialchars(mysqli_error($conn));
        error_log("Prepare failed for get_student_details: " . mysqli_error($conn));
    }
} else {
    $response['message'] = "ID pendaftar tidak valid atau tidak disediakan.";
}

mysqli_close($conn);
echo json_encode($response);
exit;
?>