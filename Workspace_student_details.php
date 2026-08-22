<?php
// FILE: Workspace_student_details.php

// --- HAPUS ATAU KOMEN DUA BARIS INI ---
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ---------------------------------------

// ... sisa kode Anda ...
// FILE: beranda_admin.php (Dashboard Admin Utama - VERSI LENGKAP DISUSUN ULANG)

// 1. Mulai Sesi (WAJIB PALING ATAS)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Pengaturan Error Reporting untuk Development (AKTIFKAN UNTUK MELIHAT SEMUA ERROR)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 3. Cegah Cache
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 4. Sertakan File Konfigurasi Database
if (file_exists('config.php')) {
    include_once 'config.php'; // Variabel $conn akan dibuat di sini
} else {
    error_log("CRITICAL: config.php tidak ditemukan di beranda_admin.php");
    exit('File konfigurasi database (config.php) tidak ditemukan. Halaman tidak dapat dimuat. Harap hubungi administrator.');
}

// 5. Pastikan Koneksi $conn Berhasil
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $db_conn_error_msg = isset($conn) && $conn instanceof mysqli ? $conn->connect_error : 'Objek koneksi database tidak terdefinisi atau bukan instance mysqli.';
    error_log("CRITICAL: Koneksi database gagal di beranda_admin.php: " . $db_conn_error_msg);
    exit('Koneksi database gagal. Harap hubungi administrator. Detail: ' . htmlspecialchars($db_conn_error_msg));
}

// 6. Cek Login Admin
if (!isset($_SESSION['admin'])) { // Sesuaikan dengan nama variabel session admin Anda
    $_SESSION['error_message'] = "Anda harus login untuk mengakses halaman ini.";
    header("Location: login.php"); // Arahkan ke halaman login Anda
    exit;
}

// --- BAGIAN HANDLE AKSI (CONTOH: DOWNLOAD EXCEL) ---
// (Letakkan logika aksi seperti download SEBELUM pengambilan data utama untuk halaman)
if (isset($_GET['action']) && $_GET['action'] === 'download_excel') {
    // Menggunakan $conn yang sudah ada
    $where_clause_export = "WHERE 1=1";
    if (isset($_GET['filter_status']) && $_GET['filter_status'] != '') {
        $filter_status_safe = mysqli_real_escape_string($conn, $_GET['filter_status']);
        $where_clause_export .= " AND status = '$filter_status_safe'";
    }
    if (isset($_GET['search_term']) && $_GET['search_term'] != '') {
        $search_term_safe = mysqli_real_escape_string($conn, $_GET['search_term']);
        $where_clause_export .= " AND (name LIKE '%$search_term_safe%' OR email LIKE '%$search_term_safe%')";
    }

    $query_export_inline = "SELECT * FROM students $where_clause_export ORDER BY id ASC";
    $result_export_inline = mysqli_query($conn, $query_export_inline);

    if (!$result_export_inline) {
        $error_message_csv = mysqli_error($conn);
        error_log("Export Excel Gagal: Query error - " . $error_message_csv . " | Query: " . $query_export_inline);
        // Sebaiknya jangan exit begitu saja, beri tahu user atau redirect dengan pesan error
        $_SESSION['status_message'] = "Gagal membuat file Excel: Terjadi kesalahan query.";
        $_SESSION['alert_class'] = "alert-danger";
        header("Location: beranda_admin.php"); // Kembali ke dashboard
        exit;
    }

    $filename = "data_pendaftar_sma_ibnuaqil_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output_inline = fopen('php://output', 'w');

    // Pastikan fungsi-fungsi ini sudah didefinisikan atau di-include
    if (!function_exists('formatPhoneNumberForExcel')) {
        function formatPhoneNumberForExcel($phone)
        {
            /* ... logika Anda ... */
            return '="' . preg_replace('/[^\d]/', '', $phone ?? '') . '"';
        }
    }
    if (!function_exists('formatDateForExport')) {
        function formatDateForExport($date_string)
        {
            /* ... logika Anda ... */
            return (!empty($date_string) && $date_string != '0000-00-00') ? date('Y-m-d', strtotime($date_string)) : '';
        }
    }
    if (!function_exists('formatDateTimeForExport')) {
        function formatDateTimeForExport($datetime_string)
        {
            /* ... logika Anda ... */
            return (!empty($datetime_string) && $datetime_string != '0000-00-00 00:00:00') ? date('Y-m-d H:i:s', strtotime($datetime_string)) : '';
        }
    }

    $column_headers_inline = [
        'ID Pendaftar', 'Nama Lengkap', 'Email', /* ... sisa header Anda ... */
        'Path File KK Wali',
    ];
    fputcsv($output_inline, $column_headers_inline);

    if (mysqli_num_rows($result_export_inline) > 0) {
        while ($row_export = mysqli_fetch_assoc($result_export_inline)) {
            $csv_row_inline = [
                $row_export['id'] ?? '', $row_export['name'] ?? '', /* ... sisa data Anda ... */
                $row_export['kk_guardian'] ?? '',
            ];
            fputcsv($output_inline, $csv_row_inline);
        }
    }
    fclose($output_inline);
    mysqli_free_result($result_export_inline);
    exit; // PENTING: akhiri script setelah mengirim file
}
// --- AKHIR BAGIAN HANDLE AKSI ---


