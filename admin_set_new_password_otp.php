<?php
// FILE: admin_set_new_password_otp.php

// Mulai sesi
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user berhak mengatur password baru (datang dari verifikasi OTP yang sukses)
// Kita cek session yang diset di admin_verify_otp.php
if (!isset($_SESSION['can_set_password']) || $_SESSION['can_set_password'] !== true || !isset($_SESSION['set_password_user_id'])) {
    // Jika tidak berhak, arahkan kembali ke awal alur lupa password
    // Hapus session yang mungkin tersisa
    unset($_SESSION['can_set_password']);
    unset($_SESSION['set_password_user_id']);
    header("Location: forgot_password_wa.php"); // Atau login.php
    exit;
}

// Ambil ID user dari session
$user_id_to_update = $_SESSION['set_password_user_id'];

// Sertakan file konfigurasi database
if (file_exists('config.php')) {
    include_once 'config.php';
} else {
    error_log("File konfigurasi database (config.php) tidak ditemukan di admin_set_new_password_otp.php");
     $message = '<div class="alert alert-danger" role="alert">Kesalahan sistem: Konfigurasi database tidak ditemukan.</div>';
     // Hentikan proses karena tidak bisa update password
     unset($_SESSION['can_set_password']);
     unset($_SESSION['set_password_user_id']);
     // Jangan die() di sini, biarkan HTML ditampilkan dengan pesan error
}

// Pastikan koneksi database ada dan berhasil (hanya jika config.php ditemukan)
if (isset($conn) && (!($conn instanceof mysqli) || $conn->connect_error)) {
    $error_message = (isset($conn) && $conn instanceof mysqli) ? $conn->connect_error : 'Koneksi database tidak terdefinisi atau bukan objek mysqli.';
    error_log("Koneksi database gagal di admin_set_new_password_otp.php: " . $error_message);
     $message = '<div class="alert alert-danger" role="alert">Kesalahan sistem: Koneksi database gagal.</div>';
     // Hentikan proses karena tidak bisa update password
     unset($_SESSION['can_set_password']);
     unset($_SESSION['set_password_user_id']);
}


// Enable error reporting for debugging (HATI-HATI! Nonaktifkan di produksi)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Variabel untuk pesan feedback ke user (jika belum diisi dari error config/koneksi)
if (!isset($message)) {
    $message = '';
}

// Flag untuk menampilkan form (default true jika berhak)
// Asumsi user berhak jika sesi set password aktif DAN tidak ada error config/koneksi
$show_form = isset($_SESSION['can_set_password']) && !isset($conn) || (isset($conn) && !$conn->connect_error);


// --- Handle POST Request (Saat Form Atur Password Baru Disubmit) ---
// Pastikan hanya proses jika user berhak dan ada koneksi DB
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['can_set_password']) && $_SESSION['can_set_password'] === true && isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {

    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 1. Validasi input password baru
    if (empty($new_password) || empty($confirm_password)) {
        $message = '<div class="alert alert-warning" role="alert">Password baru dan konfirmasi password tidak boleh kosong.</div>';
        // $show_form tetap true
    } elseif ($new_password !== $confirm_password) {
        $message = '<div class="alert alert-warning" role="alert">Password baru dan konfirmasi password tidak cocok.</div>';
         // $show_form tetap true
    }
    // Anda bisa tambahkan validasi kekuatan password baru di sini (min length, karakter khusus, dll)
    // elseif (strlen($new_password) < 8) { ... $message = ...; }

    else {
        // Validasi password sukses
        // 2. Hash Password Baru
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // 3. Update Password di Database
        // Kita menggunakan $user_id_to_update yang diambil dari session di awal script
        $update_query = "UPDATE admin SET password = ?, reset_token = NULL, reset_token_expires_at = NULL, otp_code = NULL, otp_expires_at = NULL WHERE id = ?"; // Kosongkan juga kolom OTP
        $update_stmt = mysqli_prepare($conn, $update_query);

        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id_to_update);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);

            // Password berhasil diupdate!

            // 4. Clear Semua Session Terkait Reset Password
            unset($_SESSION['can_set_password']);
            unset($_SESSION['set_password_user_id']);
            // Pastikan juga session OTP dari step sebelumnya sudah terhapus (seharusnya sudah di admin_verify_otp.php)
            unset($_SESSION['otp_user_id']);
            unset($_SESSION['otp_phone_number']);
            unset($_SESSION['otp_process_active']);


            // 5. Berikan Pesan Sukses Akhir
            $message = '<div class="alert alert-success" role="alert">Password Anda berhasil diperbarui. Anda sekarang bisa login dengan password baru.</div>';
            $show_form = false; // Jangan tampilkan form lagi
            // Anda bisa redirect ke halaman login di sini jika mau
            // header("Location: login.php?status=password_reset_success"); exit;

        } else {
            // Gagal update password
            $message = '<div class="alert alert-danger" role="alert">Terjadi kesalahan database saat memperbarui password. Mohon coba lagi nanti.</div>';
            error_log("Password update prepare error in set_new_password: " . mysqli_error($conn));
             // $show_form tetap true
        }
    } // end else (validasi password sukses)

} // end if method POST

