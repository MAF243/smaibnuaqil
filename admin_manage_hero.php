<?php
// FILE: admin_manage_hero.php (Nama file diubah dari admin_hero_settings.php)

// 1. Mulai Sesi (WAJIB PALING ATAS)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
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
    if (!headers_sent()) { header("Location: login.php"); }
    exit;
}

// 5. Sertakan File Konfigurasi Database
if (file_exists('config.php')) {
    include_once 'config.php'; 
} else {
    error_log("CRITICAL: config.php tidak ditemukan di admin_manage_hero.php"); 
    exit("<!DOCTYPE html><html><head><title>Error</title></head><body>Error: Config.php tidak ditemukan.</body></html>");
}

// 6. Pastikan Koneksi $conn Berhasil
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $db_error_msg = isset($conn) && $conn instanceof mysqli ? $conn->connect_error : 'Objek koneksi DB tidak terdefinisi.';
    error_log("CRITICAL: Koneksi database gagal di admin_manage_hero.php: " . $db_error_msg); 
    exit("<!DOCTYPE html><html><head><title>Error</title></head><body>Error: Koneksi database gagal. Detail: ".htmlspecialchars($db_error_msg)."</body></html>");
}

// Pengaturan $site_url
if (!isset($site_url)) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $site_url = $protocol . $domainName;
}
$site_url = rtrim($site_url, '/');

// ========================================================================
// === PERBAIKAN PATH UPLOAD HERO BERDASARKAN INFO ANDA ===
// Path untuk upload gambar hero (relatif dari web root)
$hero_upload_base_web_path = '/uploads/hero/'; // Menggunakan path yang Anda berikan
// ========================================================================
$hero_upload_dir_server = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $hero_upload_base_web_path;
error_log("ADMIN_MANAGE_HERO: Hero upload server directory set to: " . $hero_upload_dir_server);

// Pastikan direktori upload ada dan bisa ditulis
if (!is_dir($hero_upload_dir_server)) {
    error_log("ADMIN_MANAGE_HERO: Direktori upload hero '{$hero_upload_dir_server}' tidak ditemukan, mencoba membuat...");
    if (!mkdir($hero_upload_dir_server, 0755, true) && !is_dir($hero_upload_dir_server)) { 
        $_SESSION['status_message_hero'] = "Error Kritis: Gagal membuat direktori upload hero di '{$hero_upload_dir_server}'. Harap buat manual atau cek izin folder.";
        $_SESSION['alert_class_hero'] = "alert-danger";
        error_log("ADMIN_MANAGE_HERO: CRITICAL - Failed to create hero upload directory: " . $hero_upload_dir_server);
        if (!headers_sent()) { header("Location: " . basename($_SERVER['PHP_SELF'])); exit; } else { exit($_SESSION['status_message_hero']);}
    } else {
        error_log("ADMIN_MANAGE_HERO: Direktori upload hero '{$hero_upload_dir_server}' berhasil dibuat.");
    }
}
if (!is_writable($hero_upload_dir_server)) {
    $_SESSION['status_message_hero'] = "Error Kritis: Direktori upload hero '{$hero_upload_dir_server}' tidak bisa ditulis. Cek izin folder.";
    $_SESSION['alert_class_hero'] = "alert-danger";
    error_log("ADMIN_MANAGE_HERO: CRITICAL - Hero upload directory not writable: " . $hero_upload_dir_server);
    if (!headers_sent()) { header("Location: " . basename($_SERVER['PHP_SELF'])); exit; } else { exit($_SESSION['status_message_hero']);}
}

// Fungsi untuk mengambil nilai setting dari database
function get_setting_value($conn_db, $setting_name_key) {
    $value = null; 
    $stmt = $conn_db->prepare("SELECT setting_value FROM site_settings WHERE setting_name = ?");
    if ($stmt) {
        $stmt->bind_param("s", $setting_name_key);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $value = $row['setting_value'];
            }
        } else { error_log("Error execute get_setting_value for {$setting_name_key}: " . $stmt->error); }
        $stmt->close();
    } else { error_log("Error prepare get_setting_value for {$setting_name_key}: " . $conn_db->error); }
    return $value;
}

