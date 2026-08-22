<?php
session_start();

// ✅ Tambahan proteksi login & data
if (!isset($_SESSION['user_id']) || !isset($_SESSION['pendaftaran'])) {
    header("Location: step1.php");
    exit();
}

// Periksa apakah data pendaftaran dari semua step sudah ada di session
if (!isset($_SESSION['pendaftaran']) ||
    empty($_SESSION['pendaftaran']['name']) ||
    empty($_SESSION['pendaftaran']['birthplace']) ||
    empty($_SESSION['pendaftaran']['dob']) ||
    empty($_SESSION['pendaftaran']['phone']) ||
    empty($_SESSION['pendaftaran']['address']) ||
    empty($_SESSION['pendaftaran']['kk_file']) ||
    empty($_SESSION['pendaftaran']['akta_lahir']) ||
    empty($_SESSION['pendaftaran']['nilai_rapor']) ||
    // Jika data wali tidak ada, periksa data ayah dan ibu
    (!isset($_SESSION['pendaftaran']['guardian_name']) && (
        empty($_SESSION['pendaftaran']['father_name']) ||
        empty($_SESSION['pendaftaran']['mother_name']) ||
        empty($_SESSION['pendaftaran']['father_phone']) ||
        empty($_SESSION['pendaftaran']['mother_phone'])
        // Kita tidak perlu memeriksa ktp_father dan ktp_mother di sini karena sudah di-null-kan
    )) ||
    // Jika data wali ada, pastikan datanya tidak kosong
    (isset($_SESSION['pendaftaran']['guardian_name']) && (
        empty($_SESSION['pendaftaran']['guardian_name']) ||
        empty($_SESSION['pendaftaran']['guardian_phone']) ||
        empty($_SESSION['pendaftaran']['guardian_relation'])
    ))
) {
    // Jika ada data yang kosong, arahkan kembali ke step 1
    header("Location: step1.php?error=incomplete");
    exit;
}

$data = $_SESSION['pendaftaran'];

// Fungsi bantu untuk menampilkan data dengan aman
function showData($data, $key) {
    return isset($data[$key]) ? htmlspecialchars($data[$key]) : '-';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Konfirmasi Data Pendaftaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Konfirmasi Data Pendaftaran</h2>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Data Siswa</div>
        <div class="card-body">
            <p><strong>Nama:</strong> <?= showData($data, 'name') ?></p>
            <p><strong>Tempat Lahir:</strong> <?= showData($data, 'birthplace') ?></p>
            <p><strong>Tanggal Lahir:</strong> <?= showData($data, 'dob') ?></p>
            <p><strong>No. HP:</strong> <?= showData($data, 'phone') ?></p>
            <p><strong>Alamat:</strong> <?= showData($data, 'address') ?></p>
            <p><strong>Hobi:</strong> <?= showData($data, 'student_hobby') ?></p>
            <p><strong>Cita-cita:</strong> <?= showData($data, 'goal') ?></p>
            <p><strong>Motivasi:</strong> <?= showData($data, 'motivation') ?></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-success text-white">Data Orang Tua / Wali</div>
        <div class="card-body">
            <?php if (isset($data['father_name']) && isset($data['mother_name'])): ?>
                <p><strong>Nama Ayah:</strong> <?= showData($data, 'father_name') ?></p>
                <p><strong>Tempat Lahir Ayah:</strong> <?= showData($data, 'father_birthplace') ?></p>
                <p><strong>Tanggal Lahir Ayah:</strong> <?= showData($data, 'father_dob') ?></p>
                <p><strong>No. HP Ayah:</strong> <?= showData($data, 'father_phone') ?></p>
                <p><strong>Pekerjaan Ayah:</strong> <?= showData($data, 'father_job') ?></p>
                <p><strong>Email Ayah:</strong> <?= showData($data, 'father_email') ?></p>
                <p><strong>Pendapatan Ayah:</strong> <?= showData($data, 'father_income') ?></p>
                <p><strong>Nama Ibu:</strong> <?= showData($data, 'mother_name') ?></p>
                <p><strong>Tempat Lahir Ibu:</strong> <?= showData($data, 'mother_birthplace') ?></p>
                <p><strong>Tanggal Lahir Ibu:</strong> <?= showData($data, 'mother_dob') ?></p>
                <p><strong>No. HP Ibu:</strong> <?= showData($data, 'mother_phone') ?></p>
                <p><strong>Pekerjaan Ibu:</strong> <?= showData($data, 'mother_job') ?></p>
                <p><strong>Email Ibu:</strong> <?= showData($data, 'mother_email') ?></p>
                <p><strong>Pendapatan Ibu:</strong> <?= showData($data, 'mother_income') ?></p>
            <?php endif; ?>
            <?php if (isset($data['guardian_name'])): ?>
                <p><strong>Nama Wali:</strong> <?= showData($data, 'guardian_name') ?></p>
                <p><strong>Tempat Lahir Wali:</strong> <?= showData($data, 'guardian_birthplace') ?></p>
                <p><strong>Tanggal Lahir Wali:</strong> <?= showData($data, 'guardian_dob') ?></p>
                <p><strong>No. HP Wali:</strong> <?= showData($data, 'guardian_phone') ?></p>
                <p><strong>Hubungan dengan Siswa:</strong> <?= showData($data, 'guardian_relation') ?></p>
                <p><strong>Email Wali:</strong> <?= showData($data, 'guardian_email') ?></p>
                <p><strong>Pendapatan Wali:</strong> <?= showData($data, 'guardian_income') ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-info text-white">Dokumen Terunggah</div>
        <div class="card-body">
            <ul>
                <li>KK: <?= showData($data, 'kk_file') ?></li>
                <li>KTP Ayah: <?= showData($data, 'ktp_father') ?></li>
                <li>KTP Ibu: <?= showData($data, 'ktp_mother') ?></li>
                <?php if (isset($data['ktp_guardian'])): ?>
                    <li>KTP Wali: <?= showData($data, 'ktp_guardian') ?></li>
                    <li>KK Wali: <?= showData($data, 'kk_guardian') ?></li>
                <?php endif; ?>
                <li>Akta Lahir: <?= showData($data, 'akta_lahir') ?></li>
                <li>Nilai Rapor: <?= showData($data, 'nilai_rapor') ?></li>
            </ul>
        </div>
    </div>

    <form action="process.php" method="post">
        <button type="submit" class="btn btn-success">Kirim & Simpan</button>
        <a href="step3.php" class="btn btn-secondary">Kembali</a>
    </form>
</div>
</body>
</html>