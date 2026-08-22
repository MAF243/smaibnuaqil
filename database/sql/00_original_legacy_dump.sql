-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 12, 2025 at 02:23 PM
-- Server version: 10.11.11-MariaDB-cll-lve
-- PHP Version: 8.3.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `goaa7147_sma_ibnu_aqil`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','panitia') NOT NULL DEFAULT 'panitia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `role`) VALUES
(9, 'admin', '0192023a7bbd73250516f069df18b500', 'panitia'),
(10, 'adminq', '$2y$10$6kWVJh0gxX0ckwKWYbJpA.X/iJJdHEdFTDV7sTvPrpC2wsF01uwVy', '');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `document_type` enum('kk','akta','rapor') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `dob` date NOT NULL,
  `phone` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `status` enum('belum_daftar','pending','verified','incomplete','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `kk_file` varchar(255) DEFAULT NULL,
  `ktp_father` varchar(255) DEFAULT NULL,
  `ktp_mother` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_relation` varchar(50) DEFAULT NULL,
  `ktp_guardian` varchar(255) DEFAULT NULL,
  `kk_guardian` varchar(255) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `father_phone` varchar(20) DEFAULT NULL,
  `father_job` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `mother_phone` varchar(20) DEFAULT NULL,
  `mother_job` varchar(100) DEFAULT NULL,
  `akta_lahir` varchar(255) DEFAULT NULL,
  `nilai_rapor` varchar(255) DEFAULT NULL,
  `father_email` varchar(100) DEFAULT NULL,
  `father_income` enum('<Rp.500.000','Rp.500.000 - Rp.1.500.000','Rp.1.500.000 - Rp.3.000.000','Rp.3.000.000 - Rp.5.000.000','>Rp.5.000.000') DEFAULT NULL,
  `father_birthplace` varchar(100) DEFAULT NULL,
  `father_dob` date DEFAULT NULL,
  `mother_email` varchar(100) DEFAULT NULL,
  `mother_income` enum('<Rp.500.000','Rp.500.000 - Rp.1.500.000','Rp.1.500.000 - Rp.3.000.000','Rp.3.000.000 - Rp.5.000.000','>Rp.5.000.000') DEFAULT NULL,
  `mother_birthplace` varchar(100) DEFAULT NULL,
  `mother_dob` date DEFAULT NULL,
  `guardian_birthplace` varchar(100) DEFAULT NULL,
  `guardian_dob` date DEFAULT NULL,
  `guardian_email` varchar(100) DEFAULT NULL,
  `guardian_income` enum('<Rp.500.000','Rp.500.000 - Rp.1.500.000','Rp.1.500.000 - Rp.3.000.000','Rp.3.000.000 - Rp.5.000.000','>Rp.5.000.000') DEFAULT NULL,
  `student_hobby` varchar(100) DEFAULT NULL,
  `goal` varchar(100) DEFAULT NULL,
  `motivation` varchar(100) DEFAULT NULL,
  `birthplace` varchar(100) DEFAULT NULL,
  `gender` enum('Laki-Laki','Perempuan') DEFAULT NULL,
  `religion_child` enum('Islam','Kristen Protestan','Kristen Katolik','Hindu','Buddha','Konghucu') DEFAULT NULL,
  `father_religion` enum('Islam','Kristen Protestan','Kristen Katolik','Hindu','Buddha','Konghucu') DEFAULT NULL,
  `mother_religion` enum('Islam','Kristen Protestan','Kristen Katolik','Hindu','Buddha','Konghucu') DEFAULT NULL,
  `guardian_religion` enum('Islam','Kristen Protestan','Kristen Katolik','Hindu','Buddha','Konghucu') DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `name`, `dob`, `phone`, `address`, `status`, `created_at`, `kk_file`, `ktp_father`, `ktp_mother`, `guardian_name`, `guardian_phone`, `guardian_relation`, `ktp_guardian`, `kk_guardian`, `father_name`, `father_phone`, `father_job`, `mother_name`, `mother_phone`, `mother_job`, `akta_lahir`, `nilai_rapor`, `father_email`, `father_income`, `father_birthplace`, `father_dob`, `mother_email`, `mother_income`, `mother_birthplace`, `mother_dob`, `guardian_birthplace`, `guardian_dob`, `guardian_email`, `guardian_income`, `student_hobby`, `goal`, `motivation`, `birthplace`, `gender`, `religion_child`, `father_religion`, `mother_religion`, `guardian_religion`, `user_id`, `rejection_reason`) VALUES
(1, 'Ahmad Fauzan', '2008-05-12', '081234567890', 'Jl. Kenanga No. 45, Jakarta', 'verified', '2025-05-06 07:30:00', 'kk_ahmad.pdf', 'ktp_ayah_ahmad.pdf', 'ktp_ibu_ahmad.pdf', 'Budi Santoso', '081234567891', 'Paman', 'ktp_wali_ahmad.pdf', 'kk_wali_ahmad.pdf', 'Muhammad Rizki', '081234567892', 'Pegawai Negeri', 'Siti Aminah', '081234567893', 'Ibu Rumah Tangga', 'akta_ahmad.pdf', 'nilai_rapor_ahmad.pdf', 'rizki.muhammad@example.com', '', 'Jakarta', '1980-07-20', 'aminah.siti@example.com', '', 'Surabaya', '1982-09-15', 'Bekasi', '1975-03-10', 'budi.santoso@example.com', '', 'Membaca, Sepak Bola', 'Menjadi Dokter', 'Ingin membantu orang lain', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Laila Nur', '2007-08-22', '082345678901', 'Jl. Melati No. 23, Bandung', '', '2025-05-06 08:00:00', 'kk_laila.pdf', 'ktp_ayah_laila.pdf', 'ktp_ibu_laila.pdf', 'Fitri Handayani', '082345678902', 'Tante', 'ktp_wali_laila.pdf', 'kk_wali_laila.pdf', 'Agus Saputra', '082345678903', 'Wiraswasta', 'Rina Kartika', '082345678904', 'Guru', 'akta_laila.pdf', 'nilai_rapor_laila.pdf', 'saputra.agus@example.com', '', 'Bandung', '1978-06-15', 'kartika.rina@example.com', '', 'Semarang', '1980-11-25', 'Tangerang', '1972-02-20', 'handayani.fitri@example.com', '', 'Melukis, Musik', 'Menjadi Arsitek', 'Ingin merancang bangunan yang indah', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Rizky Pratama', '2006-12-10', '083456789012', 'Jl. Mawar No. 56, Yogyakarta', '', '2025-05-06 09:20:00', 'kk_rizky.pdf', 'ktp_ayah_rizky.pdf', 'ktp_ibu_rizky.pdf', 'Dewi Anggraini', '083456789013', 'Bibi', 'ktp_wali_rizky.pdf', 'kk_wali_rizky.pdf', 'Hendro Wijaya', '083456789014', 'Petani', 'Sulastri', '083456789015', 'Pedagang', 'akta_rizky.pdf', 'nilai_rapor_rizky.pdf', 'wijaya.hendro@example.com', '', 'Yogyakarta', '1985-04-18', 'sulastri@example.com', '', 'Solo', '1986-08-05', 'Magelang', '1978-12-30', 'anggraini.dewi@example.com', '', 'Mendaki Gunung, Fotografi', 'Menjadi Penulis', 'Ingin menceritakan pengalaman yang menginspirasi', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Muhammad Ikhwan Manshur', '2005-02-02', '087783932412', 'Jl.salak', 'pending', '2025-05-06 18:07:19', 'uploads/WhatsApp Image 2025-05-05 at 10.35.05_7a3e7a78.jpg', '681a4fab1744b_WhatsApp Image 2025-05-05 at 10.35.05_7a3e7a78.jpg', '681a4fab17669_WhatsApp Image 2025-05-05 at 09.49.46_fad9992d.jpg', NULL, NULL, NULL, '', '', 'Ayah', '089876532141', 'nguli', 'Ibu', '085218917853', 'Freelance', 'uploads/WhatsApp Image 2025-05-05 at 09.39.39_846ea37a.jpg', 'uploads/ChatGPT Image May 4, 2025, 12_10_59 AM.png', 'bapaksayanganak@gmail.com', '', 'Kasur', '1976-10-10', 'istritercinta@gmail.com', '', 'Rumah Sehat', '1986-03-02', NULL, NULL, NULL, NULL, 'Mancing', 'Menguasai 7 elemen', 'ingin membanggakan orangtua', 'Rumah Sakit', 'Laki-Laki', 'Islam', 'Islam', 'Islam', NULL, NULL, NULL),
(6, 'lucifer', '2025-05-17', '98765678', 'Kota Roti Baznaz', 'pending', '2025-05-07 03:09:45', 'uploads/Screenshot 2025-01-06 133455.png', '681ace93988bb_Screenshot 2025-01-06 133455.png', '681ace9398a46_Screenshot 2025-01-04 194403.png', NULL, NULL, NULL, '', '', 'tyui', '345678909876543', 'Lembaga Penghimpunan Ikan Bernyit', 'oketi', '4567865', 'vtuber', 'uploads/Screenshot 2025-01-06 133455.png', 'uploads/Screenshot 2025-01-06 133516.png', 'jklop@gmail.com', '', 'kota oke ', '2025-05-01', 'lawop@gmail.com', '', 'Bojokerto', '2025-05-22', NULL, NULL, NULL, NULL, 'Memancing Emosi', 'Raja Iblis', 'Mencari Vision', 'Kasur', 'Laki-Laki', '', '', '', NULL, NULL, NULL),
(7, 'Cappucino Assasino', '1999-09-08', '45678987654', 'Kota Anomali Pedesaan Suka aja', 'pending', '2025-05-07 06:54:37', '7/Screenshot 2025-01-06 133516.png', '681b03261ecc6_Doc2.pdf', '681b03261ee70_Screenshot 2025-01-06 133516.png', NULL, NULL, NULL, '', '', 'TungTungSahur', '345678909876543', 'qwerty', 'minu', '4567809', 'Jual Angin', 'Screenshot 2025-01-06 133516.png', '7/Screenshot 2025-01-03 154646.png', 'yoooosh@gamil.com', '', 'qwerty', '0089-07-06', 'Angin@gmail.com', '', 'kota qwerty', '2025-05-08', NULL, NULL, NULL, NULL, 'mancing', 'Cappucino Ter Nguawor', 'Mencari Duel Sengit Dari Cappucino Ter Debes', 'Kasur', '', '', '', '', NULL, NULL, NULL),
(8, 'Hanazawa', '2008-08-07', '087752169012', 'Jl.Gatot Subroto RT.4/1 Kota. Tokyo ', 'pending', '2025-05-08 04:17:40', 'uploads/CamScanner 15-12-2024 15.27_11.jpg', '', '', 'Kana ', '089678416253', 'Bibi', '681c304042556_CamScanner 15-12-2024 15.27_2.jpg', '681c30404283a_CamScanner 15-12-2024 15.27_12.jpg', '', '', '', '', '', '', 'uploads/CamScanner 15-12-2024 15.27_10.jpg', 'uploads/CamScanner 15-12-2024 15.27_9.jpg', '', '', '', NULL, '', '', '', NULL, 'Osaka', '1995-07-05', 'kanamizawa@gmail.com', 'Rp.3.000.000 - Rp.5.000.000', 'Melukis', 'Penyanyi ', 'Ingin menambah ilmu', 'Tokyo', 'Laki-Laki', 'Kristen Protestan', '', '', 'Kristen Katolik', NULL, NULL),
(9, 'Muhammad Ibnu ', '2010-01-10', '087767890116', 'Jl. Gatot subroto', 'pending', '2025-05-08 04:44:30', 'uploads/data_pendaftar.pdf', '681c368d2387e_data_pendaftar (2).pdf', '681c368d23ab1_data_pendaftar (1).pdf', NULL, NULL, NULL, '', '', 'Abu Jamil', '087898771123', 'Dokter', 'Ummu Jamil', '0877657123', 'Freelance', 'uploads/Homepage Sekolah Ibnu \'Aqil.png', 'uploads/WhatsApp Image 2025-05-05 at 09.39.39_846ea37a.jpg', 'Q@gmail.com', 'Rp.1.500.000 - Rp.3.000.000', 'Jakarta', '1989-01-01', 'istritercinta@gmail.com', 'Rp.1.500.000 - Rp.3.000.000', 'Yogyakarta', '1978-01-10', NULL, NULL, NULL, NULL, 'Membaca Buku', 'Dokter', 'Ingin menambah ilmu dan membanggakan orangtua', 'Surakarta', 'Laki-Laki', 'Islam', '', '', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`) VALUES
(1, 'Abdurrahman', '$2y$10$gg.1ePbQsCZklCxIUWIZSej58OUABJQbKEv2GH24Z/qj4ttFxXtV.'),
(2, 'Fathin', '$2y$10$OLVSgBJgKQlc6CCNO64EI.BYAO1RY5sOYdPqnmRRTt8rGk9ErS.PK');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`) USING BTREE;

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