// Fungsi untuk menyimpan/update nilai setting ke database
function update_setting_value($conn_db, $setting_name_key, $setting_new_value) {
    $stmt_check = $conn_db->prepare("SELECT setting_name FROM site_settings WHERE setting_name = ?");
    if(!$stmt_check) { error_log("Error prepare (check) update_setting_value: " . $conn_db->error); return false; }
    $stmt_check->bind_param("s", $setting_name_key);
    $stmt_check->execute();
    $stmt_check->store_result();
    $exists = $stmt_check->num_rows > 0;
    $stmt_check->close();

    $sql_query = $exists ? "UPDATE site_settings SET setting_value = ? WHERE setting_name = ?" : "INSERT INTO site_settings (setting_name, setting_value) VALUES (?, ?)";
    $stmt = $conn_db->prepare($sql_query);
    if(!$stmt) { error_log("Error prepare (".($exists ? 'update':'insert').") update_setting_value: " . $conn_db->error); return false; }
    
    if ($exists) { $stmt->bind_param("ss", $setting_new_value, $setting_name_key); } 
    else { $stmt->bind_param("ss", $setting_name_key, $setting_new_value); }
    
    if ($stmt->execute()) { $stmt->close(); return true; } 
    else { error_log("Error execute update_setting_value for {$setting_name_key}: " . $stmt->error); $stmt->close(); return false; }
}

// Ambil data admin untuk navbar
$admin_name_display = $_SESSION['admin_name'] ?? "adminq";
$default_avatar_web_path = '/public/asset/user.png'; 
$admin_photo_url_final = $site_url . $default_avatar_web_path;
$specific_photo_found_and_valid = false; 
if (isset($_SESSION['admin_photo_url']) && !empty($_SESSION['admin_photo_url'])) {
    $session_path_admin = trim($_SESSION['admin_photo_url']);
    if (preg_match('/^https?:\/\//i', $session_path_admin)) {
        $admin_photo_url_final = htmlspecialchars($session_path_admin);
        $specific_photo_found_and_valid = true;
    } elseif (strpos($session_path_admin, '/') === 0) { 
        if (file_exists(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $session_path_admin)) {
            $admin_photo_url_final = htmlspecialchars($site_url . $session_path_admin);
            $specific_photo_found_and_valid = true;
        }
    } else { 
        $admin_photos_default_base_nav = '/uploads/admin_photos/'; 
        $potential_web_path_nav = rtrim($admin_photos_default_base_nav, '/') . '/' . ltrim($session_path_admin, '/');
        if (file_exists(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $potential_web_path_nav)) {
            $admin_photo_url_final = htmlspecialchars($site_url . $potential_web_path_nav);
            $specific_photo_found_and_valid = true;
        }
    }
}
if (!$specific_photo_found_and_valid && isset($_SESSION['admin_photo_filename']) && !empty($_SESSION['admin_photo_filename'])) {
    $filename_admin_nav = basename(htmlspecialchars($_SESSION['admin_photo_filename']));
    $admin_photos_default_base_nav_file = '/uploads/admin_photos/';
    $full_web_path_admin_nav_file = rtrim($admin_photos_default_base_nav_file, '/') . '/' . $filename_admin_nav;
    if (file_exists(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $full_web_path_admin_nav_file)) {
        $admin_photo_url_final = htmlspecialchars($site_url . $full_web_path_admin_nav_file);
        $specific_photo_found_and_valid = true; 
    }
}
if (!$specific_photo_found_and_valid) { 
    $admin_photo_url_final = $site_url . $default_avatar_web_path; 
}
$current_page_filename = basename($_SERVER['PHP_SELF']);

