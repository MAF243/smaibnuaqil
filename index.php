<?php
// FILE: index.php (BAGIAN PALING ATAS)

// 1. Mulai Sesi (opsional, uncomment jika Anda menggunakan session di halaman ini)
// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }

// 2. Pengaturan Error Reporting untuk Development (opsional, tapi baik untuk debug)
// Anda bisa mengaktifkan ini jika masih mencari error, dan matikan di server produksi.
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// 3. Sertakan File Konfigurasi Database
if (file_exists('config.php')) {
    include_once 'config.php'; // Variabel $conn akan dibuat di sini
} else {
    // Tampilkan error jika config.php tidak ditemukan dan hentikan eksekusi
    error_log("CRITICAL: config.php tidak ditemukan di index.php");
    die("<!DOCTYPE html><html><head><title>Error Konfigurasi</title></head><body><div style='text-align:center; margin-top: 50px;'><h1>Error Website</h1><p>File konfigurasi database (config.php) tidak ditemukan. Mohon hubungi administrator.</p></div></body></html>");
}


// 4. Pastikan Koneksi Database $conn Berhasil
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $error_message_conn = (isset($conn) && $conn instanceof mysqli) ? $conn->connect_error : 'Koneksi database tidak terdefinisi atau bukan objek mysqli.';
    error_log("CRITICAL: Koneksi database gagal di index.php: " . $error_message_conn);
    die("<!DOCTYPE html><html><head><title>Error Koneksi Database</title></head><body><div style='text-align:center; margin-top: 50px;'><h1>Error Koneksi Database</h1><p>Gagal terhubung ke database.</p><p>Detail Error: " . htmlspecialchars($error_message_conn) . "</p><p>Mohon hubungi administrator.</p></div></body></html>");
}


// --- BAGIAN PENGAMBILAN DATA DINAMIS ---

// 5. Ambil Data Berita Terbaru
$news_items = [];
$news_fetch_error = null;
// Jika tabel 'news' Anda memiliki kolom 'is_published' dan Anda ingin menggunakannya, tambahkan "WHERE is_published = 1"
$news_query = "SELECT id, title, content, image_path, created_at FROM news ORDER BY created_at DESC LIMIT 3";
$result_news = mysqli_query($conn, $news_query);
if ($result_news === false) {
    $news_fetch_error = "Gagal mengambil data berita: " . htmlspecialchars(mysqli_error($conn));
    error_log("Error fetching news: " . mysqli_error($conn) . " | Query: " . $news_query);
} else {
    if (mysqli_num_rows($result_news) > 0) {
        while ($row_news = mysqli_fetch_assoc($result_news)) {
            $news_items[] = $row_news;
        }
    }
    mysqli_free_result($result_news);
}

// 6. Ambil Data Galeri Foto Terbaru
$gallery_items = [];
$gallery_fetch_error = null;
// Jika tabel 'gallery' Anda memiliki kolom 'is_published' dan Anda ingin menggunakannya, tambahkan "WHERE is_published = 1"
$gallery_query = "SELECT id, image_path, title FROM gallery ORDER BY uploaded_at DESC LIMIT 8";
$result_gallery = mysqli_query($conn, $gallery_query);
if ($result_gallery === false) {
    $gallery_fetch_error = "Gagal mengambil data galeri: " . htmlspecialchars(mysqli_error($conn));
    error_log("Error fetching gallery: " . mysqli_error($conn) . " | Query: " . $gallery_query);
} else {
    if (mysqli_num_rows($result_gallery) > 0) {
        while ($row_gallery = mysqli_fetch_assoc($result_gallery)) {
            $gallery_items[] = $row_gallery;
        }
    }
    mysqli_free_result($result_gallery);
}

// 7. Ambil Data Fasilitas
$facilities_list = [];
$facilities_fetch_error = null; 
$sql_facilities = "SELECT id, name, short_description, icon_class, facility_key, modal_title, modal_description, modal_image_path 
                   FROM facilities
                   WHERE is_published = 1
                   ORDER BY display_order ASC, id ASC";
$result_facilities = mysqli_query($conn, $sql_facilities);

// DEBUG OUTPUT UNTUK FASILITAS (INI AKAN MUNCUL DI ATAS HALAMAN ANDA)
if ($result_facilities === false) {
    $facilities_fetch_error = "Error pada SQL query fasilitas: " . htmlspecialchars(mysqli_error($conn)); // Simpan juga errornya
    error_log($facilities_fetch_error . " | Query: " . htmlspecialchars($sql_facilities));
} else {
    $num_facility_rows = mysqli_num_rows($result_facilities);
    if ($num_facility_rows === 0) {
    } else {
        while ($row_facility = mysqli_fetch_assoc($result_facilities)) {
            $facilities_list[] = $row_facility;
        }
        // Jika Anda ingin melihat isi array $facilities_list untuk memastikan data kolomnya benar:
        // echo "Isi \$facilities_list (data pertama jika ada): <pre>"; 
        // if (!empty($facilities_list)) { print_r($facilities_list[0]); } else { echo "Kosong"; }
        // echo "</pre>";
    }
    mysqli_free_result($result_facilities);
}
echo "</div>"; // Akhir div debug fasilitas

// Jangan tutup koneksi $conn di sini jika masih ada bagian HTML di bawahnya
// yang mungkin secara tidak sengaja (atau di masa depan) membutuhkan koneksi.
// PHP akan otomatis menutupnya di akhir skrip.
// mysqli_close($conn); 

