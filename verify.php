<?php
// FILE: verify.php

session_start();
include 'config.php'; // Pastikan $conn terdefinisi di sini dan sudah terhubung ke database

// Pastikan admin sudah login
if (!isset($_SESSION['admin'])) {
    // Jika ini permintaan AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Kirim respons JSON error jika sesi tidak valid
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode(["success" => false, "error" => "Sesi admin tidak valid. Silakan login kembali."]);
    } else {
        // Arahkan ke halaman login jika bukan permintaan AJAX
        header("Location: login.php"); // Ganti dengan halaman login admin Anda
    }
    exit;
}

$upload_dir = 'uploads/'; // Direktori untuk menyimpan file upload

// Pastikan direktori upload ada dan bisa ditulis
if (!is_dir($upload_dir)) {
    // Coba buat direktori
    if (!mkdir($upload_dir, 0775, true) && !is_dir($upload_dir)) { 
        $error_message = "Kesalahan Server: Gagal membuat direktori upload di '{$upload_dir}'.";
        error_log("CRITICAL: verify.php - " . $error_message);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            if (!headers_sent()) { header('Content-Type: application/json'); }
            echo json_encode(["success" => false, "error" => "Kesalahan server internal (upload dir creation failed)."]);
        } else { die("Terjadi kesalahan pada server dalam membuat direktori. Silakan coba lagi nanti."); } // Pesan lebih umum
        exit;
    }
}

if (!is_writable($upload_dir)) {
    $error_message = "Kesalahan Server: Direktori upload '{$upload_dir}' tidak bisa ditulis.";
    error_log("CRITICAL: verify.php - " . $error_message);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        if (!headers_sent()) { header('Content-Type: application/json'); }
        echo json_encode(["success" => false, "error" => "Kesalahan server internal (upload dir not writable)."]);
    } else { die("Terjadi kesalahan pada server terkait izin direktori. Silakan coba lagi nanti."); } // Pesan lebih umum
    exit;
}

// --- FUNGSI UNTUK MENANGANI UPLOAD FILE ---
function handle_student_file_upload($file_input_name, $current_db_filename, $student_id, $field_identifier, $upload_path, $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf']) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES[$file_input_name]['tmp_name'];
        $file_original_name = $_FILES[$file_input_name]['name'];
        $file_extension = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_extensions)) {
            error_log("DEBUG: Upload Gagal: Tipe file $file_extension untuk $field_identifier tidak diizinkan.");
            return ['success' => false, 'error' => "Tipe file untuk $field_identifier tidak diizinkan ($file_extension). Hanya: " . implode(', ', $allowed_extensions), 'filename' => $current_db_filename];
        }

        if (!empty($current_db_filename) && file_exists($upload_path . $current_db_filename)) {
            if (!unlink($upload_path . $current_db_filename)) {
                error_log("DEBUG: Failed to delete old file: " . $upload_path . $current_db_filename);
            }
        }

        $safe_original_name = preg_replace("/[^a-zA-Z0-9\._-]/", "", pathinfo($file_original_name, PATHINFO_FILENAME));
        $new_filename = "student{$student_id}_{$field_identifier}_" . $safe_original_name . "_" . uniqid() . "." . $file_extension;
        $destination_path = $upload_path . $new_filename;

        if (move_uploaded_file($file_tmp_name, $destination_path)) {
            return ['success' => true, 'filename' => $new_filename];
        } else {
            return ['success' => false, 'error' => "Gagal memindahkan file $field_identifier.", 'filename' => $current_db_filename];
        }
    } elseif (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] != UPLOAD_ERR_NO_FILE) {
        $error_code = $_FILES[$file_input_name]['error'];
        error_log("DEBUG: Upload Error for field $file_input_name: Code $error_code");
        $php_errors = [
            UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas upload_max_filesize.',
            UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas MAX_FILE_SIZE form.',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian.',
            UPLOAD_ERR_NO_TMP_DIR => 'Direktori temporary tidak ada.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
            UPLOAD_ERR_EXTENSION => 'Ekstensi PHP menghentikan upload file.'
        ];
        $error_message = $php_errors[$error_code] ?? 'Error upload tidak diketahui (Code ' . $error_code . ')';
        return ['success' => false, 'error' => "Upload $field_identifier gagal: " . $error_message, 'filename' => $current_db_filename];
    }
    return ['success' => true, 'filename' => $current_db_filename];
}

