<?php
// FILE: admin_add_edit_news.php (Disesuaikan dengan DB: title, content)

// Mulai sesi
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cegah cache
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Cek login admin
if (!isset($_SESSION['admin'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Sertakan file konfigurasi database
if (file_exists('config.php')) {
    include_once 'config.php';
} else {
    exit('File konfigurasi database (config.php) tidak ditemukan. Halaman tidak dapat dimuat.');
}

// Pastikan koneksi database ada
if (!isset($conn) || $conn->connect_error) {
    error_log("Koneksi database gagal di admin_add_edit_news.php: " . ($conn->connect_error ?? 'Unknown error'));
    exit('Koneksi database gagal. Harap hubungi administrator.');
}

// Ambil data admin untuk Navbar
$admin_id_sess = $_SESSION['admin_id'] ?? $_SESSION['admin'] ?? null;
$admin_name_sess = "Admin";
$admin_photo_url_sess = 'public/asset/user.png'; // Default path

if ($admin_id_sess) {
    if (isset($_SESSION['admin_name'])) $admin_name_sess = htmlspecialchars($_SESSION['admin_name']);
    if (isset($_SESSION['admin_photo_url']) && !empty(trim($_SESSION['admin_photo_url']))) {
        if (filter_var($_SESSION['admin_photo_url'], FILTER_VALIDATE_URL) || file_exists($_SESSION['admin_photo_url'])) {
            $admin_photo_url_sess = htmlspecialchars($_SESSION['admin_photo_url']);
        }
    } elseif (isset($_SESSION['admin_photo_filename']) && !empty(trim($_SESSION['admin_photo_filename']))) {
        $potential_admin_photo_path = 'uploads/admin_photos/' . htmlspecialchars($_SESSION['admin_photo_filename']);
        if (file_exists($potential_admin_photo_path)) {
            $admin_photo_url_sess = $potential_admin_photo_path;
        }
    }
}

$news_id = $_GET['id'] ?? null;
$page_title = $news_id ? "Edit Berita" : "Tambah Berita Baru";
$news_data = [
    'title' => '', // Disesuaikan dengan DB
    'content' => '', // Disesuaikan dengan DB
    'news_image_path' => null,
    'is_published' => 1
];
$error_messages = [];
$success_message = "";

define('NEWS_UPLOAD_DIR', 'uploads/news_images/');

if ($news_id) {
    // Mengambil kolom: title, content, news_image_path, is_published
    $query = "SELECT title, content, news_image_path, is_published FROM news WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $news_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && $fetched_data = mysqli_fetch_assoc($result)) {
            $news_data = $fetched_data;
        } else {
            $error_messages[] = "Berita dengan ID tersebut tidak ditemukan.";
            $news_id = null;
            $page_title = "Tambah Berita Baru";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_messages[] = "Gagal menyiapkan query untuk mengambil berita: " . mysqli_error($conn);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_title = trim($_POST['title'] ?? ''); // Disesuaikan dengan DB & form name
    $posted_content = trim($_POST['content'] ?? ''); // Disesuaikan dengan DB & form name
    $posted_is_published = isset($_POST['is_published']) ? 1 : 0;
    $current_image_filename = $_POST['current_image_filename'] ?? $news_data['news_image_path'] ?? null;
    
    $news_data['title'] = $posted_title;
    $news_data['content'] = $posted_content;
    $news_data['is_published'] = $posted_is_published;

    $new_image_filename = $current_image_filename;

    if (empty($posted_title)) {
        $error_messages[] = "Judul berita tidak boleh kosong.";
    }
    if (empty($posted_content)) {
        $error_messages[] = "Isi berita tidak boleh kosong.";
    }

    if (isset($_FILES['news_image_file']) && $_FILES['news_image_file']['error'] === UPLOAD_ERR_OK) {
        $target_dir = NEWS_UPLOAD_DIR;
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                $error_messages[] = "Gagal membuat direktori upload: " . $target_dir;
            }
        }

        if (empty($error_messages)) {
            $original_filename = basename($_FILES['news_image_file']['name']);
            $image_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
            $unique_filename = uniqid('berita_') . '.' . $image_extension;
            $target_file_path = $target_dir . $unique_filename;

            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($image_extension, $allowed_extensions)) {
                $error_messages[] = "Hanya file JPG, JPEG, PNG, & GIF yang diperbolehkan untuk gambar berita.";
            }
            if ($_FILES['news_image_file']['size'] > 5 * 1024 * 1024) {
                $error_messages[] = "Ukuran file gambar berita maksimal 5MB.";
            }

            if (empty($error_messages)) {
                if (move_uploaded_file($_FILES['news_image_file']['tmp_name'], $target_file_path)) {
                    $new_image_filename = $unique_filename;
                    if ($news_id && $current_image_filename && file_exists($target_dir . $current_image_filename) && $current_image_filename !== $new_image_filename) {
                        unlink($target_dir . $current_image_filename);
                    }
                } else {
                    $error_messages[] = "Gagal mengupload gambar berita. Error code: " . $_FILES['news_image_file']['error'];
                }
            }
        }
    } elseif (!$news_id && empty($current_image_filename) && (!isset($_FILES['news_image_file']) || $_FILES['news_image_file']['error'] !== UPLOAD_ERR_OK)) {
        $error_messages[] = "Anda harus mengunggah gambar untuk berita baru.";
    }
    $news_data['news_image_path'] = $new_image_filename;

    if (empty($error_messages)) {
        if ($news_id) { // Mode EDIT
            // Kolom DB: title, content, news_image_path, is_published, updated_at
            $sql = "UPDATE news SET title = ?, content = ?, news_image_path = ?, is_published = ?, updated_at = NOW() WHERE id = ?";
            $stmt_db = mysqli_prepare($conn, $sql);
            if ($stmt_db) {
                mysqli_stmt_bind_param($stmt_db, 'sssii', $posted_title, $posted_content, $new_image_filename, $posted_is_published, $news_id);
                if (mysqli_stmt_execute($stmt_db)) {
                    $success_message = "Berita berhasil diperbarui.";
                } else {
                    $error_messages[] = "Gagal memperbarui berita: " . mysqli_stmt_error($stmt_db);
                }
                mysqli_stmt_close($stmt_db);
            } else {
                $error_messages[] = "Gagal menyiapkan query UPDATE: " . mysqli_error($conn);
            }
        } else { // Mode TAMBAH BARU
            // Kolom DB: title, content, news_image_path, is_published, created_at, updated_at
            $sql = "INSERT INTO news (title, content, news_image_path, is_published, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
            $stmt_db = mysqli_prepare($conn, $sql);
            if ($stmt_db) {
                mysqli_stmt_bind_param($stmt_db, 'sssi', $posted_title, $posted_content, $new_image_filename, $posted_is_published);
                if (mysqli_stmt_execute($stmt_db)) {
                    $new_inserted_id = mysqli_insert_id($conn);
                    $_SESSION['manage_news_success_message'] = "Berita baru berhasil ditambahkan.";
                    header("Location: admin_manage_news.php?highlight_id=" . $new_inserted_id);
                    exit;
                } else {
                    $error_messages[] = "Gagal menambahkan berita: " . mysqli_stmt_error($stmt_db);
                }
                mysqli_stmt_close($stmt_db);
            } else {
                $error_messages[] = "Gagal menyiapkan query INSERT: " . mysqli_error($conn);
            }
        }
    }
}

