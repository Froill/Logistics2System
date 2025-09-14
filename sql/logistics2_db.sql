-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Sep 14, 2025 at 08:55 PM
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
(172, 'FVM', 'adjust_maintenance', 5, 'admin', NULL, '2025-09-15 02:29:00');

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
  `purpose` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, 'D25072', 'Maria Santos', 'PH-DL-2025-002', '09182345678', 'maria.santos@example.com', 'Available', '2025-08-29 02:49:13'),
(3, 'D25073', 'Pedro Ramirez', 'PH-DL-2025-003', '09193456789', 'pedro.ramirez@example.com', 'Available', '2025-08-29 02:49:13'),
(4, 'D25074', 'Ana Villanueva', 'PH-DL-2025-004', '09184561234', 'ana.villanueva@example.com', 'Available', '2025-08-29 02:49:13'),
(5, 'D25075', 'Ramon Cruz', 'PH-DL-2025-005', '09185672345', 'ramon.cruz@example.com', 'Available', '2025-08-29 02:49:13'),
(11, 'D250708', 'John Doe', '1234567', NULL, 'johndoe@gmail.com', 'Available', '2025-08-31 22:21:42'),
(14, 'D250701', 'Carlos Reyes', 'PH-DL-2025-101', '09170000001', 'carlos.reyes@example.com', 'Available', '2025-08-01 00:00:00'),
(15, 'D250702', 'Liza Fernandez', 'PH-DL-2025-102', '09170000002', 'liza.fernandez@example.com', 'Available', '2025-08-01 00:00:00'),
(16, 'D250703', 'Miguel Cruz', 'PH-DL-2025-103', '09170000003', 'miguel.cruz@example.com', 'Available', '2025-08-01 00:00:00'),
(17, 'D250704', 'Sofia Santos', 'PH-DL-2025-104', '09170000004', 'sofia.santos@example.com', 'Available', '2025-08-01 00:00:00'),
(18, 'D250705', 'Rafael Garcia', 'PH-DL-2025-105', '09170000005', 'rafael.garcia@example.com', 'Available', '2025-08-01 00:00:00'),
(19, 'D250706', 'Jasmine Dela Rosa', 'PH-DL-2025-106', '09170000006', 'jasmine.delarosa@example.com', 'Available', '2025-08-01 00:00:00');

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
(2, 'Mitsubishi L300', 'XYZ-456', 'Van', 'Active', 1000.00, 55.00, 'uploads/vehicle_1757162937_8200.jpg'),
(3, 'Isuzu D-Max', 'LMN-789', 'Pickup', 'Active', 475.00, 76.00, 'uploads/vehicle_1757162957_9277.jpg'),
(4, 'Hyundai H100', 'JKL-321', 'Van', 'Under Maintenance', 1090.00, 65.00, 'uploads/vehicle_1757162972_2521.jpg'),
(5, 'Ford Ranger', 'PQR-654', 'Pickup', 'Under Maintenance', 985.00, 80.00, 'uploads/vehicle_1757163149_5973.png');

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
(31, 1, 'maintenance', 'Scheduled maintenance adjusted to 2025-09-08', '2025-09-08 08:00:00'),
(32, 2, 'maintenance', 'Monthly Scheduled Maintenance', '2025-10-08 08:00:00'),
(33, 3, 'maintenance', 'Monthly Scheduled Maintenance', '2025-10-08 08:00:00'),
(34, 4, 'maintenance', 'Scheduled maintenance adjusted to 2025-09-15', '2025-09-15 08:00:00'),
(35, 5, 'maintenance', 'Scheduled maintenance adjusted to 2025-09-16', '2025-09-16 08:00:00');

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
(8, 1, '0ff78a42681786814889c6d5fe1244d96e6dee5d8e34a08e947c0ce90ab7f9a9', 'c872b1a5d8f484c5e37fe7be0753f974e53712ba2d75f667602585626e90101d', '::1::/64', '2025-09-15 10:52:06', '2025-09-08 16:52:06', '2025-09-08 16:52:06'),
(9, 1, '9bc0972d5a7e27aed6dc12c4141641f6dcbdac737d71dd9bc39c0312aed1b354', 'b5696a699925e22006af19488170e4e2ab139f50a49cff1cea664b7bd6f67a2e', '::1::/64', '2025-09-21 06:26:27', '2025-09-14 12:26:27', '2025-09-14 12:26:27');

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
(8, 'D250708', 'John Doe', 'johndoe@gmail.com', '$2y$10$.ntimqwMd/xqkVZNc22vd.cqXE5TwsnBPZOKmmPkiDmEDvPYhyQfq', 'driver', '2025-08-31 14:21:41'),
(9, 'D250701', 'Carlos Reyes', 'carlos.reyes@example.com', 'dummyhash', 'driver', '2025-08-01 00:00:00'),
(10, 'D250702', 'Liza Fernandez', 'liza.fernandez@example.com', 'dummyhash', 'driver', '2025-08-01 00:00:00'),
(11, 'D250703', 'Miguel Cruz', 'miguel.cruz@example.com', 'dummyhash', 'driver', '2025-08-01 00:00:00'),
(12, 'D250704', 'Sofia Santos', 'sofia.santos@example.com', 'dummyhash', 'driver', '2025-08-01 00:00:00'),
(13, 'D250705', 'Rafael Garcia', 'rafael.garcia@example.com', 'dummyhash', 'driver', '2025-08-01 00:00:00'),
(14, 'D250706', 'Jasmine Dela Rosa', 'jasmine.delarosa@example.com', 'dummyhash', 'driver', '2025-08-01 00:00:00'),
(15, 'D250749', 'Juan Dela Cruz', 'juan.dela.cruz@example.com', '$2y$10$xSDgKfhE0ouWpQ9NRXGfmOGmS0s.B3vqi86tRWpNz3OmsDpswjXsi', 'driver', '2025-09-01 14:14:54'),
(16, 'D250773', 'Maria Santos', 'maria.santos@example.com', '$2y$10$sQA3Rsmz2ICz4YnOjdP9uOnftuAvn4JSUQVymqRDfwlrpxJWrSzQW', 'driver', '2025-09-01 14:14:54'),
(17, 'D250762', 'Pedro Ramirez', 'pedro.ramirez@example.com', '$2y$10$q75lpaZOxgGUd8wuzs7JIelJQTx6vfEJBbX91s7OhrdrbYxA4EKp2', 'driver', '2025-09-01 14:14:54'),
(18, 'D250731', 'Ana Villanueva', 'ana.villanueva@example.com', '$2y$10$ANFnS7FS6iBskhZQw37ySeScoNdRTvMcRyrJTRCSOuy3bpwCdBsDW', 'driver', '2025-09-01 14:14:54'),
(19, 'D250730', 'Ramon Cruz', 'ramon.cruz@example.com', '$2y$10$YAOQJC0Zi.9PlW5BipleV.zxv5vOVv2fEazeoZ3xdDyq7OYb672j.', 'driver', '2025-09-01 14:14:54'),
(20, 'D250761', 'Josefa Dela Paz', 'josefa.dela.paz@example.com', '$2y$10$BYZNtn8rCrr3s/48Uum4qO8NVB.urcFazJpNpRsWx/7bmLRanSdI.', 'driver', '2025-09-01 14:14:54');

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

