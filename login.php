<?php
// FILE: login.php

// Selalu mulai sesi di paling atas
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Aktifkan error reporting untuk development. Matikan di server produksi.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file koneksi database
if (file_exists('config.php')) {
    include_once 'config.php';
} else {
    error_log("CRITICAL: config.php tidak ditemukan di login.php");
    // Tampilkan pesan error user-friendly jika config.php tidak ditemukan
    die("<!DOCTYPE html><html><head><title>Error</title></head><body><div style='text-align:center; margin-top: 50px;'><h1>Kesalahan Sistem</h1><p>File konfigurasi database tidak ditemukan. Mohon hubungi administrator.</p></div></body></html>");
}

// Variabel untuk menyimpan pesan error login
$login_error_message = '';

// Jika admin sudah login (ditandai dengan $_SESSION['admin'] = true), arahkan ke dashboard
if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    header("Location: beranda_admin.php"); // Pastikan ini adalah halaman dashboard admin Anda
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pastikan koneksi database berhasil
    if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
        $login_error_message = "Koneksi database gagal. Mohon coba lagi nanti.";
        error_log("Koneksi database gagal saat proses login: " . ($conn->connect_error ?? 'Tidak diketahui'));
    } else {
        $username_input = trim($_POST['username'] ?? '');
        $password_input = $_POST['password'] ?? '';

        if (empty($username_input) || empty($password_input)) {
            $login_error_message = "Username dan password tidak boleh kosong.";
        } else {
            // Mengambil id, username, dan password hash dari tabel admin
            // Sesuaikan nama tabel ('admin') dan nama kolom ('username', 'password')
            $sql = "SELECT id, username, password FROM admin WHERE username = ?";

            $stmt = mysqli_prepare($conn, $sql);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $username_input);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $admin_data_from_db = mysqli_fetch_assoc($result);

                // Verifikasi password yang dimasukkan dengan hash di database
                if ($admin_data_from_db && password_verify($password_input, $admin_data_from_db['password'])) {
                    // Login berhasil!
                    session_regenerate_id(true); // Ganti ID sesi untuk mencegah Session Fixation

                    // Set variabel sesi yang konsisten dengan index_admin.php/beranda_admin.php
                    $_SESSION['admin'] = true; // Tandai admin sudah login
                    $_SESSION['admin_id'] = $admin_data_from_db['id']; // Simpan ID admin
                    $_SESSION['admin_name'] = $admin_data_from_db['username']; // Contoh: pakai username sebagai nama tampilan
                    // Jika Anda punya kolom nama (misal 'full_name') dan foto di tabel admin:
                    // $_SESSION['admin_name'] = $admin_data_from_db['full_name'];
                    // $_SESSION['admin_photo_url'] = 'uploads/admin_photos/' . $admin_data_from_db['photo_filename']; // Sesuaikan path jika ada


                    // Arahkan ke halaman dashboard admin
                    header("Location: beranda_admin.php"); // Pastikan ini path yang benar
                    exit;

                } else {
                    $login_error_message = "Login gagal! Username atau password salah."; // Pesan error generic
                }
                if($result) mysqli_free_result($result);
                mysqli_stmt_close($stmt);
            } else {
                $login_error_message = "Terjadi kesalahan pada sistem login (prepare SQL gagal).";
                error_log("Gagal menyiapkan statement login: " . mysqli_error($conn) . " | Query: " . htmlspecialchars($sql));
            }
        }
    }
}

