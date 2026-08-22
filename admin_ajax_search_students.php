<?php
// FILE: admin_ajax_search_students.php

// Selalu mulai dengan pengaturan error dan sesi
ini_set('display_errors', 1); // Aktifkan untuk development, bisa dimatikan (0) di produksi
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set header output sebagai HTML karena kita akan mengirimkan baris tabel
header('Content-Type: text/html; charset=utf-8');

// Keamanan dasar: Hanya admin yang boleh akses
if (!isset($_SESSION['admin'])) { // Sesuaikan dengan variabel session login admin Anda
    http_response_code(403); // Forbidden
    echo '<tr><td colspan="7" class="text-center text-danger py-3">AKSES DITOLAK: Anda harus login sebagai admin.</td></tr>';
    exit;
}

// Include config dan cek koneksi
if (file_exists('config.php')) {
    include_once 'config.php';
} else {
    http_response_code(500); // Internal Server Error
    error_log("CRITICAL: config.php tidak ditemukan di admin_ajax_search_students.php");
    echo '<tr><td colspan="7" class="text-center text-danger py-3">ERROR SERVER: File konfigurasi penting tidak ditemukan.</td></tr>';
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    http_response_code(500);
    $ajax_db_err = isset($conn) && $conn instanceof mysqli ? $conn->connect_error : 'Objek koneksi DB tidak terdefinisi.';
    error_log("CRITICAL: Koneksi DB gagal di admin_ajax_search_students.php: " . $ajax_db_err);
    echo '<tr><td colspan="7" class="text-center text-danger py-3">ERROR SERVER: Koneksi database gagal.</td></tr>';
    exit;
}

// Ambil parameter pencarian dan filter dari GET request
$search_term = $_GET['search_term'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

// Bangun klausa WHERE untuk SQL menggunakan prepared statements
$where_clauses = [];
$params = []; // Untuk menampung parameter yang akan di-bind
$types = "";  // Untuk menampung tipe data parameter

// Filter berdasarkan status jika ada
if (!empty($filter_status)) {
    $where_clauses[] = "status = ?";
    $params[] = $filter_status;
    $types .= "s"; // 's' untuk string
}

// Filter berdasarkan search term jika ada
if (!empty($search_term)) {
    $search_term_like = "%" . $search_term . "%";
    // Kolom yang akan dicari: id, name, phone, father_name
    // Sesuaikan dengan kolom yang ingin Anda sertakan dalam pencarian
    $search_fields_clause = "(students.id LIKE ? OR students.name LIKE ? OR students.phone LIKE ? OR students.father_name LIKE ?)";
    $where_clauses[] = $search_fields_clause;
    
    // Tambahkan parameter untuk setiap placeholder ? di $search_fields_clause
    $params[] = $search_term_like; // untuk students.id LIKE ?
    $params[] = $search_term_like; // untuk students.name LIKE ?
    $params[] = $search_term_like; // untuk students.phone LIKE ?
    $params[] = $search_term_like; // untuk students.father_name LIKE ?
    $types .= "ssss"; // 4 string
}

$sql_where = "";
if (!empty($where_clauses)) {
    $sql_where = "WHERE " . implode(" AND ", $where_clauses);
}

// Query utama untuk mengambil data siswa
// Kolom yang diambil (id, name, phone, created_at, status, father_name) harus sesuai dengan 
// yang ingin Anda tampilkan di tabel pada beranda_admin.php
$query_students_ajax = "SELECT id, name, phone, created_at, status, father_name 
                        FROM students 
                        $sql_where 
                        ORDER BY created_at DESC 
                        LIMIT 10"; // Batasi hasil untuk dashboard

$stmt = mysqli_prepare($conn, $query_students_ajax);

if ($stmt === false) {
    error_log("AJAX Gagal prepare statement: " . mysqli_error($conn) . " | Query: " . $query_students_ajax);
    echo '<tr><td colspan="7" class="text-center text-danger py-3">Error saat menyiapkan pencarian: ' . htmlspecialchars(mysqli_error($conn)) . '</td></tr>';
    mysqli_close($conn);
    exit;
}

// Bind parameter jika ada (jika $types dan $params tidak kosong)
if (!empty($types) && !empty($params)) {
    // '...' adalah spread operator, membutuhkan PHP 5.6+
    // Jika versi PHP Anda lebih lama, Anda perlu menggunakan call_user_func_array
    if (version_compare(PHP_VERSION, '5.6.0', '>=')) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    } else {
        // Fallback untuk PHP < 5.6 (meskipun jarang ditemui sekarang)
        $bind_params_ref = [];
        $bind_params_ref[] = &$types; // Referensi ke tipe
        for ($i = 0; $i < count($params); $i++) {
            $bind_params_ref[] = &$params[$i]; // Referensi ke setiap parameter
        }
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $bind_params_ref));
    }
}

