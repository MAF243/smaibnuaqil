<?php
// FILE: admin_manage_facilities.php

// 0. Log Awal Eksekusi
error_log("ADMIN_MANAGE_FACILITIES: Script execution started at " . date("Y-m-d H:i:s"));

// 1. Mulai Sesi (WAJIB PALING ATAS)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    error_log("ADMIN_MANAGE_FACILITIES: Session started.");
} else {
    error_log("ADMIN_MANAGE_FACILITIES: Session already active.");
}

// 2. Pengaturan Error Reporting untuk Development
error_reporting(E_ALL);
ini_set('display_errors', 1); 

// 3. Cegah Cache
if (!headers_sent()) {
    header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}

// 4. Cek Login Admin
if (!isset($_SESSION['admin'])) {
    $_SESSION['error_message'] = "Anda harus login untuk mengakses halaman ini.";
    if (!headers_sent()) {
        header("Location: login.php");
    } else {
        error_log("ADMIN_MANAGE_FACILITIES: CRITICAL - Cannot redirect to login.php, headers already sent.");
        echo "<!DOCTYPE html><html><head><title>Redirect Error</title></head><body>Harap login terlebih dahulu. <a href='login.php'>Ke Halaman Login</a> (Redirect gagal karena output sudah dimulai)</body></html>";
    }
    exit;
}

// 5. Sertakan File Konfigurasi Database
if (file_exists('config.php')) {
    include_once 'config.php';
} else {
    error_log("CRITICAL: config.php tidak ditemukan di admin_manage_facilities.php. Script terminated.");
    exit("<!DOCTYPE html><html><head><title>Error Konfigurasi</title></head><body>Error Kritis: File konfigurasi (config.php) tidak ditemukan. Aplikasi tidak dapat berjalan. Harap hubungi administrator.</body></html>");
}

// 6. Pastikan Koneksi $conn Berhasil
if (!isset($conn) || !($conn instanceof mysqli)) {
    $db_error_msg_facilities = '$conn tidak terdefinisi atau bukan objek mysqli setelah config.php.';
    if (isset($conn_connect_error_message_from_config) && !empty($conn_connect_error_message_from_config)){ 
        $db_error_msg_facilities = $conn_connect_error_message_from_config;
    }
    error_log("CRITICAL: Koneksi database tidak valid atau tidak terdefinisi di admin_manage_facilities.php. Pesan: " . $db_error_msg_facilities);
    exit("<!DOCTYPE html><html><head><title>Error Database Kritis</title></head><body>Error Kritis: Koneksi ke database tidak dapat dibuat atau tidak valid. Periksa file 'config.php' dan log server. Pesan: ".htmlspecialchars($db_error_msg_facilities).". Harap hubungi administrator.</body></html>");
} elseif ($conn->connect_error) {
    $db_error_msg_facilities = $conn->connect_error;
    error_log("CRITICAL: Koneksi database gagal (connect_error) di admin_manage_facilities.php: " . $db_error_msg_facilities);
    exit("<!DOCTYPE html><html><head><title>Error Koneksi Database</title></head><body>Error: Koneksi database gagal. Detail: ".htmlspecialchars($db_error_msg_facilities).". Harap hubungi administrator.</body></html>");
}

// --- Pengaturan $site_url ---
if (!isset($site_url)) {
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $protocol = "https://";
    } else {
        $protocol = "http://";
    }
    if (isset($_SERVER['HTTP_HOST'])) {
        $site_url = $protocol . $_SERVER['HTTP_HOST'];
    } else {
        $site_url = "https://smaibnuaqil.my.id"; // Fallback
    }
}
$site_url = rtrim($site_url, '/');
// --- AKHIR Pengaturan $site_url ---

// Definisikan path upload default untuk fasilitas (relatif dari web root)
// $facilities_default_upload_base_web = '/uploads/facilities_images/'; // Untuk sementara tidak digunakan dalam logika gambar yang lebih simpel
// error_log("ADMIN_MANAGE_FACILITIES: \$facilities_default_upload_base_web DISET ke: " . $facilities_default_upload_base_web);

// Path default untuk placeholder jika gambar fasilitas tidak ditemukan atau path kosong
// Dibuat relatif seperti contoh galeri, tapi pastikan path ini benar dari lokasi skrip ini.
// Atau, untuk lebih aman, kita tetap pakai URL lengkap untuk placeholder.
$placeholder_facility_img = $site_url . '/public/asset/placeholder-facility.jpg'; 
// Jika placeholder galeri Anda ('public/asset/placeholder-gallery.jpg') berfungsi relatif,
// Anda bisa mencoba: $placeholder_facility_img = 'public/asset/placeholder-facility.jpg';
// Pastikan file placeholder-facility.jpg ada di public_html/public/asset/


