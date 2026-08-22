<?php
// FILE: admin_change_password.php

// Mulai sesi
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah admin sudah login
if (!isset($_SESSION['admin'])) {
    // Jika belum login, hapus sesi dan arahkan kembali ke halaman login
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Sertakan file konfigurasi database
// Pastikan path 'config.php' sudah benar
if (file_exists('config.php')) {
    include_once 'config.php';
} else {
    // Tampilkan pesan error jika config.php tidak ditemukan
    error_log("File konfigurasi database (config.php) tidak ditemukan di admin_change_password.php");
    // Arahkan ke halaman admin dengan pesan error yang lebih ramah (atau tampilkan di sini)
    header("Location: index_admin.php?status=error&message=" . urlencode("Konfigurasi database tidak ditemukan."));
    exit;
}

// Pastikan koneksi database ada dan berhasil
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $error_message = (isset($conn) && $conn instanceof mysqli) ? $conn->connect_error : 'Koneksi database tidak terdefinisi atau bukan objek mysqli.';
    error_log("Koneksi database gagal di admin_change_password.php: " . $error_message);
     // Arahkan ke halaman admin dengan pesan error
    header("Location: index_admin.php?status=error&message=" . urlencode("Koneksi database gagal."));
    exit;
}

// Enable error reporting for debugging (HATI-HATI! Nonaktifkan di produksi)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = ''; // Variabel untuk pesan feedback (sukses/error)

// --- Handle POST Request (Saat Form Disubmit) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Lakukan validasi dasar
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = '<div class="alert alert-warning" role="alert">Semua field password harus diisi.</div>';
    } elseif ($new_password !== $confirm_password) {
        $message = '<div class="alert alert-warning" role="alert">Password baru dan konfirmasi password tidak cocok.</div>';
    }
    // Anda bisa tambahkan validasi kekuatan password baru di sini (min length, karakter khusus, dll)
    // elseif (strlen($new_password) < 8) { ... }

    else {
        // Validasi awal sukses, lanjutkan ke proses PHP sebenarnya (verifikasi password lama, generate OTP, simpan OTP, kirim email)
        // LOGIKA INI AKAN DIBUAT DI LANGKAH SELANJUTNYA
        $message = '<div class="alert alert-info" role="alert">Validasi form sukses. Logika verifikasi password lama dan OTP akan diproses di sini.</div>'; // Pesan placeholder

        // Tutup koneksi DB di sini jika tidak akan digunakan lagi dalam proses POST ini
        // mysqli_close($conn);
        // exit; // Mungkin perlu exit setelah proses POST selesai (redirect/pesan akhir)
    }
}

// Tutup koneksi database (jika request bukan POST atau jika POST sudah selesai)
if (isset($conn) && $conn instanceof mysqli && $_SERVER["REQUEST_METHOD"] != "POST") {
     // Jika koneksi masih terbuka dan ini request GET
     // Biarkan terbuka jika nanti di HTML ada pengambilan data DB lain, atau tutup
    // mysqli_close($conn);
}


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Admin - Ganti Password</title>

    <link href="public/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <link href="public/vendor/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="icon" type="image/jpg" href="public/asset/logo.jpg">


    <style>
        /* Tambahkan style kustom jika perlu, misal untuk form password toggle */
         .input-group-text {
             cursor: pointer;
         }
    </style>

</head>

<body id="page-top">

    <div id="wrapper">

        <?php // include 'admin_sidebar.php'; // Contoh ?>
        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">

                <?php // include 'admin_topbar.php'; // Contoh ?>
                <div class="container-fluid">

                    <h1 class="h3 mb-4 text-gray-800">Ganti Password Admin</h1>

                    <?php
                    // Tampilkan pesan feedback di sini
                    if (!empty($message)) {
                        echo $message;
                    }
                    ?>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Form Ganti Password</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="admin_change_password.php">

                                        <div class="form-group">
                                            <label for="current_password">Password Lama:</label>
                                             <div class="input-group">
                                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text" onclick="togglePassword('current_password', 'toggleCurrentPasswordIcon')">
                                                        <i class="fas fa-eye" id="toggleCurrentPasswordIcon"></i>
                                                    </span>
                                                </div>
                                             </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="new_password">Password Baru:</label>
                                             <div class="input-group">
                                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                                 <div class="input-group-append">
                                                    <span class="input-group-text" onclick="togglePassword('new_password', 'toggleNewPasswordIcon')">
                                                        <i class="fas fa-eye" id="toggleNewPasswordIcon"></i>
                                                    </span>
                                                </div>
                                             </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="confirm_password">Konfirmasi Password Baru:</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text" onclick="togglePassword('confirm_password', 'toggleConfirmPasswordIcon')">
                                                        <i class="fas fa-eye" id="toggleConfirmPasswordIcon"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Kirim OTP & Ganti Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
                </div>
            <?php // include 'admin_footer.php'; // Contoh ?>
            </div>
        </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <?php // include 'admin_logout_modal.php'; // Contoh ?>


    <script src="public/vendor/jquery/jquery.min.js"></script>
    <script src="public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script src="public/vendor/jquery-easing/jquery.easing.min.js"></script>

    <script src="public/vendor/js/sb-admin-2.min.js"></script>

    <script>
        // Fungsi toggle password yang diupdate agar bisa dipakai untuk multiple input
        // Pastikan Anda menggunakan ID ikon yang unik untuk setiap input
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>


</body>

</html>