// --- PROSES PERMINTAAN POST (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!headers_sent()) {
        header('Content-Type: application/json');
    } else {
        error_log("verify.php: Peringatan - Header Content-Type: application/json tidak dapat diatur karena header sudah terkirim sebelumnya.");
    }

    if (!$conn || !($conn instanceof mysqli)) { 
        error_log("verify.php: Database connection invalid or not established on POST request.");
        echo json_encode(['success' => false, 'error' => 'Koneksi database gagal atau tidak valid saat memproses POST.']);
        exit;
    }

    // AKSI: UPDATE STATUS PENDAFTAR
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $student_id = intval($_POST['student_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        error_log("DEBUG (update_status): Menerima request. student_id: $student_id, status: '$status'");

        $allowed_statuses = ['pending', 'verified', 'incomplete', 'rejected'];
        if ($student_id > 0 && !empty($status) && in_array($status, $allowed_statuses)) {
            $query_update_status = "UPDATE students SET status = ? WHERE id = ?";
            $stmt_status = mysqli_prepare($conn, $query_update_status);

            if ($stmt_status) {
                error_log("DEBUG (update_status): mysqli_prepare succeeded untuk ID: $student_id.");
                if (mysqli_stmt_bind_param($stmt_status, 'si', $status, $student_id)) {
                    error_log("DEBUG (update_status): mysqli_stmt_bind_param succeeded untuk ID: $student_id.");
                    if (mysqli_stmt_execute($stmt_status)) {
                        error_log("DEBUG (update_status): mysqli_stmt_execute succeeded untuk ID: $student_id.");
                        $affected_rows = mysqli_stmt_affected_rows($stmt_status);
                        if ($affected_rows > 0) {
                            echo json_encode(["success" => true, "message" => "Status pendaftar (ID: {$student_id}) berhasil diperbarui menjadi '{$status}'.", "new_status_display" => ucfirst($status), "new_status_value" => $status]);
                        } elseif ($affected_rows === 0) {
                            echo json_encode(["success" => true, "message" => "Status pendaftar (ID: {$student_id}) tidak berubah (kemungkinan status sudah '{$status}').", "new_status_display" => ucfirst($status), "new_status_value" => $status]);
                        } else { 
                            $stmt_error = mysqli_stmt_error($stmt_status);
                            error_log("ERROR (update_status): Error pada affected_rows (nilai: $affected_rows) setelah update status untuk ID $student_id: " . $stmt_error);
                            echo json_encode(["success" => false, "error" => "Gagal memverifikasi pembaruan status di database. Detail: " . $stmt_error]);
                        }
                    } else { 
                        $stmt_error = mysqli_stmt_error($stmt_status);
                        error_log("ERROR (update_status): mysqli_stmt_execute failed untuk ID $student_id: " . $stmt_error);
                        echo json_encode(["success" => false, "error" => "Gagal menjalankan update status di database. Detail: " . $stmt_error]);
                    }
                } else { 
                    $bind_error_detail = mysqli_stmt_error($stmt_status);
                    error_log("ERROR (update_status): mysqli_stmt_bind_param failed untuk ID $student_id. Error MySQL: " . $bind_error_detail);
                    if (empty($bind_error_detail)) {
                        error_log("CRITICAL (update_status): mysqli_stmt_bind_param failed untuk ID $student_id, TETAPI mysqli_stmt_error() KOSONG. Periksa log error PHP server utama. Variabel yang coba diikat: status='$status' (tipe: ".gettype($status)."), student_id=$student_id (tipe: ".gettype($student_id).")");
                    }
                    echo json_encode(["success" => false, "error" => "Database error saat update (bind error): " . $bind_error_detail . ". Silakan periksa log server (PHP error log) untuk info DEBUG."]);
                }
                mysqli_stmt_close($stmt_status);
            } else { 
                $conn_error = mysqli_error($conn);
                error_log("ERROR (update_status): mysqli_prepare failed: " . $conn_error);
                echo json_encode(["success" => false, "error" => "Gagal mempersiapkan update status. Detail: " . $conn_error]);
            }
        } else { 
            $error_msg = "Data update status tidak valid.";
            if ($student_id <= 0) $error_msg .= " ID pendaftar ('" . htmlspecialchars($student_id) . "') tidak benar.";
            if (empty($status)) $error_msg .= " Status tidak boleh kosong.";
            elseif (!in_array($status, $allowed_statuses)) $error_msg .= " Status ('" . htmlspecialchars($status) . "') tidak diizinkan.";
            error_log("WARNING (update_status): Validasi gagal - $error_msg");
            echo json_encode(["success" => false, "error" => $error_msg]);
        }
        exit; 
    }

    // AKSI: UPDATE DATA LENGKAP PENDAFTAR DARI MODAL
    elseif (isset($_POST['action']) && $_POST['action'] === 'update_full_data') {
        $student_id = intval($_POST['edit_student_id'] ?? 0);

        if ($student_id <= 0) {
            echo json_encode(["success" => false, "error" => "ID pendaftar untuk update data tidak valid."]);
            exit;
        }

        $name = trim($_POST['edit_name'] ?? '');
        $dob_input = $_POST['edit_dob'] ?? '';
        $dob = (!empty($dob_input) && $dob_input !== '0000-00-00') ? $dob_input : null;
        $phone = trim($_POST['edit_phone'] ?? '');
        $address = trim($_POST['edit_address'] ?? '');
        $modal_status = trim($_POST['edit_modal_status'] ?? 'pending');
        $father_name = trim($_POST['edit_father_name'] ?? '');
        $father_phone = trim($_POST['edit_father_phone'] ?? '');
        $mother_name = trim($_POST['edit_mother_name'] ?? '');
        $mother_phone = trim($_POST['edit_mother_phone'] ?? '');
        $gender = trim($_POST['edit_gender'] ?? '');
        $birthplace = trim($_POST['edit_birthplace'] ?? '');

        $current_akta = trim($_POST['current_edit_akta_lahir'] ?? '');
        $current_kk = trim($_POST['current_edit_kk_file'] ?? '');
        $current_rapor = trim($_POST['current_edit_nilai_rapor'] ?? '');

        $upload_errors = [];
        $uploaded_filenames = [
            'akta_lahir' => $current_akta,
            'kk_file' => $current_kk,
            'nilai_rapor' => $current_rapor
        ];

        $akta_upload_result = handle_student_file_upload('edit_akta_lahir_file', $current_akta, $student_id, 'akta_lahir', $upload_dir);
        if (!$akta_upload_result['success']) { $upload_errors[] = $akta_upload_result['error']; }
        $uploaded_filenames['akta_lahir'] = $akta_upload_result['filename'];

        $kk_upload_result = handle_student_file_upload('edit_kk_file', $current_kk, $student_id, 'kk_file', $upload_dir);
        if (!$kk_upload_result['success']) { $upload_errors[] = $kk_upload_result['error']; }
        $uploaded_filenames['kk_file'] = $kk_upload_result['filename'];

        $rapor_upload_result = handle_student_file_upload('edit_nilai_rapor_file', $current_rapor, $student_id, 'nilai_rapor', $upload_dir);
        if (!$rapor_upload_result['success']) { $upload_errors[] = $rapor_upload_result['error']; }
        $uploaded_filenames['nilai_rapor'] = $rapor_upload_result['filename'];

        if (!empty($upload_errors)) {
            echo json_encode(["success" => false, "error" => "Terjadi kesalahan saat upload file: " . implode("; ", $upload_errors)]);
            exit;
        }

        $sql_full_update = "UPDATE students SET
                                name = ?, phone = ?, address = ?, birthplace = ?, dob = ?, gender = ?, status = ?,
                                father_name = ?, father_phone = ?, mother_name = ?, mother_phone = ?,
                                akta_lahir = ?, kk_file = ?, nilai_rapor = ? 
                              WHERE id = ?";
        $bind_types_string = 'ssssssssssssssi'; 

        $stmt_full_update = mysqli_prepare($conn, $sql_full_update);

        if ($stmt_full_update === false) {
            $conn_error = mysqli_error($conn);
            error_log("ERROR (update_full_data): mysqli_prepare failed: " . $conn_error . " | Query: " . $sql_full_update);
            echo json_encode(["success" => false, "error" => "Gagal mempersiapkan statement update (prepare error): " . $conn_error]);
            exit;
        }
        
        error_log("DEBUG (update_full_data): Akan bind untuk ID: $student_id. Name: '$name', DOB: ".($dob ?? 'NULL').", Akta: '".$uploaded_filenames['akta_lahir']."', Status: '$modal_status'");

        if (mysqli_stmt_bind_param(
            $stmt_full_update,
            $bind_types_string,
            $name, $phone, $address, $birthplace, $dob, $gender, $modal_status,
            $father_name, $father_phone, $mother_name, $mother_phone,
            $uploaded_filenames['akta_lahir'], $uploaded_filenames['kk_file'], $uploaded_filenames['nilai_rapor'],
            $student_id
        )) {
            error_log("DEBUG (update_full_data): mysqli_stmt_bind_param succeeded untuk ID: $student_id.");
            if (mysqli_stmt_execute($stmt_full_update)) {
                error_log("DEBUG (update_full_data): mysqli_stmt_execute succeeded untuk ID: " . $student_id . ". Affected rows: " . mysqli_stmt_affected_rows($stmt_full_update));
                
                $query_updated_row = "SELECT * FROM students WHERE id = ?";
                $stmt_fetch_updated = mysqli_prepare($conn, $query_updated_row);
                $updated_student_data_for_table = null;
                if ($stmt_fetch_updated) {
                    mysqli_stmt_bind_param($stmt_fetch_updated, 'i', $student_id);
                    mysqli_stmt_execute($stmt_fetch_updated);
                    $result_fetch_updated = mysqli_stmt_get_result($stmt_fetch_updated);
                    if ($result_fetch_updated) {
                        $updated_student_data_for_table = mysqli_fetch_assoc($result_fetch_updated);
                        mysqli_free_result($result_fetch_updated);
                    } else { error_log("ERROR (update_full_data): Gagal get_result fetch data terbaru: " . mysqli_stmt_error($stmt_fetch_updated)); }
                    mysqli_stmt_close($stmt_fetch_updated);
                } else { error_log("ERROR (update_full_data): Gagal prepare fetch data terbaru: " . mysqli_error($conn)); }

                echo json_encode([
                    "success" => true,
                    "message" => "Data pendaftar (ID: {$student_id}) berhasil diperbarui." . (mysqli_stmt_affected_rows($stmt_full_update) == 0 ? " (Tidak ada perubahan data terdeteksi)" : ""),
                    "updated_data" => $updated_student_data_for_table
                ]);
            } else { 
                $db_error = mysqli_stmt_error($stmt_full_update);
                error_log("ERROR (update_full_data): mysqli_stmt_execute failed: " . $db_error . " untuk ID: " . $student_id);
                echo json_encode(["success" => false, "error" => "Database error saat update (execute error): " . $db_error]);
            }
        } else { 
            $bind_error = mysqli_stmt_error($stmt_full_update);
            error_log("ERROR (update_full_data): mysqli_stmt_bind_param failed: " . $bind_error . " untuk ID: " . $student_id);
            if (empty($bind_error)) {
                $vars_to_log_critical = [
                    'student_id_for_bind' => $student_id,
                    'name' => ['value' => $name, 'type' => gettype($name)],
                    'phone' => ['value' => $phone, 'type' => gettype($phone)],
                    'address' => ['value' => $address, 'type' => gettype($address)],
                    'birthplace' => ['value' => $birthplace, 'type' => gettype($birthplace)],
                    'dob' => ['value' => $dob, 'type' => gettype($dob)],
                    'gender' => ['value' => $gender, 'type' => gettype($gender)],
                    'modal_status' => ['value' => $modal_status, 'type' => gettype($modal_status)],
                    'father_name' => ['value' => $father_name, 'type' => gettype($father_name)],
                    'father_phone' => ['value' => $father_phone, 'type' => gettype($father_phone)],
                    'mother_name' => ['value' => $mother_name, 'type' => gettype($mother_name)],
                    'mother_phone' => ['value' => $mother_phone, 'type' => gettype($mother_phone)],
                    'akta_lahir_file' => ['value' => $uploaded_filenames['akta_lahir'], 'type' => gettype($uploaded_filenames['akta_lahir'])],
                    'kk_file' => ['value' => $uploaded_filenames['kk_file'], 'type' => gettype($uploaded_filenames['kk_file'])],
                    'nilai_rapor_file' => ['value' => $uploaded_filenames['nilai_rapor'], 'type' => gettype($uploaded_filenames['nilai_rapor'])]
                ];
                error_log("CRITICAL (update_full_data): mysqli_stmt_bind_param failed untuk ID $student_id, TETAPI mysqli_stmt_error() KOSONG. Periksa log error PHP server utama. Variabel yang coba diikat: " . json_encode($vars_to_log_critical, JSON_PRETTY_PRINT));
            }
            echo json_encode(["success" => false, "error" => "Database error saat update (bind error): " . $bind_error . ". Silakan periksa log server (PHP error log) untuk info DEBUG."]);
        }
        mysqli_stmt_close($stmt_full_update);
    } else {
        echo json_encode(["success" => false, "error" => "Aksi POST tidak dikenali."]);
    }

    if (isset($conn) && ($conn instanceof mysqli)) { 
        mysqli_close($conn);
    }
    exit; 
}

