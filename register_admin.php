<?php
session_start();
// Pastikan config.php ada dan $conn (koneksi MySQLi) terdefinisi.
// Sesuaikan path jika file ini tidak satu folder dengan config.php
// Contoh: include __DIR__ . '/config.php';
include 'config.php'; 

// Untuk menampilkan error PHP (penting selama development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$registration_message = '';
$message_type = ''; // Akan diisi 'success' atau 'danger' untuk styling alert Bootstrap

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'] ?? ''; // Default ke string kosong jika tidak ada
    $confirm_password = $_POST['confirm_password'] ?? ''; // Default ke string kosong

    if (empty($username) || empty($password) || empty($confirm_password)) {
        $registration_message = "Semua field (Username, Password, Konfirmasi Password) harus diisi!";
        $message_type = 'danger';
    } elseif ($password !== $confirm_password) {
        $registration_message = "Password dan konfirmasi password tidak cocok!";
        $message_type = 'danger';
    } elseif (strlen($password) < 6) { // Contoh validasi panjang password minimal
        $registration_message = "Password minimal harus 6 karakter!";
        $message_type = 'danger';
    } else {
        // Cek apakah username sudah ada di tabel 'admin'
        $query_check = "SELECT username FROM admin WHERE username = ?";
        $stmt_check = mysqli_prepare($conn, $query_check);

        if ($stmt_check) {
            mysqli_stmt_bind_param($stmt_check, "s", $username);
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);

            if (mysqli_fetch_assoc($result_check)) {
                $registration_message = "Username sudah digunakan. Silakan pilih username lain.";
                $message_type = 'danger';
                mysqli_stmt_close($stmt_check);
            } else {
                mysqli_stmt_close($stmt_check); // Tutup statement check sebelum membuka yang baru
                
                // Username belum ada, hash password dan insert
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query_insert = "INSERT INTO admin (username, password) VALUES (?, ?)";
                $stmt_insert = mysqli_prepare($conn, $query_insert);

                if ($stmt_insert) {
                    mysqli_stmt_bind_param($stmt_insert, "ss", $username, $hashed_password);
                    if (mysqli_stmt_execute($stmt_insert)) {
                        $registration_message = "Registrasi admin berhasil! Anda sekarang bisa login.";
                        $message_type = 'success';
                        // Anda bisa mengarahkan pengguna ke halaman login admin secara otomatis setelah beberapa detik jika mau
                        // header("refresh:3;url=login.php"); // Ganti nama file jika perlu
                    } else {
                        $registration_message = "Registrasi gagal saat menyimpan data: " . htmlspecialchars(mysqli_stmt_error($stmt_insert));
                        $message_type = 'danger';
                        error_log("Admin Registration Execute Error: " . mysqli_stmt_error($stmt_insert));
                    }
                    mysqli_stmt_close($stmt_insert);
                } else {
                    $registration_message = "Registrasi gagal (persiapan query insert): " . htmlspecialchars(mysqli_error($conn));
                    $message_type = 'danger';
                    error_log("Admin Registration Prepare Insert Error: " . mysqli_error($conn));
                }
            }
        } else {
            $registration_message = "Registrasi gagal (persiapan query check): " . htmlspecialchars(mysqli_error($conn));
            $message_type = 'danger';
            error_log("Admin Registration Prepare Check Error: " . mysqli_error($conn));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrasi Admin Baru</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="public/vendor/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="icon" type="image/jpg" href="public/asset/logo.jpg">

    <style>
        body.bg-gradient-primary {
            /* Pastikan path ke gedunghd.png benar */
            background: url('public/asset/gedunghd.png') no-repeat center center fixed;
            background-size: cover;
        }
        .card-transparent {
            background-color: rgba(255, 255, 255, 0.93); /* Sedikit lebih transparan */
        }
        .input-group-text { /* Untuk ikon mata */
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gradient-primary">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-8 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5 card-transparent">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="p-5">
                                    <div class="text-center mb-4">
                                        <h1 class="h4 text-gray-900">Buat Akun Admin</h1>
                                    </div>

                                    <?php if (!empty($registration_message)): ?>
                                        <div class="alert alert-<?= htmlspecialchars($message_type) ?> text-center" role="alert">
                                            <?= htmlspecialchars($registration_message) ?>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" action="register_admin.php" class="user"> {/* Action bisa dikosongkan jika submit ke halaman sendiri */}
                                        <div class="form-group">
                                            <input type="text" name="username" class="form-control form-control-user" placeholder="Masukkan Username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <div class="input-group">
                                                <input type="password" name="password" id="password" class="form-control form-control-user" placeholder="Masukkan Password (min. 6 karakter)" required>
                                                <span class="input-group-text bg-white border-left-0" onclick="togglePasswordVisibility('password', 'togglePasswordIcon')">
                                                    <i class="fas fa-eye-slash" id="togglePasswordIcon"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="input-group">
                                                <input type="password" name="confirm_password" id="confirm_password" class="form-control form-control-user" placeholder="Konfirmasi Password" required>
                                                <span class="input-group-text bg-white border-left-0" onclick="togglePasswordVisibility('confirm_password', 'toggleConfirmPasswordIcon')">
                                                    <i class="fas fa-eye-slash" id="toggleConfirmPasswordIcon"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Daftar Akun
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

    <script>
        function togglePasswordVisibility(fieldId, iconId) {
            const input = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);

            if (input && icon) { // Pastikan elemen ada
                if (input.type === "password") {
                    input.type = "text";
                    icon.classList.remove("fa-eye-slash");
                    icon.classList.add("fa-eye");
                } else {
                    input.type = "password";
                    icon.classList.remove("fa-eye");
                    icon.classList.add("fa-eye-slash");
                }
            }
        }
    </script>
</body>
</html>