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
ini_set('display_errors', 1); // Penting untuk melihat error langsung di browser saat development

// 3. Cegah Cache
if (!headers_sent()) {
    header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    // error_log("ADMIN_MANAGE_FACILITIES: Cache headers sent."); // Bisa di-uncomment jika perlu debug header
} else {
    error_log("ADMIN_MANAGE_FACILITIES: WARNING - Cache headers not sent because output already started.");
}


// 4. Cek Login Admin
if (!isset($_SESSION['admin'])) {
    error_log("ADMIN_MANAGE_FACILITIES: Admin session not set. Redirecting to login.");
    $_SESSION['error_message'] = "Anda harus login untuk mengakses halaman ini.";
    if (!headers_sent()) {
        header("Location: login.php");
    } else {
        error_log("ADMIN_MANAGE_FACILITIES: CRITICAL - Cannot redirect to login.php, headers already sent.");
        echo "<!DOCTYPE html><html><head><title>Redirect Error</title></head><body>Harap login terlebih dahulu. <a href='login.php'>Ke Halaman Login</a> (Redirect gagal karena output sudah dimulai)</body></html>";
    }
    exit;
}
error_log("ADMIN_MANAGE_FACILITIES: Admin session check passed.");

// 5. Sertakan File Konfigurasi Database
if (file_exists('config.php')) {
    include_once 'config.php';
    error_log("ADMIN_MANAGE_FACILITIES: config.php included.");
    if (isset($conn) && ($conn instanceof mysqli) && !$conn->connect_error) {
        error_log("ADMIN_MANAGE_FACILITIES: \$conn is a valid mysqli connection after config.php include.");
    } elseif (isset($conn) && ($conn instanceof mysqli) && $conn->connect_error) {
        error_log("ADMIN_MANAGE_FACILITIES: CRITICAL - \$conn is a mysqli object BUT connection failed in config.php. Error: " . $conn->connect_error);
    } elseif (!isset($conn)) {
        error_log("ADMIN_MANAGE_FACILITIES: CRITICAL - \$conn is NOT SET after config.php include. Check config.php for errors or if it defines \$conn.");
    } else {
        error_log("ADMIN_MANAGE_FACILITIES: CRITICAL - \$conn is set but NOT a mysqli object after config.php include. Type: " . gettype($conn));
    }
} else {
    error_log("CRITICAL: config.php tidak ditemukan di admin_manage_facilities.php. Script terminated.");
    exit("<!DOCTYPE html><html><head><title>Error Konfigurasi</title></head><body>Error Kritis: File konfigurasi (config.php) tidak ditemukan. Aplikasi tidak dapat berjalan. Harap hubungi administrator.</body></html>");
}

// 6. Pastikan Koneksi $conn Berhasil (Pengecekan Ulang yang Lebih Detail)
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
error_log("ADMIN_MANAGE_FACILITIES: \$conn check PASSED before facilities query. Connection appears OK.");


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
error_log("ADMIN_MANAGE_FACILITIES: \$site_url set to: " . $site_url);
// --- AKHIR Pengaturan $site_url ---

// Definisikan path upload khusus untuk fasilitas (relatif dari web root)
$facilities_upload_path_web = '/uploads/facilities_images/'; 
error_log("ADMIN_MANAGE_FACILITIES: \$facilities_upload_path_web set to: " . $facilities_upload_path_web);


// Ambil data admin untuk navbar
$admin_name = $_SESSION['admin_name'] ?? "Admin";
$default_avatar_path = '/public/asset/default_admin_avatar.png';
$admin_photo_url = $site_url . $default_avatar_path;

