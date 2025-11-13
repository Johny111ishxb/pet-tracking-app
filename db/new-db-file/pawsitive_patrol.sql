-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 30, 2025 at 05:04 AM
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
-- Database: `pawsitive_patrol`
--

-- --------------------------------------------------------

--
-- Table structure for table `found_reports`
--

CREATE TABLE `found_reports` (
  `report_id` int(11) NOT NULL,
  `pet_id` int(11) DEFAULT NULL,
  `finder_name` varchar(100) DEFAULT NULL,
  `finder_contact` varchar(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `attached_photo` varchar(255) DEFAULT NULL,
  `reported_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `owners`
--

CREATE TABLE `owners` (
  `owner_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `owners`
--

INSERT INTO `owners` (`owner_id`, `name`, `email`, `password`, `phone`, `created_at`) VALUES
(1, 'Test User', 'test@example.com', '$2y$10$9sEtyFerRvtlQcPVBrFQF.ETZaUj2j/xVdpljDDygThBHAXbTBO.u', '123-456-7890', '2025-10-27 06:25:52'),
(2, 'Test123', 'test123@gmail.com', '$2y$10$6xjoalvv0x/H7mnVwxamwu3Ba9cpxnHymKZrfQkIoAhsHLhr1q6lq', '09999999999', '2025-10-27 06:26:55'),
(3, 'founder', 'founder123@gmail.com', '$2y$10$SslgHyOcw3RGCjFOinoIrejOfogc9vleNdy1EwCIJBqOj8PGs8cyO', '09999999999', '2025-10-28 10:48:59'),
(4, 'pew', 'pew@gmail.com', '$2y$10$pQPj/gBCyM3mggg4lA2M4eH.wu2REu/8GK4hlM11GzHj5PIuR3qi2', '09999999999', '2025-10-29 16:12:25'),
(5, 'pewpew', 'pewpew@gmail.com', '$2y$10$jTG/5o3oXK/Cnk4T1mBsTeOYRDMgW8uuVhMXBAUfE0G0FFXpm6sSm', '09999999999', '2025-10-29 16:14:16');

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `pet_id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `age_unit` varchar(10) DEFAULT 'years',
  `photo` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `status` enum('safe','lost') DEFAULT 'safe',
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `lost_since` datetime DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `emergency_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`pet_id`, `owner_id`, `name`, `type`, `breed`, `color`, `age`, `age_unit`, `photo`, `qr_code`, `status`, `date_added`, `lost_since`, `gender`, `description`, `emergency_notes`) VALUES
(1, 2, 'Cholo Loco', 'Dog', 'Lhasa Apso', 'White', 4, 'years', '1761577569_fb5f9c16d383.jpg', NULL, 'safe', '2025-10-27 15:06:09', NULL, NULL, NULL, NULL),
(2, 2, 'Cholo Loco', 'Dog', 'Lhasa Apso', 'White', 4, 'years', '1761577608_6538a92350f5.jpg', '1c62f93f1510d97ce685', 'safe', '2025-10-27 15:06:48', NULL, NULL, NULL, NULL),
(3, 2, 'Cholo Loco', 'Dog', 'Lhasa Apso', 'White', 4, 'years', '1761577630_e4fd0a65dbdc.jpg', NULL, 'safe', '2025-10-27 15:07:10', NULL, NULL, NULL, NULL),
(4, 2, 'Cholo Loco', 'Dog', 'Lhasa Apso', 'White', 4, 'years', '1761577652_4ddc59bc63b6.jpg', 'e816146afda5d67142bb', 'safe', '2025-10-27 15:07:32', NULL, NULL, NULL, NULL),
(5, 2, 'Cholo Loco', 'Dog', 'Lhasa Apso', 'White', 4, 'years', '1761578133_ac746cd89115.jpg', '4107b6cd34d5279c656a', 'safe', '2025-10-27 15:15:33', NULL, NULL, NULL, NULL),
(6, 2, 'Cholo Loco', 'Dog', 'Lhasa Apso', 'White', 4, 'years', '1761578248_2d571bf55fb2.jpg', 'eb92dcf91e5b0d63e8c4', 'safe', '2025-10-27 15:17:28', NULL, NULL, NULL, NULL),
(7, 2, 'Cholo Loco', 'Dog', 'Lhasa Apso', 'White', 4, 'years', '1761578357_f5dae67e2089.jpg', '62114dcc641fef9068ac', 'safe', '2025-10-27 15:19:17', NULL, NULL, NULL, NULL),
(8, 2, 'Askie', 'Dog', 'Aspin', 'Golden Brown', 3, 'years', '1761578668_270f13f4620f.jpg', '8c039571e3c01be9c333', 'safe', '2025-10-27 15:24:28', NULL, NULL, NULL, NULL),
(9, 2, '123', 'Dog', '123', 'White', 1, 'years', '1761583415_d54f8bff0ff2.jpg', '04adcb59909eeab81a4d', 'safe', '2025-10-27 16:43:35', NULL, NULL, NULL, NULL),
(10, 2, 'Cholo Locos', 'Dog', 'Aspin', 'Golden Brown', 1, 'years', '1761583552_d16314c1e292.jpg', NULL, 'safe', '2025-10-27 16:45:52', NULL, NULL, NULL, NULL),
(11, 2, '123123', 'Dog', 'Aspin', 'White', 1, 'years', '1761617974_d1b888f1870b.jpg', '2b64af55535e9f657256', 'safe', '2025-10-28 02:19:34', NULL, NULL, NULL, NULL),
(12, 2, 'qwe', 'Dog', 'qwe', 'qwe', 1, 'years', '1761618171_fbd22fcd31f8.jpg', '30ed822401ed3ff736d6', 'safe', '2025-10-28 02:22:51', NULL, NULL, NULL, NULL),
(13, 2, '1123', 'Dog', '12311', '12311', 1, 'years', '1761618239_e4ff1b0a87c9.jpg', '8d4b6f4e6c146a261567', 'safe', '2025-10-28 02:23:59', NULL, NULL, NULL, NULL),
(14, 2, 'coop-central', 'Dog', 'Lhasa Apso', 'Golden Brown', 1, 'years', '1761618427_46300e80cc69.jpg', 'a934967f7683b3e8d9b2', 'safe', '2025-10-28 02:27:07', NULL, NULL, NULL, NULL),
(15, 2, 'coop-central1', 'Dog', 'Lhasa Apso', 'Golden Brown', 1, 'years', '1761618483_15f11f529785.jpg', '9728f5325ea917d0d0a5', 'safe', '2025-10-28 02:28:03', NULL, NULL, NULL, NULL),
(16, 2, 'coop-central12', 'Dog', 'Lhasa Apso', 'Golden Brown', 1, 'years', '1761618614_42bb1b988aa1.jpg', 'b9c143fe6e2377a1c3ff', 'safe', '2025-10-28 02:30:14', NULL, NULL, NULL, NULL),
(17, 2, 'coop-central123', 'Dog', 'Lhasa Apso', 'Golden Brown', 1, 'years', '1761619043_d47942fb7b15.jpg', '0f518dd45b746825a26f', 'safe', '2025-10-28 02:37:23', NULL, NULL, NULL, NULL),
(18, 2, 'asdasd', 'Dog', 'asdasd', 'asdasda', 1, 'years', '1761619537_ea2cb8dcaf94.jpg', '73b4fa3462a4afa42b3c', 'safe', '2025-10-28 02:45:37', NULL, NULL, NULL, NULL),
(19, 2, 'Cholo', 'Dog', 'Lhasa Apso', 'Black', 4, 'years', '1761620180_defa835a01ce.jpeg', '78bb93954735e7c8809b', 'safe', '2025-10-28 02:56:20', NULL, NULL, NULL, NULL),
(20, 2, 'Cholo Loco 2', 'Dog', 'Lhasa Apso', 'Black', 4, 'years', '1761620399_a28efae0779e.jpeg', '9e90563a76a31fbaee17', 'safe', '2025-10-28 02:59:59', NULL, NULL, NULL, NULL),
(21, 2, 'mi dog 1', 'Dog', 'Dachshund / Pembroke Welsh Corgi', 'Golden Brown', 12, 'years', '1761753912_f61e762a9592.jpeg', '49505ae12f907beb9871', 'safe', '2025-10-29 16:05:12', NULL, 'Male', NULL, NULL),
(27, 5, 'midog4', 'Dog', 'Labrador Retriever / Shih Tzu', 'Golden Brown', 12, 'years', '1761795248_a6718c6b44a7.jpeg', '74310bf847a5c9fdcd94', 'safe', '2025-10-30 03:34:08', NULL, 'Male', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `scans`
--

CREATE TABLE `scans` (
  `scan_id` int(11) NOT NULL,
  `pet_id` int(11) DEFAULT NULL,
  `scanner_info` varchar(255) DEFAULT NULL,
  `location_lat` decimal(10,8) DEFAULT NULL,
  `location_lng` decimal(11,8) DEFAULT NULL,
  `scanned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scans`
--

INSERT INTO `scans` (`scan_id`, `pet_id`, `scanner_info`, `location_lat`, `location_lng`, `scanned_at`) VALUES
(1, 8, NULL, NULL, NULL, '2025-10-27 15:25:23'),
(2, 2, NULL, NULL, NULL, '2025-10-27 15:32:03'),
(3, 19, NULL, NULL, NULL, '2025-10-28 02:56:49'),
(4, 20, NULL, NULL, NULL, '2025-10-28 03:00:32'),
(5, 20, NULL, NULL, NULL, '2025-10-28 10:55:20'),
(6, 20, NULL, NULL, NULL, '2025-10-28 10:59:56'),
(8, 9, NULL, NULL, NULL, '2025-10-29 14:30:49'),
(9, 9, NULL, NULL, NULL, '2025-10-29 14:32:22'),
(10, 9, NULL, NULL, NULL, '2025-10-29 14:34:13'),
(11, 9, NULL, NULL, NULL, '2025-10-29 14:35:31'),
(12, 9, NULL, NULL, NULL, '2025-10-29 14:39:08'),
(13, 9, NULL, NULL, NULL, '2025-10-29 14:43:26'),
(14, 9, NULL, NULL, NULL, '2025-10-29 14:43:37'),
(15, 9, NULL, NULL, NULL, '2025-10-29 14:43:59'),
(16, 9, NULL, NULL, NULL, '2025-10-29 14:44:15'),
(17, 9, NULL, NULL, NULL, '2025-10-29 14:44:31'),
(18, 9, NULL, NULL, NULL, '2025-10-29 14:45:59'),
(19, 9, NULL, NULL, NULL, '2025-10-29 14:46:10'),
(20, 9, NULL, NULL, NULL, '2025-10-29 14:46:53'),
(21, 9, NULL, NULL, NULL, '2025-10-29 14:47:48'),
(22, 9, NULL, NULL, NULL, '2025-10-29 14:48:01'),
(23, 9, NULL, NULL, NULL, '2025-10-29 14:48:07'),
(24, 9, NULL, NULL, NULL, '2025-10-29 14:49:10'),
(25, 9, NULL, NULL, NULL, '2025-10-29 14:49:41'),
(26, 9, NULL, NULL, NULL, '2025-10-29 14:49:50'),
(27, 9, NULL, NULL, NULL, '2025-10-29 14:55:39'),
(28, 9, NULL, NULL, NULL, '2025-10-29 14:56:36'),
(29, 9, NULL, NULL, NULL, '2025-10-29 14:56:49'),
(30, 9, NULL, NULL, NULL, '2025-10-29 14:57:28'),
(31, 9, NULL, NULL, NULL, '2025-10-29 14:57:44'),
(32, 9, NULL, NULL, NULL, '2025-10-29 14:57:53'),
(33, 9, NULL, NULL, NULL, '2025-10-29 14:58:39'),
(34, 9, NULL, NULL, NULL, '2025-10-29 14:58:48'),
(35, 9, NULL, NULL, NULL, '2025-10-29 14:58:58'),
(36, 9, NULL, NULL, NULL, '2025-10-29 14:59:34'),
(37, 9, NULL, NULL, NULL, '2025-10-29 14:59:42'),
(38, 9, NULL, NULL, NULL, '2025-10-29 14:59:56'),
(39, 9, NULL, NULL, NULL, '2025-10-29 15:00:03'),
(40, 9, NULL, NULL, NULL, '2025-10-29 15:00:08'),
(41, 9, NULL, NULL, NULL, '2025-10-29 15:00:44'),
(42, 9, NULL, NULL, NULL, '2025-10-29 15:00:52'),
(43, 9, NULL, NULL, NULL, '2025-10-29 15:02:29'),
(44, 9, NULL, NULL, NULL, '2025-10-29 15:02:40'),
(45, 9, NULL, NULL, NULL, '2025-10-29 15:03:20'),
(46, 9, NULL, NULL, NULL, '2025-10-29 15:03:36'),
(47, 9, NULL, NULL, NULL, '2025-10-29 15:03:55'),
(48, 9, NULL, NULL, NULL, '2025-10-29 15:04:00'),
(49, 9, NULL, NULL, NULL, '2025-10-29 15:04:09'),
(50, 9, NULL, NULL, NULL, '2025-10-29 15:04:10'),
(51, 9, NULL, NULL, NULL, '2025-10-29 15:04:26'),
(52, 9, NULL, NULL, NULL, '2025-10-29 15:04:34'),
(53, 9, NULL, NULL, NULL, '2025-10-29 15:05:00'),
(54, 9, NULL, NULL, NULL, '2025-10-29 15:05:07'),
(55, 9, NULL, NULL, NULL, '2025-10-29 15:05:49'),
(56, 9, NULL, NULL, NULL, '2025-10-29 15:06:03'),
(57, 9, NULL, NULL, NULL, '2025-10-29 15:06:12'),
(58, 9, NULL, NULL, NULL, '2025-10-29 15:06:20'),
(59, 9, NULL, NULL, NULL, '2025-10-29 15:06:24'),
(60, 9, NULL, NULL, NULL, '2025-10-29 15:07:12'),
(61, 9, NULL, NULL, NULL, '2025-10-29 15:07:12'),
(62, 9, NULL, NULL, NULL, '2025-10-29 15:07:22'),
(63, 9, NULL, NULL, NULL, '2025-10-29 15:07:26'),
(64, 9, NULL, NULL, NULL, '2025-10-29 15:07:31'),
(65, 9, NULL, NULL, NULL, '2025-10-29 15:07:35'),
(66, 9, NULL, NULL, NULL, '2025-10-29 15:07:44'),
(67, 9, NULL, NULL, NULL, '2025-10-29 15:08:32'),
(68, 9, NULL, NULL, NULL, '2025-10-29 15:08:56'),
(69, 9, NULL, NULL, NULL, '2025-10-29 15:09:34'),
(70, 9, NULL, NULL, NULL, '2025-10-29 15:09:45'),
(71, 9, NULL, NULL, NULL, '2025-10-29 15:09:59'),
(72, 9, NULL, NULL, NULL, '2025-10-29 15:10:13'),
(73, 9, NULL, NULL, NULL, '2025-10-29 15:10:20'),
(74, 9, NULL, NULL, NULL, '2025-10-29 15:10:25'),
(75, 9, NULL, NULL, NULL, '2025-10-29 15:10:35'),
(76, 9, NULL, NULL, NULL, '2025-10-29 15:10:42'),
(77, 9, NULL, NULL, NULL, '2025-10-29 15:11:09'),
(78, 9, NULL, NULL, NULL, '2025-10-29 15:11:16'),
(79, 9, NULL, NULL, NULL, '2025-10-29 15:11:30'),
(80, 9, NULL, NULL, NULL, '2025-10-29 15:11:38'),
(81, 9, NULL, NULL, NULL, '2025-10-29 15:11:45'),
(82, 9, NULL, NULL, NULL, '2025-10-29 15:12:00'),
(83, 9, NULL, NULL, NULL, '2025-10-29 15:12:21'),
(84, 9, NULL, NULL, NULL, '2025-10-29 15:12:28'),
(85, 9, NULL, NULL, NULL, '2025-10-29 15:12:42'),
(86, 9, NULL, NULL, NULL, '2025-10-29 15:13:12'),
(87, 9, NULL, NULL, NULL, '2025-10-29 15:13:23'),
(88, 9, NULL, NULL, NULL, '2025-10-29 15:13:46'),
(89, 9, NULL, NULL, NULL, '2025-10-29 15:14:10'),
(90, 9, NULL, NULL, NULL, '2025-10-29 15:14:15'),
(91, 9, NULL, NULL, NULL, '2025-10-29 15:14:53'),
(92, 9, NULL, NULL, NULL, '2025-10-29 15:15:25'),
(93, 9, NULL, NULL, NULL, '2025-10-29 15:16:56'),
(94, 9, NULL, NULL, NULL, '2025-10-29 15:17:09'),
(95, 9, NULL, NULL, NULL, '2025-10-29 15:17:19'),
(96, 9, NULL, NULL, NULL, '2025-10-29 15:18:55'),
(97, 9, NULL, NULL, NULL, '2025-10-29 15:19:01'),
(98, 9, NULL, NULL, NULL, '2025-10-29 15:19:04'),
(99, 9, NULL, NULL, NULL, '2025-10-29 15:19:10'),
(100, 9, NULL, NULL, NULL, '2025-10-29 15:20:05'),
(101, 9, NULL, NULL, NULL, '2025-10-29 15:21:30'),
(102, 9, NULL, NULL, NULL, '2025-10-29 15:21:59'),
(103, 9, NULL, NULL, NULL, '2025-10-29 15:22:05'),
(104, 9, NULL, NULL, NULL, '2025-10-29 15:22:13'),
(105, 9, NULL, NULL, NULL, '2025-10-29 15:22:40'),
(106, 9, NULL, NULL, NULL, '2025-10-29 15:23:15'),
(107, 9, NULL, NULL, NULL, '2025-10-29 15:24:32'),
(108, 9, NULL, NULL, NULL, '2025-10-29 15:25:30'),
(109, 9, NULL, NULL, NULL, '2025-10-29 15:25:48'),
(110, 9, NULL, NULL, NULL, '2025-10-29 15:27:01'),
(111, 9, NULL, NULL, NULL, '2025-10-29 15:27:42'),
(112, 9, NULL, NULL, NULL, '2025-10-29 15:27:50'),
(113, 9, NULL, NULL, NULL, '2025-10-29 15:28:09'),
(114, 9, NULL, NULL, NULL, '2025-10-29 15:29:02'),
(115, 9, NULL, NULL, NULL, '2025-10-29 15:43:51'),
(116, 9, NULL, NULL, NULL, '2025-10-29 15:45:12'),
(117, 9, NULL, NULL, NULL, '2025-10-29 15:46:04'),
(118, 9, NULL, NULL, NULL, '2025-10-29 15:46:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `found_reports`
--
ALTER TABLE `found_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `pet_id` (`pet_id`);

--
-- Indexes for table `owners`
--
ALTER TABLE `owners`
  ADD PRIMARY KEY (`owner_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`pet_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `scans`
--
ALTER TABLE `scans`
  ADD PRIMARY KEY (`scan_id`),
  ADD KEY `pet_id` (`pet_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `found_reports`
--
ALTER TABLE `found_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `owners`
--
ALTER TABLE `owners`
  MODIFY `owner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `pet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `scans`
--
ALTER TABLE `scans`
  MODIFY `scan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `found_reports`
--
ALTER TABLE `found_reports`
  ADD CONSTRAINT `found_reports_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE;

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`owner_id`) ON DELETE CASCADE;

--
-- Constraints for table `scans`
--
ALTER TABLE `scans`
  ADD CONSTRAINT `scans_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
