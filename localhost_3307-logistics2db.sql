-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Aug 13, 2025 at 03:46 PM
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
-- Database: `logistics2_db`
--
CREATE DATABASE IF NOT EXISTS `logistics2_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `logistics2_db`;

-- --------------------------------------------------------

--
-- Table structure for table `driver_trips`
--

CREATE TABLE `driver_trips` (
  `id` int(11) NOT NULL,
  `driver_name` varchar(100) NOT NULL,
  `trip_date` date DEFAULT NULL,
  `performance_score` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver_trips`
--

INSERT INTO `driver_trips` (`id`, `driver_name`, `trip_date`, `performance_score`, `remarks`) VALUES
(1, 'John Doe', '2025-08-01', 85, 'Good performance'),
(2, 'Jane Smith', '2025-08-02', 90, 'Excellent'),
(3, 'Michael Cruz', '2025-08-03', 70, 'Needs improvement'),
(4, 'Anna Reyes', '2025-08-04', 95, 'Outstanding'),
(5, 'Chris Lee', '2025-08-05', 80, 'Satisfactory');

-- --------------------------------------------------------

--
-- Table structure for table `fleet_vehicles`
--

CREATE TABLE `fleet_vehicles` (
  `id` int(11) NOT NULL,
  `vehicle_name` varchar(100) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `status` enum('Active','Under Maintenance','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fleet_vehicles`
--

INSERT INTO `fleet_vehicles` (`id`, `vehicle_name`, `plate_number`, `status`) VALUES
(1, 'Toyota Hilux', 'ABC-123', 'Active'),
(2, 'Mitsubishi L300', 'XYZ-456', 'Inactive'),
(3, 'Isuzu D-Max', 'LMN-789', 'Under Maintenance'),
(4, 'Hyundai H100', 'JKL-321', 'Active'),
(5, 'Ford Ranger', 'PQR-654', 'Under Maintenance');

-- --------------------------------------------------------

--
-- Table structure for table `transport_costs`
--

CREATE TABLE `transport_costs` (
  `id` int(11) NOT NULL,
  `trip_id` varchar(50) NOT NULL,
  `fuel_cost` decimal(10,2) DEFAULT NULL,
  `toll_fees` decimal(10,2) DEFAULT NULL,
  `other_expenses` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transport_costs`
--

INSERT INTO `transport_costs` (`id`, `trip_id`, `fuel_cost`, `toll_fees`, `other_expenses`, `total_cost`) VALUES
(1, '1', 3500.00, 500.00, 200.00, 4200.00),
(2, '2', 3000.00, 450.00, 150.00, 3600.00),
(3, '3', 4000.00, 550.00, 300.00, 4850.00),
(4, '4', 2500.00, 300.00, 100.00, 2900.00),
(5, '5', 3700.00, 600.00, 250.00, 4550.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', 'password', 'admin'),
(4, 'admin1', '$2y$10$/c90/0KTGJmypaf1w42wX.j7KXIhdLdWV9jgbnrbJJOX6FGpm3F2K', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_routes`
--

CREATE TABLE `vehicle_routes` (
  `id` int(11) NOT NULL,
  `route_name` varchar(100) NOT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `dispatch_date` date DEFAULT NULL,
  `status` enum('Planned','Dispatched','Completed') DEFAULT 'Planned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_routes`
--

INSERT INTO `vehicle_routes` (`id`, `route_name`, `vehicle_id`, `dispatch_date`, `status`) VALUES
(2, 'Route A', 1, '2025-08-06', 'Planned'),
(3, 'Route B', 2, '2025-08-06', 'Completed'),
(4, 'Route C', 3, '2025-08-06', 'Completed'),
(5, 'Route D', 4, '2025-08-06', 'Dispatched'),
(6, 'Route E', 5, '2025-08-06', 'Planned');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `driver_trips`
--
ALTER TABLE `driver_trips`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fleet_vehicles`
--
ALTER TABLE `fleet_vehicles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transport_costs`
--
ALTER TABLE `transport_costs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `vehicle_routes`
--
ALTER TABLE `vehicle_routes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `driver_trips`
--
ALTER TABLE `driver_trips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `fleet_vehicles`
--
ALTER TABLE `fleet_vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transport_costs`
--
ALTER TABLE `transport_costs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vehicle_routes`
--
ALTER TABLE `vehicle_routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `vehicle_routes`
--
ALTER TABLE `vehicle_routes`
  ADD CONSTRAINT `vehicle_routes_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `fleet_vehicles` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
