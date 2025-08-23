CREATE TABLE trusted_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_token CHAR(64) NOT NULL,
    ua_hash CHAR(64) NOT NULL,
    ip_net VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    last_seen TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uniq_user_device (user_id, device_token),
    INDEX idx_lookup (user_id, device_token, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;