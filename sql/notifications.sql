-- SQL to create the notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    -- Add FOREIGN KEY(user_id) REFERENCES users(id) if users table exists
);