// Proses form jika di-submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_hero_image'])) {
    if (!isset($_POST['csrf_token_hero']) || !hash_equals($_SESSION['csrf_token_hero'] ?? '', $_POST['csrf_token_hero'])) {
        $_SESSION['status_message_hero'] = "Error: Permintaan tidak valid (CSRF token mismatch).";
        $_SESSION['alert_class_hero'] = "alert-danger";
    } else {
        $current_hero_image_db_path_on_submit = get_setting_value($conn, 'hero_image_path');
        $new_image_path_to_save_in_db = $current_hero_image_db_path_on_submit; 
        $image_changed_in_post = false;

        if (isset($_FILES['new_hero_image']) && $_FILES['new_hero_image']['error'] == UPLOAD_ERR_OK) {
            $file_name_original = basename($_FILES['new_hero_image']['name']);
            $file_ext = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_size = $_FILES['new_hero_image']['size'];
            $max_size_bytes = 2 * 1024 * 1024; // 2MB

            if (!in_array($file_ext, $allowed_ext)) {
                $_SESSION['status_message_hero'] = "Error: Format file tidak diizinkan. Hanya JPG, JPEG, PNG, GIF, WEBP.";
                $_SESSION['alert_class_hero'] = "alert-danger";
            } elseif ($file_size > $max_size_bytes) { 
                $_SESSION['status_message_hero'] = "Error: Ukuran file terlalu besar (Maksimal ".($max_size_bytes/1024/1024)."MB).";
                $_SESSION['alert_class_hero'] = "alert-danger";
            } else {
                $new_hero_filename = 'hero_image_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
                $destination_server_path_for_upload = $hero_upload_dir_server . $new_hero_filename;

                if (move_uploaded_file($_FILES['new_hero_image']['tmp_name'], $destination_server_path_for_upload)) {
                    $new_image_path_to_save_in_db = rtrim($hero_upload_base_web_path, '/') . '/' . $new_hero_filename; 
                    $image_changed_in_post = true;
                    error_log("ADMIN_MANAGE_HERO: New hero image uploaded: " . $new_image_path_to_save_in_db);

                    if (!empty($current_hero_image_db_path_on_submit) && 
                        $current_hero_image_db_path_on_submit !== $new_image_path_to_save_in_db &&
                        strpos($current_hero_image_db_path_on_submit, 'placeholder-hero.jpg') === false) {
                        
                        $old_image_server_path_to_delete = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $current_hero_image_db_path_on_submit;
                        if (file_exists($old_image_server_path_to_delete) && strpos($current_hero_image_db_path_on_submit, $hero_upload_base_web_path) === 0) {
                           if(unlink($old_image_server_path_to_delete)){
                                error_log("ADMIN_MANAGE_HERO: Old hero image '{$old_image_server_path_to_delete}' deleted.");
                           } else {
                                error_log("ADMIN_MANAGE_HERO: WARNING - Failed to delete old hero image '{$old_image_server_path_to_delete}'.");
                           }
                        }
                    }
                } else {
                    $_SESSION['status_message_hero'] = "Error: Gagal mengupload gambar baru. Error PHP: ".$_FILES['new_hero_image']['error'];
                    $_SESSION['alert_class_hero'] = "alert-danger";
                    error_log("Failed to move uploaded hero image to: " . $destination_server_path_for_upload . " - PHP Upload Error: " . $_FILES['new_hero_image']['error']);
                }
            }
        } elseif (isset($_FILES['new_hero_image']) && $_FILES['new_hero_image']['error'] != UPLOAD_ERR_NO_FILE) {
            $_SESSION['status_message_hero'] = "Error: Terjadi error saat upload file: Kode Error PHP " . $_FILES['new_hero_image']['error'];
            $_SESSION['alert_class_hero'] = "alert-danger";
        }

        if (!isset($_SESSION['status_message_hero'])) { 
            $update_image_success = update_setting_value($conn, 'hero_image_path', $new_image_path_to_save_in_db);
            // Input judul dan subtitle dihilangkan, jadi tidak perlu diupdate
            // $update_title_success = update_setting_value($conn, 'hero_title', $hero_title_new);
            // $update_subtitle_success = update_setting_value($conn, 'hero_subtitle', $hero_subtitle_new);
            
            if ($update_image_success) { 
                $_SESSION['status_message_hero'] = "Gambar hero berhasil diperbarui!";
                $_SESSION['alert_class_hero'] = "alert-success";
            } else {
                $_SESSION['status_message_hero'] = "Gagal memperbarui gambar hero ke database.";
                $_SESSION['alert_class_hero'] = "alert-danger";
            }
        }
    }
    if (!headers_sent()) { header("Location: " . basename($_SERVER['PHP_SELF'])); exit; }
}

// Ambil pengaturan hero saat ini dari database untuk ditampilkan di form
// Judul dan subtitle tidak lagi diambil karena formnya dihilangkan
// $current_hero_title_db = get_setting_value($conn, 'hero_title') ?? '';
// $current_hero_subtitle_db = get_setting_value($conn, 'hero_subtitle') ?? '';
$current_hero_image_path_db_value = get_setting_value($conn, 'hero_image_path'); 