?>
<!DOCTYPE html>
<html lang="id">
<head>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SMA Ibnu'Aqil - Serba Bisa, Pasti Bisa SUKSES!</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Selamat datang di SMA Ibnu'Aqil. Sekolah Islam Unggulan dengan berbagai fasilitas modern, program ekstrakurikuler beragam, dan berita terbaru. Daftar sekarang!">
    <meta name="keywords" content="SMA Ibnu Aqil, Pendaftaran SMA, Sekolah Islam, PPDB Online, SMA Unggulan, Serang, Banten, Ekstrakurikuler, Galeri, Berita Sekolah">
    <link rel="icon" type="image/jpg" href="public/asset/logo.jpg">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        html
        {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Arial', sans-serif;
            position: relative;
        }

        /* === PERBAIKAN UNTUK NAVBAR MENUTUPI KONTEN === */
        /* Terapkan scroll-margin-top ke semua elemen yang bisa menjadi target scroll */
        header[id], section[id] {
            scroll-margin-top: 80px; /* Sesuaikan nilai ini dengan tinggi navbar Anda + sedikit padding jika perlu */
        }
        /* === AKHIR PERBAIKAN === */

        .hero-section img {
            width: 100%;
            height: auto;
            object-fit: cover;
            max-height: 550px;
        }
        .hero-section {
            position: relative;
            text-align: center;
            color: white;
        }
        .hero-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.65);
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 450px;
        }
        .hero-text h1 {
            font-size: 2.2rem;
            margin-bottom: 0.75rem;
        }
        .hero-text p {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }

        /* Penyesuaian font hero untuk layar sangat kecil */
        @media (max-width: 576px) {
            .hero-text h1 {
                font-size: 1.8rem;
            }
            .hero-text p {
                font-size: 1rem;
            }
            .hero-text {
                padding: 20px;
            }
        }

        .navbar-custom {
            background-color: #156c26;
            padding-top: 0.8rem;
            padding-bottom: 0.8rem;
            transition: background-color 0.3s ease;
        }
        .navbar-custom .navbar-brand img {
            transition: transform 0.3s ease;
        }
        .navbar-custom .navbar-brand:hover img {
            transform: scale(1.1);
        }

        .navbar-custom .nav-link,
        .navbar-custom .dropdown-toggle {
            color: rgba(255, 255, 255, 0.85) !important;
            margin-left: 0.6rem;
            margin-right: 0.6rem;
            padding: 0.7rem 0.5rem;
            position: relative;
            text-decoration: none;
            transition: color 0.3s ease, background-color 0.2s ease;
            font-weight: 500;
            border-radius: 4px;
            border: none;
            background: none;
        }

        .navbar-custom .nav-link::before,
        .navbar-custom .dropdown-toggle::before {
            content: '';
            position: absolute;
            width: 0;
            height: 2.5px;
            bottom: 2px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #f0ad4e;
            visibility: hidden;
            transition: all 0.3s ease-in-out;
        }

        .navbar-custom .nav-link:hover::before,
        .navbar-custom .dropdown-toggle:hover::before,
        .navbar-custom .nav-link.active::before {
            width: 75%;
            visibility: visible;
        }

        .navbar-custom .nav-link:hover,
        .navbar-custom .nav-link.active,
        .navbar-custom .dropdown-toggle:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .navbar-custom .dropdown-toggle .bi-chevron-down {
            transition: transform 0.3s ease-in-out;
            font-size: 0.8em;
            margin-left: 0.25rem;
        }
        .navbar-custom .dropdown-toggle[aria-expanded="true"] .bi-chevron-down {
            transform: rotate(180deg);
        }

        .navbar-custom .dropdown-menu {
            background-color: #125a20;
            border: 1px solid rgba(255,255,255,0.1);
            border-top: none;
            border-radius: 0 0 6px 6px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
            margin-top: 0.25rem;
             /* Styling for dropdown animation */
            opacity: 0;
            transform: translateY(10px) scale(0.98);
            visibility: hidden;
            display: block; /* Important for transition to work */
            transition: opacity 0.25s ease-out, transform 0.25s ease-out, visibility 0.25s ease-out;
            will-change: opacity, transform;
        }

        .navbar-custom .dropdown-menu.show {
            opacity: 1;
            transform: translateY(0) scale(1);
            visibility: visible;
        }


        .navbar-custom .dropdown-item {
            color: rgba(255, 255, 255, 0.9);
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            position: relative;
            transition: color 0.25s ease, background-color 0.25s ease, padding-left 0.25s ease;
        }

        .navbar-custom .dropdown-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 4px;
            height: 100%;
            background-color: #f0ad4e;
            transform: scaleY(0);
            transition: transform 0.25s ease-in-out;
            transform-origin: center;
        }

        .navbar-custom .dropdown-item:hover::before,
        .navbar-custom .dropdown-item:focus::before,
        .navbar-custom .dropdown-item.active::before {
            transform: scaleY(1);
        }

        .navbar-custom .dropdown-item:hover,
        .navbar-custom .dropdown-item:focus,
        .navbar-custom .dropdown-item.active {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.1);
            padding-left: calc(1.2rem + 6px);
        }
        .navbar-custom .dropdown-divider {
            border-top: 1px solid rgba(255,255,255,0.15);
            margin: 0.3rem 0;
        }


        .btn-custom-primary {
            background-color: #0056b3;
            color: white;
            border: none;
            padding: 12px 25px;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-custom-primary:hover {
            background-color: #004085;
            color: white;
            transform: translateY(-2px);
        }
         .btn-custom-secondary {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 25px;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-custom-secondary:hover {
            background-color: #1e7e34;
            color: white;
            transform: translateY(-2px);
        }
        .section-title {
            margin-bottom: 40px;
            font-weight: bold;
            color: #333;
            text-align: center;
        }
        .section-padding {
            padding: 60px 0;
        }
        #fasilitas, #ekstrakurikuler, #galeri, #berita, #testimoni, #faq {
            background-color: #f8f9fa; /* Warna latar belakang abu-abu muda untuk beberapa section */
        }
         #berita { /* Berita tetap putih sesuai desain sebelumnya */
            background-color: #ffffff;
        }
        #kontak { /* Kontak juga bisa putih atau f8f9fa sesuai desain */
             background-color: #ffffff;
        }

        /* Styling untuk card berita */
        .card-news {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-news:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .card-news .card-img-top {
            height: 200px; /* Tinggi tetap untuk gambar berita */
            object-fit: cover; /* Agar gambar tidak distretch */
        }
        .card-news .card-body {
            min-height: 150px; /* Minimum tinggi body card */
        }
         .card-news .card-text {
             min-height: 70px; /* Disesuaikan agar seragam */
             overflow: hidden; /* Pastikan teks terpotong jika melebihi min-height */
             text-overflow: ellipsis; /* Tambahkan elipsis jika teks terpotong */
             display: -webkit-box; /* For webkit browsers */
             -webkit-line-clamp: 4; /* Batasi hingga 4 baris */
             -webkit-box-orient: vertical;
         }


        .card-ekskul, .card-testimoni {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-ekskul:hover, .card-testimoni:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }


        .gallery-item img {
            cursor: pointer;
            transition: transform 0.3s ease, opacity 0.3s ease;
            border: 3px solid transparent;
            width: 100%; /* Ensure image takes full column width */
            height: 150px; /* Example fixed height for gallery thumbnails */
            object-fit: cover; /* Crop image to fit */
        }
        .gallery-item img:hover {
            transform: scale(1.05);
            opacity: 0.85;
            border-color: #156c26;
        }
        #fasilitas h2, #ekstrakurikuler h2, #berita h2, #galeri h2, #testimoni h2, #faq h2, #kontak h2 {
            text-align: center;
            margin-bottom: 15px;
        }
        #fasilitas > p, #ekstrakurikuler > p, #berita > p, #galeri > p, #testimoni > p, #faq > p, #kontak > p:first-of-type {
            text-align: center;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 40px;
            color: #555;
        }
        .fasilitas-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }
        .fasilitas-item {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .fasilitas-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        }
        .fasilitas-item i, .card-ekskul i {
            font-size: 3rem;
            color: #156c26;
            margin-bottom: 15px;
        }
        .fasilitas-item h3, .card-ekskul .card-title {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #333;
        }
        .fasilitas-item p, .card-ekskul .card-text {
            font-size: 0.95rem;
            color: #555;
            min-height: 70px; /* Disesuaikan agar seragam */
            margin-bottom: 15px;
        }
         .daftar-section {
            background-color: #156c26;
        }
        .footer {
            background-color: #343a40;
            color: #f8f9fa;
            padding: 40px 0;
        }
        .footer p {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .footer a {
            color: #00aaff;
            text-decoration: none;
        }
        .footer a:hover {
            color: #ffffff;
            text-decoration: underline;
        }
        .modal-body img.feature-image {
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .modal-body img { /* Berlaku untuk semua img di modal-body, termasuk di facilityDetailContent */
             margin-top: 15px; /* Add space above images in modals */
             border-radius: 5px;
             max-width: 100%; /* Ensure images don't overflow modal */
             height: auto;
        }

        .navbar-collapse { /* Style untuk navbar collapsed (mobile) */
            background-color: #156c26; /* Warna hijau tua yang sama dengan navbar */
            padding: 1rem; /* Padding di dalam menu collapsed */
        }
        @media (min-width: 992px) { /* Style untuk navbar expanded (desktop) */
            .navbar-collapse {
                background-color: transparent; /* Kembali transparan di desktop */
                padding: 0; /* Hapus padding */
            }
        }

        /* CSS untuk Peta Responsif */
        .map-container {
            position: relative;
            overflow: hidden;
            width: 100%; /* Lebar penuh dari parent */
            padding-top: 56.25%; /* Rasio Aspek 16:9 ( (9 / 16) * 100% ) */
            /* Untuk rasio 4:3, gunakan padding-top: 75%; */
            border-radius: 8px; /* Optional: samakan dengan rounded p-3 */
        }
        .map-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
    </style>
</head>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    // ... (JavaScript Anda yang lain mungkin sudah ada di sini, seperti untuk currentYear atau smooth scroll) ...

    const facilityDetailModalElement = document.getElementById('facilityDetailModal'); // PASTIKAN ID INI SAMA DENGAN ID MODAL ANDA
    
    if (facilityDetailModalElement) {
        facilityDetailModalElement.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Tombol yang memicu modal

            // Ambil data dari atribut data-* di tombol
            const modalTitleText = button.getAttribute('data-modal-title');
            const modalDescriptionHTML = button.getAttribute('data-modal-description'); // Ini sudah mengandung <br> dari nl2br PHP
            const modalImagePath = button.getAttribute('data-modal-image');
            // const facilityKey = button.getAttribute('data-facility-key'); // Bisa digunakan jika perlu

            // Target elemen di dalam modal
            const modalHeaderTitleElement = facilityDetailModalElement.querySelector('.modal-title'); // Judul di header modal
            const modalImageElement = facilityDetailModalElement.querySelector('#facilityModalImage');
            const modalTitleBodyElement = facilityDetailModalElement.querySelector('#facilityModalTitle'); // Judul di body modal
            const modalDescriptionElement = facilityDetailModalElement.querySelector('#facilityModalDescription');
            
            // Update konten modal
            if (modalHeaderTitleElement) {
                 modalHeaderTitleElement.innerHTML = '<i class="bi bi-info-circle-fill me-2"></i>' + (modalTitleText || 'Detail Fasilitas');
            }
            if (modalTitleBodyElement) {
                modalTitleBodyElement.textContent = modalTitleText || '';
            }
            if (modalDescriptionElement) {
                modalDescriptionElement.innerHTML = modalDescriptionHTML || '<p><em>Detail tidak tersedia.</em></p>'; // Gunakan innerHTML karena ada <br>
            }

            if (modalImageElement && modalImagePath && modalImagePath !== 'public/asset/placeholder-facility.png') { // Hanya tampilkan jika bukan placeholder
                modalImageElement.src = modalImagePath;
                modalImageElement.alt = modalTitleText || 'Gambar Fasilitas';
                modalImageElement.style.display = 'block';
            } else if (modalImageElement) {
                modalImageElement.style.display = 'none'; // Sembunyikan jika path kosong atau placeholder
                modalImageElement.src = '';
            }
        });
    }

    // ... (JavaScript Anda untuk modal galeri, dll.) ...
});
</script>

    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="public/asset/logo.jpg" alt="Logo SMA Ibnu Aqil" width="50" height="50" class="me-2 rounded-circle">
                <strong>SMA IBNU'AQIL</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="#">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Pendaftaran</a>
                    </li>
                    <li class="nav-item dropdown d-none d-lg-block">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLinkDesktop" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Jelajahi <i class="bi bi-chevron-down"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLinkDesktop">
                            <li><a class="dropdown-item" href="#tentang-kami-modal" data-bs-toggle="modal" data-bs-target="#learnMoreModal"><i class="bi bi-info-circle me-2"></i>Tentang Kami</a></li>
                            <li><a class="dropdown-item" href="#fasilitas"><i class="bi bi-building me-2"></i>Fasilitas</a></li>
                            <li><a class="dropdown-item" href="#ekstrakurikuler"><i class="bi bi-joystick me-2"></i>Ekstrakurikuler</a></li>
                            <li><a class="dropdown-item" href="#berita"><i class="bi bi-newspaper me-2"></i>Berita & Info</a></li>
                            <li><a class="dropdown-item" href="#galeri"><i class="bi bi-images me-2"></i>Galeri</a></li>
                             <li><a class="dropdown-item" href="#kontak"><i class="bi bi-telephone-fill me-2"></i>Kontak</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index_admin.php"><i class="bi bi-person-gear me-2"></i>Login Admin</a></li> </ul>
                    </li>
                    <li class="nav-item d-lg-none">
                        <a class="nav-link" href="#tentang-kami-modal" data-bs-toggle="modal" data-bs-target="#learnMoreModal">Tentang SMA IBNU'AQIL</a>
                     </li>
                     <li class="nav-item d-lg-none">
                        <a class="nav-link" href="#fasilitas">Fasilitas</a>
                     </li>
                     <li class="nav-item d-lg-none">
                        <a class="nav-link" href="#ekstrakurikuler">Ekstrakurikuler</a>
                     </li>
                     <li class="nav-item d-lg-none">
                        <a class="nav-link" href="#berita">Berita & Info</a>
                     </li>
                     <li class="nav-item d-lg-none">
                        <a class="nav-link" href="#galeri">Galeri</a>
                     </li>
                     <li class="nav-item d-lg-none">
                        <a class="nav-link" href="#kontak">Kontak</a>
                     </li>
                     <li class="nav-item d-lg-none">
                         <a class="nav-link" href="index_admin.php">Login Admin</a> </li>
                </ul>
            </div>
        </div>
    </nav>

    <header id="beranda-hero" class="hero-section">
        <img src="gedunghd.png" alt="Gedung SMA Ibnu Aqil" class="img-fluid">
        <div class="hero-text">
            <h1 class="h2">SMA IBNU'AQIL</h1>
            <p>Serba Bisa, Pasti Bisa SUKSES!</p>
            <button class="btn btn-custom-primary btn-lg" data-bs-toggle="modal" data-bs-target="#learnMoreModal">Pelajari Lebih Lanjut</button>
        </div>
    </header>

    <section id="daftar" class="text-center py-5 daftar-section text-white section-padding">
        <div class="container">
            <h2 class="h3">Daftar Sekarang di SMA IBNU'AQIL</h2>
            <p class="lead mb-4">Pendidikan Agama Islam untuk Masa Depan Gemilang.</p>
            <a class="btn btn-custom-secondary btn-lg" href="register.php">Daftar Sekarang <i class="bi bi-arrow-right-circle-fill ms-2"></i></a>
        </div>
    </section>

