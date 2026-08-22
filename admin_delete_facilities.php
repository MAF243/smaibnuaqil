<?php
// FILE: beranda_admin.php (Dashboard Admin Utama)

// Mulai sesi
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Aktifkan untuk development, matikan (0) atau komentari untuk produksi
error_reporting(E_ALL);
ini_set('display_errors', 1); 

// Cegah cache
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Sertakan file konfigurasi database
if (file_exists('config.php')) {
    include_once 'config.php';
} else {
    error_log("CRITICAL: File konfigurasi database (config.php) tidak ditemukan di beranda_admin.php");
    exit('File konfigurasi database (config.php) tidak ditemukan. Halaman tidak dapat dimuat. Harap hubungi administrator.');
}

// Pastikan koneksi $conn ada dan berhasil setelah include config.php
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $db_conn_error_msg = isset($conn) && $conn instanceof mysqli ? $conn->connect_error : 'Objek koneksi database tidak terdefinisi atau bukan instance mysqli.';
    error_log("CRITICAL: Koneksi database gagal di beranda_admin.php: " . $db_conn_error_msg);
    exit('Koneksi database gagal. Harap hubungi administrator. Detail: ' . htmlspecialchars($db_conn_error_msg));
}

// Cek login admin
if (!isset($_SESSION['admin'])) { // Sesuaikan dengan nama variabel session admin Anda
    $_SESSION['error_message'] = "Anda harus login untuk mengakses halaman ini.";
    header("Location: login.php"); // Arahkan ke halaman login Anda
    exit;
}

// --- BAGIAN HANDLE AKSI DOWNLOAD EXCEL (CSV) ---
// (Pastikan logika download ini sudah benar dan menggunakan $conn atau $conn_download yang valid)
if (isset($_GET['action']) && $_GET['action'] === 'download_excel') {
    // ... (SALIN SELURUH BLOK LOGIKA DOWNLOAD CSV ANDA YANG SUDAH ADA DI SINI) ...
    // Contoh singkat jika Anda menggunakan $conn utama:
    // $where_clause_export = "WHERE 1=1";
    // if (isset($_GET['filter_status']) && $_GET['filter_status'] != '') { /* ... set $where_clause_export ... */ }
    // if (isset($_GET['search_term']) && $_GET['search_term'] != '') { /* ... set $where_clause_export ... */ }
    // $query_export_inline = "SELECT * FROM students $where_clause_export ORDER BY id ASC";
    // $result_export_inline = mysqli_query($conn, $query_export_inline);
    // if (!$result_export_inline) { /* ... error handling ... */ exit; }
    // $filename = "data_pendaftar_sma_ibnuaqil_" . date('Ymd_His') . ".csv";
    // header('Content-Type: text/csv; charset=utf-8');
    // header('Content-Disposition: attachment; filename="' . $filename . '"');
    // $output_inline = fopen('php://output', 'w');
    // ... (fputcsv headers dan data) ...
    // fclose($output_inline);
    // mysqli_free_result($result_export_inline);
    // exit; // PENTING: akhiri script setelah mengirim file
    // (Jika Anda memiliki kode download yang lebih panjang dan kompleks, pastikan sudah disalin lengkap)
}
// Akhir dari blok download excel

// Ambil pesan status dari session jika ada
$status_message_admin = '';
$alert_class_admin = '';
if (isset($_SESSION['status_message']) && isset($_SESSION['alert_class'])) {
    $status_message_admin = htmlspecialchars($_SESSION['status_message']);
    $alert_class_admin = htmlspecialchars($_SESSION['alert_class']);
    unset($_SESSION['status_message']);
    unset($_SESSION['alert_class']); 
}

// Ambil data admin untuk sapaan
$admin_id = $_SESSION['admin_id'] ?? null; // Pastikan 'admin_id' ada di session saat login
$admin_name = "Admin"; // Default
// Anda bisa ambil dari database jika mau, atau dari session jika sudah disimpan saat login
if(isset($_SESSION['admin_name'])) { // Jika nama admin disimpan di session
    $admin_name = htmlspecialchars($_SESSION['admin_name']);
} elseif ($admin_id && $conn) { // Jika ada admin_id, coba ambil dari DB
    $stmt_admin_name = $conn->prepare("SELECT name FROM admin WHERE id = ?");
    if ($stmt_admin_name) {
        $stmt_admin_name->bind_param("i", $admin_id);
        $stmt_admin_name->execute();
        $result_admin_name = $stmt_admin_name->get_result();
        if ($admin_data_name = $result_admin_name->fetch_assoc()) {
            $admin_name = htmlspecialchars($admin_data_name['name']);
        }
        $stmt_admin_name->close();
    }
}


