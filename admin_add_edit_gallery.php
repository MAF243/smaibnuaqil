<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

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
    error_log("Koneksi database gagal di admin_add_edit_gallery.php: " . ($conn->connect_error ?? 'Unknown error'));
    exit('Koneksi database gagal. Harap hubungi administrator.');
}

$gallery_id = $_GET['id'] ?? null;
$page_title = "Tambah Gambar Galeri Baru";
$gallery_data = [];
$error_messages = [];
$success_message = "";

// Jika ada ID galeri di URL, berarti kita akan mengedit gambar galeri
if ($gallery_id) {
    $page_title = "Edit Gambar Galeri";
    $query = "SELECT * FROM gallery WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $gallery_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && mysqli_num_rows($result) > 0) {
            $gallery_data = mysqli_fetch_assoc($result);
        } else {
            $error_messages[] = "Gambar galeri dengan ID tersebut tidak ditemukan.";
            $gallery_id = null; // Set ID ke null agar dianggap menambah baru jika tidak ditemukan
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_messages[] = "Gagal menyiapkan query untuk mengambil gambar galeri: " . mysqli_error($conn);
    }
}

// --- HANDLE FORM SUBMISSION (Ketika tombol "Simpan" diklik) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $current_image_path = $_POST['current_image_path'] ?? null; // Untuk menyimpan path gambar yang sudah ada
    $new_image_path = $current_image_path; // Defaultnya adalah path gambar yang sudah ada

    // Validasi input
    if (empty($title)) {
        $error_messages[] = "Judul gambar tidak boleh kosong.";
    }
    // Deskripsi bisa kosong, jadi tidak perlu validasi empty

    // Penanganan Upload Gambar
    if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/gallery_images/"; // Direktori untuk menyimpan gambar galeri
        // Pastikan direktori ada, jika tidak, buat
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true); // Buat direktori secara rekursif dengan permission 0755
        }

        $image_name = basename($_FILES['gallery_image']['name']);
        $image_type = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        $target_file = $target_dir . uniqid('galeri_') . '.' . $image_type; // Nama file unik

        // Validasi tipe file
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($image_type, $allowed_types)) {
            $error_messages[] = "Hanya file JPG, JPEG, PNG, & GIF yang diperbolehkan.";
        }

        // Validasi ukuran file (maksimal 5MB, bisa disesuaikan)
        if ($_FILES['gallery_image']['size'] > 5 * 1024 * 1024) { // 5MB
            $error_messages[] = "Ukuran file gambar maksimal 5MB.";
        }

        // Jika tidak ada error validasi, coba upload
        if (empty($error_messages)) {
            if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $target_file)) {
                $new_image_path = $target_file; // Update path gambar baru
                // Hapus gambar lama jika ada dan ini adalah proses edit
                if ($gallery_id && $current_image_path && file_exists($current_image_path) && $current_image_path !== $new_image_path) {
                    unlink($current_image_path);
                }
            } else {
                $error_messages[] = "Gagal mengupload gambar. Error: " . $_FILES['gallery_image']['error'];
            }
        }
    } elseif (!$gallery_id && empty($current_image_path)) {
        // Jika ini mode tambah baru DAN tidak ada gambar diupload
        $error_messages[] = "Anda harus mengunggah gambar baru untuk item galeri.";
    }

    // Jika tidak ada error validasi, lakukan INSERT atau UPDATE
    if (empty($error_messages)) {
        if ($gallery_id) { // Mode EDIT
            $query = "UPDATE gallery SET title = ?, description = ?, image_path = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sssi', $title, $description, $new_image_path, $gallery_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Gambar galeri berhasil diperbarui.";
                    // Setelah update, perbarui data untuk tampilan formulir
                    $gallery_data['title'] = $title;
                    $gallery_data['description'] = $description;
                    $gallery_data['image_path'] = $new_image_path;
                } else {
                    $error_messages[] = "Gagal memperbarui gambar galeri: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            } else {
                $error_messages[] = "Gagal menyiapkan query UPDATE: " . mysqli_error($conn);
            }
        } else { // Mode TAMBAH BARU
            $query = "INSERT INTO gallery (title, description, image_path) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sss', $title, $description, $new_image_path);
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Gambar galeri baru berhasil ditambahkan.";
                    // Setelah insert, kosongkan form
                    $title = ''; $description = ''; $new_image_path = null;
                    // Opsional: Redirect ke halaman daftar galeri setelah menambah
                    header("Location: admin_manage_gallery.php?status=added&message=" . urlencode("Gambar galeri berhasil ditambahkan."));
                    exit;
                } else {
                    $error_messages[] = "Gagal menambahkan gambar galeri: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            } else {
                $error_messages[] = "Gagal menyiapkan query INSERT: " . mysqli_error($conn);
            }
        }
    }
    // Jika ada error validasi, pastikan form retains submitted values
    if (!empty($error_messages)) {
        $gallery_data['title'] = $title;
        $gallery_data['description'] = $description;
        // new_image_path will already be set if an upload attempt was made
    }
}

