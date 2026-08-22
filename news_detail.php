<?php
// FILE: news_detail.php

// PENTING: Aktifkan pelaporan error untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sertakan file konfigurasi database
if (file_exists('config.php')) {
    include_once 'config.php';
} else {
    error_log("File konfigurasi database (config.php) tidak ditemukan di news_detail.php");
    exit('Kesalahan sistem: File konfigurasi database tidak ditemukan.');
}

// Pastikan koneksi database tersedia
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $error_message = (isset($conn) && $conn instanceof mysqli) ? $conn->connect_error : 'Koneksi tidak terdefinisi atau bukan objek mysqli.';
    error_log("Koneksi database gagal di news_detail.php: " . $error_message);
    exit('Mohon maaf, tidak dapat memuat detail berita saat ini karena masalah koneksi database.');
}

// Ambil ID berita dari parameter URL
$news_id = $_GET['id'] ?? null;

// Validasi ID berita
if (empty($news_id) || !is_numeric($news_id)) {
    // Jika ID tidak valid, arahkan kembali ke halaman utama atau arsip berita
    header("Location: index.php"); 
    exit;
}

$news_item = null; // Variabel untuk menyimpan data berita

// Ambil data berita lengkap dari database berdasarkan ID
$query = "SELECT id, title, content, image_path, created_at FROM news WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $news_id); // 'i' untuk integer
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $news_item = mysqli_fetch_assoc($result);
    } else {
        // Berita tidak ditemukan
        $error_message_display = "Berita tidak ditemukan.";
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Gagal menyiapkan query detail berita: " . mysqli_error($conn));
    $error_message_display = "Terjadi kesalahan saat mengambil detail berita.";
}

// Tutup koneksi database
if (isset($conn)) {
    mysqli_close($conn);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $news_item['title'] ?? 'Detail Berita'; ?> - SMA Ibnu Aqil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/jpg" href="public/asset/logo.jpg">
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f8f9fa; }
        .navbar { background-color: #156c26; } /* Warna hijau tua */
        .navbar-brand strong { color: white; }
        .container-news { margin-top: 30px; margin-bottom: 30px; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.07); }
        .news-title { color: #007bff; margin-bottom: 15px; }
        .news-meta { font-size: 0.9rem; color: #6c757d; margin-bottom: 20px; }
        .news-image { max-width: 100%; height: auto; margin-bottom: 25px; border-radius: 8px; }
        .news-content { line-height: 1.6; color: #333; }
        .footer { text-align: center; padding: 20px; background-color: #343a40; color: white; margin-top: 40px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
             <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="public/asset/logo.jpg" alt="Logo SMA Ibnu Aqil" width="40" height="40" class="me-2 rounded-circle">
                <strong>SMA IBNU'AQIL</strong>
             </a>
             <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                 <span class="navbar-toggler-icon"></span>
             </button>
             <div class="collapse navbar-collapse" id="navbarNav">
                 <ul class="navbar-nav ms-auto">
                     <li class="nav-item"><a class="nav-link" href="index.php">Beranda</a></li>
                     <li class="nav-item"><a class="nav-link" href="register.php">Pendaftaran</a></li>
                     </ul>
             </div>
        </div>
    </nav>

    <div class="container container-news">
        <?php if (isset($error_message_display)): ?>
            <div class="alert alert-warning text-center" role="alert">
                <?= htmlspecialchars($error_message_display); ?>
            </div>
        <?php elseif ($news_item): ?>
            <h1 class="news-title"><?= htmlspecialchars($news_item['title']); ?></h1>
            <p class="news-meta">
                <i class="bi bi-calendar-event"></i> <?= htmlspecialchars(date('d M Y H:i', strtotime($news_item['created_at']))); ?>
            </p>
            <?php if (!empty($news_item['image_path']) && file_exists($news_item['image_path'])): ?>
                <img src="<?= htmlspecialchars($news_item['image_path']); ?>" class="news-image" alt="<?= htmlspecialchars($news_item['title']); ?>">
            <?php endif; ?>
            <div class="news-content">
                <?= nl2br(htmlspecialchars($news_item['content'])); ?> </div>
            <div class="mt-4">
                 <a href="index.php#berita" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Kembali ke Berita Terbaru</a>
                 </div>
        <?php else: ?>
             <div class="alert alert-info text-center" role="alert">
                Berita tidak ditemukan.
            </div>
            <div class="mt-4 text-center">
                 <a href="index.php#berita" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Kembali ke Berita Terbaru</a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="container"> <p>&copy; <?= date("Y"); ?> SMA Ibnu Aqil. Hak Cipta Dilindungi.</p> </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>