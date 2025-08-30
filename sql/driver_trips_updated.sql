-- Updated driver_trips table with additional fields
CREATE TABLE IF NOT EXISTS driver_trips (
    id INT PRIMARY KEY AUTO_INCREMENT,
    driver_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    trip_date DATE NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME,
    distance_traveled DECIMAL(10,2),
    fuel_consumed DECIMAL(10,2),
    idle_time INT, -- in minutes
    average_speed DECIMAL(10,2),
    performance_score DECIMAL(5,2),
    validation_status ENUM('pending', 'valid', 'invalid') DEFAULT 'pending',
    validation_message TEXT,
    supervisor_review_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    supervisor_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (vehicle_id) REFERENCES fleet_vehicles(id)
);
