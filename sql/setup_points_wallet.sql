-- Points and Wallet System Setup for Poshy Lifestyle E-Commerce
-- This adds loyalty points and wallet functionality
-- Run with: mysql -u poshy_user -p poshy_lifestyle < setup_points_wallet.sql

USE poshy_lifestyle;

-- Add points and wallet balance columns to users table (if they don't exist)
-- Using procedure to check existence first
DELIMITER $$

CREATE PROCEDURE AddPointsColumns()
BEGIN
    -- Check and add points column
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = 'poshy_lifestyle' 
        AND TABLE_NAME = 'users' 
        AND COLUMN_NAME = 'points'
    ) THEN
        ALTER TABLE users ADD COLUMN points INT DEFAULT 0 COMMENT 'Loyalty points earned from purchases';
    END IF;
    
    -- Check and add wallet_balance column
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = 'poshy_lifestyle' 
        AND TABLE_NAME = 'users' 
        AND COLUMN_NAME = 'wallet_balance'
    ) THEN
        ALTER TABLE users ADD COLUMN wallet_balance DECIMAL(10, 3) DEFAULT 0.000 COMMENT 'Wallet balance in JOD';
    END IF;
END$$

DELIMITER ;

CALL AddPointsColumns();
DROP PROCEDURE AddPointsColumns;

-- Create points_transactions table to track point earning and spending
CREATE TABLE IF NOT EXISTS points_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points_change INT NOT NULL COMMENT 'Positive for earning, negative for spending',
    transaction_type ENUM('earned_purchase', 'converted_to_wallet', 'admin_adjustment', 'bonus', 'expired') NOT NULL,
    reference_id INT DEFAULT NULL COMMENT 'Related order_id or transaction_id',
    description VARCHAR(500) DEFAULT NULL,
    points_before INT NOT NULL COMMENT 'Points balance before this transaction',
    points_after INT NOT NULL COMMENT 'Points balance after this transaction',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (transaction_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create wallet_transactions table to track wallet balance changes
CREATE TABLE IF NOT EXISTS wallet_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10, 3) NOT NULL COMMENT 'Amount in JOD - positive for credit, negative for debit',
    transaction_type ENUM('points_conversion', 'order_payment', 'refund', 'admin_adjustment', 'bonus') NOT NULL,
    reference_id INT DEFAULT NULL COMMENT 'Related order_id or points_transaction_id',
    description VARCHAR(500) DEFAULT NULL,
    balance_before DECIMAL(10, 3) NOT NULL COMMENT 'Wallet balance before transaction',
    balance_after DECIMAL(10, 3) NOT NULL COMMENT 'Wallet balance after transaction',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (transaction_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create points_settings table for configurable point system rules
CREATE TABLE IF NOT EXISTS points_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value VARCHAR(255) NOT NULL,
    description VARCHAR(500) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default points settings
INSERT INTO points_settings (setting_key, setting_value, description) VALUES
('points_per_jod', '10', 'Points earned per 1 JOD spent'),
('points_to_jod_rate', '100', 'Points needed to convert to 1 JOD'),
('minimum_conversion_points', '100', 'Minimum points required for conversion'),
('points_expiry_days', '365', 'Days until points expire (0 = never expire)'),
('enable_points_system', '1', 'Enable/disable the points system (1=enabled, 0=disabled)')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Update existing users to have 0 points and wallet balance if columns were just added
UPDATE users SET points = IFNULL(points, 0), wallet_balance = IFNULL(wallet_balance, 0.000);

SELECT 'Points and Wallet system setup complete!' as Status;
SELECT CONCAT('Default: Earn ', setting_value, ' points per 1 JOD spent') as Info 
FROM points_settings WHERE setting_key = 'points_per_jod';
SELECT CONCAT('Default: Convert ', setting_value, ' points to 1 JOD') as Info 
FROM points_settings WHERE setting_key = 'points_to_jod_rate';
