-- Guest Orders Support
-- Allows users to place orders without creating an account

-- 1. Guest cart using session ID
CREATE TABLE IF NOT EXISTS guest_cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(128) NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    selected_options JSON DEFAULT NULL COMMENT 'Selected variant options as JSON',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Allow orders without user_id (guest orders)
ALTER TABLE orders
MODIFY COLUMN user_id INT DEFAULT NULL,
ADD COLUMN guest_name VARCHAR(255) DEFAULT NULL COMMENT 'Guest customer name' AFTER user_id,
ADD COLUMN guest_email VARCHAR(255) DEFAULT NULL COMMENT 'Guest customer email' AFTER guest_name,
ADD COLUMN is_guest TINYINT(1) DEFAULT 0 COMMENT 'Whether this is a guest order' AFTER order_type;

-- 3. Cleanup old guest carts (run periodically)
-- DELETE FROM guest_cart WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
