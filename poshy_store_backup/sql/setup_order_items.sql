-- Order Items Table Setup
-- This table stores individual line items for each order
-- Run with: mysql -u root -p poshy_lifestyle < setup_order_items.sql

USE poshy_lifestyle;

-- Create order_items table if it doesn't exist
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name_en VARCHAR(255) NOT NULL,
    product_name_ar VARCHAR(255),
    quantity INT NOT NULL DEFAULT 1,
    price_per_item DECIMAL(10, 3) NOT NULL,
    subtotal DECIMAL(10, 3) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Order items table setup complete!' as Status;
