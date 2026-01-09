-- Migration: add vehicle_documents and vehicle_insurance tables

CREATE TABLE IF NOT EXISTS `vehicle_documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` INT NOT NULL,
  `doc_type` VARCHAR(100) DEFAULT NULL,
  `doc_name` VARCHAR(255) DEFAULT NULL,
  `file_path` VARCHAR(500) DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `uploaded_by` INT DEFAULT NULL,
  `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (`vehicle_id`),
  FOREIGN KEY (`vehicle_id`) REFERENCES `fleet_vehicles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `vehicle_insurance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` INT NOT NULL,
  `insurer` VARCHAR(255) DEFAULT NULL,
  `policy_number` VARCHAR(255) DEFAULT NULL,
  `coverage_type` VARCHAR(100) DEFAULT NULL,
  `coverage_start` DATE DEFAULT NULL,
  `coverage_end` DATE DEFAULT NULL,
  `premium` DECIMAL(12,2) DEFAULT NULL,
  `document_path` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (`vehicle_id`),
  FOREIGN KEY (`vehicle_id`) REFERENCES `fleet_vehicles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
