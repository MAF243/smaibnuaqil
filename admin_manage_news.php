<?php
// FILE: admin_manage_news.php

// 1. Mulai Sesi (WAJIB PALING ATAS)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Pengaturan Error Reporting untuk Development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 3. Cegah Cache
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 4. Cek Login Admin
if (!isset($_SESSION['admin'])) { // Sesuaikan dengan nama variabel session admin Anda
    $_SESSION['error_message'] = "Anda harus login untuk mengakses halaman ini.";
    header("Location: login.php");
    exit;
}

// 5. Sertakan File Konfigurasi Database
if (file_exists('config.php')) {
    include_once 'config.php';
} else {
    error_log("CRITICAL: config.php tidak ditemukan di admin_manage_news.php");
    exit("<!DOCTYPE html><html><head><title>Error</title></head><body>Error: Config.php tidak ditemukan. Harap hubungi administrator.</body></html>");
}

// 6. Pastikan Koneksi $conn Berhasil
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $db_error_msg_news = isset($conn) && $conn instanceof mysqli ? $conn->connect_error : 'Objek koneksi DB tidak terdefinisi.';
    error_log("CRITICAL: Koneksi database gagal di admin_manage_news.php: " . $db_error_msg_news);
    exit("<!DOCTYPE html><html><head><title>Error</title></head><body>Error: Koneksi database gagal. Detail: ".htmlspecialchars($db_error_msg_news).". Harap hubungi administrator.</body></html>");
}

// MEMPERBAIKI MASALAH: Define site_url jika belum ada (misalnya dari config.php)
// Idealnya, $site_url didefinisikan di config.php untuk penggunaan di seluruh situs.
if (!isset($site_url)) {
    // Cara dinamis untuk menentukan site_url
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST']; // Ini seharusnya menghasilkan 'smaibnuaqil.my.id'
    $site_url = $protocol . $domainName;

    // Untuk kasus spesifik Anda (smaibnuaqil.my.id dengan HTTPS), Anda juga bisa menggunakan ini:
    // $site_url = "https://smaibnuaqil.my.id";
}


// Ambil data admin untuk navbar (dipertahankan dari kode news asli)
$admin_name = $_SESSION['admin_name'] ?? "Admin";
$admin_photo_url = 'public/asset/default_admin_avatar.png';
if (isset($_SESSION['admin_photo_url']) && !empty($_SESSION['admin_photo_url']) && file_exists($_SESSION['admin_photo_url'])) {
    $admin_photo_url = htmlspecialchars($_SESSION['admin_photo_url']);
} elseif (isset($_SESSION['admin_photo_filename']) && !empty($_SESSION['admin_photo_filename'])) {
    $potential_admin_photo_path = 'uploads/admin_photos/' . basename(htmlspecialchars($_SESSION['admin_photo_filename']));
    if (file_exists($potential_admin_photo_path)) {
        $admin_photo_url = $potential_admin_photo_path;
    }
}
$current_page_filename = basename($_SERVER['PHP_SELF']);


// Ambil pesan status dari session
$status_message_news = '';
$alert_class_news = '';
if (isset($_SESSION['status_message']) && isset($_SESSION['alert_class'])) {
    $status_message_news = htmlspecialchars($_SESSION['status_message']);
    $alert_class_news = htmlspecialchars($_SESSION['alert_class']);
    unset($_SESSION['status_message']);
    unset($_SESSION['alert_class']);
}

// Ambil semua berita untuk ditampilkan
$news_list_admin = [];
$error_message_display_news = null;
$sql_all_news = "SELECT id, title, LEFT(content, 100) AS content_preview, image_path, is_published, created_at FROM news ORDER BY created_at DESC";
$result_all_news = $conn->query($sql_all_news);

if ($result_all_news) {
    while($row = $result_all_news->fetch_assoc()){
        $news_list_admin[] = $row;
    }
    $result_all_news->free();
} else {
    $error_message_display_news = "Gagal mengambil daftar berita: " . htmlspecialchars($conn->error);
    error_log("Gagal query berita di admin_manage_news.php: " . $conn->error . " | Query: " . $sql_all_news);
}

