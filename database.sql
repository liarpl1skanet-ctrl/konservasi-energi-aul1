CREATE DATABASE IF NOT EXISTS `konservasi_energi_aul` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `konservasi_energi_aul`;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2026 at 09:08 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `konservasi_energi_aul`
--

-- --------------------------------------------------------

--
-- Table structure for table `checklist`
--

CREATE TABLE `checklist` (
  `id_checklist` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `nis` int(10) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jam` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `checklist`
--

INSERT INTO `checklist` (`id_checklist`, `id_user`, `nis`, `tanggal`, `jam`) VALUES
(1, 3, 1, '2026-10-22', '10:36:55'),
(2, 3, 1, '2026-10-22', '06:20:00');

-- --------------------------------------------------------

--
-- Table structure for table `detail_checklist`
--

CREATE TABLE `detail_checklist` (
  `id_detail` int(11) NOT NULL,
  `id_checklist` int(11) NOT NULL,
  `id_perangkat` int(11) NOT NULL,
  `tahapan` enum('SEBELUM','SELAMA','SESUDAH','5R') NOT NULL,
  `kondisi` varchar(100) DEFAULT NULL,
  `status` enum('Ya','Tidak') NOT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_checklist`
--

INSERT INTO `detail_checklist` (`id_detail`, `id_checklist`, `id_perangkat`, `tahapan`, `kondisi`, `status`, `keterangan`) VALUES
(1, 1, 1, 'SEBELUM', 'Baik', 'Ya', 'Contoh data awal; boleh dihapus'),
(2, 1, 3, 'SESUDAH', 'Baik', 'Ya', 'Contoh data awal; boleh dihapus'),
(3, 2, 2, '5R', 'mati', 'Ya', 'baik'),
(4, 2, 3, 'SEBELUM', '', 'Ya', ''),
(5, 2, 1, 'SEBELUM', '', 'Ya', ''),
(6, 2, 4, 'SEBELUM', '', 'Ya', '');

-- --------------------------------------------------------

--
-- Table structure for table `perangkat`
--

CREATE TABLE `perangkat` (
  `id_perangkat` int(11) NOT NULL,
  `nama_perangkat` varchar(100) NOT NULL,
  `jenis` varchar(50) DEFAULT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `perangkat`
--

INSERT INTO `perangkat` (`id_perangkat`, `nama_perangkat`, `jenis`, `keterangan`) VALUES
(1, 'Lampu', 'Elektronik', 'penerangan'),
(2, 'AC', 'Elektronik', 'penyejuk ruangan'),
(3, 'Komputer', 'Elektronik', 'Matikan setelah kegiatan selesai'),
(4, 'Proyektor', 'Elektronik', 'Matikan setelah pembelajaran selesai');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `nis` int(10) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kelas` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`nis`, `nama`, `kelas`) VALUES
(1, 'Aul', 'XI RPL 1'),
(2, 'mayden', 'XI DKV 2'),
(3, 'Ayub', 'XI RPL 2'),
(4, 'Akbar', 'XI RPL 1'),
(5, 'Anas', 'X RPL 1');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `level` enum('Admin','Guru','Siswa') NOT NULL,
  `nis` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `nama`, `level`, `nis`) VALUES
(1, 'admin', '123', 'Administrator', 'Admin', NULL),
(2, 'guru', '123', 'Bapak/Ibu Guru', 'Guru', NULL),
(3, 'Siswa', '123', 'Aul', 'Siswa', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `checklist`
--
ALTER TABLE `checklist`
  ADD PRIMARY KEY (`id_checklist`),
  ADD KEY `idx_checklist_nis` (`nis`),
  ADD KEY `idx_checklist_user` (`id_user`);

--
-- Indexes for table `detail_checklist`
--
ALTER TABLE `detail_checklist`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `idx_detail_checklist` (`id_checklist`),
  ADD KEY `idx_detail_perangkat` (`id_perangkat`);

--
-- Indexes for table `perangkat`
--
ALTER TABLE `perangkat`
  ADD PRIMARY KEY (`id_perangkat`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`nis`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_nis` (`nis`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `checklist`
--
ALTER TABLE `checklist`
  MODIFY `id_checklist` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `detail_checklist`
--
ALTER TABLE `detail_checklist`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `perangkat`
--
ALTER TABLE `perangkat`
  MODIFY `id_perangkat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `checklist`
--
ALTER TABLE `checklist`
  ADD CONSTRAINT `fk_checklist_siswa` FOREIGN KEY (`nis`) REFERENCES `siswa` (`nis`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_checklist_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_siswa` FOREIGN KEY (`nis`) REFERENCES `siswa` (`nis`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `detail_checklist`
--
ALTER TABLE `detail_checklist`
  ADD CONSTRAINT `fk_detail_checklist` FOREIGN KEY (`id_checklist`) REFERENCES `checklist` (`id_checklist`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_perangkat` FOREIGN KEY (`id_perangkat`) REFERENCES `perangkat` (`id_perangkat`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