// Tutup koneksi database
if (isset($conn) && $conn instanceof mysqli) {
    // Hanya tutup jika koneksi masih terbuka dan bukan request POST yang berhasil redirect
     if (!($_SERVER["REQUEST_METHOD"] == "POST" && headers_sent())) {
         @mysqli_close($conn); // Menggunakan @ untuk mencegah warning jika sudah tertutup
     }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atur Password Baru Admin</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <link href="public/vendor/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="icon" type="image/jpg" href="public/asset/logo.jpg">

    <style>
        /* Anda bisa reuse style background dari halaman login */
        body.bg-gradient-primary {
             background: url('public/asset/gedunghd.png') no-repeat center center fixed;
             background-size: cover;
        }
        .card-transparent {
             background-color: rgba(255, 255, 255, 0.93);
        }
         .small a {
             color: #858796; /* Warna teks sekunder SB Admin 2 */
             text-decoration: none;
         }
         .small a:hover {
             text-decoration: underline;
         }
         /* Style untuk gambar di sisi kiri */
         .bg-password-image {
             /* Ganti URL gambar di style inline di HTML jika berbeda */
             background: url('public/asset/password_reset_image.jpg') no-repeat center center;
             background-size: cover;
         }
          .input-group-text {
             cursor: pointer;
         }
    </style>
</head>
<body class="bg-gradient-primary">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5 card-transparent">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-password-image">
                                </div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-2">Atur Password Baru</h1>
                                        <p class="mb-4">Silakan masukkan password baru Anda.</p>
                                    </div>

                                    <?php
                                    // Tampilkan pesan (sukses/gagal/warning) di sini
                                    if (!empty($message)) {
                                        echo $message;
                                    }
                                    ?>

                                    <?php if ($show_form): // Tampilkan form jika berhak dan belum berhasil ?>
                                        <form class="user" method="POST" action="admin_set_new_password_otp.php">

                                            <div class="form-group">
                                                 <label for="new_password">Password Baru:</label>
                                                <div class="input-group">
                                                     <input type="password" class="form-control form-control-user"
                                                            id="new_password" name="new_password" placeholder="Masukkan Password Baru" required>
                                                     <span class="input-group-text bg-white border-left-0" onclick="togglePassword('new_password', 'toggleNewPasswordIcon1')">
                                                         <i class="fas fa-eye" id="toggleNewPasswordIcon1"></i>
                                                     </span>
                                                 </div>
                                            </div>
                                            <div class="form-group">
                                                 <label for="confirm_password">Konfirmasi Password Baru:</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control form-control-user"
                                                           id="confirm_password" name="confirm_password" placeholder="Konfirmasi Password Baru" required>
                                                    <span class="input-group-text bg-white border-left-0" onclick="togglePassword('confirm_password', 'toggleConfirmPasswordIcon2')">
                                                        <i class="fas fa-eye" id="toggleConfirmPasswordIcon2"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-user btn-block">
                                                Simpan Password Baru
                                            </button>
                                        </form>
                                    <?php endif; // Akhir if ($show_form) ?>

                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="login.php">Kembali ke Halaman Login</a>
                                    </div>
                                     <?php if (!$show_form): ?>
                                         <div class="text-center mt-3">
                                             <a class="btn btn-primary" href="forgot_password_wa.php">Ajukan OTP Baru</a>
                                         </div>
                                     <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="public/vendor/jquery/jquery.min.js"></script>
    <script src="public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script src="public/vendor/jquery-easing/jquery.easing.min.js"></script>

    <script src="public/vendor/js/sb-admin-2.min.js"></script>

    <script>
         // Fungsi toggle password (sama seperti sebelumnya)
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