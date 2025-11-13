CREATE DATABASE IF NOT EXISTS `pawsitive_patrol` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pawsitive_patrol`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- First create owners table (no dependencies)
CREATE TABLE IF NOT EXISTS `owners` (
  `owner_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`owner_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Then create pets table (depends on owners)
CREATE TABLE IF NOT EXISTS `pets` (
  `pet_id` int(11) NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`pet_id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`owner_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Then create scans table (depends on pets)
CREATE TABLE IF NOT EXISTS `scans` (
  `scan_id` int(11) NOT NULL AUTO_INCREMENT,
  `pet_id` int(11) DEFAULT NULL,
  `scanner_info` varchar(255) DEFAULT NULL,
  `location_lat` decimal(10,8) DEFAULT NULL,
  `location_lng` decimal(11,8) DEFAULT NULL,
  `scanned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`scan_id`),
  KEY `pet_id` (`pet_id`),
  CONSTRAINT `scans_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Finally create found_reports table (depends on pets)
CREATE TABLE IF NOT EXISTS `found_reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `pet_id` int(11) DEFAULT NULL,
  `finder_name` varchar(100) DEFAULT NULL,
  `finder_contact` varchar(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `reported_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`report_id`),
  KEY `pet_id` (`pet_id`),
  CONSTRAINT `found_reports_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert test user
INSERT INTO `owners` (`name`, `email`, `password`, `phone`) VALUES
('Test User', 'test@example.com', '$2y$10$9sEtyFerRvtlQcPVBrFQF.ETZaUj2j/xVdpljDDygThBHAXbTBO.u', '123-456-7890');

COMMIT;

-- Ensure `gender` column exists on installations that may lack it
ALTER TABLE `pets` ADD COLUMN IF NOT EXISTS `gender` varchar(20) DEFAULT NULL;
-- Ensure description and emergency_notes exist
ALTER TABLE `pets` ADD COLUMN IF NOT EXISTS `description` TEXT DEFAULT NULL;
ALTER TABLE `pets` ADD COLUMN IF NOT EXISTS `emergency_notes` TEXT DEFAULT NULL;
