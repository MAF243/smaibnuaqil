<?php
// FILE: admin_verify_otp.php

// Mulai sesi
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sedang dalam proses OTP (misal dari forgot_password_wa.php)
// Kita cek session yang kita set di forgot_password_wa.php
if (!isset($_SESSION['otp_user_id']) || !isset($_SESSION['otp_process_active'])) {
    // Jika tidak dalam proses OTP yang valid, hapus session sisa dan arahkan kembali
    unset($_SESSION['otp_user_id']);
    unset($_SESSION['otp_phone_number']);
    unset($_SESSION['otp_process_active']);
    header("Location: forgot_password_wa.php"); // Arahkan kembali ke halaman input nomor HP
    exit;
}

// Ambil data user dari session
$otp_user_id = $_SESSION['otp_user_id'];
$otp_phone_number = $_SESSION['otp_phone_number'] ?? 'nomor Anda'; // Ambil nomor HP dari session jika ada

// Sertakan file konfigurasi database
if (file_exists('config.php')) {
    include_once 'config.php';
} else {
    error_log("File konfigurasi database (config.php) tidak ditemukan di admin_verify_otp.php");
    // Berikan pesan error dan hentikan proses
     $message = '<div class="alert alert-danger" role="alert">Kesalahan sistem: Konfigurasi database tidak ditemukan.</div>';
     // Hentikan proses OTP karena tidak bisa validasi
     unset($_SESSION['otp_user_id']);
     unset($_SESSION['otp_phone_number']);
     unset($_SESSION['otp_process_active']);
     // Jangan die() di sini, biarkan HTML ditampilkan dengan pesan error
}

// Pastikan koneksi database ada dan berhasil (hanya jika config.php ditemukan)
if (isset($conn) && (!($conn instanceof mysqli) || $conn->connect_error)) {
    $error_message = (isset($conn) && $conn instanceof mysqli) ? $conn->connect_error : 'Koneksi database tidak terdefinisi atau bukan objek mysqli.';
    error_log("Koneksi database gagal di admin_verify_otp.php: " . $error_message);
     $message = '<div class="alert alert-danger" role="alert">Kesalahan sistem: Koneksi database gagal.</div>';
     // Hentikan proses OTP karena tidak bisa validasi
     unset($_SESSION['otp_user_id']);
     unset($_SESSION['otp_phone_number']);
     unset($_SESSION['otp_process_active']);
}


// Enable error reporting for debugging (HATI-HATI! Nonaktifkan di produksi)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Variabel untuk pesan feedback ke user (jika belum diisi dari error config/koneksi)
if (!isset($message)) {
    $message = '';
}

// Flag untuk menampilkan form (default true jika berhak)
// Asumsi user berhak jika sesi OTP aktif DAN tidak ada error config/koneksi
$show_form = isset($_SESSION['otp_process_active']) && !isset($conn) || (isset($conn) && !$conn->connect_error);