// Ambil pesan status dari session (jika ada dari aksi sebelumnya)
$status_message_admin = '';
$alert_class_admin = '';
if (isset($_SESSION['status_message']) && isset($_SESSION['alert_class'])) {
    $status_message_admin = htmlspecialchars($_SESSION['status_message']);
    $alert_class_admin = htmlspecialchars($_SESSION['alert_class']);
    unset($_SESSION['status_message']);
    unset($_SESSION['alert_class']);
}

// Ambil data admin (Nama dan Foto jika ada)
$admin_id = $_SESSION['admin_id'] ?? $_SESSION['admin'] ?? null; // Coba ambil admin_id atau admin (sesuaikan dengan key session Anda)
$admin_name = "Admin"; // Default
$admin_photo_url = 'user.png'; // Default

if ($admin_id) {
    // Ambil dari database jika perlu, atau jika sudah ada di session saat login, gunakan itu.
    // Contoh jika admin_name dan admin_photo_url disimpan di session saat login:
    if (isset($_SESSION['admin_name'])) {
        $admin_name = htmlspecialchars($_SESSION['admin_name']);
    }
    if (isset($_SESSION['admin_photo_url']) && file_exists($_SESSION['admin_photo_url'])) {
        $admin_photo_url = htmlspecialchars($_SESSION['admin_photo_url']);
    } elseif (isset($_SESSION['admin_photo_filename']) && file_exists('uploads/admin_photos/' . $_SESSION['admin_photo_filename'])) {
        $admin_photo_url = 'uploads/admin_photos/' . htmlspecialchars($_SESSION['admin_photo_filename']);
    }
    // Jika tidak ada di session, baru query ke DB (seperti kode Anda sebelumnya)
    // else { /* ... kode query Anda untuk mengambil nama/foto admin dari DB berdasarkan $admin_id ... */ }
}


// --- PENGHITUNGAN DATA UNTUK DASHBOARD CARDS ---
$counts = [
        'total_students' => 0, 'pending_students' => 0, 'verified_students' => 0,
        'incomplete_students' => 0, 'rejected_students' => 0, // Ini akan dijumlahkan untuk satu kartu
        'facilities' => 0, 'news' => 0, 'gallery' => 0,
];
// Key di HTML Anda: total, verified, pending, (incomplete+rejected), facilities, news, gallery