// --- PENGHITUNGAN DATA UNTUK DASHBOARD CARDS ---
$counts = [
    'total'    => 0,
    'pending'  => 0,
    'verified' => 0,
    'incomplete_rejected' => 0,
    'facilities' => 0,
    'news'     => 0,
    'gallery'  => 0
];

// 1. Total Pendaftar
$query_total_students = "SELECT COUNT(*) AS count_val FROM students";
$res_total_students = mysqli_query($conn, $query_total_students);
if ($res_total_students) {
    $row_count = mysqli_fetch_assoc($res_total_students);
    $counts['total'] = $row_count['count_val'] ?? 0;
    mysqli_free_result($res_total_students);
} else {
    error_log("Query count total_students gagal di beranda_admin.php: " . mysqli_error($conn));
}

// 2. Pendaftar Pending
$query_pending_students = "SELECT COUNT(*) AS count_val FROM students WHERE status = 'pending'";
$res_pending_students = mysqli_query($conn, $query_pending_students);
if ($res_pending_students) {
    $row_count = mysqli_fetch_assoc($res_pending_students);
    $counts['pending'] = $row_count['count_val'] ?? 0;
    mysqli_free_result($res_pending_students);
} else {
    error_log("Query count pending_students gagal di beranda_admin.php: " . mysqli_error($conn));
}

// 3. Pendaftar Terverifikasi
$query_verified_students = "SELECT COUNT(*) AS count_val FROM students WHERE status = 'verified'";
$res_verified_students = mysqli_query($conn, $query_verified_students);
if ($res_verified_students) {
    $row_count = mysqli_fetch_assoc($res_verified_students);
    $counts['verified'] = $row_count['count_val'] ?? 0;
    mysqli_free_result($res_verified_students);
} else {
    error_log("Query count verified_students gagal di beranda_admin.php: " . mysqli_error($conn));
}

// 4. Pendaftar Belum Lengkap (incomplete)
$incomplete_count = 0;
$query_incomplete_students = "SELECT COUNT(*) AS count_val FROM students WHERE status = 'incomplete'";
$res_incomplete_students = mysqli_query($conn, $query_incomplete_students);
if ($res_incomplete_students) {
    $row_count = mysqli_fetch_assoc($res_incomplete_students);
    $incomplete_count = $row_count['count_val'] ?? 0;
    mysqli_free_result($res_incomplete_students);
} else {
    error_log("Query count incomplete_students gagal di beranda_admin.php: " . mysqli_error($conn));
}

// 5. Pendaftar Ditolak (rejected)
$rejected_count = 0;
$query_rejected_students = "SELECT COUNT(*) AS count_val FROM students WHERE status = 'rejected'";
$res_rejected_students = mysqli_query($conn, $query_rejected_students);
if ($res_rejected_students) {
    $row_count = mysqli_fetch_assoc($res_rejected_students);
    $rejected_count = $row_count['count_val'] ?? 0;
    mysqli_free_result($res_rejected_students);
} else {
    error_log("Query count rejected_students gagal di beranda_admin.php: " . mysqli_error($conn));
}
$counts['incomplete_rejected'] = $incomplete_count + $rejected_count;

// 6. Total Fasilitas (Asumsi tabel 'facilities' dan kolom 'is_published')
$query_facilities = "SELECT COUNT(*) AS count_val FROM facilities WHERE is_published = 1";
$res_facilities = mysqli_query($conn, $query_facilities);
if ($res_facilities) {
    $row_count = mysqli_fetch_assoc($res_facilities);
    $counts['facilities'] = $row_count['count_val'] ?? 0;
    mysqli_free_result($res_facilities);
} else {
    error_log("Query count facilities gagal (mungkin tabel belum ada): " . mysqli_error($conn));
}

// 7. Total Berita (Asumsi tabel 'news')
//    Jika tabel 'news' Anda punya kolom 'is_published', tambahkan "WHERE is_published = 1"
$query_news = "SELECT COUNT(*) AS count_val FROM news";
$res_news = mysqli_query($conn, $query_news);
if ($res_news) {
    $row_count = mysqli_fetch_assoc($res_news);
    $counts['news'] = $row_count['count_val'] ?? 0;
    mysqli_free_result($res_news);
} else {
    error_log("Query count news gagal (mungkin tabel belum ada): " . mysqli_error($conn));
}