// Tutup koneksi database setelah semua operasi selesai
if (isset($conn)) {
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title; ?> - Admin SMA Ibnu Aqil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/jpg" href="public/asset/logo.jpg">
    <style>
        body { background-color: #f8f9fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background-color: #007bff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); min-height: 56px; }
        .container-form { margin: 30px auto; max-width: 800px; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.07); }
        .form-title { margin-bottom: 25px; text-align: center; color: #007bff; font-weight: 600; font-size: 2rem; }
        .footer { text-align: center; padding: 20px; background-color: #343a40; color: white; margin-top: 40px; }
        .logout-top-right { position: fixed; top: 12px; right: 20px; z-index: 1050; }
        .back-to-manage-btn { position: fixed; top: 12px; right: 100px; z-index: 1050; }
        .img-thumbnail-preview { max-width: 250px; height: auto; margin-top: 10px; border: 1px solid #ddd; padding: 5px; border-radius: 4px; }
    </style>
</head>
<body class="admin-authenticated">
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid">
            <span class="navbar-text text-white fw-bold mx-auto"> Admin - <?= $page_title; ?>
            </span>
        </div>
    </nav>
    <a href="admin_manage_gallery.php" class="btn btn-secondary btn-sm back-to-manage-btn shadow-sm">
        <i class="bi bi-arrow-left-circle"></i> Kembali
    </a>
    <a href="logout.php" class="btn btn-danger btn-sm logout-top-right shadow-sm" onclick="return confirm('Yakin ingin logout?')">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>

    <div class="container container-form mt-4">
        <h2 class="form-title"><?= $page_title; ?></h2>

        <?php if (!empty($error_messages)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
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

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="title" class="form-label">Judul Gambar</label>
                <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($gallery_data['title'] ?? ($title ?? '')); ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi (Opsional)</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($gallery_data['description'] ?? ($description ?? '')); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="gallery_image" class="form-label">File Gambar</label>
                <input type="file" class="form-control" id="gallery_image" name="gallery_image" accept="image/*" <?= empty($gallery_data['image_path']) ? 'required' : ''; ?>>
                <?php if (!empty($gallery_data['image_path'])): ?>
                    <p class="mt-2">Gambar saat ini:</p>
                    <img src="<?= htmlspecialchars($gallery_data['image_path']); ?>" alt="Gambar Galeri" class="img-thumbnail img-thumbnail-preview">
                    <input type="hidden" name="current_image_path" value="<?= htmlspecialchars($gallery_data['image_path']); ?>">
                    <div class="form-text">Unggah file baru untuk mengganti gambar ini.</div>
                <?php else: ?>
                    <div class="form-text">Pilih file gambar (JPG, JPEG, PNG, GIF, maks 5MB).</div>
                <?php endif; ?>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
            <a href="admin_manage_gallery.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Batal</a>
        </form>
    </div>

    <footer class="footer">
        <div class="container"> <p>&copy; <?= date("Y"); ?> SMA Ibnu Aqil. Hak Cipta Dilindungi.</p> </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Pencegahan akses halaman admin dari cache (BACK button)
        if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_BACK_FORWARD) {
            window.location.href = 'logout.php';
        }

        // Tambahan perlindungan jika class "admin-authenticated" tidak ada
        window.onload = function () {
            if (!document.body.classList.contains('admin-authenticated')) {
                window.location.href = 'logout.php';
            }
        };
    </script>
</body>
</html>