$queries_for_counts = [
        'total_students' => "SELECT COUNT(*) AS count_val FROM students",
        'pending_students' => "SELECT COUNT(*) AS count_val FROM students WHERE status = 'pending'",
        'verified_students' => "SELECT COUNT(*) AS count_val FROM students WHERE status = 'verified'",
        'incomplete_students' => "SELECT COUNT(*) AS count_val FROM students WHERE status = 'incomplete'",
        'rejected_students' => "SELECT COUNT(*) AS count_val FROM students WHERE status = 'rejected'",
        'facilities' => "SELECT COUNT(*) AS count_val FROM facilities WHERE is_published = 1",
        'news' => "SELECT COUNT(*) AS count_val FROM news", // Tambahkan "WHERE is_published = 1" jika ada di tabel news
        'gallery' => "SELECT COUNT(*) AS count_val FROM gallery", // Tambahkan "WHERE is_published = 1" jika ada di tabel gallery
];

foreach ($queries_for_counts as $key => $sql_count) {
    $result_count = mysqli_query($conn, $sql_count);
    if ($result_count) {
        $row_c = mysqli_fetch_assoc($result_count);
        $counts[$key] = $row_c['count_val'] ?? 0;
        mysqli_free_result($result_count);
    } else {
        error_log("Query count untuk '$key' gagal di beranda_admin.php: " . mysqli_error($conn) . " | Query: " . $sql_count);
        // Biarkan count 0 jika query gagal (misalnya tabel belum ada)
    }
}
// --- AKHIR PENGHITUNGAN DATA DASHBOARD CARDS ---


// Logika filter dan pencarian pendaftar (students) untuk tabel di bawah
$where_clause_display_students = "WHERE 1=1";
$filter_status_val = $_GET['filter_status'] ?? '';
$search_term_val = $_GET['search_term'] ?? '';

if (!empty($filter_status_val)) {
    $filter_status_safe = mysqli_real_escape_string($conn, $filter_status_val);
    $where_clause_display_students .= " AND status = '$filter_status_safe'";
}
if (!empty($search_term_val)) {
    $search_term_safe = mysqli_real_escape_string($conn, $search_term_val);
    $where_clause_display_students .= " AND (name LIKE '%$search_term_safe%' OR email LIKE '%$search_term_safe%')";
}

$students_list_for_dashboard = [];
$query_students_display = "SELECT id, name, phone, created_at, status, father_name FROM students $where_clause_display_students ORDER BY created_at DESC LIMIT 10";
$result_students_display_query = mysqli_query($conn, $query_students_display);
if (!$result_students_display_query) {
    error_log("Gagal query students untuk display di beranda_admin.php: " . mysqli_error($conn));
    // Anda bisa set pesan error di sini untuk ditampilkan di area tabel pendaftar
    $students_table_error = "Gagal memuat data pendaftar: " . htmlspecialchars(mysqli_error($conn));
} else {
    while ($row_student = mysqli_fetch_assoc($result_students_display_query)) {
        $students_list_for_dashboard[] = $row_student;
    }
    // Jangan free result di sini jika Anda menggunakan $result_students_display_query lagi di bawah untuk modal
}

