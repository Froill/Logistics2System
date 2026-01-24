-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Jan 23, 2026 at 03:04 PM
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
(46, 97, 1, 1, 1, '2025-09-18 03:33:58', NULL, 'Completed', 'Warehouse North', 'Maintenance Shop A', 14.751000, 121.025400, 14.733507, 121.056591, 'Maintenance/Repair Run', '', '2025-09-18 01:33:58', '2026-01-09 05:59:17'),
(48, 101, 1, 20, 1, '2025-09-18 05:15:05', NULL, 'Completed', 'Warehouse North', 'Soliera : Hotel and Restaurant', 14.751000, 121.025400, 14.726544, 121.036862, 'Event Logistics', '', '2025-09-18 03:15:05', '2025-09-18 03:15:29'),
(49, 104, 2, 20, 1, '2025-09-18 06:49:19', NULL, 'Completed', 'Soliera : Hotel and Restaurant', 'Supplier B', 14.726544, 121.036862, 14.678258, 121.031576, 'Supplies Pickup', '', '2025-09-18 04:49:19', '2026-01-10 08:28:13'),
(50, 103, 1, 1, 1, '2026-01-09 06:59:31', NULL, 'Completed', 'Warehouse Legit', 'Supplier A', 14.651422, 121.049265, 14.688802, 121.034322, 'Supplies Pickup', '', '2026-01-09 05:59:31', '2026-01-09 08:00:15'),
(51, 100, 1, 1, 1, '2026-01-09 11:10:10', NULL, 'Completed', 'Warehouse North', 'Soliera : Hotel and Restaurant', 14.751000, 121.025400, 14.726544, 121.036862, 'Delivery', '', '2026-01-09 10:10:10', '2026-01-18 01:37:00'),
(52, 102, 3, 21, 1, '2026-01-09 11:10:49', NULL, 'Ongoing', 'Warehouse South', 'Maintenance Shop A', 14.409800, 121.041500, 14.733507, 121.056591, 'Maintenance / Repair Runs', '', '2026-01-09 10:10:49', '2026-01-09 10:10:49'),
(53, 110, 4, 23, 1, '2026-01-10 09:27:38', NULL, 'Completed', 'Manila', 'QC', 0.000000, 0.000000, 0.000000, 0.000000, 'Gala', '', '2026-01-10 08:27:38', '2026-01-17 18:56:03'),
(54, 106, 2, 20, 1, '2026-01-14 15:15:36', NULL, 'Ongoing', 'Bestlink', 'Warehouse Legit', 14.726490, 121.036444, 14.651422, 121.049265, 'Guest Transport', '', '2026-01-14 14:15:36', '2026-01-14 14:15:36'),
(55, 105, 4, 23, 1, '2026-01-17 20:40:33', NULL, 'Completed', 'Warehouse North', 'Warehouse South', 14.751000, 121.025400, 14.409800, 121.041500, 'Gala lng', '', '2026-01-17 19:40:33', '2026-01-18 01:37:14');

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
(20, 'D250722', 'Jerome Adrian Ragas', 'A20-52-123456', NULL, 'jeromeadrianragas@gmail.com', 'Dispatched', '2025-09-15 03:56:24'),
(21, 'D250755', 'Felipe Dela Cruz', 'XXS-232', '01231234567', 'fururano@gmail.com', 'Dispatched', '2026-01-09 15:07:03'),
(23, 'D260700', 'Harry Styles', 'ZZZ-424-XYZ', NULL, 'froilan.respicio2021@gmail.com', 'Available', '2026-01-09 18:12:30');

-- --------------------------------------------------------

--
-- Table structure for table `driver_trips`
--