// --- PROSES REQUEST GET (Untuk menampilkan halaman awal tabel) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($conn) || !($conn instanceof mysqli) || @mysqli_ping($conn) === false) {
        error_log("verify.php (GET): Koneksi awal tidak valid atau ping gagal. Mencoba include ulang config.php.");
        unset($conn); 
        include 'config.php'; 
        
        if (!isset($conn) || !($conn instanceof mysqli)) { 
            error_log("verify.php (GET): Koneksi database tidak tersedia setelah include ulang config.php.");
            if (!headers_sent() && (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
                 if(!headers_sent()) { header('Content-Type: application/json'); }
                 echo json_encode(['success' => false, 'error' => 'Kesalahan server: Koneksi database tidak tersedia.']);
            } else {
                if(!headers_sent()) {
                    header('Content-Type: text/html; charset=utf-8');
                }
                echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><title>Error Koneksi</title></head><body>";
                echo "<div style='padding: 20px; margin: 20px; border: 1px solid #ff0000; background-color: #ffeeee; font-family: Arial, sans-serif;'>";
                echo "<h1>Kesalahan Koneksi Database</h1>";
                echo "<p>Tidak dapat terhubung ke database saat ini. Silakan coba lagi nanti atau hubungi administrator.</p>";
                echo "<p><strong>PENTING:</strong> Pastikan pengaturan di file 'config.php' (host, user, password, nama database) sudah benar dan server database Anda berjalan.</p>";
                echo "</div></body></html>";
            }
            exit; 
        }
        error_log("verify.php (GET): Koneksi berhasil dibangun/diverifikasi setelah include ulang config.php.");
    }
}

