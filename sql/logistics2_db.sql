-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Sep 18, 2025 at 07:23 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `logistics2_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `module` varchar(32) NOT NULL,
  `action` varchar(64) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `user` varchar(64) NOT NULL,
  `details` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `module`, `action`, `record_id`, `user`, `details`, `timestamp`) VALUES
(1, 'TCAO', 'submitted', 8, 'admin', NULL, '2025-08-30 19:11:09'),
(2, 'VRDS', 'complete_dispatch', 1, 'admin', NULL, '2025-08-30 21:37:44'),
(3, 'TCAO', 'deleted', 8, 'unknown', NULL, '2025-09-01 22:13:14'),
(4, 'DTP', 'delete_trip', 1, 'unknown', NULL, '2025-09-01 22:13:19'),
(5, 'FVM', 'delete_vehicle', 23, 'unknown', NULL, '2025-09-01 22:13:53'),
(6, 'FVM', 'delete_vehicle', 22, 'unknown', NULL, '2025-09-01 22:13:57'),
(7, 'FVM', 'delete_vehicle', 21, 'unknown', NULL, '2025-09-01 22:14:01'),
(8, 'VRDS', 'delete_dispatch', 20, 'unknown', NULL, '2025-09-02 00:14:19'),
(9, 'TCAO', 'deleted', 10, 'unknown', NULL, '2025-09-02 01:08:39'),
(10, 'VRDS', 'approve_dispatch', 37, 'unknown', NULL, '2025-09-02 02:07:54'),
(11, 'VRDS', 'complete_dispatch', 37, 'unknown', NULL, '2025-09-02 02:08:07'),
(12, 'TCAO', 'submitted', 40, 'unknown', NULL, '2025-09-02 02:22:22'),
(13, 'DTP', 'add_trip', 33, 'unknown', NULL, '2025-09-02 02:32:38'),
(14, 'VRDS', 'request_vehicle', 87, 'unknown', NULL, '2025-09-02 02:39:47'),
(15, 'VRDS', 'approve_dispatch', 38, 'unknown', NULL, '2025-09-02 02:40:45'),
(16, 'DTP', 'add_trip', 34, 'unknown', NULL, '2025-09-02 03:24:20'),
(17, 'FVM', 'edit_vehicle', 18, 'unknown', NULL, '2025-09-02 10:21:11'),
(18, 'FVM', 'edit_vehicle', 4, 'unknown', NULL, '2025-09-02 10:47:34'),
(19, 'FVM', 'edit_vehicle', 24, 'unknown', NULL, '2025-09-02 10:47:38'),
(20, 'FVM', 'edit_vehicle', 25, 'unknown', NULL, '2025-09-02 10:47:42'),
(21, 'FVM', 'edit_vehicle', 26, 'unknown', NULL, '2025-09-02 10:47:47'),
(22, 'VRDS', 'request_vehicle', 88, 'unknown', NULL, '2025-09-02 12:01:32'),
(23, 'VRDS', 'request_vehicle', 89, 'unknown', NULL, '2025-09-02 12:39:31'),
(24, 'Authentication', 'OTP Sent', 1, 'S250701', 'OTP sent for login', '2025-09-05 23:24:05'),
(25, 'Authentication', 'Successful Login', 1, 'S250701', 'User successfully logged in after OTP verification', '2025-09-05 23:24:27'),
(26, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-06 16:10:19'),
(27, 'FVM', 'adjust_maintenance', 2, 'admin', NULL, '2025-09-06 17:21:44'),
(28, 'FVM', 'adjust_maintenance', 2, 'admin', NULL, '2025-09-06 17:21:55'),
(29, 'FVM', 'adjust_maintenance', 3, 'admin', NULL, '2025-09-06 17:22:06'),
(30, 'FVM', 'adjust_maintenance', 3, 'admin', NULL, '2025-09-06 17:22:21'),
(31, 'FVM', 'adjust_maintenance', 4, 'admin', NULL, '2025-09-06 17:22:59'),
(32, 'FVM', 'adjust_maintenance', 5, 'admin', NULL, '2025-09-06 17:23:11'),
(33, 'FVM', 'adjust_maintenance', 24, 'admin', NULL, '2025-09-06 17:23:20'),
(34, 'FVM', 'adjust_maintenance', 25, 'admin', NULL, '2025-09-06 17:23:29'),
(35, 'FVM', 'adjust_maintenance', 26, 'admin', NULL, '2025-09-06 17:23:39'),
(36, 'FVM', 'edit_vehicle', 4, 'admin', NULL, '2025-09-06 17:28:58'),
(37, 'FVM', 'edit_vehicle', 4, 'admin', NULL, '2025-09-06 17:31:46'),
(38, 'FVM', 'adjust_maintenance', 4, 'admin', NULL, '2025-09-06 17:51:56'),
(39, 'FVM', 'adjust_maintenance', 4, 'admin', NULL, '2025-09-06 17:52:11'),
(40, 'FVM', 'adjust_maintenance', 4, 'admin', NULL, '2025-09-06 17:52:50'),
(41, 'FVM', 'adjust_maintenance', 4, 'admin', NULL, '2025-09-06 17:53:09'),
(42, 'FVM', 'adjust_maintenance', 4, 'admin', NULL, '2025-09-06 17:53:28'),
(43, 'FVM', 'edit_vehicle', 2, 'admin', NULL, '2025-09-06 18:32:45'),
(44, 'FVM', 'edit_vehicle', 3, 'admin', NULL, '2025-09-06 18:32:54'),
(45, 'FVM', 'edit_vehicle', 5, 'admin', NULL, '2025-09-06 18:33:08'),
(46, 'FVM', 'edit_vehicle', 24, 'admin', NULL, '2025-09-06 18:33:16'),
(47, 'FVM', 'edit_vehicle', 25, 'admin', NULL, '2025-09-06 18:33:22'),
(48, 'FVM', 'edit_vehicle', 26, 'admin', NULL, '2025-09-06 18:33:29'),
(49, 'FVM', 'edit_vehicle', 4, 'admin', NULL, '2025-09-06 18:33:47'),
(50, 'FVM', 'adjust_maintenance', 4, 'admin', NULL, '2025-09-06 18:34:29'),
(51, 'FVM', 'edit_vehicle', 4, 'admin', NULL, '2025-09-06 18:35:02'),
(52, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 18:43:11'),
(53, 'FVM', 'add_vehicle', 28, 'admin', NULL, '2025-09-06 18:46:25'),
(54, 'FVM', 'delete_vehicle', 28, 'admin', NULL, '2025-09-06 18:46:33'),
(55, 'VRDS', 'delete_dispatch', 38, 'admin', NULL, '2025-09-06 18:54:15'),
(56, 'VRDS', 'approve_dispatch', 39, 'admin', NULL, '2025-09-06 18:59:14'),
(57, 'FVM', 'add_vehicle', 29, 'admin', NULL, '2025-09-06 19:03:12'),
(58, 'FVM', 'delete_vehicle', 29, 'admin', NULL, '2025-09-06 19:03:37'),
(59, 'FVM', 'edit_vehicle', 20, 'admin', NULL, '2025-09-06 19:03:48'),
(60, 'VRDS', 'complete_dispatch', 39, 'admin', NULL, '2025-09-06 19:05:23'),
(61, 'FVM', 'add_vehicle', 30, 'admin', NULL, '2025-09-06 19:11:57'),
(62, 'FVM', 'delete_vehicle', 30, 'admin', NULL, '2025-09-06 19:12:06'),
(63, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 19:40:34'),
(64, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 19:44:05'),
(65, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 20:19:51'),
(66, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 20:22:05'),
(67, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 20:22:19'),
(68, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 20:25:40'),
(69, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 20:27:18'),
(70, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 20:29:50'),
(71, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 20:31:01'),
(72, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 20:36:17'),
(73, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 20:38:38'),
(74, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 20:40:07'),
(75, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 20:43:41'),
(76, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 20:44:12'),
(77, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 20:46:36'),
(78, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 20:47:44'),
(79, 'FVM', 'edit_vehicle', 2, 'admin', NULL, '2025-09-06 20:48:57'),
(80, 'FVM', 'edit_vehicle', 3, 'admin', NULL, '2025-09-06 20:49:17'),
(81, 'FVM', 'edit_vehicle', 4, 'admin', NULL, '2025-09-06 20:49:32'),
(82, 'FVM', 'adjust_maintenance', 1, 'admin', NULL, '2025-09-06 20:51:17'),
(83, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 20:51:34'),
(84, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-06 20:51:46'),
(85, 'FVM', 'edit_vehicle', 4, 'admin', NULL, '2025-09-06 20:52:07'),
(86, 'FVM', 'edit_vehicle', 5, 'admin', NULL, '2025-09-06 20:52:29'),
(87, 'FVM', 'edit_vehicle', 18, 'admin', NULL, '2025-09-06 20:52:52'),
(88, 'FVM', 'edit_vehicle', 20, 'admin', NULL, '2025-09-06 20:53:02'),
(89, 'VRDS', 'request_vehicle', 90, 'admin', NULL, '2025-09-06 20:58:28'),
(90, 'VRDS', 'request_vehicle', 91, 'admin', NULL, '2025-09-06 21:08:53'),
(91, 'VRDS', 'approve_dispatch', 40, 'admin', NULL, '2025-09-06 21:22:04'),
(92, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-06 22:23:26'),
(93, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-06 22:38:05'),
(94, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-06 22:38:14'),
(95, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-06 23:42:17'),
(96, 'VRDS', 'request_vehicle', 92, 'admin', NULL, '2025-09-07 00:36:25'),
(97, 'VRDS', 'approve_dispatch', 41, 'admin', NULL, '2025-09-07 01:38:07'),
(98, 'VRDS', 'complete_dispatch', 41, 'admin', NULL, '2025-09-07 01:38:28'),
(99, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-08 22:22:09'),
(100, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-08 22:23:13'),
(101, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-08 22:23:30'),
(102, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-08 22:59:06'),
(103, 'FVM', 'clear_maintenance_logs', NULL, 'admin', NULL, '2025-09-08 22:59:53'),
(104, 'FVM', 'adjust_maintenance', 1, 'admin', NULL, '2025-09-08 23:00:11'),
(105, 'Authentication', 'OTP Sent', 1, 'S250701', 'OTP sent for login', '2025-09-09 00:50:36'),
(106, 'Authentication', 'Successful Login', 1, 'S250701', 'User successfully logged in after OTP verification', '2025-09-09 00:52:06'),
(107, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-09 00:52:42'),
(108, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-11 19:41:56'),
(109, 'Authentication', 'OTP Sent', 1, 'S250701', 'OTP sent for login', '2025-09-14 20:26:02'),
(110, 'Authentication', 'Successful Login', 1, 'S250701', 'User successfully logged in after OTP verification', '2025-09-14 20:26:27'),
(111, 'FVM', 'delete_vehicle', 20, 'admin', NULL, '2025-09-14 20:50:41'),
(112, 'FVM', 'delete_vehicle', 20, 'admin', NULL, '2025-09-14 20:50:47'),
(113, 'FVM', 'delete_vehicle', 20, 'admin', NULL, '2025-09-14 20:51:11'),
(114, 'FVM', 'delete_vehicle', 26, 'admin', NULL, '2025-09-14 20:51:31'),
(115, 'FVM', 'edit_vehicle', 25, 'admin', NULL, '2025-09-14 20:51:40'),
(116, 'FVM', 'delete_vehicle', 20, 'admin', NULL, '2025-09-14 21:17:33'),
(117, 'FVM', 'delete_vehicle', 18, 'admin', NULL, '2025-09-14 21:17:48'),
(118, 'FVM', 'delete_vehicle', 25, 'admin', NULL, '2025-09-14 21:17:55'),
(119, 'FVM', 'delete_vehicle', 24, 'admin', NULL, '2025-09-14 21:18:01'),
(120, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-14 21:19:17'),
(121, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-14 21:19:45'),
(122, 'FVM', 'edit_vehicle', 2, 'admin', NULL, '2025-09-14 21:20:31'),
(123, 'FVM', 'edit_vehicle', 3, 'admin', NULL, '2025-09-14 21:22:11'),
(124, 'FVM', 'edit_vehicle', 3, 'admin', NULL, '2025-09-14 21:22:44'),
(125, 'FVM', 'edit_vehicle', 4, 'admin', NULL, '2025-09-14 21:25:58'),
(126, 'FVM', 'edit_vehicle', 5, 'admin', NULL, '2025-09-14 21:26:50'),
(127, 'TCAO', 'deleted', 11, 'admin', NULL, '2025-09-14 23:42:52'),
(128, 'TCAO', 'deleted', 12, 'admin', NULL, '2025-09-14 23:42:52'),
(129, 'TCAO', 'deleted', 13, 'admin', NULL, '2025-09-14 23:42:52'),
(130, 'TCAO', 'deleted', 14, 'admin', NULL, '2025-09-14 23:42:52'),
(131, 'TCAO', 'deleted', 15, 'admin', NULL, '2025-09-14 23:42:52'),
(132, 'TCAO', 'deleted', 16, 'admin', NULL, '2025-09-14 23:42:52'),
(133, 'TCAO', 'deleted', 17, 'admin', NULL, '2025-09-14 23:42:52'),
(134, 'TCAO', 'deleted', 19, 'admin', NULL, '2025-09-14 23:42:52'),
(135, 'TCAO', 'deleted', 20, 'admin', NULL, '2025-09-14 23:42:52'),
(136, 'TCAO', 'deleted', 21, 'admin', NULL, '2025-09-14 23:42:52'),
(137, 'TCAO', 'deleted', 23, 'admin', NULL, '2025-09-14 23:42:52'),
(138, 'TCAO', 'deleted', 24, 'admin', NULL, '2025-09-14 23:42:52'),
(139, 'TCAO', 'deleted', 25, 'admin', NULL, '2025-09-14 23:42:52'),
(140, 'TCAO', 'deleted', 26, 'admin', NULL, '2025-09-14 23:42:52'),
(141, 'TCAO', 'deleted', 27, 'admin', NULL, '2025-09-14 23:42:52'),
(142, 'TCAO', 'deleted', 28, 'admin', NULL, '2025-09-14 23:42:52'),
(143, 'TCAO', 'deleted', 29, 'admin', NULL, '2025-09-14 23:42:52'),
(144, 'TCAO', 'deleted', 30, 'admin', NULL, '2025-09-14 23:42:52'),
(145, 'TCAO', 'deleted', 31, 'admin', NULL, '2025-09-14 23:42:52'),
(146, 'TCAO', 'deleted', 32, 'admin', NULL, '2025-09-14 23:42:52'),
(147, 'TCAO', 'deleted', 33, 'admin', NULL, '2025-09-14 23:42:52'),
(148, 'TCAO', 'deleted', 34, 'admin', NULL, '2025-09-14 23:42:52'),
(149, 'TCAO', 'deleted', 35, 'admin', NULL, '2025-09-14 23:42:52'),
(150, 'TCAO', 'deleted', 36, 'admin', NULL, '2025-09-14 23:42:52'),
(151, 'TCAO', 'deleted', 37, 'admin', NULL, '2025-09-14 23:42:52'),
(152, 'TCAO', 'deleted', 38, 'admin', NULL, '2025-09-14 23:42:52'),
(153, 'TCAO', 'deleted', 39, 'admin', NULL, '2025-09-14 23:42:52'),
(154, 'TCAO', 'deleted', 40, 'admin', NULL, '2025-09-14 23:42:52'),
(155, 'DTP', 'delete_trip', 2, 'admin', NULL, '2025-09-14 23:43:40'),
(156, 'DTP', 'delete_trip', 19, 'admin', NULL, '2025-09-14 23:43:48'),
(157, 'DTP', 'delete_trip', 20, 'admin', NULL, '2025-09-14 23:43:52'),
(158, 'DTP', 'delete_trip', 21, 'admin', NULL, '2025-09-14 23:43:55'),
(159, 'DTP', 'delete_trip', 16, 'admin', NULL, '2025-09-14 23:43:59'),
(160, 'DTP', 'delete_trip', 25, 'admin', NULL, '2025-09-14 23:44:03'),
(161, 'DTP', 'delete_trip', 30, 'admin', NULL, '2025-09-14 23:44:10'),
(162, 'DTP', 'delete_trip', 31, 'admin', NULL, '2025-09-14 23:44:13'),
(163, 'DTP', 'delete_trip', 3, 'admin', NULL, '2025-09-14 23:44:17'),
(164, 'DTP', 'delete_trip', 5, 'admin', NULL, '2025-09-14 23:44:20'),
(165, 'DTP', 'delete_trip', 9, 'admin', NULL, '2025-09-14 23:44:24'),
(166, 'DTP', 'delete_trip', 11, 'admin', NULL, '2025-09-14 23:44:27'),
(167, 'DTP', 'delete_trip', 13, 'admin', NULL, '2025-09-14 23:49:24'),
(168, 'DTP', 'clear_trip_logs', NULL, 'admin', NULL, '2025-09-15 00:03:20'),
(169, 'VRDS', 'complete_dispatch', 40, 'admin', NULL, '2025-09-15 00:03:34'),
(170, 'VRDS', 'clear_dispatch_logs', NULL, 'admin', NULL, '2025-09-15 00:11:22'),
(171, 'FVM', 'adjust_maintenance', 4, 'admin', NULL, '2025-09-15 02:28:49'),
(172, 'FVM', 'adjust_maintenance', 5, 'admin', NULL, '2025-09-15 02:29:00'),
(173, 'FVM', 'edit_vehicle', 4, 'admin', NULL, '2025-09-15 03:42:17'),
(174, 'FVM', 'edit_vehicle', 5, 'admin', NULL, '2025-09-15 03:42:23'),
(175, 'FVM', 'edit_vehicle', 5, 'admin', NULL, '2025-09-15 03:42:30'),
(176, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-16 21:29:41'),
(177, 'VRDS', 'request_vehicle', 93, 'admin', NULL, '2025-09-17 00:14:07'),
(178, 'VRDS', 'approve_dispatch', 42, 'admin', NULL, '2025-09-17 00:14:48'),
(179, 'VRDS', 'complete_dispatch', 42, 'admin', NULL, '2025-09-17 00:15:30'),
(180, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-17 12:57:41'),
(181, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-17 20:29:21'),
(182, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-17 20:30:29'),
(183, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-17 20:41:46'),
(184, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-17 20:43:34'),
(185, 'Authentication', 'OTP Sent', 1, 'S250701', 'OTP sent for login', '2025-09-17 20:43:46'),
(186, 'Authentication', 'Successful Login', 1, 'S250701', 'User successfully logged in after OTP verification', '2025-09-17 20:44:32'),
(187, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-17 21:39:44'),
(188, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-17 21:39:59'),
(189, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-17 22:29:26'),
(190, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-17 22:37:36'),
(191, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-17 22:44:55'),
(192, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-17 22:49:41'),
(193, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-17 23:07:40'),
(194, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-17 23:08:08'),
(195, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 00:01:08'),
(196, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-18 00:10:37'),
(197, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 00:10:41'),
(198, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 00:19:43'),
(199, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 00:36:42'),
(200, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 00:59:20'),
(201, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-18 01:10:31'),
(202, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 01:29:16'),
(203, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 02:07:12'),
(204, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 02:26:04'),
(205, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 02:31:58'),
(206, 'FVM', 'set_maintenance', 1, 'admin', NULL, '2025-09-18 02:36:23'),
(207, 'FVM', 'clear_maintenance_logs', NULL, 'admin', NULL, '2025-09-18 02:37:10'),
(208, 'FVM', 'set_maintenance', 1, 'admin', NULL, '2025-09-18 02:37:28'),
(209, 'FVM', 'set_maintenance', 2, 'admin', NULL, '2025-09-18 02:37:50'),
(210, 'FVM', 'set_maintenance', 3, 'admin', NULL, '2025-09-18 02:38:03'),
(211, 'FVM', 'set_maintenance', 4, 'admin', NULL, '2025-09-18 02:38:19'),
(212, 'FVM', 'set_maintenance', 5, 'admin', NULL, '2025-09-18 02:38:29'),
(213, 'FVM', 'set_maintenance', 1, 'admin', NULL, '2025-09-18 02:38:47'),
(214, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 02:45:13'),
(215, 'FVM', 'clear_maintenance_logs', NULL, 'admin', NULL, '2025-09-18 02:46:46'),
(216, 'FVM', 'set_maintenance', 1, 'admin', NULL, '2025-09-18 02:47:01'),
(217, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 02:53:25'),
(218, 'FVM', 'set_maintenance', 2, 'admin', NULL, '2025-09-18 02:53:59'),
(219, 'FVM', 'set_maintenance', 3, 'admin', NULL, '2025-09-18 02:54:19'),
(220, 'FVM', 'clear_maintenance_logs', NULL, 'admin', NULL, '2025-09-18 02:54:38'),
(221, 'FVM', 'set_maintenance', 1, 'admin', NULL, '2025-09-18 02:59:05'),
(222, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 03:08:50'),
(223, 'FVM', 'set_maintenance', 2, 'admin', NULL, '2025-09-18 03:09:24'),
(224, 'FVM', 'set_maintenance', 3, 'admin', NULL, '2025-09-18 03:09:43'),
(225, 'FVM', 'set_maintenance', 4, 'admin', NULL, '2025-09-18 03:10:06'),
(226, 'FVM', 'set_maintenance', 5, 'admin', NULL, '2025-09-18 03:10:25'),
(227, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-18 03:36:45'),
(228, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 03:42:06'),
(229, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-18 03:47:49'),
(230, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 03:47:59'),
(231, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-18 04:19:00'),
(232, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 04:55:41'),
(233, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-18 05:41:55'),
(234, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 08:13:26'),
(235, 'VRDS', 'request_vehicle', 94, 'admin', NULL, '2025-09-18 08:45:36'),
(236, 'FVM', 'edit_vehicle', 1, 'admin', NULL, '2025-09-18 08:46:52'),
(237, 'FVM', 'edit_vehicle', 5, 'admin', NULL, '2025-09-18 08:47:22'),
(238, 'VRDS', 'approve_dispatch', 43, 'admin', NULL, '2025-09-18 08:48:52'),
(239, 'VRDS', 'complete_dispatch', 43, 'admin', NULL, '2025-09-18 09:13:31'),
(240, 'VRDS', 'request_vehicle', 95, 'admin', NULL, '2025-09-18 09:15:34'),
(241, 'VRDS', 'approve_dispatch', 44, 'admin', NULL, '2025-09-18 09:15:55'),
(242, 'VRDS', 'request_vehicle', 96, 'admin', NULL, '2025-09-18 09:25:37'),
(243, 'FVM', 'edit_vehicle', 2, 'admin', NULL, '2025-09-18 09:26:22'),
(244, 'FVM', 'edit_vehicle', 4, 'admin', NULL, '2025-09-18 09:26:34'),
(245, 'FVM', 'edit_vehicle', 3, 'admin', NULL, '2025-09-18 09:26:44'),
(246, 'VRDS', 'approve_dispatch', 45, 'admin', NULL, '2025-09-18 09:27:13'),
(247, 'VRDS', 'complete_dispatch', 44, 'admin', NULL, '2025-09-18 09:29:42'),
(248, 'VRDS', 'complete_dispatch', 45, 'admin', NULL, '2025-09-18 09:29:54'),
(249, 'VRDS', 'request_vehicle', 97, 'admin', NULL, '2025-09-18 09:33:40'),
(250, 'VRDS', 'approve_dispatch', 46, 'admin', NULL, '2025-09-18 09:33:58'),
(251, 'VRDS', 'delete_dispatch', 42, 'admin', NULL, '2025-09-18 09:53:28'),
(252, 'VRDS', 'approve_dispatch', 47, 'admin', NULL, '2025-09-18 09:54:18'),
(253, 'VRDS', 'complete_dispatch', 47, 'admin', NULL, '2025-09-18 10:05:56'),
(254, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-18 10:30:25'),
(255, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 10:39:19'),
(256, 'VRDS', 'request_vehicle', 98, 'admin', NULL, '2025-09-18 10:49:13'),
(257, 'VRDS', 'request_vehicle', 99, 'admin', NULL, '2025-09-18 10:53:07'),
(258, 'VRDS', 'request_vehicle', 100, 'admin', NULL, '2025-09-18 11:00:37'),
(259, 'VRDS', 'request_vehicle', 101, 'admin', NULL, '2025-09-18 11:14:39'),
(260, 'VRDS', 'approve_dispatch', 48, 'admin', NULL, '2025-09-18 11:15:05'),
(261, 'VRDS', 'complete_dispatch', 48, 'admin', NULL, '2025-09-18 11:15:29'),
(262, 'VRDS', 'request_vehicle', 102, 'admin', NULL, '2025-09-18 11:29:38'),
(263, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 11:36:46'),
(264, 'DTP', 'add_trip', 35, 'admin', NULL, '2025-09-18 11:52:24'),
(265, 'DTP', 'add_trip', 36, 'admin', NULL, '2025-09-18 11:53:08'),
(266, 'TCAO', 'submitted', 41, 'admin', NULL, '2025-09-18 12:00:28'),
(267, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 12:42:36'),
(268, 'VRDS', 'request_vehicle', 103, 'admin', NULL, '2025-09-18 12:47:38'),
(269, 'VRDS', 'request_vehicle', 104, 'admin', NULL, '2025-09-18 12:48:49'),
(270, 'VRDS', 'approve_dispatch', 49, 'admin', NULL, '2025-09-18 12:49:19'),
(271, 'Authentication', 'Logout', 1, 'S250701', 'User logged out successfully', '2025-09-18 13:05:15'),
(272, 'Authentication', 'Login', 1, 'S250701', 'User logged in via trusted device', '2025-09-18 13:09:50');

-- --------------------------------------------------------

--
-- Table structure for table `dispatches`
--

CREATE TABLE `dispatches` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `officer_id` int(11) DEFAULT NULL,
  `dispatch_date` datetime NOT NULL,
  `return_date` datetime DEFAULT NULL,
  `status` enum('Ongoing','Completed','Cancelled') DEFAULT 'Ongoing',
  `origin` varchar(255) DEFAULT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `origin_lat` decimal(8,6) DEFAULT NULL,
  `origin_lon` decimal(9,6) DEFAULT NULL,
  `destination_lat` decimal(8,6) DEFAULT NULL,
  `destination_lon` decimal(9,6) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dispatches`