INSERT INTO `vehicle_requests` (`id`, `requester_id`, `request_date`, `reservation_date`, `expected_return`, `purpose`, `origin`, `destination`, `requested_vehicle_type`, `requested_driver_id`, `status`, `approved_by`, `approved_at`, `dispatched_at`, `completed_at`, `notes`) VALUES
(16, 1, '2025-08-29 12:44:30', '2025-07-23', '2025-08-31', 'Resupply Ketchup', 'Kitchen', 'Warehouse', 'Car', NULL, 'Approved', NULL, NULL, NULL, NULL, ''),
(17, 1, '2025-08-29 12:46:46', '2025-08-29', '2025-08-30', 'Balut Order', 'Kitchen', 'Supplier', 'Car', NULL, 'Approved', NULL, NULL, NULL, NULL, ''),
(28, 1, '2025-08-02 08:00:00', '2025-08-02', '2025-08-02', 'Delivery 2-2', 'Warehouse', 'Hotel', 'Car', 4, 'Approved', NULL, NULL, NULL, NULL, NULL),
(29, 1, '2025-08-03 08:00:00', '2025-08-03', '2025-08-03', 'Delivery 3-1', 'Warehouse', 'Hotel', 'Pickup', 5, 'Approved', NULL, NULL, NULL, NULL, NULL),
(34, 1, '2025-08-05 08:00:00', '2025-08-05', '2025-08-05', 'Delivery 5-2', 'Warehouse', 'Hotel', 'Van', 5, 'Approved', NULL, NULL, NULL, NULL, NULL),
(37, 6, '2025-08-07 08:00:00', '2025-08-07', '2025-08-07', 'Delivery 7-1', 'Warehouse', 'Hotel', 'Truck', 1, 'Approved', NULL, NULL, NULL, NULL, NULL),
(39, 6, '2025-08-08 08:00:00', '2025-08-08', '2025-08-08', 'Delivery 8-1', 'Warehouse', 'Hotel', 'Truck', 4, 'Approved', NULL, NULL, NULL, NULL, NULL),
(42, 6, '2025-08-09 08:00:00', '2025-08-09', '2025-08-09', 'Delivery 9-2', 'Warehouse', 'Hotel', 'Pickup', 1, 'Approved', NULL, NULL, NULL, NULL, NULL),
(44, 6, '2025-08-10 08:00:00', '2025-08-10', '2025-08-10', 'Delivery 10-2', 'Warehouse', 'Hotel', 'Van', 2, 'Approved', NULL, NULL, NULL, NULL, NULL),
(46, 1, '2025-08-11 08:00:00', '2025-08-11', '2025-08-11', 'Delivery 11-2', 'Warehouse', 'Hotel', 'Van', 5, 'Approved', NULL, NULL, NULL, NULL, NULL),
(50, 6, '2025-08-13 08:00:00', '2025-08-13', '2025-08-13', 'Delivery 13-2', 'Warehouse', 'Hotel', 'Van', 5, 'Approved', NULL, NULL, NULL, NULL, NULL),
(52, 1, '2025-08-14 08:00:00', '2025-08-14', '2025-08-14', 'Delivery 14-2', 'Warehouse', 'Hotel', 'Pickup', 5, 'Approved', NULL, NULL, NULL, NULL, NULL),
(59, 1, '2025-08-18 08:00:00', '2025-08-18', '2025-08-18', 'Delivery 18-1', 'Warehouse', 'Hotel', 'Pickup', 5, 'Approved', NULL, NULL, NULL, NULL, NULL),
(67, 6, '2025-08-22 08:00:00', '2025-08-22', '2025-08-22', 'Delivery 22-1', 'Warehouse', 'Hotel', 'Van', 1, 'Approved', NULL, NULL, NULL, NULL, NULL),
(74, 6, '2025-08-25 08:00:00', '2025-08-25', '2025-08-25', 'Delivery 25-2', 'Warehouse', 'Hotel', 'Pickup', 1, 'Approved', NULL, NULL, NULL, NULL, NULL),
(80, 6, '2025-08-28 08:00:00', '2025-08-28', '2025-08-28', 'Delivery 28-2', 'Warehouse', 'Hotel', 'Car', 4, 'Approved', NULL, NULL, NULL, NULL, NULL),
(83, 1, '2025-08-30 08:00:00', '2025-08-30', '2025-08-30', 'Delivery 30-1', 'Warehouse', 'Hotel', 'Truck', 1, 'Approved', NULL, NULL, NULL, NULL, NULL),
(89, 1, '2025-09-02 12:39:31', '2025-09-02', '2025-09-03', 'Gala', 'Warehouse Legit', 'Bestlink', 'Van', NULL, 'Approved', NULL, NULL, NULL, NULL, ''),
(92, 1, '2025-09-07 00:36:25', '2025-09-07', '2025-09-08', 'Delivery', 'adsad', 'sadsad', 'Pickup', NULL, 'Pending', NULL, NULL, NULL, NULL, '');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT for table `dispatches`
--
ALTER TABLE `dispatches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `driver_trips`
--
ALTER TABLE `driver_trips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `fleet_vehicles`
--
ALTER TABLE `fleet_vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `fleet_vehicle_logs`
--
ALTER TABLE `fleet_vehicle_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `trusted_devices`
--
ALTER TABLE `trusted_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `vehicle_requests`
--
ALTER TABLE `vehicle_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

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
