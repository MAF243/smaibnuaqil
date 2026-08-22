<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['pendaftaran'])) {
    header("Location: step1.php");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Simpan data step 2 ke session
    $_SESSION['pendaftaran']['father_name'] = $_POST['father_name'];
    $_SESSION['pendaftaran']['father_phone'] = $_POST['father_phone'];
    $_SESSION['pendaftaran']['father_job'] = $_POST['father_job'];
    $_SESSION['pendaftaran']['father_dob'] = $_POST['father_dob'];
    $_SESSION['pendaftaran']['religion_father'] = $_POST['religion_father'];
    $_SESSION['pendaftaran']['father_birthplace'] = $_POST['father_birthplace'];
    $_SESSION['pendaftaran']['father_email'] = $_POST['father_email'];
    $_SESSION['pendaftaran']['father_income'] = $_POST['father_income'];

    $_SESSION['pendaftaran']['mother_name'] = $_POST['mother_name'];
    $_SESSION['pendaftaran']['mother_phone'] = $_POST['mother_phone'];
    $_SESSION['pendaftaran']['mother_job'] = $_POST['mother_job'];
    $_SESSION['pendaftaran']['mother_dob'] = $_POST['mother_dob'];
    $_SESSION['pendaftaran']['religion_mother'] = $_POST['religion_mother'];
    $_SESSION['pendaftaran']['mother_birthplace'] = $_POST['mother_birthplace'];
    $_SESSION['pendaftaran']['mother_email'] = $_POST['mother_email'];
    $_SESSION['pendaftaran']['mother_income'] = $_POST['mother_income'];

    // Upload KTP Ayah dan Ibu HANYA JIKA tidak menggunakan wali pengganti
    if (!isset($_POST['use_guardian']) || $_POST['use_guardian'] != 'on') {
      // Upload KTP Ayah
      $fileKtpAyah = uploadFile('ktp_father');
      if (!$fileKtpAyah) {
          echo "<script>alert('Upload KTP Ayah gagal! Pastikan format file benar (PDF/JPG/PNG)'); window.history.back();</script>";
          exit;
      }
      $_SESSION['pendaftaran']['ktp_father'] = $fileKtpAyah;

      // Upload KTP Ibu
      $fileKtpIbu = uploadFile('ktp_mother');
      if (!$fileKtpIbu) {
          echo "<script>alert('Upload KTP Ibu gagal! Pastikan format file benar (PDF/JPG/PNG)'); window.history.back();</script>";
          exit;
      }
      $_SESSION['pendaftaran']['ktp_mother'] = $fileKtpIbu;
  } else {
      // Jika menggunakan wali, pastikan field KTP ayah dan ibu di session di-unset atau diisi null
      $_SESSION['pendaftaran']['ktp_father'] = null;
      $_SESSION['pendaftaran']['ktp_mother'] = null;
  }

    // Cek jika wali pengganti dipilih
    if (isset($_POST['use_guardian']) && $_POST['use_guardian'] == 'on') {
        $_SESSION['pendaftaran']['guardian_name'] = $_POST['guardian_name'];
        $_SESSION['pendaftaran']['guardian_phone'] = $_POST['guardian_phone'];
        $_SESSION['pendaftaran']['guardian_relation'] = $_POST['guardian_relation'];
        $_SESSION['pendaftaran']['guardian_birthplace'] = $_POST['guardian_birthplace'];
        $_SESSION['pendaftaran']['guardian_dob'] = $_POST['guardian_dob'];
        $_SESSION['pendaftaran']['guardian_religion'] = $_POST['guardian_religion'];
        $_SESSION['pendaftaran']['guardian_email'] = $_POST['guardian_email'];
        $_SESSION['pendaftaran']['guardian_income'] = $_POST['guardian_income'];

        // Upload KTP dan KK Wali Pengganti
        if (isset($_FILES['ktp_guardian']) && isset($_FILES['kk_guardian'])) {
            $_SESSION['pendaftaran']['ktp_guardian'] = uploadFile('ktp_guardian');
            $_SESSION['pendaftaran']['kk_guardian'] = uploadFile('kk_guardian');
        }
    }

    header("Location: step3.php");
    exit;
}