--

INSERT INTO `dispatches` (`id`, `request_id`, `vehicle_id`, `driver_id`, `officer_id`, `dispatch_date`, `return_date`, `status`, `origin`, `destination`, `origin_lat`, `origin_lon`, `destination_lat`, `destination_lon`, `purpose`, `notes`, `created_at`, `updated_at`) VALUES
(43, 94, 1, 1, 1, '2025-09-18 02:48:52', NULL, 'Completed', 'Warehouse North', 'Ninoy Aquino International Airport, Alegria, Buena Vida Townhomes, Parañaque District 2, Parañaque, Southern Manila District, Metro Manila, 1700, Philippines', NULL, NULL, NULL, NULL, 'Guest Transport', '', '2025-09-18 00:48:52', '2025-09-18 01:13:31'),
(44, 95, 1, 1, 1, '2025-09-18 03:15:55', NULL, 'Completed', 'Warehouse Legit', 'Soliera : Hotel and Restaurant', NULL, NULL, NULL, NULL, 'Supplies', '', '2025-09-18 01:15:55', '2025-09-18 01:29:42'),
(45, 96, 3, 20, 1, '2025-09-18 03:27:13', NULL, 'Completed', 'NAIA Airport Pickup', 'Soliera : Hotel and Restaurant', NULL, NULL, NULL, NULL, 'Guest Transport 1-2', '', '2025-09-18 01:27:13', '2025-09-18 01:29:54'),
(46, 97, 1, 1, 1, '2025-09-18 03:33:58', NULL, 'Ongoing', 'Warehouse North', 'Maintenance Shop A', 14.751000, 121.025400, 14.733507, 121.056591, 'Maintenance/Repair Run', '', '2025-09-18 01:33:58', '2025-09-18 01:33:58'),
(48, 101, 1, 20, 1, '2025-09-18 05:15:05', NULL, 'Completed', 'Warehouse North', 'Soliera : Hotel and Restaurant', 14.751000, 121.025400, 14.726544, 121.036862, 'Event Logistics', '', '2025-09-18 03:15:05', '2025-09-18 03:15:29'),
(49, 104, 2, 20, 1, '2025-09-18 06:49:19', NULL, 'Ongoing', 'Soliera : Hotel and Restaurant', 'Supplier B', 14.726544, 121.036862, 14.678258, 121.031576, 'Supplies Pickup', '', '2025-09-18 04:49:19', '2025-09-18 04:49:19');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
  `eid` varchar(100) NOT NULL,
  `driver_name` varchar(100) NOT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('Available','Dispatched','Inactive') DEFAULT 'Available',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `eid`, `driver_name`, `license_number`, `phone`, `email`, `status`, `created_at`) VALUES