<section id="fasilitas" class="container section-padding">
    <h2 class="section-title text-center">Fasilitas Unggulan SMA IBNU AQIL</h2>
    <p class="text-center lead mb-5">SMA Ibnu Aqil dilengkapi dengan berbagai fasilitas modern untuk mendukung kegiatan belajar mengajar dan pengembangan diri siswa.</p>

    <div class="fasilitas-list">
        <?php 
        // Asumsi $facilities_fetch_error dan $facilities_list sudah didefinisikan
        // oleh kode PHP di bagian atas file index.php Anda.
        // Dan kita sudah tahu $facilities_list berhasil diisi (misalnya ada 3 item).
        if (isset($facilities_fetch_error) && $facilities_fetch_error): 
        ?>
        <?php 
        elseif (!empty($facilities_list)): 
        ?>
            <?php foreach ($facilities_list as $facility): ?>
                <?php
                    // --- LOGIKA GAMBAR FASILITAS (DISAMAKAN DENGAN BERITA) ---
                    $path_gambar_fasilitas_dari_db = $facility['modal_image_path'] ?? '';
                    // Ganti 'public/asset/placeholder-facility.png' dengan path placeholder Anda jika berbeda
                    $image_facility_path = 'public/asset/placeholder-facility.png'; 

                    if (!empty($path_gambar_fasilitas_dari_db) && file_exists($path_gambar_fasilitas_dari_db)) {
                        $image_facility_path = htmlspecialchars($path_gambar_fasilitas_dari_db);
                    }
                    // Untuk DEBUG jika gambar fasilitas tidak ditemukan dengan path dari DB:
                    // elseif (!empty($path_gambar_fasilitas_dari_db)) {
                    //     echo "";
                    // }
                    // --- AKHIR LOGIKA GAMBAR FASILITAS ---

                    // Untuk Ikon (pastikan $facility['icon_class'] berisi nama kelas yang valid)
                    $kelas_ikon_dari_db = $facility['icon_class'] ?? 'bi-building'; // Default jika kosong
                ?>
                <div class="fasilitas-item">
                    <div class="mb-3">
                        <img src="<?php echo $image_facility_path; ?>?t=<?php echo time(); // Cache buster sederhana ?>" alt="<?php echo htmlspecialchars($facility['name']); ?>" class="img-fluid rounded facility-card-image">
                    </div>
                    <div> 
                        <i class="bi <?php echo $kelas_ikon_dari_db; ?> facility-icon"></i>
                        <h3><?php echo htmlspecialchars($facility['name']); ?></h3>
                        <p class="short-desc"><?php echo htmlspecialchars($facility['short_description'] ?? 'Informasi detail akan segera tersedia.'); ?></p>
                    </div>
                    <button class="btn btn-outline-success btn-sm detail-button mt-auto" 
                            data-bs-toggle="modal" 
                            data-bs-target="#facilityDetailModal" 
                            data-facility-key="<?php echo htmlspecialchars($facility['facility_key']); ?>"
                            data-modal-title="<?php echo htmlspecialchars($facility['modal_title'] ?? $facility['name']); ?>"
                            data-modal-description="<?php echo htmlspecialchars(nl2br($facility['modal_description'] ?? ($facility['short_description'] ?? 'Detail tidak tersedia.'))); ?>"
                            data-modal-image="<?php echo $image_facility_path; ?>"> 
                        Lihat Detail
                    </button>
                </div>
            <?php endforeach; // Akhir dari loop foreach fasilitas ?>
        <?php 
        else: // Jika $facilities_list kosong (dan tidak ada error fetch)
        ?>
            <p class="text-center text-muted fst-italic">Informasi fasilitas belum tersedia saat ini.</p>
        <?php endif; ?>
    </div>