// 8. Total Galeri (Asumsi tabel 'gallery')
//    Jika tabel 'gallery' Anda punya kolom 'is_published', tambahkan "WHERE is_published = 1"
$query_gallery = "SELECT COUNT(*) AS count_val FROM gallery";
$res_gallery = mysqli_query($conn, $query_gallery);
if ($res_gallery) {
    $row_count = mysqli_fetch_assoc($res_gallery);
    $counts['gallery'] = $row_count['count_val'] ?? 0;
    mysqli_free_result($res_gallery);
} else {
    error_log("Query count gallery gagal (mungkin tabel belum ada): " . mysqli_error($conn));
}
// --- AKHIR PENGHITUNGAN DATA UNTUK DASHBOARD CARDS ---


// Logika filter dan pencarian pendaftar (students) untuk tabel di bawah
$where_clause_display = "WHERE 1=1"; // Selalu mulai dengan kondisi true
$filter_status_val = $_GET['filter_status'] ?? '';
$search_term_val = $_GET['search_term'] ?? '';

if (!empty($filter_status_val)) {
    $filter_status_safe = mysqli_real_escape_string($conn, $filter_status_val);
    $where_clause_display .= " AND status = '$filter_status_safe'";
}
if (!empty($search_term_val)) {
    $search_term_safe = mysqli_real_escape_string($conn, $search_term_val);
    $where_clause_display .= " AND (name LIKE '%$search_term_safe%' OR email LIKE '%$search_term_safe%')";
}

$students_list_for_dashboard = []; // Gunakan nama variabel berbeda untuk daftar pendaftar di dashboard
$query_students_display = "SELECT id, name, phone, created_at, status, father_name FROM students $where_clause_display ORDER BY created_at DESC LIMIT 10";
$result_students_display_query = mysqli_query($conn, $query_students_display); // Menggunakan variabel $result_students_display_query
if (!$result_students_display_query) {
    error_log("Gagal query students untuk display di beranda_admin.php: " . mysqli_error($conn));
} else {
    while($row_student = mysqli_fetch_assoc($result_students_display_query)){
        $students_list_for_dashboard[] = $row_student;
    }
    // mysqli_free_result($result_students_display_query); // Dikosongkan di bagian HTML loop nanti
}