$query_all_students = "SELECT * FROM students ORDER BY created_at DESC";
$result_all_students = null; 
if (isset($conn) && ($conn instanceof mysqli)) { 
    $result_all_students = mysqli_query($conn, $query_all_students);
    if (!$result_all_students) {
        $fetch_error_message = "Terjadi kesalahan saat mengambil data pendaftar. Detail: " . mysqli_error($conn); // Tampilkan error MySQL
        error_log("verify.php: Error fetching all students for display: " . mysqli_error($conn));
    }
} else {
    $fetch_error_message = "Koneksi database tidak tersedia untuk mengambil data pendaftar.";
    error_log("verify.php: Koneksi database tidak valid sebelum query all students.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pendaftar - SMA Ibnu Aqil</title>
    <link rel="icon" type="image/jpg" href="public/asset/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .status-badge { padding: 0.3em 0.6em; border-radius: 0.25rem; font-size: 0.85em; }
        .badge-pending { background-color: #ffc107; color: #000; }
        .badge-verified { background-color: #198754; color: #fff; }
        .badge-incomplete { background-color: #fd7e14; color: #fff; }
        .badge-rejected { background-color: #dc3545; color: #fff; }
        .action-buttons button, .action-buttons .btn { margin-left: 5px; }
        td.action-buttons { display: flex; flex-wrap: wrap; gap: 5px; align-items: center; padding-top: .75rem; padding-bottom: .75rem; }
        td.action-buttons .btn { margin: 0; }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white"><h2>Manajemen Data Pendaftar</h2></div>
        <div class="card-body">
            <a href="beranda_admin.php" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left-circle"></i> Kembali ke Dashboard Utama</a>
            <div id="global-notification" class="my-2"></div>
            <?php if (isset($fetch_error_message)): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($fetch_error_message) ?></div>
            <?php elseif (!$result_all_students && isset($conn) && ($conn instanceof mysqli) && mysqli_error($conn)): ?>
                <div class="alert alert-danger" role="alert">Terjadi kesalahan saat mengambil data pendaftar: <?= htmlspecialchars(mysqli_error($conn)) ?></div>
            <?php elseif ($result_all_students && mysqli_num_rows($result_all_students) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th><th>Nama</th><th>Status Saat Ini</th><th style="min-width: 250px;">Ubah Status</th><th style="min-width: 200px;">Aksi Lain</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result_all_students)): ?>
                            <?php $rowDataJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>
                            <tr id="row-student-<?= $row['id'] ?>">
                                <td><?= $no++; ?></td>
                                <td class="student-name"><?= htmlspecialchars($row['name'] ?? 'N/A'); ?></td>
                                <td><span class="status-badge badge-<?= htmlspecialchars(strtolower($row['status'] ?? 'pending')) ?>" id="status-text-<?= $row['id'] ?>"><?= ucfirst(htmlspecialchars($row['status'] ?? 'Pending')); ?></span></td>
                                <td>
                                    <form class="update-status-form d-flex align-items-center" data-id="<?= $row['id'] ?>">
                                        <select name="status" class="form-select form-select-sm status-dropdown me-2" data-original-value="<?= htmlspecialchars($row['status'] ?? 'pending') ?>">
                                            <option value="pending" <?= (($row['status'] ?? 'pending') == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="verified" <?= (($row['status'] ?? 'pending') == 'verified') ? 'selected' : ''; ?>>Terverifikasi ✅</option>
                                            <option value="incomplete" <?= (($row['status'] ?? 'pending') == 'incomplete') ? 'selected' : ''; ?>>Data Tidak Lengkap ⚠️</option>
                                            <option value="rejected" <?= (($row['status'] ?? 'pending') == 'rejected') ? 'selected' : ''; ?>>Ditolak ❌</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="action-buttons">
                                    <button type="button" class="btn btn-sm btn-info btn-edit-full" data-bs-toggle="modal" data-bs-target="#editStudentModal" data-student='<?= $rowDataJson ?>' title="Edit Detail Pendaftar"><i class="bi bi-pencil-square"></i> Edit Detail</button>
                                    <a href="hapus_pendaftar.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus pendaftar <?= htmlspecialchars($row['name'] ?? '') ?>?')"><i class="bi bi-trash"></i> Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif($result_all_students && mysqli_num_rows($result_all_students) == 0): ?>
                 <tr><td colspan="5" class="text-center">Belum ada data pendaftar.</td></tr>
            <?php else: ?>
                <div class="alert alert-warning text-center">Tidak dapat menampilkan data pendaftar saat ini.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editStudentForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_full_data">
                <input type="hidden" id="edit_student_id" name="edit_student_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStudentModalLabel">Edit Data Pendaftar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modal-notification" class="my-2"></div>
                    <fieldset class="mb-3 border p-3"><legend class="float-none w-auto px-1">Data Siswa</legend><div class="row"><div class="col-md-6 mb-2"><label for="edit_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label><input type="text" class="form-control form-control-sm" id="edit_name" name="edit_name" required></div><div class="col-md-6 mb-2"><label for="edit_phone" class="form-label">Telepon</label><input type="text" class="form-control form-control-sm" id="edit_phone" name="edit_phone"></div><div class="col-md-6 mb-2"><label for="edit_gender" class="form-label">Jenis Kelamin</label><select class="form-select form-select-sm" id="edit_gender" name="edit_gender"><option value="">- Pilih -</option><option value="Laki-laki">Laki-laki</option><option value="Perempuan">Perempuan</option></select></div><div class="col-md-6 mb-2"><label for="edit_birthplace" class="form-label">Tempat Lahir</label><input type="text" class="form-control form-control-sm" id="edit_birthplace" name="edit_birthplace"></div><div class="col-md-6 mb-2"><label for="edit_dob" class="form-label">Tanggal Lahir</label><input type="date" class="form-control form-control-sm" id="edit_dob" name="edit_dob"></div><div class="col-md-12 mb-2"><label for="edit_address" class="form-label">Alamat</label><textarea class="form-control form-control-sm" id="edit_address" name="edit_address" rows="2"></textarea></div></div></fieldset>
                    <fieldset class="mb-3 border p-3"><legend class="float-none w-auto px-1">Data Orang Tua</legend><div class="row"><div class="col-md-6 mb-2"><label for="edit_father_name" class="form-label">Nama Ayah</label><input type="text" class="form-control form-control-sm" id="edit_father_name" name="edit_father_name"></div><div class="col-md-6 mb-2"><label for="edit_father_phone" class="form-label">Telepon Ayah</label><input type="text" class="form-control form-control-sm" id="edit_father_phone" name="edit_father_phone"></div></div><div class="row"><div class="col-md-6 mb-2"><label for="edit_mother_name" class="form-label">Nama Ibu</label><input type="text" class="form-control form-control-sm" id="edit_mother_name" name="edit_mother_name"></div><div class="col-md-6 mb-2"><label for="edit_mother_phone" class="form-label">Telepon Ibu</label><input type="text" class="form-control form-control-sm" id="edit_mother_phone" name="edit_mother_phone"></div></div></fieldset>
                    <fieldset class="mb-3 border p-3"><legend class="float-none w-auto px-1">Berkas Utama</legend><input type="hidden" id="current_edit_akta_lahir" name="current_edit_akta_lahir"><div class="mb-2"><label for="edit_akta_lahir_file" class="form-label">Ganti Akta Lahir (pdf, jpg, png)</label><input type="file" class="form-control form-control-sm" id="edit_akta_lahir_file" name="edit_akta_lahir_file" accept=".pdf,.jpg,.jpeg,.png"><small>File saat ini: <span id="current_akta_filename_display">Belum ada</span> <a href="#" id="view_current_akta" target="_blank" style="display:none;">Lihat</a></small></div><input type="hidden" id="current_edit_kk_file" name="current_edit_kk_file"><div class="mb-2"><label for="edit_kk_file" class="form-label">Ganti Kartu Keluarga (pdf, jpg, png)</label><input type="file" class="form-control form-control-sm" id="edit_kk_file" name="edit_kk_file" accept=".pdf,.jpg,.jpeg,.png"><small>File saat ini: <span id="current_kk_filename_display">Belum ada</span> <a href="#" id="view_current_kk" target="_blank" style="display:none;">Lihat</a></small></div><input type="hidden" id="current_edit_nilai_rapor" name="current_edit_nilai_rapor"><div class="mb-2"><label for="edit_nilai_rapor_file" class="form-label">Ganti Nilai Rapor (pdf, jpg, png)</label><input type="file" class="form-control form-control-sm" id="edit_nilai_rapor_file" name="edit_nilai_rapor_file" accept=".pdf,.jpg,.jpeg,.png"><small>File saat ini: <span id="current_nilai_rapor_filename_display">Belum ada</span> <a href="#" id="view_current_nilai_rapor" target="_blank" style="display:none;">Lihat</a></small></div></fieldset>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan Perubahan</button></div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    const UPLOAD_PATH = '<?= $upload_dir ?>'; 

    function showGlobalNotification(message, type = 'info') {
        const randomId = 'notif-' + Math.random().toString(36).substr(2, 9);
        $('#global-notification').html(
            `<div class="alert alert-${type} alert-dismissible fade show" role="alert" id="${randomId}">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`
        );
        setTimeout(function() {
            $('#' + randomId).alert('close');
        }, 7000);
    }

    function showModalNotification(message, type = 'info') {
        $('#modal-notification').html(
            `<div class="alert alert-${type}" role="alert">
                ${message}
            </div>`
        );
    }

    $('.card-body').on('change', '.status-dropdown', function() {
        let studentId = $(this).closest("form").data("id");
        let newStatus = $(this).val();
        let originalValue = $(this).data('original-value');
        let dropdownElement = $(this);

        if (!confirm(`Anda yakin ingin mengubah status pendaftar ini menjadi "${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}"?`)) {
            dropdownElement.val(originalValue);
            return;
        }

        $.ajax({
            url: window.location.pathname,
            type: "POST",
            data: { action: "update_status", student_id: studentId, status: newStatus },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#status-text-" + studentId)
                        .text(response.new_status_display)
                        .removeClass()
                        .addClass("status-badge badge-" + response.new_status_value.toLowerCase());
                    dropdownElement.data('original-value', newStatus);
                    showGlobalNotification(response.message, 'success');
                } else {
                    showGlobalNotification('Update status gagal: ' + (response.error || 'Error tidak diketahui.'), 'danger');
                    dropdownElement.val(originalValue);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error update status:", status, error, xhr);
                showGlobalNotification('Terjadi kesalahan saat update status: ' + (xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : error), 'danger');
                dropdownElement.val(originalValue);
            }
        });
    });

    $('.card-body').on('click', '.btn-edit-full', function() {
        const studentDataString = $(this).attr('data-student');
        if (!studentDataString) {
            console.error("Data siswa tidak ditemukan pada tombol edit.");
            showGlobalNotification("Gagal memuat data siswa untuk diedit: Data tidak lengkap.", "danger");
            return;
        }
        try {
            var studentData = JSON.parse(studentDataString);
        } catch(e) {
            console.error("Gagal parse data siswa JSON:", e, studentDataString);
            showGlobalNotification("Gagal memuat data siswa untuk diedit: Format data salah.", "danger");
            return;
        }

        const modal = $('#editStudentModal');
        $('#modal-notification').html('');
        $('#editStudentForm')[0].reset();

        modal.find('#edit_student_id').val(studentData.id || '');
        modal.find('#edit_name').val(studentData.name || '');
        modal.find('#edit_phone').val(studentData.phone || '');
        modal.find('#edit_address').val(studentData.address || '');
        modal.find('#edit_birthplace').val(studentData.birthplace || '');
        modal.find('#edit_dob').val(studentData.dob || '');
        modal.find('#edit_gender').val(studentData.gender || '');
        modal.find('#edit_modal_status').val(studentData.status || 'pending');
        modal.find('#edit_father_name').val(studentData.father_name || '');
        modal.find('#edit_father_phone').val(studentData.father_phone || '');
        modal.find('#edit_mother_name').val(studentData.mother_name || '');
        modal.find('#edit_mother_phone').val(studentData.mother_phone || '');
        
        function setFileInputModal(currentVal, currentIdHidden, displayId, viewId) {
            modal.find(currentIdHidden).val(currentVal || '');
            if (currentVal && currentVal !== "null" && currentVal !== "") {
                const fileName = currentVal.split('/').pop().split('\\').pop();
                modal.find(displayId).text(fileName);
                modal.find(viewId).attr('href', UPLOAD_PATH + currentVal).show();
            } else {
                modal.find(displayId).text('Belum ada');
                modal.find(viewId).attr('href', '#').hide();
            }
        }

        setFileInputModal(studentData.akta_lahir, '#current_edit_akta_lahir', '#current_akta_filename_display', '#view_current_akta');
        setFileInputModal(studentData.kk_file, '#current_edit_kk_file', '#current_kk_filename_display', '#view_current_kk');
        setFileInputModal(studentData.nilai_rapor, '#current_edit_nilai_rapor', '#current_nilai_rapor_filename_display', '#view_current_nilai_rapor');
    });

    $("#editStudentForm").submit(function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        let studentId = $("#edit_student_id").val();

        showModalNotification('Menyimpan data...', 'info');
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');

        $.ajax({
            url: window.location.pathname,
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $('#editStudentModal').modal('hide');
                    showGlobalNotification(response.message, 'success');
                    if(response.updated_data) {
                        const updatedData = response.updated_data;
                        const row = $("#row-student-" + studentId);
                        row.find(".student-name").text(updatedData.name || 'N/A');
                        $("#status-text-" + studentId)
                            .text(updatedData.status ? updatedData.status.charAt(0).toUpperCase() + updatedData.status.slice(1) : 'N/A')
                            .removeClass()
                            .addClass("status-badge badge-" + (updatedData.status ? updatedData.status.toLowerCase() : 'secondary'));
                        const statusDropdownInRow = row.find(".status-dropdown");
                        statusDropdownInRow.val(updatedData.status || 'pending');
                        statusDropdownInRow.data('original-value', updatedData.status || 'pending');
                        const newRowDataJson = JSON.stringify(updatedData);
                        row.find(".btn-edit-full").attr('data-student', newRowDataJson);
                    }
                } else {
                    showModalNotification('Gagal menyimpan data: ' + (response.error || 'Error tidak diketahui.'), 'danger');
                    console.error("Server returned error:", response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error full update:", status, error, xhr);
                let errorMessage = 'Terjadi kesalahan AJAX: ' + error;
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = 'Gagal menyimpan data: ' + xhr.responseJSON.error;
                } else if (xhr.responseText) {
                     try {
                        const errResponse = JSON.parse(xhr.responseText);
                        if (errResponse && errResponse.error) {
                             errorMessage = 'Gagal menyimpan data: ' + errResponse.error;
                        } else {
                            errorMessage = 'Terjadi kesalahan Server. Cek log server untuk detail. Respons tidak terduga.';
                        }
                    } catch (e) {
                         errorMessage = 'Terjadi kesalahan Server. Cek log server untuk detail. Respons: ' + xhr.responseText.substring(0, 200) + '...';
                    }
                }
                showModalNotification(errorMessage, 'danger');
            },
            complete: function() {
                submitButton.prop('disabled', false).html('Simpan Perubahan');
            }
        });
    });
});
</script>
<?php
// Penutupan koneksi mysqli_close($conn) di akhir skrip secara keseluruhan jika $conn masih ada.
// if (isset($conn) && is_object($conn) && method_exists($conn, 'close')) {
//    mysqli_close($conn);
// }
?>
```
