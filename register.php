<?php
// Baris untuk menampilkan error (penting untuk debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// Menggunakan __DIR__ untuk path yang lebih robust. Pastikan config.php satu folder.
include __DIR__ . '/config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pastikan $conn ada dan merupakan objek mysqli yang valid
    if (!isset($conn) || !($conn instanceof mysqli)) {
        $error_detail = isset($conn) ? "Variabel \$conn ditemukan tapi bukan objek mysqli." : "Variabel \$conn tidak terdefinisi dari config.php.";
        error_log("Registrasi Gagal - \$conn tidak valid di awal POST: " . $error_detail);
        echo "<script>alert('Registrasi Gagal: Kesalahan konfigurasi koneksi database (mysqli).');</script>";
    } elseif (mysqli_connect_errno()) { // Cek jika ada error koneksi pada $conn
         error_log("Registrasi Gagal - mysqli_connect_errno: " . mysqli_connect_error());
        echo "<script>alert('Registrasi Gagal: Koneksi database error. Detail: ".addslashes(mysqli_connect_error())."');</script>";
    }else {
        $username = trim($_POST['username']);
        $password_plain = $_POST['password'] ?? '';
        $confirm_password_plain = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($password_plain) || empty($confirm_password_plain)) {
            echo "<script>alert('Semua field (Username, Password, Konfirmasi Password) tidak boleh kosong!');</script>";
        } else if (strlen($password_plain) < 6) {
            echo "<script>alert('Password minimal harus 6 karakter!');</script>";
        } else if ($password_plain !== $confirm_password_plain) {
            echo "<script>alert('Password dan Konfirmasi Password tidak cocok!');</script>";
        } else {
            $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

            $stmt_check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ?");
            if ($stmt_check) {
                mysqli_stmt_bind_param($stmt_check, "s", $username);
                mysqli_stmt_execute($stmt_check);
                mysqli_stmt_store_result($stmt_check);

                if (mysqli_stmt_num_rows($stmt_check) > 0) {
                    echo "<script>alert('Username sudah digunakan! Silakan pilih username lain.');</script>";
                    mysqli_stmt_close($stmt_check);
                } else {
                    mysqli_stmt_close($stmt_check);

                    $stmt_insert = mysqli_prepare($conn, "INSERT INTO users (username, password) VALUES (?, ?)");
                    if ($stmt_insert) {
                        mysqli_stmt_bind_param($stmt_insert, "ss", $username, $password_hashed);
                        if (mysqli_stmt_execute($stmt_insert)) {
                            echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location='login_register.php';</script>";
                            exit(); 
                        } else {
                            $insert_error_detail = mysqli_stmt_error($stmt_insert);
                            error_log("Registrasi Gagal (MySQLi Execute Insert Error): " . $insert_error_detail); 
                            echo "<script>alert('Registrasi gagal saat menyimpan data. DB SAYS: " . addslashes($insert_error_detail) . "');</script>";
                        }
                        mysqli_stmt_close($stmt_insert);
                    } else {
                        $prepare_insert_error_detail = mysqli_error($conn);
                        error_log("Registrasi Gagal (MySQLi Prepare Insert Error): " . $prepare_insert_error_detail);
                        echo "<script>alert('Registrasi gagal (persiapan simpan). DB SAYS: " . addslashes($prepare_insert_error_detail) . "');</script>";
                    }
                }
            } else { 
                $prepare_check_error_detail = mysqli_error($conn); 
                error_log("Registrasi Gagal (MySQLi Prepare Check Error): " . $prepare_check_error_detail);
                echo "<script>alert('Registrasi gagal (pengecekan sistem). DB SAYS: " . addslashes($prepare_check_error_detail) . ".');</script>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi Pendaftar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        #togglePassword, #toggleConfirmPassword { cursor: pointer; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h3 class="card-title text-center mb-4">Registrasi Akun</h3>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="usernameField" class="form-label">Username/Email</label>
                            <input type="text" name="username" id="usernameField" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="passwordField" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" name="password" class="form-control" id="passwordField" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye-slash" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPasswordField" class="form-label">Konfirmasi Password</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" class="form-control" id="confirmPasswordField" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="bi bi-eye-slash" id="toggleConfirmPasswordIcon"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Daftar</button>
                    </form>
                    <p class="text-center mt-3">Sudah punya akun? <a href="login_register.php">Login di sini</a></p> 
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function toggleVisibility(field, iconElement) { /* ... (fungsi sama seperti sebelumnya) ... */ 
        if (field && iconElement) {
            const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
            field.setAttribute('type', type);
            if (type === 'password') {
                iconElement.classList.remove('bi-eye');
                iconElement.classList.add('bi-eye-slash');
            } else {
                iconElement.classList.remove('bi-eye-slash');
                iconElement.classList.add('bi-eye');
            }
        }
    }
    const pF = document.getElementById('passwordField'), tPB = document.getElementById('togglePassword'), tPI = document.getElementById('togglePasswordIcon');
    if(tPB){ tPB.addEventListener('click', function(){ toggleVisibility(pF, tPI); }); }
    const cPF = document.getElementById('confirmPasswordField'), tCPB = document.getElementById('toggleConfirmPassword'), tCPI = document.getElementById('toggleConfirmPasswordIcon');
    if(tCPB){ tCPB.addEventListener('click', function(){ toggleVisibility(cPF, tCPI); }); }
});
</script>
</body>
</html>