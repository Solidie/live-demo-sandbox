-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Jun 24, 2024 at 10:33 AM
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
-- Table structure for table `wp_slds_hits`
--

CREATE TABLE IF NOT EXISTS `wp_slds_hits` (
  `hit_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sandbox_id` mediumint(8) UNSIGNED NOT NULL,
  `the_url` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `hit_count` bigint(20) NOT NULL,
  PRIMARY KEY (`hit_id`),
  KEY `sandbox_id` (`sandbox_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wp_slds_sandboxes`
--

CREATE TABLE IF NOT EXISTS `wp_slds_sandboxes` (
  `sandbox_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_title` varchar(400) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `site_url` varchar(400) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `site_id` mediumint(8) UNSIGNED NOT NULL,
  `user_ip_address` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`sandbox_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