// Path default untuk placeholder jika gambar tidak ada atau path kosong
$default_hero_placeholder_url = $site_url . '/public/asset/placeholder-hero.jpg'; 
$hero_image_display_url_final = $default_hero_placeholder_url; 

if (!empty($current_hero_image_path_db_value)) {
    error_log("ADMIN_HERO_SETTINGS: DB hero path: '{$current_hero_image_path_db_value}'");
    if (preg_match('/^https?:\/\//i', $current_hero_image_path_db_value)) { 
        $hero_image_display_url_final = htmlspecialchars($current_hero_image_path_db_value);
        error_log("ADMIN_HERO_SETTINGS: Logic 1 - Full URL. Display URL: '{$hero_image_display_url_final}'");
    } 
    elseif (strpos($current_hero_image_path_db_value, '/') === 0) { 
        $hero_image_server_path_check = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $current_hero_image_path_db_value;
        error_log("ADMIN_HERO_SETTINGS: Logic 2 - Web Root Path. Check FS: '{$hero_image_server_path_check}'");
        if (file_exists($hero_image_server_path_check)) {
            $hero_image_display_url_final = htmlspecialchars($site_url . $current_hero_image_path_db_value);
            error_log("ADMIN_HERO_SETTINGS: Logic 2 - File EXISTS. Display URL: '{$hero_image_display_url_final}'");
        } else {
            error_log("ADMIN_HERO_SETTINGS: WARNING - Logic 2 - File NOT FOUND at '{$hero_image_server_path_check}'. Using placeholder.");
        }
    } 
    else {
        // Jika $current_hero_image_path_db_value adalah nama file saja (misal 'hero.jpg')
        // dan $hero_upload_base_web_path adalah '/uploads/hero/', maka path lengkapnya adalah gabungan keduanya.
        // Namun, jika $current_hero_image_path_db_value sudah berisi $hero_upload_base_web_path (misal 'uploads/hero/hero.jpg')
        // maka logika ini perlu disesuaikan agar tidak ada duplikasi.
        // Untuk sekarang, kita asumsikan jika tidak dimulai '/' dan bukan URL, maka relatif terhadap $hero_upload_base_web_path
        $full_web_path_hero = rtrim($hero_upload_base_web_path, '/') . '/' . ltrim($current_hero_image_path_db_value, '/');
        $hero_image_server_path_check = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $full_web_path_hero;
        error_log("ADMIN_HERO_SETTINGS: Logic 3 - Relative to Default Upload. Check FS: '{$hero_image_server_path_check}'");
        if (file_exists($hero_image_server_path_check)) {
            $hero_image_display_url_final = htmlspecialchars($site_url . $full_web_path_hero);
             error_log("ADMIN_HERO_SETTINGS: Logic 3 - File EXISTS. Display URL: '{$hero_image_display_url_final}'");
        } else {
            error_log("ADMIN_HERO_SETTINGS: WARNING - Logic 3 - File NOT FOUND at '{$hero_image_server_path_check}'. Using placeholder.");
        }
    }
} else {
    error_log("ADMIN_HERO_SETTINGS: DB hero path is empty. Using placeholder: '{$hero_image_display_url_final}'");
}