CREATE TABLE `driver_trips` (
  `id` int(11) NOT NULL,
  `dispatch_id` int(11) DEFAULT NULL,
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

INSERT INTO `driver_trips` (`id`, `dispatch_id`, `driver_id`, `vehicle_id`, `trip_date`, `start_time`, `end_time`, `distance_traveled`, `fuel_consumed`, `idle_time`, `average_speed`, `performance_score`, `validation_status`, `validation_message`, `supervisor_review_status`, `supervisor_remarks`, `created_at`, `updated_at`, `cargo_weight`, `vehicle_capacity`) VALUES
(35, NULL, 20, 1, '2025-09-18', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 7.00, 1.20, 3, 0.64, 79.17, 'valid', NULL, 'approved', '', '2025-09-18 03:52:24', '2026-01-08 16:40:38', 120.00, 475.00),
(36, NULL, 1, 1, '2025-09-18', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 12.00, 4.00, 2, 0.63, 65.00, 'valid', NULL, 'pending', NULL, '2025-09-18 03:53:08', '2025-09-18 03:53:08', 200.00, 475.00),
(37, NULL, 20, 1, '2025-09-18', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 10.00, 2.00, 1, 10.00, 75.00, 'valid', NULL, 'approved', 'good', '2025-09-18 08:41:01', '2026-01-09 10:04:21', 100.00, 475.00),
(38, NULL, 1, 1, '2026-01-09', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 12.00, 50.00, 3, 6.00, 51.20, 'valid', NULL, 'approved', 'goods', '2026-01-10 08:29:26', '2026-01-10 08:30:21', 432.00, 475.00),
(39, NULL, 1, 1, '2026-01-09', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 10.00, 1.00, 10, 0.56, 100.00, 'valid', NULL, 'approved', 'VERY GOOD WOW ', '2026-01-14 14:20:11', '2026-01-14 14:20:52', 200.00, 475.00),
(40, NULL, 1, 1, '2026-01-09', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 15.00, 5.00, 10, 0.88, 65.00, 'valid', NULL, 'approved', 'sadsad', '2026-01-17 13:38:11', '2026-01-17 13:38:40', 250.00, 475.00);

-- --------------------------------------------------------

--
-- Table structure for table `fleet_vehicles`
--

CREATE TABLE `fleet_vehicles` (
  `id` int(11) NOT NULL,
  `vehicle_name` varchar(100) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `vehicle_type` enum('Motorcycle','Tricycle','Truck','Van','Pickup','Car') DEFAULT NULL,
  `status` enum('Active','Under Maintenance','Inactive','Dispatched') DEFAULT 'Active',
  `weight_capacity` decimal(6,2) DEFAULT NULL,
  `fuel_capacity` decimal(6,2) DEFAULT NULL,
  `vehicle_image` varchar(255) DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fleet_vehicles`
--

INSERT INTO `fleet_vehicles` (`id`, `vehicle_name`, `plate_number`, `vehicle_type`, `status`, `weight_capacity`, `fuel_capacity`, `vehicle_image`, `is_archived`, `archived_at`, `archived_by`) VALUES
(1, 'Toyota Hilux', 'ABC-124', 'Pickup', 'Active', 475.00, 80.00, 'uploads/vehicle_1757162864_8262.png', 0, NULL, NULL),
(2, 'Mitsubishi L300', 'XYZ-456', 'Van', 'Dispatched', 1000.00, 55.00, 'uploads/vehicle_1757162937_8200.jpg', 0, NULL, NULL),
(3, 'Isuzu D-Max', 'LMN-789', 'Pickup', 'Dispatched', 475.00, 76.00, 'uploads/vehicle_1757162957_9277.jpg', 0, NULL, NULL),
(4, 'Hyundai H100', 'JKL-321', 'Van', 'Active', 1090.00, 65.00, 'uploads/vehicle_1757162972_2521.jpg', 0, NULL, NULL),
(5, 'Ford Ranger', 'PQR-654', 'Pickup', 'Inactive', 985.00, 80.00, 'uploads/vehicle_1757163149_5973.png', 0, NULL, NULL);

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
(52, 5, 'maintenance', 'Left Taillight scheduled for maintenance', '2025-09-22 03:10:25'),
(53, 1, 'maintenance', 'Battery scheduled for maintenance', '2026-01-08 22:03:15'),
(54, 2, 'maintenance', 'Engine Condition scheduled for maintenance', '2026-01-09 22:04:19'),
(55, 1, 'maintenance', 'Brakes Condition scheduled for maintenance', '2026-01-18 00:30:27');

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
-- Table structure for table `receipt_duplicate_checks`
--

CREATE TABLE `receipt_duplicate_checks` (
  `id` int(11) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_duplicate` tinyint(1) NOT NULL DEFAULT 0,
  `max_similarity` decimal(5,2) DEFAULT 0.00,
  `check_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`check_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit log for receipt duplicate detection attempts';

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
  `created_at` timestamp(6) NULL DEFAULT NULL,
  `ocr_data` text DEFAULT NULL COMMENT 'JSON data from OCR processing of receipt',
  `ocr_confidence` varchar(10) DEFAULT NULL COMMENT 'OCR confidence level (high/medium/low)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transport_costs`
--

INSERT INTO `transport_costs` (`id`, `trip_id`, `fuel_cost`, `toll_fees`, `other_expenses`, `total_cost`, `status`, `receipt`, `created_by`, `created_at`, `ocr_data`, `ocr_confidence`) VALUES
(41, 35, 200.00, 0.00, 500.00, 700.00, 'submitted', 'receipts_68cb83dca7c0b.jpg', 'admin', '2025-09-18 04:00:28.000000', NULL, NULL),
(42, 36, 500.00, 50.00, 200.00, 750.00, 'submitted', NULL, 'admin', '2025-09-18 08:41:44.000000', NULL, NULL),
(43, 37, 300.00, 500.00, 200.00, 1000.00, 'submitted', NULL, 'admin', '2025-09-18 08:44:06.000000', NULL, NULL),
(44, 38, 120.00, 120.00, 0.00, 240.00, 'submitted', NULL, 'admin', '2026-01-10 08:31:35.000000', NULL, NULL);

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
(15, 1, '56f52e0cdfb3347fb0234fd3ca46ec8b4ee2d073e5e879865d452f2bc2fcd622', '62d18984722ed057781bce4342a009271fd0bf4799981a048ca348f09af03584', '', '2026-01-24 06:07:47', '2026-01-17 13:07:47', '2026-01-17 13:07:47'),
(16, 25, '4ac405510ef0689807d301fa95bf2376843024013665364fccf9907a0cfe5273', 'bf04b646a4b3d1c55f0cce511ad67664a6c319e96c535ee28110c9938cda12ff', '', '2026-01-24 06:55:15', '2026-01-17 13:55:15', '2026-01-17 13:55:15'),
(17, 1, '4bbbe9d91f1d8fe594d92090ad8be684992612ca1b1944fde02fb553d710da4f', '816147bedd84317a66534e444e60842858f0f2a3b3fa43710b4ef836da4252d2', '', '2026-01-29 21:42:50', '2026-01-23 04:42:50', '2026-01-23 04:42:50'),
(18, 25, '77fe4040b87f31e0e7ec4286680dd7587c8558a09f5be5bb73639e1da37dc11e', '39f5649641f05c7d05cecd8fd6bc2ee283cb7c4f48a4e8969d923c49784f5a05', '', '2026-01-29 22:45:01', '2026-01-23 05:45:01', '2026-01-23 05:45:01');

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
  `role` enum('superadmin','admin','requester','driver','staff','manager','supervisor') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `eid`, `full_name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'S250701', 'admin', 'froilan.respicio2021@gmail.com', '$2y$10$nrcTLEWxb.V.cDjh0AOiBuR425BWdWBXkLrot5AzpGhu3XTGqPTzK', 'admin', '2025-08-30 08:37:43'),
(6, 'R250706', 'Richard Gutierrez', 'cringey.ch@gmail.com', '$2y$10$wNgGzedIfvZk1QzI4vhHtu13oDEouldOU77tzpjoobB46E.G99Dia', 'requester', '2025-08-30 09:51:12'),
(21, 'R250721', 'Leonard Manicdo', 'leonardomanicdo119@gmail.com', '$2y$10$JrUQhjXMQHfyKrq55oFHrOxxqNX2r0rAY0g8BteDBEYOjli6sE0..', 'requester', '2025-09-14 19:49:20'),
(22, 'D250722', 'Jerome Adrian Ragas', 'jeromeadrianragas@gmail.com', '$2y$10$5NUbZkgUZ5UBnTKulfrxs.rGBfnfqgmSKHu43Evboa2OdEWUeYfxK', 'driver', '2025-09-14 19:56:24'),
(23, 'S250723', 'Mariel Jade Jarapan', 'Mianojarapan@gmail.com', '$2y$10$v8Yz.UWo/3.FKA3TREU3augUk.noP/oH2vjDPspRng6Q3KqPmX7pS', 'supervisor', '2025-09-14 19:57:09'),
(25, 'D250755', 'Felipe Dela Cruz', 'fururano@gmail.com', '$2y$10$2.7/aWIuqzLO9IFh8wAGkOESBYxvHU5iDYN0xwqzh/VB7Db18K82G', 'driver', '2026-01-09 06:49:05'),
(27, 'S250779', 'Clark Kent', 'clledgermayne@gmail.com', '$2y$10$SpDjs/HWXCRFqCNGlSIBOOGgeH9WjbuHyJskkFu/vfxEEOgjY8AMG', 'supervisor', '2026-01-09 06:52:23'),
(29, 'M260729', 'Gelli', 'bantilangelli1@gmail.com', '$2y$10$Utz2bI.w2xWQKtnwq.2gJOdxWZfQsVmFmMVPHGX7E/3WPHe03PKEe', 'manager', '2026-01-23 10:23:43');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_documents`
--

CREATE TABLE `vehicle_documents` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `doc_type` varchar(100) DEFAULT NULL,
  `doc_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_documents`
--

INSERT INTO `vehicle_documents` (`id`, `vehicle_id`, `doc_type`, `doc_name`, `file_path`, `expiry_date`, `uploaded_by`, `uploaded_at`) VALUES
(1, 1, 'Registration', 'OR-CR-vehicle1.pdf', 'uploads/vehicles/1/orcr1.png', '2026-08-15', 1, '2026-01-09 10:58:14'),
(2, 2, 'Registration', 'OR-CR-vehicle2.pdf', 'uploads/vehicles/2/orcr2.png', '2027-12-08', 1, '2026-01-09 10:58:14'),
(3, 3, 'Registration', 'OR-CR-vehicle3.pdf', 'uploads/vehicles/3/orcr3.png', '2026-03-20', 1, '2026-01-09 10:58:14'),
(4, 4, 'Registration', 'OR-CR-vehicle4.pdf', 'uploads/vehicles/4/orcr4.png', '2027-05-10', 1, '2026-01-09 10:58:14'),
(5, 5, 'Registration', 'OR-CR-vehicle5.pdf', 'uploads/vehicles/5/orcr5.png', '2025-11-30', 1, '2026-01-09 10:58:14');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_insurance`
--

CREATE TABLE `vehicle_insurance` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `insurer` varchar(255) DEFAULT NULL,
  `policy_number` varchar(255) DEFAULT NULL,
  `coverage_type` varchar(100) DEFAULT NULL,
  `coverage_start` date DEFAULT NULL,
  `coverage_end` date DEFAULT NULL,
  `premium` decimal(12,2) DEFAULT NULL,
  `document_path` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_insurance`
--

INSERT INTO `vehicle_insurance` (`id`, `vehicle_id`, `insurer`, `policy_number`, `coverage_type`, `coverage_start`, `coverage_end`, `premium`, `document_path`, `created_at`) VALUES
(1, 1, 'Charter Ping An Insurance Corporation', 'POL-CP-0001', 'Comprehensive', '2025-01-01', '2028-01-07', 12000.00, 'uploads/vehicles/1/insurance1.jpg', '2026-01-09 10:58:14'),
(2, 2, 'Malayan Insurance Company', 'MI-2025-9876', 'Third-Party', '2024-12-10', '2026-12-10', 8000.00, 'uploads/vehicles/2/insurance2.jpg', '2026-01-09 10:58:14'),
(3, 3, 'BPI/MS Insurance Company', 'BPI-3030-555', 'Comprehensive', '2025-04-01', '2026-04-01', 11000.00, 'uploads/vehicles/3/insurance3.jpg', '2026-01-09 10:58:14'),
(4, 4, 'FPG Insurance Philippines', 'FPG-4400-772', 'Comprehensive', '2026-05-11', '2027-05-11', 13000.00, 'uploads/vehicles/4/insurance4.jpg', '2026-01-09 10:58:14'),
(5, 5, 'AXA Philippines', 'AXA-5599-210', 'Third-Party', '2024-12-01', '2025-12-01', 7500.00, 'uploads/vehicles/5/insurance5.jpg', '2026-01-09 10:58:14');

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
(100, 1, '2025-09-18 11:00:37', '2025-09-18', '2025-09-19', 'Delivery', 'Warehouse North', 'Soliera : Hotel and Restaurant', 14.751000, 121.025400, 14.726544, 121.036862, 'Van', NULL, 'Approved', NULL, NULL, NULL, NULL, ''),
(101, 1, '2025-09-18 11:14:39', '2025-09-18', '2025-09-19', 'Event Logistics', 'Warehouse North', 'Soliera : Hotel and Restaurant', 14.751000, 121.025400, 14.726544, 121.036862, 'Pickup', NULL, 'Approved', NULL, NULL, NULL, NULL, ''),
(102, 1, '2025-09-18 11:29:38', '2025-09-18', '2025-09-19', 'Maintenance / Repair Runs', 'Warehouse South', 'Maintenance Shop A', 14.409800, 121.041500, 14.733507, 121.056591, 'Pickup', NULL, 'Approved', NULL, NULL, NULL, NULL, ''),
(103, 1, '2025-09-18 12:47:38', '2025-09-18', '2025-09-20', 'Supplies Pickup', 'Warehouse Legit', 'Supplier A', 14.651422, 121.049265, 14.688802, 121.034322, 'Pickup', NULL, 'Approved', NULL, NULL, NULL, NULL, ''),
(104, 1, '2025-09-18 12:48:49', '2025-09-18', '2025-09-21', 'Supplies Pickup', 'Soliera : Hotel and Restaurant', 'Supplier B', 14.726544, 121.036862, 14.678258, 121.031576, 'Van', NULL, 'Approved', NULL, NULL, NULL, NULL, ''),
(105, 1, '2026-01-09 12:59:38', '2026-01-09', '2026-01-11', 'Gala lng', 'Warehouse North', 'Warehouse South', 14.751000, 121.025400, 14.409800, 121.041500, 'Bike', NULL, 'Approved', NULL, NULL, NULL, NULL, ''),
(106, 1, '2026-01-09 15:59:15', '2026-01-09', '2026-01-10', 'Guest Transport', 'Bestlink', 'Warehouse Legit', 14.726490, 121.036444, 14.651422, 121.049265, 'Van', NULL, 'Approved', NULL, NULL, NULL, NULL, ''),
(107, 1, '2026-01-09 16:00:42', '2026-01-09', '2026-01-10', 'Supplies Pickup', 'Manila, Capital District, Metro Manila, Philippines', 'Bagui Road, Sitio Kanluran, Kumintang Ibaba, Poblacion, Batangas City, Batangas, Calabarzon, 4200, Philippines', 14.590449, 120.980362, 13.768036, 121.067426, 'Pickup', NULL, 'Pending', NULL, NULL, NULL, NULL, ''),
(108, 1, '2026-01-09 18:01:24', '2026-01-09', '2026-01-10', 'Gala', 'Manila, Capital District, Metro Manila, Philippines', 'Warehouse South', 14.590449, 120.980362, 14.409800, 121.041500, 'Motor', NULL, 'Pending', NULL, NULL, NULL, NULL, ''),
(109, 1, '2026-01-09 18:02:23', '2026-01-09', '2026-01-11', 'Delivery', 'sdsaddsadsa', 'sadsad', 0.000000, 0.000000, 0.000000, 0.000000, 'Pickup', NULL, 'Pending', NULL, NULL, NULL, NULL, ''),
(110, 1, '2026-01-10 16:26:59', '2026-01-10', '2026-01-11', 'Gala', 'Manila', 'QC', 0.000000, 0.000000, 0.000000, 0.000000, 'Motorcycle', NULL, 'Approved', NULL, NULL, NULL, NULL, '');

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
  ADD UNIQUE KEY `uq_driver_trips_dispatch` (`dispatch_id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `fleet_vehicles`
--
ALTER TABLE `fleet_vehicles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fleet_vehicles_archived` (`is_archived`);

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
-- Indexes for table `receipt_duplicate_checks`
--
ALTER TABLE `receipt_duplicate_checks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_duplicate` (`is_duplicate`,`created_at`);

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
-- Indexes for table `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `vehicle_insurance`
--
ALTER TABLE `vehicle_insurance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=634;

--
-- AUTO_INCREMENT for table `dispatches`
--
ALTER TABLE `dispatches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `driver_trips`
--
ALTER TABLE `driver_trips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `fleet_vehicles`
--
ALTER TABLE `fleet_vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `fleet_vehicle_logs`
--
ALTER TABLE `fleet_vehicle_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

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
-- AUTO_INCREMENT for table `receipt_duplicate_checks`
--
ALTER TABLE `receipt_duplicate_checks`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `trusted_devices`
--
ALTER TABLE `trusted_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vehicle_insurance`
--
ALTER TABLE `vehicle_insurance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vehicle_requests`
--
ALTER TABLE `vehicle_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

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
  ADD CONSTRAINT `driver_trips_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `fleet_vehicles` (`id`),
  ADD CONSTRAINT `fk_driver_trips_dispatch` FOREIGN KEY (`dispatch_id`) REFERENCES `dispatches` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `vehicle_documents`
--
ALTER TABLE `vehicle_documents`
  ADD CONSTRAINT `vehicle_documents_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `fleet_vehicles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicle_insurance`
--
ALTER TABLE `vehicle_insurance`
  ADD CONSTRAINT `vehicle_insurance_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `fleet_vehicles` (`id`) ON DELETE CASCADE;

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


-- Account Lockout & Login Rate Limiting Tables
-- This migration adds support for tracking failed login attempts and account lockouts

-- ========================================
-- 1. Failed Login Attempts Table
-- ========================================
-- Tracks every failed login attempt by email/user and IP address
CREATE TABLE IF NOT EXISTS `failed_login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent_hash` varchar(64) DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `failure_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `ip_address` (`ip_address`),
  KEY `attempt_time` (`attempt_time`),
  KEY `email_ip` (`email`, `ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- 2. Account Lockout Table
-- ========================================
-- Tracks locked accounts with lockout duration
CREATE TABLE IF NOT EXISTS `account_lockouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `lockout_reason` enum('failed_attempts','manual','security_alert') DEFAULT 'failed_attempts',
  `locked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `locked_until` datetime NOT NULL,
  `failed_attempts_count` int(11) DEFAULT 0,
  `unlocked_at` datetime DEFAULT NULL,
  `unlocked_by` int(11) DEFAULT NULL,
  `unlock_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `email` (`email`),
  KEY `locked_until` (`locked_until`),
  CONSTRAINT `fk_lockout_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- 3. IP Address Rate Limiting Table
-- ========================================
-- Tracks rate limiting per IP address
CREATE TABLE IF NOT EXISTS `ip_rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `request_count` int(11) DEFAULT 1,
  `first_request_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_request_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_blocked` tinyint(1) DEFAULT 0,
  `blocked_until` datetime DEFAULT NULL,
  `block_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`),
  KEY `blocked_until` (`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- 4. Alter users table (if not already present)
-- ========================================
-- Add lockout-related columns to users table if they don't exist
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `account_locked` tinyint(1) DEFAULT 0;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `locked_until` datetime DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `failed_login_count` int(11) DEFAULT 0;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `last_failed_login` datetime DEFAULT NULL;

-- ========================================
-- Create cleanup events for expired records (optional)
-- ========================================
-- Automatically clean up old failed login attempts (older than 30 days)
CREATE EVENT IF NOT EXISTS `cleanup_old_failed_attempts`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
  DELETE FROM `failed_login_attempts` 
  WHERE `attempt_time` < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Automatically clean up old IP rate limit records (older than 7 days with low attempt counts)
CREATE EVENT IF NOT EXISTS `cleanup_old_ip_limits`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
  DELETE FROM `ip_rate_limits`
  WHERE `last_request_time` < DATE_SUB(NOW(), INTERVAL 7 DAY) 
  AND `request_count` < 5
  AND `is_blocked` = 0;