// Ambil data admin untuk navbar
// ... (Logika $admin_photo_url dan $admin_name seperti di versi Canvas sebelumnya) ...
$admin_name = $_SESSION['admin_name'] ?? "Admin";
$default_avatar_web_path = '/public/asset/default_admin_avatar.png'; 
$admin_photo_url = $site_url . $default_avatar_web_path; 
$specific_photo_found_and_valid = false;
if (isset($_SESSION['admin_photo_url']) && !empty($_SESSION['admin_photo_url'])) {
    $session_path_admin = $_SESSION['admin_photo_url'];
    if (preg_match('/^https?:\/\//i', $session_path_admin)) {
        $admin_photo_url = htmlspecialchars($session_path_admin);
        $specific_photo_found_and_valid = true;
    } elseif (strpos($session_path_admin, '/') === 0) {
        $server_check_path_admin = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $session_path_admin;
        if (file_exists($server_check_path_admin)) {
            $admin_photo_url = htmlspecialchars($site_url . $session_path_admin);
            $specific_photo_found_and_valid = true;
        }
    } else {
        $admin_photos_default_base = '/uploads/admin_photos/';
        $potential_web_path_admin = rtrim($admin_photos_default_base, '/') . '/' . ltrim($session_path_admin, '/');
        $server_check_path_admin = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $potential_web_path_admin;
        if (file_exists($server_check_path_admin)) {
            $admin_photo_url = htmlspecialchars($site_url . $potential_web_path_admin);
            $specific_photo_found_and_valid = true;
        }
    }
}
if (!$specific_photo_found_and_valid && isset($_SESSION['admin_photo_filename']) && !empty($_SESSION['admin_photo_filename'])) {
    $filename_admin = basename(htmlspecialchars($_SESSION['admin_photo_filename']));
    $admin_photos_default_base_file = '/uploads/admin_photos/';
    $full_web_path_admin_file = rtrim($admin_photos_default_base_file, '/') . '/' . $filename_admin;
    $server_check_path_admin_file = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $full_web_path_admin_file;
    if (file_exists($server_check_path_admin_file)) {
        $admin_photo_url = htmlspecialchars($site_url . $full_web_path_admin_file);
    }
}
$current_page_filename = basename($_SERVER['PHP_SELF']);


// Ambil pesan status dari session
$status_message_facilities = ''; $alert_class_facilities = ''; 
if (isset($_SESSION['status_message']) && isset($_SESSION['alert_class'])) { /* ... */ }

// Ambil semua fasilitas untuk ditampilkan
$facilities_list_admin = []; $error_message_display_facilities = null; 
$sql_all_facilities = "SELECT id, name, LEFT(short_description, 150) AS description_preview, modal_image_path AS image_path, created_at FROM facilities ORDER BY created_at DESC";
$result_all_facilities = $conn->query($sql_all_facilities);
if (!$result_all_facilities) { /* ... (penanganan error query) ... */ }
if ($result_all_facilities && $result_all_facilities->num_rows > 0) { 
    while($row = $result_all_facilities->fetch_assoc()){ $facilities_list_admin[] = $row; }
    $result_all_facilities->free();
} elseif ($result_all_facilities) { $result_all_facilities->free(); }