// --- Handle POST Request (Saat Form Verifikasi OTP Disubmit) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['otp_code'])) {

    // Pastikan masih dalam proses OTP dan ada koneksi database yang valid
    if (isset($_SESSION['otp_user_id']) && isset($_SESSION['otp_process_active']) && isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $entered_otp = trim($_POST['otp_code']); // Ambil dan bersihkan input OTP
        $current_time = date("Y-m-d H:i:s"); // Waktu sekarang untuk cek kedaluwarsa

        if (empty($entered_otp)) {
            $message = '<div class="alert alert-warning" role="alert">Mohon masukkan kode OTP.</div>';
            $show_form = true; // Tetap tampilkan form
        } else {
            // 1. Cari user berdasarkan ID dari session dan validasi OTP
            // **PASTIKAN** nama kolom 'otp_code' dan 'otp_expires_at' sesuai di tabel 'admin'
            $query = "SELECT id FROM admin WHERE id = ? AND otp_code = ? AND otp_expires_at > ?";
            $stmt = mysqli_prepare($conn, $query);

            if ($stmt) {
                // Convert entered OTP to the correct type if necessary (e.g., int if DB column is INT)
                // For VARCHAR(10) in DB, string is fine
                mysqli_stmt_bind_param($stmt, "iss", $otp_user_id, $entered_otp, $current_time);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result); // Akan hanya 1 baris atau 0
                mysqli_stmt_close($stmt);

                if ($user) {
                    // Kode OTP valid dan belum kedaluwarsa!
                    // Berarti user berhak mengganti password

                    // **PENTING:** Clear OTP dari DB setelah berhasil diverifikasi
                    $clear_otp_query = "UPDATE admin SET otp_code = NULL, otp_expires_at = NULL WHERE id = ?";
                    $clear_otp_stmt = mysqli_prepare($conn, $clear_otp_query);
                    if ($clear_otp_stmt) {
                        mysqli_stmt_bind_param($clear_otp_stmt, "i", $otp_user_id);
                        mysqli_stmt_execute($clear_otp_stmt);
                        mysqli_stmt_close($clear_otp_stmt);
                    } else {
                        error_log("Failed to clear OTP for user ID " . $otp_user_id . ": " . mysqli_error($conn));
                        // Lanjutkan proses, tapi catat error
                    }


                    // Arahkan user ke halaman untuk mengatur password baru

                    // Set session flag atau data yang diperlukan untuk halaman set password baru
                    $_SESSION['can_set_password'] = true; // Menandai user sudah terverifikasi OTP
                    $_SESSION['set_password_user_id'] = $otp_user_id; // Sampaikan user ID ke halaman berikutnya

                    // Hapus session OTP yang sudah tidak diperlukan
                    unset($_SESSION['otp_user_id']);
                    unset($_SESSION['otp_phone_number']);
                    unset($_SESSION['otp_process_active']);


                    // **PENTING:** Ganti 'admin_set_new_password_otp.php' dengan nama file halaman set password baru Anda
                    header("Location: admin_set_new_password_otp.php");
                    exit; // Penting untuk menghentikan eksekusi

                } else {
                    // Kode OTP tidak valid atau sudah kedaluwarsa
                    $message = '<div class="alert alert-danger" role="alert">Kode OTP salah atau sudah kedaluwarsa.</div>';
                    $show_form = true; // Tetap tampilkan form
                    // Opsional: Beri user kesempatan mencoba lagi atau ajukan kirim ulang OTP
                    // Untuk keamanan, batasi jumlah percobaan OTP
                }

            } else {
                 // Gagal prepared statement saat validasi OTP
                 $message = '<div class="alert alert-danger" role="alert">Terjadi kesalahan database saat memverifikasi OTP. Mohon coba lagi nanti.</div>';
                 error_log("OTP verification prepare error: " . mysqli_error($conn));
                 $show_form = true; // Tetap tampilkan form
            }
        } // end else (input not empty)

    } else {
        // Kasus jika POST diterima tapi session OTP tidak valid atau koneksi DB bermasalah
        // Pesan error sudah diset di awal PHP jika ada masalah config/koneksi
        // Jika session OTP tiba-tiba hilang saat POST, ini bisa terjadi
         $message = '<div class="alert alert-danger" role="alert">Sesi verifikasi tidak valid. Mohon ajukan permintaan OTP baru.</div>';
         $show_form = false; // Jangan tampilkan form
         unset($_SESSION['otp_user_id']);
         unset($_SESSION['otp_phone_number']);
         unset($_SESSION['otp_process_active']);
    }
}

// Tutup koneksi database jika belum ditutup
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
    <title>Verifikasi OTP Admin</title>

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
             background: url('public/asset/password_reset_image_wa.jpg') no-repeat center center;
             background-size: cover;
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
                                        <h1 class="h4 text-gray-900 mb-2">Verifikasi Kode OTP</h1>
                                        <p class="mb-4">Kode OTP telah dikirim ke nomor WhatsApp Anda **<?php echo htmlspecialchars($otp_phone_number); ?>**.</p>
                                         <p class="mb-4">Mohon masukkan kode tersebut di bawah ini.</p>
                                    </div>

                                    <?php
                                    // Tampilkan pesan (sukses/gagal/warning) di sini
                                    if (!empty($message)) {
                                        echo $message;
                                    }
                                    ?>

                                    <?php
                                    // Hanya tampilkan form jika user masih dalam proses OTP dan tidak ada error fatal
                                     if ($show_form):
                                    ?>

                                        <form class="user" method="POST" action="admin_verify_otp.php">
                                            <div class="form-group">
                                                <label for="otpCodeInput">Kode OTP:</label>
                                                <input type="text" class="form-control form-control-user"
                                                    id="otpCodeInput" name="otp_code"
                                                    placeholder="Masukkan Kode OTP yang diterima..." required>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-user btn-block">
                                                Verifikasi
                                            </button>
                                        </form>
                                        <hr>
                                         <?php endif; // Akhir if ($show_form) ?>

                                    <div class="text-center">
                                        <a class="small" href="login.php">Kembali ke Halaman Login</a>
                                    </div>
                                    <?php if (!$show_form): // Tampilkan link ajukan OTP baru hanya jika form tidak tampil ?>
                                         <div class="text-center mt-3">
                                             <a class="small" href="forgot_password_wa.php">Ajukan OTP Baru</a>
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

</body>
</html>