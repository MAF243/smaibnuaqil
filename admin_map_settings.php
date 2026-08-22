<?php
// FILE: admin_hero_settings.php

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
    include_once 'config.php'; // $conn dan mungkin $site_url didefinisikan di sini
} else {
    error_log("CRITICAL: config.php tidak ditemukan di admin_hero_settings.php");
    exit("<!DOCTYPE html><html><head><title>Error</title></head><body>Error: Config.php tidak ditemukan.</body></html>");
}

// 6. Pastikan Koneksi $conn Berhasil
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $db_error_msg = isset($conn) && $conn instanceof mysqli ? $conn->connect_error : 'Objek koneksi DB tidak terdefinisi.';
    error_log("CRITICAL: Koneksi database gagal di admin_hero_settings.php: " . $db_error_msg);
    exit("<!DOCTYPE html><html><head><title>Error</title></head><body>Error: Koneksi database gagal. Detail: ".htmlspecialchars($db_error_msg)."</body></html>");
}

// Pengaturan $site_url
if (!isset($site_url)) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $site_url = $protocol . $domainName;
}
$site_url = rtrim($site_url, '/');

// Path untuk upload gambar hero (relatif dari web root)
$hero_upload_base_web_path = '/uploads/hero_images/'; // Ganti jika perlu
$hero_upload_dir_server = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $hero_upload_base_web_path;

// Pastikan direktori upload ada dan bisa ditulis
if (!is_dir($hero_upload_dir_server)) {
    if (!mkdir($hero_upload_dir_server, 0775, true) && !is_dir($hero_upload_dir_server)) {
        $_SESSION['status_message_hero'] = "Error: Gagal membuat direktori upload hero di '{$hero_upload_dir_server}'.";
        $_SESSION['alert_class_hero'] = "alert-danger";
        if (!headers_sent()) { header("Location: " . $_SERVER['PHP_SELF']); exit; } else { exit($_SESSION['status_message_hero']);}
    }
}
if (!is_writable($hero_upload_dir_server)) {
    $_SESSION['status_message_hero'] = "Error: Direktori upload hero '{$hero_upload_dir_server}' tidak bisa ditulis.";
    $_SESSION['alert_class_hero'] = "alert-danger";
    if (!headers_sent()) { header("Location: " . $_SERVER['PHP_SELF']); exit; } else { exit($_SESSION['status_message_hero']);}
}

// Fungsi untuk mengambil nilai setting dari database
function get_setting_value($conn_db, $setting_name_key) {
    $stmt = $conn_db->prepare("SELECT setting_value FROM site_settings WHERE setting_name = ?");
    if (!$stmt) {
        error_log("Error prepare get_setting_value for {$setting_name_key}: " . $conn_db->error);
        return null;
    }
    $stmt->bind_param("s", $setting_name_key);
    $stmt->execute();
    $result = $stmt->get_result();
    $value = null;
    if ($row = $result->fetch_assoc()) {
        $value = $row['setting_value'];
    }
    $stmt->close();
    return $value;
}

// Fungsi untuk menyimpan/update nilai setting ke database
function update_setting_value($conn_db, $setting_name_key, $setting_new_value) {
    // Cek dulu apakah setting sudah ada
    $stmt_check = $conn_db->prepare("SELECT setting_name FROM site_settings WHERE setting_name = ?");
    if(!$stmt_check) { error_log("Error prepare (check) update_setting_value: " . $conn_db->error); return false; }
    $stmt_check->bind_param("s", $setting_name_key);
    $stmt_check->execute();
    $stmt_check->store_result();
    $exists = $stmt_check->num_rows > 0;
    $stmt_check->close();

    if ($exists) {
        $stmt = $conn_db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_name = ?");
        if(!$stmt) { error_log("Error prepare (update) update_setting_value: " . $conn_db->error); return false; }
        $stmt->bind_param("ss", $setting_new_value, $setting_name_key);
    } else {
        $stmt = $conn_db->prepare("INSERT INTO site_settings (setting_name, setting_value) VALUES (?, ?)");
        if(!$stmt) { error_log("Error prepare (insert) update_setting_value: " . $conn_db->error); return false; }
        $stmt->bind_param("ss", $setting_name_key, $setting_new_value);
    }
    
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        error_log("Error execute update_setting_value for {$setting_name_key}: " . $stmt->error);
        $stmt->close();
        return false;
    }
}

