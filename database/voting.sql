-- voting.sql — tabel untuk fitur Voting Mubes HMSE.
-- Import file ini SEKALI ke database `web_hmse` yang sudah ada
-- (phpMyAdmin > pilih database web_hmse > Import). Tabel lama tidak disentuh.
--
-- Kerahasiaan suara:
--   vote_voters  = SIAPA yang sudah memilih (tanpa isi pilihan).
--   vote_ballots = ISI suara. Kolom voter_id cuma terisi selama voting
--                  berlangsung (buat tombol "Batalkan suara" di admin) dan
--                  otomatis dikosongkan permanen begitu voting ditutup.
--                  ID-nya acak & tanpa jam, jadi tidak bisa dicocokkan ke
--                  jam memilih di vote_voters.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `vote_settings` (
  `id` tinyint(4) NOT NULL,
  `title` varchar(200) NOT NULL DEFAULT 'Pemilihan Ketua HMSE',
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `closed_manually` tinyint(1) NOT NULL DEFAULT 0,
  `link_wiped` tinyint(1) NOT NULL DEFAULT 0,
  `results_public` tinyint(1) NOT NULL DEFAULT 0,
  `panitia_wa` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `vote_settings` (`id`) VALUES (1);

CREATE TABLE IF NOT EXISTS `vote_voters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `npm` varchar(30) NOT NULL,
  `name` varchar(150) NOT NULL,
  `has_voted` tinyint(1) NOT NULL DEFAULT 0,
  `voted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_npm` (`npm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `vote_candidates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` int(11) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `ketua_name` varchar(150) NOT NULL,
  `ketua_npm` varchar(30) DEFAULT NULL,
  `wakil_name` varchar(150) DEFAULT NULL,
  `wakil_npm` varchar(30) DEFAULT NULL,
  `visi` text DEFAULT NULL,
  `misi` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- candidate_id NULL = Kotak Kosong (cuma tersedia kalau paslonnya 1).
CREATE TABLE IF NOT EXISTS `vote_ballots` (
  `id` char(16) NOT NULL,
  `candidate_id` int(11) DEFAULT NULL,
  `voter_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_voter` (`voter_id`),
  KEY `idx_candidate` (`candidate_id`),
  CONSTRAINT `fk_ballot_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `vote_candidates` (`id`),
  CONSTRAINT `fk_ballot_voter` FOREIGN KEY (`voter_id`) REFERENCES `vote_voters` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Log perangkat buat audit panitia (TIDAK menyimpan isi pilihan).
CREATE TABLE IF NOT EXISTS `vote_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voter_id` int(11) DEFAULT NULL,
  `npm` varchar(30) DEFAULT NULL,
  `event` varchar(40) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Wakil opsional. Baris ini juga memperbarui tabel yang sudah terlanjur
-- di-import dengan versi lama (aman dijalankan berulang kali).
ALTER TABLE `vote_candidates` MODIFY `wakil_name` varchar(150) DEFAULT NULL;
