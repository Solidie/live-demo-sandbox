-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Jul 08, 2024 at 06:09 PM
-- Server version: 5.7.39
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sandbox`
--

-- --------------------------------------------------------

--
-- Table structure for table `wp_slds_sandboxes`
--

CREATE TABLE IF NOT EXISTS `wp_slds_sandboxes` (
  `sandbox_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `host_id` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `site_title` varchar(1000) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `site_path` varchar(1000) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `site_id` mediumint(8) UNSIGNED NOT NULL,
  `user_ip` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_hit` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`sandbox_id`),
  KEY `host_id` (`host_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