if (isset($_SESSION['admin_photo_url']) && !empty($_SESSION['admin_photo_url'])) {
    $photo_path_from_session = $_SESSION['admin_photo_url'];
    if (preg_match('/^https?:\/\//', $photo_path_from_session)) {
        $admin_photo_url = htmlspecialchars($photo_path_from_session);
    } elseif (strpos($photo_path_from_session, $_SERVER['DOCUMENT_ROOT']) === 0 && file_exists($photo_path_from_session)) {
        $relative_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $photo_path_from_session);
        $admin_photo_url = $site_url . $relative_path;
    } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($photo_path_from_session, '/'))) {
         $admin_photo_url = $site_url . '/' . ltrim(htmlspecialchars($photo_path_from_session), '/');
    }
} elseif (isset($_SESSION['admin_photo_filename']) && !empty($_SESSION['admin_photo_filename'])) {
    $potential_admin_photo_web_path = '/uploads/admin_photos/' . basename(htmlspecialchars($_SESSION['admin_photo_filename']));
    if (file_exists(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $potential_admin_photo_web_path)) {
        $admin_photo_url = $site_url . $potential_admin_photo_web_path;
    }
}
$current_page_filename = basename($_SERVER['PHP_SELF']);

// Ambil pesan status dari session
$status_message_facilities = '';
$alert_class_facilities = '';
if (isset($_SESSION['status_message']) && isset($_SESSION['alert_class'])) {
    $status_message_facilities = htmlspecialchars($_SESSION['status_message']);
    $alert_class_facilities = htmlspecialchars($_SESSION['alert_class']);
    unset($_SESSION['status_message']);
    unset($_SESSION['alert_class']);
}

// Ambil semua fasilitas untuk ditampilkan
$facilities_list_admin = [];
$error_message_display_facilities = null;

$sql_all_facilities = "SELECT id, name, LEFT(short_description, 150) AS description_preview, modal_image_path AS image_path, created_at FROM facilities ORDER BY created_at DESC";
error_log("ADMIN_MANAGE_FACILITIES: SQL Query for facilities: " . $sql_all_facilities);
$result_all_facilities = $conn->query($sql_all_facilities);

if (!$result_all_facilities) {
    $detailed_error = $conn ? htmlspecialchars($conn->error) : 'Koneksi $conn tidak valid';
    error_log("ERROR (admin_manage_facilities): Query gagal - " . $detailed_error . " | Query: " . $sql_all_facilities);
    $error_message_display_facilities = "Gagal mengambil daftar fasilitas. Error: " . $detailed_error;
}


if ($result_all_facilities && $result_all_facilities->num_rows > 0) {
    while($row = $result_all_facilities->fetch_assoc()){
        $facilities_list_admin[] = $row;
    }
    $result_all_facilities->free();
} elseif ($result_all_facilities) { 
    $result_all_facilities->free();
}