// Logika untuk menangani POST request (update settings)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token_hero']) || !hash_equals($_SESSION['csrf_token_hero'] ?? '', $_POST['csrf_token_hero'])) {
        $_SESSION['status_message_hero'] = "Error: Permintaan tidak valid (CSRF token mismatch).";
        $_SESSION['alert_class_hero'] = "alert-danger";
    } else {
        $hero_title_new = trim($_POST['hero_title'] ?? '');
        $hero_subtitle_new = trim($_POST['hero_subtitle'] ?? '');
        $current_hero_image = get_setting_value($conn, 'hero_image_path');
        $new_hero_image_path = $current_hero_image; // Default ke gambar lama

        // Penanganan upload gambar hero
        if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['hero_image']['tmp_name'];
            $file_name = $_FILES['hero_image']['name'];
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($file_extension, $allowed_extensions)) {
                $safe_file_name = "hero_image_" . uniqid() . "." . $file_extension;
                $destination_server_path = $hero_upload_dir_server . $safe_file_name;
                
                if (move_uploaded_file($file_tmp_name, $destination_server_path)) {
                    // Hapus gambar lama jika ada dan berbeda
                    if (!empty($current_hero_image) && $current_hero_image !== ($hero_upload_base_web_path . $safe_file_name) && file_exists(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $current_hero_image) ) {
                        if(strpos($current_hero_image, $hero_upload_base_web_path) === 0) { // Hanya hapus jika di folder hero
                           unlink(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $current_hero_image);
                           error_log("ADMIN_HERO_SETTINGS: Old hero image '{$current_hero_image}' deleted.");
                        }
                    }
                    $new_hero_image_path = $hero_upload_base_web_path . $safe_file_name; // Simpan path web relatif
                    error_log("ADMIN_HERO_SETTINGS: New hero image uploaded: " . $new_hero_image_path);
                } else {
                    $_SESSION['status_message_hero'] = "Error: Gagal memindahkan file gambar hero yang diunggah.";
                    $_SESSION['alert_class_hero'] = "alert-danger";
                }
            } else {
                $_SESSION['status_message_hero'] = "Error: Format file gambar hero tidak diizinkan. Hanya JPG, JPEG, PNG, WEBP.";
                $_SESSION['alert_class_hero'] = "alert-danger";
            }
        }

        // Simpan ke database jika tidak ada error upload sebelumnya (jika ada file baru)
        if (!isset($_SESSION['status_message_hero'])) {
            $update_image = update_setting_value($conn, 'hero_image_path', $new_hero_image_path);
            $update_title = update_setting_value($conn, 'hero_title', $hero_title_new);
            $update_subtitle = update_setting_value($conn, 'hero_subtitle', $hero_subtitle_new);

            if ($update_image && $update_title && $update_subtitle) {
                $_SESSION['status_message_hero'] = "Pengaturan hero berhasil diperbarui!";
                $_SESSION['alert_class_hero'] = "alert-success";
            } else {
                $_SESSION['status_message_hero'] = "Gagal memperbarui beberapa pengaturan hero ke database.";
                $_SESSION['alert_class_hero'] = "alert-danger";
            }
        }
    }
    // Redirect untuk mencegah resubmit form dan menampilkan pesan status
    if (!headers_sent()) { header("Location: " . $_SERVER['PHP_SELF']); exit; }
}

// Ambil pengaturan hero saat ini dari database untuk ditampilkan di form
$current_hero_title = get_setting_value($conn, 'hero_title') ?? '';
$current_hero_subtitle = get_setting_value($conn, 'hero_subtitle') ?? '';
$current_hero_image_db_path = get_setting_value($conn, 'hero_image_path');

