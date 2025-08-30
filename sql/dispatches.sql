CREATE TABLE IF NOT EXISTS dispatches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    driver_id INT NOT NULL,
    officer_id INT NOT NULL,
    dispatch_date DATETIME NOT NULL,
    return_date DATETIME,
    status ENUM('Ongoing', 'Completed', 'Cancelled') DEFAULT 'Ongoing',
    origin VARCHAR(255),
    destination VARCHAR(255),
    purpose TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES vehicle_requests(id),
    FOREIGN KEY (vehicle_id) REFERENCES fleet_vehicles(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id)
);
