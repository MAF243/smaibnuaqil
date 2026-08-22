<?php
// FILE: index_admin.php

// Mulai sesi
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cegah cache agar tidak bisa diakses dari tombol "Back"
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Cek login
if (!isset($_SESSION['admin'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// --- BAGIAN HANDLE AKSI DOWNLOAD EXCEL (CSV) TELAH DIHAPUS DARI SINI ---
// Logika yang terkait dengan $_GET['action'] === 'download_excel' dan fputcsv()
// sudah tidak ada di file ini.
// --- AKHIR BAGIAN DOWNLOAD EXCEL ---


// --- BAGIAN UTAMA HALAMAN DASHBOARD ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (file_exists('config.php')) {
    include_once 'config.php';
} else {
    exit('File konfigurasi database (config.php) tidak ditemukan. Halaman tidak dapat dimuat.');
}

// Pastikan koneksi database ada untuk bagian display
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
     exit('Koneksi database gagal. Harap hubungi administrator.');
}


// Mengambil admin_id dari sesi, harusnya sudah diset saat login
// Jika 'admin_id' tidak diset di sesi login, coba cari berdasarkan 'admin' username
$admin_id = $_SESSION['admin_id'] ?? null; // Pastikan 'admin_id' diset di sesi saat login
// Jika 'admin_id' tidak diset di sesi login, gunakan 'admin' username sebagai fallback jika ada
if ($admin_id === null && isset($_SESSION['admin'])) {
     $username_from_session = $_SESSION['admin'];
     $query_admin_id = "SELECT id FROM admin WHERE username = ?";
     $stmt_admin_id = mysqli_prepare($conn, $query_admin_id);
     if ($stmt_admin_id) {
         mysqli_stmt_bind_param($stmt_admin_id, 's', $username_from_session);
         mysqli_stmt_execute($stmt_admin_id);
         $result_admin_id = mysqli_stmt_get_result($stmt_admin_id);
         if ($row_admin_id = mysqli_fetch_assoc($result_admin_id)) {
             $admin_id = $row_admin_id['id'];
             $_SESSION['admin_id'] = $admin_id; // Simpan di sesi untuk nanti
         }
         mysqli_stmt_close($stmt_admin_id);
     } else {
         error_log("Failed to get admin_id by username: " . mysqli_error($conn));
     }
}


$admin_name = "Admin";
$admin_photo_url = null;

// Ambil nama dan foto admin dari DB menggunakan admin_id
if ($admin_id) { // Hanya query jika admin_id valid
    // PASTIKAN nama tabel 'admin' dan kolom 'name', 'photo' sesuai DB
    $query_admin = "SELECT name, photo FROM admin WHERE id = ?";
    $stmt_admin = mysqli_prepare($conn, $query_admin);
    if($stmt_admin) {
        mysqli_stmt_bind_param($stmt_admin, 'i', $admin_id);
        mysqli_stmt_execute($stmt_admin);
        $result_admin = mysqli_stmt_get_result($stmt_admin);
        if ($admin_data = mysqli_fetch_assoc($result_admin)) {
            $admin_name = htmlspecialchars($admin_data['name'] ?? "Admin"); // Gunakan Admin default jika nama kosong
            if (!empty($admin_data['photo'])) {
                $photo_filename = htmlspecialchars($admin_data['photo']);
                // **PENTING:** Sesuaikan path dasar folder upload foto admin jika berbeda
                $potential_admin_photo_path = 'uploads/admin_photos/' . $photo_filename;
                // Cek apakah file foto ada di server sebelum digunakan
                if (file_exists($potential_admin_photo_path)) {
                    $admin_photo_url = $potential_admin_photo_path;
                } else {
                    // Foto tidak ditemukan di path yang diharapkan
                    error_log("Admin photo file not found: " . $potential_admin_photo_path);
                    // Anda bisa set $admin_photo_url ke gambar placeholder default di sini
                }
            }
        }
        mysqli_stmt_close($stmt_admin);
    } else {
        error_log("Query admin error: " . mysqli_error($conn));
    }
} else {
    // admin_id tidak valid atau tidak ditemukan setelah login
    error_log("Admin ID not found in session or DB lookup failed after login.");
    // Anda bisa set $admin_name ke default dan $admin_photo_url ke placeholder
}


// Menghitung jumlah pendaftar untuk dashboard cards
$counts = ['total' => 0, 'pending' => 0, 'verified' => 0, 'incomplete' => 0, 'rejected' => 0];
if(isset($conn)) {
    // PASTIKAN nama tabel 'students' dan kolom 'status' sesuai DB
    $query_counts_sql = [
        'total' => "SELECT COUNT(*) AS count_val FROM students",
        'pending' => "SELECT COUNT(*) AS count_val FROM students WHERE status = 'pending'",
        'verified' => "SELECT COUNT(*) AS count_val FROM students WHERE status = 'verified'",
        'incomplete' => "SELECT COUNT(*) AS count_val FROM students WHERE status = 'incomplete'",
        'rejected' => "SELECT COUNT(*) AS count_val FROM students WHERE status = 'rejected'"
    ];
    foreach ($query_counts_sql as $key => $sql) {
        $result_count_query = mysqli_query($conn, $sql);
        if ($result_count_query) {
            $row_count = mysqli_fetch_assoc($result_count_query);
            $counts[$key] = $row_count['count_val'] ?? 0;
            mysqli_free_result($result_count_query);
        } else {
            error_log("Count query for '$key' failed: " . mysqli_error($conn));
        }
    }
} else {
    error_log("Koneksi database tidak tersedia untuk menghitung jumlah pendaftar.");
}

// Bagian ini adalah logika inti untuk pencarian dan filter data pendaftar yang ditampilkan di tabel
$where_clause_display = "WHERE 1=1"; // Selalu mulai dengan kondisi TRUE
$filter_status_val = $_GET['filter_status'] ?? '';
$search_term_val = $_GET['search_term'] ?? '';

if (isset($conn)) {
    if ($filter_status_val != '') {
        // Memastikan nilai status aman untuk query
        $filter_status_safe = mysqli_real_escape_string($conn, $filter_status_val);
        // PASTIKAN nama kolom 'status' di tabel students sesuai DB
        $where_clause_display .= " AND status = '$filter_status_safe'";
    }
    if ($search_term_val != '') {
        // Memastikan nilai pencarian aman dan menggunakan LIKE untuk pencarian fleksibel
        $search_term_safe = mysqli_real_escape_string($conn, $search_term_val);
        // *******************************************************************************
        // *** PENTING: PERIKSA NAMA KOLOM DI SINI! ***
        // *** Pastikan 'name' dan 'email' adalah nama kolom yang *persis* di tabel 'students' Anda.
        // *** Jika di phpMyAdmin Anda melihat kolomnya bernama 'full_name' atau 'student_email',
        // *** Anda harus mengubahnya di baris berikut:
        // *******************************************************************************
        $where_clause_display .= " AND (name LIKE '%$search_term_safe%' OR email LIKE '%$search_term_safe%')"; // Query search di kolom 'name' dan 'email' pendaftar
    }
}


$result_students_display = null;
if (isset($conn)) {
    // Query database untuk menampilkan data pendaftar berdasarkan filter dan pencarian
    // **PENTING:** Pastikan nama tabel 'students' dan nama kolom yang dipilih (id, name, phone, created_at, status, father_name, email) sesuai DB
    $query_students_display = "SELECT id, name, email, phone, created_at, status, father_name FROM students $where_clause_display ORDER BY created_at DESC"; // Memilih kolom spesifik untuk tabel display
    $result_students_display = mysqli_query($conn, $query_students_display);
    if (!$result_students_display) {
        error_log("Gagal query students untuk display: " . mysqli_error($conn));
        // Anda bisa menampilkan pesan error di sini jika ingin user tahu
        // echo "<p class='text-danger'>Terjadi kesalahan saat mengambil data pendaftar: " . mysqli_error($conn) . "</p>";
    }
} else {
     error_log("Koneksi database tidak tersedia untuk menampilkan data pendaftar.");
     // Anda bisa menampilkan pesan error di sini jika ingin user tahu
     // echo "<p class='text-danger'>Koneksi database tidak tersedia. Harap hubungi administrator.</p>";
}

// Parameter untuk link export Excel (CSV) - Sekarang tidak lagi dipakai karena fitur export dihapus
// $excel_link_params_array = ['action' => 'download_excel'];
// if ($filter_status_val != '') {
//     $excel_link_params_array['filter_status'] = $filter_status_val;
// }
// if ($search_term_val != '') {
//     $excel_link_params_array['search_term'] = $search_term_val;
// }
// $excel_link_params = http_build_query($excel_link_params_array); // Ini juga tidak dipakai

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SMA Ibnu Aqil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/jpg" href="public/asset/logo.jpg">
    <style>
        body { background-color: #f8f9fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background-color: #007bff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); min-height: 56px; }
        .table-container { margin: 30px auto; max-width: 95%; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.07); }
        .table-title { margin-bottom: 20px; text-align: center; color: #007bff; font-weight: 600; font-size: 1.75rem; }
        .footer { text-align: center; padding: 20px; background-color: #343a40; color: white; margin-top: 40px; }
        .table-responsive { margin-top: 15px; }
        .dashboard-options { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; margin-top: 30px; }
        .dashboard-card { width: 270px; padding: 25px; text-align: center; border-radius: 8px; font-weight: bold; color: white; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: transform 0.2s ease; }
        .dashboard-card:hover { transform: translateY(-5px); }
        .dashboard-card h3 { margin-top: 10px; font-size: 2rem;}
        .card-blue { background-color: #007bff; }
        .card-green { background-color: #28a745; }
        .card-orange { background-color: #ffc107; color: #212529 !important; }
        .card-red { background-color: #dc3545; }
        .logout-top-right { position: fixed; top: 12px; right: 20px; z-index: 1050; }
        .filter-search-container { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; align-items: center; justify-content: flex-end; padding: 15px; background-color:#f8f9fa; border-radius: 8px; }
         .filter-search-container > * { /* Apply flex-grow to children of filter-search-container */
             flex-grow: 1; /* Allow children to grow */
             max-width: 300px; /* Optional: Limit max width of each item */
         }
          .filter-search-container .form-label {
              flex-grow: 0; /* Prevent label from growing */
          }
         @media (min-width: 768px) { /* Adjust layout for larger screens */
             .filter-search-container {
                 justify-content: flex-start; /* Align items to the start */
             }
         }

        .action-buttons .btn { margin-right: 5px; margin-bottom: 5px; }
        th, td { min-width: 80px; vertical-align: middle; }
        th:first-child, td:first-child { min-width: 40px; width: 40px; text-align: center;}
        th.aksi-column, td.aksi-column { min-width: 180px; }
    </style>
</head>
<body class="admin-authenticated">
    <div id="wrapper">

        <?php
             // Misalnya, jika sidebar Anda ada di admin_sidebar.php
             // include 'admin_sidebar.php';
        ?>
        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">

                <?php
                      // Misalnya, jika topbar Anda ada di admin_topbar.php
                      // include 'admin_topbar.php';
                 ?>
                <div class="container-fluid">

                    <h1 class="h3 mb-4 text-gray-800">Dashboard Admin</h1>

                    <?php
                     // --- Tambahan Link "Ganti Password" di Dashboard (Contoh) ---
                     // Anda bisa letakkan ini di sidebar atau topbar user dropdown juga
                    ?>
                     <div class="row mb-4">
                         <div class="col text-center">
                             <a href="admin_change_password.php" class="btn btn-secondary">
                                  <i class="bi bi-key-fill"></i> Ganti Password Admin
                             </a>
                         </div>
                     </div>
                    <?php // --- Akhir Tambahan Link "Ganti Password" --- ?>


                    <div class="container dashboard-options mt-3">
                        <div class="dashboard-card card-blue">Total Pendaftar<br><h3><?= $counts['total'] ?? 0; ?></h3></div>
                        <div class="dashboard-card card-green">Terverifikasi<br><h3><?= $counts['verified'] ?? 0; ?></h3></div>
                        <div class="dashboard-card card-orange">Pending<br><h3><?= $counts['pending'] ?? 0; ?></h3></div>
                        <div class="dashboard-card card-red">Berkas Tidak Lengkap<br><h3><?= $counts['incomplete'] ?? 0; ?></h3></div>
                         <?php /*
                         <div class="dashboard-card card-dark">Ditolak<br><h3><?= $counts['rejected'] ?? 0; ?></h3></h3></div>
                         */ ?>
                    </div>

                    <div class="container mt-4 text-center">
                         <h3 class="mb-3 text-primary">Manajemen Konten</h3>
                         <a href="admin_manage_news.php" class="btn btn-info btn-lg me-2 mb-2">
                             <i class="bi bi-newspaper"></i> Kelola Berita
                         </a>
                         <a href="admin_manage_gallery.php" class="btn btn-success btn-lg me-2 mb-2">
                             <i class="bi bi-images"></i> Kelola Galeri
                         </a>
                         </div>

                    <div class="container mt-4 table-container" id="dataTableContainer">
                         <h2 class="table-title">Data Pendaftar</h2>

                         <div class="filter-search-container card shadow-sm mb-4">
                            <form method="get" action="" class="d-flex flex-wrap flex-grow-1 gap-2 align-items-center justify-content-end">
                                 <label for="filter_status" class="form-label mb-0 fw-bold">Filter Status:</label>
                                 <select class="form-select form-select-sm" style="width: auto; flex-grow: 0;" id="filter_status" name="filter_status" onchange="this.form.submit()">
                                     <option value="">Semua Status</option>
                                     <option value="pending" <?php if ($filter_status_val == 'pending') echo 'selected'; ?>>Pending</option>
                                     <option value="verified" <?php if ($filter_status_val == 'verified') echo 'selected'; ?>>Terverifikasi</option>
                                     <option value="incomplete" <?php if ($filter_status_val == 'incomplete') echo 'selected'; ?>>Berkas Tidak Lengkap</option>
                                     <option value="rejected" <?php if ($filter_status_val == 'rejected') echo 'selected'; ?>>Ditolak</option>
                                 </select>

                                 <label for="search_term" class="form-label mb-0 fw-bold">Cari (Nama/Email):</label>
                                 <input type="text" class="form-control form-control-sm" style="width: auto; flex-grow: 1;" id="search_term" name="search_term" value="<?= htmlspecialchars($search_term_val) ?>" placeholder="Cari Nama atau Email...">
                                 <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Cari</button>
                                 <?php if(!empty($filter_status_val) || !empty($search_term_val)): // Tampilkan tombol reset hanya jika ada filter/search ?>
                                     <a href="index_admin.php" class="btn btn-secondary btn-sm"><i class="bi bi-x-lg"></i> Reset Filter</a>
                                 <?php endif; ?>

                             </form>
                         </div>


                         <div class="mb-3 text-center text-md-start">
                             <button class="btn btn-danger mb-2" onclick="downloadPDF()">
                                 <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
                             </button>
                             <a href="?<?= $excel_link_params ?>" class="btn btn-success mb-2">
                                 <i class="bi bi-file-earmark-excel-fill"></i> Download Excel (CSV)
                             </a>
                             <?php /*
                             <a href="admin_add_student.php" class="btn btn-primary mb-2">
                                <i class="bi bi-plus-circle"></i> Tambah Pendaftar
                             </a>
                             */ ?>
                         </div>


                         <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Nama</th>
                                                <th>Email</th>
                                                <th>Telepon</th>
                                                <th>Tgl Daftar</th>
                                                <th>Status</th>
                                                <th>Nama Ayah</th>
                                                <th class="aksi-column">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        if ($result_students_display && mysqli_num_rows($result_students_display) > 0) {
                                            $no = 1;
                                            while ($row = mysqli_fetch_assoc($result_students_display)) {
                                                $status_badge_class = 'secondary';
                                                switch (strtolower($row['status'] ?? '')) {
                                                    case 'verified': $status_badge_class = 'success'; break;
                                                    case 'pending': $status_badge_class = 'warning text-dark'; break;
                                                    case 'incomplete': $status_badge_class = 'danger'; break;
                                                    case 'rejected': $status_badge_class = 'dark'; break;
                                                }
                                                // Menggunakan kolom 'id' dari hasil query untuk data-student-id
                                                $studentId = htmlspecialchars($row['id'] ?? '');
                                                // Modal target untuk hapus (jika Anda masih menggunakannya)
                                                // Pastikan template_modal_hapus.php ada dan bekerja seperti yang diharapkan
                                                $template_hapus_path = 'template_modal_hapus.php'; // Sesuaikan jika nama file Anda berbeda
                                                $hapusModalTarget = "#hapusModal" . $studentId;

                                                echo "<tr>
                                                    <td>{$no}</td>
                                                    <td>" . htmlspecialchars($row['name'] ?? 'N/A') . "</td>
                                                    <td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td> {/* Menampilkan kolom Email */}
                                                    <td>" . htmlspecialchars($row['phone'] ?? 'N/A') . "</td>
                                                    <td>" . (!empty($row['created_at']) ? htmlspecialchars(date('d M Y H:i', strtotime($row['created_at']))) : 'N/A') . "</td>
                                                    <td><span class='badge bg-{$status_badge_class}'>" . htmlspecialchars(ucfirst($row['status'] ?? 'N/A')) . "</span></td>
                                                    <td>" . htmlspecialchars($row['father_name'] ?? 'N/A') . "</td>
                                                    <td class='action-buttons aksi-column'>
                                                        {/* Button Lihat Detail */}
                                                        <button type='button' class='btn btn-info btn-sm view-detail-btn' data-bs-toggle='modal' data-bs-target='#studentDetailModal' data-student-id='" . $studentId . "' title='Lihat Detail'><i class='bi bi-eye-fill'></i></button>
                                                        {/* Link Edit Data */}
                                                        <a href='verify.php?id=" . $studentId . "' class='btn btn-warning btn-sm' title='Edit Data'><i class='bi bi-pencil-fill'></i></a>
                                                        {/* Button Hapus Data */}
                                                        <button type='button' class='btn btn-danger btn-sm' data-bs-toggle='modal' data-bs-target='" . $hapusModalTarget . "' title='Hapus Data'><i class='bi bi-trash-fill'></i></button>
                                                    </td>
                                                </tr>";
                                                $no++;
                                            }
                                        } else {
                                            echo "<tr><td colspan='8' class='text-center fst-italic py-3'>Tidak ada data pendaftar";
                                            if (!empty($filter_status_val) || !empty($search_term_val)) { echo " yang cocok."; } else { echo "."; }
                                            echo "</td></tr>";
                                        }
                                        ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            <?php
                  // Misalnya, jika footer Anda ada di admin_footer.php
                  // include 'admin_footer.php';
             ?>
            </div>
        </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <?php
        // Misalnya, jika modal logout Anda ada di admin_logout_modal.php
        // include 'admin_logout_modal.php';
     ?>


    <?php
    // Bagian ini yang sebelumnya melooping template_modal_detail.php telah dihapus.
    // Hanya loop untuk modal hapus yang mungkin masih ada jika Anda memiliki 'template_modal_hapus.php'.
    // Pastikan 'template_modal_hapus.php' tidak berisi HTML modal detail dan hanya modal hapus.
    if (isset($result_students_display) && $result_students_display && mysqli_num_rows($result_students_display) > 0) {
        // Kembalikan pointer hasil query ke awal untuk loop modal
        mysqli_data_seek($result_students_display, 0);
        while ($row_modal = mysqli_fetch_assoc($result_students_display)):
            $student_id_for_modal = htmlspecialchars($row_modal['id'] ?? 'UNKNOWN_ID');

            // Include template modal hapus jika file ada
            $template_hapus_path = 'template_modal_hapus.php'; // Sesuaikan jika nama file Anda berbeda
            if (file_exists($template_hapus_path)) {
                // Pastikan template_modal_hapus.php menggunakan variabel $student_id_for_modal untuk ID modalnya.
                include $template_hapus_path;
            } else {
                // Jika file tidak ditemukan, tambahkan baris kosong di output HTML (opsional)
                echo "\n";
            }
        endwhile;
    }

    // Bebaskan memori hasil query display
    if (isset($result_students_display) && $result_students_display) {
        mysqli_free_result($result_students_display);
    }

    // Tutup koneksi database (jika belum ditutup oleh bagian export)
     if (isset($conn) && $conn instanceof mysqli) {
         // Cek apakah koneksi masih terbuka sebelum ditutup
         // mysqli_close($conn); // Biasanya koneksi ditutup otomatis di akhir script
     }
    ?>

    <div class="modal fade" id="studentDetailModal" tabindex="-1" aria-labelledby="studentDetailModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="studentDetailModalLabel">Detail Pendaftar</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="studentDetailModalBody">
            <p class="text-center">Memuat detail...</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>


    <footer class="footer">
        <div class="container"> <p>&copy; <?= date("Y"); ?> SMA Ibnu Aqil. Hak Cipta Dilindungi.</p> </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
       // Pastikan kode ini dieksekusi setelah DOM siap
        document.addEventListener('DOMContentLoaded', function() {

           // Cegah akses halaman admin dari cache (BACK button)
           // Note: Ini mungkin mengganggu jika halaman admin memiliki state complex yang disimpan di history
           // Pertimbangkan alternatif lain jika ini jadi masalah
           if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_BACK_FORWARD) {
               window.location.href = 'logout.php'; // Arahkan ke logout jika diakses via back/forward cache
           }

           // Tambahan perlindungan jika class "admin-authenticated" tidak ada
           // Note: Class ini harus ditambahkan di tag body HTML untuk perlindungan ini bekerja
           // Anda juga bisa gunakan cek sesi PHP di awal file
           // window.onload = function () {
           //     if (!document.body.classList.contains('admin-authenticated')) {
           //          window.location.href = 'logout.php'; // Logika logout sudah ada di PHP awal
           //      }
           // };


           // --- JavaScript untuk fitur Lihat Detail via AJAX ---
            // !!! PENTING: JALUR INI SUDAH DIKONFIRMASI BENAR UNTUK SETUP ANDA !!!
            // Ini adalah base URL tempat semua file upload Anda disimpan dan diakses publik.
            const BASE_UPLOAD_URL = '/uploads/'; // Pastikan ini sesuai dengan struktur direktori Anda

            // Mengambil referensi ke modal body tempat detail akan ditampilkan
            var modalBody = document.getElementById('studentDetailModalBody'); // Menggunakan ID modal body yang diperbarui

            // Fungsi helper untuk membuat link file yang benar di detail modal
            function getFileLink(filePath) {
                if (!filePath || typeof filePath !== 'string') return 'Tidak ada';

                // Hapus "../" jika ada di awal path yang tersimpan di DB
                let cleanedPath = filePath.replace(/^\.\.\//, ''); // Hapus "../" di awal jika ada

                // Jika path sudah diawali dengan "uploads/" atau "/" (absolute/relative dari root web)
                // gunakan langsung filePath. Contoh: "uploads/image.jpg" atau "/gambar/file.pdf"
                 // Atau jika path sudah URL lengkap (http/https)
                if (cleanedPath.startsWith('uploads/') || cleanedPath.startsWith('/') || cleanedPath.startsWith('http://') || cleanedPath.startsWith('https://')) {
                    return `<a href="${cleanedPath}" target="_blank">Lihat File</a>`;
                }
                // Jika hanya nama file atau path relatif pendek, tambahkan BASE_UPLOAD_URL
                return `<a href="${BASE_UPLOAD_URL}${cleanedPath}" target="_blank">Lihat File</a>`;
            }


            // Menambahkan event listener ke setiap tombol "Lihat Detail"
            document.querySelectorAll('.view-detail-btn').forEach(button => {
                button.addEventListener('click', function() {
                    // Mengambil ID pendaftar dari atribut data-student-id pada tombol yang diklik
                    var studentId = this.getAttribute('data-student-id');
                    console.log('Clicked detail button for student ID:', studentId); // Debug log di console browser

                    // Validasi dasar ID pendaftar yang diambil
                    if (!studentId || isNaN(studentId)) {
                         modalBody.innerHTML = '<p class="text-danger text-center">Error: ID pendaftar tidak valid pada tombol.</p>';
                         console.error('Invalid student ID found on button:', studentId);
                         return; // Hentikan proses jika ID tidak valid
                    }


                    // Tampilkan pesan loading di modal body
                    modalBody.innerHTML = '<p class="text-center"><div class="spinner-border text-info" role="status"><span class="visually-hidden">Loading...</span></div> Memuat detail...</p>';

                    // Lakukan permintaan AJAX ke file get_student_details.php
                    // Menggunakan ID pendaftar dari atribut data-student-id
                    fetch('get_student_details.php?id=' + studentId)
                        .then(response => {
                            console.log('Fetch response status:', response.status); // Debug status respon
                            if (!response.ok) {
                                // Jika respons bukan 2xx (misalnya 403, 404, 500)
                                console.error('Fetch failed, not OK status:', response.status, response.statusText); // Log error fetch
                                throw new Error('Network response was not ok. Status: ' + response.status + ' ' + response.statusText);
                            }
                            // Coba parse respons sebagai JSON
                            return response.json();
                        })
                        .then(data => {
                            console.log('Received data successfully:', data); // Debug data yang diterima dari PHP
                            if (data.success) {
                                var student = data.data;
                                // Buat HTML untuk menampilkan detail pendaftar berdasarkan data yang diterima
                                // PASTIKAN NAMA KUNCI (student.name, student.email, dll) COCOK DENGAN NAMA KOLOM DI TABEL students DAN JUGA COCOK DENGAN NAMA ALIAS DI QUERY get_student_details.php JIKA PAKAI ALIAS SELAIN NAMA KOLOM
                                var detailsHtml = `
                                    <table class="table table-bordered table-hover table-sm">
                                        <thead>
                                            <tr class="table-primary"><th colspan="2">Data Diri Siswa</th></tr>
                                        </thead>
                                        <tbody>
                                            <tr><th scope="row">ID Pendaftar:</th><td>${student.id || 'N/A'}</td></tr>
                                            <tr><th scope="row">Nama Lengkap:</th><td>${student.name || 'N/A'}</td></tr> {/* Pastikan nama kolom 'name' */}
                                            <tr><th scope="row">Email:</th><td>${student.email || 'N/A'}</td></tr> {/* Pastikan nama kolom 'email' */}
                                            <tr><th scope="row">Tanggal Lahir (DOB):</th><td>${student.dob || 'N/A'}</td></tr> {/* Pastikan nama kolom 'dob' */}
                                            <tr><th scope="row">Tempat Lahir:</th><td>${student.birthplace || 'N/A'}</td></tr> {/* Pastikan nama kolom 'birthplace' */}
                                            <tr><th scope="row">No. Telepon:</th><td>${student.phone || 'N/A'}</td></tr> {/* Pastikan nama kolom 'phone' */}
                                            <tr><th scope="row">Alamat Lengkap:</th><td>${student.address || 'N/A'}</td></tr> {/* Pastikan nama kolom 'address' */}
                                            <tr><th scope="row">Jenis Kelamin:</th><td>${student.gender || 'N/A'}</td></tr> {/* Pastikan nama kolom 'gender' */}
                                            <tr><th scope="row">Agama Siswa:</th><td>${student.religion_child || 'N/A'}</td></tr> {/* Pastikan nama kolom 'religion_child' */}
                                            <tr><th scope="row">Hobi Siswa:</th><td>${student.student_hobby || 'N/A'}</td></tr> {/* Pastikan nama kolom 'student_hobby' */}
                                            <tr><th scope="row">Cita-cita:</th><td>${student.goal || 'N/A'}</td></tr> {/* Pastikan nama kolom 'goal' */}
                                            <tr><th scope="row">Motivasi:</th><td>${student.motivation || 'N/A'}</td></tr> {/* Pastikan nama kolom 'motivation' */}
                                            <tr><th scope="row">Status Pendaftaran:</th><td>${student.status || 'N/A'}</td></tr> {/* Pastikan nama kolom 'status' */}
                                            <tr><th scope="row">Tanggal Daftar:</th><td>${student.created_at ? new Date(student.created_at).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' }) : 'N/A'}</td></tr> {/* Pastikan nama kolom 'created_at' */}
                                        </tbody>

                                        <thead><tr class="table-primary"><th colspan="2">Data Ayah Kandung</th></tr></thead>
                                        <tbody>
                                            <tr><th scope="row">Nama Ayah:</th><td>${student.father_name || 'N/A'}</td></tr> {/* Pastikan nama kolom father_name */}
                                            <tr><th scope="row">No. Telepon Ayah:</th><td>${student.father_phone || 'N/A'}</td></tr> {/* Pastikan nama kolom father_phone */}
                                            <tr><th scope="row">Pekerjaan Ayah:</th><td>${student.father_job || 'N/A'}</td></tr> {/* Pastikan nama kolom father_job */}
                                            <tr><th scope="row">Email Ayah:</th><td>${student.father_email || 'N/A'}</td></tr> {/* Pastikan nama kolom father_email */}
                                            <tr><th scope="row">Penghasilan Ayah:</th><td>${student.father_income || 'N/A'}</td></tr> {/* Pastikan nama kolom father_income */}
                                            <tr><th scope="row">Tempat Lahir Ayah:</th><td>${student.father_birthplace || 'N/A'}</td></tr> {/* Pastikan nama kolom father_birthplace */}
                                            <tr><th scope="row">Tanggal Lahir Ayah:</th><td>${student.father_dob || 'N/A'}</td></tr> {/* Pastikan nama kolom father_dob */}
                                            <tr><th scope="row">Agama Ayah:</th><td>${student.religion_father || 'N/A'}</td></tr> {/* Pastikan nama kolom religion_father */}
                                        </tbody>

                                        <thead><tr class="table-primary"><th colspan="2">Data Ibu Kandung</th></tr></thead>
                                        <tbody>
                                            <tr><th scope="row">Nama Ibu:</th><td>${student.mother_name || 'N/A'}</td></tr> {/* Pastikan nama kolom mother_name */}
                                            <tr><th scope="row">No. Telepon Ibu:</th><td>${student.mother_phone || 'N/A'}</td></tr> {/* Pastikan nama kolom mother_phone */}
                                            <tr><th scope="row">Pekerjaan Ibu:</th><td>${student.mother_job || 'N/A'}</td></tr> {/* Pastikan nama kolom mother_job */}
                                            <tr><th scope="row">Email Ibu:</th><td>${student.mother_email || 'N/A'}</td></tr> {/* Pastikan nama kolom mother_email */}
                                            <tr><th scope="row">Penghasilan Ibu:</th><td>${student.mother_income || 'N/A'}</td></tr> {/* Pastikan nama kolom mother_income */}
                                            <tr><th scope="row">Tempat Lahir Ibu:</th><td>${student.mother_birthplace || 'N/A'}</td></tr> {/* Pastikan nama kolom mother_birthplace */}
                                            <tr><th scope="row">Tanggal Lahir Ibu:</th><td>${student.mother_dob || 'N/A'}</td></tr> {/* Pastikan nama kolom mother_dob */}
                                            <tr><th scope="row">Agama Ibu:</th><td>${student.religion_mother || 'N/A'}</td></tr> {/* Pastikan nama kolom religion_mother */}
                                        </tbody>

                                        ${student.guardian_name || student.guardian_phone || student.guardian_relation || student.guardian_email || student.guardian_income || student.guardian_birthplace || student.guardian_dob || student.guardian_religion || student.ktp_guardian || student.kk_guardian ? `
                                        <thead><tr class="table-primary"><th colspan="2">Data Wali (Jika Ada)</th></tr></thead>
                                        <tbody>
                                            <tr><th scope="row">Nama Wali:</th><td>${student.guardian_name || 'N/A'}</td></tr> {/* Pastikan nama kolom guardian_name */}
                                            <tr><th scope="row">No. Telepon Wali:</th><td>${student.guardian_phone || 'N/A'}</td></tr> {/* Pastikan nama kolom guardian_phone */}
                                            <tr><th scope="row">Hubungan Wali:</th><td>${student.guardian_relation || 'N/A'}</td></tr> {/* Pastikan nama kolom guardian_relation */}
                                            <tr><th scope="row">Email Wali:</th><td>${student.guardian_email || 'N/A'}</td></tr> {/* Pastikan nama kolom guardian_email */}
                                            <tr><th scope="row">Penghasilan Wali:</th><td>${student.guardian_income || 'N/A'}</td></tr> {/* Pastikan nama kolom guardian_income */}
                                            <tr><th scope="row">Tempat Lahir Wali:</th><td>${student.guardian_birthplace || 'N/A'}</td></tr> {/* Pastikan nama kolom guardian_birthplace */}
                                            <tr><th scope="row">Tanggal Lahir Wali:</th><td>${student.guardian_dob || 'N/A'}</td></tr> {/* Pastikan nama kolom guardian_dob */}
                                            <tr><th scope="row">Agama Wali:</th><td>${student.guardian_religion || 'N/A'}</td></tr> {/* Pastikan nama kolom religion_guardian */}
                                        </tbody>
                                        ` : ''}

                                        <thead><tr class="table-primary"><th colspan="2">Dokumen Fisik</th></tr></thead>
                                        <tbody>
                                            <tr><th scope="row">File KK Siswa:</th><td>${getFileLink(student.kk_file)}</td></tr> {/* Pastikan nama kolom file KK */}
                                            <tr><th scope="row">File Akta Lahir:</th><td>${getFileLink(student.akta_lahir)}</td></tr> {/* Pastikan nama kolom file Akta */}
                                            <tr><th scope="row">File Nilai Rapor:</th><td>${getFileLink(student.nilai_rapor)}</td></tr> {/* Pastikan nama kolom file Rapor */}
                                            <tr><th scope="row">File KTP Ayah:</th><td>${getFileLink(student.ktp_father)}</td></tr> {/* Pastikan nama kolom file KTP Ayah */}
                                            <tr><th scope="row">File KTP Ibu:</th><td>${getFileLink(student.ktp_mother)}</td></tr> {/* Pastikan nama kolom file KTP Ibu */}
                                            ${student.ktp_guardian ? `<tr><th scope="row">File KTP Wali:</th><td>${getFileLink(student.ktp_guardian)}</td></tr>` : ''} {/* Pastikan nama kolom file KTP Wali */}
                                            ${student.kk_guardian ? `<tr><th scope="row">File KK Wali:</th><td>${getFileLink(student.kk_guardian)}</td></tr>` : ''} {/* Pastikan nama kolom file KK Wali */}
                                        </tbody>
                                    </table>
                                `;
                                modalBody.innerHTML = detailsHtml; // Isi modal body dengan HTML detail
                            } else {
                                // Jika PHP mengembalikan success: false
                                console.error('API returned success:false:', data.message); // Log pesan error dari PHP
                                modalBody.innerHTML = `<p class="text-danger text-center">${data.message || 'Gagal memuat detail.'}</p>`; // Tampilkan pesan error dari PHP jika ada
                            }
                        })
                        .catch(error => {
                            // Jika permintaan fetch gagal sama sekali
                            console.error('Fetch error caught:', error); // Log error yang ditangkap
                            modalBody.innerHTML = '<p class="text-danger text-center">Gagal memuat detail. Silakan coba lagi.</p>'; // Pesan error generik
                        });
                });
            });

            // JavaScript untuk generate PDF (tetap sama)
             function downloadPDF() {
                 const elementToPrint = document.getElementById("dataTableContainer");
                 const originalTitle = document.title;
                 const pdfFilename = 'Data Pendaftar SMA Ibnu Aqil - Dicetak ' + new Date().toLocaleDateString("id-ID") + '.pdf';
                 document.title = pdfFilename;

                 const elementsToHideForPDF = elementToPrint.querySelectorAll('.action-buttons, .filter-search-container form, .mb-3.text-center.text-md-start .btn:not(.btn-danger)'); // Hide filter/search form and excel/add buttons
                  // Remove the "Download PDF" button itself from being hidden (it's already clicked) or just hide all action buttons
                elementsToHideForPDF.forEach(el => el.style.display = 'none');


                 const opt = {
                     margin: [10, 10, 10, 10],
                     filename: pdfFilename,
                     image: { type: 'jpeg', quality: 0.98 },
                     html2canvas: { scale: 2, logging: true, useCORS: true, scrollY: 0 }, // ScrollY 0 untuk mencegah masalah scroll
                     jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
                 };

                 html2pdf().from(elementToPrint).set(opt).save().then(function () {
                     elementsToHideForPDF.forEach(el => el.style.display = ''); // Show hidden elements back
                     document.title = originalTitle;
                 }).catch(function (error) {
                     console.error("Error saat generate PDF:", error);
                     elementsToHideForPDF.forEach(el => el.style.display = ''); // Show hidden elements back
                     document.title = originalTitle;
                     alert("Gagal membuat PDF. Silakan cek konsol (F12) untuk detail error.");
                 });
             }
            // Tombol download PDF memanggil fungsi di atas
             document.querySelector('.mb-3.text-center.text-md-start .btn-danger').addEventListener('click', function() {
                 downloadPDF();
             });


        }); // Penutup DOMContentLoaded

        // Cegah akses halaman admin dari cache (BACK button) - Bisa diletakkan di luar DOMContentLoaded
        // Note: Ini mungkin mengganggu jika halaman admin memiliki state complex yang disimpan di history
        // Pertimbangkan alternatif lain jika ini jadi masalah
        if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_BACK_FORWARD) {
            window.location.href = 'logout.php'; // Arahkan ke logout jika diakses via back/forward cache
        }

        // Tambahan perlindungan jika class "admin-authenticated" tidak ada - Bisa diletakkan di luar DOMContentLoaded
        // Memastikan body memiliki class ini setelah login sukses
         window.onload = function () {
             if (!document.body.classList.contains('admin-authenticated')) {
                  // Hanya redirect jika belum di-redirect oleh PHP di atas
                  // Cek sederhana untuk menghindari double redirect
                  if (window.location.pathname.indexOf('login.php') === -1) { // Jika bukan sudah di halaman login
                     // window.location.href = 'logout.php'; // Logika logout sudah ada di PHP awal
                  }
              }
          };


    </script>
</body>
</html>