(1, 'D25071', 'Juan Dela Cruz', 'PH-DL-2025-001', '09171234567', 'juan.delacruz@example.com', 'Available', '2025-08-29 02:49:13'),
(20, 'D250722', 'Jerome Adrian Ragas', 'A20-52-123456', NULL, 'jeromeadrianragas@gmail.com', 'Dispatched', '2025-09-15 03:56:24');

-- --------------------------------------------------------

--
-- Table structure for table `driver_trips`
--

CREATE TABLE `driver_trips` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `trip_date` date NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `distance_traveled` decimal(10,2) DEFAULT NULL,
  `fuel_consumed` decimal(10,2) DEFAULT NULL,
  `idle_time` int(11) DEFAULT NULL,
  `average_speed` decimal(10,2) DEFAULT NULL,
  `performance_score` decimal(5,2) DEFAULT NULL,
  `validation_status` enum('pending','valid','invalid') DEFAULT 'pending',
  `validation_message` text DEFAULT NULL,
  `supervisor_review_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `supervisor_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cargo_weight` decimal(10,2) DEFAULT 0.00,
  `vehicle_capacity` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver_trips`
--

INSERT INTO `driver_trips` (`id`, `driver_id`, `vehicle_id`, `trip_date`, `start_time`, `end_time`, `distance_traveled`, `fuel_consumed`, `idle_time`, `average_speed`, `performance_score`, `validation_status`, `validation_message`, `supervisor_review_status`, `supervisor_remarks`, `created_at`, `updated_at`, `cargo_weight`, `vehicle_capacity`) VALUES
(35, 20, 1, '2025-09-18', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 7.00, 1.20, 3, 0.64, 79.17, 'valid', NULL, 'pending', NULL, '2025-09-18 03:52:24', '2025-09-18 03:52:24', 120.00, 475.00),
(36, 1, 1, '2025-09-18', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 12.00, 4.00, 2, 0.63, 65.00, 'valid', NULL, 'pending', NULL, '2025-09-18 03:53:08', '2025-09-18 03:53:08', 200.00, 475.00);