// CSRF Token untuk delete
if (empty($_SESSION['csrf_token_facilities_delete'])) {
    if (function_exists('random_bytes')) {
        $_SESSION['csrf_token_facilities_delete'] = bin2hex(random_bytes(32));
    } else { 
        $_SESSION['csrf_token_facilities_delete'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}
$csrf_token_delete_facilities = $_SESSION['csrf_token_facilities_delete'];
error_log("ADMIN_MANAGE_FACILITIES: Script PHP execution completed before HTML output.");
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
                <img src="<?php echo htmlspecialchars($admin_photo_url); ?>" alt="Foto Admin" width="35" height="35" class="d-inline-block align-text-top rounded-circle me-2">
                <?php echo htmlspecialchars($admin_name); ?> (Admin Panel)
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
                        <a class="nav-link <?php echo ($current_page_filename == 'admin_manage_facilities.php' ? 'active' : ''); ?>" aria-current="page" href="admin_manage_facilities.php"><i class="bi bi-building-fill-gear me-1"></i> Fasilitas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page_filename == 'admin_manage_news.php' ? 'active' : ''); ?>" href="admin_manage_news.php"><i class="bi bi-newspaper me-1"></i> Berita</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page_filename == 'admin_manage_gallery.php' ? 'active' : ''); ?>" href="admin_manage_gallery.php"><i class="bi bi-images me-1"></i> Galeri</a>
                    </li>
                     <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo (strpos($current_page_filename, 'admin_hero_settings.php') !== false || strpos($current_page_filename, 'admin_site_settings.php') !== false ? 'active' : ''); ?>" href="#" id="settingsAdminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-sliders me-1"></i> Pengaturan
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="settingsAdminDropdown">
                            <li><a class="dropdown-item <?php echo ($current_page_filename == 'admin_hero_settings.php' ? 'active' : ''); ?>" href="admin_hero_settings.php"><i class="bi bi-image-alt me-2"></i>Gambar Hero</a></li>
                            <li><a class="dropdown-item <?php echo ($current_page_filename == 'admin_site_settings.php' ? 'active' : ''); ?>" href="admin_site_settings.php"><i class="bi bi-info-circle-fill me-2"></i>Info Situs</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars($site_url); ?>/index.php" target="_blank" title="Buka situs publik di tab baru">
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

        <?php if (!empty($status_message_facilities)): ?>
            <div class="alert <?php echo htmlspecialchars($alert_class_facilities); ?> alert-dismissible fade show" role="alert">
                <?php echo $status_message_facilities; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message_display_facilities) && !empty($error_message_display_facilities)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message_display_facilities; ?>
            </div>
        <?php endif; ?>

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
                                            // --- LOGIKA URL GAMBAR DENGAN LOGGING DETAIL ---
                                            $facility_img_url = $site_url . '/public/asset/placeholder-facility.jpg'; // Default placeholder
                                            $facility_id_for_log = $facility_item['id'] ?? 'UNKNOWN_ID';
                                            error_log("DEBUG_IMG (Facility ID: $facility_id_for_log): Memulai proses URL gambar. Site URL: '$site_url', Facilities Upload Path Web: '$facilities_upload_path_web'"); 
                                            
                                            if (!empty($facility_item['image_path'])) {
                                                $db_path = $facility_item['image_path'];
                                                error_log("DEBUG_IMG (Facility ID: $facility_id_for_log): Path dari DB (modal_image_path as image_path): '$db_path'");

                                                if (preg_match('/^https?:\/\//i', $db_path)) { // LOGIKA 1: URL Lengkap
                                                    $facility_img_url = htmlspecialchars($db_path);
                                                    error_log("DEBUG_IMG (Facility ID: $facility_id_for_log): [L1] Path adalah URL lengkap. URL Final: '$facility_img_url'");
                                                } 
                                                elseif (strpos($db_path, '/') === 0) { // LOGIKA 2: Path Absolut dari Web Root
                                                    $file_system_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $db_path;
                                                    error_log("DEBUG_IMG (Facility ID: $facility_id_for_log): [L2] Path adalah absolut web root. Cek file system: '$file_system_path'");
                                                    if (file_exists($file_system_path)) {
                                                         $facility_img_url = htmlspecialchars($site_url . $db_path);
                                                         error_log("DEBUG_IMG (Facility ID: $facility_id_for_log): [L2] File ditemukan (abs). URL Final: '$facility_img_url'");
                                                    } else {
                                                         error_log("WARNING_IMG (Facility ID: $facility_id_for_log): [L2] Gambar (abs path) TIDAK DITEMUKAN di: '$file_system_path' (dari db: '$db_path')");
                                                    }
                                                }
                                                else { // LOGIKA 3: Path Relatif (dari $facilities_upload_path_web)
                                                    $full_web_path = rtrim($facilities_upload_path_web, '/') . '/' . ltrim($db_path, '/');
                                                    $file_system_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $full_web_path;
                                                    error_log("DEBUG_IMG (Facility ID: $facility_id_for_log): [L3] Path adalah relatif. Cek file system: '$file_system_path' (Base: '$facilities_upload_path_web', DB Path: '$db_path')");
                                                    
                                                    if (file_exists($file_system_path)) {
                                                        $facility_img_url = htmlspecialchars($site_url . $full_web_path);
                                                        error_log("DEBUG_IMG (Facility ID: $facility_id_for_log): [L3] File ditemukan (rel). URL Final: '$facility_img_url'");
                                                    } else {
                                                         error_log("WARNING_IMG (Facility ID: $facility_id_for_log): [L3] Gambar (rel path) TIDAK DITEMUKAN di: '$file_system_path' (dari db: '$db_path', base path: '$facilities_upload_path_web')");
                                                    }
                                                }
                                            } else {
                                                error_log("DEBUG_IMG (Facility ID: $facility_id_for_log): Kolom image_path (dari modal_image_path) kosong atau null. Menggunakan placeholder.");
                                            }
                                            // --- AKHIR LOGIKA URL GAMBAR DENGAN LOGGING DETAIL ---
                                            ?>
                                            <img src="<?php echo $facility_img_url; ?>?t=<?php echo time(); // Cache buster ?>" alt="<?php echo htmlspecialchars($facility_item['name'] ?? 'Gambar Fasilitas'); ?>" class="facility-thumb img-thumbnail">
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

    <div id="livePreviewContainerFacility"> 
        <div id="livePreviewHeader">
            <strong><i class="bi bi-display"></i> Live Preview: <span id="previewTargetTitleFacility">fasilitas.php</span></strong>
            <button type="button" class="btn-close btn-close-white btn-sm" aria-label="Close" id="closePreviewBtnFacility" title="Tutup Preview"></button>
        </div>
        <iframe id="livePreviewFrameFacility" src="<?php echo htmlspecialchars($site_url); ?>/fasilitas.php"></iframe>
    </div>

    <footer class="footer">
        <div class="container"> <p>&copy; <?php echo date("Y"); ?> SMA Ibnu Aqil. Admin Panel. Hak Cipta Dilindungi.</p> </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const siteUrlForJsFacilities = '<?php echo htmlspecialchars($site_url); ?>';
        const facilitiesUploadPathWebForJs = '<?php echo htmlspecialchars(rtrim($facilities_upload_path_web, '/')); ?>';

        function confirmDeleteFacility(facilityId, csrfToken, facilityName) {
            if (confirm('Apakah Anda yakin ingin menghapus fasilitas: "' + facilityName.replace(/\\'/g, "'").replace(/\\"/g, '"') + '"? Tindakan ini tidak dapat diurungkan.')) {
                window.location.href = 'admin_delete_facility.php?id=' + facilityId + '&token=' + csrfToken;
            }
        }

        const togglePreviewBtnFacility = document.getElementById('togglePreviewBtnFacility');
        const livePreviewContainerFacility = document.getElementById('livePreviewContainerFacility');
        const livePreviewFrameFacility = document.getElementById('livePreviewFrameFacility');
        const closePreviewBtnFacility = document.getElementById('closePreviewBtnFacility');
        const refreshPreviewBtnFacility = document.getElementById('refreshPreviewBtnFacility');
        const previewTargetTitleFacilityJS = document.getElementById('previewTargetTitleFacility');
        let currentPreviewUrlFacility = siteUrlForJsFacilities + '/fasilitas.php'; 

        if(togglePreviewBtnFacility && livePreviewContainerFacility && livePreviewFrameFacility) {
            togglePreviewBtnFacility.addEventListener('click', function() {
                if (livePreviewContainerFacility.style.display === 'none' || livePreviewContainerFacility.style.display === '') {
                    livePreviewContainerFacility.style.display = 'flex';
                    livePreviewFrameFacility.src = currentPreviewUrlFacility + (currentPreviewUrlFacility.includes('?') ? '&' : '?') + 'timestamp=' + new Date().getTime();
                    if(refreshPreviewBtnFacility) refreshPreviewBtnFacility.style.display = 'inline-block';
                    if(previewTargetTitleFacilityJS) previewTargetTitleFacilityJS.textContent = currentPreviewUrlFacility.replace(siteUrlForJsFacilities + '/', '').split('?')[0] || 'fasilitas.php';
                } else {
                    livePreviewContainerFacility.style.display = 'none';
                    if(refreshPreviewBtnFacility) refreshPreviewBtnFacility.style.display = 'none';
                }
            });
        }

        if(closePreviewBtnFacility && livePreviewContainerFacility) {
            closePreviewBtnFacility.addEventListener('click', function() {
                livePreviewContainerFacility.style.display = 'none';
                if(refreshPreviewBtnFacility) refreshPreviewBtnFacility.style.display = 'none';
            });
        }

        if(refreshPreviewBtnFacility && livePreviewFrameFacility) {
            refreshPreviewBtnFacility.addEventListener('click', function() {
                if (livePreviewFrameFacility.contentWindow) {
                    livePreviewFrameFacility.contentWindow.location.href = currentPreviewUrlFacility + (currentPreviewUrlFacility.includes('?') ? '&' : '?') + 'timestamp=' + new Date().getTime();
                } else { 
                    livePreviewFrameFacility.src = currentPreviewUrlFacility + (currentPreviewUrlFacility.includes('?') ? '&' : '?') + 'timestamp=' + new Date().getTime();
                }
            });
        }
        
        window.onload = function () {
            if (window.history.replaceState) {
                const url = new URL(window.location.href);
                let paramsChanged = false;
                const paramsToRemove = ['status_add', 'status_edit', 'status_delete', 'error'];
                paramsToRemove.forEach(param => {
                    if (url.searchParams.has(param)) {
                        url.searchParams.delete(param);
                        paramsChanged = true;
                    }
                });
                if (paramsChanged) {
                    window.history.replaceState({path: url.href}, '', url.href);
                }
            }
        };
    </script>
