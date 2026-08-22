<?php
// FILE: reset_password.php

// Mulai sesi
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sertakan file konfigurasi database
if (file_exists('config.php')) {
    include_once 'config.php';
} else {
    die("Error: Database configuration file not found.");
}

// Pastikan koneksi database ada dan berhasil
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $error_message = (isset($conn) && $conn instanceof mysqli) ? $conn->connect_error : 'Koneksi database tidak terdefinisi atau bukan objek mysqli.';
    die("Error connecting to database: " . htmlspecialchars($error_message));
}

// Enable error reporting for debugging (HATI-HATI! Nonaktifkan di produksi)
error_reporting(E_ALL);
ini_set('display_errors', 1);


$message = ''; // Variabel untuk pesan feedback ke user
$show_form = false; // Apakah form ganti password ditampilkan
$valid_user_id = null; // ID user jika token valid
$reset_token_value = ''; // Nilai token dari URL atau POST

// Mendapatkan waktu sekarang dalam format database
$current_time = date("Y-m-d H:i:s");

// --- Proses REQUEST (saat user KLIK LINK dari email atau SUBMIT FORM) ---

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['token'])) {
    // Kasus 1: User mengklik link dari email (Metode GET dengan token di URL)
    $reset_token_value = $_GET['token'];

    // 1. Cari user berdasarkan token DAN pastikan token belum kedaluwarsa
    // **PASTIKAN** nama kolom 'reset_token' dan 'reset_token_expires_at' sesuai di tabel 'admin'
    $query = "SELECT id, username FROM admin WHERE reset_token = ? AND reset_token_expires_at > ?";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $reset_token_value, $current_time);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user) {
            // Token valid dan belum kedaluwarsa
            $show_form = true; // Tampilkan form ganti password
            $valid_user_id = $user['id']; // Simpan ID user untuk digunakan saat update
            // Kita simpan token dan user ID di session untuk digunakan saat form disubmit (metode POST)
            $_SESSION['reset_token'] = $reset_token_value; // Simpan token di session
            $_SESSION['reset_user_id'] = $valid_user_id; // Simpan user ID di session

            $message = '<div class="alert alert-info" role="alert">Token valid. Silakan masukkan password baru Anda.</div>';

        } else {
            // Token tidak valid atau sudah kedaluwarsa
            $message = '<div class="alert alert-danger" role="alert">Link reset password tidak valid atau sudah kedaluwarsa. Mohon ajukan permintaan reset password baru.</div>';
             // Opsional: Hapus token yang sudah kedaluwarsa dari database secara berkala
        }

    } else {
        // Jika prepared statement gagal saat mencari token
        $message = '<div class="alert alert-danger" role="alert">Terjadi kesalahan database. Mohon coba lagi nanti.</div>';
        error_log("Reset password token lookup prepare error: " . mysqli_error($conn));
    }

} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['password']) && isset($_POST['confirm_password']) && isset($_SESSION['reset_token']) && isset($_SESSION['reset_user_id'])) {
    // Kasus 2: User SUBMIT FORM password baru (Metode POST)
    // Memastikan ada data password di POST dan ada token/user ID di session
    $reset_token_value = $_SESSION['reset_token']; // Ambil token dari session
    $user_id_from_session = $_SESSION['reset_user_id']; // Ambil user ID dari session

    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validasi input password baru
    if (empty($password) || empty($confirm_password)) {
        $message = '<div class="alert alert-warning" role="alert">Password dan konfirmasi password tidak boleh kosong.</div>';
        $show_form = true; // Tetap tampilkan form
        // Pertahankan token dan user ID di session
        // $_SESSION['reset_token'] = $reset_token_value; // Sudah ada di session
        // $_SESSION['reset_user_id'] = $user_id_from_session; // Sudah ada di session

    } elseif ($password !== $confirm_password) {
        $message = '<div class="alert alert-warning" role="alert">Password baru dan konfirmasi password tidak cocok.</div>';
        $show_form = true; // Tetap tampilkan form
         // Pertahankan token dan user ID di session
        // $_SESSION['reset_token'] = $reset_token_value; // Sudah ada di session
        // $_SESSION['reset_user_id'] = $user_id_from_session; // Sudah ada di session

    } else {
        // Password cocok dan tidak kosong

        // 2. Re-Validasi Token (Sangat Penting untuk Keamanan!)
        // Pastikan token masih valid dan belum kedaluwarsa saat form disubmit
        $query = "SELECT id FROM admin WHERE id = ? AND reset_token = ? AND reset_token_expires_at > ?";
        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {
             mysqli_stmt_bind_param($stmt, "iss", $user_id_from_session, $reset_token_value, $current_time);
             mysqli_stmt_execute($stmt);
             $result = mysqli_stmt_get_result($stmt);
             $user = mysqli_fetch_assoc($result);
             mysqli_stmt_close($stmt);

             if ($user) {
                 // Token masih valid saat POST! User ID cocok.

                 // 3. Hash Password Baru
                 $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                 // 4. Update Password di Database dan Hapus Token Reset
                 $update_query = "UPDATE admin SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?";
                 $update_stmt = mysqli_prepare($conn, $update_query);

                 if ($update_stmt) {
                     mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user['id']); // Gunakan user['id'] dari hasil query re-validasi
                     mysqli_stmt_execute($update_stmt);
                     mysqli_stmt_close($update_stmt);

                     // Password berhasil diupdate!

                     // 5. Berikan Pesan Sukses dan Hapus Session Reset
                     $message = '<div class="alert alert-success" role="alert">Password Anda berhasil diperbarui. Anda sekarang bisa login dengan password baru.</div>';
                     $show_form = false; // Jangan tampilkan form lagi

                     // Hapus semua session terkait reset password
                     unset($_SESSION['reset_token']);
                     unset($_SESSION['reset_user_id']);
                     // Pastikan juga session OTP dari alur lain terhapus jika ada
                     unset($_SESSION['otp_user_id']);
                     unset($_SESSION['otp_phone_number']);
                     unset($_SESSION['otp_process_active']);


                     // Anda bisa redirect ke halaman login di sini jika mau
                     // header("Location: login.php?status=password_reset_success"); exit;

                 } else {
                     // Gagal update password di DB
                     $message = '<div class="alert alert-danger" role="alert">Terjadi kesalahan database saat memperbarui password. Mohon coba lagi nanti.</div>';
                     error_log("Password update prepare error in reset_password: " . mysqli_error($conn));
                     $show_form = true; // Tetap tampilkan form
                      // Pertahankan token dan user ID di session
                     // $_SESSION['reset_token'] = $reset_token_value; // Sudah ada di session
                     // $_SESSION['reset_user_id'] = $user_id_from_session; // Sudah ada di session
                 }

             } else {
                 // Token tidak lagi valid saat POST (sudah kedaluwarsa atau user ID di session salah)
                 $message = '<div class="alert alert-danger" role="alert">Sesi reset password tidak valid atau sudah kedaluwarsa. Mohon ajukan permintaan reset password baru.</div>';
                 $show_form = false; // Jangan tampilkan form
                 // Hapus semua session terkait reset password
                 unset($_SESSION['reset_token']);
                 unset($_SESSION['reset_user_id']);
             }

         } else {
             // Gagal prepared statement re-validasi token
             $message = '<div class="alert alert-danger" role="alert">Terjadi kesalahan database saat memverifikasi token. Mohon coba lagi nanti.</div>';
             error_log("Reset password token re-validation prepare error in reset_password: " . mysqli_error($conn));
             $show_form = true; // Tetap tampilkan form
              // Pertahankan token dan user ID di session
             // $_SESSION['reset_token'] = $reset_token_value; // Sudah ada di session
             // $_SESSION['reset_user_id'] = $user_id_from_session; // Sudah ada di session
         }
    } // end else (validasi password sukses)

} else {
    // Kasus 3: Halaman diakses non-valid (misal GET tanpa token, atau POST tanpa data/session)
    // Jika POST tanpa data/session, pesan error sudah diset.
    // Jika GET tanpa token, pesan error sudah diset di awal GET handler.

    // Jika ini POST tanpa data password tapi session reset valid (anomali?)
    if (isset($_SESSION['reset_token']) && isset($_SESSION['reset_user_id'])) {
         $show_form = true; // Tetap tampilkan form jika sesi reset valid
          // Pertahankan token dan user ID di session
         // $_SESSION['reset_token'] = $_SESSION['reset_token']; // Sudah ada di session
         // $_SESSION['reset_user_id'] = $_SESSION['reset_user_id']; // Sudah ada di session
    } else {
         // Jika bukan GET dengan token valid, dan bukan POST dengan sesi reset valid
         // Pesan error sudah diset di awal GET handler
         $show_form = false; // Jangan tampilkan form
         // Hapus session reset jika ada (harusnya tidak ada kalau sampai sini)
         unset($_SESSION['reset_token']);
         unset($_SESSION['reset_user_id']);
    }

}

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
    <title>Reset Password Admin</title>

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
                                        <h1 class="h4 text-gray-900 mb-2">Reset Password</h1>
                                        <p class="mb-4">Silakan masukkan password baru Anda.</p>
                                    </div>

                                    <?php
                                    // Tampilkan pesan (sukses/gagal/warning) di sini
                                    if (!empty($message)) {
                                        echo $message;
                                    }
                                    ?>

                                    <?php if ($show_form): // Tampilkan form jika berhak dan belum berhasil ?>
                                        <form class="user" method="POST" action="reset_password.php">
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
                                                Ubah Password
                                            </button>
                                        </form>
                                    <?php endif; // Akhir if ($show_form) ?>

                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="login.php">Kembali ke Halaman Login</a>
                                    </div>
                                     <?php if (!$show_form): ?>
                                         <div class="text-center mt-3">
                                             <a class="small" href="forgot_password.php">Ajukan Reset Password Baru</a>
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