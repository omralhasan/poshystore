-- FIFO Cost Pricing System: product_batches table
-- Each row = a separate purchase batch with its own cost_price
-- When products are sold, quantity is consumed from oldest batches first

CREATE TABLE IF NOT EXISTS product_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity_added INT NOT NULL COMMENT 'Quantity received in this batch',
    quantity_remaining INT NOT NULL COMMENT 'Quantity still available for sale (consumed FIFO)',
    cost_price DECIMAL(10, 3) NOT NULL COMMENT 'Per-unit cost of this batch',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_product_remaining (product_id, quantity_remaining)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
