<?php
session_start(); // Meskipun tidak banyak digunakan di sini, baik untuk konsistensi
include 'config.php'; // Sertakan file koneksi database Anda

// Pastikan skrip hanya diakses melalui metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validasi input username
    if (empty(trim($_POST['username']))) {
        header("Location: forgot.html?status=username_kosong");
        exit;
    }

    $username_input = mysqli_real_escape_string($conn, trim($_POST['username']));

    // 1. Cek apakah username ada di database dan ambil email serta id terkait
    $sql = "SELECT id, email, username FROM admin WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username_input);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user) {
            // Pengguna ditemukan
            $user_id = $user['id'];
            $email_tujuan = $user['email'];
            $nama_pengguna_db = $user['username']; // Untuk sapaan di email

            // Periksa apakah email tujuan valid (tidak kosong dan formatnya benar)
            if (empty($email_tujuan) || !filter_var($email_tujuan, FILTER_VALIDATE_EMAIL)) {
                // Username ada tapi tidak punya email valid terkait
                header("Location: forgot.html?status=username_tidak_terdaftar"); // Pesan ini juga mencakup kasus email tidak valid/kosong
                exit;
            }

            // 2. Generate token reset yang unik dan aman
            try {
                $token = bin2hex(random_bytes(32)); // Menghasilkan token 64 karakter heksadesimal
            } catch (Exception $e) {
                // Jika random_bytes gagal (sangat jarang terjadi)
                error_log("Gagal membuat token acak: " . $e->getMessage());
                header("Location: forgot.html?status=gagal_sistem"); // Pesan error umum
                exit;
            }
            
            $waktu_kedaluwarsa = date("Y-m-d H:i:s", time() + 3600); // Token berlaku 1 jam (3600 detik)

            // 3. Simpan token dan waktu kedaluwarsa ke database
            $sql_update_token = "UPDATE admin SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update_token);

            if ($stmt_update) {
                mysqli_stmt_bind_param($stmt_update, "ssi", $token, $waktu_kedaluwarsa, $user_id);
                if (mysqli_stmt_execute($stmt_update)) {
                    // Token berhasil disimpan

                    // 4. Buat link reset
                    // GANTI 'http://localhost/nama_proyek_anda/' dengan URL basis aplikasi Anda yang sebenarnya!
                    // Pastikan path ke reset_password.php sudah benar.
                    $base_url = "http://localhost/nama_proyek_anda/"; // SESUAIKAN INI!
                    $link_reset = $base_url . "reset_password.php?token=" . $token;

                    // 5. Kirim email
                    $subjek = "Permintaan Reset Password Akun Anda";
                    
                    // Isi email dalam format HTML untuk link yang bisa diklik
                    $isi_email_html = "<p>Halo " . htmlspecialchars($nama_pengguna_db) . ",</p>";
                    $isi_email_html .= "<p>Seseorang (semoga Anda) telah meminta untuk mereset password akun Anda di website kami.</p>";
                    $isi_email_html .= "<p>Jika ini bukan Anda, mohon abaikan email ini. Tidak ada tindakan lebih lanjut yang diperlukan.</p>";
                    $isi_email_html .= "<p>Untuk melanjutkan proses reset password, silakan klik link di bawah ini:</p>";
                    $isi_email_html .= "<p><a href='" . $link_reset . "'>" . $link_reset . "</a></p>";
                    $isi_email_html .= "<p>Link ini akan kedaluwarsa dalam 1 jam.</p>";
                    $isi_email_html .= "<p>Jika Anda tidak bisa mengklik link di atas, silakan salin dan tempel URL tersebut ke browser Anda.</p>";
                    $isi_email_html .= "<br><p>Salam,</p><p>Tim Support Website Anda</p>"; // Ganti "Website Anda"

                    // Headers untuk email HTML
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    // GANTI 'no-reply@domainanda.com' dengan alamat email pengirim yang valid dari domain Anda
                    $headers .= "From: Tim Support Website Anda <no-reply@domainanda.com>" . "\r\n"; 

                    if (mail($email_tujuan, $subjek, $isi_email_html, $headers)) {
                        header("Location: forgot.html?status=sukses_kirim");
                        exit;
                    } else {
                        error_log("Gagal mengirim email reset password ke: " . $email_tujuan);
                        header("Location: forgot.html?status=gagal_kirim");
                        exit;
                    }
                } else {
                    // Gagal menyimpan token
                    error_log("Gagal menyimpan token reset ke DB: " . mysqli_stmt_error($stmt_update));
                    header("Location: forgot.html?status=gagal_sistem");
                    exit;
                }
                mysqli_stmt_close($stmt_update);
            } else {
                // Gagal mempersiapkan statement update token
                error_log("Gagal mempersiapkan statement update token: " . mysqli_error($conn));
                header("Location: forgot.html?status=gagal_sistem");
                exit;
            }
        } else {
            // Username tidak ditemukan atau tidak ada email terkait
            header("Location: forgot.html?status=username_tidak_terdaftar");
            exit;
        }
    } else {
        // Gagal mempersiapkan statement cek username
        error_log("Gagal mempersiapkan statement cek username: " . mysqli_error($conn));
        header("Location: forgot.html?status=gagal_sistem");
        exit;
    }
} else {
    // Jika halaman diakses langsung tanpa metode POST, arahkan ke form forgot
    header("Location: forgot.html");
    exit;
}

mysqli_close($conn); // Tutup koneksi database
?>