// Tutup koneksi database di akhir script
if (isset($conn)) {
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - SMA Ibnu Aqil</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="public/vendor/css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="icon" type="image/jpg" href="public/asset/logo.jpg">
    <style>
        body.bg-gradient-primary {
            /* Pastikan path ke gambar latar belakang ini benar */
            background: url('public/asset/gedunghd.png') no-repeat center center fixed;
            background-size: cover;
        }
        .card-transparent {
            background-color: rgba(255, 255, 255, 0.93); /* Latar belakang card sedikit transparan */
        }
        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .logo-container img {
            max-height: 120px; /* Ukuran logo di card */
            width: auto;
        }
        .input-group-text {
            cursor: pointer; /* Kursor pointer untuk ikon mata */
        }
        /* Penyesuaian responsif untuk kolom */
        @media (min-width: 992px) {
            .col-lg-5 { /* Kolom logo */
                flex: 0 0 auto;
                width: 41.66666667%; /* Mirip col-5 Bootstrap */
            }
            .col-lg-7 { /* Kolom form */
                flex: 0 0 auto;
                width: 58.33333333%; /* Mirip col-7 Bootstrap */
            }
        }
        /* Pusatkan konten vertikal */
        .row.justify-content-center {
            min-height: 100vh;
            align-items: center;
        }
        /* Batasi lebar card utama */
        .card.o-hidden {
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        /* Style untuk link lupa password */
        .forgot-password-link {
            display: block;
            text-align: center;
            margin-top: 1rem; /* Jarak dari elemen di atasnya */
            font-size: 0.9em;
            color: #4e73df; /* Warna default link SB Admin 2 */
        }
        .forgot-password-link:hover {
            color: #224abe;
            text-decoration: none;
        }
        /* Style untuk modal */
        .modal-content { border-radius: 0.3rem; }
        .modal-header { border-bottom: 1px solid #e3e6f0; }
        .modal-footer { border-top: 1px solid #e3e6f0; }
        /* Pastikan input form-control-user di modal punya border-radius yang benar */
        .modal .form-control-user {
            border-radius: 0.35rem; /* Default radius SB Admin 2 */
        }
        /* Jika ada input-group di modal, sesuaikan radius */
        .modal .input-group .form-control-user {
             border-top-left-radius: 0 !important;
             border-bottom-left-radius: 0 !important;
        }
        .modal .input-group-text {
            border-top-right-radius: 0.35rem !important;
            border-bottom-right-radius: 0.35rem !important;
            border-left: 1px solid #d1d3e2; /* Sesuaikan border SB Admin 2 */
        }
        /* Style untuk alert di modal */
        .modal .alert {
            font-size: 0.9em;
            padding: 0.75rem 1rem;
        }

    </style>
</head>
<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-10 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5 card-transparent">
                    <div class="card-body p-0">
                        <div class="row g-0"> 
                            <div class="col-lg-5 d-none d-lg-flex logo-container">
                                <img src="public/asset/logo.jpg" alt="Logo Sekolah">
                            </div>
                            <div class="col-lg-7">
                                <div class="p-5">
                                    <div class="text-center mb-4">
                                        <h1 class="h4 text-gray-900">Login Admin Panel</h1>
                                        <p class="text-muted small">SMA Ibnu Aqil</p>
                                    </div>

                                    <?php if (!empty($login_error_message)): ?>
                                        <div class="alert alert-danger text-center small py-2 alert-dismissible fade show" role="alert">
                                            <?php echo htmlspecialchars($login_error_message); ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" action="login.php" class="user">
                                        <div class="form-group mb-3">
                                            <input type="text" name="username" class="form-control form-control-user" placeholder="Masukkan Username Anda" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                        </div>
                                        <div class="form-group mb-3">
                                            <div class="input-group">
                                                <input type="password" name="password" id="passwordInput" class="form-control form-control-user" placeholder="Masukkan Password Anda" required>
                                                <span class="input-group-text" onclick="togglePasswordVisibility()">
                                                    <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block w-100">
                                            Login
                                        </button>
                                    </form>
                                    <hr>
                                    
                                    <div class="text-center mt-3">
                                        <a class="small" href="index.php">&larr; Kembali ke Halaman Utama Situs</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="forgotPasswordForm" action="" method="POST">
                    
                    <div class="modal-body">
                        <p>Masukkan username atau Nomor Telepon Anda yang terdaftar untuk menerima link reset password.</p>
                        <div class="mb-3">
                            <label for="user_identifier" class="form-label">Username atau Email</label>
                            <input type="text" class="form-control" id="user_identifier" name="user_identifier" required>
                        </div>
                        <div id="forgot-password-notification" class="my-2"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Kirim Link Reset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="public/vendor/jquery/jquery.min.js"></script>
    <script src="public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="public/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="public/vendor/js/sb-admin-2.min.js"></script>

    <script>
        // Fungsi toggle password visibility
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById("passwordInput");
            const toggleIcon = document.getElementById("togglePasswordIcon");

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleIcon.classList.remove("fa-eye");
                toggleIcon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                toggleIcon.classList.remove("fa-eye-slash");
                toggleIcon.classList.add("fa-eye");
            }
        }

        // Optional: Script jika ingin submit form lupa password via AJAX daripada refresh halaman
        // Ini membutuhkan jQuery
        /*
        $(document).ready(function() {
            $("#forgotPasswordForm").submit(function(e) {
                e.preventDefault(); // Cegah submit default

                let form = $(this);
                // PASTIKAN action URL form di atas sudah benar, misal 'send_reset_link.php'
                let url = form.attr('action');

                 if (url === "") {
                      $('#forgot-password-notification').html('<div class="alert alert-danger">URL pemroses lupa password belum ditentukan (action form kosong).</div>');
                      return;
                 }


                // Validasi sederhana
                if ($("#user_identifier").val().trim() === "") {
                    $('#forgot-password-notification').html('<div class="alert alert-warning">Username atau Email tidak boleh kosong.</div>');
                    return;
                }


                // Tampilkan pesan loading
                $('#forgot-password-notification').html('<div class="alert alert-info">Memproses permintaan...</div>');
                const submitButton = form.find('button[type="submit"]');
                submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengirim...');


                $.ajax({
                    type: "POST",
                    url: url, // Gunakan URL dari action form (misal 'send_reset_link.php')
                    data: form.serialize(), // Ambil data form
                    dataType: "json", // Asumsikan respons dari send_reset_link.php adalah JSON
                    success: function(response) {
                        if (response.success) {
                            $('#forgot-password-notification').html('<div class="alert alert-success">' + response.message + '</div>');
                            // Opsional: Tutup modal setelah beberapa detik jika sukses
                            // setTimeout(function() { $('#forgotPasswordModal').modal('hide'); }, 5000);
                        } else {
                            $('#forgot-password-notification').html('<div class="alert alert-danger">' + (response.error || 'Terjadi kesalahan saat memproses permintaan.') + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX error lupa password:", status, error, xhr);
                         let errorMessage = 'Terjadi kesalahan jaringan atau server.';
                         if (xhr.responseJSON && xhr.responseJSON.error) {
                              errorMessage = xhr.responseJSON.error;
                         } else if (xhr.responseText) {
                               // Jika server mengembalikan output non-JSON (misal fatal error PHP)
                               errorMessage = 'Terjadi kesalahan server. Cek log server untuk detail.';
                               console.error("Server response:", xhr.responseText);
                         }
                        $('#forgot-password-notification').html('<div class="alert alert-danger">' + errorMessage + '</div>');
                    },
                    complete: function() {
                         // Aktifkan kembali tombol setelah permintaan selesai
                         submitButton.prop('disabled', false).text('Kirim Link Reset');
                    }
                });
            });
        });
        */
    </script>
</body>
</html>