// CSRF Token untuk delete
if (!isset($_SESSION['csrf_token_facilities_delete']) || empty($_SESSION['csrf_token_facilities_delete'])) {
    if (function_exists('random_bytes')) {
        $_SESSION['csrf_token_facilities_delete'] = bin2hex(random_bytes(32));
    } else { $_SESSION['csrf_token_facilities_delete'] = bin2hex(openssl_random_pseudo_bytes(32)); }
}
$csrf_token_delete_facilities = $_SESSION['csrf_token_facilities_delete'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Fasilitas - Admin SMA Ibnu Aqil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/jpg" href="<?php echo htmlspecialchars($site_url); ?>/public/asset/logo.jpg">
    <style>
        body { background-color: #f8f9fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-main { margin: 20px auto; max-width: 95%; background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.07); }
        .page-title { margin-bottom: 25px; text-align: center; color: #fd7e14; font-weight: 600; font-size: 1.85rem; }
        .footer { text-align: center; padding: 20px; background-color: #212529; color: white; margin-top: 40px; }
        .action-buttons .btn { margin-right: 5px; margin-bottom: 5px; padding: 0.3rem 0.6rem; font-size:0.8rem; }
        th, td { vertical-align: middle; font-size: 0.9rem; }
        .facility-thumb { width: 120px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #dee2e6; padding:2px; }
        #livePreviewContainerFacility { display: none; position: fixed; bottom: 20px; right: 20px; width: 380px; height: 550px; border: 2px solid #fd7e14; background-color: white; box-shadow: 0 5px 20px rgba(0,0,0,0.25); z-index: 1060; border-radius: 8px; overflow: hidden; flex-direction: column; }
        #livePreviewHeader { padding: 8px 10px; background-color: #fd7e14; color: white; font-size: 0.9em; display: flex; justify-content: space-between; align-items: center; }
        #livePreviewHeader .btn-close-white { padding: 0.25rem 0.5rem; filter: brightness(0) invert(1); }
        #livePreviewFrameFacility { width: 100%; height: calc(100% - 38px); border: none; }
    </style>
</head>
<body class="admin-authenticated">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
        <div class="container-fluid">
           <a class="navbar-brand" href="beranda_admin.php"> 
                <img src="public/asset/user.png" alt="Foto Admin" width="30" height="30" class="d-inline-block align-text-top rounded-circle me-2">
                adminq (Admin Panel)
            </a>
               
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

    <div class="container container-main">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h2 class="page-title text-warning mb-0 me-3"><i class="bi bi-building-fill-gear"></i> Kelola Fasilitas</h2>
             <div class="mt-2 mt-md-0">
                 <button class="btn btn-outline-warning btn-sm" id="togglePreviewBtnFacility" title="Tampilkan/Sembunyikan Live Preview">
                    <i class="bi bi-display-fill"></i> Live Preview
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="refreshPreviewBtnFacility" style="display:none;" title="Muat Ulang Preview">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
                <a href="admin_add_edit_facility.php" class="btn btn-primary ms-2"><i class="bi bi-plus-circle-fill"></i> Tambah Fasilitas</a>
            </div>
        </div>

        <?php if (!empty($status_message_facilities)): ?> <?php endif; ?>
        <?php if (isset($error_message_display_facilities) && !empty($error_message_display_facilities)): ?> <?php endif; ?>


        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%; text-align:center;">ID</th>
                                <th style="width: 20%; text-align:center;">Gambar</th>
                                <th style="width: 25%;">Nama Fasilitas</th>
                                <th style="width: 30%;">Deskripsi Singkat</th>
                                <th style="width: 10%;">Tgl Dibuat</th>
                                <th style="width: 10%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($facilities_list_admin) && !$error_message_display_facilities): ?>
                                <tr><td colspan="6" class="text-center fst-italic py-3">Belum ada fasilitas. Silakan tambahkan fasilitas baru.</td></tr>
                            <?php elseif (!empty($facilities_list_admin)): ?>
                                <?php foreach ($facilities_list_admin as $facility_item): ?>
                                    <tr>
                                        <td style="text-align:center;"><?php echo htmlspecialchars($facility_item['id'] ?? ''); ?></td>
                                        <td style="text-align:center;">
                                            <?php
                                            // --- AWAL LOGIKA GAMBAR FASILITAS (MENIRU PENDEKATAN GALERI SEDERHANA) ---
                                            $facility_img_src_to_display = $placeholder_facility_img; // Default ke placeholder (URL lengkap)
                                            $db_path_from_facility = $facility_item['image_path'] ?? null; // 'image_path' adalah alias dari 'modal_image_path'
                                            $facility_id_for_log = $facility_item['id'] ?? 'UNKNOWN_ID';

                                            error_log("FAC_IMG_DIRECT_LOGIC (ID: {$facility_id_for_log}): Mencoba path dari DB: '{$db_path_from_facility}'");

                                            if (!empty($db_path_from_facility)) {
                                                // Path yang akan dicek oleh file_exists().
                                                // Jika $db_path_from_facility sudah merupakan path yang bisa dicek langsung (misal, relatif dari skrip),
                                                // atau path absolut sistem file, $path_for_file_check akan sama.
                                                // Jika $db_path_from_facility adalah path web-root (dimulai '/'), kita tambahkan DOCUMENT_ROOT.
                                                $path_for_file_check = $db_path_from_facility;
                                                if (strpos($db_path_from_facility, '/') === 0 && strpos($db_path_from_facility, '//') !== 0) { // Path absolut web, bukan URL
                                                    $path_for_file_check = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $db_path_from_facility;
                                                }
                                                // Jika $db_path_from_facility TIDAK dimulai dengan '/' dan bukan URL, 
                                                // file_exists() akan mengeceknya relatif terhadap direktori skrip saat ini.

                                                error_log("FAC_IMG_DIRECT_LOGIC (ID: {$facility_id_for_log}): Path untuk file_exists(): '{$path_for_file_check}'");

                                                if (file_exists($path_for_file_check)) {
                                                    // Jika file ada, kita gunakan $db_path_from_facility sebagai dasar untuk src.
                                                    // Perlu dipastikan ini menjadi URL yang benar.
                                                    if (preg_match('/^https?:\/\//i', $db_path_from_facility)) { // Jika sudah URL lengkap
                                                        $facility_img_src_to_display = htmlspecialchars($db_path_from_facility);
                                                    } elseif (strpos($db_path_from_facility, '/') === 0) { // Jika path absolut dari web root
                                                        $facility_img_src_to_display = htmlspecialchars($site_url . $db_path_from_facility);
                                                    } else { // Jika path relatif (misal 'uploads/facilities_images/file.jpg')
                                                             // yang berhasil dicek file_exists (relatif dari skrip).
                                                             // Untuk src, kita bisa coba gunakan langsung atau bentuk dengan $site_url
                                                             // Jika script ada di root dan pathnya 'uploads/...' maka langsung bisa.
                                                             // Agar lebih aman, selalu bentuk URL lengkap jika bukan sudah URL.
                                                        $facility_img_src_to_display = htmlspecialchars($site_url . '/' . ltrim($db_path_from_facility, '/'));
                                                    }
                                                    error_log("FAC_IMG_DIRECT_LOGIC (ID: {$facility_id_for_log}): File ADA. SRC Digunakan: '{$facility_img_src_to_display}'");
                                                } else {
                                                    error_log("FAC_IMG_DIRECT_LOGIC_WARNING (ID: {$facility_id_for_log}): File TIDAK ADA di '{$path_for_file_check}'. Menggunakan placeholder. (Path DB: '{$db_path_from_facility}')");
                                                }
                                            } else {
                                                 error_log("FAC_IMG_DIRECT_LOGIC (ID: {$facility_id_for_log}): Path gambar dari DB kosong. Menggunakan placeholder.");
                                            }
                                            // --- AKHIR LOGIKA GAMBAR FASILITAS ---
                                            ?>
                                            <img src="<?php echo $facility_img_src_to_display; ?>?t=<?php echo time(); // Cache buster ?>" alt="<?php echo htmlspecialchars($facility_item['name'] ?? 'Gambar Fasilitas'); ?>" class="facility-thumb img-thumbnail">
                                        </td>
                                        <td><?php echo htmlspecialchars($facility_item['name'] ?? 'Nama Tidak Tersedia'); ?></td>
                                        <td><?php echo htmlspecialchars($facility_item['description_preview'] ?? 'Deskripsi Tidak Tersedia'); ?>...</td>
                                        <td><?php echo htmlspecialchars(date('d M Y', strtotime($facility_item['created_at'] ?? 'now'))); ?></td>
                                        <td class="text-center action-buttons">
                                            <a href="admin_add_edit_facility.php?id=<?php echo htmlspecialchars($facility_item['id'] ?? ''); ?>" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                            <button type="button" class="btn btn-sm btn-danger" title="Hapus" onclick="confirmDeleteFacility(<?php echo htmlspecialchars($facility_item['id'] ?? ''); ?>, '<?php echo $csrf_token_delete_facilities; ?>', '<?php echo htmlspecialchars(addslashes($facility_item['name'] ?? 'Fasilitas Ini')); ?>');"><i class="bi bi-trash-fill"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="livePreviewContainerFacility"> </div>
    <footer class="footer"> </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const siteUrlForJsFacilities = '<?php echo htmlspecialchars($site_url); ?>';
        const facilitiesUploadPathWebForJs = '<?php echo htmlspecialchars(rtrim($facilities_default_upload_base_web, '/')); ?>'; 
        // ... (sisa JavaScript Anda dari kode asli) ...
        function confirmDeleteFacility(facilityId, csrfToken, facilityName) { /* ... */ }
        const togglePreviewBtnFacility = document.getElementById('togglePreviewBtnFacility'); /* ... */
        if(togglePreviewBtnFacility) { /* ... */ }
        const closePreviewBtnFacility = document.getElementById('closePreviewBtnFacility'); /* ... */
        if(closePreviewBtnFacility) { /* ... */ }
        const refreshPreviewBtnFacility = document.getElementById('refreshPreviewBtnFacility'); /* ... */
        if(refreshPreviewBtnFacility) { /* ... */ }
        window.onload = function () { /* ... */ };
    </script>
</body>
</html>
```