-- --------------------------------------------------------

--
-- Table structure for table `fleet_vehicles`
--

CREATE TABLE `fleet_vehicles` (
  `id` int(11) NOT NULL,
  `vehicle_name` varchar(100) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `vehicle_type` enum('Truck','Van','Pickup','Car') DEFAULT NULL,
  `status` enum('Active','Under Maintenance','Inactive','Dispatched') DEFAULT 'Active',
  `weight_capacity` decimal(6,2) DEFAULT NULL,
  `fuel_capacity` decimal(6,2) DEFAULT NULL,
  `vehicle_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fleet_vehicles`
--

INSERT INTO `fleet_vehicles` (`id`, `vehicle_name`, `plate_number`, `vehicle_type`, `status`, `weight_capacity`, `fuel_capacity`, `vehicle_image`) VALUES
(1, 'Toyota Hilux', 'ABC-124', 'Pickup', 'Active', 475.00, 80.00, 'uploads/vehicle_1757162864_8262.png'),
(2, 'Mitsubishi L300', 'XYZ-456', 'Van', 'Dispatched', 1000.00, 55.00, 'uploads/vehicle_1757162937_8200.jpg'),
(3, 'Isuzu D-Max', 'LMN-789', 'Pickup', 'Active', 475.00, 76.00, 'uploads/vehicle_1757162957_9277.jpg'),
(4, 'Hyundai H100', 'JKL-321', 'Van', 'Active', 1090.00, 65.00, 'uploads/vehicle_1757162972_2521.jpg'),
(5, 'Ford Ranger', 'PQR-654', 'Pickup', 'Inactive', 985.00, 80.00, 'uploads/vehicle_1757163149_5973.png');

-- --------------------------------------------------------

--
-- Table structure for table `fleet_vehicle_logs`
--

CREATE TABLE `fleet_vehicle_logs` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `log_type` enum('maintenance','fuel') NOT NULL,
  `details` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fleet_vehicle_logs`
--

INSERT INTO `fleet_vehicle_logs` (`id`, `vehicle_id`, `log_type`, `details`, `created_at`) VALUES
(1, 1, 'fuel', 'Need Refill', '2025-08-28 17:07:58'),
(48, 1, 'maintenance', 'Battery scheduled for maintenance', '2025-09-18 02:59:05'),
(49, 2, 'maintenance', 'Tire Condition scheduled for maintenance', '2025-09-19 03:09:24'),
(50, 3, 'maintenance', 'Gas/Fuel Tank Condition scheduled for maintenance', '2025-09-20 03:09:43'),
(51, 4, 'maintenance', 'Oil Level scheduled for maintenance', '2025-09-20 03:10:06'),
(52, 5, 'maintenance', 'Left Taillight scheduled for maintenance', '2025-09-22 03:10:25');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recommendations`
--

CREATE TABLE `recommendations` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `suggested_supplier` int(11) DEFAULT NULL,
  `suggested_vehicle` int(11) DEFAULT NULL,
  `estimated_time` int(11) DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_costs`
--

CREATE TABLE `transport_costs` (
  `id` int(11) NOT NULL,
  `trip_id` int(11) DEFAULT NULL,
  `fuel_cost` decimal(10,2) DEFAULT NULL,
  `toll_fees` decimal(10,2) DEFAULT NULL,
  `other_expenses` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `receipt` varchar(255) DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp(6) NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transport_costs`
--

INSERT INTO `transport_costs` (`id`, `trip_id`, `fuel_cost`, `toll_fees`, `other_expenses`, `total_cost`, `status`, `receipt`, `created_by`, `created_at`) VALUES
(41, 35, 200.00, 0.00, 500.00, 700.00, 'submitted', 'receipts_68cb83dca7c0b.jpg', 'admin', '2025-09-18 04:00:28.000000');

-- --------------------------------------------------------

--
-- Table structure for table `trusted_devices`
--

CREATE TABLE `trusted_devices` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_token` char(64) NOT NULL,
  `ua_hash` char(64) NOT NULL,
  `ip_net` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_seen` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trusted_devices`
--

INSERT INTO `trusted_devices` (`id`, `user_id`, `device_token`, `ua_hash`, `ip_net`, `expires_at`, `last_seen`, `created_at`) VALUES
(10, 1, '87e75946a0ac4f573507022af40a0e241487df7255abf5356a64c732d5fcd04d', 'b5696a699925e22006af19488170e4e2ab139f50a49cff1cea664b7bd6f67a2e', '::1::/64', '2025-09-24 06:44:32', '2025-09-17 12:44:32', '2025-09-17 12:44:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `eid` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','requester','driver','staff','manager','supervisor') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `eid`, `full_name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'S250701', 'admin', 'froilan.respicio2021@gmail.com', '$2y$10$jIX3O6u91NS/77OMfeFYieOa7n2G9MAKSdmYFmz3nMwudlo652XTS', 'admin', '2025-08-30 08:37:43'),
(6, 'M250706', 'gusion', 'cringey.ch@gmail.com', '$2y$10$EPiNvDrT8aKDFTmHIl5vnuq/UBbmmhOOe7Ov5NpqmETHoYzorZuc6', 'manager', '2025-08-30 09:51:12'),
(21, 'R250721', 'Leonard Manicdo', 'leonardomanicdo119@gmail.com', '$2y$10$JrUQhjXMQHfyKrq55oFHrOxxqNX2r0rAY0g8BteDBEYOjli6sE0..', 'requester', '2025-09-14 19:49:20'),
(22, 'D250722', 'Jerome Adrian Ragas', 'jeromeadrianragas@gmail.com', '$2y$10$5NUbZkgUZ5UBnTKulfrxs.rGBfnfqgmSKHu43Evboa2OdEWUeYfxK', 'driver', '2025-09-14 19:56:24'),
(23, 'S250723', 'Mariel Jade Jarapan', 'Mianojarapan@gmail.com', '$2y$10$v8Yz.UWo/3.FKA3TREU3augUk.noP/oH2vjDPspRng6Q3KqPmX7pS', 'supervisor', '2025-09-14 19:57:09');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_requests`
--

CREATE TABLE `vehicle_requests` (
  `id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `request_date` datetime NOT NULL,
  `reservation_date` date DEFAULT NULL,
  `expected_return` date DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `origin_lat` decimal(8,6) DEFAULT NULL,
  `origin_lon` decimal(9,6) DEFAULT NULL,
  `destination_lat` decimal(8,6) DEFAULT NULL,
  `destination_lon` decimal(9,6) DEFAULT NULL,
  `requested_vehicle_type` varchar(100) DEFAULT NULL,
  `requested_driver_id` int(11) DEFAULT NULL,
  `status` enum('Pending','Approved','Denied','Dispatched','Completed') DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `dispatched_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_requests`
