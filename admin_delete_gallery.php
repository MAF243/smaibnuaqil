<?php
// FILE: admin_manage_news.php (Versi Terintegrasi: List, Add Form, Edit Form, Delete)

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
    error_log("Koneksi database gagal di admin_manage_news.php: " . ($conn->connect_error ?? 'Unknown error'));
    exit('Koneksi database gagal. Harap hubungi administrator.');
}

// Ambil data admin untuk Navbar
$admin_id_sess = $_SESSION['admin_id'] ?? $_SESSION['admin'] ?? null;
$admin_name_sess = "Admin";
$admin_photo_url_sess = 'public/asset/user.png';

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

define('NEWS_UPLOAD_DIR', 'uploads/news_images/');

// --- Inisialisasi Variabel ---
$action = $_GET['action'] ?? 'list'; // Default: tampilkan daftar
$news_id = $_GET['id'] ?? null;      // ID untuk edit atau delete

$page_main_title = "Kelola Berita"; // Judul utama halaman
$form_section_title = "";         // Judul untuk bagian form

// Data untuk form, diisi saat edit atau jika ada error validasi
$form_data = [
    'id' => null, // Untuk membedakan add/edit di form
    'title' => '', // Sesuai DB
    'content' => '', // Sesuai DB
    'news_image_path' => null, // Nama file gambar
    'is_published' => 1       // Default terbit untuk item baru
];

$form_errors = []; // Pesan error untuk form
$form_success_msg = ""; // Pesan sukses untuk form (jika tidak redirect)

// Pesan untuk halaman daftar (biasanya dari session setelah redirect)
$list_success_msg = $_SESSION['list_view_success_message'] ?? "";
if ($list_success_msg) unset($_SESSION['list_view_success_message']);

$list_error_msg = $_SESSION['list_view_error_message'] ?? "";
if ($list_error_msg) unset($_SESSION['list_view_error_message']);


// --- Logika Penanganan Aksi ---

