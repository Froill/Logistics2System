-- Create table for logging receipt duplicate checks
CREATE TABLE receipt_duplicate_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_duplicate TINYINT(1) NOT NULL DEFAULT 0,
    max_similarity DECIMAL(5,2) DEFAULT 0,
    check_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_duplicate (is_duplicate, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit log for receipt duplicate detection attempts';
