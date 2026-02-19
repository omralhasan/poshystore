-- Database Setup for Poshy Lifestyle E-Commerce Backend
-- Run with: mysql -u root -p < setup_ecommerce.sql

-- Create or use existing database
CREATE DATABASE IF NOT EXISTS poshy_lifestyle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE poshy_lifestyle;

-- Products table structure (if not exists)
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_en VARCHAR(255) NOT NULL,
    name_ar VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 3) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image_url VARCHAR(500),
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category_id),
    INDEX idx_price (price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cart table structure
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table structure
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 3) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample products (optional - for testing)
INSERT IGNORE INTO products (id, name_en, name_ar, description, price, stock, category_id) VALUES
(1, 'Luxury Handbag', 'حقيبة يد فاخرة', 'Premium leather handbag with gold accents', 125.500, 15, 1),
(2, 'Designer Sunglasses', 'نظارات شمسية مصممة', 'UV protection designer sunglasses', 85.250, 30, 2),
(3, 'Silk Scarf', 'وشاح حريري', 'Hand-woven silk scarf', 45.000, 50, 3);

SELECT 'Database setup complete!' as Status;
