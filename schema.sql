-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 28 Sep 2026 pada 09.58
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
-- Database: `schema`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `labels`
--

CREATE TABLE `labels` (
  `id` int(11) NOT NULL,
  `name` varchar(30) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `labels`
--

INSERT INTO `labels` (`id`, `name`, `created_at`) VALUES
(3, 'SERVER', '2026-09-28 15:17:44');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `body` text DEFAULT NULL,
  `checklist_json` text DEFAULT NULL,
  `color` varchar(9) NOT NULL DEFAULT '#F3E1AE',
  `text_color` varchar(9) DEFAULT NULL,
  `font_family` varchar(20) NOT NULL DEFAULT 'default',
  `tags_json` text DEFAULT NULL,
  `images_json` text DEFAULT NULL,
  `pinned` tinyint(1) NOT NULL DEFAULT 0,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `trashed` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notes`
--

INSERT INTO `notes` (`id`, `title`, `body`, `checklist_json`, `color`, `text_color`, `font_family`, `tags_json`, `images_json`, `pinned`, `archived`, `trashed`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(10, 'Server Linux Mint', 'Username : Anzi\nPassword : 12345678\n\n\"Uji Coba Aplikasi Lis dan Bridging satu sehat\"', NULL, '#F4E7C3', '#16232B', 'default', '[\"SERVER\"]', '[\"e27c6b60768f95e8ee44aab46d29e838.png\"]', 0, 0, 0, 2, 2, '2026-09-28 15:17:48', '2026-09-28 15:23:05');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `display_name`, `is_admin`, `failed_attempts`, `locked_until`, `is_active`, `created_at`) VALUES
(1, 'admin', '$2y$10$6azRuqMT.QIY..JmSkvQCeyuBFwiyq0cNZA4LPHfGdlOSJVq/X4o6', 'Admin', 1, 0, NULL, 1, '2026-09-25 22:14:18'),
(2, 'ANZI', '$2y$10$KcyfOlRYjx0pVTD.mn/fdu24PxsFg4C4iPbxGA17xU6QKr9/25SP.', 'Nestha', 1, 0, NULL, 1, '2026-09-25 22:23:31'),
(3, 'test', '$2y$10$LdZrK7SoLm5S5A9EbCVuV.Fe0EYRe1WoAu/v/7gyguwP9iaiguzlm', 'test', 0, 0, NULL, 1, '2026-09-28 10:19:04');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `labels`
--
ALTER TABLE `labels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeks untuk tabel `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `labels`
--
ALTER TABLE `labels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
