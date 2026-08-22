<?php
session_start();
// Diasumsikan Anda memiliki file config.php di path yang benar
// dan $pdo sudah diinisialisasi di sana.
// Jika file login dan config.php tidak satu folder, sesuaikan path require.
require 'config.php'; 

$error = ''; // Inisialisasi variabel error

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Username dan password tidak boleh kosong!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Simpan user ID dan username ke session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];

                // Ganti 'dashboard_register.php' jika nama file dashboard Anda berbeda
                header("Location: dashboard_register.php"); 
                exit;
            } else {
                $error = "Username atau password salah!";
            }
        } catch (PDOException $e) {
            // Di lingkungan produksi, sebaiknya catat error ini ke log server
            // dan tampilkan pesan yang lebih umum kepada pengguna.
            error_log("Login Gagal (PDOException): " . $e->getMessage());
            $error = "Terjadi masalah dengan sistem login. Silakan coba lagi nanti.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Pendaftar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Opsional: membuat kursor menjadi pointer saat diarahkan ke tombol toggle mata */
        #togglePassword {
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h3 class="card-title text-center mb-4">Login Akun</h3>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

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
                        <button type="submit" class="btn btn-success w-100">Login</button>
                    </form>
                    <p class="text-center mt-3">Belum punya akun? <a href="register.php">Daftar sekarang</a></p> 
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordField = document.getElementById('passwordField');
    const togglePasswordButton = document.getElementById('togglePassword');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    // Pastikan semua elemen ada sebelum menambahkan event listener
    if (passwordField && togglePasswordButton && togglePasswordIcon) {
        togglePasswordButton.addEventListener('click', function () {
            // Toggle tipe input password (dari password ke text, atau sebaliknya)
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);

            // Ganti ikon mata sesuai kondisi
            if (type === 'password') {
                // Jika password tersembunyi, ikon mata tercoret
                togglePasswordIcon.classList.remove('bi-eye');
                togglePasswordIcon.classList.add('bi-eye-slash');
            } else {
                // Jika password terlihat, ikon mata terbuka
                togglePasswordIcon.classList.remove('bi-eye-slash');
                togglePasswordIcon.classList.add('bi-eye');
            }
        });
    }
});
</script>
</body>
</html>