// AKSI: ADD (Menampilkan form tambah) atau EDIT (Mengambil data dan menampilkan form edit)
if ($action === 'add' || ($action === 'edit' && $news_id)) {
    if ($action === 'add') {
        $page_main_title = "Tambah Berita Baru";
        $form_section_title = "Formulir Tambah Berita";
        // $form_data sudah diinisialisasi untuk 'add'
    } else { // action === 'edit'
        $page_main_title = "Edit Berita";
        $form_section_title = "Formulir Edit Berita";
        // Ambil data dari DB untuk di-edit
        $stmt_fetch = mysqli_prepare($conn, "SELECT id, title, content, news_image_path, is_published FROM news WHERE id = ?");
        if ($stmt_fetch) {
            mysqli_stmt_bind_param($stmt_fetch, 'i', $news_id);
            mysqli_stmt_execute($stmt_fetch);
            $result_fetch = mysqli_stmt_get_result($stmt_fetch);
            if ($result_fetch && $db_data = mysqli_fetch_assoc($result_fetch)) {
                $form_data = $db_data; // Isi $form_data dengan data dari DB
            } else {
                $_SESSION['list_view_error_message'] = "Berita dengan ID '$news_id' tidak ditemukan.";
                header("Location: admin_manage_news.php?action=list");
                exit;
            }
            mysqli_stmt_close($stmt_fetch);
        } else {
            $form_errors[] = "Gagal menyiapkan query untuk mengambil data berita: " . mysqli_error($conn);
            // Tetap tampilkan form dengan pesan error ini
        }
    }

    // Penanganan SUBMIT FORM untuk ADD atau EDIT
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_news_form_button'])) {
        // Ambil data dari POST
        $posted_title = trim($_POST['title'] ?? ''); // Sesuai DB & form name
        $posted_content = trim($_POST['content'] ?? ''); // Sesuai DB & form name
        $posted_is_published = isset($_POST['is_published']) ? 1 : 0;
        $current_image_filename_from_form = $_POST['current_image_filename'] ?? null;
        
        // Update $form_data dengan nilai yang di-POST untuk repopulasi jika ada error
        $form_data['title'] = $posted_title;
        $form_data['content'] = $posted_content;
        $form_data['is_published'] = $posted_is_published;
        // $form_data['news_image_path'] akan diupdate jika ada upload baru
        // Jika tidak ada upload baru, $form_data['news_image_path'] akan tetap dari data awal (edit) atau null (add)
        // Jika edit, $current_image_filename_from_form akan menjadi basis jika tidak ada upload baru
        $form_data['news_image_path'] = $action === 'edit' ? $current_image_filename_from_form : null;


        $new_uploaded_filename = $form_data['news_image_path']; // Inisialisasi dengan gambar lama (edit) atau null (add)

        // Validasi Input
        if (empty($posted_title)) {
            $form_errors[] = "Judul berita tidak boleh kosong.";
        }
        if (empty($posted_content)) {
            $form_errors[] = "Isi berita tidak boleh kosong.";
        }

        // Penanganan Upload Gambar Baru
        if (isset($_FILES['news_image_upload_file']) && $_FILES['news_image_upload_file']['error'] === UPLOAD_ERR_OK) {
            $target_dir = NEWS_UPLOAD_DIR;
            if (!is_dir($target_dir)) {
                if (!mkdir($target_dir, 0755, true)) {
                    $form_errors[] = "Gagal membuat direktori upload: " . $target_dir;
                }
            }

            if (empty($form_errors)) {
                $original_filename = basename($_FILES['news_image_upload_file']['name']);
                $image_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
                $unique_filename_for_upload = uniqid('berita_') . '.' . $image_extension;
                $target_file_path = $target_dir . $unique_filename_for_upload;

                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($image_extension, $allowed_extensions)) {
                    $form_errors[] = "Hanya file JPG, JPEG, PNG, & GIF yang diperbolehkan.";
                }
                if ($_FILES['news_image_upload_file']['size'] > 5 * 1024 * 1024) { // 5MB
                    $form_errors[] = "Ukuran file gambar maksimal 5MB.";
                }

                if (empty($form_errors)) {
                    if (move_uploaded_file($_FILES['news_image_upload_file']['tmp_name'], $target_file_path)) {
                        $new_uploaded_filename = $unique_filename_for_upload;
                        // Hapus gambar lama jika ini mode edit, ada gambar lama, dan gambar baru berhasil diupload & berbeda
                        if ($action === 'edit' && $current_image_filename_from_form && file_exists($target_dir . $current_image_filename_from_form) && $current_image_filename_from_form !== $new_uploaded_filename) {
                            unlink($target_dir . $current_image_filename_from_form);
                        }
                    } else {
                        $form_errors[] = "Gagal memindahkan file gambar yang diupload. Kode Error: " . $_FILES['news_image_upload_file']['error'];
                    }
                }
            }
        } elseif ($action === 'add' && empty($new_uploaded_filename)) { // Mode tambah & tidak ada gambar (lama atau baru)
            $form_errors[] = "Gambar berita wajib diunggah untuk berita baru.";
        }
        // Update $form_data['news_image_path'] dengan nama file yang relevan setelah proses upload
        $form_data['news_image_path'] = $new_uploaded_filename;
        
        // Jika tidak ada error, lakukan operasi Database
        if (empty($form_errors)) {
            if ($action === 'edit' && $form_data['id']) { // Mode EDIT
                $sql_update = "UPDATE news SET title = ?, content = ?, news_image_path = ?, is_published = ?, updated_at = NOW() WHERE id = ?";
                $stmt_update_db = mysqli_prepare($conn, $sql_update);
                if ($stmt_update_db) {
                    mysqli_stmt_bind_param($stmt_update_db, 'sssii', $posted_title, $posted_content, $new_uploaded_filename, $posted_is_published, $form_data['id']);
                    if (mysqli_stmt_execute($stmt_update_db)) {
                        $_SESSION['list_view_success_message'] = "Berita berhasil diperbarui.";
                        header("Location: admin_manage_news.php?action=list&highlight_id=" . $form_data['id']);
                        exit;
                    } else {
                        $form_errors[] = "Gagal memperbarui berita ke database: " . mysqli_stmt_error($stmt_update_db);
                    }
                    mysqli_stmt_close($stmt_update_db);
                } else {
                     $form_errors[] = "Gagal menyiapkan statement UPDATE: " . mysqli_error($conn);
                }
            } elseif ($action === 'add') { // Mode ADD
                $sql_insert = "INSERT INTO news (title, content, news_image_path, is_published, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
                $stmt_insert_db = mysqli_prepare($conn, $sql_insert);
                if ($stmt_insert_db) {
                    mysqli_stmt_bind_param($stmt_insert_db, 'sssi', $posted_title, $posted_content, $new_uploaded_filename, $posted_is_published);
                    if (mysqli_stmt_execute($stmt_insert_db)) {
                        $newly_inserted_id = mysqli_insert_id($conn);
                        $_SESSION['list_view_success_message'] = "Berita baru berhasil ditambahkan.";
                        header("Location: admin_manage_news.php?action=list&highlight_id=" . $newly_inserted_id);
                        exit;
                    } else {
                        $form_errors[] = "Gagal menambahkan berita ke database: " . mysqli_stmt_error($stmt_insert_db);
                    }
                    mysqli_stmt_close($stmt_insert_db);
                } else {
                    $form_errors[] = "Gagal menyiapkan statement INSERT: " . mysqli_error($conn);
                }
            }
        }
        // Jika ada error, $form_data sudah diupdate dengan nilai POST dan $new_uploaded_filename, jadi form akan terisi kembali.
    }
} 
// AKSI: DELETE (Menghapus berita)
elseif ($action === 'delete' && $news_id && isset($_POST['confirm_delete_action_button'])) {
    $image_filename_to_delete = null;
    $stmt_fetch_img = mysqli_prepare($conn, "SELECT news_image_path FROM news WHERE id = ?");
    if ($stmt_fetch_img) {
        mysqli_stmt_bind_param($stmt_fetch_img, 'i', $news_id);
        mysqli_stmt_execute($stmt_fetch_img);
        $result_img_fetch = mysqli_stmt_get_result($stmt_fetch_img);
        if ($row_img_data = mysqli_fetch_assoc($result_img_fetch)) {
            $image_filename_to_delete = $row_img_data['news_image_path'];
        }
        mysqli_stmt_close($stmt_fetch_img);
    }

    $stmt_delete_db = mysqli_prepare($conn, "DELETE FROM news WHERE id = ?");
    if ($stmt_delete_db) {
        mysqli_stmt_bind_param($stmt_delete_db, 'i', $news_id);
        if (mysqli_stmt_execute($stmt_delete_db)) {
            if (mysqli_stmt_affected_rows($stmt_delete_db) > 0) {
                if ($image_filename_to_delete && file_exists(NEWS_UPLOAD_DIR . $image_filename_to_delete)) {
                    unlink(NEWS_UPLOAD_DIR . $image_filename_to_delete);
                }
                $_SESSION['list_view_success_message'] = "Berita berhasil dihapus.";
            } else {
                $_SESSION['list_view_error_message'] = "Berita tidak ditemukan atau gagal dihapus.";
            }
        } else {
            $_SESSION['list_view_error_message'] = "Gagal menghapus berita: " . mysqli_stmt_error($stmt_delete_db);
        }
        mysqli_stmt_close($stmt_delete_db);
    } else {
        $_SESSION['list_view_error_message'] = "Gagal menyiapkan statement DELETE: " . mysqli_error($conn);
    }
    header("Location: admin_manage_news.php?action=list");
    exit;
}

