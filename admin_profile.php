<?php
session_start(); // Pastikan sesi dimulai di paling atas

// --- KONFIGURASI DATABASE ---
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// !!! WAJIB GANTI BAGIAN INI DENGAN DETAIL DATABASE ANDA YANG SEBENARNYA !!!
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
$db_host = 'localhost';                        // Biasanya 'localhost' jika database di server yang sama
$db_name = 'smap9589_sma_ibnu_aqil';         // Nama database Anda (sudah sesuai dari info Anda)
$db_user = 'smap9589';  // <<< GANTI DENGAN USERNAME MYSQL ANDA !!!
$db_pass = 'Xxz82UMAcDYy11'; // <<< GANTI DENGAN PASSWORD MYSQL ANDA !!!
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

// Buat koneksi PDO
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Jika error ini muncul, berarti $db_user atau $db_pass atau $db_host salah,
    // atau user tersebut tidak punya izin ke $db_name.
    die("Koneksi database gagal: " . $e->getMessage() . 
        "<br><br><strong>PASTIKAN ANDA SUDAH MENGGANTI 'USER_MYSQL_ANDA_YANG_SEBENARNYA' DAN 'PASSWORD_MYSQL_ANDA_YANG_SEBENARNYA' DI FILE INI DENGAN KREDENSIAL YANG BENAR.</strong>");
}

// --- ASUMSI: ID Admin yang Sedang Login ---
if (!isset($_SESSION['adminq'])) {
    // Untuk pengembangan, Anda bisa set ID admin secara manual.
    // HAPUS ATAU SESUAIKAN BARIS INI DI LINGKUNGAN PRODUKSI SESUAI SISTEM LOGIN ANDA.
    $_SESSION['adminq'] = 11; // Contoh: admin dengan ID 1.
}
$adminq_login = $_SESSION['adminq'];

// --- Mengambil Data Admin dari Database ---
try {
    // Ambil kolom yang ada, termasuk 'avatar' (PASTIKAN KOLOM AVATAR SUDAH DITAMBAHKAN KE TABEL 'admin')
    $stmt = $pdo->prepare("SELECT id, username, email, avatar FROM admin WHERE id = ?");
    $stmt->execute([$adminq_login]);
    $admin_data = $stmt->fetch();

    if (!$admin_data) {
        die("Data admin dengan ID " . htmlspecialchars($adminq_login) . " tidak ditemukan. Pastikan admin ada di database dan sistem login Anda mengatur 'adminq' dengan benar.");
    }
    $admin_data['nama_lengkap'] = $admin_data['username']; // Menggunakan username sebagai nama tampilan

} catch (PDOException $e) {
    die("Gagal mengambil data admin: " . $e->getMessage());
}

// --- Variabel untuk Pesan ---
$pesan_sukses = '';
$pesan_error = '';
$script_local_storage = '';

