-- SQL to create the fleet_vehicle_logs table for fleet management system
CREATE TABLE IF NOT EXISTS fleet_vehicle_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    log_type ENUM('maintenance', 'fuel') NOT NULL,
    details TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (vehicle_id) REFERENCES fleet_vehicles(id) ON DELETE CASCADE
);
