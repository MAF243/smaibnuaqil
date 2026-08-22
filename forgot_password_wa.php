<?php
// FILE: forgot_password.php

// Mulai sesi
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sertakan file konfigurasi database
// Pastikan path 'config.php' sudah benar
if (file_exists('config.php')) {
    include_once 'config.php';
} else {
    // Jika config.php tidak ditemukan, tampilkan pesan error
    // Di produksi: error_log("File konfigurasi database (config.php) tidak ditemukan di forgot_password.php");
    die("Error: Database configuration file not found.");
}

// Pastikan koneksi database ada dan berhasil
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    // Jika koneksi gagal
    $error_message = (isset($conn) && $conn instanceof mysqli) ? $conn->connect_error : 'Koneksi database tidak terdefinisi atau bukan objek mysqli.';
    // Di produksi: error_log("Koneksi database gagal di forgot_password.php: " . $error_message);
    die("Error connecting to database: " . htmlspecialchars($error_message));
}

// Enable error reporting for debugging (HATI-HATI! Nonaktifkan di produksi)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = ''; // Variabel untuk pesan feedback ke user

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_username = trim($_POST['email_username'] ?? ''); // Ambil dan bersihkan input

    if (empty($email_username)) {
        $message = '<div class="alert alert-warning" role="alert">Mohon masukkan Email atau Username.</div>';
    } else {
        // 1. Cari user berdasarkan username atau email
        // **PASTIKAN** ada kolom 'email' di tabel 'admin' Anda
        $query = "SELECT id, username, email FROM admin WHERE username = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $email_username, $email_username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($user) {
                // User ditemukan DAN user memiliki email terdaftar
                if (!empty($user['email'])) {
                    // 2. Generate Token Unik dan Waktu Kedaluwarsa
                    $token = bin2hex(random_bytes(32)); // Menghasilkan token 64 karakter hex
                    $expires = date("Y-m-d H:i:s", strtotime('+1 hour')); // Token kedaluwarsa 1 jam dari sekarang

                    // 3. Simpan Token dan Waktu Kedaluwarsa di Database
                    // **PASTIKAN** ada kolom 'reset_token' dan 'reset_token_expires_at' di tabel 'admin'
                    $update_query = "UPDATE admin SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_query);

                    if ($update_stmt) {
                        mysqli_stmt_bind_param($update_stmt, "ssi", $token, $expires, $user['id']);
                        mysqli_stmt_execute($update_stmt);
                        mysqli_stmt_close($update_stmt);

                        // 4. Kirim Email Berisi Link Reset Password
                        // **PENTING:** Ganti 'reset_password.php' dengan nama file halaman reset password Anda
                        // Pastikan URL ini benar sesuai lokasi file di server Anda
                        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;

                        $to = $user['email']; // Ambil email dari data user
                        $subject = "Reset Password Akun Admin SMA Ibnu'Aqil";
                        $email_body = 'Halo Admin ' . htmlspecialchars($user['username']) . ",\n\n";
                        $email_body .= "Anda menerima email ini karena ada permintaan reset password untuk akun admin Anda.\n\n";
                        $email_body .= "Klik link berikut untuk mereset password Anda:\n";
                        $email_body .= $reset_link . "\n\n";
                        $email_body .= "Link ini akan kedaluwarsa dalam 1 jam.\n";
                        $email_body .= "Jika Anda tidak merasa melakukan permintaan ini, abaikan saja email ini.\n\n";
                        $email_body .= "Terima kasih,\nTim Admin SMA Ibnu'Aqil";

                        $headers = 'From: noreply@smaibnuaqil.my.id' . "\r\n" . // Ganti dengan alamat email pengirim yang sesuai
                                   'Reply-To: noreply@smaibnuaqil.my.id' . "\r\n" .
                                   'X-Mailer: PHP/' . phpversion();

                        // ** BAGIAN PENTING: Mengirim Email **
                        // Fungsi mail() dasar PHP. Mungkin perlu konfigurasi server (sendmail, SMTP, dll.)
                        // Untuk solusi yang lebih robust dan fitur seperti SMTP, gunakan library seperti PHPMailer.
                        if (mail($to, $subject, $email_body, $headers)) {
                            // Email berhasil diproses oleh fungsi mail() (bukan jaminan sampai di inbox)
                            $message = '<div class="alert alert-success" role="alert">Link reset password telah dikirim ke alamat email Anda (jika akun ditemukan). Mohon cek folder inbox/spam Anda.</div>';
                        } else {
                            // Gagal memanggil fungsi mail()
                            error_log("Failed to send reset password email to " . $user['email']);
                             $message = '<div class="alert alert-danger" role="alert">Gagal mengirim email reset password. Mohon coba lagi nanti atau hubungi administrator.</div>';
                             // Untuk keamanan, bisa tampilkan pesan sukses generik
                             // $message = '<div class="alert alert-success" role="alert">Jika akun ditemukan, instruksi reset password telah dikirim.</div>';
                        }

                    } else {
                         // Jika prepared statement update gagal
                         error_log("Reset token update prepare error: " . mysqli_error($conn));
                         $message = '<div class="alert alert-danger" role="alert">Terjadi kesalahan database saat menyiapkan reset password. Mohon coba lagi nanti.</div>';
                         // Untuk keamanan, bisa tampilkan pesan sukses generik
                         // $message = '<div class="alert alert-success" role="alert">Jika akun ditemukan, instruksi reset password telah dikirim.</div>';
                    }
                } else {
                     // User ditemukan tapi kolom email kosong
                     $message = '<div class="alert alert-warning" role="alert">Akun ditemukan, tetapi email admin tidak terdaftar di database. Mohon hubungi administrator untuk mereset password Anda secara manual.</div>';
                }

            } else {
                // User tidak ditemukan
                // Tampilkan pesan sukses generik untuk keamanan
                $message = '<div class="alert alert-success" role="alert">Link reset password telah dikirim ke alamat email Anda (jika akun ditemukan). Mohon cek folder inbox/spam Anda.</div>';
            }

        } else {
            // Jika prepared statement select gagal
             error_log("User lookup prepare error: " . mysqli_error($conn));
            $message = '<div class="alert alert-danger" role="alert">Terjadi kesalahan database saat mencari akun Anda. Mohon coba lagi nanti.</div>';
             // Untuk keamanan, bisa tampilkan pesan sukses generik
             // $message = '<div class="alert alert-success" role="alert">Jika akun ditemukan, instruksi reset password telah dikirim.</div>';
        }
    }
}

// Tutup koneksi database di akhir script
if (isset($conn) && $conn instanceof mysqli) {
    mysqli_close($conn);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password Admin</title>

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
                                        <h1 class="h4 text-gray-900 mb-2">Lupa Password Anda?</h1>
                                        <p class="mb-4">Kami mengerti, hal itu terjadi. Cukup masukkan alamat email atau username Admin Anda di bawah ini dan kami akan kirimkan link untuk mereset password Anda!</p>
                                    </div>

                                    <?php
                                    // Tampilkan pesan (sukses/gagal/warning) di sini
                                    if (!empty($message)) {
                                        echo $message;
                                    }
                                    ?>

                                    <form class="user" method="POST" action="forgot_password.php">
                                        <div class="form-group">
                                            <input type="text" class="form-control form-control-user"
                                                id="emailOrUsername" name="email_username" aria-describedby="emailHelp"
                                                placeholder="Masukkan Email atau Username..." required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Reset Password
                                        </button>
                                    </form>
                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="login.php">Sudah punya akun? Login!</a>
                                    </div>
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