// AKSI: LIST (Mengambil semua berita untuk ditampilkan) - default jika tidak ada aksi lain atau setelah operasi
$all_news_items_list = [];
if ($action === 'list') {
    $page_main_title = "Kelola Berita"; // Set judul untuk halaman daftar
    $query_all_news = "SELECT id, title, LEFT(content, 100) AS short_content, news_image_path, is_published, updated_at FROM news ORDER BY updated_at DESC";
    $result_all_news = mysqli_query($conn, $query_all_news);
    if ($result_all_news) {
        while ($news_row = mysqli_fetch_assoc($result_all_news)) {
            $all_news_items_list[] = $news_row;
        }
    } else {
        $list_error_msg = "Gagal mengambil daftar berita: " . mysqli_error($conn); // Tampilkan error jika query gagal
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_main_title); ?> - Admin SMA Ibnu Aqil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/jpg" href="public/asset/logo.jpg">
    <style>
        body { background-color: #f8f9fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background-color: #007bff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); min-height: 56px; }
        .container-main { margin: 20px auto; max-width: 1100px; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.07); }
        .page-header-title { margin-bottom: 25px; color: #007bff; font-weight: 600; font-size: 2rem; }
        .form-section-title { margin-bottom: 25px; text-align: center; color: #007bff; font-weight: 600; font-size: 1.75rem; }
        .footer { text-align: center; padding: 20px; background-color: #343a40; color: white; margin-top: 40px; }
        .img-thumbnail-preview { max-width: 200px; height: auto; margin-top: 10px; border: 1px solid #ddd; padding: 5px; border-radius: 4px; }
        .news-table-img { max-width: 100px; max-height:75px; object-fit:cover; height: auto; border-radius: 4px; }
        .action-buttons .btn { margin-right: 5px; margin-bottom: 5px;}
        .highlight { background-color: #fff3cd !important; }
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

    <div class="container container-main mt-4">
        <?php if ($action === 'list'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-header-title mb-0"><i class="bi bi-newspaper me-2"></i><?= htmlspecialchars($page_main_title); ?></h1>
                <a href="admin_manage_news.php?action=add" class="btn btn-primary"><i class="bi bi-plus-circle-fill me-2"></i>Tambah Berita Baru</a>
            </div>

            <?php if (!empty($list_success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($list_success_msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($list_error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($list_error_msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Gambar</th>
                            <th>Judul Berita</th>
                            <th>Ringkasan</th>
                            <th>Status</th>
                            <th>Pembaruan Terakhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($all_news_items_list)): ?>
                            <?php 
                            $counter_list_display = 1;
                            $highlight_id_from_url = $_GET['highlight_id'] ?? null;
                            ?>
                            <?php foreach ($all_news_items_list as $item_news): ?>
                            <tr <?= ($item_news['id'] == $highlight_id_from_url) ? 'class="highlight"' : ''; ?>>
                                <td><?= $counter_list_display++; ?></td>
                                <td>
                                    <?php if (!empty($item_news['news_image_path']) && file_exists(NEWS_UPLOAD_DIR . $item_news['news_image_path'])): ?>
                                        <img src="<?= htmlspecialchars(NEWS_UPLOAD_DIR . $item_news['news_image_path']); ?>?t=<?= time(); ?>" alt="Gambar Berita" class="news-table-img img-thumbnail">
                                    <?php else: ?>
                                        <small class="text-muted">Tidak ada gambar</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($item_news['title']); ?></td>
                                <td><?= htmlspecialchars($item_news['short_content']); ?>...</td>
                                <td>
                                    <?= ($item_news['is_published'] == 1) ? '<span class="badge bg-success">Diterbitkan</span>' : '<span class="badge bg-warning text-dark">Draft</span>'; ?>
                                </td>
                                <td><?= htmlspecialchars(date('d M Y, H:i', strtotime($item_news['updated_at']))); ?> WIB</td>
                                <td class="action-buttons">
                                    <a href="admin_manage_news.php?action=edit&id=<?= $item_news['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal<?= $item_news['id']; ?>" title="Hapus">
                                        <i class="bi bi-trash-fill"></i> Hapus
                                    </button>
                                    <div class="modal fade" id="deleteConfirmationModal<?= $item_news['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $item_news['id']; ?>" aria-hidden="true">
                                      <div class="modal-dialog">
                                        <div class="modal-content">
                                          <div class="modal-header">
                                            <h5 class="modal-title" id="deleteModalLabel<?= $item_news['id']; ?>">Konfirmasi Hapus Berita</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                          </div>
                                          <div class="modal-body">
                                            Yakin ingin menghapus berita "<strong><?= htmlspecialchars($item_news['title']); ?></strong>"?
                                          </div>
                                          <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <form action="admin_manage_news.php?action=delete&id=<?= $item_news['id']; ?>" method="POST" style="display: inline;">
                                                <input type="hidden" name="confirm_delete_action_button" value="1">
                                                <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                                            </form>
                                          </div>
                                        </div>
                                      </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">Belum ada berita.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; // Akhir dari $action === 'list' ?>


        <?php if ($action === 'add' || ($action === 'edit' && $news_id)): ?>
            <h2 class="form-section-title mt-5"><?= htmlspecialchars($form_section_title); ?></h2>

            <?php if (!empty($form_errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Terjadi Kesalahan pada Form:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($form_errors as $err_msg): ?>
                            <li><?= htmlspecialchars($err_msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($form_success_msg)): // Pesan sukses spesifik form, jika tidak redirect ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($form_success_msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="admin_manage_news.php?action=<?= $action; ?><?= ($form_data['id'] ? '&id='.$form_data['id'] : ''); ?>" method="POST" enctype="multipart/form-data" class="mb-5">
                <input type="hidden" name="submit_news_form_button" value="1">
                <?php if ($action === 'edit' && $form_data['id']): ?>
                    <input type="hidden" name="news_id_hidden_field" value="<?= htmlspecialchars($form_data['id']); ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="title_form_input" class="form-label">Judul Berita <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title_form_input" name="title" value="<?= htmlspecialchars($form_data['title']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="content_form_input" class="form-label">Isi Berita <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="content_form_input" name="content" rows="10" required><?= htmlspecialchars($form_data['content']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="news_image_upload_file_input" class="form-label">Gambar Berita <?= ($action === 'add' && empty($form_data['news_image_path'])) ? '<span class="text-danger">*</span>' : '(Opsional jika mengganti)'; ?></label>
                    <input type="file" class="form-control" id="news_image_upload_file_input" name="news_image_upload_file" accept="image/jpeg,image/png,image/gif" <?= ($action === 'add' && empty($form_data['news_image_path'])) ? 'required' : ''; ?>>
                    
                    <?php if ($action === 'edit' && !empty($form_data['news_image_path'])): ?>
                        <input type="hidden" name="current_image_filename" value="<?= htmlspecialchars($form_data['news_image_path']); ?>">
                        <?php if (file_exists(NEWS_UPLOAD_DIR . $form_data['news_image_path'])): ?>
                        <p class="mt-2 mb-1">Gambar saat ini:</p>
                        <img src="<?= htmlspecialchars(NEWS_UPLOAD_DIR . $form_data['news_image_path']); ?>?t=<?= time(); ?>" alt="Gambar Saat Ini" class="img-thumbnail img-thumbnail-preview mb-2">
                        <div class="form-text">Unggah file baru untuk mengganti gambar ini.</div>
                        <?php else: ?>
                        <p class="mt-2 mb-1 text-warning">File gambar saat ini (<?= htmlspecialchars($form_data['news_image_path']); ?>) tidak ditemukan. Unggah gambar baru jika diperlukan.</p>
                        <?php endif; ?>
                    <?php elseif ($action === 'add'): ?>
                         <div class="form-text">Pilih file gambar (JPG, JPEG, PNG, GIF, maks 5MB). Wajib untuk berita baru.</div>
                    <?php endif; ?>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_published_form_input" name="is_published" value="1" <?= ($form_data['is_published'] == 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_published_form_input">Terbitkan Berita Ini</label>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Simpan Berita</button>
                <a href="admin_manage_news.php?action=list" class="btn btn-secondary"><i class="bi bi-x-circle me-2"></i>Batal</a>
            </form>
        <?php endif; // Akhir dari $action === 'add' || $action === 'edit' ?>
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