<?php
session_start();
include 'config.php'; // Pastikan koneksi database disertakan

// Proteksi jika belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: login_register.php");
    exit();
}

// Cek apakah user sudah pernah isi form (hanya 1 kali)
$user_id = (int)$_SESSION['user_id'];
$cek = mysqli_query($conn, "SELECT * FROM students WHERE user_id = $user_id");
if (mysqli_num_rows($cek) > 0) {
    header("Location: dashboard_register.php");
    exit();
}

// Simpan data dari form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['pendaftaran']['name'] = $_POST['name'];
    $_SESSION['pendaftaran']['gender'] = $_POST['gender'];
    $_SESSION['pendaftaran']['email'] = $_POST['email'];
    $_SESSION['pendaftaran']['birthplace'] = $_POST['birthplace'];
    $_SESSION['pendaftaran']['dob'] = $_POST['dob'];
    $_SESSION['pendaftaran']['religion_child'] = $_POST['religion_child'];
    $_SESSION['pendaftaran']['phone'] = $_POST['phone'];
    $_SESSION['pendaftaran']['address'] = $_POST['address'];
    $_SESSION['pendaftaran']['student_hobby'] = $_POST['student_hobby'];
    $_SESSION['pendaftaran']['goal'] = $_POST['goal'];
    $_SESSION['pendaftaran']['motivation'] = $_POST['motivation'];

    header("Location: step2.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Form Pendaftaran - Step 1</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <div class="col-md-8 mx-auto">
    <div class="card shadow rounded">
      <div class="card-header bg-primary text-white">
        <h4 class="mb-0">Biodata Calon Siswa</h4>
      </div>
      <div class="card-body">
        <form method="POST">
          <div class="mb-3">
            <label for="name" class="form-label">Nama Lengkap</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="gender" class="form-label">Jenis Kelamin</label>
            <select name="gender" class="form-control" required>
              <option value="">Pilih</option>
              <option value="Laki-laki">Laki-laki</option>
              <option value="Perempuan">Perempuan</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="birthplace" class="form-label">Tempat Lahir</label>
            <input type="text" name="birthplace" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="dob" class="form-label">Tanggal Lahir</label>
            <input type="date" name="dob" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="religion_child" class="form-label">Agama</label>
            <select name="religion_child" class="form-control" required>
              <option value="">Pilih</option>
              <option value="Islam">Islam</option>
              <option value="Kristen Protestan">Kristen Protestan</option>
              <option value="Kristen Katolik">Kristen Katolik</option>
              <option value="Hindu">Hindu</option>
              <option value="Buddha">Buddha</option>
              <option value="Konghucu">Konghucu</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="phone" class="form-label">Nomor Telepon</label>
            <input type="text" name="phone" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="address" class="form-label">Alamat Lengkap</label>
            <textarea name="address" class="form-control" rows="3" required></textarea>
          </div>
          <div class="mb-3">
            <label for="student_hobby" class="form-label">Hobi</label>
            <input type="text" name="student_hobby" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="goal" class="form-label">Cita-cita</label>
            <input type="text" name="goal" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="motivation" class="form-label">Motivasi Masuk Sekolah</label>
            <textarea name="motivation" class="form-control" rows="3" required></textarea>
          </div>

          <button type="submit" class="btn btn-success w-100">Lanjutkan</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
