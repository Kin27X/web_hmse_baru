-- web_hmse.sql
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 11 Okt 2026 pada 03.36
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `web_hmse`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'admin', '$2y$10$Pxj7yfmsb4y7tstLd1gsmuNEa1k7FDT1joqT7xCP5xOmh1oj4d1se', '2026-07-28 16:57:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `place` varchar(200) DEFAULT NULL,
  `event_time` varchar(100) DEFAULT NULL,
  `event_date` date NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `events`
--

INSERT INTO `events` (`id`, `title`, `place`, `event_time`, `event_date`, `photo`, `created_at`) VALUES
(5, 'Tes', 'Villa Puncak, Bogor', '', '2026-10-06', 'img_6ac4eedec0a074.02042414.jpeg', '2026-10-06 19:51:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `gallery_photos`
--

CREATE TABLE `gallery_photos` (
  `id` int(11) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `category` enum('olahraga','belajar','event') NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `gallery_photos`
--

INSERT INTO `gallery_photos` (`id`, `photo`, `category`, `sort_order`, `created_at`) VALUES
(1, 'motif_biru.jpg', 'olahraga', 1, '2026-07-28 16:57:26'),
(2, 'home.png', 'belajar', 2, '2026-07-28 16:57:26'),
(3, 'computer_1.jpg', 'belajar', 3, '2026-07-28 16:57:26'),
(4, 'foto.jpg', 'event', 4, '2026-07-28 16:57:26'),
(5, 'img_6a6880182ee7c0.37363060.png', 'event', 0, '2026-07-28 17:10:32');

-- --------------------------------------------------------

--
-- Struktur dari tabel `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `title` varchar(200) NOT NULL,
  `event_datetime` datetime NOT NULL,
  `author` varchar(150) DEFAULT NULL,
  `body_html` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `news`
--

INSERT INTO `news` (`id`, `slug`, `title`, `event_datetime`, `author`, `body_html`, `created_at`, `updated_at`) VALUES
(6, 'tes-fungsi-35a0d', 'Tes Fungsi', '2026-10-04 18:41:00', 'Rinkin', '<strong> What are the TEKOTOK? </strong>\r\n<h2>The Texas Essential Knowledge and Skills (TEKS) are the state standards for what students should know and be able to do in each subject and grade level.</h2> The Texas State Board of Education (SBOE) has legislative authority to adopt the TEKS for each subject of the required curriculum. The SBOE periodically reviews and revises the TEKS for each subject area. To learn more about the process and timelines for review and revision of the TEKS please visit \r\n<a href=\"https://tea.texas.gov/curriculum/teks-review/.\" color=\"blue\">disini</a>\r\n\r\n\r\n\r\n[foto:11|34|right]\r\n\r\n\r\n\r\n<h3> What are the ELPS? </h3>\r\n\r\nThe English Language Proficiency Standards (ELPS) are the state standards that outline the English language acquisition expectations and proficiency level descriptors for emergent bilingual students.\r\n\r\nThe ELPS are divided into the four domains of language acquisition: listening, speaking, reading, and writing. Each domain includes cross-curricular student expectations and proficiency level descriptors. The cross-curricular student expectations indicate what the students should know and be able to do to meet academic content standards in all subjects. The proficiency level descriptors describe behaviors emergent bilingual students exhibit and the progression of those behaviors as students acquire more English.', '2026-10-04 18:44:18', '2026-10-08 20:06:38'),
(7, 'tes-2-1d503', 'tes 2', '2026-10-04 13:59:00', '', 'Ayam Bekicot Banyak maun\r\n\r\nAyam lagi', '2026-10-04 18:59:49', '2026-10-04 19:05:14'),
(8, 'tes-lagi-6eeac', 'Tes lagi', '2026-10-06 14:52:00', '!@384575', 'AYAMADJW(AJDOSDLMWOMOASMDXAMDC AW\"M\r\n\r\n\r\n[foto:22]\r\n\r\n\r\nadwoajnodsaomdwl\r\n\r\n\r\n[foto:23]', '2026-10-06 19:52:32', '2026-10-06 19:54:49'),
(9, 'tes-setelah-input-besar-foto-597a4', 'Tes Setelah input besar foto', '2026-10-08 15:07:00', '!@384575', 'Pengertian Energi, Bentuk-Bentuk, Contoh & Perubahannya | Fisika Kelas 7\r\nHani Ammariah\r\n\r\nOctober 30, 2024 • 6 minutes read\r\n\r\n\r\nEnergi dan Perubahannya\r\n\r\nYuk, kita pahami bersama mengenai konsep energi, meliputi pengertian, rumus, bentuk-bentuk energi disertai contoh, hingga perubahannya di dalam kehidupan sehari-hari di artikel Fisika kelas 7 ini!\r\n\r\n—\r\n\r\n \r\n\r\n\r\n\r\n\r\n\r\nKamu pernah kan berolahraga, lalu jadi merasa lelah? Misalnya, lari atau bersepeda. Biasanya, kalo udah capek, hal paling mudah yang dapat kamu lakukan adalah beristirahat. Bisa dengan cara duduk, berdiri di depan kipas angin, atau mengonsumsi makanan dan minuman.\r\n\r\nHal ini perlu kamu lakukan, karena sudah kehabisan energi. Apa sih energi itu? Apa hubungannya lelah dengan energi, ya? Ayo, kita temukan jawabannya!\r\n\r\nSeperti peralatan elektronik yang memakai baterai, jika peralatan tersebut kehabisan baterainya, maka alat itu tidak akan bisa bekerja dan mati. Selain itu, jika ada dua alat elektronik yang memiliki baterai berbeda.\r\n\r\nMisalnya, yang satu dalam keadaan penuh, dan yang satunya dalam keadaan setengah penuh, maka alat yang memiliki kondisi baterai penuh akan bertahan dan bekerja lebih lama.\r\n\r\n \r\n\r\nPengertian Energi\r\nBaterai dan kelelahan manusia sama-sama dipengaruhi oleh energi. Energi adalah kemampuan untuk melakukan usaha (kerja) dan mengalami perubahan.\r\n\r\nPerubahan ini bisa berupa perubahan posisi, perubahan gerak, perubahan suhu, perubahan wujud zat, bahkan perubahan pada makhluk hidup, seperti tumbuh dan berkembang juga termasuk di dalamnya.\r\n\r\nJika energi habis, maka suatu benda itu tidak akan bisa melakukan kerja (usaha). Manusia bisa merasa kelelahan sehabis berkegiatan, karena manusia menggunakan energi. Maka, manusia beristirahat dan mengonsumsi makanan serta minuman untuk mengembalikan energi yang hilang.\r\n\r\nBaca Juga: Macam-Macam Sifat dan Perubahan Bentuk Zat\r\n\r\n \r\n\r\nSatuan Energi\r\nDalam satuan internasional (SI), satuan dari energi adalah joule. Selain itu, untuk menyatakan energi dalam bentuk kalor (panas) digunakan kalori dan kWh untuk menyatakan energi listrik.\r\n\r\nsatuan energi\r\n\r\n \r\n\r\nBentuk-Bentuk Energi\r\nAlam menyediakan banyak sekali sumber energi yang dapat digunakan oleh manusia. Angin, air, cahaya, minyak bumi, bahkan bunyi pun memiliki energi. Berbagai jenis atau bentuk-bentuk energi yang bisa kamu ketahui, di antaranya sebagai berikut:\r\n\r\nbentuk-bentuk energi\r\n\r\n \r\n\r\n1. Energi Potensial\r\nEnergi potensial (EP) merupakan energi yang tersimpan pada sebuah benda atau sesuatu karena posisi atau kedudukannya. Energi potensial terbagi lagi menjadi beberapa macam, yaitu energi potensial gravitasi, energi potensial pegas (elastis), dan energi potensial listrik, dan masih banyak lagi. \r\n\r\nNah, contohnya itu apa, sih? Misalnya, kamu ingin menjatuhkan sebuah benda dengan ketinggian tertentu. Semakin tinggi kedudukan benda tersebut dari tanah/lantai, maka semakin besar pula energi potensialnya. Paham, ya? \r\n\r\nRumus Energi Potensial: Ep = m × g × h\r\n\r\nKeterangan:\r\n\r\nm = Massa benda (kg)\r\ng = Gravitasi (N/kg)\r\nh = Ketinggian letak benda dari bumi (m)', '2026-10-08 20:08:47', '2026-10-08 20:11:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `news_photos`
--

CREATE TABLE `news_photos` (
  `id` int(11) NOT NULL,
  `news_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `news_photos`
--

INSERT INTO `news_photos` (`id`, `news_id`, `filename`, `sort_order`, `created_at`) VALUES
(10, 6, 'img_6ac23e95053c46.27326872.jpeg', 0, '2026-10-04 18:55:01'),
(11, 6, 'img_6ac23eeeeb93e6.23554630.jpeg', 0, '2026-10-04 18:56:30'),
(13, 7, 'img_6ac23fbd616fe7.82288848.jpg', 0, '2026-10-04 18:59:57'),
(15, 7, 'img_6ac240490416d4.80052662.jpg', 0, '2026-10-04 19:02:17'),
(16, 7, 'img_6ac2408ed67d02.66187571.jpeg', 0, '2026-10-04 19:03:26'),
(17, 7, 'img_6ac240fa7bcac4.69667832.jpg', 0, '2026-10-04 19:05:14'),
(22, 8, 'img_6ac4ef7b887f66.37959049.jpg', 0, '2026-10-06 19:54:19'),
(23, 8, 'img_6ac4ef7b88ccb0.05281426.jpg', 0, '2026-10-06 19:54:19'),
(24, 9, 'img_6ac795fec06491.89742169.jpg', 0, '2026-10-08 20:09:18');

-- --------------------------------------------------------

--
-- Struktur dari tabel `periode`
--

CREATE TABLE `periode` (
  `id` int(11) NOT NULL,
  `start_year` int(11) NOT NULL,
  `end_year` int(11) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `periode`
--

INSERT INTO `periode` (`id`, `start_year`, `end_year`, `sort_order`, `created_at`) VALUES
(6, 2024, 2025, 0, '2026-08-01 20:59:19'),
(9, 2025, 2026, 0, '2026-08-02 23:38:10'),
(10, 2027, 2028, 0, '2026-10-06 19:47:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `program_kerja`
--

CREATE TABLE `program_kerja` (
  `id` int(11) NOT NULL,
  `periode_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `program_kerja`
--

INSERT INTO `program_kerja` (`id`, `periode_id`, `title`, `description`, `sort_order`, `created_at`) VALUES
(7, 6, 'Tarung AYam', 'Ayam Jago', 0, '2026-08-01 21:17:36'),
(8, 6, 'Tes 1', 'Tes ulang', 0, '2026-08-02 23:27:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `struktur_nodes`
--

CREATE TABLE `struktur_nodes` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `group_type` enum('leader','branch','division') DEFAULT NULL,
  `node_key` varchar(50) DEFAULT NULL,
  `label` varchar(100) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `npm` varchar(30) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `alamat` varchar(150) DEFAULT NULL,
  `instagram` varchar(150) DEFAULT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `nid` varchar(30) DEFAULT NULL,
  `masa_jabatan` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `struktur_nodes`
--

INSERT INTO `struktur_nodes` (`id`, `parent_id`, `group_type`, `node_key`, `label`, `name`, `npm`, `semester`, `alamat`, `instagram`, `whatsapp`, `email`, `photo`, `icon`, `sort_order`, `created_at`, `nid`, `masa_jabatan`) VALUES
(1, NULL, 'leader', 'kaprodi', 'Kepala Program Studi', 'Gusti Nyoman Budiadnyana, S.Kom., MM.', '', '', 'Telaga Bestari', '@gustibudiadnyana', '085717296717', '', 'img_6a717d11180fa3.59241067.jpg', NULL, 1, '2026-07-28 16:57:26', '04-2804-7602', ''),
(2, NULL, 'leader', 'ketua', 'Ketua Umum', 'Ahmad Nurohman', '2024807033', '4', 'Pasar Kemis', 'ahmadnurohman', '083845656653', 'ahmad@example.com', 'img_6a717f29d33147.95855272.jpg', NULL, 2, '2026-07-28 16:57:26', NULL, NULL),
(3, NULL, 'leader', 'wakil', 'Wakil Ketua Umum', 'Ramadhan Putra Adi Nugraha Bangaskrama', '2025807042', '2', 'Cikupa', '@rambngskrm', '081586738381', '', 'img_6a7180786cc5d5.87912368.jpg', NULL, 3, '2026-07-28 16:57:26', NULL, NULL),
(4, NULL, 'branch', 'sekretaris', 'Cabang', 'Sekretaris', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'edit_note', 4, '2026-07-28 16:57:26', NULL, NULL),
(5, 4, NULL, NULL, 'Anggota Sekretaris', 'Nur Ramadhani', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, '2026-07-28 16:57:26', NULL, NULL),
(6, 4, NULL, NULL, 'Anggota Sekretaris', 'Chika Anggi Taryana', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-07-28 16:57:26', NULL, NULL),
(7, NULL, 'branch', 'bendahara', 'Cabang', 'Bendahara', '', '', '', '', '', '', 'img_6a7180b6c79755.08107768.jpg', 'account_balance_wallet', 5, '2026-07-28 16:57:26', NULL, NULL),
(8, 7, NULL, NULL, 'Anggota Bendahara', 'Sahriyal Riza Saputra', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, '2026-07-28 16:57:26', NULL, NULL),
(9, 7, NULL, NULL, 'Anggota Bendahara', 'Dilla Alvena', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, '2026-07-28 16:57:26', NULL, NULL),
(10, NULL, 'division', 'kominfo', 'Divisi', 'Kominfo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'campaign', 6, '2026-07-28 16:57:26', NULL, NULL),
(11, NULL, 'division', 'humas', 'Divisi', 'Humas', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'groups', 7, '2026-07-28 16:57:26', NULL, NULL),
(12, NULL, 'division', 'litbang', 'Divisi', 'Litbang', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'science', 8, '2026-07-28 16:57:26', NULL, NULL),
(13, NULL, 'division', 'sapras', 'Divisi', 'Sapras', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'construction', 9, '2026-07-28 16:57:26', NULL, NULL),
(14, NULL, 'division', 'olahraga', 'Divisi', 'Olahraga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'sports_soccer', 10, '2026-07-28 16:57:26', NULL, NULL),
(15, NULL, 'division', 'sdm', 'Divisi', 'SDM', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'badge', 11, '2026-07-28 16:57:26', NULL, NULL),
(16, 10, NULL, NULL, 'Ketua Kominfo', 'Didik', '2024807041', '4', 'rumah sakit', 'rinx2_7', '081218390639', 'shiryumin27@gmail.com', 'img_6a699ce78b7a58.90249166.jpg', NULL, 0, '2026-07-29 13:25:43', NULL, NULL),
(17, 4, NULL, NULL, 'Sekretaris', 'Sherly Mila Saputri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-31 17:23:09', NULL, NULL),
(18, 7, NULL, NULL, 'Bendahara', 'Amelia Virnada', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-31 17:23:09', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `gallery_photos`
--
ALTER TABLE `gallery_photos`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indeks untuk tabel `news_photos`
--
ALTER TABLE `news_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `news_id` (`news_id`);

--
-- Indeks untuk tabel `periode`
--
ALTER TABLE `periode`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `program_kerja`
--
ALTER TABLE `program_kerja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `periode_id` (`periode_id`);

--
-- Indeks untuk tabel `struktur_nodes`
--
ALTER TABLE `struktur_nodes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `gallery_photos`
--
ALTER TABLE `gallery_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `news_photos`
--
ALTER TABLE `news_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `periode`
--
ALTER TABLE `periode`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `program_kerja`
--
ALTER TABLE `program_kerja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `struktur_nodes`
--
ALTER TABLE `struktur_nodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `news_photos`
--
ALTER TABLE `news_photos`
  ADD CONSTRAINT `news_photos_ibfk_1` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `program_kerja`
--
ALTER TABLE `program_kerja`
  ADD CONSTRAINT `program_kerja_ibfk_1` FOREIGN KEY (`periode_id`) REFERENCES `periode` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `struktur_nodes`
--
ALTER TABLE `struktur_nodes`
  ADD CONSTRAINT `struktur_nodes_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `struktur_nodes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;