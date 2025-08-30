-- SQL to create the vehicle_requests table
CREATE TABLE IF NOT EXISTS vehicle_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    request_date DATETIME NOT NULL,
    purpose TEXT,
    origin VARCHAR(255),
    destination VARCHAR(255),
    requested_vehicle_type VARCHAR(100),
    requested_driver_id INT,
    status ENUM('Pending', 'Approved', 'Denied', 'Dispatched', 'Completed') DEFAULT 'Pending',
    approved_by INT,
    approved_at DATETIME,
    dispatched_at DATETIME,
    completed_at DATETIME,
    notes TEXT,
    -- Add FOREIGN KEYs as needed
    CONSTRAINT fk_vehicle_requests_requested_driver
    FOREIGN KEY (requested_driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    CONSTRAINT fk_vehicle_requests_approved_by
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_vehicle_requests_requester
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE
);