$hero_image_display_url = $site_url . '/public/asset/placeholder-hero.jpg'; // Default placeholder
if (!empty($current_hero_image_db_path)) {
    if (preg_match('/^https?:\/\//i', $current_hero_image_db_path)) {
        $hero_image_display_url = htmlspecialchars($current_hero_image_db_path);
    } elseif (strpos($current_hero_image_db_path, '/') === 0) { // Path absolut web root
        if (file_exists(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $current_hero_image_db_path)) {
            $hero_image_display_url = htmlspecialchars($site_url . $current_hero_image_db_path);
        }
    } else { // Path relatif dari $hero_upload_base_web_path
        $test_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . rtrim($hero_upload_base_web_path, '/') . '/' . ltrim($current_hero_image_db_path, '/');
        if (file_exists($test_path)) {
            $hero_image_display_url = htmlspecialchars($site_url . rtrim($hero_upload_base_web_path, '/') . '/' . ltrim($current_hero_image_db_path, '/'));
        }
    }
}


// Ambil data admin untuk navbar
$admin_name_display = $_SESSION['admin_name'] ?? "adminq";
$default_avatar_web_path = '/public/asset/user.png';
$admin_photo_url_final = $site_url . $default_avatar_web_path;
// ... (Logika lengkap untuk $admin_photo_url_final dari sesi seperti di admin_manage_facilities)

// Generate CSRF token untuk form
if (empty($_SESSION['csrf_token_hero'])) {
    $_SESSION['csrf_token_hero'] = bin2hex(random_bytes(32));
}
$csrf_token_hero = $_SESSION['csrf_token_hero'];

