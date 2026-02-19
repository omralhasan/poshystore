-- Create coupons table for discount codes
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    min_purchase DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    times_used INT DEFAULT 0,
    valid_from DATETIME DEFAULT CURRENT_TIMESTAMP,
    valid_until DATETIME DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    INDEX idx_valid (valid_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample coupons
INSERT INTO coupons (code, discount_type, discount_value, min_purchase, max_discount, usage_limit, is_active, valid_until) VALUES
('WELCOME10', 'percentage', 10.00, 50.00, 20.00, 100, 1, DATE_ADD(NOW(), INTERVAL 6 MONTH)),
('SAVE20', 'percentage', 20.00, 100.00, 50.00, 50, 1, DATE_ADD(NOW(), INTERVAL 3 MONTH)),
('FLAT15', 'fixed', 15.00, 75.00, NULL, NULL, 1, DATE_ADD(NOW(), INTERVAL 6 MONTH));
