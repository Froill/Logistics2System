ALTER TABLE `fleet_vehicles`
  ADD COLUMN `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN `archived_at` DATETIME NULL DEFAULT NULL,
  ADD COLUMN `archived_by` INT NULL DEFAULT NULL;

CREATE INDEX `idx_fleet_vehicles_archived` ON `fleet_vehicles` (`is_archived`);