mysqli_stmt_execute($stmt);
$result_students_ajax = mysqli_stmt_get_result($stmt);

if ($result_students_ajax === false) {
    error_log("AJAX Gagal execute/get_result: " . mysqli_stmt_error($stmt) . " | Query: " . $query_students_ajax);
    echo '<tr><td colspan="7" class="text-center text-danger py-3">Error saat menjalankan pencarian: ' . htmlspecialchars(mysqli_stmt_error($stmt)) . '</td></tr>';
} else {
    if (mysqli_num_rows($result_students_ajax) > 0) {
        $no_pendaftar_ajax = 1;
        while ($row_pendaftar = mysqli_fetch_assoc($result_students_ajax)) {
            $status_badge_class = 'secondary'; // Default badge
            switch (strtolower($row_pendaftar['status'] ?? '')) {
                case 'verified': $status_badge_class = 'success'; break;
                case 'pending': $status_badge_class = 'warning text-dark'; break;
                case 'incomplete': $status_badge_class = 'danger'; break;
                case 'rejected': $status_badge_class = 'dark'; break;
            }
            $studentId_ajax = htmlspecialchars($row_pendaftar['id'] ?? '');
            $hapusModalTarget_ajax = "#hapusPendaftarModal" . $studentId_ajax; // Sesuaikan ID modal hapus Anda

            // Mencetak baris HTML tabel (`<tr>`)
            echo "<tr>";
            echo "<td class='no-column'>{$no_pendaftar_ajax}</td>";
            echo "<td>" . htmlspecialchars($row_pendaftar['name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row_pendaftar['phone'] ?? 'N/A') . "</td>";
            echo "<td>" . (!empty($row_pendaftar['created_at']) ? htmlspecialchars(date('d M Y H:i', strtotime($row_pendaftar['created_at']))) . ' WIB' : 'N/A') . "</td>";
            echo "<td><span class='badge bg-{$status_badge_class}'>" . htmlspecialchars(ucfirst($row_pendaftar['status'] ?? 'N/A')) . "</span></td>";
            echo "<td>" . htmlspecialchars($row_pendaftar['father_name'] ?? 'N/A') . "</td>";
            echo "<td class='action-buttons aksi-column'>";
            echo "<button type='button' class='btn btn-info btn-sm view-detail-btn' data-bs-toggle='modal' data-bs-target='#studentDetailModal' data-student-id='" . $studentId_ajax . "' title='Lihat Detail'><i class='bi bi-eye-fill'></i></button> ";
            echo "<a href='admin_verify_student.php?id=" . $studentId_ajax . "' class='btn btn-warning btn-sm' title='Edit/Verifikasi Data'><i class='bi bi-pencil-fill'></i></a> "; // Ganti admin_verify_student.php jika nama file beda
            echo "<button type='button' class='btn btn-danger btn-sm' data-bs-toggle='modal' data-bs-target='" . htmlspecialchars($hapusModalTarget_ajax) . "' title='Hapus Data'><i class='bi bi-trash-fill'></i></button>";
            echo "</td>";
            echo "</tr>";
            $no_pendaftar_ajax++;
        }
    } else {
        echo "<tr><td colspan='7' class='text-center fst-italic py-3'>Tidak ada data pendaftar";
        if (!empty($filter_status) || !empty($search_term)) { echo " yang cocok dengan filter/pencarian Anda."; } else { echo "."; }
        echo "</td></tr>";
    }
    mysqli_free_result($result_students_ajax);
}
mysqli_stmt_close($stmt);
mysqli_close($conn); // Tutup koneksi database
?>