--

INSERT INTO `vehicle_requests` (`id`, `requester_id`, `request_date`, `reservation_date`, `expected_return`, `purpose`, `origin`, `destination`, `origin_lat`, `origin_lon`, `destination_lat`, `destination_lon`, `requested_vehicle_type`, `requested_driver_id`, `status`, `approved_by`, `approved_at`, `dispatched_at`, `completed_at`, `notes`) VALUES
(94, 1, '2025-09-18 08:45:36', '2025-09-18', '2025-09-19', 'Guest Transport', 'Warehouse North', 'Ninoy Aquino International Airport, Alegria, Buena Vida Townhomes, Parañaque District 2, Parañaque, Southern Manila District, Metro Manila, 1700, Philippines', NULL, NULL, NULL, NULL, 'Van', NULL, 'Approved', NULL, NULL, NULL, NULL, ''),
(95, 1, '2025-09-18 09:15:34', '2025-09-18', '2025-09-19', 'Supplies', 'Warehouse Legit', 'Soliera : Hotel and Restaurant', NULL, NULL, NULL, NULL, 'Van', NULL, 'Approved', NULL, NULL, NULL, NULL, ''),
(96, 1, '2025-09-18 09:25:37', '2025-09-18', '2025-09-19', 'Guest Transport 1-2', 'NAIA Airport Pickup', 'Soliera : Hotel and Restaurant', 14.510474, 121.022666, 14.726544, 121.036862, 'Pickup', NULL, 'Approved', NULL, NULL, NULL, NULL, ''),
(97, 1, '2025-09-18 09:33:40', '2025-09-18', '2025-09-19', 'Maintenance/Repair Run', 'Warehouse North', 'Maintenance Shop A', 14.751000, 121.025400, 14.733507, 121.056591, 'Pickup', NULL, 'Approved', NULL, NULL, NULL, NULL, ''),
(99, 1, '2025-09-18 10:53:07', '2025-09-18', '2025-09-19', 'Delivery', 'Warehouse North', 'Soliera : Hotel and Restaurant', 14.751000, 121.025400, 14.726544, 121.036862, 'Van', NULL, 'Denied', NULL, NULL, NULL, NULL, ''),
(100, 1, '2025-09-18 11:00:37', '2025-09-18', '2025-09-19', 'Delivery', 'Warehouse North', 'Soliera : Hotel and Restaurant', 14.751000, 121.025400, 14.726544, 121.036862, 'Van', NULL, '', NULL, NULL, NULL, NULL, ''),
(101, 1, '2025-09-18 11:14:39', '2025-09-18', '2025-09-19', 'Event Logistics', 'Warehouse North', 'Soliera : Hotel and Restaurant', 14.751000, 121.025400, 14.726544, 121.036862, 'Pickup', NULL, 'Approved', NULL, NULL, NULL, NULL, ''),
(102, 1, '2025-09-18 11:29:38', '2025-09-18', '2025-09-19', 'Maintenance / Repair Runs', 'Warehouse South', 'Maintenance Shop A', 14.409800, 121.041500, 14.733507, 121.056591, 'Pickup', NULL, 'Pending', NULL, NULL, NULL, NULL, ''),
(103, 1, '2025-09-18 12:47:38', '2025-09-18', '2025-09-20', 'Supplies Pickup', 'Warehouse Legit', 'Supplier A', 14.651422, 121.049265, 14.688802, 121.034322, 'Pickup', NULL, 'Pending', NULL, NULL, NULL, NULL, ''),
(104, 1, '2025-09-18 12:48:49', '2025-09-18', '2025-09-21', 'Supplies Pickup', 'Soliera : Hotel and Restaurant', 'Supplier B', 14.726544, 121.036862, 14.678258, 121.031576, 'Van', NULL, 'Approved', NULL, NULL, NULL, NULL, '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dispatches`
--
ALTER TABLE `dispatches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `eid_index` (`eid`);

--
-- Indexes for table `driver_trips`
--
ALTER TABLE `driver_trips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `fleet_vehicles`
--
ALTER TABLE `fleet_vehicles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fleet_vehicle_logs`
--
ALTER TABLE `fleet_vehicle_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`),
  ADD KEY `idx_used` (`used`);

