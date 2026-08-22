<?php
session_start();
require 'config.php'; // koneksi ke database

// Proteksi session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['pendaftaran'])) {
    header("Location: step1.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$data = $_SESSION['pendaftaran'];

// Daftar field yang akan dimasukkan ke tabel
$fields = [
    'name', 'birthplace', 'dob', 'gender', 'religion_child', 'phone', 'address',
    'father_name', 'father_phone', 'father_job', 'father_email', 'father_income', 'father_birthplace', 'father_dob', 'father_religion',
    'mother_name', 'mother_phone', 'mother_job', 'mother_email', 'mother_income', 'mother_birthplace', 'mother_dob', 'mother_religion',
    'guardian_name', 'guardian_phone', 'guardian_relation', 'guardian_birthplace', 'guardian_dob', 'guardian_email', 'guardian_income', 'guardian_religion',
    'goal', 'student_hobby', 'motivation',
    'kk_file', 'ktp_father', 'ktp_mother', 'ktp_guardian', 'kk_guardian', 'akta_lahir', 'nilai_rapor'
];

// Pastikan semua data tersedia
foreach ($fields as $field) {
    if (!isset($data[$field])) {
        if (strpos($field, 'guardian_') === 0) {
            $data[$field] = null;
        } else {
            $data[$field] = '';
        }
    }
}
// Query INSERT
$query = "INSERT INTO students (
    user_id, name, birthplace, dob, gender, religion_child, phone, address,
    father_name, father_phone, father_job, father_email, father_income, father_birthplace, father_dob, father_religion,
    mother_name, mother_phone, mother_job, mother_email, mother_income, mother_birthplace, mother_dob, mother_religion,
    guardian_name, guardian_phone, guardian_relation, guardian_birthplace, guardian_dob, guardian_email, guardian_income, guardian_religion,
    goal, student_hobby, motivation,
    kk_file, ktp_father, ktp_mother, ktp_guardian, kk_guardian, akta_lahir, nilai_rapor
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?
)";

$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Prepare statement error: " . $conn->error);
}

// Bind semua parameter
$stmt->bind_param(
    str_repeat('s', count($fields) + 1), // +1 untuk user_id
    $user_id,
    $data['name'],
    $data['birthplace'],
    $data['dob'],
    $data['gender'],
    $data['religion_child'],
    $data['phone'],
    $data['address'],
    $data['father_name'],
    $data['father_phone'],
    $data['father_job'],
    $data['father_email'],
    $data['father_income'],
    $data['father_birthplace'],
    $data['father_dob'],
    $data['father_religion'],
    $data['mother_name'],
    $data['mother_phone'],
    $data['mother_job'],
    $data['mother_email'],
    $data['mother_income'],
    $data['mother_birthplace'],
    $data['mother_dob'],
    $data['mother_religion'],
    $data['guardian_name'],
    $data['guardian_phone'],
    $data['guardian_relation'],
    $data['guardian_birthplace'],
    $data['guardian_dob'],
    $data['guardian_email'],
    $data['guardian_income'],
    $data['guardian_religion'],
    $data['goal'],
    $data['student_hobby'],
    $data['motivation'],
    $data['kk_file'],
    $data['ktp_father'],
    $data['ktp_mother'],
    $data['ktp_guardian'],
    $data['kk_guardian'],
    $data['akta_lahir'],
    $data['nilai_rapor']
);

// Jalankan query
if ($stmt->execute()) {
    unset($_SESSION['pendaftaran']); // Bersihkan session setelah insert
    header("Location: success.php");
    exit();
} else {
    echo "<h2>Gagal menyimpan data!</h2>";
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
