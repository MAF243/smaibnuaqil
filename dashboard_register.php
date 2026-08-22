<?php
// Baris-baris ini SANGAT PENTING untuk mencoba menampilkan error di browser.
// Jika masih tidak muncul pesan error PHP detail di browser, error pasti ada di log server.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Pastikan config.php di-require dan $pdo terdefinisi dari sana
// Pastikan path ke config.php benar jika tidak satu folder
require 'config.php'; 

// Proteksi halaman: jika user belum login, redirect ke halaman login
// GANTI 'login.php' dengan nama file halaman login Anda yang sebenarnya jika berbeda
if (!isset($_SESSION['user_id'])) {
    // Anda mungkin ingin menggunakan nama file login yang Anda sebutkan sebelumnya: 'login_register.php'
    header("Location: login.php"); // <-- GANTI DENGAN NAMA FILE LOGIN ANDA
    exit();
}

$user_id = $_SESSION['user_id'];
$data_pendaftaran = null; // Inisialisasi variabel untuk data pendaftaran
$error_message_display = null; // Inisialisasi variabel untuk pesan error yang akan ditampilkan ke user

try {
    // Asumsi tabel Anda bernama 'students' dan memiliki kolom 'user_id'
    $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $data_pendaftaran = $stmt->fetch(); // Hasilnya bisa false jika tidak ada data
} catch (PDOException $e) {
    // Catat error detail ke log server (lebih baik daripada echo langsung di produksi)
    error_log("Kesalahan Database di Dashboard (user_id: $user_id): " . $e->getMessage());
    $error_message_display = "Terjadi kesalahan saat mencoba mengambil data pendaftaran Anda. Silakan coba lagi nanti.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Pendaftaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Dashboard Pendaftaran</h3>
        <a href="logout.php" class="btn btn-danger">Logout</a> </div>

    <?php if ($error_message_display): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error_message_display) ?>
        </div>
    <?php endif; ?>

    <?php if ($data_pendaftaran): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                Data Pendaftaran Anda
            </div>
            <div class="card-body">
                <p><strong>Nama Lengkap:</strong> <?= htmlspecialchars($data_pendaftaran['name'] ?? 'Data tidak tersedia') ?></p>
                <p><strong>Tempat Lahir:</strong> <?= htmlspecialchars($data_pendaftaran['birthplace'] ?? 'Data tidak tersedia') ?></p>
                <p><strong>Tanggal Lahir:</strong> <?= htmlspecialchars($data_pendaftaran['dob'] ?? 'Data tidak tersedia') ?></p>
                <p><strong>Alamat:</strong> <?= nl2br(htmlspecialchars($data_pendaftaran['address'] ?? 'Data tidak tersedia')) ?></p>
                <p><strong>Jenis Kelamin:</strong> <?= htmlspecialchars($data_pendaftaran['gender'] ?? 'Data tidak tersedia') ?></p>
                <p><strong>Agama:</strong> <?= htmlspecialchars($data_pendaftaran['religion_child'] ?? 'Data tidak tersedia') ?></p>
                <p><strong>Nomor Telepon:</strong> <?= htmlspecialchars($data_pendaftaran['phone'] ?? 'Data tidak tersedia') ?></p>
                <p><strong>Hobi:</strong> <?= htmlspecialchars($data_pendaftaran['student_hobby'] ?? 'Data tidak tersedia') ?></p>
                <p><strong>Cita-cita:</strong> <?= htmlspecialchars($data_pendaftaran['goal'] ?? 'Data tidak tersedia') ?></p>
                <p><strong>Motivasi Masuk Sekolah:</strong> <?= nl2br(htmlspecialchars($data_pendaftaran['motivation'] ?? 'Data tidak tersedia')) ?></p>
                <hr>
                <p><strong>Status Pendaftaran:</strong>
                    <?php
                    // Menggunakan strtolower() dan null coalescing operator untuk keamanan
                    $status_pendaftaran = strtolower($data_pendaftaran['status'] ?? 'pending'); // Default ke 'pending' jika null
                    $badge_class = 'secondary'; // Default badge class

                    // Menggunakan 'switch' untuk kompatibilitas PHP yang lebih luas (PHP 5.6+)
                    switch ($status_pendaftaran) {
                        case 'diterima':
                            $badge_class = 'success';
                            break;
                        case 'ditolak':
                            $badge_class = 'danger';
                            break;
                        case 'berkas tidak lengkap':
                            $badge_class = 'warning';
                            break;
                        // 'pending' atau status lain akan menggunakan 'secondary' (default)
                    }
                    ?>
                    <span class="badge bg-<?= $badge_class ?>"><?= ucfirst(htmlspecialchars($status_pendaftaran)) ?></span>
                </p>

                <?php if (($status_pendaftaran === 'ditolak' || $status_pendaftaran === 'berkas tidak lengkap') && !empty($data_pendaftaran['alasan_penolakan'])): ?>
                    <div class="alert alert-<?= ($status_pendaftaran === 'ditolak' ? 'danger' : 'warning') ?> mt-3">
                        <strong>Alasan:</strong> <?= nl2br(htmlspecialchars($data_pendaftaran['alasan_penolakan'])) ?>
                    </div>
                    <?php if ($status_pendaftaran === 'berkas tidak lengkap'): ?>
                        <a href="step1.php" class="btn btn-warning mt-2">Lengkapi Data</a> <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif (!$error_message_display): // Hanya tampilkan jika tidak ada error database dan data memang tidak ada ?>
        <div class="alert alert-info">
            Anda belum mengisi formulir pendaftaran, atau data pendaftaran Anda tidak ditemukan.
        </div>
        <a href="step1.php" class="btn btn-primary">Isi Formulir Pendaftaran</a> <?php endif; ?>

</div>
</body>
</html>