--
-- Indexes for table `recommendations`
--
ALTER TABLE `recommendations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transport_costs`
--
ALTER TABLE `transport_costs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_transport_trip` (`trip_id`);

--
-- Indexes for table `trusted_devices`
--
ALTER TABLE `trusted_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_device` (`user_id`,`device_token`),
  ADD KEY `idx_lookup` (`user_id`,`device_token`,`expires_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `eid` (`eid`);

--
-- Indexes for table `vehicle_requests`
--
ALTER TABLE `vehicle_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vehicle_requests_requested_driver` (`requested_driver_id`),
  ADD KEY `fk_vehicle_requests_approved_by` (`approved_by`),
  ADD KEY `fk_vehicle_requests_requester` (`requester_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=273;

--
-- AUTO_INCREMENT for table `dispatches`
--
ALTER TABLE `dispatches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `driver_trips`
--
ALTER TABLE `driver_trips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `fleet_vehicles`
--
ALTER TABLE `fleet_vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `fleet_vehicle_logs`
--
ALTER TABLE `fleet_vehicle_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recommendations`
--
ALTER TABLE `recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transport_costs`
--
ALTER TABLE `transport_costs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `trusted_devices`
--
ALTER TABLE `trusted_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `vehicle_requests`
--
ALTER TABLE `vehicle_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dispatches`
--
ALTER TABLE `dispatches`
  ADD CONSTRAINT `dispatches_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `vehicle_requests` (`id`),
  ADD CONSTRAINT `dispatches_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `fleet_vehicles` (`id`),
  ADD CONSTRAINT `dispatches_ibfk_3` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`);

--
-- Constraints for table `driver_trips`
--
ALTER TABLE `driver_trips`
  ADD CONSTRAINT `driver_trips_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
  ADD CONSTRAINT `driver_trips_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `fleet_vehicles` (`id`);

--
-- Constraints for table `fleet_vehicle_logs`
--
ALTER TABLE `fleet_vehicle_logs`
  ADD CONSTRAINT `fleet_vehicle_logs_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `fleet_vehicles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transport_costs`
--
ALTER TABLE `transport_costs`
  ADD CONSTRAINT `fk_transport_trip` FOREIGN KEY (`trip_id`) REFERENCES `driver_trips` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_requests`
--
ALTER TABLE `vehicle_requests`
  ADD CONSTRAINT `fk_vehicle_requests_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vehicle_requests_requested_driver` FOREIGN KEY (`requested_driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vehicle_requests_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