// Ambil pesan status dari session untuk ditampilkan
$status_message_hero_display = '';
$alert_class_hero_display = '';
if (isset($_SESSION['status_message_hero']) && isset($_SESSION['alert_class_hero'])) {
    $status_message_hero_display = $_SESSION['status_message_hero'];
    $alert_class_hero_display = $_SESSION['alert_class_hero'];
    unset($_SESSION['status_message_hero']);
    unset($_SESSION['alert_class_hero']);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Gambar Hero - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/jpg" href="<?php echo htmlspecialchars($site_url); ?>/public/asset/logo.jpg">
    <style>
        body { background-color: #f8f9fc; }
        .container-main { margin-top: 20px; margin-bottom: 20px; max-width: 800px;}
        .preview-hero-image { max-width: 100%; height: auto; max-height: 300px; display: block; margin-bottom: 15px; border: 1px solid #ddd; padding: 5px; border-radius: 4px;}
        .footer { text-align: center; padding: 20px; background-color: #212529; color: white; margin-top: 40px; }
        /* Live Preview Panel - bisa disamakan dengan halaman lain */
        #livePreviewContainerHero { display: none; position: fixed; bottom: 20px; right: 20px; width: 380px; height: 550px; border: 2px solid #ffc107; background-color: white; box-shadow: 0 5px 20px rgba(0,0,0,0.25); z-index: 1060; border-radius: 8px; overflow: hidden; flex-direction: column; }
        #livePreviewHeaderHero { padding: 8px 10px; background-color: #ffc107; color: black; font-size: 0.9em; display: flex; justify-content: space-between; align-items: center; }
        #livePreviewHeaderHero .btn-close { padding: 0.25rem 0.5rem; }
        #livePreviewFrameHero { width: 100%; height: calc(100% - 38px); border: none; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
       <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page_filename == 'beranda_admin.php' ? 'active' : ''); ?>" href="beranda_admin.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page_filename == 'admin_manage_facilities.php' ? 'active' : ''); ?>" href="admin_manage_facilities.php"><i class="bi bi-building-fill-gear me-1"></i> Fasilitas</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page_filename == 'admin_manage_news.php' ? 'active' : ''); ?>" href="admin_manage_news.php"><i class="bi bi-newspaper me-1"></i> Berita</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page_filename == 'admin_manage_gallery.php' ? 'active' : ''); ?>" aria-current="page" href="admin_manage_gallery.php"><i class="bi bi-images me-1"></i> Galeri</a></li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page_filename == 'admin_hero_settings.php' ? 'active' : ''); ?>" href="admin_manage_hero.php"><i class="bi bi-image-alt me-1"></i> Gambar Hero</a>
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
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-image-alt"></i> Pengaturan Gambar Hero</h1>
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
                <h6 class="m-0 font-weight-bold text-primary">Formulir Pengaturan Hero</h6>
            </div>
            <div class="card-body">
                <form action="admin_hero_settings.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token_hero" value="<?php echo htmlspecialchars($csrf_token_hero); ?>">

                    <div class="mb-3">
                        <label for="hero_title" class="form-label">Judul Hero:</label>
                        <input type="text" class="form-control" id="hero_title" name="hero_title" value="<?php echo htmlspecialchars($current_hero_title); ?>">
                        <small class="form-text text-muted">Teks utama yang besar pada bagian hero.</small>
                    </div>

                    <div class="mb-3">
                        <label for="hero_subtitle" class="form-label">Subjudul Hero:</label>
                        <textarea class="form-control" id="hero_subtitle" name="hero_subtitle" rows="3"><?php echo htmlspecialchars($current_hero_subtitle); ?></textarea>
                        <small class="form-text text-muted">Teks tambahan atau deskripsi singkat di bawah judul hero.</small>
                    </div>

                    <div class="mb-3">
                        <label for="hero_image" class="form-label">Gambar Hero:</label>
                        <input type="file" class="form-control" id="hero_image" name="hero_image" accept="image/jpeg,image/png,image/webp">
                        <small class="form-text text-muted">Unggah gambar baru untuk menggantikan gambar hero saat ini. Format yang diizinkan: JPG, PNG, WEBP. Ukuran ideal (misalnya): 1920x800 pixel.</small>
                    </div>

                    <?php if (!empty($current_hero_image_db_path) && $hero_image_display_url !== ($site_url . '/public/asset/placeholder-hero.jpg')): ?>
                        <div class="mb-3">
                            <p><strong>Gambar Hero Saat Ini:</strong></p>
                            <img src="<?php echo $hero_image_display_url; ?>?t=<?php echo time(); ?>" alt="Current Hero Image" class="preview-hero-image img-thumbnail">
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <p><strong>Gambar Hero Saat Ini:</strong></p>
                            <img src="<?php echo $placeholder_facility_img; // Bisa juga placeholder khusus hero jika ada ?>" alt="Placeholder Hero Image" class="preview-hero-image img-thumbnail">
                             <p class="text-muted"><small>Belum ada gambar hero atau gambar default digunakan.</small></p>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill"></i> Simpan Pengaturan</button>
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
        // Live Preview Logic (mirip dengan halaman lain, sesuaikan ID elemen)
        const togglePreviewBtnHero = document.getElementById('togglePreviewBtnHero');
        const livePreviewContainerHero = document.getElementById('livePreviewContainerHero');
        const livePreviewFrameHero = document.getElementById('livePreviewFrameHero');
        const closePreviewBtnHero = document.getElementById('closePreviewBtnHero');
        const refreshPreviewBtnHero = document.getElementById('refreshPreviewBtnHero'); // Jika Anda menambahkan tombol refresh
        const previewTargetTitleHeroJS = document.getElementById('previewTargetTitleHero');
        let currentPreviewUrlHero = siteUrlForJsHero + '/index.php'; 

        if(togglePreviewBtnHero && livePreviewContainerHero && livePreviewFrameHero) {
            togglePreviewBtnHero.addEventListener('click', function() {
                if (livePreviewContainerHero.style.display === 'none' || livePreviewContainerHero.style.display === '') {
                    livePreviewContainerHero.style.display = 'flex';
                    livePreviewFrameHero.src = currentPreviewUrlHero + '?timestamp=' + new Date().getTime(); // Refresh dengan timestamp
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
```

**Perubahan dan Penambahan Utama:**

1.  **Struktur Dasar**: Mengikuti pola `admin_manage_news.php` untuk sesi, cache, cek login, `config.php`, dan validasi koneksi `$conn`.
2.  **Variabel Path**:
    * `$hero_upload_base_web_path`: Path dasar dari web root ke folder penyimpanan gambar hero (misalnya, `'/uploads/hero_images/'`). **Anda perlu menyesuaikan ini.**
    * `$hero_upload_dir_server`: Path sistem file absolut ke folder upload hero, dibuat dari `$_SERVER['DOCUMENT_ROOT']` dan `$hero_upload_base_web_path`. Skrip akan mencoba membuat direktori ini jika belum ada dan memvalidasi izin tulis.
3.  **Fungsi Database `get_setting_value` dan `update_setting_value`**:
    * Saya membuat dua fungsi helper untuk mengambil dan menyimpan pengaturan ke tabel `site_settings` menggunakan prepared statements. Ini mengasumsikan Anda memiliki tabel `site_settings` dengan kolom `setting_name` (sebagai primary key atau unique key) dan `setting_value`.
    * Fungsi `update_setting_value` akan melakukan `INSERT` jika setting belum ada, atau `UPDATE` jika sudah ada (logika UPSERT sederhana).
4.  **Logika Penanganan POST**:
    * **CSRF Protection**: Token CSRF (`csrf_token_hero`) digenerate dan divalidasi.
    * **Pengambilan Input**: Mengambil `hero_title`, `hero_subtitle`.
    * **Penanganan Upload Gambar**:
        * Jika file gambar baru diunggah (`$_FILES['hero_image']`):
            * Validasi ekstensi (jpg, jpeg, png, webp).
            * Buat nama file unik.
            * Pindahkan file ke `$hero_upload_dir_server`.
            * Jika berhasil, hapus gambar hero lama (jika ada dan berbeda) dari server. Pastikan hanya menghapus dari folder hero.
            * `$new_hero_image_path` akan berisi path web relatif (misalnya, `/uploads/hero_images/namafilebaru.jpg`) untuk disimpan ke database.
        * Jika tidak ada file baru, `$new_hero_image_path` akan tetap menggunakan nilai lama dari database.
    * **Penyimpanan ke Database**: Menggunakan `update_setting_value()` untuk menyimpan `hero_image_path`, `hero_title`, dan `hero_subtitle`.
    * **Pesan Status**: Menggunakan `$_SESSION['status_message_hero']` dan `$_SESSION['alert_class_hero']` untuk pesan sukses/error.
    * **Redirect**: Setelah POST, redirect kembali ke halaman yang sama untuk mencegah resubmission dan menampilkan pesan status.
5.  **Pengambilan Data untuk Form**: Sebelum menampilkan HTML, skrip mengambil nilai `hero_title`, `hero_subtitle`, dan `hero_image_path` saat ini dari database menggunakan `get_setting_value()`.
6.  **Logika Tampilan Gambar Hero Saat Ini**: Mirip dengan logika yang kita buat untuk fasilitas, mencoba menampilkan gambar dari `hero_image_display_url` atau placeholder.
7.  **HTML Form**:
    * Form untuk mengedit judul, subjudul, dan mengunggah gambar baru.
    * Menampilkan preview gambar hero saat ini.
8.  **Navbar**: Disalin dari `admin_manage_news.php` dan disesuaikan. Menu "Gambar Hero" akan aktif jika halaman ini yang dibuka. Opsi "Edit Peta" dan "Info Situs" juga disertakan sesuai permintaan Anda untuk menyamakan navbar.
9.  **Live Preview**: Ditambahkan kerangka untuk Live Preview, menargetkan `index.php` (Anda bisa sesuaikan jika halaman hero Anda berbeda).

**Langkah Selanjutnya untuk Anda:**

1.  **Buat Tabel `site_settings` (Jika Belum Ada)**:
    Jika Anda belum punya tabel `site_settings`, Anda bisa membuatnya dengan struktur sederhana, misalnya:
    ```sql
    CREATE TABLE site_settings (
        setting_name VARCHAR(100) PRIMARY KEY,
        setting_value TEXT NULL
    );
    ```
    Anda mungkin sudah punya tabel ini dari sistem lain, pastikan saja ada kolom untuk `setting_name` dan `setting_value`.

2.  **Sesuaikan Path di Atas Skrip**:
    * `$hero_upload_base_web_path`: Ganti `'/uploads/hero_images/'` dengan path aktual dari web root ke folder tempat Anda ingin menyimpan gambar hero.
    * Path untuk placeholder default.

3.  **Periksa Navbar**: Pastikan link dan kelas `active` di navbar sudah sesuai dengan semua halaman admin Anda.

4.  **Uji Halaman**: Coba unggah gambar dan simpan teks. Periksa apakah data tersimpan di database dan gambar tampil dengan benar. Periksa juga log error server jika ada masalah.

Ini adalah kerangka yang cukup lengkap. Anda mungkin perlu menyesuaikan beberapa detail kecil sesuai dengan kebutuhan spesifik dan struktur database/file An