// Generate CSRF token untuk form
if (empty($_SESSION['csrf_token_hero'])) {
    if (function_exists('random_bytes')) {
        $_SESSION['csrf_token_hero'] = bin2hex(random_bytes(32));
    } else {
        $_SESSION['csrf_token_hero'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}
$csrf_token_hero_form = $_SESSION['csrf_token_hero'];

// Ambil pesan status dari session untuk ditampilkan
$status_message_hero_display = '';
$alert_class_hero_display = '';
if (isset($_SESSION['status_message_hero']) && isset($_SESSION['alert_class_hero'])) {
    $status_message_hero_display = $_SESSION['status_message_hero'];
    $alert_class_hero_display = $_SESSION['alert_class_hero'];
    unset($_SESSION['status_message_hero']);
    unset($_SESSION['alert_class_hero']);
}
$page_title = "Pengaturan Gambar Hero"; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/jpg" href="<?php echo htmlspecialchars($site_url); ?>/public/asset/logo.jpg">
    <style>
        body { background-color: #f4f7f6; }
        .container-main { max-width: 700px; margin: 40px auto; background-color: #fff; padding: 25px 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .current-hero-img-display { max-width: 100%; height: auto; max-height: 250px; border: 1px solid #ced4da; padding: 4px; border-radius: .25rem; background-color: #e9ecef; object-fit: contain; display: block; margin: 0 auto 15px auto; }
        .form-label { font-weight: 500; }
        .footer { text-align: center; padding: 20px; background-color: #212529; color: white; margin-top: 40px; }
        #livePreviewContainerHero { display: none; position: fixed; bottom: 20px; right: 20px; width: 380px; height: 550px; border: 2px solid #ffc107; background-color: white; box-shadow: 0 5px 20px rgba(0,0,0,0.25); z-index: 1060; border-radius: 8px; overflow: hidden; flex-direction: column; }
        #livePreviewHeaderHero { padding: 8px 10px; background-color: #ffc107; color: black; font-size: 0.9em; display: flex; justify-content: space-between; align-items: center; }
        #livePreviewHeaderHero .btn-close { padding: 0.25rem 0.5rem; } 
        #livePreviewFrameHero { width: 100%; height: calc(100% - 38px); border: none; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="beranda_admin.php">
                <img src="<?php echo htmlspecialchars($admin_photo_url_final); ?>" alt="Foto Admin" width="30" height="30" class="d-inline-block align-text-top rounded-circle me-2">
                <?php echo htmlspecialchars($admin_name_display); ?> (Admin Panel)
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page_filename == 'beranda_admin.php' ? 'active' : ''); ?>" href="beranda_admin.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page_filename == 'admin_manage_facilities.php' ? 'active' : ''); ?>" href="admin_manage_facilities.php"><i class="bi bi-building-fill-gear me-1"></i> Fasilitas</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page_filename == 'admin_manage_news.php' ? 'active' : ''); ?>" href="admin_manage_news.php"><i class="bi bi-newspaper me-1"></i> Berita</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page_filename == 'admin_manage_gallery.php' ? 'active' : ''); ?>" href="admin_manage_gallery.php"><i class="bi bi-images me-1"></i> Galeri</a></li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page_filename == 'admin_manage_hero.php' ? 'active' : ''); ?>" aria-current="page" href="admin_manage_hero.php"><i class="bi bi-image-alt me-1"></i> Gambar Hero</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page_filename == 'admin_map_settings.php' ? 'active' : ''); ?>" href="admin_map_settings.php"><i class="bi bi-map-fill me-1"></i> Edit Peta</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page_filename == 'admin_site_settings.php' ? 'active' : ''); ?>" href="admin_site_settings.php"><i class="bi bi-info-circle-fill me-1"></i> Info Situs</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars($site_url); ?>/index.php" target="_blank" title="Buka situs publik di tab baru"><i class="bi bi-eye-fill me-1"></i> Lihat Situs</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($current_page_filename == 'admin_profile.php' || $current_page_filename == 'admin_settings.php' ? 'active' : ''); ?>" href="#" id="adminProfileDropdownTopNav" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-person-circle me-1"></i> Profil Admin</a>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-image-alt"></i> <?php echo htmlspecialchars($page_title ?? 'Pengaturan Gambar Hero'); ?></h1>
            <div>
                <button class="btn btn-outline-info btn-sm" id="togglePreviewBtnHero" title="Tampilkan/Sembunyikan Live Preview Homepage">
                    <i class="bi bi-display-fill"></i> Live Preview
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="refreshPreviewBtnHero" style="display:none;" title="Muat Ulang Preview">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
        </div>

        <?php if (!empty($status_message_hero_display)): ?>
            <div class="alert <?php echo htmlspecialchars($alert_class_hero_display); ?> alert-dismissible fade show" role="alert">
                <?php echo $status_message_hero_display; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Formulir Ganti Gambar Hero</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token_hero" value="<?php echo htmlspecialchars($csrf_token_hero_form); ?>">
                    
                    <div class="mb-4">
                        <p class="mb-2"><strong>Gambar Hero Saat Ini:</strong></p>
                        <img src="<?php echo $hero_image_display_url_final; ?>?t=<?php echo time(); ?>" alt="Gambar Hero Saat Ini" class="current-hero-img-display img-thumbnail">
                        <?php 
                        if ($hero_image_display_url_final !== $default_hero_placeholder_url && !empty($current_hero_image_path_db_value)): 
                        ?>
                             <p class="mt-1"><small>Path di Database: <code><?php echo htmlspecialchars($current_hero_image_path_db_value); ?></code></small></p>
                        <?php elseif ($hero_image_display_url_final === $default_hero_placeholder_url): ?>
                             <p class="mt-1 text-muted"><small>Gambar placeholder ditampilkan.</small></p>
                        <?php else: ?>
                            <p class="mt-1 text-muted"><small>Gambar hero saat ini ditampilkan (path DB mungkin tidak diset atau berbeda).</small></p>
                        <?php endif; ?>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label for="new_hero_image" class="form-label fw-bold">Ganti atau Upload Gambar Hero Baru:</label>
                        <p class="small text-muted mb-2">Pilih gambar baru jika ingin mengganti. Jika tidak ada gambar dipilih dan Anda tekan simpan, gambar saat ini tidak akan berubah. Ukuran ideal sekitar 1920x800 pixel. Format: JPG, PNG, GIF, WEBP. Maks 2MB.</p>
                        <input type="file" class="form-control" id="new_hero_image" name="new_hero_image" accept="image/jpeg,image/png,image/gif,image/webp" required>
                    </div>
                    
                    <button type="submit" name="save_hero_image" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Gambar Hero</button>
                </form>
            </div>
        </div>
    </div>

    <div id="livePreviewContainerHero">
        <div id="livePreviewHeaderHero">
            <strong><i class="bi bi-display"></i> Live Preview: <span id="previewTargetTitleHero">Homepage</span></strong>
            <button type="button" class="btn-close" id="closePreviewBtnHero" title="Tutup Preview"></button>
        </div>
        <iframe id="livePreviewFrameHero" src="<?php echo htmlspecialchars($site_url); ?>/index.php"></iframe>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> SMA Ibnu Aqil. Admin Panel. Hak Cipta Dilindungi.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const siteUrlForJsHero = '<?php echo htmlspecialchars($site_url); ?>';
        const heroUploadPathWebForJs = '<?php echo htmlspecialchars(rtrim($hero_upload_base_web_path, '/')); ?>';

        const togglePreviewBtnHero = document.getElementById('togglePreviewBtnHero');
        const livePreviewContainerHero = document.getElementById('livePreviewContainerHero');
        const livePreviewFrameHero = document.getElementById('livePreviewFrameHero');
        const closePreviewBtnHero = document.getElementById('closePreviewBtnHero');
        const refreshPreviewBtnHero = document.getElementById('refreshPreviewBtnHero'); 
        const previewTargetTitleHeroJS = document.getElementById('previewTargetTitleHero');
        let currentPreviewUrlHero = siteUrlForJsHero + '/index.php'; 

        if(togglePreviewBtnHero && livePreviewContainerHero && livePreviewFrameHero) {
            togglePreviewBtnHero.addEventListener('click', function() {
                if (livePreviewContainerHero.style.display === 'none' || livePreviewContainerHero.style.display === '') {
                    livePreviewContainerHero.style.display = 'flex';
                    livePreviewFrameHero.src = currentPreviewUrlHero + '?timestamp=' + new Date().getTime(); 
                    if(refreshPreviewBtnHero) refreshPreviewBtnHero.style.display = 'inline-block';
                    if(previewTargetTitleHeroJS) previewTargetTitleHeroJS.textContent = 'Homepage';
                } else {
                    livePreviewContainerHero.style.display = 'none';
                    if(refreshPreviewBtnHero) refreshPreviewBtnHero.style.display = 'none';
                }
            });
        }
        if(closePreviewBtnHero && livePreviewContainerHero) {
            closePreviewBtnHero.addEventListener('click', function() {
                livePreviewContainerHero.style.display = 'none';
                if(refreshPreviewBtnHero) refreshPreviewBtnHero.style.display = 'none';
            });
        }
        if(refreshPreviewBtnHero && livePreviewFrameHero) {
            refreshPreviewBtnHero.addEventListener('click', function() {
                 if (livePreviewFrameHero.contentWindow) {
                    livePreviewFrameHero.contentWindow.location.href = currentPreviewUrlHero + '?timestamp=' + new Date().getTime();
                } else {
                    livePreviewFrameHero.src = currentPreviewUrlHero + '?timestamp=' + new Date().getTime();
                }
            });
        }
    </script>
</body>
</html>
<?php
if (isset($conn) && ($conn instanceof mysqli)) {
    // mysqli_close($conn); // Opsional
}
?>