// --- Logika Pembaruan Profil ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_baru = trim($_POST['username']);
    $email_baru = trim($_POST['email']);
    $data_berubah = false; // Flag untuk menandai apakah ada perubahan

    // Validasi dasar
    if (empty($username_baru) || empty($email_baru)) {
        $pesan_error = 'Username dan email tidak boleh kosong.';
    } elseif (!filter_var($email_baru, FILTER_VALIDATE_EMAIL)) {
        $pesan_error = 'Format email tidak valid.';
    } else {
        // Cek keunikan email jika diubah
        if ($email_baru !== $admin_data['email']) {
            $stmt_check_email = $pdo->prepare("SELECT id FROM admin WHERE email = ? AND id != ?");
            $stmt_check_email->execute([$email_baru, $adminq_login]);
            if ($stmt_check_email->fetch()) {
                $pesan_error = 'Email ini sudah digunakan oleh akun lain.';
            }
        }

        // Cek keunikan username jika diubah
        if (empty($pesan_error) && $username_baru !== $admin_data['username']) {
            $stmt_check_username = $pdo->prepare("SELECT id FROM admin WHERE username = ? AND id != ?");
            $stmt_check_username->execute([$username_baru, $adminq_login]);
            if ($stmt_check_username->fetch()) {
                $pesan_error = 'Username ini sudah digunakan oleh akun lain.';
            }
        }

        if (empty($pesan_error)) {
            try {
                $pdo->beginTransaction();

                $fields_to_update = [];
                $params_update = [];

                if ($username_baru !== $admin_data['username']) {
                    $fields_to_update[] = "username = ?"; $params_update[] = $username_baru;
                    $data_berubah = true;
                }
                if ($email_baru !== $admin_data['email']) {
                    $fields_to_update[] = "email = ?"; $params_update[] = $email_baru;
                    $data_berubah = true;
                }

                $avatar_path_db = $admin_data['avatar']; 
                $avatar_updated_on_server = false;

                if (isset($_FILES['avatar_baru']) && $_FILES['avatar_baru']['error'] == UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/avatars/'; 
                    if (!is_dir($upload_dir)) {
                        if (!mkdir($upload_dir, 0755, true)) {
                             $pesan_error = 'Gagal membuat direktori upload avatar: ' . $upload_dir;
                        }
                    }
                    
                    if(empty($pesan_error) && !is_writable($upload_dir)) {
                        $absolute_upload_dir = realpath($upload_dir) ?: $upload_dir;
                        $pesan_error = 'Direktori upload avatar (' . $absolute_upload_dir . ') tidak writable.';
                    }
                    
                    if(empty($pesan_error)) {
                        $nama_file_avatar_ext = strtolower(pathinfo(basename($_FILES['avatar_baru']['name']), PATHINFO_EXTENSION));
                        $nama_file_avatar_unik = uniqid('avatar_') . '_' . time() . '.' . $nama_file_avatar_ext;
                        $target_file_avatar = $upload_dir . $nama_file_avatar_unik;
                        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

                        if (!in_array($nama_file_avatar_ext, $allowed_types)) {
                            $pesan_error = 'Hanya file JPG, JPEG, PNG, & GIF yang diizinkan.';
                        } elseif ($_FILES['avatar_baru']['size'] > 2000000) { // Maks 2MB
                            $pesan_error = 'Ukuran file avatar maksimal 2MB.';
                        } elseif (move_uploaded_file($_FILES['avatar_baru']['tmp_name'], $target_file_avatar)) {
                            if ($admin_data['avatar'] && file_exists($admin_data['avatar']) && $admin_data['avatar'] != $target_file_avatar && strpos($admin_data['avatar'], 'placehold.co') === false) {
                                @unlink($admin_data['avatar']);
                            }
                            $avatar_path_db = $target_file_avatar; 
                            $avatar_updated_on_server = true;
                            $data_berubah = true;
                        } else {
                            $pesan_error = 'Gagal mengunggah avatar. Kode error: ' . $_FILES['avatar_baru']['error'];
                        }
                    }
                }
                
                if (empty($pesan_error) && $avatar_updated_on_server) {
                     $fields_to_update[] = "avatar = ?"; 
                     $params_update[] = $avatar_path_db;
                }

                if (empty($pesan_error) && $data_berubah && !empty($fields_to_update)) {
                    $query_update = "UPDATE admin SET " . implode(", ", $fields_to_update) . " WHERE id = ?";
                    $params_update[] = $adminq_login;

                    $stmt_update = $pdo->prepare($query_update);
                    $stmt_update->execute($params_update);
                    
                    $pdo->commit();
                    $pesan_sukses = 'Profil berhasil diperbarui!';

                    $admin_data['username'] = $username_baru;
                    $admin_data['nama_lengkap'] = $username_baru;
                    $admin_data['email'] = $email_baru;
                    $admin_data['avatar'] = $avatar_path_db; 

                    $_SESSION['admin_nama'] = $admin_data['username']; 
                    $_SESSION['admin_avatar'] = $admin_data['avatar']; 
                    
                    $script_local_storage = "
                        <script>
                            localStorage.setItem('adminName', '" . addslashes($admin_data['username']) . "');
                            localStorage.setItem('adminAvatarUrl', '" . addslashes($admin_data['avatar'] ? $admin_data['avatar'] : '') . "');
                        </script>
                    ";
                } elseif (empty($pesan_error) && !$data_berubah) { 
                     $pesan_sukses = 'Tidak ada perubahan data untuk disimpan.';
                     if ($pdo->inTransaction()) { $pdo->rollBack(); } 
                } else { 
                    if (!empty($pesan_error) && $pdo->inTransaction()) { $pdo->rollBack(); } // Rollback jika ada error
                }

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $pesan_error = "Gagal memperbarui profil ke database: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Admin - <?php echo htmlspecialchars($admin_data['nama_lengkap']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .profile-avatar { width: 120px; height: 120px; object-fit: cover; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06); }
        .input-file-button { cursor: pointer; }
        input[type="file"] { display: none; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-4 md:p-8 max-w-3xl">
        <div class="bg-white shadow-xl rounded-lg p-6 md:p-8">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2 text-center">Profil Admin</h1>
            <p class="text-gray-600 text-center mb-6">Kelola informasi profil Anda.</p>

            <?php if ($pesan_sukses): ?>
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md" role="alert">
                    <strong class="font-bold">Sukses!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($pesan_sukses); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($pesan_error): ?>
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($pesan_error); ?></span>
                </div>
            <?php endif; ?>

            <form action="admin_profile.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <div class="flex flex-col items-center space-y-4">
                    <img src="<?php echo htmlspecialchars($admin_data['avatar'] ? $admin_data['avatar'] : 'https://placehold.co/100x100/E2E8F0/A0AEC0?text=A'); ?>" alt="Avatar Admin" class="profile-avatar" id="avatarPreview">
                    <label for="avatar_baru" class="input-file-button px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition duration-150 ease-in-out text-sm">
                        Ganti Gambar Profil
                    </label>
                    <input type="file" name="avatar_baru" id="avatar_baru" accept="image/jpeg,image/png,image/gif" onchange="previewAvatar(event)">
                    <p class="text-xs text-gray-500">Format: JPG, PNG, GIF. Maks: 2MB.</p>
                </div>

                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Nama (Username)</label>
                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($admin_data['username']); ?>" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                
                <div>
                    <button type="submit" class="mt-6 w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
            
            <div class="mt-8 text-center">
                <a href="beranda_admin.php" class="text-indigo-600 hover:text-indigo-800 text-sm">
                    &larr; Kembali ke Dasbor
                </a>
            </div>
        </div>
    </div>

    <script>
        function previewAvatar(event) {
            const input = event.target;
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e){
                    const output = document.getElementById('avatarPreview');
                    output.src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
    <?php 
    if (!empty($script_local_storage)) {
        echo $script_local_storage;
    }
    ?>
</body>
</html>