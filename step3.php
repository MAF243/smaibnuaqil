<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['pendaftaran'])) {
    header("Location: step1.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengunggah file Kartu Keluarga, Akta Kelahiran, dan Nilai Report
    $upload_dir = "uploads/";

    // Upload Kartu Keluarga
    if (isset($_FILES['kk_file'])) {
        $file_kk_name = $_FILES['kk_file']['name'];
        $file_kk_tmp = $_FILES['kk_file']['tmp_name'];
        $file_kk_path = $upload_dir . basename($file_kk_name);

        if (move_uploaded_file($file_kk_tmp, $file_kk_path)) {
            $_SESSION['pendaftaran']['kk_file'] = $file_kk_path;
        } else {
            $error_message = "Gagal mengunggah file Kartu Keluarga.";
        }
    }

    // Upload Akta Kelahiran
    if (isset($_FILES['akta_lahir'])) {
        $file_akta_name = $_FILES['akta_lahir']['name'];
        $file_akta_tmp = $_FILES['akta_lahir']['tmp_name'];
        $file_akta_path = $upload_dir . basename($file_akta_name);

        if (move_uploaded_file($file_akta_tmp, $file_akta_path)) {
            $_SESSION['pendaftaran']['akta_lahir'] = $file_akta_path;
        } else {
            $error_message = "Gagal mengunggah file Akta Kelahiran.";
        }
    }

    // Upload Nilai Report
    if (isset($_FILES['nilai_rapor'])) {
        $file_nilai_name = $_FILES['nilai_rapor']['name'];
        $file_nilai_tmp = $_FILES['nilai_rapor']['tmp_name'];
        $file_nilai_path = $upload_dir . basename($file_nilai_name);

        if (move_uploaded_file($file_nilai_tmp, $file_nilai_path)) {
            $_SESSION['pendaftaran']['nilai_rapor'] = $file_nilai_path;
        } else {
            $error_message = "Gagal mengunggah file Nilai Rapor.";
        }
    }

    // Jika semua file diupload dengan sukses, redirect ke halaman konfirmasi
    if (!isset($error_message)) {
        header("Location: confirm.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Form Pendaftaran - Step 3</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <div class="col-md-8 mx-auto">
    <div class="card shadow rounded">
      <div class="card-header bg-primary text-white">
        <h4 class="mb-0">Step 3: Unggah Dokumen</h4>
      </div>
      <div class="card-body">
        <?php if (isset($error_message)): ?>
          <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
          <h5>Unggah File</h5>

          <!-- Kartu Keluarga -->
          <div class="mb-3">
            <label for="kk_file" class="form-label">Upload Kartu Keluarga (PDF/JPG/PNG)</label>
            <input type="file" name="kk_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
          </div>

          <!-- Akta Kelahiran -->
          <div class="mb-3">
            <label for="akta_lahir" class="form-label">Upload Akta Kelahiran Calon Siswa (PDF/JPG/PNG)</label>
            <input type="file" name="akta_lahir" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
          </div>

          <!-- Nilai Report -->
          <div class="mb-3">
            <label for="nilai_rapor" class="form-label">Upload Nilai Report Kelas Terakhir (PDF/JPG/PNG)</label>
            <input type="file" name="nilai_rapor" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
          </div>

          <button type="submit" class="btn btn-success w-100">Selesaikan Pendaftaran</button>
        </form>
        </ul>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
