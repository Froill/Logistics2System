-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Aug 30, 2025 at 04:20 PM
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
(2, 'VRDS', 'complete_dispatch', 1, 'admin', NULL, '2025-08-30 21:37:44');

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

--
-- Dumping data for table `dispatches`
--

INSERT INTO `dispatches` (`id`, `request_id`, `vehicle_id`, `driver_id`, `officer_id`, `dispatch_date`, `return_date`, `status`, `origin`, `destination`, `purpose`, `notes`, `created_at`, `updated_at`) VALUES
(1, 17, 1, 2, 1, '2025-08-29 07:22:41', NULL, 'Completed', 'Kitchen', 'Supplier', 'Balut Order', '', '2025-08-29 05:22:41', '2025-08-30 13:37:44');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
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

INSERT INTO `drivers` (`id`, `driver_name`, `license_number`, `phone`, `email`, `status`, `created_at`) VALUES
(1, 'Juan Dela Cruz', 'PH-DL-2025-001', '09171234567', 'juan.delacruz@example.com', 'Dispatched', '2025-08-29 02:49:13'),
(2, 'Maria Santos', 'PH-DL-2025-002', '09182345678', 'maria.santos@example.com', 'Available', '2025-08-29 02:49:13'),
(3, 'Pedro Ramirez', 'PH-DL-2025-003', '09193456789', 'pedro.ramirez@example.com', 'Available', '2025-08-29 02:49:13'),
(4, 'Ana Villanueva', 'PH-DL-2025-004', '09184561234', 'ana.villanueva@example.com', 'Available', '2025-08-29 02:49:13'),
(5, 'Ramon Cruz', 'PH-DL-2025-005', '09185672345', 'ramon.cruz@example.com', 'Available', '2025-08-29 02:49:13');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver_trips`
--

INSERT INTO `driver_trips` (`id`, `driver_id`, `vehicle_id`, `trip_date`, `start_time`, `end_time`, `distance_traveled`, `fuel_consumed`, `idle_time`, `average_speed`, `performance_score`, `validation_status`, `validation_message`, `supervisor_review_status`, `supervisor_remarks`, `created_at`, `updated_at`) VALUES
(1, 1, 18, '2025-08-14', '2025-08-13 04:22:00', '2025-08-20 10:28:00', 5.00, 52.00, 90, 0.03, 44.48, 'valid', NULL, 'pending', NULL, '2025-08-28 20:23:16', '2025-08-28 20:23:16');

-- --------------------------------------------------------

--
-- Table structure for table `fleet_vehicles`
--

CREATE TABLE `fleet_vehicles` (
  `id` int(11) NOT NULL,
  `vehicle_name` varchar(100) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `vehicle_type` enum('Truck','Van','Pickup','Car') DEFAULT NULL,
  `status` enum('Active','Under Maintenance','Inactive','Dispatched') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fleet_vehicles`
--

INSERT INTO `fleet_vehicles` (`id`, `vehicle_name`, `plate_number`, `vehicle_type`, `status`) VALUES
(1, 'Toyota Hilux', 'ABC-123', 'Car', 'Active'),
(2, 'Mitsubishi L300', 'XYZ-456', 'Van', 'Active'),
(3, 'Isuzu D-Max', 'LMN-789', 'Pickup', 'Under Maintenance'),
(4, 'Hyundai H100', 'JKL-321', 'Truck', 'Dispatched'),
(5, 'Ford Ranger', 'PQR-654', 'Car', 'Under Maintenance'),
(18, 'Gold Ship', '69420', 'Car', 'Dispatched'),
(20, 'Lightning Mcqueen', 'KACHOW', NULL, 'Active');

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
(2, 1, 'fuel', 'Needs Refill', '2025-08-28 17:10:25'),
(3, 1, 'maintenance', 'asdad', '2025-08-28 17:11:39'),
(4, 18, 'maintenance', 'Training Options restricted', '2025-08-28 18:24:34'),
(5, 2, 'maintenance', 'Nabuang ang driver, ninakaw ang catalytic converter bago nag resign', '2025-08-30 10:51:43'),
(6, 20, 'maintenance', 'Change Tires', '2025-08-30 10:56:03');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `item_type` enum('Raw Material','Finished Product','Equipment') DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_type` enum('Inbound','Outbound') DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed') DEFAULT NULL,
  `created_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL
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
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `contact_info` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tcao_audit_log`
--

CREATE TABLE `tcao_audit_log` (
  `id` int(11) NOT NULL,
  `cost_id` int(11) NOT NULL,
  `action` varchar(64) NOT NULL,
  `user` varchar(64) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tcao_audit_log`