// Persiapan parameter untuk link download Excel pendaftar
$excel_link_params_array = ['action' => 'download_excel'];
if (!empty($filter_status_val)) { $excel_link_params_array['filter_status'] = $filter_status_val; }
if (!empty($search_term_val)) { $excel_link_params_array['search_term'] = $search_term_val; }
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
        /* ... (SALIN SEMUA CSS ANDA YANG SUDAH ADA DI SINI DARI KODE BERANDA_ADMIN.PHP ANDA) ... */
        body { background-color: #f8f9fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .table-container { margin: 30px auto; max-width: 95%; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.07); }
        .table-title { margin-bottom: 20px; text-align: center; color: #007bff; font-weight: 600; font-size: 1.75rem; }
        .footer { text-align: center; padding: 20px; background-color: #343a40; color: white; margin-top: 40px; }
        .dashboard-options { display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; margin-top: 25px; margin-bottom: 25px;}
        .dashboard-card { width: 220px; padding: 20px; text-align: center; border-radius: 8px; font-weight: bold; color: white; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: transform 0.2s ease; }
        .dashboard-card:hover { transform: translateY(-5px); }
        .dashboard-card h3 { margin-top: 8px; font-size: 1.8rem;}
        .dashboard-card .icon { font-size: 2rem; margin-bottom: 5px; }
        .card-blue { background-color: #007bff; }
        .card-green { background-color: #28a745; }
        .card-orange { background-color: #fd7e14; }
        .card-red { background-color: #dc3545; }
        .card-purple { background-color: #6f42c1; }
        .card-teal { background-color: #20c997; }
        .card-pink { background-color: #d63384; }
        
        .filter-search-container { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; align-items: center; justify-content: flex-end; padding: 15px; background-color:#f0f2f5; border-radius: 8px; }
        .action-buttons .btn { margin-right: 5px; margin-bottom: 5px; }
        th, td { min-width: 80px; vertical-align: middle; font-size:0.9rem; }
        th.no-column, td.no-column { min-width: 50px; width: 50px; text-align: center;}
        th.aksi-column, td.aksi-column { min-width: 180px; text-align:center; } /* Disesuaikan agar tombol muat */
    </style>
</head>
<body class="admin-authenticated">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="beranda_admin.php"> <img src="public/asset/logo.jpg" alt="Logo" width="30" height="30" class="d-inline-block align-text-top rounded-circle">
                Admin SMA Ibnu Aqil
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
           <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page_filename == 'beranda_admin.php' ? 'active' : ''); ?>" href="beranda_admin.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page_filename == 'admin_manage_facilities.php' ? 'active' : ''); ?>" href="admin_manage_facilities.php"><i class="bi bi-building-fill-gear me-1"></i> Fasilitas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page_filename == 'admin_manage_news.php' ? 'active' : ''); ?>" aria-current="page" href="admin_manage_news.php"><i class="bi bi-newspaper me-1"></i> Berita</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page_filename == 'admin_manage_gallery.php' ? 'active' : ''); ?>" href="admin_manage_gallery.php"><i class="bi bi-images me-1"></i> Galeri</a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo rtrim($site_url ?? '.', '/'); ?>/index.php" target="_blank" title="Buka situs publik di tab baru">
                            <i class="bi bi-eye-fill me-1"></i> Lihat Situs
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($current_page_filename == 'admin_profile.php' || $current_page_filename == 'admin_settings.php' ? 'active' : ''); ?>" href="#" id="adminProfileDropdownTopNav" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> Profil Admin
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminProfileDropdownTopNav">
                            <li><a class="dropdown-item <?php echo ($current_page_filename == 'admin_profile.php' ? 'active' : ''); ?>" href="admin_profile.php"><i class="bi bi-person-badge me-2"></i>Profil Saya</a></li>
                            <li><a class="dropdown-item <?php echo ($current_page_filename == 'admin_settings.php' ? 'active' : ''); ?>" href="admin_settings.php"><i class="bi bi-gear-fill me-2"></i>Pengaturan Akun</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php" onclick="return confirm('Yakin ingin logout?')"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="h2 mb-4">Selamat Datang, <?php echo $admin_name; ?>!</h1>

        <?php if (!empty($status_message_admin)): ?>
            <div class="alert <?php echo $alert_class_admin; ?> alert-dismissible fade show" role="alert">
                <?php echo $status_message_admin; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="dashboard-options">
            <div class="dashboard-card card-blue"><i class="bi bi-people-fill icon"></i>Total Pendaftar<h3><?= $counts['total'] ?? 0; ?></h3></div>
            <div class="dashboard-card card-green"><i class="bi bi-person-check-fill icon"></i>Terverifikasi<h3><?= $counts['verified'] ?? 0; ?></h3></div>
            <div class="dashboard-card card-orange"><i class="bi bi-hourglass-split icon"></i>Pending<h3><?= $counts['pending'] ?? 0; ?></h3></div>
            <div class="dashboard-card card-red"><i class="bi bi-person-x-fill icon"></i>Ditolak/Belum Lengkap<h3><?= $counts['incomplete_rejected'] ?? 0; ?></h3></div>
            <div class="dashboard-card card-purple"><i class="bi bi-buildings-fill icon"></i>Total Fasilitas<h3><?= $counts['facilities'] ?? 0; ?></h3></div>
            <div class="dashboard-card card-teal"><i class="bi bi-journal-text icon"></i>Total Berita<h3><?= $counts['news'] ?? 0; ?></h3></div>
            <div class="dashboard-card card-pink"><i class="bi bi-card-image icon"></i>Total Galeri<h3><?= $counts['gallery'] ?? 0; ?></h3></div>
        </div>
    
        <div class="container mt-4 text-center bg-light py-3 rounded shadow-sm"> {/* Penyesuaian Aksi Cepat */}
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
            <div class="filter-search-container card shadow-sm mb-3 p-3"> {/* Penyesuaian Filter */}
                <form method="get" action="beranda_admin.php#dataTableContainer" class="row g-2 w-100 align-items-center">
                     <div class="col-md">
                        <input type="text" class="form-control form-control-sm" name="search_term" placeholder="Cari Nama/Email Pendaftar..." value="<?= htmlspecialchars($search_term_val); ?>">
                    </div>
                    <div class="col-md-auto">
                        <select class="form-select form-select-sm" name="filter_status">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php if ($filter_status_val == 'pending') echo 'selected'; ?>>Pending</option>
                            <option value="verified" <?php if ($filter_status_val == 'verified') echo 'selected'; ?>>Terverifikasi</option>
                            <option value="incomplete" <?php if ($filter_status_val == 'incomplete') echo 'selected'; ?>>Belum Lengkap</option>
                            <option value="rejected" <?php if ($filter_status_val == 'rejected') echo 'selected'; ?>>Ditolak</option>
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
                            if ($result_students_display_query && mysqli_num_rows($result_students_display_query) > 0) {
                                $no_pendaftar_dashboard = 1; 
                                mysqli_data_seek($result_students_display_query, 0); // Kembali ke awal jika sudah di-loop untuk modal
                                while ($row_pendaftar_dashboard = mysqli_fetch_assoc($result_students_display_query)) {
                                    $status_badge_class = 'secondary';
                                    switch (strtolower($row_pendaftar_dashboard['status'] ?? '')) {
                                        case 'verified': $status_badge_class = 'success'; break;
                                        case 'pending': $status_badge_class = 'warning text-dark'; break;
                                        case 'incomplete': $status_badge_class = 'danger'; break;
                                        case 'rejected': $status_badge_class = 'dark'; break;
                                    }
                                    $studentId_dashboard = htmlspecialchars($row_pendaftar_dashboard['id'] ?? '');
                                    $hapusModalTarget_dashboard = "#hapusModal" . $studentId_dashboard;

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
                                            <button type='button' class='btn btn-danger btn-sm' data-bs-toggle='modal' data-bs-target='" . $hapusModalTarget_dashboard . "' title='Hapus Data'><i class='bi bi-trash-fill'></i></button>
                                        </td>
                                    </tr>";
                                    $no_pendaftar_dashboard++;
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center fst-italic py-3'>Tidak ada data pendaftar";
                                if (!empty($filter_status_val) || !empty($search_term_val)) { echo " yang cocok dengan filter/pencarian."; } else { echo " untuk ditampilkan."; }
                                echo "</td></tr>";
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Sertakan template modal hapus
        // Pastikan $students_list_for_dashboard berisi data yang sama yang ditampilkan di tabel
        if (!empty($students_list_for_dashboard)) {
            foreach ($students_list_for_dashboard as $row_modal_hapus){ 
                $student_id_for_modal = htmlspecialchars($row_modal_hapus['id'] ?? 'UNKNOWN_ID_HAPUS_DASH');
                $template_hapus_path = 'template_modal_hapus.php'; 
                if (file_exists($template_hapus_path)) {
                    // Pastikan template_modal_hapus.php menggunakan $student_id_for_modal untuk ID modalnya.
                    // Dan pastikan variabel $student_id_for_modal tidak bentrok dengan penggunaan di tempat lain.
                    include $template_hapus_path;  
                }
            }
        }
        // Bebaskan memori hasil query students jika belum
        if (isset($result_students_display_query) && $result_students_display_query) { 
            mysqli_free_result($result_students_display_query); 
        }
        ?>
    </div> 
    
    <div class="modal fade" id="studentDetailModal" tabindex="-1" aria-labelledby="studentDetailModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"> 
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="studentDetailModalLabel"><i class="bi bi-person-lines-fill"></i> Detail Pendaftar</h5> 
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="studentDetailModalBody">
                <p class="text-center my-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><br>Memuat detail pendaftar...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
      </div>
    </div>

    <footer class="footer">
        <div class="container"> <p>&copy; <?php echo date("Y"); ?> SMA Ibnu Aqil. Hak Cipta Dilindungi.</p> </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Cegah akses halaman admin dari cache (BACK button) - SUDAH ADA DI KODE ASLI ANDA
    // if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_BACK_FORWARD) {
    //     window.location.href = 'logout.php';
    // }

    // ... (SALIN SEMUA JAVASCRIPT ANDA YANG SUDAH ADA DI SINI DARI KODE BERANDA_ADMIN.PHP ANDA) ...
    // Termasuk untuk getFileLink, AJAX detail pendaftar, dll.
    // Pastikan BASE_UPLOAD_URL di JavaScript disesuaikan jika perlu
    document.addEventListener('DOMContentLoaded', function() {
        // Menentukan BASE_UPLOAD_URL
        let currentPath = window.location.pathname;
        let pathSegments = currentPath.split('/');
        let relativePathToRoot = ''; 
        // Jika beranda_admin.php ada di dalam folder /admin/, maka ../uploads/
        // Jika beranda_admin.php di root, maka uploads/
        if (pathSegments.length > 2 && pathSegments[pathSegments.length - 2] !== '') { // Cek jika ada subfolder sebelum nama file
            // Jika ada di /admin/beranda_admin.php, pathSegments akan ['', 'admin', 'beranda_admin.php']
            // atau /folder1/admin/beranda_admin.php -> ['', 'folder1', 'admin', 'beranda_admin.php']
            // Kita ingin ../uploads/ jika /admin/, atau ../../uploads/ jika /folder1/admin/
            // Cara aman: asumsikan uploads sejajar dengan folder di mana index.php utama (publik) berada.
            // Jika beranda_admin.php ada di /admin/, maka relatif ke root adalah '../'
            // Ini mungkin perlu disesuaikan dengan struktur folder Anda yang sebenarnya.
            // Cara paling aman adalah hardcode path relatif ke root jika Anda tahu strukturnya,
            // atau gunakan path absolut jika memungkinkan.
            // Contoh jika admin di subfolder: relativePathToRoot = '../';
            // Jika admin di root: relativePathToRoot = '';
             if (currentPath.toLowerCase().includes('/admin/')) { // Asumsi ada folder /admin/
                relativePathToRoot = '../';
            }
        }
        const BASE_UPLOAD_URL_MODAL = relativePathToRoot + 'uploads/'; 
        // Perhatian: BASE_UPLOAD_URL yang digunakan di file index_admin.php yang pertama Anda berikan (download Excel)
        // juga perlu diperhatikan konsistensinya jika ada.

        var studentDetailModalEl = document.getElementById('studentDetailModal');
        var studentDetailModal = studentDetailModalEl ? new bootstrap.Modal(studentDetailModalEl) : null;
        var modalBody = document.getElementById('studentDetailModalBody');

        function getFileLink(filePath, subfolder = '') {
            if (!filePath || typeof filePath !== 'string' || filePath.trim() === '') return '<span class="text-muted"><em>Tidak ada file</em></span>';
            
            let fileNameOnly = filePath.split(/[\\/]/).pop(); 
            // Gunakan BASE_UPLOAD_URL_MODAL yang sudah didefinisikan dengan benar
            let fullPublicPath = BASE_UPLOAD_URL_MODAL + (subfolder ? subfolder.trim() + '/' : '') + fileNameOnly.trim();
            
            // Jika filePath sudah merupakan path yang benar (misalnya dari database sudah ada 'uploads/kk_siswa/file.jpg')
            // dan BASE_UPLOAD_URL_MODAL adalah '' (jika admin di root dan uploads di root)
            // Maka ini perlu penyesuaian.
            // Logika getFileLink Anda yang asli mungkin lebih cocok jika path di DB sudah termasuk 'uploads/subfolder/'
            // Coba kita pakai pendekatan yang lebih sederhana: jika filePath sudah dimulai dengan 'uploads/', anggap itu benar.
            if (filePath.toLowerCase().startsWith('uploads/')) {
                 // Jika admin di subfolder, kita perlu '../'
                 fullPublicPath = relativePathToRoot + filePath;
            } else if (filePath.startsWith('http://') || filePath.startsWith('https://') || filePath.startsWith('/')) {
                 fullPublicPath = filePath; // Sudah URL absolut atau path dari root server
            }
            // Jika filePath HANYA nama file, dan ingin digabung dengan subfolder:
            // else if (subfolder) { fullPublicPath = BASE_UPLOAD_URL_MODAL + subfolder + '/' + filePath; }
            // else { fullPublicPath = BASE_UPLOAD_URL_MODAL + filePath; }


            return `<a href="${fullPublicPath}" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i> Lihat File</a>`;
        }

        document.querySelectorAll('.view-detail-btn').forEach(button => {
            button.addEventListener('click', function() {
                var studentId = this.getAttribute('data-student-id');
                if(modalBody) {
                    modalBody.innerHTML = '<p class="text-center my-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><br>Memuat detail pendaftar...</p>';
                }
                if(studentDetailModal) studentDetailModal.show();

                fetch('get_student_details.php?id=' + studentId)
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => { throw new Error('Network response error: ' + response.status + ' ' + response.statusText + '. Response: ' + text.substring(0, 300)) });
                        }
                        return response.json().catch(jsonErr => {
                            console.error("JSON Parsing Error:", jsonErr);
                            // Coba ambil teks respons jika JSON gagal parse, untuk debug
                            return response.text().then(text => {
                                console.error("Raw server response:", text);
                                throw new Error("Format respons server tidak valid (bukan JSON). Isi respons: " + text.substring(0,300));
                            });
                        });
                    })
                    .then(data => {
                        if (data.success && data.data) {
                            var student = data.data;
                            // Perhatikan penggunaan `getFileLink` di sini
                            var detailsHtml = `
                                <div class="table-responsive">
                                <table class="table table-striped table-bordered table-sm">
                                    <thead><tr class="table-light"><th colspan="2" class="text-primary fs-5 fw-bold text-center">DATA DIRI CALON SISWA</th></tr></thead>
                                    <tbody>
                                        <tr><th scope="row" style="width:30%;">ID Pendaftar:</th><td>#${student.id || 'N/A'}</td></tr>
                                        <tr><th scope="row">Nama Lengkap:</th><td>${student.name || 'N/A'}</td></tr>
                                        <tr><th scope="row">Email:</th><td><a href="mailto:${student.email || ''}">${student.email || 'N/A'}</a></td></tr>
                                        <tr><th scope="row">Tanggal Lahir:</th><td>${student.dob ? new Date(student.dob).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : 'N/A'}</td></tr>
                                        <tr><th scope="row">Tempat Lahir:</th><td>${student.birthplace || 'N/A'}</td></tr>
                                        <tr><th scope="row">No. Telepon:</th><td><a href="tel:${student.phone || ''}">${student.phone || 'N/A'}</a></td></tr>
                                        <tr><th scope="row">Alamat Lengkap:</th><td style="white-space: pre-wrap;">${student.address || 'N/A'}</td></tr>
                                        <tr><th scope="row">Jenis Kelamin:</th><td>${student.gender || 'N/A'}</td></tr>
                                        <tr><th scope="row">Agama:</th><td>${student.religion_child || 'N/A'}</td></tr>
                                        <tr><th scope="row">Hobi:</th><td>${student.student_hobby || 'N/A'}</td></tr>
                                        <tr><th scope="row">Cita-cita:</th><td>${student.goal || 'N/A'}</td></tr>
                                        <tr><th scope="row">Motivasi Masuk:</th><td style="white-space: pre-wrap;">${student.motivation || 'N/A'}</td></tr>
                                        <tr><th scope="row">Status Pendaftaran:</th><td><span class="badge fs-6 bg-${student.status === 'verified' ? 'success' : (student.status === 'pending' ? 'warning text-dark' : (student.status === 'incomplete' ? 'danger' : (student.status === 'rejected' ? 'dark' : 'secondary')))}">${student.status ? student.status.charAt(0).toUpperCase() + student.status.slice(1) : 'N/A'}</span></td></tr>
                                        <tr><th scope="row">Tanggal Daftar:</th><td>${student.created_at ? new Date(student.created_at).toLocaleString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) + ' WIB' : 'N/A'}</td></tr>
                                    </tbody>
                                    <thead><tr class="table-light"><th colspan="2" class="text-primary fs-5 fw-bold text-center">DATA ORANG TUA / WALI</th></tr></thead>
                                    <tbody>
                                        <tr><th scope="row">Nama Ayah:</th><td>${student.father_name || 'N/A'}</td></tr>
                                        <tr><th scope="row">No. Telepon Ayah:</th><td><a href="tel:${student.father_phone || ''}">${student.father_phone || 'N/A'}</a></td></tr>
                                        <tr><th scope="row">Pekerjaan Ayah:</th><td>${student.father_job || 'N/A'}</td></tr>
                                        <tr><th scope="row">Email Ayah:</th><td><a href="mailto:${student.father_email || ''}">${student.father_email || 'N/A'}</a></td></tr>
                                        <tr><th scope="row">Penghasilan Ayah:</th><td>${student.father_income || 'N/A'}</td></tr>
                                        <tr><th scope="row">Tempat Lahir Ayah:</th><td>${student.father_birthplace || 'N/A'}</td></tr>
                                        <tr><th scope="row">Tgl Lahir Ayah:</th><td>${student.father_dob ? new Date(student.father_dob).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : 'N/A'}</td></tr>
                                        <tr><th scope="row">Agama Ayah:</th><td>${student.religion_father || 'N/A'}</td></tr>
                                        <tr class="table-secondary"><td colspan="2" class="py-1"></td></tr>
                                        <tr><th scope="row">Nama Ibu:</th><td>${student.mother_name || 'N/A'}</td></tr>
                                        <tr><th scope="row">No. Telepon Ibu:</th><td><a href="tel:${student.mother_phone || ''}">${student.mother_phone || 'N/A'}</a></td></tr>
                                        <tr><th scope="row">Pekerjaan Ibu:</th><td>${student.mother_job || 'N/A'}</td></tr>
                                        <tr><th scope="row">Email Ibu:</th><td><a href="mailto:${student.mother_email || ''}">${student.mother_email || 'N/A'}</a></td></tr>
                                        <tr><th scope="row">Penghasilan Ibu:</th><td>${student.mother_income || 'N/A'}</td></tr>
                                        <tr><th scope="row">Tempat Lahir Ibu:</th><td>${student.mother_birthplace || 'N/A'}</td></tr>
                                        <tr><th scope="row">Tgl Lahir Ibu:</th><td>${student.mother_dob ? new Date(student.mother_dob).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : 'N/A'}</td></tr>
                                        <tr><th scope="row">Agama Ibu:</th><td>${student.religion_mother || 'N/A'}</td></tr>`;
                            
                            if (student.guardian_name || student.guardian_phone || student.guardian_relation) {
                                detailsHtml += `
                                    <tr class="table-secondary"><td colspan="2" class="py-1"></td></tr>
                                    <thead><tr class="table-light"><th colspan="2" class="text-primary fs-5 fw-bold text-center">DATA WALI (Jika Ada)</th></tr></thead><tbody>
                                    <tr><th scope="row">Nama Wali:</th><td>${student.guardian_name || 'N/A'}</td></tr>
                                    <tr><th scope="row">No. Telepon Wali:</th><td><a href="tel:${student.guardian_phone || ''}">${student.guardian_phone || 'N/A'}</a></td></tr>
                                    <tr><th scope="row">Hubungan Wali:</th><td>${student.guardian_relation || 'N/A'}</td></tr>
                                    <tr><th scope="row">Email Wali:</th><td><a href="mailto:${student.guardian_email || ''}">${student.guardian_email || 'N/A'}</a></td></tr>
                                    <tr><th scope="row">Penghasilan Wali:</th><td>${student.guardian_income || 'N/A'}</td></tr>
                                    <tr><th scope="row">Tempat Lahir Wali:</th><td>${student.guardian_birthplace || 'N/A'}</td></tr>
                                    <tr><th scope="row">Tgl Lahir Wali:</th><td>${student.guardian_dob ? new Date(student.guardian_dob).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : 'N/A'}</td></tr>
                                    <tr><th scope="row">Agama Wali:</th><td>${student.guardian_religion || 'N/A'}</td></tr></tbody>`;
                            }
                            detailsHtml += `</tbody>
                                    <thead><tr class="table-light"><th colspan="2" class="text-primary fs-5 fw-bold text-center">DOKUMEN PENDUKUNG</th></tr></thead>
                                    <tbody>
                                        <tr><th scope="row">Kartu Keluarga (KK) Siswa:</th><td>${getFileLink(student.kk_file, 'kk_siswa')}</td></tr>
                                        <tr><th scope="row">Akta Lahir Siswa:</th><td>${getFileLink(student.akta_lahir, 'akta_lahir')}</td></tr>
                                        <tr><th scope="row">Nilai Rapor Terakhir:</th><td>${getFileLink(student.nilai_rapor, 'nilai_rapor')}</td></tr>
                                        <tr><th scope="row">KTP Ayah:</th><td>${getFileLink(student.ktp_father, 'ktp_ayah')}</td></tr>
                                        <tr><th scope="row">KTP Ibu:</th><td>${getFileLink(student.ktp_mother, 'ktp_ibu')}</td></tr>`;
                            
                            if (student.guardian_name || student.guardian_phone || student.guardian_relation) { 
                                detailsHtml += `<tr><th scope="row">KTP Wali:</th><td>${getFileLink(student.ktp_guardian, 'ktp_wali')}</td></tr>`;
                                detailsHtml += `<tr><th scope="row">Kartu Keluarga (KK) Wali:</th><td>${getFileLink(student.kk_guardian, 'kk_wali')}</td></tr>`;
                            }
                            detailsHtml += `</tbody></table></div>`;
                            if(modalBody) modalBody.innerHTML = detailsHtml;
                        } else {
                             if(modalBody) modalBody.innerHTML = `<p class="text-danger text-center my-5">${data.message || 'Gagal memuat detail pendaftar atau data tidak ditemukan/lengkap.'}</p>`;
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error untuk detail siswa:', error);
                        if(modalBody) modalBody.innerHTML = `<p class="text-danger text-center my-5">Terjadi kesalahan saat mengambil data detail pendaftar: ${error.message}. Mohon coba lagi atau hubungi administrator.</p>`;
                    });
            });
        });
    });
    </script>
</body>
</html>