</section>
<section id="berita" class="container mt-5 mb-5 section-padding">
        <h2 class="section-title text-center text-primary fw-bold">Berita Terbaru</h2>
        <p class="text-center text-muted">Ikuti perkembangan dan informasi terkini dari SMA Ibnu'Aqil.</p>

        <div class="row justify-content-center">
            <?php if (isset($news_fetch_error)): ?>
                <div class="col-12"><p class="text-center text-danger"><?php echo htmlspecialchars($news_fetch_error); ?></p></div>
            <?php elseif (empty($news_items)): ?>
                <div class="col-12"><p class="text-center text-muted">Belum ada berita terbaru saat ini.</p></div>
            <?php else: ?>
                <?php
                // Loop untuk menampilkan setiap berita yang diambil dari database
                foreach ($news_items as $news):
                    // Potong konten berita untuk preview (sekitar 150 karakter)
                    $preview_content = substr($news['content'], 0, 150);
                    // Tambahkan elipsis jika konten dipotong
                    if (strlen($news['content']) > 150) {
                        $preview_content .= '...';
                    }
                     // Mengganti newline dengan <br> untuk tampilan di HTML
                     $preview_content_formatted = nl2br(htmlspecialchars($preview_content));
                ?>
                    <div class="col-md-4 mb-4">
                        <div class="card card-news h-100 shadow-sm">
                            <?php
                            // Cek apakah ada path gambar berita dan apakah file gambar ada di server
                            $image_src = 'public/asset/placeholder-news.jpg'; // Default placeholder
                            if (!empty($news['image_path']) && file_exists($news['image_path'])) {
                                $image_src = htmlspecialchars($news['image_path']);
                            }
                            ?>
                            <img src="<?php echo $image_src; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($news['title']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($news['title']); ?></h5>
                                <p class="card-text"><?php echo $preview_content_formatted; ?></p>
                                <a href="news_detail.php?id=<?php echo htmlspecialchars($news['id']); ?>" class="btn btn-primary btn-sm">Baca Selengkapnya</a>
                            </div>
                             <div class="card-footer text-muted small">
    <i class="bi bi-calendar"></i> <?php echo date('d M Y', strtotime($news['created_at'])); ?> // Ganti $news['published_at'] jadi $news['created_at']