if (isset($conn)) {
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title); ?> - Admin SMA Ibnu Aqil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/jpg" href="public/asset/logo.jpg">
    <style>
        body { background-color: #f8f9fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background-color: #007bff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); min-height: 56px; }
        .container-form { margin: 30px auto; max-width: 800px; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.07); }
        .form-title { margin-bottom: 25px; text-align: center; color: #007bff; font-weight: 600; font-size: 2rem; }
        .footer { text-align: center; padding: 20px; background-color: #343a40; color: white; margin-top: 40px; }
        .img-thumbnail-preview { max-width: 250px; height: auto; margin-top: 10px; border: 1px solid #ddd; padding: 5px; border-radius: 4px; }
    </style>
</head>
<body class="admin-authenticated">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="beranda_admin.php">
                <img src="<?= htmlspecialchars($admin_photo_url_sess); ?>" alt="Foto Admin" width="30" height="30" class="d-inline-block align-text-top rounded-circle me-2">
                <?= htmlspecialchars($admin_name_sess); ?> (Admin Panel)
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="beranda_admin.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="beranda_admin.php"><i class="bi bi-people-fill me-1"></i> Pendaftar</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_manage_facilities.php"><i class="bi bi-building-fill-gear me-1"></i> Fasilitas</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="admin_manage_news.php"><i class="bi bi-newspaper me-1"></i> Berita</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_manage_gallery.php"><i class="bi bi-images me-1"></i> Galeri</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin_manage_admins.php"><i class="bi bi-person-gear me-1"></i> Admin</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php" target="_blank"><i class="bi bi-eye-fill me-1"></i> Lihat Situs</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminProfileDropdownTop" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> Profil
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminProfileDropdownTop">
                            <li><a class="dropdown-item" href="admin_profile.php"><i class="bi bi-person-badge me-2"></i>Profil Saya</a></li>
                            <li><a class="dropdown-item" href="admin_settings.php"><i class="bi bi-gear-fill me-2"></i>Pengaturan Akun</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php" onclick="return confirm('Yakin ingin logout?')"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-form mt-4">
        <h2 class="form-title"><?= htmlspecialchars($page_title); ?></h2>

        <?php if (!empty($error_messages)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Terjadi Kesalahan:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($error_messages as $msg): ?>
                        <li><?= htmlspecialchars($msg); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="admin_add_edit_news.php<?= ($news_id ? '?id='.$news_id : ''); ?>" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="title_input" class="form-label">Judul Berita <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title_input" name="title" value="<?= htmlspecialchars($news_data['title']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="content_input" class="form-label">Isi Berita <span class="text-danger">*</span></label>
                <textarea class="form-control" id="content_input" name="content" rows="10" required><?= htmlspecialchars($news_data['content']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="news_image_file_input" class="form-label">Gambar Berita <?= ($news_id && !empty($news_data['news_image_path'])) ? '(Opsional jika mengganti)' : '<span class="text-danger">*</span>'; ?></label>
                <input type="file" class="form-control" id="news_image_file_input" name="news_image_file" accept="image/jpeg,image/png,image/gif" <?= (!$news_id && empty($news_data['news_image_path'])) ? 'required' : ''; ?>>
                
                <?php if (!empty($news_data['news_image_path'])): ?>
                    <input type="hidden" name="current_image_filename" value="<?= htmlspecialchars($news_data['news_image_path']); ?>">
                    <?php if (file_exists(NEWS_UPLOAD_DIR . $news_data['news_image_path'])): ?>
                        <p class="mt-2">Gambar saat ini:</p>
                        <img src="<?= htmlspecialchars(NEWS_UPLOAD_DIR . $news_data['news_image_path']); ?>?t=<?= time(); ?>" alt="Gambar Berita Saat Ini" class="img-thumbnail img-thumbnail-preview">
                        <div class="form-text">Unggah file baru untuk mengganti gambar ini.</div>
                    <?php else: ?>
                        <p class="mt-2 text-warning">File gambar saat ini (<?= htmlspecialchars($news_data['news_image_path']); ?>) tidak ditemukan. Unggah gambar baru jika diperlukan.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="form-text">Pilih file gambar (JPG, JPEG, PNG, GIF, maks 5MB).</div>
                <?php endif; ?>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_published_input" name="is_published" value="1" <?= ((isset($news_data['is_published']) && $news_data['is_published'] == 1) ? 'checked' : ''); ?>>
                <label class="form-check-label" for="is_published_input">Terbitkan Berita Ini</label>
                <div class="form-text">Centang untuk langsung menerbitkan berita, atau biarkan tidak tercentang sebagai draft.</div>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
            <a href="admin_manage_news.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Batal</a>
        </form>
    </div>

    <footer class="footer">
        <div class="container"> <p>&copy; <?= date("Y"); ?> SMA Ibnu Aqil. Hak Cipta Dilindungi.</p> </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('load', function () {
            if (!document.body.classList.contains('admin-authenticated')) {
                // console.warn('Admin class not found');
            }
        });
    </script>
</body>
</html>