// CSRF Token untuk delete
if (empty($_SESSION['csrf_token_news_delete'])) {
    if (function_exists('random_bytes')) {
        $_SESSION['csrf_token_news_delete'] = bin2hex(random_bytes(32));
    } else {
        $_SESSION['csrf_token_news_delete'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}
$csrf_token_delete_news = $_SESSION['csrf_token_news_delete'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Berita - Admin SMA Ibnu Aqil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/jpg" href="<?php echo rtrim($site_url ?? '.', '/'); ?>/public/asset/logo.jpg">
    <style>
        body { background-color: #f8f9fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-main { margin: 20px auto; max-width: 95%; background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.07); }
        .page-title { margin-bottom: 25px; text-align: center; color: #0dcaf0; font-weight: 600; font-size: 1.85rem; }
        .footer { text-align: center; padding: 20px; background-color: #212529; color: white; margin-top: 40px; }
        .action-buttons .btn { margin-right: 5px; margin-bottom: 5px; padding: 0.3rem 0.6rem; font-size:0.8rem; }
        th, td { vertical-align: middle; font-size: 0.9rem; }
        .news-thumb { width: 100px; height: 70px; object-fit: cover; border-radius: 4px; border: 1px solid #dee2e6; padding:2px; }
        #livePreviewContainer { display: none; position: fixed; bottom: 20px; right: 20px; width: 380px; height: 550px; border: 2px solid #0dcaf0; background-color: white; box-shadow: 0 5px 20px rgba(0,0,0,0.25); z-index: 1060; border-radius: 8px; overflow: hidden; flex-direction: column; }
        #livePreviewHeader { padding: 8px 10px; background-color: #0dcaf0; color: white; font-size: 0.9em; display: flex; justify-content: space-between; align-items: center; }
        #livePreviewHeader .btn-close-white { padding: 0.25rem 0.5rem; filter: brightness(0) invert(1); }
        #livePreviewFrame { width: 100%; height: calc(100% - 38px); border: none; }
    </style>
</head>
<body class="admin-authenticated">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="beranda_admin.php">
            <img src="<?php echo htmlspecialchars($avatar_untuk_ditampilkan); ?>" alt="Foto Admin" width="30" height="30" class="d-inline-block align-text-top rounded-circle me-2">
            adminq (Admin Panel)
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($current_page_filename) && $current_page_filename == 'beranda_admin.php' ? 'active' : ''); ?>" href="beranda_admin.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($current_page_filename) && $current_page_filename == 'admin_manage_facilities.php' ? 'active' : ''); ?>" href="admin_manage_facilities.php"><i class="bi bi-building-fill-gear me-1"></i> Fasilitas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($current_page_filename) && $current_page_filename == 'admin_manage_news.php' ? 'active' : ''); ?>" aria-current="page" href="admin_manage_news.php"><i class="bi bi-newspaper me-1"></i> Berita</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (isset($current_page_filename) && $current_page_filename == 'admin_manage_gallery.php' ? 'active' : ''); ?>" href="admin_manage_gallery.php"><i class="bi bi-images me-1"></i> Galeri</a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo rtrim($site_url ?? '.', '/'); ?>/index.php" target="_blank" title="Buka situs publik di tab baru">
                        <i class="bi bi-eye-fill me-1"></i> Lihat Situs
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo (isset($current_page_filename) && ($current_page_filename == 'admin_profile.php' || $current_page_filename == 'admin_settings.php') ? 'active' : ''); ?>" href="#" id="adminProfileDropdownTopNav" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i> Profil Admin
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminProfileDropdownTopNav">
                        <li><a class="dropdown-item <?php echo (isset($current_page_filename) && $current_page_filename == 'admin_profile.php' ? 'active' : ''); ?>" href="admin_profile.php"><i class="bi bi-person-badge me-2"></i>Profil Saya</a></li>
                        <li><a class="dropdown-item <?php echo (isset($current_page_filename) && $current_page_filename == 'admin_settings.php' ? 'active' : ''); ?>" href="admin_settings.php"><i class="bi bi-gear-fill me-2"></i>Pengaturan Akun</a></li>
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
            <h2 class="page-title text-info mb-0 me-3"><i class="bi bi-newspaper"></i> Kelola Berita</h2>
            <div class="mt-2 mt-md-0">
                 <button class="btn btn-outline-info btn-sm" id="togglePreviewBtn" title="Tampilkan/Sembunyikan Live Preview">
                    <i class="bi bi-display-fill"></i> Live Preview
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="refreshPreviewBtn" style="display:none;" title="Muat Ulang Preview">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
                <a href="admin_add_edit_news.php" class="btn btn-primary ms-2"><i class="bi bi-plus-circle-fill"></i> Tambah Berita</a>
            </div>
        </div>

        <?php if (!empty($status_message_news)): ?>
            <div class="alert <?php echo htmlspecialchars($alert_class_news); ?> alert-dismissible fade show" role="alert">
                <?php echo $status_message_news; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message_display_news) && $error_message_display_news): ?>
            <div class="alert alert-danger" role="alert">
                Terdapat masalah saat mengambil data: <?php echo $error_message_display_news; ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%; text-align:center;">ID</th>
                                <th style="width: 15%; text-align:center;">Gambar</th>
                                <th style="width: 25%;">Judul</th>
                                <th style="width: 25%;">Preview Konten</th>
                                <th style="width: 15%;">Tgl Dibuat</th>
                                <th style="width: 5%;" class="text-center">Status</th>
                                <th style="width: 10%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($news_list_admin) && !$error_message_display_news): ?>
                                <tr><td colspan="7" class="text-center fst-italic py-3">Belum ada berita. Silakan tambahkan berita baru.</td></tr>
                            <?php elseif (!empty($news_list_admin)): ?>
                                <?php foreach ($news_list_admin as $news_item): ?>
                                    <tr>
                                        <td style="text-align:center;"><?php echo htmlspecialchars($news_item['id']); ?></td>
                                        <td style="text-align:center;">
                                            <?php
                                            $news_img_path_admin = rtrim($site_url, '/') . '/public/asset/placeholder-news.jpg'; // Default dengan site_url
                                            if (!empty($news_item['image_path'])) {
                                                $potential_path = $news_item['image_path'];
                                                $resolved_path_for_file_exists = '';

                                                // 1. Cek jika path dari DB adalah path absolut URL (http/https)
                                                if (preg_match('/^https?:\/\//', $potential_path)) {
                                                    $news_img_path_admin = htmlspecialchars($potential_path);
                                                }
                                                // 2. Cek jika path relatif dari root web server (dimulai dengan /)
                                                elseif (strpos($potential_path, '/') === 0) {
                                                    $resolved_path_for_file_exists = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $potential_path;
                                                    if (file_exists($resolved_path_for_file_exists)) {
                                                        $news_img_path_admin = htmlspecialchars(rtrim($site_url, '/') . $potential_path);
                                                    }
                                                }
                                                // 3. Cek jika path relatif dari direktori saat ini (mis: 'uploads/news/file.jpg')
                                                else {
                                                    $resolved_path_for_file_exists = $potential_path; // Asumsi relatif terhadap skrip jika tidak dimulai /
                                                    if (file_exists($resolved_path_for_file_exists)) {
                                                         $news_img_path_admin = htmlspecialchars(rtrim($site_url, '/') . '/' . ltrim($potential_path, '/'));
                                                    }
                                                    // 3a. Fallback: Cek di folder uploads/news/ jika path hanya nama file
                                                    elseif (file_exists('uploads/news/' . basename($potential_path))) {
                                                       $news_img_path_admin = htmlspecialchars(rtrim($site_url, '/') . '/uploads/news/' . basename($potential_path));
                                                    }
                                                }
                                            }
                                            ?>
                                            <img src="<?php echo $news_img_path_admin; ?>?t=<?php echo time();?>" alt="<?php echo htmlspecialchars($news_item['title']); ?>" class="news-thumb img-thumbnail">
                                        </td>
                                        <td><?php echo htmlspecialchars($news_item['title']); ?></td>
                                        <td><?php echo htmlspecialchars($news_item['content_preview']); ?>...</td>
                                        <td><?php echo htmlspecialchars(date('d M Y, H:i', strtotime($news_item['created_at']))); ?> WIB</td>
                                        <td class="text-center">
                                            <span class="badge bg-<?php echo ($news_item['is_published'] ?? 0) ? 'success' : 'secondary'; ?>">
                                                <?php echo ($news_item['is_published'] ?? 0) ? 'Published' : 'Draft'; ?>
                                            </span>
                                        </td>
                                        <td class="text-center action-buttons">
                                            <a href="admin_add_edit_news.php?id=<?php echo htmlspecialchars($news_item['id']); ?>" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                            <button type="button" class="btn btn-sm btn-danger" title="Hapus" onclick="confirmDeleteNews(<?php echo htmlspecialchars($news_item['id']); ?>, '<?php echo $csrf_token_delete_news; ?>', '<?php echo htmlspecialchars(addslashes($news_item['title'])); ?>');"><i class="bi bi-trash-fill"></i></button>
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

    <div id="livePreviewContainer">
        <div id="livePreviewHeader">
            <strong><i class="bi bi-display"></i> Live Preview: <span id="previewTargetTitle">index.php</span></strong>
            <button type="button" class="btn-close btn-close-white btn-sm" aria-label="Close" id="closePreviewBtn" title="Tutup Preview"></button>
        </div>
        <iframe id="livePreviewFrame" src="<?php echo rtrim($site_url ?? '.', '/'); ?>/index.php"></iframe>
    </div>

    <footer class="footer">
        <div class="container"> <p>&copy; <?php echo date("Y"); ?> SMA Ibnu Aqil. Admin Panel. Hak Cipta Dilindungi.</p> </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const siteUrlForJs = '<?php echo rtrim($site_url ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'], '/'); ?>';

        function confirmDeleteNews(newsId, csrfToken, newsTitle) {
            if (confirm('Apakah Anda yakin ingin menghapus berita: "' + newsTitle.replace(/\\'/g, "'").replace(/\\"/g, '"') + '"? Tindakan ini tidak dapat diurungkan.')) {
                window.location.href = 'admin_delete_news.php?id=' + newsId + '&token=' + csrfToken;
            }
        }

        const togglePreviewBtn = document.getElementById('togglePreviewBtn');
        const livePreviewContainer = document.getElementById('livePreviewContainer');
        const livePreviewFrame = document.getElementById('livePreviewFrame');
        const closePreviewBtn = document.getElementById('closePreviewBtn');
        const refreshPreviewBtn = document.getElementById('refreshPreviewBtn');
        const previewTargetTitle = document.getElementById('previewTargetTitle');
        let currentPreviewUrl = siteUrlForJs + '/index.php';

        if(togglePreviewBtn) {
            togglePreviewBtn.addEventListener('click', function() {
                if (livePreviewContainer && livePreviewFrame) {
                    if (livePreviewContainer.style.display === 'none' || livePreviewContainer.style.display === '') {
                        livePreviewContainer.style.display = 'flex';
                        livePreviewFrame.src = currentPreviewUrl + (currentPreviewUrl.includes('?') ? '&' : '?') + 'timestamp=' + new Date().getTime();
                        if(refreshPreviewBtn) refreshPreviewBtn.style.display = 'inline-block';
                        previewTargetTitle.textContent = currentPreviewUrl.replace(siteUrlForJs + '/', '').split('?')[0] || 'index.php';
                    } else {
                        livePreviewContainer.style.display = 'none';
                        if(refreshPreviewBtn) refreshPreviewBtn.style.display = 'none';
                    }
                }
            });
        }

        if(closePreviewBtn && livePreviewContainer) {
            closePreviewBtn.addEventListener('click', function() {
                livePreviewContainer.style.display = 'none';
                if(refreshPreviewBtn) refreshPreviewBtn.style.display = 'none';
            });
        }

        if(refreshPreviewBtn && livePreviewFrame) {
            refreshPreviewBtn.addEventListener('click', function() {
                livePreviewFrame.src = currentPreviewUrl + (currentPreviewUrl.includes('?') ? '&' : '?') + 'timestamp=' + new Date().getTime();
            });
        }

        if (window.performance && window.performance.getEntriesByType) {
            if (window.performance.getEntriesByType("navigation")[0].type === "back_forward") {
                // window.location.reload();
            }
        }

        window.onload = function () {
            if (window.history.replaceState) {
                const url = new URL(window.location.href);
                let paramsChanged = false;
                if (url.searchParams.has('status')) { url.searchParams.delete('status'); paramsChanged = true; }
                if (url.searchParams.has('message')) { url.searchParams.delete('message'); paramsChanged = true; }
                if (paramsChanged) {
                    window.history.replaceState({path: url.href}, '', url.href);
                }
            }
        };
    </script>
</body>
</html>