// Persiapan parameter untuk link download Excel pendaftar
$excel_link_params_array = ['action' => 'download_excel'];
if (!empty($filter_status_val)) {
    $excel_link_params_array['filter_status'] = $filter_status_val;
}
if (!empty($search_term_val)) {
    $excel_link_params_array['search_term'] = $search_term_val;
}
$excel_link_params = http_build_query($excel_link_params_array);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda Admin - SMA Ibnu Aqil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/jpg" href="public/asset/logo.jpg">
    <style>
        /* --- SALIN SEMUA CSS ANDA YANG SUDAH ADA DARI KODE BERANDA_ADMIN.PHP ANDA KE SINI --- */
        /* Contoh beberapa style penting: */
        body {
            background-color: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .table-container {
            margin: 30px auto;
            max-width: 95%;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.07);
        }

        .table-title {
            margin-bottom: 20px;
            text-align: center;
            color: #007bff;
            font-weight: 600;
            font-size: 1.75rem;
        }

        .footer {
            text-align: center;
            padding: 20px;
            background-color: #343a40;
            color: white;
            margin-top: 40px;
        }

        .dashboard-options {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
            margin-bottom: 25px;
        }

        .dashboard-card {
            width: 220px;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            font-weight: bold;
            color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
            text-decoration: none;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            color: white;
        }

        /* Pastikan teks tetap putih saat hover */
        .dashboard-card h3 {
            margin-top: 8px;
            font-size: 2.2rem;
            line-height: 1;
        }

        /* Ukuran H3 diperbesar */
        .dashboard-card .card-text {
            font-size: 0.9rem;
            margin-bottom: 5px;
            opacity: 0.9;
        }

        .dashboard-card .icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: block;
        }

        /* Ikon lebih besar & block */

        .card-total-pendaftar {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
        }

        .card-terverifikasi {
            background: linear-gradient(135deg, #198754, #146c43);
        }

        .card-pending {
            background: linear-gradient(135deg, #fd7e14, #e85a00);
        }

        .card-ditolak-incomplete {
            background: linear-gradient(135deg, #dc3545, #b02a37);
        }

        .card-fasilitas {
            background: linear-gradient(135deg, #6f42c1, #59359a);
        }

        .card-berita {
            background: linear-gradient(135deg, #20c997, #15a37a);
        }

        .card-galeri {
            background: linear-gradient(135deg, #d63384, #a31f60);
        }

        .filter-search-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
            justify-content: flex-end;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .action-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }

        /* Tombol aksi lebih kecil */
        td.action-buttons {
            white-space: nowrap;
        }

        /* Agar tombol tidak wrap jika memungkinkan */
        th,
        td {
            min-width: 80px;
            vertical-align: middle;
            font-size: 0.875rem;
        }

        th.no-column,
        td.no-column {
            min-width: 40px;
            width: 40px;
            text-align: center;
        }

        th.aksi-column,
        td.aksi-column {
            min-width: 150px;
            text-align: center;
        }

        /* Disesuaikan agar 3 tombol muat */
        /* ... (CSS lain yang relevan dari kode Anda) ... */
    </style>
</head>

<body class="admin-authenticated">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="beranda_admin.php">
                <img src="<?php echo htmlspecialchars($admin_photo_url); ?>" alt="Foto Admin" width="30" height="30" class="d-inline-block align-text-top rounded-circle me-2">
                <?php echo $admin_name; ?> (Admin Panel)
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="beranda_admin.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_manage_facilities.php"><i class="bi bi-building-fill-gear me-1"></i> Fasilitas</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_manage_news.php"><i class="bi bi-newspaper me-1"></i> Berita</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_manage_gallery.php"><i class="bi bi-images me-1"></i> Galeri</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php" target="_blank"><i class="bi bi-eye-fill me-1"></i> Lihat Situs</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminProfileDropdownTop" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> Profil
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminProfileDropdownTop">
                            <li><a class="dropdown-item" href="admin_profile.php"><i class="bi bi-person-badge me-2"></i>Profil Saya</a></li>
                            <li><a class="dropdown-item" href="admin_settings.php"><i class="bi bi-gear-fill me-2"></i>Pengaturan Akun</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="logout.php" onclick="return confirm('Yakin ingin logout?')"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="h2 mb-4">Selamat Datang di Dashboard, <?php echo $admin_name; ?>!</h1>

        <?php if (!empty($status_message_admin)) : ?>
            <div class="alert <?php echo $alert_class_admin; ?> alert-dismissible fade show" role="alert">
                <?php echo $status_message_admin; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="dashboard-options">
            <a href="admin_manage_students.php?status_filter=all" class="dashboard-card card-total-pendaftar text-decoration-none">
                <i class="bi bi-people-fill icon"></i><span class="card-text">Total Pendaftar</span>
                <h3><?= $counts['total_students'] ?? 0; ?></h3>
            </a>
            <a href="admin_manage_students.php?status_filter=verified" class="dashboard-card card-terverifikasi text-decoration-none">
                <i class="bi bi-person-check-fill icon"></i><span class="card-text">Terverifikasi</span>
                <h3><?= $counts['verified_students'] ?? 0; ?></h3>
            </a>
            <a href="admin_manage_students.php?status_filter=pending" class="dashboard-card card-pending text-decoration-none">
                <i class="bi bi-hourglass-split icon"></i><span class="card-text">Pending</span>
                <h3><?= $counts['pending_students'] ?? 0; ?></h3>
            </a>
            <a href="admin_manage_students.php?status_filter=incomplete_or_rejected" class="dashboard-card card-ditolak-incomplete text-decoration-none">
                <i class="bi bi-person-x-fill icon"></i><span class="card-text">Ditolak/Belum Lengkap</span>
                <h3><?= ($counts['incomplete_students'] ?? 0) + ($counts['rejected_students'] ?? 0); ?></h3>
            </a>
            <a href="admin_manage_facilities.php" class="dashboard-card card-fasilitas text-decoration-none">
                <i class="bi bi-buildings-fill icon"></i><span class="card-text">Total Fasilitas</span>
                <h3><?= $counts['facilities'] ?? 0; ?></h3>
            </a>
            <a href="admin_manage_news.php" class="dashboard-card card-berita text-decoration-none">
                <i class="bi bi-journal-text icon"></i><span class="card-text">Total Berita</span>
                <h3><?= $counts['news'] ?? 0; ?></h3>
            </a>
            <a href="admin_manage_gallery.php" class="dashboard-card card-galeri text-decoration-none">
                <i class="bi bi-card-image icon"></i><span class="card-text">Total Galeri</span>
                <h3><?= $counts['gallery'] ?? 0; ?></h3>
            </a>
        </div>

        <div class="container mt-4 text-center bg-light py-3 rounded shadow-sm">
            <h3 class="mb-3 text-primary">Aksi Cepat Manajemen Konten</h3>
            <a href="admin_manage_facilities.php" class="btn btn-warning btn-lg me-sm-2 mb-2">
                <i class="bi bi-building"></i> Kelola Fasilitas
            </a>
            <a href="admin_manage_news.php" class="btn btn-info btn-lg me-sm-2 mb-2">
                <i class="bi bi-newspaper"></i> Kelola Berita
            </a>
            <a href="admin_manage_gallery.php" class="btn btn-success btn-lg mb-2">
                <i class="bi bi-images"></i> Kelola Galeri
            </a>
        </div>

        <div class="container mt-4 table-container" id="dataTableContainer">
            <h2 class="table-title">Pendaftar Terbaru <small class="text-muted fs-6">(Menampilkan maks. 10)</small></h2>
            <div class="filter-search-container card shadow-sm mb-3 p-3">
                <form method="get" action="beranda_admin.php#dataTableContainer" class="row g-2 w-100 align-items-center">
                    <div class="col-md">
                        <input type="text" class="form-control form-control-sm" name="search_term" placeholder="Cari Nama Pendaftar..." value="<?= htmlspecialchars($search_term_val); ?>">
                    </div>
                    <div class="col-md-auto">
                        <select class="form-select form-select-sm" name="filter_status">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php if ($filter_status_val == 'pending') {
                                                        echo 'selected';
                                                    } ?>>Pending</option>
                            <option value="verified" <?php if ($filter_status_val == 'verified') {
                                                        echo 'selected';
                                                    } ?>>Terverifikasi</option>
                            <option value="incomplete" <?php if ($filter_status_val == 'incomplete') {
                                                            echo 'selected';
                                                        } ?>>Belum Lengkap</option>
                            <option value="rejected" <?php if ($filter_status_val == 'rejected') {
                                                        echo 'selected';
                                                    } ?>>Ditolak</option>
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filter</button>
                    </div>
                    <div class="col-md-auto">
                        <a href="beranda_admin.php#dataTableContainer" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                    </div>
                </form>
            </div>

            <div class="mb-3 text-start">
                <a href="beranda_admin.php?<?php echo $excel_link_params; ?>" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel-fill"></i> Download Data Pendaftar (CSV)
                </a>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="no-column">No</th>
                                    <th>Nama</th>
                                    <th>Telepon</th>
                                    <th>Tgl Daftar</th>
                                    <th>Status</th>
                                    <th>Nama Ayah</th>
                                    <th class="aksi-column">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (isset($students_table_error)) : ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-danger py-3">
                                            <em><?php echo $students_table_error; ?></em>
                                        </td>
                                    </tr>
                                    <?php elseif (!empty($students_list_for_dashboard)) :
                                        $no_pendaftar_dashboard = 1;
                                        foreach ($students_list_for_dashboard as $row_pendaftar_dashboard) { // Menggunakan variabel yang benar
                                            $status_badge_class = 'secondary';
                                            switch (strtolower($row_pendaftar_dashboard['status'] ?? '')) {
                                                case 'verified':
                                                    $status_badge_class = 'success';
                                                    break;
                                                case 'pending':
                                                    $status_badge_class = 'warning text-dark';
                                                    break;
                                                case 'incomplete':
                                                    $status_badge_class = 'danger';
                                                    break;
                                                case 'rejected':
                                                    $status_badge_class = 'dark';
                                                    break;
                                            }
                                            $studentId_dashboard = htmlspecialchars($row_pendaftar_dashboard['id'] ?? '');
                                            $hapusModalTarget_dashboard = "#hapusPendaftarModal" . $studentId_dashboard; // ID Modal Hapus disesuaikan

                                            echo "<tr>
                                                    <td class='no-column'>{$no_pendaftar_dashboard}</td>
                                                    <td>" . htmlspecialchars($row_pendaftar_dashboard['name'] ?? 'N/A') . "</td>
                                                    <td>" . htmlspecialchars($row_pendaftar_dashboard['phone'] ?? 'N/A') . "</td>
                                                    <td>" . (!empty($row_pendaftar_dashboard['created_at']) ? htmlspecialchars(date('d M Y H:i', strtotime($row_pendaftar_dashboard['created_at']))) . ' WIB' : 'N/A') . "</td>
                                                    <td><span class='badge bg-{$status_badge_class}'>" . htmlspecialchars(ucfirst($row_pendaftar_dashboard['status'] ?? 'N/A')) . "</span></td>
                                                    <td>" . htmlspecialchars($row_pendaftar_dashboard['father_name'] ?? 'N/A') . "</td>
                                                    <td class='action-buttons aksi-column'>
                                                        <button type='button' class='btn btn-info btn-sm view-detail-btn' data-bs-toggle='modal' data-bs-target='#studentDetailModal' data-student-id='" . $studentId_dashboard . "' title='Lihat Detail'><i class='bi bi-eye-fill'></i></button>
                                                        <a href='verify.php?id=" . $studentId_dashboard . "' class='btn btn-warning btn-sm' title='Edit/Verifikasi Data'><i class='bi bi-pencil-fill'></i></a>
                                                    </td>
                                                </tr>";
                                            $no_pendaftar_dashboard++;
                                        } else :
                                            echo "<tr><td colspan='7' class='text-center fst-italic py-3'>Tidak ada data pendaftar";
                                            if (!empty($filter_status_val) || !empty($search_term_val)) {
                                                echo " yang cocok dengan filter/pencarian.";
                                            } else {
                                                echo " untuk ditampilkan.";
                                            }
                                            echo "</td></tr>";
                                        endif;
                                        ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> <?php
                // Sertakan template modal hapus untuk setiap siswa dalam daftar
                if (!empty($students_list_for_dashboard)) {
                    foreach ($students_list_for_dashboard as $row_modal_hapus_data) {
                        $student_id_for_modal = htmlspecialchars($row_modal_hapus_data['id'] ?? 'UNKNOWN_ID_HAPUS_DASH');
                        $student_name_for_modal = htmlspecialchars($row_modal_hapus_data['name'] ?? 'Pendaftar Ini');
                        // Asumsikan template_modal_hapus.php menggunakan $student_id_for_modal dan $student_name_for_modal
                        // dan ID modalnya adalah #hapusPendaftarModalSTUDENT_ID
                        $template_modal_hapus_path_pendaftar = 'template_modal_hapus_pendaftar.php'; // Gunakan nama file template yang spesifik
                        if (file