</body>
</html>
```

**Perubahan Utama:**
1.  **Logging Detail untuk URL Gambar (di dalam `foreach`):** Semua `error_log` yang sebelumnya saya tunjukkan (yang beberapa dikomentari) sekarang sudah **diaktifkan** di dalam blok logika pembentukan `$facility_img_url`.
    * Ini akan mencatat nilai `$site_url`, `$facilities_upload_path_web`, dan `$db_path` (path dari database).
    * Akan mencatat path mana yang sedang dicek `file_exists()`.
    * Akan mencatat URL final yang dihasilkan atau pesan `WARNING_IMG` jika file tidak ditemukan.

**Langkah Anda Sekarang (Sangat Penting):**
1.  Gunakan kode dari Canvas ini untuk `admin_manage_facilities.php` Anda.
2.  Muat ulang halaman "Kelola Fasilitas" di browser Anda.
3.  **Segera buka dan periksa file log error PHP di server Anda.**
    * Cari semua baris yang diawali dengan `DEBUG_IMG` atau `WARNING_IMG`.
    * Untuk setiap gambar (Anda bisa mencocokkan dengan `Facility ID: ...`), perhatikan:
        * **`Path dari DB`**: Apa nilai yang tersimpan di `modal_image_path`?
        * **`Path adalah URL lengkap/absolut web root/relatif`**: Logika mana yang dijalankan?
        * **`Cek file system`**: Path lengkap di server mana yang sedang PHP periksa? Apakah path ini benar?
        * **`Gambar TIDAK DITEMUKAN di ...`**: Jika ini muncul, berarti PHP tidak bisa menemukan file gambar di lokasi tersebut.
        * **`URL Final`**: Apa URL lengkap yang akhirnya digunakan untuk `src` gambar? Apakah URL ini benar jika Anda coba buka langsung di browser?
4.  **Bandingkan dengan Halaman Publik**:
    * Ambil URL gambar yang **berhasil tampil** di halaman publik Anda.
    * Bandingkan URL tersebut dengan `URL Final` yang Anda lihat di log untuk gambar yang sama. Di mana letak perbedaannya?

**Kemungkinan Besar Penyebabnya (Setelah Melihat Log):**
* **`$facilities_upload_path_web` tidak tepat**: Mungkin seharusnya `'/uploads/'` saja, atau `'/assets/images/fasilitas/'`, dll. Log akan menunjukkan path yang salah dibentuk jika ini masalahnya.
* **Nilai di `modal_image_path` (database) tidak seperti yang diharapkan**:
    * Mungkin Anda menyimpan `uploads/facilities_images/namafile.jpg` di database, padahal seharusnya hanya `namafile.jpg`. Logika saat ini akan mencoba menggabungkan `$facilities_upload_path_web` dengan itu, menghasilkan path ganda seperti `/uploads/facilities_images/uploads/facilities_images/namafile.jpg`.
    * Atau sebaliknya.
* **`$site_url` tidak benar** (meskipun kecil kemungkinannya jika bagian lain situs berfungsi).

Dengan melihat detail dari log, kita akan tahu persis bagaimana URL gambar dibentuk dan mengapa itu gagal. Silakan bagikan potongan log yang relevan jika Anda masih bingung setelah memeriksan