// Fungsi untuk upload file
function uploadFile($fileInputName, $targetDir = "uploads/") {
    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== 0) {
        return false;
    }

    $filename = basename($_FILES[$fileInputName]['name']);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        return false;
    }

    $newName = uniqid() . "_" . $filename;
    $targetFile = $targetDir . $newName;

    if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetFile)) {
        return $newName;
    } else {
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Form Pendaftaran - Step 2</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
  <div class="col-md-8 mx-auto">
    <div class="card shadow rounded">
      <div class="card-header bg-primary text-white">
        <h4 class="mb-0">Biodata Orang Tua atau Wali Pengganti</h4>
      </div>
      <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
    <div id="father_info" class="parent-fields">
        <h5>Data Ayah</h5>
        <div class="mb-3">
            <label for="father_name" class="form-label">Nama Ayah</label>
            <input type="text" name="father_name" class="form-control parent-required" required>
        </div>
        <div class="mb-3">
            <label for="father_birthplace" class="form-label">Tempat Lahir Ayah</label>
            <input type="text" name="father_birthplace" class="form-control parent-required" required>
        </div>
        <div class="mb-3">
            <label for="father_dob" class="form-label">Tanggal Lahir Ayah</label>
            <input type="date" name="father_dob" class="form-control parent-required" required>
        </div>
        <div class="mb-3">
            <label for="father_religion" class="form-label">Agama</label>
            <select name="father_religion" class="form-control parent-required" required>
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
            <label for="father_phone" class="form-label">No. HP Ayah</label>
            <input type="text" name="father_phone" class="form-control parent-required" required>
        </div>
        <div class="mb-3">
            <label for="father_job" class="form-label">Pekerjaan Ayah</label>
            <input type="text" name="father_job" class="form-control parent-required" required>
        </div>
        <div class="mb-3">
            <label for="father_email" class="form-label">Email Ayah</label>
            <input type="email" name="father_email" class="form-control parent-required" required>
        </div>
        <div class="mb-3">
            <label for="father_income" class="form-label">Pendapatan Bulanan Ayah</label>
            <select name="father_income" class="form-control parent-required" required>
                <option value="">Pilih</option>
                <option value="<Rp.500.000">Kurang dari Rp.500.000</option>
                <option value="Rp.500.000 - Rp.1.500.000">Rp.500.000 - Rp.1.500.000</option>
                <option value="Rp.1.500.000 - Rp.3.000.000">Rp.1.500.000 - Rp.3.000.000</option>
                <option value="Rp.3.000.000 - Rp.5.000.000">3.000.000 - Rp.5.000.000</option>
                <option value=">Rp.5.000.000">Lebih dari Rp.5.000.000</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="ktp_father" class="form-label">Upload KTP Ayah (PDF/JPG/PNG)</label>
            <input type="file" name="ktp_father" class="form-control parent-required" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
    </div>

    <div id="mother_info" class="parent-fields">
        <h5>Data Ibu</h5>
        <div class="mb-3">
            <label for="mother_name" class="form-label">Nama Ibu</label>
            <input type="text" name="mother_name" class="form-control parent-required" required>
        </div>
        <div class="mb-3">
            <label for="mother_birthplace" class="form-label">Tempat Lahir Ibu</label>
            <input type="text" name="mother_birthplace" class="form-control parent-required" required>
        </div>
        <div class="mb-3">
            <label for="mother_dob" class="form-label">Tanggal Lahir Ibu</label>
            <input type="date" name="mother_dob" class="form-control parent-required" required>
        </div>
        <div class="mb-3">
            <label for="mother_religion" class="form-label">Agama</label>
            <select name="mother_religion" class="form-control parent-required" required>
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
            <label for="mother_phone" class="form-label">No. HP Ibu</label>
            <input type="text" name="mother_phone" class="form-control parent-required" required>
        </div>
        <div class="mb-3">
            <label for="mother_job" class="form-label">Pekerjaan Ibu</label>
            <input type="text" name="mother_job" class="form-control parent-required" required>
        </div>
        <div class="mb-3">
            <label for="mother_email" class="form-label">Email Ibu</label>
            <input type="email" name="mother_email" class="form-control parent-required" required>
        </div>
        <div class="mb-3">
            <label for="mother_income" class="form-label">Pendapatan Bulanan Ibu</label>
            <select name="mother_income" class="form-control parent-required" required>
                <option value="">Pilih</option>
                <option value="<Rp.500.000">Kurang dari Rp.500.000</option>
                <option value="Rp.500.000 - Rp.1.500.000">Rp.500.000 - Rp.1.500.000</option>
                <option value="Rp.1.500.000 - Rp.3.000.000">Rp.1.500.000 - Rp.3.000.000</option>
                <option value="Rp.3.000.000 - Rp.5.000.000">3.000.000 - Rp.5.000.000</option>
                <option value=">Rp.5.000.000">Lebih dari Rp.5.000.000</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="ktp_mother" class="form-label">Upload KTP Ibu (PDF/JPG/PNG)</label>
            <input type="file" name="ktp_mother" class="form-control parent-required" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
    </div>

          <!-- Wali Pengganti -->
          <h5>Data Wali Pengganti (Opsional)</h5>
<div class="mb-3 form-check">
  <input type="checkbox" name="use_guardian" class="form-check-input" id="use_guardian">
  <label class="form-check-label" for="use_guardian">Saya menggunakan wali pengganti</label>
</div>

<div id="guardian_fields" style="display:none;">
  <div class="mb-3">
    <label for="guardian_name" class="form-label">Nama Wali Pengganti</label>
    <input type="text" name="guardian_name" id="guardian_name" class="form-control">
  </div>
  <div class="mb-3">
    <label for="guardian_birthplace" class="form-label">Tempat Lahir Wali</label>
    <input type="text" name="guardian_birthplace" id="guardian_birthplace" class="form-control">
  </div>
  <div class="mb-3">
    <label for="guardian_dob" class="form-label">Tanggal Lahir Wali</label>
    <input type="date" name="guardian_dob" id="guardian_dob" class="form-control">
  </div>
  <div class="mb-3">
    <label for="guardian_religion" class="form-label">Agama</label>
    <select name="guardian_religion" class="form-control">
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
    <label for="guardian_phone" class="form-label">No. HP Wali Pengganti</label>
    <input type="text" name="guardian_phone" id="guardian_phone" class="form-control">
  </div>
  <div class="mb-3">
    <label for="guardian_email" class="form-label">Email Wali</label>
    <input type="email" name="guardian_email" id="guardian_email" class="form-control">
  </div>
  <div class="mb-3">
    <label for="guardian_income" class="form-label">Pendapatan Bulanan Wali</label>
    <select name="guardian_income" class="form-control">
      <option value="">Pilih</option>
      <option value="<Rp.500.000">Kurang dari Rp.500.000</option>
      <option value="Rp.500.000 - Rp.1.500.000">Rp.500.000 - Rp.1.500.000</option>
      <option value="Rp.1.500.000 - Rp.3.000.000">Rp.1.500.000 - Rp.3.000.000</option>
      <option value="Rp.3.000.000 - Rp.5.000.000">Rp.3.000.000 - Rp.5.000.000</option>
      <option value=">Rp.5.000.000">Lebih dari Rp.5.000.000</option>
    </select>
  </div>
  <div class="mb-3">
    <label for="guardian_relation" class="form-label">Hubungan dengan Siswa</label>
    <input type="text" name="guardian_relation" id="guardian_relation" class="form-control">
  </div>
  <div class="mb-3">
    <label for="ktp_guardian" class="form-label">Upload KTP Wali Pengganti (PDF/JPG/PNG)</label>
    <input type="file" name="ktp_guardian" id="ktp_guardian" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
  </div>
  <div class="mb-3">
    <label for="kk_guardian" class="form-label">Upload KK Wali Pengganti (PDF/JPG/PNG)</label>
    <input type="file" name="kk_guardian" id="kk_guardian" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
  </div>
</div>
<button type="submit" class="btn btn-success w-100">Lanjutkan</button>
</form>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('use_guardian').addEventListener('change', function () {
  const fields = document.getElementById('guardian_fields');
  fields.style.display = this.checked ? 'block' : 'none';
});
document.addEventListener("DOMContentLoaded", function () {
    const guardianCheckbox = document.getElementById("use_guardian");
    const guardianFields = document.getElementById("guardian_fields");
    const parentFields = document.querySelectorAll(".parent-fields");
    const parentRequired = document.querySelectorAll(".parent-required");

    function toggleFields() {
        if (guardianCheckbox.checked) {
            // Sembunyikan data ayah dan ibu
            parentFields.forEach(field => field.style.display = "none");
            // Nonaktifkan required pada field orang tua
            parentRequired.forEach(field => field.removeAttribute("required"));
            // Tampilkan form wali
            guardianFields.style.display = "block";
        } else {
            // Tampilkan kembali data orang tua
            parentFields.forEach(field => field.style.display = "block");
            // Aktifkan kembali required
            parentRequired.forEach(field => field.setAttribute("required", "required"));
            // Sembunyikan form wali
            guardianFields.style.display = "none";
        }
    }

    // Jalankan saat checkbox diklik
    guardianCheckbox.addEventListener("change", toggleFields);

    // Jalankan sekali saat halaman dimuat
    toggleFields();
});
</script>
</script>
</body>
</html>