</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4">
            <a href="news_archive.php" class="btn btn-outline-primary btn-lg">Lihat Semua Berita</a>
        </div>
    </section>

    <section id="galeri" class="container section-padding">
        <h2 class="section-title text-center">Galeri Foto Sekolah</h2>
        <p>Momen-momen berharga dan suasana belajar di SMA Ibnu'Aqil.</p>

        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 justify-content-center">
            <?php if (isset($gallery_fetch_error)): ?>
                 <div class="col-12"><p class="text-center text-danger"><?php echo htmlspecialchars($gallery_fetch_error); ?></p></div>
            <?php elseif (empty($gallery_items)): ?>
                <div class="col-12"><p class="text-center text-muted">Belum ada foto di galeri saat ini.</p></div>
            <?php else: ?>
                <?php
                // Loop untuk menampilkan setiap item galeri yang diambil dari database
                foreach ($gallery_items as $image):
                     // Cek apakah file gambar ada di server sebelum ditampilkan
                     if (file_exists($image['image_path'])):
                ?>
                         <div class="col gallery-item">
                             <a href="<?php echo htmlspecialchars($image['image_path']); ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#galleryImageModal"
                                data-bs-imgsrc="<?php echo htmlspecialchars($image['image_path']); ?>"
                                data-bs-imgtitle="<?php echo htmlspecialchars($image['title']); ?>">
                                 <img src="<?php echo htmlspecialchars($image['image_path']); ?>"
                                      class="img-fluid rounded shadow-sm"
                                      alt="<?php echo htmlspecialchars($image['title']); ?>">
                             </a>
                         </div>
                <?php
                     endif; // End if file_exists
                endforeach; // End foreach gallery_items
                ?>
            <?php endif; ?>
        </div>
         </section>

    <div class="modal fade" id="galleryImageModal" tabindex="-1" aria-labelledby="galleryImageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="galleryImageModalLabel">Foto Kegiatan Sekolah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="galleryModalFullImage" src="" class="img-fluid rounded" alt="Foto Galeri Detail">
                </div>
            </div>
        </div>
    </div>

    <section id="kontak" class="container text-center section-padding">
        <h2 class="section-title">Lokasi & Kontak SMA IBNU'AQIL</h2>
        <p class="mb-4">Kunjungi kami atau hubungi untuk informasi lebih lanjut.</p>
        <div class="bg-light p-3 rounded">
            <div class="map-container" style="max-width: 700px; margin: auto;">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1dXYZ!2dXYZ!3dXYZ!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zSMAgSWJudiBBcWls!5e0!3m2!1sid!2sid!4v1600000000000!5m2!1sid!2sid" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            <p class="text-muted mt-2 small fst-italic">Ganti sumber iframe di atas dengan lokasi sekolah Anda yang sebenarnya dari Google Maps (Bagikan -> Sematkan peta).</p>
        </div>
        <div class="mt-4">
            <p><strong>Alamat:</strong> [Alamat Lengkap SMA Ibnu'Aqil]</p>
            <p><strong>Telepon:</strong> <a href="tel:[Nomor Telepon Asli]">[Nomor Telepon Asli]</a></p>
            <p><strong>Email:</strong> <a href="mailto:[Email Asli]">Email Asli</a></p>
        </div>
    </section>

    <div class="modal fade" id="learnMoreModal" tabindex="-1" aria-labelledby="learnMoreModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="learnMoreModalLabel"><i class="bi bi-info-circle-fill me-2"></i>Tentang SMA Ibnu'Aqil</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <img src="public/asset/logo.jpg" alt="Logo SMA Ibnu Aqil di Modal" class="img-fluid rounded-circle mb-2" style="max-height: 100px;">
                        <p class="lead">SMA Ibnu'Aqil: Mencetak Generasi Qur'ani, Cerdas, dan Berakhlak Mulia.</p>
                    </div>
                    <hr class="my-4">
                    <div class="row align-items-center mb-4">
                        <div class="col-md-5 text-center">
                            <img src="Screenshot 2025-05-10 072613.png" alt="Kegiatan Belajar Mengajar SMA Ibnu Aqil" class="img-fluid rounded feature-image shadow-sm">
                            <p class="mt-2 fst-italic text-muted small">Suasana belajar yang inspiratif dan mendukung.</p>
                        </div>
                        <div class="col-md-7">
                            <h6>Visi Kami</h6>
                            <p>Menjadi lembaga pendidikan Islam menengah atas yang unggul dalam prestasi akademik dan non-akademik, berlandaskan nilai-nilai Islam Rahmatan lil 'Alamin, serta mampu menghasilkan lulusan yang siap melanjutkan ke perguruan tinggi ternama dan berkontribusi positif bagi masyarakat.</p>
                        </div>
                    </div>
                    <div class="row align-items-center mb-4 flex-row-reverse">
                         <div class="col-md-5 text-center">
                            <img src="372833882_342016855050074_2267430978817597730_n.jpg" alt="Fasilitas Unggulan SMA Ibnu Aqil" class="img-fluid rounded feature-image shadow-sm">
                           <p class="mt-2 fst-italic text-muted small">Fasilitas modern untuk pengembangan potensi siswa.</p>
                        </div>
                        <div class="col-md-7">
                            <h6>Misi Kami</h6>
                            <ul>
                                <li>Menyelenggarakan proses pembelajaran yang aktif, kreatif, inovatif, dan menyenangkan dengan mengintegrasikan ilmu pengetahuan umum dan nilai-nilai keislaman.</li>
                                <li>Mengembangkan potensi peserta didik secara optimal melalui program pembinaan akademik, karakter, dan keterampilan hidup (life skills).</li>
                                <li>Menciptakan lingkungan sekolah yang religius, aman, nyaman, dan kondusif bagi seluruh warga sekolah.</li>
                                <li>Menjalin kerjasama yang harmonis dengan orang tua, masyarakat, dan instansi terkait untuk mendukung pencapaian tujuan pendidikan.</li>
                                <li>Menerapkan manajemen sekolah yang profesional, transparan, dan akuntabel.</li>
                            </ul>
                        </div>
                    </div>
                    <hr class="my-4">
                    <h6 class="mt-3">Program Unggulan</h6>
                    <p>Tahfidz Al-Qur'an, Kelas Olimpiade Sains, Program Bahasa Asing (Arab & Inggris Intensif), Pengembangan Kepemimpinan Siswa, Pembinaan Karakter Islami, serta berbagai kegiatan ekstrakurikuler yang menarik dan mengembangkan bakat seperti Pramuka, Paskibra, Jurnalistik, Olahraga Prestasi, dan Seni Budaya Islam.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="facilityModal" tabindex="-1" aria-labelledby="facilityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="facilityModalLabel"><i class="bi bi-building-fill-check me-2"></i>Detail Fasilitas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="facilityDetailContent">
                    </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>


    <footer class="footer text-center">
        <div class="container">
            <p class="mb-2">© <span id="currentYear">2025</span> SMA Ibnu'Aqil. Semua Hak Cipta Dilindungi.</p>
            <p class="mb-1">[Alamat Lengkap SMA Ibnu'Aqil]</p>
            <p class="mb-0">Telepon: <a href="tel:[Nomor Telepon Asli]">[Nomor Telepon Asli]</a> | Email: <a href="mailto:[Email Asli]">Email Asli</a></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Set tahun sekarang untuk footer secara dinamis
            const currentYearSpan = document.getElementById('currentYear');
            if (currentYearSpan) {
                currentYearSpan.textContent = new Date().getFullYear();
            }

            // Smooth scrolling untuk anchor links di navbar
            document.querySelectorAll('#navbarNav a.nav-link[href^="#"], #navbarNav a.dropdown-item[href^="#"]').forEach(anchor => {
                // Pastikan link memiliki href="#" yang bukan hanya untuk dropdown toggle, dan pastikan elemen target ada
                if (anchor.getAttribute('href') !== "#" &&
                    !anchor.hasAttribute('data-bs-toggle') &&
                     document.querySelector(anchor.getAttribute('href'))) { // Check if target element exists
                    anchor.addEventListener('click', function (e) {
                        e.preventDefault();
                        const targetId = this.getAttribute('href');
                        const targetElement = document.querySelector(targetId); // Get the target element

                        if (targetElement) { // Ensure target element was found
                            const navbarHeight = document.querySelector('.navbar-custom.sticky-top')?.offsetHeight || 70;
                            const elementPosition = targetElement.getBoundingClientRect().top;
                            const offsetPosition = elementPosition + window.pageYOffset - navbarHeight;

                            window.scrollTo({ top: offsetPosition, behavior: 'smooth' });

                            // Tutup navbar collapsable di mobile setelah klik
                            const navbarToggler = document.querySelector('.navbar-toggler');
                            const navbarCollapse = document.querySelector('#navbarNav');
                            if (navbarToggler && !navbarToggler.classList.contains('collapsed')) {
                                if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                                    // Hanya tutup jika link yang diklik BUKAN bagian dari dropdown menu
                                     if (!this.closest('.dropdown-menu')) {
                                         new bootstrap.Collapse(navbarCollapse).hide();
                                     }
                                }
                            }
                        }
                    });
                } else if (anchor.getAttribute('href') === "#" && anchor.getAttribute('aria-current') === 'page') {
                     // Handler khusus untuk link "Beranda" href="#"
                     anchor.addEventListener('click', function(e){
                         e.preventDefault();
                         const heroSection = document.getElementById('beranda-hero'); // Assuming your hero section has id 'beranda-hero'
                         if(heroSection){
                              const navbarHeight = document.querySelector('.navbar-custom.sticky-top')?.offsetHeight || 70;
                             const elementPosition = heroSection.getBoundingClientRect().top;
                             const offsetPosition = elementPosition + window.pageYOffset - navbarHeight;
                             window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
                         } else {
                              // Fallback to scrolling to top if hero section not found
                             window.scrollTo({ top: 0, behavior: 'smooth' });
                         }

                         // Tutup navbar collapsable di mobile setelah klik
                         const navbarToggler = document.querySelector('.navbar-toggler');
                         const navbarCollapse = document.querySelector('#navbarNav');
                         if (navbarToggler && !navbarToggler.classList.contains('collapsed')) {
                             if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                                 new bootstrap.Collapse(navbarCollapse).hide();
                             }
                         }
                     });
                }
            });


            // JavaScript untuk Modal Fasilitas (Data statis di JS)
            // PASTIKAN DATA facilityDetails SESUAI DENGAN FASILITAS YANG ADA
            const facilityModalElement = document.getElementById('facilityModal');
            if (facilityModalElement) {
                const facilityModal = new bootstrap.Modal(facilityModalElement);
                const facilityDetailContent = document.getElementById('facilityDetailContent');
                const facilityModalLabel = document.getElementById('facilityModalLabel');
                const facilityDetails = {
                    'perpustakaan': {
                        title: 'Perpustakaan Lengkap & Nyaman',
                        content: '<p>Perpustakaan kami menyediakan ribuan koleksi buku pelajaran, referensi, fiksi, dan non-fiksi. Dilengkapi dengan ruang baca yang tenang, area diskusi, dan akses ke e-library untuk mendukung kebutuhan literasi siswa.</p><img src="public/asset/fasilitas/perpustakaan.jpg" alt="Perpustakaan SMA Ibnu Aqil" class="img-fluid rounded">',
                    },
                    'laboratorium': {
                        title: 'Laboratorium Modern & Terpadu',
                        content: '<p>Kami memiliki laboratorium IPA (Fisika, Kimia, Biologi), Komputer, dan Bahasa yang dilengkapi peralatan modern sesuai standar. Siswa dapat melakukan praktikum dan eksperimen untuk memperdalam pemahaman teoritis.</p><img src="372833882_342016855050074_226743097881759730_n.jpg" alt="Laboratorium SMA Ibnu Aqil" class="img-fluid rounded">', // Cek path gambar ini
                    },
                    'ruang-kelas': {
                        title: 'Ruang Kelas Kondusif & Interaktif',
                        content: '<p>Setiap ruang kelas didesain agar nyaman dan kondusif untuk belajar. Dilengkapi dengan AC, proyektor LCD, papan tulis interaktif, papan tulis, dan pencahayaan yang baik untuk mendukung proses pembelajaran yang efektif.</p><img src="Screenshot 2025-05-10 023437.png" alt="Ruang Kelas SMA Ibnu Aqil" class="img-fluid rounded">', // Cek path gambar ini
                    },
                    'olahraga': {
                        title: 'Fasilitas Olahraga Lengkap',
                        content: '<p>SMA Ibnu Aqil menyediakan berbagai fasilitas olahraga untuk mendukung kesehatan fisik dan pengembangan bakat siswa, termasuk lapangan sepak bola multifungsi, lapangan basket/voli indoor, dan area bulu tangkis.</p><img src="public/asset/fasilitas/olahraga.jpg" alt="Fasilitas Olahraga SMA Ibnu Aqil" class="img-fluid rounded">',
                    },
                    'seni-musik': {
                         title: 'Ruang Seni dan Studio Musik Kreatif',
                         content: '<p>Fasilitas ini mencakup studio musik kedap suara dengan berbagai alat musik modern dan tradisional, ruang seni rupa yang inspiratif, serta panggung mini untuk pertunjukan dan apresiasi seni.</p><img src="public/asset/fasilitas/seni_musik.jpg" alt="Ruang Seni dan Musik SMA Ibnu Aqil" class="img-fluid rounded">',
                     },
                     'internet': {
                         title: 'Akses Internet Cepat & Andal',
                         content: '<p>Seluruh area sekolah, termasuk ruang kelas, perpustakaan, dan area umum, terjangkau oleh jaringan Wi-Fi berkecepatan tinggi untuk memudahkan siswa dan guru dalam mengakses sumber belajar online, mengerjakan tugas, dan berkomunikasi secara digital.</p><img src="public/asset/fasilitas/internet.jpg" alt="Akses Internet SMA Ibnu Aqil" class="img-fluid rounded">',
                     }
                };
                document.querySelectorAll('.detail-button').forEach(button => {
                    button.addEventListener('click', function () {
                        const facilityKey = this.getAttribute('data-facility');
                        const details = facilityDetails[facilityKey];
                        if (details) {
                            facilityModalLabel.innerHTML = '<i class="bi bi-info-square-fill me-2"></i>Detail: ' + details.title;
                            facilityDetailContent.innerHTML = details.content;
                            facilityModal.show();
                        } else {
                            facilityModalLabel.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>Informasi Tidak Tersedia';
                            facilityDetailContent.innerHTML = '<p class="text-center my-3">Maaf, detail untuk fasilitas ini belum tersedia saat ini. Silakan hubungi kami untuk informasi lebih lanjut.</p>';
                            facilityModal.show();
                        }
                    });
                });
            }

            // JavaScript untuk Modal Galeri Foto (Menangani link dengan data-bs-toggle)
            // Kode ini tetap berfungsi karena PHP akan menghasilkan elemen <a> dengan data-bs-toggle
            const galleryImageModalElement = document.getElementById('galleryImageModal');
            if (galleryImageModalElement) {
                const galleryImageModalInstance = new bootstrap.Modal(galleryImageModalElement);
                const modalFullImage = galleryImageModalElement.querySelector('#galleryModalFullImage');
                const modalTitle = galleryImageModalElement.querySelector('#galleryImageModalLabel');

                // Menggunakan event delegation untuk menangani klik pada elemen galeri yang dimuat dinamis
                document.querySelector('#galeri .row').addEventListener('click', function(event) {
                    const targetLink = event.target.closest('.gallery-item a[data-bs-toggle="modal"]'); // Cari link terdekat yang memicu modal
                    if (targetLink) {
                        event.preventDefault(); // Cegah aksi default link
                        const imgSrc = targetLink.getAttribute('data-bs-imgsrc');
                        const imgTitle = targetLink.getAttribute('data-bs-imgtitle') || "Foto Kegiatan Sekolah"; // Default title

                        if (modalFullImage) {
                            modalFullImage.src = imgSrc;
                            modalFullImage.alt = imgTitle;
                        }
                        if (modalTitle) {
                            modalTitle.textContent = imgTitle;
                        }
                        galleryImageModalInstance.show(); // Tampilkan modal
                    }
                });
            }

        }); // Penutup DOMContentLoaded
    </script>
    <div class="modal fade" id="facilityDetailModal" tabindex="-1" aria-labelledby="facilityDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white"> 
                <h5 class="modal-title" id="facilityDetailModalLabel"><i class="bi bi-info-circle-fill me-2"></i>Detail Fasilitas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img src="" alt="Gambar Fasilitas Detail" id="facilityModalImage" class="img-fluid rounded mb-3" style="display:none; max-height: 400px; width: 100%; object-fit: contain;">
                <h4 id="facilityModalTitle" class="text-success"></h4>
                <div id="facilityModalDescription" class="mt-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>