--

INSERT INTO `tcao_audit_log` (`id`, `cost_id`, `action`, `user`, `timestamp`) VALUES
(3, 8, 'submitted', 'admin', '2025-08-30 19:11:09');

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
(8, 1, 12313213.00, 69585745.00, 420.00, 81899378.00, 'submitted', 'receipts_68b2dc4de9835.jpg', 'admin', '2025-08-30 11:11:09.000000');

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
(2, 5, '41aeeeecb90bb83b0a6b2f46c1d8e3c9cec66ae3e27a472be2f5dc7242204470', 'c872b1a5d8f484c5e37fe7be0753f974e53712ba2d75f667602585626e90101d', '::1::/64', '2025-09-04 21:36:26', '2025-08-29 03:36:26', '2025-08-29 03:36:26'),
(3, 1, '0c3a8bab846dc5664c359d622417e326d27328eec58f23484af6cefebb34c245', 'c872b1a5d8f484c5e37fe7be0753f974e53712ba2d75f667602585626e90101d', '::1::/64', '2025-09-04 22:19:18', '2025-08-29 04:19:18', '2025-08-29 04:19:18'),
(4, 6, 'f2e71081f67ea74277a5b132e638e58b2c62dc1f6b13ca0a7d9338e4f60fbea3', 'c872b1a5d8f484c5e37fe7be0753f974e53712ba2d75f667602585626e90101d', '::1::/64', '2025-09-04 22:34:54', '2025-08-29 04:34:54', '2025-08-29 04:34:54'),
(5, 1, 'cd1408be2ceffb639db9fc926da5db9548b4c0d40c967f0109726e3711ba2e46', 'c872b1a5d8f484c5e37fe7be0753f974e53712ba2d75f667602585626e90101d', '::1::/64', '2025-09-04 22:36:02', '2025-08-29 04:36:02', '2025-08-29 04:36:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `eid` varchar(100) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user','staff','manager') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `eid`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'S250701', 'admin', 'froilan.respicio2021@gmail.com', '$2y$10$jIX3O6u91NS/77OMfeFYieOa7n2G9MAKSdmYFmz3nMwudlo652XTS', 'admin', '2025-08-30 08:37:43'),
(6, 'M250706', 'gusion', 'cringey.ch@gmail.com', '$2y$10$EPiNvDrT8aKDFTmHIl5vnuq/UBbmmhOOe7Ov5NpqmETHoYzorZuc6', 'manager', '2025-08-30 09:51:12');

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
(19, 1, '2025-08-29 13:32:05', '2025-08-29', '2025-08-30', 'Balut Order', 'Kitchen', 'Supplier', 'Truck', NULL, 'Pending', NULL, NULL, NULL, NULL, ''),
(20, 1, '2025-08-30 17:16:34', '2025-08-13', '2025-08-31', '100kg chongke', 'Warehouse', 'Supplier', 'Pickup', NULL, 'Pending', NULL, NULL, NULL, NULL, '');

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
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `item_id` (`item_id`);

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
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tcao_audit_log`
--
ALTER TABLE `tcao_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cost_id` (`cost_id`);

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
  ADD UNIQUE KEY `eid` (`eid`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `dispatches`
--
ALTER TABLE `dispatches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `driver_trips`
--
ALTER TABLE `driver_trips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fleet_vehicles`
--
ALTER TABLE `fleet_vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `fleet_vehicle_logs`
--
ALTER TABLE `fleet_vehicle_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
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
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tcao_audit_log`
--
ALTER TABLE `tcao_audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transport_costs`
--
ALTER TABLE `transport_costs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `trusted_devices`
--
ALTER TABLE `trusted_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `vehicle_requests`
--
ALTER TABLE `vehicle_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

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
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tcao_audit_log`
--
ALTER TABLE `tcao_audit_log`
  ADD CONSTRAINT `tcao_audit_log_ibfk_1` FOREIGN KEY (`cost_id`) REFERENCES `transport_costs` (`id`) ON DELETE CASCADE;

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
